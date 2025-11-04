<?php

class Resource
{
    private ?PDO $db;
    public int $resource_id;
    public int $owner_user_id;
    public string $resource_name;
    public ?string $description;
    public ?string $image_path;
    public string $date_creation;
    public string $owner_firstname;
    public string $owner_lastname;
    public string $access_type; // 'owner' ou 'shared' - Nouvelle propriété

    public function __construct(PDO $db = null)
    {
        $this->db = $db;
    }

    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    // Récupère toutes les ressources accessibles par un utilisateur donné (propriétaire ou avec accès)
    // Ajout d'une colonne 'access_type' pour savoir si l'utilisateur est propriétaire ou a un accès partagé
    public static function getAllAccessibleResources(PDO $db, int $userId): array
    {
        $stmt = $db->prepare("
            SELECT
                r.*,
                u.prenom AS owner_firstname,
                u.nom AS owner_lastname,
                CASE
                    WHEN r.owner_user_id = :userId THEN 'owner'
                    WHEN rpa.user_id = :userId THEN 'shared'
                    ELSE 'none' -- Ne devrait pas arriver avec la clause WHERE
                END AS access_type
            FROM resources r
            JOIN utilisateurs u ON r.owner_user_id = u.id
            LEFT JOIN resource_professors_access rpa ON r.resource_id = rpa.resource_id
            WHERE r.owner_user_id = :userId OR rpa.user_id = :userId
            GROUP BY r.resource_id, r.owner_user_id, r.resource_name, r.description, 
                r.image_path, r.date_creation, u.prenom, u.nom, rpa.user_id
            ORDER BY r.resource_name ASC
        ");
        $stmt->execute(['userId' => $userId]);
        $resourcesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resources = [];
        foreach ($resourcesData as $data) {
            $resource = new Resource();
            $resources[] = $resource->hydrate($data);
        }
        return $resources;
    }

    // Récupère une ressource par son ID
    public static function getResourceById(PDO $db, int $resourceId): ?Resource
    {
        $stmt = $db->prepare("
            SELECT
                r.*,
                u.prenom AS owner_firstname,
                u.nom AS owner_lastname,
                -- Vous pouvez ajouter access_type ici aussi si nécessaire
                CASE
                    WHEN r.owner_user_id = :currentUserId THEN 'owner'
                    ELSE 'shared' -- Ou 'none' si vous gérez les accès plus finement ici
                END AS access_type
            FROM resources r
            JOIN utilisateurs u ON r.owner_user_id = u.id
            WHERE r.resource_id = :resourceId
        ");
        // Pour le moment, nous passons un dummy currentUserId pour le CASE,
        // dans resource_details, la vérification d'accès sera plus robuste.
        // Si le user n'a pas accès, il sera redirigé avant d'atteindre ce point.
        $stmt->execute(['resourceId' => $resourceId, 'currentUserId' => $_SESSION['user_id'] ?? 0]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $resource = new Resource();
            return $resource->hydrate($data);
        }
        return null;
    }
}
