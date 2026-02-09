<?php

namespace Application\StudentTracking\UseCase;

use Application\StudentTracking\DTO\ListStudentsRequest;
use Application\StudentTracking\DTO\ListStudentsResponse;
use Domain\StudentTracking\Repository\StudentRepositoryInterface;

/**
 * List Students Use Case
 * Handles listing students with pagination
 */
class ListStudents
{
    private StudentRepositoryInterface $studentRepository;

    /**
     * Constructor
     *
     * @param StudentRepositoryInterface $studentRepository Student repository
     */
    public function __construct(StudentRepositoryInterface $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    /**
     * Execute use case
     *
     * @param ListStudentsRequest $request Request data
     * @return ListStudentsResponse Response data
     */
    public function execute(ListStudentsRequest $request): ListStudentsResponse
    {
        try {
            $result = $this->studentRepository->getPaginated(
                $request->getPage(),
                $request->getPerPage(),
                $request->getResourceId()
            );

            error_log(
                "ListStudents: Loaded " . count($result['students']) .
                " students (total: " . $result['total'] . ", perPage: " . $request->getPerPage() . ")"
            );

            return new ListStudentsResponse(
                true,
                $result['students'],
                $result['total'],
                $result['page'],
                $result['perPage'],
                $result['hasMore']
            );
        } catch (\Exception $e) {
            error_log("Error in ListStudents: " . $e->getMessage());
            return new ListStudentsResponse(
                false,
                [],
                0,
                1,
                15,
                false,
                'Erreur lors du chargement des étudiants'
            );
        }
    }
}
