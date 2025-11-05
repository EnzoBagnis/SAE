<?php

class Exercise
{
    private ?PDO $db;
    public int $exercise_id;
    public int $resource_id;
    public string $exo_name;
    public ?string $funcname;
    public ?string $solution;
    public ?string $description;
    public ?string $difficulte; // enum('facile','moyen','difficile')
    public string $date_creation;

    public function __construct(PDO $db = null)
    {
        $this->db = $db;
    }

    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    // Récupère tous les exercices pour une ressource donnée
    public static function getExercisesByResourceId(PDO $db, int $resourceId): array
    {
        $stmt = $db->prepare("SELECT * FROM exercises WHERE resource_id = :resourceId ORDER BY exo_name ASC");
        $stmt->execute(['resourceId' => $resourceId]);
        $exercisesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $exercises = [];
        foreach ($exercisesData as $data) {
            $exercise = new Exercise();
            $exercises[] = $exercise->hydrate($data);
        }
        return $exercises;
    }

    // Récupère un exercice par son ID
    public static function getExerciseById(PDO $db, int $exerciseId): ?Exercise
    {
        $stmt = $db->prepare("SELECT * FROM exercises WHERE exercise_id = :exerciseId");
        $stmt->execute(['exerciseId' => $exerciseId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $exercise = new Exercise();
            return $exercise->hydrate($data);
        }
        return null;
    }
   /**
     * Récupère tous les exercices d'un dataset
     */
    public function getByDataset($datasetId, PDO $db)
    {
        $stmt = $db->prepare(
            "SELECT e.*, GROUP_CONCAT(tc.test_case_id) as has_test_cases
             FROM exercises e
             LEFT JOIN test_cases tc ON e.exercise_id = tc.exercise_id
             WHERE e.dataset_id = ?
             GROUP BY e.exercise_id
             ORDER BY e.exo_name"
        );

        $stmt->execute([$datasetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un exercice par son ID
     */
    public function getById($exerciseId, PDO $db)
    {
        $stmt = $db->prepare("SELECT * FROM exercises WHERE exercise_id = ?");
        $stmt->execute([$exerciseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les test cases d'un exercice
     */
    public function getTestCases($exerciseId, PDO $db)
    {
        $stmt = $db->prepare(
            "SELECT * FROM test_cases 
             WHERE exercise_id = ? 
             ORDER BY test_order"
        );

        $stmt->execute([$exerciseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
