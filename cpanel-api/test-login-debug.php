<?php
/**
 * Arquivo de DEBUG para testar o login
 * Acesse: https://floripa.in9automacao.com.br/test-login-debug.php
 */

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'step' => 1,
    'message' => 'PHP está funcionando',
    'php_version' => phpversion()
]);

echo "\n\n";

// Testar carregamento do config
try {
    require_once 'config-db.php';
    echo json_encode([
        'step' => 2,
        'message' => 'config-db.php carregado com sucesso',
        'has_constants' => defined('DB_HOST'),
        'has_variables' => isset($host)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'step' => 2,
        'error' => 'Erro ao carregar config-db.php: ' . $e->getMessage()
    ]);
    exit;
}

echo "\n\n";

// Testar conexão com banco
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);

    echo json_encode([
        'step' => 3,
        'message' => 'Conexão com banco OK'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'step' => 3,
        'error' => 'Erro de conexão: ' . $e->getMessage()
    ]);
    exit;
}

echo "\n\n";

// Testar se tabela FF_Users existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'FF_Users'");
    $table = $stmt->fetch();

    echo json_encode([
        'step' => 4,
        'message' => 'Verificação de tabela',
        'table_exists' => !empty($table)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'step' => 4,
        'error' => 'Erro ao verificar tabela: ' . $e->getMessage()
    ]);
}

echo "\n\n";

// Testar estrutura da tabela
try {
    $stmt = $pdo->query("DESCRIBE FF_Users");
    $columns = $stmt->fetchAll();

    echo json_encode([
        'step' => 5,
        'message' => 'Estrutura da tabela',
        'columns' => array_column($columns, 'Field')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'step' => 5,
        'error' => 'Erro ao verificar estrutura: ' . $e->getMessage()
    ]);
}

echo "\n\n";

echo json_encode([
    'step' => 'FINAL',
    'message' => 'Todos os testes passaram! O problema pode estar na lógica do login.'
]);
?>
