<?php
namespace App\Model\Entity;

class Attempt {
    private ?int $id = null;
    private int $exerciceId;
    private string $user;
    private bool $correct;
    private string $evalSet;
    private string $upload;
    private string $aes0;
    private string $aes1;
    private string $aes2;

    // Getters & Setters (id, exerciceId, user, correct, etc.)
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function isCorrect(): bool { return $this->correct; }
    public function setCorrect(bool $correct): void { $this->correct = $correct; }
    // ... Ajoutez les autres getters/setters selon le même modèle
}