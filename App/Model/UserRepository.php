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
        return 'teachers';
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
        $stmt = $this->pdo->prepare("SELECT * FROM teachers WHERE mail = :mail");
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
        $stmt = $this->pdo->prepare("SELECT * FROM teachers WHERE reset_token = :token");
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
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM teachers WHERE mail = :mail");
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
        $query = "SELECT * FROM teachers ORDER BY surname DESC";

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
            INSERT INTO teachers 
            (mail, name, surname, password, code_verif, account_status, reset_token, reset_expiration)
            VALUES 
            (:mail, :name, :surname, :password, :code_verif, :account_status, :reset_token, :reset_expiration)
        ");

        $resetTokenExpiration = $user->getResetTokenExpiration();
        $stmt->execute([
            'mail'              => $user->getEmail(),
            'name'              => $user->getFirstName(),
            'surname'           => $user->getLastName(),
            'password'          => $user->getPasswordHash(),
            'code_verif'        => $user->getVerificationCode(),
            'account_status'    => $user->isVerified() ? 1 : 0,
            'reset_token'       => $user->getResetToken() ?? '',
            'reset_expiration'  => $resetTokenExpiration ? $resetTokenExpiration->format('Y-m-d') : null,
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
            UPDATE teachers 
            SET name             = :name,
                surname          = :surname,
                password         = :password,
                code_verif       = :code_verif,
                account_status   = :account_status,
                reset_token      = :reset_token,
                reset_expiration = :reset_expiration
            WHERE mail = :mail
        ");

        $resetTokenExpiration = $user->getResetTokenExpiration();
        $stmt->execute([
            'mail'              => $user->getEmail(),
            'name'              => $user->getFirstName(),
            'surname'           => $user->getLastName(),
            'password'          => $user->getPasswordHash(),
            'code_verif'        => $user->getVerificationCode(),
            'account_status'    => $user->isVerified() ? 1 : 0,
            'reset_token'       => $user->getResetToken() ?? '',
            'reset_expiration'  => $resetTokenExpiration ? $resetTokenExpiration->format('Y-m-d') : null,
        ]);

        return $user;
    }

    /**
     * Delete user by mail (overrides AbstractRepository::delete).
     * In the teachers table, the PK is 'mail' (string), not 'id'.
     *
     * @param mixed $id User mail address used as primary key
     * @return bool True if deleted
     */
    public function delete($id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM teachers WHERE mail = :mail");
        return $stmt->execute(['mail' => $id]);
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
        $user->setLastName($data['surname'] ?? '');
        $user->setFirstName($data['name'] ?? '');
        $user->setEmail($data['mail'] ?? '');
        $user->setPasswordHash($data['password'] ?? '');
        $user->setVerificationCode(isset($data['code_verif']) ? (string) $data['code_verif'] : null);
        $user->setIsVerified(($data['account_status'] ?? 0) == 1);

        if (!empty($data['reset_expiration']) && !empty($data['reset_token'])) {
            $user->setResetToken(
                $data['reset_token'],
                new \DateTimeImmutable($data['reset_expiration'])
            );
        }

        return $user;
    }
}
