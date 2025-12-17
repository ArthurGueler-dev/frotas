<?php
/**
 * API para atualizar status da rota após envio
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $rota_id = isset($data['rota_id']) ? intval($data['rota_id']) : 0;
    $status = isset($data['status']) ? $data['status'] : 'enviada';
    $telefone = isset($data['telefone_destino']) ? $data['telefone_destino'] : null;

    if (!$rota_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID da rota é obrigatório']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE FF_Rotas
        SET status = ?, data_envio = NOW(), telefone_destino = ?
        WHERE id = ?
    ");

    $stmt->execute([$status, $telefone, $rota_id]);

    echo json_encode(['success' => true, 'rota_id' => $rota_id]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
}
