<?php
/**
 * API para buscar quilometragem atual de um veículo do Ituran
 *
 * Uso: GET /get-current-mileage.php?plate=ABC1234
 *
 * Retorna: { "success": true, "mileage": 123456, "plate": "ABC1234" }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validar parâmetros
if (!isset($_GET['plate']) || empty($_GET['plate'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Parâmetro "plate" é obrigatório'
    ]);
    exit;
}

$plate = strtoupper(trim($_GET['plate']));
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

// Configuração Ituran
$ituranConfig = [
    'host' => 'iweb.ituran.com.br',
    'user' => 'api@i9tecnologia',
    'pass' => 'Api@In9Eng'
];

// ESTRATÉGIA 1: Usar API GetVehicleMileage_JSON (específica para odômetro)
$params1 = http_build_query([
    'UserName' => $ituranConfig['user'],
    'Password' => $ituranConfig['pass'],
    'Plate' => $plate,
    'UAID' => '0'
]);

$url1 = "https://{$ituranConfig['host']}/ituranwebservice3/Service3.asmx/GetVehicleMileage_JSON?{$params1}";

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'header' => "Accept: application/json\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$mileage = 0;
$source = 'ituran';

// Tentar GetVehicleMileage_JSON primeiro
$response1 = @file_get_contents($url1, false, $ctx);
if ($response1) {
    $data1 = @json_decode($response1, true);
    if ($data1 && isset($data1['Odometer']) && $data1['Odometer'] > 0) {
        $mileage = intval($data1['Odometer']);
        $source = 'ituran_mileage_api';
    } elseif ($data1 && isset($data1['d'])) {
        // Tentar parsear se vier como {"d": {...}}
        $inner = @json_decode($data1['d'], true);
        if ($inner && isset($inner['Odometer']) && $inner['Odometer'] > 0) {
            $mileage = intval($inner['Odometer']);
            $source = 'ituran_mileage_api';
        }
    }
}

// ESTRATÉGIA 2: Se não funcionou, tentar GetFullReport (hoje + ontem = 2 dias calendário)
if ($mileage == 0) {
    $hoje = date('Y-m-d');
    $ontem = date('Y-m-d', strtotime('-1 day'));
    $inicio = "$ontem 00:00:00";
    $fim = date('Y-m-d H:i:s');

    $params2 = http_build_query([
        'UserName' => $ituranConfig['user'],
        'Password' => $ituranConfig['pass'],
        'Plate' => $plate,
        'Start' => $inicio,
        'End' => $fim,
        'UAID' => '0',
        'MaxNumberOfRecords' => '5000'  // Aumentado para pegar mais registros
    ]);

    $url2 = "https://{$ituranConfig['host']}/ituranwebservice3/Service3.asmx/GetFullReport?{$params2}";

    $ctx2 = stream_context_create([
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

    $xml = @file_get_contents($url2, false, $ctx2);

    if ($xml) {
        $doc = @simplexml_load_string($xml);
        if ($doc) {
            // ESTRUTURA CORRETA: Records -> RecordWithPlate -> Mileage
            if (isset($doc->Records->RecordWithPlate)) {
                $records = $doc->Records->RecordWithPlate;

                // Buscar o registro mais recente (último na lista com maior data)
                $latestMileage = 0;
                $latestDate = null;

                // Se for array de records, iterar todos e pegar o mais recente
                if (count($records) > 1) {
                    foreach ($records as $record) {
                        if (isset($record->Mileage) && isset($record->Date)) {
                            $recordMileage = intval($record->Mileage);
                            $recordDate = (string)$record->Date;

                            // Se não tem data anterior OU essa data é mais recente
                            if ($latestDate === null || strtotime($recordDate) > strtotime($latestDate)) {
                                $latestMileage = $recordMileage;
                                $latestDate = $recordDate;
                            }

                            // Também pegar o maior valor de quilometragem como backup
                            if ($recordMileage > $latestMileage) {
                                $latestMileage = $recordMileage;
                            }
                        }
                    }

                    if ($latestMileage > 0) {
                        $mileage = $latestMileage;
                        $source = 'ituran_fullreport';
                    }
                } else {
                    // Se for único record
                    if (isset($records->Mileage) && intval($records->Mileage) > 0) {
                        $mileage = intval($records->Mileage);
                        $source = 'ituran_fullreport';
                    }
                }
            }
            // Fallback: Tentar estrutura antiga (EndOdometer/StartOdometer)
            elseif (isset($doc->EndOdometer) && intval($doc->EndOdometer) > 0) {
                $mileage = intval($doc->EndOdometer);
                $source = 'ituran_fullreport';
            }
            elseif (isset($doc->StartOdometer) && intval($doc->StartOdometer) > 0) {
                $mileage = intval($doc->StartOdometer);
                $source = 'ituran_fullreport';
            }
            // Tentar pegar da última rota (Route)
            elseif (isset($doc->Route)) {
                $routes = $doc->Route;
                if (is_array($routes) || $routes instanceof Traversable) {
                    $lastRoute = null;
                    foreach ($routes as $route) {
                        $lastRoute = $route;
                    }
                    if ($lastRoute && isset($lastRoute->Odometer) && intval($lastRoute->Odometer) > 0) {
                        $mileage = intval($lastRoute->Odometer);
                        $source = 'ituran_fullreport';
                    }
                }
            }
        }
    }
}

// Se não conseguiu nenhum valor, buscar do banco (último registro)
if ($mileage == 0) {
    try {
        $pdo = new PDO(
            "mysql:host=187.49.226.10;dbname=f137049_in9aut;charset=utf8mb4",
            "f137049_tool",
            "In9@1234qwer"
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT mileage_km
            FROM daily_mileage
            WHERE vehicle_plate = ?
            ORDER BY date DESC
            LIMIT 1
        ");
        $stmt->execute([$plate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['mileage_km'] > 0) {
            $mileage = intval($result['mileage_km']);
            $source = 'database';
        }
    } catch (Exception $e) {
        // Ignorar erro do banco, apenas não terá fallback
    }
}

// Preparar resposta
$response = [
    'success' => true,
    'plate' => $plate,
    'mileage' => $mileage,
    'source' => $mileage > 0 ? $source : 'unavailable',
    'timestamp' => date('Y-m-d H:i:s')
];

// Adicionar informações de debug se solicitado
if ($debug) {
    $response['debug'] = [
        'url_mileage_api' => isset($url1) ? $url1 : null,
        'response_mileage_api' => isset($response1) ? substr($response1, 0, 500) : null,
        'url_fullreport' => isset($url2) ? $url2 : null,
        'xml_sample' => isset($xml) ? substr($xml, 0, 1000) : null
    ];
}

// Retornar resultado
echo json_encode($response, JSON_PRETTY_PRINT);
?>
