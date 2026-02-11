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
     * {@inheritdoc}
     */
    public function findAllBanned(): array
    {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'utilisateurs_bannis'");
            if ($stmt->rowCount() === 0) {
                return [];
            }

            // Get all banned users
            $stmt = $this->pdo->query("SELECT * FROM utilisateurs_bannis ORDER BY date_de_ban DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function($row) {
                return [
                    'id' => $row['id'],
                    'mail' => $row['mail'],
                    'date_de_ban' => $row['date_de_ban'],
                    'duree_ban' => $row['duree_ban'] ?? null,
                    'ban_def' => $row['ban_definitif'] ?? 1
                ];
            }, $data);
        } catch (\Exception $e) {
            error_log("Get banned users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function banUser(int $userId, string $email): bool
    {
        try {
            // Create table if not exists
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs_bannis (
                id INT PRIMARY KEY,
                mail VARCHAR(255) NOT NULL,
                date_de_ban DATE NOT NULL,
                duree_ban INT DEFAULT NULL,
                ban_definitif TINYINT(1) DEFAULT 1
            )");

            // Check if user already banned
            $checkStmt = $this->pdo->prepare("SELECT id FROM utilisateurs_bannis WHERE id = :id");
            $checkStmt->execute(['id' => $userId]);

            $dateBan = date('Y-m-d');

            if ($checkStmt->rowCount() > 0) {
                // Update existing ban
                $stmt = $this->pdo->prepare(
                    "UPDATE utilisateurs_bannis 
                     SET date_de_ban = :date_de_ban, duree_ban = NULL, mail = :mail 
                     WHERE id = :id"
                );
            } else {
                // Insert new ban
                $stmt = $this->pdo->prepare(
                    "INSERT INTO utilisateurs_bannis (id, mail, date_de_ban, duree_ban, ban_definitif) 
                     VALUES (:id, :mail, :date_de_ban, NULL, 1)"
                );
            }

            try {
                $result = $stmt->execute([
                    'id' => $userId,
                    'mail' => $email,
                    'date_de_ban' => $dateBan
                ]);
            } catch (\PDOException $pdoEx) {
                error_log("PDO Exception in banUser: " . $pdoEx->getMessage());
                return false;
            }

            if ($result) {
                error_log("Successfully banned user ID: $userId, Email: $email");
            } else {
                error_log("Failed to ban user ID: $userId, Email: $email");
                $errorInfo = $stmt->errorInfo();
                error_log("SQL Error Info: " . print_r($errorInfo, true));
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Ban user error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unbanUser(string $email): bool
    {
        try {
            // Delete from banned users table
            $stmt = $this->pdo->prepare("DELETE FROM utilisateurs_bannis WHERE mail = :mail");
            return $stmt->execute(['mail' => $email]);
        } catch (\Exception $e) {
            error_log("Unban user error: " . $e->getMessage());
            return false;
        }
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
