<?php
/**
 * API para salvar rota otimizada com link do Google Maps
 * e preparar para envio via WhatsApp
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração do banco de dados
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, $options);
    $pdo->exec("SET NAMES utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        salvarRotaComLink($pdo);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Apenas método POST é permitido']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
}

/**
 * Salvar rota otimizada com link do Google Maps
 *
 * Body esperado:
 * {
 *   "bloco_id": 123,
 *   "motorista_id": 45,
 *   "veiculo_id": 67,
 *   "base_lat": -20.319,
 *   "base_lon": -40.338,
 *   "locais_ordenados": [
 *     {"id": 1, "lat": -20.32, "lon": -40.34, "nome": "Local A", "endereco": "Rua A, 123"},
 *     {"id": 2, "lat": -20.33, "lon": -40.35, "nome": "Local B", "endereco": "Rua B, 456"}
 *   ],
 *   "distancia_total_km": 15.5,
 *   "tempo_total_min": 25
 * }
 */
function salvarRotaComLink($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        return;
    }

    // Validações
    $required = ['bloco_id', 'base_lat', 'base_lon', 'locais_ordenados'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }

    $bloco_id = $data['bloco_id'];
    $motorista_id = isset($data['motorista_id']) ? $data['motorista_id'] : null;
    $veiculo_id = isset($data['veiculo_id']) ? $data['veiculo_id'] : null;
    $base_lat = floatval($data['base_lat']);
    $base_lon = floatval($data['base_lon']);
    $locais = $data['locais_ordenados'];
    $distancia_km = isset($data['distancia_total_km']) ? floatval($data['distancia_total_km']) : 0;
    $tempo_min = isset($data['tempo_total_min']) ? intval($data['tempo_total_min']) : 0;

    // Gerar link do Google Maps
    $link_google_maps = gerarLinkGoogleMaps($base_lat, $base_lon, $locais);

    // Preparar JSON com sequência de locais
    $sequencia_json = json_encode($locais, JSON_UNESCAPED_UNICODE);

    try {
        $pdo->beginTransaction();

        // Inserir rota na tabela FF_Rotas
        $stmt = $pdo->prepare("
            INSERT INTO FF_Rotas (
                bloco_id,
                motorista_id,
                veiculo_id,
                distancia_total_km,
                tempo_estimado_min,
                sequencia_locais_json,
                link_google_maps,
                status,
                data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
        ");

        $stmt->execute([
            $bloco_id,
            $motorista_id,
            $veiculo_id,
            $distancia_km,
            $tempo_min,
            $sequencia_json,
            $link_google_maps
        ]);

        $rota_id = $pdo->lastInsertId();

        // Atualizar status do bloco (REMOVIDO - coluna status não existe em FF_Blocks)
        // $stmt = $pdo->prepare("UPDATE FF_Blocks SET status = 'rota_criada' WHERE id = ?");
        // $stmt->execute([$bloco_id]);

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'rota_id' => $rota_id,
            'link_google_maps' => $link_google_maps,
            'total_locais' => count($locais),
            'distancia_km' => $distancia_km,
            'tempo_min' => $tempo_min
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao salvar rota',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Gerar link do Google Maps com rota na ordem exata
 */
function gerarLinkGoogleMaps($base_lat, $base_lon, $locais_ordenados) {
    if (empty($locais_ordenados)) {
        return "https://www.google.com/maps/search/?api=1&query=$base_lat,$base_lon";
    }

    // Origin = base
    $origin = "$base_lat,$base_lon";

    // Destination = último local
    $ultimo = end($locais_ordenados);
    $destination = "{$ultimo['lat']},{$ultimo['lon']}";

    // Waypoints = todos os locais intermediários (exceto o último)
    $waypoints = [];
    for ($i = 0; $i < count($locais_ordenados) - 1; $i++) {
        $local = $locais_ordenados[$i];
        $waypoints[] = "{$local['lat']},{$local['lon']}";
    }

    // Montar URL
    $params = [
        "origin=$origin",
        "destination=$destination",
        "travelmode=driving"
    ];

    if (!empty($waypoints)) {
        $waypoints_str = implode("|", $waypoints);
        $params[] = "waypoints=$waypoints_str";
    }

    $url = "https://www.google.com/maps/dir/?api=1&" . implode("&", $params);

    return $url;
}
