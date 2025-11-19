<?php
/**
 * API REST para Associação de Peças aos Itens de Plano de Manutenção
 * Versão: 1.0
 * Data: 19/11/2025
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder a preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuração do banco de dados
require_once 'config-db.php';

// Criar conexão
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexão
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com o banco de dados',
        'details' => $conn->connect_error
    ]);
    exit();
}

$conn->set_charset("utf8mb4");

// Pegar método HTTP e parâmetros
$method = $_SERVER['REQUEST_METHOD'];

// Roteamento da API
try {
    switch ($method) {
        case 'GET':
            // GET /plano-pecas-api.php?plano_item_id=1 - Listar peças de um item
            // GET /plano-pecas-api.php?id=1 - Buscar associação específica

            if (isset($_GET['plano_item_id'])) {
                // Listar peças de um item de plano
                $plano_item_id = intval($_GET['plano_item_id']);

                $stmt = $conn->prepare("
                    SELECT
                        pp.id,
                        pp.plano_item_id,
                        pp.peca_id,
                        pp.quantidade,
                        pp.criado_em,
                        p.codigo,
                        p.nome,
                        p.descricao,
                        p.unidade,
                        p.custo_unitario,
                        p.categoria,
                        (pp.quantidade * p.custo_unitario) as custo_total
                    FROM FF_PlanoManutencao_Pecas pp
                    JOIN FF_Pecas p ON p.id = pp.peca_id
                    WHERE pp.plano_item_id = ?
                    ORDER BY p.nome ASC
                ");
                $stmt->bind_param("i", $plano_item_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $pecas = [];
                $custo_total_pecas = 0;
                while ($row = $result->fetch_assoc()) {
                    $pecas[] = $row;
                    $custo_total_pecas += $row['custo_total'];
                }

                echo json_encode([
                    'success' => true,
                    'count' => count($pecas),
                    'custo_total_pecas' => $custo_total_pecas,
                    'data' => $pecas
                ]);
                $stmt->close();

            } elseif (isset($_GET['id'])) {
                // Buscar associação específica
                $id = intval($_GET['id']);
                $stmt = $conn->prepare("
                    SELECT
                        pp.*,
                        p.codigo,
                        p.nome,
                        p.descricao,
                        p.unidade,
                        p.custo_unitario,
                        p.categoria
                    FROM FF_PlanoManutencao_Pecas pp
                    JOIN FF_Pecas p ON p.id = pp.peca_id
                    WHERE pp.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'data' => $result->fetch_assoc()
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Associação não encontrada'
                    ]);
                }
                $stmt->close();

            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Parâmetro plano_item_id ou id é obrigatório'
                ]);
            }
            break;

        case 'POST':
            // POST /plano-pecas-api.php - Adicionar peça ao item de plano

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Dados inválidos ou JSON malformado'
                ]);
                break;
            }

            // Validar dados obrigatórios
            if (empty($input['plano_item_id']) || empty($input['peca_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'plano_item_id e peca_id são obrigatórios'
                ]);
                break;
            }

            $plano_item_id = intval($input['plano_item_id']);
            $peca_id = intval($input['peca_id']);
            $quantidade = isset($input['quantidade']) ? intval($input['quantidade']) : 1;

            // Verificar se já existe essa associação
            $stmt = $conn->prepare("SELECT id FROM FF_PlanoManutencao_Pecas WHERE plano_item_id = ? AND peca_id = ?");
            $stmt->bind_param("ii", $plano_item_id, $peca_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Atualizar quantidade existente
                $existing = $result->fetch_assoc();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE FF_PlanoManutencao_Pecas SET quantidade = quantidade + ? WHERE id = ?");
                $stmt->bind_param("ii", $quantidade, $existing['id']);
                $stmt->execute();

                echo json_encode([
                    'success' => true,
                    'message' => 'Quantidade da peça atualizada',
                    'id' => $existing['id']
                ]);
            } else {
                $stmt->close();

                // Buscar o código da peça
                $stmt_codigo = $conn->prepare("SELECT codigo FROM FF_Pecas WHERE id = ?");
                $stmt_codigo->bind_param("i", $peca_id);
                $stmt_codigo->execute();
                $result_codigo = $stmt_codigo->get_result();
                $codigo_peca = null;
                if ($result_codigo->num_rows > 0) {
                    $row = $result_codigo->fetch_assoc();
                    $codigo_peca = $row['codigo'];
                }
                $stmt_codigo->close();

                // Inserir nova associação com codigo_peca
                $stmt = $conn->prepare(
                    "INSERT INTO FF_PlanoManutencao_Pecas (plano_item_id, peca_id, codigo_peca, quantidade, criado_em)
                     VALUES (?, ?, ?, ?, NOW())"
                );
                $stmt->bind_param("iisi", $plano_item_id, $peca_id, $codigo_peca, $quantidade);

                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Peça adicionada ao plano com sucesso',
                        'id' => $conn->insert_id,
                        'codigo_peca' => $codigo_peca
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Erro ao adicionar peça',
                        'details' => $stmt->error
                    ]);
                }
            }
            $stmt->close();
            break;

        case 'PUT':
            // PUT /plano-pecas-api.php?id=1 - Atualizar quantidade

            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID não fornecido'
                ]);
                break;
            }

            $id = intval($_GET['id']);
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['quantidade'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'quantidade é obrigatória'
                ]);
                break;
            }

            $quantidade = intval($input['quantidade']);

            $stmt = $conn->prepare("UPDATE FF_PlanoManutencao_Pecas SET quantidade = ? WHERE id = ?");
            $stmt->bind_param("ii", $quantidade, $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Quantidade atualizada com sucesso'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Associação não encontrada'
                    ]);
                }
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao atualizar quantidade',
                    'details' => $stmt->error
                ]);
            }
            $stmt->close();
            break;

        case 'DELETE':
            // DELETE /plano-pecas-api.php?id=1 - Remover peça do plano

            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID não fornecido'
                ]);
                break;
            }

            $id = intval($_GET['id']);
            $stmt = $conn->prepare("DELETE FROM FF_PlanoManutencao_Pecas WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Peça removida do plano com sucesso'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Associação não encontrada'
                    ]);
                }
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao remover peça',
                    'details' => $stmt->error
                ]);
            }
            $stmt->close();
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Método não permitido'
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
