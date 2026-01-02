<?php
/**
 * Script de criação de tabelas para Sistema de Quilometragem Automática
 *
 * EXECUTAR UMA VEZ APENAS via phpMyAdmin ou linha de comando
 *
 * Este script cria:
 * 1. Tabela 'areas' - Regiões geográficas (Nova Venécia, Castelo, Vitória, etc)
 * 2. Tabela 'daily_mileage' - Registro de quilometragem diária por veículo
 * 3. Altera tabela 'Vehicles' - Adiciona campo area_id
 */

header('Content-Type: application/json; charset=utf-8');

// Conexão com banco
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conectado ao banco de dados\n\n";

    // ============================================================
    // 1. CRIAR TABELA 'areas'
    // ============================================================
    echo "📍 Criando tabela 'areas'...\n";

    $sql_areas = "
    CREATE TABLE IF NOT EXISTS areas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome da área (ex: Nova Venécia, Castelo)',
        state VARCHAR(2) NOT NULL COMMENT 'Estado (ES, RJ, etc)',
        description TEXT NULL COMMENT 'Descrição adicional da área',
        is_active BOOLEAN DEFAULT TRUE COMMENT 'Área ativa no sistema',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_name (name),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Áreas geográficas para organização regional de veículos';
    ";

    $pdo->exec($sql_areas);
    echo "   ✅ Tabela 'areas' criada\n\n";

    // Inserir áreas padrão
    echo "📍 Inserindo áreas padrão...\n";
    $areas_padrao = [
        ['Nova Venécia', 'ES', 'Região Norte do Espírito Santo'],
        ['Castelo', 'ES', 'Região Serrana do Espírito Santo'],
        ['Vitória', 'ES', 'Capital e Região Metropolitana'],
        ['Vila Velha', 'ES', 'Região Metropolitana da Grande Vitória'],
        ['Serra', 'ES', 'Região Metropolitana da Grande Vitória'],
        ['Cariacica', 'ES', 'Região Metropolitana da Grande Vitória'],
        ['Outras', 'ES', 'Demais regiões do Espírito Santo']
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO areas (name, state, description)
        VALUES (?, ?, ?)
    ");

    foreach ($areas_padrao as $area) {
        $stmt->execute($area);
    }
    echo "   ✅ " . count($areas_padrao) . " áreas inseridas\n\n";

    // ============================================================
    // 2. CRIAR TABELA 'daily_mileage'
    // ============================================================
    echo "📊 Criando tabela 'daily_mileage'...\n";

    $sql_mileage = "
    CREATE TABLE IF NOT EXISTS daily_mileage (
        id INT AUTO_INCREMENT PRIMARY KEY,

        -- Identificação
        vehicle_plate VARCHAR(20) NOT NULL COMMENT 'Placa do veículo (FK para Vehicles)',
        date DATE NOT NULL COMMENT 'Data do registro (YYYY-MM-DD)',
        area_id INT NULL COMMENT 'Área do veículo nesta data',

        -- Dados de Quilometragem
        odometer_start DECIMAL(10,2) NULL COMMENT 'Odômetro no início do dia (KM)',
        odometer_end DECIMAL(10,2) NULL COMMENT 'Odômetro no fim do dia (KM)',
        km_driven DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'KM rodados no dia (odometer_end - odometer_start)',

        -- Origem e Status
        source ENUM('API', 'Manual') DEFAULT 'API' COMMENT 'Origem dos dados',
        sync_status ENUM('success', 'failed', 'pending', 'manual') DEFAULT 'pending' COMMENT 'Status da sincronização',

        -- Metadados
        error_message TEXT NULL COMMENT 'Mensagem de erro se sync_status = failed',
        synced_at DATETIME NULL COMMENT 'Quando foi sincronizado com a API',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        -- Constraints
        UNIQUE KEY unique_vehicle_date (vehicle_plate, date),

        -- Indexes para performance
        INDEX idx_date (date),
        INDEX idx_area (area_id),
        INDEX idx_plate (vehicle_plate),
        INDEX idx_status (sync_status),
        INDEX idx_source (source),
        INDEX idx_area_date (area_id, date),

        -- Foreign Keys
        FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Registro diário de quilometragem por veículo';
    ";

    $pdo->exec($sql_mileage);
    echo "   ✅ Tabela 'daily_mileage' criada\n\n";

    // ============================================================
    // 3. ALTERAR TABELA 'Vehicles' - Adicionar area_id
    // ============================================================
    echo "🚗 Alterando tabela 'Vehicles'...\n";

    // Verificar se coluna já existe
    $check_column = $pdo->query("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '$dbname'
        AND TABLE_NAME = 'Vehicles'
        AND COLUMN_NAME = 'area_id'
    ")->fetch(PDO::FETCH_ASSOC);

    if ($check_column['count'] == 0) {
        $sql_alter = "
        ALTER TABLE Vehicles
        ADD COLUMN area_id INT NULL COMMENT 'Área à qual o veículo pertence' AFTER Id,
        ADD INDEX idx_area (area_id),
        ADD FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
        ";

        $pdo->exec($sql_alter);
        echo "   ✅ Coluna 'area_id' adicionada à tabela Vehicles\n";
    } else {
        echo "   ⚠️  Coluna 'area_id' já existe na tabela Vehicles\n";
    }

    echo "\n";

    // ============================================================
    // RESUMO FINAL
    // ============================================================
    echo "=" . str_repeat("=", 60) . "\n";
    echo "✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "=" . str_repeat("=", 60) . "\n\n";

    echo "📋 Estruturas criadas:\n";
    echo "   1. Tabela 'areas' - " . count($areas_padrao) . " áreas cadastradas\n";
    echo "   2. Tabela 'daily_mileage' - Pronta para receber dados\n";
    echo "   3. Tabela 'Vehicles' - Campo area_id adicionado\n\n";

    echo "🎯 Próximos passos:\n";
    echo "   1. Associar veículos às áreas (UPDATE Vehicles SET area_id = X)\n";
    echo "   2. Configurar Celery task para sincronização automática\n";
    echo "   3. Testar sync manual com um veículo\n\n";

    // Mostrar exemplo de query
    echo "📝 Exemplo de consulta:\n";
    echo "   SELECT v.LicensePlate, a.name as area, dm.date, dm.km_driven\n";
    echo "   FROM daily_mileage dm\n";
    echo "   JOIN Vehicles v ON dm.vehicle_plate = v.LicensePlate\n";
    echo "   LEFT JOIN areas a ON dm.area_id = a.id\n";
    echo "   WHERE dm.date >= '2025-12-01'\n";
    echo "   ORDER BY dm.date DESC;\n\n";

    echo json_encode([
        'success' => true,
        'message' => 'Migração concluída com sucesso',
        'tables_created' => ['areas', 'daily_mileage'],
        'tables_altered' => ['Vehicles'],
        'areas_inserted' => count($areas_padrao)
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit(1);
}
?>
