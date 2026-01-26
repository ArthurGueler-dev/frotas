<?php
/**
 * API para atualizar Ordem de Serviço
 *
 * Atualiza OS e registra mudanças no histórico automaticamente
 * Atualiza datas automaticamente baseado no status:
 * - Diagnóstico → data_diagnostico
 * - Orçamento → data_orcamento
 * - Execução → data_execucao
 * - Finalizada → data_finalizacao
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

date_default_timezone_set('America/Sao_Paulo');

require_once 'db-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use PUT ou PATCH.']);
    exit;
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Dados JSON inválidos: ' . json_last_error_msg());
    }

    // Validar campos obrigatórios
    if (empty($data['ordem_numero']) && empty($data['id'])) {
        throw new Exception('ordem_numero ou id é obrigatório');
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Começar transação
    $pdo->beginTransaction();

    // Buscar OS atual
    $whereClause = !empty($data['id']) ? "id = ?" : "ordem_numero = ?";
    $whereValue = !empty($data['id']) ? $data['id'] : $data['ordem_numero'];

    $stmt = $pdo->prepare("SELECT * FROM ordemservico WHERE $whereClause");
    $stmt->execute([$whereValue]);
    $osAtual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$osAtual) {
        throw new Exception('Ordem de serviço não encontrada');
    }

    $os_id = $osAtual['id'];
    $os_numero = $osAtual['ordem_numero'];

    // Preparar dados para atualização
    $updates = [];
    $params = [];
    $historico = [];

    $tz = new DateTimeZone('America/Sao_Paulo');
    $now = new DateTime('now', $tz);
    $dataAtual = $now->format('Y-m-d H:i:s');

    // Campos que podem ser atualizados
    $camposEditaveis = [
        'placa_veiculo', 'km_veiculo', 'responsavel', 'status',
        'observacoes', 'ocorrencia'
    ];

    foreach ($camposEditaveis as $campo) {
        if (isset($data[$campo]) && $data[$campo] !== $osAtual[$campo]) {
            $updates[] = "$campo = ?";
            $params[] = $data[$campo];

            // Registrar mudança no histórico
            $historico[] = [
                'tipo_mudanca' => ($campo === 'status') ? 'status_change' : 'data_change',
                'campo_alterado' => $campo,
                'valor_anterior' => $osAtual[$campo],
                'valor_novo' => $data[$campo]
            ];

            // Atualizar data automaticamente baseado no status
            if ($campo === 'status') {
                $statusDataMap = [
                    'Diagnóstico' => 'data_diagnostico',
                    'Orçamento' => 'data_orcamento',
                    'Execução' => 'data_execucao',
                    'Finalizada' => 'data_finalizacao'
                ];

                if (isset($statusDataMap[$data[$campo]])) {
                    $dataCampo = $statusDataMap[$data[$campo]];

                    // Só atualiza se ainda não tiver data
                    if (empty($osAtual[$dataCampo]) || $osAtual[$dataCampo] === '0000-00-00 00:00:00') {
                        $updates[] = "$dataCampo = ?";
                        $params[] = $dataAtual;

                        $historico[] = [
                            'tipo_mudanca' => 'data_change',
                            'campo_alterado' => $dataCampo,
                            'valor_anterior' => null,
                            'valor_novo' => $dataAtual
                        ];
                    }
                }
            }
        }
    }

    // Processar datas de acompanhamento (enviadas manualmente pelo usuário)
    $datasAcompanhamento = [
        'data_diagnostico' => 'Data de Diagnóstico',
        'data_orcamento' => 'Data de Orçamento',
        'data_execucao' => 'Data de Execução',
        'data_finalizacao' => 'Data de Finalização'
    ];

    foreach ($datasAcompanhamento as $campoData => $label) {
        if (isset($data[$campoData])) {
            // Converter ISO para MySQL datetime
            $novaData = null;
            if (!empty($data[$campoData])) {
                try {
                    $dt = new DateTime($data[$campoData]);
                    $novaData = $dt->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    // Se falhar, tenta usar o valor direto
                    $novaData = $data[$campoData];
                }
            }

            // Comparar com valor atual
            $valorAtual = isset($osAtual[$campoData]) ? $osAtual[$campoData] : null;
            if ($valorAtual === '0000-00-00 00:00:00') $valorAtual = null;

            // Se mudou, atualizar
            if ($novaData !== $valorAtual) {
                $updates[] = "$campoData = ?";
                $params[] = $novaData;

                $historico[] = [
                    'tipo_mudanca' => 'data_change',
                    'campo_alterado' => $campoData,
                    'valor_anterior' => $valorAtual,
                    'valor_novo' => $novaData
                ];
            }
        }
    }

    // Se não houver mudanças
    if (empty($updates)) {
        $pdo->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'Nenhuma alteração detectada',
            'ordem_numero' => $os_numero
        ]);
        exit;
    }

    // Executar UPDATE
    $sql = "UPDATE ordemservico SET " . implode(", ", $updates) . " WHERE id = ?";
    $params[] = $os_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Registrar histórico
    $usuario = isset($data['usuario_nome']) ? $data['usuario_nome'] : 'Sistema Web';
    $usuarioEmail = isset($data['usuario_email']) ? $data['usuario_email'] : null;

    $sqlHist = "INSERT INTO ordemservico_historico
                (os_id, os_numero, tipo_mudanca, campo_alterado,
                 valor_anterior, valor_novo, usuario_nome, usuario_email, observacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtHist = $pdo->prepare($sqlHist);

    foreach ($historico as $mudanca) {
        // Converter arrays para JSON se necessário
        $valorAnterior = is_array($mudanca['valor_anterior'])
            ? json_encode($mudanca['valor_anterior'], JSON_UNESCAPED_UNICODE)
            : $mudanca['valor_anterior'];

        $valorNovo = is_array($mudanca['valor_novo'])
            ? json_encode($mudanca['valor_novo'], JSON_UNESCAPED_UNICODE)
            : $mudanca['valor_novo'];

        $stmtHist->execute([
            $os_id,
            $os_numero,
            $mudanca['tipo_mudanca'],
            $mudanca['campo_alterado'],
            $valorAnterior,
            $valorNovo,
            $usuario,
            $usuarioEmail,
            isset($data['observacao_mudanca']) ? $data['observacao_mudanca'] : null
        ]);
    }

    // Commit
    $pdo->commit();

    // Buscar OS atualizada
    $stmt = $pdo->prepare("SELECT * FROM ordemservico WHERE id = ?");
    $stmt->execute([$os_id]);
    $osAtualizada = $stmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Ordem de serviço atualizada com sucesso',
        'ordem_numero' => $os_numero,
        'mudancas_registradas' => count($historico),
        'os' => $osAtualizada
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Erro ao atualizar OS: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
