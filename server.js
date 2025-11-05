const express = require('express');
const cors = require('cors');
const path = require('path');
const mysql = require('mysql2/promise');
const cron = require('node-cron');

const app = express();
const PORT = 5000;

// Configuração do banco de dados MySQL (servidor remoto)
const dbConfig = {
    host: '187.49.226.10',
    port: 3306,
    user: 'f137049_tool',
    password: 'In9@1234qwer',
    database: 'f137049_in9aut',
    charset: 'utf8mb4'
};

// Pool de conexões MySQL
const pool = mysql.createPool(dbConfig);

// Middleware
app.use(cors());
app.use(express.json());

// Banco de dados em memória
let database = {
    vehicles: [
        { id: 1, plate: 'ABC-1234', model: 'Volkswagen Gol', brand: 'Volkswagen', year: 2022, mileage: 50000, status: 'Ativo', color: 'Branco', fuel: 'Flex', type: 'Passeio', base: 'Serra', depreciation: 1200, rentalCost: 2500, trackerCost: 150 },
        { id: 2, plate: 'JKL-3456', model: 'Renault Kwid', brand: 'Renault', year: 2023, mileage: 25000, status: 'Ativo', color: 'Prata', fuel: 'Flex', type: 'Passeio', base: 'Vitória', depreciation: 1000, rentalCost: 2200, trackerCost: 150 },
        { id: 3, plate: 'MNO-7890', model: 'Honda Civic', brand: 'Honda', year: 2021, mileage: 70000, status: 'Ativo', color: 'Preto', fuel: 'Gasolina', type: 'Passeio', base: 'Vila Velha', depreciation: 1800, rentalCost: 3500, trackerCost: 150 },
        { id: 4, plate: 'DEF-5678', model: 'Fiat Strada', brand: 'Fiat', year: 2020, mileage: 85000, status: 'Manutenção', color: 'Vermelho', fuel: 'Flex', type: 'Utilitário', base: 'Serra', depreciation: 1500, rentalCost: 2800, trackerCost: 150 },
        { id: 5, plate: 'QWE-1122', model: 'Ford Ka', brand: 'Ford', year: 2022, mileage: 45000, status: 'Manutenção', color: 'Azul', fuel: 'Flex', type: 'Passeio', base: 'Cariacica', depreciation: 1100, rentalCost: 2300, trackerCost: 150 },
        { id: 6, plate: 'RST-3344', model: 'VW Saveiro', brand: 'Volkswagen', year: 2021, mileage: 60000, status: 'Manutenção', color: 'Branco', fuel: 'Flex', type: 'Utilitário', base: 'Serra', depreciation: 1400, rentalCost: 2700, trackerCost: 150 },
        { id: 7, plate: 'UVW-5566', model: 'Hyundai HB20', brand: 'Hyundai', year: 2023, mileage: 15000, status: 'Ativo', color: 'Cinza', fuel: 'Flex', type: 'Passeio', base: 'Vitória', depreciation: 900, rentalCost: 2400, trackerCost: 150 },
        { id: 8, plate: 'GHI-9012', model: 'Chevrolet Onix', brand: 'Chevrolet', year: 2019, mileage: 120000, status: 'Arquivado', color: 'Prata', fuel: 'Flex', type: 'Passeio', base: 'Linhares', depreciation: 1600, rentalCost: 2100, trackerCost: 150 },
        { id: 9, plate: 'ZXY-6789', model: 'Mercedes Sprinter', brand: 'Mercedes', year: 2018, mileage: 150000, status: 'Arquivado', color: 'Branco', fuel: 'Diesel', type: 'Van', base: 'Colatina', depreciation: 2500, rentalCost: 4500, trackerCost: 200 }
    ],
    maintenances: [
        { id: 1, vehicleId: 4, plate: 'DEF-5678', type: 'Troca de óleo e filtros', priority: 'Alta', status: 'Pendente', date: new Date().toISOString() },
        { id: 2, vehicleId: 5, plate: 'QWE-1122', type: 'Revisão de freios', priority: 'Média', status: 'Pendente', date: new Date().toISOString() },
        { id: 3, vehicleId: 6, plate: 'RST-3344', type: 'Reparo de suspensão', priority: 'Alta', status: 'Em Progresso', responsible: 'Mecânico A', date: new Date().toISOString() }
    ],
    drivers: [
        { id: 1, name: 'Carlos Souza', license: '12345678900', status: 'Disponível', documentExpiry: '2025-05-15' },
        { id: 2, name: 'Maria Silva', license: '98765432100', status: 'Disponível', documentExpiry: '2025-08-20' },
        { id: 3, name: 'João Santos', license: '45678912300', status: 'Em Viagem', documentExpiry: '2025-12-10' }
    ]
};

// Função para calcular estatísticas
function calculateStats() {
    const vehicles = database.vehicles;
    const activeVehicles = vehicles.filter(v => v.status === 'Ativo').length;
    const maintenanceVehicles = vehicles.filter(v => v.status === 'Manutenção').length;
    const inactiveVehicles = vehicles.filter(v => v.status === 'Inativo').length;
    const archivedVehicles = vehicles.filter(v => v.status === 'Arquivado').length;
    const totalVehicles = activeVehicles + maintenanceVehicles + inactiveVehicles;

    const availableDrivers = database.drivers.filter(d => d.status === 'Disponível').length;

    return {
        totalVehicles,
        activeVehicles,
        maintenanceVehicles,
        inactiveVehicles,
        archivedVehicles,
        availableDrivers,
        monthlyCost: 120000,
        percentages: {
            active: totalVehicles > 0 ? Math.round((activeVehicles / totalVehicles) * 100) : 0,
            maintenance: totalVehicles > 0 ? Math.round((maintenanceVehicles / totalVehicles) * 100) : 0,
            inactive: totalVehicles > 0 ? Math.round((inactiveVehicles / totalVehicles) * 100) : 0
        }
    };
}

// ===== PROXY ITURAN API =====
// Endpoint proxy para evitar problemas de CORS/Mixed Content do navegador

app.get('/api/proxy/ituran/*', async (req, res) => {
    try {
        // Pega o caminho após /api/proxy/ituran/
        const ituranPath = req.params[0];
        const queryString = Object.keys(req.query).length > 0
            ? '?' + new URLSearchParams(req.query).toString()
            : '';

        const proxyUrl = `http://localhost:8888/api/ituran/${ituranPath}${queryString}`;

        console.log(`🔄 [PROXY] Redirecionando: ${proxyUrl.substring(0, 100)}...`);

        const response = await fetch(proxyUrl, {
            method: 'GET',
            headers: {
                'Accept': '*/*'
            }
        });

        const text = await response.text();

        res.set('Content-Type', 'application/xml');
        res.send(text);

    } catch (error) {
        console.error('❌ [PROXY] Erro:', error.message);
        res.status(500).send(`<?xml version="1.0"?><Error>${error.message}</Error>`);
    }
});

// ===== ROTAS DA API =====

// Rota principal - Dashboard
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'dashboard.html'));
});

// Página de veículos
app.get('/veiculos', (req, res) => {
    res.sendFile(path.join(__dirname, 'veiculos.html'));
});

// GET - Estatísticas (do banco de dados)
app.get('/api/stats', async (req, res) => {
    try {
        // Buscar total de veículos
        const [vehicleCount] = await pool.query('SELECT COUNT(*) as count FROM Vehicles');
        const totalVehicles = vehicleCount[0].count;

        // Buscar motoristas disponíveis
        const [driverCount] = await pool.query('SELECT COUNT(*) as count FROM Drivers');
        const availableDrivers = driverCount[0].count;

        // Buscar manutenções pendentes
        const [maintenanceCount] = await pool.query(
            "SELECT COUNT(*) as count FROM FF_Maintenances WHERE status IN ('Pendente', 'Em Progresso')"
        );

        // Buscar OS abertas
        const [woCount] = await pool.query(
            "SELECT COUNT(*) as count FROM FF_WorkOrders WHERE status IN ('Aberta', 'Em Andamento', 'Aguardando Peças')"
        );

        const stats = {
            totalVehicles,
            activeVehicles: totalVehicles,
            maintenanceVehicles: maintenanceCount[0].count,
            inactiveVehicles: 0,
            archivedVehicles: 0,
            availableDrivers,
            openWorkOrders: woCount[0].count,
            monthlyCost: 120000,
            percentages: {
                active: 100,
                maintenance: 0,
                inactive: 0
            }
        };

        res.json(stats);
    } catch (error) {
        console.error('Erro ao buscar estatísticas:', error);
        res.status(500).json({ error: 'Erro ao buscar estatísticas' });
    }
});

