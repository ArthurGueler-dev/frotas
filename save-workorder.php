<?php
/**
 * API para salvar Ordem de Serviço no banco de dados
 *
 * Recebe dados JSON via POST e salva nas novas tabelas:
 * - ordemservico
 * - ordemservico_itens
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir configuração do banco
require_once 'db-config.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
    exit;
}

try {
    // Pegar dados JSON do corpo da requisição
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Dados JSON inválidos');
    }

    // Validar campos obrigatórios
    if (empty($data['ordem_numero']) || empty($data['placa_veiculo']) || empty($data['km_veiculo'])) {
        throw new Exception('Campos obrigatórios faltando: ordem_numero, placa_veiculo, km_veiculo');
    }

    // Conectar ao banco
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Começar transação
    $pdo->beginTransaction();

    // Verificar se ordem já existe
    $sqlCheck = "SELECT id FROM ordemservico WHERE ordem_numero = :ordem_numero";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':ordem_numero' => $data['ordem_numero']]);

    if ($stmtCheck->fetch()) {
        throw new Exception('Número de ordem já existe: ' . $data['ordem_numero']);
    }

    // Buscar veiculo_id pela placa
    $veiculo_id = null;
    if (!empty($data['placa_veiculo'])) {
        $sqlVeiculo = "SELECT id FROM veiculos WHERE placa = :placa LIMIT 1";
        $stmtVeiculo = $pdo->prepare($sqlVeiculo);
        $stmtVeiculo->execute([':placa' => $data['placa_veiculo']]);
        $veiculoResult = $stmtVeiculo->fetch();
        if ($veiculoResult) {
            $veiculo_id = $veiculoResult['id'];
        }
    }

    // Inserir ordem de serviço
    $sql = "INSERT INTO ordemservico
            (ordem_numero, veiculo_id, placa_veiculo, motorista_id, km_veiculo,
             responsavel, oficina, status, prioridade, ocorrencia, tipo_servico,
             defeito_reclamado, defeito_constatado, solucao_executada, observacoes,
             data_criacao, prazo_estimado, valor_orcado, criado_por)
            VALUES
            (:ordem_numero, :veiculo_id, :placa_veiculo, :motorista_id, :km_veiculo,
             :responsavel, :oficina, :status, :prioridade, :ocorrencia, :tipo_servico,
             :defeito_reclamado, :defeito_constatado, :solucao_executada, :observacoes,
             NOW(), :prazo_estimado, :valor_orcado, :criado_por)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ordem_numero' => $data['ordem_numero'],
        ':veiculo_id' => $veiculo_id,
        ':placa_veiculo' => $data['placa_veiculo'],
        ':motorista_id' => $data['motorista_id'] ?? null,
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
        ':valor_orcado' => $data['valor_orcado'] ?? 0.00,
        ':criado_por' => $data['criado_por'] ?? 'Sistema Web'
    ]);

    $os_id = $pdo->lastInsertId();

    // Inserir itens da OS se houver
    if (!empty($data['itens']) && is_array($data['itens'])) {
        $sqlItem = "INSERT INTO ordemservico_itens
                    (os_id, servico_id, tipo, codigo, descricao, quantidade,
                     unidade, valor_unitario, desconto_percentual, desconto_valor,
                     fornecedor, numero_nota, observacoes)
                    VALUES
                    (:os_id, :servico_id, :tipo, :codigo, :descricao, :quantidade,
                     :unidade, :valor_unitario, :desconto_percentual, :desconto_valor,
                     :fornecedor, :numero_nota, :observacoes)";

        $stmtItem = $pdo->prepare($sqlItem);

        foreach ($data['itens'] as $item) {
            // Buscar servico_id se houver código
            $servico_id = null;
            if (!empty($item['codigo'])) {
                $sqlServico = "SELECT id FROM servicos WHERE codigo = :codigo LIMIT 1";
                $stmtServico = $pdo->prepare($sqlServico);
                $stmtServico->execute([':codigo' => $item['codigo']]);
                $servicoResult = $stmtServico->fetch();
                if ($servicoResult) {
                    $servico_id = $servicoResult['id'];
                }
            }

            $stmtItem->execute([
                ':os_id' => $os_id,
                ':servico_id' => $servico_id,
                ':tipo' => $item['tipo'] ?? 'Serviço',
                ':codigo' => $item['codigo'] ?? null,
                ':descricao' => $item['descricao'] ?? '',
                ':quantidade' => $item['quantidade'] ?? 1,
                ':unidade' => $item['unidade'] ?? 'UN',
                ':valor_unitario' => $item['valor_unitario'] ?? 0.00,
                ':desconto_percentual' => $item['desconto_percentual'] ?? 0.00,
                ':desconto_valor' => $item['desconto_valor'] ?? 0.00,
                ':fornecedor' => $item['fornecedor'] ?? null,
                ':numero_nota' => $item['numero_nota'] ?? null,
                ':observacoes' => $item['observacoes'] ?? null
            ]);
        }

        // Calcular total da OS baseado nos itens
        $sqlTotal = "SELECT
                        COALESCE(SUM(valor_total), 0) as total,
                        COALESCE(SUM(CASE WHEN tipo = 'Produto' THEN valor_total ELSE 0 END), 0) as total_pecas,
                        COALESCE(SUM(CASE WHEN tipo IN ('Serviço', 'Mão de Obra') THEN valor_total ELSE 0 END), 0) as total_mao_obra
                     FROM ordemservico_itens
                     WHERE os_id = :os_id";

        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute([':os_id' => $os_id]);
        $totais = $stmtTotal->fetch();

        // Atualizar valores na OS
        $sqlUpdateOS = "UPDATE ordemservico
                        SET valor_total = :valor_total,
                            valor_pecas = :valor_pecas,
                            valor_mao_obra = :valor_mao_obra
                        WHERE id = :os_id";

        $stmtUpdateOS = $pdo->prepare($sqlUpdateOS);
        $stmtUpdateOS->execute([
            ':os_id' => $os_id,
            ':valor_total' => $totais['total'],
            ':valor_pecas' => $totais['total_pecas'],
            ':valor_mao_obra' => $totais['total_mao_obra']
        ]);
    }

    // Commit da transação
    $pdo->commit();

    // Retornar sucesso
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Ordem de serviço criada com sucesso',
        'id' => $os_id,
        'ordem_numero' => $data['ordem_numero']
    ]);

} catch (Exception $e) {
    // Rollback em caso de erro
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao salvar OS: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
