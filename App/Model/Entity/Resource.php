<?php
namespace App\Model\Entity;

class Ressource {
    private ?int $id = null;
    private string $ownerMail;
    private string $name;
    private string $description;
    private string $imagePath;

    // Getters & Setters
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getOwnerMail(): string { return $this->ownerMail; }
    public function setOwnerMail(string $ownerMail): void { $this->ownerMail = $ownerMail; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }

    public function getImagePath(): string { return $this->imagePath; }
    public function setImagePath(string $imagePath): void { $this->imagePath = $imagePath; }
}