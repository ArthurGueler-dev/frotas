<?php
/**
 * API REST para Ordens de Serviço
 * Suporta: GET (listar)
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração do banco de dados
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    // Pegar ordem_numero da URL se existir
    $ordem_numero = isset($_GET['ordem_numero']) ? $_GET['ordem_numero'] : null;

    if ($method === 'GET') {
        handleGet($pdo);
    } else if ($method === 'DELETE') {
        handleDelete($pdo, $ordem_numero);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
}

// ============== FUNÇÕES ==============

/**
 * GET - Listar todas as ordens de serviço
 */
function handleGet($pdo) {
    $stmt = $pdo->query("
        SELECT
            os.id,
            os.ordem_numero,
            os.placa_veiculo as placa,
            os.km_veiculo,
            os.responsavel,
            os.status,
            os.observacoes,
            os.data_criacao as data_abertura,
            os.data_finalizacao as data_conclusao,
            os.ocorrencia as tipo_servico,
            COALESCE(
                (SELECT SUM(valor_total) FROM ordemservico_itens WHERE ordem_numero = os.ordem_numero),
                0
            ) as custo_total,
            'Normal' as prioridade
        FROM ordemservico os
        ORDER BY
            CASE
                WHEN os.status = 'Aberta' THEN 1
                WHEN os.status = 'Diagnóstico' THEN 2
                WHEN os.status = 'Orçamento' THEN 3
                WHEN os.status = 'Execução' THEN 4
                WHEN os.status = 'Finalizada' THEN 5
                ELSE 6
            END,
            os.data_criacao DESC
    ");

    $ordens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ordens);
}

/**
 * DELETE - Excluir ordem de serviço
 */
function handleDelete($pdo, $ordem_numero) {
    if (!$ordem_numero) {
        http_response_code(400);
        echo json_encode(['error' => 'Número da OS é obrigatório']);
        return;
    }

    // Verificar se a OS existe
    $stmt = $pdo->prepare("SELECT id FROM ordemservico WHERE ordem_numero = ?");
    $stmt->execute([$ordem_numero]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Ordem de serviço não encontrada']);
        return;
    }

    // Excluir itens da OS primeiro
    $stmt = $pdo->prepare("DELETE FROM ordemservico_itens WHERE ordem_numero = ?");
    $stmt->execute([$ordem_numero]);

    // Excluir a OS
    $stmt = $pdo->prepare("DELETE FROM ordemservico WHERE ordem_numero = ?");
    $stmt->execute([$ordem_numero]);

    echo json_encode(['message' => 'Ordem de serviço excluída com sucesso']);
}
?>
