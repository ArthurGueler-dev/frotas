<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $hoje = date('Y-m-d');

    // Busca registros de hoje
    $stmt = $pdo->prepare("
        SELECT 
            LicensePlate, 
            data, 
            km_inicial, 
            km_final, 
            km_rodado,
            tempo_ligado_minutos,
            velocidade_media,
            velocidade_maxima,
            status_atual,
            total_pontos_gps,
            fonte_api
        FROM Telemetria_Diaria 
        WHERE data = ?
        ORDER BY LicensePlate
        LIMIT 10
    ");
    $stmt->execute([$hoje]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Conta total de registros de hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Telemetria_Diaria WHERE data = ?");
    $stmt->execute([$hoje]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $row['total'];

    // Conta quantos tem km > 0
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Telemetria_Diaria WHERE data = ? AND km_rodado > 0");
    $stmt->execute([$hoje]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $comKm = $row['total'];

    echo json_encode([
        'success' => true,
        'data_consultada' => $hoje,
        'total_registros' => $total,
        'registros_com_km' => $comKm,
        'registros_sem_km' => $total - $comKm,
        'primeiros_10_registros' => $registros
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
