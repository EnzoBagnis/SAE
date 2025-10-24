<?php

/**
 * Modèle pour gérer les exercices
 * Utilise la table exercises existante
 */
class Exercise {

    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Récupère tous les exercices d'un dataset
     */
    public function getByDataset($datasetId) {
        $stmt = $this->pdo->prepare(
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
    public function getById($exerciseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM exercises WHERE exercise_id = ?");
        $stmt->execute([$exerciseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les test cases d'un exercice
     */
    public function getTestCases($exerciseId) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM test_cases 
             WHERE exercise_id = ? 
             ORDER BY test_order"
        );

        $stmt->execute([$exerciseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}