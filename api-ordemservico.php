<?php
/**
 * API REST completa para gerenciar Ordens de Serviço
 *
 * Métodos suportados:
 * - GET: Listar/buscar OS
 * - POST: Criar nova OS
 * - PUT: Atualizar OS existente
 * - PATCH: Atualizar status da OS
 * - DELETE: Cancelar OS
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db-config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    switch ($method) {
        case 'GET':
            // Usar get-workorders.php existente
            require_once 'get-workorders.php';
            break;

        case 'POST':
            // Usar save-workorder.php existente
            require_once 'save-workorder.php';
            break;

        case 'PUT':
            handlePut($pdo);
            break;

        case 'PATCH':
            handlePatch($pdo);
            break;

        case 'DELETE':
            handleDelete($pdo);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }

} catch (Exception $e) {
    error_log('Erro na API de OS: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * PUT - Atualizar OS completa
 */
function handlePut($pdo) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['id'])) {
        throw new Exception('ID da OS é obrigatório');
    }

    // Verificar se OS existe
    $sqlCheck = "SELECT id, status FROM ordemservico WHERE id = :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':id' => $data['id']]);
    $osExistente = $stmtCheck->fetch();

    if (!$osExistente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'OS não encontrada']);
        return;
    }

    // Não permitir editar OS finalizada ou cancelada
    if (in_array($osExistente['status'], ['Finalizada', 'Cancelada'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Não é possível editar OS finalizada ou cancelada']);
        return;
    }

    $pdo->beginTransaction();

    try {
        // Atualizar OS
        $sql = "UPDATE ordemservico SET
                ordem_numero = :ordem_numero,
                placa_veiculo = :placa_veiculo,
                km_veiculo = :km_veiculo,
                responsavel = :responsavel,
                oficina = :oficina,
                status = :status,
                prioridade = :prioridade,
                ocorrencia = :ocorrencia,
                tipo_servico = :tipo_servico,
                defeito_reclamado = :defeito_reclamado,
                defeito_constatado = :defeito_constatado,
                solucao_executada = :solucao_executada,
                observacoes = :observacoes,
                prazo_estimado = :prazo_estimado,
                valor_orcado = :valor_orcado
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $data['id'],
            ':ordem_numero' => $data['ordem_numero'],
            ':placa_veiculo' => $data['placa_veiculo'],
            ':km_veiculo' => $data['km_veiculo'],
            ':responsavel' => $data['responsavel'] ?? null,
            ':oficina' => $data['oficina'] ?? null,
            ':status' => $data['status'] ?? 'Aberta',
            ':prioridade' => $data['prioridade'] ?? 'Média',
            ':ocorrencia' => $data['ocorrencia'] ?? 'Corretiva',
            ':tipo_servico' => $data['tipo_servico'] ?? null,
            ':defeito_reclamado' => $data['defeito_reclamado'] ?? null,
            ':defeito_constatado' => $data['defeito_constatado'] ?? null,
            ':solucao_executada' => $data['solucao_executada'] ?? null,
            ':observacoes' => $data['observacoes'] ?? null,
            ':prazo_estimado' => $data['prazo_estimado'] ?? null,
            ':valor_orcado' => $data['valor_orcado'] ?? 0.00
        ]);

        // Atualizar datas conforme status
        atualizarDatasStatus($pdo, $data['id'], $data['status'] ?? 'Aberta');

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'OS atualizada com sucesso'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * PATCH - Atualizar apenas o status da OS
 */
function handlePatch($pdo) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['id']) || empty($data['status'])) {
        throw new Exception('ID e status são obrigatórios');
    }

    // Verificar se OS existe
    $sqlCheck = "SELECT id FROM ordemservico WHERE id = :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':id' => $data['id']]);

    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'OS não encontrada']);
        return;
    }

    // Atualizar status
    $sql = "UPDATE ordemservico SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $data['id'],
        ':status' => $data['status']
    ]);

    // Atualizar datas conforme novo status
    atualizarDatasStatus($pdo, $data['id'], $data['status']);

    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso',
        'status' => $data['status']
    ]);
}

/**
 * DELETE - Cancelar OS
 */
function handleDelete($pdo) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$id) {
        throw new Exception('ID da OS é obrigatório');
    }

    // Verificar se OS existe
    $sqlCheck = "SELECT id, status FROM ordemservico WHERE id = :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':id' => $id]);
    $os = $stmtCheck->fetch();

    if (!$os) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'OS não encontrada']);
        return;
    }

    // Não permitir cancelar OS já finalizada
    if ($os['status'] === 'Finalizada') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Não é possível cancelar OS finalizada']);
        return;
    }

    // Cancelar (não deletar)
    $sql = "UPDATE ordemservico SET status = 'Cancelada', data_finalizacao = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    echo json_encode([
        'success' => true,
        'message' => 'OS cancelada com sucesso'
    ]);
}

/**
 * Atualiza as datas da OS conforme o status
 */
function atualizarDatasStatus($pdo, $os_id, $status) {
    $campo_data = null;

    switch ($status) {
        case 'Diagnóstico':
            $campo_data = 'data_diagnostico';
            break;
        case 'Orçamento':
            $campo_data = 'data_orcamento';
            break;
        case 'Execução':
            $campo_data = 'data_execucao';
            break;
        case 'Finalizada':
        case 'Cancelada':
            $campo_data = 'data_finalizacao';
            break;
    }

    if ($campo_data) {
        $sql = "UPDATE ordemservico SET $campo_data = NOW() WHERE id = :id AND $campo_data IS NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $os_id]);
    }
}
?>
