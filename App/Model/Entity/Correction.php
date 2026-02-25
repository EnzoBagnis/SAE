<?php
namespace App\Model\Entity;

class Correction {
    private string $exoName;
    private string $funcName;
    private string $entries;
    private string $solution;

    public function getExoName(): string { return $this->exoName; }
    public function setExoName(string $name): void { $this->exoName = $name; }
    // ... autres getters/setters
}
