<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Exercise;

/**
 * Exercise Repository
 * Handles exercise data persistence
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
     * Find all exercises, optionally filtered by resource
     *
     * @param int|null $resourceId Resource ID filter
     * @return array Array of Exercise entities
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
            $stmt->execute(['resource_id' => $resourceId]);
        }

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find exercise by ID
     *
     * @param int $exerciseId Exercise ID
     * @return Exercise|null Exercise entity or null
     */
    public function findById(int $exerciseId): ?Exercise
    {
        return $this->findByField('exercise_id', $exerciseId);
    }

    /**
     * Find exercises by resource ID
     *
     * @param int $resourceId Resource ID
     * @return array Array of Exercise entities
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
     * Find exercises by dataset ID
     *
     * @param int $datasetId Dataset ID
     * @return array Array of Exercise entities
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
        $stmt->execute(['dataset_id' => $datasetId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Count exercises, optionally filtered by resource
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
     * Save exercise (insert or update)
     *
     * @param Exercise $exercise Exercise entity
     * @return Exercise Saved exercise
     */
    public function save(Exercise $exercise): Exercise
    {
        if ($exercise->getExerciseId() === null) {
            return $this->insert($exercise);
        }
        return $this->update($exercise);
    }

    /**
     * Insert new exercise
     *
     * @param Exercise $exercise Exercise entity
     * @return Exercise Inserted exercise
     */
    private function insert(Exercise $exercise): Exercise
    {
        $query = "INSERT INTO exercises 
                 (resource_id, exo_name, funcname, solution, description, difficulte, date_creation, dataset_id) 
                 VALUES 
                 (:resource_id, :exo_name, :funcname, :solution, :description, :difficulte, NOW(), :dataset_id)";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'resource_id' => $exercise->getResourceId(),
            'exo_name' => $exercise->getExoName(),
            'funcname' => $exercise->getFuncname(),
            'solution' => $exercise->getSolution(),
            'description' => $exercise->getDescription(),
            'difficulte' => $exercise->getDifficulte(),
            'dataset_id' => $exercise->getDatasetId(),
        ]);

        $exercise->setExerciseId((int) $this->pdo->lastInsertId());
        return $exercise;
    }

    /**
     * Update existing exercise
     *
     * @param Exercise $exercise Exercise entity
     * @return Exercise Updated exercise
     */
    private function update(Exercise $exercise): Exercise
    {
        $query = "UPDATE exercises 
                 SET resource_id = :resource_id,
                     exo_name = :exo_name,
                     funcname = :funcname,
                     solution = :solution,
                     description = :description,
                     difficulte = :difficulte,
                     dataset_id = :dataset_id
                 WHERE exercise_id = :exercise_id";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'exercise_id' => $exercise->getExerciseId(),
            'resource_id' => $exercise->getResourceId(),
            'exo_name' => $exercise->getExoName(),
            'funcname' => $exercise->getFuncname(),
            'solution' => $exercise->getSolution(),
            'description' => $exercise->getDescription(),
            'difficulte' => $exercise->getDifficulte(),
            'dataset_id' => $exercise->getDatasetId(),
        ]);

        return $exercise;
    }

    /**
     * Delete exercise by ID
     *
     * @param int $exerciseId Exercise ID
     * @return bool True if deleted
     */
    public function delete(int $exerciseId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM exercises WHERE exercise_id = :id");
        return $stmt->execute(['id' => $exerciseId]);
    }

    /**
     * Hydrate exercise from database row
     *
     * @param array $data Database row data
     * @return Exercise Exercise entity
     */
    protected function hydrate(array $data): Exercise
    {
        $exercise = new Exercise();
        $exercise->setExerciseId($data['exercise_id'] ?? null);
        $exercise->setResourceId($data['resource_id'] ?? 0);
        $exercise->setExoName($data['exo_name'] ?? '');
        $exercise->setFuncname($data['funcname'] ?? null);
        $exercise->setSolution($data['solution'] ?? null);
        $exercise->setDescription($data['description'] ?? null);
        $exercise->setDifficulte($data['difficulte'] ?? null);
        $exercise->setDateCreation($data['date_creation'] ?? null);
        $exercise->setDatasetId($data['dataset_id'] ?? null);
        return $exercise;
    }
}

