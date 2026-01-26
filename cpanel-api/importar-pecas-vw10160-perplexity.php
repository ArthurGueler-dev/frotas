<?php
/**
 * Importação de Peças - VW 10.160 Delivery Diesel
 * Dados obtidos via Perplexity AI - Janeiro 2026
 *
 * Executar: https://floripa.in9automacao.com.br/importar-pecas-vw10160-perplexity.php
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
    // SISTEMA DE FREIOS
    [
        'codigo' => '2RR607304',
        'nome' => 'FLEXIVEL FREIO VW 10160 TRAS - ORIGINAL VW',
        'descricao' => 'Flexível freio traseiro, sistema hidráulico/pneumático. Referência similar VW 24.220/24.250',
        'categoria' => 'freios',
        'custo_unitario' => 0,
        'fornecedor' => 'VW Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'FLEXIVEL FREIO VW 10160 TRAS - RIGIFLEX',
        'descricao' => 'Sistema hidráulico/pneumático. Verificar comprimento específico para 10.160',
        'categoria' => 'freios',
        'custo_unitario' => 0,
        'fornecedor' => 'Rigiflex',
        'unidade' => 'un'
    ],
    [
        'codigo' => '23B609245',
        'nome' => 'SAPATA DE FREIO VW 10160 - ORIGINAL VW',
        'descricao' => 'Sapata sem lona/patim. Dimensão 325x120mm HD, diâmetro tambor 325mm, 8 furos. Dianteira e traseira',
        'categoria' => 'freios',
        'custo_unitario' => 517.00,
        'fornecedor' => 'VW Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'PA0261',
        'nome' => 'SAPATA DE FREIO VW 10160 - MASTER',
        'descricao' => 'Medida 325x120HD. Sistema Master. Aplicação 5.150 a 11.180, Worker, Ônibus',
        'categoria' => 'freios',
        'custo_unitario' => 0,
        'fornecedor' => 'Master',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'L-559',
        'nome' => 'LONA FREIO TRAS VW 10160 - FRAS-LE',
        'descricao' => 'Lona traseira 9150/10160. Dimensão 325x120mm',
        'categoria' => 'freios',
        'custo_unitario' => 0,
        'fornecedor' => 'Fras-le',
        'unidade' => 'jg'
    ],

    // SISTEMA ELÉTRICO E ARREFECIMENTO
    [
        'codigo' => null,
        'nome' => 'ELETROVENTILADOR VW 10160 24V 11" - UNIVERSAL',
        'descricao' => 'Tensão 24V obrigatória, 11 polegadas, tipo aspirante. Universal VW/MB caminhões',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 384.52,
        'fornecedor' => 'Universal',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'ELETROVENTILADOR VW 10160 24V - ROYCE CONNECT',
        'descricao' => 'Tensão 24V, aspirante. Economia: R$ 83,36',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 301.16,
        'fornecedor' => 'Royce Connect',
        'unidade' => 'un'
    ],

    // SISTEMA PNEUMÁTICO
    [
        'codigo' => null,
        'nome' => 'MANGUEIRA AR 1/4 VW 10160 - PVC',
        'descricao' => 'Diâmetro 1/4" (6,3mm interno), externo 11,5mm. Pressão 300psi (10 Bar). Temp -10°C a +60°C. Por metro',
        'categoria' => 'pneumatico',
        'custo_unitario' => 0,
        'fornecedor' => 'Transpower',
        'unidade' => 'mt'
    ],
    [
        'codigo' => null,
        'nome' => 'VALVULA GOVERNADORA VW 10160 - WABCO',
        'descricao' => 'Sistema freio pneumático. Instalação profissional obrigatória',
        'categoria' => 'pneumatico',
        'custo_unitario' => 670.57,
        'fornecedor' => 'Wabco',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'VALVULA RELE FREIO VW 10160 - WABCO',
        'descricao' => 'Sistema freio pneumático. Economia: R$ 423,67',
        'categoria' => 'pneumatico',
        'custo_unitario' => 246.90,
        'fornecedor' => 'Wabco',
        'unidade' => 'un'
    ],

    // RODAS E TRANSMISSÃO
    [
        'codigo' => '2RD601161',
        'nome' => 'PARAFUSO RODA VW 10160 DELIVERY - ORIGINAL VW',
        'descricao' => 'Rosca M18x1,5mm, comprimento 80-90mm, classe 10.9, chave 27mm, porca oscilante. Eixo traseiro',
        'categoria' => 'rodas',
        'custo_unitario' => 0,
        'fornecedor' => 'VW Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => '797715',
        'nome' => 'PARAFUSO RODA VW 10160 TRAS - FEY',
        'descricao' => 'Traseiro 18x90mm passo 1,5, Aço 10.9. Disponível 5 dias',
        'categoria' => 'rodas',
        'custo_unitario' => 0,
        'fornecedor' => 'Fey',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'T066011161CX',
        'nome' => 'PARAFUSO RODA VW 10160 KIT 5 - VW',
        'descricao' => 'Traseiro 18x1,5x80mm classe 10.9 completo. Kit 5 unidades',
        'categoria' => 'rodas',
        'custo_unitario' => 0,
        'fornecedor' => 'VW Original',
        'unidade' => 'kit'
    ],

    // SUSPENSÃO
    [
        'codigo' => null,
        'nome' => 'AMORTECEDOR DIANT VW 10160 - MONROE',
        'descricao' => 'Trocar sempre em pares. Tipo monotubo ou gás, específico carga pesada',
        'categoria' => 'suspensao',
        'custo_unitario' => 340.00,
        'fornecedor' => 'Monroe',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'AMORTECEDOR DIANT VW 10160 - COFAP',
        'descricao' => 'Aplicação Delivery 8150/9160/10160. Trocar em pares',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Cofap',
        'unidade' => 'un'
    ],
    [
        'codigo' => '23B411719D',
        'nome' => 'BUCHA FEIXE MOLA VW 10160 SUPORTE TRAS - VW',
        'descricao' => 'Suporte traseiro. Diâmetro externo 58mm, interno 30mm, altura olhal 80mm. Borracha vulcanizada',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'VW Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'TAP.511.181',
        'nome' => 'BUCHA FEIXE MOLA VW 10160 OLHAL - VW',
        'descricao' => 'Código alternativo: 2RE.511.181. Material Camelback. Garantia 90 dias',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'VW Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'FTJC2428',
        'nome' => 'BUCHA FEIXE MOLA VW 10160 - FLASH TRUCK',
        'descricao' => 'Diâmetro furo 18mm, comprimento 86mm, largura 53mm',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Flash Truck',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'FEIXE MOLA TRAS VW 10160 - ORIGINAL',
        'descricao' => 'Delivery 8-160/10-160/11-180 2012-2019. Transporte por transportadora. Instalação profissional',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'VW Original',
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
    'message' => 'Importação concluída - VW 10.160 Delivery',
    'resumo' => [
        'total_pecas' => count($pecas),
        'inseridos' => $inseridos,
        'duplicados' => $duplicados,
        'erros' => count($erros)
    ],
    'erros' => $erros,
    'proximo_passo' => 'Execute registrar-compatibilidade-vw10160.php?confirmar=SIM'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
