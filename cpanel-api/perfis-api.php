<?php
/**
 * API de Perfis e Permissões
 *
 * Endpoints:
 * GET  ?action=list                    - Listar todos os perfis
 * GET  ?action=get&id=X                - Obter perfil específico com permissões
 * GET  ?action=permissoes&perfil_id=X  - Listar permissões de um perfil
 * GET  ?action=paginas                 - Listar todas as páginas do sistema
 * GET  ?action=user_permissions&user_id=X - Obter permissões de um usuário
 * POST ?action=create                  - Criar novo perfil
 * PUT  ?action=update&id=X             - Atualizar perfil
 * PUT  ?action=update_permissoes       - Atualizar permissões de um perfil
 * DELETE ?id=X                         - Deletar perfil
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Erro de conexão'));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Páginas do sistema
$PAGINAS_SISTEMA = array(
    'dashboard' => 'Dashboard',
    'veiculos' => 'Veículos',
    'motoristas' => 'Motoristas',
    'manutencao' => 'Manutenção',
    'lancar-os' => 'Lançar OS',
    'planos-manutencao' => 'Planos de Manutenção',
    'pecas' => 'Peças',
    'servicos' => 'Serviços',
    'modelos' => 'Modelos',
    'rotas' => 'Rotas',
    'otimizador' => 'Otimizador de Rotas',
    'relatorios' => 'Relatórios',
    'usuarios' => 'Usuários',
    'configuracoes' => 'Configurações'
);

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            listarPerfis($pdo);
        } elseif ($action === 'get') {
            obterPerfil($pdo);
        } elseif ($action === 'permissoes') {
            listarPermissoes($pdo);
        } elseif ($action === 'paginas') {
            listarPaginas($PAGINAS_SISTEMA);
        } elseif ($action === 'user_permissions') {
            obterPermissoesUsuario($pdo);
        } else {
            listarPerfis($pdo);
        }
        break;

    case 'POST':
        if ($action === 'create') {
            criarPerfil($pdo, $PAGINAS_SISTEMA);
        } else {
            sendError('Ação inválida', 400);
        }
        break;

    case 'PUT':
        if ($action === 'update') {
            atualizarPerfil($pdo);
        } elseif ($action === 'update_permissoes') {
            atualizarPermissoes($pdo);
        } else {
            sendError('Ação inválida', 400);
        }
        break;

    case 'DELETE':
        deletarPerfil($pdo);
        break;

    default:
        sendError('Método não permitido', 405);
}

// ============================================================================
// FUNÇÕES
// ============================================================================

function listarPerfis($pdo) {
    $stmt = $pdo->query("
        SELECT p.*,
               (SELECT COUNT(*) FROM FF_Users u WHERE u.perfil_id = p.id) as total_usuarios,
               (SELECT COUNT(*) FROM FF_Perfil_Permissoes pp WHERE pp.perfil_id = p.id AND pp.pode_acessar = 1) as total_paginas
        FROM FF_Perfis p
        ORDER BY p.id
    ");
    $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'success' => true,
        'count' => count($perfis),
        'data' => $perfis
    ), JSON_UNESCAPED_UNICODE);
}

function obterPerfil($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        sendError('ID do perfil é obrigatório', 400);
    }

    // Buscar perfil
    $stmt = $pdo->prepare("SELECT * FROM FF_Perfis WHERE id = ?");
    $stmt->execute(array($id));
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$perfil) {
        sendError('Perfil não encontrado', 404);
    }

    // Buscar permissões
    $stmt = $pdo->prepare("SELECT pagina, pode_acessar, pode_editar FROM FF_Perfil_Permissoes WHERE perfil_id = ?");
    $stmt->execute(array($id));
    $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $perfil['permissoes'] = $permissoes;

    // Contar usuários com este perfil
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FF_Users WHERE perfil_id = ?");
    $stmt->execute(array($id));
    $perfil['total_usuarios'] = (int)$stmt->fetchColumn();

    echo json_encode(array(
        'success' => true,
        'data' => $perfil
    ), JSON_UNESCAPED_UNICODE);
}

function listarPermissoes($pdo) {
    $perfilId = isset($_GET['perfil_id']) ? (int)$_GET['perfil_id'] : 0;

    if (!$perfilId) {
        sendError('ID do perfil é obrigatório', 400);
    }

    $stmt = $pdo->prepare("SELECT pagina, pode_acessar, pode_editar FROM FF_Perfil_Permissoes WHERE perfil_id = ?");
    $stmt->execute(array($perfilId));
    $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Converter para objeto com chave = pagina
    $permissoesObj = array();
    foreach ($permissoes as $p) {
        $permissoesObj[$p['pagina']] = array(
            'pode_acessar' => (bool)$p['pode_acessar'],
            'pode_editar' => (bool)$p['pode_editar']
        );
    }

    echo json_encode(array(
        'success' => true,
        'perfil_id' => $perfilId,
        'permissoes' => $permissoesObj
    ), JSON_UNESCAPED_UNICODE);
}

function listarPaginas($paginas) {
    $lista = array();
    foreach ($paginas as $key => $nome) {
        $lista[] = array('key' => $key, 'nome' => $nome);
    }

    echo json_encode(array(
        'success' => true,
        'paginas' => $lista
    ), JSON_UNESCAPED_UNICODE);
}

function obterPermissoesUsuario($pdo) {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if (!$userId) {
        sendError('ID do usuário é obrigatório', 400);
    }

    // Buscar perfil do usuário
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.perfil_id, p.nome as perfil_nome
                           FROM FF_Users u
                           LEFT JOIN FF_Perfis p ON u.perfil_id = p.id
                           WHERE u.id = ?");
    $stmt->execute(array($userId));
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        sendError('Usuário não encontrado', 404);
    }

    // Se não tem perfil, não tem permissões
    if (!$usuario['perfil_id']) {
        echo json_encode(array(
            'success' => true,
            'usuario' => $usuario,
            'permissoes' => array(),
            'mensagem' => 'Usuário sem perfil atribuído'
        ), JSON_UNESCAPED_UNICODE);
        return;
    }

    // Buscar permissões do perfil
    $stmt = $pdo->prepare("SELECT pagina, pode_acessar, pode_editar FROM FF_Perfil_Permissoes WHERE perfil_id = ?");
    $stmt->execute(array($usuario['perfil_id']));
    $permissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $permissoesObj = array();
    foreach ($permissoes as $p) {
        $permissoesObj[$p['pagina']] = array(
            'pode_acessar' => (bool)$p['pode_acessar'],
            'pode_editar' => (bool)$p['pode_editar']
        );
    }

    echo json_encode(array(
        'success' => true,
        'usuario' => $usuario,
        'permissoes' => $permissoesObj
    ), JSON_UNESCAPED_UNICODE);
}

function criarPerfil($pdo, $paginas) {
    $data = json_decode(file_get_contents('php://input'), true);

    $nome = isset($data['nome']) ? trim($data['nome']) : '';
    $descricao = isset($data['descricao']) ? trim($data['descricao']) : '';

    if (empty($nome)) {
        sendError('Nome do perfil é obrigatório', 400);
    }

    // Verificar se já existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FF_Perfis WHERE nome = ?");
    $stmt->execute(array($nome));
    if ($stmt->fetchColumn() > 0) {
        sendError('Já existe um perfil com este nome', 409);
    }

    // Criar perfil
    $stmt = $pdo->prepare("INSERT INTO FF_Perfis (nome, descricao) VALUES (?, ?)");
    $stmt->execute(array($nome, $descricao));
    $perfilId = $pdo->lastInsertId();

    // Criar permissões vazias para todas as páginas
    $stmtPerm = $pdo->prepare("INSERT INTO FF_Perfil_Permissoes (perfil_id, pagina, pode_acessar, pode_editar) VALUES (?, ?, 0, 0)");
    foreach ($paginas as $paginaKey => $paginaNome) {
        $stmtPerm->execute(array($perfilId, $paginaKey));
    }

    echo json_encode(array(
        'success' => true,
        'message' => 'Perfil criado com sucesso',
        'id' => $perfilId
    ), JSON_UNESCAPED_UNICODE);
}

function atualizarPerfil($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$id) {
        sendError('ID do perfil é obrigatório', 400);
    }

    // Não permitir alterar perfil Administrador (id=1)
    if ($id == 1 && isset($data['nome']) && $data['nome'] !== 'Administrador') {
        sendError('Não é permitido alterar o nome do perfil Administrador', 403);
    }

    $campos = array();
    $valores = array();

    if (isset($data['nome'])) {
        $campos[] = 'nome = ?';
        $valores[] = trim($data['nome']);
    }
    if (isset($data['descricao'])) {
        $campos[] = 'descricao = ?';
        $valores[] = trim($data['descricao']);
    }
    if (isset($data['ativo'])) {
        $campos[] = 'ativo = ?';
        $valores[] = $data['ativo'] ? 1 : 0;
    }

    if (empty($campos)) {
        sendError('Nenhum campo para atualizar', 400);
    }

    $valores[] = $id;
    $sql = "UPDATE FF_Perfis SET " . implode(', ', $campos) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);

    echo json_encode(array(
        'success' => true,
        'message' => 'Perfil atualizado com sucesso'
    ), JSON_UNESCAPED_UNICODE);
}

function atualizarPermissoes($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $perfilId = isset($data['perfil_id']) ? (int)$data['perfil_id'] : 0;
    $permissoes = isset($data['permissoes']) ? $data['permissoes'] : array();

    if (!$perfilId) {
        sendError('ID do perfil é obrigatório', 400);
    }

    // Verificar se perfil existe
    $stmt = $pdo->prepare("SELECT nome FROM FF_Perfis WHERE id = ?");
    $stmt->execute(array($perfilId));
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$perfil) {
        sendError('Perfil não encontrado', 404);
    }

    // Atualizar permissões
    $stmtUpdate = $pdo->prepare("
        INSERT INTO FF_Perfil_Permissoes (perfil_id, pagina, pode_acessar, pode_editar)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE pode_acessar = VALUES(pode_acessar), pode_editar = VALUES(pode_editar)
    ");

    $atualizadas = 0;
    foreach ($permissoes as $pagina => $perm) {
        $podeAcessar = isset($perm['pode_acessar']) ? ($perm['pode_acessar'] ? 1 : 0) : 0;
        $podeEditar = isset($perm['pode_editar']) ? ($perm['pode_editar'] ? 1 : 0) : 0;

        $stmtUpdate->execute(array($perfilId, $pagina, $podeAcessar, $podeEditar));
        $atualizadas++;
    }

    echo json_encode(array(
        'success' => true,
        'message' => "Permissões atualizadas para perfil '{$perfil['nome']}'",
        'permissoes_atualizadas' => $atualizadas
    ), JSON_UNESCAPED_UNICODE);
}

function deletarPerfil($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        sendError('ID do perfil é obrigatório', 400);
    }

    // Não permitir deletar perfil Administrador
    if ($id == 1) {
        sendError('Não é permitido deletar o perfil Administrador', 403);
    }

    // Verificar se há usuários com este perfil
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FF_Users WHERE perfil_id = ?");
    $stmt->execute(array($id));
    if ($stmt->fetchColumn() > 0) {
        sendError('Existem usuários com este perfil. Altere o perfil deles antes de deletar.', 409);
    }

    // Deletar (as permissões são deletadas automaticamente pelo CASCADE)
    $stmt = $pdo->prepare("DELETE FROM FF_Perfis WHERE id = ?");
    $stmt->execute(array($id));

    if ($stmt->rowCount() == 0) {
        sendError('Perfil não encontrado', 404);
    }

    echo json_encode(array(
        'success' => true,
        'message' => 'Perfil deletado com sucesso'
    ), JSON_UNESCAPED_UNICODE);
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(array(
        'success' => false,
        'error' => $message
    ), JSON_UNESCAPED_UNICODE);
    exit;
}
?>
