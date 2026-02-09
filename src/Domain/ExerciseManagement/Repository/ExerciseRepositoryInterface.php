namespace Domain\ExerciseManagement\Repository;

use Domain\ExerciseManagement\Entity\Exercise;

/**
 * Exercise Repository interface
 * Defines contract for exercise data persistence
 */
interface ExerciseRepositoryInterface
{
    /**
     * Find all exercises
     *
     * @param int | null $resourceId Optional resource ID filter
     * @return Exercise[] Array of exercises
     */
    public function findAll(?int $resourceId = null): array;

    /**
     * Find exercise by ID
     *
     * @param int $exerciseId Exercise ID
     * @return Exercise | null Exercise or null if not found
     */
    public function findById(int $exerciseId): ?Exercise;

    /**
     * Find exercises by resource ID
     *
     * @param int $resourceId Resource ID
     * @return Exercise[] Array of exercises
     */
    public function findByResourceId(int $resourceId): array;

    /**
     * Find exercises by dataset ID
     *
     * @param int $datasetId Dataset ID
     * @return Exercise[] Array of exercises
     */
    public function findByDatasetId(int $datasetId): array;

    /**
     * Count exercises
     *
     * @param int | null $resourceId Optional resource ID filter
     * @return int Total count
     *  /
public function count(?int $resourceId = null): int;
}

<?php
