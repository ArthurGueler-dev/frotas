<?php
/**
 * Reprocessar Quilometragem de Dias Específicos
 *
 * Busca novamente os dados da API Ituran e recalcula KM
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

// API Ituran
$ITURAN_BASE_URL = 'https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx';
$ITURAN_USERNAME = 'api@i9tecnologia';
$ITURAN_PASSWORD = 'Api@In9Eng';

function getVehicleOdometer($plate, $date) {
    global $ITURAN_BASE_URL, $ITURAN_USERNAME, $ITURAN_PASSWORD;

    $url = $ITURAN_BASE_URL . '/GetVehicleMileage_JSON';
    $params = array(
        'Plate' => $plate,
        'LocTime' => $date,
        'UserName' => $ITURAN_USERNAME,
        'Password' => $ITURAN_PASSWORD
    );

    $url_with_params = $url . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_with_params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return null;
    }

    // Parse XML
    $xml = simplexml_load_string($response);
    if (!$xml) {
        return null;
    }

    $json_data = (string)$xml;
    $data = json_decode($json_data, true);

    if (!$data || $data['ResultCode'] != 'OK') {
        return null;
    }

    $odometer = floatval($data['resMileage']);

    // Filtrar odômetros absurdos (>1.000.000 km = 1 milhão)
    if ($odometer > 1000000) {
        return null; // Ignorar dados claramente errados
    }

    return $odometer;
}

$date = isset($_GET['date']) ? $_GET['date'] : '2026-01-12';
$execute = isset($_GET['execute']) ? $_GET['execute'] : false;

echo "<h1>♻️ Reprocessar Quilometragem - $date</h1>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";

if (!$execute) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; border: 1px solid #ffc107; margin: 20px 0;'>";
    echo "<h2>⚠️ Atenção</h2>";
    echo "<p>Este script vai:</p>";
    echo "<ul>";
    echo "<li>Buscar NOVAMENTE os odômetros da API Ituran para o dia <strong>$date</strong></li>";
    echo "<li>Recalcular a quilometragem rodada</li>";
    echo "<li>Atualizar os registros no banco de dados</li>";
    echo "<li>Filtrar odômetros absurdos (>1.000.000 km)</li>";
    echo "</ul>";
    echo "<p><strong>Isso pode levar alguns minutos.</strong></p>";
    echo "<p><a href='?date=$date&execute=1' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>▶️ Executar Reprocessamento</a></p>";
    echo "</div>";
    exit;
}

// Buscar veículos
$stmt = $pdo->query("SELECT LicensePlate FROM Vehicles WHERE LicensePlate IS NOT NULL AND LicensePlate != ''");
$vehicles = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>🔄 Reprocessando " . count($vehicles) . " veículos...</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Placa</th>";
echo "<th>Odômetro Início (dia anterior)</th>";
echo "<th>Odômetro Fim ($date)</th>";
echo "<th>KM Rodados</th>";
echo "<th>Status</th>";
echo "</tr>";

$stats = array(
    'success' => 0,
    'failed' => 0,
    'filtered' => 0,
    'total_km' => 0
);

foreach ($vehicles as $plate) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($plate) . "</strong></td>";

    // Data anterior
    $previousDate = date('Y-m-d', strtotime($date . ' -1 day'));

    // Buscar odômetros
    $odometerStart = getVehicleOdometer($plate, $previousDate);
    $odometerEnd = getVehicleOdometer($plate, $date);

    if ($odometerStart === null || $odometerEnd === null) {
        echo "<td colspan='3' style='color: #dc3545;'>Erro ao buscar dados da API</td>";
        echo "<td>❌ Falhou</td>";
        $stats['failed']++;
    } else {
        $kmDriven = $odometerEnd - $odometerStart;

        // Validar
        if ($kmDriven < 0) {
            $kmDriven = 0;
        }

        echo "<td>" . number_format($odometerStart, 2, ',', '.') . " km</td>";
        echo "<td>" . number_format($odometerEnd, 2, ',', '.') . " km</td>";
        echo "<td><strong>" . round($kmDriven, 2) . " km</strong></td>";

        // Salvar no banco
        try {
            $stmt = $pdo->prepare("
                INSERT INTO daily_mileage (
                    vehicle_plate, date, odometer_start, odometer_end,
                    km_driven, source, sync_status, synced_at
                ) VALUES (?, ?, ?, ?, ?, 'MANUAL_REPROCESS', 'success', NOW())
                ON DUPLICATE KEY UPDATE
                    odometer_start = VALUES(odometer_start),
                    odometer_end = VALUES(odometer_end),
                    km_driven = VALUES(km_driven),
                    source = VALUES(source),
                    sync_status = VALUES(sync_status),
                    synced_at = VALUES(synced_at)
            ");

            $stmt->execute([$plate, $date, $odometerStart, $odometerEnd, $kmDriven]);

            echo "<td style='color: #28a745;'>✅ Atualizado</td>";
            $stats['success']++;
            $stats['total_km'] += $kmDriven;
        } catch (PDOException $e) {
            echo "<td style='color: #dc3545;'>❌ Erro BD</td>";
            $stats['failed']++;
        }
    }

    echo "</tr>";

    // Dar um tempo para não sobrecarregar a API
    usleep(200000); // 200ms
}

echo "</table>";

echo "<hr>";
echo "<h2>📊 Resumo</h2>";
echo "<ul>";
echo "<li><strong>Total de veículos:</strong> " . count($vehicles) . "</li>";
echo "<li><strong>Sucesso:</strong> " . $stats['success'] . "</li>";
echo "<li><strong>Falhas:</strong> " . $stats['failed'] . "</li>";
echo "<li><strong>Total KM (novo):</strong> " . round($stats['total_km'], 2) . " km</li>";
echo "</ul>";

echo "<p><a href='diagnostico-km.php'>← Voltar para Diagnóstico</a></p>";

?>
