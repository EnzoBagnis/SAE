<?php
/**
 * @deprecated This script is no longer used.
 * The import endpoint has been moved into the MVC architecture.
 * Please use POST /api/import/attempts instead.
 *
 * This file is kept only for backward compatibility.
 */

header('Content-Type: application/json');
http_response_code(410); // 410 Gone
echo json_encode([
    'success' => false,
    'error'   => 'Ce script est obsolète. Utilisez POST /api/import/attempts à la place.',
    'moved_to' => '/api/import/attempts',
]);
