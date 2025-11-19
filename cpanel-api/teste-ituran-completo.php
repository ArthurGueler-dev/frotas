<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$ituranConfig = [
    'host' => 'iweb.ituran.com.br',
    'user' => 'api@i9tecnologia',
    'pass' => 'Api@In9Eng'
];

// Testa com periodo de ontem (mais chances de ter dados)
$placa = isset($_GET['placa']) ? $_GET['placa'] : 'BDI3G10';
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'ontem';

if ($periodo === 'ontem') {
    $data = date('Y-m-d', strtotime('-1 day'));
    $inicio = "$data 00:00:00";
    $fim = "$data 23:59:59";
} else {
    // Hoje
    $data = date('Y-m-d');
    $inicio = "$data 00:00:00";
    $fim = date('Y-m-d H:i:s');
}

$params = http_build_query([
    'UserName' => $ituranConfig['user'],
    'Password' => $ituranConfig['pass'],
    'Plate' => $placa,
    'Start' => $inicio,
    'End' => $fim,
    'UAID' => '0',
    'MaxNumberOfRecords' => '5000'
]);

$url = "https://{$ituranConfig['host']}/ituranwebservice3/Service3.asmx/GetFullReport?{$params}";

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 60,
        'header' => "Accept: application/xml\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$xml = @file_get_contents($url, false, $ctx);

if (!$xml) {
    echo json_encode([
        'success' => false,
        'error' => 'Nao conseguiu buscar da API Ituran',
        'url_testada' => $url
    ], JSON_PRETTY_PRINT);
    exit;
}

$doc = @simplexml_load_string($xml);
if (!$doc) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao parsear XML',
        'xml_raw' => $xml
    ], JSON_PRETTY_PRINT);
    exit;
}

// Extrai TODOS os campos
$resultado = [
    'success' => true,
    'placa' => $placa,
    'periodo_testado' => $periodo,
    'inicio' => $inicio,
    'fim' => $fim,
    'xml_completo' => json_decode(json_encode($doc), true),
    'campos_extraidos' => [
        'ReturnCode' => isset($doc->ReturnCode) ? (string)$doc->ReturnCode : null,
        'Username' => isset($doc->Username) ? (string)$doc->Username : null,
        'Plates' => isset($doc->Plates) ? (string)$doc->Plates : null,
        'StartTime' => isset($doc->StartTime) ? (string)$doc->StartTime : null,
        'EndTime' => isset($doc->EndTime) ? (string)$doc->EndTime : null,
        'NumOfRecords' => isset($doc->NumOfRecords) ? (int)$doc->NumOfRecords : 0,
        'StartOdometer' => isset($doc->StartOdometer) ? floatval($doc->StartOdometer) : null,
        'EndOdometer' => isset($doc->EndOdometer) ? floatval($doc->EndOdometer) : null,
        'IgnitionOnTime' => isset($doc->IgnitionOnTime) ? (int)$doc->IgnitionOnTime : null,
        'AvgSpeed' => isset($doc->AvgSpeed) ? floatval($doc->AvgSpeed) : null,
        'MaxSpeed' => isset($doc->MaxSpeed) ? floatval($doc->MaxSpeed) : null
    ],
    'tem_rotas' => isset($doc->Route),
    'total_rotas' => isset($doc->Route) ? count($doc->Route) : 0
];

// Se tiver Records, mostra alguns
if (isset($doc->Records) && isset($doc->Records->Record)) {
    $resultado['total_records'] = count($doc->Records->Record);
    $resultado['primeiro_record'] = json_decode(json_encode($doc->Records->Record[0]), true);
}

echo json_encode($resultado, JSON_PRETTY_PRINT);
?>
