<?php
/**
 * API para gerenciamento de Quilometragem Diária
 *
 * Endpoints:
 * GET    /daily-mileage-api.php                     - Listar registros
 * GET    /daily-mileage-api.php?plate=ABC1234       - Buscar por placa
 * GET    /daily-mileage-api.php?area_id=1           - Buscar por área
 * POST   /daily-mileage-api.php                     - Salvar/atualizar quilometragem (UPSERT)
 * DELETE /daily-mileage-api.php?id=X                - Deletar registro
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexão com banco
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com banco de dados',
        'message' => $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET - Listar registros ou buscar específicos
// ============================================================
if ($method === 'GET') {
    try {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $plate = isset($_GET['plate']) ? trim($_GET['plate']) : null;
        $areaId = isset($_GET['area_id']) ? intval($_GET['area_id']) : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

        $query = "
            SELECT
                dm.*,
                a.name as area_name,
                v.VehicleName as vehicle_name
            FROM daily_mileage dm
            LEFT JOIN areas a ON dm.area_id = a.id
            LEFT JOIN Vehicles v ON dm.vehicle_plate = v.LicensePlate
            WHERE 1=1
        ";

        $params = [];

        if ($id) {
            $query .= " AND dm.id = ?";
            $params[] = $id;
        }

        if ($plate) {
            $query .= " AND dm.vehicle_plate = ?";
            $params[] = $plate;
        }

        if ($areaId) {
            $query .= " AND dm.area_id = ?";
            $params[] = $areaId;
        }

        if ($dateFrom) {
            $query .= " AND dm.date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $query .= " AND dm.date <= ?";
            $params[] = $dateTo;
        }

        $query .= " ORDER BY dm.date DESC, dm.vehicle_plate ASC LIMIT " . intval($limit);

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular estatísticas
        $totalKm = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($records as $record) {
            $totalKm += floatval($record['km_driven']);
            if ($record['sync_status'] === 'success') {
                $successCount++;
            } elseif ($record['sync_status'] === 'failed') {
                $failedCount++;
            }
        }

        echo json_encode([
            'success' => true,
            'records' => $records,
            'total' => count($records),
            'statistics' => [
                'total_km' => round($totalKm, 2),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'avg_km_per_day' => count($records) > 0 ? round($totalKm / count($records), 2) : 0
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar registros',
            'message' => $e->getMessage()
        ]);
    }
}

// ============================================================
// POST - Salvar/atualizar quilometragem (UPSERT)
// ============================================================
elseif ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['vehicle_plate']) || !isset($data['date'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Placa do veículo e data são obrigatórios'
        ]);
        exit;
    }

    try {
        // Limpar placa (trim)
        $plate = trim($data['vehicle_plate']);

        // Buscar area_id do veículo
        $stmt = $pdo->prepare("SELECT area_id FROM Vehicles WHERE TRIM(LicensePlate) = ?");
        $stmt->execute([$plate]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se veículo não existe, usar area_id = NULL (permitir salvar mesmo assim)
        $areaId = $vehicle ? $vehicle['area_id'] : null;

        // UPSERT: INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $pdo->prepare("
            INSERT INTO daily_mileage (
                vehicle_plate,
                date,
                area_id,
                odometer_start,
                odometer_end,
                km_driven,
                source,
                sync_status,
                error_message,
                synced_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                area_id = VALUES(area_id),
                odometer_start = VALUES(odometer_start),
                odometer_end = VALUES(odometer_end),
                km_driven = VALUES(km_driven),
                source = VALUES(source),
                sync_status = VALUES(sync_status),
                error_message = VALUES(error_message),
                synced_at = VALUES(synced_at),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            $plate,
            $data['date'],
            $areaId,
            isset($data['odometer_start']) ? $data['odometer_start'] : null,
            isset($data['odometer_end']) ? $data['odometer_end'] : null,
            $data['km_driven'],
            isset($data['source']) ? $data['source'] : 'API',
            isset($data['sync_status']) ? $data['sync_status'] : 'success',
            isset($data['error_message']) ? $data['error_message'] : null,
            isset($data['synced_at']) ? $data['synced_at'] : date('Y-m-d H:i:s')
        ]);

        // Verificar se foi INSERT ou UPDATE
        $recordId = $pdo->lastInsertId();
        if ($recordId == 0) {
            // Foi UPDATE, buscar ID do registro existente
            $stmt = $pdo->prepare("
                SELECT id FROM daily_mileage
                WHERE vehicle_plate = ? AND date = ?
            ");
            $stmt->execute([$plate, $data['date']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $recordId = $existing['id'];
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Quilometragem salva com sucesso',
            'id' => $recordId,
            'vehicle_plate' => $plate,
            'date' => $data['date'],
            'km_driven' => $data['km_driven'],
            'area_id' => $areaId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao salvar quilometragem',
            'message' => $e->getMessage(),
            'sql_state' => $e->getCode()
        ]);
    }
}

// ============================================================
// DELETE - Deletar registro
// ============================================================
elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID é obrigatório'
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM daily_mileage WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Registro deletado com sucesso'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Registro não encontrado'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao deletar registro',
            'message' => $e->getMessage()
        ]);
    }
}

// ============================================================
// Método não permitido
// ============================================================
else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
}
?>
