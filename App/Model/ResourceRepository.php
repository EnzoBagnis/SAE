<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Resource;

/**
 * Resource Repository
 * Handles resource data persistence against the `resources` table.
 *
 * Schema: resource_id, owner_user_id, resource_name, description, image_path, date_creation
 * Access: resource_professors_access (resource_id, user_id)
 */
class ResourceRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'resources';
    }

    protected function getEntityClass(): string
    {
        return Resource::class;
    }

    /**
     * Find resources owned by a given user ID.
     */
    public function findByOwnerUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*,
                    'owner' AS access_type,
                    GROUP_CONCAT(ra.user_id) AS shared_user_ids
             FROM resources r
             LEFT JOIN resource_professors_access ra ON r.resource_id = ra.resource_id
             WHERE r.owner_user_id = :uid
             GROUP BY r.resource_id
             ORDER BY r.resource_id DESC"
        );
        $stmt->execute(['uid' => $userId]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @deprecated Use findByOwnerUserId()
     */
    public function findByOwnerMail(string $email): array
    {
        // Resolve email to user ID
        $stmt = $this->pdo->prepare("SELECT id FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        $userId = $stmt->fetchColumn();
        if (!$userId) {
            return [];
        }
        return $this->findByOwnerUserId((int) $userId);
    }

    /**
     * Find resources shared with a given user ID via resource_professors_access.
     */
    public function findSharedWithUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*,
                    'shared' AS access_type,
                    GROUP_CONCAT(ra2.user_id) AS shared_user_ids
             FROM resources r
             INNER JOIN resource_professors_access ra ON r.resource_id = ra.resource_id
             LEFT JOIN resource_professors_access ra2 ON r.resource_id = ra2.resource_id
             WHERE ra.user_id = :uid
             GROUP BY r.resource_id
             ORDER BY r.resource_id DESC"
        );
        $stmt->execute(['uid' => $userId]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * @deprecated Use findSharedWithUserId()
     */
    public function findSharedWithMail(string $email): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        $userId = $stmt->fetchColumn();
        if (!$userId) {
            return [];
        }
        return $this->findSharedWithUserId((int) $userId);
    }

    /**
     * Find a resource by its ID, with owner name joined from the utilisateurs table.
     */
    public function findById(int $resourceId): ?Resource
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*,
                    u.prenom AS owner_firstname,
                    u.nom    AS owner_lastname
             FROM resources r
             LEFT JOIN utilisateurs u ON r.owner_user_id = u.id
             WHERE r.resource_id = :id"
        );
        $stmt->execute(['id' => $resourceId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }
        $resource = $this->hydrate($data);
        $resource->setOwnerFirstname($data['owner_firstname'] ?? null);
        $resource->setOwnerLastname($data['owner_lastname'] ?? null);
        return $resource;
    }

    /**
     * Save resource (insert or update).
     */
    public function save(Resource $resource): Resource
    {
        return $resource->getResourceId() === null
            ? $this->insert($resource)
            : $this->update($resource);
    }

    private function insert(Resource $resource): Resource
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO resources (owner_user_id, resource_name, description, image_path)
            VALUES (:owner_user_id, :resource_name, :description, :image_path)
        ");
        $stmt->execute([
            'owner_user_id' => $resource->getOwnerUserId(),
            'resource_name' => $resource->getResourceName(),
            'description'   => $resource->getDescription() ?? '',
            'image_path'    => $resource->getImagePath() ?? '',
        ]);
        $resource->setResourceId((int) $this->pdo->lastInsertId());
        return $resource;
    }

    private function update(Resource $resource): Resource
    {
        $stmt = $this->pdo->prepare("
            UPDATE resources
            SET resource_name = :resource_name,
                description   = :description,
                image_path    = :image_path
            WHERE resource_id = :resource_id
        ");
        $stmt->execute([
            'resource_id'   => $resource->getResourceId(),
            'resource_name' => $resource->getResourceName(),
            'description'   => $resource->getDescription() ?? '',
            'image_path'    => $resource->getImagePath() ?? '',
        ]);
        return $resource;
    }

    /**
     * Delete a resource and all its related data.
     */
    public function delete(mixed $resourceId): bool
    {
        $id = (int) $resourceId;

        $this->pdo->beginTransaction();
        try {
            // 1. Delete attempts linked to exercises of this resource
            $this->pdo->prepare(
                "DELETE a FROM attempts a
                 INNER JOIN exercises e ON a.exercise_id = e.exercise_id
                 WHERE e.resource_id = :id"
            )->execute(['id' => $id]);

            // 2. Delete exercises of this resource
            $this->pdo->prepare(
                "DELETE FROM exercises WHERE resource_id = :id"
            )->execute(['id' => $id]);

            // 3. Delete sharing access entries
            $this->pdo->prepare(
                "DELETE FROM resource_professors_access WHERE resource_id = :id"
            )->execute(['id' => $id]);

            // 4. Delete the resource itself
            $stmt = $this->pdo->prepare(
                "DELETE FROM resources WHERE resource_id = :id"
            );
            $stmt->execute(['id' => $id]);

            $this->pdo->commit();
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[ResourceRepository::delete] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a user owns a resource.
     */
    public function isOwner(int $resourceId, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM resources r
             INNER JOIN utilisateurs u ON r.owner_user_id = u.id
             WHERE r.resource_id = :id AND u.mail = :mail"
        );
        $stmt->execute(['id' => $resourceId, 'mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if a user (by ID) owns a resource.
     */
    public function isOwnerById(int $resourceId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM resources
             WHERE resource_id = :id AND owner_user_id = :uid"
        );
        $stmt->execute(['id' => $resourceId, 'uid' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all teachers except the given one (for sharing).
     */
    public function findAllTeachersExcept(string $excludeEmail): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, mail, prenom AS name, nom AS surname FROM utilisateurs WHERE mail != :mail ORDER BY nom ASC"
        );
        $stmt->execute(['mail' => $excludeEmail]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Sync the sharing list for a resource.
     *
     * @param int   $resourceId Resource ID
     * @param int[] $userIds    List of user IDs to share with
     */
    public function syncSharing(int $resourceId, array $userIds): void
    {
        $this->pdo->prepare(
            "DELETE FROM resource_professors_access WHERE resource_id = :id"
        )->execute(['id' => $resourceId]);

        if (empty($userIds)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO resource_professors_access (resource_id, user_id) VALUES (:id, :uid)"
        );
        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $stmt->execute(['id' => $resourceId, 'uid' => $uid]);
            }
        }
    }

    protected function hydrate(array $data): Resource
    {
        $resource = new Resource();
        $resource->setResourceId(isset($data['resource_id']) ? (int) $data['resource_id'] : null);
        $resource->setOwnerUserId(isset($data['owner_user_id']) ? (int) $data['owner_user_id'] : null);
        $resource->setResourceName($data['resource_name'] ?? '');
        $resource->setDescription(($data['description'] ?? '') !== '' ? $data['description'] : null);
        $resource->setImagePath(($data['image_path'] ?? '') !== '' ? $data['image_path'] : null);
        $resource->setAccessType($data['access_type'] ?? 'owner');
        $resource->setSharedUserIds($data['shared_user_ids'] ?? null);
        return $resource;
    }
}

