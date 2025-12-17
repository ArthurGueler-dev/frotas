<?php
/**
 * Script de teste de conexão com banco de dados
 * Mostra informações detalhadas para diagnóstico
 */

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

require_once 'db-config.php';

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'pdo_available' => extension_loaded('pdo'),
    'pdo_mysql_available' => extension_loaded('pdo_mysql'),
    'connection_test' => null,
    'error' => null
];

// Testar extensões PHP necessárias
if (!extension_loaded('pdo')) {
    $result['error'] = 'Extensão PDO não está disponível';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

if (!extension_loaded('pdo_mysql')) {
    $result['error'] = 'Extensão PDO MySQL não está disponível';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Testar conexão básica
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $result['connection_details'] = [
        'host' => DB_HOST,
        'port' => DB_PORT,
        'database' => DB_NAME,
        'user' => DB_USER,
        'charset' => DB_CHARSET,
        'dsn' => $dsn
    ];

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5
        ]
    );

    // Testar query simples
    $stmt = $pdo->query("SELECT 1 as test, NOW() as now");
    $row = $stmt->fetch();

    $result['connection_test'] = 'success';
    $result['query_result'] = $row;

    // Verificar tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $result['tables'] = $tables;

    // Verificar estrutura das tabelas necessárias
    if (in_array('ordemservico', $tables)) {
        $stmt = $pdo->query("DESCRIBE ordemservico");
        $result['ordemservico_structure'] = $stmt->fetchAll();
    }

    if (in_array('ordemservico_itens', $tables)) {
        $stmt = $pdo->query("DESCRIBE ordemservico_itens");
        $result['ordemservico_itens_structure'] = $stmt->fetchAll();
    }

    // Contar registros
    if (in_array('ordemservico', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ordemservico");
        $result['ordemservico_count'] = $stmt->fetch()['count'];
    }

} catch (PDOException $e) {
    $result['connection_test'] = 'failed';
    $result['error'] = $e->getMessage();
    $result['error_code'] = $e->getCode();

    // Tentar diagnosticar problemas comuns
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        $result['diagnosis'] = 'Credenciais incorretas (usuário ou senha)';
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        $result['diagnosis'] = 'Banco de dados não existe';
    } elseif (strpos($e->getMessage(), "Can't connect") !== false || strpos($e->getMessage(), 'Connection refused') !== false) {
        $result['diagnosis'] = 'Não foi possível conectar ao servidor MySQL (host/porta incorretos ou servidor offline)';
    } elseif (strpos($e->getMessage(), 'timeout') !== false) {
        $result['diagnosis'] = 'Timeout na conexão (firewall ou rede lenta)';
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
