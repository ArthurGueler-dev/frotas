<?php
/**
 * API de Sincronização de Telemetria - 100% PHP
 *
 * Upload para: /home/f137049/public_html/api/sincronizar.php
 * URL: https://floripa.in9automacao.com.br/api/sincronizar.php
 */

// Desabilita exibição de erros (retorna apenas JSON)
error_reporting(0);
ini_set('display_errors', 0);

// Limpa qualquer output anterior
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido. Use POST.']);
    exit();
}

// Aumenta tempo de execução (pode demorar com 77 veículos)
set_time_limit(600); // 10 minutos
ini_set('max_execution_time', 600);

// Configuração do banco de dados
$dbConfig = [
    'host' => '187.49.226.10',
    'port' => 3306,
    'user' => 'f137049_tool',
    'password' => 'In9@1234qwer',
    'database' => 'f137049_in9aut'
];

// Configuração da API Ituran
$ituranConfig = [
    'host' => 'iweb.ituran.com.br',
    'username' => 'api@i9tecnologia',
    'password' => 'Api@In9Eng'
];

/**
 * Faz requisição para a API Ituran
 */
function getIturanData($placa, $dataInicio, $dataFim, $ituranConfig) {
    $params = http_build_query([
        'UserName' => $ituranConfig['username'],
        'Password' => $ituranConfig['password'],
        'Plate' => $placa,
        'Start' => date('Y-m-d H:i:s', strtotime($dataInicio)),
        'End' => date('Y-m-d H:i:s', strtotime($dataFim)),
        'UAID' => '0',
        'MaxNumberOfRecords' => '10000'
    ]);

    $url = "https://{$ituranConfig['host']}/ituranwebservice3/Service3.asmx/GetFullReport?{$params}";

    // Configuração do contexto para HTTPS
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 120,
            'header' => "Accept: application/xml\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $xmlData = @file_get_contents($url, false, $context);

    if ($xmlData === false) {
        return ['success' => false, 'error' => 'Erro ao buscar dados da API Ituran'];
    }

    // Parse XML
    $xml = @simplexml_load_string($xmlData);
    if (!$xml) {
        return ['success' => false, 'error' => 'Erro ao parsear XML da API'];
    }

    $startOdo = isset($xml->StartOdometer) ? (float)$xml->StartOdometer : 0;
    $endOdo = isset($xml->EndOdometer) ? (float)$xml->EndOdometer : 0;
    $tempoIgnicao = isset($xml->IgnitionOnTime) ? (int)$xml->IgnitionOnTime : 0;
    $avgSpeed = isset($xml->AvgSpeed) ? (float)$xml->AvgSpeed : 0;
    $maxSpeed = isset($xml->MaxSpeed) ? (float)$xml->MaxSpeed : 0;

    // Extrair rotas
    $routes = [];
    if (isset($xml->Route)) {
        foreach ($xml->Route as $route) {
            if (isset($route->Latitude) && isset($route->Longitude)) {
                $routes[] = [
                    'latitude' => (float)$route->Latitude,
                    'longitude' => (float)$route->Longitude
                ];
            }
        }
    }

    return [
        'success' => true,
        'startOdometer' => $startOdo,
        'endOdometer' => $endOdo,
        'kmDriven' => max(0, $endOdo - $startOdo),
        'tempoIgnicao' => $tempoIgnicao,
        'avgSpeed' => $avgSpeed,
        'maxSpeed' => $maxSpeed,
        'route' => $routes,
        'totalRecords' => count($routes)
    ];
}

/**
 * Sincroniza telemetria de um veículo
 */
