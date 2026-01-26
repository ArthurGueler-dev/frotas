<?php
/**
 * Debug - Verificar estrutura e dados de ordemservico_itens
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

    $resultado = array('success' => true);

    // 1. Estrutura da tabela
    $stmt = $pdo->query("DESCRIBE ordemservico_itens");
    $resultado['estrutura_tabela'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Valores distintos do campo tipo
    $stmt = $pdo->query("SELECT DISTINCT tipo, COUNT(*) as quantidade FROM ordemservico_itens GROUP BY tipo");
    $resultado['tipos_distintos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ultimos 10 itens de OS finalizadas
    $stmt = $pdo->query("SELECT oi.*, os.status as os_status, os.ordem_numero
                         FROM ordemservico_itens oi
                         JOIN ordemservico os ON os.ordem_numero = oi.ordem_numero
                         WHERE os.status = 'Finalizada'
                         ORDER BY oi.id DESC
                         LIMIT 10");
    $resultado['ultimos_itens_finalizados'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Total de itens em OS finalizadas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ordemservico_itens oi
                         JOIN ordemservico os ON os.ordem_numero = oi.ordem_numero
                         WHERE os.status = 'Finalizada'");
    $resultado['total_itens_finalizados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 5. Verificar OS finalizadas recentemente
    $stmt = $pdo->query("SELECT os.ordem_numero, os.placa_veiculo, os.status, os.data_finalizacao,
                         (SELECT COUNT(*) FROM ordemservico_itens oi WHERE oi.ordem_numero = os.ordem_numero) as qtd_itens
                         FROM ordemservico os
                         WHERE os.status = 'Finalizada'
                         ORDER BY os.data_finalizacao DESC
                         LIMIT 5");
    $resultado['os_finalizadas_recentes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
