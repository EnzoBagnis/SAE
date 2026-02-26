<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Attempt;

/**
 * Attempt Repository
 * Handles attempt data persistence against the `attempts` table.
 *
 * Schema: attempt_id (PK), exercice_id, user, correct, eval_set, upload, aes0, aes1, aes2
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
     * Find attempts by exercise ID.
     *
     * @param int $exerciceId Exercise ID
     * @return Attempt[] Array of Attempt entities
     */
    public function findByExerciceId(int $exerciceId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM attempts WHERE exercice_id = :exercice_id ORDER BY attempt_id DESC"
        );
        $stmt->execute(['exercice_id' => $exerciceId]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Find attempts by student identifier.
     *
     * @param string $user Student identifier (user field)
     * @return Attempt[] Array of Attempt entities
     */
    public function findByUser(string $user): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM attempts WHERE user = :user ORDER BY attempt_id DESC"
        );
        $stmt->execute(['user' => $user]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Bulk insert attempts with a single transaction.
     * Each item in $rows must contain: exercice_id, user, correct, eval_set, upload, aes0, aes1, aes2.
     *
     * @param array<array<string,mixed>> $rows Rows to insert
     * @return array{inserted:int, errors:list<string>} Result summary
     */
    public function bulkInsert(array $rows): array
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO attempts (exercice_id, user, correct, eval_set, upload, aes0, aes1, aes2)
             VALUES (:exercice_id, :user, :correct, :eval_set, :upload, :aes0, :aes1, :aes2)"
        );

        $inserted = 0;
        $errors   = [];

        $this->pdo->beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                try {
                    $stmt->execute([
                        'exercice_id' => (int) ($row['exercice_id'] ?? 0),
                        'user'        => (string) ($row['user'] ?? ''),
                        'correct'     => (int) ($row['correct'] ?? 0),
                        'eval_set'    => (string) ($row['eval_set'] ?? ''),
                        'upload'      => (string) ($row['upload'] ?? ''),
                        'aes0'        => (string) ($row['aes0'] ?? ''),
                        'aes1'        => (string) ($row['aes1'] ?? ''),
                        'aes2'        => (string) ($row['aes2'] ?? ''),
                    ]);
                    $inserted++;
                } catch (\Throwable $e) {
                    $errors[] = "Tentative #$index: " . $e->getMessage();
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return ['inserted' => $inserted, 'errors' => $errors];
    }

    /**
     * Save attempt (insert only – no update logic needed for imports).
     *
     * @param Attempt $attempt Attempt entity
     * @return Attempt Saved attempt with new ID
     */
    public function save(Attempt $attempt): Attempt
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO attempts (exercice_id, user, correct, eval_set, upload, aes0, aes1, aes2)
             VALUES (:exercice_id, :user, :correct, :eval_set, :upload, :aes0, :aes1, :aes2)"
        );
        $stmt->execute([
            'exercice_id' => $attempt->getExerciceId(),
            'user'        => $attempt->getUser(),
            'correct'     => $attempt->getCorrect(),
            'eval_set'    => $attempt->getEvalSet(),
            'upload'      => $attempt->getUpload(),
            'aes0'        => $attempt->getAes0(),
            'aes1'        => $attempt->getAes1(),
            'aes2'        => $attempt->getAes2(),
        ]);
        $attempt->setAttemptId((int) $this->pdo->lastInsertId());
        return $attempt;
    }

    /**
     * Hydrate an Attempt entity from a database row.
     *
     * @param array<string,mixed> $data Database row
     * @return Attempt Hydrated entity
     */
    protected function hydrate(array $data): Attempt
    {
        $attempt = new Attempt();
        $attempt->setAttemptId((int) ($data['attempt_id'] ?? 0));
        $attempt->setExerciceId((int) ($data['exercice_id'] ?? 0));
        $attempt->setUser((string) ($data['user'] ?? ''));
        $attempt->setCorrect((int) ($data['correct'] ?? 0));
        $attempt->setEvalSet((string) ($data['eval_set'] ?? ''));
        $attempt->setUpload((string) ($data['upload'] ?? ''));
        $attempt->setAes0((string) ($data['aes0'] ?? ''));
        $attempt->setAes1((string) ($data['aes1'] ?? ''));
        $attempt->setAes2((string) ($data['aes2'] ?? ''));
        return $attempt;
    }
}
