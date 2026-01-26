<?php
/**
 * Lista TODAS as peças da Hilux cadastradas no banco
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
    echo json_encode(array(
        'success' => false,
        'error' => 'Erro de conexão',
        'details' => $e->getMessage()
    ));
    exit();
}

// Buscar peças que podem ser da Hilux
$sql = "
    SELECT id, codigo, nome, categoria, custo_unitario, fornecedor
    FROM FF_Pecas
    WHERE nome LIKE '%Hilux%'
       OR nome LIKE '%hilux%'
       OR nome LIKE '%HILUX%'
       OR nome LIKE '%Toyota%'
       OR nome LIKE '%Filtro%'
       OR nome LIKE '%Óleo%'
       OR nome LIKE '%Pastilha%'
       OR categoria IN ('Filtros', 'Óleos', 'Freios', 'Motor', 'Suspensão', 'Transmissão')
    ORDER BY categoria, nome
";

$result = $conn->query($sql);
$pecas = array();

while ($row = $result->fetch_assoc()) {
    $pecas[] = $row;
}

// Agrupar por categoria
$porCategoria = array();
foreach ($pecas as $peca) {
    $cat = isset($peca['categoria']) ? $peca['categoria'] : 'Sem Categoria';
    if (!isset($porCategoria[$cat])) {
        $porCategoria[$cat] = array();
    }
    $porCategoria[$cat][] = $peca;
}

echo json_encode(array(
    'success' => true,
    'total_pecas' => count($pecas),
    'pecas' => $pecas,
    'por_categoria' => $porCategoria
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>
