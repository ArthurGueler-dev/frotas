<?php
/**
 * API de Autenticação
 * Gerencia login, logout e verificação de sessão
 *
 * Endpoints:
 * - POST /auth-api.php { action: 'login', username, password }
 * - POST /auth-api.php { action: 'logout' }
 * - GET  /auth-api.php?action=verify
 * - GET  /auth-api.php?action=status
 *
 * Tabela: aaa_usuario
 */

// Iniciar sessão
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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

    if ($method === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : 'status';

        if ($action === 'verify') {
            handleVerify();
        } elseif ($action === 'status') {
            handleStatus();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
            exit;
        }

        $action = $data['action'];

        if ($action === 'login') {
            handleLogin($pdo, $data);
        } elseif ($action === 'logout') {
            handleLogout();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor',
        'message' => $e->getMessage()
    ]);
}

/**
 * Fazer login
 */
function handleLogin($pdo, $data) {
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Usuário e senha são obrigatórios'
        ]);
        return;
    }

    $username = trim($data['username']);
    $password = $data['password'];

    try {
        // Buscar usuário por nome
        $stmt = $pdo->prepare("
            SELECT id, nome, senha, ativo, tipo_usuario, tutorial_concluido
            FROM aaa_usuario
            WHERE nome = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Usuário ou senha incorretos'
            ]);
            return;
        }

        // Verificar se usuário está ativo
        if ($user['ativo'] != 1) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Usuário desativado. Entre em contato com o administrador.'
            ]);
            return;
        }

        // Verificar senha
        // IMPORTANTE: Assumindo que senhas estão em hash (password_hash)
        // Se as senhas estiverem em texto plano (não recomendado), usar: $password === $user['senha']
        $passwordValid = password_verify($password, $user['senha']);

        // Fallback para senha em texto plano (TEMPORÁRIO - deve ser migrado para hash)
        if (!$passwordValid && $password === $user['senha']) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Usuário ou senha incorretos'
            ]);
            return;
        }

        // Login bem-sucedido - criar sessão
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_tipo'] = $user['tipo_usuario'];
        $_SESSION['tutorial_concluido'] = $user['tutorial_concluido'];
        $_SESSION['login_time'] = time();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'user' => [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'tipo_usuario' => $user['tipo_usuario'],
                'tutorial_concluido' => $user['tutorial_concluido']
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao processar login',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Fazer logout
 */
function handleLogout() {
    session_unset();
    session_destroy();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Logout realizado com sucesso'
    ]);
}

/**
 * Verificar se usuário está logado
 */
function handleVerify() {
    if (isset($_SESSION['usuario_id'])) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['usuario_id'],
                'nome' => $_SESSION['usuario_nome'],
                'tipo_usuario' => $_SESSION['usuario_tipo'],
                'tutorial_concluido' => $_SESSION['tutorial_concluido']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'message' => 'Não autenticado'
        ]);
    }
}

/**
 * Verificar status da sessão (sem erro se não logado)
 */
function handleStatus() {
    $authenticated = isset($_SESSION['usuario_id']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'authenticated' => $authenticated,
        'user' => $authenticated ? [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'tipo_usuario' => $_SESSION['usuario_tipo'],
            'tutorial_concluido' => $_SESSION['tutorial_concluido']
        ] : null
    ]);
}
