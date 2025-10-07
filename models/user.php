<?php
/**
 * Model user - Gestion CRUD des utilisateurs avec PDO
 */
class user {
    private $pdo;

    public function __construct() {
        $this->pdo = database::getConnection();
    }

    /**
     * CREATE - Créer un nouvel utilisateur
     */
    public function create($nom, $prenom, $mail, $mdp, $code_verif = null, $verifie = 0) {
        $hashedPassword = password_hash($mdp, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif, verifie) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif, :verifie)"
        );

        return $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'mdp' => $hashedPassword,
            'mail' => $mail,
            'code_verif' => $code_verif,
            'verifie' => $verifie
        ]);
    }

    /**
     * READ - Trouver un utilisateur par email
     */
    public function findByEmail($mail) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $mail]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Trouver un utilisateur par ID
     */
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Vérifier si un email existe
     */
    public function emailExists($mail) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $mail]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * READ - Trouver un utilisateur par token de reset
     */
    public function findByResetToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE reset_token = :token");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * UPDATE - Mettre à jour le mot de passe
     */
    public function updatePassword($id, $nouveauMdp) {
        $hashedPassword = password_hash($nouveauMdp, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("UPDATE utilisateurs SET mdp = :mdp WHERE id = :id");
        return $stmt->execute([
            'mdp' => $hashedPassword,
            'id' => $id
        ]);
    }

    /**
     * UPDATE - Définir le token de réinitialisation
     */
    public function setResetToken($mail, $token, $expiration) {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs 
             SET reset_token = :token, reset_expiration = :expiration 
             WHERE mail = :mail"
        );

        return $stmt->execute([
            'token' => $token,
            'expiration' => $expiration,
            'mail' => $mail
        ]);
    }

    /**
     * UPDATE - Réinitialiser le token de reset
     */
    public function clearResetToken($id) {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs 
             SET reset_token = NULL, reset_expiration = NULL 
             WHERE id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }

    /**
     * UPDATE - Mettre à jour les informations de l'utilisateur
     */
    public function update($id, $nom, $prenom, $mail) {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs 
             SET nom = :nom, prenom = :prenom, mail = :mail 
             WHERE id = :id"
        );

        return $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'mail' => $mail,
            'id' => $id
        ]);
    }

    /**
     * DELETE - Supprimer un utilisateur
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Vérifier les credentials pour la connexion
     */
    public function verifyCredentials($mail, $mdp) {
        $user = $this->findByEmail($mail);

        if (!$user) {
            return false;
        }

        if (password_verify($mdp, $user['mdp'])) {
            return $user;
        }

        return false;
    }
}

