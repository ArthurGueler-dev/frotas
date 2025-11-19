<?php
/**
 * Script de teste para verificar a API de modelos
 * Acesse: https://seudominio.com.br/cpanel-api/teste-modelos.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste API Modelos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        h1 {
            color: #333;
        }
        h2 {
            color: #666;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🧪 Teste da API de Modelos</h1>

    <?php
    // Configuração
    $host = '187.49.226.10';
    $port = 3306;
    $user = 'f137049_tool';
    $password = 'In9@1234qwer';
    $database = 'f137049_in9aut';

    echo '<div class="test-section">';
    echo '<h2>1. Teste de Conexão com Banco de Dados</h2>';

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo '<p class="success">✅ Conexão estabelecida com sucesso!</p>';
    } catch (PDOException $e) {
        echo '<p class="error">❌ Erro de conexão: ' . $e->getMessage() . '</p>';
        die();
    }
    echo '</div>';

    // Teste 2: Verificar tabela
    echo '<div class="test-section">';
    echo '<h2>2. Verificar Tabela FF_VehicleModels</h2>';

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'FF_VehicleModels'");
        if ($stmt->rowCount() > 0) {
            echo '<p class="success">✅ Tabela FF_VehicleModels existe!</p>';

            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM FF_VehicleModels");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<p>📊 Total de modelos cadastrados: <strong>' . $result['total'] . '</strong></p>';
        } else {
            echo '<p class="error">❌ Tabela FF_VehicleModels não existe!</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">❌ Erro: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    // Teste 3: Listar modelos
    echo '<div class="test-section">';
    echo '<h2>3. Listar Modelos (GET)</h2>';

    try {
        $stmt = $pdo->query("
            SELECT
                m.id,
                m.marca,
                m.modelo,
                m.ano,
                m.tipo,
                m.motor,
                (SELECT COUNT(*) FROM Vehicles v WHERE v.VehicleName LIKE CONCAT('%', m.modelo, '%')) as qtdVeiculos
            FROM FF_VehicleModels m
            ORDER BY m.marca, m.modelo
            LIMIT 5
        ");
        $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($modelos) > 0) {
            echo '<p class="success">✅ Modelos encontrados! Mostrando primeiros 5:</p>';
            echo '<pre>' . json_encode($modelos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        } else {
            echo '<p class="error">❌ Nenhum modelo encontrado!</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">❌ Erro: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    // Teste 4: Endpoint da API
    echo '<div class="test-section">';
    echo '<h2>4. Testar Endpoint da API</h2>';
    echo '<div class="info">';
    echo '<p><strong>URL da API:</strong></p>';
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $apiUrl = $baseUrl . dirname($_SERVER['REQUEST_URI']) . '/modelos.php';
    echo '<p><a href="' . $apiUrl . '" target="_blank">' . $apiUrl . '</a></p>';
    echo '</div>';

    echo '<p>📝 Testes que você pode fazer:</p>';
    echo '<ul>';
    echo '<li><strong>GET</strong>: Abra a URL acima no navegador ou Postman</li>';
    echo '<li><strong>POST</strong>: Use Postman para criar um novo modelo</li>';
    echo '<li><strong>PUT</strong>: ' . $apiUrl . '?id=1 (com body JSON)</li>';
    echo '<li><strong>DELETE</strong>: ' . $apiUrl . '?id=1</li>';
    echo '</ul>';
    echo '</div>';

    // Teste 5: Verificar CORS
    echo '<div class="test-section">';
    echo '<h2>5. Verificar Headers CORS</h2>';

    $headers = getallheaders();
    echo '<p class="success">✅ Headers recebidos:</p>';
    echo '<pre>' . print_r($headers, true) . '</pre>';
    echo '</div>';

    // Resumo
    echo '<div class="test-section">';
    echo '<h2>📋 Resumo</h2>';
    echo '<p>Se todos os testes acima passaram, você pode:</p>';
    echo '<ol>';
    echo '<li>Acessar <code>modelos.html</code> no navegador</li>';
    echo '<li>Adicionar, editar e excluir modelos</li>';
    echo '<li>Os dados serão salvos no banco de dados MySQL</li>';
    echo '</ol>';
    echo '<p class="info"><strong>Próximo passo:</strong> Acesse <a href="../modelos.html">modelos.html</a> para usar a interface completa!</p>';
    echo '</div>';
    ?>
</body>
</html>
