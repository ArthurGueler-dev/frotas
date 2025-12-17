<?php
/**
 * API REST para gerenciar blocos geográficos (FF_Blocks)
 * Inclui algoritmo de clustering geográfico
 * Suporta: GET (listar/buscar), POST (criar blocos com clustering), DELETE (excluir)
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
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    switch ($method) {
        case 'GET':
            handleGet($pdo, $id);
            break;

        case 'POST':
            handlePost($pdo);
            break;

        case 'PUT':
            handlePut($pdo);
            break;

        case 'DELETE':
            handleDelete($pdo, $id);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }

} catch (PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' &&
        (strpos($e->getMessage(), 'timed out') !== false ||
         strpos($e->getMessage(), 'Connection refused') !== false ||
         strpos($e->getMessage(), 'Access denied') !== false)) {
        http_response_code(200);
        echo json_encode([]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erro no banco de dados',
            'message' => $e->getMessage()
        ]);
    }
}

// ============== FUNÇÕES ==============

/**
 * GET - Listar blocos com seus locais ou buscar por ID
 */
function handleGet($pdo, $id) {
    if ($id) {
        // Buscar bloco específico com seus locais
        $stmt = $pdo->prepare("
            SELECT
                b.id,
                b.name,
                b.center_latitude,
                b.center_longitude,
                b.radius_km,
                b.locations_count,
                b.import_batch,
                b.created_at
            FROM FF_Blocks b
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $block = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($block) {
            $block['center_latitude'] = floatval($block['center_latitude']);
            $block['center_longitude'] = floatval($block['center_longitude']);
            $block['radius_km'] = floatval($block['radius_km']);

            // Buscar locais do bloco
            $block['locations'] = getBlockLocations($pdo, $id);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'block' => $block
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Bloco não encontrado'
            ]);
        }
    } else {
        // Listar todos os blocos
        $where = [];
        $params = [];

        if (isset($_GET['importBatch']) && !empty($_GET['importBatch'])) {
            $where[] = "b.import_batch = ?";
            $params[] = $_GET['importBatch'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT
                b.id,
                b.name,
                b.center_latitude,
                b.center_longitude,
                b.radius_km,
                b.locations_count,
                b.import_batch,
                b.created_at,
                COUNT(bl.location_id) as actual_locations_count,
                r.id as rota_id,
                r.link_google_maps
            FROM FF_Blocks b
            LEFT JOIN FF_BlockLocations bl ON b.id = bl.block_id
            LEFT JOIN FF_Rotas r ON b.id = r.bloco_id
            $whereClause
            GROUP BY b.id, r.id, r.link_google_maps
            ORDER BY b.name ASC
        ");
        $stmt->execute($params);
        $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para cada bloco, buscar seus locais
        foreach ($blocks as &$block) {
            $block['center_latitude'] = floatval($block['center_latitude']);
            $block['center_longitude'] = floatval($block['center_longitude']);
            $block['radius_km'] = floatval($block['radius_km']);
            $block['locations'] = getBlockLocations($pdo, $block['id']);
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'blocks' => $blocks,
            'total' => count($blocks)
        ]);
    }
}

/**
 * Buscar locais de um bloco
 */
