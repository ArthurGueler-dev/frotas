<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();

    // Verificar estrutura da tabela
    $stmt = $pdo->query("DESCRIBE ordemservico");
    $columns = $stmt->fetchAll();

    $hasSeqNumber = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'seq_number') {
            $hasSeqNumber = true;
            break;
        }
    }

    // Verificar últimas OS
    $stmt = $pdo->query("SELECT id, ordem_numero, seq_number FROM ordemservico ORDER BY id DESC LIMIT 5");
    $lastOrders = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'has_seq_number_column' => $hasSeqNumber,
        'table_structure' => $columns,
        'last_5_orders' => $lastOrders
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
