<?php
/**
 * Registrar Peças Motor/Transmissão da Strada na Tabela de Compatibilidade
 * USA O NOME CORRETO: "STRADA 1.4 Endurance" (sem "Fiat" na frente!)
 *
 * Executar: https://floripa.in9automacao.com.br/registrar-compatibilidade-strada-motor.php?confirmar=SIM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$host = '187.49.226.10';
$port = '3306';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// NOME CORRETO - o que funcionava antes!
$modelo_veiculo = 'STRADA 1.4 Endurance';
$ano_inicial = 2020;
$ano_final = 2024;

// Mapeamento categoria → categoria de aplicação
$categoriaMap = [
    'motor' => 'Motor',
    'transmissao' => 'Correias e Transmissão',
    'escapamento' => 'Escapamento',
    'rodas' => 'Outros'
];

// Buscar peças novas (motor/transmissão) - criadas hoje
$stmt = $pdo->prepare("
    SELECT id, codigo, nome, categoria, custo_unitario
    FROM FF_Pecas
    WHERE nome LIKE '%STRADA%'
      AND (categoria IN ('motor', 'transmissao', 'rodas') OR nome LIKE '%HOMOCINETICA%' OR nome LIKE '%VELA%' OR nome LIKE '%CORREIA%')
      AND ativo = 1
      AND DATE(criado_em) = CURDATE()
    ORDER BY categoria, nome
");
$stmt->execute();
$pecas = $stmt->fetchAll();

if (count($pecas) === 0) {
    // Se não encontrar de hoje, buscar todas de motor/transmissão
    $stmt = $pdo->prepare("
        SELECT id, codigo, nome, categoria, custo_unitario
        FROM FF_Pecas
        WHERE nome LIKE '%STRADA%'
          AND categoria IN ('motor', 'transmissao', 'rodas')
          AND ativo = 1
        ORDER BY categoria, nome
    ");
    $stmt->execute();
    $pecas = $stmt->fetchAll();
}

if (count($pecas) === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Nenhuma peça de motor/transmissão da Strada encontrada',
        'sugestao' => 'Execute primeiro: importar-pecas-strada-motor-transmissao.php'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$inseridos = 0;
$jaExistentes = 0;
$erros = [];

$pdo->beginTransaction();

try {
    foreach ($pecas as $peca) {
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

        // Inserir
        $stmtInsert = $pdo->prepare("
            INSERT INTO FF_Pecas_Compatibilidade (
                modelo_veiculo, ano_inicial, ano_final,
                peca_original_id, peca_similar_id,
                categoria_aplicacao, observacoes, ativo, criado_em
            ) VALUES (
                :modelo, :ano_ini, :ano_fim,
                :peca_id, NULL,
                :categoria, :obs, 1, NOW()
            )
        ");

        $obs = "Peça Motor/Transmissão importada via Perplexity AI - " . date('d/m/Y');

        $stmtInsert->execute([
            'modelo' => $modelo_veiculo,
            'ano_ini' => $ano_inicial,
            'ano_fim' => $ano_final,
            'peca_id' => $peca['id'],
            'categoria' => $categoriaAplicacao,
            'obs' => $obs
        ]);

        $inseridos++;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'modelo' => $modelo_veiculo,
        'periodo' => "$ano_inicial-$ano_final",
        'estatisticas' => [
            'pecas_encontradas' => count($pecas),
            'registros_inseridos' => $inseridos,
            'ja_existentes' => $jaExistentes,
            'erros' => count($erros)
        ],
        'pecas_registradas' => $pecas
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
