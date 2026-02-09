<?php

namespace Domain\StudentTracking\Repository;

use Domain\StudentTracking\Entity\Attempt;

/**
 * Attempt Repository Interface
 * Defines contract for attempt data persistence
 */
interface AttemptRepositoryInterface
{
    /**
     * Find attempts by student ID
     *
     * @param int $studentId Student ID
     * @param int|null $resourceId Optional resource ID filter
     * @return Attempt[] Array of attempts
     */
    public function findByStudentId(int $studentId, ?int $resourceId = null): array;

    /**
     * Find attempts by exercise ID
     *
     * @param int $exerciseId Exercise ID
     * @return Attempt[] Array of attempts
     */
    public function findByExerciseId(int $exerciseId): array;

    /**
     * Find attempt by ID
     *
     * @param int $attemptId Attempt ID
     * @return Attempt|null Attempt or null if not found
     */
    public function findById(int $attemptId): ?Attempt;

    /**
     * Count attempts for a student
     *
     * @param int $studentId Student ID
     * @param int|null $resourceId Optional resource ID filter
     * @return int Total count
     */
    public function countByStudentId(int $studentId, ?int $resourceId = null): int;
}