function getBlockLocations($pdo, $blockId) {
    $stmt = $pdo->prepare("
        SELECT
            l.id,
            l.name,
            l.address,
            l.latitude,
            l.longitude,
            bl.order_in_block,
            bl.distance_to_center_km
        FROM FF_Locations l
        JOIN FF_BlockLocations bl ON l.id = bl.location_id
        WHERE bl.block_id = ?
        ORDER BY bl.order_in_block ASC
    ");
    $stmt->execute([$blockId]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locations as &$location) {
        $location['latitude'] = floatval($location['latitude']);
        $location['longitude'] = floatval($location['longitude']);
        $location['distance_to_center_km'] = floatval($location['distance_to_center_km']);
    }

    return $locations;
}

/**
 * POST - Criar blocos usando algoritmo de clustering
 * Body: { locationIds: [], maxLocationsPerBlock: 5, maxDistanceKm: 5, importBatch: "..." }
 */
function handlePost($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['locationIds']) || !is_array($data['locationIds'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Array de locationIds é obrigatório'
        ]);
        return;
    }

    $locationIds = $data['locationIds'];
    $maxLocationsPerBlock = intval(isset($data['maxLocationsPerBlock']) ? $data['maxLocationsPerBlock'] : 5);
    $maxDistanceKm = floatval(isset($data['maxDistanceKm']) ? $data['maxDistanceKm'] : 5.0);
    $importBatch = isset($data['importBatch']) ? $data['importBatch'] : null;

    // Buscar locais do banco
    $placeholders = implode(',', array_fill(0, count($locationIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, name, address, latitude, longitude, category
        FROM FF_Locations
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($locationIds);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($locations)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Nenhum local encontrado com os IDs fornecidos'
        ]);
        return;
    }

    // Converter para float
    foreach ($locations as &$loc) {
        $loc['latitude'] = floatval($loc['latitude']);
        $loc['longitude'] = floatval($loc['longitude']);
    }

    $pdo->beginTransaction();

    try {
        // Executar algoritmo de clustering
        $blocks = createGeographicBlocks($pdo, $locations, $maxLocationsPerBlock, $maxDistanceKm, $importBatch);

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'blocks' => $blocks,
            'totalBlocks' => count($blocks)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao criar blocos',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * DELETE - Remover bloco por ID
 */
function handleDelete($pdo, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID é obrigatório'
        ]);
        return;
    }

    $pdo->beginTransaction();

    try {
        // Desassociar locais do bloco
        $stmt = $pdo->prepare("UPDATE FF_Locations SET block_id = NULL WHERE block_id = ?");
        $stmt->execute([$id]);

        // Deletar relacionamentos
        $stmt = $pdo->prepare("DELETE FROM FF_BlockLocations WHERE block_id = ?");
        $stmt->execute([$id]);

        // Deletar bloco
        $stmt = $pdo->prepare("DELETE FROM FF_Blocks WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bloco removido com sucesso'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao deletar bloco',
            'message' => $e->getMessage()
        ]);
    }
}

// ============== ALGORITMO DE CLUSTERING ==============

/**
 * Calcular distância entre dois pontos usando fórmula de Haversine
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

/**
 * Calcular centroide de um grupo de locais
 */
function calculateCentroid($locations) {
    $sumLat = 0;
    $sumLon = 0;
    $count = count($locations);

    foreach ($locations as $loc) {
        $sumLat += $loc['latitude'];
        $sumLon += $loc['longitude'];
    }

    return [
        'latitude' => $sumLat / $count,
        'longitude' => $sumLon / $count
    ];
}

/**
 * Criar blocos geográficos usando algoritmo de clustering
 */
