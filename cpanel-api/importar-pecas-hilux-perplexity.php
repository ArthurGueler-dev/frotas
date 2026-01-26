<?php
/**
 * Importação de Peças - Toyota Hilux 2.8 Diesel 2016-2024
 * Acessórios e Manutenção - Dados obtidos via Perplexity AI - Janeiro 2026
 *
 * Executar: https://floripa.in9automacao.com.br/importar-pecas-hilux-perplexity.php
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
    // ACESSÓRIOS
    [
        'codigo' => null,
        'nome' => 'PAR PALHETA LIMPADOR HILUX - BOSCH AEROFIT',
        'descricao' => 'Motorista 24" + passageiro 18", encaixe tipo gancho. Hilux/SW4 2016-2022',
        'categoria' => 'acessorios',
        'custo_unitario' => 99.90,
        'fornecedor' => 'Bosch',
        'unidade' => 'par'
    ],
    [
        'codigo' => null,
        'nome' => 'PAR PALHETA LIMPADOR HILUX - GENÉRICO',
        'descricao' => 'Motorista 24" + passageiro 18", encaixe tipo gancho. Economia: R$ 70,00+',
        'categoria' => 'acessorios',
        'custo_unitario' => 25.56,
        'fornecedor' => 'Genérico',
        'unidade' => 'par'
    ],

    // FILTROS
    [
        'codigo' => '17801-0L040',
        'nome' => 'FILTRO AR MOTOR HILUX - TOYOTA ORIGINAL',
        'descricao' => 'Dimensões 324x241x58mm. Hilux 2.4/2.7/2.8 16V 2016-2022',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'Toyota Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'HLP2206',
        'nome' => 'FILTRO AR MOTOR HILUX - VOX',
        'descricao' => 'Cross-ref: Toyota 17801-0L040, Tecfil ARL2206, Fram CA12055, Mahle LX4368, Mann C33017',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'Vox',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'FABR193S',
        'nome' => 'FILTRO AR MOTOR HILUX - JAPANPARTS',
        'descricao' => 'Cross-ref: Toyota 17801-0L040. Dimensões 324x241x58mm',
        'categoria' => 'filtros',
        'custo_unitario' => 0,
        'fornecedor' => 'Japanparts',
        'unidade' => 'un'
    ],

    // SUSPENSÃO
    [
        'codigo' => null,
        'nome' => 'KIT CALCO SUSPENSAO HILUX - SPARTA',
        'descricao' => 'Elevação ~2,5-3cm, aço SAE 1045 usinado, proteção galvânica. Hilux 2005-atual. Garantia 12 meses',
        'categoria' => 'suspensao',
        'custo_unitario' => 585.00,
        'fornecedor' => 'Sparta',
        'unidade' => 'kit'
    ],
    [
        'codigo' => null,
        'nome' => 'KIT CALCO SUSPENSAO HILUX 1" - STRIKE BRASIL',
        'descricao' => 'Elevação 2,5cm, inclui calços dianteiros/traseiros aço SAE 1045. Hilux 2005-2024',
        'categoria' => 'suspensao',
        'custo_unitario' => 0,
        'fornecedor' => 'Strike Brasil',
        'unidade' => 'kit'
    ],

    // ARREFECIMENTO/ELÉTRICO
    [
        'codigo' => '1163603180',
        'nome' => 'ELETROVENTILADOR HILUX 21 - TOYOTA ORIGINAL',
        'descricao' => 'Código alternativo: 1163603200. Motor interno ventilador caixa evaporadora',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 0,
        'fornecedor' => 'Toyota Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => '251846554',
        'nome' => 'ELETROVENTILADOR HILUX 21 - HD',
        'descricao' => 'Motor ventilador 12V. Hilux/SW4 2.8 16V Diesel 2016-2021. Garantia 3 meses',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 417.60,
        'fornecedor' => 'HD',
        'unidade' => 'un'
    ],
    [
        'codigo' => 'DS676315',
        'nome' => 'ELETROVENTILADOR HILUX 21 - TYRW',
        'descricao' => 'Motor ventilador 12V. Cross-ref: Toyota 1163603180',
        'categoria' => 'arrefecimento',
        'custo_unitario' => 0,
        'fornecedor' => 'Tyrw',
        'unidade' => 'un'
    ],

    // RODAS
    [
        'codigo' => '9094202052',
        'nome' => 'PARAFUSO RODA HILUX M12X44 - TOYOTA ORIGINAL',
        'descricao' => 'M12-1,5x44,5mm (traseiro 4x4). Rosca 1,5, chave 21, porca cônica. Torque 108-127 Nm',
        'categoria' => 'rodas',
        'custo_unitario' => 0,
        'fornecedor' => 'Toyota Original',
        'unidade' => 'un'
    ],
    [
        'codigo' => '43044',
        'nome' => 'PARAFUSO RODA HILUX M12X44 - MASTER PAR',
        'descricao' => 'M12-1,5x44,5mm, chave 21. Cross-ref: Toyota 9094202052. Hilux 1995-2024',
        'categoria' => 'rodas',
        'custo_unitario' => 6.18,
        'fornecedor' => 'Master Par',
        'unidade' => 'un'
    ],
    [
        'codigo' => '40.012.19',
        'nome' => 'PARAFUSO RODA HILUX M12X44 KIT 50 - ZM',
        'descricao' => 'Kit 50 unidades. M12-1,5x44,5mm, chave 21. Economia ~R$ 7,70/unidade',
        'categoria' => 'rodas',
        'custo_unitario' => 384.52,
        'fornecedor' => 'ZM',
        'unidade' => 'kit'
    ],

    // PNEUS
    [
        'codigo' => null,
        'nome' => 'PNEU 265/65R17 HILUX - PIRELLI SCORPION AT PLUS',
        'descricao' => '112T, letras brancas, All Terrain. Garantia 5 anos. Pressão 35-36 PSI',
        'categoria' => 'pneus',
        'custo_unitario' => 1222.11,
        'fornecedor' => 'Pirelli',
        'unidade' => 'un'
    ],
    [
        'codigo' => null,
        'nome' => 'PNEU 265/65R17 HILUX - CONTINENTAL CROSSCONTACT LX2',
        'descricao' => '112T. Economia: R$ 92,21. Hilux/SW4/Ranger/L200. Garantia 5 anos',
        'categoria' => 'pneus',
        'custo_unitario' => 1129.90,
        'fornecedor' => 'Continental',
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
    'message' => 'Importação concluída - Toyota Hilux Acessórios',
    'resumo' => [
        'total_pecas' => count($pecas),
        'inseridos' => $inseridos,
        'duplicados' => $duplicados,
        'erros' => count($erros)
    ],
    'erros' => $erros,
    'proximo_passo' => 'Execute registrar-compatibilidade-hilux.php?confirmar=SIM'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
