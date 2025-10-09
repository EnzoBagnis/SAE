<?php
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json');

// Fonction pour récupérer les TPs
function getTPs() {
    $tps = [];

    // Génération de TPs simples pour l'exemple
    for ($i = 1; $i <= 25; $i++) {
        $tps[] = [
            'id' => $i,
            'title' => "TP $i",
            'userId' => $_SESSION['id']
        ];
    }

    return $tps;
}

// Router simple
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $tps = getTPs();
            echo json_encode(['success' => true, 'data' => $tps]);
            break;

        case 'get':
            $tpId = $_GET['id'] ?? null;
            if (!$tpId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du TP manquant']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => ['id' => $tpId, 'title' => "TP $tpId"]]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non valide']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'message' => $e->getMessage()]);
}