function createGeographicBlocks($pdo, $locations, $maxLocationsPerBlock, $maxDistanceKm, $importBatch) {
    $blocks = [];
    $assigned = [];

    // Ordenar locais por latitude
    usort($locations, function($a, $b) {
        return $a['latitude'] <=> $b['latitude'];
    });

    $blockCounter = 1;

    // Primeira passagem: criar blocos com locais próximos
    foreach ($locations as $location) {
        if (in_array($location['id'], $assigned)) {
            continue;
        }

        // Iniciar novo bloco
        $blockLocations = [$location];
        $assigned[] = $location['id'];

        // Buscar locais próximos para adicionar ao bloco
        foreach ($locations as $candidate) {
            if (in_array($candidate['id'], $assigned)) {
                continue;
            }
            if (count($blockLocations) >= $maxLocationsPerBlock) {
                break;
            }

            // Calcular distância máxima para todos os locais já no bloco
            $maxDistanceInBlock = 0;
            foreach ($blockLocations as $blockLoc) {
                $distance = haversineDistance(
                    $blockLoc['latitude'], $blockLoc['longitude'],
                    $candidate['latitude'], $candidate['longitude']
                );
                $maxDistanceInBlock = max($maxDistanceInBlock, $distance);
            }

            // Se candidato está próximo o suficiente, adicionar ao bloco
            if ($maxDistanceInBlock <= $maxDistanceKm) {
                $blockLocations[] = $candidate;
                $assigned[] = $candidate['id'];
            }
        }

        // Criar bloco no banco
        $centroid = calculateCentroid($blockLocations);
        $category = isset($blockLocations[0]['category']) ? $blockLocations[0]['category'] : 'Geral';
        $blockName = "Bloco " . chr(64 + $blockCounter) . " - " . $category;

        $stmt = $pdo->prepare("
            INSERT INTO FF_Blocks
                (name, center_latitude, center_longitude, radius_km, locations_count, import_batch)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $blockName,
            $centroid['latitude'],
            $centroid['longitude'],
            $maxDistanceKm,
            count($blockLocations),
            $importBatch
        ]);

        $blockId = $pdo->lastInsertId();

        // Associar locais ao bloco
        foreach ($blockLocations as $index => $loc) {
            $distanceToCenter = haversineDistance(
                $centroid['latitude'], $centroid['longitude'],
                $loc['latitude'], $loc['longitude']
            );

            $stmt = $pdo->prepare("
                INSERT INTO FF_BlockLocations
                    (block_id, location_id, order_in_block, distance_to_center_km)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$blockId, $loc['id'], $index + 1, $distanceToCenter]);

            // Atualizar location com block_id
            $stmt = $pdo->prepare("UPDATE FF_Locations SET block_id = ? WHERE id = ?");
            $stmt->execute([$blockId, $loc['id']]);
        }

        $blocks[] = [
            'id' => $blockId,
            'name' => $blockName,
            'centerLatitude' => $centroid['latitude'],
            'centerLongitude' => $centroid['longitude'],
            'radiusKm' => $maxDistanceKm,
            'locationsCount' => count($blockLocations),
            'locations' => $blockLocations
        ];

        $blockCounter++;
    }

    // Segunda passagem: processar locais órfãos
    $orphans = array_filter($locations, function($loc) use ($assigned) {
        return !in_array($loc['id'], $assigned);
    });

    foreach ($orphans as $orphan) {
        // Tentar encontrar bloco próximo com espaço
        $bestBlock = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($blocks as &$block) {
            if ($block['locationsCount'] >= $maxLocationsPerBlock) {
                continue;
            }

            $distance = haversineDistance(
                $block['centerLatitude'], $block['centerLongitude'],
                $orphan['latitude'], $orphan['longitude']
            );

            if ($distance <= $maxDistanceKm && $distance < $minDistance) {
                $minDistance = $distance;
                $bestBlock = &$block;
            }
        }

        if ($bestBlock) {
            // Adicionar ao bloco existente
            $stmt = $pdo->prepare("
                INSERT INTO FF_BlockLocations
                    (block_id, location_id, order_in_block, distance_to_center_km)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$bestBlock['id'], $orphan['id'], $bestBlock['locationsCount'] + 1, $minDistance]);

            $stmt = $pdo->prepare("UPDATE FF_Locations SET block_id = ? WHERE id = ?");
            $stmt->execute([$bestBlock['id'], $orphan['id']]);

            $stmt = $pdo->prepare("UPDATE FF_Blocks SET locations_count = locations_count + 1 WHERE id = ?");
            $stmt->execute([$bestBlock['id']]);

            $bestBlock['locationsCount']++;
            $bestBlock['locations'][] = $orphan;

        } else {
            // Criar bloco individual para órfão
            $category = isset($orphan['category']) ? $orphan['category'] : 'Individual';
            $blockName = "Bloco " . chr(64 + $blockCounter) . " - " . $category;

            $stmt = $pdo->prepare("
                INSERT INTO FF_Blocks
                    (name, center_latitude, center_longitude, radius_km, locations_count, import_batch)
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([
                $blockName,
                $orphan['latitude'],
                $orphan['longitude'],
                $maxDistanceKm,
                $importBatch
            ]);

            $blockId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO FF_BlockLocations
                    (block_id, location_id, order_in_block, distance_to_center_km)
                VALUES (?, ?, 1, 0)
            ");
            $stmt->execute([$blockId, $orphan['id']]);

            $stmt = $pdo->prepare("UPDATE FF_Locations SET block_id = ? WHERE id = ?");
            $stmt->execute([$blockId, $orphan['id']]);

            $blocks[] = [
                'id' => $blockId,
                'name' => $blockName,
                'centerLatitude' => $orphan['latitude'],
                'centerLongitude' => $orphan['longitude'],
                'radiusKm' => $maxDistanceKm,
                'locationsCount' => 1,
                'locations' => [$orphan]
            ];

            $blockCounter++;
        }
    }

    return $blocks;
}

        case 'PUT':
            handlePut($pdo);
            break;

/**
 * PUT - Salvar bloco já processado (do Python)
 */
function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['center_latitude']) || !isset($data['center_longitude'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'center_latitude e center_longitude são obrigatórios']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO blocks (
                center_latitude, center_longitude, diameter_km,
                locations_count, routes_count, total_distance_km,
                import_batch, algorithm, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['center_latitude'],
            $data['center_longitude'],
            $data['diameterKm'] ?? 0,
            $data['locationsCount'] ?? 0,
            $data['routesCount'] ?? 0,
            $data['totalDistanceKm'] ?? 0,
            $data['importBatch'] ?? null,
            $data['algorithm'] ?? 'python'
        ]);
        
        $blockId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'block' => [
                'id' => $blockId,
                'center_latitude' => $data['center_latitude'],
                'center_longitude' => $data['center_longitude']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
