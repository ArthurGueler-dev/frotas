<?php
/**
 * Script para adicionar campos Color e FuelType na tabela Vehicles
 * Execute ANTES do script de atualização
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
    die(json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage()]));
}

$resultado = [
    'campos_adicionados' => [],
    'campos_existentes' => [],
    'erros' => []
];

// Verificar e adicionar campo Color
try {
    $checkColor = $pdo->query("SHOW COLUMNS FROM Vehicles LIKE 'Color'");
    if ($checkColor->rowCount() == 0) {
        $pdo->exec("ALTER TABLE Vehicles ADD COLUMN Color VARCHAR(50) NULL AFTER EngineDisplacement");
        $resultado['campos_adicionados'][] = 'Color';
    } else {
        $resultado['campos_existentes'][] = 'Color';
    }
} catch (PDOException $e) {
    $resultado['erros'][] = "Erro ao adicionar Color: " . $e->getMessage();
}

// Verificar e adicionar campo FuelType
try {
    $checkFuel = $pdo->query("SHOW COLUMNS FROM Vehicles LIKE 'FuelType'");
    if ($checkFuel->rowCount() == 0) {
        $pdo->exec("ALTER TABLE Vehicles ADD COLUMN FuelType VARCHAR(50) NULL AFTER Color");
        $resultado['campos_adicionados'][] = 'FuelType';
    } else {
        $resultado['campos_existentes'][] = 'FuelType';
    }
} catch (PDOException $e) {
    $resultado['erros'][] = "Erro ao adicionar FuelType: " . $e->getMessage();
}

// Verificar estrutura final
$structureStmt = $pdo->query("SHOW COLUMNS FROM Vehicles WHERE Field IN ('Color', 'FuelType')");
$newFields = $structureStmt->fetchAll(PDO::FETCH_ASSOC);

$resultado['success'] = empty($resultado['erros']);
$resultado['estrutura_novos_campos'] = $newFields;
$resultado['mensagem'] = $resultado['success']
    ? 'Campos adicionados com sucesso! Agora execute atualizar-veiculos-frota.php'
    : 'Houve erros na execução';

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
