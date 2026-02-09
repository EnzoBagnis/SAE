<?php

namespace Domain\ExerciseManagement\Entity;

/**
 * Exercise Entity
 * Represents an exercise in the system
 */
class Exercise
{
    private int $exerciseId;
    private int $resourceId;
    private string $exoName;
    private ?string $funcname;
    private ?string $solution;
    private ?string $description;
    private ?string $difficulte;
    private string $dateCreation;
    private ?int $datasetId;

    /**
     * Constructor
     *
     * @param int $exerciseId Exercise database ID
     * @param int $resourceId Resource ID
     * @param string $exoName Exercise name
     * @param string|null $funcname Function name
     * @param string|null $solution Solution code
     * @param string|null $description Description
     * @param string|null $difficulte Difficulty level
     * @param string $dateCreation Creation date
     * @param int|null $datasetId Dataset ID
     */
    public function __construct(
        int $exerciseId,
        int $resourceId,
        string $exoName,
        ?string $funcname = null,
        ?string $solution = null,
        ?string $description = null,
        ?string $difficulte = null,
        string $dateCreation = '',
        ?int $datasetId = null
    ) {
        $this->exerciseId = $exerciseId;
        $this->resourceId = $resourceId;
        $this->exoName = $exoName;
        $this->funcname = $funcname;
        $this->solution = $solution;
        $this->description = $description;
        $this->difficulte = $difficulte;
        $this->dateCreation = $dateCreation;
        $this->datasetId = $datasetId;
    }

    public function getExerciseId(): int
    {
        return $this->exerciseId;
    }

    public function getResourceId(): int
    {
        return $this->resourceId;
    }

    public function getExoName(): string
    {
        return $this->exoName;
    }

    public function getFuncname(): ?string
    {
        return $this->funcname;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function getDateCreation(): string
    {
        return $this->dateCreation;
    }

    public function getDatasetId(): ?int
    {
        return $this->datasetId;
    }

    /**
     * Convert to array
     *
     * @return array Exercise data as array
     */
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
            'dataset_id' => $this->datasetId
        ];
    }
}
