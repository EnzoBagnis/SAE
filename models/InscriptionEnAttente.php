<?php
/**
 * Model InscriptionEnAttente - Gestion CRUD des inscriptions en attente avec PDO
 */
class InscriptionEnAttente {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * CREATE - Créer une nouvelle inscription en attente
     */
    public function create($nom, $prenom, $mail, $mdp, $code_verif) {
        $hashedPassword = password_hash($mdp, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO inscriptions_en_attente (nom, prenom, mdp, mail, code_verif, date_creation) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif, NOW())"
        );

        return $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'mdp' => $hashedPassword,
            'mail' => $mail,
            'code_verif' => $code_verif
        ]);
    }

    /**
     * READ - Trouver une inscription par email
     */
    public function findByEmail($mail) {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $mail]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Vérifier si un email existe en attente
     */
    public function emailExists($mail) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $mail]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * UPDATE - Mettre à jour le code de vérification
     */
    public function updateCode($mail, $nouveauCode) {
        $stmt = $this->pdo->prepare(
            "UPDATE inscriptions_en_attente 
             SET code_verif = :code, date_creation = NOW() 
             WHERE mail = :mail"
        );

        return $stmt->execute([
            'code' => $nouveauCode,
            'mail' => $mail
        ]);
    }

    /**
     * DELETE - Supprimer une inscription par email
     */
    public function delete($mail) {
        $stmt = $this->pdo->prepare("DELETE FROM inscriptions_en_attente WHERE mail = :mail");
        return $stmt->execute(['mail' => $mail]);
    }

    /**
     * DELETE - Supprimer les inscriptions expirées (plus de 15 minutes)
     */
    public function deleteExpired() {
        $stmt = $this->pdo->prepare(
            "DELETE FROM inscriptions_en_attente 
             WHERE date_creation < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );

        return $stmt->execute();
    }

    /**
     * Vérifier le code de vérification
     */
    public function verifyCode($mail, $code) {
        $inscription = $this->findByEmail($mail);

        if (!$inscription) {
            return false;
        }

        return $code == $inscription['code_verif'];
    }
}

