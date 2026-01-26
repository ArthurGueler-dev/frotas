<?php
/**
 * Script para verificar veículos cadastrados e estrutura da tabela
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Conexão com banco
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de conexão']);
    exit;
}

// Buscar estrutura da tabela
$structureStmt = $pdo->query("DESCRIBE Vehicles");
$structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os veículos
$vehiclesStmt = $pdo->query("
    SELECT v.*, m.Brand, m.Model as ModelName, m.Year as ModelYear
    FROM Vehicles v
    LEFT JOIN FF_VehicleModels m ON v.ModelId = m.Id
    ORDER BY v.LicensePlate ASC
");
$vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar modelos disponíveis
$modelsStmt = $pdo->query("SELECT * FROM FF_VehicleModels ORDER BY Brand, Model");
$models = $modelsStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'table_structure' => $structure,
    'vehicles' => $vehicles,
    'total_vehicles' => count($vehicles),
    'models' => $models,
    'total_models' => count($models)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
