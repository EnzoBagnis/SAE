<?php

namespace Infrastructure\DependencyInjection;

use PDO;
use Domain\Authentication\Repository\UserRepositoryInterface;
use Domain\Authentication\Repository\PendingRegistrationRepositoryInterface;
use Domain\Authentication\Service\AuthenticationServiceInterface;
use Domain\Authentication\Service\EmailServiceInterface;
use Domain\StudentTracking\Repository\StudentRepositoryInterface;
use Domain\StudentTracking\Repository\AttemptRepositoryInterface;
use Domain\ExerciseManagement\Repository\ExerciseRepositoryInterface;
use Domain\ResourceManagement\Repository\ResourceRepositoryInterface;
use Infrastructure\Repository\PdoUserRepository;
use Infrastructure\Repository\PdoPendingRegistrationRepository;
use Infrastructure\Repository\PdoStudentRepository;
use Infrastructure\Repository\PdoAttemptRepository;
use Infrastructure\Repository\PdoExerciseRepository;
use Infrastructure\Repository\PdoResourceRepository;
use Infrastructure\Service\SessionAuthenticationService;
use Infrastructure\Service\PHPMailerEmailService;
use Infrastructure\Persistence\DatabaseConnection;

/**
 * ServiceContainer - Simple dependency injection container
 *
 * This container manages service instantiation and dependencies.
 */
class ServiceContainer
{
    private array $services = [];
    private array $singletons = [];

