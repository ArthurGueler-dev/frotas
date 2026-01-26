<?php
/**
 * Diagnóstico Comparativo - Ontem vs Hoje
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

    // Pegar os veículos suspeitos (>300km hoje)
    $stmt = $pdo->prepare("
        SELECT vehicle_plate, km_driven, odometer_start, odometer_end, synced_at
        FROM daily_mileage
        WHERE date = ? AND km_driven > 300
        ORDER BY km_driven DESC
    ");
    $stmt->execute([$hoje]);
    $suspeitosHoje = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $comparativo = [];

    foreach ($suspeitosHoje as $veiculo) {
        $placa = $veiculo['vehicle_plate'];

        // Buscar dados de ontem para comparar
        $stmt = $pdo->prepare("
            SELECT km_driven, odometer_start, odometer_end, synced_at
            FROM daily_mileage
            WHERE vehicle_plate = ? AND date = ?
        ");
        $stmt->execute([$placa, $ontem]);
        $dadosOntem = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar últimos 5 dias desse veículo
        $stmt = $pdo->prepare("
            SELECT date, km_driven, odometer_start, odometer_end
            FROM daily_mileage
            WHERE vehicle_plate = ?
            ORDER BY date DESC
            LIMIT 7
        ");
        $stmt->execute([$placa]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $comparativo[] = [
            'placa' => $placa,
            'hoje' => [
                'km_driven' => $veiculo['km_driven'],
                'odometer_start' => $veiculo['odometer_start'],
                'odometer_end' => $veiculo['odometer_end'],
                'synced_at' => $veiculo['synced_at']
            ],
            'ontem' => $dadosOntem ? [
                'km_driven' => $dadosOntem['km_driven'],
                'odometer_start' => $dadosOntem['odometer_start'],
                'odometer_end' => $dadosOntem['odometer_end']
            ] : null,
            'problema_detectado' => $dadosOntem ?
                (floatval($veiculo['odometer_start']) != floatval($dadosOntem['odometer_end']) ?
                    'odometer_start DIFERENTE do odometer_end de ontem!' : 'OK')
                : 'Sem dados de ontem',
            'historico_7_dias' => $historico
        ];
    }

    echo json_encode([
        'analise' => 'Comparativo Ontem vs Hoje para veículos com >300km',
        'data_hoje' => $hoje,
        'data_ontem' => $ontem,
        'veiculos_analisados' => count($comparativo),
        'comparativo' => $comparativo
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
