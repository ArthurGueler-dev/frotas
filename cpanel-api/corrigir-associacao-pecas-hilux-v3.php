<?php
/**
 * Script CORRIGIDO V3 - SEM colunas que não existem
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
        'warning' => 'Isso irá LIMPAR associações antigas e criar novas corretas'
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
// FUNÇÃO: Buscar peças por palavras-chave
// ===================================
function buscarPecaPorNome($conn, $palavrasChave) {
    $pecasEncontradas = [];

    foreach ($palavrasChave as $palavra) {
        $stmt = $conn->prepare("
            SELECT id, codigo, nome, categoria
            FROM FF_Pecas
            WHERE nome LIKE ? OR codigo LIKE ?
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
// MAPEAMENTO
// ===================================
$mapeamento = [
    // 10.000 km - ÓLEO MOTOR
    [
        'km' => 10000,
        'titulo_busca' => 'Troca de Óleo Motor',
        'palavras_chave' => ['Filtro de Óleo', 'Filtro Óleo', 'Óleo Motor', 'Óleo 5W', '5W30', '5W-30', 'Lubrax', 'Mobil', 'Shell'],
        'quantidade_default' => 1
    ],

    // 20.000 km - FILTRO AR MOTOR
    [
        'km' => 20000,
        'titulo_busca' => 'Troca Filtro Ar Motor',
        'palavras_chave' => ['Filtro de Ar Motor', 'Filtro Ar Motor', 'ARS', 'C25124', 'WA10448'],
        'quantidade_default' => 1
    ],

    // 20.000 km - FILTRO COMBUSTÍVEL
    [
        'km' => 20000,
        'titulo_busca' => 'Troca Filtro Combustível',
        'palavras_chave' => ['Filtro de Combustível', 'Filtro Combustível', 'PC953', 'PU9023', 'KX570'],
        'quantidade_default' => 1
    ],

    // 20.000 km - FILTRO AR-CONDICIONADO
    [
        'km' => 20000,
        'titulo_busca' => 'Troca Filtro Ar-Condicionado',
        'palavras_chave' => ['Filtro de Cabine', 'Filtro Cabine', 'Ar Condicionado', 'ACP', 'CU22032', '1987435590'],
        'quantidade_default' => 1
    ],

    // 30.000 km - FLUIDO FREIO
    [
        'km' => 30000,
        'titulo_busca' => 'Troca Fluido Freio',
        'palavras_chave' => ['Fluido de Freio', 'Fluido Freio', 'DOT 3', 'DOT 4', 'DOT3', 'DOT4'],
        'quantidade_default' => 1
    ],

    // 40.000 km - ÓLEO DIFERENCIAL
    [
        'km' => 40000,
        'titulo_busca' => 'Troca Óleo Diferencial Traseiro',
        'palavras_chave' => ['Óleo Diferencial', 'Diferencial', '80W90', 'GL-5', 'Ipiranga', 'Mobilube', 'Spirax'],
        'quantidade_default' => 3
    ],

    // 40.000 km - ÓLEO TRANSFER
    [
        'km' => 40000,
        'titulo_busca' => 'Troca Óleo Caixa Transferência',
        'palavras_chave' => ['Óleo Transfer', 'Transfer Case', '75W90', 'GL-4'],
        'quantidade_default' => 2
    ],

    // 50.000 km - ATF TRANSMISSÃO
    [
        'km' => 50000,
        'titulo_busca' => 'Troca Óleo Transmissão Automática',
        'palavras_chave' => ['ATF WS', 'ATF', 'Transmissão Automática', 'Aisin'],
        'quantidade_default' => 10
    ],

    // 50.000 km - FLUIDO DIREÇÃO
    [
        'km' => 50000,
        'titulo_busca' => 'Troca Fluido Direção Hidráulica',
        'palavras_chave' => ['Fluido Direção', 'Direção Hidráulica', 'Dexron', 'ATF 220'],
        'quantidade_default' => 2
    ],

    // 50.000 km - VELAS AQUECIMENTO
    [
        'km' => 50000,
        'titulo_busca' => 'Substituição Velas Aquecimento',
        'palavras_chave' => ['Vela Aquecimento', 'Velas Aquecimento', 'Glow Plug', 'Y-715', 'GV968', 'Duraterm'],
        'quantidade_default' => 1
    ],

    // 60.000 km - PASTILHAS DIANTEIRAS
    [
        'km' => 60000,
        'titulo_busca' => 'Troca Pastilhas Freio Dianteiras',
        'palavras_chave' => ['Pastilha Dianteira', 'Pastilhas Dianteira', 'Freio Dianteira', 'PD528', 'BB528', 'RCPT09460'],
        'quantidade_default' => 1
    ],

    // 60.000 km - LONAS TRASEIRAS
    [
        'km' => 60000,
        'titulo_busca' => 'Troca Lonas Freio Traseiras',
        'palavras_chave' => ['Lona Traseira', 'Lonas Traseira', 'Freio Traseira', 'Sapata', 'FJ1869', 'BB0341', 'RCPT08670'],
        'quantidade_default' => 1
    ],

    // 80.000 km - CORREIA SERPENTINA
    [
        'km' => 80000,
        'titulo_busca' => 'Substituição Correia Serpentina',
        'palavras_chave' => ['Correia Serpentina', 'Correia', 'Poli-V', 'K060965', '6PK2465', 'Gates', 'Continental', 'Dayco'],
        'quantidade_default' => 1
    ],

    // 80.000 km - ÓLEO DIFERENCIAL DIANTEIRO
    [
        'km' => 80000,
        'titulo_busca' => 'Troca Óleo Diferencial Dianteiro',
        'palavras_chave' => ['Óleo Diferencial', 'Diferencial Dianteiro', '80W90'],
        'quantidade_default' => 2
    ],

    // 90.000 km - BOMBA D'ÁGUA
    [
        'km' => 90000,
        'titulo_busca' => 'Substituição Bomba Água',
        'palavras_chave' => ['Bomba Água', 'Bomba d\'Água', 'Bomba dagua', 'Nakata', 'Gates', 'Urba'],
        'quantidade_default' => 1
    ],

    // 100.000 km - LÍQUIDO ARREFECIMENTO
    [
        'km' => 100000,
        'titulo_busca' => 'Troca Líquido Arrefecimento',
        'palavras_chave' => ['Líquido Arrefecimento', 'SLLC', 'Arrefecimento', 'Coolant', 'Glysantin', 'Antifreeze', 'Koiler'],
        'quantidade_default' => 10
    ],

    // 100.000 km - AMORTECEDORES
    [
        'km' => 100000,
        'titulo_busca' => 'Substituição Amortecedores',
        'palavras_chave' => ['Amortecedor', 'TurboGas', 'Monroe'],
        'quantidade_default' => 1
    ],

    // 120.000 km - DISCOS E PASTILHAS
    [
        'km' => 120000,
        'titulo_busca' => 'Troca Pastilhas + Discos Dianteiros',
        'palavras_chave' => ['Disco Freio', 'Discos Freio', 'Disco Dianteiro', 'Pastilha Dianteira', 'BD5449', 'DF7087'],
        'quantidade_default' => 1
    ],
];

// ===================================
// LIMPAR ASSOCIAÇÕES ANTIGAS
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
// PROCESSAR
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

        // Buscar peças
        $pecas = buscarPecaPorNome($conn, $palavrasChave);

        if (count($pecas) === 0) {
            $naoEncontrados[] = "Nenhuma peça encontrada para: $tituloBusca ($km km)";
            continue;
        }

        // Associar cada peça
        foreach ($pecas as $peca) {
            // Determinar quantidade
            $quantidade = $quantidadeDefault;

            // Ajustar para óleos
            if (stripos($peca['nome'], 'óleo') !== false || stripos($peca['nome'], 'oleo') !== false) {
                if (stripos($tituloBusca, 'motor') !== false || stripos($peca['nome'], 'motor') !== false) {
                    $quantidade = 8;
                } elseif (stripos($tituloBusca, 'transmissão') !== false) {
                    $quantidade = 10;
                } elseif (stripos($tituloBusca, 'diferencial traseiro') !== false) {
                    $quantidade = 3;
                } elseif (stripos($tituloBusca, 'diferencial dianteiro') !== false) {
                    $quantidade = 2;
                } elseif (stripos($tituloBusca, 'arrefecimento') !== false) {
                    $quantidade = 10;
                }
            }

            // Inserir
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
                $erros[] = "Erro ao associar {$peca['codigo']}: " . $stmtInsert->error;
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
