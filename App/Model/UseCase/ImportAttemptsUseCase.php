<?php

namespace App\Model\UseCase;

use App\Model\AttemptRepository;
use App\Model\ExerciseRepository;

/**
 * ImportAttemptsUseCase
 * Handles the business logic for importing student attempts from a JSON payload
 * into the `attempts` table.
 *
 * Expected JSON structure per attempt:
 * {
 *   "exercise_id":   1,          // or "exo_name" / "exercice_name" / "name"
 *   "student_id":    1,          // or "user" / "student" / "eleve"
 *   "correct":       1,
 *   "submission_date": "2025-01-01 12:00:00",
 *   "extension":     "py",
 *   "eval_set":      "...",
 *   "upload":        "...",
 *   "aes0":          "...",
 *   "aes1":          "...",
 *   "aes2":          "..."
 * }
 */
class ImportAttemptsUseCase
{
    private AttemptRepository $attemptRepository;
    private ExerciseRepository $exerciseRepository;

    /**
     * @param AttemptRepository  $attemptRepository  Attempt repository
     * @param ExerciseRepository $exerciseRepository Exercise repository (for name-based lookup)
     */
    public function __construct(
        AttemptRepository $attemptRepository,
        ExerciseRepository $exerciseRepository
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
        $exerciseCache = [];

        $rows   = [];
        $errors = [];

        foreach ($attempts as $index => $item) {
            try {
                // 1. Resolve exercise_id
                $exerciseId = isset($item['exercise_id']) ? (int) $item['exercise_id'] : null;
                if (!$exerciseId && isset($item['exercice_id'])) {
                    $exerciseId = (int) $item['exercice_id'];
                }

                if (!$exerciseId) {
                    // Try to resolve by name
                    $exerciseName = trim(
                        $item['exo_name']
                        ?? $item['exercice_name']
                        ?? $item['exercise_name']
                        ?? $item['name']
                        ?? ''
                    );

                    if ($exerciseName === '') {
                        throw new \InvalidArgumentException("exercise_id ou exo_name manquant");
                    }

                    $cacheKey = $exerciseName . '_' . ($ressourceId ?? 'global');

                    if (!isset($exerciseCache[$cacheKey])) {
                        $exercise = $ressourceId !== null
                            ? $this->exerciseRepository->findByRessourceIdAndName($ressourceId, $exerciseName)
                            : $this->exerciseRepository->findByName($exerciseName);

                        if ($exercise === null) {
                            throw new \RuntimeException(
                                "Exercice introuvable : \"$exerciseName\""
                                . ($ressourceId !== null ? " (ressource $ressourceId)" : '')
                            );
                        }

                        $exerciseCache[$cacheKey] = $exercise->getExerciseId();
                    }

                    $exerciseId = $exerciseCache[$cacheKey];
                }

                // 2. Resolve student_id
                $studentId = (int) ($item['student_id'] ?? $item['user'] ?? $item['student'] ?? $item['eleve'] ?? 0);

                if ($studentId <= 0) {
                    throw new \InvalidArgumentException("student_id manquant ou invalide");
                }

                // 3. Normalize the correct flag (handles booleans, strings, integers)
                $correct = $item['correct'] ?? false;
                if (is_string($correct)) {
                    $correct = filter_var($correct, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                } else {
                    $correct = $correct ? 1 : 0;
                }

                // 4. Build row for bulk insert
                $rows[] = [
                    'student_id'      => $studentId,
                    'exercise_id'     => $exerciseId,
                    'submission_date' => (string) ($item['submission_date'] ?? $item['date'] ?? date('Y-m-d H:i:s')),
                    'extension'       => (string) ($item['extension'] ?? $item['extention'] ?? ''),
                    'correct'         => $correct,
                    'upload'          => (string) ($item['upload'] ?? $item['code'] ?? ''),
                    'eval_set'        => $this->normalizeJsonField($item['eval_set'] ?? null),
                    'aes0'            => $this->normalizeJsonField($item['aes0'] ?? null),
                    'aes1'            => $this->normalizeJsonField($item['aes1'] ?? null),
                    'aes2'            => $this->normalizeJsonField($item['aes2'] ?? null),
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

