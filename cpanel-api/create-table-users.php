<?php
/**
 * Script para criar tabela FF_Users - Sistema de Login
 * Tabela simplificada apenas para autenticação e cadastro de usuários
 */

header('Content-Type: text/html; charset=utf-8');

// Configuração do banco de dados
require_once 'config-db.php';

echo "<h1>Criação da Tabela FF_Users</h1>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);

    echo "✓ Conectado ao banco de dados\n\n";

    // Criar tabela FF_Users
    echo "Criando tabela FF_Users...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS FF_Users (
            id INT PRIMARY KEY AUTO_INCREMENT,

            -- Autenticação
            username VARCHAR(100) NOT NULL UNIQUE COMMENT 'Login do usuário',
            password_hash VARCHAR(255) NULL COMMENT 'Hash da senha (bcrypt) - NULL = precisa definir',

            -- Dados Básicos
            full_name VARCHAR(255) NOT NULL COMMENT 'Nome completo',
            email VARCHAR(255) NULL COMMENT 'Email (opcional)',

            -- Controle de Acesso
            user_type ENUM('admin', 'usuario') DEFAULT 'usuario' COMMENT 'Tipo: admin ou usuario',
            status ENUM('ativo', 'pendente', 'inativo') DEFAULT 'pendente' COMMENT 'Status do usuário',

            -- Informações de Login
            last_login_at DATETIME NULL COMMENT 'Último acesso',
            password_changed_at DATETIME NULL COMMENT 'Última troca de senha',

            -- Timestamps
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            -- Índices
            INDEX idx_username (username),
            INDEX idx_user_type (user_type),
            INDEX idx_status (status)

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Usuários do sistema FleetFlow'
    ");
    echo "✓ Tabela FF_Users criada\n\n";

    // Inserir usuário admin padrão
    echo "Criando usuário admin padrão...\n";

    // Verificar se já existe admin
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM FF_Users WHERE username = 'admin'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        // Hash da senha 'admin123' com bcrypt (custo 10)
        $defaultPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);

        $pdo->exec("
            INSERT INTO FF_Users (username, password_hash, full_name, email, user_type, status, password_changed_at)
            VALUES (
                'admin',
                '$defaultPasswordHash',
                'Administrador do Sistema',
                'admin@in9automacao.com.br',
                'admin',
                'ativo',
                NOW()
            )
        ");
        echo "✓ Usuário admin criado\n";
        echo "   Login: admin\n";
        echo "   Senha: admin123\n";
        echo "   ⚠️  IMPORTANTE: Alterar a senha após primeiro login!\n\n";
    } else {
        echo "ℹ️  Usuário admin já existe\n\n";
    }

    // Mostrar estrutura da tabela
    echo "Estrutura da tabela FF_Users:\n";
    $stmt = $pdo->query("DESCRIBE FF_Users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        $comment = isset($column['Comment']) ? $column['Comment'] : '';
        echo sprintf("  %-20s %-30s %s\n",
            $column['Field'],
            $column['Type'],
            $comment
        );
    }

    echo "\n✓ SUCESSO! Tabela de usuários criada com sucesso.\n";

} catch (PDOException $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";
