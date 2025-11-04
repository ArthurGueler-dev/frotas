<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configurações do banco de dados (CORRIGIDO - servidor remoto)
$db_host = '187.49.226.10';
$db_port = 3306;
$db_user = 'f137049_tool';
$db_pass = 'In9@1234qwer';
$db_name = 'f137049_in9aut';

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'Erro de conexão com o banco de dados', 'message' => $e->getMessage()));
    exit;
}

try {
    // Buscar motoristas da tabela Drivers
    // Colunas: DriverID, FirstName, LastName
    $stmt = $pdo->prepare("SELECT DriverID, FirstName, LastName FROM Drivers ORDER BY FirstName ASC");
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar dados para o frontend
    $driversList = array();
    foreach ($drivers as $driver) {
        $driversList[] = array(
            'id' => $driver['DriverID'],
            'firstName' => $driver['FirstName'],
            'lastName' => $driver['LastName'],
            'name' => $driver['FirstName'] . ' ' . $driver['LastName'],
            'cpf' => 'N/A',
            'cnhNumber' => 'N/A',
            'status' => 'Disponível',
            'cnhStatus' => 'N/A',
            'cnhCategory' => 'N/A',
            'cnhExpiry' => 'N/A',
            'admissionDate' => 'N/A',
            'birthDate' => 'N/A'
        );
    }

    // Retornar sucesso com os motoristas
    echo json_encode(array(
        'success' => true,
        'count' => count($driversList),
        'data' => $driversList
    ));

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'error' => 'Erro ao buscar motoristas',
        'message' => $e->getMessage()
    ));
}
?>
