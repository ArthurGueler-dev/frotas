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

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
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
                    v.nome as veiculo_nome,
                    v.marca as veiculo_marca,
                    m.nome as motorista_nome,
                    m.celular as motorista_celular
                FROM ordemservico os
                LEFT JOIN veiculos v ON os.veiculo_id = v.id
                LEFT JOIN motoristas m ON os.motorista_id = m.id
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
            $sqlItens = "SELECT
                            osi.*,
                            s.nome as servico_nome,
                            s.categoria as servico_categoria
                         FROM ordemservico_itens osi
                         LEFT JOIN servicos s ON osi.servico_id = s.id
                         WHERE osi.os_id = :os_id
                         ORDER BY osi.id ASC";

            $stmtItens = $pdo->prepare($sqlItens);
            $stmtItens->execute([':os_id' => $id]);
            $os['itens'] = $stmtItens->fetchAll();
        }

        echo json_encode(['success' => true, 'data' => $os]);
        exit;
    }

    // Buscar por número da ordem
    if ($ordem_numero) {
        $sql = "SELECT
                    os.*,
                    v.nome as veiculo_nome,
                    v.marca as veiculo_marca,
                    m.nome as motorista_nome,
                    m.celular as motorista_celular
                FROM ordemservico os
                LEFT JOIN veiculos v ON os.veiculo_id = v.id
                LEFT JOIN motoristas m ON os.motorista_id = m.id
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
            $sqlItens = "SELECT
                            osi.*,
                            s.nome as servico_nome,
                            s.categoria as servico_categoria
                         FROM ordemservico_itens osi
                         LEFT JOIN servicos s ON osi.servico_id = s.id
                         WHERE osi.os_id = :os_id
                         ORDER BY osi.id ASC";

            $stmtItens = $pdo->prepare($sqlItens);
            $stmtItens->execute([':os_id' => $os['id']]);
            $os['itens'] = $stmtItens->fetchAll();
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
                os.prioridade,
                os.ocorrencia,
                os.responsavel,
                os.oficina,
                os.data_criacao,
                os.data_finalizacao,
                os.valor_total,
                os.valor_pecas,
                os.valor_mao_obra,
                v.nome as veiculo_nome,
                v.marca as veiculo_marca,
                m.nome as motorista_nome,
                (SELECT COUNT(*) FROM ordemservico_itens WHERE os_id = os.id) as total_itens
            FROM ordemservico os
            LEFT JOIN veiculos v ON os.veiculo_id = v.id
            LEFT JOIN motoristas m ON os.motorista_id = m.id
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

    // Se solicitado, incluir itens de cada OS
    if ($with_items && !empty($workOrders)) {
        $sqlItens = "SELECT
                        osi.*,
                        s.nome as servico_nome,
                        s.categoria as servico_categoria
                     FROM ordemservico_itens osi
                     LEFT JOIN servicos s ON osi.servico_id = s.id
                     WHERE osi.os_id = :os_id
                     ORDER BY osi.id ASC";

        $stmtItens = $pdo->prepare($sqlItens);

        foreach ($workOrders as &$os) {
            $stmtItens->execute([':os_id' => $os['id']]);
            $os['itens'] = $stmtItens->fetchAll();
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
                    SUM(CASE WHEN status = 'Cancelada' THEN 1 ELSE 0 END) as canceladas,
                    COALESCE(SUM(valor_total), 0) as valor_total_geral
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
