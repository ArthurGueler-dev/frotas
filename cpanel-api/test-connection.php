<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE DE CONEXÃO MYSQL ===\n\n";

// Teste 1: Verificar se config-db.php existe
if (file_exists('config-db.php')) {
    echo "✓ config-db.php existe\n";
    require_once 'config-db.php';
    echo "✓ config-db.php carregado\n\n";
} else {
    echo "✗ config-db.php NÃO EXISTE\n";
    die();
}

// Teste 2: Mostrar configurações (sem senha)
echo "Configurações:\n";
echo "  Host: " . (defined('DB_HOST') ? DB_HOST : $host) . "\n";
echo "  User: " . (defined('DB_USER') ? DB_USER : $user) . "\n";
echo "  Database: " . (defined('DB_NAME') ? DB_NAME : $database) . "\n";
echo "  Port: " . (isset($port) ? $port : '3306') . "\n\n";

// Teste 3: Verificar extensões PHP
echo "Extensões PHP:\n";
echo "  PDO: " . (extension_loaded('pdo') ? '✓ Instalado' : '✗ NÃO instalado') . "\n";
echo "  PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✓ Instalado' : '✗ NÃO instalado') . "\n";
echo "  Versão PHP: " . phpversion() . "\n\n";

// Teste 4: Tentar conexão
if (!extension_loaded('pdo_mysql')) {
    echo "✗ PDO MySQL não está disponível!\n";
    die();
}

echo "Tentando conectar...\n";
try {
    $dsn = "mysql:host=" . (defined('DB_HOST') ? DB_HOST : $host) .
           ";port=" . (isset($port) ? $port : 3306) .
           ";dbname=" . (defined('DB_NAME') ? DB_NAME : $database) .
           ";charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        defined('DB_USER') ? DB_USER : $user,
        defined('DB_PASS') ? DB_PASS : $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        )
    );

    echo "✓ CONEXÃO ESTABELECIDA COM SUCESSO!\n\n";

    // Teste 5: Verificar se tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'FF_Users'");
    $table = $stmt->fetch();

    if ($table) {
        echo "✓ Tabela FF_Users existe\n";

        // Contar usuários
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM FF_Users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Total de usuários: " . $result['count'] . "\n";
    } else {
        echo "✗ Tabela FF_Users NÃO existe\n";
        echo "  Execute create-table-users.php primeiro!\n";
    }

} catch (PDOException $e) {
    echo "✗ ERRO DE CONEXÃO:\n";
    echo "  Mensagem: " . $e->getMessage() . "\n";
    echo "  Código: " . $e->getCode() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
