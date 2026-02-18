<?php

namespace App\Model;

use Core\Model\AbstractRepository;
use App\Model\Entity\Student;

/**
 * Student Repository
 * Handles student data persistence
 */
class StudentRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getTableName(): string
    {
        return 'students';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return Student::class;
    }

    /**
     * Find students by dataset ID
     *
     * @param int $datasetId Dataset ID
     * @return array Array of Student entities
     */
    public function findByDatasetId(int $datasetId): array
    {
        $query = "SELECT s.*, d.nom_dataset
                 FROM students s
                 INNER JOIN datasets d ON s.dataset_id = d.dataset_id
                 WHERE s.dataset_id = :dataset_id
                 ORDER BY s.student_identifier ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['dataset_id' => $datasetId]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrate($row), $results);
    }

    /**
     * Find student by identifier
     *
     * @param string $identifier Student identifier
     * @return Student|null Student entity or null
     */
    public function findByStudentIdentifier(string $identifier): ?Student
    {
        return $this->findByField('student_identifier', $identifier);
    }

    /**
     * Save student (insert or update)
     *
     * @param Student $student Student entity
     * @return Student Saved student
     */
    public function save(Student $student): Student
    {
        if ($student->getStudentId() === null) {
            return $this->insert($student);
        }
        return $this->update($student);
    }

    /**
     * Insert new student
     *
     * @param Student $student Student entity
     * @return Student Inserted student
     */
    private function insert(Student $student): Student
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO students 
            (student_identifier, nom_fictif, prenom_fictif, dataset_id)
            VALUES 
            (:student_identifier, :nom_fictif, :prenom_fictif, :dataset_id)
        ");

        $stmt->execute([
            'student_identifier' => $student->getStudentIdentifier(),
            'nom_fictif' => $student->getNomFictif(),
            'prenom_fictif' => $student->getPrenomFictif(),
            'dataset_id' => $student->getDatasetId(),
        ]);

        $student->setStudentId((int) $this->pdo->lastInsertId());
        return $student;
    }

    /**
     * Update existing student
     *
     * @param Student $student Student entity
     * @return Student Updated student
     */
    private function update(Student $student): Student
    {
        $stmt = $this->pdo->prepare("
            UPDATE students 
            SET student_identifier = :student_identifier,
                nom_fictif = :nom_fictif,
                prenom_fictif = :prenom_fictif,
                dataset_id = :dataset_id
            WHERE student_id = :student_id
        ");

        $stmt->execute([
            'student_id' => $student->getStudentId(),
            'student_identifier' => $student->getStudentIdentifier(),
            'nom_fictif' => $student->getNomFictif(),
            'prenom_fictif' => $student->getPrenomFictif(),
            'dataset_id' => $student->getDatasetId(),
        ]);

        return $student;
    }

    /**
     * Hydrate student from database row
     *
     * @param array $data Database row data
     * @return Student Student entity
     */
    protected function hydrate(array $data): Student
    {
        $student = new Student();
        $student->setStudentId($data['student_id'] ?? null);
        $student->setStudentIdentifier($data['student_identifier'] ?? '');
        $student->setNomFictif($data['nom_fictif'] ?? '');
        $student->setPrenomFictif($data['prenom_fictif'] ?? '');
        $student->setDatasetId($data['dataset_id'] ?? 0);
        $student->setNomDataset($data['nom_dataset'] ?? null);
        return $student;
    }
}

