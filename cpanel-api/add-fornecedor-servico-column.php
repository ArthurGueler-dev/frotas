<?php
/**
 * Script de Migração: Renomear coluna fornecedor e adicionar fornecedor_servico
 * Data: 2026-01-12
 *
 * Este script:
 * 1. Renomeia 'fornecedor' para 'fornecedor_produto'
 * 2. Adiciona nova coluna 'fornecedor_servico'
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Incluir configuração do banco
require_once 'config-db.php';

try {
    // Conectar ao banco de dados
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception('Erro de conexão: ' . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    $results = [];

    // 1. Verificar se a tabela existe
    $checkTable = "SHOW TABLES LIKE 'ordemservico_itens'";
    $result = $conn->query($checkTable);

    if ($result->num_rows === 0) {
        throw new Exception('Tabela ordemservico_itens não existe!');
    }

    $results['table_exists'] = true;

    // 2. Verificar colunas atuais
    $checkColumns = "SHOW COLUMNS FROM ordemservico_itens";
    $result = $conn->query($checkColumns);

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $results['current_columns'] = $columns;

    // 3. Renomear 'fornecedor' para 'fornecedor_produto' se necessário
    if (in_array('fornecedor', $columns) && !in_array('fornecedor_produto', $columns)) {
        $renameColumn = "ALTER TABLE ordemservico_itens
                        CHANGE COLUMN fornecedor fornecedor_produto VARCHAR(255) NULL DEFAULT NULL";

        if ($conn->query($renameColumn)) {
            $results['fornecedor_renamed'] = true;
        } else {
            throw new Exception('Erro ao renomear coluna fornecedor: ' . $conn->error);
        }
    } else {
        $results['fornecedor_renamed'] = 'already_exists_as_fornecedor_produto';
    }

    // 4. Adicionar coluna fornecedor_servico se não existir
    if (!in_array('fornecedor_servico', $columns)) {
        $addColumn = "ALTER TABLE ordemservico_itens
                      ADD COLUMN fornecedor_servico VARCHAR(255) NULL DEFAULT NULL
                      AFTER fornecedor_produto";

        if ($conn->query($addColumn)) {
            $results['fornecedor_servico_added'] = true;

            // 5. Adicionar índice
            $addIndex = "CREATE INDEX idx_fornecedor_servico ON ordemservico_itens(fornecedor_servico)";

            if ($conn->query($addIndex)) {
                $results['index_added'] = true;
            } else {
                $results['index_added'] = false;
                $results['index_error'] = $conn->error;
            }
        } else {
            throw new Exception('Erro ao adicionar coluna fornecedor_servico: ' . $conn->error);
        }
    } else {
        $results['fornecedor_servico_added'] = 'already_exists';
    }

    // 6. Mostrar estrutura final
    $describe = "DESCRIBE ordemservico_itens";
    $result = $conn->query($describe);

    $finalColumns = [];
    while ($row = $result->fetch_assoc()) {
        $finalColumns[] = $row;
    }

    $results['final_structure'] = $finalColumns;
    $results['message'] = 'Migração concluída com sucesso!';

    $conn->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
