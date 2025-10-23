<?php

class Exercise {
    private ?PDO $db;
    public int $exercise_id;
    public int $resource_id;
    public string $exo_name;
    public ?string $funcname;
    public ?string $solution;
    public ?string $description;
    public ?string $difficulte; // enum('facile','moyen','difficile')
    public string $date_creation;

    public function __construct(PDO $db = null) {
        $this->db = $db;
    }

    public function hydrate(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    // Récupère tous les exercices pour une ressource donnée
    public static function getExercisesByResourceId(PDO $db, int $resourceId): array {
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
    public static function getExerciseById(PDO $db, int $exerciseId): ?Exercise {
        $stmt = $db->prepare("SELECT * FROM exercises WHERE exercise_id = :exerciseId");
        $stmt->execute(['exerciseId' => $exerciseId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $exercise = new Exercise();
            return $exercise->hydrate($data);
        }
        return null;
    }
}