// GET - Listar todos os veículos (do banco de dados)
app.get('/api/vehicles', async (req, res) => {
    try {
        const [vehicles] = await pool.query(`
            SELECT
                Id as id,
                LicensePlate as plate,
                VehicleName as model,
                VehicleYear as year,
                DriverId as driverId,
                LastSpeed as speed,
                LastAddress as location,
                EngineStatus as engineStatus,
                IgnitionStatus as status
            FROM Vehicles
            ORDER BY LicensePlate
        `);

        // Formatar dados para o frontend
        const formattedVehicles = vehicles.map(v => ({
            id: v.id,
            plate: v.plate,
            model: v.model || 'N/A',
            brand: 'N/A',
            year: v.year || 'N/A',
            mileage: 0,
            status: v.status || 'Desconhecido',
            color: 'N/A',
            fuel: 'N/A',
            type: 'N/A',
            base: 'N/A',
            location: v.location || 'Localização desconhecida',
            speed: v.speed || 0,
            driverId: v.driverId
        }));

        res.json(formattedVehicles);
    } catch (error) {
        console.error('Erro ao buscar veículos:', error);
        res.json(database.vehicles); // Fallback para dados mockados
    }
});

// GET - Buscar veículo por ID (do banco de dados)
app.get('/api/vehicles/:id', async (req, res) => {
    try {
        const [vehicles] = await pool.query(`
            SELECT
                Id as id,
                LicensePlate as plate,
                VehicleName as model,
                VehicleYear as year,
                DriverId as driverId,
                Renavam,
                ChassisNumber,
                EnginePower,
                EngineDisplacement,
                LastLatitude,
                LastLongitude,
                LastAddress,
                LastSpeed,
                EngineStatus,
                IgnitionStatus
            FROM Vehicles
            WHERE Id = ?
        `, [req.params.id]);

        if (vehicles.length === 0) {
            return res.status(404).json({ error: 'Veículo não encontrado' });
        }

        res.json(vehicles[0]);
    } catch (error) {
        console.error('Erro ao buscar veículo:', error);
        res.status(500).json({ error: 'Erro ao buscar veículo' });
    }
});

// POST - Criar novo veículo
app.post('/api/vehicles', (req, res) => {
    const newVehicle = {
        id: database.vehicles.length + 1,
        ...req.body,
        createdAt: new Date().toISOString()
    };

    database.vehicles.push(newVehicle);
    res.status(201).json(newVehicle);
});

// PUT - Atualizar veículo
app.put('/api/vehicles/:id', (req, res) => {
    const index = database.vehicles.findIndex(v => v.id === parseInt(req.params.id));

    if (index === -1) {
        return res.status(404).json({ error: 'Veículo não encontrado' });
    }

    database.vehicles[index] = {
        ...database.vehicles[index],
        ...req.body,
        updatedAt: new Date().toISOString()
    };

    res.json(database.vehicles[index]);
});

// DELETE - Remover veículo
app.delete('/api/vehicles/:id', (req, res) => {
    const index = database.vehicles.findIndex(v => v.id === parseInt(req.params.id));

    if (index === -1) {
        return res.status(404).json({ error: 'Veículo não encontrado' });
    }

    database.vehicles.splice(index, 1);
    res.json({ message: 'Veículo removido com sucesso' });
});

// GET - Listar manutenções (do banco de dados)
app.get('/api/maintenances', async (req, res) => {
    try {
        const { status } = req.query;

        let query = `
            SELECT
                m.*,
                v.LicensePlate,
                v.VehicleName
            FROM FF_Maintenances m
            LEFT JOIN Vehicles v ON m.vehicle_id = v.Id
        `;

        const params = [];

        if (status) {
            query += ' WHERE m.status = ?';
            params.push(status);
        }

        query += ' ORDER BY m.scheduled_date DESC';

        const [maintenances] = await pool.query(query, params);

        // Formatar dados para o frontend
        const formattedMaintenances = maintenances.map(m => ({
            id: m.id,
            vehicleId: m.vehicle_id,
            plate: m.license_plate,
            vehicleName: m.VehicleName,
            type: m.maintenance_type,
            description: m.description,
            priority: m.priority,
            status: m.status,
            responsible: m.responsible,
            date: m.scheduled_date,
            cost: m.cost,
            mileage: m.mileage
        }));

        res.json(formattedMaintenances);
    } catch (error) {
        console.error('Erro ao buscar manutenções:', error);
        res.json(database.maintenances); // Fallback
    }
});

// POST - Criar nova manutenção (no banco de dados)
app.post('/api/maintenances', async (req, res) => {
    try {
        const {
            vehicleId,
            plate,
            type,
            description,
            priority,
            status,
            responsible,
            date,
            cost,
            mileage
        } = req.body;

        const [result] = await pool.query(`
            INSERT INTO FF_Maintenances
            (vehicle_id, license_plate, maintenance_type, description, priority,
             status, responsible, scheduled_date, cost, mileage, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'web')
        `, [vehicleId, plate, type, description, priority || 'Média',
            status || 'Pendente', responsible, date, cost, mileage]);

        const newMaintenance = {
            id: result.insertId,
            vehicleId,
            plate,
            type,
            description,
            priority,
            status,
            responsible,
            date,
            cost,
            mileage
        };

        res.status(201).json(newMaintenance);
    } catch (error) {
        console.error('Erro ao criar manutenção:', error);
        res.status(500).json({ error: 'Erro ao criar manutenção' });
    }
});

// GET - Listar motoristas (do banco de dados MySQL)
app.get('/api/drivers', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT DriverID, FirstName, LastName FROM Drivers ORDER BY FirstName ASC');

        const driversList = rows.map(driver => ({
            id: driver.DriverID,
            firstName: driver.FirstName,
            lastName: driver.LastName,
            name: `${driver.FirstName} ${driver.LastName}`,
            cpf: 'N/A',
            cnhNumber: 'N/A',
            status: 'Disponível',
            cnhStatus: 'N/A',
            cnhCategory: 'N/A',
            cnhExpiry: 'N/A',
            admissionDate: 'N/A',
            birthDate: 'N/A'
        }));

        res.json(driversList);
    } catch (error) {
        console.error('Erro ao buscar motoristas do banco:', error);
        res.status(500).json({ error: 'Erro ao buscar motoristas' });
    }
});

// GET - Endpoint compatível com get-drivers.php (formato esperado pelo api-client.js)
app.get('/get-drivers.php', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT DriverID, FirstName, LastName FROM Drivers ORDER BY FirstName ASC');

        const driversList = rows.map(driver => ({
            id: driver.DriverID,
            firstName: driver.FirstName,
            lastName: driver.LastName,
            name: `${driver.FirstName} ${driver.LastName}`,
            cpf: 'N/A',
            cnhNumber: 'N/A',
            status: 'Disponível',
            cnhStatus: 'N/A',
            cnhCategory: 'N/A',
            cnhExpiry: 'N/A',
            admissionDate: 'N/A',
            birthDate: 'N/A'
        }));

        res.json({
            success: true,
            count: driversList.length,
            data: driversList
        });
    } catch (error) {
        console.error('Erro ao buscar motoristas do banco:', error);
        res.status(500).json({
            success: false,
            error: 'Erro ao buscar motoristas',
            message: error.message
        });
    }
});

// POST - Criar novo motorista
app.post('/api/drivers', (req, res) => {
    const newDriver = {
        id: database.drivers.length + 1,
        ...req.body,
        createdAt: new Date().toISOString()
    };

    database.drivers.push(newDriver);
    res.status(201).json(newDriver);
});

// GET - Alertas (do banco de dados)
app.get('/api/alerts', async (req, res) => {
    try {
        const [dbAlerts] = await pool.query(`
            SELECT
                a.*,
                v.LicensePlate,
                v.VehicleName,
                DATEDIFF(a.due_date, NOW()) as days_remaining
            FROM FF_Alerts a
            LEFT JOIN Vehicles v ON a.vehicle_id = v.Id
            WHERE a.is_resolved = FALSE
            ORDER BY a.severity DESC, a.due_date ASC
            LIMIT 10
        `);

        const alerts = dbAlerts.map(alert => {
            const severityMap = {
                'Crítico': { type: 'urgent', icon: 'error', color: 'red' },
                'Urgente': { type: 'urgent', icon: 'warning', color: 'red' },
                'Aviso': { type: 'warning', icon: 'warning', color: 'yellow' },
                'Info': { type: 'info', icon: 'info', color: 'blue' }
            };

            const severity = severityMap[alert.severity] || severityMap['Info'];
            const timeText = alert.days_remaining !== null
                ? (alert.days_remaining === 0 ? 'Hoje' :
                   alert.days_remaining === 1 ? 'Amanhã' :
                   alert.days_remaining < 0 ? 'Vencido' :
                   `Vence em ${alert.days_remaining} dias`)
                : 'Sem data';

            return {
                type: severity.type,
                title: alert.title,
                description: alert.description,
                time: timeText,
                icon: severity.icon,
                color: severity.color
            };
        });

        res.json(alerts);
    } catch (error) {
        console.error('Erro ao buscar alertas:', error);
        res.json([]); // Retorna array vazio em caso de erro
    }
});

// ===== ROTAS DE ORDENS DE SERVIÇO (WORK ORDERS) =====

