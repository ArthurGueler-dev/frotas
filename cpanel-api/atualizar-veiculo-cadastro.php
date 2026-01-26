<?php
/**
 * API para atualizar dados cadastrais de um veículo
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'error' => 'Método não permitido'));
    exit();
}

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Erro de conexao: ' . $e->getMessage()));
    exit();
}

// Receber dados JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'error' => 'Dados inválidos'));
    exit();
}

// Verificar se tem placa ou ID
if (empty($data['plate']) && empty($data['id'])) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'error' => 'Placa ou ID são obrigatórios'));
    exit();
}

try {
    // Construir UPDATE dinâmico
    $campos = array();
    $valores = array();

    // Mapeamento de campos
    $camposMapeados = array(
        'VehicleName' => 'VehicleName',
        'Brand' => 'Brand',
        'VehicleType' => 'VehicleType',
        'VehicleYear' => 'VehicleYear',
        'Color' => 'Color',
        'FuelType' => 'FuelType',
        'area_id' => 'area_id',
        'Mileage' => 'Mileage',
        'EnginePower' => 'EnginePower',
        'EngineDisplacement' => 'EngineDisplacement',
        'EngineNumber' => 'EngineNumber',
        'Renavam' => 'Renavam',
        'ChassisNumber' => 'ChassisNumber',
        'DocExpiration' => 'DocExpiration',
        'DocStatus' => 'DocStatus',
        'FipeValue' => 'FipeValue',
        'IpvaCost' => 'IpvaCost',
        'InsuranceCost' => 'InsuranceCost',
        'LicensingCost' => 'LicensingCost',
        'DepreciationValue' => 'DepreciationValue',
        'TrackerCost' => 'TrackerCost'
    );

    foreach ($camposMapeados as $jsonField => $dbField) {
        if (isset($data[$jsonField])) {
            $valor = $data[$jsonField];

            // Tratar valores vazios
            if ($valor === '' || $valor === null) {
                // Para campos numéricos, usar NULL ou 0
                if (in_array($dbField, array('Mileage', 'FipeValue', 'IpvaCost', 'InsuranceCost', 'LicensingCost', 'DepreciationValue', 'TrackerCost', 'area_id'))) {
                    $valor = null;
                }
            }

            // Tratar data
            if ($dbField === 'DocExpiration' && !empty($valor)) {
                $valor = $valor; // Já vem no formato correto (YYYY-MM-DD)
            }

            $campos[] = "$dbField = ?";
            $valores[] = $valor;
        }
    }

    if (empty($campos)) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'error' => 'Nenhum campo para atualizar'));
        exit();
    }

    // Identificar veículo por ID ou placa
    if (!empty($data['id'])) {
        $whereClause = "Id = ?";
        $valores[] = $data['id'];
    } else {
        $whereClause = "LicensePlate = ?";
        $valores[] = $data['plate'];
    }

    $sql = "UPDATE Vehicles SET " . implode(', ', $campos) . " WHERE $whereClause";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    $rowsAffected = $stmt->rowCount();

    echo json_encode(array(
        'success' => true,
        'message' => 'Veículo atualizado com sucesso',
        'rowsAffected' => $rowsAffected,
        'sql_debug' => $sql,
        'campos_atualizados' => count($campos)
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Erro ao atualizar veículo',
        'message' => $e->getMessage()
    ));
}
?>
