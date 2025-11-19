<?php
/**
 * Endpoint de Sincronização de Telemetria
 *
 * Upload para: /home/f137049/public_html/api/sincronizar.php
 * URL: https://floripa.in9automacao.com.br/api/sincronizar.php
 *
 * Uso: POST https://floripa.in9automacao.com.br/api/sincronizar.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido. Use POST.']);
    exit();
}

// Caminho do script Node.js
$scriptPath = __DIR__ . '/sync-telemetria.js';

// Verifica se o script existe
if (!file_exists($scriptPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Script não encontrado']);
    exit();
}

// Executa o script Node.js
$output = [];
$returnCode = 0;

exec("node $scriptPath 2>&1", $output, $returnCode);

// Junta a saída
$fullOutput = implode("\n", $output);

// Procura pelo JSON na última linha
$lines = explode("\n", $fullOutput);
$jsonOutput = null;

// Procura de trás para frente pela primeira linha que parece JSON
for ($i = count($lines) - 1; $i >= 0; $i--) {
    $line = trim($lines[$i]);
    if ($line && ($line[0] === '{' || $line[0] === '[')) {
        $jsonOutput = $line;
        break;
    }
}

if ($jsonOutput) {
    // Retorna o JSON
    echo $jsonOutput;
} else {
    // Se não encontrou JSON, retorna erro com a saída completa
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao executar sincronização',
        'output' => $fullOutput,
        'returnCode' => $returnCode
    ]);
}
?>
