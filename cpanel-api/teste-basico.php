<?php
/**
 * Teste Básico - Debugging
 * Upload para: /home/f137049/public_html/api/teste-basico.php
 */

// HABILITA exibição de TODOS os erros para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== TESTE BÁSICO PHP ===\n\n";

// Teste 1: PHP funcionando
echo "1. PHP Version: " . phpversion() . "\n";
echo "   Status: OK\n\n";

// Teste 2: Extensões necessárias
echo "2. Extensões:\n";
echo "   - mysqli: " . (extension_loaded('mysqli') ? 'OK' : 'NÃO INSTALADO') . "\n";
echo "   - simplexml: " . (extension_loaded('simplexml') ? 'OK' : 'NÃO INSTALADO') . "\n";
echo "   - openssl: " . (extension_loaded('openssl') ? 'OK' : 'NÃO INSTALADO') . "\n\n";

// Teste 3: Conexão MySQL
echo "3. Conexão MySQL:\n";
try {
    $mysqli = @new mysqli('187.49.226.10', 'f137049_tool', 'In9@1234qwer', 'f137049_in9aut', 3306);

    if ($mysqli->connect_error) {
        echo "   ERRO: " . $mysqli->connect_error . "\n\n";
    } else {
        echo "   Status: CONECTADO\n";

        // Teste 3.1: Buscar veículos
        $result = @$mysqli->query("SELECT COUNT(*) as total FROM Vehicles");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "   Veículos na tabela: " . $row['total'] . "\n";
        }

        // Teste 3.2: Verificar tabela Telemetria_Diaria
        $result = @$mysqli->query("SHOW TABLES LIKE 'Telemetria_Diaria'");
        if ($result && $result->num_rows > 0) {
            echo "   Tabela Telemetria_Diaria: EXISTE\n";
        } else {
            echo "   Tabela Telemetria_Diaria: NÃO EXISTE\n";
        }

        $mysqli->close();
        echo "\n";
    }
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n\n";
}

// Teste 4: Função file_get_contents
echo "4. Função file_get_contents:\n";
if (function_exists('file_get_contents')) {
    echo "   Status: DISPONÍVEL\n";

    // Teste 4.1: URL wrapper habilitado
    if (ini_get('allow_url_fopen')) {
        echo "   allow_url_fopen: HABILITADO\n";
    } else {
        echo "   allow_url_fopen: DESABILITADO (problema!)\n";
    }
} else {
    echo "   Status: NÃO DISPONÍVEL\n";
}
echo "\n";

// Teste 5: Teste HTTPS simples
echo "5. Teste HTTPS:\n";
try {
    $testUrl = "https://www.google.com";
    $context = stream_context_create([
        'http' => ['timeout' => 5],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $result = @file_get_contents($testUrl, false, $context);

    if ($result !== false) {
        echo "   Status: HTTPS FUNCIONA\n";
    } else {
        echo "   Status: ERRO AO ACESSAR HTTPS\n";
    }
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
}
echo "\n";

// Teste 6: Configurações PHP importantes
echo "6. Configurações PHP:\n";
echo "   max_execution_time: " . ini_get('max_execution_time') . "s\n";
echo "   memory_limit: " . ini_get('memory_limit') . "\n";
echo "   post_max_size: " . ini_get('post_max_size') . "\n";
echo "\n";

echo "=== FIM DOS TESTES ===\n";
?>