function sincronizarVeiculo($mysqli, $placa, $ituranConfig) {
    $hoje = date('Y-m-d');
    $agora = date('Y-m-d H:i:s');

    // Busca dados da API
    $report = getIturanData($placa, "{$hoje} 00:00:00", $agora, $ituranConfig);

    if (!$report['success']) {
        return ['success' => false, 'error' => $report['error'], 'placa' => $placa];
    }

    // Verifica se já existe registro
    $stmt = $mysqli->prepare("SELECT id FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");
    $stmt->bind_param("ss", $placa, $hoje);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();

    $primeiraRota = isset($report['route'][0]) ? $report['route'][0] : null;
    $ultimaRota = !empty($report['route']) ? $report['route'][count($report['route']) - 1] : null;
    $kmRodado = $report['kmDriven'];

    if (!$existe) {
        // INSERT
        $stmt = $mysqli->prepare("
            INSERT INTO Telemetria_Diaria (
                LicensePlate, data,
                km_inicial, km_final, km_rodado,
                tempo_ligado_minutos,
                velocidade_media, velocidade_maxima,
                status_atual,
                lat_inicio, lng_inicio,
                lat_fim, lng_fim,
                total_pontos_gps,
                fonte_api
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $status = 'Ligado';
        $fonte = 'Ituran';

        $stmt->bind_param(
            "ssdddiddsddddis",
            $placa, $hoje,
            $report['startOdometer'], $report['endOdometer'], $kmRodado,
            $report['tempoIgnicao'],
            $report['avgSpeed'], $report['maxSpeed'],
            $status,
            $primeiraRota['latitude'], $primeiraRota['longitude'],
            $ultimaRota['latitude'], $ultimaRota['longitude'],
            $report['totalRecords'], $fonte
        );
    } else {
        // UPDATE
        $stmt = $mysqli->prepare("
            UPDATE Telemetria_Diaria SET
                km_final = ?, km_rodado = ?,
                tempo_ligado_minutos = ?,
                velocidade_media = ?, velocidade_maxima = ?,
                status_atual = ?,
                lat_fim = ?, lng_fim = ?,
                total_pontos_gps = ?
            WHERE LicensePlate = ? AND data = ?
        ");

        $status = 'Ligado';

        $stmt->bind_param(
            "ddiddsddiss",
            $report['endOdometer'], $kmRodado,
            $report['tempoIgnicao'],
            $report['avgSpeed'], $report['maxSpeed'],
            $status,
            $ultimaRota['latitude'], $ultimaRota['longitude'],
            $report['totalRecords'],
            $placa, $hoje
        );
    }

    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        return ['success' => false, 'error' => $mysqli->error, 'placa' => $placa];
    }

    return [
        'success' => true,
        'placa' => $placa,
        'kmInicial' => $report['startOdometer'],
        'kmFinal' => $report['endOdometer'],
        'kmRodado' => $kmRodado
    ];
}

// EXECUÇÃO PRINCIPAL
try {
    // Conecta ao banco
    $mysqli = new mysqli(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['password'],
        $dbConfig['database'],
        $dbConfig['port']
    );

    if ($mysqli->connect_error) {
        throw new Exception("Erro de conexão: " . $mysqli->connect_error);
    }

    $mysqli->set_charset('utf8mb4');

    // Busca veículos ativos
    $result = $mysqli->query("
        SELECT DISTINCT LicensePlate
        FROM Vehicles
        WHERE LicensePlate IS NOT NULL AND LicensePlate != ''
        ORDER BY LicensePlate
    ");

    if (!$result) {
        throw new Exception("Erro ao buscar veículos: " . $mysqli->error);
    }

    $veiculos = [];
    while ($row = $result->fetch_assoc()) {
        $veiculos[] = $row['LicensePlate'];
    }

    $total = count($veiculos);
    $resultados = [];
    $sucessos = 0;
    $falhas = 0;

    // Processa cada veículo
    foreach ($veiculos as $placa) {
        $resultado = sincronizarVeiculo($mysqli, $placa, $ituranConfig);
        $resultados[] = $resultado;

        if ($resultado['success']) {
            $sucessos++;
        } else {
            $falhas++;
        }

        // Aguarda 1 segundo entre requisições
        sleep(1);
    }

    $mysqli->close();

    // Limpa buffer e retorna apenas JSON
    ob_end_clean();

    // Retorna resultado
    echo json_encode([
        'success' => true,
        'total' => $total,
        'sucessos' => $sucessos,
        'falhas' => $falhas,
        'resultados' => $resultados
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Limpa buffer em caso de erro também
    ob_end_clean();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
