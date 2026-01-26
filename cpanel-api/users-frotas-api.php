<?php
/**
 * API de Usuários - FleetFlow
 *
 * Endpoints:
 * - POST /users-api.php?action=login          - Login de usuário
 * - POST /users-api.php?action=register       - Cadastrar novo usuário
 * - POST /users-api.php?action=set_password   - Definir senha (primeiro acesso)
 * - GET  /users-api.php?action=list           - Listar usuários (admin only)
 * - GET  /users-api.php?action=verify         - Verificar token
 * - PUT  /users-api.php?id=X                  - Atualizar usuário
 * - DELETE /users-api.php?id=X                - Deletar usuário
 */

// CRITICAL: CORS headers MUST be sent before ANY output
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
@header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
@header('Access-Control-Max-Age: 86400');
@header('Access-Control-Allow-Credentials: false');

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    @header('Content-Type: text/plain; charset=utf-8');
    @header('Content-Length: 0');
    http_response_code(204);
    die();
}

@header('Content-Type: application/json; charset=utf-8');

// Configuração do banco de dados
require_once 'config-db.php';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Erro de conexão com banco de dados'
    ));
    exit;
}

// Roteamento
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            handleList($pdo);
        } elseif ($action === 'verify') {
            handleVerify($pdo);
        } else {
            sendError('Ação inválida', 400);
        }
        break;

    case 'POST':
        if ($action === 'login') {
            handleLogin($pdo);
        } elseif ($action === 'register') {
            handleRegister($pdo);
        } elseif ($action === 'set_password') {
            handleSetPassword($pdo);
        } else {
            sendError('Ação inválida', 400);
        }
        break;

    case 'PUT':
        handleUpdate($pdo);
        break;

    case 'DELETE':
        handleDelete($pdo);
        break;

    default:
        sendError('Método não permitido', 405);
}

// ============================================================================
// FUNÇÕES
// ============================================================================

/**
 * LOGIN - Autenticar usuário
 */
function handleLogin($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $username = isset($data['username']) ? $data['username'] : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if (empty($username) || empty($password)) {
        sendError('Username e senha são obrigatórios', 400);
    }

    // Buscar usuário
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.password_hash, u.full_name, u.email, u.user_type, u.status, u.last_login_at, u.perfil_id,
               p.nome as perfil_nome
        FROM FF_Users u
        LEFT JOIN FF_Perfis p ON u.perfil_id = p.id
        WHERE u.username = :username
    ");
    $stmt->execute(array('username' => $username));
    $user = $stmt->fetch();

    if (!$user) {
        sendError('Usuário ou senha incorretos', 401);
    }

    // Verificar se usuário está ativo ou pendente (precisa criar senha)
    if ($user['status'] === 'inativo') {
        sendError('Usuário inativo. Entre em contato com o administrador.', 403);
    }

    // Se password_hash é NULL, usuário precisa definir senha
    if ($user['password_hash'] === null) {
        echo json_encode(array(
            'success' => false,
            'needs_password' => true,
            'message' => 'Você precisa criar uma senha na primeira vez',
            'user' => array(
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name']
            )
        ));
        exit;
    }

    // Verificar senha
    if (!password_verify($password, $user['password_hash'])) {
        sendError('Usuário ou senha incorretos', 401);
    }

    // Gerar token (UUID simples)
    $token = bin2hex(openssl_random_pseudo_bytes(32));

    // Atualizar last_login
    $stmt = $pdo->prepare("UPDATE FF_Users SET last_login_at = NOW() WHERE id = :id");
    $stmt->execute(array('id' => $user['id']));

    // Buscar permissões do usuário
    $permissoes = array();
    if ($user['perfil_id']) {
        $stmtPerm = $pdo->prepare("SELECT pagina, pode_acessar, pode_editar FROM FF_Perfil_Permissoes WHERE perfil_id = ?");
        $stmtPerm->execute(array($user['perfil_id']));
        $perms = $stmtPerm->fetchAll();
        foreach ($perms as $p) {
            $permissoes[$p['pagina']] = array(
                'pode_acessar' => (bool)$p['pode_acessar'],
                'pode_editar' => (bool)$p['pode_editar']
            );
        }
    }

    // Retornar sucesso
    echo json_encode(array(
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'token' => $token,
        'user' => array(
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'status' => $user['status'],
            'perfil_id' => $user['perfil_id'],
            'perfil_nome' => $user['perfil_nome']
        ),
        'permissoes' => $permissoes
    ));
}

/**
 * SET PASSWORD - Definir senha no primeiro acesso
 */
