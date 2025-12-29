<?php
/**
 * API para gerenciar destinatários de alertas (diretores, gerentes)
 *
 * Endpoints:
 * - GET / - Listar destinatários ativos
 * - POST / - Criar novo destinatário
 * - PUT / - Atualizar destinatário
 * - DELETE ?id=X - Desativar destinatário
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conexão com banco de dados
try {
    $pdo = new PDO(
        "mysql:host=187.49.226.10;dbname=f137049_in9aut;charset=utf8mb4",
        "f137049_tool",
        "In9@1234qwer",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão: ' . $e->getMessage()
    ]);
    exit;
}

// ========== GET - Listar Destinatários ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $only_active = isset($_GET['only_active']) ? filter_var($_GET['only_active'], FILTER_VALIDATE_BOOLEAN) : true;

        $sql = "SELECT * FROM FF_AlertRecipients";

        if ($only_active) {
            $sql .= " WHERE is_active = 1";
        }

        $sql .= " ORDER BY name ASC";

        $stmt = $pdo->query($sql);
        $recipients = $stmt->fetchAll();

        // Converter booleanos
        foreach ($recipients as &$recipient) {
            $recipient['receive_critical'] = (bool)$recipient['receive_critical'];
            $recipient['receive_high'] = (bool)$recipient['receive_high'];
            $recipient['receive_medium'] = (bool)$recipient['receive_medium'];
            $recipient['receive_low'] = (bool)$recipient['receive_low'];
            $recipient['receive_weekdays'] = (bool)$recipient['receive_weekdays'];
            $recipient['receive_weekends'] = (bool)$recipient['receive_weekends'];
            $recipient['is_active'] = (bool)$recipient['is_active'];
        }

        echo json_encode([
            'success' => true,
            'count' => count($recipients),
            'recipients' => $recipients
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========== POST - Criar Destinatário ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validações
        if (!isset($input['name']) || !isset($input['phone'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Nome e telefone são obrigatórios'
            ]);
            exit;
        }

        // Verificar se telefone já existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM FF_AlertRecipients WHERE phone = ?");
        $stmt->execute([$input['phone']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'Este telefone já está cadastrado'
            ]);
            exit;
        }

        // Inserir
        $stmt = $pdo->prepare("
            INSERT INTO FF_AlertRecipients
            (name, role, phone, email, receive_critical, receive_high, receive_medium, receive_low)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $input['name'],
            isset($input['role']) ? $input['role'] : null,
            $input['phone'],
            isset($input['email']) ? $input['email'] : null,
            isset($input['receive_critical']) ? intval($input['receive_critical']) : 1,
            isset($input['receive_high']) ? intval($input['receive_high']) : 1,
            isset($input['receive_medium']) ? intval($input['receive_medium']) : 0,
            isset($input['receive_low']) ? intval($input['receive_low']) : 0
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Destinatário cadastrado com sucesso',
            'id' => $pdo->lastInsertId()
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========== PUT - Atualizar Destinatário ==========
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID é obrigatório'
            ]);
            exit;
        }

        $id = intval($input['id']);

        // Atualizar
        $stmt = $pdo->prepare("
            UPDATE FF_AlertRecipients
            SET name = ?,
                role = ?,
                phone = ?,
                email = ?,
                receive_critical = ?,
                receive_high = ?,
                receive_medium = ?,
                receive_low = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $input['name'],
            isset($input['role']) ? $input['role'] : null,
            $input['phone'],
            isset($input['email']) ? $input['email'] : null,
            isset($input['receive_critical']) ? intval($input['receive_critical']) : 1,
            isset($input['receive_high']) ? intval($input['receive_high']) : 1,
            isset($input['receive_medium']) ? intval($input['receive_medium']) : 0,
            isset($input['receive_low']) ? intval($input['receive_low']) : 0,
            $id
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Destinatário atualizado com sucesso'
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Destinatário não encontrado'
            ]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ========== DELETE - Desativar Destinatário ==========
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID é obrigatório'
            ]);
            exit;
        }

        $id = intval($_GET['id']);

        // Desativar (soft delete)
        $stmt = $pdo->prepare("UPDATE FF_AlertRecipients SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Destinatário desativado com sucesso'
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Destinatário não encontrado'
            ]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Método não suportado
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Método não suportado'
]);
?>
