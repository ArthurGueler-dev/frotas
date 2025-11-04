const mysql = require('mysql2/promise');

async function setupDatabase() {
    let connection;

    try {
        connection = await mysql.createConnection({
            host: '187.49.226.10',
            port: 3306,
            user: 'f137049_tool',
            password: 'In9@1234qwer',
            database: 'f137049_in9aut'
        });

        console.log('✅ Conectado ao banco de dados!\n');

        // 1. Tabela de Manutenções
        console.log('1. Criando tabela FF_Maintenances...');
        try {
            await connection.query(`
                CREATE TABLE IF NOT EXISTS FF_Maintenances (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    vehicle_id INT NOT NULL,
                    license_plate VARCHAR(20) NOT NULL,
                    maintenance_type VARCHAR(100) NOT NULL,
                    description TEXT,
                    priority ENUM('Baixa', 'Média', 'Alta', 'Urgente') DEFAULT 'Média',
                    status ENUM('Pendente', 'Em Progresso', 'Concluída', 'Cancelada') DEFAULT 'Pendente',
                    responsible VARCHAR(100),
                    scheduled_date DATETIME,
                    started_date DATETIME,
                    completed_date DATETIME,
                    cost DECIMAL(10, 2),
                    mileage INT,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_by VARCHAR(100),

                    INDEX idx_vehicle_id (vehicle_id),
                    INDEX idx_license_plate (license_plate),
                    INDEX idx_status (status),
                    INDEX idx_priority (priority)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            `);
            console.log('   ✅ FF_Maintenances criada!');
        } catch (err) {
            console.log('   ⚠️  FF_Maintenances já existe ou erro:', err.message);
        }

        // 2. Tabela de Ordens de Serviço
        console.log('\n2. Criando tabela FF_WorkOrders...');
        try {
            await connection.query(`
                CREATE TABLE IF NOT EXISTS FF_WorkOrders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_number VARCHAR(20) UNIQUE NOT NULL,
                    vehicle_id INT NOT NULL,
                    license_plate VARCHAR(20) NOT NULL,
                    driver_id INT,
                    service_type VARCHAR(100) NOT NULL,
                    description TEXT NOT NULL,
                    priority ENUM('Baixa', 'Média', 'Alta', 'Urgente') DEFAULT 'Média',
                    status ENUM('Aberta', 'Em Andamento', 'Aguardando Peças', 'Concluída', 'Cancelada') DEFAULT 'Aberta',
                    responsible VARCHAR(100),
                    opened_date DATETIME NOT NULL,
                    scheduled_date DATETIME,
                    closed_date DATETIME,
                    estimated_cost DECIMAL(10, 2),
                    final_cost DECIMAL(10, 2),
                    parts_cost DECIMAL(10, 2),
                    labor_cost DECIMAL(10, 2),
                    workshop VARCHAR(200),
                    invoice_number VARCHAR(50),
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_by VARCHAR(100),

                    INDEX idx_order_number (order_number),
                    INDEX idx_vehicle_id (vehicle_id),
                    INDEX idx_license_plate (license_plate),
                    INDEX idx_driver_id (driver_id),
                    INDEX idx_status (status),
                    INDEX idx_priority (priority),
                    INDEX idx_opened_date (opened_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            `);
            console.log('   ✅ FF_WorkOrders criada!');
        } catch (err) {
            console.log('   ⚠️  FF_WorkOrders já existe ou erro:', err.message);
        }

        // 3. Tabela de Itens das OS
        console.log('\n3. Criando tabela FF_WorkOrderItems...');
        try {
            await connection.query(`
                CREATE TABLE IF NOT EXISTS FF_WorkOrderItems (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    work_order_id INT NOT NULL,
                    item_type ENUM('Peça', 'Serviço', 'Outro') DEFAULT 'Peça',
                    description VARCHAR(255) NOT NULL,
                    quantity INT DEFAULT 1,
                    unit_price DECIMAL(10, 2),
                    total_price DECIMAL(10, 2),
                    supplier VARCHAR(200),
                    part_number VARCHAR(100),
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_work_order_id (work_order_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            `);
            console.log('   ✅ FF_WorkOrderItems criada!');
        } catch (err) {
            console.log('   ⚠️  FF_WorkOrderItems já existe ou erro:', err.message);
        }

        // 4. Tabela de Histórico de Manutenções
        console.log('\n4. Criando tabela FF_MaintenanceHistory...');
        try {
            await connection.query(`
                CREATE TABLE IF NOT EXISTS FF_MaintenanceHistory (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    vehicle_id INT NOT NULL,
                    license_plate VARCHAR(20) NOT NULL,
                    maintenance_date DATETIME NOT NULL,
                    maintenance_type VARCHAR(100) NOT NULL,
                    description TEXT,
                    cost DECIMAL(10, 2),
                    mileage INT,
                    workshop VARCHAR(200),
                    responsible VARCHAR(100),
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_by VARCHAR(100),

                    INDEX idx_vehicle_id (vehicle_id),
                    INDEX idx_license_plate (license_plate),
                    INDEX idx_maintenance_date (maintenance_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            `);
            console.log('   ✅ FF_MaintenanceHistory criada!');
        } catch (err) {
            console.log('   ⚠️  FF_MaintenanceHistory já existe ou erro:', err.message);
        }

        // 5. Tabela de Alertas
        console.log('\n5. Criando tabela FF_Alerts...');
        try {
            await connection.query(`
                CREATE TABLE IF NOT EXISTS FF_Alerts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    alert_type ENUM('Manutenção', 'Documentação', 'CNH', 'Inspeção', 'Outro') NOT NULL,
                    title VARCHAR(200) NOT NULL,
                    description TEXT,
                    severity ENUM('Info', 'Aviso', 'Urgente', 'Crítico') DEFAULT 'Aviso',
                    vehicle_id INT,
                    driver_id INT,
                    due_date DATETIME,
                    is_resolved BOOLEAN DEFAULT FALSE,
                    resolved_date DATETIME,
                    resolved_by VARCHAR(100),
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    INDEX idx_alert_type (alert_type),
                    INDEX idx_severity (severity),
                    INDEX idx_is_resolved (is_resolved),
                    INDEX idx_vehicle_id (vehicle_id),
                    INDEX idx_driver_id (driver_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            `);
            console.log('   ✅ FF_Alerts criada!');
        } catch (err) {
            console.log('   ⚠️  FF_Alerts já existe ou erro:', err.message);
        }

        // Verificar tabelas criadas
        console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('📊 Tabelas FleetFlow no banco:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        const [tables] = await connection.query("SHOW TABLES LIKE 'FF_%'");
        tables.forEach((table, index) => {
            console.log(`  ${index + 1}. ✓ ${Object.values(table)[0]}`);
        });

        console.log('\n✅ Banco de dados configurado com sucesso!');

    } catch (error) {
        console.error('❌ Erro:', error.message);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

setupDatabase();