function handleSetPassword($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $userId = isset($data['user_id']) ? $data['user_id'] : 0;
    $newPassword = isset($data['new_password']) ? $data['new_password'] : '';

    if (empty($userId) || empty($newPassword)) {
        sendError('ID do usuário e nova senha são obrigatórios', 400);
    }

    if (strlen($newPassword) < 4) {
        sendError('A senha deve ter no mínimo 4 caracteres', 400);
    }

    // Verificar se usuário existe
    $stmt = $pdo->prepare("SELECT id, password_hash FROM FF_Users WHERE id = :id");
    $stmt->execute(array('id' => $userId));
    $user = $stmt->fetch();

    if (!$user) {
        sendError('Usuário não encontrado', 404);
    }

    // Gerar hash da senha
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Atualizar senha e status
    $stmt = $pdo->prepare("
        UPDATE FF_Users
        SET password_hash = :hash,
            status = 'ativo',
            password_changed_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute(array(
        'hash' => $passwordHash,
        'id' => $userId
    ));

    echo json_encode(array(
        'success' => true,
        'message' => 'Senha definida com sucesso! Faça login novamente.'
    ));
}

/**
 * REGISTER - Cadastrar novo usuário
 */
function handleRegister($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $username = isset($data['username']) ? $data['username'] : '';
    $fullName = isset($data['full_name']) ? $data['full_name'] : '';
    $email = isset($data['email']) ? $data['email'] : null;
    $password = isset($data['password']) ? $data['password'] : null;
    $userType = isset($data['user_type']) ? $data['user_type'] : 'usuario';

    if (empty($username) || empty($fullName)) {
        sendError('Username e nome completo são obrigatórios', 400);
    }

    // Verificar se username já existe
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM FF_Users WHERE username = :username");
    $stmt->execute(array('username' => $username));
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        sendError('Username já está em uso', 409);
    }

    // Hash da senha se fornecida
    $passwordHash = null;
    $status = 'pendente';

    if (!empty($password)) {
        if (strlen($password) < 4) {
            sendError('A senha deve ter no mínimo 4 caracteres', 400);
        }
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $status = 'ativo';
    }

    // Inserir usuário
    $stmt = $pdo->prepare("
        INSERT INTO FF_Users (username, password_hash, full_name, email, user_type, status, password_changed_at)
        VALUES (:username, :password_hash, :full_name, :email, :user_type, :status, :password_changed_at)
    ");
    $stmt->execute(array(
        'username' => $username,
        'password_hash' => $passwordHash,
        'full_name' => $fullName,
        'email' => $email,
        'user_type' => $userType,
        'status' => $status,
        'password_changed_at' => $passwordHash ? date('Y-m-d H:i:s') : null
    ));

    $userId = $pdo->lastInsertId();

    echo json_encode(array(
        'success' => true,
        'message' => 'Usuário cadastrado com sucesso',
        'user' => array(
            'id' => $userId,
            'username' => $username,
            'full_name' => $fullName,
            'status' => $status
        )
    ));
}

/**
 * LIST - Listar usuários
 */
function handleList($pdo) {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.full_name, u.email, u.user_type, u.status,
               u.last_login_at, u.created_at, u.perfil_id,
               p.nome as perfil_nome
        FROM FF_Users u
        LEFT JOIN FF_Perfis p ON u.perfil_id = p.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();

    echo json_encode(array(
        'success' => true,
        'count' => count($users),
        'data' => $users
    ));
}

/**
 * VERIFY - Verificar token (simplificado - apenas verifica se usuário existe)
 */
function handleVerify($pdo) {
    $userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

    if (empty($userId)) {
        sendError('User ID não fornecido', 400);
    }

    $stmt = $pdo->prepare("
        SELECT id, username, full_name, email, user_type, status
        FROM FF_Users
        WHERE id = :id AND status = 'ativo'
    ");
    $stmt->execute(array('id' => $userId));
    $user = $stmt->fetch();

    if (!$user) {
        sendError('Usuário não encontrado ou inativo', 404);
    }

    echo json_encode(array(
        'success' => true,
        'user' => $user
    ));
}

/**
 * UPDATE - Atualizar usuário
 */
function handleUpdate($pdo) {
    $userId = isset($_GET['id']) ? $_GET['id'] : 0;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($userId)) {
        sendError('ID do usuário não fornecido', 400);
    }

    // Verificar se usuário existe
    $stmt = $pdo->prepare("SELECT id FROM FF_Users WHERE id = :id");
    $stmt->execute(array('id' => $userId));
    if (!$stmt->fetch()) {
        sendError('Usuário não encontrado', 404);
    }

    // Campos permitidos para atualização
    $allowedFields = array('full_name', 'email', 'user_type', 'status', 'perfil_id');
    $updates = array();
    $params = array('id' => $userId);

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = :$field";
            $params[$field] = $data[$field];
        }
    }

    if (empty($updates)) {
        sendError('Nenhum campo para atualizar', 400);
    }

    // Executar update
    $sql = "UPDATE FF_Users SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(array(
        'success' => true,
        'message' => 'Usuário atualizado com sucesso'
    ));
}

/**
 * DELETE - Deletar usuário
 */
function handleDelete($pdo) {
    $userId = isset($_GET['id']) ? $_GET['id'] : 0;

    if (empty($userId)) {
        sendError('ID do usuário não fornecido', 400);
    }

    // Não permitir deletar o próprio admin
    if ($userId == 1) {
        sendError('Não é possível deletar o usuário admin principal', 403);
    }

    $stmt = $pdo->prepare("DELETE FROM FF_Users WHERE id = :id");
    $stmt->execute(array('id' => $userId));

    if ($stmt->rowCount() === 0) {
        sendError('Usuário não encontrado', 404);
    }

    echo json_encode(array(
        'success' => true,
        'message' => 'Usuário deletado com sucesso'
    ));
}

/**
 * Enviar erro em JSON
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(array(
        'success' => false,
        'error' => $message
    ));
    exit;
}
