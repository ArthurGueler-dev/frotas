<?php
/**
 * Script temporário para limpar dados incorretos do dia 31/12/2025
 * Esses dados foram salvos por engano quando deveria ser 30/12
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../db-config.php';

try {
    // Deletar todos os registros do dia 31/12/2025
    $stmt = $pdo->prepare("DELETE FROM daily_mileage WHERE date = '2025-12-31'");
    $stmt->execute();

    $deletedCount = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => "Deletados $deletedCount registros incorretos do dia 31/12/2025",
        'deleted_count' => $deletedCount
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao deletar registros',
        'message' => $e->getMessage()
    ]);
}
?>
