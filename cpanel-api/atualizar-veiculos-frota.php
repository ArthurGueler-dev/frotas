<?php
/**
 * Script para atualizar veículos da frota com dados do CSV
 * SEGURO: Só atualiza campos que estão NULL ou vazios
 *
 * Executa: INSERT dos faltantes + UPDATE APENAS de campos vazios
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
    'inseridos' => [],
    'atualizados' => [],
    'ignorados' => [],
    'erros' => []
];

// ============================================
// PARTE 1: INSERIR VEÍCULOS FALTANTES (3)
// ============================================

$veiculos_inserir = [
    [
        'placa' => 'MTQ3874',
        'nome' => 'VW 13.180 CNM',
        'ano' => '2011',
        'renavam' => '00335152376',
        'chassi' => '953467239BR138114',
        'cor' => 'BRANCA',
        'combustivel' => 'DIESEL',
        'potencia' => '180',
        'cilindradas' => '4.740'
    ],
    [
        'placa' => 'PPV1E52',
        'nome' => 'HONDA CG 125I FAN',
        'ano' => '2018',
        'renavam' => '01143103510',
        'chassi' => '9C2JC6900JR307480',
        'cor' => 'PRETA',
        'combustivel' => 'GASOLINA',
        'potencia' => '12',
        'cilindradas' => '0.124'
    ],
    [
        'placa' => 'RHL3B76',
        'nome' => 'VW GOL 1.6L MB5',
        'ano' => '2022',
        'renavam' => '1277392410',
        'chassi' => '9BWAB45U2NT079331',
        'cor' => 'BRANCA',
        'combustivel' => 'ALCOOL/GASOLINA',
        'potencia' => '104',
        'cilindradas' => '1.598'
    ]
];

foreach ($veiculos_inserir as $v) {
    try {
        $checkStmt = $pdo->prepare("SELECT Id FROM Vehicles WHERE LicensePlate = ?");
        $checkStmt->execute([$v['placa']]);

        if ($checkStmt->fetch()) {
            $resultado['ignorados'][] = "INSERT {$v['placa']} - já existe";
            continue;
        }

        $stmt = $pdo->prepare("
            INSERT INTO Vehicles (
                LicensePlate, VehicleName, VehicleYear, Renavam, ChassisNumber,
                EnginePower, EngineDisplacement, Color, FuelType, IsWhitelisted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $v['placa'],
            $v['nome'],
            $v['ano'],
            $v['renavam'],
            $v['chassi'],
            $v['potencia'] . ' cv',
            $v['cilindradas'],
            $v['cor'],
            $v['combustivel']
        ]);

        $resultado['inseridos'][] = $v['placa'] . ' - ' . $v['nome'];
    } catch (PDOException $e) {
        $resultado['erros'][] = "Erro INSERT {$v['placa']}: " . $e->getMessage();
    }
}

// ============================================
// PARTE 2: ATUALIZAR APENAS CAMPOS VAZIOS
// ============================================

$atualizacoes = [
    // Veículos com TODOS os campos para preencher
    ['placa' => 'EKU9H22', 'renavam' => '1197290831', 'chassi' => '93ZC35B01K8485800', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'ano' => '2019'],
    ['placa' => 'RBA2F98', 'renavam' => '1227336397', 'chassi' => '9BD1196GDM1156981', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'ano' => '2021'],
    ['placa' => 'RVS7E02', 'renavam' => '1329883915', 'chassi' => '93XLJKL1TPCN65915', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL', 'ano' => '2023'],
    ['placa' => 'SIY6H86', 'renavam' => '1365511933', 'chassi' => '9BD341ACZRY921914', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA', 'ano' => '2024'],

    // Veículos com apenas Cor e Combustível
    ['placa' => 'BDI3G10', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'FEV7J00', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'FFK7H28', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'FPW8F78', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'MTQ7J93', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'OVE4358', 'cor' => 'VERDE', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'OVK0C71', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'PPC6J12', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'PPG4B36', 'cor' => 'PRATA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'PPI7E95', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'PPT7D92', 'cor' => 'PRATA', 'combustivel' => 'DIESEL'],
    ['placa' => 'PPV7A55', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'PPW0562', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'PPX2803', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'QRM6D15', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'QRM8C24', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'RBE1J59', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RBF3B52', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RBG9E05', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RBG9E06', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RBG9E07', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMJ5D10', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMJ5D13', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMJ5D18', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO1G52', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO1G96', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO3F38', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO3F64', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO3H46', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO3H62', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO3H69', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO3H76', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO3J23', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO4A08', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO4A32', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO5I38', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO5J29', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO5J32', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMO5J35', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RMR5H78', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RNA8G41', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RNC4G56', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'RNH0A91', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'RNQ2H45', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'RNQ2H54', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'RNR0D90', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RNZ5A49', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RQS3I74', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RQS7F87', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RQT8J27', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RQT8J28', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTA8J97', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTA9A37', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTA9A39', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTA9A40', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTA9A41', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTA9A55', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTA9J00', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTB4D56', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTB5E31', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTB5F87', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTB5G60', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTE5D36', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTG1G68', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTG2F73', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTS9B34', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTS9B92', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTS9D53', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTS9E12', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RTS9E91', 'cor' => 'BRANCA', 'combustivel' => 'ALCOOL/GASOLINA'],
    ['placa' => 'RUR3I05', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'SFT4I72', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'SGD9B96', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL'],
    ['placa' => 'SGF3H84', 'cor' => 'BRANCA', 'combustivel' => 'DIESEL']
];

// Função para verificar se campo está vazio
function campoVazio($valor) {
    return $valor === null || $valor === '' || trim($valor) === '';
}

foreach ($atualizacoes as $v) {
    try {
        // Primeiro, buscar dados atuais do veículo
        $selectStmt = $pdo->prepare("SELECT Renavam, ChassisNumber, VehicleYear, Color, FuelType FROM Vehicles WHERE LicensePlate = ?");
        $selectStmt->execute([$v['placa']]);
        $atual = $selectStmt->fetch(PDO::FETCH_ASSOC);

        if (!$atual) {
            $resultado['ignorados'][] = "UPDATE {$v['placa']} - não encontrado no banco";
            continue;
        }

        // Construir UPDATE apenas para campos vazios
        $setClauses = [];
        $params = [];
        $camposAtualizados = [];

        // Renavam - só atualiza se vazio
        if (isset($v['renavam']) && campoVazio($atual['Renavam'])) {
            $setClauses[] = "Renavam = ?";
            $params[] = $v['renavam'];
            $camposAtualizados[] = 'Renavam';
        }

        // ChassisNumber - só atualiza se vazio
        if (isset($v['chassi']) && campoVazio($atual['ChassisNumber'])) {
            $setClauses[] = "ChassisNumber = ?";
            $params[] = $v['chassi'];
            $camposAtualizados[] = 'ChassisNumber';
        }

        // VehicleYear - só atualiza se vazio
        if (isset($v['ano']) && campoVazio($atual['VehicleYear'])) {
            $setClauses[] = "VehicleYear = ?";
            $params[] = $v['ano'];
            $camposAtualizados[] = 'VehicleYear';
        }

        // Color - só atualiza se vazio
        if (isset($v['cor']) && campoVazio($atual['Color'])) {
            $setClauses[] = "Color = ?";
            $params[] = $v['cor'];
            $camposAtualizados[] = 'Color';
        }

        // FuelType - só atualiza se vazio
        if (isset($v['combustivel']) && campoVazio($atual['FuelType'])) {
            $setClauses[] = "FuelType = ?";
            $params[] = $v['combustivel'];
            $camposAtualizados[] = 'FuelType';
        }

        // Se não há campos para atualizar, pular
        if (empty($setClauses)) {
            $resultado['ignorados'][] = "{$v['placa']} - todos campos já preenchidos";
            continue;
        }

        // Executar UPDATE
        $params[] = $v['placa'];
        $sql = "UPDATE Vehicles SET " . implode(", ", $setClauses) . " WHERE LicensePlate = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            $resultado['atualizados'][] = $v['placa'] . ' (' . implode(', ', $camposAtualizados) . ')';
        }
    } catch (PDOException $e) {
        $resultado['erros'][] = "Erro UPDATE {$v['placa']}: " . $e->getMessage();
    }
}

// ============================================
// RESULTADO FINAL
// ============================================

$resultado['success'] = true;
$resultado['resumo'] = [
    'total_inseridos' => count($resultado['inseridos']),
    'total_atualizados' => count($resultado['atualizados']),
    'total_ignorados' => count($resultado['ignorados']),
    'total_erros' => count($resultado['erros'])
];

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
