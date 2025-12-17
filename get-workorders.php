<?php
/**
 * API para listar Ordens de Serviço do banco de dados
 *
 * Suporta filtros via query string:
 * - id: buscar por ID específico
 * - ordem_numero: buscar por número da ordem
 * - status: filtrar por status
 * - placa: filtrar por placa do veículo
 * - data_inicio, data_fim: filtrar por período
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Definir timezone para Brasil
date_default_timezone_set('America/Sao_Paulo');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Tratar métodos PUT e DELETE
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !isset($_GET['id'])) {
            throw new Exception('Dados inválidos para atualização');
        }

        $id = intval($_GET['id']);

        // Validar status (valores aceitos no ENUM)
        $statusValidos = array('Aberta', 'Diagnóstico', 'Orçamento', 'Execução', 'Finalizada', 'Cancelada');
        $status = isset($data['status']) ? $data['status'] : 'Aberta';
        if (!in_array($status, $statusValidos)) {
            $status = 'Aberta';
        }

        // Validar ocorrencia
        $ocorrenciaValidas = array('Corretiva', 'Preventiva', 'Garantia');
        $ocorrencia = isset($data['ocorrencia']) ? $data['ocorrencia'] : 'Corretiva';
        if (!in_array($ocorrencia, $ocorrenciaValidas)) {
            $ocorrencia = 'Corretiva';
        }

        // Atualizar apenas campos que existem na tabela
        $sql = "UPDATE ordemservico SET
                    km_veiculo = :km_veiculo,
                    responsavel = :responsavel,
                    status = :status,
                    ocorrencia = :ocorrencia,
                    observacoes = :observacoes
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':km_veiculo' => isset($data['km_veiculo']) ? $data['km_veiculo'] : 0,
            ':responsavel' => isset($data['responsavel']) ? $data['responsavel'] : null,
            ':status' => $status,
            ':ocorrencia' => $ocorrencia,
            ':observacoes' => isset($data['observacoes']) ? $data['observacoes'] : null
        ]);

        echo json_encode(['success' => true, 'message' => 'OS atualizada com sucesso']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['ordem_numero'])) {
            throw new Exception('Número da ordem não informado');
        }

        $ordem_numero = $_GET['ordem_numero'];

        // Deletar itens primeiro
        $sqlItens = "DELETE FROM ordemservico_itens WHERE ordem_numero = :ordem_numero";
        $stmtItens = $pdo->prepare($sqlItens);
        $stmtItens->execute([':ordem_numero' => $ordem_numero]);

        // Deletar OS
        $sql = "DELETE FROM ordemservico WHERE ordem_numero = :ordem_numero";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ordem_numero' => $ordem_numero]);

        echo json_encode(['success' => true, 'message' => 'OS deletada com sucesso']);
        exit;
    }

    // Parâmetros de filtro
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $ordem_numero = isset($_GET['ordem_numero']) ? $_GET['ordem_numero'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $placa = isset($_GET['placa']) ? $_GET['placa'] : null;
    $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : null;
    $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : null;
    $with_items = isset($_GET['with_items']) ? filter_var($_GET['with_items'], FILTER_VALIDATE_BOOLEAN) : false;

    // Se buscar por ID específico
    if ($id) {
        $sql = "SELECT
                    os.*,
                    v.VehicleName as veiculo_nome,
                    v.LicensePlate as veiculo_placa
                FROM ordemservico os
                LEFT JOIN Vehicles v ON os.placa_veiculo = v.LicensePlate
                WHERE os.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $os = $stmt->fetch();

        if (!$os) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ordem de serviço não encontrada']);
            exit;
        }

        // Buscar itens da OS
        if ($with_items) {
            $sqlItens = "SELECT *
                         FROM ordemservico_itens
                         WHERE ordem_numero = :ordem_numero
                         ORDER BY id ASC";

            $stmtItens = $pdo->prepare($sqlItens);
            $stmtItens->execute([':ordem_numero' => $os['ordem_numero']]);
            $os['itens'] = $stmtItens->fetchAll();

            // Calcular total baseado nos itens
            $total = 0;
            foreach ($os['itens'] as $item) {
                $total += floatval($item['valor_total']);
            }
            $os['valor_total'] = $total;
        }

        echo json_encode(['success' => true, 'data' => $os]);
        exit;
    }

    // Buscar por número da ordem
    if ($ordem_numero) {
        $sql = "SELECT
                    os.*,
                    v.VehicleName as veiculo_nome,
                    v.LicensePlate as veiculo_placa
                FROM ordemservico os
                LEFT JOIN Vehicles v ON os.placa_veiculo = v.LicensePlate
                WHERE os.ordem_numero = :ordem_numero";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ordem_numero' => $ordem_numero]);
        $os = $stmt->fetch();

        if (!$os) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ordem de serviço não encontrada']);
            exit;
        }

        // Buscar itens da OS
        if ($with_items) {
            $sqlItens = "SELECT *
                         FROM ordemservico_itens
                         WHERE ordem_numero = :ordem_numero
                         ORDER BY id ASC";

            $stmtItens = $pdo->prepare($sqlItens);
            $stmtItens->execute([':ordem_numero' => $ordem_numero]);
            $os['itens'] = $stmtItens->fetchAll();

            // Calcular total baseado nos itens
            $total = 0;
            foreach ($os['itens'] as $item) {
                $total += floatval($item['valor_total']);
            }
            $os['valor_total'] = $total;
        }

        echo json_encode(['success' => true, 'data' => $os]);
        exit;
    }

    // Listar todas as OS com filtros opcionais
    $sql = "SELECT
                os.id,
                os.ordem_numero,
                os.placa_veiculo,
                os.km_veiculo,
                os.status,
                os.ocorrencia,
                os.responsavel,
                os.data_criacao,
                os.data_diagnostico,
                os.data_orcamento,
                os.data_execucao,
                os.data_finalizacao,
                os.observacoes,
                v.VehicleName as veiculo_nome,
                v.LicensePlate as veiculo_placa,
                (SELECT COUNT(*) FROM ordemservico_itens WHERE ordem_numero = os.ordem_numero) as total_itens
            FROM ordemservico os
            LEFT JOIN Vehicles v ON os.placa_veiculo = v.LicensePlate
            WHERE 1=1";

    $params = [];

    // Aplicar filtros
    if ($status) {
        $sql .= " AND os.status = :status";
        $params[':status'] = $status;
    }

    if ($placa) {
        $sql .= " AND os.placa_veiculo LIKE :placa";
        $params[':placa'] = "%$placa%";
    }

    if ($data_inicio) {
        $sql .= " AND DATE(os.data_criacao) >= :data_inicio";
        $params[':data_inicio'] = $data_inicio;
    }

    if ($data_fim) {
        $sql .= " AND DATE(os.data_criacao) <= :data_fim";
        $params[':data_fim'] = $data_fim;
    }

    $sql .= " ORDER BY os.data_criacao DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $workOrders = $stmt->fetchAll();

    // Calcular valor total para cada OS baseado nos itens (usando batch query para evitar N+1)
    if (!empty($workOrders)) {
        // Buscar todos os números de OS em um array
        $osNumbers = array_column($workOrders, 'ordem_numero');

        // Batch query: buscar todos os itens de uma vez
        $placeholders = implode(',', array_fill(0, count($osNumbers), '?'));
        $sqlItens = "SELECT *
                     FROM ordemservico_itens
                     WHERE ordem_numero IN ($placeholders)
                     ORDER BY ordem_numero ASC, id ASC";

        $stmtItens = $pdo->prepare($sqlItens);
        $stmtItens->execute($osNumbers);
        $allItens = $stmtItens->fetchAll();

        // Agrupar itens por ordem_numero
        $itensByOS = [];
        foreach ($allItens as $item) {
            $itensByOS[$item['ordem_numero']][] = $item;
        }

        // Associar itens às OS e calcular total
        foreach ($workOrders as &$os) {
            $itens = isset($itensByOS[$os['ordem_numero']]) ? $itensByOS[$os['ordem_numero']] : [];

            // Calcular total
            $total = 0;
            foreach ($itens as $item) {
                $total += floatval($item['valor_total']);
            }
            $os['valor_total'] = $total;

            // Se with_items, incluir os itens
            if ($with_items) {
                $os['itens'] = $itens;
            }
        }
    }

    // Estatísticas resumidas
    $sqlStats = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Aberta' THEN 1 ELSE 0 END) as abertas,
                    SUM(CASE WHEN status = 'Diagnóstico' THEN 1 ELSE 0 END) as diagnostico,
                    SUM(CASE WHEN status = 'Orçamento' THEN 1 ELSE 0 END) as orcamento,
                    SUM(CASE WHEN status = 'Execução' THEN 1 ELSE 0 END) as execucao,
                    SUM(CASE WHEN status = 'Finalizada' THEN 1 ELSE 0 END) as finalizadas,
                    SUM(CASE WHEN status = 'Cancelada' THEN 1 ELSE 0 END) as canceladas
                 FROM ordemservico
                 WHERE 1=1";

    if ($status) {
        $sqlStats .= " AND status = :status";
    }
    if ($placa) {
        $sqlStats .= " AND placa_veiculo LIKE :placa";
    }
    if ($data_inicio) {
        $sqlStats .= " AND DATE(data_criacao) >= :data_inicio";
    }
    if ($data_fim) {
        $sqlStats .= " AND DATE(data_criacao) <= :data_fim";
    }

    $stmtStats = $pdo->prepare($sqlStats);
    $stmtStats->execute($params);
    $stats = $stmtStats->fetch();

    echo json_encode([
        'success' => true,
        'data' => $workOrders,
        'stats' => $stats,
        'total' => count($workOrders)
    ]);

} catch (Exception $e) {
    error_log('Erro ao buscar OS: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