// GET - Listar todas as OS
app.get('/api/workorders', async (req, res) => {
    try {
        const { status } = req.query;

        let query = `
            SELECT
                wo.*,
                v.LicensePlate,
                v.VehicleName,
                CONCAT(d.FirstName, ' ', d.LastName) as driver_name
            FROM FF_WorkOrders wo
            LEFT JOIN Vehicles v ON wo.vehicle_id = v.Id
            LEFT JOIN Drivers d ON wo.driver_id = d.DriverID
        `;

        const params = [];

        if (status) {
            query += ' WHERE wo.status = ?';
            params.push(status);
        }

        query += ' ORDER BY wo.opened_date DESC';

        const [workOrders] = await pool.query(query, params);

        res.json(workOrders);
    } catch (error) {
        console.error('Erro ao buscar ordens de serviço:', error);
        res.status(500).json({ error: 'Erro ao buscar ordens de serviço' });
    }
});

// GET - Buscar OS por ID com itens
app.get('/api/workorders/:id', async (req, res) => {
    try {
        const [workOrders] = await pool.query(`
            SELECT
                wo.*,
                v.LicensePlate,
                v.VehicleName,
                CONCAT(d.FirstName, ' ', d.LastName) as driver_name
            FROM FF_WorkOrders wo
            LEFT JOIN Vehicles v ON wo.vehicle_id = v.Id
            LEFT JOIN Drivers d ON wo.driver_id = d.DriverID
            WHERE wo.id = ?
        `, [req.params.id]);

        if (workOrders.length === 0) {
            return res.status(404).json({ error: 'OS não encontrada' });
        }

        // Buscar itens da OS
        const [items] = await pool.query(`
            SELECT * FROM FF_WorkOrderItems
            WHERE work_order_id = ?
            ORDER BY id
        `, [req.params.id]);

        const workOrder = {
            ...workOrders[0],
            items
        };

        res.json(workOrder);
    } catch (error) {
        console.error('Erro ao buscar OS:', error);
        res.status(500).json({ error: 'Erro ao buscar OS' });
    }
});

// POST - Criar nova OS (usando apenas as colunas que existem)
app.post('/api/workorders', async (req, res) => {
    try {
        const {
            ordem_numero,
            placa_veiculo,
            km_veiculo,
            responsavel,
            status,
            observacoes,
            ocorrencia,
            itens
        } = req.body;

        console.log('📥 Recebendo requisição para criar OS:', req.body);

        // Inserir OS usando SOMENTE as colunas que existem na tabela atual
        const [result] = await pool.query(`
            INSERT INTO ordemservico
            (ordem_numero, placa_veiculo, km_veiculo, responsavel, status, observacoes, ocorrencia, data_criacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        `, [
            ordem_numero,
            placa_veiculo,
            km_veiculo || 0,
            responsavel || 'Sistema Web',
            status || 'Aberta',
            observacoes || '',
            ocorrencia || 'Corretiva'
        ]);

        const os_id = result.insertId;
        console.log('✅ OS criada com ID:', os_id);

        // Inserir itens se houver
        if (itens && itens.length > 0) {
            // Verificar qual coluna usar: os_id ou ordem_numero
            const [itensColumns] = await pool.query("DESCRIBE ordemservico_itens");
            const usaOrdemNumero = itensColumns.some(col => col.Field === 'ordem_numero');
            const usaOsId = itensColumns.some(col => col.Field === 'os_id');

            for (const item of itens) {
                // Verificar se a coluna servico_id existe
                const hasServicoId = itensColumns.some(col => col.Field === 'servico_id');

                if (usaOrdemNumero && !usaOsId) {
                    // Nova estrutura: usa ordem_numero
                    if (hasServicoId) {
                        // Ainda tem servico_id (não foi removido)
                        await pool.query(`
                            INSERT INTO ordemservico_itens
                            (ordem_numero, tipo, servico_id, descricao, quantidade, valor_unitario)
                            VALUES (?, ?, NULL, ?, ?, ?)
                        `, [
                            ordem_numero,
                            item.tipo || 'Serviço',
                            item.descricao || '',
                            item.quantidade || 1,
                            item.valor_unitario || 0
                        ]);
                    } else {
                        // Sem servico_id (já foi removido)
                        await pool.query(`
                            INSERT INTO ordemservico_itens
                            (ordem_numero, tipo, descricao, quantidade, valor_unitario)
                            VALUES (?, ?, ?, ?, ?)
                        `, [
                            ordem_numero,
                            item.tipo || 'Serviço',
                            item.descricao || '',
                            item.quantidade || 1,
                            item.valor_unitario || 0
                        ]);
                    }
                } else {
                    // Estrutura antiga: usa os_id
                    if (hasServicoId) {
                        await pool.query(`
                            INSERT INTO ordemservico_itens
                            (os_id, tipo, servico_id, descricao, quantidade, valor_unitario)
                            VALUES (?, ?, NULL, ?, ?, ?)
                        `, [
                            os_id,
                            item.tipo || 'Serviço',
                            item.descricao || '',
                            item.quantidade || 1,
                            item.valor_unitario || 0
                        ]);
                    } else {
                        await pool.query(`
                            INSERT INTO ordemservico_itens
                            (os_id, tipo, descricao, quantidade, valor_unitario)
                            VALUES (?, ?, ?, ?, ?)
                        `, [
                            os_id,
                            item.tipo || 'Serviço',
                            item.descricao || '',
                            item.quantidade || 1,
                            item.valor_unitario || 0
                        ]);
                    }
                }
            }

            console.log('✅ Itens inseridos');
        }

        res.status(201).json({
            success: true,
            message: 'Ordem de serviço criada com sucesso',
            id: os_id,
            ordem_numero: ordem_numero
        });
    } catch (error) {
        console.error('❌ Erro ao criar OS:', error);
        res.status(500).json({
            success: false,
            error: 'Erro ao criar OS',
            message: error.message
        });
    }
});

// PUT - Atualizar OS
app.put('/api/workorders/:id', async (req, res) => {
    try {
        const {
            status,
            closedDate,
            finalCost,
            partsCost,
            laborCost,
            invoiceNumber,
            notes
        } = req.body;

        await pool.query(`
            UPDATE FF_WorkOrders
            SET status = ?, closed_date = ?, final_cost = ?, parts_cost = ?,
                labor_cost = ?, invoice_number = ?, notes = ?
            WHERE id = ?
        `, [status, closedDate, finalCost, partsCost, laborCost, invoiceNumber, notes, req.params.id]);

        res.json({ message: 'OS atualizada com sucesso' });
    } catch (error) {
        console.error('Erro ao atualizar OS:', error);
        res.status(500).json({ error: 'Erro ao atualizar OS' });
    }
});

// ===== ROTAS DE DEBUG =====

