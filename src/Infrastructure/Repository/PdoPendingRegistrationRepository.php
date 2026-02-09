<?php

namespace Infrastructure\Repository;

use Domain\Authentication\Entity\PendingRegistration;
use Domain\Authentication\Repository\PendingRegistrationRepositoryInterface;
use PDO;

/**
 * PDO Pending Registration Repository - Concrete implementation
 *
 * This class implements pending registration persistence using PDO.
 */
class PdoPendingRegistrationRepository implements PendingRegistrationRepositoryInterface
{
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo PDO database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?PendingRegistration
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?PendingRegistration
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function save(PendingRegistration $registration): PendingRegistration
    {
        if ($registration->getId() === null) {
            return $this->insert($registration);
        }
        return $this->update($registration);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM inscriptions_en_attente WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM inscriptions_en_attente ORDER BY date_creation DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->mapToEntity($row), $data);
    }

    /**
     * Insert new pending registration
     *
     * @param PendingRegistration $registration Pending registration entity
     * @return PendingRegistration Registration with ID
     */
    private function insert(PendingRegistration $registration): PendingRegistration
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO inscriptions_en_attente (nom, prenom, mdp, mail, code_verif, verifie, date_creation) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif, :verifie, :date_creation)"
        );

        $stmt->execute([
            'nom' => $registration->getLastName(),
            'prenom' => $registration->getFirstName(),
            'mdp' => $registration->getPasswordHash(),
            'mail' => $registration->getEmail(),
            'code_verif' => $registration->getVerificationCode(),
            'verifie' => $registration->isVerified() ? 1 : 0,
            'date_creation' => $registration->getCreatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id);
    }

    /**
     * Update existing pending registration
     *
     * @param PendingRegistration $registration Pending registration entity
     * @return PendingRegistration Updated registration
     */
    private function update(PendingRegistration $registration): PendingRegistration
    {
        $stmt = $this->pdo->prepare(
            "UPDATE inscriptions_en_attente 
             SET nom = :nom, prenom = :prenom, mdp = :mdp, mail = :mail, 
                 code_verif = :code_verif, verifie = :verifie
             WHERE id = :id"
        );

        $stmt->execute([
            'nom' => $registration->getLastName(),
            'prenom' => $registration->getFirstName(),
            'mdp' => $registration->getPasswordHash(),
            'mail' => $registration->getEmail(),
            'code_verif' => $registration->getVerificationCode(),
            'verifie' => $registration->isVerified() ? 1 : 0,
            'id' => $registration->getId()
        ]);

        return $registration;
    }

    /**
     * Map database row to PendingRegistration entity
     *
     * @param array $data Database row
     * @return PendingRegistration PendingRegistration entity
     */
    private function mapToEntity(array $data): PendingRegistration
    {
        return new PendingRegistration(
            (int) $data['id'],
            $data['nom'],
            $data['prenom'],
            $data['mail'],
            $data['mdp'],
            $data['code_verif'] ?? null,
            (bool) ($data['verifie'] ?? 0),
            isset($data['date_creation']) ? new \DateTimeImmutable($data['date_creation']) : null
        );
    }
}
