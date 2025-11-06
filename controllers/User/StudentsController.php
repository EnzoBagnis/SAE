<?php

namespace Controllers\User;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/Student.php';

/**
 * StudentsController - Handles student data API
 */
class StudentsController extends \BaseController
{
    private $studentModel;

    public function __construct()
    {
        try {
            $this->studentModel = new \Student();
        } catch (\Exception $e) {
            error_log("StudentsController initialization error: " . $e->getMessage());
            $this->studentModel = null;
        }
    }

    /**
     * Get paginated list of students
     */
    public function getStudents()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');

        // Check if model is initialized
        if ($this->studentModel === null) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur d\'initialisation du service'
            ]);
            exit;
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
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        // Validate parameters
        if ($page < 1) {
            $page = 1;
        }
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        try {
            $result = $this->studentModel->getPaginatedStudents($page, $perPage, $resourceId);

            // Format students for display
            $formattedStudents = [];
            foreach ($result['students'] as $student) {
                $formattedStudents[] = [
                    'id' => $student['student_id'],
                    'identifier' => $student['student_identifier'],
                    'title' => $student['student_identifier'],
                    'nom_fictif' => $student['nom_fictif'],
                    'prenom_fictif' => $student['prenom_fictif'],
                    'dataset' => $student['nom_dataset']
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
            error_log("Error in getStudents: " . $e->getMessage());
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
    public function getStudent()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');

        // Check if model is initialized
        if ($this->studentModel === null) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur d\'initialisation du service'
            ]);
            exit;
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

        // Get student ID from URL parameter
        $studentId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        if (!$studentId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID étudiant manquant'
            ]);
            exit;
        }

        try {
            // Get student information
            $student = $this->studentModel->getStudentById($studentId);

            if (!$student) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Étudiant non trouvé'
                ]);
                exit;
            }

            // Get all attempts for this student
            $attempts = $this->studentModel->getStudentAttempts($studentId, $resourceId);

            // Get student statistics
            $stats = $this->studentModel->getStudentStats($studentId, $resourceId);

            // Format the response
            echo json_encode([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $student['student_id'],
                        'identifier' => $student['student_identifier'],
                        'nom_fictif' => $student['nom_fictif'],
                        'prenom_fictif' => $student['prenom_fictif'],
                        'dataset' => $student['nom_dataset'],
                        'pays' => $student['pays'],
                        'annee' => $student['annee']
                    ],
                    'attempts' => $attempts,
                    'stats' => $stats
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            error_log("Error in getStudent: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des données de l\'étudiant'
            ]);
        }
    }
}

