<?php

namespace App\Model\Entity;

/**
 * Attempt Entity
 * Represents a student's attempt at an exercise.
 * Maps to the `attempts` table.
 *
 * Schema: attempt_id (PK), exercice_id, user_id, correct, eval_set, upload, aes0, aes1, aes2
 */
class Attempt
{
    private ?int $attemptId = null;
    private int $exerciceId = 0;
    private string $userId = '';
    private int $correct = 0;
    private string $evalSet = '';
    private string $upload = '';
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
    public function getExerciceId(): int
    {
        return $this->exerciceId;
    }

    /**
     * @param int $exerciceId
     * @return void
     */
    public function setExerciceId(int $exerciceId): void
    {
        $this->exerciceId = $exerciceId;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return void
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
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
            'attempt_id' => $this->attemptId,
            'exercice_id' => $this->exerciceId,
            'user_id'     => $this->userId,
            'correct'     => $this->correct,
            'eval_set'    => $this->evalSet,
            'upload'      => $this->upload,
            'aes0'        => $this->aes0,
            'aes1'        => $this->aes1,
            'aes2'        => $this->aes2,
        ];
    }
}

