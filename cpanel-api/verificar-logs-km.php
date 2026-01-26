<?php
/**
 * Verificar Logs de Sincronização de KM
 *
 * Identifica problemas na busca de dados da API Ituran
 */

header('Content-Type: text/html; charset=utf-8');

// Conexão
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$date = isset($_GET['date']) ? $_GET['date'] : '2026-01-12';

echo "<h1>🔍 Verificação de Logs - $date</h1>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";

// Verificar dia da semana
$dayOfWeek = date('l', strtotime($date));
$dayOfWeekPt = array(
    'Monday' => 'Segunda-feira',
    'Tuesday' => 'Terça-feira',
    'Wednesday' => 'Quarta-feira',
    'Thursday' => 'Quinta-feira',
    'Friday' => 'Sexta-feira',
    'Saturday' => 'Sábado',
    'Sunday' => 'Domingo'
);

echo "<h2>📅 Dia da Semana: " . $dayOfWeekPt[$dayOfWeek] . "</h2>";

if ($dayOfWeek == 'Sunday') {
    echo "<p style='background: #ffffcc; padding: 10px; border-radius: 5px;'>";
    echo "⚠️ <strong>DOMINGO</strong> - É normal ter menos KM rodados pois muitos veículos ficam parados.";
    echo "</p>";
}

// Buscar veículos com 0 km
$stmt = $pdo->prepare("
    SELECT
        vehicle_plate,
        odometer_start,
        odometer_end,
        km_driven,
        synced_at
    FROM daily_mileage
    WHERE date = ? AND km_driven = 0
    ORDER BY vehicle_plate
");
$stmt->execute([$date]);
$zeroKm = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>🚫 Veículos com 0 KM ($date)</h2>";
echo "<p><strong>Total: " . count($zeroKm) . " veículos</strong></p>";

if (count($zeroKm) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Placa</th>";
    echo "<th>Odômetro Início</th>";
    echo "<th>Odômetro Fim</th>";
    echo "<th>Sincronizado em</th>";
    echo "</tr>";

    foreach ($zeroKm as $row) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['vehicle_plate']) . "</strong></td>";
        echo "<td>" . number_format($row['odometer_start'], 2, ',', '.') . " km</td>";
        echo "<td>" . number_format($row['odometer_end'], 2, ',', '.') . " km</td>";
        echo "<td>" . $row['synced_at'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

// Buscar veículos que rodaram
$stmt = $pdo->prepare("
    SELECT
        vehicle_plate,
        odometer_start,
        odometer_end,
        km_driven,
        synced_at
    FROM daily_mileage
    WHERE date = ? AND km_driven > 0
    ORDER BY km_driven DESC
    LIMIT 10
");
$stmt->execute([$date]);
$withKm = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>✅ Top 10 Veículos que Rodaram ($date)</h2>";

if (count($withKm) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Placa</th>";
    echo "<th>KM Rodados</th>";
    echo "<th>Odômetro Início</th>";
    echo "<th>Odômetro Fim</th>";
    echo "<th>Sincronizado em</th>";
    echo "</tr>";

    foreach ($withKm as $row) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['vehicle_plate']) . "</strong></td>";
        echo "<td><strong>" . round($row['km_driven'], 2) . " km</strong></td>";
        echo "<td>" . number_format($row['odometer_start'], 2, ',', '.') . " km</td>";
        echo "<td>" . number_format($row['odometer_end'], 2, ',', '.') . " km</td>";
        echo "<td>" . $row['synced_at'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

// Comparar com dia anterior
$previousDate = date('Y-m-d', strtotime($date . ' -1 day'));

echo "<h2>📊 Comparação com Dia Anterior ($previousDate)</h2>";

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_vehicles,
        SUM(km_driven) as total_km,
        COUNT(CASE WHEN km_driven = 0 THEN 1 END) as zero_km_count,
        COUNT(CASE WHEN km_driven > 0 THEN 1 END) as with_km_count
    FROM daily_mileage
    WHERE date = ?
");

$stmt->execute([$date]);
$statsToday = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt->execute([$previousDate]);
$statsYesterday = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Métrica</th>";
echo "<th>$previousDate</th>";
echo "<th>$date</th>";
echo "<th>Diferença</th>";
echo "</tr>";

$totalVehiclesToday = isset($statsToday['total_vehicles']) ? $statsToday['total_vehicles'] : 0;
$totalVehiclesYesterday = isset($statsYesterday['total_vehicles']) ? $statsYesterday['total_vehicles'] : 0;

$totalKmToday = isset($statsToday['total_km']) ? round($statsToday['total_km'], 2) : 0;
$totalKmYesterday = isset($statsYesterday['total_km']) ? round($statsYesterday['total_km'], 2) : 0;

$zeroKmToday = isset($statsToday['zero_km_count']) ? $statsToday['zero_km_count'] : 0;
$zeroKmYesterday = isset($statsYesterday['zero_km_count']) ? $statsYesterday['zero_km_count'] : 0;

$withKmToday = isset($statsToday['with_km_count']) ? $statsToday['with_km_count'] : 0;
$withKmYesterday = isset($statsYesterday['with_km_count']) ? $statsYesterday['with_km_count'] : 0;

echo "<tr>";
echo "<td><strong>Total Veículos</strong></td>";
echo "<td>$totalVehiclesYesterday</td>";
echo "<td>$totalVehiclesToday</td>";
echo "<td>" . ($totalVehiclesToday - $totalVehiclesYesterday) . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Total KM</strong></td>";
echo "<td>$totalKmYesterday km</td>";
echo "<td>$totalKmToday km</td>";
echo "<td>" . round($totalKmToday - $totalKmYesterday, 2) . " km</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Veículos com 0 KM</strong></td>";
echo "<td>$zeroKmYesterday</td>";
echo "<td>$zeroKmToday</td>";
echo "<td>" . ($zeroKmToday - $zeroKmYesterday) . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>Veículos que rodaram</strong></td>";
echo "<td>$withKmYesterday</td>";
echo "<td>$withKmToday</td>";
echo "<td>" . ($withKmToday - $withKmYesterday) . "</td>";
echo "</tr>";

echo "</table>";

// Conclusão
echo "<hr>";
echo "<h2>💡 Conclusão</h2>";

$percentZero = $totalVehiclesToday > 0 ? round(($zeroKmToday / $totalVehiclesToday) * 100, 1) : 0;

if ($dayOfWeek == 'Sunday' || $dayOfWeek == 'Saturday') {
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "✅ <strong>NORMAL</strong> - É final de semana ($dayOfWeekPt[$dayOfWeek]). ";
    echo "É esperado que " . $percentZero . "% dos veículos fiquem parados.";
    echo "</p>";
} elseif ($percentZero > 50) {
    echo "<p style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "⚠️ <strong>ATENÇÃO</strong> - " . $percentZero . "% dos veículos com 0 KM em dia de semana. ";
    echo "Pode indicar problema na sincronização com a API Ituran.";
    echo "</p>";
} else {
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "✅ <strong>NORMAL</strong> - " . $percentZero . "% dos veículos com 0 KM está dentro do esperado.";
    echo "</p>";
}

echo "<p><a href='?date=" . date('Y-m-d', strtotime($date . ' -1 day')) . "'>← Dia Anterior</a> | ";
echo "<a href='?date=" . date('Y-m-d', strtotime($date . ' +1 day')) . "'>Próximo Dia →</a></p>";

?>
