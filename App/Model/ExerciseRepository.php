<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Exercise;

/**
 * Exercise Repository
 * Handles exercise data persistence against the `exercises` table.
 *
 * Schema: exercise_id (PK), resource_id, exo_name, funcname, solution, description, difficulte, date_creation
 */
class ExerciseRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName(): string
    {
        return 'exercises';
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
    public function findAllWithAttempts(?int $resourceId = null): array
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
        $stmt = $this->pdo->prepare("SELECT * FROM exercises WHERE exercise_id = :id");
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
        $query = "SELECT * FROM exercises
                  WHERE resource_id = :resource_id
                  ORDER BY exo_name ASC";

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
        $query = "SELECT e.exercise_id,
                         e.resource_id,
                         e.exo_name,
                         COALESCE(e.funcname, e.exo_name) AS display_name,
                         e.difficulte,
                         e.date_creation,
                         COUNT(a.attempt_id)                             AS total_attempts,
                         SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END) AS successful_attempts
                  FROM exercises e
                  LEFT JOIN attempts a ON e.exercise_id = a.exercise_id
                  WHERE e.resource_id = :resource_id
                  GROUP BY e.exercise_id
                  ORDER BY display_name ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['resource_id' => $resourceId]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            $row['total_attempts']      = (int) $row['total_attempts'];
            $row['successful_attempts'] = (int) $row['successful_attempts'];
            $row['success_rate']        = $row['total_attempts'] > 0
                ? round(($row['successful_attempts'] / $row['total_attempts']) * 100, 1)
                : null;
            $row['display_name'] = $row['display_name'] ?? $row['exo_name'];
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
            $query = "SELECT COUNT(DISTINCT e.exercise_id)
                      FROM exercises e
                      INNER JOIN attempts a ON e.exercise_id = a.exercise_id";
            $stmt = $this->pdo->query($query);
        } else {
            $query = "SELECT COUNT(DISTINCT e.exercise_id)
                      FROM exercises e
                      INNER JOIN attempts a ON e.exercise_id = a.exercise_id
                      WHERE e.resource_id = :resource_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['resource_id' => $resourceId]);
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Find an exercise by resource ID and name (case-sensitive).
     *
     * @param int    $ressourceId Resource ID
     * @param string $name        Exercise name
     * @return Exercise|null Exercise entity or null
     */
    public function findByRessourceIdAndName(int $ressourceId, string $name): ?Exercise
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM exercises WHERE resource_id = :resource_id AND exo_name = :name LIMIT 1"
        );
        $stmt->execute(['resource_id' => $ressourceId, 'name' => $name]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find an exercise by name globally (returns the most recent one).
     *
     * @param string $name Exercise name
     * @return Exercise|null Exercise entity or null
     */
    public function findByName(string $name): ?Exercise
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM exercises WHERE exo_name = :name ORDER BY exercise_id DESC LIMIT 1"
        );
        $stmt->execute(['name' => $name]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Insert a new exercise row.
     *
     * @param int    $ressourceId  Resource ID
     * @param string $exerciceName Exercise name
     * @param string $extention    File extension
     * @param string $date         Date (Y-m-d)
     * @return int New exercise ID
     */
    public function insertExercice(int $ressourceId, string $exerciceName, string $extention, string $date): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO exercises (resource_id, exo_name)
             VALUES (:resource_id, :exo_name)"
        );
        $stmt->execute([
            'resource_id' => $ressourceId,
            'exo_name'    => $exerciceName,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Delete an exercise by ID.
     *
     * @param mixed $exerciceId Exercise ID
     * @return bool True if deleted
     */
    public function delete(mixed $exerciceId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM exercises WHERE exercise_id = :id");
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
        $exercise->setExerciseId($data['exercise_id'] ?? null);
        $exercise->setResourceId((int) ($data['resource_id'] ?? 0));
        $exercise->setExoName($data['exo_name'] ?? '');
        $exercise->setFuncname($data['funcname'] ?? null);
        $exercise->setSolution($data['solution'] ?? null);
        $exercise->setDescription($data['description'] ?? null);
        $exercise->setDifficulte($data['difficulte'] ?? null);
        $exercise->setDateCreation($data['date_creation'] ?? null);
        return $exercise;
    }
}
