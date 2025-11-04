const mysql = require('mysql2/promise');

async function insertTestData() {
    let connection;

    try {
        connection = await mysql.createConnection({
            host: '187.49.226.10',
            port: 3306,
            user: 'f137049_tool',
            password: 'In9@1234qwer',
            database: 'f137049_in9aut'
        });

        console.log('✅ Conectado ao banco!\n');

        // Buscar alguns veículos reais para usar nos dados de teste
        console.log('📋 Buscando veículos do banco...');
        const [vehicles] = await connection.query('SELECT Id, LicensePlate, VehicleName FROM Vehicles LIMIT 5');
        console.log(`   Encontrados ${vehicles.length} veículos\n`);

        if (vehicles.length === 0) {
            console.log('⚠️  Nenhum veículo encontrado. Não é possível inserir dados de teste.');
            return;
        }

        // Inserir Manutenções
        console.log('1️⃣  Inserindo manutenções de teste...');
        const maintenances = [
            {
                vehicle_id: vehicles[0].Id,
                license_plate: vehicles[0].LicensePlate,
                maintenance_type: 'Troca de óleo e filtros',
                description: 'Troca de óleo do motor, filtro de óleo, filtro de ar e filtro de combustível',
                priority: 'Alta',
                status: 'Pendente',
                responsible: 'Oficina Central',
                scheduled_date: '2025-11-05 08:00:00',
                cost: 450.00,
                mileage: 45000
            },
            {
                vehicle_id: vehicles[1].Id,
                license_plate: vehicles[1].LicensePlate,
                maintenance_type: 'Revisão de freios',
                description: 'Verificação e troca de pastilhas de freio dianteiras',
                priority: 'Média',
                status: 'Em Progresso',
                responsible: 'Mecânico João',
                scheduled_date: '2025-11-02 14:00:00',
                started_date: '2025-11-02 14:30:00',
                cost: 680.00,
                mileage: 52000
            },
            {
                vehicle_id: vehicles[2].Id,
                license_plate: vehicles[2].LicensePlate,
                maintenance_type: 'Alinhamento e balanceamento',
                description: 'Alinhamento de direção e balanceamento dos 4 pneus',
                priority: 'Baixa',
                status: 'Concluída',
                responsible: 'Auto Center',
                scheduled_date: '2025-10-20 09:00:00',
                started_date: '2025-10-20 09:15:00',
                completed_date: '2025-10-20 11:30:00',
                cost: 280.00,
                mileage: 38000
            }
        ];

        for (const m of maintenances) {
            await connection.query(`
                INSERT INTO FF_Maintenances
                (vehicle_id, license_plate, maintenance_type, description, priority, status,
                 responsible, scheduled_date, started_date, completed_date, cost, mileage, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin')
            `, [m.vehicle_id, m.license_plate, m.maintenance_type, m.description, m.priority,
                m.status, m.responsible, m.scheduled_date, m.started_date || null,
                m.completed_date || null, m.cost, m.mileage]);
        }
        console.log(`   ✅ ${maintenances.length} manutenções inseridas!\n`);

        // Inserir Ordens de Serviço
        console.log('2️⃣  Inserindo ordens de serviço...');
        const workOrders = [
            {
                order_number: 'OS-2025-001',
                vehicle_id: vehicles[0].Id,
                license_plate: vehicles[0].LicensePlate,
                service_type: 'Manutenção Preventiva',
                description: 'Revisão completa dos 50.000 km',
                priority: 'Alta',
                status: 'Aberta',
                responsible: 'Carlos Silva',
                opened_date: '2025-10-25 10:00:00',
                scheduled_date: '2025-11-05 08:00:00',
                estimated_cost: 1200.00,
                workshop: 'Oficina Central Ltda'
            },
            {
                order_number: 'OS-2025-002',
                vehicle_id: vehicles[1].Id,
                license_plate: vehicles[1].LicensePlate,
                service_type: 'Manutenção Corretiva',
                description: 'Reparo no sistema de freios - ruído ao frear',
                priority: 'Urgente',
                status: 'Em Andamento',
                responsible: 'João Mecânico',
                opened_date: '2025-10-26 14:00:00',
                scheduled_date: '2025-10-27 08:00:00',
                estimated_cost: 850.00,
                parts_cost: 450.00,
                labor_cost: 400.00,
                workshop: 'Freios & Suspensões'
            },
            {
                order_number: 'OS-2025-003',
                vehicle_id: vehicles[2].Id,
                license_plate: vehicles[2].LicensePlate,
                service_type: 'Inspeção Veicular',
                description: 'Inspeção anual obrigatória',
                priority: 'Média',
                status: 'Concluída',
                responsible: 'Maria Santos',
                opened_date: '2025-10-15 09:00:00',
                scheduled_date: '2025-10-20 10:00:00',
                closed_date: '2025-10-20 12:00:00',
                estimated_cost: 300.00,
                final_cost: 280.00,
                labor_cost: 280.00,
                workshop: 'Auto Center Express',
                invoice_number: 'NF-45678'
            }
        ];

        for (const wo of workOrders) {
            await connection.query(`
                INSERT INTO FF_WorkOrders
                (order_number, vehicle_id, license_plate, service_type, description, priority,
                 status, responsible, opened_date, scheduled_date, closed_date, estimated_cost,
                 final_cost, parts_cost, labor_cost, workshop, invoice_number, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin')
            `, [wo.order_number, wo.vehicle_id, wo.license_plate, wo.service_type, wo.description,
                wo.priority, wo.status, wo.responsible, wo.opened_date, wo.scheduled_date,
                wo.closed_date || null, wo.estimated_cost, wo.final_cost || null,
                wo.parts_cost || null, wo.labor_cost || null, wo.workshop, wo.invoice_number || null]);
        }
        console.log(`   ✅ ${workOrders.length} ordens de serviço inseridas!\n`);

        // Buscar ID da última OS para inserir itens
        const [lastWO] = await connection.query('SELECT id FROM FF_WorkOrders WHERE order_number = ?', ['OS-2025-002']);

        if (lastWO.length > 0) {
            console.log('3️⃣  Inserindo itens da OS-2025-002...');
            const items = [
                {
                    work_order_id: lastWO[0].id,
                    item_type: 'Peça',
                    description: 'Pastilhas de freio dianteiras',
                    quantity: 1,
                    unit_price: 280.00,
                    total_price: 280.00,
                    supplier: 'Autopeças Central',
                    part_number: 'PF-1234'
                },
                {
                    work_order_id: lastWO[0].id,
                    item_type: 'Peça',
                    description: 'Disco de freio',
                    quantity: 2,
                    unit_price: 85.00,
                    total_price: 170.00,
                    supplier: 'Autopeças Central',
                    part_number: 'DF-5678'
                },
                {
                    work_order_id: lastWO[0].id,
                    item_type: 'Serviço',
                    description: 'Mão de obra - troca de pastilhas e discos',
                    quantity: 1,
                    unit_price: 400.00,
                    total_price: 400.00
                }
            ];

            for (const item of items) {
                await connection.query(`
                    INSERT INTO FF_WorkOrderItems
                    (work_order_id, item_type, description, quantity, unit_price, total_price, supplier, part_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                `, [item.work_order_id, item.item_type, item.description, item.quantity,
                    item.unit_price, item.total_price, item.supplier || null, item.part_number || null]);
            }
            console.log(`   ✅ ${items.length} itens inseridos!\n`);
        }

        // Inserir Alertas
        console.log('4️⃣  Inserindo alertas...');
        const alerts = [
            {
                alert_type: 'Manutenção',
                title: 'Manutenção Preventiva Urgente',
                description: `Veículo ${vehicles[0].LicensePlate} - Revisão dos 50.000 km vencendo`,
                severity: 'Urgente',
                vehicle_id: vehicles[0].Id,
                due_date: '2025-11-05 23:59:59'
            },
            {
                alert_type: 'Documentação',
                title: 'Vencimento de Documentação',
                description: `Veículo ${vehicles[3] ? vehicles[3].LicensePlate : 'N/A'} - Licenciamento vencendo em 15 dias`,
                severity: 'Aviso',
                vehicle_id: vehicles[3] ? vehicles[3].Id : vehicles[0].Id,
                due_date: '2025-11-15 23:59:59'
            }
        ];

        for (const alert of alerts) {
            await connection.query(`
                INSERT INTO FF_Alerts
                (alert_type, title, description, severity, vehicle_id, due_date)
                VALUES (?, ?, ?, ?, ?, ?)
            `, [alert.alert_type, alert.title, alert.description, alert.severity,
                alert.vehicle_id, alert.due_date]);
        }
        console.log(`   ✅ ${alerts.length} alertas inseridos!\n`);

        // Resumo
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('📊 RESUMO DOS DADOS INSERIDOS:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        const [mainCount] = await connection.query('SELECT COUNT(*) as count FROM FF_Maintenances');
        const [woCount] = await connection.query('SELECT COUNT(*) as count FROM FF_WorkOrders');
        const [itemCount] = await connection.query('SELECT COUNT(*) as count FROM FF_WorkOrderItems');
        const [alertCount] = await connection.query('SELECT COUNT(*) as count FROM FF_Alerts');

        console.log(`  ✓ Manutenções: ${mainCount[0].count}`);
        console.log(`  ✓ Ordens de Serviço: ${woCount[0].count}`);
        console.log(`  ✓ Itens de OS: ${itemCount[0].count}`);
        console.log(`  ✓ Alertas: ${alertCount[0].count}`);
        console.log('\n✅ Dados de teste inseridos com sucesso!');

    } catch (error) {
        console.error('❌ Erro:', error.message);
        console.error(error);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

insertTestData();
