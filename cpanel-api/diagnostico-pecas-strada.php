<?php
/**
 * Diagnóstico - Verificar peças da Strada no sistema
 * Executar: https://floripa.in9automacao.com.br/diagnostico-pecas-strada.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configuração do banco de dados
$host = '187.49.226.10';
$port = '3306';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$resultado = [];

// 1. Verificar peças com "STRADA" no nome em FF_Pecas
$stmt = $pdo->query("SELECT id, codigo, nome, categoria, custo_unitario FROM FF_Pecas WHERE nome LIKE '%STRADA%' AND ativo = 1");
$pecasStrada = $stmt->fetchAll();
$resultado['1_pecas_strada_em_FF_Pecas'] = [
    'quantidade' => count($pecasStrada),
    'pecas' => $pecasStrada
];

// 2. Verificar total de peças em FF_Pecas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE ativo = 1");
$resultado['2_total_pecas_FF_Pecas'] = $stmt->fetch()['total'];

// 3. Verificar últimas 10 peças inseridas
$stmt = $pdo->query("SELECT id, codigo, nome, categoria, criado_em FROM FF_Pecas WHERE ativo = 1 ORDER BY id DESC LIMIT 10");
$resultado['3_ultimas_10_pecas_inseridas'] = $stmt->fetchAll();

// 4. Verificar modelos disponíveis em FF_VehicleModels
$stmt = $pdo->query("SELECT id, marca, modelo, ano FROM FF_VehicleModels WHERE modelo LIKE '%STRADA%' ORDER BY modelo");
$resultado['4_modelos_strada_FF_VehicleModels'] = $stmt->fetchAll();

// 5. Verificar todos os modelos em FF_VehicleModels
$stmt = $pdo->query("SELECT DISTINCT CONCAT(marca, ' ', modelo) as modelo_completo FROM FF_VehicleModels ORDER BY modelo_completo");
$resultado['5_todos_modelos_FF_VehicleModels'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 6. Verificar compatibilidades existentes para Strada
$stmt = $pdo->query("SELECT * FROM FF_Pecas_Compatibilidade WHERE modelo_veiculo LIKE '%STRADA%' AND ativo = 1");
$resultado['6_compatibilidades_strada'] = $stmt->fetchAll();

// 7. Verificar modelos em FF_Pecas_Compatibilidade
$stmt = $pdo->query("SELECT DISTINCT modelo_veiculo FROM FF_Pecas_Compatibilidade WHERE ativo = 1 ORDER BY modelo_veiculo");
$resultado['7_modelos_em_compatibilidade'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 8. Verificar estrutura da tabela FF_Pecas
$stmt = $pdo->query("DESCRIBE FF_Pecas");
$resultado['8_estrutura_FF_Pecas'] = $stmt->fetchAll();

// 9. Verificar se tabela FF_Pecas_Compatibilidade existe
try {
    $stmt = $pdo->query("DESCRIBE FF_Pecas_Compatibilidade");
    $resultado['9_estrutura_FF_Pecas_Compatibilidade'] = $stmt->fetchAll();
} catch (Exception $e) {
    $resultado['9_estrutura_FF_Pecas_Compatibilidade'] = 'TABELA NÃO EXISTE: ' . $e->getMessage();
}

// 10. Resumo e próximos passos
$resultado['10_resumo'] = [
    'pecas_strada_encontradas' => count($pecasStrada),
    'problema_identificado' => count($pecasStrada) == 0
        ? 'Nenhuma peça da Strada foi inserida em FF_Pecas. Execute importar-pecas-strada-perplexity.php primeiro.'
        : (empty($resultado['6_compatibilidades_strada'])
            ? 'Peças existem mas não estão registradas em FF_Pecas_Compatibilidade. Execute registrar-compatibilidade-strada.php'
            : 'Peças e compatibilidades existem. Verifique o nome do modelo no select da página.'),
    'scripts_para_executar' => [
        '1. importar-pecas-strada-perplexity.php - Inserir peças na tabela FF_Pecas',
        '2. registrar-compatibilidade-strada.php?confirmar=SIM - Registrar na tabela de compatibilidade'
    ]
];

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
