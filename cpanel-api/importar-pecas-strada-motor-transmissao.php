<?php
/**
 * Importação de Peças - Fiat Strada 1.4 8V Flex 2020-2024
 * MOTOR E TRANSMISSÃO - Dados obtidos via Perplexity AI - Janeiro 2026
 *
 * Executar: https://floripa.in9automacao.com.br/importar-pecas-strada-motor-transmissao.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Conexão falhou: ' . $conn->connect_error]));
}

// Array com as peças - MOTOR E TRANSMISSÃO
$pecas = [
    // MOTOR
    [
        'codigo' => null,
        'nome' => 'PARAFUSO CABECOTE STRADA 1.4 8V FIRE',
        'descricao' => 'Peça específica motor Fire 1.4 8V, requer torque específico na montagem',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Fiat Peças',
        'unidade' => 'jg'
    ],
    [
        'codigo' => 'SCT68',
        'nome' => 'CABO DE VELA STRADA 1.4 8V - NGK SCT68',
        'descricao' => 'Jogo completo para motor 1.4 8V Fire/Evo. Compatível 2008-2024',
        'categoria' => 'motor',
        'custo_unitario' => 127.00,
        'fornecedor' => 'NGK',
        'unidade' => 'jg'
    ],
    [
        'codigo' => null,
        'nome' => 'JOGO JUNTA MOTOR SUPERIOR STRADA 1.4',
        'descricao' => 'Kit inclui juntas de cabeçote, coletor, tampa de válvulas. Consultar Sabó, Corteco, Reinz',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => null,
        'unidade' => 'jg'
    ],
    [
        'codigo' => '260916',
        'nome' => 'JUNTA COLETOR ESCAPAMENTO STRADA - TARANTO',
        'descricao' => 'Referência 46412116. Verificar compatibilidade motor 1.4 8V',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Taranto',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'JUNTA COLETOR ESCAP STRADA 2021 - CORTECO',
        'descricao' => 'Material específico para alta temperatura. Aplicação Strada 1.0 1.3 1.4 8V Fire',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Corteco',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'JUNTA FLEXIVEL CATALIZADOR STRADA 2021 - DIVICAR',
        'descricao' => 'Junta metalizada para conexão flexível/catalisador',
        'categoria' => 'escapamento',
        'custo_unitario' => 0,
        'fornecedor' => 'Divicar',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'NJH05669',
        'nome' => 'JUNTA HOMOCINÉTICA LADO RODA STRADA - NAKATA',
        'descricao' => 'Kit com trava (anel externo), garantia 3 meses. Medida 22x22. Aplicação Strada 1.3 1.5 1.6',
        'categoria' => 'transmissao',
        'custo_unitario' => 195.30,
        'fornecedor' => 'Nakata',
        'unidade' => 'un'
    ],
    [
        'codigo' => '55247076',
        'nome' => 'JUNTA TAMPA VALVULA STRADA 2021 - SABÓ 75305',
        'descricao' => 'Material borracha, aplicação Strada 1.4 8V Fire Evo 2017+, tampa plástica',
        'categoria' => 'motor',
        'custo_unitario' => 56.00,
        'fornecedor' => 'Sabó',
        'unidade' => 'un'
    ],
    [
        'codigo' => '02539BRGP',
        'nome' => 'RETENTOR COMANDO VALVULA STRADA - SABÓ',
        'descricao' => 'Componente crítico de vedação do comando de válvulas. Aplicação Uno Palio Siena Strada',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Sabó',
        'unidade' => 'un'
    ],
    [
        'codigo' => '46352980',
        'nome' => 'TAMPA VALVULAS STRADA 2021 - ORIGINAL FIAT',
        'descricao' => 'Tampa completa com junta incluída, produto genuíno Fiat. Strada 1.4 Flex 2020',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Original Fiat',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'F000KE0P07',
        'nome' => 'VELA IGNIÇÃO STRADA 1.4 8V - BOSCH',
        'descricao' => 'Jogo com 4 velas, garantia 90 dias. Aplicação Strada 1.4 8V Flex 2006-2020',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Bosch',
        'unidade' => 'jg'
    ],
    [
        'codigo' => 'BKR6ED',
        'nome' => 'VELA IGNIÇÃO STRADA 1.4 8V - NGK BKR6ED',
        'descricao' => 'Aplicação Strada 1.4 8V 2005-2023. Similar NGK BKR6EZ',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'NGK',
        'unidade' => 'jg'
    ],
    [
        'codigo' => 'KS210',
        'nome' => 'KIT TENSOR CORREIA DENTADA STRADA - GATES',
        'descricao' => 'Kit completo: correia 121 dentes x 18mm + tensor. Aplicação Strada 1.0/1.4 Fire 2009+',
        'categoria' => 'motor',
        'custo_unitario' => 172.30,
        'fornecedor' => 'Gates',
        'unidade' => 'kit'
    ],
    [
        'codigo' => 'VKM12206H',
        'nome' => 'TENSOR CORREIA DENTADA STRADA - SKF',
        'descricao' => 'Tensor SKF + Correia Gates 40859X22XS. Aplicação Strada 1.0/1.4 Fire 2009+',
        'categoria' => 'motor',
        'custo_unitario' => 172.30,
        'fornecedor' => 'SKF',
        'unidade' => 'un'
    ],

    // TRANSMISSÃO
    [
        'codigo' => '1091',
        'nome' => 'COXIM CAMBIO STRADA 1.4 - SAMPEL',
        'descricao' => 'Peça essencial para fixação e amortecimento do câmbio. Aplicação Strada 1.4 2020-2023',
        'categoria' => 'transmissao',
        'custo_unitario' => 0,
        'fornecedor' => 'Sampel',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'NJH42-410S',
        'nome' => 'SEMI-EIXO HOMOCINETICA STRADA 1.3 - NAKATA',
        'descricao' => 'Semi-eixo dianteiro lado direito completo. Peso 6972g, garantia fabricante. Strada 1.3 2020-2023',
        'categoria' => 'transmissao',
        'custo_unitario' => 534.58,
        'fornecedor' => 'Nakata',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'NJH046481',
        'nome' => 'KIT HOMOCINETICA LADO RODA STRADA - NAKATA',
        'descricao' => 'Kit com trava (anel externo), garantia 3 meses, inclui junta e graxa',
        'categoria' => 'transmissao',
        'custo_unitario' => 195.30,
        'fornecedor' => 'Nakata',
        'unidade' => 'kit'
    ],
    [
        'codigo' => null,
        'nome' => 'VARETA DE OLEO STRADA 1.4 8V FIRE',
        'descricao' => 'Peça específica do bloco do motor 1.4 8V Fire',
        'categoria' => 'motor',
        'custo_unitario' => 0,
        'fornecedor' => 'Fiat Peças',
        'unidade' => 'un'
    ],
    [
        'codigo' => '7097388',
        'nome' => 'RODA DE FERRO ARO 15 STRADA - MOPAR',
        'descricao' => 'Códigos: 52055420, 52193495. Cor preta, produto original Fiat. Aplicação Doblo/Strada 2002-2021',
        'categoria' => 'rodas',
        'custo_unitario' => 299.99,
        'fornecedor' => 'Mopar',
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
    'message' => 'Importação concluída - Motor e Transmissão',
    'resumo' => [
        'total_pecas' => count($pecas),
        'inseridos' => $inseridos,
        'duplicados' => $duplicados,
        'erros' => count($erros)
    ],
    'erros' => $erros,
    'proximo_passo' => 'Execute registrar-compatibilidade-strada-motor.php?confirmar=SIM para registrar na tabela de compatibilidade'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
