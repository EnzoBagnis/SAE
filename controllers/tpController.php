<?php
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json');

// Fonction pour récupérer les TPs avec pagination
function getTPs($page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    $tps = [];

    // Génération de TPs simples pour l'exemple (total de 50 TPs)
    $totalTPs = 50;

    for ($i = $offset + 1; $i <= min($offset + $perPage, $totalTPs); $i++) {
        $tps[] = [
            'id' => $i,
            'title' => "TP $i",
            'userId' => $_SESSION['id']
        ];
    }

    return [
        'tps' => $tps,
        'total' => $totalTPs,
        'page' => $page,
        'perPage' => $perPage,
        'hasMore' => ($offset + $perPage) < $totalTPs
    ];
}

// Router simple
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;

            $result = getTPs($page, $perPage);
            echo json_encode(['success' => true, 'data' => $result]);
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
