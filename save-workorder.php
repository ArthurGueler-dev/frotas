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

// Definir timezone para Brasil
date_default_timezone_set('America/Sao_Paulo');

// Incluir configuração do banco
require_once 'db-config.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
    exit;
}

try {
    $startTime = microtime(true);

    // Pegar dados JSON do corpo da requisição
    $json = file_get_contents('php://input');

    // Debug: log do JSON recebido
    error_log('save-workorder: JSON recebido: ' . substr($json, 0, 200));
    error_log('save-workorder: Tamanho do JSON: ' . strlen($json) . ' bytes');

    $data = json_decode($json, true);

    if (!$data) {
        $jsonError = json_last_error_msg();
        error_log('save-workorder: Erro ao decodificar JSON: ' . $jsonError);
        throw new Exception('Dados JSON inválidos: ' . $jsonError);
    }

    error_log('save-workorder: Dados recebidos em ' . (microtime(true) - $startTime) . 's');

    // Validar campos obrigatórios
    if (empty($data['placa_veiculo']) || empty($data['km_veiculo'])) {
        throw new Exception('Campos obrigatórios faltando: placa_veiculo, km_veiculo');
    }

    // Conectar ao banco
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Começar transação
    $pdo->beginTransaction();

    error_log('save-workorder: Transação iniciada em ' . (microtime(true) - $startTime) . 's');

    // Calcular data/hora atual no horário de Brasília
    $tz = new DateTimeZone('America/Sao_Paulo');
    $now = new DateTime('now', $tz);
    $dataCriacao = $now->format('Y-m-d H:i:s');

    error_log("save-workorder: Timezone: America/Sao_Paulo, Data/Hora: {$dataCriacao}");

    // Gerar seq_number único com lock para evitar race conditions
    // Usar FOR UPDATE para lock pessimista
    $seqQuery = "SELECT COALESCE(MAX(seq_number), 0) + 1 as next_seq FROM ordemservico FOR UPDATE";
    $seqStmt = $pdo->query($seqQuery);
    $seq_number = $seqStmt->fetchColumn();

    // Gerar ordem_numero baseado no seq_number
    $year = date('Y');
    $ordem_numero = sprintf('OS-%d-%05d', $year, $seq_number);

    // Preparar SQL de INSERT (COM seq_number e ordem_numero)
    $sql = "INSERT INTO ordemservico
            (seq_number, ordem_numero, placa_veiculo, km_veiculo,
             responsavel, status, ocorrencia, observacoes,
             data_criacao)
            VALUES
            (:seq_number, :ordem_numero, :placa_veiculo, :km_veiculo,
             :responsavel, :status, :ocorrencia, :observacoes,
             :data_criacao)";

    $stmt = $pdo->prepare($sql);

    // Inserir com seq_number e ordem_numero já definidos
    $stmt->execute([
        ':seq_number' => $seq_number,
        ':ordem_numero' => $ordem_numero,
        ':placa_veiculo' => $data['placa_veiculo'],
        ':km_veiculo' => $data['km_veiculo'],
        ':responsavel' => isset($data['responsavel']) ? $data['responsavel'] : null,
        ':status' => isset($data['status']) ? $data['status'] : 'Aberta',
        ':ocorrencia' => isset($data['ocorrencia']) ? $data['ocorrencia'] : 'Corretiva',
        ':observacoes' => isset($data['observacoes']) ? $data['observacoes'] : null,
        ':data_criacao' => $dataCriacao
    ]);

    // Obter ID gerado
    $os_id = $pdo->lastInsertId();

    // Atualizar $data para usar nos itens
    $data['ordem_numero'] = $ordem_numero;

    error_log('save-workorder: OS inserida (ID: ' . $os_id . ', Número: ' . $ordem_numero . ', Seq: ' . $seq_number . ') em ' . (microtime(true) - $startTime) . 's');

    // Inserir itens da OS se houver (usando Batch INSERT para melhor performance)
    if (!empty($data['itens']) && is_array($data['itens'])) {
        $numItens = count($data['itens']);

        // Construir VALUES placeholders para batch insert
        $valuesPlaceholders = [];
        $valuesData = [];

        foreach ($data['itens'] as $item) {
            $valuesPlaceholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $valuesData[] = $data['ordem_numero'];
            $valuesData[] = isset($item['tipo']) ? $item['tipo'] : 'Serviço';
            $valuesData[] = isset($item['categoria']) ? $item['categoria'] : null;
            $valuesData[] = isset($item['codigo']) ? $item['codigo'] : null;
            $valuesData[] = isset($item['descricao']) ? $item['descricao'] : '';
            $valuesData[] = isset($item['quantidade']) ? $item['quantidade'] : 1;
            $valuesData[] = isset($item['valor_unitario']) ? $item['valor_unitario'] : 0.00;
            $valuesData[] = isset($item['fornecedor_produto']) ? $item['fornecedor_produto'] : null;
            $valuesData[] = isset($item['fornecedor_servico']) ? $item['fornecedor_servico'] : null;
        }

        // Executar batch insert (1 query para todos os itens)
        $sqlItem = "INSERT INTO ordemservico_itens
                    (ordem_numero, tipo, categoria, codigo, descricao, quantidade, valor_unitario, fornecedor_produto, fornecedor_servico)
                    VALUES " . implode(", ", $valuesPlaceholders);

        $stmtItem = $pdo->prepare($sqlItem);
        $stmtItem->execute($valuesData);

        error_log('save-workorder: ' . $numItens . ' itens inseridos em batch em ' . (microtime(true) - $startTime) . 's');
    }

    // REGISTRAR CRIAÇÃO NO HISTÓRICO
    $usuario = isset($data['responsavel']) ? $data['responsavel'] : 'Sistema Web';
    $usuarioEmail = isset($data['usuario_email']) ? $data['usuario_email'] : null;

    $sqlHist = "INSERT INTO ordemservico_historico
                (os_id, os_numero, tipo_mudanca, campo_alterado,
                 valor_novo, usuario_nome, usuario_email, observacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtHist = $pdo->prepare($sqlHist);
    $stmtHist->execute(array(
        $os_id,
        $data['ordem_numero'],
        'created',
        'status',
        isset($data['status']) ? $data['status'] : 'Aberta',
        $usuario,
        $usuarioEmail,
        'OS criada no sistema'
    ));

    // Commit da transação
    $pdo->commit();

    $totalTime = microtime(true) - $startTime;
    error_log('save-workorder: OS criada com sucesso em ' . $totalTime . 's');

    // ========== INTEGRAÇÃO COM ALERTAS DE MANUTENÇÃO ==========
    // Se a OS for Preventiva e estiver Finalizada, atualizar alertas correspondentes
    $ocorrencia = isset($data['ocorrencia']) ? $data['ocorrencia'] : 'Corretiva';
    $statusOS = isset($data['status']) ? $data['status'] : 'Aberta';
    $placaVeiculo = $data['placa_veiculo'];
    $kmVeiculo = intval($data['km_veiculo']);

    $statusFinalizados = array('Finalizada', 'Concluida', 'Fechada');

    if ($ocorrencia === 'Preventiva' && in_array($statusOS, $statusFinalizados)) {
        try {
            error_log("save-workorder: OS Preventiva finalizada - atualizando alertas para placa {$placaVeiculo}");

            // Buscar alertas pendentes para este veículo
            $sqlAlertas = "SELECT av.id, av.plano_id, pm.descricao_titulo
                           FROM avisos_manutencao av
                           LEFT JOIN `Planos_Manutenção` pm ON av.plano_id = pm.id
                           WHERE av.placa_veiculo = ?
                             AND av.status NOT IN ('Concluido', 'Cancelado')";
            $stmtAlertas = $pdo->prepare($sqlAlertas);
            $stmtAlertas->execute(array($placaVeiculo));
            $alertasPendentes = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);

            $alertasAtualizados = 0;

            // Verificar quais itens da OS correspondem aos alertas
            if (!empty($data['itens']) && is_array($data['itens'])) {
                foreach ($alertasPendentes as $alerta) {
                    $planoDescricao = isset($alerta['descricao_titulo']) ? mb_strtolower($alerta['descricao_titulo'], 'UTF-8') : '';

                    // Verificar se algum item da OS corresponde ao alerta
                    foreach ($data['itens'] as $item) {
                        $itemDescricao = isset($item['descricao']) ? mb_strtolower($item['descricao'], 'UTF-8') : '';

                        // Match por palavras-chave
                        $keywords = array('óleo', 'oleo', 'filtro', 'freio', 'pastilha', 'correia', 'vela',
                                          'embreagem', 'suspensão', 'suspensao', 'amortecedor', 'pneu',
                                          'alinhamento', 'balanceamento', 'bateria', 'fluido', 'revisão', 'revisao');

                        $matched = false;
                        foreach ($keywords as $keyword) {
                            if (mb_strpos($planoDescricao, $keyword) !== false &&
                                mb_strpos($itemDescricao, $keyword) !== false) {
                                $matched = true;
                                break;
                            }
                        }

                        if ($matched) {
                            // Marcar alerta como concluído
                            $sqlUpdateAlerta = "UPDATE avisos_manutencao SET
                                                status = 'Concluido',
                                                concluido_em = NOW(),
                                                km_finalizacao = ?,
                                                os_numero = ?,
                                                atualizado_em = NOW()
                                                WHERE id = ?";
                            $stmtUpdateAlerta = $pdo->prepare($sqlUpdateAlerta);
                            $stmtUpdateAlerta->execute(array($kmVeiculo, $data['ordem_numero'], $alerta['id']));
                            $alertasAtualizados++;

                            error_log("save-workorder: Alerta ID {$alerta['id']} marcado como concluído");
                            break; // Não precisa verificar mais itens para este alerta
                        }
                    }
                }
            }

            error_log("save-workorder: {$alertasAtualizados} alertas atualizados para concluído");

        } catch (Exception $alertaException) {
            // Não interromper o fluxo principal se falhar a atualização de alertas
            error_log('save-workorder: Erro ao atualizar alertas: ' . $alertaException->getMessage());
        }
    }
    // ========== FIM INTEGRAÇÃO ALERTAS ==========

    // Retornar sucesso
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Ordem de serviço criada com sucesso',
        'id' => $os_id,
        'ordem_numero' => $data['ordem_numero'],
        'tempo' => round($totalTime, 2) . 's'
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
