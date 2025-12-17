<?php
/**
 * API REST para gerenciar modelos de veículos
 * Suporta: GET (listar/buscar), POST (criar), PUT (atualizar), DELETE (excluir)
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

    // Pegar ID da URL se existir (para PUT e DELETE)
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

        case 'PUT':
            handlePut($pdo, $id);
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
 * GET - Listar todos os modelos ou buscar por ID
 */
function handleGet($pdo, $id) {
    if ($id) {
        // Buscar modelo específico
        $stmt = $pdo->prepare("
            SELECT
                m.id,
                m.marca,
                m.modelo,
                m.ano,
                m.tipo,
                m.motor,
                m.observacoes,
                m.created_at,
                m.updated_at,
                (SELECT COUNT(*) FROM Vehicles v WHERE v.VehicleName LIKE CONCAT('%', m.modelo, '%')) as qtdVeiculos
            FROM FF_VehicleModels m
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($modelo) {
            $modelo['qtdVeiculos'] = intval($modelo['qtdVeiculos']);
            echo json_encode($modelo);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Modelo não encontrado']);
        }
    } else {
        // Listar todos os modelos
        $stmt = $pdo->query("
            SELECT
                m.id,
                m.marca,
                m.modelo,
                m.ano,
                m.tipo,
                m.motor,
                m.observacoes,
                m.created_at,
                m.updated_at,
                (SELECT COUNT(*) FROM Vehicles v WHERE v.VehicleName LIKE CONCAT('%', m.modelo, '%')) as qtdVeiculos
            FROM FF_VehicleModels m
            ORDER BY m.marca, m.modelo, m.ano
        ");
        $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Converter qtdVeiculos para inteiro
        foreach ($modelos as &$modelo) {
            $modelo['qtdVeiculos'] = intval($modelo['qtdVeiculos']);
        }

        echo json_encode($modelos);
    }
}

/**
 * POST - Criar novo modelo
 */
function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validar dados obrigatórios
    if (empty($data['marca']) || empty($data['modelo']) || empty($data['ano']) || empty($data['tipo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Marca, modelo, ano e tipo são obrigatórios']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO FF_VehicleModels (marca, modelo, ano, tipo, motor, observacoes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['marca'],
            $data['modelo'],
            $data['ano'],
            $data['tipo'],
            isset($data['motor']) ? $data['motor'] : null,
            isset($data['observacoes']) ? $data['observacoes'] : null
        ]);

        $novoId = $pdo->lastInsertId();

        // Buscar o modelo criado
        $stmt = $pdo->prepare("SELECT * FROM FF_VehicleModels WHERE id = ?");
        $stmt->execute([$novoId]);
        $novoModelo = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        echo json_encode($novoModelo);

    } catch (PDOException $e) {
        // Verificar se é erro de duplicação
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['error' => 'Já existe um modelo com essa combinação de marca, modelo e ano']);
        } else {
            throw $e;
        }
    }
}

/**
 * PUT - Atualizar modelo existente
 */
function handlePut($pdo, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID é obrigatório']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    // Validar dados obrigatórios
    if (empty($data['marca']) || empty($data['modelo']) || empty($data['ano']) || empty($data['tipo'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Marca, modelo, ano e tipo são obrigatórios']);
        return;
    }

    // Verificar se o modelo existe
    $stmt = $pdo->prepare("SELECT id FROM FF_VehicleModels WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Modelo não encontrado']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE FF_VehicleModels
            SET marca = ?, modelo = ?, ano = ?, tipo = ?, motor = ?, observacoes = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['marca'],
            $data['modelo'],
            $data['ano'],
            $data['tipo'],
            isset($data['motor']) ? $data['motor'] : null,
            isset($data['observacoes']) ? $data['observacoes'] : null,
            $id
        ]);

        // Buscar o modelo atualizado
        $stmt = $pdo->prepare("SELECT * FROM FF_VehicleModels WHERE id = ?");
        $stmt->execute([$id]);
        $modeloAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($modeloAtualizado);

    } catch (PDOException $e) {
        // Verificar se é erro de duplicação
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['error' => 'Já existe um modelo com essa combinação de marca, modelo e ano']);
        } else {
            throw $e;
        }
    }
}

/**
 * DELETE - Excluir modelo
 */
function handleDelete($pdo, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID é obrigatório']);
        return;
    }

    // Verificar se o modelo existe
    $stmt = $pdo->prepare("SELECT id, marca, modelo FROM FF_VehicleModels WHERE id = ?");
    $stmt->execute([$id]);
    $modelo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$modelo) {
        http_response_code(404);
        echo json_encode(['error' => 'Modelo não encontrado']);
        return;
    }

    // Verificar se há veículos associados
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Vehicles WHERE VehicleName LIKE ?");
    $stmt->execute(['%' . $modelo['modelo'] . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        http_response_code(409);
        echo json_encode([
            'error' => "Não é possível excluir este modelo pois existem {$result['count']} veículo(s) associado(s) a ele"
        ]);
        return;
    }

    // Excluir modelo
    $stmt = $pdo->prepare("DELETE FROM FF_VehicleModels WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['message' => 'Modelo excluído com sucesso']);
}
?>
