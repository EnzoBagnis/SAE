<?php

namespace Domain\StudentTracking\Entity;

/**
 * Attempt Entity
 * Represents a student's attempt at an exercise
 */
class Attempt
{
    private int $attemptId;
    private int $studentId;
    private int $exerciseId;
    private string $submissionDate;
    private string $extension;
    private int $correct;
    private ?string $aes2;
    private ?string $code;

    /**
     * Constructor
     *
     * @param int $attemptId Attempt database ID
     * @param int $studentId Student ID
     * @param int $exerciseId Exercise ID
     * @param string $submissionDate Submission date
     * @param string $extension File extension
     * @param int $correct Number of correct test cases
     * @param string|null $aes2 AES2 vector
     * @param string|null $code Submitted code
     */
    public function __construct(
        int $attemptId,
        int $studentId,
        int $exerciseId,
        string $submissionDate,
        string $extension,
        int $correct,
        ?string $aes2 = null,
        ?string $code = null
    ) {
        $this->attemptId = $attemptId;
        $this->studentId = $studentId;
        $this->exerciseId = $exerciseId;
        $this->submissionDate = $submissionDate;
        $this->extension = $extension;
        $this->correct = $correct;
        $this->aes2 = $aes2;
        $this->code = $code;
    }

    public function getAttemptId(): int
    {
        return $this->attemptId;
    }

    public function getStudentId(): int
    {
        return $this->studentId;
    }

    public function getExerciseId(): int
    {
        return $this->exerciseId;
    }

    public function getSubmissionDate(): string
    {
        return $this->submissionDate;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getCorrect(): int
    {
        return $this->correct;
    }

    public function getAes2(): ?string
    {
        return $this->aes2;
    }

    public function getCode(): ?string
    {
        return $this->code;
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
            'code' => $this->code
        ];
    }
}
