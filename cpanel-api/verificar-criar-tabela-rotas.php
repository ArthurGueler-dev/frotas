<?php
/**
 * Verificar se tabela FF_Rotas existe e criar se necessário
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

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
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Verificar se tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'FF_Rotas'");
    $exists = $stmt->rowCount() > 0;

    if (!$exists) {
        // Criar tabela
        $sql = "
        CREATE TABLE FF_Rotas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bloco_id INT NOT NULL,
            motorista_id INT DEFAULT NULL,
            veiculo_id INT DEFAULT NULL,
            distancia_total_km DECIMAL(10, 2) DEFAULT 0,
            tempo_estimado_min INT DEFAULT 0,
            sequencia_locais_json TEXT,
            link_google_maps TEXT,
            status ENUM('pendente', 'enviada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'pendente',
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_envio DATETIME DEFAULT NULL,
            data_inicio DATETIME DEFAULT NULL,
            data_conclusao DATETIME DEFAULT NULL,
            telefone_destino VARCHAR(20) DEFAULT NULL,
            observacoes TEXT DEFAULT NULL,
            INDEX idx_bloco (bloco_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $pdo->exec($sql);

        echo json_encode([
            'success' => true,
            'message' => 'Tabela FF_Rotas criada com sucesso',
            'existed_before' => false
        ]);
    } else {
        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE FF_Rotas");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Tabela FF_Rotas já existe',
            'existed_before' => true,
            'columns' => array_column($columns, 'Field')
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
}
