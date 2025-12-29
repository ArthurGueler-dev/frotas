<?php
/**
 * API REST para gerenciar blocos geográficos (FF_Blocks)
 * Inclui algoritmo de clustering geográfico
 * Suporta: GET (listar/buscar), POST (criar blocos com clustering), DELETE (excluir)
 *
 * VERSÃO: 2.3 - COM WHATSAPP (2025-12-16)
 * - Adicionado LEFT JOIN com FF_Rotas para incluir rota_id e link_google_maps
 * - Validação completa na 1ª e 2ª passagem
 * - Cálculo de maxPairDistanceKm no GET e POST
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

// Aumentar limites de tempo e memória para processar muitos locais
set_time_limit(300); // 5 minutos
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

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

    // Endpoint de versão (GET /blocks-api.php?version=1)
    if ($method === 'GET' && isset($_GET['version'])) {
        http_response_code(200);
        echo json_encode([
            'version' => '2.3',
            'date' => '2025-12-16',
            'fixes' => [
                'Adicionado rota_id e link_google_maps via LEFT JOIN',
                'Suporte a envio via WhatsApp',
                'Validação completa na 1ª passagem',
                'Validação completa na 2ª passagem (órfãos)',
                'Cálculo de maxPairDistanceKm no GET e POST'
            ]
        ]);
        exit;
    }

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
                b.created_at,
                b.map_html,
                r.id as rota_id,
                r.link_google_maps
            FROM FF_Blocks b
            LEFT JOIN FF_Rotas r ON b.id = r.bloco_id
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

            // Calcular distância máxima entre TODOS os pares de pontos
            $maxPairDistance = 0;
            $locs = $block['locations'];
            for ($i = 0; $i < count($locs); $i++) {
                for ($j = $i + 1; $j < count($locs); $j++) {
                    $pairDist = haversineDistance(
                        $locs[$i]['latitude'], $locs[$i]['longitude'],
                        $locs[$j]['latitude'], $locs[$j]['longitude']
                    );
                    $maxPairDistance = max($maxPairDistance, $pairDist);
                }
            }
            $block['maxPairDistanceKm'] = $maxPairDistance;

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
                COUNT(DISTINCT bl.location_id) as actual_locations_count,
                (LENGTH(b.map_html) > 0) as has_map_html,
                b.map_html,
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

        // Para cada bloco, buscar seus locais e calcular maxPairDistanceKm
        foreach ($blocks as &$block) {
            $block['center_latitude'] = floatval($block['center_latitude']);
            $block['center_longitude'] = floatval($block['center_longitude']);
            $block['radius_km'] = floatval($block['radius_km']);
            $block['locations'] = getBlockLocations($pdo, $block['id']);

            // Calcular distância máxima entre TODOS os pares de pontos
            $maxPairDistance = 0;
            $locs = $block['locations'];
            for ($i = 0; $i < count($locs); $i++) {
                for ($j = $i + 1; $j < count($locs); $j++) {
                    $pairDist = haversineDistance(
                        $locs[$i]['latitude'], $locs[$i]['longitude'],
                        $locs[$j]['latitude'], $locs[$j]['longitude']
                    );
                    $maxPairDistance = max($maxPairDistance, $pairDist);
                }
            }
            $block['maxPairDistanceKm'] = $maxPairDistance;
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
 * POST - Criar blocos usando algoritmo de clustering OU criar bloco Python individual
 * Body (clustering): { locationIds: [], maxLocationsPerBlock: 5, maxDistanceKm: 5, importBatch: "..." }
 * Body (Python): { createSingleBlock: true, name, center_latitude, center_longitude, ... }
 */
function handlePost($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Detectar se é criação de bloco Python individual
    if (isset($data['createSingleBlock']) && $data['createSingleBlock'] === true) {
        return handlePut($pdo); // Reutilizar a lógica do PUT
    }

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

    // Limitar a 300 locais por processamento para evitar timeout
    $totalLocations = count($locationIds);
    if ($totalLocations > 300) {
        $locationIds = array_slice($locationIds, 0, 300);
        error_log("AVISO: Processando apenas 300 de $totalLocations locais para evitar timeout");
    }

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
 * DELETE - Remover bloco por ID OU deletar todos
 */
function handleDelete($pdo, $id) {
    // Verificar se é uma requisição para deletar TODOS
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['deleteAll']) && $data['deleteAll'] === true) {
        return handleDeleteAll($pdo);
    }

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

