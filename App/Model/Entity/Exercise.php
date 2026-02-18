<?php

namespace App\Model\Entity;

/**
 * Exercise Entity
 * Represents an exercise in the system
 */
class Exercise
{
    private ?int $exerciseId = null;
    private int $resourceId;
    private string $exoName;
    private ?string $funcname = null;
    private ?string $solution = null;
    private ?string $description = null;
    private ?string $difficulte = null;
    private ?string $dateCreation = null;
    private ?int $datasetId = null;

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }

    public function setExerciseId(?int $exerciseId): void
    {
        $this->exerciseId = $exerciseId;
    }

    public function getResourceId(): int
    {
        return $this->resourceId;
    }

    public function setResourceId(int $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getExoName(): string
    {
        return $this->exoName;
    }

    public function setExoName(string $exoName): void
    {
        $this->exoName = $exoName;
    }

    public function getFuncname(): ?string
    {
        return $this->funcname;
    }

    public function setFuncname(?string $funcname): void
    {
        $this->funcname = $funcname;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function setSolution(?string $solution): void
    {
        $this->solution = $solution;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function setDifficulte(?string $difficulte): void
    {
        $this->difficulte = $difficulte;
    }

    public function getDateCreation(): ?string
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?string $dateCreation): void
    {
        $this->dateCreation = $dateCreation;
    }

    public function getDatasetId(): ?int
    {
        return $this->datasetId;
    }

    public function setDatasetId(?int $datasetId): void
    {
        $this->datasetId = $datasetId;
    }

    public function toArray(): array
    {
        return [
            'exercise_id' => $this->exerciseId,
            'resource_id' => $this->resourceId,
            'exo_name' => $this->exoName,
            'funcname' => $this->funcname,
            'solution' => $this->solution,
            'description' => $this->description,
            'difficulte' => $this->difficulte,
            'date_creation' => $this->dateCreation,
            'dataset_id' => $this->datasetId,
        ];
    }
}

