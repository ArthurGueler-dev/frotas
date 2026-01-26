<?php
/**
 * Setar TODAS as peças como ESPECÍFICAS (universal=0)
 * Nenhuma peça aparecerá como universal
 * Apenas peças com compatibilidade cadastrada aparecerão para cada modelo
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");

    // Setar TODAS as peças como universal=0 (específicas)
    $conn->query("UPDATE FF_Pecas SET universal = 0");
    $affected = $conn->affected_rows;

    // Verificar
    $countEsp = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 0");
    $countUniv = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 1");

    echo json_encode([
        'success' => true,
        'message' => 'TODAS as peças agora são ESPECÍFICAS (universal=0)',
        'pecas_atualizadas' => $affected,
        'resultado' => [
            'especificas' => $countEsp->fetch_assoc()['total'],
            'universais' => $countUniv->fetch_assoc()['total']
        ],
        'comportamento' => 'Agora só aparecem peças que têm compatibilidade cadastrada na tabela FF_Pecas_Compatibilidade'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