    /**
     * Register services in the container
     *
     * @return void
     */
    public function register(): void
    {
        // Register PDO connection as singleton
        $this->singleton(PDO::class, function () {
            return DatabaseConnection::getConnection();
        });

        // Register repositories
        $this->singleton(UserRepositoryInterface::class, function () {
            return new PdoUserRepository($this->get(PDO::class));
        });

        $this->singleton(PendingRegistrationRepositoryInterface::class, function () {
            return new PdoPendingRegistrationRepository($this->get(PDO::class));
        });

        $this->singleton(StudentRepositoryInterface::class, function () {
            return new PdoStudentRepository($this->get(PDO::class));
        });

        $this->singleton(AttemptRepositoryInterface::class, function () {
            return new PdoAttemptRepository($this->get(PDO::class));
        });

        $this->singleton(ExerciseRepositoryInterface::class, function () {
            return new PdoExerciseRepository($this->get(PDO::class));
        });

        $this->singleton(ResourceRepositoryInterface::class, function () {
            return new PdoResourceRepository($this->get(PDO::class));
        });

        // Register services
        $this->singleton(AuthenticationServiceInterface::class, function () {
            return new SessionAuthenticationService(
                $this->get(UserRepositoryInterface::class)
            );
        });

        $this->singleton(EmailServiceInterface::class, function () {
            $env = $this->loadEnv();
            return new PHPMailerEmailService(
                $env['MAIL_HOST'] ?? 'smtp.gmail.com',
                (int)($env['MAIL_PORT'] ?? 587),
                $env['MAIL_USERNAME'] ?? '',
                $env['MAIL_PASSWORD'] ?? '',
                $env['MAIL_USERNAME'] ?? '',
                $env['MAIL_FROM_NAME'] ?? 'SAE Platform'
            );
        });

        // Register use cases
        $this->bind(\Application\Authentication\UseCase\LoginUser::class, function () {
            return new \Application\Authentication\UseCase\LoginUser(
                $this->get(UserRepositoryInterface::class),
                $this->get(AuthenticationServiceInterface::class)
            );
        });

        $this->bind(\Application\Authentication\UseCase\RegisterUser::class, function () {
            return new \Application\Authentication\UseCase\RegisterUser(
                $this->get(UserRepositoryInterface::class),
                $this->get(PendingRegistrationRepositoryInterface::class),
                $this->get(EmailServiceInterface::class)
            );
        });

        $this->bind(\Application\Authentication\UseCase\VerifyUserEmail::class, function () {
            return new \Application\Authentication\UseCase\VerifyUserEmail(
                $this->get(PendingRegistrationRepositoryInterface::class)
            );
        });

        $this->bind(\Application\Authentication\UseCase\RequestPasswordReset::class, function () {
            return new \Application\Authentication\UseCase\RequestPasswordReset(
                $this->get(UserRepositoryInterface::class),
                $this->get(EmailServiceInterface::class)
            );
        });

        $this->bind(\Application\Authentication\UseCase\ResetPassword::class, function () {
            return new \Application\Authentication\UseCase\ResetPassword(
                $this->get(UserRepositoryInterface::class)
            );
        });

        $this->bind(\Application\StudentTracking\UseCase\ListStudents::class, function () {
            return new \Application\StudentTracking\UseCase\ListStudents(
                $this->get(StudentRepositoryInterface::class)
            );
        });

        $this->bind(\Application\ExerciseManagement\UseCase\ListExercises::class, function () {
            return new \Application\ExerciseManagement\UseCase\ListExercises(
                $this->get(ExerciseRepositoryInterface::class)
            );
        });

        // Register controllers
        $this->bind(\Presentation\Controller\Authentication\LoginController::class, function () {
            return new \Presentation\Controller\Authentication\LoginController(
                $this->get(\Application\Authentication\UseCase\LoginUser::class)
            );
        });

        $this->bind(\Presentation\Controller\Authentication\RegisterController::class, function () {
            return new \Presentation\Controller\Authentication\RegisterController(
                $this->get(\Application\Authentication\UseCase\RegisterUser::class)
            );
        });

        $this->bind(\Presentation\Controller\Authentication\EmailVerificationController::class, function () {
            return new \Presentation\Controller\Authentication\EmailVerificationController(
                $this->get(\Application\Authentication\UseCase\VerifyUserEmail::class)
            );
        });

        $this->bind(\Presentation\Controller\Authentication\LogoutController::class, function () {
            return new \Presentation\Controller\Authentication\LogoutController(
                $this->get(AuthenticationServiceInterface::class)
            );
        });

        $this->bind(\Presentation\Controller\Authentication\PasswordResetController::class, function () {
            return new \Presentation\Controller\Authentication\PasswordResetController(
                $this->get(\Application\Authentication\UseCase\RequestPasswordReset::class),
                $this->get(\Application\Authentication\UseCase\ResetPassword::class)
            );
        });

        $this->bind(\Presentation\Controller\HomeController::class, function () {
            return new \Presentation\Controller\HomeController();
        });

        $this->bind(\Presentation\Controller\StudentTracking\StudentsController::class, function () {
            return new \Presentation\Controller\StudentTracking\StudentsController(
                $this->get(\Application\StudentTracking\UseCase\ListStudents::class)
            );
        });

        $this->bind(\Presentation\Controller\ExerciseManagement\ExercisesController::class, function () {
            return new \Presentation\Controller\ExerciseManagement\ExercisesController(
                $this->get(\Application\ExerciseManagement\UseCase\ListExercises::class)
            );
        });

        $this->bind(\Presentation\Controller\UserManagement\DashboardController::class, function () {
            return new \Presentation\Controller\UserManagement\DashboardController(
                $this->get(ResourceRepositoryInterface::class)
            );
        });

        $this->bind(\Presentation\Controller\ResourceManagement\ResourcesListController::class, function () {
            return new \Presentation\Controller\ResourceManagement\ResourcesListController(
                $this->get(ResourceRepositoryInterface::class)
            );
        });

        $this->bind(\Presentation\Controller\ResourceManagement\ResourceDetailsController::class, function () {
            return new \Presentation\Controller\ResourceManagement\ResourceDetailsController(
                $this->get(ResourceRepositoryInterface::class),
                $this->get(ExerciseRepositoryInterface::class)
            );
        });

        $this->bind(\Presentation\Controller\Administration\AdminController::class, function () {
            return new \Presentation\Controller\Administration\AdminController(
                $this->get(UserRepositoryInterface::class),
                $this->get(PendingRegistrationRepositoryInterface::class)
            );
        });
    }

    /**
     * Register a service
     *
     * @param string $id Service identifier
     * @param callable $factory Factory function
     * @return void
     */
    public function bind(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * Register a singleton service
     *
     * @param string $id Service identifier
     * @param callable $factory Factory function
     * @return void
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
        $this->singletons[$id] = null;
    }

    /**
     * Get a service from the container
     *
     * @param string $id Service identifier
     * @return mixed Service instance
     */
    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            throw new \Exception("Service {$id} not found in container");
        }

        // Return singleton if exists
        if (array_key_exists($id, $this->singletons)) {
            if ($this->singletons[$id] === null) {
                $this->singletons[$id] = $this->services[$id]($this);
            }
            return $this->singletons[$id];
        }

        // Create new instance
        return $this->services[$id]($this);
    }

    /**
     * Load environment variables
     *
     * @return array Environment variables
     */
    private function loadEnv(): array
    {
        // Try config/.env outside the project root first (production)
        $envFile = __DIR__ . '/../../../../config/.env';
        if (file_exists($envFile)) {
            return parse_ini_file($envFile);
        }

        return [];
    }
}
