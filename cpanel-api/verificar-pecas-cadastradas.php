<?php
/**
 * Script para verificar quais peças estão cadastradas no banco
 * e seus IDs reais
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

// Buscar TODAS as peças relacionadas à Hilux
$sql = "
    SELECT
        id,
        codigo,
        nome,
        categoria,
        custo_estimado,
        tipo,
        modelo_compativel
    FROM FF_Pecas
    WHERE modelo_compativel LIKE '%HILUX%'
       OR nome LIKE '%Hilux%'
       OR nome LIKE '%HILUX%'
    ORDER BY categoria, tipo, id
";

$result = $conn->query($sql);
$pecas = [];

while ($row = $result->fetch_assoc()) {
    $pecas[] = $row;
}

// Agrupar por categoria
$porCategoria = [];
foreach ($pecas as $peca) {
    $cat = $peca['categoria'];
    if (!isset($porCategoria[$cat])) {
        $porCategoria[$cat] = [];
    }
    $porCategoria[$cat][] = $peca;
}

echo json_encode([
    'success' => true,
    'total_pecas' => count($pecas),
    'pecas' => $pecas,
    'por_categoria' => $porCategoria
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>
