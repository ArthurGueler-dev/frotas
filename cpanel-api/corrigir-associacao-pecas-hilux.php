<?php
/**
 * Script DEFINITIVO para corrigir associação de peças aos itens do plano
 * Usa os IDs REAIS das peças já cadastradas no banco
 * Data: 2026-01-09
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar',
        'warning' => 'Isso irá LIMPAR associações antigas e criar novas baseadas nas peças REALMENTE cadastradas'
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
        'error' => 'Erro de conexão',
        'details' => $e->getMessage()
    ]);
    exit();
}

$modelo = 'HILUX CD';

// ===================================
// FUNÇÃO: Buscar ID da peça por palavras-chave
// ===================================
function buscarPecaPorNome($conn, $palavrasChave) {
    $pecasEncontradas = [];

    foreach ($palavrasChave as $palavra) {
        $stmt = $conn->prepare("
            SELECT id, codigo, nome, categoria
            FROM FF_Pecas
            WHERE (nome LIKE ? OR codigo LIKE ?)
              AND (modelo_compativel LIKE '%HILUX%' OR nome LIKE '%Hilux%')
            ORDER BY tipo = 'original' DESC
            LIMIT 10
        ");
        $palavraLike = '%' . $palavra . '%';
        $stmt->bind_param("ss", $palavraLike, $palavraLike);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $pecasEncontradas[] = $row;
        }

        $stmt->close();
    }

    // Remover duplicatas pelo ID
    $pecasUnicas = [];
    $idsVistos = [];
    foreach ($pecasEncontradas as $peca) {
        if (!in_array($peca['id'], $idsVistos)) {
            $pecasUnicas[] = $peca;
            $idsVistos[] = $peca['id'];
        }
    }

    return $pecasUnicas;
}

// ===================================
// MAPEAMENTO: Item do Plano → Palavras-chave para buscar peças
// ===================================
$mapeamento = [
    // 10.000 km
    [
        'km' => 10000,
        'titulo_busca' => 'Troca de Óleo Motor',
        'palavras_chave' => ['filtro óleo', 'filtro de óleo', 'óleo motor', 'óleo 5W', '5W30', '5W-30'],
        'quantidade_default' => 1
    ],

    // 20.000 km - FILTRO AR MOTOR
    [
        'km' => 20000,
        'titulo_busca' => 'Troca Filtro Ar Motor',
        'palavras_chave' => ['filtro ar motor', 'filtro de ar motor', '17801', 'ARS7065', 'C25124'],
        'quantidade_default' => 1
    ],

    // 20.000 km - FILTRO COMBUSTÍVEL
    [
        'km' => 20000,
        'titulo_busca' => 'Troca Filtro Combustível',
        'palavras_chave' => ['filtro combustível', 'filtro diesel', '23300', '23390', 'PC953'],
        'quantidade_default' => 1
    ],

    // 20.000 km - FILTRO AR-CONDICIONADO
    [
        'km' => 20000,
        'titulo_busca' => 'Troca Filtro Ar-Condicionado',
        'palavras_chave' => ['filtro cabine', 'filtro ar condicionado', 'filtro pólen', '87139', 'ACP889'],
        'quantidade_default' => 1
    ],

    // 30.000 km - FLUIDO FREIO
    [
        'km' => 30000,
        'titulo_busca' => 'Troca Fluido Freio',
        'palavras_chave' => ['fluido freio', 'DOT 3', 'DOT 4', 'fluido de freio'],
        'quantidade_default' => 1
    ],

    // 40.000 km - ÓLEO DIFERENCIAL
    [
        'km' => 40000,
        'titulo_busca' => 'Troca Óleo Diferencial Traseiro',
        'palavras_chave' => ['óleo diferencial', '80W90', 'diferencial traseiro', '08885-81080'],
        'quantidade_default' => 3
    ],

    // 40.000 km - ÓLEO TRANSFER
    [
        'km' => 40000,
        'titulo_busca' => 'Troca Óleo Caixa Transferência',
        'palavras_chave' => ['óleo transfer', '75W90', 'transferência', '08885-81081'],
        'quantidade_default' => 2
    ],

    // 50.000 km - ATF TRANSMISSÃO
    [
        'km' => 50000,
        'titulo_busca' => 'Troca Óleo Transmissão Automática',
        'palavras_chave' => ['ATF WS', 'transmissão automática', '08886-02505', 'Aisin'],
        'quantidade_default' => 10
    ],

    // 50.000 km - FLUIDO DIREÇÃO
    [
        'km' => 50000,
        'titulo_busca' => 'Troca Fluido Direção Hidráulica',
        'palavras_chave' => ['fluido direção', 'direção hidráulica', 'Dexron III', '08886-01206'],
        'quantidade_default' => 2
    ],

    // 50.000 km - VELAS AQUECIMENTO
    [
        'km' => 50000,
        'titulo_busca' => 'Substituição Velas Aquecimento',
        'palavras_chave' => ['vela aquecimento', 'glow plug', '19850', 'velas diesel'],
        'quantidade_default' => 1
    ],

    // 60.000 km - PASTILHAS DIANTEIRAS
    [
        'km' => 60000,
        'titulo_busca' => 'Troca Pastilhas Freio Dianteiras',
        'palavras_chave' => ['pastilha dianteira', 'pastilhas freio dianteira', '04465', 'PD528', 'BB528'],
        'quantidade_default' => 1
    ],

    // 60.000 km - LONAS TRASEIRAS
    [
        'km' => 60000,
        'titulo_busca' => 'Troca Lonas Freio Traseiras',
        'palavras_chave' => ['lona traseira', 'lonas freio traseira', 'sapata', '04495', 'FJ1869'],
        'quantidade_default' => 1
    ],

    // 80.000 km - CORREIA SERPENTINA
    [
        'km' => 80000,
        'titulo_busca' => 'Substituição Correia Serpentina',
        'palavras_chave' => ['correia serpentina', 'correia acessórios', 'correia poli', '16620', 'K060965'],
        'quantidade_default' => 1
    ],

    // 80.000 km - ÓLEO DIFERENCIAL DIANTEIRO
    [
        'km' => 80000,
        'titulo_busca' => 'Troca Óleo Diferencial Dianteiro',
        'palavras_chave' => ['diferencial dianteiro', '80W90', '08885-81080'],
        'quantidade_default' => 2
    ],

    // 90.000 km - BOMBA D'ÁGUA
    [
        'km' => 90000,
        'titulo_busca' => 'Substituição Bomba Água',
        'palavras_chave' => ['bomba água', 'bomba d\'água', '16100', 'bomba dagua'],
        'quantidade_default' => 1
    ],

    // 100.000 km - LÍQUIDO ARREFECIMENTO
    [
        'km' => 100000,
        'titulo_busca' => 'Troca Líquido Arrefecimento',
        'palavras_chave' => ['líquido arrefecimento', 'SLLC', 'radiador', '08889', 'coolant'],
        'quantidade_default' => 10
    ],

    // 100.000 km - AMORTECEDORES
    [
        'km' => 100000,
        'titulo_busca' => 'Substituição Amortecedores',
        'palavras_chave' => ['amortecedor', '48510', '48530'],
        'quantidade_default' => 1
    ],

    // 120.000 km - DISCOS FREIO DIANTEIROS
    [
        'km' => 120000,
        'titulo_busca' => 'Troca Pastilhas + Discos Dianteiros',
        'palavras_chave' => ['disco freio', 'disco dianteiro', '43512', 'BD5449', 'pastilha dianteira'],
        'quantidade_default' => 1
    ],
];

// ===================================
// LIMPAR ASSOCIAÇÕES ANTIGAS DO MODELO
// ===================================
$stmtLimpar = $conn->prepare("
    DELETE pp FROM FF_PlanoManutencao_Pecas pp
    INNER JOIN Planos_Manutenção pm ON pp.plano_item_id = pm.id
    WHERE pm.modelo_carro = ?
");
$stmtLimpar->bind_param("s", $modelo);
$stmtLimpar->execute();
$limpas = $stmtLimpar->affected_rows;
$stmtLimpar->close();

// ===================================
// PROCESSAR CADA MAPEAMENTO
// ===================================
$associacoes = [];
$naoEncontrados = [];
$erros = [];

$conn->begin_transaction();

try {
    foreach ($mapeamento as $config) {
        $km = $config['km'];
        $tituloBusca = $config['titulo_busca'];
        $palavrasChave = $config['palavras_chave'];
        $quantidadeDefault = $config['quantidade_default'];

        // Buscar item do plano
        $stmtItem = $conn->prepare("
            SELECT id, descricao_titulo
            FROM Planos_Manutenção
            WHERE modelo_carro = ?
              AND km_recomendado = ?
              AND descricao_titulo LIKE ?
            LIMIT 1
        ");
        $tituloBuscaLike = '%' . $tituloBusca . '%';
        $stmtItem->bind_param("sis", $modelo, $km, $tituloBuscaLike);
        $stmtItem->execute();
        $resultItem = $stmtItem->get_result();
        $itemPlano = $resultItem->fetch_assoc();
        $stmtItem->close();

        if (!$itemPlano) {
            $naoEncontrados[] = "Item não encontrado: $tituloBusca ($km km)";
            continue;
        }

        // Buscar peças compatíveis
        $pecas = buscarPecaPorNome($conn, $palavrasChave);

        if (count($pecas) === 0) {
            $naoEncontrados[] = "Nenhuma peça encontrada para: $tituloBusca ($km km)";
            continue;
        }

        // Associar cada peça encontrada
        foreach ($pecas as $peca) {
            // Determinar quantidade
            $quantidade = $quantidadeDefault;

            // Ajustar quantidade baseado no tipo de peça
            if (stripos($peca['nome'], 'óleo') !== false || stripos($peca['nome'], 'oleo') !== false) {
                if (stripos($tituloBusca, 'motor') !== false) {
                    $quantidade = 8; // 8L óleo motor
                } elseif (stripos($tituloBusca, 'transmissão') !== false) {
                    $quantidade = 10; // 10L ATF
                } elseif (stripos($tituloBusca, 'diferencial') !== false) {
                    $quantidade = 3; // 3L diferencial
                } elseif (stripos($tituloBusca, 'arrefecimento') !== false) {
                    $quantidade = 10; // 10L coolant
                }
            }

            // Inserir associação
            $stmtInsert = $conn->prepare("
                INSERT INTO FF_PlanoManutencao_Pecas
                (plano_item_id, peca_id, codigo_peca, quantidade, criado_em)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtInsert->bind_param("iisi", $itemPlano['id'], $peca['id'], $peca['codigo'], $quantidade);

            if ($stmtInsert->execute()) {
                $associacoes[] = [
                    'km' => $km,
                    'item_titulo' => $itemPlano['descricao_titulo'],
                    'peca_id' => $peca['id'],
                    'peca_codigo' => $peca['codigo'],
                    'peca_nome' => $peca['nome'],
                    'quantidade' => $quantidade
                ];
            } else {
                $erros[] = "Erro ao associar {$peca['codigo']} a {$itemPlano['descricao_titulo']}: " . $stmtInsert->error;
            }

            $stmtInsert->close();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'modelo' => $modelo,
        'estatisticas' => [
            'associacoes_antigas_removidas' => $limpas,
            'itens_processados' => count($mapeamento),
            'novas_associacoes' => count($associacoes),
            'itens_nao_encontrados' => count($naoEncontrados),
            'erros' => count($erros)
        ],
        'associacoes' => $associacoes,
        'nao_encontrados' => $naoEncontrados,
        'erros' => $erros
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
