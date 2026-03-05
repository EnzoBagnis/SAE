n<?php

namespace App\Model\Entity;

/**
 * Exercise Entity
 * Represents an exercise in the system.
 * Maps to the `exercises` table.
 *
 * Schema: exercise_id (PK), resource_id, exo_name, funcname, solution, description, difficulte, date_creation
 */
class Exercise
{
    private ?int $exerciseId = null;
    private int $resourceId = 0;
    private string $exoName = '';
    private ?string $funcname = null;
    private ?string $solution = null;
    private ?string $description = null;
    private ?string $difficulte = null;
    private ?string $dateCreation = null;

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
    public function getFuncname(): ?string
    {
        return $this->funcname;
    }

    /**
     * @param string|null $funcname
     * @return void
     */
    public function setFuncname(?string $funcname): void
    {
        $this->funcname = $funcname;
    }

    /**
     * @return string|null
     */
    public function getSolution(): ?string
    {
        return $this->solution;
    }

    /**
     * @param string|null $solution
     * @return void
     */
    public function setSolution(?string $solution): void
    {
        $this->solution = $solution;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return void
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    /**
     * @param string|null $difficulte
     * @return void
     */
    public function setDifficulte(?string $difficulte): void
    {
        $this->difficulte = $difficulte;
    }

    /**
     * @return string|null
     */
    public function getDateCreation(): ?string
    {
        return $this->dateCreation;
    }

    /**
     * @param string|null $dateCreation
     * @return void
     */
    public function setDateCreation(?string $dateCreation): void
    {
        $this->dateCreation = $dateCreation;
    }

    /**
     * @deprecated Use getExoName() or getFuncname()
     */
    public function getExtention(): ?string
    {
        return null;
    }

    /**
     * @deprecated
     */
    public function setExtention(?string $extention): void
    {
        // no-op
    }

    /**
     * @deprecated Use getDateCreation()
     */
    public function getDate(): ?string
    {
        return $this->dateCreation;
    }

    /**
     * @deprecated Use setDateCreation()
     */
    public function setDate(?string $date): void
    {
        $this->dateCreation = $date;
    }

    /**
     * Get display name: funcname if available, otherwise exo_name
     */
    public function getDisplayName(): string
    {
        return $this->funcname ?? $this->exoName;
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
            'funcname'      => $this->funcname,
            'solution'      => $this->solution,
            'description'   => $this->description,
            'difficulte'    => $this->difficulte,
            'date_creation' => $this->dateCreation,
        ];
    }
}

