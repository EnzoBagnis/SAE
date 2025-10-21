<?php
/**
 * User Model - CRUD operations for users with PDO
 */
class User {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * CREATE - Create a new user
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email address
     * @param string $password User's password (will be hashed)
     * @param string|null $verificationCode Verification code for email
     * @param int $isVerified Whether email is verified (0 or 1)
     * @return bool Success status
     */
    public function create($lastName, $firstName, $email, $password, $verificationCode = null, $isVerified = 0) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif, verifie) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif, :verifie)"
        );

        return $stmt->execute([
            'nom' => $lastName,
            'prenom' => $firstName,
            'mdp' => $hashedPassword,
            'mail' => $email,
            'code_verif' => $verificationCode,
            'verifie' => $isVerified
        ]);
    }

    /**
     * READ - Find a user by email
     * @param string $email User's email address
     * @return array|false User data or false if not found
     */
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Find a user by ID
     * @param int $id User's ID
     * @return array|false User data or false if not found
     */
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Check if an email exists
     * @param string $email Email to check
     * @return bool True if email exists, false otherwise
     */
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * READ - Find a user by reset token
     * @param string $token Reset token
     * @return array|false User data or false if not found
     */
    public function findByResetToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE reset_token = :token");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * UPDATE - Update user's password
     * @param int $id User's ID
     * @param string $newPassword New password (will be hashed)
     * @return bool Success status
     */
    public function updatePassword($id, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("UPDATE utilisateurs SET mdp = :mdp WHERE id = :id");
        return $stmt->execute([
            'mdp' => $hashedPassword,
            'id' => $id
        ]);
    }

    /**
     * UPDATE - Set password reset token
     * @param string $email User's email
     * @param string $token Reset token
     * @param string $expiration Token expiration date
     * @return bool Success status
     */
    public function setResetToken($email, $token, $expiration) {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs 
             SET reset_token = :token, reset_expiration = :expiration 
             WHERE mail = :mail"
        );

        return $stmt->execute([
            'token' => $token,
            'expiration' => $expiration,
            'mail' => $email
        ]);
    }

    /**
     * UPDATE - Clear password reset token
     * @param int $id User's ID
     * @return bool Success status
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
     * UPDATE - Update user information
     * @param int $id User's ID
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email
     * @return bool Success status
     */
    public function update($id, $lastName, $firstName, $email) {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs 
             SET nom = :nom, prenom = :prenom, mail = :mail 
             WHERE id = :id"
        );

        return $stmt->execute([
            'nom' => $lastName,
            'prenom' => $firstName,
            'mail' => $email,
            'id' => $id
        ]);
    }

    /**
     * DELETE - Delete a user
     * @param int $id User's ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Verify user credentials for login
     * @param string $email User's email
     * @param string $password User's password
     * @return array|false User data if valid, false otherwise
     */
    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        // Verify password hash
        if (password_verify($password, $user['mdp'])) {
            return $user;
        }

        return false;
    }
}
