<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Attempt;

/**
 * Attempt Repository
 * Handles attempt data persistence
 */
class AttemptRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName(): string
    {
        return 'attempts';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return Attempt::class;
    }

    /**
     * Find attempts by student ID
     *
     * @param int $studentId Student ID
     * @return array Array of Attempt entities
     */
    public function findByStudentId(int $studentId): array
    {
        $query = "SELECT * FROM attempts 
                 WHERE student_id = :student_id 
                 ORDER BY submission_date DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['student_id' => $studentId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find attempts by exercise ID
     *
     * @param int $exerciseId Exercise ID
     * @return array Array of Attempt entities
     */
    public function findByExerciseId(int $exerciseId): array
    {
        $query = "SELECT * FROM attempts 
                 WHERE exercise_id = :exercise_id 
                 ORDER BY submission_date DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['exercise_id' => $exerciseId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find attempts by student and exercise
     *
     * @param int $studentId Student ID
     * @param int $exerciseId Exercise ID
     * @return array Array of Attempt entities
     */
    public function findByStudentAndExercise(int $studentId, int $exerciseId): array
    {
        $query = "SELECT * FROM attempts 
                 WHERE student_id = :student_id AND exercise_id = :exercise_id 
                 ORDER BY submission_date DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'student_id' => $studentId,
            'exercise_id' => $exerciseId
        ]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Save attempt (insert or update)
     *
     * @param Attempt $attempt Attempt entity
     * @return Attempt Saved attempt
     */
    public function save(Attempt $attempt): Attempt
    {
        if ($attempt->getAttemptId() === null) {
            return $this->insert($attempt);
        }
        return $this->update($attempt);
    }

    /**
     * Insert new attempt
     *
     * @param Attempt $attempt Attempt entity
     * @return Attempt Inserted attempt
     */
    private function insert(Attempt $attempt): Attempt
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO attempts 
            (student_id, exercise_id, submission_date, extension, correct, aes2, code)
            VALUES 
            (:student_id, :exercise_id, NOW(), :extension, :correct, :aes2, :code)
        ");

        $stmt->execute([
            'student_id' => $attempt->getStudentId(),
            'exercise_id' => $attempt->getExerciseId(),
            'extension' => $attempt->getExtension(),
            'correct' => $attempt->getCorrect(),
            'aes2' => $attempt->getAes2(),
            'code' => $attempt->getCode(),
        ]);

        $attempt->setAttemptId((int) $this->pdo->lastInsertId());
        return $attempt;
    }

    /**
     * Update existing attempt
     *
     * @param Attempt $attempt Attempt entity
     * @return Attempt Updated attempt
     */
    private function update(Attempt $attempt): Attempt
    {
        $stmt = $this->pdo->prepare("
            UPDATE attempts 
            SET student_id = :student_id,
                exercise_id = :exercise_id,
                extension = :extension,
                correct = :correct,
                aes2 = :aes2,
                code = :code
            WHERE attempt_id = :attempt_id
        ");

        $stmt->execute([
            'attempt_id' => $attempt->getAttemptId(),
            'student_id' => $attempt->getStudentId(),
            'exercise_id' => $attempt->getExerciseId(),
            'extension' => $attempt->getExtension(),
            'correct' => $attempt->getCorrect(),
            'aes2' => $attempt->getAes2(),
            'code' => $attempt->getCode(),
        ]);

        return $attempt;
    }

    /**
     * Hydrate attempt from database row
     *
     * @param array $data Database row data
     * @return Attempt Attempt entity
     */
    protected function hydrate(array $data): Attempt
    {
        $attempt = new Attempt();
        $attempt->setAttemptId($data['attempt_id'] ?? null);
        $attempt->setStudentId($data['student_id'] ?? 0);
        $attempt->setExerciseId($data['exercise_id'] ?? 0);
        $attempt->setSubmissionDate($data['submission_date'] ?? null);
        $attempt->setExtension($data['extension'] ?? null);
        $attempt->setCorrect($data['correct'] ?? 0);
        $attempt->setAes2($data['aes2'] ?? null);
        $attempt->setCode($data['code'] ?? null);
        return $attempt;
    }
}

