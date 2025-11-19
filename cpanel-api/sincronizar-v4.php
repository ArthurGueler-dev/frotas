<?php
/**
 * API de Sincronizacao de Telemetria - Versao 4 (PDO)
 * Upload para: /home/f137049/public_html/api/sincronizar.php
 */

// Desabilita exibicao de erros (evita quebrar JSON)
error_reporting(0);
ini_set('display_errors', 0);

// Configuracao de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// Lidar com requisicoes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuracoes
set_time_limit(300);
ini_set('max_execution_time', 300);

// Configuracao de conexao com o banco de dados
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

    // Verifica se a API retornou sucesso
    if (!isset($doc->ReturnCode) || (string)$doc->ReturnCode !== 'OK') {
        return ['ok' => false, 'erro' => 'API retornou erro: ' . (isset($doc->ReturnCode) ? (string)$doc->ReturnCode : 'desconhecido')];
    }

    // Extrai dados dos Records (estrutura real da API Ituran)
    $kmIni = 0;
    $kmFim = 0;
    $velMax = 0;
    $velTotal = 0;
    $tempoLigado = 0;
    $rotas = [];

    if (isset($doc->Records->RecordWithPlate)) {
        $records = $doc->Records->RecordWithPlate;
        $totalRecords = count($records);

        if ($totalRecords > 0) {
            // Primeiro registro (km inicial)
            // Se o primeiro registro tem Mileage = 0, procura o primeiro com valor valido
            $kmIni = 0;
            foreach ($records as $record) {
                if (isset($record->Mileage) && floatval($record->Mileage) > 0) {
                    $kmIni = floatval($record->Mileage);
                    break;
                }
            }

            // Ultimo registro (km final)
            // Percorre de tras para frente procurando o ultimo com valor valido
            $kmFim = 0;
            for ($i = $totalRecords - 1; $i >= 0; $i--) {
                if (isset($records[$i]->Mileage) && floatval($records[$i]->Mileage) > 0) {
                    $kmFim = floatval($records[$i]->Mileage);
                    break;
                }
            }

            // Processa todos os registros para calcular velocidade media/maxima
            foreach ($records as $record) {
                if (isset($record->Lat) && isset($record->Lon)) {
                    $rotas[] = [
                        'lat' => floatval($record->Lat),
                        'lng' => floatval($record->Lon)
                    ];
                }

                // Velocidade maxima
                if (isset($record->Speed)) {
                    $speed = floatval($record->Speed);
                    if ($speed > $velMax) {
                        $velMax = $speed;
                    }
                    $velTotal += $speed;
                }
            }

            // Calcula tempo ligado aproximado (numero de registros * intervalo medio)
            // Assumindo registros a cada 5 minutos em media
            $tempoLigado = $totalRecords * 5;
        }
    }

    $velMed = count($rotas) > 0 ? $velTotal / count($rotas) : 0;

    return [
        'ok' => true,
        'kmIni' => $kmIni,
        'kmFim' => $kmFim,
        'kmRod' => max(0, $kmFim - $kmIni),
        'tempo' => $tempoLigado,
        'velMed' => round($velMed, 2),
        'velMax' => $velMax,
        'rotas' => $rotas,
        'total' => count($rotas)
    ];
}

/**
 * Sincroniza um veiculo
 */
function sincVeiculo($pdo, $placa, $ituranCfg) {
    $hoje = date('Y-m-d');
    $agora = date('Y-m-d H:i:s');

    // Busca API
    $dados = buscarIturan($placa, "$hoje 00:00:00", $agora, $ituranCfg);

    if (!$dados['ok']) {
        return ['ok' => false, 'erro' => $dados['erro'], 'placa' => $placa];
    }

    // Verifica se existe
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");
    $stmt->execute([$placa, $hoje]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $existe = $row['total'] > 0;

    $r1 = isset($dados['rotas'][0]) ? $dados['rotas'][0] : ['lat' => null, 'lng' => null];
    $rN = !empty($dados['rotas']) ? end($dados['rotas']) : ['lat' => null, 'lng' => null];

    if (!$existe) {
        // INSERT
        $sql = "INSERT INTO Telemetria_Diaria (
            LicensePlate, data, km_inicial, km_final, km_rodado,
            tempo_ligado_minutos, velocidade_media, velocidade_maxima,
            status_atual, lat_inicio, lng_inicio, lat_fim, lng_fim,
            total_pontos_gps, fonte_api
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $placa, $hoje,
            $dados['kmIni'], $dados['kmFim'], $dados['kmRod'],
            $dados['tempo'], $dados['velMed'], $dados['velMax'],
            'Ligado',
            $r1['lat'], $r1['lng'], $rN['lat'], $rN['lng'],
            $dados['total'], 'Ituran'
        ]);
    } else {
        // UPDATE
        $sql = "UPDATE Telemetria_Diaria SET
            km_final = ?, km_rodado = ?, tempo_ligado_minutos = ?,
            velocidade_media = ?, velocidade_maxima = ?, status_atual = ?,
            lat_fim = ?, lng_fim = ?, total_pontos_gps = ?
            WHERE LicensePlate = ? AND data = ?";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $dados['kmFim'], $dados['kmRod'], $dados['tempo'],
            $dados['velMed'], $dados['velMax'], 'Ligado',
            $rN['lat'], $rN['lng'], $dados['total'],
            $placa, $hoje
        ]);
    }

    if (!$ok) {
        return ['ok' => false, 'erro' => 'Erro ao salvar no banco', 'placa' => $placa];
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
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca veiculos
    $stmt = $pdo->query("
        SELECT DISTINCT LicensePlate
        FROM Vehicles
        WHERE LicensePlate IS NOT NULL AND LicensePlate != ''
        ORDER BY LicensePlate
    ");

    $veiculos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $total = count($veiculos);
    $resultados = [];
    $ok = 0;
    $erro = 0;

    // Processa
    foreach ($veiculos as $placa) {
        $r = sincVeiculo($pdo, $placa, $ituranConfig);
        $resultados[] = $r;

        if ($r['ok']) {
            $ok++;
        } else {
            $erro++;
        }

        sleep(1);
    }

    echo json_encode([
        'success' => true,
        'total' => $total,
        'sucessos' => $ok,
        'falhas' => $erro,
        'resultados' => $resultados
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro na conexao: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
