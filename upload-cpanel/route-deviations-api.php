<?php
/**
 * API para gerenciar desvios de rota detectados
 *
 * Endpoints:
 * - GET / - Listar desvios
 * - GET ?route_id=X - Filtrar por rota
 * - GET ?severity=critical - Filtrar por severidade
 * - GET ?resolved=0 - Filtrar por status de resolução
 * - PUT / - Resolver um desvio
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
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

// ========== GET - Listar Desvios ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // ✅ Verificar se a tabela existe
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'FF_RouteDeviations'");
        if ($tableCheck->rowCount() === 0) {
            // Tabela ainda não criada - retornar array vazio ao invés de erro
            echo json_encode([
                'success' => true,
                'count' => 0,
                'deviations' => [],
                'message' => 'Sistema de monitoramento ainda não inicializado. Tabela FF_RouteDeviations não encontrada.'
            ], JSON_PRETTY_PRINT);
            exit;
        }

        $route_id = isset($_GET['route_id']) ? intval($_GET['route_id']) : null;
        $severity = isset($_GET['severity']) ? $_GET['severity'] : null;
        $resolved = isset($_GET['resolved']) ? intval($_GET['resolved']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

        // Construir query
        $sql = "SELECT * FROM FF_RouteDeviations WHERE 1=1";
        $params = [];

        if ($route_id) {
            $sql .= " AND route_id = ?";
            $params[] = $route_id;
        }

        if ($severity) {
            $sql .= " AND severity = ?";
            $params[] = $severity;
        }

        if ($resolved !== null) {
            $sql .= " AND is_resolved = ?";
            $params[] = $resolved;
        }

        $sql .= " ORDER BY detected_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $deviations = $stmt->fetchAll();

        // Converter valores numéricos e booleanos
        foreach ($deviations as &$deviation) {
            $deviation['location_latitude'] = floatval($deviation['location_latitude']);
            $deviation['location_longitude'] = floatval($deviation['location_longitude']);
            $deviation['alert_sent'] = (bool)$deviation['alert_sent'];
            $deviation['is_resolved'] = (bool)$deviation['is_resolved'];
        }

        echo json_encode([
            'success' => true,
            'count' => count($deviations),
            'deviations' => $deviations
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

// ========== PUT - Resolver Desvio ==========
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID do desvio é obrigatório'
            ]);
            exit;
        }

        $id = intval($input['id']);
        $resolution_notes = isset($input['resolution_notes']) ? $input['resolution_notes'] : '';

        // Atualizar desvio
        $stmt = $pdo->prepare("
            UPDATE FF_RouteDeviations
            SET is_resolved = 1,
                resolved_at = NOW(),
                resolution_notes = ?
            WHERE id = ?
        ");

        $stmt->execute([$resolution_notes, $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Desvio marcado como resolvido',
                'id' => $id
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Desvio não encontrado'
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
