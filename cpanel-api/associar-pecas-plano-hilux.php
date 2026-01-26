<?php
/**
 * Script para associar peças compatíveis aos itens do plano de manutenção
 * Modelo: Toyota HILUX CD
 * Data: 2026-01-09
 *
 * Este script analisa os títulos dos itens do plano e associa automaticamente
 * as peças compatíveis baseado em palavras-chave.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

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

// Modelo que será processado
$modelo = 'HILUX CD';

// ===================================
// MAPEAMENTO: Palavras-chave → IDs de Compatibilidade
// ===================================
// Baseado nos dados retornados pela API de peças compatíveis

$mapeamentos = [
    // FILTROS
    'óleo' => [1],              // ID 1: Filtro de Óleo
    'oleo' => [1],
    'ar motor' => [8],          // ID 8: Filtro de Ar Motor
    'ar do motor' => [8],
    'combustível' => [15],      // ID 15: Filtro Combustível
    'combustivel' => [15],
    'diesel' => [15],
    'cabine' => [22],           // ID 22: Filtro Cabine (A/C)
    'ar condicionado' => [22],

    // FREIOS
    'freio dianteiro' => [36],  // ID 36: Pastilhas Dianteiras
    'pastilha dianteira' => [36],
    'freio traseiro' => [43],   // ID 43: Pastilhas Traseiras
    'pastilha traseira' => [43],

    // MOTOR
    'corrente' => [50],         // ID 50: Corrente Distribuição
    'distribuição' => [50],
    'vela aquecimento' => [53], // ID 53: Vela Aquecimento
    'vela' => [53]
];

// ===================================
// BUSCAR ITENS DO PLANO
// ===================================
$stmt = $conn->prepare("
    SELECT id, descricao_titulo
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

foreach ($itens as $item) {
    $planoItemId = $item['id'];
    $titulo = strtolower($item['descricao_titulo']);

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

    // Procurar palavras-chave no título
    $compatibilidadesEncontradas = [];

    foreach ($mapeamentos as $palavra => $compatibilidadeIds) {
        if (strpos($titulo, $palavra) !== false) {
            $compatibilidadesEncontradas = array_merge($compatibilidadesEncontradas, $compatibilidadeIds);
        }
    }

    // Remover duplicatas
    $compatibilidadesEncontradas = array_unique($compatibilidadesEncontradas);

    if (count($compatibilidadesEncontradas) === 0) {
        // Nenhuma peça compatível encontrada para este item
        continue;
    }

    // Para cada compatibilidade encontrada, buscar as peças (original + similares)
    foreach ($compatibilidadesEncontradas as $compatibilidadeId) {
        // Buscar peça original
        $pecaStmt = $conn->prepare("
            SELECT peca_original_id, peca_similar_id, quantidade_recomendada
            FROM FF_Pecas_Compatibilidade
            WHERE id = ?
        ");
        $pecaStmt->bind_param("i", $compatibilidadeId);
        $pecaStmt->execute();
        $pecaResult = $pecaStmt->get_result();
        $compatibilidade = $pecaResult->fetch_assoc();
        $pecaStmt->close();

        if (!$compatibilidade) continue;

        // Inserir peça ORIGINAL
        if ($compatibilidade['peca_original_id']) {
            try {
                $insertStmt = $conn->prepare("
                    INSERT INTO FF_PlanoManutencao_Pecas
                    (plano_item_id, peca_id, quantidade, tipo_peca)
                    VALUES (?, ?, ?, 'original')
                ");
                $quantidade = $compatibilidade['quantidade_recomendada'] ?? 1;
                $insertStmt->bind_param("iii", $planoItemId, $compatibilidade['peca_original_id'], $quantidade);
                $insertStmt->execute();
                $insertStmt->close();

                $associacoes[] = [
                    'item_id' => $planoItemId,
                    'item_titulo' => $item['descricao_titulo'],
                    'compatibilidade_id' => $compatibilidadeId,
                    'peca_id' => $compatibilidade['peca_original_id'],
                    'tipo' => 'original',
                    'quantidade' => $quantidade
                ];
            } catch (Exception $e) {
                $erros[] = "Erro ao inserir peça original ID {$compatibilidade['peca_original_id']} no item $planoItemId: " . $e->getMessage();
            }
        }

        // Buscar e inserir peças SIMILARES
        $similaresStmt = $conn->prepare("
            SELECT peca_similar_id
            FROM FF_Pecas_Compatibilidade
            WHERE peca_original_id = ? AND peca_similar_id IS NOT NULL
        ");
        $similaresStmt->bind_param("i", $compatibilidade['peca_original_id']);
        $similaresStmt->execute();
        $similaresResult = $similaresStmt->get_result();

        while ($similar = $similaresResult->fetch_assoc()) {
            try {
                $insertStmt = $conn->prepare("
                    INSERT INTO FF_PlanoManutencao_Pecas
                    (plano_item_id, peca_id, quantidade, tipo_peca)
                    VALUES (?, ?, ?, 'similar')
                ");
                $quantidade = $compatibilidade['quantidade_recomendada'] ?? 1;
                $insertStmt->bind_param("iii", $planoItemId, $similar['peca_similar_id'], $quantidade);
                $insertStmt->execute();
                $insertStmt->close();

                $associacoes[] = [
                    'item_id' => $planoItemId,
                    'item_titulo' => $item['descricao_titulo'],
                    'compatibilidade_id' => $compatibilidadeId,
                    'peca_id' => $similar['peca_similar_id'],
                    'tipo' => 'similar',
                    'quantidade' => $quantidade
                ];
            } catch (Exception $e) {
                $erros[] = "Erro ao inserir peça similar ID {$similar['peca_similar_id']} no item $planoItemId: " . $e->getMessage();
            }
        }
        $similaresStmt->close();
    }
}

// ===================================
// RESULTADO FINAL
// ===================================
echo json_encode([
    'success' => true,
    'modelo' => $modelo,
    'total_itens_processados' => count($itens),
    'itens_com_pecas_ja_associadas' => $pulos,
    'novas_associacoes' => count($associacoes),
    'erros' => count($erros),
    'detalhes' => [
        'associacoes' => $associacoes,
        'erros' => $erros
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>
