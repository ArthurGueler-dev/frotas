<?php
/**
 * API para gerenciar telemetria diária - VERSÃO CORRIGIDA
 * Endpoint: floripa.in9automacao.com.br/telemetria-diaria-api.php
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

// Configuração do banco de dados
define('DB_HOST', '187.49.226.10');
define('DB_PORT', '3306');
define('DB_NAME', 'f137049_in9aut');
define('DB_USER', 'f137049_tool');
define('DB_PASS', 'In9@1234qwer');

// Criar conexão MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Verificar conexão
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com banco de dados',
        'details' => $conn->connect_error
    ]);
    exit();
}

// Configurar charset
$conn->set_charset('utf8mb4');

// Obter método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            saveTelemetry($conn);
            break;

        case 'GET':
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
} finally {
    if ($conn) {
        $conn->close();
    }
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

    // Verificar se já existe registro
    $stmt = $conn->prepare("SELECT LicensePlate FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");

    if (!$stmt) {
        throw new Exception("Erro ao preparar query SELECT: " . $conn->error);
    }

    $stmt->bind_param("ss", $licensePlate, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Atualizar registro existente
        $stmt = $conn->prepare("
            UPDATE Telemetria_Diaria
            SET km_inicial = ?, km_final = ?, atualizado_em = NOW()
            WHERE LicensePlate = ? AND data = ?
        ");

        if (!$stmt) {
            throw new Exception("Erro ao preparar query UPDATE: " . $conn->error);
        }

        $stmt->bind_param("ddss", $kmInicial, $kmFinal, $licensePlate, $date);
        $action = 'atualizado';
    } else {
        // Inserir novo registro
        $stmt = $conn->prepare("
            INSERT INTO Telemetria_Diaria (LicensePlate, data, km_inicial, km_final, sincronizado_em)
            VALUES (?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            throw new Exception("Erro ao preparar query INSERT: " . $conn->error);
        }

        $stmt->bind_param("ssdd", $licensePlate, $date, $kmInicial, $kmFinal);
        $action = 'criado';
    }

    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => "Telemetria $action com sucesso",
        'data' => [
            'licensePlate' => $licensePlate,
            'date' => $date,
            'kmInicial' => $kmInicial,
            'kmFinal' => $kmFinal,
            'kmRodado' => $kmFinal - $kmInicial
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

    // Construir WHERE clause
    $whereClauses = [];
    $params = [];
    $types = '';

    if ($startDate) {
        $whereClauses[] = "t.data >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if ($endDate) {
        $whereClauses[] = "t.data <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    if ($plate) {
        $whereClauses[] = "t.LicensePlate LIKE ?";
        $params[] = "%$plate%";
        $types .= 's';
    }

    $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Query principal
    $sql = "
        SELECT
            t.LicensePlate,
            t.data,
            t.km_inicial,
            t.km_final,
            t.km_rodado,
            t.sincronizado_em,
            v.VehicleName as modelo,
            v.IgnitionStatus as status
        FROM Telemetria_Diaria t
        LEFT JOIN Vehicles v ON t.LicensePlate = v.LicensePlate
        $whereSQL
        ORDER BY t.data DESC, t.LicensePlate ASC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Erro ao preparar query principal: " . $conn->error);
    }

    // Adicionar limit e offset aos parâmetros
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    // Bind parameters
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar query principal: " . $stmt->error);
    }

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

    // Contar total de registros (sem LIMIT)
    $countSQL = "
        SELECT COUNT(*) as total
        FROM Telemetria_Diaria t
        $whereSQL
    ";

    $countStmt = $conn->prepare($countSQL);

    if (!$countStmt) {
        throw new Exception("Erro ao preparar query COUNT: " . $conn->error);
    }

    // Bind parameters (sem limit e offset)
    if (!empty($whereClauses)) {
        $countParams = array_slice($params, 0, -2); // Remove os 2 últimos (limit, offset)
        $countTypes = substr($types, 0, -2);

        if (!empty($countParams)) {
            $countStmt->bind_param($countTypes, ...$countParams);
        }
    }

    if (!$countStmt->execute()) {
        throw new Exception("Erro ao executar query COUNT: " . $countStmt->error);
    }

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
?>
