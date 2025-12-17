<?php
/**
 * API Proxy Assíncrona para Python Route Optimization
 * Inicia job e retorna job_id para polling
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$pythonApiUrl = 'http://31.97.169.36:8000';

// POST /start - Iniciar job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['job_id'])) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['base']) || !isset($data['locais'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Campos obrigatórios: base, locais']);
        exit;
    }

    $payload = [
        'base' => $data['base'],
        'locais' => $data['locais'],
        'max_diameter_km' => isset($data['max_diameter_km']) ? $data['max_diameter_km'] : 15.0,
        'max_locais_por_rota' => isset($data['max_locais_por_rota']) ? $data['max_locais_por_rota'] : 10
    ];

    $ch = curl_init($pythonApiUrl . '/otimizar-async');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 202) {
        echo $response;
    } else {
        http_response_code($httpCode ? $httpCode : 500);
        echo $response ? $response : json_encode(['success' => false, 'error' => 'Erro ao iniciar job']);
    }
    exit;
}

// GET /status?job_id=xxx - Verificar status
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['job_id'])) {
    $jobId = $_GET['job_id'];

    $ch = curl_init($pythonApiUrl . '/job-status/' . urlencode($jobId));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    http_response_code($httpCode);
    echo $response;
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Método inválido']);
