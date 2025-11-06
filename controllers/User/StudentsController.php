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
        $this->studentModel = new \Student();
    }

    /**
     * Get paginated list of students
     */
    public function getStudents()
    {
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');

        // Check if model is initialized
        if (!$this->studentModel->isInitialized()) {
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
        // Set up error handler to catch any PHP errors and convert to JSON
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        try {
            // Set JSON header FIRST
            header('Content-Type: application/json; charset=utf-8');

            // Check if model is initialized
            if (!$this->studentModel->isInitialized()) {
                $initError = $this->studentModel->getInitError();
                error_log("Student model not initialized: " . $initError);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur d\'initialisation du service',
                    'debug' => $initError
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

            // Get student information
            error_log("Fetching student by ID: $studentId");
            $student = $this->studentModel->getStudentById($studentId);

            if (!$student) {
                error_log("Student not found: $studentId");
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Étudiant non trouvé'
                ]);
                exit;
            }

            error_log("Student found, fetching attempts for student $studentId with resource $resourceId");
            // Get all attempts for this student
            $attempts = $this->studentModel->getStudentAttempts($studentId, $resourceId);
            error_log("Found " . count($attempts) . " attempts");

            error_log("Fetching stats for student $studentId with resource $resourceId");
            // Get student statistics
            $stats = $this->studentModel->getStudentStats($studentId, $resourceId);
            error_log("Stats fetched successfully");

            // Format the response
            echo json_encode([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $student['student_id'],
                        'identifier' => $student['student_identifier'],
                        'nom_fictif' => $student['nom_fictif'] ?? null,
                        'prenom_fictif' => $student['prenom_fictif'] ?? null,
                        'dataset' => $student['nom_dataset'] ?? null,
                        'pays' => $student['pays'] ?? null,
                        'annee' => $student['annee'] ?? null
                    ],
                    'attempts' => $attempts,
                    'stats' => $stats
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            error_log("Error in getStudent: " . json_encode($errorDetails));

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des données de l\'étudiant',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ]);
        } finally {
            restore_error_handler();
        }
    }
}
