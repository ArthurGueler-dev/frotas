<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('America/Sao_Paulo');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    $sql = "SELECT ordem_numero, placa_veiculo, data_criacao, status
            FROM ordemservico
            ORDER BY ordem_numero DESC
            LIMIT 20";

    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($orders),
        'orders' => $orders
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
