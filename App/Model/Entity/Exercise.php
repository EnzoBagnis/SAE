<?php
namespace App\Model\Entity;

class Exercice {
    private ?int $id = null;
    private int $ressourceId;
    private string $name;
    private string $extension;
    private \DateTimeInterface $date;

    // Getters & Setters ...
    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }
    public function getRessourceId(): int { return $this->ressourceId; }
    public function setRessourceId(int $id): void { $this->ressourceId = $id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getExtension(): string { return $this->extension; }
    public function setExtension(string $ext): void { $this->extension = $ext; }
    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): void { $this->date = $date; }
}