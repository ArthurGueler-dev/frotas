<?php
/**
 * Teste simples da API de Telemetria
 * Upload este arquivo para testar se o PHP está funcionando
 */

header('Content-Type: application/json; charset=utf-8');

// Teste 1: PHP funcionando
$tests = [
    'php_version' => phpversion(),
    'mysqli_available' => extension_loaded('mysqli'),
    'json_available' => function_exists('json_encode'),
];

// Teste 2: Conexão com banco
$db_config = [
    'host' => '187.49.226.10',
    'port' => 3306,
    'database' => 'f137049_in9aut',
    'username' => 'f137049_tool',
    'password' => 'In9@1234qwer'
];

$tests['db_connection'] = false;
$tests['db_error'] = null;

try {
    $conn = new mysqli(
        $db_config['host'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database'],
        $db_config['port']
    );

    if ($conn->connect_error) {
        $tests['db_error'] = $conn->connect_error;
    } else {
        $tests['db_connection'] = true;

        // Teste 3: Verificar se tabela existe
        $result = $conn->query("SHOW TABLES LIKE 'Telemetria_Diaria'");
        $tests['table_exists'] = ($result && $result->num_rows > 0);

        // Teste 4: Contar registros
        if ($tests['table_exists']) {
            $result = $conn->query("SELECT COUNT(*) as total FROM Telemetria_Diaria");
            $row = $result->fetch_assoc();
            $tests['total_records'] = $row['total'];
        }

        $conn->close();
    }
} catch (Exception $e) {
    $tests['db_error'] = $e->getMessage();
}

// Resultado
echo json_encode([
    'success' => true,
    'message' => 'Teste de API de Telemetria',
    'tests' => $tests,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
?>
