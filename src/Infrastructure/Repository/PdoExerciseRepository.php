<?php

namespace Infrastructure\Repository;

use Domain\ExerciseManagement\Entity\Exercise;
use Domain\ExerciseManagement\Repository\ExerciseRepositoryInterface;
use PDO;

/**
 * PDO Exercise Repository Implementation
 * Handles exercise data persistence using PDO
 */
class PdoExerciseRepository implements ExerciseRepositoryInterface
{
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(?int $resourceId = null): array
    {
        if ($resourceId === null) {
            $query = "SELECT DISTINCT e.* 
                     FROM exercises e 
                     INNER JOIN attempts a ON e.exercise_id = a.exercise_id 
                     ORDER BY e.exo_name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
        } else {
            $query = "SELECT DISTINCT e.* 
                     FROM exercises e 
                     INNER JOIN attempts a ON e.exercise_id = a.exercise_id 
                     WHERE e.resource_id = :resource_id 
                     ORDER BY e.exo_name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
            $stmt->execute();
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return $this->hydrateExercise($row);
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $exerciseId): ?Exercise
    {
        $query = "SELECT * FROM exercises WHERE exercise_id = :exercise_id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':exercise_id', $exerciseId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateExercise($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findByResourceId(int $resourceId): array
    {
        $query = "SELECT 
                    e.*,
                    COUNT(DISTINCT a.attempt_id) as attempts_count,
                    COUNT(DISTINCT a.student_id) as students_count,
                    ROUND(
                        (COUNT(CASE WHEN a.correct = 1 THEN 1 END) * 100.0 / 
                        NULLIF(COUNT(a.attempt_id), 0)),
                        2
                    ) as success_rate,
                    ROUND(
                        (COUNT(DISTINCT CASE WHEN a.correct = 1 THEN a.student_id END) * 100.0 / 
                        NULLIF(COUNT(DISTINCT a.student_id), 0)),
                        2
                    ) as completion_rate
                 FROM exercises e
                 LEFT JOIN attempts a ON e.exercise_id = a.exercise_id
                 WHERE e.resource_id = :resource_id
                 GROUP BY e.exercise_id
                 ORDER BY e.exo_name ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            $exercise = $this->hydrateExercise($row);
            // Add statistics as properties
            $exercise->attempts_count = $row['attempts_count'] ?? 0;
            $exercise->students_count = $row['students_count'] ?? 0;
            $exercise->success_rate = $row['success_rate'] ?? 0;
            $exercise->completion_rate = $row['completion_rate'] ?? 0;
            return $exercise;
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function findByDatasetId(int $datasetId): array
    {
        $query = "SELECT e.*, GROUP_CONCAT(tc.test_case_id) as has_test_cases
                 FROM exercises e
                 LEFT JOIN test_cases tc ON e.exercise_id = tc.exercise_id
                 WHERE e.dataset_id = :dataset_id
                 GROUP BY e.exercise_id
                 ORDER BY e.exo_name";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':dataset_id', $datasetId, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return $this->hydrateExercise($row);
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function count(?int $resourceId = null): int
    {
        if ($resourceId === null) {
            $query = "SELECT COUNT(DISTINCT e.exercise_id) 
                     FROM exercises e 
                     INNER JOIN attempts a ON e.exercise_id = a.exercise_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
        } else {
            $query = "SELECT COUNT(DISTINCT e.exercise_id) 
                     FROM exercises e 
                     INNER JOIN attempts a ON e.exercise_id = a.exercise_id 
                     WHERE e.resource_id = :resource_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
            $stmt->execute();
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Hydrate exercise from database row
     *
     * @param array $row Database row
     * @return Exercise Exercise entity
     */
    private function hydrateExercise(array $row): Exercise
    {
        return new Exercise(
            $row['exercise_id'],
            $row['resource_id'],
            $row['exo_name'],
            $row['funcname'] ?? null,
            $row['solution'] ?? null,
            $row['description'] ?? null,
            $row['difficulte'] ?? null,
            $row['date_creation'] ?? '',
            isset($row['dataset_id']) ? (int)$row['dataset_id'] : null
        );
    }
}
