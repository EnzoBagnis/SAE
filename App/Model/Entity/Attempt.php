<?php

namespace App\Model\Entity;

/**
 * Attempt Entity
 * Represents a student's attempt at an exercise.
 * Maps to the `attempts` table.
 *
 * Schema: attempt_id (PK), student_id, exercise_id, submission_date, extension, correct, upload, eval_set, aes0, aes1, aes2
 */
class Attempt
{
    private ?int $attemptId = null;
    private int $studentId = 0;
    private int $exerciseId = 0;
    private string $submissionDate = '';
    private string $extension = '';
    private int $correct = 0;
    private string $upload = '';
    private string $evalSet = '';
    private string $aes0 = '';
    private string $aes1 = '';
    private string $aes2 = '';

    /**
     * @return int|null
     */
    public function getAttemptId(): ?int
    {
        return $this->attemptId;
    }

    /**
     * @param int|null $attemptId
     * @return void
     */
    public function setAttemptId(?int $attemptId): void
    {
        $this->attemptId = $attemptId;
    }

    /**
     * @return int
     */
    public function getStudentId(): int
    {
        return $this->studentId;
    }

    /**
     * @param int $studentId
     * @return void
     */
    public function setStudentId(int $studentId): void
    {
        $this->studentId = $studentId;
    }

    /**
     * @return int
     */
    public function getExerciseId(): int
    {
        return $this->exerciseId;
    }

    /**
     * @param int $exerciseId
     * @return void
     */
    public function setExerciseId(int $exerciseId): void
    {
        $this->exerciseId = $exerciseId;
    }

    /**
     * @deprecated Use getExerciseId()
     */
    public function getExerciceId(): int
    {
        return $this->exerciseId;
    }

    /**
     * @deprecated Use setExerciseId()
     */
    public function setExerciceId(int $exerciceId): void
    {
        $this->exerciseId = $exerciceId;
    }

    /**
     * @deprecated Use getStudentId()
     */
    public function getUser(): string
    {
        return (string) $this->studentId;
    }

    /**
     * @deprecated Use setStudentId()
     */
    public function setUser(string $user): void
    {
        $this->studentId = (int) $user;
    }

    /**
     * @return string
     */
    public function getSubmissionDate(): string
    {
        return $this->submissionDate;
    }

    /**
     * @param string $submissionDate
     * @return void
     */
    public function setSubmissionDate(string $submissionDate): void
    {
        $this->submissionDate = $submissionDate;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     * @return void
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return int
     */
    public function getCorrect(): int
    {
        return $this->correct;
    }

    /**
     * @param int $correct
     * @return void
     */
    public function setCorrect(int $correct): void
    {
        $this->correct = $correct;
    }

    /**
     * @return string
     */
    public function getEvalSet(): string
    {
        return $this->evalSet;
    }

    /**
     * @param string $evalSet
     * @return void
     */
    public function setEvalSet(string $evalSet): void
    {
        $this->evalSet = $evalSet;
    }

    /**
     * @return string
     */
    public function getUpload(): string
    {
        return $this->upload;
    }

    /**
     * @param string $upload
     * @return void
     */
    public function setUpload(string $upload): void
    {
        $this->upload = $upload;
    }

    /**
     * @return string
     */
    public function getAes0(): string
    {
        return $this->aes0;
    }

    /**
     * @param string $aes0
     * @return void
     */
    public function setAes0(string $aes0): void
    {
        $this->aes0 = $aes0;
    }

    /**
     * @return string
     */
    public function getAes1(): string
    {
        return $this->aes1;
    }

    /**
     * @param string $aes1
     * @return void
     */
    public function setAes1(string $aes1): void
    {
        $this->aes1 = $aes1;
    }

    /**
     * @return string
     */
    public function getAes2(): string
    {
        return $this->aes2;
    }

    /**
     * @param string $aes2
     * @return void
     */
    public function setAes2(string $aes2): void
    {
        $this->aes2 = $aes2;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attempt_id'      => $this->attemptId,
            'student_id'      => $this->studentId,
            'exercise_id'     => $this->exerciseId,
            'submission_date' => $this->submissionDate,
            'extension'       => $this->extension,
            'correct'         => $this->correct,
            'eval_set'        => $this->evalSet,
            'upload'          => $this->upload,
            'aes0'            => $this->aes0,
            'aes1'            => $this->aes1,
            'aes2'            => $this->aes2,
        ];
    }
}

