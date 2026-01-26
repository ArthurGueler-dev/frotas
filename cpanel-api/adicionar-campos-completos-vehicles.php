<?php
/**
 * Script para adicionar todos os campos necessários na tabela Vehicles
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(array('success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage())));
}

$resultado = array(
    'campos_adicionados' => array(),
    'campos_existentes' => array(),
    'erros' => array()
);

// Lista de campos a adicionar com suas definições
$campos = array(
    'Brand' => 'VARCHAR(100) NULL COMMENT "Marca do veículo"',
    'VehicleType' => 'VARCHAR(50) NULL COMMENT "Tipo: Passeio, Caminhão, Van, etc"',
    'Color' => 'VARCHAR(50) NULL COMMENT "Cor predominante"',
    'FuelType' => 'VARCHAR(50) NULL COMMENT "Combustível"',
    'EngineNumber' => 'VARCHAR(50) NULL COMMENT "Número do motor"',
    'Mileage' => 'INT NULL COMMENT "Quilometragem atual"',
    'DocExpiration' => 'DATE NULL COMMENT "Vencimento da documentação"',
    'DocStatus' => 'VARCHAR(30) NULL DEFAULT "Em dia" COMMENT "Status: Em dia, Vencida, Pendente"',
    'FipeValue' => 'DECIMAL(12,2) NULL COMMENT "Valor FIPE"',
    'IpvaCost' => 'DECIMAL(10,2) NULL COMMENT "Custo IPVA anual"',
    'InsuranceCost' => 'DECIMAL(10,2) NULL COMMENT "Custo Seguro anual"',
    'LicensingCost' => 'DECIMAL(10,2) NULL COMMENT "Custo Licenciamento"',
    'DepreciationValue' => 'DECIMAL(12,2) NULL COMMENT "Valor depreciação"'
);

// Verificar campos existentes
$existingColumns = array();
$columnsStmt = $pdo->query("SHOW COLUMNS FROM Vehicles");
while ($row = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {
    $existingColumns[] = $row['Field'];
}

// Adicionar campos faltantes
foreach ($campos as $campo => $definicao) {
    if (in_array($campo, $existingColumns)) {
        $resultado['campos_existentes'][] = $campo;
    } else {
        try {
            $sql = "ALTER TABLE Vehicles ADD COLUMN $campo $definicao";
            $pdo->exec($sql);
            $resultado['campos_adicionados'][] = $campo;
        } catch (PDOException $e) {
            $resultado['erros'][] = "Erro ao adicionar $campo: " . $e->getMessage();
        }
    }
}

// Mostrar estrutura final
$finalStmt = $pdo->query("SHOW COLUMNS FROM Vehicles");
$estrutura_final = $finalStmt->fetchAll(PDO::FETCH_ASSOC);

$resultado['success'] = count($resultado['erros']) === 0;
$resultado['estrutura_final'] = $estrutura_final;
$resultado['mensagem'] = $resultado['success']
    ? 'Campos adicionados com sucesso!'
    : 'Houve erros na execução';

echo json_encode($resultado, JSON_PRETTY_PRINT);
?>
