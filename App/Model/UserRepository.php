<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\User;

/**
 * User Repository
 * Handles user data persistence against the `utilisateurs` table.
 */
class UserRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'utilisateurs';
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find user by reset token
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
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Find all users
     */
    public function findAll(?int $limit = null, int $offset = 0): array
    {
        $query = "SELECT * FROM utilisateurs ORDER BY nom DESC";
        if ($limit !== null) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->pdo->query($query);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $data);
    }

    /**
     * Save user (insert or update)
     */
    public function save(User $user): User
    {
        if ($user->getId() !== null) {
            return $this->update($user);
        }
        if ($this->emailExists($user->getEmail())) {
            // User already exists by email, find and update
            $existing = $this->findByEmail($user->getEmail());
            if ($existing) {
                $user->setId($existing->getId());
                return $this->update($user);
            }
        }
        return $this->insert($user);
    }

    private function insert(User $user): User
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO utilisateurs 
            (nom, prenom, mdp, mail, code_verif, reset_token, reset_expiration)
            VALUES 
            (:nom, :prenom, :mdp, :mail, :code_verif, :reset_token, :reset_expiration)
        ");

        $resetExpiration = $user->getResetTokenExpiration();

        $stmt->execute([
            'nom'              => $user->getLastName(),
            'prenom'           => $user->getFirstName(),
            'mdp'              => $user->getPasswordHash(),
            'mail'             => $user->getEmail(),
            'code_verif'       => (int) ($user->getVerificationCode() ?? 0),
            'reset_token'      => $user->getResetToken(),
            'reset_expiration' => $resetExpiration ? $resetExpiration->format('Y-m-d H:i:s') : null,
        ]);

        $user->setId((int) $this->pdo->lastInsertId());
        return $user;
    }

    private function update(User $user): User
    {
        $stmt = $this->pdo->prepare("
            UPDATE utilisateurs 
            SET nom              = :nom,
                prenom           = :prenom,
                mdp              = :mdp,
                code_verif       = :code_verif,
                reset_token      = :reset_token,
                reset_expiration = :reset_expiration
            WHERE id = :id
        ");

        $resetExpiration = $user->getResetTokenExpiration();
        $stmt->execute([
            'id'               => $user->getId(),
            'nom'              => $user->getLastName(),
            'prenom'           => $user->getFirstName(),
            'mdp'              => $user->getPasswordHash(),
            'code_verif'       => (int) ($user->getVerificationCode() ?? 0),
            'reset_token'      => $user->getResetToken(),
            'reset_expiration' => $resetExpiration ? $resetExpiration->format('Y-m-d H:i:s') : null,
        ]);

        return $user;
    }

    /**
     * Delete user by ID
     */
    public function delete($id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute(['id' => (int) $id]);
    }

    protected function hydrate(array $data): User
    {
        $user = new User();
        $user->setId(isset($data['id']) ? (int) $data['id'] : null);
        $user->setLastName($data['nom'] ?? '');
        $user->setFirstName($data['prenom'] ?? '');
        $user->setEmail($data['mail'] ?? '');
        $user->setPasswordHash($data['mdp'] ?? '');
        $user->setVerificationCode(isset($data['code_verif']) ? (string) $data['code_verif'] : null);
        // utilisateurs table has no account_status column; treat all as active (1)
        $user->setAccountStatus(1);

        if (!empty($data['reset_expiration']) && !empty($data['reset_token'])) {
            $user->setResetToken(
                $data['reset_token'],
                new \DateTimeImmutable($data['reset_expiration'])
            );
        }

        return $user;
    }
}
