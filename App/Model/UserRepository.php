<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\User;

/**
 * User Repository
 * Handles user data persistence
 */
class UserRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName(): string
    {
        return 'utilisateurs';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return User::class;
    }

    /**
     * Find user by email
     *
     * @param string $email User email
     * @return User|null User entity or null
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find user by reset token
     *
     * @param string $token Reset token
     * @return User|null User entity or null
     */
    public function findByResetToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE reset_token = :token");
        $stmt->execute(['token' => $token]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Check if email exists
     *
     * @param string $email User email
     * @return bool True if email exists
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Find all users
     *
     * @return array Array of User entities
     */
    public function findAll(?int $limit = null, int $offset = 0): array
    {
        $query = "SELECT * FROM utilisateurs ORDER BY date_creation DESC";

        if ($limit !== null) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $this->pdo->query($query);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrate($row), $data);
    }

    /**
     * Save user (insert or update)
     *
     * @param User $user User entity
     * @return User Saved user
     */
    public function save(User $user): User
    {
        if ($user->getId() === null) {
            return $this->insert($user);
        }
        return $this->update($user);
    }

    /**
     * Insert new user
     *
     * @param User $user User entity
     * @return User Inserted user
     */
    private function insert(User $user): User
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO utilisateurs 
            (nom, prenom, mail, mdp, code_verif, mail_verifie, date_creation, reset_token, reset_token_expiration)
            VALUES 
            (:nom, :prenom, :mail, :mdp, :code_verif, :mail_verifie, NOW(), :reset_token, :reset_token_expiration)
        ");

        $resetTokenExpiration = $user->getResetTokenExpiration();
        $stmt->execute([
            'nom' => $user->getLastName(),
            'prenom' => $user->getFirstName(),
            'mail' => $user->getEmail(),
            'mdp' => $user->getPasswordHash(),
            'code_verif' => $user->getVerificationCode(),
            'mail_verifie' => $user->isVerified() ? 1 : 0,
            'reset_token' => $user->getResetToken(),
            'reset_token_expiration' => $resetTokenExpiration ? $resetTokenExpiration->format('Y-m-d H:i:s') : null,
        ]);

        $user->setId((int) $this->pdo->lastInsertId());
        return $user;
    }

    /**
     * Update existing user
     *
     * @param User $user User entity
     * @return User Updated user
     */
    private function update(User $user): User
    {
        $stmt = $this->pdo->prepare("
            UPDATE utilisateurs 
            SET nom = :nom,
                prenom = :prenom,
                mail = :mail,
                mdp = :mdp,
                code_verif = :code_verif,
                mail_verifie = :mail_verifie,
                reset_token = :reset_token,
                reset_token_expiration = :reset_token_expiration
            WHERE id = :id
        ");

        $resetTokenExpiration = $user->getResetTokenExpiration();
        $stmt->execute([
            'id' => $user->getId(),
            'nom' => $user->getLastName(),
            'prenom' => $user->getFirstName(),
            'mail' => $user->getEmail(),
            'mdp' => $user->getPasswordHash(),
            'code_verif' => $user->getVerificationCode(),
            'mail_verifie' => $user->isVerified() ? 1 : 0,
            'reset_token' => $user->getResetToken(),
            'reset_token_expiration' => $resetTokenExpiration ? $resetTokenExpiration->format('Y-m-d H:i:s') : null,
        ]);

        return $user;
    }

    /**
     * Delete user by ID
     *
     * @param int $id User ID
     * @return bool True if deleted
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Hydrate user from database row
     *
     * @param array $data Database row data
     * @return User User entity
     */
    protected function hydrate(array $data): User
    {
        $user = new User();
        $user->setId($data['id'] ?? null);
        $user->setLastName($data['nom'] ?? '');
        $user->setFirstName($data['prenom'] ?? '');
        $user->setEmail($data['mail'] ?? '');
        $user->setPasswordHash($data['mdp'] ?? '');
        $user->setVerificationCode($data['code_verif'] ?? null);
        $user->setIsVerified(($data['mail_verifie'] ?? 0) == 1);

        if (isset($data['date_creation'])) {
            $user->setCreatedAt(new \DateTimeImmutable($data['date_creation']));
        }

        if (isset($data['reset_token_expiration']) && $data['reset_token_expiration']) {
            $user->setResetToken(
                $data['reset_token'] ?? null,
                new \DateTimeImmutable($data['reset_token_expiration'])
            );
        }

        return $user;
    }
}



