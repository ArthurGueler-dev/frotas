<?php
/**
 * API PHP - Filtros Disponíveis para Busca de Quilometragem
 * Endpoint: km-filters-api.php
 * Método: GET
 * Retorna: tipos de veículos, bases, centros de custo e status disponíveis
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

    error_log("🔍 Buscando filtros disponíveis...");

    // Buscar modelos de veículos únicos da tabela FF_VehicleModels
    $queryVehicleTypes = "
        SELECT DISTINCT tipo
        FROM FF_VehicleModels
        WHERE tipo IS NOT NULL AND tipo != ''
        ORDER BY tipo
    ";
    $stmtVehicleTypes = $pdo->query($queryVehicleTypes);
    $vehicleTypes = array();
    while ($row = $stmtVehicleTypes->fetch()) {
        $vehicleTypes[] = $row['tipo'];
    }

    // Buscar bases/localidades únicas da tabela Telemetria_Diaria
    $queryBases = "
        SELECT DISTINCT base
        FROM Telemetria_Diaria
        WHERE base IS NOT NULL AND base != '' AND base != 'N/A'
        ORDER BY base
    ";
    $stmtBases = $pdo->query($queryBases);
    $bases = array();
    while ($row = $stmtBases->fetch()) {
        $bases[] = $row['base'];
    }

    // Status baseado em IgnitionStatus da tabela Vehicles
    $queryStatuses = "
        SELECT DISTINCT IgnitionStatus
        FROM Vehicles
        WHERE IgnitionStatus IS NOT NULL AND IgnitionStatus != ''
        ORDER BY IgnitionStatus
    ";
    $stmtStatuses = $pdo->query($queryStatuses);
    $statuses = array();
    while ($row = $stmtStatuses->fetch()) {
        $statuses[] = $row['IgnitionStatus'];
    }

    $pdo = null;

    error_log("✅ Filtros carregados: " . count($vehicleTypes) . " tipos, " . count($bases) . " bases, " . count($statuses) . " status");

    echo json_encode(array(
        'success' => true,
        'filters' => array(
            'vehicleTypes' => $vehicleTypes,
            'bases' => $bases,
            'statuses' => $statuses
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("❌ Erro na API km-filters: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
