<?php
/**
 * Script para associar peças compatíveis aos itens do plano de manutenção
 * Modelo: Toyota HILUX CD
 * Data: 2026-01-09
 * Versão: 2.0 - Usando API de peças compatíveis
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar a associação',
        'warning' => 'Isso irá associar peças aos itens do plano'
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
// MAPEAMENTO: Palavras-chave → Peças
// ===================================
// Baseado nos IDs reais das peças cadastradas

$mapeamentos = [
    // FILTROS (categoria_aplicacao: Filtros)
    'óleo motor' => [4, 13, 14, 15],           // Filtro Óleo Original + Similares (Tecfil, Mann, Wega)
    'oleo motor' => [4, 13, 14, 15],
    'troca de óleo' => [4, 13, 14, 15],
    'troca de oleo' => [4, 13, 14, 15],

    'ar motor' => [5, 16, 17, 18],             // Filtro Ar Motor Original + Similares
    'filtro de ar' => [5, 16, 17, 18],

    'combustível' => [6, 19, 20, 21],          // Filtro Combustível Original + Similares
    'combustivel' => [6, 19, 20, 21],
    'diesel' => [6, 19, 20, 21],

    'cabine' => [7, 22, 23, 24],               // Filtro Cabine Original + Similares
    'ar condicionado' => [7, 22, 23, 24],

    // FREIOS (categoria_aplicacao: Freios)
    'freio dianteiro' => [9, 28, 29, 30],      // Pastilhas Dianteiras Original + Similares
    'pastilha dianteira' => [9, 28, 29, 30],
    'disco dianteiro' => [9, 28, 29, 30],

    'freio traseiro' => [10, 31, 32, 33],      // Pastilhas Traseiras Original + Similares
    'pastilha traseira' => [10, 31, 32, 33],

    // MOTOR (categoria_aplicacao: Motor)
    'corrente' => [11, 34],                    // Corrente Distribuição Original + Similar
    'distribuição' => [11, 34],

    'vela aquecimento' => [12, 35, 36, 37],    // Vela Aquecimento Original + Similares
    'vela' => [12, 35, 36, 37],

    // ÓLEOS (categoria_aplicacao: Óleos)
    'lubrificante' => [8, 25, 26, 27],         // Óleo Motor 5W-30 Original + Similares
];

// ===================================
// BUSCAR ITENS DO PLANO
// ===================================
$stmt = $conn->prepare("
    SELECT id, descricao_titulo, descricao_observacao
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

$conn->begin_transaction();

try {
    foreach ($itens as $item) {
        $planoItemId = $item['id'];
        $titulo = strtolower($item['descricao_titulo']);
        $descricao = isset($item['descricao_observacao']) ? strtolower($item['descricao_observacao']) : '';
        $textoCompleto = $titulo . ' ' . $descricao;

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
            if (strpos($textoCompleto, $palavra) !== false) {
                $pecasEncontradas = array_merge($pecasEncontradas, $pecaIds);
            }
        }

        // Remover duplicatas
        $pecasEncontradas = array_unique($pecasEncontradas);

        if (count($pecasEncontradas) === 0) {
            // Nenhuma peça compatível encontrada para este item
            continue;
        }

        // Inserir cada peça encontrada
        foreach ($pecasEncontradas as $pecaId) {
            // Verificar tipo da peça (original ou similar)
            $tipoStmt = $conn->prepare("
                SELECT codigo, nome, categoria
                FROM FF_Pecas
                WHERE id = ?
            ");
            $tipoStmt->bind_param("i", $pecaId);
            $tipoStmt->execute();
            $tipoResult = $tipoStmt->get_result();
            $peca = $tipoResult->fetch_assoc();
            $tipoStmt->close();

            if (!$peca) continue;

            // Determinar se é original ou similar baseado no nome
            $tipo = (strpos(strtolower($peca['nome']), 'original') !== false) ? 'original' : 'similar';

            // Inserir associação
            $insertStmt = $conn->prepare("
                INSERT INTO FF_PlanoManutencao_Pecas
                (plano_item_id, peca_id, codigo_peca, quantidade, criado_em)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $insertStmt->bind_param("iis", $planoItemId, $pecaId, $peca['codigo']);
            $insertStmt->execute();
            $insertStmt->close();

            $associacoes[] = [
                'item_id' => $planoItemId,
                'item_titulo' => $item['descricao_titulo'],
                'peca_id' => $pecaId,
                'peca_nome' => $peca['nome'],
                'tipo' => $tipo
            ];
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'modelo' => $modelo,
        'total_itens_processados' => count($itens),
        'itens_ja_com_pecas' => $pulos,
        'novas_associacoes' => count($associacoes),
        'detalhes' => $associacoes
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
