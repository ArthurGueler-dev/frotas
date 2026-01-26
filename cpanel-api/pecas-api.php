<?php
/**
 * API REST para Gerenciamento de Peças
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

// Criar conexão com timeout
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Se for um GET e houver erro de conexão, retornar array vazio
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'total' => 0,
            'count' => 0,
            'data' => []
        ]);
        exit();
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro de conexão com o banco de dados',
            'details' => $e->getMessage()
        ]);
        exit();
    }
}

// Pegar método HTTP e parâmetros
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Função auxiliar para validar dados
function validarDadosPeca($dados, $requerTodos = true) {
    $erros = [];

    if ($requerTodos || isset($dados['nome'])) {
        if (empty($dados['nome'])) {
            $erros[] = 'nome é obrigatório';
        }
    }

    if (isset($dados['custo_unitario']) && !is_numeric($dados['custo_unitario'])) {
        $erros[] = 'custo_unitario deve ser um número';
    }

    if (isset($dados['estoque_minimo']) && !is_numeric($dados['estoque_minimo'])) {
        $erros[] = 'estoque_minimo deve ser um número';
    }

    if (isset($dados['estoque_atual']) && !is_numeric($dados['estoque_atual'])) {
        $erros[] = 'estoque_atual deve ser um número';
    }

    return $erros;
}

// Roteamento da API
try {
    switch ($method) {
        case 'GET':
            // GET /pecas-api.php - Listar todas
            // GET /pecas-api.php?categoria=filtro - Filtrar por categoria
            // GET /pecas-api.php?id=1 - Buscar por ID
            // GET /pecas-api.php?busca=termo - Buscar por nome/código

            if (isset($_GET['id'])) {
                // Buscar peça específica por ID
                $id = intval($_GET['id']);
                $stmt = $conn->prepare("SELECT * FROM FF_Pecas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $peca = $result->fetch_assoc();
                    echo json_encode([
                        'success' => true,
                        'data' => $peca
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Peça não encontrada'
                    ]);
                }
                $stmt->close();

            } elseif (isset($_GET['categoria'])) {
                // Filtrar por categoria
                $categoria = $_GET['categoria'];
                $stmt = $conn->prepare("SELECT * FROM FF_Pecas WHERE categoria = ? AND ativo = 1 ORDER BY nome ASC");
                $stmt->bind_param("s", $categoria);
                $stmt->execute();
                $result = $stmt->get_result();

                $pecas = [];
                while ($row = $result->fetch_assoc()) {
                    $pecas[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'count' => count($pecas),
                    'data' => $pecas
                ]);
                $stmt->close();

            } elseif (isset($_GET['busca'])) {
                // Buscar por nome ou código
                $busca = $_GET['busca'];
                $stmt = $conn->prepare("SELECT * FROM FF_Pecas WHERE (nome LIKE ? OR codigo LIKE ?) AND ativo = 1 ORDER BY nome ASC");
                $busca_like = "%{$busca}%";
                $stmt->bind_param("ss", $busca_like, $busca_like);
                $stmt->execute();
                $result = $stmt->get_result();

                $pecas = [];
                while ($row = $result->fetch_assoc()) {
                    $pecas[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'count' => count($pecas),
                    'data' => $pecas
                ]);
                $stmt->close();

            } elseif (isset($_GET['universal'])) {
                // Buscar apenas peças universais (compatíveis com todos os veículos)
                $universal = intval($_GET['universal']);
                $stmt = $conn->prepare("SELECT * FROM FF_Pecas WHERE universal = ? AND ativo = 1 ORDER BY categoria ASC, nome ASC");
                $stmt->bind_param("i", $universal);
                $stmt->execute();
                $result = $stmt->get_result();

                $pecas = [];
                while ($row = $result->fetch_assoc()) {
                    $pecas[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'count' => count($pecas),
                    'data' => $pecas
                ]);
                $stmt->close();

            } else {
                // Listar todas as peças
                $sql = "SELECT * FROM FF_Pecas WHERE ativo = 1 ORDER BY nome ASC";

                // Paginação opcional
                if (isset($_GET['limit']) && isset($_GET['offset'])) {
                    $limit = intval($_GET['limit']);
                    $offset = intval($_GET['offset']);
                    $sql = "SELECT * FROM FF_Pecas WHERE ativo = 1 ORDER BY nome ASC LIMIT {$limit} OFFSET {$offset}";
                }

                $result = $conn->query($sql);

                $pecas = [];
                while ($row = $result->fetch_assoc()) {
                    $pecas[] = $row;
                }

                // Contar total
                $total_result = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE ativo = 1");
                $total = $total_result->fetch_assoc()['total'];

                // Buscar categorias únicas
                $categorias_result = $conn->query("SELECT DISTINCT categoria FROM FF_Pecas WHERE ativo = 1 AND categoria IS NOT NULL ORDER BY categoria ASC");
                $categorias = [];
                while ($row = $categorias_result->fetch_assoc()) {
                    if ($row['categoria']) {
                        $categorias[] = $row['categoria'];
                    }
                }

                echo json_encode([
                    'success' => true,
                    'total' => $total,
                    'count' => count($pecas),
                    'categorias' => $categorias,
                    'data' => $pecas
                ]);
            }
            break;

        case 'POST':
            // POST /pecas-api.php - Criar nova peça

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Dados inválidos ou JSON malformado'
                ]);
                break;
            }

            // Validar dados
            $erros = validarDadosPeca($input, true);
            if (!empty($erros)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro de validação',
                    'details' => $erros
                ]);
                break;
            }

            // Inserir no banco
            $stmt = $conn->prepare(
                "INSERT INTO FF_Pecas
                (codigo, nome, descricao, unidade, custo_unitario, estoque_minimo, estoque_atual, fornecedor, categoria, vida_util_km, vida_util_meses, ativo, universal, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())"
            );

            $codigo = isset($input['codigo']) ? $input['codigo'] : null;
            $nome = $input['nome'];
            $descricao = isset($input['descricao']) ? $input['descricao'] : null;
            $unidade = isset($input['unidade']) ? $input['unidade'] : 'un';
            $custo_unitario = isset($input['custo_unitario']) ? $input['custo_unitario'] : 0;
            $estoque_minimo = isset($input['estoque_minimo']) ? $input['estoque_minimo'] : 0;
            $estoque_atual = isset($input['estoque_atual']) ? $input['estoque_atual'] : 0;
            $fornecedor = isset($input['fornecedor']) ? $input['fornecedor'] : null;
            $categoria = isset($input['categoria']) ? $input['categoria'] : null;
            $vida_util_km = isset($input['vida_util_km']) ? intval($input['vida_util_km']) : null;
            $vida_util_meses = isset($input['vida_util_meses']) ? intval($input['vida_util_meses']) : null;
            $universal = isset($input['universal']) ? intval($input['universal']) : 0;

            $stmt->bind_param(
                "ssssdiissiii",
                $codigo,
                $nome,
                $descricao,
                $unidade,
                $custo_unitario,
                $estoque_minimo,
                $estoque_atual,
                $fornecedor,
                $categoria,
                $vida_util_km,
                $vida_util_meses,
                $universal
            );

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Peça criada com sucesso',
                    'id' => $conn->insert_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao criar peça',
                    'details' => $stmt->error
                ]);
            }
            $stmt->close();
            break;

        case 'PUT':
            // PUT /pecas-api.php?id=1 - Atualizar peça

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

            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Dados inválidos ou JSON malformado'
                ]);
                break;
            }

            // Validar dados (não requer todos os campos)
            $erros = validarDadosPeca($input, false);
            if (!empty($erros)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro de validação',
                    'details' => $erros
                ]);
                break;
            }

            // Construir query dinamicamente
            $campos_update = [];
            $tipos = "";
            $valores = [];

            if (isset($input['codigo'])) {
                $campos_update[] = "codigo = ?";
                $tipos .= "s";
                $valores[] = $input['codigo'];
            }
            if (isset($input['nome'])) {
                $campos_update[] = "nome = ?";
                $tipos .= "s";
                $valores[] = $input['nome'];
            }
            if (isset($input['descricao'])) {
                $campos_update[] = "descricao = ?";
                $tipos .= "s";
                $valores[] = $input['descricao'];
            }
            if (isset($input['unidade'])) {
                $campos_update[] = "unidade = ?";
                $tipos .= "s";
                $valores[] = $input['unidade'];
            }
            if (isset($input['custo_unitario'])) {
                $campos_update[] = "custo_unitario = ?";
                $tipos .= "d";
                $valores[] = $input['custo_unitario'];
            }
            if (isset($input['estoque_minimo'])) {
                $campos_update[] = "estoque_minimo = ?";
                $tipos .= "i";
                $valores[] = $input['estoque_minimo'];
            }
            if (isset($input['estoque_atual'])) {
                $campos_update[] = "estoque_atual = ?";
                $tipos .= "i";
                $valores[] = $input['estoque_atual'];
            }
            if (isset($input['fornecedor'])) {
                $campos_update[] = "fornecedor = ?";
                $tipos .= "s";
                $valores[] = $input['fornecedor'];
            }
            if (isset($input['categoria'])) {
                $campos_update[] = "categoria = ?";
                $tipos .= "s";
                $valores[] = $input['categoria'];
            }
            if (isset($input['vida_util_km'])) {
                $campos_update[] = "vida_util_km = ?";
                $tipos .= "i";
                $valores[] = intval($input['vida_util_km']);
            }
            if (isset($input['vida_util_meses'])) {
                $campos_update[] = "vida_util_meses = ?";
                $tipos .= "i";
                $valores[] = intval($input['vida_util_meses']);
            }
            if (isset($input['universal'])) {
                $campos_update[] = "universal = ?";
                $tipos .= "i";
                $valores[] = intval($input['universal']);
            }

            if (empty($campos_update)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Nenhum campo para atualizar'
                ]);
                break;
            }

            // Adicionar atualizado_em
            $campos_update[] = "atualizado_em = NOW()";

            $sql = "UPDATE FF_Pecas SET " . implode(', ', $campos_update) . " WHERE id = ?";
            $tipos .= "i";
            $valores[] = $id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($tipos, ...$valores);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Peça atualizada com sucesso'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Peça não encontrada ou nenhuma alteração realizada'
                    ]);
                }
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao atualizar peça',
                    'details' => $stmt->error
                ]);
            }
            $stmt->close();
            break;

        case 'DELETE':
            // DELETE /pecas-api.php?id=1 - Deletar (desativar) peça

            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID não fornecido'
                ]);
                break;
            }

            $id = intval($_GET['id']);

            // Soft delete - apenas desativa
            $stmt = $conn->prepare("UPDATE FF_Pecas SET ativo = 0, atualizado_em = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Peça removida com sucesso'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Peça não encontrada'
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
