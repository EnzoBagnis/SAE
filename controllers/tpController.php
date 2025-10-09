<?php
session_start();

// Check if user is authenticated
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    outputJson(['error' => 'Not authenticated']);
    exit;
}

/**
 * Get workshops with pagination
 * @param int $page Current page number
 * @param int $perPage Number of workshops per page
 * @return array Workshops data with pagination info
 */
function getWorkshops($page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    $workshops = [];

    // Generate sample workshops (total of 50 workshops)
    $totalWorkshops = 50;

    for ($i = $offset + 1; $i <= min($offset + $perPage, $totalWorkshops); $i++) {
        $workshops[] = [
            'id' => $i,
            'title' => "TP $i",
            'userId' => $_SESSION['id']
        ];
    }

    return [
        'tps' => $workshops,
        'total' => $totalWorkshops,
        'page' => $page,
        'perPage' => $perPage,
        'hasMore' => ($offset + $perPage) < $totalWorkshops
    ];
}

/**
 * Output JSON response (centralized json_encode)
 */
function outputJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
}

// Simple API router
$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            // Get workshops list with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;

            $result = getWorkshops($page, $perPage);
            outputJson(['success' => true, 'data' => $result]);
            break;

        case 'get':
            // Get single workshop by ID
            $workshopId = $_GET['id'] ?? null;
            if (!$workshopId) {
                http_response_code(400);
                outputJson(['error' => 'Workshop ID missing']);
                exit;
            }
            outputJson(['success' => true, 'data' => ['id' => $workshopId, 'title' => "TP $workshopId"]]);
            break;

        default:
            http_response_code(400);
            outputJson(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    outputJson(['error' => 'Server error', 'message' => $e->getMessage()]);
}
