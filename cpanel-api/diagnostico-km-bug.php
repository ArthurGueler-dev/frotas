<?php
/**
 * Diagnóstico do BUG de KM inflado
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

    $hoje = date('Y-m-d');
    $ontem = date('Y-m-d', strtotime('-1 day'));

    // 1. KM total por dia nos últimos 7 dias
    $stmt = $pdo->query("
        SELECT
            date,
            COUNT(*) as qtd_veiculos,
            SUM(km_driven) as total_km,
            ROUND(AVG(km_driven), 2) as media_km
        FROM daily_mileage
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY date
        ORDER BY date DESC
    ");
    $kmPorDia = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Verificar horário da última sincronização
    $stmt = $pdo->prepare("
        SELECT
            MIN(synced_at) as primeira_sync,
            MAX(synced_at) as ultima_sync
        FROM daily_mileage
        WHERE date = ?
    ");
    $stmt->execute([$hoje]);
    $syncInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Distribuição de KM hoje (quantos veículos em cada faixa)
    $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN km_driven = 0 THEN '0 km (parado)'
                WHEN km_driven <= 20 THEN '1-20 km'
                WHEN km_driven <= 50 THEN '21-50 km'
                WHEN km_driven <= 100 THEN '51-100 km'
                WHEN km_driven <= 200 THEN '101-200 km'
                WHEN km_driven <= 300 THEN '201-300 km'
                ELSE 'Mais de 300 km'
            END as faixa,
            COUNT(*) as qtd_veiculos,
            SUM(km_driven) as total_km_faixa
        FROM daily_mileage
        WHERE date = ?
        GROUP BY faixa
        ORDER BY MIN(km_driven)
    ");
    $stmt->execute([$hoje]);
    $distribuicao = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Veículos que mais contribuem pro total (top 10)
    $stmt = $pdo->prepare("
        SELECT
            vehicle_plate,
            km_driven,
            ROUND((km_driven / (SELECT SUM(km_driven) FROM daily_mileage WHERE date = ?)) * 100, 2) as percentual_do_total
        FROM daily_mileage
        WHERE date = ?
        ORDER BY km_driven DESC
        LIMIT 10
    ");
    $stmt->execute([$hoje, $hoje]);
    $topContribuintes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Comparar média de hoje com média histórica
    $stmt = $pdo->query("
        SELECT ROUND(AVG(total_diario), 2) as media_historica
        FROM (
            SELECT date, SUM(km_driven) as total_diario
            FROM daily_mileage
            WHERE date < CURDATE() AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY date
        ) as historico
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $mediaHistorica = isset($row['media_historica']) ? $row['media_historica'] : 0;

    // 6. Total de hoje
    $stmt = $pdo->prepare("SELECT SUM(km_driven) as total FROM daily_mileage WHERE date = ?");
    $stmt->execute([$hoje]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalHoje = isset($row['total']) ? $row['total'] : 0;

    echo json_encode([
        'data_analise' => $hoje,
        'hora_servidor' => date('H:i:s'),
        'resumo_problema' => [
            'total_km_hoje' => round($totalHoje, 2),
            'media_historica_30_dias' => $mediaHistorica,
            'diferenca' => round($totalHoje - $mediaHistorica, 2),
            'percentual_acima' => $mediaHistorica > 0 ? round((($totalHoje / $mediaHistorica) - 1) * 100, 2) . '%' : 'N/A'
        ],
        'sync_info_hoje' => $syncInfo,
        'km_por_dia_ultimos_7_dias' => $kmPorDia,
        'distribuicao_km_hoje' => $distribuicao,
        'top_10_contribuintes' => $topContribuintes
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
