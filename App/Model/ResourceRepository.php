<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Resource;

/**
 * Resource Repository
 * Handles resource data persistence against the `ressources` table.
 *
 * Real schema:
 *   ressource_id (PK), owner_mail, ressource_name, ressource_description, image_path
 * Access control:
 *   ressources_access (ressource_id, teacher_mail)
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
     * Find resources owned by a given teacher email.
     *
     * @param string $email Owner email
     * @return Resource[] Array of Resource entities
     */
    public function findByOwnerMail(string $email): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *, 'owner' AS access_type
             FROM ressources
             WHERE owner_mail = :mail
             ORDER BY ressource_id DESC"
        );
        $stmt->execute(['mail' => $email]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Find resources shared with a given teacher email via ressources_access.
     *
     * @param string $email Teacher email
     * @return Resource[] Array of Resource entities
     */
    public function findSharedWithMail(string $email): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*, 'shared' AS access_type
             FROM ressources r
             INNER JOIN ressources_access ra ON r.ressource_id = ra.ressource_id
             WHERE ra.teacher_mail = :mail
             ORDER BY r.ressource_id DESC"
        );
        $stmt->execute(['mail' => $email]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Find a resource by its ID.
     *
     * @param int $resourceId Resource ID
     * @return Resource|null Resource entity or null
     */
    public function findById(int $resourceId): ?Resource
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ressources WHERE ressource_id = :id"
        );
        $stmt->execute(['id' => $resourceId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Save resource (insert or update).
     *
     * @param Resource $resource Resource entity
     * @return Resource Saved resource
     */
    public function save(Resource $resource): Resource
    {
        return $resource->getResourceId() === null
            ? $this->insert($resource)
            : $this->update($resource);
    }

    /**
     * Insert a new resource.
     *
     * @param Resource $resource Resource entity
     * @return Resource Inserted resource with new ID set
     */
    private function insert(Resource $resource): Resource
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO ressources (owner_mail, ressource_name, ressource_description, image_path)
            VALUES (:owner_mail, :ressource_name, :ressource_description, :image_path)
        ");
        $stmt->execute([
            'owner_mail'           => $resource->getOwnerMail(),
            'ressource_name'       => $resource->getResourceName(),
            'ressource_description' => $resource->getDescription() ?? '',
            'image_path'           => $resource->getImagePath() ?? '',
        ]);
        $resource->setResourceId((int) $this->pdo->lastInsertId());
        return $resource;
    }

    /**
     * Update an existing resource.
     *
     * @param Resource $resource Resource entity
     * @return Resource Updated resource
     */
    private function update(Resource $resource): Resource
    {
        $stmt = $this->pdo->prepare("
            UPDATE ressources
            SET ressource_name        = :ressource_name,
                ressource_description = :ressource_description,
                image_path            = :image_path
            WHERE ressource_id = :ressource_id
        ");
        $stmt->execute([
            'ressource_id'         => $resource->getResourceId(),
            'ressource_name'       => $resource->getResourceName(),
            'ressource_description' => $resource->getDescription() ?? '',
            'image_path'           => $resource->getImagePath() ?? '',
        ]);
        return $resource;
    }

    /**
     * Delete a resource by ID.
     *
     * @param int $resourceId Resource ID
     * @return bool True if deleted
     */
    public function delete(mixed $resourceId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM ressources WHERE ressource_id = :id");
        return $stmt->execute(['id' => (int) $resourceId]);
    }

    /**
     * Check if a teacher owns a resource.
     *
     * @param int    $resourceId Resource ID
     * @param string $email      Teacher email
     * @return bool True if the teacher owns the resource
     */
    public function isOwner(int $resourceId, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM ressources
             WHERE ressource_id = :id AND owner_mail = :mail"
        );
        $stmt->execute(['id' => $resourceId, 'mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Hydrate a Resource entity from a database row.
     *
     * @param array $data Database row
     * @return Resource Hydrated Resource entity
     */
    protected function hydrate(array $data): Resource
    {
        $resource = new Resource();
        $resource->setResourceId(isset($data['ressource_id']) ? (int) $data['ressource_id'] : null);
        $resource->setOwnerMail($data['owner_mail'] ?? '');
        $resource->setResourceName($data['ressource_name'] ?? '');
        $resource->setDescription($data['ressource_description'] !== '' ? $data['ressource_description'] : null);
        $resource->setImagePath($data['image_path'] !== '' ? $data['image_path'] : null);
        $resource->setAccessType($data['access_type'] ?? 'owner');
        return $resource;
    }
}

