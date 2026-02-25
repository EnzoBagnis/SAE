<?php
namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Correction;

class CorrectionRepository extends AbstractRepository {
    protected function getTableName(): string { return 'corrections'; }
    protected function getEntityClass(): string { return Correction::class; }

    public function findByName(string $name): ?Correction {
        $stmt = $this->pdo->prepare("SELECT * FROM corrections WHERE exo_name = :name");
        $stmt->execute(['name' => $name]);
        $data = $stmt->fetch();
        return $data ? $this->hydrate($data) : null;
    }

    protected function hydrate(array $data): Correction {
        $c = new Correction();
        $c->setExoName($data['exo_name']);
        $c->setFuncName($data['funcname']);
        $c->setEntries($data['entries']);
        $c->setSolution($data['solution']);
        return $c;
    }
}