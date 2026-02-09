<?php

namespace Infrastructure\Repository;

use Domain\StudentTracking\Entity\Student;
use Domain\StudentTracking\Repository\StudentRepositoryInterface;
use PDO;

/**
 * PDO Student Repository Implementation
 * Handles student data persistence using PDO
 */
class PdoStudentRepository implements StudentRepositoryInterface
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
            $query = "SELECT DISTINCT s.student_id, s.student_identifier, 
                     s.nom_fictif, s.prenom_fictif, s.dataset_id, d.nom_dataset 
                     FROM students s 
                     JOIN datasets d ON s.dataset_id = d.dataset_id 
                     ORDER BY CAST(SUBSTRING_INDEX(s.student_identifier, '_', -1) AS UNSIGNED)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
        } else {
            // Check if exercises table has dataset_id column
            $checkQuery = "SHOW COLUMNS FROM exercises LIKE 'dataset_id'";
            $checkStmt = $this->pdo->query($checkQuery);
            $hasDatasetId = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($hasDatasetId) {
                $query = "SELECT DISTINCT s.student_id, s.student_identifier, 
                         s.nom_fictif, s.prenom_fictif, s.dataset_id, d.nom_dataset 
                         FROM students s 
                         JOIN datasets d ON s.dataset_id = d.dataset_id 
                         WHERE s.dataset_id IN (
                             SELECT DISTINCT e.dataset_id 
                             FROM exercises e 
                             WHERE e.resource_id = :resource_id
                         ) 
                         ORDER BY CAST(SUBSTRING_INDEX(s.student_identifier, '_', -1) AS UNSIGNED)";
            } else {
                $query = "SELECT DISTINCT s.student_id, s.student_identifier, 
                         s.nom_fictif, s.prenom_fictif, s.dataset_id, d.nom_dataset 
                         FROM students s 
                         JOIN datasets d ON s.dataset_id = d.dataset_id 
                         JOIN attempts a ON s.student_id = a.student_id 
                         JOIN exercises e ON a.exercise_id = e.exercise_id 
                         WHERE e.resource_id = :resource_id 
                         ORDER BY CAST(SUBSTRING_INDEX(s.student_identifier, '_', -1) AS UNSIGNED)";
            }
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
            $stmt->execute();
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return new Student(
                $row['student_id'],
                $row['student_identifier'],
                $row['nom_fictif'],
                $row['prenom_fictif'],
                $row['dataset_id'],
                $row['nom_dataset']
            );
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $studentId): ?Student
    {
        $query = "SELECT s.student_id, s.student_identifier, 
                 s.nom_fictif, s.prenom_fictif, s.dataset_id, d.nom_dataset 
                 FROM students s 
                 JOIN datasets d ON s.dataset_id = d.dataset_id 
                 WHERE s.student_id = :student_id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Student(
            $row['student_id'],
            $row['student_identifier'],
            $row['nom_fictif'],
            $row['prenom_fictif'],
            $row['dataset_id'],
            $row['nom_dataset']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByDatasetId(int $datasetId): array
    {
        $query = "SELECT s.student_id, s.student_identifier, 
                 s.nom_fictif, s.prenom_fictif, s.dataset_id, d.nom_dataset 
                 FROM students s 
                 JOIN datasets d ON s.dataset_id = d.dataset_id 
                 WHERE s.dataset_id = :dataset_id 
                 ORDER BY CAST(SUBSTRING_INDEX(s.student_identifier, '_', -1) AS UNSIGNED)";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':dataset_id', $datasetId, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return new Student(
                $row['student_id'],
                $row['student_identifier'],
                $row['nom_fictif'],
                $row['prenom_fictif'],
                $row['dataset_id'],
                $row['nom_dataset']
            );
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginated(int $page, int $perPage, ?int $resourceId = null): array
    {
        $allStudents = $this->findAll($resourceId);
        $total = count($allStudents);

        $offset = ($page - 1) * $perPage;
        $students = array_slice($allStudents, $offset, $perPage);

        return [
            'students' => $students,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'hasMore' => ($offset + $perPage) < $total
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function count(?int $resourceId = null): int
    {
        return count($this->findAll($resourceId));
    }
}
