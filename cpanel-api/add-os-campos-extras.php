<?php
/**
 * Script para adicionar campos extras nas tabelas de OS
 * - modelo_veiculo na tabela ordemservico
 * - codigo, categoria, fornecedor_produto na tabela ordemservico_itens
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    $results = [];

    // 1. Adicionar campo modelo_veiculo na tabela ordemservico
    $checkCol1 = $pdo->query("SHOW COLUMNS FROM ordemservico LIKE 'modelo_veiculo'");
    if ($checkCol1->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ordemservico ADD COLUMN modelo_veiculo VARCHAR(100) NULL DEFAULT NULL AFTER placa_veiculo");
        $results[] = "Campo 'modelo_veiculo' adicionado na tabela ordemservico";
    } else {
        $results[] = "Campo 'modelo_veiculo' já existe na tabela ordemservico";
    }

    // 2. Adicionar campo codigo na tabela ordemservico_itens
    $checkCol2 = $pdo->query("SHOW COLUMNS FROM ordemservico_itens LIKE 'codigo'");
    if ($checkCol2->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ordemservico_itens ADD COLUMN codigo VARCHAR(50) NULL DEFAULT NULL AFTER tipo");
        $results[] = "Campo 'codigo' adicionado na tabela ordemservico_itens";
    } else {
        $results[] = "Campo 'codigo' já existe na tabela ordemservico_itens";
    }

    // 3. Adicionar campo categoria na tabela ordemservico_itens
    $checkCol3 = $pdo->query("SHOW COLUMNS FROM ordemservico_itens LIKE 'categoria'");
    if ($checkCol3->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ordemservico_itens ADD COLUMN categoria VARCHAR(50) NULL DEFAULT NULL AFTER codigo");
        $results[] = "Campo 'categoria' adicionado na tabela ordemservico_itens";
    } else {
        $results[] = "Campo 'categoria' já existe na tabela ordemservico_itens";
    }

    // 4. Adicionar campo fornecedor_produto na tabela ordemservico_itens
    $checkCol4 = $pdo->query("SHOW COLUMNS FROM ordemservico_itens LIKE 'fornecedor_produto'");
    if ($checkCol4->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ordemservico_itens ADD COLUMN fornecedor_produto VARCHAR(200) NULL DEFAULT NULL AFTER valor_unitario");
        $results[] = "Campo 'fornecedor_produto' adicionado na tabela ordemservico_itens";
    } else {
        $results[] = "Campo 'fornecedor_produto' já existe na tabela ordemservico_itens";
    }

    // 5. Adicionar campo fornecedor_servico na tabela ordemservico_itens
    $checkCol5 = $pdo->query("SHOW COLUMNS FROM ordemservico_itens LIKE 'fornecedor_servico'");
    if ($checkCol5->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ordemservico_itens ADD COLUMN fornecedor_servico VARCHAR(200) NULL DEFAULT NULL AFTER fornecedor_produto");
        $results[] = "Campo 'fornecedor_servico' adicionado na tabela ordemservico_itens";
    } else {
        $results[] = "Campo 'fornecedor_servico' já existe na tabela ordemservico_itens";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Verificação de campos concluída',
        'results' => $results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
