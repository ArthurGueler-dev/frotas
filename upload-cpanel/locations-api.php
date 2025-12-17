<?php
/**
 * API REST para gerenciar locais (FF_Locations)
 * Suporta: GET (listar/buscar), POST (criar batch), DELETE (excluir)
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
    // Configurar timeout de 3 segundos para conexão
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, $options);
    $pdo->exec("SET NAMES utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    // Pegar ID da URL se existir
    $id = null;
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
    }

    switch ($method) {
        case 'GET':
            handleGet($pdo, $id);
            break;

        case 'POST':
            handlePost($pdo);
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
    // Se for um GET e o erro for de conexão, retornar array vazio
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
 * GET - Listar todos os locais ou buscar por ID
 * Query params: importBatch, blockId, category
 */
function handleGet($pdo, $id) {
    if ($id) {
        // Buscar local específico
        $stmt = $pdo->prepare("
            SELECT
                l.id,
                l.name,
                l.address,
                l.latitude,
                l.longitude,
                l.category,
                l.import_batch,
                l.block_id,
                b.name as block_name,
                l.created_at,
                l.updated_at
            FROM FF_Locations l
            LEFT JOIN FF_Blocks b ON l.block_id = b.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($location) {
            // Converter latitude e longitude para float
            $location['latitude'] = floatval($location['latitude']);
            $location['longitude'] = floatval($location['longitude']);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'location' => $location
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Local não encontrado'
            ]);
        }
    } else {
        // Listar locais com filtros opcionais
        $where = [];
        $params = [];

        if (isset($_GET['importBatch']) && !empty($_GET['importBatch'])) {
            $where[] = "l.import_batch = ?";
            $params[] = $_GET['importBatch'];
        }

        if (isset($_GET['blockId']) && !empty($_GET['blockId'])) {
            $where[] = "l.block_id = ?";
            $params[] = intval($_GET['blockId']);
        }

        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $where[] = "l.category = ?";
            $params[] = $_GET['category'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT
                l.id,
                l.name,
                l.address,
                l.latitude,
                l.longitude,
                l.category,
                l.import_batch,
                l.block_id,
                b.name as block_name,
                l.created_at
            FROM FF_Locations l
            LEFT JOIN FF_Blocks b ON l.block_id = b.id
            $whereClause
            ORDER BY l.name ASC
        ");
        $stmt->execute($params);
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Converter latitude e longitude para float
        foreach ($locations as &$location) {
            $location['latitude'] = floatval($location['latitude']);
            $location['longitude'] = floatval($location['longitude']);
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'locations' => $locations,
            'total' => count($locations)
        ]);
    }
}

/**
 * POST - Inserir locais em batch
 * Body: { locations: [{name, address, latitude, longitude, category, importBatch}] }
 */
function handlePost($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['locations']) || !is_array($data['locations'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Array de locations é obrigatório'
        ]);
        return;
    }

    $locations = $data['locations'];
    $insertedIds = [];
    $errors = [];

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO FF_Locations
                (name, address, latitude, longitude, category, import_batch)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($locations as $index => $location) {
            // Validar campos obrigatórios
            if (empty($location['name']) || !isset($location['latitude']) || !isset($location['longitude'])) {
                $errors[] = "Linha $index: campos obrigatórios faltando (name, latitude, longitude)";
                continue;
            }

            $stmt->execute([
                $location['name'],
                isset($location['address']) ? $location['address'] : '',
                floatval($location['latitude']),
                floatval($location['longitude']),
                isset($location['category']) ? $location['category'] : null,
                isset($location['importBatch']) ? $location['importBatch'] : null
            ]);

            $insertedIds[] = $pdo->lastInsertId();
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'insertedIds' => $insertedIds,
            'total' => count($insertedIds),
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao inserir locais',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * DELETE - Remover local por ID
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
        // Buscar block_id antes de deletar
        $stmt = $pdo->prepare("SELECT block_id FROM FF_Locations WHERE id = ?");
        $stmt->execute([$id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$location) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Local não encontrado'
            ]);
            return;
        }

        $blockId = $location['block_id'];

        // Deletar relacionamento (cascade deveria fazer isso, mas fazemos explicitamente)
        $stmt = $pdo->prepare("DELETE FROM FF_BlockLocations WHERE location_id = ?");
        $stmt->execute([$id]);

        // Deletar local
        $stmt = $pdo->prepare("DELETE FROM FF_Locations WHERE id = ?");
        $stmt->execute([$id]);

        // Atualizar contador do bloco se necessário
        if ($blockId) {
            $stmt = $pdo->prepare("
                UPDATE FF_Blocks
                SET locations_count = (
                    SELECT COUNT(*) FROM FF_BlockLocations WHERE block_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$blockId, $blockId]);

            // Se bloco ficou vazio, deletar
            $stmt = $pdo->prepare("SELECT locations_count FROM FF_Blocks WHERE id = ?");
            $stmt->execute([$blockId]);
            $block = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($block && $block['locations_count'] == 0) {
                $stmt = $pdo->prepare("DELETE FROM FF_Blocks WHERE id = ?");
                $stmt->execute([$blockId]);
            }
        }

        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Local removido com sucesso'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao deletar local',
            'message' => $e->getMessage()
        ]);
    }
}
