<?php
/**
 * Endpoint para buscar dados de telemetria diaria
 * Retorna dados salvos na tabela Telemetria_Diaria
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
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

    // Busca data especifica ou hoje
    $data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

    // Busca todos os dados de telemetria da data
    $stmt = $pdo->prepare("
        SELECT 
            LicensePlate as plate,
            data as date,
            km_inicial as kmInicial,
            km_final as kmFinal,
            km_rodado as kmRodado,
            tempo_ligado_minutos as tempoLigado,
            velocidade_media as velocidadeMedia,
            velocidade_maxima as velocidadeMaxima,
            status_atual as status,
            lat_inicio as latInicio,
            lng_inicio as lngInicio,
            lat_fim as latFim,
            lng_fim as lngFim,
            total_pontos_gps as totalPontosGPS,
            fonte_api as fonte
        FROM Telemetria_Diaria 
        WHERE data = ?
        ORDER BY LicensePlate
    ");
    
    $stmt->execute([$data]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Converte para o formato esperado pelo dashboard
    $veiculos = array();
    foreach ($registros as $reg) {
        $veiculos[] = array(
            'plate' => $reg['plate'],
            'date' => $reg['date'],
            'kmInicial' => floatval($reg['kmInicial']),
            'kmFinal' => floatval($reg['kmFinal']),
            'kmRodado' => floatval($reg['kmRodado']),
            'tempoLigado' => intval($reg['tempoLigado']),
            'velocidadeMedia' => floatval($reg['velocidadeMedia']),
            'velocidadeMaxima' => floatval($reg['velocidadeMaxima']),
            'status' => $reg['status'],
            'totalPontosGPS' => intval($reg['totalPontosGPS'])
        );
    }

    echo json_encode(array(
        'success' => true,
        'data' => $data,
        'total' => count($veiculos),
        'veiculos' => $veiculos
    ));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
