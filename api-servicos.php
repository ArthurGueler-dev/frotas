<?php
/**
 * API para gerenciar Serviços/Produtos/Mão de Obra
 *
 * Métodos suportados:
 * - GET: Listar todos os serviços ou buscar por ID/código
 * - POST: Criar novo serviço
 * - PUT: Atualizar serviço existente
 * - DELETE: Desativar serviço
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Cache-Control');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config-db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Conectar ao banco usando PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 3
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;

        case 'POST':
            handlePost($pdo);
            break;

        case 'PUT':
            handlePut($pdo);
            break;

        case 'DELETE':
            handleDelete($pdo);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }

} catch (Exception $e) {
    error_log('Erro na API de serviços: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * GET - Listar serviços
 */
function handleGet($pdo) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $ativo = isset($_GET['ativo']) ? intval($_GET['ativo']) : 1;
    $search = isset($_GET['search']) ? $_GET['search'] : null;

    if ($id) {
        // Buscar por ID
        $sql = "SELECT * FROM servicos WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $servico = $stmt->fetch();

        if ($servico) {
            echo json_encode(['success' => true, 'data' => $servico]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Serviço não encontrado']);
        }
        return;
    }

    if ($codigo) {
        // Buscar por código
        $sql = "SELECT * FROM servicos WHERE codigo = :codigo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $servico = $stmt->fetch();

        if ($servico) {
            echo json_encode(['success' => true, 'data' => $servico]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Serviço não encontrado']);
        }
        return;
    }

    // Listar todos ou filtrar
    $sql = "SELECT * FROM servicos WHERE 1=1";
    $params = [];

    if ($tipo) {
        $sql .= " AND tipo = :tipo";
        $params[':tipo'] = $tipo;
    }

    if ($ativo !== null) {
        $sql .= " AND ativo = :ativo";
        $params[':ativo'] = $ativo;
    }

    if ($search) {
        $sql .= " AND (nome LIKE :search OR codigo LIKE :search OR descricao LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $servicos = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $servicos,
        'total' => count($servicos)
    ]);
}

/**
 * POST - Criar novo serviço
 */
function handlePost($pdo) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Dados JSON inválidos');
    }

    // Validar campos obrigatórios
    if (empty($data['codigo']) || empty($data['nome']) || empty($data['tipo'])) {
        throw new Exception('Campos obrigatórios: codigo, nome, tipo');
    }

    // Verificar se código já existe
    $sqlCheck = "SELECT id FROM servicos WHERE codigo = :codigo";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':codigo' => $data['codigo']]);

    if ($stmtCheck->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Código já existe']);
        return;
    }

    // Inserir serviço (apenas campos que existem na tabela)
    $sql = "INSERT INTO servicos
            (codigo, nome, tipo, valor_padrao, ocorrencia_padrao, ativo)
            VALUES
            (:codigo, :nome, :tipo, :valor_padrao, :ocorrencia_padrao, :ativo)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':codigo' => $data['codigo'],
        ':nome' => $data['nome'],
        ':tipo' => $data['tipo'],
        ':valor_padrao' => $data['valor_padrao'] ?? 0.00,
        ':ocorrencia_padrao' => $data['ocorrencia_padrao'] ?? 'Corretiva',
        ':ativo' => $data['ativo'] ?? 1
    ]);

    $id = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Serviço criado com sucesso',
        'id' => $id
    ]);
}

/**
 * PUT - Atualizar serviço
 */
function handlePut($pdo) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['id'])) {
        throw new Exception('ID do serviço é obrigatório');
    }

    // Verificar se serviço existe
    $sqlCheck = "SELECT id FROM servicos WHERE id = :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':id' => $data['id']]);

    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Serviço não encontrado']);
        return;
    }

    // Atualizar serviço (apenas campos que existem na tabela)
    $sql = "UPDATE servicos SET
            codigo = :codigo,
            nome = :nome,
            tipo = :tipo,
            valor_padrao = :valor_padrao,
            ocorrencia_padrao = :ocorrencia_padrao,
            ativo = :ativo
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $data['id'],
        ':codigo' => $data['codigo'],
        ':nome' => $data['nome'],
        ':tipo' => $data['tipo'],
        ':valor_padrao' => $data['valor_padrao'] ?? 0.00,
        ':ocorrencia_padrao' => $data['ocorrencia_padrao'] ?? 'Corretiva',
        ':ativo' => $data['ativo'] ?? 1
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Serviço atualizado com sucesso'
    ]);
}

/**
 * DELETE - Desativar serviço
 */
function handleDelete($pdo) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$id) {
        throw new Exception('ID do serviço é obrigatório');
    }

    // Desativar ao invés de deletar
    $sql = "UPDATE servicos SET ativo = 0 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Serviço desativado com sucesso'
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Serviço não encontrado'
        ]);
    }
}
?>
