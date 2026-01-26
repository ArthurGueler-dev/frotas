<?php
/**
 * Cron Job - Auto Recovery de Quilometragem
 *
 * Este script deve rodar via cron a cada hora (ex: 0 * * * *)
 * Detecta automaticamente dias com problemas e reprocessa
 *
 * Configuração do cron:
 * 0 * * * * php /caminho/para/cron-auto-recovery.php >> /var/log/auto-recovery.log 2>&1
 */

// CLI only
if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado via CLI (cron)");
}

// Logging
function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

logMessage("========================================");
logMessage("Auto Recovery - Iniciando verificação");
logMessage("========================================");

// Conexão
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("✅ Conectado ao banco de dados");
} catch (PDOException $e) {
    logMessage("❌ Erro de conexão: " . $e->getMessage());
    exit(1);
}

// Funções auxiliares
function getVehicleOdometer($plate, $date) {
    $ITURAN_BASE_URL = 'https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx';
    $ITURAN_USERNAME = 'api@i9tecnologia';
    $ITURAN_PASSWORD = 'Api@In9Eng';

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

    // Filtrar odômetros absurdos
    if ($odometer > 1000000) {
        return null;
    }

    return $odometer;
}

function reprocessDate($pdo, $date) {
    logMessage("🔄 Reprocessando $date...");

    // Buscar veículos
    $stmt = $pdo->query("SELECT LicensePlate FROM Vehicles WHERE LicensePlate IS NOT NULL AND LicensePlate != ''");
    $vehicles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    logMessage("   Encontrados " . count($vehicles) . " veículos");

    $success = 0;
    $failed = 0;

    foreach ($vehicles as $plate) {
        $previousDate = date('Y-m-d', strtotime($date . ' -1 day'));

        $odometerStart = getVehicleOdometer($plate, $previousDate);
        $odometerEnd = getVehicleOdometer($plate, $date);

        if ($odometerStart === null || $odometerEnd === null) {
            $failed++;
            continue;
        }

        $kmDriven = $odometerEnd - $odometerStart;
        if ($kmDriven < 0) {
            $kmDriven = 0;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO daily_mileage (
                    vehicle_plate, date, odometer_start, odometer_end,
                    km_driven, source, sync_status, synced_at
                ) VALUES (?, ?, ?, ?, ?, 'AUTO_RECOVERY', 'success', NOW())
                ON DUPLICATE KEY UPDATE
                    odometer_start = VALUES(odometer_start),
                    odometer_end = VALUES(odometer_end),
                    km_driven = VALUES(km_driven),
                    source = VALUES(source),
                    sync_status = VALUES(sync_status),
                    synced_at = VALUES(synced_at)
            ");

            $stmt->execute(array($plate, $date, $odometerStart, $odometerEnd, $kmDriven));
            $success++;
        } catch (PDOException $e) {
            $failed++;
        }

        usleep(200000); // 200ms entre requisições
    }

    logMessage("   ✅ Sucesso: $success | ❌ Falhas: $failed");

    return array('success' => $success, 'failed' => $failed);
}

// Verificar últimos 3 dias (excluindo hoje)
$dates_to_check = array();
for ($i = 1; $i <= 3; $i++) {
    $dates_to_check[] = date('Y-m-d', strtotime("-$i day"));
}

logMessage("Verificando últimos 3 dias: " . implode(', ', $dates_to_check));

$recovery_count = 0;

foreach ($dates_to_check as $date) {
    // Verificar saúde do dia
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_vehicles,
            SUM(CASE WHEN km_driven = 0 THEN 1 ELSE 0 END) as zero_km_count,
            MAX(synced_at) as last_sync
        FROM daily_mileage
        WHERE date = ?
    ");
    $stmt->execute(array($date));
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $total = isset($stats['total_vehicles']) ? intval($stats['total_vehicles']) : 0;
    $zeroKm = isset($stats['zero_km_count']) ? intval($stats['zero_km_count']) : 0;

    if ($total == 0) {
        logMessage("⚠️ $date - Nenhum registro encontrado, pulando...");
        continue;
    }

    $zeroPercent = ($zeroKm / $total) * 100;

    // Verificar se é fim de semana
    $dayOfWeek = date('w', strtotime($date));
    $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);

    $maxZeroPercent = $isWeekend ? 70 : 50;

    logMessage("📊 $date - Total: $total | 0km: $zeroKm (" . round($zeroPercent, 1) . "%) | Limite: $maxZeroPercent%");

    if ($zeroPercent > $maxZeroPercent) {
        logMessage("⚠️ $date - PROBLEMA DETECTADO! Iniciando reprocessamento...");

        // Verificar se já reprocessamos hoje
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM daily_mileage
            WHERE date = ?
            AND source = 'AUTO_RECOVERY'
            AND DATE(synced_at) = CURDATE()
        ");
        $stmt->execute(array($date));
        $already_recovered = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($already_recovered) {
            logMessage("   ℹ️ Já reprocessado hoje, pulando...");
            continue;
        }

        $result = reprocessDate($pdo, $date);
        $recovery_count++;

        logMessage("   ✅ Reprocessamento concluído!");
    } else {
        logMessage("   ✅ Status saudável, nenhuma ação necessária");
    }
}

logMessage("========================================");
logMessage("Auto Recovery - Concluído");
logMessage("Total de dias reprocessados: $recovery_count");
logMessage("========================================");

exit(0);

?>
