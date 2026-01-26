<?php
/**
 * Corrigir nomes de modelo STRADA na tabela FF_Pecas_Compatibilidade
 * Unificar para o nome usado em FF_VehicleModels: "Fiat STRADA 1.4 Endurance"
 *
 * Executar: https://floripa.in9automacao.com.br/corrigir-nomes-modelo-strada.php?confirmar=SIM
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

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    // Mostrar o que será alterado
    $stmt = $pdo->query("
        SELECT modelo_veiculo, COUNT(*) as total
        FROM FF_Pecas_Compatibilidade
        WHERE modelo_veiculo LIKE '%STRADA%'
        GROUP BY modelo_veiculo
        ORDER BY modelo_veiculo
    ");
    $modelos = $stmt->fetchAll();

    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar',
        'modelos_encontrados' => $modelos,
        'acao' => 'Todos serão unificados para "Fiat STRADA 1.4 Endurance"'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Nome correto (como está em FF_VehicleModels)
$nomeCorreto = 'Fiat STRADA 1.4 Endurance';

// Atualizar todos os registros STRADA para o nome correto
$stmt = $pdo->prepare("
    UPDATE FF_Pecas_Compatibilidade
    SET modelo_veiculo = :nome_correto,
        atualizado_em = NOW()
    WHERE modelo_veiculo LIKE '%STRADA%'
      AND modelo_veiculo != :nome_correto
");
$stmt->execute([
    'nome_correto' => $nomeCorreto
]);

$atualizados = $stmt->rowCount();

// Verificar resultado
$stmt = $pdo->query("
    SELECT modelo_veiculo, COUNT(*) as total
    FROM FF_Pecas_Compatibilidade
    WHERE modelo_veiculo LIKE '%STRADA%'
    GROUP BY modelo_veiculo
");
$resultado = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'registros_atualizados' => $atualizados,
    'modelo_padronizado' => $nomeCorreto,
    'situacao_atual' => $resultado,
    'proximos_passos' => [
        'Atualize a página planos-manutencao-novo.html (Ctrl+F5)',
        'Selecione "Fiat STRADA 1.4 Endurance" no dropdown',
        'Clique na aba "Peças Compatíveis"',
        'As peças devem aparecer agora'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
