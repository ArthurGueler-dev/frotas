<?php
/**
 * API para listar modelos de veículos
 * Retorna lista única de modelos da tabela FF_Pecas_Compatibilidade
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração do banco de dados
$host = '187.49.226.10';
$port = '3306';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Erro de conexão com banco de dados',
        'details' => $e->getMessage()
    ));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Buscar modelos únicos da tabela de modelos de veículos
        $sql = "
            SELECT DISTINCT
                id,
                model as model,
                manufacturer,
                year
            FROM FF_VehicleModels
            WHERE active = 1
            ORDER BY model ASC
        ";

        $stmt = $pdo->query($sql);
        $modelos = $stmt->fetchAll();

        // Formatar dados
        $resultado = array();
        foreach ($modelos as $modelo) {
            $resultado[] = array(
                'id' => (int)$modelo['id'],
                'model' => $modelo['model'],
                'manufacturer' => $modelo['manufacturer'],
                'year' => $modelo['year'] ? (int)$modelo['year'] : null
            );
        }

        echo json_encode(array(
            'success' => true,
            'count' => count($resultado),
            'data' => $resultado
        ));

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => 'Erro ao buscar modelos',
            'details' => $e->getMessage()
        ));
    }
    exit;
}

// Método não suportado
http_response_code(405);
echo json_encode(array(
    'success' => false,
    'error' => 'Método não suportado'
));
?>
