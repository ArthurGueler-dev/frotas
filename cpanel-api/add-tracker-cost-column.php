<?php
/**
 * Script para adicionar coluna TrackerCost na tabela Vehicles
 * Valor padrao: R$ 65,25 para todos os veiculos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resultado = array(
        'success' => true,
        'etapas' => array()
    );

    // 1. Verificar se coluna ja existe
    $stmt = $pdo->query("SHOW COLUMNS FROM Vehicles LIKE 'TrackerCost'");
    $colunaExiste = $stmt->fetch();

    if ($colunaExiste) {
        $resultado['etapas'][] = array(
            'acao' => 'verificar_coluna',
            'status' => 'ja_existe',
            'mensagem' => 'Coluna TrackerCost ja existe na tabela'
        );
    } else {
        // 2. Adicionar coluna TrackerCost
        $pdo->exec("ALTER TABLE Vehicles ADD COLUMN TrackerCost DECIMAL(10,2) NULL DEFAULT 65.25 COMMENT 'Custo Rastreador Mensal'");

        $resultado['etapas'][] = array(
            'acao' => 'adicionar_coluna',
            'status' => 'sucesso',
            'mensagem' => 'Coluna TrackerCost adicionada com sucesso'
        );
    }

    // 3. Atualizar todos os veiculos que tem NULL para o valor padrao
    $stmt = $pdo->exec("UPDATE Vehicles SET TrackerCost = 65.25 WHERE TrackerCost IS NULL");
    $veiculosAtualizados = $stmt;

    $resultado['etapas'][] = array(
        'acao' => 'atualizar_veiculos',
        'status' => 'sucesso',
        'veiculos_atualizados' => $veiculosAtualizados,
        'mensagem' => "Valor padrao R$ 65,25 aplicado a $veiculosAtualizados veiculos"
    );

    // 4. Verificar total de veiculos com TrackerCost
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(TrackerCost) as custo_total FROM Vehicles WHERE TrackerCost IS NOT NULL");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $resultado['estatisticas'] = array(
        'total_veiculos_com_rastreador' => (int)$stats['total'],
        'custo_total_mensal_rastreadores' => number_format((float)$stats['custo_total'], 2, ',', '.')
    );

    // 5. Mostrar estrutura da coluna
    $stmt = $pdo->query("SHOW COLUMNS FROM Vehicles LIKE 'TrackerCost'");
    $estrutura = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado['estrutura_coluna'] = $estrutura;

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Erro: ' . $e->getMessage()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
