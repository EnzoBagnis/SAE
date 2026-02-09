<?php

namespace Infrastructure\Repository;

use Domain\StudentTracking\Entity\Attempt;
use Domain\StudentTracking\Repository\AttemptRepositoryInterface;
use PDO;

/**
 * PDO Attempt Repository Implementation
 * Handles attempt data persistence using PDO
 */
class PdoAttemptRepository implements AttemptRepositoryInterface
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
    public function findByStudentId(int $studentId, ?int $resourceId = null): array
    {
        if ($resourceId === null) {
            $query = "SELECT a.* 
                     FROM attempts a 
                     WHERE a.student_id = :student_id 
                     ORDER BY a.submission_date DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        } else {
            $query = "SELECT a.* 
                     FROM attempts a 
                     JOIN exercises e ON a.exercise_id = e.exercise_id 
                     WHERE a.student_id = :student_id 
                     AND e.resource_id = :resource_id 
                     ORDER BY a.submission_date DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return new Attempt(
                $row['attempt_id'],
                $row['student_id'],
                $row['exercise_id'],
                $row['submission_date'],
                $row['extension'],
                $row['correct'],
                $row['aes2'] ?? null,
                $row['code'] ?? null
            );
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function findByExerciseId(int $exerciseId): array
    {
        $query = "SELECT a.* 
                 FROM attempts a 
                 WHERE a.exercise_id = :exercise_id 
                 ORDER BY a.submission_date DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':exercise_id', $exerciseId, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return new Attempt(
                $row['attempt_id'],
                $row['student_id'],
                $row['exercise_id'],
                $row['submission_date'],
                $row['extension'],
                $row['correct'],
                $row['aes2'] ?? null,
                $row['code'] ?? null
            );
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $attemptId): ?Attempt
    {
        $query = "SELECT a.* 
                 FROM attempts a 
                 WHERE a.attempt_id = :attempt_id";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':attempt_id', $attemptId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Attempt(
            $row['attempt_id'],
            $row['student_id'],
            $row['exercise_id'],
            $row['submission_date'],
            $row['extension'],
            $row['correct'],
            $row['aes2'] ?? null,
            $row['code'] ?? null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function countByStudentId(int $studentId, ?int $resourceId = null): int
    {
        if ($resourceId === null) {
            $query = "SELECT COUNT(*) 
                     FROM attempts a 
                     WHERE a.student_id = :student_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        } else {
            $query = "SELECT COUNT(*) 
                     FROM attempts a 
                     JOIN exercises e ON a.exercise_id = e.exercise_id 
                     WHERE a.student_id = :student_id 
                     AND e.resource_id = :resource_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
