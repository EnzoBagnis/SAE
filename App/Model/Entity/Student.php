<?php

namespace App\Model\Entity;

/**
 * Student Entity
 * Represents a student in the system
 */
class Student
{
    private ?int $studentId = null;
    private string $studentIdentifier;
    private string $nomFictif;
    private string $prenomFictif;
    private int $datasetId;
    private ?string $nomDataset = null;

    public function getStudentId(): ?int
    {
        return $this->studentId;
    }

    public function setStudentId(?int $studentId): void
    {
        $this->studentId = $studentId;
    }

    public function getStudentIdentifier(): string
    {
        return $this->studentIdentifier;
    }

    public function setStudentIdentifier(string $studentIdentifier): void
    {
        $this->studentIdentifier = $studentIdentifier;
    }

    public function getNomFictif(): string
    {
        return $this->nomFictif;
    }

    public function setNomFictif(string $nomFictif): void
    {
        $this->nomFictif = $nomFictif;
    }

    public function getPrenomFictif(): string
    {
        return $this->prenomFictif;
    }

    public function setPrenomFictif(string $prenomFictif): void
    {
        $this->prenomFictif = $prenomFictif;
    }

    public function getDatasetId(): int
    {
        return $this->datasetId;
    }

    public function setDatasetId(int $datasetId): void
    {
        $this->datasetId = $datasetId;
    }

    public function getNomDataset(): ?string
    {
        return $this->nomDataset;
    }

    public function setNomDataset(?string $nomDataset): void
    {
        $this->nomDataset = $nomDataset;
    }

    public function getFullName(): string
    {
        return $this->prenomFictif . ' ' . $this->nomFictif;
    }

    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'student_identifier' => $this->studentIdentifier,
            'nom_fictif' => $this->nomFictif,
            'prenom_fictif' => $this->prenomFictif,
            'dataset_id' => $this->datasetId,
            'nom_dataset' => $this->nomDataset
        ];
    }
}
