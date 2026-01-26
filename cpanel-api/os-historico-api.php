<?php
/**
 * API de Histórico de Ordens de Serviço
 *
 * Endpoints:
 * GET  ?action=timeline&os_id=123 - Buscar histórico de uma OS
 * POST ?action=register             - Registrar mudança no histórico
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conexão
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'Database connection failed'));
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// ============================================================================
// GET TIMELINE - Buscar histórico de uma OS
// ============================================================================
if ($action === 'timeline' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $os_id = isset($_GET['os_id']) ? intval($_GET['os_id']) : null;
    $os_numero = isset($_GET['os_numero']) ? $_GET['os_numero'] : null;

    if (!$os_id && !$os_numero) {
        http_response_code(400);
        echo json_encode(array('error' => 'os_id ou os_numero é obrigatório'));
        exit;
    }

    $sql = "
        SELECT
            h.*,
            o.placa_veiculo,
            o.status as status_atual
        FROM ordemservico_historico h
        LEFT JOIN ordemservico o ON h.os_id = o.id
        WHERE " . ($os_id ? "h.os_id = ?" : "h.os_numero = ?") . "
        ORDER BY h.data_mudanca DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($os_id ? array($os_id) : array($os_numero));
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular tempo total e tempo parado
    $tempo_info = array(
        'tempo_total_horas' => 0,
        'tempo_parado_horas' => 0,
        'data_inicio' => null,
        'data_conclusao' => null
    );

    // Buscar datas da OS
    $os_query = $os_id ? "id = ?" : "ordem_numero = ?";
    $stmt = $pdo->prepare("
        SELECT
            data_diagnostico,
            data_orcamento,
            data_execucao,
            data_finalizacao,
            status
        FROM ordemservico
        WHERE $os_query
    ");
    $stmt->execute($os_id ? array($os_id) : array($os_numero));
    $os_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($os_data) {
        // Data início = primeira data preenchida
        $datas = array(
            $os_data['data_diagnostico'],
            $os_data['data_orcamento'],
            $os_data['data_execucao']
        );

        $datas = array_filter($datas, function($d) {
            return $d && $d !== '0000-00-00 00:00:00';
        });

        if (count($datas) > 0) {
            $tempo_info['data_inicio'] = min($datas);
        }

        // Data conclusão
        if ($os_data['data_finalizacao'] && $os_data['data_finalizacao'] !== '0000-00-00 00:00:00') {
            $tempo_info['data_conclusao'] = $os_data['data_finalizacao'];
        }

        // Calcular tempo total
        if ($tempo_info['data_inicio'] && $tempo_info['data_conclusao']) {
            $inicio = new DateTime($tempo_info['data_inicio']);
            $fim = new DateTime($tempo_info['data_conclusao']);
            $diff = $inicio->diff($fim);

            $tempo_info['tempo_total_horas'] = round(
                ($diff->days * 24) + $diff->h + ($diff->i / 60),
                2
            );
        }

        // Calcular tempo parado (dias entre diagnóstico e execução)
        if ($os_data['data_diagnostico'] && $os_data['data_execucao']) {
            $diag = new DateTime($os_data['data_diagnostico']);
            $exec = new DateTime($os_data['data_execucao']);
            $diff = $diag->diff($exec);

            $tempo_info['tempo_parado_horas'] = round(
                ($diff->days * 24) + $diff->h + ($diff->i / 60),
                2
            );
        }
    }

    echo json_encode(array(
        'success' => true,
        'historico' => $historico,
        'tempo_info' => $tempo_info,
        'total_registros' => count($historico)
    ));
    exit;
}

// ============================================================================
// REGISTER - Registrar mudança no histórico
// ============================================================================
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $os_id = isset($data['os_id']) ? intval($data['os_id']) : null;
    $os_numero = isset($data['os_numero']) ? $data['os_numero'] : null;
    $tipo_mudanca = isset($data['tipo_mudanca']) ? $data['tipo_mudanca'] : null;
    $campo_alterado = isset($data['campo_alterado']) ? $data['campo_alterado'] : null;
    $valor_anterior = isset($data['valor_anterior']) ? $data['valor_anterior'] : null;
    $valor_novo = isset($data['valor_novo']) ? $data['valor_novo'] : null;
    $usuario_nome = isset($data['usuario_nome']) ? $data['usuario_nome'] : 'Sistema Web';
    $usuario_email = isset($data['usuario_email']) ? $data['usuario_email'] : null;
    $observacao = isset($data['observacao']) ? $data['observacao'] : null;

    if (!$os_id || !$os_numero || !$tipo_mudanca) {
        http_response_code(400);
        echo json_encode(array('error' => 'os_id, os_numero e tipo_mudanca são obrigatórios'));
        exit;
    }

    // Converter arrays/objetos para JSON
    if (is_array($valor_anterior) || is_object($valor_anterior)) {
        $valor_anterior = json_encode($valor_anterior, JSON_UNESCAPED_UNICODE);
    }
    if (is_array($valor_novo) || is_object($valor_novo)) {
        $valor_novo = json_encode($valor_novo, JSON_UNESCAPED_UNICODE);
    }

    $stmt = $pdo->prepare("
        INSERT INTO ordemservico_historico (
            os_id, os_numero, tipo_mudanca, campo_alterado,
            valor_anterior, valor_novo, usuario_nome, usuario_email, observacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute(array(
            $os_id,
            $os_numero,
            $tipo_mudanca,
            $campo_alterado,
            $valor_anterior,
            $valor_novo,
            $usuario_nome,
            $usuario_email,
            $observacao
        ));

        echo json_encode(array(
            'success' => true,
            'message' => 'Mudança registrada no histórico',
            'historico_id' => $pdo->lastInsertId()
        ));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => 'Erro ao registrar histórico: ' . $e->getMessage()
        ));
    }

    exit;
}

// ============================================================================
// ESTATÍSTICAS
// ============================================================================
if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $os_id = isset($_GET['os_id']) ? intval($_GET['os_id']) : null;

    if (!$os_id) {
        http_response_code(400);
        echo json_encode(array('error' => 'os_id é obrigatório'));
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            tipo_mudanca,
            COUNT(*) as total
        FROM ordemservico_historico
        WHERE os_id = ?
        GROUP BY tipo_mudanca
    ");

    $stmt->execute(array($os_id));
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'success' => true,
        'stats' => $stats
    ));
    exit;
}

// Ação inválida
http_response_code(400);
echo json_encode(array('error' => 'Ação inválida'));

?>
