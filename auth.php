<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configurações do banco de dados
$db_host = 'localhost';
$db_user = 'f137049_tool';  // Usar as credenciais do seu projeto
$db_pass = 'In9@1234qwer';
$db_name = 'f137049_in9aut';

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'Erro de conexão com o banco de dados'));
    exit;
}

// Apenas aceitar POST para login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'Método não permitido'));
    exit;
}

// Ler dados do POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Se não conseguir decodificar JSON, tentar dados form-urlencoded
if (!$data) {
    $data = $_POST;
}

$username = isset($data['username']) ? $data['username'] : '';
$password = isset($data['password']) ? $data['password'] : '';

// Validar campos obrigatórios
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Username e password são obrigatórios'));
    exit;
}

try {
    // Buscar usuário na tabela Users
    $stmt = $pdo->prepare("SELECT Username, Password, IsAdmin, IsRoot, Aplicativos FROM Users WHERE Username = ?");
    $stmt->execute(array($username));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(array('error' => 'Usuário não encontrado'));
        exit;
    }
    
    // Verificar senha (hash SHA-256)
    $passwordHash = hash('sha256', $password);
    
    if ($passwordHash !== $user['Password']) {
        http_response_code(401);
        echo json_encode(array('error' => 'Senha incorreta'));
        exit;
    }
    
    // Determinar role baseado nos flags
    $role = 'operator'; // Default
    if ($user['IsRoot']) {
        $role = 'root';
    } elseif ($user['IsAdmin']) {
        $role = 'admin';
    }
    
    // Login bem-sucedido - retornar dados do usuário
    $userData = array(
        'id' => $user['Username'] . '_' . time(),
        'username' => $user['Username'],
        'name' => ucfirst($user['Username']), // Capitalizar primeira letra
        'role' => $role,
        'isAdmin' => (bool)$user['IsAdmin'],
        'isRoot' => (bool)$user['IsRoot'],
        'aplicativos' => $user['Aplicativos'],
        'active' => true,
        'lastLogin' => date('Y-m-d\TH:i:s\Z'),
        'createdAt' => date('Y-m-d\TH:i:s\Z')
    );
    
    echo json_encode(array(
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'user' => $userData
    ));
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'Erro interno do servidor'));
}
?> 