<?php
/**
 * API de Sincronização de Telemetria - Versão 3 (Testada)
 * Upload para: /home/f137049/public_html/api/sincronizar.php
 */

// Configuração de erros
ini_set('display_errors', 0);
error_reporting(0);

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurações
set_time_limit(300);
ini_set('max_execution_time', 300);

$dbConfig = [
    'host' => '187.49.226.10',
    'port' => 3306,
    'user' => 'f137049_tool',
    'pass' => 'In9@1234qwer',
    'db' => 'f137049_in9aut'
];

$ituranConfig = [
    'host' => 'iweb.ituran.com.br',
    'user' => 'api@i9tecnologia',
    'pass' => 'Api@In9Eng'
];

/**
 * Busca dados da API Ituran
 */
function buscarIturan($placa, $inicio, $fim, $config) {
    $params = http_build_query([
        'UserName' => $config['user'],
        'Password' => $config['pass'],
        'Plate' => $placa,
        'Start' => $inicio,
        'End' => $fim,
        'UAID' => '0',
        'MaxNumberOfRecords' => '5000'
    ]);

    $url = "https://{$config['host']}/ituranwebservice3/Service3.asmx/GetFullReport?{$params}";

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
        return ['ok' => false, 'erro' => 'Erro ao buscar API'];
    }

    $doc = @simplexml_load_string($xml);
    if (!$doc) {
        return ['ok' => false, 'erro' => 'Erro ao parsear XML'];
    }

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
                    'lng' => floatval($r->Longitude)
                ];
            }
        }
    }

    return [
        'ok' => true,
        'kmIni' => $kmIni,
        'kmFim' => $kmFim,
        'kmRod' => max(0, $kmFim - $kmIni),
        'tempo' => $tempo,
        'velMed' => $velMed,
        'velMax' => $velMax,
        'rotas' => $rotas,
        'total' => count($rotas)
    ];
}

/**
 * Sincroniza um veículo
 */
function sincVeiculo($db, $placa, $ituranCfg) {
    $hoje = date('Y-m-d');
    $agora = date('Y-m-d H:i:s');

    // Busca API
    $dados = buscarIturan($placa, "$hoje 00:00:00", $agora, $ituranCfg);

    if (!$dados['ok']) {
        return ['ok' => false, 'erro' => $dados['erro'], 'placa' => $placa];
    }

    // Verifica se existe
    $sql = "SELECT id FROM Telemetria_Diaria WHERE LicensePlate=? AND data=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $placa, $hoje);
    $stmt->execute();
    $res = $stmt->get_result();
    $existe = $res->num_rows > 0;
    $stmt->close();

    $r1 = isset($dados['rotas'][0]) ? $dados['rotas'][0] : ['lat' => null, 'lng' => null];
    $rN = !empty($dados['rotas']) ? end($dados['rotas']) : ['lat' => null, 'lng' => null];

    if (!$existe) {
        // INSERT
        $sql = "INSERT INTO Telemetria_Diaria (
            LicensePlate, data, km_inicial, km_final, km_rodado,
            tempo_ligado_minutos, velocidade_media, velocidade_maxima,
            status_atual, lat_inicio, lng_inicio, lat_fim, lng_fim,
            total_pontos_gps, fonte_api
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $db->prepare($sql);
        $status = 'Ligado';
        $fonte = 'Ituran';

        $stmt->bind_param(
            'ssdddiddsddddis',
            $placa, $hoje,
            $dados['kmIni'], $dados['kmFim'], $dados['kmRod'],
            $dados['tempo'], $dados['velMed'], $dados['velMax'],
            $status,
            $r1['lat'], $r1['lng'], $rN['lat'], $rN['lng'],
            $dados['total'], $fonte
        );
    } else {
        // UPDATE
        $sql = "UPDATE Telemetria_Diaria SET
            km_final=?, km_rodado=?, tempo_ligado_minutos=?,
            velocidade_media=?, velocidade_maxima=?, status_atual=?,
            lat_fim=?, lng_fim=?, total_pontos_gps=?
            WHERE LicensePlate=? AND data=?";

        $stmt = $db->prepare($sql);
        $status = 'Ligado';

        $stmt->bind_param(
            'ddiddsddiss',
            $dados['kmFim'], $dados['kmRod'], $dados['tempo'],
            $dados['velMed'], $dados['velMax'], $status,
            $rN['lat'], $rN['lng'], $dados['total'],
            $placa, $hoje
        );
    }

    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return ['ok' => false, 'erro' => $db->error, 'placa' => $placa];
    }

    return [
        'ok' => true,
        'placa' => $placa,
        'kmIni' => $dados['kmIni'],
        'kmFim' => $dados['kmFim'],
        'kmRod' => $dados['kmRod']
    ];
}

// MAIN
try {
    $db = new mysqli(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['pass'],
        $dbConfig['db'],
        $dbConfig['port']
    );

    if ($db->connect_error) {
        throw new Exception('Erro MySQL: ' . $db->connect_error);
    }

    $db->set_charset('utf8mb4');

    // Busca veículos
    $res = $db->query("
        SELECT DISTINCT LicensePlate
        FROM Vehicles
        WHERE LicensePlate IS NOT NULL AND LicensePlate != ''
        ORDER BY LicensePlate
    ");

    if (!$res) {
        throw new Exception('Erro ao buscar veículos: ' . $db->error);
    }

    $veiculos = [];
    while ($row = $res->fetch_assoc()) {
        $veiculos[] = $row['LicensePlate'];
    }

    $total = count($veiculos);
    $resultados = [];
    $ok = 0;
    $erro = 0;

    // Processa
    foreach ($veiculos as $placa) {
        $r = sincVeiculo($db, $placa, $ituranConfig);
        $resultados[] = $r;

        if ($r['ok']) {
            $ok++;
        } else {
            $erro++;
        }

        sleep(1);
    }

    $db->close();

    echo json_encode([
        'success' => true,
        'total' => $total,
        'sucessos' => $ok,
        'falhas' => $erro,
        'resultados' => $resultados
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
