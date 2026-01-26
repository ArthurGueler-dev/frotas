<?php
/**
 * Associar Peças da Strada ao Plano de Manutenção
 * As peças já foram importadas em FF_Pecas via importar-pecas-strada-perplexity.php
 * Este script cria as associações na tabela FF_PlanoManutencao_Pecas
 *
 * Executar: https://floripa.in9automacao.com.br/associar-pecas-strada-perplexity.php?confirmar=SIM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar',
        'warning' => 'Isso irá associar as peças da Strada ao plano de manutenção'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

// ===================================
// 1. Primeiro, verificar qual modelo de Strada existe no banco
// ===================================
$resultModelos = $conn->query("
    SELECT DISTINCT modelo_carro
    FROM Planos_Manutenção
    WHERE modelo_carro LIKE '%STRADA%'
    ORDER BY modelo_carro
");

$modelosStrada = [];
while ($row = $resultModelos->fetch_assoc()) {
    $modelosStrada[] = $row['modelo_carro'];
}

if (count($modelosStrada) === 0) {
    // Se não existe plano para Strada, listar todos os modelos disponíveis
    $resultTodos = $conn->query("
        SELECT DISTINCT modelo_carro
        FROM Planos_Manutenção
        ORDER BY modelo_carro
    ");

    $todosModelos = [];
    while ($row = $resultTodos->fetch_assoc()) {
        $todosModelos[] = $row['modelo_carro'];
    }

    echo json_encode([
        'success' => false,
        'error' => 'Nenhum plano de manutenção encontrado para STRADA',
        'modelos_disponiveis' => $todosModelos,
        'sugestao' => 'Primeiro é necessário criar um plano de manutenção para a Strada'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Usar o primeiro modelo encontrado (ou o mais específico)
$modelo = $modelosStrada[0];

// ===================================
// 2. Buscar peças da Strada já importadas
// ===================================
$resultPecas = $conn->query("
    SELECT id, codigo, nome, categoria, custo_unitario
    FROM FF_Pecas
    WHERE nome LIKE '%STRADA%' AND ativo = 1
    ORDER BY categoria, nome
");

$pecasStrada = [];
while ($row = $resultPecas->fetch_assoc()) {
    $pecasStrada[] = $row;
}

if (count($pecasStrada) === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Nenhuma peça da Strada encontrada na tabela FF_Pecas',
        'sugestao' => 'Execute primeiro: importar-pecas-strada-perplexity.php'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// ===================================
// 3. Buscar itens do plano de manutenção da Strada
// ===================================
$stmtItens = $conn->prepare("
    SELECT id, descricao_titulo, km_recomendado
    FROM Planos_Manutenção
    WHERE modelo_carro = ?
    ORDER BY km_recomendado ASC
");
$stmtItens->bind_param("s", $modelo);
$stmtItens->execute();
$resultItens = $stmtItens->get_result();

$itensPlano = [];
while ($row = $resultItens->fetch_assoc()) {
    $itensPlano[] = $row;
}
$stmtItens->close();

if (count($itensPlano) === 0) {
    echo json_encode([
        'success' => false,
        'error' => "Nenhum item de plano encontrado para o modelo: $modelo",
        'modelos_strada_encontrados' => $modelosStrada
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// ===================================
// 4. Mapeamento: Categoria da peça → Palavras-chave no título do item
// ===================================
$mapeamentoCategoria = [
    'freios' => ['freio', 'pastilha', 'sapata', 'disco', 'lona'],
    'escapamento' => ['escapamento', 'abafador', 'silencioso', 'catalisador'],
    'arrefecimento' => ['arrefecimento', 'radiador', 'válvula termostática', 'bomba água', 'reservatório', 'mangueira'],
    'carroceria' => ['carroceria', 'parabrisa', 'retrovisor', 'parachoque', 'grade'],
    'motor' => ['óleo motor', 'filtro óleo', 'filtro ar', 'correia', 'vela'],
    'suspensao' => ['suspensão', 'amortecedor', 'mola', 'pivô', 'terminal'],
    'eletrica' => ['bateria', 'alternador', 'motor partida', 'farol', 'lâmpada'],
    'transmissao' => ['transmissão', 'embreagem', 'câmbio']
];

// ===================================
// 5. Associar peças aos itens do plano
// ===================================
$associacoes = [];
$erros = [];
$jaExistentes = 0;

$conn->begin_transaction();

try {
    foreach ($pecasStrada as $peca) {
        $categoriaPeca = strtolower($peca['categoria']);

        // Encontrar palavras-chave para esta categoria
        $palavrasChave = isset($mapeamentoCategoria[$categoriaPeca])
            ? $mapeamentoCategoria[$categoriaPeca]
            : [$categoriaPeca];

        // Buscar itens do plano que correspondem
        foreach ($itensPlano as $item) {
            $tituloItem = strtolower($item['descricao_titulo']);

            // Verificar se alguma palavra-chave está no título
            $corresponde = false;
            foreach ($palavrasChave as $palavra) {
                if (strpos($tituloItem, $palavra) !== false) {
                    $corresponde = true;
                    break;
                }
            }

            // Verificar também se o nome da peça contém palavras do título
            $nomePecaLower = strtolower($peca['nome']);
            $palavrasTitulo = explode(' ', $tituloItem);
            foreach ($palavrasTitulo as $palavraTitulo) {
                if (strlen($palavraTitulo) > 4 && strpos($nomePecaLower, $palavraTitulo) !== false) {
                    $corresponde = true;
                    break;
                }
            }

            if ($corresponde) {
                // Verificar se já existe associação
                $stmtCheck = $conn->prepare("
                    SELECT id FROM FF_PlanoManutencao_Pecas
                    WHERE plano_item_id = ? AND peca_id = ?
                ");
                $stmtCheck->bind_param("ii", $item['id'], $peca['id']);
                $stmtCheck->execute();
                $checkResult = $stmtCheck->get_result();

                if ($checkResult->num_rows > 0) {
                    $jaExistentes++;
                    $stmtCheck->close();
                    continue;
                }
                $stmtCheck->close();

                // Inserir associação
                $stmtInsert = $conn->prepare("
                    INSERT INTO FF_PlanoManutencao_Pecas
                    (plano_item_id, peca_id, codigo_peca, quantidade, criado_em)
                    VALUES (?, ?, ?, 1, NOW())
                ");
                $stmtInsert->bind_param("iis", $item['id'], $peca['id'], $peca['codigo']);

                if ($stmtInsert->execute()) {
                    $associacoes[] = [
                        'peca_id' => $peca['id'],
                        'peca_nome' => $peca['nome'],
                        'peca_categoria' => $peca['categoria'],
                        'item_id' => $item['id'],
                        'item_titulo' => $item['descricao_titulo'],
                        'item_km' => $item['km_recomendado']
                    ];
                } else {
                    $erros[] = "Erro ao associar peça {$peca['nome']}: " . $stmtInsert->error;
                }
                $stmtInsert->close();
            }
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'modelo' => $modelo,
        'modelos_strada_encontrados' => $modelosStrada,
        'estatisticas' => [
            'pecas_strada_encontradas' => count($pecasStrada),
            'itens_plano_encontrados' => count($itensPlano),
            'novas_associacoes' => count($associacoes),
            'associacoes_ja_existentes' => $jaExistentes,
            'erros' => count($erros)
        ],
        'associacoes' => $associacoes,
        'erros' => $erros,
        'pecas_importadas' => $pecasStrada
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
