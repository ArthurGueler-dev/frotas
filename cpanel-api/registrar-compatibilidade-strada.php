<?php
/**
 * Registrar Peças da Strada na Tabela de Compatibilidade
 * As peças já foram importadas em FF_Pecas via importar-pecas-strada-perplexity.php
 * Este script registra na tabela FF_Pecas_Compatibilidade para aparecer na aba "Peças Compatíveis"
 *
 * Executar: https://floripa.in9automacao.com.br/registrar-compatibilidade-strada.php?confirmar=SIM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar',
        'warning' => 'Isso irá registrar as peças da Strada na tabela de compatibilidade'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Configuração do banco de dados
$host = '187.49.226.10';
$port = '3306';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão',
        'details' => $e->getMessage()
    ]);
    exit;
}

// ===================================
// 0. Descobrir nome exato do modelo STRADA no sistema
// ===================================

// Primeiro, verificar na tabela FF_VehicleModels
$stmtModelo = $pdo->query("
    SELECT DISTINCT CONCAT(marca, ' ', modelo) as nome_completo
    FROM FF_VehicleModels
    WHERE modelo LIKE '%STRADA%'
    ORDER BY modelo
");
$modelosEncontrados = $stmtModelo->fetchAll(PDO::FETCH_COLUMN);

// Se não encontrar em FF_VehicleModels, verificar em Planos_Manutenção
if (empty($modelosEncontrados)) {
    $stmtModelo = $pdo->query("
        SELECT DISTINCT modelo_carro
        FROM `Planos_Manutenção`
        WHERE modelo_carro LIKE '%STRADA%'
        ORDER BY modelo_carro
    ");
    $modelosEncontrados = $stmtModelo->fetchAll(PDO::FETCH_COLUMN);
}

// Se não encontrar em nenhum lugar, verificar em FF_Pecas_Compatibilidade existente
if (empty($modelosEncontrados)) {
    $stmtModelo = $pdo->query("
        SELECT DISTINCT modelo_veiculo
        FROM FF_Pecas_Compatibilidade
        WHERE modelo_veiculo LIKE '%STRADA%'
        ORDER BY modelo_veiculo
    ");
    $modelosEncontrados = $stmtModelo->fetchAll(PDO::FETCH_COLUMN);
}

// Se ainda não encontrar, usar valor padrão e listar todos os modelos disponíveis
if (empty($modelosEncontrados)) {
    // Listar todos os modelos para referência
    $stmtTodos = $pdo->query("
        SELECT DISTINCT CONCAT(marca, ' ', modelo) as nome FROM FF_VehicleModels
        UNION
        SELECT DISTINCT modelo_carro FROM `Planos_Manutenção`
        ORDER BY nome
    ");
    $todosModelos = $stmtTodos->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => false,
        'error' => 'Nenhum modelo STRADA encontrado no sistema',
        'modelos_disponiveis' => $todosModelos,
        'sugestao' => 'Informe o nome exato do modelo STRADA ou cadastre-o primeiro'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Usar o primeiro modelo encontrado (ou permitir escolha via parâmetro)
$modelo_veiculo = isset($_GET['modelo']) ? $_GET['modelo'] : $modelosEncontrados[0];

// Configurações
$ano_inicial = 2020;
$ano_final = 2024;

// Mapeamento de categoria interna → categoria de aplicação
$categoriaMap = [
    'freios' => 'Freios',
    'escapamento' => 'Escapamento',
    'arrefecimento' => 'Arrefecimento',
    'carroceria' => 'Carroceria',
    'motor' => 'Motor',
    'suspensao' => 'Suspensão',
    'eletrica' => 'Elétrica',
    'transmissao' => 'Transmissão'
];

// ===================================
// 1. Buscar peças da Strada já importadas
// ===================================
$stmt = $pdo->prepare("
    SELECT id, codigo, nome, categoria, custo_unitario
    FROM FF_Pecas
    WHERE nome LIKE '%STRADA%' AND ativo = 1
    ORDER BY categoria, nome
");
$stmt->execute();
$pecasStrada = $stmt->fetchAll();

if (count($pecasStrada) === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Nenhuma peça da Strada encontrada na tabela FF_Pecas',
        'sugestao' => 'Execute primeiro: importar-pecas-strada-perplexity.php'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ===================================
// 2. Registrar cada peça na tabela de compatibilidade
// ===================================
$inseridos = 0;
$jaExistentes = 0;
$erros = [];

$pdo->beginTransaction();

try {
    foreach ($pecasStrada as $peca) {
        // Determinar categoria de aplicação
        $categoriaInterna = strtolower($peca['categoria']);
        $categoriaAplicacao = isset($categoriaMap[$categoriaInterna])
            ? $categoriaMap[$categoriaInterna]
            : ucfirst($categoriaInterna);

        // Verificar se já existe
        $stmtCheck = $pdo->prepare("
            SELECT id FROM FF_Pecas_Compatibilidade
            WHERE modelo_veiculo = :modelo
              AND peca_original_id = :peca_id
              AND peca_similar_id IS NULL
              AND ativo = 1
        ");
        $stmtCheck->execute([
            'modelo' => $modelo_veiculo,
            'peca_id' => $peca['id']
        ]);

        if ($stmtCheck->fetch()) {
            $jaExistentes++;
            continue;
        }

        // Inserir na tabela de compatibilidade
        $stmtInsert = $pdo->prepare("
            INSERT INTO FF_Pecas_Compatibilidade (
                modelo_veiculo,
                ano_inicial,
                ano_final,
                peca_original_id,
                peca_similar_id,
                categoria_aplicacao,
                observacoes,
                ativo,
                criado_em
            ) VALUES (
                :modelo_veiculo,
                :ano_inicial,
                :ano_final,
                :peca_original_id,
                NULL,
                :categoria_aplicacao,
                :observacoes,
                1,
                NOW()
            )
        ");

        $observacoes = "Peça importada via Perplexity AI - " . date('d/m/Y');

        $stmtInsert->execute([
            'modelo_veiculo' => $modelo_veiculo,
            'ano_inicial' => $ano_inicial,
            'ano_final' => $ano_final,
            'peca_original_id' => $peca['id'],
            'categoria_aplicacao' => $categoriaAplicacao,
            'observacoes' => $observacoes
        ]);

        $inseridos++;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'modelo' => $modelo_veiculo,
        'modelos_strada_encontrados' => $modelosEncontrados,
        'periodo' => "$ano_inicial-$ano_final",
        'estatisticas' => [
            'pecas_encontradas' => count($pecasStrada),
            'registros_inseridos' => $inseridos,
            'ja_existentes' => $jaExistentes,
            'erros' => count($erros)
        ],
        'pecas_registradas' => array_map(function($p) use ($categoriaMap) {
            return [
                'id' => $p['id'],
                'nome' => $p['nome'],
                'categoria' => $p['categoria'],
                'custo' => $p['custo_unitario']
            ];
        }, $pecasStrada),
        'proximos_passos' => [
            'Acesse a página: https://frotas.in9automacao.com.br/planos-manutencao-novo.html',
            'Selecione o modelo STRADA 1.4',
            'Clique na aba "Peças Compatíveis"',
            'As peças devem aparecer organizadas por categoria'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar',
        'details' => $e->getMessage()
    ]);
}
?>
