<?php
/**
 * API PHP - Busca KM por Período
 * Endpoint: km-by-period-api.php
 * Método: GET
 * Parâmetros: period (today, yesterday, week, month), plate (opcional)
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

    $period = isset($_GET['period']) ? $_GET['period'] : 'today';
    $plate = isset($_GET['plate']) ? $_GET['plate'] : null;

    error_log("📊 Busca KM por período: $period" . ($plate ? " para $plate" : ""));

    // Calcular datas baseado no período
    $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $startDate = null;
    $endDate = null;

    switch ($period) {
        case 'today':
            $startDate = clone $now;
            $startDate->setTime(0, 0, 0);
            $endDate = clone $now;
            $endDate->setTime(23, 59, 59);
            break;

        case 'yesterday':
            $yesterday = clone $now;
            $yesterday->modify('-1 day');
            $startDate = clone $yesterday;
            $startDate->setTime(0, 0, 0);
            $endDate = clone $yesterday;
            $endDate->setTime(23, 59, 59);
            break;

        case 'week':
            // Início da semana (segunda-feira)
            $dayOfWeek = (int)$now->format('N'); // 1 = segunda, 7 = domingo
            $startDate = clone $now;
            $startDate->modify('-' . ($dayOfWeek - 1) . ' days');
            $startDate->setTime(0, 0, 0);
            $endDate = clone $now;
            $endDate->setTime(23, 59, 59);
            break;

        case 'month':
            $startDate = clone $now;
            $startDate->setDate((int)$now->format('Y'), (int)$now->format('m'), 1);
            $startDate->setTime(0, 0, 0);
            $endDate = clone $now;
            $endDate->setTime(23, 59, 59);
            break;

        default:
            throw new Exception("Período inválido: $period");
    }

    // Formatar datas para SQL
    $startDateStr = $startDate->format('Y-m-d');
    $endDateStr = $endDate->format('Y-m-d');

    error_log("   📅 Período: $startDateStr até $endDateStr");

    // Construir query
    $query = "
        SELECT
            LicensePlate,
            SUM(km_final - km_inicial) as totalKm
        FROM Telemetria_Diaria
        WHERE data >= :startDate AND data <= :endDate
    ";

    $params = array(
        ':startDate' => $startDateStr,
        ':endDate' => $endDateStr
    );

    // Filtro de placa (opcional)
    if ($plate && $plate !== '') {
        $query .= " AND LicensePlate = :plate";
        $params[':plate'] = $plate;
    }

    $query .= " GROUP BY LicensePlate";

    // Preparar e executar
    $stmt = $pdo->prepare($query);
    if ($stmt === false) {
        throw new Exception("Erro ao preparar query");
    }

    $stmt->execute($params);

    $data = array();
    $totalKm = 0;

    while ($row = $stmt->fetch()) {
        $km = isset($row['totalKm']) ? (float)$row['totalKm'] : 0;
        $data[] = array(
            'LicensePlate' => $row['LicensePlate'],
            'totalKm' => $km
        );
        $totalKm += $km;
    }

    $pdo = null;

    error_log("✅ Total: " . round($totalKm) . " km de " . count($data) . " veículos");

    echo json_encode(array(
        'success' => true,
        'period' => $period,
        'startDate' => $startDate->format('c'),
        'endDate' => $endDate->format('c'),
        'data' => $data,
        'totalKm' => round($totalKm)
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("❌ Erro na API km-by-period: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
