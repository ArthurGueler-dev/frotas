<?php
/**
 * API para listar Manutenções do banco de dados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Buscar todas as manutenções com informações do veículo
    $sql = "SELECT 
                m.*,
                v.LicensePlate,
                v.VehicleName
            FROM FF_Maintenances m
            LEFT JOIN Vehicles v ON m.vehicle_id = v.Id
            ORDER BY m.scheduled_date DESC";

    $stmt = $pdo->query($sql);
    $maintenances = $stmt->fetchAll();

    echo json_encode(array(
        'success' => true,
        'data' => $maintenances
    ));

} catch (Exception $e) {
    error_log('Erro ao buscar manutenções: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
