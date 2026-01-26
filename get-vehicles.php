<?php
/**
 * API para listar Veículos do banco de dados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Buscar todos os veículos com área
    $sql = "SELECT
                v.Id as id,
                v.LicensePlate as plate,
                v.VehicleName as model,
                v.VehicleYear as year,
                v.DriverId as driverId,
                v.LastSpeed as speed,
                v.LastAddress as location,
                v.EngineStatus as engineStatus,
                v.IgnitionStatus as status,
                v.Brand as brand,
                v.VehicleType as vehicleType,
                v.Color as color,
                v.FuelType as fuel,
                v.Mileage as mileage,
                v.area_id,
                a.name as area_name
            FROM Vehicles v
            LEFT JOIN areas a ON v.area_id = a.id
            ORDER BY v.LicensePlate";

    $stmt = $pdo->query($sql);
    $vehicles = $stmt->fetchAll();

    // Formatar dados para o frontend
    $formattedVehicles = array();
    foreach ($vehicles as $v) {
        $formattedVehicles[] = array(
            'id' => $v['id'],
            'plate' => $v['plate'],
            'model' => !empty($v['model']) ? $v['model'] : 'N/A',
            'brand' => !empty($v['brand']) ? $v['brand'] : 'N/A',
            'year' => !empty($v['year']) ? $v['year'] : 'N/A',
            'mileage' => !empty($v['mileage']) ? intval($v['mileage']) : 0,
            'status' => !empty($v['status']) ? $v['status'] : 'Desconhecido',
            'color' => !empty($v['color']) ? $v['color'] : 'N/A',
            'fuel' => !empty($v['fuel']) ? $v['fuel'] : 'N/A',
            'type' => !empty($v['vehicleType']) ? $v['vehicleType'] : 'N/A',
            'base' => !empty($v['area_name']) ? $v['area_name'] : 'Sem Base',
            'area_id' => $v['area_id'],
            'location' => !empty($v['location']) ? $v['location'] : 'Localização desconhecida',
            'speed' => !empty($v['speed']) ? $v['speed'] : 0,
            'driverId' => $v['driverId']
        );
    }

    echo json_encode(array(
        'success' => true,
        'data' => $formattedVehicles
    ));

} catch (Exception $e) {
    error_log('Erro ao buscar veículos: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
