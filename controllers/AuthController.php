<?php
require_once __DIR__ . '/../models/database.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/inscriptionEnAttente.php';
require_once __DIR__ . '/../models/emailService.php';

/**
 * AuthController - Authentication controller with CRUD operations
 * Handles user registration, login, and password management
 */
class AuthController {
    private $userModel;
    private $pendingRegistrationModel;
    private $emailService;

    public function __construct() {
        $this->userModel = new User();
        $this->pendingRegistrationModel = new PendingRegistration();
        $this->emailService = new EmailService();
    }

    /**
     * CREATE - Register a new user
     * @param string $lastName User's last name
     * @param string $firstName User's first name
     * @param string $email User's email
     * @param string $password User's password
     * @return array Result with success status and data/error
     */
    public function register($lastName, $firstName, $email, $password) {
        // Clean up expired registrations
        $this->pendingRegistrationModel->deleteExpired();

        // Check if email already exists in users table
        if ($this->userModel->emailExists($email)) {
            return ['success' => false, 'error' => 'email_exists'];
        }

        // Check if email exists in pending registrations
        if ($this->pendingRegistrationModel->emailExists($email)) {
            return ['success' => false, 'error' => 'pending_exists'];
        }

        // Generate 6-digit verification code
        $verificationCode = rand(100000, 999999);

        // Create pending registration
        if ($this->pendingRegistrationModel->create($lastName, $firstName, $email, $password, $verificationCode)) {
            // Send verification code by email
            if ($this->emailService->sendVerificationCode($email, $verificationCode)) {
                return ['success' => true, 'email' => $email];
            }
            return ['success' => false, 'error' => 'email_send_failed'];
        }

        return ['success' => false, 'error' => 'creation_failed'];
    }

    /**
     * CREATE - Validate verification code and create user account
     * @param string $email User's email
     * @param string $code Verification code
     * @return array Result with success status and user data/error
     */
    public function validateCode($email, $code) {
        $registration = $this->pendingRegistrationModel->findByEmail($email);

        if (!$registration) {
            return ['success' => false, 'error' => 'registration_expired'];
        }

        // Verify the code
        if ($this->pendingRegistrationModel->verifyCode($email, $code)) {
            // Create user account
            $this->userModel->create(
                $registration['nom'],
                $registration['prenom'],
                $registration['mail'],
                $registration['mdp'],
                $registration['code_verif'],
                1 // Email verified
            );

            // Delete pending registration
            $this->pendingRegistrationModel->delete($email);

            // Get created user
            $user = $this->userModel->findByEmail($email);

            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'error' => 'code_incorrect'];
    }

    /**
     * READ - Login user
     * @param string $email User's email
     * @param string $password User's password
     * @return array Result with success status and user data/error
     */
    public function login($email, $password) {
        $user = $this->userModel->verifyCredentials($email, $password);

        if (!$user) {
            // Check if email exists to provide specific error
            if (!$this->userModel->emailExists($email)) {
                return ['success' => false, 'error' => 'email_not_found'];
            }
            return ['success' => false, 'error' => 'password_incorrect'];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * UPDATE - Resend verification code
     * @param string $email User's email
     * @return array Result with success status and error
     */
    public function resendCode($email) {
        $registration = $this->pendingRegistrationModel->findByEmail($email);

        if (!$registration) {
            return ['success' => false, 'error' => 'registration_expired'];
        }

        // Generate new verification code
        $newCode = rand(100000, 999999);

        // Update code in database
        if ($this->pendingRegistrationModel->updateCode($email, $newCode)) {
            // Send new code by email
            if ($this->emailService->sendVerificationCode($email, $newCode)) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'email_send_failed'];
        }

        return ['success' => false, 'error' => 'update_failed'];
    }

    /**
     * UPDATE - Request password reset
     * @param string $email User's email
     * @return array Result with success status and error
     */
    public function requestPasswordReset($email) {
        if (!$this->userModel->emailExists($email)) {
            return ['success' => false, 'error' => 'email_not_found'];
        }

        // Generate secure reset token
        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token to database
        if ($this->userModel->setResetToken($email, $token, $expiration)) {
            // Send reset link by email
            if ($this->emailService->sendResetLink($email, $token)) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'email_send_failed'];
        }

        return ['success' => false, 'error' => 'update_failed'];
    }

    /**
     * UPDATE - Reset password with token
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array Result with success status and error
     */
    public function resetPassword($token, $newPassword) {
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return ['success' => false, 'error' => 'token_invalid'];
        }

        // Check if token is expired
        if (strtotime($user['reset_expiration']) < time()) {
            return ['success' => false, 'error' => 'token_expired'];
        }

        // Update password
        if ($this->userModel->updatePassword($user['id'], $newPassword)) {
            // Clear reset token
            $this->userModel->clearResetToken($user['id']);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'update_failed'];
    }

    /**
     * Create user session
     * @param array $user User data
     */
    public function createSession($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Store user data in session
        $_SESSION['id'] = $user['id'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['mail'] = $user['mail'];
    }
}
