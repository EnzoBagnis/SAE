<?php

namespace Application\StudentTracking\DTO;

use Domain\StudentTracking\Entity\Student;

/**
 * List Students Response DTO
 */
class ListStudentsResponse
{
    private bool $success;
    private array $students;
    private int $total;
    private int $page;
    private int $perPage;
    private bool $hasMore;
    private ?string $error;

    /**
     * Constructor
     *
     * @param bool $success Success status
     * @param Student[] $students Array of students
     * @param int $total Total count
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param bool $hasMore Whether more items exist
     * @param string|null $error Error message
     */
    public function __construct(
        bool $success,
        array $students = [],
        int $total = 0,
        int $page = 1,
        int $perPage = 15,
        bool $hasMore = false,
        ?string $error = null
    ) {
        $this->success = $success;
        $this->students = $students;
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->hasMore = $hasMore;
        $this->error = $error;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getStudents(): array
    {
        return $this->students;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Convert to array
     *
     * @return array Response as array
     */
    public function toArray(): array
    {
        if (!$this->success) {
            return [
                'success' => false,
                'message' => $this->error
            ];
        }

        $formattedStudents = array_map(function (Student $student) {
            return [
                'id' => $student->getStudentId(),
                'identifier' => $student->getStudentIdentifier(),
                'title' => $student->getStudentIdentifier(),
                'nom_fictif' => $student->getNomFictif(),
                'prenom_fictif' => $student->getPrenomFictif(),
                'dataset' => $student->getNomDataset()
            ];
        }, $this->students);

        return [
            'success' => true,
            'data' => [
                'students' => $formattedStudents,
                'total' => $this->total,
                'page' => $this->page,
                'perPage' => $this->perPage,
                'hasMore' => $this->hasMore
            ]
        ];
    }
}
