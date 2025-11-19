<?php
/**
 * Teste Simples de Conexao e Diagnostico
 * Upload para: /home/f137049/public_html/api/teste-conexao.php
 */

// Configuracao de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type");

// Lidar com requisicoes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Configuracao de conexao com o banco de dados
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

$resultado = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'testes' => []
];

// Teste 1: Conexao MySQL
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resultado['testes']['mysql'] = [
        'status' => 'OK',
        'mensagem' => 'Conectado com sucesso'
    ];

    // Teste 2: Verifica tabela Vehicles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Vehicles");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado['testes']['tabela_vehicles'] = [
        'status' => 'OK',
        'total' => $row['total']
    ];

    // Teste 3: Verifica tabela Telemetria_Diaria
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Telemetria_Diaria");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado['testes']['tabela_telemetria'] = [
        'status' => 'OK',
        'total' => $row['total']
    ];

    // Teste 4: Busca 1 veiculo
    $stmt = $pdo->query("SELECT LicensePlate FROM Vehicles WHERE LicensePlate IS NOT NULL AND LicensePlate != '' LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado['testes']['veiculo_exemplo'] = [
        'status' => 'OK',
        'placa' => $row['LicensePlate']
    ];

} catch (PDOException $e) {
    $resultado['testes']['mysql'] = [
        'status' => 'ERRO',
        'mensagem' => $e->getMessage()
    ];
}

// Teste 5: Extensoes PHP
$resultado['testes']['extensoes'] = [
    'pdo' => extension_loaded('pdo') ? 'OK' : 'FALTANDO',
    'pdo_mysql' => extension_loaded('pdo_mysql') ? 'OK' : 'FALTANDO',
    'simplexml' => extension_loaded('simplexml') ? 'OK' : 'FALTANDO',
    'openssl' => extension_loaded('openssl') ? 'OK' : 'FALTANDO'
];

// Teste 6: HTTPS
$resultado['testes']['https'] = [
    'disponivel' => function_exists('file_get_contents') ? 'OK' : 'FALTANDO',
    'ssl_context' => function_exists('stream_context_create') ? 'OK' : 'FALTANDO'
];

$resultado['status'] = 'SUCCESS';

echo json_encode($resultado, JSON_PRETTY_PRINT);
?>
