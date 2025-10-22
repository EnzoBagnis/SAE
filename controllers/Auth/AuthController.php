<?php
namespace Controllers\Auth;

require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/PendingRegistration.php';
require_once __DIR__ . '/../../models/EmailService.php';

/**
 * AuthController - Authentication service with CRUD operations
 * Handles user registration, login, and password management
 */
class AuthController {
    private $userModel;
    private $pendingRegistrationModel;
    private $emailService;

    public function __construct() {
        $this->userModel = new \User();
        $this->pendingRegistrationModel = new \PendingRegistration();
        $this->emailService = new \EmailService();
    }

    /**
     * CREATE - Register a new user
     */
    public function register($lastName, $firstName, $email, $password) {
        $this->pendingRegistrationModel->deleteExpired();

        if ($this->userModel->emailExists($email)) {
            return ['success' => false, 'error' => 'email_exists'];
        }

        if ($this->pendingRegistrationModel->emailExists($email)) {
            return ['success' => false, 'error' => 'pending_exists'];
        }

        $verificationCode = rand(100000, 999999);

        if ($this->pendingRegistrationModel->create($lastName, $firstName, $email, $password, $verificationCode)) {
            if ($this->emailService->sendVerificationCode($email, $verificationCode)) {
                return ['success' => true, 'email' => $email];
            }
            return ['success' => false, 'error' => 'email_send_failed'];
        }

        return ['success' => false, 'error' => 'creation_failed'];
    }

    /**
     * CREATE - Validate verification code and create user account
     */
    public function validateCode($email, $code) {
        $registration = $this->pendingRegistrationModel->findByEmail($email);

        if (!$registration) {
            return ['success' => false, 'error' => 'registration_expired'];
        }

        if ($this->pendingRegistrationModel->verifyCode($email, $code)) {
            $this->userModel->create(
                $registration['nom'],
                $registration['prenom'],
                $registration['mail'],
                $registration['mdp'],
                $registration['code_verif'],
                1
            );

            $this->pendingRegistrationModel->delete($email);
            $user = $this->userModel->findByEmail($email);

            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'error' => 'code_incorrect'];
    }

    /**
     * READ - Login user
     */
    public function login($email, $password) {
        $user = $this->userModel->verifyCredentials($email, $password);

        if (!$user) {
            if (!$this->userModel->emailExists($email)) {
                return ['success' => false, 'error' => 'email_not_found'];
            }
            return ['success' => false, 'error' => 'password_incorrect'];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * UPDATE - Resend verification code
     */
    public function resendCode($email) {
        $registration = $this->pendingRegistrationModel->findByEmail($email);

        if (!$registration) {
            return ['success' => false, 'error' => 'registration_expired'];
        }

        $newCode = rand(100000, 999999);

        if ($this->pendingRegistrationModel->updateCode($email, $newCode)) {
            if ($this->emailService->sendVerificationCode($email, $newCode)) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'email_send_failed'];
        }

        return ['success' => false, 'error' => 'update_failed'];
    }

    /**
     * UPDATE - Request password reset
     */
    public function requestPasswordReset($email) {
        if (!$this->userModel->emailExists($email)) {
            return ['success' => false, 'error' => 'email_not_found'];
        }

        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

        if ($this->userModel->setResetToken($email, $token, $expiration)) {
            if ($this->emailService->sendResetLink($email, $token)) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'email_send_failed'];
        }

        return ['success' => false, 'error' => 'update_failed'];
    }

    /**
     * UPDATE - Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return ['success' => false, 'error' => 'token_invalid'];
        }

        if (strtotime($user['reset_expiration']) < time()) {
            return ['success' => false, 'error' => 'token_expired'];
        }

        if ($this->userModel->updatePassword($user['id'], $newPassword)) {
            $this->userModel->clearResetToken($user['id']);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'update_failed'];
    }

    /**
     * Create user session
     */
    public function createSession($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['id'] = $user['id'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['mail'] = $user['mail'];
    }
}
