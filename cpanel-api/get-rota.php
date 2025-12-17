<?php
/**
 * API para buscar uma rota por ID
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

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
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);

    $rota_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$rota_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID da rota é obrigatório']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM FF_Rotas WHERE id = ?");
    $stmt->execute([$rota_id]);
    $rota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rota) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rota não encontrada']);
        exit;
    }

    echo json_encode(['success' => true, 'rota' => $rota]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
}
