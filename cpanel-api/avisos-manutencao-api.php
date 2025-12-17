<?php
/**
 * API de Avisos de Manutenção Preventiva
 *
 * Esta API gerencia os alertas de manutenção preventiva dos veículos
 * Funciona como ponte entre o frontend e o banco de dados MySQL
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuração do banco
require_once __DIR__ . '/db-config.php';

// Função para enviar resposta JSON
function sendResponse($success, $data = null, $error = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Função para validar e sanitizar parâmetros
function getParam($key, $default = null) {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// Obter método HTTP e ação
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$segments = array_filter(explode('/', $path));

// Fallback: Se PATH_INFO não funcionar, usar query parameter 'action'
$action = getParam('action', '');

try {
    // Conectar ao banco
    $conn = getDBConnection();

    if ($conn === null) {
        sendResponse(false, null, 'Erro ao conectar ao banco de dados', 500);
    }

    // ========== ROTEAMENTO ==========

    // GET /avisos-manutencao-api.php - Listar alertas
    if ($method === 'GET' && empty($segments)) {

        // Parâmetros de filtro
        $status = getParam('status', 'Ativo');
        $nivel_alerta = getParam('nivel_alerta');
        $busca = getParam('busca');
        $page = max(1, intval(getParam('page', 1)));
        $limit = max(1, min(100, intval(getParam('limit', 10))));
        $offset = ($page - 1) * $limit;

        // Construir WHERE dinâmico
        $whereConditions = [];
        $params = [];

        if ($status && $status !== 'Todos') {
            $whereConditions[] = "av.status = ?";
            $params[] = $status;
        }

        if ($nivel_alerta) {
            $whereConditions[] = "av.nivel_alerta = ?";
            $params[] = $nivel_alerta;
        }

        if ($busca) {
            $whereConditions[] = "av.placa_veiculo LIKE ?";
            $params[] = "%{$busca}%";
        }

        $whereClause = count($whereConditions) > 0
            ? 'WHERE ' . implode(' AND ', $whereConditions)
            : '';

        // Query principal
        $sql = "
            SELECT
                av.id,
                av.vehicle_id,
                av.placa_veiculo as placa,
                av.plano_id,
                av.km_programado,
                av.data_proxima,
                av.km_atual_veiculo as km_atual,
                av.km_restantes,
                av.dias_restantes,
                av.status,
                av.nivel_alerta as prioridade,
                av.mensagem,
                av.criado_em as data_alerta,
                av.concluido_em,
                COALESCE(v.VehicleName, 'Veículo') as modelo,
                COALESCE(pm.descricao_titulo, 'Plano de Manutenção') as plano_nome,
                COALESCE(pm.custo_estimado, 0) as custo_estimado
            FROM avisos_manutencao av
            LEFT JOIN Vehicles v ON av.placa_veiculo = v.LicensePlate
            LEFT JOIN `Planos_Manutenção` pm ON av.plano_id = pm.id
            {$whereClause}
            ORDER BY
                CASE av.nivel_alerta
                    WHEN 'Critico' THEN 1
                    WHEN 'Alto' THEN 2
                    WHEN 'Medio' THEN 3
                    WHEN 'Baixo' THEN 4
                    ELSE 5
                END,
                av.criado_em DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;

        // Bind parameters for PDO (sequential, not named)
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }

        $stmt->execute();
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Contar total
        $sqlCount = "SELECT COUNT(*) as total FROM avisos_manutencao av {$whereClause}";
        $stmtCount = $conn->prepare($sqlCount);

        if (!empty($whereConditions)) {
            // Remover os últimos 2 parâmetros (limit e offset) para o count
            $countParams = array_slice($params, 0, -2);
            foreach ($countParams as $index => $value) {
                $stmtCount->bindValue($index + 1, $value);
            }
        }

        $stmtCount->execute();
        $totalRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
        $total = $totalRow['total'];
        $totalPages = ceil($total / $limit);

        // Calcular estatísticas
        $sqlStats = "
            SELECT
                SUM(CASE WHEN status = 'Vencido' THEN 1 ELSE 0 END) as vencidas,
                SUM(CASE WHEN nivel_alerta IN ('Alto', 'Critico') THEN 1 ELSE 0 END) as urgentes,
                SUM(CASE WHEN nivel_alerta = 'Medio' THEN 1 ELSE 0 END) as proximas,
                SUM(CASE WHEN status = 'Pendente' AND nivel_alerta = 'Baixo' THEN 1 ELSE 0 END) as emDia
            FROM avisos_manutencao
            WHERE status IN ('Pendente', 'Vence_hoje', 'Vencido', 'Ativo')
        ";

        $stmtStats = $conn->query($sqlStats);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        sendResponse(true, [
            'alertas' => $alertas,
            'stats' => [
                'vencidas' => intval(isset($stats['vencidas']) ? $stats['vencidas'] : 0),
                'urgentes' => intval(isset($stats['urgentes']) ? $stats['urgentes'] : 0),
                'proximas' => intval(isset($stats['proximas']) ? $stats['proximas'] : 0),
                'emDia' => intval(isset($stats['emDia']) ? $stats['emDia'] : 0),
                'custoEstimado30Dias' => 0
            ],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($total),
                'totalPages' => intval($totalPages)
            ]
        ]);
    }

    // GET /avisos-manutencao-api.php?action=history&placa=XXX - Histórico
    elseif ($method === 'GET' && ($action === 'history' || (count($segments) >= 2 && $segments[1] === 'history'))) {

        $placa = getParam('placa', '');
        if (empty($placa) && isset($segments[2])) {
            $placa = $segments[2];
        }

        if (empty($placa)) {
            sendResponse(false, null, 'Placa não informada', 400);
        }

        $sql = "
            SELECT
                av.*,
                COALESCE(pm.descricao_titulo, 'Plano de Manutenção') as plano_nome
            FROM avisos_manutencao av
            LEFT JOIN `Planos_Manutenção` pm ON av.plano_id = pm.id
            WHERE av.placa_veiculo = ?
            ORDER BY av.criado_em DESC
            LIMIT 50
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $placa);
        $stmt->execute();
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse(true, $historico);
    }

    // PUT /avisos-manutencao-api.php?action=resolve&id=123 - Marcar como resolvido
    elseif ($method === 'PUT' && ($action === 'resolve' || (count($segments) >= 2 && isset($segments[2]) && $segments[2] === 'resolve'))) {

        $id = intval(getParam('id', '0'));
        if ($id === 0 && isset($segments[1])) {
            $id = intval($segments[1]);
        }

        if ($id <= 0) {
            sendResponse(false, null, 'ID inválido', 400);
        }

        $sql = "
            UPDATE avisos_manutencao
            SET status = 'Concluido',
                concluido_em = NOW()
            WHERE id = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            sendResponse(true, ['message' => 'Alerta marcado como concluído']);
        } else {
            sendResponse(false, null, 'Erro ao atualizar alerta', 500);
        }
    }

    // POST /avisos-manutencao-api.php?action=sync-km - Sincronizar KM
    elseif ($method === 'POST' && ($action === 'sync-km' || (count($segments) >= 1 && $segments[1] === 'sync-km'))) {

        // Fazer requisição para o servidor Node.js que tem integração com Ituran
        $nodeUrl = 'https://frotas.in9automacao.com.br/api/maintenance-alerts/sync-km';

        $ch = curl_init($nodeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 segundos
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            sendResponse(false, null, 'Erro ao conectar com servidor: ' . $error, 500);
        }

        if ($httpCode !== 200) {
            sendResponse(false, null, 'Erro no servidor Node.js: HTTP ' . $httpCode, 500);
        }

        $result = json_decode($response, true);

        if ($result && isset($result['success'])) {
            sendResponse($result['success'], $result['data'], $result['error']);
        } else {
            sendResponse(false, null, 'Resposta inválida do servidor', 500);
        }
    }

    // Rota não encontrada
    else {
        sendResponse(false, null, 'Endpoint não encontrado', 404);
    }

} catch (Exception $e) {
    sendResponse(false, null, 'Erro: ' . $e->getMessage(), 500);
} finally {
    // PDO fecha automaticamente a conexão quando o objeto é destruído
    $conn = null;
}
