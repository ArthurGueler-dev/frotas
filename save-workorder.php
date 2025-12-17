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
            $valuesPlaceholders[] = "(?, ?, ?, ?, ?)";
            $valuesData[] = $data['ordem_numero'];
            $valuesData[] = isset($item['tipo']) ? $item['tipo'] : 'Serviço';
            $valuesData[] = isset($item['descricao']) ? $item['descricao'] : '';
            $valuesData[] = isset($item['quantidade']) ? $item['quantidade'] : 1;
            $valuesData[] = isset($item['valor_unitario']) ? $item['valor_unitario'] : 0.00;
        }

        // Executar batch insert (1 query para todos os itens)
        $sqlItem = "INSERT INTO ordemservico_itens
                    (ordem_numero, tipo, descricao, quantidade, valor_unitario)
                    VALUES " . implode(", ", $valuesPlaceholders);

        $stmtItem = $pdo->prepare($sqlItem);
        $stmtItem->execute($valuesData);

        error_log('save-workorder: ' . $numItens . ' itens inseridos em batch em ' . (microtime(true) - $startTime) . 's');
    }

    // Commit da transação
    $pdo->commit();

    $totalTime = microtime(true) - $startTime;
    error_log('save-workorder: OS criada com sucesso em ' . $totalTime . 's');

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
