<?php
/**
 * Configuração do Banco de Dados - cPanel
 * IMPORTANTE: Ajuste as credenciais conforme seu cPanel
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost'); // MySQL local no cPanel
define('DB_USER', 'f137049_tool'); // seu usuário do banco
define('DB_PASS', 'In9@1234qwer'); // sua senha do banco
define('DB_NAME', 'f137049_in9aut'); // nome do banco de dados

// Charset
define('DB_CHARSET', 'utf8mb4');

// Variáveis para compatibilidade com arquivos existentes
$host = DB_HOST;
$user = DB_USER;
$password = DB_PASS;
$database = DB_NAME;
$port = 3306;

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>
