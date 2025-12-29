<?php
/**
 * API para métricas do dashboard de otimização de rotas
 *
 * Endpoints:
 * - GET / - Retornar métricas gerais
 * - GET ?period=6months - Retornar histórico mensal (últimos 6 meses)
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

// ========== GET - Métricas Gerais ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['period'])) {
    try {
        // Data de início do mês atual
        $currentMonth = date('Y-m-01 00:00:00');

        // Métricas do histórico de otimizações (mês atual)
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_routes,
                COALESCE(AVG(num_stops), 0) as avg_stops,
                COALESCE(SUM(total_distance_km), 0) as total_distance
            FROM FF_RouteHistory
            WHERE optimization_date >= ?
        ");
        $stmt->execute([$currentMonth]);
        $routeMetrics = $stmt->fetch();

        // Total de blocos cadastrados
        $stmt = $pdo->query("SELECT COUNT(*) as total_blocks FROM FF_Blocks");
        $blockMetrics = $stmt->fetch();

        // Total de locais cadastrados
        $stmt = $pdo->query("SELECT COUNT(*) as total_locations FROM FF_Locations");
        $locationMetrics = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'metrics' => [
                'total_routes' => (int)$routeMetrics['total_routes'],
                'avg_stops' => round($routeMetrics['avg_stops'], 1),
                'total_distance' => round($routeMetrics['total_distance'], 2),
                'active_blocks' => (int)$blockMetrics['total_blocks'],
                'total_locations' => (int)$locationMetrics['total_locations']
            ],
            'period' => 'current_month'
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

// ========== GET - Histórico Mensal (6 meses) ==========
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['period']) && $_GET['period'] === '6months') {
    try {
        // Últimos 6 meses
        $stmt = $pdo->query("
            SELECT
                DATE_FORMAT(optimization_date, '%Y-%m') as month,
                COUNT(*) as total_routes,
                COALESCE(SUM(total_distance_km), 0) as total_distance,
                COALESCE(AVG(total_distance_km), 0) as avg_distance
            FROM FF_RouteHistory
            WHERE optimization_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(optimization_date, '%Y-%m')
            ORDER BY month ASC
        ");

        $history = $stmt->fetchAll();

        // Preencher meses faltantes com zeros
        $sixMonthsAgo = new DateTime();
        $sixMonthsAgo->modify('-6 months');

        $allMonths = [];
        for ($i = 0; $i < 6; $i++) {
            $date = clone $sixMonthsAgo;
            $date->modify("+$i months");
            $monthKey = $date->format('Y-m');
            $monthLabel = ucfirst($date->format('M/y'));

            // Verificar se há dados para este mês
            $found = false;
            foreach ($history as $record) {
                if ($record['month'] === $monthKey) {
                    $allMonths[] = [
                        'month' => $monthKey,
                        'label' => $monthLabel,
                        'total_routes' => (int)$record['total_routes'],
                        'total_distance' => round($record['total_distance'], 2),
                        'avg_distance' => round($record['avg_distance'], 2)
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $allMonths[] = [
                    'month' => $monthKey,
                    'label' => $monthLabel,
                    'total_routes' => 0,
                    'total_distance' => 0,
                    'avg_distance' => 0
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'history' => $allMonths
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
