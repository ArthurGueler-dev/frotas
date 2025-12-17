<?php
/**
 * API Proxy para Python Route Optimization
 * Integra o otimizador Python com o frontend PHP existente
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Get input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

// Validate required fields
if (!isset($data['base']) || !isset($data['locais'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campos obrigatórios: base, locais']);
    exit;
}

// Prepare payload for Python API
$payload = [
    'base' => $data['base'],
    'locais' => $data['locais'],
    'max_diameter_km' => $data['max_diameter_km'] ?? 5.0,
    'max_locais_por_rota' => $data['max_locais_por_rota'] ?? 5
];

// Call Python API
$pythonApiUrl = 'http://31.97.169.36:8000/otimizar';

$ch = curl_init($pythonApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 900); // 15 minutes timeout
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle errors
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao conectar com API Python: ' . $curlError
    ]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $response ?: json_encode([
        'success' => false,
        'error' => 'API Python retornou erro: HTTP ' . $httpCode
    ]);
    exit;
}

// Return response
echo $response;
