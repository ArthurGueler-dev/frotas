<?php
/**
 * Diagnóstico completo dos veículos - mostra quais campos estão vazios
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

// Verificar estrutura da tabela
$structureStmt = $pdo->query("SHOW COLUMNS FROM Vehicles");
$colunas = $structureStmt->fetchAll(PDO::FETCH_ASSOC);

$colunasExistentes = array();
foreach ($colunas as $col) {
    $colunasExistentes[] = $col['Field'];
}

// Campos que deveriam existir
$camposNecessarios = array(
    'LicensePlate', 'VehicleName', 'VehicleYear', 'Renavam', 'ChassisNumber',
    'Brand', 'VehicleType', 'Color', 'FuelType', 'EnginePower', 'EngineDisplacement', 'EngineNumber',
    'Mileage', 'FipeValue', 'IpvaCost', 'InsuranceCost', 'LicensingCost', 'DepreciationValue',
    'DocExpiration', 'DocStatus'
);

$camposFaltantes = array();
foreach ($camposNecessarios as $campo) {
    if (!in_array($campo, $colunasExistentes)) {
        $camposFaltantes[] = $campo;
    }
}

// Buscar todos os veículos
$stmt = $pdo->query("SELECT * FROM Vehicles ORDER BY LicensePlate");
$veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = array(
    'total_veiculos' => count($veiculos),
    'colunas_existentes' => $colunasExistentes,
    'campos_necessarios_faltando_na_tabela' => $camposFaltantes,
    'veiculos_com_campos_vazios' => array(),
    'resumo_campos_vazios' => array()
);

// Campos a verificar (apenas os que existem na tabela)
$camposVerificar = array(
    'VehicleName' => 'Modelo',
    'Brand' => 'Marca',
    'VehicleType' => 'Tipo',
    'Color' => 'Cor',
    'FuelType' => 'Combustivel',
    'EnginePower' => 'Potencia',
    'EngineDisplacement' => 'Cilindradas',
    'EngineNumber' => 'NumeroMotor',
    'Mileage' => 'Quilometragem',
    'FipeValue' => 'ValorFIPE',
    'IpvaCost' => 'CustoIPVA',
    'InsuranceCost' => 'CustoSeguro',
    'LicensingCost' => 'CustoLicenciamento',
    'DepreciationValue' => 'Depreciacao'
);

// Inicializar contadores
foreach ($camposVerificar as $campo => $nome) {
    if (in_array($campo, $colunasExistentes)) {
        $resultado['resumo_campos_vazios'][$nome] = 0;
    }
}

// Analisar cada veículo
foreach ($veiculos as $veiculo) {
    $camposVazios = array();

    foreach ($camposVerificar as $campo => $nome) {
        if (!in_array($campo, $colunasExistentes)) {
            continue; // Campo não existe na tabela
        }

        $valor = isset($veiculo[$campo]) ? $veiculo[$campo] : null;
        $vazio = ($valor === null || $valor === '' || $valor === '0' || $valor === '0.00');

        if ($vazio) {
            $camposVazios[] = $nome;
            $resultado['resumo_campos_vazios'][$nome]++;
        }
    }

    if (count($camposVazios) > 0) {
        $resultado['veiculos_com_campos_vazios'][] = array(
            'placa' => $veiculo['LicensePlate'],
            'modelo' => isset($veiculo['VehicleName']) ? $veiculo['VehicleName'] : 'N/A',
            'campos_vazios' => $camposVazios,
            'total_vazios' => count($camposVazios)
        );
    }
}

// Ordenar por quantidade de campos vazios (mais problemáticos primeiro)
usort($resultado['veiculos_com_campos_vazios'], function($a, $b) {
    return $b['total_vazios'] - $a['total_vazios'];
});

// Limitar a 20 veículos mais problemáticos na saída
$resultado['veiculos_mais_problematicos'] = array_slice($resultado['veiculos_com_campos_vazios'], 0, 20);
$resultado['total_veiculos_com_problemas'] = count($resultado['veiculos_com_campos_vazios']);

// Remover lista completa para não sobrecarregar
unset($resultado['veiculos_com_campos_vazios']);

$resultado['success'] = true;

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
