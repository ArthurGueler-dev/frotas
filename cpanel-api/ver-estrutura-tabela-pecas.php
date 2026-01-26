<?php
/**
 * Script para ver estrutura REAL da tabela FF_Pecas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

// Criar conexão
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão',
        'details' => $e->getMessage()
    ]);
    exit();
}

// Ver estrutura da tabela FF_Pecas
$result = $conn->query("DESCRIBE FF_Pecas");

$colunas = [];
while ($row = $result->fetch_assoc()) {
    $colunas[] = $row;
}

// Ver exemplo de dados
$result2 = $conn->query("SELECT * FROM FF_Pecas LIMIT 5");

$exemplos = [];
while ($row = $result2->fetch_assoc()) {
    $exemplos[] = $row;
}

echo json_encode([
    'success' => true,
    'estrutura_tabela' => $colunas,
    'exemplos_dados' => $exemplos
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>
