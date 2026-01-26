<?php
/**
 * Criar área Serra e associar veículos sem base
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
} catch (PDOException $e) {
    die(json_encode(array('success' => false, 'error' => 'Erro de conexao: ' . $e->getMessage())));
}

$resultado = array();

// 1. Verificar se área Serra já existe
$checkStmt = $pdo->prepare("SELECT id FROM areas WHERE name = ?");
$checkStmt->execute(array('Serra'));
$areaExistente = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($areaExistente) {
    $serraId = $areaExistente['id'];
    $resultado['area_serra'] = array('status' => 'ja_existia', 'id' => $serraId);
} else {
    // Criar área Serra
    $insertStmt = $pdo->prepare("INSERT INTO areas (name, state, description, is_active) VALUES (?, ?, ?, 1)");
    $insertStmt->execute(array('Serra', 'ES', 'Região da Serra - ES'));
    $serraId = $pdo->lastInsertId();
    $resultado['area_serra'] = array('status' => 'criada', 'id' => $serraId);
}

// 2. Buscar veículos sem área
$semAreaStmt = $pdo->query("
    SELECT Id, LicensePlate, VehicleName
    FROM Vehicles
    WHERE area_id IS NULL OR area_id = 0
");
$veiculosSemArea = $semAreaStmt->fetchAll(PDO::FETCH_ASSOC);

$resultado['veiculos_encontrados'] = count($veiculosSemArea);

// 3. Associar todos à área Serra
if (count($veiculosSemArea) > 0) {
    $updateStmt = $pdo->prepare("UPDATE Vehicles SET area_id = ? WHERE area_id IS NULL OR area_id = 0");
    $updateStmt->execute(array($serraId));
    $resultado['veiculos_atualizados'] = $updateStmt->rowCount();
} else {
    $resultado['veiculos_atualizados'] = 0;
}

// 4. Listar veículos que foram atualizados
$resultado['veiculos_lista'] = array();
foreach ($veiculosSemArea as $v) {
    $resultado['veiculos_lista'][] = $v['LicensePlate'] . ' - ' . $v['VehicleName'];
}

// 5. Nova distribuição por área
$distribuicaoStmt = $pdo->query("
    SELECT a.name as area_name, COUNT(v.Id) as total_veiculos
    FROM areas a
    LEFT JOIN Vehicles v ON v.area_id = a.id
    GROUP BY a.id, a.name
    ORDER BY a.name
");
$resultado['nova_distribuicao'] = $distribuicaoStmt->fetchAll(PDO::FETCH_ASSOC);

$resultado['success'] = true;

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
