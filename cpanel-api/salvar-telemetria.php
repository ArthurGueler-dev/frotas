<?php
/**
 * API para Salvar Telemetria no Banco
 * Recebe dados do JavaScript e salva na tabela Telemetria_Diaria
 */

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// OPTIONS pre-flight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração do banco
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
    // Conecta ao banco
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Lê dados do POST
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Dados inválidos');
    }

    // Valida campos obrigatórios
    $plate = $data['plate'] ?? null;
    $date = $data['date'] ?? null;

    if (!$plate || !$date) {
        throw new Exception('Placa e data são obrigatórios');
    }

    // Dados do veículo
    $kmInicial = intval($data['kmInicial'] ?? 0);
    $kmFinal = intval($data['kmFinal'] ?? 0);
    $kmRodado = intval($data['kmRodado'] ?? 0);
    $velocidadeMedia = intval($data['velocidadeMedia'] ?? 0);
    $velocidadeMaxima = intval($data['velocidadeMaxima'] ?? 0);
    $totalPontosGPS = intval($data['totalPontosGPS'] ?? 0);
    $status = $data['status'] ?? 'Parado';

    // Verifica se já existe registro para essa placa e data
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM Telemetria_Diaria
        WHERE LicensePlate = ? AND data = ?
    ");
    $stmtCheck->execute([$plate, $date]);
    $exists = $stmtCheck->fetch()['total'] > 0;

    if ($exists) {
        // Atualiza registro existente
        $stmt = $pdo->prepare("
            UPDATE Telemetria_Diaria SET
                km_inicial = ?,
                km_final = ?,
                km_rodado = ?,
                velocidade_media = ?,
                velocidade_maxima = ?,
                total_pontos_gps = ?,
                status_atual = ?,
                fonte_api = 'ituran_javascript',
                ultima_atualizacao = NOW()
            WHERE LicensePlate = ? AND data = ?
        ");

        $stmt->execute([
            $kmInicial,
            $kmFinal,
            $kmRodado,
            $velocidadeMedia,
            $velocidadeMaxima,
            $totalPontosGPS,
            $status,
            $plate,
            $date
        ]);

        echo json_encode([
            'success' => true,
            'action' => 'updated',
            'plate' => $plate,
            'date' => $date,
            'kmRodado' => $kmRodado
        ]);

    } else {
        // Insere novo registro
        $stmt = $pdo->prepare("
            INSERT INTO Telemetria_Diaria (
                LicensePlate,
                data,
                km_inicial,
                km_final,
                km_rodado,
                velocidade_media,
                velocidade_maxima,
                total_pontos_gps,
                status_atual,
                fonte_api,
                data_criacao,
                ultima_atualizacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ituran_javascript', NOW(), NOW())
        ");

        $stmt->execute([
            $plate,
            $date,
            $kmInicial,
            $kmFinal,
            $kmRodado,
            $velocidadeMedia,
            $velocidadeMaxima,
            $totalPontosGPS,
            $status
        ]);

        echo json_encode([
            'success' => true,
            'action' => 'inserted',
            'plate' => $plate,
            'date' => $date,
            'kmRodado' => $kmRodado
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
