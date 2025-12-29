<?php
/**
 * API de Usuários - Sistema de Frotas (MySQLi version)
 * Compatível com PHP 5.6+
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

ini_set('display_errors', 0);
error_reporting(0);

// Carregar configuração do banco
require_once __DIR__ . '/config-db.php';

// Função para conectar ao banco
function getConnection() {
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $user = defined('DB_USER') ? DB_USER : 'f137049_tool';
    $pass = defined('DB_PASS') ? DB_PASS : 'In9@1234qwer';
    $dbname = defined('DB_NAME') ? DB_NAME : 'f137049_in9aut';

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        return null;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// Conectar
$conn = getConnection();
if (!$conn) {
    echo json_encode(array('success' => false, 'error' => 'Erro de conexão com banco de dados'));
    exit();
}

// Roteamento
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ============================================================================
// HANDLERS
// ============================================================================

if ($method === 'POST' && $action === 'login') {
    handleLogin($conn);
} elseif ($method === 'POST' && $action === 'set_password') {
    handleSetPassword($conn);
} elseif ($method === 'POST' && $action === 'register') {
    handleRegister($conn);
} elseif ($method === 'GET' && $action === 'list') {
    handleList($conn);
} else {
    echo json_encode(array('success' => false, 'error' => 'Ação inválida'));
}

$conn->close();

// ============================================================================
// FUNÇÕES
// ============================================================================

function handleLogin($conn) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $username = isset($data['username']) ? trim($data['username']) : '';
    $password = isset($data['password']) ? trim($data['password']) : '';

    if (empty($username) || empty($password)) {
        sendError('Username e senha são obrigatórios', 400);
    }

    // Buscar usuário
    $stmt = $conn->prepare("SELECT id, username, password_hash, full_name, email, user_type, status FROM FF_Users WHERE username = ?");
    if (!$stmt) {
        sendError('Erro ao preparar query', 500);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendError('Usuário ou senha incorretos', 401);
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verificar status
    if ($user['status'] === 'inativo') {
        sendError('Usuário inativo. Entre em contato com o administrador.', 403);
    }

    // Se password_hash é NULL, usuário precisa definir senha
    if ($user['password_hash'] === null || $user['password_hash'] === '') {
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
        exit();
    }

    // Verificar senha
    if (!password_verify($password, $user['password_hash'])) {
        sendError('Usuário ou senha incorretos', 401);
    }

    // Gerar token
    $token = bin2hex(openssl_random_pseudo_bytes(32));

    // Atualizar last_login
    $stmt = $conn->prepare("UPDATE FF_Users SET last_login_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $stmt->close();

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
            'status' => $user['status']
        )
    ));
}

function handleSetPassword($conn) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $userId = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $newPassword = isset($data['new_password']) ? trim($data['new_password']) : '';

    if (empty($userId) || empty($newPassword)) {
        sendError('ID do usuário e nova senha são obrigatórios', 400);
    }

    if (strlen($newPassword) < 4) {
        sendError('A senha deve ter no mínimo 4 caracteres', 400);
    }

    // Verificar se usuário existe
    $stmt = $conn->prepare("SELECT id FROM FF_Users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendError('Usuário não encontrado', 404);
    }
    $stmt->close();

    // Gerar hash da senha
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Atualizar senha
    $stmt = $conn->prepare("UPDATE FF_Users SET password_hash = ?, status = 'ativo', password_changed_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $passwordHash, $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(array(
        'success' => true,
        'message' => 'Senha definida com sucesso! Faça login novamente.'
    ));
}

function handleRegister($conn) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $username = isset($data['username']) ? trim($data['username']) : '';
    $fullName = isset($data['full_name']) ? trim($data['full_name']) : '';
    $email = isset($data['email']) ? trim($data['email']) : null;
    $password = isset($data['password']) ? trim($data['password']) : null;
    $userType = isset($data['user_type']) ? $data['user_type'] : 'usuario';

    if (empty($username) || empty($fullName)) {
        sendError('Username e nome completo são obrigatórios', 400);
    }

    // Verificar se username já existe
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM FF_Users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        sendError('Username já está em uso', 409);
    }
    $stmt->close();

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
    $stmt = $conn->prepare("INSERT INTO FF_Users (username, password_hash, full_name, email, user_type, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $username, $passwordHash, $fullName, $email, $userType, $status);
    $stmt->execute();
    $userId = $conn->insert_id;
    $stmt->close();

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

function handleList($conn) {
    $result = $conn->query("SELECT id, username, full_name, email, user_type, status, last_login_at, created_at FROM FF_Users ORDER BY created_at DESC");

    $users = array();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(array(
        'success' => true,
        'count' => count($users),
        'data' => $users
    ));
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(array(
        'success' => false,
        'error' => $message
    ));
    exit();
}
?>
