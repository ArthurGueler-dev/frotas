<?php
/**
 * Script para associar peças mencionadas pelo Perplexity aos itens do plano de manutenção
 * Baseado na resposta EXATA do Perplexity para Hilux 2021
 * Data: 2026-01-09
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar a associação',
        'warning' => 'Isso irá associar as peças mencionadas pelo Perplexity aos itens do plano'
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
// MAPEAMENTO DIRETO: KM → CÓDIGOS DE PEÇAS MENCIONADAS PELO PERPLEXITY
// ===================================
// Estrutura: KM => ['titulo_busca' => string, 'codigos' => [array de códigos], 'quantidade' => [array de quantidades]]

$mapeamentoPerplexity = [
    // 10.000 km - Troca Óleo Motor + Filtro
    10000 => [
        [
            'titulo_busca' => 'Troca de Óleo Motor',
            'codigos' => ['90915-YZZJ2', '90915-YZZD2', 'JFO0211', '08880-10705'],
            'quantidades' => [1, 1, 1, 8], // 1 filtro, 8L óleo
            'descricao' => 'Filtro óleo blindado + 8L óleo 5W-30 ACEA C2/C3'
        ]
    ],

    // 20.000 km - Troca Filtro Ar Motor
    20000 => [
        [
            'titulo_busca' => 'Troca Filtro Ar Motor',
            'codigos' => ['17801-0L050', '17801-AZG03'],
            'quantidades' => [1, 1],
            'descricao' => 'Elemento filtro ar primário'
        ],
        [
            'titulo_busca' => 'Troca Filtro Combustível',
            'codigos' => ['23300-0L041', '23390-0L070'],
            'quantidades' => [1, 1],
            'descricao' => 'Filtro combustível principal + secundário'
        ],
        [
            'titulo_busca' => 'Troca Filtro Ar-Condicionado',
            'codigos' => ['87139-0K030', '87139-58010', '87139-0K070'],
            'quantidades' => [1, 1, 1],
            'descricao' => 'Filtro cabine/pólen'
        ]
    ],

    // 30.000 km - Troca Fluido Freio
    30000 => [
        [
            'titulo_busca' => 'Troca Fluido Freio',
            'codigos' => ['08823-80001'],
            'quantidades' => [1],
            'descricao' => 'Fluido DOT 3 (~1L sistema completo)'
        ]
    ],

    // 40.000 km - Troca Óleo Diferencial
    40000 => [
        [
            'titulo_busca' => 'Troca Óleo Diferencial Traseiro',
            'codigos' => ['08885-81080'],
            'quantidades' => [3], // 2,6L ~ 3 litros
            'descricao' => 'Óleo diferencial 80W90 GL-5 (2,6L)'
        ],
        [
            'titulo_busca' => 'Troca Óleo Caixa Transferência',
            'codigos' => ['08885-81081'],
            'quantidades' => [2], // 1,3L ~ 2 litros
            'descricao' => 'Óleo transfer 75W90 GL-4 (1,3L)'
        ]
    ],

    // 50.000 km - Troca ATF + Velas
    50000 => [
        [
            'titulo_busca' => 'Troca Óleo Transmissão Automática',
            'codigos' => ['08886-02505'],
            'quantidades' => [10], // 9,5L ~ 10 litros
            'descricao' => 'ATF WS Aisin (9,5L com conversor)'
        ],
        [
            'titulo_busca' => 'Troca Fluido Direção Hidráulica',
            'codigos' => ['08886-01206'],
            'quantidades' => [2], // 1,2L ~ 2 litros
            'descricao' => 'Fluido direção ATF Dexron III (1,2L)'
        ],
        [
            'titulo_busca' => 'Substituição Velas Aquecimento',
            'codigos' => ['19850-0L020', '19850-30060'],
            'quantidades' => [1, 1], // 1 jogo (4 unidades)
            'descricao' => 'Jogo 4 velas aquecimento (glow plugs)'
        ]
    ],

    // 60.000 km - Pastilhas + Lonas + Bateria
    60000 => [
        [
            'titulo_busca' => 'Troca Pastilhas Freio Dianteiras',
            'codigos' => ['04465-0K270', '04465-0K280'],
            'quantidades' => [1, 1], // 1 jogo
            'descricao' => 'Jogo pastilhas dianteiras (4 peças)'
        ],
        [
            'titulo_busca' => 'Troca Lonas Freio Traseiras',
            'codigos' => ['04495-0K130'],
            'quantidades' => [1],
            'descricao' => 'Jogo lonas traseiras (tambor 295mm)'
        ]
    ],

    // 80.000 km - Correia Serpentina
    80000 => [
        [
            'titulo_busca' => 'Substituição Correia Serpentina',
            'codigos' => ['16620-59265'],
            'quantidades' => [1],
            'descricao' => 'Kit correia poli-V + tensores + rolamentos'
        ],
        [
            'titulo_busca' => 'Troca Óleo Diferencial Dianteiro',
            'codigos' => ['08885-81080'],
            'quantidades' => [2], // 1,7L ~ 2 litros
            'descricao' => 'Óleo diferencial dianteiro 80W90 GL-5 (1,7L) - Específico 4x4'
        ]
    ],

    // 90.000 km - Bomba d'Água
    90000 => [
        [
            'titulo_busca' => 'Substituição Bomba Água',
            'codigos' => ['16100-59275'],
            'quantidades' => [1],
            'descricao' => 'Bomba água motor (preventivo 90.000 km)'
        ]
    ],

    // 100.000 km - Líquido Arrefecimento + ATF + Amortecedores
    100000 => [
        [
            'titulo_busca' => 'Troca Líquido Arrefecimento',
            'codigos' => ['08889-80015'],
            'quantidades' => [10], // 10L concentrado 50%
            'descricao' => 'Líquido arrefecimento SLLC (10L concentrado 50%)'
        ],
        [
            'titulo_busca' => 'Substituição Amortecedores',
            'codigos' => ['48510-0K260', '48530-0K250'],
            'quantidades' => [2, 2], // 2 dianteiros + 2 traseiros
            'descricao' => '4 amortecedores (2 dianteiros + 2 traseiros)'
        ]
    ],

    // 120.000 km - Discos + Pastilhas
    120000 => [
        [
            'titulo_busca' => 'Troca Pastilhas + Discos Dianteiros',
            'codigos' => ['04465-0K270', '43512-0K070'],
            'quantidades' => [1, 1], // 1 jogo pastilhas + 1 par discos
            'descricao' => 'Pastilhas + discos ventilados (par)'
        ],
        [
            'titulo_busca' => 'Troca Óleo Diferencial + Transfer',
            'codigos' => ['08885-81080', '08885-81081'],
            'quantidades' => [5, 2], // 2,6L+1,7L traseiro/dianteiro + 1,3L transfer
            'descricao' => 'Óleos diferencial traseiro/dianteiro + transfer'
        ]
    ],
];

// ===================================
// PROCESSAR CADA KM
// ===================================
$associacoes = [];
$erros = [];
$naoEncontradas = [];

$conn->begin_transaction();

try {
    foreach ($mapeamentoPerplexity as $km => $itensKm) {
        foreach ($itensKm as $itemConfig) {
            $tituloBusca = $itemConfig['titulo_busca'];
            $codigos = $itemConfig['codigos'];
            $quantidades = $itemConfig['quantidades'];
            $descricaoConfig = $itemConfig['descricao'];

            // Buscar item do plano no banco
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
                $naoEncontradas[] = "Item não encontrado: $tituloBusca ($km km)";
                continue;
            }

            $planoItemId = $itemPlano['id'];

            // Para cada código de peça mencionado
            foreach ($codigos as $index => $codigo) {
                $quantidade = isset($quantidades[$index]) ? $quantidades[$index] : 1;

                // Buscar peça no banco pelo código
                $stmtPeca = $conn->prepare("
                    SELECT id, codigo, nome
                    FROM FF_Pecas
                    WHERE codigo = ?
                    LIMIT 1
                ");
                $stmtPeca->bind_param("s", $codigo);
                $stmtPeca->execute();
                $resultPeca = $stmtPeca->get_result();
                $peca = $resultPeca->fetch_assoc();
                $stmtPeca->close();

                if (!$peca) {
                    $naoEncontradas[] = "Peça não cadastrada: $codigo ($tituloBusca - $km km)";
                    continue;
                }

                // Verificar se já existe associação
                $stmtCheck = $conn->prepare("
                    SELECT COUNT(*) as total
                    FROM FF_PlanoManutencao_Pecas
                    WHERE plano_item_id = ? AND peca_id = ?
                ");
                $stmtCheck->bind_param("ii", $planoItemId, $peca['id']);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                $rowCheck = $resultCheck->fetch_assoc();
                $stmtCheck->close();

                if ($rowCheck['total'] > 0) {
                    continue; // Já associada, pular
                }

                // Inserir associação
                $stmtInsert = $conn->prepare("
                    INSERT INTO FF_PlanoManutencao_Pecas
                    (plano_item_id, peca_id, codigo_peca, quantidade, criado_em)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmtInsert->bind_param("iisi", $planoItemId, $peca['id'], $codigo, $quantidade);

                if ($stmtInsert->execute()) {
                    $associacoes[] = [
                        'km' => $km,
                        'item_titulo' => $itemPlano['descricao_titulo'],
                        'peca_codigo' => $codigo,
                        'peca_nome' => $peca['nome'],
                        'quantidade' => $quantidade
                    ];
                } else {
                    $erros[] = "Erro ao associar $codigo a {$itemPlano['descricao_titulo']}: " . $stmtInsert->error;
                }

                $stmtInsert->close();
            }
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'modelo' => $modelo,
        'estatisticas' => [
            'kms_processados' => count($mapeamentoPerplexity),
            'associacoes_criadas' => count($associacoes),
            'itens_nao_encontrados' => count($naoEncontradas),
            'erros' => count($erros)
        ],
        'associacoes' => $associacoes,
        'nao_encontradas' => $naoEncontradas,
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
