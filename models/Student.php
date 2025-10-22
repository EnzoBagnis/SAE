<?php

/**
 * Student Model - Handles student data from JSON file
 */
class Student {
    private $dataFile;

    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/NewCaledonia_1014.json';
    }

    /**
     * Get all students from JSON file
     * @return array Array of students
     */
    public function getAllStudents() {
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
     * Get paginated students
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
     * Get student by ID
     * @param string $studentId Student ID
     * @return array|null Student data or null if not found
     */
    public function getStudentById($studentId) {
        $allStudents = $this->getAllStudents();

        foreach ($allStudents as $student) {
            if (isset($student['id']) && $student['id'] == $studentId) {
                return $student;
            }
        }

        return null;
    }

    /**
     * Get unique student IDs
     * @return array Array of unique student IDs
     */
    public function getStudentIds() {
        $allStudents = $this->getAllStudents();
        $ids = [];

        foreach ($allStudents as $student) {
            if (isset($student['id'])) {
                $ids[] = $student['id'];
            }
        }

        return array_unique($ids);
    }
}

