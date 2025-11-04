<?php
// Configuração de CORS
header("Access-Control-Allow-Origin: *"); // Substitua * pelo domínio do frontend em produção
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type");

// Lidar com requisições OPTIONS (pré-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração de conexão com o banco de dados
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Buscar NR específica por ID
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM sesmt_nr WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $nr = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($nr) {
                echo json_encode(['success' => true, 'data' => $nr]);
            } else {
                echo json_encode(['success' => false, 'message' => 'NR não encontrada']);
            }
        } else {
            // Buscar todas as NRs
            $stmt = $pdo->query("SELECT * FROM sesmt_nr ORDER BY numero_nr");
            $nrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $nrs]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão: ' . $e->getMessage()]);
}
?>
