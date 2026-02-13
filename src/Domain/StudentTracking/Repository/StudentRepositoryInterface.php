<?php

namespace Domain\StudentTracking\Repository;

use Domain\StudentTracking\Entity\Student;

/**
 * Student Repository interface
 * Defines contract for student data persistence
 */
interface StudentRepositoryInterface
{
    /**
     * Find all students
     *
     * @param int | null $resourceId Optional resource ID filter
     * @return Student[] Array of students
     */
    public function findAll(?int $resourceId = null): array;

    /**
     * Find student by ID
     *
     * @param int $studentId Student ID
     * @return Student | null Student or null if not found
     */
    public function findById(int $studentId): ?Student;

    /**
     * Find students by dataset ID
     *
     * @param int $datasetId Dataset ID
     * @return Student[] Array of students
     */
    public function findByDatasetId(int $datasetId): array;

    /**
     * Get paginated students
     *
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param int | null $resourceId Optional resource ID filter
     * @return array Paginated result with students and metadata
     */
    public function getPaginated(int $page, int $perPage, ?int $resourceId = null): array;

    /**
     * Count total students
     *
     * @param int | null $resourceId Optional resource ID filter
     * @return int Total count
     *
     * Note: This method is used for pagination to determine total pages
     */
public function count(?int $resourceId = null): int;
}


