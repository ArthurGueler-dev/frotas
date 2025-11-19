<?php
/**
 * API de Sincronizacao de Telemetria - Versao 4 DEBUG
 * Mostra todos os erros para diagnostico
 */

// HABILITA exibicao de erros para DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

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

// Array para log
$debugLog = [];
function addLog($msg) {
    global $debugLog;
    $debugLog[] = "[" . date("H:i:s") . "] " . $msg;
}

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

addLog("Iniciando sincronizacao...");

/**
 * Busca dados da API Ituran
 */
function buscarIturan($placa, $inicio, $fim, $config) {
    addLog("Buscando dados Ituran para placa: $placa");

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
        addLog("ERRO: Falha ao buscar API Ituran para placa $placa");
        return ['ok' => false, 'erro' => 'Erro ao buscar API'];
    }

    addLog("XML recebido com sucesso, tamanho: " . strlen($xml) . " bytes");

    $doc = @simplexml_load_string($xml);
    if (!$doc) {
        addLog("ERRO: Falha ao parsear XML");
        return ['ok' => false, 'erro' => 'Erro ao parsear XML'];
    }

    // Verifica se a API retornou sucesso
    if (!isset($doc->ReturnCode) || (string)$doc->ReturnCode !== 'OK') {
        $code = isset($doc->ReturnCode) ? (string)$doc->ReturnCode : 'desconhecido';
        addLog("ERRO: API retornou codigo: $code");
        return ['ok' => false, 'erro' => "API retornou erro: $code"];
    }

    addLog("API retornou OK");

    // Extrai dados dos Records
    $kmIni = 0;
    $kmFim = 0;
    $velMax = 0;
    $velTotal = 0;
    $tempoLigado = 0;
    $rotas = [];

    if (isset($doc->Records->RecordWithPlate)) {
        $records = $doc->Records->RecordWithPlate;
        $totalRecords = count($records);
        addLog("Total de registros encontrados: $totalRecords");

        if ($totalRecords > 0) {
            // Primeiro registro (km inicial)
            $kmIni = 0;
            foreach ($records as $record) {
                if (isset($record->Mileage) && floatval($record->Mileage) > 0) {
                    $kmIni = floatval($record->Mileage);
                    break;
                }
            }

            // Ultimo registro (km final)
            $kmFim = 0;
            for ($i = $totalRecords - 1; $i >= 0; $i--) {
                if (isset($records[$i]->Mileage) && floatval($records[$i]->Mileage) > 0) {
                    $kmFim = floatval($records[$i]->Mileage);
                    break;
                }
            }

            addLog("KM inicial: $kmIni, KM final: $kmFim");

            // Processa todos os registros
            foreach ($records as $record) {
                if (isset($record->Lat) && isset($record->Lon)) {
                    $rotas[] = [
                        'lat' => floatval($record->Lat),
                        'lng' => floatval($record->Lon)
                    ];
                }

                if (isset($record->Speed)) {
                    $speed = floatval($record->Speed);
                    if ($speed > $velMax) {
                        $velMax = $speed;
                    }
                    $velTotal += $speed;
                }
            }

            $tempoLigado = $totalRecords * 5;
            addLog("Pontos GPS: " . count($rotas) . ", Vel Max: $velMax");
        }
    } else {
        addLog("AVISO: Nenhum registro encontrado no XML");
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
    addLog("\n=== SINCRONIZANDO VEICULO: $placa ===");

    $hoje = date('Y-m-d');
    $agora = date('Y-m-d H:i:s');

    // Busca API
    $dados = buscarIturan($placa, "$hoje 00:00:00", $agora, $ituranCfg);

    if (!$dados['ok']) {
        addLog("ERRO: Falha ao buscar dados para $placa");
        return ['ok' => false, 'erro' => $dados['erro'], 'placa' => $placa];
    }

    addLog("Dados obtidos com sucesso");

    // Verifica se existe
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");
        $stmt->execute([$placa, $hoje]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $existe = $row['total'] > 0;

        addLog($existe ? "Registro ja existe - UPDATE" : "Novo registro - INSERT");
    } catch (PDOException $e) {
        addLog("ERRO ao verificar existencia: " . $e->getMessage());
        return ['ok' => false, 'erro' => 'Erro ao verificar registro: ' . $e->getMessage(), 'placa' => $placa];
    }

    $r1 = isset($dados['rotas'][0]) ? $dados['rotas'][0] : ['lat' => null, 'lng' => null];
    $rN = !empty($dados['rotas']) ? end($dados['rotas']) : ['lat' => null, 'lng' => null];

    try {
        if (!$existe) {
            // INSERT
            addLog("Preparando INSERT...");
            $sql = "INSERT INTO Telemetria_Diaria (
                LicensePlate, data, km_inicial, km_final, km_rodado,
                tempo_ligado_minutos, velocidade_media, velocidade_maxima,
                status_atual, lat_inicio, lng_inicio, lat_fim, lng_fim,
                total_pontos_gps, fonte_api
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $params = [
                $placa, $hoje,
                $dados['kmIni'], $dados['kmFim'], $dados['kmRod'],
                $dados['tempo'], $dados['velMed'], $dados['velMax'],
                'Ligado',
                $r1['lat'], $r1['lng'], $rN['lat'], $rN['lng'],
                $dados['total'], 'Ituran'
            ];

            addLog("Executando INSERT com valores: " . json_encode($params));
            $ok = $stmt->execute($params);

            if ($ok) {
                addLog("INSERT executado com sucesso! ID inserido: " . $pdo->lastInsertId());
            } else {
                addLog("ERRO: INSERT retornou false");
            }
        } else {
            // UPDATE
            addLog("Preparando UPDATE...");
            $sql = "UPDATE Telemetria_Diaria SET
                km_final = ?, km_rodado = ?, tempo_ligado_minutos = ?,
                velocidade_media = ?, velocidade_maxima = ?, status_atual = ?,
                lat_fim = ?, lng_fim = ?, total_pontos_gps = ?
                WHERE LicensePlate = ? AND data = ?";

            $stmt = $pdo->prepare($sql);
            $params = [
                $dados['kmFim'], $dados['kmRod'], $dados['tempo'],
                $dados['velMed'], $dados['velMax'], 'Ligado',
                $rN['lat'], $rN['lng'], $dados['total'],
                $placa, $hoje
            ];

            addLog("Executando UPDATE com valores: " . json_encode($params));
            $ok = $stmt->execute($params);

            if ($ok) {
                addLog("UPDATE executado com sucesso! Linhas afetadas: " . $stmt->rowCount());
            } else {
                addLog("ERRO: UPDATE retornou false");
            }
        }

        if (!$ok) {
            addLog("ERRO: Operacao de banco retornou false");
            return ['ok' => false, 'erro' => 'Erro ao salvar no banco', 'placa' => $placa];
        }

    } catch (PDOException $e) {
        addLog("ERRO PDO: " . $e->getMessage());
        return ['ok' => false, 'erro' => 'Erro PDO: ' . $e->getMessage(), 'placa' => $placa];
    }

    addLog("Sincronizacao concluida com sucesso para $placa");

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
    addLog("Conectando ao banco de dados...");
    addLog("Host: $host:$port, Database: $database, User: $user");

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    addLog("Conexao estabelecida com sucesso!");

    // Busca veiculos
    addLog("Buscando veiculos...");
    $stmt = $pdo->query("
        SELECT DISTINCT LicensePlate
        FROM Vehicles
        WHERE LicensePlate IS NOT NULL AND LicensePlate != ''
        ORDER BY LicensePlate
    ");

    $veiculos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $total = count($veiculos);
    addLog("Encontrados $total veiculos");

    $resultados = [];
    $ok = 0;
    $erro = 0;

    // Processa apenas os primeiros 2 veiculos para teste
    $veiculosTeste = array_slice($veiculos, 0, 2);
    addLog("Processando " . count($veiculosTeste) . " veiculos para teste");

    foreach ($veiculosTeste as $placa) {
        $r = sincVeiculo($pdo, $placa, $ituranConfig);
        $resultados[] = $r;

        if ($r['ok']) {
            $ok++;
        } else {
            $erro++;
        }

        sleep(1);
    }

    addLog("\nSincronizacao finalizada!");
    addLog("Sucessos: $ok, Falhas: $erro");

    echo json_encode([
        'success' => true,
        'total' => count($veiculosTeste),
        'total_veiculos_disponiveis' => $total,
        'sucessos' => $ok,
        'falhas' => $erro,
        'resultados' => $resultados,
        'debug_log' => $debugLog
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    addLog("ERRO FATAL PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro na conexao: ' . $e->getMessage(),
        'debug_log' => $debugLog
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    addLog("ERRO FATAL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_log' => $debugLog
    ], JSON_PRETTY_PRINT);
}
?>
