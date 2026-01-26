<?php
/**
 * Importação de Peças - Chevrolet S10 2.8 Diesel 4x4 2012-2024
 * Dados obtidos via Perplexity AI - Janeiro 2026
 *
 * Executar: https://floripa.in9automacao.com.br/importar-pecas-s10-perplexity.php
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
    // FILTROS
    [
        'codigo' => '52049880',
        'nome' => 'FILTRO AR CONDICIONADO S10 - GM ORIGINAL',
        'descricao' => 'Filtro cabine. Dimensões 21,7x20,0x3,0 cm. Troca a cada 10.000 km ou 6 meses',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'GM Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'ACP843',
        'nome' => 'FILTRO AR CONDICIONADO S10 - TECFIL',
        'descricao' => 'Cross-ref: GM 52049880. Dimensões 21,7x20,0x3,0 cm. Economia variável',
        'categoria' => 'filtros',
        'custo_unitario' => 27.16,
        'fornecedor' => 'Tecfil',
        'unidade' => 'un'
    ],
    [
        'codigo' => '52014629',
        'nome' => 'FILTRO AR MOTOR S10 2.4/2.8 - GM ORIGINAL',
        'descricao' => 'Elemento papel celulose, formato cilíndrico. Dimensões 16,1x8,7x27,0 cm, peso 0,569kg',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'GM Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'C16010/1',
        'nome' => 'FILTRO AR MOTOR S10 2.4/2.8 - MANN',
        'descricao' => 'Cross-ref: GM 52014629. Garantia 6 meses. Troca a cada 20.000 km',
        'categoria' => 'filtros',
        'custo_unitario' => 51.31,
        'fornecedor' => 'Mann Filter',
        'unidade' => 'un'
    ],
    [
        'codigo' => '94771044',
        'nome' => 'FILTRO COMBUSTIVEL S10 2.8 TDI - GM ORIGINAL',
        'descricao' => 'Código alternativo: 52100220. Kit contém 2 filtros. Diâmetro 77mm, altura 110mm',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'GM Original',
        'unidade' => 'kit'
    ],
    [
        'codigo' => 'KX444D',
        'nome' => 'FILTRO COMBUSTIVEL S10 2.8 TDI - MAHLE',
        'descricao' => 'Cross-ref: GM 94771044, Mann PU7020z, Tecfil PEC3029. Garantia 3 meses',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'Mahle',
        'unidade' => 'kit'
    ],
    [
        'codigo' => 'PEC3029',
        'nome' => 'FILTRO COMBUSTIVEL S10 2.8 TDI - TECFIL',
        'descricao' => 'Cross-ref: GM 94771044, Mahle KX444D, Fram C11723, Vox CE3029',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'Tecfil',
        'unidade' => 'kit'
    ],
    [
        'codigo' => '12636838',
        'nome' => 'FILTRO OLEO S10 2.8 DIESEL - GM ORIGINAL',
        'descricao' => 'Código alternativo: 94750420. Filtro refil (elemento). S10/Trailblazer 2.8 Diesel 2012-2025',
        'categoria' => 'filtros',
        'custo_unitario' => 98.80,
        'fornecedor' => 'GM Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'PEL726',
        'nome' => 'FILTRO OLEO S10 2.8 DIESEL - TECFIL',
        'descricao' => 'Cross-ref: GM 12636838, Fram CH11724, Vox LE726. Economia: R$ 57,77. Peso 390g. Garantia 3 meses',
        'categoria' => 'filtros',
        'custo_unitario' => 41.03,
        'fornecedor' => 'Tecfil',
        'unidade' => 'un'
    ],

    // LUBRIFICANTES E TRANSMISSÃO
    [
        'codigo' => '98550135',
        'nome' => 'OLEO CAMBIO 75W90 S10 - ACDELCO',
        'descricao' => '75W90 sintético API GL-5 LS. Capacidade diferencial ~2,0L, câmbio manual ~2,4L. Troca 50.000 km',
        'categoria' => 'oleos',
        'custo_unitario' => 0,
        'fornecedor' => 'ACDelco',
        'unidade' => 'lt'
    ],
    [
        'codigo' => null,
        'nome' => 'OLEO CAMBIO 75W90 S10 - GT-OIL GEAR',
        'descricao' => 'API GL-5 LS (Limited Slip) obrigatório para diferencial com bloqueio. Sintético/semissintético',
        'categoria' => 'oleos',
        'custo_unitario' => 0,
        'fornecedor' => 'GT-Oil',
        'unidade' => 'lt'
    ],
    [
        'codigo' => 'VKCH151112',
        'nome' => 'ATUADOR EMBREAGEM S10 2.8 DIESEL - SKF',
        'descricao' => 'Aciona sistema embreagem via força hidráulica. Garantia 3-12 meses. S10 2.8 Diesel',
        'categoria' => 'transmissao',
        'custo_unitario' => 368.89,
        'fornecedor' => 'SKF',
        'unidade' => 'un'
    ],

    // SUSPENSÃO
    [
        'codigo' => '334480MM',
        'nome' => 'AMORTECEDOR DIANT S10 - MONROE',
        'descricao' => 'Dianteiro direito/esquerdo bilateral. Trocar sempre em pares. S10 2012+',
        'categoria' => 'suspensao',
        'custo_unitario' => 442.08,
        'fornecedor' => 'Monroe',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'L12122',
        'nome' => 'AMORTECEDOR DIANT S10 - COFAP',
        'descricao' => 'Cross-ref: Monroe 334480MM. Dianteiro bilateral. S10 2012+',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Cofap',
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
    'message' => 'Importação concluída - Chevrolet S10 2.8 Diesel',
    'resumo' => [
        'total_pecas' => count($pecas),
        'inseridos' => $inseridos,
        'duplicados' => $duplicados,
        'erros' => count($erros)
    ],
    'erros' => $erros,
    'proximo_passo' => 'Execute registrar-compatibilidade-s10.php?confirmar=SIM'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
