<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Resource;

/**
 * Resource Repository
 * Handles resource data persistence
 */
class ResourceRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName(): string
    {
        return 'ressources';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return Resource::class;
    }

    /**
     * Find resources by owner user ID
     *
     * @param int $userId Owner user ID
     * @return array Array of Resource entities
     */
    public function findByOwnerUserId(int $userId): array
    {
        $query = "SELECT r.*, u.prenom as owner_firstname, u.nom as owner_lastname, 'owner' as access_type
                 FROM ressources r
                 INNER JOIN utilisateurs u ON r.owner_user_id = u.id
                 WHERE r.owner_user_id = :user_id
                 ORDER BY r.date_creation DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find shared resources for user
     *
     * @param int $userId User ID
     * @return array Array of Resource entities
     */
    public function findSharedWithUser(int $userId): array
    {
        $query = "SELECT r.*, u.prenom as owner_firstname, u.nom as owner_lastname, 'shared' as access_type
                 FROM ressources r
                 INNER JOIN shared_ressources sr ON r.resource_id = sr.ressource_id
                 INNER JOIN utilisateurs u ON r.owner_user_id = u.id
                 WHERE sr.shared_with_user_id = :user_id
                 ORDER BY r.date_creation DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find resource by ID
     *
     * @param int $resourceId Resource ID
     * @return Resource|null Resource entity or null
     */
    public function findById(int $resourceId): ?Resource
    {
        $query = "SELECT r.*, u.prenom as owner_firstname, u.nom as owner_lastname
                 FROM ressources r
                 INNER JOIN utilisateurs u ON r.owner_user_id = u.id
                 WHERE r.resource_id = :resource_id";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['resource_id' => $resourceId]);

        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Save resource (insert or update)
     *
     * @param Resource $resource Resource entity
     * @return Resource Saved resource
     */
    public function save(Resource $resource): Resource
    {
        if ($resource->getResourceId() === null) {
            return $this->insert($resource);
        }
        return $this->update($resource);
    }

    /**
     * Insert new resource
     *
     * @param Resource $resource Resource entity
     * @return Resource Inserted resource
     */
    private function insert(Resource $resource): Resource
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO ressources 
            (owner_user_id, resource_name, description, image_path, date_creation)
            VALUES 
            (:owner_user_id, :resource_name, :description, :image_path, NOW())
        ");

        $stmt->execute([
            'owner_user_id' => $resource->getOwnerUserId(),
            'resource_name' => $resource->getResourceName(),
            'description' => $resource->getDescription(),
            'image_path' => $resource->getImagePath(),
        ]);

        $resource->setResourceId((int) $this->pdo->lastInsertId());
        return $resource;
    }

    /**
     * Update existing resource
     *
     * @param Resource $resource Resource entity
     * @return Resource Updated resource
     */
    private function update(Resource $resource): Resource
    {
        $stmt = $this->pdo->prepare("
            UPDATE ressources 
            SET resource_name = :resource_name,
                description = :description,
                image_path = :image_path
            WHERE resource_id = :resource_id
        ");

        $stmt->execute([
            'resource_id' => $resource->getResourceId(),
            'resource_name' => $resource->getResourceName(),
            'description' => $resource->getDescription(),
            'image_path' => $resource->getImagePath(),
        ]);

        return $resource;
    }

    /**
     * Delete resource by ID
     *
     * @param int $resourceId Resource ID
     * @return bool True if deleted
     */
    public function delete(int $resourceId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM ressources WHERE resource_id = :id");
        return $stmt->execute(['id' => $resourceId]);
    }

    /**
     * Check if user owns resource
     *
     * @param int $resourceId Resource ID
     * @param int $userId User ID
     * @return bool True if user owns resource
     */
    public function isOwner(int $resourceId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM ressources 
            WHERE resource_id = :resource_id AND owner_user_id = :user_id
        ");
        $stmt->execute([
            'resource_id' => $resourceId,
            'user_id' => $userId
        ]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Hydrate resource from database row
     *
     * @param array $data Database row data
     * @return Resource Resource entity
     */
    protected function hydrate(array $data): Resource
    {
        $resource = new Resource();
        $resource->setResourceId($data['resource_id'] ?? null);
        $resource->setOwnerUserId($data['owner_user_id'] ?? 0);
        $resource->setResourceName($data['resource_name'] ?? '');
        $resource->setDescription($data['description'] ?? null);
        $resource->setImagePath($data['image_path'] ?? null);
        $resource->setDateCreation($data['date_creation'] ?? null);
        $resource->setOwnerFirstname($data['owner_firstname'] ?? null);
        $resource->setOwnerLastname($data['owner_lastname'] ?? null);
        $resource->setAccessType($data['access_type'] ?? 'owner');
        return $resource;
    }
}

