<?php
/**
 * Importação de Peças - Fiat Mobi 1.0 8V Flex 2017-2024
 * Dados obtidos via Perplexity AI - Janeiro 2026
 *
 * Executar: https://floripa.in9automacao.com.br/importar-pecas-mobi-perplexity.php
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
    // FREIOS
    [
        'codigo' => 'P85',
        'nome' => 'PASTILHA FREIO MOBI P85 - LONAFLEX',
        'descricao' => 'Pastilha dianteira sem alarme, sistema Teves, 4 peças por kit. EAN 7893026763898. Mobi/Uno 2010-2023',
        'categoria' => 'freios',
        'custo_unitario' => 65.90,
        'fornecedor' => 'Lonaflex',
        'unidade' => 'jg'
    ],
    [
        'codigo' => '7091922',
        'nome' => 'SAPATA DE FREIO MOBI - ORIGINAL FIAT',
        'descricao' => 'Código alternativo: 7093055. Mobi 1.0 2020+, com haste, dimensão 185x33mm',
        'categoria' => 'freios',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'jg'
    ],
    [
        'codigo' => 'SA40135',
        'nome' => 'SAPATA DE FREIO MOBI SA40135 - AUTHO MIX',
        'descricao' => 'Cross-ref: Cobreq 2826-CPA, Fras-le FI/639-CPA, Mazzicar BPSA 0090135. Dimensão 185x33mm',
        'categoria' => 'freios',
        'custo_unitario' => 0,
        'fornecedor' => 'Autho Mix',
        'unidade' => 'jg'
    ],

    // MOTOR E ARREFECIMENTO
    [
        'codigo' => '55249345',
        'nome' => 'SONDA LAMBDA FIAT MOBI 1.0 POS - ORIGINAL FIAT',
        'descricao' => 'Sonda pós-catalisador. Aplicação Mobi 1.0/Uno 1.4 2016+',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => '0258030157',
        'nome' => 'SONDA LAMBDA MOBI 1.0 POS - BOSCH',
        'descricao' => 'Sonda pós-catalisador. Economia: R$ 278,40. Aplicação Mobi 1.0/Uno 1.4 2016+',
        'categoria' => 'motor',
        'custo_unitario' => 138.90,
        'fornecedor' => 'Bosch',
        'unidade' => 'un'
    ],
    [
        'codigo' => '4477529',
        'nome' => 'VÁLVULA TERMOSTÁTICA MOBI - ORIGINAL FIAT',
        'descricao' => 'Código alternativo: 511090. Temperatura abertura 87°C. Manutenção preventiva a cada 30.000 km',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'VC442288',
        'nome' => 'VÁLVULA TERMOSTÁTICA MOBI - VALCLEI',
        'descricao' => 'Economia: R$ 84,54. EAN 7893989078893. Abertura ~90°C. Aplicação Mobi/Uno/Palio/Siena',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 197.25,
        'fornecedor' => 'Valclei',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'VT42288',
        'nome' => 'VÁLVULA TERMOSTÁTICA MOBI - MTE',
        'descricao' => 'Cross-ref: Valclei VC442288, Fiat 4477529. Uno Evo Siena Mobi 2010-2016',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 0,
        'fornecedor' => 'MTE',
        'unidade' => 'un'
    ],
    [
        'codigo' => '354009',
        'nome' => 'BOMBA DAGUA MOBI 2021 - ÍNDISA',
        'descricao' => 'Motor Fire 1.0/1.3/1.4. Compatível Palio/Siena/Strada/Uno/Doblò/Idea/Fiorino. Garantia 3 meses',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 74.79,
        'fornecedor' => 'Índisa',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'JUNTA DO ESCAPE MOBI 1.0 8V',
        'descricao' => 'Junta coletor de escapamento motor Fire 1.0 8V. Consultar Corteco, Sabó',
        'categoria' => 'escapamento',
        'custo_unitario' => 0,
        'fornecedor' => null,
        'unidade' => 'un'
    ],

    // SUSPENSÃO E RODAS
    [
        'codigo' => 'AL-630',
        'nome' => 'CUBO DE RODA DIANT MOBI 2021 - IMA',
        'descricao' => 'Dianteiro com rolamento incluído. Verificar código específico para Mobi',
        'categoria' => 'suspensao',
        'custo_unitario' => 60.00,
        'fornecedor' => 'IMA',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'AL-609',
        'nome' => 'CUBO DE RODA TRAS MOBI 2021 - IMA',
        'descricao' => 'Traseiro direito/esquerdo. Incluído rolamento',
        'categoria' => 'suspensao',
        'custo_unitario' => 275.24,
        'fornecedor' => 'IMA',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'BTC03111',
        'nome' => 'BIELETA MOBI 2021 - COFAP',
        'descricao' => 'Bieleta dianteira bilateral. Aplicação Mobi 2016-2023',
        'categoria' => 'suspensao',
        'custo_unitario' => 49.47,
        'fornecedor' => 'Cofap',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'MB4423',
        'nome' => 'BIELETA MOBI 2021 - MOBENSANI',
        'descricao' => 'Dianteira direita/esquerda. Aplicação Mobi 2016-2023',
        'categoria' => 'suspensao',
        'custo_unitario' => 61.54,
        'fornecedor' => 'Mobensani',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'KBT0104',
        'nome' => 'BIELETA MOBI - PERFECT',
        'descricao' => 'Bilateral. Cross-ref: ZM 370.110.06 (par), ZM 370.112.06',
        'categoria' => 'suspensao',
        'custo_unitario' => 34.41,
        'fornecedor' => 'Perfect',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'AX-442618',
        'nome' => 'KIT AMORTEC DIANT COXIM C/ROL+BAT+COIFA MOBI 2021 - AXIOS',
        'descricao' => 'Kit completo: coxim + rolamento + batente + coifa. Mobi 2016-2023',
        'categoria' => 'suspensao',
        'custo_unitario' => 189.84,
        'fornecedor' => 'Axios',
        'unidade' => 'kit'
    ],
    [
        'codigo' => 'SK143S',
        'nome' => 'KIT AMORTEC MOBI 2021 - SAMPEL',
        'descricao' => 'Kit reparo amortecedor dianteiro direito/esquerdo. Aplicação Grand Siena/Mobi/Palio/Uno',
        'categoria' => 'suspensao',
        'custo_unitario' => 283.05,
        'fornecedor' => 'Sampel',
        'unidade' => 'kit'
    ],
    [
        'codigo' => '52004183',
        'nome' => 'AMORTEC DIANT DIR MOBI 2021 - ORIGINAL FIAT',
        'descricao' => 'Amortecedor dianteiro direito. Mobi 2016-2024',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => '749080SP',
        'nome' => 'AMORTEC DIANT DIR MOBI 2021 - MONROE OESpectrum',
        'descricao' => 'Estrutura a gás, peso 4,7kg, comprimento 375mm/552mm. Garantia 24 meses. Cross-ref: Cofap GP-33262',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Monroe',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'GP-33262',
        'nome' => 'AMORTEC DIANT MOBI 2021 - COFAP',
        'descricao' => 'Cross-ref: Monroe 749080SP. Mobi 2016-2024',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Cofap',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'VKBC3577',
        'nome' => 'ROLAMENTO RODA DIANT MOBI 2021 - SKF',
        'descricao' => 'Rolamento dianteiro. Pode estar incluído no cubo IMA',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'SKF',
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
    'message' => 'Importação concluída - Fiat Mobi 1.0',
    'resumo' => [
        'total_pecas' => count($pecas),
        'inseridos' => $inseridos,
        'duplicados' => $duplicados,
        'erros' => count($erros)
    ],
    'erros' => $erros,
    'proximo_passo' => 'Execute registrar-compatibilidade-mobi.php?confirmar=SIM'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
