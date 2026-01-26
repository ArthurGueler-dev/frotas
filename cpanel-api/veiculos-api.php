<?php
/**
 * API para gerenciar veículos (CRUD completo)
 */

// CORS Headers - DEVEM ser os primeiros
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Preflight request
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
    echo json_encode(['success' => false, 'error' => 'Erro de conexão com banco de dados']);
    exit;
}

// GET - Listar veículos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        if ($action === 'list') {
            $stmt = $pdo->query("
                SELECT v.*, a.name as area_name
                FROM Vehicles v
                LEFT JOIN areas a ON v.area_id = a.id
                ORDER BY v.LicensePlate ASC
            ");

            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'vehicles' => $vehicles,
                'total' => count($vehicles)
            ]);
        } elseif ($action === 'get' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("
                SELECT v.*, a.name as area_name
                FROM Vehicles v
                LEFT JOIN areas a ON v.area_id = a.id
                WHERE v.Id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($vehicle) {
                echo json_encode(['success' => true, 'vehicle' => $vehicle]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Veículo não encontrado']);
            }
        } elseif ($action === 'get' && isset($_GET['plate'])) {
            $stmt = $pdo->prepare("
                SELECT v.*, a.name as area_name
                FROM Vehicles v
                LEFT JOIN areas a ON v.area_id = a.id
                WHERE v.LicensePlate = ?
            ");
            $stmt->execute([$_GET['plate']]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($vehicle) {
                echo json_encode(['success' => true, 'vehicle' => $vehicle]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Veículo não encontrado']);
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao buscar veículos', 'message' => $e->getMessage()]);
    }
}

// POST - Criar veículo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['plate']) || !isset($data['model'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Placa e modelo são obrigatórios']);
            exit;
        }

        // Verificar se já existe
        $checkStmt = $pdo->prepare("SELECT Id FROM Vehicles WHERE LicensePlate = ?");
        $checkStmt->execute([$data['plate']]);
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Veículo com esta placa já existe']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO Vehicles (
                LicensePlate, VehicleName, VehicleYear, Renavam, ChassisNumber,
                EnginePower, EngineDisplacement, Color, FuelType, IsWhitelisted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $data['plate'],
            $data['model'],
            isset($data['year']) ? $data['year'] : date('Y'),
            isset($data['renavam']) ? $data['renavam'] : '',
            isset($data['chassi']) ? $data['chassi'] : '',
            isset($data['power']) ? $data['power'] : '',
            isset($data['displacement']) ? $data['displacement'] : '',
            isset($data['color']) ? $data['color'] : '',
            isset($data['fuel']) ? $data['fuel'] : ''
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Veículo criado com sucesso',
            'id' => $newId
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao criar veículo', 'message' => $e->getMessage()]);
    }
}

// PUT - Atualizar veículo
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || (!isset($data['id']) && !isset($data['plate']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID ou placa são obrigatórios']);
            exit;
        }

        // Identificar veículo
        if (isset($data['id'])) {
            $whereClause = "Id = ?";
            $whereValue = $data['id'];
        } else {
            $whereClause = "LicensePlate = ?";
            $whereValue = $data['plate'];
        }

        // Construir UPDATE dinâmico
        $updates = [];
        $params = [];

        $fieldMap = [
            'model' => 'VehicleName',
            'year' => 'VehicleYear',
            'renavam' => 'Renavam',
            'chassi' => 'ChassisNumber',
            'power' => 'EnginePower',
            'displacement' => 'EngineDisplacement',
            'color' => 'Color',
            'fuel' => 'FuelType'
        ];

        foreach ($fieldMap as $jsonField => $dbField) {
            if (isset($data[$jsonField])) {
                $updates[] = "$dbField = ?";
                $params[] = $data[$jsonField];
            }
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
            exit;
        }

        $params[] = $whereValue;
        $sql = "UPDATE Vehicles SET " . implode(", ", $updates) . " WHERE $whereClause";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Veículo atualizado com sucesso',
            'rowsAffected' => $stmt->rowCount()
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar veículo', 'message' => $e->getMessage()]);
    }
}

// DELETE - Remover veículo
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $plate = isset($_GET['plate']) ? $_GET['plate'] : null;

        if (!$id && !$plate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID ou placa são obrigatórios']);
            exit;
        }

        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM Vehicles WHERE Id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM Vehicles WHERE LicensePlate = ?");
            $stmt->execute([$plate]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Veículo removido com sucesso',
            'rowsAffected' => $stmt->rowCount()
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao remover veículo', 'message' => $e->getMessage()]);
    }
}
?>
