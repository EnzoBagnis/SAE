<?php

namespace App\Model\UseCase;

use App\Model\UseCase\Ports\AttemptBulkInserterPort;
use App\Model\UseCase\Ports\ExerciseLookupPort;

/**
 * ImportAttemptsUseCase.
 *
 * Handles the import of attempts into the `attempts` table.
 * Depends on {@see AttemptBulkInserterPort} for persistence and
 * {@see ExerciseLookupPort} for exercise resolution,
 * following the Dependency Inversion Principle.
 *
 * Expected attempt data format:
 * {
 *   "exercice_id":   1,          // or "exercice_name" / "exercise_name" / "name"
 *   "user":          "etudiant1",
 *   "correct":       1,
 *   "eval_set":      "...",
 *   "upload":        "...",
 *   "aes0":          "...",
 *   "aes1":          "...",
 *   "aes2":          "..."
 * }
 */
class ImportAttemptsUseCase
{
    private AttemptBulkInserterPort $attemptRepository;
    private ExerciseLookupPort $exerciseRepository;

    /**
     * Constructor.
     *
     * @param AttemptBulkInserterPort $attemptRepository  Port for bulk-inserting attempts.
     * @param ExerciseLookupPort      $exerciseRepository Port for exercise name-based lookup.
     */
    public function __construct(
        AttemptBulkInserterPort $attemptRepository,
        ExerciseLookupPort $exerciseRepository
    ) {
        $this->attemptRepository  = $attemptRepository;
        $this->exerciseRepository = $exerciseRepository;
    }

    /**
     * Execute the import of a list of attempts, optionally scoped to a resource.
     *
     * @param array<array<string,mixed>> $attempts    List of attempt data arrays
     * @param int|null                   $ressourceId Optional resource ID to scope exercise lookup
     * @return array{inserted:int, errors:list<string>} Import result
     */
    public function execute(array $attempts, ?int $ressourceId): array
    {
        // Build a cache: exercice_name -> exercice_id (within the given resource if provided)
        $exerciceCache = [];

        $rows   = [];
        $errors = [];

        foreach ($attempts as $index => $item) {
            try {
                // 1. Resolve exercice_id
                $exerciceId = isset($item['exercice_id']) ? (int) $item['exercice_id'] : null;

                if (!$exerciceId) {
                    // Try to resolve by name (truncated to varchar(20))
                    $exerciceName = mb_substr(trim(
                        $item['exercice_name']
                        ?? $item['exercise_name']
                        ?? $item['exo_name']
                        ?? $item['name']
                        ?? ''
                    ), 0, 20);

                    if ($exerciceName === '') {
                        throw new \InvalidArgumentException("exercice_id ou exercice_name manquant");
                    }

                    $cacheKey = $exerciceName . '_' . ($ressourceId ?? 'global');

                    if (!isset($exerciceCache[$cacheKey])) {
                        $exercise = $ressourceId !== null
                            ? $this->exerciseRepository->findByRessourceIdAndName($ressourceId, $exerciceName)
                            : $this->exerciseRepository->findByName($exerciceName);

                        if ($exercise === null) {
                            throw new \RuntimeException(
                                "Exercice introuvable : \"$exerciceName\""
                                . ($ressourceId !== null ? " (ressource $ressourceId)" : '')
                            );
                        }

                        $exerciceCache[$cacheKey] = $exercise->getExerciseId();
                    }

                    $exerciceId = $exerciceCache[$cacheKey];
                }

                // 2. Normalize the correct flag (handles booleans, strings, integers)
                $correct = $item['correct'] ?? false;
                if (is_string($correct)) {
                    $correct = filter_var($correct, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                } else {
                    $correct = $correct ? 1 : 0;
                }

                // 3. Stocker les champs en respectant les contraintes varchar(20) de la BD
                $rows[] = [
                    'exercice_id' => $exerciceId,
                    'user_id'     => mb_substr((string) ($item['user_id'] ?? $item['user'] ?? $item['student'] ?? $item['eleve'] ?? ''), 0, 20),
                    'correct'     => $correct,
                    'eval_set'    => mb_substr((string) ($item['eval_set'] ?? ''), 0, 20),
                    'upload'      => mb_substr((string) ($item['upload'] ?? $item['code'] ?? ''), 0, 20),
                    'aes0'        => mb_substr($this->normalizeScalarField($item['aes0'] ?? null), 0, 20),
                    'aes1'        => mb_substr($this->normalizeScalarField($item['aes1'] ?? null), 0, 20),
                    'aes2'        => mb_substr($this->normalizeScalarField($item['aes2'] ?? null), 0, 20),
                ];
            } catch (\Throwable $e) {
                $errors[] = "Tentative #$index: " . $e->getMessage();
                error_log('[ImportAttemptsUseCase] Error at index ' . $index . ': ' . $e->getMessage());
            }
        }

        // Bulk insert all valid rows
        $result = $this->attemptRepository->bulkInsert($rows);

        return [
            'inserted' => $result['inserted'],
            'errors'   => array_merge($errors, $result['errors']),
        ];
    }

    /**
     * Normalize a scalar field for storage in a varchar(20) column.
     * Converts booleans to '0'/'1', numeric values to string, arrays to empty string.
     *
     * @param mixed $value Raw value from the JSON payload
     * @return string Normalized string value, max 20 chars
     */
    private function normalizeScalarField(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            // Tableau/objet : prendre la première valeur scalaire ou vide
            return '';
        }
        return mb_substr((string) $value, 0, 20);
    }
}
