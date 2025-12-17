<?php
/**
 * Script para criar tabelas FF_Locations, FF_Blocks e FF_BlockLocations
 */

header('Content-Type: text/html; charset=utf-8');

// Configuração do banco de dados
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

echo "<h1>Criação de Tabelas para Sistema de Blocos Geográficos</h1>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);

    echo "✓ Conectado ao banco de dados\n\n";

    // Criar tabela FF_Locations
    echo "Criando tabela FF_Locations...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS FF_Locations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            address TEXT NOT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            category VARCHAR(100) NULL COMMENT 'Camada/Categoria do local',
            import_batch VARCHAR(50) NULL COMMENT 'Identificador do lote de importação',
            block_id INT NULL COMMENT 'ID do bloco ao qual pertence',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_block (block_id),
            INDEX idx_category (category),
            INDEX idx_import_batch (import_batch),
            INDEX idx_coordinates (latitude, longitude)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Armazena locais importados de planilhas'
    ");
    echo "✓ Tabela FF_Locations criada\n\n";

    // Criar tabela FF_Blocks
    echo "Criando tabela FF_Blocks...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS FF_Blocks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            center_latitude DECIMAL(10, 8) NOT NULL COMMENT 'Centro geográfico do bloco',
            center_longitude DECIMAL(11, 8) NOT NULL,
            radius_km DECIMAL(5, 2) DEFAULT 5.00 COMMENT 'Raio do bloco em km',
            locations_count INT DEFAULT 0,
            import_batch VARCHAR(50) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_import_batch (import_batch),
            INDEX idx_center (center_latitude, center_longitude)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Blocos geográficos agrupando locais próximos'
    ");
    echo "✓ Tabela FF_Blocks criada\n\n";

    // Criar tabela FF_BlockLocations
    echo "Criando tabela FF_BlockLocations...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS FF_BlockLocations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            block_id INT NOT NULL,
            location_id INT NOT NULL,
            order_in_block INT DEFAULT 0 COMMENT 'Ordem de visita dentro do bloco',
            distance_to_center_km DECIMAL(5, 2) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

            UNIQUE KEY unique_block_location (block_id, location_id),
            INDEX idx_block (block_id),
            INDEX idx_location (location_id),
            INDEX idx_order (order_in_block),

            FOREIGN KEY (block_id) REFERENCES FF_Blocks(id) ON DELETE CASCADE,
            FOREIGN KEY (location_id) REFERENCES FF_Locations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Relacionamento entre blocos e locais'
    ");
    echo "✓ Tabela FF_BlockLocations criada\n\n";

    // Verificar tabelas criadas
    echo "Verificando tabelas criadas...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'FF_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    echo "\n✓ SUCESSO! Todas as tabelas foram criadas.\n";

} catch (PDOException $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";
