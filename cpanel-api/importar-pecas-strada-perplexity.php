<?php
/**
 * Importação de Peças - Fiat Strada 1.4 8V Flex 2020-2024
 * Dados obtidos via Perplexity AI - Janeiro 2026
 *
 * Executar: https://floripa.in9automacao.com.br/importar-pecas-strada-perplexity.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Conexão falhou: ' . $conn->connect_error]));
}

// Array com as peças da Strada - Dados do Perplexity
$pecas = [
    // FREIOS
    [
        'codigo' => null,
        'nome' => 'PASTILHA DE FREIO STRADA 2021 CO-N540 - COBREQ',
        'descricao' => 'Sistema Teves, sem alarme, aplicação dianteira. Similar: Cobreq N540',
        'categoria' => 'freios',
        'custo_unitario' => 71.90,
        'fornecedor' => 'Cobreq',
        'unidade' => 'jg'
    ],
    [
        'codigo' => null,
        'nome' => 'PASTILHA DE FREIO STRADA 2021 PD2226 - FRASLE',
        'descricao' => 'Pastilha dianteira para Strada 1.4 8V 2020-2021. Similar: Frasle PD-2226',
        'categoria' => 'freios',
        'custo_unitario' => 129.85,
        'fornecedor' => 'Frasle',
        'unidade' => 'jg'
    ],
    [
        'codigo' => null,
        'nome' => 'SAPATA FREIO TRAS FIAT STRADA 21 90147 - MAZZICAR',
        'descricao' => 'Sistema Teves, aplicação traseira. Similar: Mazzicar 090147',
        'categoria' => 'freios',
        'custo_unitario' => 181.68,
        'fornecedor' => 'Mazzicar',
        'unidade' => 'jg'
    ],
    [
        'codigo' => null,
        'nome' => 'SAPATA FREIO TRAS FIAT STRADA 21 FI/432 - FRASLE',
        'descricao' => 'Sapata traseira sistema Teves. Similar: Mazzicar 090147',
        'categoria' => 'freios',
        'custo_unitario' => 181.68,
        'fornecedor' => 'Frasle',
        'unidade' => 'jg'
    ],

    // ESCAPAMENTO
    [
        'codigo' => null,
        'nome' => 'ABAFADOR INTERMEDIÁRIO STRADA 21',
        'descricao' => 'Material aço galvanizado, garantia 90 dias. Similar: Diverso 4442M-C',
        'categoria' => 'escapamento',
        'custo_unitario' => 187.07,
        'fornecedor' => 'Diverso',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'LUVA - TUBO ESCAPAMENTO STRADA 21',
        'descricao' => 'Tubo flexível escapamento. Verificar compatibilidade motor 1.4 8V',
        'categoria' => 'escapamento',
        'custo_unitario' => 159.17,
        'fornecedor' => null,
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'SILENCIOSO - STRADA',
        'descricao' => 'Silencioso traseiro. Similar: Way 521585. Garantia fábrica 6 meses',
        'categoria' => 'escapamento',
        'custo_unitario' => 220.50,
        'fornecedor' => 'Catania',
        'unidade' => 'un'
    ],

    // ARREFECIMENTO
    [
        'codigo' => null,
        'nome' => 'MANG INF RADIADOR - STRADA',
        'descricao' => 'Mangueira inferior radiador. Similar: Gates 21106',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 173.00,
        'fornecedor' => 'Gates',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'MANG SUP RADIADOR - STRADA',
        'descricao' => 'Mangueira superior radiador motor 1.4 8V Flex. Similar: Gates 21106',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 173.00,
        'fornecedor' => 'Gates',
        'unidade' => 'un'
    ],
    [
        'codigo' => '52126299',
        'nome' => 'RADIADOR STRADA',
        'descricao' => 'Radiador Fiat Strada 2020-2024 1.4 com/sem ar. Similar: Visconde RV 2277 R$ 483,91',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 614.04,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'CANO DAGUA STRADA',
        'descricao' => 'Peça específica sistema arrefecimento. Consultar Mopar/Fiat',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 0,
        'fornecedor' => null,
        'unidade' => 'un'
    ],
    [
        'codigo' => '52022889',
        'nome' => 'RESERVATÓRIO EXPANSÃO FIAT STRADA 21',
        'descricao' => 'Reservatório expansão Uno/Strada/Mobi. Material polipropileno alta resistência. Garantia 3 meses',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 163.80,
        'fornecedor' => 'Original Mopar',
        'unidade' => 'un'
    ],
    [
        'codigo' => '7086068',
        'nome' => 'TAMPA RESERVATÓRIO DE ÁGUA STRADA 21',
        'descricao' => 'Tampa vedação reservatório expansão',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 47.89,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => '46737644',
        'nome' => 'VÁLVULA TERMOSTÁTICA STRADA',
        'descricao' => 'Temperatura 87°C, dimensão 29,5mm, sem jiggle pin. Similar: MTE-Thomson VT34987 R$ 69,06',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 69.06,
        'fornecedor' => 'MTE-Thomson',
        'unidade' => 'un'
    ],

    // CARROCERIA
    [
        'codigo' => null,
        'nome' => 'SUPORTE RETROVISOR - STRADA',
        'descricao' => 'Suporte retrovisor. Consultar catálogo Fiat Peças',
        'categoria' => 'carroceria',
        'custo_unitario' => 0,
        'fornecedor' => null,
        'unidade' => 'un'
    ],
    [
        'codigo' => '2054039',
        'nome' => 'PARACHOQUE STRADA PRETO TEXTURIZADO',
        'descricao' => 'Parachoque dianteiro superior 2021-2024, preto texturizado com furos. Autoglass',
        'categoria' => 'carroceria',
        'custo_unitario' => 588.81,
        'fornecedor' => 'Autoglass',
        'unidade' => 'un'
    ],
    [
        'codigo' => '100256493',
        'nome' => 'GRADE RAD. STRADA 21/PRETA TEXT.',
        'descricao' => 'Grade preta com emblema Fiat. Similar: Fipparts 2083069',
        'categoria' => 'carroceria',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'VIDRO PARABRISA STRADA',
        'descricao' => 'Cab Dupla 1.350x650mm, Cab Simples 1.300x650mm, verde laminado. Similar: Pilkington R$ 645,20 / AGC R$ 580,45',
        'categoria' => 'carroceria',
        'custo_unitario' => 580.45,
        'fornecedor' => 'AGC',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'BORRACHA DE VEDAÇÃO L/E - STRADA',
        'descricao' => 'Borracha vedação lateral esquerda. Normalmente incluída na instalação do parabrisa',
        'categoria' => 'carroceria',
        'custo_unitario' => 0,
        'fornecedor' => null,
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'TRANCA DA TAMPA TRASEIRA STRADA',
        'descricao' => 'Peça segurança específica carroceria. Consultar Fiat Peças/Mopar',
        'categoria' => 'carroceria',
        'custo_unitario' => 0,
        'fornecedor' => null,
        'unidade' => 'un'
    ]
];

$inseridos = 0;
$erros = [];
$duplicados = 0;

foreach ($pecas as $peca) {
    // Verificar se já existe
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

    // Inserir nova peça
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
    'message' => 'Importação concluída',
    'resumo' => [
        'total_pecas' => count($pecas),
        'inseridos' => $inseridos,
        'duplicados' => $duplicados,
        'erros' => count($erros)
    ],
    'erros' => $erros
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
