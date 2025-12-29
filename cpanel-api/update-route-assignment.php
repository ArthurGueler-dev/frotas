<?php
/**
 * API para atualizar atribuição de veículo/motorista na rota
 * Usado ao enviar WhatsApp
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Apenas POST é permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Conexão com banco
try {
    $pdo = new PDO(
        "mysql:host=187.49.226.10;port=3306;dbname=f137049_in9aut;charset=utf8mb4",
        "f137049_tool",
        "In9@1234qwer",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

// Receber dados JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

// Validar campos obrigatórios
$requiredFields = ['rota_id', 'veiculo_placa', 'motorista_id'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Campo obrigatório faltando: $field"]);
        exit;
    }
}

$rotaId = intval($data['rota_id']);
$veiculoPlaca = trim($data['veiculo_placa']);
$motoristaId = intval($data['motorista_id']);

// 1. Buscar ID do veículo pela placa
try {
    $stmt = $pdo->prepare("SELECT id FROM Vehicles WHERE LicensePlate = ? LIMIT 1");
    $stmt->execute([$veiculoPlaca]);
    $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$veiculo) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => "Veículo com placa '$veiculoPlaca' não encontrado"
        ]);
        exit;
    }

    $veiculoId = intval($veiculo['id']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar veículo: ' . $e->getMessage()
    ]);
    exit;
}

// 2. Validar que a rota existe
$stmt = $pdo->prepare("SELECT id FROM FF_Rotas WHERE id = ?");
$stmt->execute([$rotaId]);
$rota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rota) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Rota não encontrada']);
    exit;
}

// 3. Atualizar rota com veículo, motorista e status
try {
    $stmt = $pdo->prepare("
        UPDATE FF_Rotas
        SET veiculo_id = ?,
            motorista_id = ?,
            status = 'em_andamento'
        WHERE id = ?
    ");

    $stmt->execute([$veiculoId, $motoristaId, $rotaId]);

    echo json_encode([
        'success' => true,
        'message' => 'Rota atualizada com sucesso',
        'data' => [
            'rota_id' => $rotaId,
            'veiculo_id' => $veiculoId,
            'veiculo_placa' => $veiculoPlaca,
            'motorista_id' => $motoristaId,
            'status' => 'em_andamento'
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao atualizar rota: ' . $e->getMessage()
    ]);
}
?>
