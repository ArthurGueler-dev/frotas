<?php
/**
 * API para gerenciamento de Áreas
 *
 * Endpoints:
 * GET    /areas-api.php              - Listar todas as áreas
 * GET    /areas-api.php?id=X         - Buscar área específica
 * POST   /areas-api.php              - Criar nova área
 * PUT    /areas-api.php?id=X         - Atualizar área
 * DELETE /areas-api.php?id=X         - Deletar/desativar área
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexão com banco
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com banco de dados',
        'message' => $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// ============================================================
// GET - Listar áreas ou buscar específica
// ============================================================
if ($method === 'GET') {
    try {
        if ($id) {
            // Buscar área específica
            $stmt = $pdo->prepare("
                SELECT id, name, state, description, is_active,
                       created_at, updated_at
                FROM areas
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $area = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($area) {
                echo json_encode([
                    'success' => true,
                    'area' => $area
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Área não encontrada'
                ]);
            }
        } else {
            // Listar todas as áreas
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';

            $query = "
                SELECT id, name, state, description, is_active,
                       created_at, updated_at
                FROM areas
            ";

            if ($activeOnly) {
                $query .= " WHERE is_active = 1";
            }

            $query .= " ORDER BY name ASC";

            $stmt = $pdo->query($query);
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'areas' => $areas,
                'total' => count($areas)
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar áreas',
            'message' => $e->getMessage()
        ]);
    }
}

// ============================================================
// POST - Criar nova área
// ============================================================
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['name']) || empty(trim($data['name']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Nome da área é obrigatório'
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO areas (name, state, description, is_active)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($data['name']),
            isset($data['state']) ? trim($data['state']) : 'ES',
            isset($data['description']) ? trim($data['description']) : null,
            isset($data['is_active']) ? (bool)$data['is_active'] : true
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Área criada com sucesso',
            'id' => $newId
        ]);
    } catch (PDOException $e) {
        // Verificar se é erro de duplicata
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'Área com este nome já existe'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao criar área',
                'message' => $e->getMessage()
            ]);
        }
    }
}

// ============================================================
// PUT - Atualizar área
// ============================================================
elseif ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID da área é obrigatório'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = trim($data['name']);
        }
        if (isset($data['state'])) {
            $fields[] = "state = ?";
            $values[] = trim($data['state']);
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $values[] = trim($data['description']);
        }
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $values[] = (bool)$data['is_active'];
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Nenhum campo para atualizar'
            ]);
            exit;
        }

        $values[] = $id;

        $stmt = $pdo->prepare("
            UPDATE areas
            SET " . implode(', ', $fields) . "
            WHERE id = ?
        ");

        $stmt->execute($values);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Área atualizada com sucesso'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Área não encontrada ou nenhuma alteração feita'
            ]);
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'Área com este nome já existe'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao atualizar área',
                'message' => $e->getMessage()
            ]);
        }
    }
}

// ============================================================
// DELETE - Desativar área (soft delete)
// ============================================================
elseif ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID da área é obrigatório'
        ]);
        exit;
    }

    try {
        // Soft delete - apenas desativa
        $stmt = $pdo->prepare("
            UPDATE areas
            SET is_active = 0
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Área desativada com sucesso'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Área não encontrada'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao desativar área',
            'message' => $e->getMessage()
        ]);
    }
}

// ============================================================
// Método não permitido
// ============================================================
else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
}
?>
