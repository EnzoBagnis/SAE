<?php

/**
 * Modèle pour gérer les datasets
 * Utilise la structure BD existante
 */
class Dataset
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Récupère tous les datasets d'un enseignant
     */
    public function getByTeacher($teacherId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM datasets 
             WHERE enseignant_id = ? 
             ORDER BY date_import DESC"
        );

        $stmt->execute([$teacherId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un dataset par son ID
     */
    public function getById($datasetId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM datasets WHERE dataset_id = ?");
        $stmt->execute([$datasetId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les tentatives d'un dataset avec vecteurs
     */
    public function getAttemptsWithVectors($datasetId)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.attempt_id,
                a.submission_date,
                a.correct,
                a.aes2,
                s.student_identifier,
                s.nom_fictif,
                s.prenom_fictif,
                e.exo_name,
                e.funcname
            FROM attempts a
            JOIN students s ON a.student_id = s.student_id
            JOIN exercises e ON a.exercise_id = e.exercise_id
            WHERE s.dataset_id = ?
            ORDER BY a.submission_date ASC
        ");

        $stmt->execute([$datasetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si l'enseignant a accès au dataset
     */
    public function hasAccess($datasetId, $teacherId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM datasets 
             WHERE dataset_id = ? AND enseignant_id = ?"
        );

        $stmt->execute([$datasetId, $teacherId]);
        return $stmt->fetchColumn() > 0;
    }
}
