<?php
/**
 * Script para verificar modelos cadastrados no sistema
 * Acesso: https://floripa.in9automacao.com.br/verificar-modelos.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configuração do banco de dados
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
        'error' => 'Erro de conexão com o banco de dados',
        'details' => $e->getMessage()
    ]);
    exit();
}

try {
    // Buscar todos os modelos de veículos
    $stmt = $conn->prepare("
        SELECT
            id,
            marca,
            modelo,
            ano,
            tipo
        FROM FF_VehicleModels
        ORDER BY marca, modelo
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $modelos = [];
    while ($row = $result->fetch_assoc()) {
        $modelos[] = [
            'id' => $row['id'],
            'marca' => $row['marca'],
            'modelo' => $row['modelo'],
            'ano' => $row['ano'],
            'tipo' => $row['tipo'],
            'nome_completo' => $row['marca'] . ' ' . $row['modelo'],
            'campo_para_busca' => $row['modelo'] // Este é o campo usado pela API
        ];
    }

    echo json_encode([
        'success' => true,
        'total' => count($modelos),
        'data' => $modelos,
        'instrucoes' => 'Use o campo "campo_para_busca" no SQL (modelo_carro = valor)'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar modelos',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
