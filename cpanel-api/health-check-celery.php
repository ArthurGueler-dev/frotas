<?php
/**
 * Health Check do Celery Beat
 *
 * Verifica se o Celery Beat está rodando e se os syncs estão funcionando
 * Pode ser chamado por sistemas de monitoramento externos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Conexão
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'healthy' => false,
        'error' => 'Database connection failed',
        'timestamp' => date('c')
    ));
    exit;
}

// Verificar últimos 7 dias
$stmt = $pdo->query("
    SELECT
        date as sync_date,
        COUNT(*) as total_vehicles,
        SUM(CASE WHEN km_driven = 0 THEN 1 ELSE 0 END) as zero_km_count,
        SUM(km_driven) as total_km,
        MAX(synced_at) as last_sync
    FROM daily_mileage
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY date
    ORDER BY date DESC
    LIMIT 7
");

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$issues = array();
$healthy_days = 0;
$unhealthy_days = 0;
$last_sync_time = null;

foreach ($records as $row) {
    $date_str = $row['sync_date'];
    $total = isset($row['total_vehicles']) ? intval($row['total_vehicles']) : 0;
    $zeroKm = isset($row['zero_km_count']) ? intval($row['zero_km_count']) : 0;
    $zeroPercent = $total > 0 ? ($zeroKm / $total * 100) : 0;

    // Pegar horário da última sync
    if (!$last_sync_time && isset($row['last_sync'])) {
        $last_sync_time = $row['last_sync'];
    }

    // Verificar se é fim de semana
    $timestamp = strtotime($date_str);
    $dayOfWeek = date('w', $timestamp);
    $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);

    $maxZeroPercent = $isWeekend ? 70 : 50;

    if ($zeroPercent > $maxZeroPercent) {
        $issues[] = array(
            'date' => $date_str,
            'issue' => 'high_zero_km_percentage',
            'zero_percent' => round($zeroPercent, 2),
            'threshold' => $maxZeroPercent,
            'is_weekend' => $isWeekend
        );
        $unhealthy_days++;
    } else {
        $healthy_days++;
    }
}

// Verificar se última sync foi há muito tempo
$hours_since_last = null;
$sync_delayed = false;

if ($last_sync_time) {
    $last_sync_timestamp = strtotime($last_sync_time);
    $now_timestamp = time();
    $diff_seconds = $now_timestamp - $last_sync_timestamp;
    $hours_since_last = $diff_seconds / 3600;

    // Se última sync foi há mais de 7 horas, considerar atrasado
    if ($hours_since_last > 7) {
        $sync_delayed = true;
        $issues[] = array(
            'issue' => 'sync_delayed',
            'hours_since_last' => round($hours_since_last, 2),
            'last_sync_at' => $last_sync_time,
            'threshold_hours' => 7
        );
    }
}

// Determinar status geral
$overall_healthy = (count($issues) == 0);

$response = array(
    'healthy' => $overall_healthy,
    'status' => $overall_healthy ? 'OK' : 'DEGRADED',
    'timestamp' => date('c'),
    'statistics' => array(
        'days_checked' => count($records),
        'healthy_days' => $healthy_days,
        'unhealthy_days' => $unhealthy_days,
        'last_sync_at' => $last_sync_time,
        'hours_since_last_sync' => $hours_since_last ? round($hours_since_last, 2) : null
    ),
    'issues' => $issues
);

// Status HTTP correto
if (!$overall_healthy) {
    http_response_code(503); // Service Unavailable
}

echo json_encode($response, JSON_PRETTY_PRINT);

?>
