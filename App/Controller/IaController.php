<?php

namespace App\Controller;

use Core\Controller\AbstractController;
use App\Model\AuthenticationService;
use Core\Service\SessionService;
use Core\Config\DatabaseConnection;

/**
 * IA Controller
 * Handles the AI/ML analysis page (aes2vec / Doc2Vec)
 */
class IaController extends AbstractController
{
    private AuthenticationService $authService;

    public function __construct()
    {
        $this->authService = new AuthenticationService(new SessionService());
    }

    /**
     * Show the IA dashboard page
     *
     * @return void
     */
    public function index(): void
    {
        $this->authService->requireAuth('/auth/login');

        $pdo = DatabaseConnection::getInstance()->getConnection();

        // Statistiques globales
        $totalAttempts  = (int)$pdo->query("SELECT COUNT(*) FROM attempts")->fetchColumn();
        $totalExercises = (int)$pdo->query("SELECT COUNT(*) FROM exercices")->fetchColumn();
        $totalStudents  = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM attempts")->fetchColumn();

        // Répartition par eval_set
        $evalSets = $pdo->query(
            "SELECT eval_set, COUNT(*) AS count FROM attempts GROUP BY eval_set ORDER BY eval_set"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Ressources disponibles (pour le sélecteur)
        $resources = $pdo->query(
            "SELECT ressource_id, ressource_name FROM ressources ORDER BY ressource_name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->renderView('user/ia', [
            'stats' => [
                'total_attempts'  => $totalAttempts,
                'total_exercises' => $totalExercises,
                'total_students'  => $totalStudents,
                'eval_sets'       => $evalSets,
            ],
            'resources' => $resources,
        ]);
    }
}