/**
 * PUT - Criar bloco Python individual (sem clustering automático)
 */
function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        return;
    }

    // Verificar se é UPDATE (tem ID) ou INSERT (sem ID)
    $blockId = isset($_GET['id']) ? intval($_GET['id']) : (isset($data['id']) ? intval($data['id']) : null);

    if ($blockId) {
        // UPDATE - Atualizar bloco existente
        handleUpdateBlock($pdo, $blockId, $data);
    } else {
        // INSERT - Criar novo bloco (comportamento antigo)
        handleInsertBlock($pdo, $data);
    }
}

/**
 * Atualizar bloco existente (renomear, alterar coordenadas, etc.)
 */
function handleUpdateBlock($pdo, $blockId, $data) {
    $pdo->beginTransaction();

    try {
        // Verificar se o bloco existe
        $stmt = $pdo->prepare("SELECT id, name FROM FF_Blocks WHERE id = ?");
        $stmt->execute([$blockId]);
        $block = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$block) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Bloco não encontrado']);
            return;
        }

        // Preparar campos para UPDATE
        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }

        if (isset($data['center_latitude'])) {
            $fields[] = "center_latitude = ?";
            $values[] = $data['center_latitude'];
        }

        if (isset($data['center_longitude'])) {
            $fields[] = "center_longitude = ?";
            $values[] = $data['center_longitude'];
        }

        if (isset($data['radius_km'])) {
            $fields[] = "radius_km = ?";
            $values[] = $data['radius_km'];
        }

        if (isset($data['diameterKm'])) {
            $fields[] = "radius_km = ?";
            $values[] = $data['diameterKm'];
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
            return;
        }

        // Adicionar ID aos valores
        $values[] = $blockId;

        // Executar UPDATE
        $sql = "UPDATE FF_Blocks SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $pdo->commit();

        // Retornar bloco atualizado
        $stmt = $pdo->prepare("SELECT * FROM FF_Blocks WHERE id = ?");
        $stmt->execute([$blockId]);
        $updatedBlock = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Bloco atualizado com sucesso',
            'block' => $updatedBlock
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao atualizar bloco',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Inserir novo bloco (comportamento original do handlePut)
 */
