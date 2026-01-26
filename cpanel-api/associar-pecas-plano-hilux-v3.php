<?php
/**
 * Script para associar peças compatíveis aos itens do plano de manutenção
 * Modelo: Toyota HILUX CD
 * Data: 2026-01-09
 * Versão: 3.0 - Mapeamento EXPANDIDO baseado em resposta Perplexity
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar a associação',
        'warning' => 'Isso irá associar peças aos itens do plano de manutenção'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Criar conexão
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com o banco de dados',
        'details' => $e->getMessage()
    ]);
    exit();
}

$modelo = 'HILUX CD';

// ===================================
// MAPEAMENTO EXPANDIDO
// ===================================
// Baseado na resposta completa do Perplexity
// IDs das peças já cadastradas no sistema

$mapeamentos = [
    // ============================================
    // CATEGORIA: MOTOR
    // ============================================

    // Óleo Motor + Filtro (10.000 km, 20.000 km, etc)
    'troca de óleo motor' => [4, 8, 13, 14, 15, 25, 26, 27],
    'troca de oleo motor' => [4, 8, 13, 14, 15, 25, 26, 27],
    'substituição de 8l' => [8, 25, 26, 27],  // Óleo específico
    'filtro blindado' => [4, 13, 14, 15],      // Filtro óleo
    'resetar computador de bordo' => [4, 8],  // Troca de óleo completa
    '5w30' => [8, 25, 26, 27],
    'acea c2' => [8, 25, 26, 27],
    'low-saps' => [8, 25, 26, 27],
    'código filtro: 90915' => [4, 13, 14, 15],
    'wega jfo0211' => [15],

    // Velas de Aquecimento (50.000 km)
    'velas aquecimento' => [12, 35, 36, 37],
    'glow plugs' => [12, 35, 36, 37],
    'pré-câmara combustão' => [12, 35, 36, 37],
    '19850-0l020' => [12],
    'velas aquecem' => [12, 35, 36, 37],

    // Corrente Distribuição (60.000 km+)
    'corrente distribuição' => [11, 34],
    'corrente de distribuição' => [11, 34],
    'kit corrente' => [11, 34],
    'tensores corrente' => [11, 34],

    // ============================================
    // CATEGORIA: FILTROS
    // ============================================

    // Filtro Ar Motor (20.000 km)
    'troca filtro ar motor' => [5, 16, 17, 18],
    'elemento filtro ar' => [5, 16, 17, 18],
    'filtro ar primário' => [5, 16, 17, 18],
    'limpeza caixa ar' => [5, 16, 17, 18],
    '17801-0l050' => [5],
    'filtro sujo reduz potência' => [5, 16, 17, 18],

    // Filtro Combustível (20.000 km)
    'troca filtro combustível' => [6, 19, 20, 21],
    'filtro combustível principal' => [6, 19, 20, 21],
    'pré-filtro' => [6, 19, 20, 21],
    'separador água' => [6, 19, 20, 21],
    'drenar água' => [6, 19, 20, 21],
    'purgar sistema' => [6, 19, 20, 21],
    'common rail' => [6, 19, 20, 21],
    '23300-0l041' => [6],
    '23390-0l070' => [6],
    'bicos injetores' => [6, 19, 20, 21],

    // Filtro Ar-Condicionado (20.000 km)
    'troca filtro ar-condicionado' => [7, 22, 23, 24],
    'filtro cabine' => [7, 22, 23, 24],
    'filtro pólen' => [7, 22, 23, 24],
    'limpeza evaporador' => [7, 22, 23, 24],
    '87139-0k030' => [7],

    // ============================================
    // CATEGORIA: FREIOS
    // ============================================

    // Inspeção Freios (10.000 km)
    'inspeção sistema freios' => [9, 10, 28, 29, 30, 31, 32, 33],
    'verificação visual pastilhas' => [9, 28, 29, 30],
    'lonas traseiras' => [10, 31, 32, 33],
    'disco ventilado' => [9, 28, 29, 30],
    'tambor 295mm' => [10, 31, 32, 33],
    'abs+ebd+ba' => [],  // Inspeção geral, sem peças específicas

    // Troca Pastilhas Dianteiras (60.000 km)
    'troca pastilhas freio dianteiras' => [9, 28, 29, 30],
    'substituição jogo pastilhas' => [9, 28, 29, 30],
    'limpeza pinças' => [9, 28, 29, 30],
    'medição discos' => [9, 28, 29, 30],
    '04465-0k270' => [9],
    'pastilhas originais' => [9],

    // Troca Lonas Traseiras (60.000 km)
    'troca lonas freio traseiras' => [10, 31, 32, 33],
    'substituição lonas tambor' => [10, 31, 32, 33],
    'ajuste folga' => [10, 31, 32, 33],
    'lubrificação came' => [10, 31, 32, 33],
    '04495-0k130' => [10],

    // ============================================
    // CATEGORIA: SUSPENSÃO
    // ============================================

    'inspeção suspensão' => [],  // Inspeção visual, sem peças
    'verificar folgas' => [],
    'pivôs' => [],
    'terminais' => [],
    'amortecedores' => [],  // Peça ainda não cadastrada
    'buchas bandeja' => [],

    // ============================================
    // CATEGORIA: TRANSMISSÃO
    // ============================================

    'inspeção transmissão 4x4' => [],  // Fluidos não cadastrados ainda
    'verificar níveis' => [],
    'atf ws' => [],  // Peça não cadastrada
    'diferencial traseiro' => [],  // Óleo não cadastrado
    'caixa transferência' => [],  // Óleo não cadastrado
    '80w90 gl-5' => [],
    '75w90 gl-4' => [],

    // ============================================
    // CATEGORIA: GERAL
    // ============================================

    'inspeção visual geral' => [],  // Múltiplas verificações
    'calibragem pneus' => [],
    'nível líquidos' => [],
    'bateria' => [],  // Peça não cadastrada
    'correias' => [],  // Correia serpentina não cadastrada
    'mangueiras' => [],

    // ============================================
    // PALAVRAS-CHAVE GENÉRICAS
    // ============================================

    // Óleo Motor (genérico)
    'oleo motor' => [8, 25, 26, 27],
    'óleo motor' => [8, 25, 26, 27],
    'lubrificante motor' => [8, 25, 26, 27],

    // Filtros (genérico)
    'filtro oleo' => [4, 13, 14, 15],
    'filtro óleo' => [4, 13, 14, 15],
    'filtro ar' => [5, 16, 17, 18],
    'filtro diesel' => [6, 19, 20, 21],
    'filtro combustivel' => [6, 19, 20, 21],
    'filtro cabine' => [7, 22, 23, 24],

    // Freios (genérico)
    'freio dianteiro' => [9, 28, 29, 30],
    'freio traseiro' => [10, 31, 32, 33],
    'pastilha dianteira' => [9, 28, 29, 30],
    'pastilha traseira' => [10, 31, 32, 33],
    'lona traseira' => [10, 31, 32, 33],

    // Motor (genérico)
    'vela aquecimento' => [12, 35, 36, 37],
    'corrente' => [11, 34],
    'distribuicao' => [11, 34],
    'distribuição' => [11, 34],
];

// ===================================
// BUSCAR ITENS DO PLANO
// ===================================
$stmt = $conn->prepare("
    SELECT id, descricao_titulo, descricao_observacao, km_recomendado
    FROM Planos_Manutenção
    WHERE modelo_carro = ?
    ORDER BY km_recomendado
");
$stmt->bind_param("s", $modelo);
$stmt->execute();
$result = $stmt->get_result();

$itens = [];
while ($row = $result->fetch_assoc()) {
    $itens[] = $row;
}
$stmt->close();

if (count($itens) === 0) {
    echo json_encode([
        'success' => false,
        'message' => "Nenhum item de plano encontrado para modelo: $modelo"
    ]);
    $conn->close();
    exit();
}

// ===================================
// PROCESSAR CADA ITEM
// ===================================
$associacoes = [];
$erros = [];
$pulos = 0;
$semPecas = 0;

$conn->begin_transaction();

try {
    foreach ($itens as $item) {
        $planoItemId = $item['id'];
        $titulo = mb_strtolower($item['descricao_titulo'], 'UTF-8');
        $descricao = isset($item['descricao_observacao']) ? mb_strtolower($item['descricao_observacao'], 'UTF-8') : '';
        $textoCompleto = $titulo . ' ' . $descricao;

        // Remover acentos para melhor matching
        $textoCompleto = iconv('UTF-8', 'ASCII//TRANSLIT', $textoCompleto);

        // Verificar se já tem peças associadas
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM FF_PlanoManutencao_Pecas
            WHERE plano_item_id = ?
        ");
        $checkStmt->bind_param("i", $planoItemId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkRow = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($checkRow['total'] > 0) {
            $pulos++;
            continue; // Já tem peças, pular
        }

        // Procurar palavras-chave no título + descrição
        $pecasEncontradas = [];

        foreach ($mapeamentos as $palavra => $pecaIds) {
            // Remover acentos da palavra-chave também
            $palavraSemAcento = iconv('UTF-8', 'ASCII//TRANSLIT', $palavra);

            if (strpos($textoCompleto, $palavraSemAcento) !== false) {
                $pecasEncontradas = array_merge($pecasEncontradas, $pecaIds);
            }
        }

        // Remover duplicatas
        $pecasEncontradas = array_unique($pecasEncontradas);

        if (count($pecasEncontradas) === 0) {
            $semPecas++;
            continue;
        }

        // Inserir cada peça encontrada
        $pecasInseridas = 0;

        foreach ($pecasEncontradas as $pecaId) {
            // Verificar se peça existe
            $tipoStmt = $conn->prepare("
                SELECT id, codigo, nome, categoria
                FROM FF_Pecas
                WHERE id = ?
            ");
            $tipoStmt->bind_param("i", $pecaId);
            $tipoStmt->execute();
            $tipoResult = $tipoStmt->get_result();
            $peca = $tipoResult->fetch_assoc();
            $tipoStmt->close();

            if (!$peca) {
                $erros[] = "Peça ID $pecaId não encontrada no banco (item: {$item['descricao_titulo']})";
                continue;
            }

            // Verificar se já existe esta associação específica
            $dupCheckStmt = $conn->prepare("
                SELECT COUNT(*) as total
                FROM FF_PlanoManutencao_Pecas
                WHERE plano_item_id = ? AND peca_id = ?
            ");
            $dupCheckStmt->bind_param("ii", $planoItemId, $pecaId);
            $dupCheckStmt->execute();
            $dupCheckResult = $dupCheckStmt->get_result();
            $dupCheckRow = $dupCheckResult->fetch_assoc();
            $dupCheckStmt->close();

            if ($dupCheckRow['total'] > 0) {
                continue; // Já associada, pular
            }

            // Determinar quantidade padrão baseado na peça
            $quantidade = 1;
            if (strpos(strtolower($peca['nome']), 'jogo') !== false) {
                $quantidade = 1; // Jogo já é completo
            } elseif (strpos(strtolower($peca['nome']), 'vela') !== false) {
                $quantidade = 4; // 4 velas de aquecimento
            } elseif (strpos(strtolower($peca['nome']), 'óleo') !== false ||
                      strpos(strtolower($peca['nome']), 'oleo') !== false) {
                $quantidade = 8; // 8 litros de óleo motor
            }

            // Inserir associação
            $insertStmt = $conn->prepare("
                INSERT INTO FF_PlanoManutencao_Pecas
                (plano_item_id, peca_id, codigo_peca, quantidade, criado_em)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insertStmt->bind_param("iisi", $planoItemId, $pecaId, $peca['codigo'], $quantidade);

            if ($insertStmt->execute()) {
                $pecasInseridas++;

                $associacoes[] = [
                    'item_id' => $planoItemId,
                    'item_titulo' => $item['descricao_titulo'],
                    'item_km' => $item['km_recomendado'],
                    'peca_id' => $pecaId,
                    'peca_nome' => $peca['nome'],
                    'peca_codigo' => $peca['codigo'],
                    'quantidade' => $quantidade
                ];
            } else {
                $erros[] = "Erro ao inserir peça {$peca['nome']} no item {$item['descricao_titulo']}: " . $insertStmt->error;
            }

            $insertStmt->close();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'modelo' => $modelo,
        'estatisticas' => [
            'total_itens_processados' => count($itens),
            'itens_ja_com_pecas' => $pulos,
            'itens_sem_pecas_compativeis' => $semPecas,
            'novas_associacoes' => count($associacoes)
        ],
        'associacoes' => $associacoes,
        'erros' => $erros
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao associar peças',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
