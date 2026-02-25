<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Exercise;

/**
 * Exercise Repository
 * Handles exercise data persistence against the `exercices` table.
 *
 * Schema: exercice_id (PK), ressource_id, exercice_name, extention, date
 * Attempts: attempts (attempt_id, exercice_id, user, correct, ...)
 */
class ExerciseRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName(): string
    {
        return 'exercices';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return Exercise::class;
    }

    /**
     * Find all exercises that have at least one attempt, optionally filtered by resource.
     *
     * @param int|null $resourceId Resource ID filter
     * @return Exercise[] Array of Exercise entities
     */
    public function findAll(?int $resourceId = null): array
    {
        if ($resourceId === null) {
            $query = "SELECT DISTINCT e.*
                      FROM exercices e
                      INNER JOIN attempts a ON e.exercice_id = a.exercice_id
                      ORDER BY e.exercice_name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
        } else {
            $query = "SELECT DISTINCT e.*
                      FROM exercices e
                      INNER JOIN attempts a ON e.exercice_id = a.exercice_id
                      WHERE e.ressource_id = :resource_id
                      ORDER BY e.exercice_name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['resource_id' => $resourceId]);
        }

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find an exercise by its primary key.
     *
     * @param int $exerciseId Exercise ID
     * @return Exercise|null Exercise entity or null
     */
    public function findById(int $exerciseId): ?Exercise
    {
        $stmt = $this->pdo->prepare("SELECT * FROM exercices WHERE exercice_id = :id");
        $stmt->execute(['id' => $exerciseId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find exercises belonging to a resource.
     *
     * @param int $resourceId Resource ID
     * @return Exercise[] Array of Exercise entities
     */
    public function findByResourceId(int $resourceId): array
    {
        $query = "SELECT * FROM exercices
                  WHERE ressource_id = :resource_id
                  ORDER BY exercice_name ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['resource_id' => $resourceId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find exercises by resource ID with attempt statistics.
     * Returns raw associative arrays with total_attempts, successful_attempts and success_rate.
     *
     * @param int $resourceId Resource ID
     * @return array<array{exercice_id:int, ressource_id:int, exercice_name:string,
     *                     extention:string, date:string,
     *                     total_attempts:int, successful_attempts:int, success_rate:float|null}> Stats rows
     */
    public function findByResourceIdWithStats(int $resourceId): array
    {
        $query = "SELECT e.exercice_id,
                         e.ressource_id,
                         e.exercice_name,
                         e.extention,
                         e.date,
                         COUNT(a.attempt_id)                             AS total_attempts,
                         SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END) AS successful_attempts
                  FROM exercices e
                  LEFT JOIN attempts a ON e.exercice_id = a.exercice_id
                  WHERE e.ressource_id = :resource_id
                  GROUP BY e.exercice_id, e.ressource_id, e.exercice_name, e.extention, e.date
                  ORDER BY e.exercice_name ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['resource_id' => $resourceId]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            $row['total_attempts']      = (int) $row['total_attempts'];
            $row['successful_attempts'] = (int) $row['successful_attempts'];
            $row['success_rate']        = $row['total_attempts'] > 0
                ? round(($row['successful_attempts'] / $row['total_attempts']) * 100, 1)
                : null;
        }

        return $results;
    }

    /**
     * Count exercises, optionally filtered by resource.
     *
     * @param int|null $resourceId Resource ID filter
     * @return int Exercise count
     */
    public function count(?int $resourceId = null): int
    {
        if ($resourceId === null) {
            $query = "SELECT COUNT(DISTINCT e.exercice_id)
                      FROM exercices e
                      INNER JOIN attempts a ON e.exercice_id = a.exercice_id";
            $stmt = $this->pdo->query($query);
        } else {
            $query = "SELECT COUNT(DISTINCT e.exercice_id)
                      FROM exercices e
                      INNER JOIN attempts a ON e.exercice_id = a.exercice_id
                      WHERE e.ressource_id = :resource_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['resource_id' => $resourceId]);
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Delete an exercise by ID.
     *
     * @param mixed $exerciceId Exercise ID
     * @return bool True if deleted
     */
    public function delete(mixed $exerciceId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM exercices WHERE exercice_id = :id");
        return $stmt->execute(['id' => (int) $exerciceId]);
    }

    /**
     * Hydrate an Exercise entity from a database row.
     *
     * @param array $data Database row
     * @return Exercise Hydrated entity
     */
    protected function hydrate(array $data): Exercise
    {
        $exercise = new Exercise();
        $exercise->setExerciseId($data['exercice_id']   ?? null);
        $exercise->setResourceId((int) ($data['ressource_id'] ?? 0));
        $exercise->setExoName($data['exercice_name']    ?? '');
        $exercise->setExtention($data['extention']      ?? null);
        $exercise->setDate($data['date']                ?? null);
        return $exercise;
    }
}

