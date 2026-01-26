<?php
/**
 * Diagnóstico de áreas dos veículos
 * Verifica quais veículos têm área associada
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

// 1. Verificar estrutura da tabela Vehicles (se tem campo area_id)
$structureStmt = $pdo->query("SHOW COLUMNS FROM Vehicles");
$colunas = $structureStmt->fetchAll(PDO::FETCH_ASSOC);

$colunasNomes = array();
$temAreaId = false;
foreach ($colunas as $col) {
    $colunasNomes[] = $col['Field'];
    if ($col['Field'] === 'area_id') {
        $temAreaId = true;
    }
}

$resultado['estrutura_vehicles'] = array(
    'colunas' => $colunasNomes,
    'tem_area_id' => $temAreaId
);

// 2. Listar áreas cadastradas
$areasStmt = $pdo->query("SELECT * FROM areas ORDER BY name");
$areas = $areasStmt->fetchAll(PDO::FETCH_ASSOC);
$resultado['areas_cadastradas'] = $areas;
$resultado['total_areas'] = count($areas);

// 3. Se tem area_id, verificar quantos veículos têm área associada
if ($temAreaId) {
    // Veículos COM área
    $comAreaStmt = $pdo->query("
        SELECT v.LicensePlate, v.VehicleName, v.area_id, a.name as area_name
        FROM Vehicles v
        LEFT JOIN areas a ON v.area_id = a.id
        WHERE v.area_id IS NOT NULL AND v.area_id > 0
        ORDER BY a.name, v.LicensePlate
    ");
    $comArea = $comAreaStmt->fetchAll(PDO::FETCH_ASSOC);

    // Veículos SEM área
    $semAreaStmt = $pdo->query("
        SELECT LicensePlate, VehicleName
        FROM Vehicles
        WHERE area_id IS NULL OR area_id = 0
        ORDER BY LicensePlate
    ");
    $semArea = $semAreaStmt->fetchAll(PDO::FETCH_ASSOC);

    // Contagem por área
    $porAreaStmt = $pdo->query("
        SELECT a.name as area_name, COUNT(v.Id) as total_veiculos
        FROM areas a
        LEFT JOIN Vehicles v ON v.area_id = a.id
        GROUP BY a.id, a.name
        ORDER BY a.name
    ");
    $porArea = $porAreaStmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado['veiculos_com_area'] = array(
        'total' => count($comArea),
        'lista' => array_slice($comArea, 0, 20) // Primeiros 20
    );

    $resultado['veiculos_sem_area'] = array(
        'total' => count($semArea),
        'lista' => array_slice($semArea, 0, 20) // Primeiros 20
    );

    $resultado['distribuicao_por_area'] = $porArea;
} else {
    $resultado['aviso'] = 'Campo area_id NAO EXISTE na tabela Vehicles. Precisa ser criado!';

    // Total de veículos
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM Vehicles");
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $resultado['total_veiculos'] = $total['total'];
}

$resultado['success'] = true;

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
