<?php
/**
 * Script de Diagnóstico de Quilometragem
 *
 * Verifica os dados de quilometragem para identificar problemas
 */

header('Content-Type: text/html; charset=utf-8');

// Conexão com banco
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

echo "<h1>Diagnóstico de Quilometragem</h1>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Obter datas dos últimos 7 dias
$dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = new DateTime();
    $date->modify("-$i days");
    $dates[] = $date->format('Y-m-d');
}

echo "<h2>📊 Últimos 7 dias</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Data</th>";
echo "<th>Total Veículos</th>";
echo "<th>Total KM</th>";
echo "<th>Média KM/Veículo</th>";
echo "<th>Máx KM (Veículo)</th>";
echo "<th>Mín KM</th>";
echo "</tr>";

foreach ($dates as $date) {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_vehicles,
            SUM(km_driven) as total_km,
            AVG(km_driven) as avg_km,
            MAX(km_driven) as max_km,
            MIN(km_driven) as min_km
        FROM daily_mileage
        WHERE date = ?
    ");
    $stmt->execute([$date]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Buscar veículo com maior KM
    $stmt = $pdo->prepare("
        SELECT vehicle_plate, km_driven, odometer_start, odometer_end
        FROM daily_mileage
        WHERE date = ?
        ORDER BY km_driven DESC
        LIMIT 1
    ");
    $stmt->execute([$date]);
    $maxVehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalKm = round(isset($stats['total_km']) ? $stats['total_km'] : 0, 2);
    $avgKm = round(isset($stats['avg_km']) ? $stats['avg_km'] : 0, 2);
    $maxKm = round(isset($stats['max_km']) ? $stats['max_km'] : 0, 2);
    $minKm = round(isset($stats['min_km']) ? $stats['min_km'] : 0, 2);

    // Destacar valores anormais
    $style = '';
    if ($totalKm > 5000) {
        $style = "background: #ffcccc;"; // Vermelho claro
    }

    echo "<tr style='$style'>";
    echo "<td><strong>$date</strong></td>";
    echo "<td>" . (isset($stats['total_vehicles']) ? $stats['total_vehicles'] : 0) . "</td>";
    echo "<td><strong>$totalKm km</strong></td>";
    echo "<td>$avgKm km</td>";
    echo "<td>$maxKm km<br><small>" . (isset($maxVehicle['vehicle_plate']) ? $maxVehicle['vehicle_plate'] : '-') . "</small></td>";
    echo "<td>$minKm km</td>";
    echo "</tr>";
}

echo "</table>";

// Detalhes do dia com problema (ontem)
$yesterday = date('Y-m-d', strtotime('-1 day'));

echo "<h2>🔍 Detalhes de $yesterday (Ontem)</h2>";

$stmt = $pdo->prepare("
    SELECT
        vehicle_plate,
        odometer_start,
        odometer_end,
        km_driven,
        sync_status
    FROM daily_mileage
    WHERE date = ?
    ORDER BY km_driven DESC
    LIMIT 20
");
$stmt->execute([$yesterday]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Placa</th>";
echo "<th>Odômetro Início</th>";
echo "<th>Odômetro Fim</th>";
echo "<th>KM Rodados</th>";
echo "<th>Status</th>";
echo "</tr>";

$totalKmCheck = 0;
foreach ($records as $record) {
    $km = round($record['km_driven'], 2);
    $totalKmCheck += $km;

    // Destacar valores anormais (>500km em um dia)
    $style = '';
    if ($km > 500) {
        $style = "background: #ffcccc;"; // Vermelho claro
    } elseif ($km > 200) {
        $style = "background: #ffffcc;"; // Amarelo claro
    }

    echo "<tr style='$style'>";
    echo "<td><strong>" . htmlspecialchars($record['vehicle_plate']) . "</strong></td>";
    echo "<td>" . number_format($record['odometer_start'], 2, ',', '.') . " km</td>";
    echo "<td>" . number_format($record['odometer_end'], 2, ',', '.') . " km</td>";
    echo "<td><strong>$km km</strong></td>";
    echo "<td>" . htmlspecialchars($record['sync_status']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p><strong>Total dos Top 20:</strong> " . round($totalKmCheck, 2) . " km</p>";

// Verificar total de registros para o dia
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM daily_mileage WHERE date = ?");
$stmt->execute([$yesterday]);
$totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<p><strong>Total de veículos no dia:</strong> $totalRecords</p>";

// Verificar registros duplicados
echo "<h2>🔍 Verificando Duplicados</h2>";
$stmt = $pdo->query("
    SELECT vehicle_plate, date, COUNT(*) as count
    FROM daily_mileage
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY vehicle_plate, date
    HAVING count > 1
");
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) > 0) {
    echo "<p style='color: red;'><strong>⚠️ DUPLICADOS ENCONTRADOS!</strong></p>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Placa</th>";
    echo "<th>Data</th>";
    echo "<th>Quantidade de Registros</th>";
    echo "</tr>";

    foreach ($duplicates as $dup) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($dup['vehicle_plate']) . "</td>";
        echo "<td>" . $dup['date'] . "</td>";
        echo "<td><strong>" . $dup['count'] . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'><strong>✅ Nenhum duplicado encontrado</strong></p>";
}

// Legenda
echo "<hr>";
echo "<h3>Legenda:</h3>";
echo "<ul>";
echo "<li><span style='background: #ffcccc; padding: 5px;'>Vermelho</span> - KM acima de 500 (anormal)</li>";
echo "<li><span style='background: #ffffcc; padding: 5px;'>Amarelo</span> - KM entre 200-500 (atenção)</li>";
echo "<li>Normal - KM abaixo de 200</li>";
echo "</ul>";

?>
