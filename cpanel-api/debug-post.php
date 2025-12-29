<?php
// Debug: Ver o que está sendo enviado no POST
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Pegar o body raw
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Salvar em arquivo de log
$logFile = '/tmp/debug-post.log';
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
    'input_size' => strlen($input),
    'has_map_html' => isset($data['map_html']),
    'map_html_size' => isset($data['map_html']) ? strlen($data['map_html']) : 0,
    'map_html_preview' => isset($data['map_html']) ? substr($data['map_html'], 0, 100) : 'N/A',
    'other_fields' => array_keys($data ?: [])
];

file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Retornar info
echo json_encode([
    'success' => true,
    'debug' => $logData
], JSON_PRETTY_PRINT);
?>
