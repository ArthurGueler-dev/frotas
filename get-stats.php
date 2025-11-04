<?php
/**
 * API para buscar estatísticas do sistema
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Total de veículos
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM Vehicles');
    $totalVehicles = $stmt->fetch()['count'];

    // Total de motoristas
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM Drivers');
    $availableDrivers = $stmt->fetch()['count'];

    // Manutenções pendentes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM FF_Maintenances WHERE status IN ('Pendente', 'Em Progresso')");
    $maintenanceCount = $stmt->fetch()['count'];

    // OS abertas
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM FF_WorkOrders WHERE status IN ('Aberta', 'Em Andamento', 'Aguardando Peças')");
    $openWorkOrders = $stmt->fetch()['count'];

    $stats = array(
        'totalVehicles' => (int)$totalVehicles,
        'activeVehicles' => (int)$totalVehicles,
        'maintenanceVehicles' => (int)$maintenanceCount,
        'inactiveVehicles' => 0,
        'archivedVehicles' => 0,
        'availableDrivers' => (int)$availableDrivers,
        'openWorkOrders' => (int)$openWorkOrders,
        'monthlyCost' => 120000,
        'percentages' => array(
            'active' => 100,
            'maintenance' => 0,
            'inactive' => 0
        )
    );

    echo json_encode(array(
        'success' => true,
        'data' => $stats
    ));

} catch (Exception $e) {
    error_log('Erro ao buscar estatísticas: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
