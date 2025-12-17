<?php
/**
 * API PHP - Busca Detalhada de Quilometragem
 * Endpoint: km-detailed-api.php
 * Método: GET
 * Parâmetros: startDate, endDate, plate, vehicleType, base, costCenter, status
 * Compatível com PHP 5.6+
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Se for OPTIONS, retorna 200
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuração do banco
require_once('db-config.php');

try {
    // Conectar ao banco usando PDO
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Pegar parâmetros da query string (compatível PHP 5.6)
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $plate = isset($_GET['plate']) ? $_GET['plate'] : null;
    $vehicleType = isset($_GET['vehicleType']) ? $_GET['vehicleType'] : null;
    $base = isset($_GET['base']) ? $_GET['base'] : null;
    $costCenter = isset($_GET['costCenter']) ? $_GET['costCenter'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;

    // Log dos parâmetros recebidos
    error_log("🔍 Busca detalhada KM: startDate=$startDate, endDate=$endDate, plate=$plate");

    // Construir query SQL dinâmica
    $query = "
        SELECT
            v.Id as id,
            v.LicensePlate as plate,
            v.VehicleName as model,
            v.VehicleYear as year,
            v.IgnitionStatus as status,
            td.data as date,
            td.km_final as kmFinal,
            td.km_inicial as kmInicial,
            td.base as base,
            (td.km_final - td.km_inicial) as kmRodado
        FROM Vehicles v
        LEFT JOIN Telemetria_Diaria td ON v.LicensePlate = td.LicensePlate
        WHERE 1=1
    ";

    $params = array();

    // Filtro de data
    if ($startDate) {
        $query .= " AND td.data >= :startDate";
        $params[':startDate'] = $startDate;
    }
    if ($endDate) {
        $query .= " AND td.data <= :endDate";
        $params[':endDate'] = $endDate;
    }

    // Filtro de placa
    if ($plate && $plate !== '') {
        $query .= " AND v.LicensePlate LIKE :plate";
        $params[':plate'] = "%$plate%";
    }

    // Filtro de base/localidade
    if ($base && $base !== '' && $base !== 'Base/Localidade') {
        $query .= " AND td.base = :base";
        $params[':base'] = $base;
    }

    // Filtro de status (IgnitionStatus)
    if ($status && $status !== 'Status do Veículo' && $status !== '') {
        $query .= " AND v.IgnitionStatus = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY td.data DESC, v.LicensePlate";

    // Preparar statement
    $stmt = $pdo->prepare($query);

    if ($stmt === false) {
        throw new Exception("Erro ao preparar query");
    }

    // Executar query
    $stmt->execute($params);

    // Agrupar por veículo
    $vehiclesMap = array();

    while ($row = $stmt->fetch()) {
        $key = $row['plate'];

        if (!isset($vehiclesMap[$key])) {
            $vehiclesMap[$key] = array(
                'id' => (int)$row['id'],
                'plate' => $row['plate'],
                'model' => $row['model'],
                'year' => $row['year'],
                'status' => $row['status'],
                'base' => $row['base'],
                'totalKm' => 0,
                'days' => array()
            );
        }

        $kmRodado = isset($row['kmRodado']) ? (float)$row['kmRodado'] : 0;

        if ($kmRodado > 0) {
            $vehiclesMap[$key]['totalKm'] += $kmRodado;
            $vehiclesMap[$key]['days'][] = array(
                'date' => $row['date'],
                'kmRodado' => $kmRodado,
                'kmInicial' => (float)$row['kmInicial'],
                'kmFinal' => (float)$row['kmFinal']
            );
        }
    }

    $pdo = null;

    $data = array_values($vehiclesMap);

    error_log("✅ " . count($data) . " veículos encontrados");

    echo json_encode(array(
        'success' => true,
        'data' => $data,
        'count' => count($data),
        'filters' => array(
            'startDate' => $startDate,
            'endDate' => $endDate,
            'plate' => $plate,
            'status' => $status
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("❌ Erro na API km-detailed: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
