<?php
namespace Controllers\Analysis;

require_once __DIR__ . '/../../controllers/BaseController.php';
require_once __DIR__ . '/../../models/Code2VecService.php';
require_once __DIR__ . '/../../models/Dataset.php';

/**
 * Contrôleur pour la génération et la gestion des vecteurs
 */
class VectorController extends \BaseController {

    private $code2vecService;
    private $datasetModel;

    public function __construct() {
        $this->code2vecService = new \Code2VecService();
        $this->datasetModel = new \Dataset();
    }

    /**
     * Lance la génération de vecteurs pour un dataset
     */
    public function generate() {
        $this->requireAuth();

        $datasetId = $_GET['dataset_id'] ?? null;
        $teacherId = $_SESSION['id'];

        if (!$datasetId) {
            $_SESSION['error'] = 'Dataset non spécifié';
            $this->redirect('dashboard');
            return;
        }

        // Vérifier que l'enseignant a accès au dataset
        if (!$this->datasetModel->hasAccess($datasetId, $teacherId)) {
            $_SESSION['error'] = 'Accès refusé à ce dataset';
            $this->redirect('dashboard');
            return;
        }

        // Lancer le traitement en arrière-plan
        $result = $this->code2vecService->processInBackground($datasetId);

        if ($result['success']) {
            $_SESSION['success'] = 'Génération des vecteurs lancée !';
            $this->redirect('processing', ['dataset_id' => $datasetId]);
        } else {
            $_SESSION['error'] = $result['error'];
            $this->redirect('dashboard');
        }
    }

    /**
     * Page de traitement avec barre de progression
     */
    public function processing() {
        $this->requireAuth();

        $datasetId = $_GET['dataset_id'] ?? null;
        $dataset = $this->datasetModel->getById($datasetId);

        $this->loadView('analysis/processing', [
            'title' => 'Traitement en cours',
            'dataset_id' => $datasetId,
            'dataset' => $dataset,
            'user' => [
                'nom' => $_SESSION['nom'],
                'prenom' => $_SESSION['prenom']
            ]
        ]);
    }

    /**
     * API pour vérifier le statut du traitement (AJAX)
     */
    public function status() {
        $this->requireAuth();

        $datasetId = $_GET['dataset_id'] ?? null;

        header('Content-Type: application/json');

        $status = $this->code2vecService->getProcessingStatus($datasetId);
        echo json_encode($status);
    }

    /**
     * Récupère les vecteurs générés
     */
    public function getVectors() {
        $this->requireAuth();

        $datasetId = $_GET['dataset_id'] ?? null;

        header('Content-Type: application/json');

        $vectorsFile = __DIR__ . "/../../data/vectors/dataset_{$datasetId}_vectors.json";

        if (!file_exists($vectorsFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'Vecteurs non trouvés']);
            return;
        }

        $vectors = file_get_contents($vectorsFile);
        echo $vectors;
    }
}