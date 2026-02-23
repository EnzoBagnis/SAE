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
        $stmt = $this->pdo->prepare("SELECT * FROM teacher WHERE mail = :mail");
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
        $stmt = $this->pdo->prepare("SELECT * FROM teacher WHERE reset_token = :token");
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
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM teacher WHERE mail = :mail");
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
        $query = "SELECT * FROM utilisateurs ORDER BY surname DESC";

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
            (name, surname, mail, password, code_verif, reset_token, reset_expiration, account_status)
            VALUES 
            (:nom, :prenom, :mail, :mdp, :code_verif, :reset_token, :reset_token_expiration, status)
        ");

        $resetTokenExpiration = $user->getResetTokenExpiration();
        $stmt->execute([
            'nom' => $user->getLastName(),
            'prenom' => $user->getFirstName(),
            'mail' => $user->getEmail(),
            'mdp' => $user->getPasswordHash(),
            'code_verif' => $user->getVerificationCode(),
            'status' => $user->isVerified() ? 1 : 0,
            'reset_token' => $user->getResetToken(),
            'reset_token_expiration' => $resetTokenExpiration ? $resetTokenExpiration->format('Y-m-d H:i:s') : null,
        ]);
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
            SET surname = :nom,
                name = :prenom,
                mail = :mail,
                password = :mdp,
                code_verif = :code_verif,
                account_status = :status,
                reset_token = :reset_token,
                reset_expiration = :reset_token_expiration
            WHERE mail = :mail
        ");

        $resetTokenExpiration = $user->getResetTokenExpiration();
        $stmt->execute([
            'surname' => $user->getLastName(),
            'name' => $user->getFirstName(),
            'mail' => $user->getEmail(),
            'password' => $user->getPasswordHash(),
            'code_verif' => $user->getVerificationCode(),
            'account_status' => $user->isVerified() ? 1 : 0,
            'reset_token' => $user->getResetToken(),
            'reset_expiration' => $resetTokenExpiration ? $resetTokenExpiration->format('Y-m-d H:i:s') : null,
        ]);

        return $user;
    }

    /**
     * Delete user by ID
     *
     * @param int $id User ID
     * @return bool True if deleted
     */
    public function delete(String $mail): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM teacher WHERE mail = :mail");
        return $stmt->execute(['id' => $mail]);
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
        $user->setLastName($data['nom'] ?? '');
        $user->setFirstName($data['prenom'] ?? '');
        $user->setEmail($data['mail'] ?? '');
        $user->setPasswordHash($data['mdp'] ?? '');
        $user->setVerificationCode($data['code_verif'] ?? null);
        $user->setIsVerified(($data['mail_verifie'] ?? 0) == 1);



        if (isset($data['reset_token_expiration']) && $data['reset_token_expiration']) {
            $user->setResetToken(
                $data['reset_token'] ?? null,
                new \DateTimeImmutable($data['reset_token_expiration'])
            );
        }

        return $user;
    }
}



