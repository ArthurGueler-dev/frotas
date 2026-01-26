<?php
/**
 * Script para adicionar coluna 'codigo' na tabela ordemservico_itens
 * Executar UMA vez no cPanel
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();

    // Verificar se a coluna já existe
    $checkSql = "SHOW COLUMNS FROM ordemservico_itens LIKE 'codigo'";
    $checkStmt = $pdo->query($checkSql);
    $exists = $checkStmt->fetch();

    if ($exists) {
        echo json_encode([
            'success' => true,
            'message' => 'Coluna "codigo" já existe na tabela ordemservico_itens'
        ]);
        exit;
    }

    // Adicionar coluna codigo após categoria
    $sql = "ALTER TABLE ordemservico_itens
            ADD COLUMN codigo VARCHAR(50) NULL
            AFTER categoria";

    $pdo->exec($sql);

    echo json_encode([
        'success' => true,
        'message' => 'Coluna "codigo" adicionada com sucesso à tabela ordemservico_itens'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
