<?php
/**
 * API Dashboard Diario - Veiculos sem Checklist
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Erro de conexao'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'error' => 'Metodo nao permitido'));
    exit;
}

$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'error' => 'Formato de data invalido'));
    exit;
}

try {
    $query = "
        SELECT
            v.LicensePlate as placa,
            v.VehicleName as modelo,
            a.name as regiao,
            COALESCE(dm.km_driven, 0) as km_driven,
            dm.odometer_start,
            dm.odometer_end,
            iv.id as checklist_id,
            iv.status_geral as checklist_status,
            DATE_FORMAT(iv.data_realizacao, '%H:%i') as checklist_hora,
            u.nome as usuario_checklist,
            CASE
                WHEN COALESCE(dm.km_driven, 0) <= 0 THEN 'PARADO'
                WHEN iv.id IS NOT NULL THEN 'CONFORME'
                ELSE 'NAO_CONFORME'
            END as status
        FROM Vehicles v
        LEFT JOIN areas a ON v.area_id = a.id
        LEFT JOIN daily_mileage dm ON v.LicensePlate = dm.vehicle_plate AND dm.date = :data1
        LEFT JOIN bbb_inspecao_veiculo iv ON v.LicensePlate = iv.placa AND DATE(iv.data_realizacao) = :data2
        LEFT JOIN bbb_usuario u ON iv.usuario_id = u.id
        ORDER BY
            CASE
                WHEN COALESCE(dm.km_driven, 0) > 0 AND iv.id IS NULL THEN 0
                WHEN COALESCE(dm.km_driven, 0) > 0 AND iv.id IS NOT NULL THEN 1
                ELSE 2
            END,
            v.LicensePlate ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array('data1' => $date, 'data2' => $date));
    $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalVeiculos = count($veiculos);
    $veiculosAndaram = 0;
    $veiculosParados = 0;
    $veiculosConformes = 0;
    $veiculosNaoConformes = 0;
    $naoConformes = array();

    foreach ($veiculos as $v) {
        $kmDriven = floatval($v['km_driven']);
        if ($kmDriven > 0) {
            $veiculosAndaram++;
            if ($v['checklist_id']) {
                $veiculosConformes++;
            } else {
                $veiculosNaoConformes++;
                $naoConformes[] = $v;
            }
        } else {
            $veiculosParados++;
        }
    }

    $percentualConformidade = $veiculosAndaram > 0
        ? round(($veiculosConformes / $veiculosAndaram) * 100, 2)
        : 100;

    $timestamp = strtotime($date);
    $diasSemana = array('Domingo', 'Segunda-feira', 'Terca-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sabado');
    $meses = array('Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
    $dataFormatada = $diasSemana[date('w', $timestamp)] . ', ' . date('d', $timestamp) . ' de ' . $meses[date('n', $timestamp) - 1] . ' de ' . date('Y', $timestamp);

    $response = array(
        'success' => true,
        'date' => $date,
        'date_formatted' => $dataFormatada,
        'statistics' => array(
            'total_veiculos' => $totalVeiculos,
            'veiculos_andaram' => $veiculosAndaram,
            'veiculos_parados' => $veiculosParados,
            'veiculos_conformes' => $veiculosConformes,
            'veiculos_nao_conformes' => $veiculosNaoConformes,
            'percentual_conformidade' => $percentualConformidade
        ),
        'veiculos' => $veiculos,
        'nao_conformes' => $naoConformes
    );

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Erro ao consultar dados', 'message' => $e->getMessage()));
}
