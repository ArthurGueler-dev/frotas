<?php
/**
 * API para listar veículos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
        'error' => 'Erro de conexão com banco de dados'
    ]);
    exit;
}

// GET - Listar veículos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        if ($action === 'list') {
            $stmt = $pdo->query("
                SELECT *
                FROM Vehicles
                WHERE 1=1
                ORDER BY LicensePlate ASC
            ");

            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'vehicles' => $vehicles,
                'total' => count($vehicles)
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar veículos',
            'message' => $e->getMessage()
        ]);
    }
}
?>
