<?php
namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Ressource;

class RessourceRepository extends AbstractRepository {
    protected function getTableName(): string { return 'ressources'; }
    protected function getEntityClass(): string { return Ressource::class; }

    public function findByOwner(string $email): array {
        $stmt = $this->pdo->prepare("SELECT * FROM ressources WHERE owner_mail = :mail");
        $stmt->execute(['mail' => $email]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    public function save(Ressource $res): Ressource {
        return ($res->getId() === null) ? $this->insert($res) : $this->update($res);
    }

    private function insert(Ressource $res): Ressource {
        $stmt = $this->pdo->prepare("INSERT INTO ressources (owner_mail, ressource_name, ressource_description, image_path) VALUES (:mail, :name, :desc, :img)");
        $stmt->execute([
            'mail' => $res->getOwnerMail(),
            'name' => $res->getName(),
            'desc' => $res->getDescription(),
            'img'  => $res->getImagePath()
        ]);
        $res->setId((int)$this->pdo->lastInsertId());
        return $res;
    }

    private function update(Ressource $res): Ressource {
        $stmt = $this->pdo->prepare("UPDATE ressources SET owner_mail = :mail, ressource_name = :name, ressource_description = :desc, image_path = :img WHERE ressource_id = :id");
        $stmt->execute([
            'mail' => $res->getOwnerMail(),
            'name' => $res->getName(),
            'desc' => $res->getDescription(),
            'img'  => $res->getImagePath(),
            'id'   => $res->getId()
        ]);
        return $res;
    }

    protected function hydrate(array $data): Ressource {
        $res = new Ressource();
        $res->setId($data['ressource_id'] ?? null);
        $res->setOwnerMail($data['owner_mail'] ?? '');
        $res->setName($data['ressource_name'] ?? '');
        $res->setDescription($data['ressource_description'] ?? '');
        $res->setImagePath($data['image_path'] ?? '');
        return $res;
    }
}