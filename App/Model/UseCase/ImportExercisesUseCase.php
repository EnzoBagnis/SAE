<?php

namespace App\Model\UseCase;

use App\Model\ExerciseRepository;

/**
 * ImportExercisesUseCase
 * Handles the business logic for importing exercises from a JSON payload
 * into the `exercises` table.
 *
 * Expected JSON structure per exercise:
 * {
 *   "exo_name": "...",         // or "exercice_name" / "name" / "title"
 *   "funcname": "...",         // optional
 *   "description": "...",      // optional
 *   "difficulte": "facile"     // optional
 * }
 */
class ImportExercisesUseCase
{
    private ExerciseRepository $exerciseRepository;

    /**
     * @param ExerciseRepository $exerciseRepository Exercise repository
     */
    public function __construct(ExerciseRepository $exerciseRepository)
    {
        $this->exerciseRepository = $exerciseRepository;
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
                $exerciceName = trim(
                    $item['exo_name']
                    ?? $item['exercice_name']
                    ?? $item['name']
                    ?? $item['title']
                    ?? ''
                );

                if ($exerciceName === '') {
                    throw new \InvalidArgumentException("Nom de l'exercice manquant");
                }

                // Check if the exercise already exists for this resource
                $existing = $this->exerciseRepository->findByRessourceIdAndName($ressourceId, $exerciceName);

                if ($existing !== null) {
                    // Exercise already exists, count as updated
                    $updated++;
                } else {
                    // Insert new exercise
                    $this->exerciseRepository->insertExercice($ressourceId, $exerciceName, '', '');
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
