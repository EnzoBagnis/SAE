<?php
namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Attempt;

class AttemptRepository extends AbstractRepository {
    protected function getTableName(): string { return 'attempts'; }
    protected function getEntityClass(): string { return Attempt::class; }

    protected function hydrate(array $data): Attempt {
        $attempt = new Attempt();
        $attempt->setId($data['attempt_id']);
        $attempt->setCorrect((bool)$data['correct']);
        // ... hydratez les autres champs (aes0, aes1, etc.)
        return $attempt;
    }

    // Méthode save() similaire à ExerciceRepository en utilisant attempt_id
}