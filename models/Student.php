<?php

require_once __DIR__ . '/Database.php';

/**
 * Student Model - Handles student data from database
 */
class Student
{
    private $db;
    private $initError = null;

    public function __construct()
    {
        try {
            $this->db = Database::getConnection();
        } catch (Exception $e) {
            error_log("Student model initialization error: " . $e->getMessage());
            $this->initError = $e->getMessage();
            $this->db = null;
        }
    }

    /**
     * Check if the model is properly initialized
     * @return bool
     */
    public function isInitialized()
    {
        return $this->db !== null;
    }

    /**
     * Get initialization error if any
     * @return string|null
     */
    public function getInitError()
    {
        return $this->initError;
    }

    /**
     * Get all students for a specific resource
     * @param int $resourceId Resource ID
     * @return array Array of students
     */
    public function getAllStudents($resourceId = null)
    {
        try {
            if ($resourceId === null) {
                // Si aucune ressource spécifiée, retourner tous les étudiants
                $query = "SELECT DISTINCT s.student_id, s.student_identifier, " .
                         "s.nom_fictif, s.prenom_fictif, d.nom_dataset " .
                         "FROM students s " .
                         "JOIN datasets d ON s.dataset_id = d.dataset_id " .
                         "ORDER BY CAST(SUBSTRING_INDEX(s.student_identifier, '_', -1) AS UNSIGNED)";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
            } else {
                // Récupérer les étudiants qui ont des tentatives pour les exercices de cette ressource
                $query = "SELECT DISTINCT s.student_id, s.student_identifier, " .
                         "s.nom_fictif, s.prenom_fictif, d.nom_dataset " .
                         "FROM students s " .
                         "JOIN datasets d ON s.dataset_id = d.dataset_id " .
                         "JOIN attempts a ON s.student_id = a.student_id " .
                         "JOIN exercises e ON a.exercise_id = e.exercise_id " .
                         "WHERE e.resource_id = :resource_id " .
                         "ORDER BY CAST(SUBSTRING_INDEX(s.student_identifier, '_', -1) AS UNSIGNED)";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting students: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get paginated students for a specific resource
     * @param int $page Current page number
     * @param int $perPage Number of items per page
     * @param int $resourceId Resource ID (optional)
     * @return array Paginated data with students and metadata
     */
    public function getPaginatedStudents($page = 1, $perPage = 15, $resourceId = null)
    {
        $allStudents = $this->getAllStudents($resourceId);
        $total = count($allStudents);

        $offset = ($page - 1) * $perPage;
        $students = array_slice($allStudents, $offset, $perPage);

        return [
            'students' => $students,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'hasMore' => ($offset + $perPage) < $total
        ];
    }

    /**
     * Get all attempts for a specific student
     * @param int $studentId Student ID (database ID)
     * @param int $resourceId Resource ID (optional, pour filtrer par ressource)
     * @return array Array of attempts with exercise and test cases details
     */
    public function getStudentAttempts($studentId, $resourceId = null)
    {
        try {
            $query = "SELECT 
                        a.attempt_id,
                        a.submission_date,
                        a.extension,
                        a.correct,
                        a.upload,
                        a.eval_set,
                        a.aes0,
                        a.aes1,
                        a.aes2,
                        e.exercise_id,
                        e.exo_name,
                        e.funcname,
                        e.solution,
                        e.description,
                        e.difficulte,
                        r.resource_id,
                        r.resource_name
                     FROM attempts a
                     JOIN exercises e ON a.exercise_id = e.exercise_id
                     JOIN resources r ON e.resource_id = r.resource_id
                     WHERE a.student_id = :student_id";

            if ($resourceId !== null) {
                $query .= " AND e.resource_id = :resource_id";
            }

            $query .= " ORDER BY a.submission_date DESC";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);

            if ($resourceId !== null) {
                $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Pour chaque tentative, récupérer les test cases de l'exercice
            foreach ($attempts as &$attempt) {
                $attempt['test_cases'] = $this->getExerciseTestCases($attempt['exercise_id']);

                // Décoder les JSON AES si présents
                if (!empty($attempt['aes0'])) {
                    $attempt['aes0'] = json_decode($attempt['aes0'], true);
                }
                if (!empty($attempt['aes1'])) {
                    $attempt['aes1'] = json_decode($attempt['aes1'], true);
                }
                if (!empty($attempt['aes2'])) {
                    $attempt['aes2'] = json_decode($attempt['aes2'], true);
                }
            }

            return $attempts;
        } catch (PDOException $e) {
            error_log("Error getting student attempts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get test cases for a specific exercise
     * @param int $exerciseId Exercise ID
     * @return array Array of test cases
     */
    private function getExerciseTestCases($exerciseId)
    {
        try {
            $query = "SELECT input_data, expected_output, test_order
                     FROM test_cases
                     WHERE exercise_id = :exercise_id
                     ORDER BY test_order";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':exercise_id', $exerciseId, PDO::PARAM_INT);
            $stmt->execute();

            $testCases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Décoder les JSON
            foreach ($testCases as &$testCase) {
                $testCase['input_data'] = !empty($testCase['input_data'])
                    ? json_decode($testCase['input_data'], true)
                    : null;
                $testCase['expected_output'] = !empty($testCase['expected_output'])
                    ? json_decode($testCase['expected_output'], true)
                    : null;
            }

            return $testCases;
        } catch (PDOException $e) {
            error_log("Error getting test cases: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get student by ID
     * @param int $studentId Student ID
     * @return array|null Student data or null if not found
     */
    public function getStudentById($studentId)
    {
        try {
            $query = "SELECT s.*, d.nom_dataset, d.pays, d.annee
                     FROM students s
                     JOIN datasets d ON s.dataset_id = d.dataset_id
                     WHERE s.student_id = :student_id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting student by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get statistics for a student
     * @param int $studentId Student ID
     * @param int $resourceId Resource ID (optional)
     * @return array Statistics
     */
    public function getStudentStats($studentId, $resourceId = null)
    {
        try {
            $query = "SELECT 
                        COUNT(a.attempt_id) as total_attempts,
                        SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END) as correct_attempts,
                        COUNT(DISTINCT a.exercise_id) as unique_exercises,
                        COUNT(DISTINCT CASE WHEN a.correct = 1 THEN a.exercise_id END) as exercises_mastered,
                        MIN(a.submission_date) as first_attempt,
                        MAX(a.submission_date) as last_attempt
                     FROM attempts a
                     JOIN exercises e ON a.exercise_id = e.exercise_id
                     WHERE a.student_id = :student_id";

            if ($resourceId !== null) {
                $query .= " AND e.resource_id = :resource_id";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);

            if ($resourceId !== null) {
                $stmt->bindParam(':resource_id', $resourceId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculer le taux de réussite
            if ($stats['total_attempts'] > 0) {
                $stats['success_rate'] = round(($stats['correct_attempts'] / $stats['total_attempts']) * 100, 2);
            } else {
                $stats['success_rate'] = 0;
            }

            return $stats;
        } catch (PDOException $e) {
            error_log("Error getting student stats: " . $e->getMessage());
            return [
                'total_attempts' => 0,
                'correct_attempts' => 0,
                'unique_exercises' => 0,
                'exercises_mastered' => 0,
                'success_rate' => 0,
                'first_attempt' => null,
                'last_attempt' => null
            ];
        }
    }

    /**
     * Get student by identifier (student_identifier)
     * @param string $identifier Student identifier (e.g., "userId_36")
     * @param int $datasetId Dataset ID (optional)
     * @return array|null Student data or null if not found
     */
    public function getStudentByIdentifier($identifier, $datasetId = null)
    {
        try {
            $query = "SELECT s.*, d.nom_dataset, d.pays, d.annee
                     FROM students s
                     JOIN datasets d ON s.dataset_id = d.dataset_id
                     WHERE s.student_identifier = :identifier";

            if ($datasetId !== null) {
                $query .= " AND s.dataset_id = :dataset_id";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);

            if ($datasetId !== null) {
                $stmt->bindParam(':dataset_id', $datasetId, PDO::PARAM_INT);
            }

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting student by identifier: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get statistics for all students in a resource
     * This method calculates the success rate (or average score) for each student.
     * @param int|null $resourceId The resource ID
     * @return array
     */
    public function getStudentStatistics($resourceId = null)
    {
        if (!$this->isInitialized()) {
            return [];
        }

        try {
            // This query calculates the average success rate for each student.
            // Assuming 'tentatives' table has a 'reussi' column (boolean or int 0/1)
            // and links to 'utilisateurs' (students).
            // We join users and attempts.

            // Adjust table names and column names based on your schema.
            // Assuming:
            // - utilisateurs (id, nom, prenom, role)
            // - tentatives (id, user_id, exercise_id, reussi)
            // - exercises (exercise_id, resource_id)

            $sql = "SELECT 
                        s.student_id as id, 
                        s.student_identifier,
                        s.nom_fictif as nom, 
                        s.prenom_fictif as prenom, 
                        COUNT(a.attempt_id) as total_attempts, 
                        SUM(CASE WHEN a.correct = 1 THEN 1 ELSE 0 END) as successful_attempts
                    FROM students s
                    LEFT JOIN attempts a ON s.student_id = a.student_id
                    LEFT JOIN exercises e ON a.exercise_id = e.exercise_id
                    WHERE 1=1";

            $params = [];
            if ($resourceId !== null) {
                $sql .= " AND e.resource_id = :resource_id";
                $params[':resource_id'] = $resourceId;
            }

            $sql .= " GROUP BY s.student_id, s.student_identifier, s.nom_fictif, s.prenom_fictif ORDER BY s.student_identifier";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate percentage
            foreach ($results as &$row) {
                if ($row['total_attempts'] > 0) {
                    $row['success_rate'] = round(($row['successful_attempts'] / $row['total_attempts']) * 100, 2);
                } else {
                    $row['success_rate'] = 0;
                }
            }

            return $results;

        } catch (PDOException $e) {
            error_log("Error getting student stats: " . $e->getMessage());
            return [];
        }
    }
}
