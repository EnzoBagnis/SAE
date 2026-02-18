<?php

namespace App\Model\Entity;

/**
 * Attempt Entity
 * Represents a student's attempt at an exercise
 */
class Attempt
{
    private ?int $attemptId = null;
    private int $studentId;
    private int $exerciseId;
    private ?string $submissionDate = null;
    private ?string $extension = null;
    private int $correct = 0;
    private ?string $aes2 = null;
    private ?string $code = null;

    // Getters and Setters

    public function getAttemptId(): ?int
    {
        return $this->attemptId;
    }

    public function setAttemptId(?int $attemptId): void
    {
        $this->attemptId = $attemptId;
    }

    public function getStudentId(): int
    {
        return $this->studentId;
    }

    public function setStudentId(int $studentId): void
    {
        $this->studentId = $studentId;
    }

    public function getExerciseId(): int
    {
        return $this->exerciseId;
    }

    public function setExerciseId(int $exerciseId): void
    {
        $this->exerciseId = $exerciseId;
    }

    public function getSubmissionDate(): ?string
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(?string $submissionDate): void
    {
        $this->submissionDate = $submissionDate;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
    }

    public function getCorrect(): int
    {
        return $this->correct;
    }

    public function setCorrect(int $correct): void
    {
        $this->correct = $correct;
    }

    public function getAes2(): ?string
    {
        return $this->aes2;
    }

    public function setAes2(?string $aes2): void
    {
        $this->aes2 = $aes2;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    /**
     * Check if attempt is successful
     *
     * @param int $totalTestCases Total number of test cases
     * @return bool True if all test cases passed
     */
    public function isSuccessful(int $totalTestCases): bool
    {
        return $this->correct === $totalTestCases;
    }

    /**
     * Convert to array
     *
     * @return array Attempt data as array
     */
    public function toArray(): array
    {
        return [
            'attempt_id' => $this->attemptId,
            'student_id' => $this->studentId,
            'exercise_id' => $this->exerciseId,
            'submission_date' => $this->submissionDate,
            'extension' => $this->extension,
            'correct' => $this->correct,
            'aes2' => $this->aes2,
            'code' => $this->code,
        ];
    }
}

