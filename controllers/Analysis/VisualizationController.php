<?php

namespace Controllers\Analysis;

require_once __DIR__ . '/../../controllers/BaseController.php';
require_once __DIR__ . '/../../models/Dataset.php';

/**
 * Contrôleur pour la visualisation des données avec D3.js
 */
class VisualizationController extends \BaseController
{
    private $datasetModel;

    public function __construct()
    {
        $this->datasetModel = new \Dataset();
    }

    /**
     * Affiche la page de visualisation
     */
    public function index()
    {
        $this->requireAuth();

        $datasetId = $_GET['dataset_id'] ?? null;
        $teacherId = $_SESSION['id'];

        if (!$datasetId || !$this->datasetModel->hasAccess($datasetId, $teacherId)) {
            $_SESSION['error'] = 'Dataset invalide';
            $this->redirect('dashboard');
            return;
        }

        $dataset = $this->datasetModel->getById($datasetId);

        $this->loadView('analysis/visualization', [
            'title' => 'Visualisation - ' . $dataset['nom_dataset'],
            'dataset_id' => $datasetId,
            'dataset' => $dataset,
            'user' => [
                'nom' => $_SESSION['nom'],
                'prenom' => $_SESSION['prenom']
            ]
        ]);
    }

    /**
     * API pour récupérer les données formatées pour D3.js
     */
    public function getData()
    {
        $this->requireAuth();

        $datasetId = $_GET['dataset_id'] ?? null;

        header('Content-Type: application/json');

        // Charger les vecteurs 2D (après t-SNE)
        $vectors2dFile = __DIR__ . "/../../data/vectors/dataset_{$datasetId}_vectors_2d.json";

        if (!file_exists($vectors2dFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'Données de visualisation non disponibles']);
            return;
        }

        $vectors2d = json_decode(file_get_contents($vectors2dFile), true);

        // Récupérer les métadonnées depuis la BD
        $attempts = $this->datasetModel->getAttemptsWithVectors($datasetId);

        // Fusionner vecteurs et métadonnées
        $data = [
            'nodes' => [],
            'metadata' => []
        ];

        foreach ($attempts as $index => $attempt) {
            if (isset($vectors2d[$index])) {
                $data['nodes'][] = [
                    'id' => $attempt['attempt_id'],
                    'x' => $vectors2d[$index][0],
                    'y' => $vectors2d[$index][1],
                    'exercise' => $attempt['exo_name'],
                    'correct' => (bool)$attempt['correct'],
                    'student' => $attempt['nom_fictif'] . ' ' . $attempt['prenom_fictif']
                ];

                $data['metadata'][] = [
                    'id' => $attempt['attempt_id'],
                    'date' => $attempt['submission_date'],
                    'exercise' => $attempt['exo_name'],
                    'student_id' => $attempt['student_identifier']
                ];
            }
        }

        echo json_encode($data);
    }
}
