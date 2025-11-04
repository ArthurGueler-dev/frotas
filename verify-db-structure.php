<?php
/**
 * Script para verificar e corrigir a estrutura do banco de dados
 * Verifica se as tabelas necessárias existem e têm as colunas corretas
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }

    $results = [];

    // Verificar se a tabela ordemservico existe
    $sql = "SHOW TABLES LIKE 'ordemservico'";
    $stmt = $pdo->query($sql);
    $tableExists = $stmt->fetch();

    $results['table_exists'] = $tableExists ? true : false;

    if ($tableExists) {
        // Verificar estrutura da tabela
        $sql = "DESCRIBE ordemservico";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results['columns'] = $columns;

        // Verificar se veiculo_id existe
        $hasVeiculoId = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'veiculo_id') {
                $hasVeiculoId = true;
                break;
            }
        }

        $results['has_veiculo_id'] = $hasVeiculoId;

        // Se não tem veiculo_id, adicionar
        if (!$hasVeiculoId) {
            $sql = "ALTER TABLE ordemservico
                    ADD COLUMN veiculo_id INT NULL DEFAULT NULL
                    AFTER ordem_numero";
            $pdo->exec($sql);

            $sql = "ALTER TABLE ordemservico
                    ADD INDEX idx_veiculo (veiculo_id ASC)";
            $pdo->exec($sql);

            $sql = "ALTER TABLE ordemservico
                    ADD CONSTRAINT fk_os_veiculos
                    FOREIGN KEY (veiculo_id)
                    REFERENCES veiculos (id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE";
            $pdo->exec($sql);

            $results['fixed'] = true;
            $results['message'] = 'Coluna veiculo_id adicionada com sucesso';
        } else {
            $results['message'] = 'Estrutura da tabela está correta';
        }
    } else {
        $results['message'] = 'Tabela ordemservico não existe. Execute o script bd_frotas.sql';
    }

    // Verificar outras tabelas importantes
    $tables = ['ordemservico_itens', 'servicos', 'veiculos', 'motoristas'];
    $results['other_tables'] = [];

    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $stmt = $pdo->query($sql);
        $exists = $stmt->fetch();
        $results['other_tables'][$table] = $exists ? true : false;
    }

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
