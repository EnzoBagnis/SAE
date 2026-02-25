<?php
namespace App\Model\Entity;
class Exercise
{
    private ?int $exerciseId = null;
    private int $resourceId  = 0;
    private string $exoName  = '';
    private ?string $extention = null;
    private ?string $date      = null;
    public function getExerciseId(): ?int { return $this->exerciseId; }
    public function setExerciseId(?int $exerciseId): void { $this->exerciseId = $exerciseId; }
    public function getResourceId(): int { return $this->resourceId; }
    public function setResourceId(int $resourceId): void { $this->resourceId = $resourceId; }
    public function getExoName(): string { return $this->exoName; }
    public function setExoName(string $exoName): void { $this->exoName = $exoName; }
    public function getExtention(): ?string { return $this->extention; }
    public function setExtention(?string $extention): void { $this->extention = $extention; }
    public function getDate(): ?string { return $this->date; }
    public function setDate(?string $date): void { $this->date = $date; }
    public function toArray(): array
    {
        return [
            'exercice_id'   => $this->exerciseId,
            'ressource_id'  => $this->resourceId,
            'exercice_name' => $this->exoName,
            'extention'     => $this->extention,
            'date'          => $this->date,
        ];
    }
}
