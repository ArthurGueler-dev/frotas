<?php
/**
 * API auxiliar para buscar destinatários filtrados por severidade
 *
 * Usado pelo backend Python para determinar quem deve receber cada tipo de alerta
 *
 * GET ?severity=critical - Retorna destinatários que recebem alertas CRITICAL
 * GET ?severity=high - Retorna destinatários que recebem alertas HIGH
 * GET ?severity=medium - Retorna destinatários que recebem alertas MEDIUM
 * GET ?severity=low - Retorna destinatários que recebem alertas LOW
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

// ========== GET - Buscar Destinatários por Severidade ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($_GET['severity'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Parâmetro "severity" é obrigatório (critical, high, medium, low)'
            ]);
            exit;
        }

        $severity = strtolower($_GET['severity']);
        $validSeverities = ['critical', 'high', 'medium', 'low'];

        if (!in_array($severity, $validSeverities)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Severidade inválida. Use: critical, high, medium ou low'
            ]);
            exit;
        }

        // Construir query baseada na severidade
        $sql = "SELECT id, name, role, phone, email FROM FF_AlertRecipients WHERE is_active = 1";

        switch ($severity) {
            case 'critical':
                $sql .= " AND receive_critical = 1";
                break;
            case 'high':
                $sql .= " AND receive_high = 1";
                break;
            case 'medium':
                $sql .= " AND receive_medium = 1";
                break;
            case 'low':
                $sql .= " AND receive_low = 1";
                break;
        }

        $sql .= " ORDER BY name ASC";

        $stmt = $pdo->query($sql);
        $recipients = $stmt->fetchAll();

        // Verificar horário de recebimento (opcional - pode ser implementado depois)
        // Por enquanto retornar todos os que aceitam a severidade

        echo json_encode([
            'success' => true,
            'severity' => $severity,
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

// Método não suportado
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Método não suportado. Use GET.'
]);
?>
