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

    // Buscar todos os veículos
    $sql = "SELECT
                Id as id,
                LicensePlate as plate,
                VehicleName as model,
                VehicleYear as year,
                DriverId as driverId,
                LastSpeed as speed,
                LastAddress as location,
                EngineStatus as engineStatus,
                IgnitionStatus as status
            FROM Vehicles
            ORDER BY LicensePlate";

    $stmt = $pdo->query($sql);
    $vehicles = $stmt->fetchAll();

    // Formatar dados para o frontend
    $formattedVehicles = array_map(function($v) {
        return array(
            'id' => $v['id'],
            'plate' => $v['plate'],
            'model' => !empty($v['model']) ? $v['model'] : 'N/A',
            'brand' => 'N/A',
            'year' => !empty($v['year']) ? $v['year'] : 'N/A',
            'mileage' => 0,
            'status' => !empty($v['status']) ? $v['status'] : 'Desconhecido',
            'color' => 'N/A',
            'fuel' => 'N/A',
            'type' => 'N/A',
            'base' => 'N/A',
            'location' => !empty($v['location']) ? $v['location'] : 'Localização desconhecida',
            'speed' => !empty($v['speed']) ? $v['speed'] : 0,
            'driverId' => $v['driverId']
        );
    }, $vehicles);

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
