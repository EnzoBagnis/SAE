<?php

/**
 * Student Model - Handles student data from JSON file
 */
class Student {
    private $dataFile;
    private $exercisesFile;
    private $exercisesData = null;

    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/NewCaledonia_1014.json';
        $this->exercisesFile = __DIR__ . '/../data/NewCaledonia_exercises.json';
    }

    /**
     * Get all attempts from JSON file
     * @return array Array of attempts
     */
    public function getAllAttempts() {
        if (!file_exists($this->dataFile)) {
            return [];
        }

        $jsonContent = file_get_contents($this->dataFile);
        $data = json_decode($jsonContent, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Load exercises data from JSON file
     * @return array Array of exercises
     */
    private function loadExercises() {
        if ($this->exercisesData !== null) {
            return $this->exercisesData;
        }

        if (!file_exists($this->exercisesFile)) {
            $this->exercisesData = [];
            return $this->exercisesData;
        }

        $jsonContent = file_get_contents($this->exercisesFile);
        $this->exercisesData = json_decode($jsonContent, true);

        if (!is_array($this->exercisesData)) {
            $this->exercisesData = [];
        }

        return $this->exercisesData;
    }

    /**
     * Get exercise details by exercise name
     * @param string $exerciseName Exercise name (exo_name)
     * @return array|null Exercise details or null if not found
     */
    private function getExerciseByName($exerciseName) {
        $exercises = $this->loadExercises();

        foreach ($exercises as $exercise) {
            if (isset($exercise['exo_name']) && $exercise['exo_name'] === $exerciseName) {
                return $exercise;
            }
        }

        return null;
    }

    /**
     * Get all unique students (users)
     * @return array Array of unique user IDs
     */
    public function getAllStudents() {
        $allAttempts = $this->getAllAttempts();
        $students = [];

        foreach ($allAttempts as $attempt) {
            if (isset($attempt['user']) && !in_array($attempt['user'], $students)) {
                $students[] = $attempt['user'];
            }
        }

        // Tri naturel pour les identifiants numériques
        // Support pour userId_XX, userid_XX, ou userIdI_XX (insensible à la casse)
        usort($students, function($a, $b) {
            // Extraire les numéros - pattern flexible pour capturer le numéro après le dernier underscore
            $numA = 0;
            $numB = 0;

            // Pattern: chercher n'importe quel texte suivi de underscore et des chiffres
            // Ex: userId_1, userid_10, userIdI_5, etc.
            if (preg_match('/_(\d+)$/', $a, $matchA)) {
                $numA = (int)$matchA[1];
            }

            if (preg_match('/_(\d+)$/', $b, $matchB)) {
                $numB = (int)$matchB[1];
            }

            // Comparer les numéros extraits
            if ($numA === $numB) {
                return 0;
            }
            return ($numA < $numB) ? -1 : 1;
        });

        return $students;
    }

    /**
     * Get paginated students (unique users)
     * @param int $page Current page number
     * @param int $perPage Number of items per page
     * @return array Paginated data with students and metadata
     */
    public function getPaginatedStudents($page = 1, $perPage = 15) {
        $allStudents = $this->getAllStudents();
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
     * Get all attempts for a specific student (user)
     * @param string $userId User ID (e.g., "userId_36")
     * @return array Array of attempts for this user with test cases
     */
    public function getStudentAttempts($userId) {
        $allAttempts = $this->getAllAttempts();
        $userAttempts = [];

        foreach ($allAttempts as $attempt) {
            if (isset($attempt['user']) && $attempt['user'] === $userId) {
                // Ajouter les test cases de l'exercice
                if (isset($attempt['exercise_name'])) {
                    $exercise = $this->getExerciseByName($attempt['exercise_name']);
                    if ($exercise && isset($exercise['entries'])) {
                        $attempt['test_cases'] = $exercise['entries'];
                        $attempt['funcname'] = $exercise['funcname'] ?? null;
                    }
                }
                $userAttempts[] = $attempt;
            }
        }

        // Sort by date (most recent first)
        usort($userAttempts, function($a, $b) {
            $dateA = isset($a['date']) ? strtotime($a['date']) : 0;
            $dateB = isset($b['date']) ? strtotime($b['date']) : 0;
            return $dateB - $dateA;
        });

        return $userAttempts;
    }

    /**
     * Get statistics for a student
     * @param string $userId User ID
     * @return array Statistics
     */
    public function getStudentStats($userId) {
        $attempts = $this->getStudentAttempts($userId);
        $total = count($attempts);
        $correct = 0;
        $exercises = [];

        foreach ($attempts as $attempt) {
            if (isset($attempt['correct']) && $attempt['correct'] == 1) {
                $correct++;
            }
            if (isset($attempt['exercise_name'])) {
                $exercises[$attempt['exercise_name']] = true;
            }
        }

        return [
            'total_attempts' => $total,
            'correct_attempts' => $correct,
            'success_rate' => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            'unique_exercises' => count($exercises)
        ];
    }
}
