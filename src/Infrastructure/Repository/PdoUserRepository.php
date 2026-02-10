<?php

namespace Infrastructure\Repository;

use Domain\Authentication\Entity\User;
use Domain\Authentication\Repository\UserRepositoryInterface;
use PDO;

/**
 * PDO User Repository - Concrete implementation of UserRepositoryInterface
 *
 * This class implements user persistence using PDO for database access.
 */
class PdoUserRepository implements UserRepositoryInterface
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
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByResetToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE reset_token = :token");
        $stmt->execute(['token' => $token]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function save(User $user): User
    {
        if ($user->getId() === null) {
            return $this->insert($user);
        }
        return $this->update($user);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM utilisateurs ORDER BY date_creation DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->mapToEntity($row), $data);
    }

    /**
     * Get PDO connection (for admin operations)
     *
     * @return PDO PDO connection
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Insert new user
     *
     * @param User $user User entity
     * @return User User with ID
     */
    private function insert(User $user): User
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif, date_creation) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif, :date_creation)"
        );

        $stmt->execute([
            'nom' => $user->getLastName(),
            'prenom' => $user->getFirstName(),
            'mdp' => $user->getPasswordHash(),
            'mail' => $user->getEmail(),
            'code_verif' => $user->getVerificationCode() ?? '',
            'date_creation' => $user->getCreatedAt()
                ? $user->getCreatedAt()->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s')
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id);
    }

    /**
     * Update existing user
     *
     * @param User $user User entity
     * @return User Updated user
     */
    private function update(User $user): User
    {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs 
             SET nom = :nom, prenom = :prenom, mdp = :mdp, mail = :mail, 
                 code_verif = :code_verif, reset_token = :reset_token, 
                 reset_expiration = :reset_expiration
             WHERE id = :id"
        );

        $stmt->execute([
            'nom' => $user->getLastName(),
            'prenom' => $user->getFirstName(),
            'mdp' => $user->getPasswordHash(),
            'mail' => $user->getEmail(),
            'code_verif' => $user->getVerificationCode() ?? '',
            'reset_token' => $user->getResetToken(),
            'reset_expiration' => $user->getResetTokenExpiration()
                ? $user->getResetTokenExpiration()->format('Y-m-d H:i:s')
                : null,
            'id' => $user->getId()
        ]);

        return $user;
    }

    /**
     * Map database row to User entity
     *
     * @param array $data Database row
     * @return User User entity
     */
    private function mapToEntity(array $data): User
    {
        return new User(
            (int) $data['id'],
            $data['nom'],
            $data['prenom'],
            $data['mail'],
            $data['mdp'],
            $data['code_verif'] ?? null,
            true, // Les utilisateurs dans la table 'utilisateurs' sont toujours vérifiés
            isset($data['date_creation']) ? new \DateTimeImmutable($data['date_creation']) : null,
            $data['reset_token'] ?? null,
            isset($data['reset_expiration']) && $data['reset_expiration']
                ? new \DateTimeImmutable($data['reset_expiration'])
                : null
        );
    }
}
