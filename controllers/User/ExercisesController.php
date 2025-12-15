<?php

namespace Controllers\User;

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/Exercise.php';
require_once __DIR__ . '/../../models/Database.php';

/**
 * ExercisesController - Handles exercise data API
 *
 * This controller provides API endpoints for retrieving exercise data
 * from the database, including filtering by resource ID and sorting by name.
 */
class ExercisesController extends \BaseController
{
    /**
     * @var \PDO Database connection instance
     */
    private $db;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct()
    {
        $database = new \Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get list of exercises
     *
     * Retrieves exercises from the database, optionally filtered by resource ID.
     * Results are sorted alphabetically by exercise name.
     *
     * @return void Outputs JSON response with exercises data
     *
     * @api GET /index.php?action=exercises
     * @apiParam {int} [resource_id] Optional resource ID to filter exercises
     *
     * @apiSuccess {boolean} success Operation success status
     * @apiSuccess {object} data Response data container
     * @apiSuccess {array} data.exercises Array of exercise objects
     *
     * @apiError {boolean} success Always false on error
     * @apiError {string} message Error description
     */
    public function getExercises()
    {
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');

        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Non authentifié'
            ]);
            exit;
        }

        // Check database connection
        if (!$this->db) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de connexion à la base de données'
            ]);
            exit;
        }

        // Get resource_id parameter if provided
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;

        try {
            $exercises = $this->fetchExercises($resourceId);

            echo json_encode([
                'success' => true,
                'data' => [
                    'exercises' => $exercises,
                    'total' => count($exercises)
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error in getExercises: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des exercices'
            ]);
        }
    }

    /**
     * Fetch exercises from database
     *
     * Retrieves exercises with optional resource filtering.
     * Results are sorted by exercise name in ascending order.
     *
     * @param int|null $resourceId Optional resource ID to filter exercises
     * @return array Array of exercise data with keys:
     *               - exercise_id: int
     *               - exo_name: string
     *               - funcname: string|null
     *               - description: string|null
     *               - difficulte: string|null ('facile', 'moyen', 'difficile')
     *               - resource_name: string|null
     */
    private function fetchExercises(?int $resourceId): array
    {
        $sql = "SELECT e.exercise_id, e.exo_name, e.funcname, e.description, e.difficulte, 
                       r.resource_name
                FROM exercises e
                LEFT JOIN resources r ON e.resource_id = r.resource_id";

        $params = [];

        if ($resourceId !== null) {
            $sql .= " WHERE e.resource_id = :resourceId";
            $params['resourceId'] = $resourceId;
        }

        $sql .= " ORDER BY e.exo_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get single exercise details with students who attempted it
     *
     * @return void Outputs JSON response with exercise details
     *
     * @api GET /index.php?action=exercise&id={exerciseId}
     * @apiParam {int} id Exercise ID (required)
     *
     * @apiSuccess {boolean} success Operation success status
     * @apiSuccess {object} data Response data container
     * @apiSuccess {object} data.exercise Exercise details
     * @apiSuccess {array} data.students Students who attempted this exercise
     */
    public function getExercise()
    {
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');

        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Non authentifié'
            ]);
            exit;
        }

        // Check database connection
        if (!$this->db) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de connexion à la base de données'
            ]);
            exit;
        }

        // Get exercise ID
        $exerciseId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$exerciseId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID d\'exercice requis'
            ]);
            exit;
        }

        try {
            // Fetch exercise details
            $exercise = $this->fetchExerciseById($exerciseId);

            if (!$exercise) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Exercice non trouvé'
                ]);
                exit;
            }

            // Fetch students who attempted this exercise
            $students = $this->fetchStudentsByExercise($exerciseId);

            echo json_encode([
                'success' => true,
                'data' => [
                    'exercise' => $exercise,
                    'students' => $students
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error in getExercise: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement de l\'exercice: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Fetch single exercise by ID
     *
     * @param int $exerciseId The exercise ID
     * @return array|null Exercise data or null if not found
     */
    private function fetchExerciseById(int $exerciseId): ?array
    {
        $sql = "SELECT e.exercise_id, e.exo_name, e.funcname, e.solution, e.description, 
                       e.difficulte, r.resource_name
                FROM exercises e
                LEFT JOIN resources r ON e.resource_id = r.resource_id
                WHERE e.exercise_id = :exerciseId";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['exerciseId' => $exerciseId]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Fetch all attempts for a specific exercise with student info
     *
     * @param int $exerciseId The exercise ID
     * @return array Array of attempts with student details
     */
    private function fetchStudentsByExercise(int $exerciseId): array
    {
        $sql = "SELECT a.attempt_id, a.student_id, a.exercise_id, a.upload, a.correct,
                       a.submission_date, a.aes0, a.aes1, a.aes2,
                       s.student_identifier, s.nom_fictif, s.prenom_fictif,
                       e.exo_name, e.funcname, e.description
                FROM attempts a
                INNER JOIN students s ON a.student_id = s.student_id
                INNER JOIN exercises e ON a.exercise_id = e.exercise_id
                WHERE a.exercise_id = :exerciseId
                ORDER BY s.student_identifier ASC, a.submission_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['exerciseId' => $exerciseId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

