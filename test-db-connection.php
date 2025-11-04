<?php
/**
 * Teste de Conexão com Banco de Dados
 *
 * Use este arquivo para testar se a conexão com o banco está funcionando
 * Acesse: http://seusite.com/test-db-connection.php
 */

// Incluir arquivo de configuração
require_once 'db-config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - FleetFlow</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1E3A8A;
            margin-bottom: 20px;
        }
        .success {
            padding: 15px;
            background: #D1FAE5;
            border-left: 4px solid #10B981;
            color: #065F46;
            border-radius: 4px;
            margin: 20px 0;
        }
        .error {
            padding: 15px;
            background: #FEE2E2;
            border-left: 4px solid #EF4444;
            color: #991B1B;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info {
            padding: 15px;
            background: #DBEAFE;
            border-left: 4px solid #3B82F6;
            color: #1E40AF;
            border-radius: 4px;
            margin: 20px 0;
        }
        .code {
            background: #1F2937;
            color: #F3F4F6;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        th {
            background: #F9FAFB;
            font-weight: bold;
            color: #1F2937;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste de Conexão - FleetFlow</h1>

        <?php
        // Teste 1: Verificar se arquivo de configuração existe
        echo "<h2>1. Arquivo de Configuração</h2>";
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
            echo '<div class="success">✅ Arquivo db-config.php carregado com sucesso!</div>';

            echo '<table>';
            echo '<tr><th>Configuração</th><th>Valor</th></tr>';
            echo '<tr><td>Host</td><td>' . DB_HOST . '</td></tr>';
            echo '<tr><td>Database</td><td>' . DB_NAME . '</td></tr>';
            echo '<tr><td>Usuário</td><td>' . DB_USER . '</td></tr>';
            echo '<tr><td>Senha</td><td>' . (DB_PASS ? '••••••••' : '<span style="color: red;">Não definida</span>') . '</td></tr>';
            echo '</table>';
        } else {
            echo '<div class="error">❌ Erro ao carregar configurações do banco de dados</div>';
        }

        // Teste 2: Tentar conectar ao banco
        echo "<h2>2. Teste de Conexão</h2>";
        $conn = getDBConnection();

        if ($conn !== null) {
            echo '<div class="success">✅ Conexão estabelecida com sucesso!</div>';

            // Teste 3: Verificar se tabela Drivers existe
            echo "<h2>3. Verificando Tabela 'Drivers'</h2>";
            try {
                $stmt = $conn->query("SHOW TABLES LIKE 'Drivers'");
                $tableExists = $stmt->rowCount() > 0;

                if ($tableExists) {
                    echo '<div class="success">✅ Tabela "Drivers" encontrada!</div>';

                    // Teste 4: Contar registros
                    echo "<h2>4. Contagem de Registros</h2>";
                    $stmt = $conn->query("SELECT COUNT(*) as total FROM Drivers");
                    $count = $stmt->fetch();
                    echo '<div class="info">📊 Total de motoristas: <strong>' . $count['total'] . '</strong></div>';

                    // Teste 5: Verificar estrutura da tabela
                    echo "<h2>5. Estrutura da Tabela</h2>";
                    $stmt = $conn->query("DESCRIBE Drivers");
                    $columns = $stmt->fetchAll();

                    echo '<table>';
                    echo '<tr><th>Coluna</th><th>Tipo</th><th>Nulo</th><th>Chave</th></tr>';
                    foreach ($columns as $col) {
                        echo '<tr>';
                        echo '<td>' . $col['Field'] . '</td>';
                        echo '<td>' . $col['Type'] . '</td>';
                        echo '<td>' . $col['Null'] . '</td>';
                        echo '<td>' . $col['Key'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    // Verificar se colunas necessárias existem
                    $requiredColumns = ['DriverID', 'FirstName', 'LastName'];
                    $existingColumns = array_column($columns, 'Field');

                    $missingColumns = array_diff($requiredColumns, $existingColumns);

                    if (empty($missingColumns)) {
                        echo '<div class="success">✅ Todas as colunas necessárias estão presentes!</div>';
                    } else {
                        echo '<div class="error">❌ Colunas faltando: ' . implode(', ', $missingColumns) . '</div>';
                    }

                    // Teste 6: Buscar alguns registros de exemplo
                    if ($count['total'] > 0) {
                        echo "<h2>6. Primeiros 5 Registros</h2>";
                        $stmt = $conn->query("SELECT DriverID, FirstName, LastName FROM Drivers LIMIT 5");
                        $drivers = $stmt->fetchAll();

                        echo '<table>';
                        echo '<tr><th>ID</th><th>Primeiro Nome</th><th>Último Nome</th></tr>';
                        foreach ($drivers as $driver) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($driver['DriverID']) . '</td>';
                            echo '<td>' . htmlspecialchars($driver['FirstName']) . '</td>';
                            echo '<td>' . htmlspecialchars($driver['LastName']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }

                } else {
                    echo '<div class="error">❌ Tabela "Drivers" não encontrada no banco de dados!</div>';
                    echo '<div class="info">💡 Verifique se o nome da tabela está correto. Pode ser case-sensitive.</div>';

                    // Listar tabelas disponíveis
                    echo "<h3>Tabelas Disponíveis:</h3>";
                    $stmt = $conn->query("SHOW TABLES");
                    $tables = $stmt->fetchAll();

                    if (count($tables) > 0) {
                        echo '<ul>';
                        foreach ($tables as $table) {
                            $tableName = array_values($table)[0];
                            echo '<li>' . $tableName . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>Nenhuma tabela encontrada no banco de dados.</p>';
                    }
                }

            } catch (PDOException $e) {
                echo '<div class="error">❌ Erro ao verificar tabela: ' . $e->getMessage() . '</div>';
            }

        } else {
            echo '<div class="error">❌ Falha na conexão com o banco de dados!</div>';
            echo '<div class="info">
                <strong>Verifique:</strong><br>
                1. Se as credenciais em db-config.php estão corretas<br>
                2. Se o banco de dados existe<br>
                3. Se o usuário tem permissões<br>
                4. Se o servidor MySQL está rodando
            </div>';
        }
        ?>

        <h2>7. Próximos Passos</h2>
        <div class="info">
            <strong>Se todos os testes passaram:</strong><br>
            1. Edite o arquivo <code>db-config.php</code> com suas credenciais reais<br>
            2. Faça upload dos arquivos para o cPanel<br>
            3. Teste o endpoint: <code>get-drivers.php</code><br>
            4. A página de motoristas vai carregar os dados automaticamente
        </div>

        <div class="code">
            <strong>Exemplo de credenciais no cPanel:</strong><br><br>
            define('DB_HOST', 'localhost');<br>
            define('DB_NAME', 'cpanel_usuario_nomedobanco');<br>
            define('DB_USER', 'cpanel_usuario');<br>
            define('DB_PASS', 'sua_senha_aqui');
        </div>
    </div>
</body>
</html>
