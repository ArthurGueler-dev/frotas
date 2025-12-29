<?php
/**
 * API para gerenciar histórico de otimizações de rotas
 *
 * Endpoints:
 * - GET ?block_id=123 - Listar histórico de um bloco
 * - GET ?id=456 - Obter detalhes de uma otimização específica
 * - POST - Salvar nova otimização no histórico
 * - DELETE ?id=456 - Deletar entrada do histórico
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conexão com banco de dados
try {
    $pdo = new PDO(
        "mysql:host=187.49.226.10;dbname=f137049_in9aut;charset=utf8mb4",
        "f137049_tool",
        "In9@1234qwer",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão: ' . $e->getMessage()
    ]);
    exit;
}

// ========== GET - Listar histórico de um bloco ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['block_id'])) {
    try {
        $blockId = intval($_GET['block_id']);
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        $stmt = $pdo->prepare("
            SELECT
                rh.id,
                rh.block_id,
                rh.vehicle_id,
                rh.driver_id,
                rh.optimization_date,
                rh.total_distance_km,
                rh.total_duration_min,
                rh.num_stops,
                rh.created_by,
                v.plate as vehicle_plate,
                v.name as vehicle_name,
                d.name as driver_name,
                b.name as block_name
            FROM FF_RouteHistory rh
            LEFT JOIN Vehicles v ON rh.vehicle_id = v.id
            LEFT JOIN Drivers d ON rh.driver_id = d.id
            LEFT JOIN FF_Blocks b ON rh.block_id = b.id
            WHERE rh.block_id = ?
            ORDER BY rh.optimization_date DESC
            LIMIT ?
        ");

        $stmt->execute([$blockId, $limit]);
        $history = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'count' => count($history),
            'history' => $history
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========== GET - Obter detalhes de uma otimização específica ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);

        $stmt = $pdo->prepare("
            SELECT
                rh.*,
                v.plate as vehicle_plate,
                v.name as vehicle_name,
                d.name as driver_name,
                b.name as block_name
            FROM FF_RouteHistory rh
            LEFT JOIN Vehicles v ON rh.vehicle_id = v.id
            LEFT JOIN Drivers d ON rh.driver_id = d.id
            LEFT JOIN FF_Blocks b ON rh.block_id = b.id
            WHERE rh.id = ?
        ");

        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if (!$record) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Registro não encontrado'
            ]);
            exit;
        }

        // Decodificar JSON se existir
        if ($record['route_data']) {
            $record['route_data'] = json_decode($record['route_data'], true);
        }

        echo json_encode([
            'success' => true,
            'record' => $record
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========== POST - Salvar nova otimização ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            throw new Exception('JSON inválido');
        }

        // Validação
        if (!isset($data['block_id'])) {
            throw new Exception('block_id é obrigatório');
        }

        $blockId = intval($data['block_id']);
        $vehicleId = isset($data['vehicle_id']) && $data['vehicle_id'] ? intval($data['vehicle_id']) : null;
        $driverId = isset($data['driver_id']) && $data['driver_id'] ? intval($data['driver_id']) : null;
        $totalDistanceKm = isset($data['total_distance_km']) ? floatval($data['total_distance_km']) : null;
        $totalDurationMin = isset($data['total_duration_min']) ? intval($data['total_duration_min']) : null;
        $numStops = isset($data['num_stops']) ? intval($data['num_stops']) : null;
        $routeData = isset($data['route_data']) ? json_encode($data['route_data']) : null;
        $mapHtml = isset($data['map_html']) ? $data['map_html'] : null;
        $createdBy = isset($data['created_by']) ? $data['created_by'] : 'system';

        $stmt = $pdo->prepare("
            INSERT INTO FF_RouteHistory (
                block_id,
                vehicle_id,
                driver_id,
                total_distance_km,
                total_duration_min,
                num_stops,
                route_data,
                map_html,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $blockId,
            $vehicleId,
            $driverId,
            $totalDistanceKm,
            $totalDurationMin,
            $numStops,
            $routeData,
            $mapHtml,
            $createdBy
        ]);

        $insertedId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Otimização salva no histórico',
            'id' => $insertedId
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========== DELETE - Deletar entrada do histórico ==========
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
    try {
        $id = intval($_GET['id']);

        $stmt = $pdo->prepare("DELETE FROM FF_RouteHistory WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Registro não encontrado'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Registro deletado'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Método não suportado
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Método não suportado'
]);
?>