function handleInsertBlock($pdo, $data) {
    if (!isset($data['center_latitude']) || !isset($data['center_longitude'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'center_latitude e center_longitude são obrigatórios']);
        return;
    }

    $pdo->beginTransaction();

    try {
        // Inserir bloco
        $stmt = $pdo->prepare("
            INSERT INTO FF_Blocks (
                name,
                center_latitude,
                center_longitude,
                radius_km,
                locations_count,
                import_batch,
                map_html
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            isset($data['name']) ? $data['name'] : 'Bloco Python',
            $data['center_latitude'],
            $data['center_longitude'],
            isset($data['diameterKm']) ? $data['diameterKm'] : 0,
            isset($data['locationsCount']) ? $data['locationsCount'] : 0,
            isset($data['importBatch']) ? $data['importBatch'] : null,
            isset($data['map_html']) ? $data['map_html'] : null
        ]);

        $blockId = $pdo->lastInsertId();

        // Associar locais ao bloco (se fornecidos)
        if (isset($data['locationIds']) && is_array($data['locationIds'])) {
            $order = 1;
            foreach ($data['locationIds'] as $locationId) {
                // Inserir em FF_BlockLocations
                $stmt = $pdo->prepare("
                    INSERT INTO FF_BlockLocations (block_id, location_id, order_in_block, distance_to_center_km)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$blockId, $locationId, $order]);

                // Atualizar FF_Locations
                $stmt = $pdo->prepare("UPDATE FF_Locations SET block_id = ? WHERE id = ?");
                $stmt->execute([$blockId, $locationId]);

                $order++;
            }
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'block' => [
                'id' => $blockId,
                'name' => isset($data['name']) ? $data['name'] : 'Bloco Python',
                'center_latitude' => $data['center_latitude'],
                'center_longitude' => $data['center_longitude'],
                'locations_count' => isset($data['locationsCount']) ? $data['locationsCount'] : 0
            ]
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao criar bloco',
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
        if ($a['latitude'] == $b['latitude']) return 0;
        return ($a['latitude'] < $b['latitude']) ? -1 : 1;
    });

    // Buscar o maior número de bloco existente para este importBatch para continuar a numeração
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(name, 7) AS UNSIGNED)) as max_num
        FROM FF_Blocks
        WHERE import_batch = ? AND name LIKE 'Bloco %'
    ");
    $stmt->execute([$importBatch]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $blockCounter = $result && $result['max_num'] ? intval($result['max_num']) + 1 : 1;

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

            // Verificar se candidato está próximo de TODOS os pontos do bloco
            $canAdd = true;
            foreach ($blockLocations as $blockLoc) {
                $distance = haversineDistance(
                    $blockLoc['latitude'], $blockLoc['longitude'],
                    $candidate['latitude'], $candidate['longitude']
                );

                // Se está longe demais de QUALQUER ponto, não pode adicionar
                if ($distance > $maxDistanceKm) {
                    $canAdd = false;
                    break;
                }
            }

            // Adicionar apenas se estiver próximo de TODOS os pontos
            if ($canAdd) {
                $blockLocations[] = $candidate;
                $assigned[] = $candidate['id'];
            }
        }

        // Criar bloco no banco
        $centroid = calculateCentroid($blockLocations);

        // Calcular o raio REAL do bloco (maior distância do centro até qualquer ponto)
        $actualRadius = 0;
        foreach ($blockLocations as $loc) {
            $distFromCenter = haversineDistance(
                $centroid['latitude'], $centroid['longitude'],
                $loc['latitude'], $loc['longitude']
            );
            $actualRadius = max($actualRadius, $distFromCenter);
        }

        // Nome simples com número (suporta infinitos blocos)
        $blockName = "Bloco " . $blockCounter;

        $stmt = $pdo->prepare("
            INSERT INTO FF_Blocks
                (name, center_latitude, center_longitude, radius_km, locations_count, import_batch)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $blockName,
            $centroid['latitude'],
            $centroid['longitude'],
            $actualRadius, // Raio REAL, não o limite máximo
            count($blockLocations),
            $importBatch
        ]);

        $blockId = $pdo->lastInsertId();

        // Calcular distância máxima entre TODOS os pares de pontos (para validação)
        $maxPairDistance = 0;
        $debugDistances = [];
        for ($i = 0; $i < count($blockLocations); $i++) {
            for ($j = $i + 1; $j < count($blockLocations); $j++) {
                $pairDist = haversineDistance(
                    $blockLocations[$i]['latitude'], $blockLocations[$i]['longitude'],
                    $blockLocations[$j]['latitude'], $blockLocations[$j]['longitude']
                );
                if ($pairDist > $maxDistanceKm) {
                    $debugDistances[] = sprintf(
                        "ERRO: %s->%s = %.2fkm (LIMITE: %.1fkm)",
                        $blockLocations[$i]['name'],
                        $blockLocations[$j]['name'],
                        $pairDist,
                        $maxDistanceKm
                    );
                }
                $maxPairDistance = max($maxPairDistance, $pairDist);
            }
        }

        // Log de debug se houver violação
        if ($maxPairDistance > $maxDistanceKm) {
            error_log("=== BLOCO $blockName VIOLOU LIMITE ===");
            error_log("Max pair distance: " . round($maxPairDistance, 2) . "km");
            error_log("Violações: " . implode(" | ", $debugDistances));
        }

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
            'radiusKm' => $actualRadius,
            'maxPairDistanceKm' => $maxPairDistance, // NOVA MÉTRICA: maior distância entre 2 pontos
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
        $minMaxDistance = PHP_FLOAT_MAX; // Menor das distâncias máximas

        foreach ($blocks as &$block) {
            if ($block['locationsCount'] >= $maxLocationsPerBlock) {
                continue;
            }

            // CORREÇÃO CRÍTICA: Verificar se órfão está próximo de TODOS os pontos do bloco
            $canAddToBlock = true;
            $maxDistToAnyPoint = 0;

            foreach ($block['locations'] as $blockLoc) {
                $distance = haversineDistance(
                    $blockLoc['latitude'], $blockLoc['longitude'],
                    $orphan['latitude'], $orphan['longitude']
                );

                $maxDistToAnyPoint = max($maxDistToAnyPoint, $distance);

                // Se órfão está longe demais de QUALQUER ponto do bloco, não pode adicionar
                if ($distance > $maxDistanceKm) {
                    $canAddToBlock = false;
                    break;
                }
            }

            // Se pode adicionar E tem a menor distância máxima até agora, este é o melhor bloco
            if ($canAddToBlock && $maxDistToAnyPoint < $minMaxDistance) {
                $minMaxDistance = $maxDistToAnyPoint;
                $bestBlock = &$block;
            }
        }

        if ($bestBlock) {
            // Adicionar ao bloco existente
            $distToCenter = haversineDistance(
                $bestBlock['centerLatitude'], $bestBlock['centerLongitude'],
                $orphan['latitude'], $orphan['longitude']
            );

            $stmt = $pdo->prepare("
                INSERT INTO FF_BlockLocations
                    (block_id, location_id, order_in_block, distance_to_center_km)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$bestBlock['id'], $orphan['id'], $bestBlock['locationsCount'] + 1, $distToCenter]);

            $stmt = $pdo->prepare("UPDATE FF_Locations SET block_id = ? WHERE id = ?");
            $stmt->execute([$bestBlock['id'], $orphan['id']]);

            $stmt = $pdo->prepare("UPDATE FF_Blocks SET locations_count = locations_count + 1 WHERE id = ?");
            $stmt->execute([$bestBlock['id']]);

            $bestBlock['locationsCount']++;
            $bestBlock['locations'][] = $orphan;

            // Recalcular maxPairDistanceKm após adicionar órfão
            $maxPairDistance = 0;
            $blockLocs = $bestBlock['locations'];
            for ($i = 0; $i < count($blockLocs); $i++) {
                for ($j = $i + 1; $j < count($blockLocs); $j++) {
                    $pairDist = haversineDistance(
                        $blockLocs[$i]['latitude'], $blockLocs[$i]['longitude'],
                        $blockLocs[$j]['latitude'], $blockLocs[$j]['longitude']
                    );
                    $maxPairDistance = max($maxPairDistance, $pairDist);
                }
            }
            $bestBlock['maxPairDistanceKm'] = $maxPairDistance;

        } else {
            // Criar bloco individual para órfão
            // Nome simples com número (suporta infinitos blocos)
            $blockName = "Bloco " . $blockCounter;

            $stmt = $pdo->prepare("
                INSERT INTO FF_Blocks
                    (name, center_latitude, center_longitude, radius_km, locations_count, import_batch)
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([
                $blockName,
                $orphan['latitude'],
                $orphan['longitude'],
                0, // Raio = 0 para blocos com apenas 1 local
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
                'radiusKm' => 0,
                'maxPairDistanceKm' => 0, // Bloco com apenas 1 local
                'locationsCount' => 1,
                'locations' => [$orphan]
            ];

            $blockCounter++;
        }
    }

    return $blocks;
}

/**
 * Deletar TODOS os blocos e locais
 */
function handleDeleteAll($pdo) {
    $pdo->beginTransaction();

    try {
        // Deletar todos os relacionamentos
        $stmt = $pdo->prepare("DELETE FROM FF_BlockLocations");
        $stmt->execute();

        // Deletar todos os blocos
        $stmt = $pdo->prepare("DELETE FROM FF_Blocks");
        $stmt->execute();

        // Deletar todos os locais
        $stmt = $pdo->prepare("DELETE FROM FF_Locations");
        $stmt->execute();

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Todos os blocos e locais foram deletados'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao deletar todos os blocos',
            'message' => $e->getMessage()
        ]);
    }
}
