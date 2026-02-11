<?php

namespace Infrastructure\Repository;

use Domain\ResourceManagement\Entity\Resource;
use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;
use PDO;

/**
 * PDO Resource Repository Implementation
 * Handles resource data persistence using PDO
 */
class PdoResourceRepository implements ResourceRepositoryInterface
{
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findAllAccessibleByUser(int $userId): array
    {
        $sql = "SELECT 
            r.*, 
            u.prenom AS owner_firstname, 
            u.nom AS owner_lastname,
            (SELECT GROUP_CONCAT(user_id) 
             FROM resource_professors_access rpa2 
             WHERE rpa2.resource_id = r.resource_id) AS shared_user_ids,
            CASE 
                WHEN r.owner_user_id = :userId1 THEN 'owner'
                ELSE 'shared'
            END AS access_type
        FROM resources r
        JOIN utilisateurs u ON r.owner_user_id = u.id
        WHERE r.owner_user_id = :userId2 
           OR EXISTS (
               SELECT 1 
               FROM resource_professors_access rpa 
               WHERE rpa.resource_id = r.resource_id 
               AND rpa.user_id = :userId3
           )
        ORDER BY r.resource_name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':userId1', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':userId2', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':userId3', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return $this->hydrateResource($row);
        }, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $resourceId, int $userId): ?Resource
    {
        $sql = "SELECT
            r.*,
            u.prenom AS owner_firstname,
            u.nom AS owner_lastname,
            CASE
                WHEN r.owner_user_id = :userId THEN 'owner'
                ELSE 'shared'
            END AS access_type
        FROM resources r
        JOIN utilisateurs u ON r.owner_user_id = u.id
        WHERE r.resource_id = :resourceId";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'resourceId' => $resourceId,
            'userId' => $userId
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateResource($row);
    }

    /**
     * {@inheritdoc}
     */
    public function userHasAccess(int $resourceId, int $userId): bool
    {
        $sql = "SELECT 1 
                FROM resources r
                WHERE r.resource_id = :resourceId 
                AND (
                    r.owner_user_id = :userId 
                    OR EXISTS (
                        SELECT 1 
                        FROM resource_professors_access rpa 
                        WHERE rpa.resource_id = :resourceId 
                        AND rpa.user_id = :userId
                    )
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'resourceId' => $resourceId,
            'userId' => $userId
        ]);

        return (bool) $stmt->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedUserIds(int $resourceId): array
    {
        $sql = "SELECT user_id 
                FROM resource_professors_access 
                WHERE resource_id = :resourceId";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['resourceId' => $resourceId]);

        return array_map(function ($row) {
            return (int) $row['user_id'];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, int $userId, ?string $description = null, ?string $imagePath = null): int
    {
        $sql = "INSERT INTO resources (resource_name, description, image_path, owner_user_id) 
                VALUES (:name, :description, :image_path, :user_id)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'image_path' => $imagePath,
            'user_id' => $userId
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $resourceId, string $name, ?string $description = null, ?string $imagePath = null): bool
    {
        $sql = "UPDATE resources
                SET resource_name = :name,
                    description = :description";

        $params = [
            'resource_id' => $resourceId,
            'name' => $name,
            'description' => $description
        ];

        // Only update image if provided
        if ($imagePath !== null) {
            $sql .= ", image_path = :image_path";
            $params['image_path'] = $imagePath;
        }

        $sql .= " WHERE resource_id = :resource_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $resourceId): bool
    {
        // Delete shared access first (foreign key constraint)
        $stmt = $this->pdo->prepare("DELETE FROM resource_professors_access WHERE resource_id = :resource_id");
        $stmt->execute(['resource_id' => $resourceId]);

        // Delete resource
        $stmt = $this->pdo->prepare("DELETE FROM resources WHERE resource_id = :resource_id");
        return $stmt->execute(['resource_id' => $resourceId]);
    }

    /**
     * {@inheritdoc}
     */
    public function updateSharedUsers(int $resourceId, array $userIds): bool
    {
        try {
            // Begin transaction
            $this->pdo->beginTransaction();

            // Delete existing shares
            $stmt = $this->pdo->prepare("DELETE FROM resource_professors_access WHERE resource_id = :resource_id");
            $stmt->execute(['resource_id' => $resourceId]);

            // Insert new shares
            if (!empty($userIds)) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO resource_professors_access (resource_id, user_id) VALUES (:resource_id, :user_id)"
                );
                foreach ($userIds as $userId) {
                    $stmt->execute([
                        'resource_id' => $resourceId,
                        'user_id' => $userId
                    ]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating shared users: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hydrate resource from database row
     *
     * @param array $row Database row
     * @return Resource Resource entity
     */
    private function hydrateResource(array $row): Resource
    {
        return new Resource(
            $row['resource_id'],
            $row['owner_user_id'],
            $row['resource_name'],
            $row['description'] ?? null,
            $row['image_path'] ?? null,
            $row['date_creation'],
            $row['owner_firstname'],
            $row['owner_lastname'],
            $row['access_type'] ?? 'owner'
        );
    }
}
