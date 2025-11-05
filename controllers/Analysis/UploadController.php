<?php

namespace Controllers\Analysis;

require_once __DIR__ . '/../../controllers/BaseController.php';
require_once __DIR__ . '/../../models/Code2VecService.php';

/**
 * Contrôleur pour l'upload des données de programmes étudiants
 */
class UploadController extends \BaseController
{
    private $code2vecService;

    public function __construct()
    {
        $this->code2vecService = new \Code2VecService();
    }

    /**
     * Affiche la page d'upload
     */
    public function index()
    {
        $this->requireAuth(); // Vérification authentification

        $this->loadView('analysis/upload', [
            'title' => 'Importer des données',
            'user' => [
                'nom' => $_SESSION['nom'],
                'prenom' => $_SESSION['prenom']
            ]
        ]);
    }

    /**
     * Traite l'upload du fichier JSON
     */
    public function upload()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('upload');
            return;
        }

        // Vérifier qu'un fichier a été uploadé
        if (!isset($_FILES['dataset']) || $_FILES['dataset']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Erreur lors de l\'upload du fichier';
            $this->redirect('upload');
            return;
        }

        $file = $_FILES['dataset'];

        // Vérifier que c'est un fichier JSON
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($extension !== 'json') {
            $_SESSION['error'] = 'Seuls les fichiers JSON sont acceptés';
            $this->redirect('upload');
            return;
        }

        // Vérifier que le JSON est valide
        $jsonContent = file_get_contents($file['tmp_name']);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['error'] = 'Le fichier JSON est invalide';
            $this->redirect('upload');
            return;
        }

        // Sauvegarder le fichier
        $teacherId = $_SESSION['id']; // Utilise 'id' comme dans votre code
        $uploadDir = __DIR__ . '/../../data/uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadedFile = $uploadDir . 'teacher_' . $teacherId . '_' . time() . '.json';
        move_uploaded_file($file['tmp_name'], $uploadedFile);

        // Lancer le traitement en arrière-plan
        $result = $this->code2vecService->processInBackground($uploadedFile, $teacherId);

        if ($result['success']) {
            $_SESSION['success'] = 'Fichier uploadé ! Le traitement est en cours...';
            $this->redirect('processing', ['teacher_id' => $teacherId]);
        } else {
            $_SESSION['error'] = $result['error'];
            $this->redirect('upload');
        }
    }
}