// GET - Debug: mostrar todas as OS
app.get('/api/debug-os', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT * FROM ordemservico ORDER BY id DESC LIMIT 10');
        res.json({ success: true, count: rows.length, data: rows });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET - Debug: mostrar todos os itens
app.get('/api/debug-os-itens', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT * FROM ordemservico_itens ORDER BY id DESC LIMIT 20');
        res.json({ success: true, count: rows.length, data: rows });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET - Debug: mostrar relacionamento entre OS e itens
app.get('/api/debug-os-join', async (req, res) => {
    try {
        const [rows] = await pool.query(`
            SELECT
                os.id as os_pk_id,
                os.ordem_numero,
                os.placa_veiculo,
                os.km_veiculo,
                os.status,
                oi.id as item_id,
                oi.os_id as item_os_id,
                oi.tipo,
                oi.descricao,
                oi.quantidade,
                oi.valor_unitario,
                oi.valor_total
            FROM ordemservico os
            LEFT JOIN ordemservico_itens oi ON os.id = oi.os_id
            ORDER BY os.id DESC, oi.id ASC
            LIMIT 50
        `);
        res.json({ success: true, count: rows.length, data: rows });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// ===== ROTAS DE OTIMIZAÇÃO DE ROTAS =====

// POST - Otimizar rota usando OpenRouteService
app.post('/api/routes/optimize', async (req, res) => {
    try {
        const { start, stops, returnToStart, startCoords, stopCoords, destinationCoords } = req.body;

        console.log('📥 Recebendo requisição de otimização...');

        let coordinates = [];

        // Se recebeu coordenadas diretas (clique no mapa)
        if (startCoords) {
            coordinates.push({ ...startCoords, address: start || 'Ponto de partida' });
        } else if (start) {
            // Geocodificar endereço de partida
            const coords = await geocodeAddress(start);
            if (!coords) {
                return res.status(400).json({
                    success: false,
                    error: `Não foi possível encontrar o endereço: ${start}`
                });
            }
            coordinates.push({ ...coords, address: start });
        } else {
            return res.status(400).json({
                success: false,
                error: 'Ponto de partida é obrigatório'
            });
        }

        // Processar paradas
        if (stops && stops.length > 0) {
            for (let i = 0; i < stops.length; i++) {
                if (stopCoords && stopCoords[i]) {
                    coordinates.push({ ...stopCoords[i], address: stops[i] || `Parada ${i + 1}` });
                } else if (stops[i]) {
                    const coords = await geocodeAddress(stops[i]);
                    if (!coords) {
                        return res.status(400).json({
                            success: false,
                            error: `Não foi possível encontrar o endereço: ${stops[i]}`
                        });
                    }
                    coordinates.push({ ...coords, address: stops[i] });
                }
            }
        }

        // Processar destino
        if (returnToStart) {
            coordinates.push({ ...coordinates[0], address: coordinates[0].address + ' (Retorno)' });
        } else if (destinationCoords) {
            coordinates.push({ ...destinationCoords, address: destinationCoords.address || 'Destino final' });
        }

        console.log('📍 Total de pontos:', coordinates.length);

        // Calcular rota otimizada usando OpenRouteService
        const optimizedRoute = await calculateOptimizedRoute(coordinates);

        res.json({
            success: true,
            route: optimizedRoute
        });
    } catch (error) {
        console.error('❌ Erro ao otimizar rota:', error);
        res.status(500).json({
            success: false,
            error: 'Erro ao otimizar rota: ' + error.message
        });
    }
});

// GET - Listar todas as rotas
app.get('/api/routes', async (req, res) => {
    try {
        const [routes] = await pool.query(`
            SELECT
                r.*,
                v.LicensePlate as vehicle_plate,
                CONCAT(d.FirstName, ' ', d.LastName) as driver_name
            FROM FF_Routes r
            LEFT JOIN Vehicles v ON r.vehicle_id = v.Id
            LEFT JOIN Drivers d ON r.driver_id = d.DriverID
            ORDER BY r.created_at DESC
        `);

        res.json(routes);
    } catch (error) {
        console.error('Erro ao buscar rotas:', error);
        res.status(500).json({ success: false, error: 'Erro ao buscar rotas' });
    }
});

// GET - Buscar rota por ID
app.get('/api/routes/:id', async (req, res) => {
    try {
        const [routes] = await pool.query(`
            SELECT * FROM FF_Routes WHERE id = ?
        `, [req.params.id]);

        if (routes.length === 0) {
            return res.status(404).json({ success: false, error: 'Rota não encontrada' });
        }

        const route = routes[0];
        route.route = JSON.parse(route.route_data);

        res.json({
            success: true,
            route: route.route
        });
    } catch (error) {
        console.error('Erro ao buscar rota:', error);
        res.status(500).json({ success: false, error: 'Erro ao buscar rota' });
    }
});

// POST - Salvar rota
app.post('/api/routes', async (req, res) => {
    try {
        const { name, vehicleId, driverId, route } = req.body;

        const [result] = await pool.query(`
            INSERT INTO FF_Routes
            (name, vehicle_id, driver_id, route_data, total_distance, total_duration, stops_count, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Planejada', NOW())
        `, [
            name,
            vehicleId,
            driverId,
            JSON.stringify(route),
            route.totalDistance,
            route.totalDuration,
            route.waypoints.length
        ]);

        res.json({
            success: true,
            id: result.insertId
        });
    } catch (error) {
        console.error('Erro ao salvar rota:', error);
        res.status(500).json({ success: false, error: 'Erro ao salvar rota' });
    }
});

// DELETE - Excluir rota
app.delete('/api/routes/:id', async (req, res) => {
    try {
        await pool.query('DELETE FROM FF_Routes WHERE id = ?', [req.params.id]);
        res.json({ success: true });
    } catch (error) {
        console.error('Erro ao excluir rota:', error);
        res.status(500).json({ success: false, error: 'Erro ao excluir rota' });
    }
});

// POST - Enviar rota por WhatsApp usando Evolution API
app.post('/api/routes/send-whatsapp', async (req, res) => {
    try {
        const { phone, route, routeName, instanceName } = req.body;

        // Formatar mensagem
        let message = `🚗 *Nova Rota: ${routeName}*\n\n`;
        message += `📏 *Distância Total:* ${(route.totalDistance / 1000).toFixed(2)} km\n`;
        message += `⏱️ *Tempo Estimado:* ${formatDuration(route.totalDuration)}\n\n`;
        message += `📍 *Sequência de Paradas:*\n`;

        route.waypoints.forEach((waypoint, index) => {
            message += `${index + 1}. ${waypoint.address}\n`;
        });

        // Criar link do Google Maps com rota otimizada
        message += `\n🗺️ Rota no Google Maps:\n`;
        const origin = `${route.waypoints[0].lat},${route.waypoints[0].lon}`;
        const destination = `${route.waypoints[route.waypoints.length - 1].lat},${route.waypoints[route.waypoints.length - 1].lon}`;

        // Se houver paradas intermediárias, adicionar como waypoints
        let waypointsParam = '';
        if (route.waypoints.length > 2) {
            const intermediateWaypoints = route.waypoints.slice(1, -1);
            waypointsParam = '&waypoints=' + intermediateWaypoints.map(wp => `${wp.lat},${wp.lon}`).join('|');
        }

        const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}${waypointsParam}&travelmode=driving`;
        message += googleMapsUrl;

        console.log('🗺️ Link do Google Maps gerado:', googleMapsUrl);

        // Enviar via Evolution API
        const evolutionApiUrl = 'http://10.0.2.12:60010';
        const apiKey = 'b0faf368ea81f396469c0bd26fa07bf9d6076117cd3b6fab6e0ca6004b3d710e';
        const instance = instanceName || 'Thiago Costa'; // Nome da instância

        const phoneNumber = phone.replace(/\D/g, '');
        const phoneWithCountry = phoneNumber.startsWith('55') ? phoneNumber : `55${phoneNumber}`;

        console.log('📱 Enviando mensagem via Evolution API para:', phoneWithCountry);

        const response = await fetch(`${evolutionApiUrl}/message/sendText/${instance}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'apikey': apiKey
            },
            body: JSON.stringify({
                number: phoneWithCountry,
                text: message
            })
        });

        const data = await response.json();

        if (response.ok) {
            console.log('✅ Mensagem enviada com sucesso!');
            res.json({
                success: true,
                message: 'Mensagem enviada com sucesso via WhatsApp!',
                data
            });
        } else {
            console.error('❌ Erro ao enviar via Evolution API:', data);
            res.status(400).json({
                success: false,
                error: 'Erro ao enviar mensagem',
                details: data
            });
        }
    } catch (error) {
        console.error('❌ Erro ao enviar WhatsApp:', error);
        res.status(500).json({
            success: false,
            error: 'Erro ao enviar WhatsApp: ' + error.message
        });
    }
});

// GET - Monitorar rota (comparar rota planejada vs executada)
app.get('/api/routes/:id/monitor', async (req, res) => {
    try {
        const routeId = req.params.id;

        // Buscar rota planejada
        const [routes] = await pool.query('SELECT * FROM FF_Routes WHERE id = ?', [routeId]);
        if (routes.length === 0) {
            return res.status(404).json({ success: false, error: 'Rota não encontrada' });
        }

        const route = routes[0];
        const plannedRoute = JSON.parse(route.route_data);

        // Buscar trajetória real do veículo (usando API Ituran)
        const vehicleId = route.vehicle_id;
        const [vehicles] = await pool.query('SELECT LicensePlate FROM Vehicles WHERE Id = ?', [vehicleId]);

        if (vehicles.length === 0) {
            return res.status(404).json({ success: false, error: 'Veículo não encontrado' });
        }

        // Buscar tracking histórico
        const [tracking] = await pool.query(`
            SELECT latitude, longitude, speed, recorded_at
            FROM FF_RouteTracking
            WHERE route_id = ?
            ORDER BY recorded_at ASC
        `, [routeId]);

        const actualRoute = tracking.map(t => ({
            lat: parseFloat(t.latitude),
            lon: parseFloat(t.longitude),
            speed: parseFloat(t.speed || 0),
            timestamp: t.recorded_at
        }));

        // Calcular desvio e conformidade
        let deviation = 0;
        let actualDistance = 0;

        if (actualRoute.length > 1) {
            for (let i = 0; i < actualRoute.length - 1; i++) {
                actualDistance += calculateDistance(
                    actualRoute[i].lat,
                    actualRoute[i].lon,
                    actualRoute[i + 1].lat,
                    actualRoute[i + 1].lon
                );
            }
        }

        const plannedDistance = plannedRoute.totalDistance;
        deviation = Math.abs(actualDistance - plannedDistance);
        const compliance = plannedDistance > 0
            ? Math.max(0, Math.min(100, (1 - deviation / plannedDistance) * 100))
            : 100;

        res.json({
            success: true,
            plannedRoute,
            actualRoute,
            actualDistance: actualDistance / 1000, // Converter para km
            deviation: deviation / 1000, // Converter para km
            compliance
        });
    } catch (error) {
        console.error('Erro ao monitorar rota:', error);
        res.status(500).json({ success: false, error: 'Erro ao monitorar rota' });
    }
});

// POST - Iniciar rota
app.post('/api/routes/:id/start', async (req, res) => {
    try {
        await pool.query(`
            UPDATE FF_Routes
            SET status = 'Em Andamento', started_at = NOW()
            WHERE id = ?
        `, [req.params.id]);

        res.json({ success: true, message: 'Rota iniciada' });
    } catch (error) {
        console.error('Erro ao iniciar rota:', error);
        res.status(500).json({ success: false, error: 'Erro ao iniciar rota' });
    }
});

// POST - Concluir rota
app.post('/api/routes/:id/complete', async (req, res) => {
    try {
        await pool.query(`
            UPDATE FF_Routes
            SET status = 'Concluída', completed_at = NOW()
            WHERE id = ?
        `, [req.params.id]);

        res.json({ success: true, message: 'Rota concluída' });
    } catch (error) {
        console.error('Erro ao concluir rota:', error);
        res.status(500).json({ success: false, error: 'Erro ao concluir rota' });
    }
});

// Função auxiliar: Geocodificar endereço usando Nominatim (OpenStreetMap)
async function geocodeAddress(address) {
    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1&countrycodes=br`;

        const response = await fetch(url, {
            headers: {
                'User-Agent': 'FleetFlow/1.0'
            }
        });

        const data = await response.json();

        if (data && data.length > 0) {
            return {
                lat: parseFloat(data[0].lat),
                lon: parseFloat(data[0].lon)
            };
        }

        return null;
    } catch (error) {
        console.error('Erro ao geocodificar:', error);
        return null;
    }
}

// Função auxiliar: Calcular rota otimizada usando OpenRouteService
async function calculateOptimizedRoute(coordinates) {
    try {
        const ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImNmNDNlZjc1MjQwMTRjMzY4ODEyYzRjM2VlZTlhNTZjIiwiaCI6Im11cm11cjY0In0=';

        const waypoints = coordinates.map(coord => ({
            lat: coord.lat,
            lon: coord.lon,
            address: coord.address
        }));

        // Preparar coordenadas para OpenRouteService (formato: [lon, lat])
        const orsCoordinates = waypoints.map(wp => [wp.lon, wp.lat]);

        console.log('📍 Calculando rota com OpenRouteService...');
        console.log('Coordenadas:', orsCoordinates);

        // Chamar OpenRouteService Directions API
        const orsResponse = await fetch('https://api.openrouteservice.org/v2/directions/driving-car', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': ORS_API_KEY
            },
            body: JSON.stringify({
                coordinates: orsCoordinates,
                instructions: false,
                language: 'pt'
            })
        });

        if (!orsResponse.ok) {
            const errorText = await orsResponse.text();
            console.error('❌ Erro OpenRouteService:', errorText);
            throw new Error('Erro ao calcular rota no OpenRouteService');
        }

        const orsData = await orsResponse.json();
        const route = orsData.routes[0];

        console.log('✅ Rota calculada com sucesso!');
        console.log('Distância:', route.summary.distance, 'metros');
        console.log('Duração:', route.summary.duration, 'segundos');

        return {
            waypoints,
            totalDistance: route.summary.distance, // em metros
            totalDuration: route.summary.duration, // em segundos
            geometry: route.geometry // polyline codificado
        };
    } catch (error) {
        console.error('❌ Erro ao calcular rota:', error);

        // Fallback: cálculo simples em linha reta
        console.log('⚠️ Usando fallback: cálculo em linha reta');
        const waypoints = coordinates.map(coord => ({
            lat: coord.lat,
            lon: coord.lon,
            address: coord.address
        }));

        let totalDistance = 0;
        let totalDuration = 0;

        for (let i = 0; i < waypoints.length - 1; i++) {
            const distance = calculateDistance(
                waypoints[i].lat,
                waypoints[i].lon,
                waypoints[i + 1].lat,
                waypoints[i + 1].lon
            );
            totalDistance += distance;

            const distanceKm = distance / 1000;
            const avgSpeed = distanceKm < 5 ? 30 : distanceKm < 20 ? 45 : 60;
            totalDuration += (distanceKm / avgSpeed) * 3600;
        }

        return {
            waypoints,
            totalDistance,
            totalDuration,
            geometry: null
        };
    }
}

// Função auxiliar: Calcular distância entre dois pontos (Haversine)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Raio da Terra em metros
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

// Função auxiliar: Formatar duração
function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return hours > 0 ? `${hours}h ${minutes}min` : `${minutes}min`;
}

// ===== ROTA DE VERIFICAÇÃO E CORREÇÃO DO BANCO =====

// GET - Verificar estrutura do banco de dados
app.get('/api/verify-db-structure', async (req, res) => {
    try {
        const results = {};

        // Verificar se a tabela ordemservico existe
        const [tables] = await pool.query("SHOW TABLES LIKE 'ordemservico'");
        results.table_exists = tables.length > 0;

        if (results.table_exists) {
            // Verificar estrutura da tabela
            const [columns] = await pool.query("DESCRIBE ordemservico");
            results.columns = columns;

            // Verificar se veiculo_id existe
            const hasVeiculoId = columns.some(col => col.Field === 'veiculo_id');
            results.has_veiculo_id = hasVeiculoId;

            if (hasVeiculoId) {
                results.message = 'Estrutura da tabela está correta';
            } else {
                results.message = 'Coluna veiculo_id não encontrada';
            }
        } else {
            results.message = 'Tabela ordemservico não existe';
        }

        // Verificar outras tabelas importantes
        results.other_tables = {};
        const tablesToCheck = ['ordemservico_itens', 'servicos', 'veiculos', 'motoristas'];

        for (const table of tablesToCheck) {
            const [exists] = await pool.query(`SHOW TABLES LIKE '${table}'`);
            results.other_tables[table] = exists.length > 0;
        }

        res.json({
            success: true,
            results
        });
    } catch (error) {
        console.error('Erro ao verificar estrutura:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// POST - Remover colunas não utilizadas (servico_id e veiculo_id)
app.post('/api/remove-unused-columns', async (req, res) => {
    try {
        console.log('🧹 Removendo colunas não utilizadas...');

        const results = {
            servico_id_removed: false,
            veiculo_id_removed: false,
            messages: []
        };

        // 1. Remover servico_id de ordemservico_itens
        const [itensColumns] = await pool.query("DESCRIBE ordemservico_itens");
        const hasServicoId = itensColumns.some(col => col.Field === 'servico_id');

        if (hasServicoId) {
            // Remover Foreign Key se existir
            const [fkServico] = await pool.query(`
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = 'f137049_in9aut'
                AND TABLE_NAME = 'ordemservico_itens'
                AND COLUMN_NAME = 'servico_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            `);

            if (fkServico.length > 0) {
                await pool.query(`ALTER TABLE ordemservico_itens DROP FOREIGN KEY ${fkServico[0].CONSTRAINT_NAME}`);
                console.log('✅ FK servico_id removida');
            }

            // Remover índice se existir
            try {
                await pool.query('ALTER TABLE ordemservico_itens DROP INDEX idx_servico');
            } catch (e) {
                console.log('⚠️ Índice idx_servico não existia');
            }

            // Remover coluna
            await pool.query('ALTER TABLE ordemservico_itens DROP COLUMN servico_id');
            results.servico_id_removed = true;
            results.messages.push('✅ Coluna servico_id removida de ordemservico_itens');
            console.log('✅ Coluna servico_id removida');
        } else {
            results.messages.push('ℹ️ Coluna servico_id já não existe');
        }

        // 2. Remover veiculo_id de ordemservico
        const [osColumns] = await pool.query("DESCRIBE ordemservico");
        const hasVeiculoId = osColumns.some(col => col.Field === 'veiculo_id');

        if (hasVeiculoId) {
            // Remover Foreign Key se existir
            const [fkVeiculo] = await pool.query(`
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = 'f137049_in9aut'
                AND TABLE_NAME = 'ordemservico'
                AND COLUMN_NAME = 'veiculo_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            `);

            if (fkVeiculo.length > 0) {
                await pool.query(`ALTER TABLE ordemservico DROP FOREIGN KEY ${fkVeiculo[0].CONSTRAINT_NAME}`);
                console.log('✅ FK veiculo_id removida');
            }

            // Remover índice se existir
            try {
                await pool.query('ALTER TABLE ordemservico DROP INDEX idx_veiculo');
            } catch (e) {
                console.log('⚠️ Índice idx_veiculo não existia');
            }

            // Remover coluna
            await pool.query('ALTER TABLE ordemservico DROP COLUMN veiculo_id');
            results.veiculo_id_removed = true;
            results.messages.push('✅ Coluna veiculo_id removida de ordemservico');
            console.log('✅ Coluna veiculo_id removida');
        } else {
            results.messages.push('ℹ️ Coluna veiculo_id já não existe');
        }

        res.json({
            success: true,
            message: 'Colunas não utilizadas removidas com sucesso!',
            details: results
        });

    } catch (error) {
        console.error('❌ Erro ao remover colunas:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// POST - Alterar ordemservico_itens para usar ordem_numero ao invés de os_id
app.post('/api/alter-itens-to-ordem-numero', async (req, res) => {
    try {
        console.log('🔧 Iniciando alteração da tabela ordemservico_itens...');

        // 1. Verificar se a coluna os_id existe
        const [columns] = await pool.query("DESCRIBE ordemservico_itens");
        const hasOsId = columns.some(col => col.Field === 'os_id');
        const hasOrdemNumero = columns.some(col => col.Field === 'ordem_numero');

        if (!hasOsId && hasOrdemNumero) {
            return res.json({
                success: true,
                already_fixed: true,
                message: 'A tabela já usa ordem_numero ao invés de os_id'
            });
        }

        // 2. Remover Foreign Key antiga (se existir)
        try {
            await pool.query('ALTER TABLE ordemservico_itens DROP FOREIGN KEY fk_itens_os');
            console.log('✅ Foreign key antiga removida');
        } catch (e) {
            console.log('⚠️ Foreign key não existia:', e.message);
        }

        // 3. Adicionar coluna ordem_numero temporária
        if (!hasOrdemNumero) {
            await pool.query(`
                ALTER TABLE ordemservico_itens
                ADD COLUMN ordem_numero VARCHAR(50) NULL
                AFTER os_id
            `);
            console.log('✅ Coluna ordem_numero adicionada');
        }

        // 4. Preencher ordem_numero com base no os_id existente
        await pool.query(`
            UPDATE ordemservico_itens oi
            JOIN ordemservico os ON oi.os_id = os.id
            SET oi.ordem_numero = os.ordem_numero
        `);
        console.log('✅ Dados migrados de os_id para ordem_numero');

        // 5. Remover TODAS as foreign keys que usam os_id
        const [foreignKeys] = await pool.query(`
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'f137049_in9aut'
            AND TABLE_NAME = 'ordemservico_itens'
            AND COLUMN_NAME = 'os_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        `);

        for (const fk of foreignKeys) {
            try {
                await pool.query(`ALTER TABLE ordemservico_itens DROP FOREIGN KEY ${fk.CONSTRAINT_NAME}`);
                console.log(`✅ Foreign key ${fk.CONSTRAINT_NAME} removida`);
            } catch (e) {
                console.log(`⚠️ Erro ao remover FK ${fk.CONSTRAINT_NAME}:`, e.message);
            }
        }

        // 6. Remover coluna os_id antiga
        if (hasOsId) {
            await pool.query('ALTER TABLE ordemservico_itens DROP COLUMN os_id');
            console.log('✅ Coluna os_id removida');
        }

        // 6. Tornar ordem_numero NOT NULL
        await pool.query(`
            ALTER TABLE ordemservico_itens
            MODIFY COLUMN ordem_numero VARCHAR(50) NOT NULL
        `);
        console.log('✅ Coluna ordem_numero definida como NOT NULL');

        // 7. Adicionar índice
        try {
            await pool.query(`
                ALTER TABLE ordemservico_itens
                ADD INDEX idx_ordem_numero (ordem_numero ASC)
            `);
            console.log('✅ Índice adicionado');
        } catch (e) {
            console.log('⚠️ Índice já existia');
        }

        // 8. Adicionar Foreign Key nova
        try {
            await pool.query(`
                ALTER TABLE ordemservico_itens
                ADD CONSTRAINT fk_itens_os_numero
                FOREIGN KEY (ordem_numero)
                REFERENCES ordemservico (ordem_numero)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            `);
            console.log('✅ Foreign key nova criada');
        } catch (e) {
            console.log('⚠️ Erro ao criar foreign key:', e.message);
        }

        // Verificar resultado
        const [newColumns] = await pool.query("DESCRIBE ordemservico_itens");

        res.json({
            success: true,
            fixed: true,
            message: 'Tabela ordemservico_itens alterada com sucesso! Agora usa ordem_numero ao invés de os_id.',
            columns: newColumns
        });

    } catch (error) {
        console.error('❌ Erro ao alterar tabela:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// POST - Corrigir estrutura do banco (adicionar veiculo_id)
app.post('/api/fix-db-structure', async (req, res) => {
    try {
        // Verificar se a coluna já existe
        const [columns] = await pool.query("DESCRIBE ordemservico");
        const hasVeiculoId = columns.some(col => col.Field === 'veiculo_id');

        if (hasVeiculoId) {
            return res.json({
                success: true,
                already_fixed: true,
                message: 'A coluna veiculo_id já existe na tabela'
            });
        }

        // Adicionar a coluna veiculo_id
        await pool.query(`
            ALTER TABLE ordemservico
            ADD COLUMN veiculo_id INT NULL DEFAULT NULL
            AFTER ordem_numero
        `);

        // Adicionar índice
        await pool.query(`
            ALTER TABLE ordemservico
            ADD INDEX idx_veiculo (veiculo_id ASC)
        `);

        // Adicionar foreign key (pode falhar se a tabela veiculos não existir)
        try {
            await pool.query(`
                ALTER TABLE ordemservico
                ADD CONSTRAINT fk_os_veiculos
                FOREIGN KEY (veiculo_id)
                REFERENCES veiculos (id)
                ON DELETE SET NULL
                ON UPDATE CASCADE
            `);
        } catch (fkError) {
            console.warn('Aviso: Não foi possível criar foreign key:', fkError.message);
        }

        res.json({
            success: true,
            fixed: true,
            message: 'Coluna veiculo_id adicionada com sucesso!'
        });
    } catch (error) {
        console.error('Erro ao corrigir estrutura:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// ========== ROTAS DE QUILOMETRAGEM (REFATORADO) ==========
// Inicializa serviços de quilometragem
const { mileageService } = require('./services/index');

// POST - Atualizar quilometragem diária de um veículo (busca da API Ituran e salva)
app.post('/api/v2/mileage/update/:plate', async (req, res) => {
    try {
        const { plate } = req.params;
        const { date } = req.body; // Opcional, padrão = hoje

        const result = await mileageService.updateDailyMileage(plate, date || new Date().toISOString().split('T')[0]);
        res.json(result);
    } catch (error) {
        console.error('Erro ao atualizar quilometragem:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// POST - Atualizar quilometragem de múltiplos veículos
app.post('/api/v2/mileage/update-multiple', async (req, res) => {
    try {
        const { plates, date } = req.body;

        if (!plates || !Array.isArray(plates)) {
            return res.status(400).json({
                success: false,
                error: 'Parâmetro "plates" deve ser um array de placas'
            });
        }

        const result = await mileageService.updateMultipleVehicles(plates, date || new Date().toISOString().split('T')[0]);
        res.json(result);
    } catch (error) {
        console.error('Erro ao atualizar múltiplos veículos:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET - Buscar quilometragem diária de um veículo
app.get('/api/v2/mileage/daily/:plate/:date', async (req, res) => {
    try {
        const { plate, date } = req.params;
        const result = await mileageService.getDailyMileage(plate, date);
        res.json(result);
    } catch (error) {
        console.error('Erro ao buscar quilometragem diária:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET - Buscar quilometragem de um período
app.get('/api/v2/mileage/period/:plate', async (req, res) => {
    try {
        const { plate } = req.params;
        const { startDate, endDate } = req.query;

        if (!startDate || !endDate) {
            return res.status(400).json({
                success: false,
                error: 'Parâmetros startDate e endDate são obrigatórios'
            });
        }

        const result = await mileageService.getPeriodMileage(plate, startDate, endDate);
        res.json(result);
    } catch (error) {
        console.error('Erro ao buscar período:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET - Buscar quilometragem mensal
app.get('/api/v2/mileage/monthly/:plate/:year/:month', async (req, res) => {
    try {
        const { plate, year, month } = req.params;
        const result = await mileageService.getMonthlyMileage(plate, parseInt(year), parseInt(month));
        res.json(result);
    } catch (error) {
        console.error('Erro ao buscar quilometragem mensal:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET - Buscar estatísticas de um veículo
app.get('/api/v2/mileage/stats/:plate', async (req, res) => {
    try {
        const { plate } = req.params;
        const { period } = req.query; // 'semana', 'mes', 'ano'
        const result = await mileageService.getStatistics(plate, period || 'mes');
        res.json(result);
    } catch (error) {
        console.error('Erro ao buscar estatísticas:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET - Buscar totais da frota por dia
app.get('/api/v2/mileage/fleet-daily/:date', async (req, res) => {
    try {
        const { date } = req.params;
        const result = await mileageService.getFleetDailyTotal(date);
        res.json(result);
    } catch (error) {
        console.error('Erro ao buscar totais da frota:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// POST - Sincronizar dados faltantes de um veículo em um período
app.post('/api/v2/mileage/sync/:plate', async (req, res) => {
    try {
        const { plate } = req.params;
        const { startDate, endDate } = req.body;

        if (!startDate || !endDate) {
            return res.status(400).json({
                success: false,
                error: 'Parâmetros startDate e endDate são obrigatórios'
            });
        }

        const result = await mileageService.syncMissingData(plate, startDate, endDate);
        res.json(result);
    } catch (error) {
        console.error('Erro ao sincronizar dados:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// ========== ROTAS ANTIGAS (COMPATIBILIDADE) ==========
const quilometragemAPI = require('./quilometragem-api');

// GET - Buscar quilometragem de um dia (antiga)
app.get('/api/quilometragem/diaria/:placa/:data', async (req, res) => {
    const { placa, data } = req.params;
    const result = await mileageService.getDailyMileage(placa, data);
    res.json(result);
});

// POST - Atualizar quilometragem de um veículo (antiga)
app.post('/api/quilometragem/atualizar/:placa', async (req, res) => {
    const { placa } = req.params;
    const { data } = req.body;
    const result = await mileageService.updateDailyMileage(placa, data);
    res.json(result);
});

// GET - Buscar estatísticas de um veículo (antiga)
app.get('/api/quilometragem/estatisticas/:placa', async (req, res) => {
    const { placa } = req.params;
    const { periodo } = req.query;
    const result = await mileageService.getStatistics(placa, periodo || 'mes');
    res.json(result);
});

// ========== ROTAS DE CHECKLIST ==========

// GET - Listar todos os checklists
app.get('/api/checklists', async (req, res) => {
    try {
        const limite = req.query.limite ? parseInt(req.query.limite) : 100;

        const [checklists] = await pool.query(`
            SELECT
                i.id,
                i.placa,
                i.data_realizacao,
                i.km_inicial,
                i.nivel_combustivel,
                i.status_geral,
                COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
            FROM aaa_inspecao_veiculo i
            LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
            ORDER BY i.data_realizacao DESC
            LIMIT ?
        `, [limite]);

        res.json(checklists);
    } catch (error) {
        console.error('Erro ao buscar checklists:', error);
        res.status(500).json({
            erro: 'Erro ao buscar dados',
            detalhes: error.message
        });
    }
});

// GET - Buscar checklist completo por ID
app.get('/api/checklists/:id', async (req, res) => {
    try {
        const id = req.params.id;

        // Buscar inspeção com dados do usuário
        const [inspecoes] = await pool.query(`
            SELECT
                i.*,
                COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
            FROM aaa_inspecao_veiculo i
            LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
            WHERE i.id = ?
        `, [id]);

        if (inspecoes.length === 0) {
            return res.status(404).json({ erro: 'Checklist não encontrado' });
        }

        const inspecao = inspecoes[0];

        // Buscar fotos organizadas por tipo
        const [fotosArray] = await pool.query(`
            SELECT tipo, foto FROM aaa_inspecao_foto WHERE inspecao_id = ?
        `, [id]);

        const fotos = {};
        fotosArray.forEach(foto => {
            fotos[foto.tipo] = foto.foto;
        });

        // Buscar itens organizados por categoria
        const [itensArray] = await pool.query(`
            SELECT categoria, item, status, foto, pressao, foto_caneta
            FROM aaa_inspecao_item
            WHERE inspecao_id = ?
        `, [id]);

        // Organizar itens por categoria
        const itens = {
            MOTOR: [],
            ELETRICO: [],
            LIMPEZA: [],
            FERRAMENTA: [],
            PNEU: []
        };

        itensArray.forEach(item => {
            itens[item.categoria].push({
                item: item.item,
                status: item.status,
                foto: item.foto,
                pressao: item.pressao,
                foto_caneta: item.foto_caneta
            });
        });

        // Montar resultado completo
        const resultado = {
            id: inspecao.id,
            placa: inspecao.placa,
            km_inicial: inspecao.km_inicial,
            nivel_combustivel: inspecao.nivel_combustivel,
            observacao_painel: inspecao.observacao_painel,
            data_realizacao: inspecao.data_realizacao,
            status_geral: inspecao.status_geral,
            usuario: {
                id: inspecao.usuario_id,
                nome: inspecao.usuario_nome
            },
            fotos,
            itens
        };

        res.json(resultado);
    } catch (error) {
        console.error('Erro ao buscar checklist:', error);
        res.status(500).json({
            erro: 'Erro ao buscar dados',
            detalhes: error.message
        });
    }
});

// GET - Buscar checklists por placa
app.get('/api/checklists/placa/:placa', async (req, res) => {
    try {
        const [checklists] = await pool.query(`
            SELECT
                i.*,
                COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
            FROM aaa_inspecao_veiculo i
            LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
            WHERE i.placa = ?
            ORDER BY i.data_realizacao DESC
        `, [req.params.placa]);

        res.json(checklists);
    } catch (error) {
        console.error('Erro ao buscar checklists por placa:', error);
        res.status(500).json({
            erro: 'Erro ao buscar dados',
            detalhes: error.message
        });
    }
});

// GET - Buscar checklists por período
app.get('/api/checklists/periodo', async (req, res) => {
    try {
        const { data_inicio, data_fim } = req.query;

        if (!data_inicio || !data_fim) {
            return res.status(400).json({ erro: 'Datas não informadas' });
        }

        const [checklists] = await pool.query(`
            SELECT
                i.*,
                COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
            FROM aaa_inspecao_veiculo i
            LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
            WHERE i.data_realizacao BETWEEN ? AND ?
            ORDER BY i.data_realizacao DESC
        `, [data_inicio, data_fim]);

        res.json(checklists);
    } catch (error) {
        console.error('Erro ao buscar checklists por período:', error);
        res.status(500).json({
            erro: 'Erro ao buscar dados',
            detalhes: error.message
        });
    }
});

// ========== ROTAS DE MANUTENÇÃO PREVENTIVA ==========
const { setupManutencaoRoutes } = require('./manutencao-api');
setupManutencaoRoutes(app, pool);

// ========== ROTAS COMPATÍVEIS COM ARQUIVOS PHP (para Angular) ==========
// Estas rotas replicam o comportamento dos arquivos PHP para compatibilidade com o frontend Angular

// GET - veicular_get.php (compatível com os endpoints PHP)
app.get('/admin/veicular_get.php', async (req, res) => {
    try {
        // Headers CORS
        res.setHeader('Access-Control-Allow-Origin', '*');
        res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        res.setHeader('Content-Type', 'application/json; charset=utf-8');

        const acao = req.query.acao || 'todos';

        switch (acao) {
            case 'id':
                if (!req.query.id) {
                    return res.status(400).json({ erro: 'ID não informado' });
                }

                const [inspecoes] = await pool.query(`
                    SELECT
                        i.*,
                        COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
                    FROM aaa_inspecao_veiculo i
                    LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
                    WHERE i.id = ?
                `, [req.query.id]);

                if (inspecoes.length === 0) {
                    return res.status(404).json({ erro: 'Checklist não encontrado' });
                }

                const inspecao = inspecoes[0];

                // Buscar fotos
                const [fotosArray] = await pool.query(`
                    SELECT tipo, foto FROM aaa_inspecao_foto WHERE inspecao_id = ?
                `, [req.query.id]);

                // Buscar itens
                const [itensArray] = await pool.query(`
                    SELECT categoria, item, status, foto FROM aaa_inspecao_item WHERE inspecao_id = ?
                `, [req.query.id]);

                res.json({
                    inspecao,
                    fotos: fotosArray,
                    itens: itensArray
                });
                break;

            case 'placa':
                if (!req.query.placa) {
                    return res.status(400).json({ erro: 'Placa não informada' });
                }

                const [checklistsPlaca] = await pool.query(`
                    SELECT
                        i.*,
                        COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
                    FROM aaa_inspecao_veiculo i
                    LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
                    WHERE i.placa = ?
                    ORDER BY i.data_realizacao DESC
                `, [req.query.placa]);

                res.json(checklistsPlaca);
                break;

            case 'periodo':
                if (!req.query.data_inicio || !req.query.data_fim) {
                    return res.status(400).json({ erro: 'Datas não informadas' });
                }

                const [checklistsPeriodo] = await pool.query(`
                    SELECT
                        i.*,
                        COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
                    FROM aaa_inspecao_veiculo i
                    LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
                    WHERE i.data_realizacao BETWEEN ? AND ?
                    ORDER BY i.data_realizacao DESC
                `, [req.query.data_inicio, req.query.data_fim]);

                res.json(checklistsPeriodo);
                break;

            case 'completo':
                if (!req.query.id) {
                    return res.status(400).json({ erro: 'ID não informado' });
                }

                const [inspecoesCompleto] = await pool.query(`
                    SELECT
                        i.*,
                        COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
                    FROM aaa_inspecao_veiculo i
                    LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
                    WHERE i.id = ?
                `, [req.query.id]);

                if (inspecoesCompleto.length === 0) {
                    return res.status(404).json({ erro: 'Checklist não encontrado' });
                }

                const inspecaoCompleto = inspecoesCompleto[0];

                // Buscar fotos organizadas por tipo
                const [fotosCompleto] = await pool.query(`
                    SELECT tipo, foto FROM aaa_inspecao_foto WHERE inspecao_id = ?
                `, [req.query.id]);

                const fotosObj = {};
                fotosCompleto.forEach(foto => {
                    fotosObj[foto.tipo] = foto.foto;
                });

                // Buscar itens organizados por categoria
                const [itensCompleto] = await pool.query(`
                    SELECT categoria, item, status, foto, pressao, foto_caneta
                    FROM aaa_inspecao_item
                    WHERE inspecao_id = ?
                `, [req.query.id]);

                // Organizar itens por categoria
                const itens = {
                    MOTOR: [],
                    ELETRICO: [],
                    LIMPEZA: [],
                    FERRAMENTA: [],
                    PNEU: []
                };

                itensCompleto.forEach(item => {
                    itens[item.categoria].push({
                        item: item.item,
                        status: item.status,
                        foto: item.foto,
                        pressao: item.pressao,
                        foto_caneta: item.foto_caneta
                    });
                });

                // Adicionar itens padrão que não estão no banco (se necessário)
                const todosItens = {
                    'MOTOR': ['Água Radiador', 'Água Limpador Parabrisa', 'Fluido de Freio', 'Nível de Óleo', 'Tampa do Radiador', 'Freio de Mão'],
                    'ELETRICO': ['Seta Esquerda', 'Seta Direita', 'Pisca Alerta', 'Farol'],
                    'LIMPEZA': ['Limpeza Interna', 'Limpeza Externa'],
                    'FERRAMENTA': ['Macaco', 'Chave de Roda', 'Chave do Estepe', 'Triângulo']
                };

                Object.keys(todosItens).forEach(categoria => {
                    if (categoria !== 'PNEU') {
                        const itensSalvos = itens[categoria].map(i => i.item);
                        todosItens[categoria].forEach(itemNome => {
                            if (!itensSalvos.includes(itemNome)) {
                                itens[categoria].push({
                                    item: itemNome,
                                    status: categoria === 'MOTOR' || categoria === 'ELETRICO' ? 'bom' : 
                                            categoria === 'LIMPEZA' ? 'otimo' : 'contem',
                                    foto: ''
                                });
                            }
                        });
                    }
                });

                res.json({
                    id: inspecaoCompleto.id,
                    placa: inspecaoCompleto.placa,
                    km_inicial: inspecaoCompleto.km_inicial,
                    nivel_combustivel: inspecaoCompleto.nivel_combustivel,
                    observacao_painel: inspecaoCompleto.observacao_painel,
                    data_realizacao: inspecaoCompleto.data_realizacao,
                    status_geral: inspecaoCompleto.status_geral,
                    usuario: {
                        id: inspecaoCompleto.usuario_id,
                        nome: inspecaoCompleto.usuario_nome
                    },
                    fotos: fotosObj,
                    itens: itens
                });
                break;

            case 'todos':
            default:
                const limite = req.query.limite ? parseInt(req.query.limite) : 100;

                const [checklistsTodos] = await pool.query(`
                    SELECT
                        i.id,
                        i.placa,
                        i.data_realizacao,
                        i.km_inicial,
                        i.nivel_combustivel,
                        i.status_geral,
                        COALESCE(u.nome, 'Usuário não identificado') as usuario_nome
                    FROM aaa_inspecao_veiculo i
                    LEFT JOIN aaa_usuario u ON i.usuario_id = u.id
                    ORDER BY i.data_realizacao DESC
                    LIMIT ?
                `, [limite]);

                res.json(checklistsTodos);
                break;
        }
    } catch (error) {
        console.error('Erro em veicular_get.php:', error);
        res.status(500).json({
            erro: 'Erro ao buscar dados',
            detalhes: error.message
        });
    }
});

// GET/POST - veicular_tempotelas.php (compatível com os endpoints PHP)
app.get('/admin/veicular_tempotelas.php', async (req, res) => {
    try {
        // Headers CORS
        res.setHeader('Access-Control-Allow-Origin', '*');
        res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        res.setHeader('Content-Type', 'application/json; charset=utf-8');

        const acao = req.query.acao || 'todos';

        switch (acao) {
            case 'inspecao':
                if (!req.query.inspecao_id) {
                    return res.status(400).json({ erro: 'ID da inspeção não informado' });
                }

                const [temposInspecao] = await pool.query(`
                    SELECT * FROM aaa_tempo_telas
                    WHERE inspecao_id = ?
                    ORDER BY data_hora_inicio ASC
                `, [req.query.inspecao_id]);

                res.json(temposInspecao);
                break;

            case 'usuario':
                if (!req.query.usuario_id) {
                    return res.status(400).json({ erro: 'ID do usuário não informado' });
                }

                const [temposUsuario] = await pool.query(`
                    SELECT * FROM aaa_tempo_telas
                    WHERE usuario_id = ?
                    ORDER BY data_hora_inicio DESC
                    LIMIT 100
                `, [req.query.usuario_id]);

                res.json(temposUsuario);
                break;

            case 'estatisticas':
                const [estatisticas] = await pool.query(`
                    SELECT
                        tela,
                        COUNT(*) as total_registros,
                        AVG(tempo_segundos) as tempo_medio_segundos,
                        MIN(tempo_segundos) as tempo_minimo_segundos,
                        MAX(tempo_segundos) as tempo_maximo_segundos,
                        SUM(tempo_segundos) as tempo_total_segundos
                    FROM aaa_tempo_telas
                    GROUP BY tela
                    ORDER BY tempo_medio_segundos DESC
                `);

                res.json(estatisticas);
                break;

            case 'todos':
            default:
                const limite = req.query.limite ? parseInt(req.query.limite) : 100;

                const [temposTodos] = await pool.query(`
                    SELECT * FROM aaa_tempo_telas
                    ORDER BY data_hora_inicio DESC
                    LIMIT ?
                `, [limite]);

                res.json(temposTodos);
                break;
        }
    } catch (error) {
        console.error('Erro em veicular_tempotelas.php:', error);
        res.status(500).json({
            erro: 'Erro no banco de dados',
            mensagem: error.message
        });
    }
});

// POST - veicular_tempotelas.php (salvar tempo de tela)
app.post('/admin/veicular_tempotelas.php', async (req, res) => {
    try {
        // Headers CORS
        res.setHeader('Access-Control-Allow-Origin', '*');
        res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        res.setHeader('Content-Type', 'application/json; charset=utf-8');

        const dados = req.body;

        if (!dados || !dados.tela || !dados.tempo_segundos) {
            return res.status(400).json({
                erro: 'Dados incompletos. É necessário informar: tela, tempo_segundos, data_hora_inicio, data_hora_fim'
            });
        }

        const [result] = await pool.query(`
            INSERT INTO aaa_tempo_telas
            (inspecao_id, usuario_id, tela, tempo_segundos, data_hora_inicio, data_hora_fim)
            VALUES (?, ?, ?, ?, ?, ?)
        `, [
            dados.inspecao_id || null,
            dados.usuario_id || null,
            dados.tela,
            dados.tempo_segundos,
            dados.data_hora_inicio,
            dados.data_hora_fim
        ]);

        res.status(201).json({
            sucesso: true,
            id: result.insertId,
            mensagem: 'Tempo de tela registrado com sucesso'
        });
    } catch (error) {
        console.error('Erro ao salvar tempo de tela:', error);
        res.status(500).json({
            erro: 'Erro no banco de dados',
            mensagem: error.message
        });
    }
});

// OPTIONS - Preflight para CORS
app.options('/admin/veicular_get.php', (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
    res.status(200).end();
});

app.options('/admin/veicular_tempotelas.php', (req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
    res.status(200).end();
});

// Servir arquivos estáticos DEPOIS de todas as rotas dinâmicas
app.use(express.static(__dirname));

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║           🚗  FLEETFLOW - Sistema de Frotas  🚗          ║
║                                                           ║
╠═══════════════════════════════════════════════════════════╣
║                                                           ║
║  Servidor rodando em: http://localhost:${PORT}              ║
║                                                           ║
║  Páginas Disponíveis:                                    ║
║  • Dashboard:  http://localhost:${PORT}/                    ║
║  • Veículos:   http://localhost:${PORT}/veiculos            ║
║                                                           ║
║  API REST Endpoints:                                     ║
║  • GET    /api/stats           - Estatísticas            ║
║  • GET    /api/vehicles        - Listar veículos         ║
║  • POST   /api/vehicles        - Criar veículo           ║
║  • PUT    /api/vehicles/:id    - Atualizar veículo       ║
║  • DELETE /api/vehicles/:id    - Remover veículo         ║
║  • GET    /api/maintenances    - Listar manutenções      ║
║  • GET    /api/drivers         - Listar motoristas       ║
║  • GET    /api/alerts          - Alertas do sistema      ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
    `);

    // ========== CONFIGURAÇÃO DE CRON JOBS ==========
    // Atualizar quilometragem diária automaticamente todos os dias à meia-noite e meia
    cron.schedule('30 0 * * *', async () => {
        console.log('\n⏰ [CRON] Iniciando atualização automática de quilometragem...');
        try {
            await atualizarQuilometragemDiaria();
            console.log('✅ [CRON] Atualização de quilometragem concluída com sucesso!\n');
        } catch (error) {
            console.error('❌ [CRON] Erro na atualização de quilometragem:', error);
        }
    });

    console.log('\n⏰ Cron job configurado: Atualização de quilometragem todos os dias às 00:30h\n');
});
