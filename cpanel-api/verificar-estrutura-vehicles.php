<?php
/**
 * Script para verificar estrutura da tabela Vehicles
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
    die(json_encode(['success' => false, 'error' => 'Erro de conexão']));
}

// Estrutura da tabela
$structureStmt = $pdo->query("DESCRIBE Vehicles");
$structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar campos específicos
$campos_necessarios = ['Color', 'FuelType', 'Renavam', 'ChassisNumber', 'VehicleYear'];
$campos_existentes = array_column($structure, 'Field');

$campos_faltando = [];
foreach ($campos_necessarios as $campo) {
    if (!in_array($campo, $campos_existentes)) {
        $campos_faltando[] = $campo;
    }
}

// Contar veículos
$countStmt = $pdo->query("SELECT COUNT(*) as total FROM Vehicles");
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Amostra de um veículo
$sampleStmt = $pdo->query("SELECT * FROM Vehicles LIMIT 1");
$sample = $sampleStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'estrutura' => $structure,
    'campos_existentes' => $campos_existentes,
    'campos_necessarios' => $campos_necessarios,
    'campos_faltando' => $campos_faltando,
    'total_veiculos' => $total,
    'exemplo_veiculo' => $sample
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
