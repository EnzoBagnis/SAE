<?php

namespace App\Model\Entity;

/**
 * Exercise Entity
 * Represents an exercise in the system.
 * Maps to the `exercices` table.
 *
 * Real schema: exercice_id (PK), ressource_id, exercice_name, extention, date
 */
class Exercise
{
    private ?int $exerciseId = null;
    private int $resourceId = 0;
    private string $exoName = '';
    private ?string $extention = null;
    private ?string $date = null;

    /**
     * @return int|null
     */
    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }

    /**
     * @param int|null $exerciseId
     * @return void
     */
    public function setExerciseId(?int $exerciseId): void
    {
        $this->exerciseId = $exerciseId;
    }

    /**
     * @return int
     */
    public function getResourceId(): int
    {
        return $this->resourceId;
    }

    /**
     * @param int $resourceId
     * @return void
     */
    public function setResourceId(int $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @return string
     */
    public function getExoName(): string
    {
        return $this->exoName;
    }

    /**
     * @param string $exoName
     * @return void
     */
    public function setExoName(string $exoName): void
    {
        $this->exoName = $exoName;
    }

    /**
     * @return string|null
     */
    public function getExtention(): ?string
    {
        return $this->extention;
    }

    /**
     * @param string|null $extention
     * @return void
     */
    public function setExtention(?string $extention): void
    {
        $this->extention = $extention;
    }

    /**
     * @return string|null
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * @param string|null $date
     * @return void
     */
    public function setDate(?string $date): void
    {
        $this->date = $date;
    }

    /**
     * @return array<string, mixed>
     */
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


