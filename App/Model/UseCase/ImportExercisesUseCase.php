<?php

namespace App\Model\UseCase;

use App\Model\UseCase\Ports\ExerciseImporterPort;

/**
 * ImportExercisesUseCase.
 *
 * Handles the import of exercises into the `exercices` table.
 * Depends on {@see ExerciseImporterPort} for persistence,
 * following the Dependency Inversion Principle.
 *
 * Expected exercise data format:
 * {
 *   "exercice_name": "...",    // or "name" / "title"
 *   "extention":     "py",     // optional, defaults to "py"
 *   "date":          "2025-01-01"  // optional, defaults to today
 * }
 */
class ImportExercisesUseCase
{
    private ExerciseImporterPort $exerciseRepository;

    /**
     * Constructor.
     *
     * @param ExerciseImporterPort $exerciseRepository Port for exercise import persistence.
     */
    public function __construct(ExerciseImporterPort $exerciseRepository)
    {
        $this->exerciseRepository = $exerciseRepository;
    }

    /**
     * Extract the first function name from Python source code.
     * Returns null if no function definition is found.
     *
     * @param string $code Python source code
     * @return string|null Function name or null
     */
    private function extractPythonFuncName(string $code): ?string
    {
        if (preg_match('/def\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $code, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Determine whether a string looks like a MD5 hash (32 hex chars).
     *
     * @param string $name Name to test
     * @return bool
     */
    private function isMd5Hash(string $name): bool
    {
        return (bool) preg_match('/^[0-9a-f]{32}$/i', $name);
    }

    /**
     * Execute the import of a list of exercises for a given resource.
     *
     * @param int                        $ressourceId  Target resource ID
     * @param array<array<string,mixed>> $exercises    List of exercise data arrays
     * @return array{inserted:int, updated:int, errors:list<string>} Import result
     */
    public function execute(int $ressourceId, array $exercises): array
    {
        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        foreach ($exercises as $index => $item) {
            try {
                // Normalize the exercise name from various possible keys
                $rawName = trim(
                    $item['exercice_name']
                    ?? $item['exercise_name']
                    ?? $item['funcname']
                    ?? $item['name']
                    ?? $item['title']
                    ?? $item['hash']
                    ?? ''
                );

                // If the name looks like a MD5 hash, prefer funcname if present, then try upload
                if ($this->isMd5Hash($rawName)) {
                    if (!empty($item['funcname'])) {
                        $rawName = trim($item['funcname']);
                    } elseif (!empty($item['upload'])) {
                        $funcName = $this->extractPythonFuncName((string) $item['upload']);
                        if ($funcName !== null) {
                            $rawName = $funcName;
                        }
                    }
                }

                $exerciceName = mb_substr($rawName, 0, 80);

                if ($exerciceName === '') {
                    throw new \InvalidArgumentException("Nom de l'exercice manquant");
                }

                // Garder le hash original (avant résolution)
                $originalHash = null;
                $firstKey = trim(
                    $item['exercice_name']
                    ?? $item['exercise_name']
                    ?? $item['name']
                    ?? $item['title']
                    ?? $item['hash']
                    ?? ''
                );
                if ($this->isMd5Hash($firstKey)) {
                    $originalHash = $firstKey;
                }

                $extention = mb_substr($item['extention'] ?? $item['extension'] ?? 'py', 0, 20);
                $date      = $item['date'] ?? date('Y-m-d');

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $date = date('Y-m-d');
                }

                // 1. Chercher par nom exact
                $existing = $this->exerciseRepository->findByRessourceIdAndName($ressourceId, $exerciceName);

                // 2. Si pas trouvé par nom et qu'on a un hash, chercher par hash
                if ($existing === null && $originalHash !== null) {
                    $existing = $this->exerciseRepository->findByRessourceIdAndHash($ressourceId, $originalHash);
                }

                if ($existing !== null) {
                    // Mettre à jour extension, date et le nom lisible si on a résolu depuis un hash
                    $this->exerciseRepository->updateExtentionAndDate(
                        $existing->getExerciseId(),
                        $extention,
                        $date
                    );
                    // Si le nom stocké est un hash et qu'on a maintenant le vrai nom, mettre à jour
                    if ($originalHash !== null && $existing->getExoName() !== $exerciceName) {
                        $this->exerciseRepository->updateName($existing->getExerciseId(), $exerciceName);
                    }
                    $updated++;
                } else {
                    // Insérer avec le hash original pour référence future
                    $this->exerciseRepository->insertExercice($ressourceId, $exerciceName, $extention, $date, $originalHash);
                    $inserted++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Exercice #$index: " . $e->getMessage();
                error_log('[ImportExercisesUseCase] Error at index ' . $index . ': ' . $e->getMessage());
            }
        }

        return [
            'inserted' => $inserted,
            'updated'  => $updated,
            'errors'   => $errors,
        ];
    }
}
