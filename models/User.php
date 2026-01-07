<?php

/**
 * User Model - CRUD operations for users with PDO
 */
class User
{
    private $pdo;

    public function __construct()
    {
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
    public function create($lastName, $firstName, $email, $password, $verificationCode = null, $isVerified = 0)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif)"
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
     * CREATE - Create a new user
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email address
     * @param string $password User's password (will be hashed)
     * @param string|null $verificationCode Verification code for email
     * @param int $isVerified Whether email is verified (0 or 1)
     * @return bool Success status
     */
    public function switchUser($id)
    {
        $user = $this->findByIdInPending($id);

        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateurs (nom, prenom, mdp, mail, code_verif, date_creation) 
             VALUES (:nom, :prenom, :mdp, :mail, :code_verif, :date_creation)"
        );

        $stmt->execute([
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'mdp' => $user['mdp'],
            'mail' => $user['mail'],
            'code_verif' => $user['code_verif'],
            'date_creation' => $user['date_creation']
        ]);
        return $this->delete("P", $id);
    }

    /**
     * READ - Find a user by email
     * @param string $email User's email address
     * @return array|false User data or false if not found
     */

    public function createBanUser($mail, $duree_ban, $ban_def)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateurs_bloques (mail, duree_ban, date_de_ban, ban_def)
            VALUES (:mail, :duree_ban, CURRENT_TIMESTAMP, :ban_def)"
        );
        return $stmt->execute([
            'mail' => $mail,
            'duree_ban' => $duree_ban,
            'ban_def' => $ban_def
        ]);

    }



    /**
     * READ - Find a user by email
     * @param string $email User's email address
     * @return array|false User data or false if not found
     */
    public function findByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Find all user
     * FOR ADMIN PANEL
     * @return array User data
     */
    public function showVerifiedUser()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Find all pending user
     * FOR ADMIN PANEL
     * @return array User data
     */
    public function showPendingUser()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Find all blocked user
     * FOR ADMIN PANEL
     * @return array User data
     */
    public function showBlockedUser()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs_bloques");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Find a user by ID
     * @param int $id User's ID
     * @return array|false User data or false if not found
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Find a user by ID
     * @param int $id User's ID
     * @return array|false User data or false if not found
     */
    public function findByIdInPending($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inscriptions_en_attente WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * READ - Check if an email exists
     * @param string $email Email to check
     * @return bool True if email exists, false otherwise
     */
    public function emailExists($email)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE mail = :mail");
        $stmt->execute(['mail' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * READ - Find a user by reset token
     * @param string $token Reset token
     * @return array|false User data or false if not found
     */
    public function findByResetToken($token)
    {
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
    public function updatePassword($id, $newPassword)
    {
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
    public function setResetToken($email, $token, $expiration)
    {
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
    public function clearResetToken($id)
    {
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
    public function update($id, $lastName, $firstName, $email)
    {
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
     * UPDATE - Update user information
     * @param int $id User's ID
     * @return bool Success status
     */
    public function updateVerifie($id)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE inscriptions_en_attente 
             SET verifie = 1
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
    public function updateBan($id, $email, $duree_ban, $ban_def)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs_bloques
             SET duree_ban = :duree_ban AND ban_def = :ban_def
             WHERE id = :id AND mail = :mail"
        );

        return $stmt->execute([
            'duree_ban' => $duree_ban,
            'ban_def' => $ban_def,
            'mail' => $email,
            'id' => $id
        ]);
    }

    /**
     * DELETE - Delete a user
     * @param int $id User's ID
     * @return bool Success status
     */
    public function delete($tableKey, $id)
    {
        $map = [
            'V' => 'utilisateurs',
            'P' => 'inscriptions_en_attente',
            'B' => 'utilisateurs_bloques'
        ];

        if (!isset($map[$tableKey])) {
            return false; // table non autorisée
        }

        $table = $map[$tableKey];

        // Construction de la requête avec nom de table validé
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute( ['id' => $id]);
    }

    /**
     * Verify user credentials for login
     * @param string $email User's email
     * @param string $password User's password
     * @return array|false User data if valid, false otherwise
     */
    public function verifyCredentials($email, $password)
    {
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
