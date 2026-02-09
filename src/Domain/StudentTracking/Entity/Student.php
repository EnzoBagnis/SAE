<?php

namespace Domain\StudentTracking\Entity;

/**
 * Student Entity
 * Represents a student in the system
 */
class Student
{
    private int $studentId;
    private string $studentIdentifier;
    private string $nomFictif;
    private string $prenomFictif;
    private int $datasetId;
    private string $nomDataset;

    /**
     * Constructor
     *
     * @param int $studentId Student database ID
     * @param string $studentIdentifier Student identifier (e.g., student_1)
     * @param string $nomFictif Student fictional last name
     * @param string $prenomFictif Student fictional first name
     * @param int $datasetId Dataset ID
     * @param string $nomDataset Dataset name
     */
    public function __construct(
        int $studentId,
        string $studentIdentifier,
        string $nomFictif,
        string $prenomFictif,
        int $datasetId,
        string $nomDataset
    ) {
        $this->studentId = $studentId;
        $this->studentIdentifier = $studentIdentifier;
        $this->nomFictif = $nomFictif;
        $this->prenomFictif = $prenomFictif;
        $this->datasetId = $datasetId;
        $this->nomDataset = $nomDataset;
    }

    public function getStudentId(): int
    {
        return $this->studentId;
    }

    public function getStudentIdentifier(): string
    {
        return $this->studentIdentifier;
    }

    public function getNomFictif(): string
    {
        return $this->nomFictif;
    }

    public function getPrenomFictif(): string
    {
        return $this->prenomFictif;
    }

    public function getDatasetId(): int
    {
        return $this->datasetId;
    }

    public function getNomDataset(): string
    {
        return $this->nomDataset;
    }

    /**
     * Get full name
     *
     * @return string Full fictional name
     */
    public function getFullName(): string
    {
        return $this->prenomFictif . ' ' . $this->nomFictif;
    }

    /**
     * Convert to array
     *
     * @return array Student data as array
     */
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
