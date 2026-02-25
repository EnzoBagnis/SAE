<?php
namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Exercice;

class ExerciceRepository extends AbstractRepository {
    protected function getTableName(): string { return 'exercices'; }
    protected function getEntityClass(): string { return Exercice::class; }

    public function findByRessource(int $ressourceId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM exercices WHERE ressource_id = :id");
        $stmt->execute(['id' => $ressourceId]);
        return array_map(fn($row) => $this->hydrate($row), $stmt->fetchAll());
    }

    public function save(Exercice $exo): Exercice {
        return ($exo->getId() === null) ? $this->insert($exo) : $this->update($exo);
    }

    private function insert(Exercice $exo): Exercice {
        $stmt = $this->pdo->prepare("INSERT INTO exercices (ressource_id, exercice_name, extention, date) VALUES (:res_id, :name, :ext, :date)");
        $stmt->execute([
            'res_id' => $exo->getRessourceId(),
            'name'   => $exo->getName(),
            'ext'    => $exo->getExtension(),
            'date'   => $exo->getDate()->format('Y-m-d')
        ]);
        $exo->setId((int)$this->pdo->lastInsertId());
        return $exo;
    }

    private function update(Exercice $exo): Exercice {
        $stmt = $this->pdo->prepare("UPDATE exercices SET ressource_id = :res_id, exercice_name = :name, extention = :ext, date = :date WHERE exercice_id = :id");
        $stmt->execute([
            'res_id' => $exo->getRessourceId(),
            'name'   => $exo->getName(),
            'ext'    => $exo->getExtension(),
            'date'   => $exo->getDate()->format('Y-m-d'),
            'id'     => $exo->getId()
        ]);
        return $exo;
    }

    protected function hydrate(array $data): Exercice {
        $exo = new Exercice();
        $exo->setId($data['exercice_id'] ?? null);
        $exo->setRessourceId((int)$data['ressource_id']);
        $exo->setName($data['exercice_name'] ?? '');
        $exo->setExtension($data['extention'] ?? '');
        $exo->setDate(new \DateTime($data['date']));
        return $exo;
    }
}