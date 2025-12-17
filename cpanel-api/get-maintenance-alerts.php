<?php
/**
 * API para buscar alertas de manutenção preventiva
 * Retorna veículos que precisam de manutenção baseado na quilometragem
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Cache-Control');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Definir timezone para Brasil
date_default_timezone_set('America/Sao_Paulo');

require_once 'config-db.php';

try {
    // Conectar ao banco usando PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 3
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Buscar veículos com suas quilometragens atuais e histórico de manutenções
    // Verificar se a tabela telemetria existe
    $tableExists = false;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'telemetria'");
        $tableExists = $checkTable->rowCount() > 0;
    } catch (Exception $e) {
        $tableExists = false;
    }

    if ($tableExists) {
        $sql = "SELECT
                    v.Id as id,
                    v.LicensePlate as placa,
                    v.VehicleName as nome,
                    v.VehicleYear as ano,
                    COALESCE(t.km_total, 0) as km_atual,
                    COALESCE(
                        (SELECT MAX(km_veiculo)
                         FROM ordemservico
                         WHERE placa_veiculo = v.LicensePlate
                         AND ocorrencia = 'Preventiva'
                         AND status = 'Finalizada'),
                        0
                    ) as km_ultima_preventiva
                FROM Vehicles v
                LEFT JOIN (
                    SELECT placa, MAX(km_total) as km_total
                    FROM telemetria
                    GROUP BY placa
                ) t ON v.LicensePlate = t.placa
                WHERE v.LicensePlate IS NOT NULL
                ORDER BY v.LicensePlate";
    } else {
        // Se não existe telemetria, usar apenas km das OS
        $sql = "SELECT
                    v.Id as id,
                    v.LicensePlate as placa,
                    v.VehicleName as nome,
                    v.VehicleYear as ano,
                    COALESCE(
                        (SELECT MAX(km_veiculo)
                         FROM ordemservico
                         WHERE placa_veiculo = v.LicensePlate),
                        0
                    ) as km_atual,
                    COALESCE(
                        (SELECT MAX(km_veiculo)
                         FROM ordemservico
                         WHERE placa_veiculo = v.LicensePlate
                         AND ocorrencia = 'Preventiva'
                         AND status = 'Finalizada'),
                        0
                    ) as km_ultima_preventiva
                FROM Vehicles v
                WHERE v.LicensePlate IS NOT NULL
                ORDER BY v.LicensePlate";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $vehicles = $stmt->fetchAll();

    $alerts = [];

    foreach ($vehicles as $vehicle) {
        $kmAtual = (int)$vehicle['km_atual'];
        $kmUltimaPreventiva = (int)$vehicle['km_ultima_preventiva'];
        $kmDesdeUltimaPreventiva = $kmAtual - $kmUltimaPreventiva;

        // Alertar se passou de 10.000 km desde a última manutenção preventiva
        // ou se nunca fez manutenção preventiva e já tem mais de 10.000 km
        if ($kmDesdeUltimaPreventiva >= 10000) {
            $nivelAlerta = 'danger';
            $mensagem = 'Manutenção preventiva URGENTE';

            if ($kmDesdeUltimaPreventiva >= 15000) {
                $nivelAlerta = 'critical';
                $mensagem = 'Manutenção preventiva CRÍTICA - Já passou ' . number_format($kmDesdeUltimaPreventiva, 0, ',', '.') . ' km';
            }

            $alerts[] = [
                'id' => $vehicle['id'],
                'placa' => $vehicle['placa'],
                'veiculo' => $vehicle['nome'],
                'ano' => $vehicle['ano'],
                'km_atual' => $kmAtual,
                'km_ultima_preventiva' => $kmUltimaPreventiva,
                'km_desde_ultima' => $kmDesdeUltimaPreventiva,
                'tipo' => 'preventiva',
                'nivel' => $nivelAlerta,
                'mensagem' => $mensagem,
                'recomendacao' => 'Agende uma revisão preventiva o mais rápido possível'
            ];
        } else if ($kmDesdeUltimaPreventiva >= 8000) {
            // Alerta de aviso quando se aproxima dos 10.000 km
            $alerts[] = [
                'id' => $vehicle['id'],
                'placa' => $vehicle['placa'],
                'veiculo' => $vehicle['nome'],
                'ano' => $vehicle['ano'],
                'km_atual' => $kmAtual,
                'km_ultima_preventiva' => $kmUltimaPreventiva,
                'km_desde_ultima' => $kmDesdeUltimaPreventiva,
                'tipo' => 'preventiva',
                'nivel' => 'warning',
                'mensagem' => 'Manutenção preventiva se aproximando',
                'recomendacao' => 'Faltam ' . number_format(10000 - $kmDesdeUltimaPreventiva, 0, ',', '.') . ' km para a próxima manutenção'
            ];
        }
    }

    // Ordenar por nível de criticidade (critical > danger > warning)
    usort($alerts, function($a, $b) {
        $ordem = ['critical' => 0, 'danger' => 1, 'warning' => 2];
        return $ordem[$a['nivel']] - $ordem[$b['nivel']];
    });

    echo json_encode($alerts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Erro ao buscar alertas de manutenção: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
