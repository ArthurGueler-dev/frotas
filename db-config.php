<?php
/**
 * Configuração de Conexão com Banco de Dados
 *
 * IMPORTANTE: Altere essas informações com os dados do seu cPanel/phpMyAdmin
 */

// Configurações do Banco de Dados
define('DB_HOST', '187.49.226.10');
define('DB_PORT', '3306');
define('DB_NAME', 'f137049_in9aut');
define('DB_USER', 'f137049_tool');
define('DB_PASS', 'In9@1234qwer');
define('DB_CHARSET', 'utf8mb4');

/**
 * Cria conexão com o banco de dados usando PDO
 *
 * @return PDO|null Retorna objeto PDO ou null em caso de erro
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        return $pdo;

    } catch (PDOException $e) {
        // Log do erro (não mostrar detalhes ao usuário em produção)
        error_log("Erro na conexão com banco de dados: " . $e->getMessage());

        // Em desenvolvimento, você pode descomentar a linha abaixo para ver o erro
        // echo "Erro de conexão: " . $e->getMessage();

        return null;
    }
}

/**
 * Testa a conexão com o banco de dados
 *
 * @return bool True se conectou com sucesso, False caso contrário
 */
function testConnection() {
    $conn = getDBConnection();

    if ($conn === null) {
        return false;
    }

    try {
        // Tenta fazer uma query simples
        $stmt = $conn->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao testar conexão: " . $e->getMessage());
        return false;
    }
}
?>
