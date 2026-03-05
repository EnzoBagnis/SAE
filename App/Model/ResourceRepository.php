<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Resource;

/**
 * Resource Repository
 * Handles resource data persistence against the `ressources` table.
 *
 * Schema: ressource_id, mail, ressource_name, ressource_description, image_path
 * Access: ressources_access (ressource_id, mail)
 * Owner info: joined from teachers (name=firstname, surname=lastname)
 */
class ResourceRepository extends AbstractRepository implements ResourceRepositoryInterface
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
     * Find resources owned by a given teacher email, including shared teacher mails list.
     *
     * @param string $email Owner email
     * @return Resource[] Array of Resource entities
     */
    public function findByOwnerMail(string $email): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*,
                    'owner' AS access_type,
                    GROUP_CONCAT(ra.mail) AS shared_mails
             FROM ressources r
             LEFT JOIN ressources_access ra ON r.ressource_id = ra.ressource_id
             WHERE r.mail = :mail
             GROUP BY r.ressource_id
             ORDER BY r.ressource_id DESC"
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
            "SELECT r.*,
                    'shared' AS access_type,
                    GROUP_CONCAT(ra2.mail) AS shared_mails
             FROM ressources r
             INNER JOIN ressources_access ra ON r.ressource_id = ra.ressource_id
             LEFT JOIN ressources_access ra2 ON r.ressource_id = ra2.ressource_id
             WHERE ra.mail = :mail
             GROUP BY r.ressource_id
             ORDER BY r.ressource_id DESC"
        );
        $stmt->execute(['mail' => $email]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Find a resource by its ID, with owner name joined from the teachers table.
     *
     * @param int $resourceId Resource ID
     * @return Resource|null Resource entity or null
     */
    public function findById(int $resourceId): ?Resource
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*,
                    t.name    AS owner_firstname,
                    t.surname AS owner_lastname
             FROM ressources r
             LEFT JOIN teachers t ON CAST(r.mail AS CHAR) = t.mail
             WHERE r.ressource_id = :id"
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
            INSERT INTO ressources (mail, ressource_name, ressource_description, image_path)
            VALUES (:mail, :ressource_name, :ressource_description, :image_path)
        ");
        $stmt->execute([
            'mail'                  => $resource->getOwnerMail(),
            'ressource_name'        => mb_substr($resource->getResourceName(), 0, 20),
            'ressource_description' => $resource->getDescription() ?? '',
            'image_path'            => $resource->getImagePath() ?? '',
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
            'ressource_id'          => $resource->getResourceId(),
            'ressource_name'        => mb_substr($resource->getResourceName(), 0, 20),
            'ressource_description' => $resource->getDescription() ?? '',
            'image_path'            => $resource->getImagePath() ?? '',
        ]);
        return $resource;
    }

    /**
     * Delete a resource and all its related data (exercises, attempts, access).
     * Deletion order respects FK dependencies:
     *   1. attempts linked to the resource's exercises
     *   2. exercices of the resource
     *   3. ressources_access entries
     *   4. the resource itself
     *
     * @param int $resourceId Resource ID
     * @return bool True if deleted
     */
    public function delete($resourceId): bool
    {
        $id = (int) $resourceId;

        $this->pdo->beginTransaction();
        try {
            // 1. Delete attempts linked to exercises of this resource
            $this->pdo->prepare(
                "DELETE a FROM attempts a
                 INNER JOIN exercices e ON a.exercice_id = e.exercice_id
                 WHERE e.ressource_id = :id"
            )->execute(['id' => $id]);

            // 2. Delete exercises of this resource
            $this->pdo->prepare(
                "DELETE FROM exercices WHERE ressource_id = :id"
            )->execute(['id' => $id]);

            // 3. Delete sharing access entries
            $this->pdo->prepare(
                "DELETE FROM ressources_access WHERE ressource_id = :id"
            )->execute(['id' => $id]);

            // 4. Delete the resource itself
            $stmt = $this->pdo->prepare(
                "DELETE FROM ressources WHERE ressource_id = :id"
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
             WHERE ressource_id = :id AND mail = :mail"
        );
        $stmt->execute(['id' => $resourceId, 'mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all teacher emails except the given one (for sharing).
     *
     * @param string $excludeEmail Email to exclude (current user)
     * @return array<array{mail: string, name: string, surname: string}> List of teachers
     */
    public function findAllTeachersExcept(string $excludeEmail): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT mail, name, surname FROM teachers WHERE mail != :mail ORDER BY surname ASC"
        );
        $stmt->execute(['mail' => $excludeEmail]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Sync the sharing list for a resource (replaces all existing access entries).
     *
     * @param int      $resourceId   Resource ID
     * @param string[] $teacherMails List of teacher emails to share with
     * @return void
     */
    public function syncSharing(int $resourceId, array $teacherMails): void
    {
        $this->pdo->prepare(
            "DELETE FROM ressources_access WHERE ressource_id = :id"
        )->execute(['id' => $resourceId]);

        if (empty($teacherMails)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO ressources_access (ressource_id, mail) VALUES (:id, :mail)"
        );
        foreach ($teacherMails as $mail) {
            $mail = trim($mail);
            if ($mail !== '') {
                $stmt->execute(['id' => $resourceId, 'mail' => $mail]);
            }
        }
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
        $resource->setOwnerMail($data['mail'] ?? '');
        $resource->setResourceName($data['ressource_name'] ?? '');
        $resource->setDescription(($data['ressource_description'] ?? '') !== '' ? $data['ressource_description'] : null);
        $resource->setImagePath(($data['image_path'] ?? '') !== '' ? $data['image_path'] : null);
        $resource->setAccessType($data['access_type'] ?? 'owner');
        $resource->setSharedMails($data['shared_mails'] ?? null);
        return $resource;
    }
}

