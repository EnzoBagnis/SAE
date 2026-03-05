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
                    // Try to resolve by name
                    $exerciceName = trim(
                        $item['exercice_name']
                        ?? $item['exercise_name']
                        ?? $item['exo_name']
                        ?? $item['name']
                        ?? ''
                    );

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

                // 3. Normalize AES fields (may be already-encoded JSON or raw values)
                $rows[] = [
                    'exercice_id' => $exerciceId,
                    'user_id'     => (string) ($item['user_id'] ?? $item['user'] ?? $item['student'] ?? $item['eleve'] ?? ''),
                    'correct'     => $correct,
                    'eval_set'    => $this->normalizeJsonField($item['eval_set'] ?? null),
                    'upload'      => (string) ($item['upload'] ?? $item['code'] ?? ''),
                    'aes0'        => $this->normalizeJsonField($item['aes0'] ?? null),
                    'aes1'        => $this->normalizeJsonField($item['aes1'] ?? null),
                    'aes2'        => $this->normalizeJsonField($item['aes2'] ?? null),
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
     * Normalize a field that should be stored as a JSON string in the database.
     * Handles arrays, objects, already-encoded JSON strings, and plain strings.
     *
     * @param array|string|int|float|bool|null $value Raw value from the JSON payload
     * @return string Normalized string value (JSON-encoded if needed)
     */
    private function normalizeJsonField($value): string
    {
        if ($value === null) {
            return '';
        }

        // Arrays / objects are re-encoded to JSON string
        if (!is_string($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        // If the string looks like a JSON object or array and is valid JSON, keep as-is
        if (
            (str_starts_with($value, '{') && str_ends_with($value, '}')) ||
            (str_starts_with($value, '[') && str_ends_with($value, ']'))
        ) {
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        return $value;
    }
}
