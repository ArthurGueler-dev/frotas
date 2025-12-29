<?php
/**
 * API para consultar verificações de conformidade de rotas
 *
 * Endpoints:
 * - GET / - Listar verificações de conformidade
 * - GET ?route_id=X - Filtrar por rota específica
 * - GET ?route_id=X&limit=50 - Limitar quantidade de resultados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// ========== GET - Listar Verificações de Conformidade ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $route_id = isset($_GET['route_id']) ? intval($_GET['route_id']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $only_non_compliant = isset($_GET['only_non_compliant']) ? filter_var($_GET['only_non_compliant'], FILTER_VALIDATE_BOOLEAN) : false;

        // Construir query
        $sql = "SELECT * FROM FF_RouteCompliance WHERE 1=1";
        $params = [];

        if ($route_id) {
            $sql .= " AND route_id = ?";
            $params[] = $route_id;
        }

        if ($only_non_compliant) {
            $sql .= " AND is_compliant = 0";
        }

        $sql .= " ORDER BY check_timestamp DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $checks = $stmt->fetchAll();

        // Converter valores numéricos
        foreach ($checks as &$check) {
            $check['current_latitude'] = floatval($check['current_latitude']);
            $check['current_longitude'] = floatval($check['current_longitude']);
            $check['distance_from_planned_route_km'] = floatval($check['distance_from_planned_route_km']);
            $check['compliance_score'] = floatval($check['compliance_score']);
            $check['is_compliant'] = (bool)$check['is_compliant'];
        }

        echo json_encode([
            'success' => true,
            'count' => count($checks),
            'compliance_checks' => $checks
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

// Método não suportado
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Método não suportado'
]);
?>
