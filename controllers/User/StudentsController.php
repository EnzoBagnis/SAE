<?php
namespace Controllers\User;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/Student.php';

/**
 * StudentsController - Handles student data API
 */
class StudentsController extends \BaseController {

    private $studentModel;

    public function __construct() {
        $this->studentModel = new \Student();
    }

    /**
     * Get paginated list of students
     */
    public function getStudents() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Non authentifié'
            ]);
            exit;
        }

        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 15;

        // Validate parameters
        if ($page < 1) $page = 1;
        if ($perPage < 1 || $perPage > 100) $perPage = 15;

        try {
            $result = $this->studentModel->getPaginatedStudents($page, $perPage);

            // Format students for display
            $formattedStudents = [];
            foreach ($result['students'] as $userId) {
                $formattedStudents[] = [
                    'id' => $userId,
                    'title' => $userId // Afficher "userId_XX" directement
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'students' => $formattedStudents,
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'perPage' => $result['perPage'],
                    'hasMore' => $result['hasMore']
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des étudiants'
            ]);
        }
    }

    /**
     * Get student details and all attempts by ID
     */
    public function getStudent() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Non authentifié'
            ]);
            exit;
        }

        $userId = $_GET['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID étudiant manquant'
            ]);
            exit;
        }

        try {
            // Get all attempts for this student
            $attempts = $this->studentModel->getStudentAttempts($userId);

            if (empty($attempts)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Aucune tentative trouvée pour cet étudiant'
                ]);
                exit;
            }

            // Get statistics
            $stats = $this->studentModel->getStudentStats($userId);

            echo json_encode([
                'success' => true,
                'data' => [
                    'userId' => $userId,
                    'attempts' => $attempts,
                    'stats' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement de l\'étudiant'
            ]);
        }
    }
}
