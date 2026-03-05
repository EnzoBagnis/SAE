<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\PendingRegistration;

/**
 * PendingRegistration Repository
 * Handles pending registration data persistence against `inscriptions_en_attente` table.
 */
class PendingRegistrationRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName(): string
    {
        return 'inscriptions_en_attente';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return PendingRegistration::class;
    }

    /**
     * Find pending registration by email
     *
     * @param string $email Email address
     * @return PendingRegistration|null PendingRegistration entity or null
     */
    public function findByEmail(string $email): ?PendingRegistration
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find pending registration by verification code
     *
     * @param string $code Verification code
     * @return PendingRegistration|null PendingRegistration entity or null
     */
    public function findByVerificationCode(string $code): ?PendingRegistration
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE code_verif = :code");
        $stmt->execute(['code' => $code]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Check if email exists
     *
     * @param string $email Email address
     * @return bool True if email exists
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Find all verified pending registrations
     *
     * @return array Array of PendingRegistration entities
     */
    public function findAllVerified(): array
    {
        $query = "SELECT * FROM inscriptions_en_attente 
                 WHERE verifie = 1 
                 ORDER BY date_creation DESC";

        $stmt = $this->pdo->query($query);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Save pending registration (insert or update)
     *
     * @param PendingRegistration $registration PendingRegistration entity
     * @return PendingRegistration Saved registration
     */
    public function save(PendingRegistration $registration): PendingRegistration
    {
        if ($registration->getId() === null) {
            return $this->insert($registration);
        }
        return $this->update($registration);
    }

    /**
     * Insert new pending registration
     *
     * @param PendingRegistration $registration PendingRegistration entity
     * @return PendingRegistration Inserted registration
     */
    private function insert(PendingRegistration $registration): PendingRegistration
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO inscriptions_en_attente 
            (nom, prenom, mail, mdp, code_verif, verifie, date_creation)
            VALUES 
            (:nom, :prenom, :mail, :mdp, :code_verif, :verifie, NOW())
        ");

        $stmt->execute([
            'nom'        => $registration->getLastName(),
            'prenom'     => $registration->getFirstName(),
            'mail'       => $registration->getEmail(),
            'mdp'        => $registration->getPasswordHash(),
            'code_verif' => $registration->getVerificationCode(),
            'verifie'    => $registration->isVerified() ? 1 : 0,
        ]);

        $registration->setId((int) $this->pdo->lastInsertId());
        return $registration;
    }

    /**
     * Update existing pending registration
     *
     * @param PendingRegistration $registration PendingRegistration entity
     * @return PendingRegistration Updated registration
     */
    private function update(PendingRegistration $registration): PendingRegistration
    {
        $stmt = $this->pdo->prepare("
            UPDATE inscriptions_en_attente 
            SET nom        = :nom,
                prenom     = :prenom,
                mail       = :mail,
                mdp        = :mdp,
                code_verif = :code_verif,
                verifie    = :verifie
            WHERE id = :id
        ");

        $stmt->execute([
            'id'         => $registration->getId(),
            'nom'        => $registration->getLastName(),
            'prenom'     => $registration->getFirstName(),
            'mail'       => $registration->getEmail(),
            'mdp'        => $registration->getPasswordHash(),
            'code_verif' => $registration->getVerificationCode(),
            'verifie'    => $registration->isVerified() ? 1 : 0,
        ]);

        return $registration;
    }

    /**
     * Delete pending registration by ID
     *
     * @param int $id Registration ID
     * @return bool True if deleted
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM inscriptions_en_attente WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Hydrate pending registration from database row
     *
     * @param array $data Database row data
     * @return PendingRegistration PendingRegistration entity
     */
    protected function hydrate(array $data): PendingRegistration
    {
        $registration = new PendingRegistration();
        $registration->setId($data['id'] ?? null);
        $registration->setLastName($data['nom'] ?? '');
        $registration->setFirstName($data['prenom'] ?? '');
        $registration->setEmail($data['mail'] ?? '');
        $registration->setPasswordHash($data['mdp'] ?? '');
        $registration->setVerificationCode(isset($data['code_verif']) ? (string) $data['code_verif'] : null);
        $registration->setIsVerified(($data['verifie'] ?? 0) == 1);

        if (isset($data['date_creation'])) {
            $registration->setCreatedAt(new \DateTimeImmutable($data['date_creation']));
        }

        return $registration;
    }
}
