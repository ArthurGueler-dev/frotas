<?php
/**
 * API para gerenciar telemetria diária
 * Endpoint: floripa.in9automacao.com.br/cpanel-api/telemetria-diaria-api.php
 */

// Configurações de CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Responder OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuração do banco
require_once __DIR__ . '/../db-config.php';

// Obter método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Salvar ou atualizar telemetria diária
            saveTelemetry($conn);
            break;

        case 'GET':
            // Consultar telemetria diária
            getTelemetry($conn);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Salva ou atualiza telemetria diária
 */
function saveTelemetry($conn) {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar dados obrigatórios
    if (!isset($input['licensePlate']) || !isset($input['date'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Placa e data são obrigatórios'
        ]);
        return;
    }

    $licensePlate = $input['licensePlate'];
    $date = $input['date'];
    $kmInicial = isset($input['kmInicial']) ? floatval($input['kmInicial']) : 0;
    $kmFinal = isset($input['kmFinal']) ? floatval($input['kmFinal']) : 0;
    $kmRodado = isset($input['kmRodado']) ? floatval($input['kmRodado']) : 0;
    $base = isset($input['base']) ? $input['base'] : null;

    // Verificar se já existe registro
    $stmt = $conn->prepare("
        SELECT id FROM Telemetria_Diaria
        WHERE LicensePlate = ? AND data = ?
    ");
    $stmt->bind_param("ss", $licensePlate, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Atualizar registro existente
        $stmt = $conn->prepare("
            UPDATE Telemetria_Diaria
            SET km_inicial = ?,
                km_final = ?,
                atualizado_em = NOW()
            WHERE LicensePlate = ? AND data = ?
        ");
        $stmt->bind_param("ddss", $kmInicial, $kmFinal, $licensePlate, $date);
        $stmt->execute();

        $action = 'atualizado';
    } else {
        // Inserir novo registro
        $stmt = $conn->prepare("
            INSERT INTO Telemetria_Diaria
            (LicensePlate, data, km_inicial, km_final, sincronizado_em)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssdd", $licensePlate, $date, $kmInicial, $kmFinal);
        $stmt->execute();

        $action = 'criado';
    }

    if ($stmt->error) {
        throw new Exception($stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => "Telemetria $action com sucesso",
        'data' => [
            'licensePlate' => $licensePlate,
            'date' => $date,
            'kmInicial' => $kmInicial,
            'kmFinal' => $kmFinal,
            'kmRodado' => $kmRodado
        ]
    ]);
}

/**
 * Consulta telemetria diária com filtros
 */
function getTelemetry($conn) {
    // Parâmetros de filtro
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $plate = isset($_GET['plate']) ? $_GET['plate'] : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // Construir query
    $where = [];
    $params = [];
    $types = '';

    if ($startDate) {
        $where[] = "data >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if ($endDate) {
        $where[] = "data <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    if ($plate) {
        $where[] = "LicensePlate LIKE ?";
        $params[] = "%$plate%";
        $types .= 's';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Query principal
    $sql = "
        SELECT
            t.LicensePlate,
            t.data,
            t.km_inicial,
            t.km_final,
            t.km_rodado,
            t.sincronizado_em,
            v.Model as modelo,
            v.Status as status
        FROM Telemetria_Diaria t
        LEFT JOIN Vehicles v ON t.LicensePlate = v.LicensePlate
        $whereClause
        ORDER BY t.data DESC, t.LicensePlate ASC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $telemetry = [];
    while ($row = $result->fetch_assoc()) {
        $telemetry[] = [
            'plate' => $row['LicensePlate'],
            'date' => $row['data'],
            'kmInicial' => floatval($row['km_inicial']),
            'kmFinal' => floatval($row['km_final']),
            'kmRodado' => floatval($row['km_rodado']),
            'model' => $row['modelo'],
            'status' => $row['status'],
            'sincronizadoEm' => $row['sincronizado_em']
        ];
    }

    // Contar total de registros
    $countSql = "
        SELECT COUNT(*) as total
        FROM Telemetria_Diaria t
        $whereClause
    ";

    $countStmt = $conn->prepare($countSql);

    if (!empty($where)) {
        // Remove os dois últimos parâmetros (limit e offset) para a contagem
        $countParams = array_slice($params, 0, -2);
        $countTypes = substr($types, 0, -2);

        if (!empty($countParams)) {
            $countStmt->bind_param($countTypes, ...$countParams);
        }
    }

    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRow = $countResult->fetch_assoc();
    $total = intval($totalRow['total']);

    echo json_encode([
        'success' => true,
        'data' => $telemetry,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total
        ]
    ]);
}
