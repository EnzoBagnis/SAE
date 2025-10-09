<?php
/**
 * PendingRegistration Model - CRUD operations for pending registrations with PDO
 */
class PendingRegistration {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * CREATE - Create a new pending registration
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email
     * @param string $password User's password (will be hashed)
     * @param string $verificationCode Verification code
     * @return bool Success status
     */
    public function create($lastName, $firstName, $email, $password, $verificationCode) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO inscriptions_en_attente (nom, prenom, mdp, mail, code_verif, date_creation) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif, NOW())"
        );

        return $stmt->execute([
            'nom' => $lastName,
            'prenom' => $firstName,
            'mdp' => $hashedPassword,
            'mail' => $email,
            'code_verif' => $verificationCode
        ]);
    }

    /**
     * READ - Find a pending registration by email
     * @param string $email Email to search for
     * @return array|false Registration data or false if not found
     */
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Check if an email exists in pending registrations
     * @param string $email Email to check
     * @return bool True if email exists, false otherwise
     */
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inscriptions_en_attente WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * UPDATE - Update verification code
     * @param string $email User's email
     * @param string $newCode New verification code
     * @return bool Success status
     */
    public function updateCode($email, $newCode) {
        $stmt = $this->pdo->prepare(
            "UPDATE inscriptions_en_attente 
             SET code_verif = :code, date_creation = NOW() 
             WHERE mail = :mail"
        );

        return $stmt->execute([
            'code' => $newCode,
            'mail' => $email
        ]);
    }

    /**
     * DELETE - Delete a pending registration by email
     * @param string $email Email of registration to delete
     * @return bool Success status
     */
    public function delete($email) {
        $stmt = $this->pdo->prepare("DELETE FROM inscriptions_en_attente WHERE mail = :mail");
        return $stmt->execute(['mail' => $email]);
    }

    /**
     * DELETE - Delete expired pending registrations (older than 15 minutes)
     * @return bool Success status
     */
    public function deleteExpired() {
        $stmt = $this->pdo->prepare(
            "DELETE FROM inscriptions_en_attente 
             WHERE date_creation < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );

        return $stmt->execute();
    }

    /**
     * Verify verification code
     * @param string $email User's email
     * @param string $code Verification code to check
     * @return bool True if code is valid, false otherwise
     */
    public function verifyCode($email, $code) {
        $registration = $this->findByEmail($email);

        if (!$registration) {
            return false;
        }

        return $code == $registration['code_verif'];
    }
}
