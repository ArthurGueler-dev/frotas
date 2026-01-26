<?php
/**
 * REVERTER - Voltar nomes para "STRADA 1.4 Endurance" (sem Fiat)
 * Executar: https://floripa.in9automacao.com.br/reverter-nomes-strada.php?confirmar=SIM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$port = '3306';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    $stmt = $pdo->query("
        SELECT modelo_veiculo, COUNT(*) as total
        FROM FF_Pecas_Compatibilidade
        WHERE modelo_veiculo LIKE '%STRADA%'
        GROUP BY modelo_veiculo
    ");
    echo json_encode([
        'message' => 'Acesse com ?confirmar=SIM para REVERTER',
        'situacao_atual' => $stmt->fetchAll(),
        'acao' => 'Todos serão revertidos para "STRADA 1.4 Endurance"'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// REVERTER para o nome original
$nomeOriginal = 'STRADA 1.4 Endurance';

$stmt = $pdo->prepare("
    UPDATE FF_Pecas_Compatibilidade
    SET modelo_veiculo = :nome_original
    WHERE modelo_veiculo LIKE '%STRADA%Endurance%'
");
$stmt->execute(['nome_original' => $nomeOriginal]);
$atualizados = $stmt->rowCount();

// Verificar
$stmt = $pdo->query("
    SELECT modelo_veiculo, COUNT(*) as total
    FROM FF_Pecas_Compatibilidade
    WHERE modelo_veiculo LIKE '%STRADA%'
    GROUP BY modelo_veiculo
");

echo json_encode([
    'success' => true,
    'registros_revertidos' => $atualizados,
    'modelo_restaurado' => $nomeOriginal,
    'situacao_atual' => $stmt->fetchAll()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
