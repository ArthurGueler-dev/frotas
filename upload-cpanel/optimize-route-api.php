<?php
/**
 * API REST para otimizar rotas visitando blocos/locais
 * Busca locais dos blocos selecionados e prepara dados para otimização
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
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
        handleOptimize($pdo);
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

// ============== FUNÇÃO PRINCIPAL ==============

/**
 * POST - Otimizar rota com blocos selecionados
 * Body: {
 *   startPoint: {name, address, latitude, longitude},
 *   selectedBlocks: [1, 3, 5],
 *   selectedLocations: [2, 7, 15],
 *   returnToStart: true
 * }
 */
function handleOptimize($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validar ponto de partida
    if (!isset($data['startPoint']) || !isset($data['startPoint']['latitude']) || !isset($data['startPoint']['longitude'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Ponto de partida é obrigatório'
        ]);
        return;
    }

    $startPoint = $data['startPoint'];
    $selectedBlocks = isset($data['selectedBlocks']) ? $data['selectedBlocks'] : array();
    $selectedLocations = isset($data['selectedLocations']) ? $data['selectedLocations'] : array();
    $returnToStart = isset($data['returnToStart']) ? $data['returnToStart'] : true;

    $waypoints = [];

    // Adicionar ponto de partida
    $addressStart = isset($startPoint['address']) ? $startPoint['address'] : (isset($startPoint['name']) ? $startPoint['name'] : 'Ponto de Partida');
    $waypoints[] = [
        'lat' => floatval($startPoint['latitude']),
        'lon' => floatval($startPoint['longitude']),
        'address' => $addressStart,
        'type' => 'start'
    ];

    // Buscar locais dos blocos selecionados
    if (!empty($selectedBlocks)) {
        $placeholders = implode(',', array_fill(0, count($selectedBlocks), '?'));
        $stmt = $pdo->prepare("
            SELECT
                l.id,
                l.name,
                l.address,
                l.latitude,
                l.longitude,
                bl.block_id,
                bl.order_in_block
            FROM FF_Locations l
            JOIN FF_BlockLocations bl ON l.id = bl.location_id
            WHERE bl.block_id IN ($placeholders)
            ORDER BY bl.block_id ASC, bl.order_in_block ASC
        ");
        $stmt->execute($selectedBlocks);
        $blockLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($blockLocations as $loc) {
            $waypoints[] = [
                'lat' => floatval($loc['latitude']),
                'lon' => floatval($loc['longitude']),
                'address' => $loc['address'] ?: $loc['name'],
                'type' => 'stop',
                'blockId' => intval($loc['block_id']),
                'locationId' => intval($loc['id'])
            ];
        }
    }

    // Buscar locais individuais selecionados (que não fazem parte dos blocos já incluídos)
    if (!empty($selectedLocations)) {
        $placeholders = implode(',', array_fill(0, count($selectedLocations), '?'));

        $query = "
            SELECT
                l.id,
                l.name,
                l.address,
                l.latitude,
                l.longitude
            FROM FF_Locations l
            WHERE l.id IN ($placeholders)
        ";

        // Excluir locais já incluídos nos blocos
        if (!empty($selectedBlocks)) {
            $blockPlaceholders = implode(',', array_fill(0, count($selectedBlocks), '?'));
            $query .= " AND (l.block_id IS NULL OR l.block_id NOT IN ($blockPlaceholders))";
            $params = array_merge($selectedLocations, $selectedBlocks);
        } else {
            $params = $selectedLocations;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $individualLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($individualLocations as $loc) {
            $waypoints[] = [
                'lat' => floatval($loc['latitude']),
                'lon' => floatval($loc['longitude']),
                'address' => $loc['address'] ?: $loc['name'],
                'type' => 'stop',
                'locationId' => intval($loc['id'])
            ];
        }
    }

    // Validar se há pelo menos um destino além do ponto de partida
    if (count($waypoints) < 2) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Selecione pelo menos um bloco ou local para otimizar'
        ]);
        return;
    }

    // Adicionar retorno ao ponto de partida se solicitado
    if ($returnToStart) {
        $addressEnd = isset($startPoint['address']) ? $startPoint['address'] : (isset($startPoint['name']) ? $startPoint['name'] : 'Ponto de Partida');
        $waypoints[] = [
            'lat' => floatval($startPoint['latitude']),
            'lon' => floatval($startPoint['longitude']),
            'address' => $addressEnd . ' (Retorno)',
            'type' => 'end'
        ];
    }

    // Chamar OpenRouteService para calcular rota real seguindo ruas
    $routeData = calculateRouteWithOpenRouteService($waypoints);

    if (!$routeData) {
        // Fallback: usar haversine se OpenRouteService falhar
        $totalDistance = 0;
        $totalDuration = 0;

        for ($i = 0; $i < count($waypoints) - 1; $i++) {
            $distance = haversineDistance(
                $waypoints[$i]['lat'],
                $waypoints[$i]['lon'],
                $waypoints[$i + 1]['lat'],
                $waypoints[$i + 1]['lon']
            );
            $totalDistance += $distance * 1000;
            $totalDuration += ($distance / 40) * 3600;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'route' => [
                'totalDistance' => round($totalDistance, 2),
                'totalDuration' => round($totalDuration, 0),
                'waypoints' => $waypoints,
                'geometry' => null
            ]
        ]);
        return;
    }

    // Retornar rota otimizada com geometria real
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'route' => [
            'totalDistance' => $routeData['distance'],
            'totalDuration' => $routeData['duration'],
            'waypoints' => $waypoints,
            'geometry' => $routeData['geometry']
        ]
    ]);
}

// ============== FUNÇÕES AUXILIARES ==============

/**
 * Calcular rota real usando OSRM (Open Source Routing Machine - 100% gratuito)
 */
function calculateRouteWithOpenRouteService($waypoints) {
    // OSRM API público (gratuito, sem necessidade de chave)
    // Formato: /route/v1/driving/{coordinates}?overview=full&geometries=geojson

    // Converter waypoints para formato "lon,lat;lon,lat;..."
    $coordinates = array();
    foreach ($waypoints as $wp) {
        $coordinates[] = floatval($wp['lon']) . ',' . floatval($wp['lat']);
    }
    $coordsString = implode(';', $coordinates);

    // URL da API OSRM
    $apiUrl = "http://router.project-osrm.org/route/v1/driving/{$coordsString}";
    $apiUrl .= "?overview=full&geometries=geojson&steps=false";

    // Fazer requisição GET
    $options = array(
        'http' => array(
            'method' => 'GET',
            'timeout' => 30,
            'ignore_errors' => true
        )
    );

    $context = stream_context_create($options);
    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        return null; // Falhou, usar fallback
    }

    $data = json_decode($response, true);

    if (!isset($data['routes']) || count($data['routes']) === 0) {
        return null;
    }

    $route = $data['routes'][0];

    // Extrair geometria (coordenadas da rota seguindo ruas)
    $geometry = $route['geometry']['coordinates'];

    return array(
        'distance' => floatval($route['distance']), // metros
        'duration' => floatval($route['duration']), // segundos
        'geometry' => $geometry // array de [lon, lat]
    );
}

/**
 * Calcular distância entre dois pontos usando fórmula de Haversine (fallback)
 */
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Raio da Terra em km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}
