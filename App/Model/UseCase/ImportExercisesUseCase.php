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
                    ?? $item['name']
                    ?? $item['title']
                    ?? $item['hash']
                    ?? ''
                );

                // If the name looks like a MD5 hash, try to extract the function name
                // from the uploaded Python code instead
                if ($this->isMd5Hash($rawName) && !empty($item['upload'])) {
                    $funcName = $this->extractPythonFuncName((string) $item['upload']);
                    if ($funcName !== null) {
                        $rawName = $funcName;
                    }
                }

                $exerciceName = mb_substr($rawName, 0, 20);

                if ($exerciceName === '') {
                    throw new \InvalidArgumentException("Nom de l'exercice manquant");
                }

                $extention = mb_substr($item['extention'] ?? $item['extension'] ?? 'py', 0, 20);
                $date      = $item['date'] ?? date('Y-m-d');

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $date = date('Y-m-d');
                }

                // Check if the exercise already exists for this resource
                $existing = $this->exerciseRepository->findByRessourceIdAndName($ressourceId, $exerciceName);

                if ($existing !== null) {
                    // Update extension and date if exercise already exists
                    $this->exerciseRepository->updateExtentionAndDate(
                        $existing->getExerciseId(),
                        $extention,
                        $date
                    );
                    $updated++;
                } else {
                    // Insert new exercise
                    $this->exerciseRepository->insertExercice($ressourceId, $exerciceName, $extention, $date);
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
