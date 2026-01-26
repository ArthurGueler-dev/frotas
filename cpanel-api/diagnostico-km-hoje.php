<?php
/**
 * Diagnóstico de Quilometragem de Hoje
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

    // 1. Total de registros hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM daily_mileage WHERE date = ?");
    $stmt->execute([$hoje]);
    $totalRegistros = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Soma total de KM hoje
    $stmt = $pdo->prepare("SELECT SUM(km_driven) as total_km FROM daily_mileage WHERE date = ?");
    $stmt->execute([$hoje]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalKm = isset($row['total_km']) ? $row['total_km'] : 0;

    // 3. Top 20 veículos com mais KM hoje
    $stmt = $pdo->prepare("
        SELECT vehicle_plate, km_driven, odometer_start, odometer_end, synced_at
        FROM daily_mileage
        WHERE date = ?
        ORDER BY km_driven DESC
        LIMIT 20
    ");
    $stmt->execute([$hoje]);
    $topVeiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Veículos com KM suspeito (>500km em um dia)
    $stmt = $pdo->prepare("
        SELECT vehicle_plate, km_driven, odometer_start, odometer_end
        FROM daily_mileage
        WHERE date = ? AND km_driven > 500
        ORDER BY km_driven DESC
    ");
    $stmt->execute([$hoje]);
    $kmSuspeito = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Verificar registros duplicados (mesma placa, mesma data)
    $stmt = $pdo->prepare("
        SELECT vehicle_plate, COUNT(*) as qtd
        FROM daily_mileage
        WHERE date = ?
        GROUP BY vehicle_plate
        HAVING COUNT(*) > 1
    ");
    $stmt->execute([$hoje]);
    $duplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Verificar total de veículos na frota
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Vehicles");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalVeiculos = isset($row['total']) ? $row['total'] : 0;

    echo json_encode([
        'data_analise' => $hoje,
        'hora_analise' => date('H:i:s'),
        'resumo' => [
            'total_registros_hoje' => $totalRegistros,
            'total_km_hoje' => round($totalKm, 2),
            'media_km_por_veiculo' => $totalRegistros > 0 ? round($totalKm / $totalRegistros, 2) : 0,
            'total_veiculos_frota' => $totalVeiculos
        ],
        'top_20_veiculos' => $topVeiculos,
        'veiculos_km_suspeito_maior_500' => $kmSuspeito,
        'registros_duplicados' => $duplicados,
        'problemas_detectados' => [
            'tem_duplicados' => count($duplicados) > 0,
            'tem_km_suspeito' => count($kmSuspeito) > 0
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
