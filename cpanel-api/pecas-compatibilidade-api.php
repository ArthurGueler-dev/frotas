<?php
/**
 * API de Compatibilidade de Peças
 * Gerencia peças originais e similares/compatíveis por modelo e ano de veículo
 *
 * Endpoints:
 * - GET: Listar compatibilidades (filtros: modelo, ano, peca_original_id, id)
 * - POST: Criar nova compatibilidade
 * - PUT: Atualizar compatibilidade existente
 * - DELETE: Desativar compatibilidade (soft delete)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
        'error' => 'Erro de conexão com banco de dados',
        'details' => $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * GET - Listar compatibilidades
 * Parâmetros:
 * - modelo: nome do modelo de veículo
 * - ano: ano específico
 * - peca_original_id: ID da peça original
 * - id: ID específico do registro
 */
if ($method === 'GET') {
    try {
        $modelo = isset($_GET['modelo']) ? $_GET['modelo'] : null;
        $ano = isset($_GET['ano']) ? $_GET['ano'] : null;
        $pecaOriginalId = isset($_GET['peca_original_id']) ? $_GET['peca_original_id'] : null;
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        // Query específica por ID
        if ($id) {
            $stmt = $pdo->prepare("
                SELECT
                    c.id,
                    c.modelo_veiculo,
                    c.ano_inicial,
                    c.ano_final,
                    c.categoria_aplicacao,
                    c.observacoes,
                    c.ativo,
                    c.criado_em,
                    c.atualizado_em,
                    po.id as peca_original_id,
                    po.codigo as peca_original_codigo,
                    po.nome as peca_original_nome,
                    po.categoria as peca_original_categoria,
                    po.custo_unitario as peca_original_custo,
                    ps.id as peca_similar_id,
                    ps.codigo as peca_similar_codigo,
                    ps.nome as peca_similar_nome,
                    ps.custo_unitario as peca_similar_custo
                FROM FF_Pecas_Compatibilidade c
                INNER JOIN FF_Pecas po ON c.peca_original_id = po.id
                LEFT JOIN FF_Pecas ps ON c.peca_similar_id = ps.id
                WHERE c.id = :id AND c.ativo = 1
            ");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();

            if (!$result) {
                echo json_encode(['success' => false, 'error' => 'Registro não encontrado']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => formatarRegistro($result)
            ]);
            exit;
        }

        // Query com filtros
        $sql = "
            SELECT
                c.id,
                c.modelo_veiculo,
                c.ano_inicial,
                c.ano_final,
                c.categoria_aplicacao,
                c.observacoes,
                c.peca_original_id,
                po.codigo as peca_original_codigo,
                po.nome as peca_original_nome,
                po.categoria as peca_original_categoria,
                po.custo_unitario as peca_original_custo
            FROM FF_Pecas_Compatibilidade c
            INNER JOIN FF_Pecas po ON c.peca_original_id = po.id
            WHERE c.ativo = 1 AND c.peca_similar_id IS NULL
        ";

        $params = [];

        if ($modelo) {
            $sql .= " AND c.modelo_veiculo = :modelo";
            $params['modelo'] = $modelo;
        }

        if ($ano) {
            $sql .= " AND c.ano_inicial <= :ano AND (c.ano_final IS NULL OR c.ano_final >= :ano)";
            $params['ano'] = $ano;
        }

        if ($pecaOriginalId) {
            $sql .= " AND c.peca_original_id = :peca_original_id";
            $params['peca_original_id'] = $pecaOriginalId;
        }

        $sql .= " ORDER BY c.modelo_veiculo, c.categoria_aplicacao, c.ano_inicial";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $originais = $stmt->fetchAll();

        // Para cada peça original, buscar similares
        $resultado = [];
        foreach ($originais as $original) {
            $stmtSimilares = $pdo->prepare("
                SELECT
                    c.id as compatibilidade_id,
                    c.observacoes,
                    ps.id,
                    ps.codigo,
                    ps.nome,
                    ps.custo_unitario
                FROM FF_Pecas_Compatibilidade c
                INNER JOIN FF_Pecas ps ON c.peca_similar_id = ps.id
                WHERE c.peca_original_id = :peca_original_id
                  AND c.modelo_veiculo = :modelo
                  AND c.ano_inicial = :ano_inicial
                  AND c.ativo = 1
                  AND c.peca_similar_id IS NOT NULL
            ");
            $stmtSimilares->execute([
                'peca_original_id' => $original['peca_original_id'],
                'modelo' => $original['modelo_veiculo'],
                'ano_inicial' => $original['ano_inicial']
            ]);
            $similares = $stmtSimilares->fetchAll();

            // Formatar similares usando foreach (compatibilidade PHP antigo)
            $similaresFormatados = [];
            foreach ($similares as $similar) {
                $similaresFormatados[] = [
                    'compatibilidade_id' => (int)$similar['compatibilidade_id'],
                    'id' => (int)$similar['id'],
                    'codigo' => $similar['codigo'],
                    'nome' => $similar['nome'],
                    'custo_unitario' => (float)$similar['custo_unitario'],
                    'observacoes' => $similar['observacoes']
                ];
            }

            $resultado[] = [
                'id' => $original['id'],
                'modelo_veiculo' => $original['modelo_veiculo'],
                'ano_inicial' => (int)$original['ano_inicial'],
                'ano_final' => $original['ano_final'] ? (int)$original['ano_final'] : null,
                'categoria_aplicacao' => $original['categoria_aplicacao'],
                'observacoes' => $original['observacoes'],
                'peca_original' => [
                    'id' => (int)$original['peca_original_id'],
                    'codigo' => $original['peca_original_codigo'],
                    'nome' => $original['peca_original_nome'],
                    'categoria' => $original['peca_original_categoria'],
                    'custo_unitario' => (float)$original['peca_original_custo']
                ],
                'pecas_similares' => $similaresFormatados
            ];
        }

        echo json_encode([
            'success' => true,
            'count' => count($resultado),
            'data' => $resultado
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar compatibilidades',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * POST - Criar nova compatibilidade
 * Body JSON:
 * {
 *   "modelo_veiculo": "Toyota HILUX",
 *   "ano_inicial": 2020,
 *   "ano_final": 2024,
 *   "peca_original_id": 15,
 *   "peca_similar_id": 16,
 *   "categoria_aplicacao": "Filtros",
 *   "observacoes": "Compatível com motores 2.8 diesel"
 * }
 */
if ($method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validações
        if (!isset($input['modelo_veiculo']) || empty(trim($input['modelo_veiculo']))) {
            echo json_encode(['success' => false, 'error' => 'Modelo de veículo é obrigatório']);
            exit;
        }

        if (!isset($input['ano_inicial']) || !is_numeric($input['ano_inicial'])) {
            echo json_encode(['success' => false, 'error' => 'Ano inicial é obrigatório e deve ser numérico']);
            exit;
        }

        if (!isset($input['peca_original_id']) || !is_numeric($input['peca_original_id'])) {
            echo json_encode(['success' => false, 'error' => 'ID da peça original é obrigatório']);
            exit;
        }

        // Validar ano_final >= ano_inicial
        if (isset($input['ano_final']) && $input['ano_final'] !== null) {
            if (!is_numeric($input['ano_final'])) {
                echo json_encode(['success' => false, 'error' => 'Ano final deve ser numérico']);
                exit;
            }
            if ($input['ano_final'] < $input['ano_inicial']) {
                echo json_encode(['success' => false, 'error' => 'Ano final deve ser maior ou igual ao ano inicial']);
                exit;
            }
        }

        // Verificar se peça original existe
        $stmt = $pdo->prepare("SELECT id FROM FF_Pecas WHERE id = :id AND ativo = 1");
        $stmt->execute(['id' => $input['peca_original_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Peça original não encontrada ou inativa']);
            exit;
        }

        // Verificar se peça similar existe (se fornecida)
        if (isset($input['peca_similar_id']) && $input['peca_similar_id'] !== null) {
            $stmt = $pdo->prepare("SELECT id FROM FF_Pecas WHERE id = :id AND ativo = 1");
            $stmt->execute(['id' => $input['peca_similar_id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Peça similar não encontrada ou inativa']);
                exit;
            }
        }

        // Verificar duplicatas
        $checkSql = "
            SELECT id FROM FF_Pecas_Compatibilidade
            WHERE modelo_veiculo = :modelo
              AND ano_inicial = :ano_inicial
              AND peca_original_id = :peca_original_id
              AND peca_similar_id " . (isset($input['peca_similar_id']) ? "= :peca_similar_id" : "IS NULL") . "
              AND ativo = 1
        ";
        $checkParams = [
            'modelo' => trim($input['modelo_veiculo']),
            'ano_inicial' => $input['ano_inicial'],
            'peca_original_id' => $input['peca_original_id']
        ];
        if (isset($input['peca_similar_id']) && $input['peca_similar_id'] !== null) {
            $checkParams['peca_similar_id'] = $input['peca_similar_id'];
        }

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute($checkParams);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Compatibilidade já cadastrada para este modelo/ano/peças']);
            exit;
        }

        // Inserir registro
        $stmt = $pdo->prepare("
            INSERT INTO FF_Pecas_Compatibilidade (
                modelo_veiculo,
                ano_inicial,
                ano_final,
                peca_original_id,
                peca_similar_id,
                categoria_aplicacao,
                observacoes,
                ativo
            ) VALUES (
                :modelo_veiculo,
                :ano_inicial,
                :ano_final,
                :peca_original_id,
                :peca_similar_id,
                :categoria_aplicacao,
                :observacoes,
                1
            )
        ");

        $stmt->execute([
            'modelo_veiculo' => trim($input['modelo_veiculo']),
            'ano_inicial' => $input['ano_inicial'],
            'ano_final' => isset($input['ano_final']) ? $input['ano_final'] : null,
            'peca_original_id' => $input['peca_original_id'],
            'peca_similar_id' => isset($input['peca_similar_id']) ? $input['peca_similar_id'] : null,
            'categoria_aplicacao' => isset($input['categoria_aplicacao']) ? $input['categoria_aplicacao'] : null,
            'observacoes' => isset($input['observacoes']) ? $input['observacoes'] : null
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Compatibilidade criada com sucesso',
            'id' => (int)$pdo->lastInsertId()
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao criar compatibilidade',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * PUT - Atualizar compatibilidade existente
 * Query: ?id=123
 * Body JSON: campos a atualizar
 */
if ($method === 'PUT') {
    try {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID é obrigatório']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Verificar se registro existe
        $stmt = $pdo->prepare("SELECT id FROM FF_Pecas_Compatibilidade WHERE id = :id AND ativo = 1");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Registro não encontrado']);
            exit;
        }

        // Construir query de update dinamicamente
        $updateFields = [];
        $params = ['id' => $id];

        if (isset($input['modelo_veiculo'])) {
            $updateFields[] = 'modelo_veiculo = :modelo_veiculo';
            $params['modelo_veiculo'] = trim($input['modelo_veiculo']);
        }

        if (isset($input['ano_inicial'])) {
            $updateFields[] = 'ano_inicial = :ano_inicial';
            $params['ano_inicial'] = $input['ano_inicial'];
        }

        if (isset($input['ano_final'])) {
            $updateFields[] = 'ano_final = :ano_final';
            $params['ano_final'] = $input['ano_final'];
        }

        if (isset($input['peca_original_id'])) {
            // Verificar se peça existe
            $stmt = $pdo->prepare("SELECT id FROM FF_Pecas WHERE id = :id AND ativo = 1");
            $stmt->execute(['id' => $input['peca_original_id']]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Peça original não encontrada']);
                exit;
            }
            $updateFields[] = 'peca_original_id = :peca_original_id';
            $params['peca_original_id'] = $input['peca_original_id'];
        }

        if (isset($input['peca_similar_id'])) {
            if ($input['peca_similar_id'] !== null) {
                // Verificar se peça existe
                $stmt = $pdo->prepare("SELECT id FROM FF_Pecas WHERE id = :id AND ativo = 1");
                $stmt->execute(['id' => $input['peca_similar_id']]);
                if (!$stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Peça similar não encontrada']);
                    exit;
                }
            }
            $updateFields[] = 'peca_similar_id = :peca_similar_id';
            $params['peca_similar_id'] = $input['peca_similar_id'];
        }

        if (isset($input['categoria_aplicacao'])) {
            $updateFields[] = 'categoria_aplicacao = :categoria_aplicacao';
            $params['categoria_aplicacao'] = $input['categoria_aplicacao'];
        }

        if (isset($input['observacoes'])) {
            $updateFields[] = 'observacoes = :observacoes';
            $params['observacoes'] = $input['observacoes'];
        }

        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
            exit;
        }

        $sql = "UPDATE FF_Pecas_Compatibilidade SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Compatibilidade atualizada com sucesso'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao atualizar compatibilidade',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * DELETE - Desativar compatibilidade (soft delete)
 * Query: ?id=123
 */
if ($method === 'DELETE') {
    try {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID é obrigatório']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE FF_Pecas_Compatibilidade SET ativo = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => 'Registro não encontrado']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Compatibilidade desativada com sucesso'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao desativar compatibilidade',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}

// Método não suportado
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Método não suportado'
]);

/**
 * Função auxiliar para formatar registro individual
 */
function formatarRegistro($row) {
    return [
        'id' => (int)$row['id'],
        'modelo_veiculo' => $row['modelo_veiculo'],
        'ano_inicial' => (int)$row['ano_inicial'],
        'ano_final' => $row['ano_final'] ? (int)$row['ano_final'] : null,
        'categoria_aplicacao' => $row['categoria_aplicacao'],
        'observacoes' => $row['observacoes'],
        'ativo' => (int)$row['ativo'],
        'criado_em' => $row['criado_em'],
        'atualizado_em' => $row['atualizado_em'],
        'peca_original' => [
            'id' => (int)$row['peca_original_id'],
            'codigo' => $row['peca_original_codigo'],
            'nome' => $row['peca_original_nome'],
            'categoria' => $row['peca_original_categoria'],
            'custo_unitario' => (float)$row['peca_original_custo']
        ],
        'peca_similar' => $row['peca_similar_id'] ? [
            'id' => (int)$row['peca_similar_id'],
            'codigo' => $row['peca_similar_codigo'],
            'nome' => $row['peca_similar_nome'],
            'custo_unitario' => (float)$row['peca_similar_custo']
        ] : null
    ];
}
