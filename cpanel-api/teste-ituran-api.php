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

// Testa com uma placa especifica
$placa = isset($_GET['placa']) ? $_GET['placa'] : 'BDI3G10';
$hoje = date('Y-m-d');
$inicio = "$hoje 00:00:00";
$fim = date('Y-m-d H:i:s');

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
        'url_testada' => $url,
        'placa' => $placa,
        'periodo' => "$inicio ate $fim"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Mostra o XML bruto
$doc = @simplexml_load_string($xml);
if (!$doc) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao parsear XML',
        'xml_raw' => substr($xml, 0, 500) // Primeiros 500 caracteres
    ], JSON_PRETTY_PRINT);
    exit;
}

// Extrai dados
$kmIni = isset($doc->StartOdometer) ? floatval($doc->StartOdometer) : 0;
$kmFim = isset($doc->EndOdometer) ? floatval($doc->EndOdometer) : 0;
$tempo = isset($doc->IgnitionOnTime) ? intval($doc->IgnitionOnTime) : 0;
$velMed = isset($doc->AvgSpeed) ? floatval($doc->AvgSpeed) : 0;
$velMax = isset($doc->MaxSpeed) ? floatval($doc->MaxSpeed) : 0;

$rotas = [];
if (isset($doc->Route)) {
    foreach ($doc->Route as $r) {
        if (isset($r->Latitude) && isset($r->Longitude)) {
            $rotas[] = [
                'lat' => floatval($r->Latitude),
                'lng' => floatval($r->Longitude),
                'speed' => isset($r->Speed) ? floatval($r->Speed) : 0,
                'time' => isset($r->GPSTime) ? (string)$r->GPSTime : ''
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'placa' => $placa,
    'periodo' => "$inicio ate $fim",
    'dados_extraidos' => [
        'StartOdometer' => $kmIni,
        'EndOdometer' => $kmFim,
        'KmRodado' => max(0, $kmFim - $kmIni),
        'IgnitionOnTime' => $tempo,
        'AvgSpeed' => $velMed,
        'MaxSpeed' => $velMax,
        'TotalPontosGPS' => count($rotas)
    ],
    'primeira_rota' => isset($rotas[0]) ? $rotas[0] : null,
    'ultima_rota' => !empty($rotas) ? end($rotas) : null,
    'xml_campos_disponiveis' => array_keys((array)$doc)
], JSON_PRETTY_PRINT);
?>
