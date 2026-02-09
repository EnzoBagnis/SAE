<?php

namespace Presentation\Controller\StudentTracking;

use Application\StudentTracking\DTO\ListStudentsRequest;
use Application\StudentTracking\UseCase\ListStudents;

/**
 * Students Controller
 * Handles HTTP requests for student tracking
 */
class StudentsController
{
    private ListStudents $listStudentsUseCase;

    /**
     * Constructor
     *
     * @param ListStudents $listStudentsUseCase List students use case
     */
    public function __construct(ListStudents $listStudentsUseCase)
    {
        $this->listStudentsUseCase = $listStudentsUseCase;
    }

    /**
     * Get students list (API endpoint)
     *
     * @return void Outputs JSON response
     */
    public function getStudents(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Check authentication
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Non authentifié'
            ]);
            return;
        }

        // Get request parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 15;
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        // Create request DTO
        $request = new ListStudentsRequest($page, $perPage, $resourceId);

        // Execute use case
        $response = $this->listStudentsUseCase->execute($request);

        // Return JSON response
        if (!$response->isSuccess()) {
            http_response_code(500);
        }

        echo json_encode($response->toArray());
    }
}
