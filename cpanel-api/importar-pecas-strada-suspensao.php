<?php
/**
 * Importação de Peças - Fiat Strada 1.4 8V Flex 2020-2024
 * SUSPENSÃO E OUTROS - Dados obtidos via Perplexity AI - Janeiro 2026
 *
 * Executar: https://floripa.in9automacao.com.br/importar-pecas-strada-suspensao.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Conexão falhou: ' . $conn->connect_error]));
}

$pecas = [
    // SUSPENSÃO
    [
        'codigo' => '52025097',
        'nome' => 'BALANCA BANDEJA STRADA WORKING 1.4 - ORIGINAL FIAT',
        'descricao' => 'Códigos: 52075662, 52177272. Bandeja completa com bucha, coxim e pivô suspensão',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'NBJ4018DP',
        'nome' => 'BALANCA BANDEJA STRADA - NAKATA NBJ4018DP',
        'descricao' => 'Lado direito. Bandeja completa com bucha, coxim e pivô. Garantia 6 meses',
        'categoria' => 'suspensao',
        'custo_unitario' => 328.70,
        'fornecedor' => 'Nakata',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'SK121S',
        'nome' => 'COXIM AMORTECEDOR TRASEIRO STRADA - SAMPEL',
        'descricao' => 'Traseiro esquerdo/direito bilateral. Aplicação traseira serve ambos os lados',
        'categoria' => 'suspensao',
        'custo_unitario' => 88.90,
        'fornecedor' => 'Sampel',
        'unidade' => 'un'
    ],
    [
        'codigo' => '1165',
        'nome' => 'COXIM MOTOR DIANT DIREITO C/ SUPORTE STRADA - SAMPEL',
        'descricao' => 'Coxim limitador de torção motor/câmbio inferior. Strada 1.4 17/19. Instalação requer ferramental específico',
        'categoria' => 'suspensao',
        'custo_unitario' => 175.95,
        'fornecedor' => 'Sampel',
        'unidade' => 'un'
    ],
    [
        'codigo' => '52173308',
        'nome' => 'AMORTECEDOR DIANT DIR STRADA 2021 - ORIGINAL FIAT',
        'descricao' => 'Códigos: 52173309, 52173310, 52189661. Aplicação Strada 2020-2024 cabine dupla/simples',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'GP33398',
        'nome' => 'AMORTECEDOR DIANT DIR STRADA 2021 - COFAP TURBOGÁS',
        'descricao' => 'Lado direito. Aplicação Strada 2020-2024 cabine dupla/simples, garantia 3 meses',
        'categoria' => 'suspensao',
        'custo_unitario' => 505.16,
        'fornecedor' => 'Cofap',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'GP33399',
        'nome' => 'AMORTECEDOR DIANT ESQ STRADA 2021 - COFAP TURBOGÁS',
        'descricao' => 'Lado esquerdo. Tipo TurboGás, aplicação dianteira Strada 2020-2025',
        'categoria' => 'suspensao',
        'custo_unitario' => 505.16,
        'fornecedor' => 'Cofap',
        'unidade' => 'un'
    ],
    [
        'codigo' => '7081851',
        'nome' => 'BARRA AXIAL STRADA HIDRAULICA - ORIGINAL FIAT',
        'descricao' => 'Código alternativo: 4358537. Comprimento 270mm, rosca M14x1,5mm, direção hidráulica/mecânica',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'JARB0001',
        'nome' => 'BARRA AXIAL STRADA - TRW JARB0001',
        'descricao' => 'Terminal axial bilateral. Comprimento 270mm, rosca M14x1,5mm. Aplicação Strada 2009-2019',
        'categoria' => 'suspensao',
        'custo_unitario' => 82.27,
        'fornecedor' => 'TRW',
        'unidade' => 'un'
    ],
    [
        'codigo' => '680022',
        'nome' => 'BARRA AXIAL STRADA - VIEMAR 680022',
        'descricao' => 'Terminal dianteiro direito/esquerdo. Cross-reference: TRW JARB0001, Nakata N644',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Viemar',
        'unidade' => 'un'
    ],
    [
        'codigo' => '044.1435',
        'nome' => 'KIT BATENTE E COIFA AMORT DIANT STRADA - AXIOS',
        'descricao' => 'Kit inclui batente, coifa e coxim amortecedor dianteiro. Par completo',
        'categoria' => 'suspensao',
        'custo_unitario' => 182.19,
        'fornecedor' => 'Axios',
        'unidade' => 'kit'
    ],
    [
        'codigo' => 'VKDS6038',
        'nome' => 'BIELETA STRADA 2021 - SKF VKDS6038',
        'descricao' => 'Bieleta estabilizadora dianteira, peso 300g. Aplicação Strada 1.3/1.4 Firefly 2020-2021',
        'categoria' => 'suspensao',
        'custo_unitario' => 35.70,
        'fornecedor' => 'SKF',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'JTSB0006',
        'nome' => 'BIELETA STRADA 2021 - TRW JTSB0006',
        'descricao' => 'Par de bieletas dianteiras. Aplicação Nova Strada 2021-2025',
        'categoria' => 'suspensao',
        'custo_unitario' => 92.99,
        'fornecedor' => 'TRW',
        'unidade' => 'par'
    ],

    // OUTROS
    [
        'codigo' => '7091956',
        'nome' => 'EVAPORADOR AR COND STRADA 21 - ACP',
        'descricao' => 'Aplicação Strada Hard Working 2019-2021. Instalação por profissional qualificado, requer higienização A/C',
        'categoria' => 'ar_condicionado',
        'custo_unitario' => 0,
        'fornecedor' => 'ACP',
        'unidade' => 'un'
    ],
    [
        'codigo' => '52022972',
        'nome' => 'ELETROVENTILADOR GMV MOBI STRADA 21 - ORIGINAL FIAT',
        'descricao' => '10 pás, com resistência e módulo. Aplicação radiador, ar condicionado. Mobi 1.4/Strada 1.4 2020+',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 1350.00,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'LANTERNA TRASEIRA STRADA LD - ORIGINAL FIAT',
        'descricao' => 'Lado direito bicolor 2021-2025. Lente em acrílico. Nova Strada 2020-2024 cabine dupla/simples',
        'categoria' => 'iluminacao',
        'custo_unitario' => 857.73,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'LANTERNA TRASEIRA STRADA LD - FITAM',
        'descricao' => 'Lado direito/esquerdo. Lente em acrílico. Nova Strada 2020-2024. Economia de R$ 527,83',
        'categoria' => 'iluminacao',
        'custo_unitario' => 329.90,
        'fornecedor' => 'Fitam',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'BICO INJETOR PALIO SIENA STRADA 1.4 FLEX',
        'descricao' => 'Bico injetor motor Fire 1.4 8V Flex. Requer limpeza/teste ultrassônico',
        'categoria' => 'injecao',
        'custo_unitario' => 0,
        'fornecedor' => 'Bosch',
        'unidade' => 'un'
    ]
];

$inseridos = 0;
$erros = [];
$duplicados = 0;

foreach ($pecas as $peca) {
    $stmt_check = $conn->prepare("SELECT id FROM FF_Pecas WHERE nome = ?");
    $stmt_check->bind_param("s", $peca['nome']);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $duplicados++;
        $stmt_check->close();
        continue;
    }
    $stmt_check->close();

    $stmt = $conn->prepare(
        "INSERT INTO FF_Pecas
        (codigo, nome, descricao, unidade, custo_unitario, estoque_minimo, estoque_atual, fornecedor, categoria, ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, 1, 0, ?, ?, 1, NOW())"
    );

    $stmt->bind_param(
        "ssssdss",
        $peca['codigo'],
        $peca['nome'],
        $peca['descricao'],
        $peca['unidade'],
        $peca['custo_unitario'],
        $peca['fornecedor'],
        $peca['categoria']
    );

    if ($stmt->execute()) {
        $inseridos++;
    } else {
        $erros[] = "Erro ao inserir '{$peca['nome']}': " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Importação concluída - Suspensão e Outros',
    'resumo' => [
        'total_pecas' => count($pecas),
        'inseridos' => $inseridos,
        'duplicados' => $duplicados,
        'erros' => count($erros)
    ],
    'erros' => $erros,
    'proximo_passo' => 'Execute registrar-compatibilidade-strada-suspensao.php?confirmar=SIM'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
