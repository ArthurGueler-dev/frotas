<?php
/**
 * Script de Migração: Adicionar coluna fornecedor na tabela ordemservico_itens
 * Data: 2026-01-12
 *
 * Este script adiciona a coluna 'fornecedor' na tabela ordemservico_itens
 * se ela ainda não existir.
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

    // 1. Verificar se a tabela ordemservico_itens existe
    $checkTable = "SHOW TABLES LIKE 'ordemservico_itens'";
    $result = $conn->query($checkTable);

    if ($result->num_rows === 0) {
        throw new Exception('Tabela ordemservico_itens não existe!');
    }

    $results['table_exists'] = true;

    // 2. Verificar se a coluna fornecedor já existe
    $checkColumn = "SHOW COLUMNS FROM ordemservico_itens LIKE 'fornecedor'";
    $result = $conn->query($checkColumn);

    if ($result->num_rows > 0) {
        $results['column_exists'] = true;
        $results['message'] = 'Coluna fornecedor já existe. Nenhuma alteração necessária.';
    } else {
        $results['column_exists'] = false;

        // 3. Adicionar coluna fornecedor
        $addColumn = "ALTER TABLE ordemservico_itens
                      ADD COLUMN fornecedor VARCHAR(255) NULL DEFAULT NULL
                      AFTER valor_unitario";

        if ($conn->query($addColumn)) {
            $results['column_added'] = true;

            // 4. Adicionar índice para melhorar performance
            $addIndex = "CREATE INDEX idx_fornecedor ON ordemservico_itens(fornecedor)";

            if ($conn->query($addIndex)) {
                $results['index_added'] = true;
                $results['message'] = 'Coluna fornecedor e índice adicionados com sucesso!';
            } else {
                $results['index_added'] = false;
                $results['message'] = 'Coluna adicionada, mas falha ao criar índice: ' . $conn->error;
            }
        } else {
            throw new Exception('Erro ao adicionar coluna: ' . $conn->error);
        }
    }

    // 5. Mostrar estrutura atual da tabela
    $describe = "DESCRIBE ordemservico_itens";
    $result = $conn->query($describe);

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }

    $results['table_structure'] = $columns;

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
