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
        $skipped  = 0;  // doublons dans le JSON lui-même
        $errors   = [];

        // Cache mémoire des noms déjà traités dans cet import (évite faux "mis à jour")
        $seenNames  = [];   // nom résolu => exercice_id
        $seenHashes = [];   // hash       => exercice_id

        foreach ($exercises as $index => $item) {
            try {
                // L'identifiant unique est l'exo_name (hash MD5 du TP source)
                // Plusieurs exercices peuvent avoir le même funcname mais des hashs différents (exercices de TPs différents)
                $rawName = trim(
                    $item['exercice_name']
                    ?? $item['exercise_name']
                    ?? $item['name']
                    ?? $item['title']
                    ?? $item['hash']
                    ?? ''
                );

                // Garder le hash original si c'est bien un hash MD5
                $originalHash = $this->isMd5Hash($rawName) ? $rawName : null;

                // Résoudre le vrai nom lisible depuis funcname ou upload
                if ($originalHash !== null) {
                    if (!empty($item['funcname'])) {
                        $rawName = trim($item['funcname']);
                    } elseif (!empty($item['upload'])) {
                        $funcName = $this->extractPythonFuncName((string) $item['upload']);
                        if ($funcName !== null) {
                            $rawName = $funcName;
                        }
                    }
                } elseif (!empty($item['funcname']) && $rawName === '') {
                    $rawName = trim($item['funcname']);
                }

                $exerciceName = mb_substr($rawName, 0, 80);

                if ($exerciceName === '') {
                    throw new \InvalidArgumentException("Nom de l'exercice manquant");
                }

                $extention = mb_substr($item['extention'] ?? $item['extension'] ?? 'py', 0, 20);
                $date      = $item['date'] ?? date('Y-m-d');

                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $date = date('Y-m-d');
                }

                // Éviter les doublons de hash dans ce même batch (même hash = même exercice)
                if ($originalHash !== null && isset($seenHashes[$originalHash])) {
                    $skipped++;
                    continue;
                }

                // 1. Chercher par hash en BDD (priorité absolue — le hash est l'identifiant unique)
                $existing = null;
                if ($originalHash !== null) {
                    $existing = $this->exerciseRepository->findByRessourceIdAndHash($ressourceId, $originalHash);
                }

                // 2. Si pas de hash (exercice sans exo_name), chercher par nom
                if ($existing === null && $originalHash === null) {
                    $existing = $this->exerciseRepository->findByRessourceIdAndName($ressourceId, $exerciceName);
                    // Doublon de nom dans ce batch sans hash
                    $nameLower = mb_strtolower($exerciceName);
                    if (isset($seenNames[$nameLower])) {
                        $skipped++;
                        continue;
                    }
                }

                if ($existing !== null) {
                    $this->exerciseRepository->updateExtentionAndDate(
                        $existing->getExerciseId(),
                        $extention,
                        $date
                    );
                    // Mettre à jour le nom si le nom stocké est encore un hash
                    if ($this->isMd5Hash($existing->getExoName()) && !$this->isMd5Hash($exerciceName)) {
                        $this->exerciseRepository->updateName($existing->getExerciseId(), $exerciceName);
                    }
                    if ($originalHash !== null) {
                        $seenHashes[$originalHash] = $existing->getExerciseId();
                    }
                    $updated++;
                } else {
                    $newId = $this->exerciseRepository->insertExercice(
                        $ressourceId, $exerciceName, $extention, $date, $originalHash
                    );
                    if ($originalHash !== null) {
                        $seenHashes[$originalHash] = $newId;
                    } else {
                        $nameLower = mb_strtolower($exerciceName);
                        $seenNames[$nameLower] = $newId;
                    }
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
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }
}
