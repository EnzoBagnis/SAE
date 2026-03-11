<?php

namespace App\Model\UseCase;

use App\Model\UseCase\Ports\AttemptBulkInserterPort;
use App\Model\UseCase\Ports\ExerciseImporterPort;

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
    private ExerciseImporterPort $exerciseRepository;

    /**
     * Constructor.
     *
     * @param AttemptBulkInserterPort $attemptRepository  Port for bulk-inserting attempts.
     * @param ExerciseImporterPort    $exerciseRepository Port for exercise lookup and auto-creation.
     */
    public function __construct(
        AttemptBulkInserterPort $attemptRepository,
        ExerciseImporterPort $exerciseRepository
    ) {
        $this->attemptRepository  = $attemptRepository;
        $this->exerciseRepository = $exerciseRepository;
    }

    /**
     * Execute the import of a list of attempts, optionally scoped to a resource.
     * If an exercise referenced by name does not exist yet and a ressourceId is provided,
     * it will be created automatically (upsert behaviour).
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
                    // Try to resolve by name (truncated to varchar(80))
                    $rawName = trim(
                        $item['exercice_name']
                        ?? $item['exercise_name']
                        ?? $item['funcname']
                        ?? $item['hash']
                        ?? $item['name']
                        ?? ''
                    );

                    // If the name looks like a MD5 hash, prefer funcname then upload
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
                        throw new \InvalidArgumentException("exercice_id ou exercice_name manquant");
                    }

                    $cacheKey = $exerciceName . '_' . ($ressourceId ?? 'global');

                    if (!isset($exerciceCache[$cacheKey])) {
                        $exercise = $ressourceId !== null
                            ? $this->exerciseRepository->findByRessourceIdAndName($ressourceId, $exerciceName)
                            : $this->exerciseRepository->findByName($exerciceName);

                        if ($exercise === null) {
                            if ($ressourceId === null) {
                                throw new \RuntimeException(
                                    "Exercice introuvable : \"$exerciceName\" et resource_id manquant pour le créer"
                                );
                            }
                            // Auto-create the exercise so attempts are not lost
                            $extention = mb_substr($item['extension'] ?? $item['extention'] ?? 'py', 0, 20);
                            $date      = $item['date'] ?? date('Y-m-d');
                            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                                $date = date('Y-m-d');
                            }
                            $newId = $this->exerciseRepository->insertExercice(
                                $ressourceId,
                                $exerciceName,
                                $extention,
                                $date
                            );
                            $exerciceCache[$cacheKey] = $newId;
                        } else {
                            $exerciceCache[$cacheKey] = $exercise->getExerciseId();
                        }
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
                    'upload'      => (string) ($item['upload'] ?? $item['code'] ?? ''),
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
     * Extract the first function name from Python source code.
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
