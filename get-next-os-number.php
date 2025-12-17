<?php
/**
 * API para obter o próximo número de OS disponível
 * Retorna o próximo número baseado no banco de dados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Cache-Control');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Definir timezone para Brasil
date_default_timezone_set('America/Sao_Paulo');

// Incluir configuração do banco
require_once 'config-db.php';

try {
    // Conectar ao banco usando PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 3
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Obter o ano atual
    $year = date('Y');

    // Buscar o próximo seq_number (maior seq_number + 1)
    $sql = "SELECT COALESCE(MAX(seq_number), 0) + 1 as next_seq FROM ordemservico";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch();
    $nextNumber = intval($result['next_seq']);

    // Formatar o número da OS
    $nextOSNumber = sprintf('OS-%d-%05d', $year, $nextNumber);

    // Log para debug
    error_log("get-next-os-number: Próximo seq_number: {$nextNumber}");
    error_log("get-next-os-number: Próximo número de OS: {$nextOSNumber}");

    // Retornar sucesso com cache-control para evitar cache
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'next_os_number' => $nextOSNumber,
        'year' => $year,
        'sequence' => $nextNumber
    ]);

} catch (Exception $e) {
    error_log('Erro ao obter próximo número de OS: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
