<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

$ituranConfig = [
    'host' => 'iweb.ituran.com.br',
    'user' => 'api@i9tecnologia',
    'pass' => 'Api@In9Eng'
];

// Busca dados de ontem (mais chance de ter dados)
$data = date('Y-m-d', strtotime('-1 day'));
$inicio = "$data 00:00:00";
$fim = "$data 23:59:59";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca 5 placas
    $stmt = $pdo->query("SELECT LicensePlate FROM Vehicles WHERE LicensePlate IS NOT NULL LIMIT 5");
    $placas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $resultados = [];

    foreach ($placas as $placa) {
        $params = http_build_query([
            'UserName' => $ituranConfig['user'],
            'Password' => $ituranConfig['pass'],
            'Plate' => $placa,
            'Start' => $inicio,
            'End' => $fim,
            'UAID' => '0',
            'MaxNumberOfRecords' => '100'
        ]);

        $url = "https://{$ituranConfig['host']}/ituranwebservice3/Service3.asmx/GetFullReport?{$params}";

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'header' => "Accept: application/xml\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $xml = @file_get_contents($url, false, $ctx);
        
        if ($xml) {
            $doc = @simplexml_load_string($xml);
            if ($doc) {
                $resultados[] = [
                    'placa' => $placa,
                    'ReturnCode' => isset($doc->ReturnCode) ? (string)$doc->ReturnCode : null,
                    'NumOfRecords' => isset($doc->NumOfRecords) ? (int)$doc->NumOfRecords : 0,
                    'StartOdometer' => isset($doc->StartOdometer) ? floatval($doc->StartOdometer) : 0,
                    'EndOdometer' => isset($doc->EndOdometer) ? floatval($doc->EndOdometer) : 0,
                    'tem_dados' => isset($doc->StartOdometer) && floatval($doc->StartOdometer) > 0
                ];
            }
        }

        usleep(500000); // 0.5 segundos entre requisicoes
    }

    echo json_encode([
        'success' => true,
        'periodo' => "$inicio ate $fim",
        'total_testado' => count($placas),
        'resultados' => $resultados,
        'resumo' => [
            'com_dados' => count(array_filter($resultados, function($r) { return $r['tem_dados']; })),
            'sem_dados' => count(array_filter($resultados, function($r) { return !$r['tem_dados']; }))
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
