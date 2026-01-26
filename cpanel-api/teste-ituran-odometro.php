<?php
/**
 * Teste direto da API Ituran para verificar odômetro
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configurações Ituran
$ituranUrl = 'https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx/GetVehicleMileage_JSON';
$username = 'api@i9tecnologia';
$password = 'Api@In9Eng';

// Placa para testar (a que tem 716km hoje)
$placa = isset($_GET['placa']) ? $_GET['placa'] : 'RBA2F98';

$hoje = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));

$resultados = [];

// Função para chamar API Ituran
function chamarIturan($url, $username, $password, $placa, $data) {
    $params = http_build_query([
        'Plate' => $placa,
        'LocTime' => $data,
        'UserName' => $username,
        'Password' => $password
    ]);

    $fullUrl = $url . '?' . $params;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => $error];
    }

    // Parse XML para extrair JSON
    $xml = simplexml_load_string($response);
    if ($xml === false) {
        return ['error' => 'Erro ao parsear XML', 'raw' => $response];
    }

    $jsonData = json_decode((string)$xml, true);
    return $jsonData;
}

// 1. Chamar API com data de HOJE
$resultadoHoje = chamarIturan($ituranUrl, $username, $password, $placa, $hoje);

// 2. Chamar API com data de ONTEM
$resultadoOntem = chamarIturan($ituranUrl, $username, $password, $placa, $ontem);

// 3. Chamar API SEM data (ver se retorna odômetro atual)
$resultadoSemData = chamarIturan($ituranUrl, $username, $password, $placa, '');

// 4. Buscar dados salvos no banco
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$dbUsername = 'f137049_tool';
$dbPassword = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbUsername, $dbPassword);

    $stmt = $pdo->prepare("
        SELECT date, odometer_start, odometer_end, km_driven, synced_at
        FROM daily_mileage
        WHERE vehicle_plate = ?
        ORDER BY date DESC
        LIMIT 3
    ");
    $stmt->execute([$placa]);
    $dadosBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dadosBanco = ['error' => $e->getMessage()];
}

echo json_encode([
    'placa_testada' => $placa,
    'hora_teste' => date('H:i:s'),
    'api_ituran' => [
        'com_data_hoje' => [
            'data_enviada' => $hoje,
            'resposta' => $resultadoHoje
        ],
        'com_data_ontem' => [
            'data_enviada' => $ontem,
            'resposta' => $resultadoOntem
        ],
        'sem_data' => [
            'data_enviada' => '(vazio)',
            'resposta' => $resultadoSemData
        ]
    ],
    'dados_no_banco' => $dadosBanco,
    'analise' => [
        'odometro_ituran_hoje' => isset($resultadoHoje['resMileage']) ? $resultadoHoje['resMileage'] : 'N/A',
        'odometro_banco_hoje' => isset($dadosBanco[0]) ? $dadosBanco[0]['odometer_end'] : 'N/A',
        'diferenca' => 'Compare os valores acima'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
