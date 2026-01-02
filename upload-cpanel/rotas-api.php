<?php
/**
 * API REST para gerenciar rotas
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, $options);
    $pdo->exec("SET NAMES utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        listarRotas($pdo);
    } elseif ($method === 'PUT') {
        atualizarStatusRota($pdo);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
}

/**
 * Listar rotas com filtros opcionais
 * Query params: status, motorista_id, data_inicio, data_fim
 */
function listarRotas($pdo) {
    $where = [];
    $params = [];

    // Filtros
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $where[] = "r.status = ?";
        $params[] = $_GET['status'];
    }

    if (isset($_GET['motorista_id']) && $_GET['motorista_id'] !== '') {
        $where[] = "r.motorista_id = ?";
        $params[] = intval($_GET['motorista_id']);
    }

    if (isset($_GET['data_inicio'])) {
        $where[] = "r.data_criacao >= ?";
        $params[] = $_GET['data_inicio'];
    }

    if (isset($_GET['data_fim'])) {
        $where[] = "r.data_criacao <= ?";
        $params[] = $_GET['data_fim'];
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "
        SELECT
            r.id,
            r.bloco_id,
            r.motorista_id,
            r.veiculo_id,
            r.distancia_total_km,
            r.tempo_estimado_min,
            r.sequencia_locais_json,
            r.link_google_maps,
            r.status,
            r.data_criacao,
            r.data_envio,
            r.data_inicio,
            r.data_conclusao,
            r.telefone_destino,
            r.observacoes,
            CONCAT(m.FirstName, ' ', m.LastName) as motorista_nome,
            NULL as motorista_whatsapp,
            v.LicensePlate as veiculo_placa,
            b.name as bloco_nome
        FROM FF_Rotas r
        LEFT JOIN Drivers m ON r.motorista_id = m.DriverID
        LEFT JOIN Vehicles v ON r.veiculo_id = v.Id
        LEFT JOIN FF_Blocks b ON r.bloco_id = b.id
        $whereClause
        ORDER BY r.data_criacao DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'rotas' => $rotas,
        'total' => count($rotas)
    ]);
}

/**
 * Atualizar status da rota
 * Body: { "rota_id": 123, "status": "em_andamento" }
 */
function atualizarStatusRota($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['rota_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'rota_id e status são obrigatórios']);
        return;
    }

    $rota_id = intval($data['rota_id']);
    $status = $data['status'];

    // Validar status
    $status_validos = ['pendente', 'enviada', 'em_andamento', 'concluida', 'cancelada'];
    if (!in_array($status, $status_validos)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Status inválido']);
        return;
    }

    try {
        // Atualizar status e datas correspondentes
        $campos_update = ["status = ?"];
        $params = [$status];

        if ($status === 'em_andamento' && isset($data['data_inicio'])) {
            $campos_update[] = "data_inicio = ?";
            $params[] = $data['data_inicio'];
        } elseif ($status === 'em_andamento') {
            $campos_update[] = "data_inicio = NOW()";
        }

        if ($status === 'concluida' && isset($data['data_conclusao'])) {
            $campos_update[] = "data_conclusao = ?";
            $params[] = $data['data_conclusao'];
        } elseif ($status === 'concluida') {
            $campos_update[] = "data_conclusao = NOW()";
        }

        if (isset($data['observacoes'])) {
            $campos_update[] = "observacoes = ?";
            $params[] = $data['observacoes'];
        }

        $params[] = $rota_id;

        $sql = "UPDATE FF_Rotas SET " . implode(", ", $campos_update) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Status atualizado com sucesso'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao atualizar status',
            'message' => $e->getMessage()
        ]);
    }
}
