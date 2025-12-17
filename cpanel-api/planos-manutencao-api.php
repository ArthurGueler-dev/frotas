<?php
/**
 * API REST para Planos de Manutenção
 * Versão: 1.0
 * Data: 13/11/2025
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
$path_parts = explode('/', trim(parse_url($request_uri, PHP_URL_PATH), '/'));

// Função auxiliar para validar dados
function validarDadosPlano($dados, $requerTodos = true) {
    $erros = [];

    if ($requerTodos || isset($dados['modelo_carro'])) {
        if (empty($dados['modelo_carro'])) {
            $erros[] = 'modelo_carro é obrigatório';
        }
    }

    if ($requerTodos || isset($dados['descricao_titulo'])) {
        if (empty($dados['descricao_titulo'])) {
            $erros[] = 'descricao_titulo é obrigatório';
        }
    }

    if (isset($dados['criticidade'])) {
        $criticidade_lower = strtolower($dados['criticidade']);
        $criticidades_validas = ['baixa', 'média', 'media', 'alta', 'crítica', 'critica'];
        if (!in_array($criticidade_lower, $criticidades_validas)) {
            $erros[] = 'criticidade deve ser: Baixa, Média, Alta ou Crítica';
        }
    }

    if (isset($dados['custo_estimado']) && !is_numeric($dados['custo_estimado'])) {
        $erros[] = 'custo_estimado deve ser um número';
    }

    if (isset($dados['km_recomendado']) && !is_numeric($dados['km_recomendado'])) {
        $erros[] = 'km_recomendado deve ser um número';
    }

    return $erros;
}

// Roteamento da API
try {
    switch ($method) {
        case 'GET':
            // GET /planos-manutencao-api.php - Listar todos
            // GET /planos-manutencao-api.php?modelo=Toyota - Filtrar por modelo
            // GET /planos-manutencao-api.php?id=1 - Buscar por ID

            if (isset($_GET['id'])) {
                // Buscar plano específico por ID
                $id = intval($_GET['id']);
                $stmt = $conn->prepare("SELECT * FROM Planos_Manutenção WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $plano = $result->fetch_assoc();
                    echo json_encode([
                        'success' => true,
                        'data' => $plano
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Plano não encontrado'
                    ]);
                }
                $stmt->close();

            } elseif (isset($_GET['modelo'])) {
                // Filtrar por modelo
                $modelo = $_GET['modelo'];
                $stmt = $conn->prepare("SELECT * FROM Planos_Manutenção WHERE modelo_carro LIKE ? ORDER BY km_recomendado ASC");
                $modelo_like = "%{$modelo}%";
                $stmt->bind_param("s", $modelo_like);
                $stmt->execute();
                $result = $stmt->get_result();

                $planos = [];
                while ($row = $result->fetch_assoc()) {
                    $planos[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'count' => count($planos),
                    'data' => $planos
                ]);
                $stmt->close();

            } else {
                // Listar todos os planos
                $sql = "SELECT * FROM Planos_Manutenção ORDER BY modelo_carro, km_recomendado ASC";

                // Paginação opcional
                if (isset($_GET['limit']) && isset($_GET['offset'])) {
                    $limit = intval($_GET['limit']);
                    $offset = intval($_GET['offset']);
                    $sql .= " LIMIT {$limit} OFFSET {$offset}";
                }

                $result = $conn->query($sql);

                $planos = [];
                while ($row = $result->fetch_assoc()) {
                    $planos[] = $row;
                }

                // Contar total
                $total_result = $conn->query("SELECT COUNT(*) as total FROM Planos_Manutenção");
                $total = $total_result->fetch_assoc()['total'];

                echo json_encode([
                    'success' => true,
                    'total' => $total,
                    'count' => count($planos),
                    'data' => $planos
                ]);
            }
            break;

        case 'POST':
            // POST /planos-manutencao-api.php - Criar novo plano

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
            $erros = validarDadosPlano($input, true);
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
                "INSERT INTO Planos_Manutenção
                (modelo_carro, descricao_titulo, km_recomendado, intervalo_tempo, custo_estimado, criticidade, descricao_observacao)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssissss",
                $input['modelo_carro'],
                $input['descricao_titulo'],
                $input['km_recomendado'],
                $input['intervalo_tempo'],
                $input['custo_estimado'],
                $input['criticidade'],
                $input['descricao_observacao']
            );

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Plano criado com sucesso',
                    'id' => $conn->insert_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao criar plano',
                    'details' => $stmt->error
                ]);
            }
            $stmt->close();
            break;

        case 'PUT':
            // PUT /planos-manutencao-api.php?id=1 - Atualizar plano

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
            $erros = validarDadosPlano($input, false);
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

            if (isset($input['modelo_carro'])) {
                $campos_update[] = "modelo_carro = ?";
                $tipos .= "s";
                $valores[] = $input['modelo_carro'];
            }
            if (isset($input['descricao_titulo'])) {
                $campos_update[] = "descricao_titulo = ?";
                $tipos .= "s";
                $valores[] = $input['descricao_titulo'];
            }
            if (isset($input['km_recomendado'])) {
                $campos_update[] = "km_recomendado = ?";
                $tipos .= "i";
                $valores[] = $input['km_recomendado'];
            }
            if (isset($input['intervalo_tempo'])) {
                $campos_update[] = "intervalo_tempo = ?";
                $tipos .= "s";
                $valores[] = $input['intervalo_tempo'];
            }
            if (isset($input['custo_estimado'])) {
                $campos_update[] = "custo_estimado = ?";
                $tipos .= "d";
                $valores[] = $input['custo_estimado'];
            }
            if (isset($input['criticidade'])) {
                $campos_update[] = "criticidade = ?";
                $tipos .= "s";
                $valores[] = $input['criticidade'];
            }
            if (isset($input['descricao_observacao'])) {
                $campos_update[] = "descricao_observacao = ?";
                $tipos .= "s";
                $valores[] = $input['descricao_observacao'];
            }

            if (empty($campos_update)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Nenhum campo para atualizar'
                ]);
                break;
            }

            $sql = "UPDATE Planos_Manutenção SET " . implode(', ', $campos_update) . " WHERE id = ?";
            $tipos .= "i";
            $valores[] = $id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($tipos, ...$valores);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Plano atualizado com sucesso'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Plano não encontrado ou nenhuma alteração realizada'
                    ]);
                }
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao atualizar plano',
                    'details' => $stmt->error
                ]);
            }
            $stmt->close();
            break;

        case 'DELETE':
            // DELETE /planos-manutencao-api.php?id=1 - Deletar plano

            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID não fornecido'
                ]);
                break;
            }

            $id = intval($_GET['id']);
            $stmt = $conn->prepare("DELETE FROM Planos_Manutenção WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Plano deletado com sucesso'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Plano não encontrado'
                    ]);
                }
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro ao deletar plano',
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
