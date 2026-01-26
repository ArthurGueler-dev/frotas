<?php
/**
 * Script para adicionar coluna 'universal' na tabela FF_Pecas
 * Permite que peças sejam compatíveis com todos os veículos
 *
 * Executar uma única vez: https://floripa.in9automacao.com.br/add-universal-column.php
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

    $results = [];

    // 1. Verificar se a coluna já existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM FF_Pecas LIKE 'universal'");

    if ($checkColumn->num_rows == 0) {
        // Adicionar coluna universal (1 = compatível com todos, 0 = precisa de compatibilidade específica)
        $sql = "ALTER TABLE FF_Pecas ADD COLUMN universal TINYINT(1) DEFAULT 1 AFTER ativo";
        $conn->query($sql);
        $results[] = "Coluna 'universal' adicionada com sucesso (default = 1)";

        // Atualizar todas as peças existentes para serem universais por padrão
        $updateSql = "UPDATE FF_Pecas SET universal = 1 WHERE universal IS NULL";
        $conn->query($updateSql);
        $affected = $conn->affected_rows;
        $results[] = "Peças existentes atualizadas para universal: {$affected} registros";
    } else {
        $results[] = "Coluna 'universal' já existe na tabela";
    }

    // 2. Verificar estrutura atual da tabela
    $estrutura = $conn->query("DESCRIBE FF_Pecas");
    $colunas = [];
    while ($row = $estrutura->fetch_assoc()) {
        $colunas[] = $row;
    }

    // 3. Contar peças universais e específicas
    $countUniv = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 1");
    $countEsp = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 0");

    $totalUniversais = $countUniv->fetch_assoc()['total'];
    $totalEspecificas = $countEsp->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'message' => 'Script executado com sucesso',
        'results' => $results,
        'estatisticas' => [
            'pecas_universais' => $totalUniversais,
            'pecas_especificas' => $totalEspecificas
        ],
        'estrutura_tabela' => $colunas
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
