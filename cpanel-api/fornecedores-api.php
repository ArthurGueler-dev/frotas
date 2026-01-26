<?php
/**
 * API REST para Gerenciamento de Fornecedores
 * Data: 2026-01-12
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config-db.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de conexão']);
    exit();
}

$conn->set_charset("utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Buscar fornecedor específico
                $id = intval($_GET['id']);
                $stmt = $conn->prepare("SELECT * FROM FF_Fornecedores WHERE id = ?");
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
                    echo json_encode(['success' => false, 'error' => 'Fornecedor não encontrado']);
                }
            } elseif (isset($_GET['busca'])) {
                // Buscar por nome
                $busca = '%' . $_GET['busca'] . '%';
                $stmt = $conn->prepare("SELECT * FROM FF_Fornecedores WHERE nome LIKE ? ORDER BY nome ASC");
                $stmt->bind_param("s", $busca);
                $stmt->execute();
                $result = $stmt->get_result();

                $fornecedores = [];
                while ($row = $result->fetch_assoc()) {
                    $fornecedores[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'count' => count($fornecedores),
                    'data' => $fornecedores
                ]);
            } else {
                // Listar todos
                $result = $conn->query("SELECT * FROM FF_Fornecedores ORDER BY nome ASC");

                $fornecedores = [];
                while ($row = $result->fetch_assoc()) {
                    $fornecedores[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'count' => count($fornecedores),
                    'data' => $fornecedores
                ]);
            }
            break;

        case 'POST':
            // Criar novo fornecedor
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nome'])) {
                throw new Exception('Nome é obrigatório');
            }

            $stmt = $conn->prepare("INSERT INTO FF_Fornecedores (nome, razao_social, cnpj, telefone, celular, email, endereco, complemento, bairro, cep, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "ssssssssssss",
                $data['nome'],
                $data['razao_social'],
                $data['cnpj'],
                $data['telefone'],
                $data['celular'],
                $data['email'],
                $data['endereco'],
                $data['complemento'],
                $data['bairro'],
                $data['cep'],
                $data['cidade'],
                $data['estado']
            );

            $stmt->execute();

            echo json_encode([
                'success' => true,
                'id' => $conn->insert_id,
                'message' => 'Fornecedor criado com sucesso'
            ]);
            break;

        case 'PUT':
            // Atualizar fornecedor
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id'])) {
                throw new Exception('ID é obrigatório');
            }

            $stmt = $conn->prepare("UPDATE FF_Fornecedores SET nome = ?, razao_social = ?, cnpj = ?, telefone = ?, celular = ?, email = ?, endereco = ?, complemento = ?, bairro = ?, cep = ?, cidade = ?, estado = ? WHERE id = ?");

            $stmt->bind_param(
                "ssssssssssssi",
                $data['nome'],
                $data['razao_social'],
                $data['cnpj'],
                $data['telefone'],
                $data['celular'],
                $data['email'],
                $data['endereco'],
                $data['complemento'],
                $data['bairro'],
                $data['cep'],
                $data['cidade'],
                $data['estado'],
                $data['id']
            );

            $stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => 'Fornecedor atualizado com sucesso'
            ]);
            break;

        case 'DELETE':
            // Deletar fornecedor
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            if ($id === 0) {
                throw new Exception('ID inválido');
            }

            $stmt = $conn->prepare("DELETE FROM FF_Fornecedores WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => 'Fornecedor deletado com sucesso'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
