<?php
/**
 * API para listar todas as rotas (DEBUG)
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.bloco_id,
            r.distancia_total_km,
            r.tempo_estimado_min,
            r.status,
            r.data_criacao,
            LENGTH(r.sequencia_locais_json) as json_size,
            b.name as bloco_nome
        FROM FF_Rotas r
        LEFT JOIN FF_Blocks b ON r.bloco_id = b.id
        ORDER BY r.id DESC
        LIMIT 20
    ");
    $stmt->execute();
    $rotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada rota, contar quantos locais tem no JSON
    foreach ($rotas as &$rota) {
        $json = $pdo->prepare("SELECT sequencia_locais_json FROM FF_Rotas WHERE id = ?");
        $json->execute([$rota['id']]);
        $row = $json->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['sequencia_locais_json']) {
            $locais = json_decode($row['sequencia_locais_json'], true);
            $rota['total_locais'] = is_array($locais) ? count($locais) : 0;
        } else {
            $rota['total_locais'] = 0;
        }
    }

    echo json_encode([
        'success' => true,
        'total' => count($rotas),
        'rotas' => $rotas
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
}
