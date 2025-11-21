// Carregar variáveis de ambiente
require('dotenv').config();

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
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 5,  // Reduzido para evitar muitas conexões simultâneas
    queueLimit: 0,
    enableKeepAlive: true,
    keepAliveInitialDelay: 30000,
    connectTimeout: 10000,
    waitForConnectionsMillis: 30000
};

// Pool de conexões MySQL
const pool = mysql.createPool(dbConfig);

// Tratamento de erros da pool
pool.on('error', (err) => {
    console.error('❌ Erro no pool de MySQL:', err.message);
    if (err.code === 'PROTOCOL_CONNECTION_LOST') {
        console.error('   Database connection was closed.');
    }
    if (err.code === 'PROTOCOL_ENQUEUE_AFTER_FATAL_ERROR') {
        console.error('   Database fatal error, need to restart connection.');
    }
    if (err.code === 'PROTOCOL_ENQUEUE_AFTER_CLOSE') {
        console.error('   Database connection was closed.');
    }
});

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

// ===== PROXY ITURAN API (PARA FRONTEND) =====
// Frontend chama /api/quilometragem/ituranwebservice3/* que redireciona para API Ituran
// Isso resolve problemas de CORS sem necessidade de proxy separado
// NOTA: Este endpoint é ESPECÍFICO para Ituran (ituranwebservice3)
// Os endpoints legítimos de quilometragem vêm DEPOIS

app.get('/api/quilometragem/ituranwebservice3/:subroute(*)', async (req, res) => {
    try {
        const subroute = req.params.subroute || '';
        const queryString = new URLSearchParams(req.query).toString();
        const ituranUrl = `https://iweb.ituran.com.br/ituranwebservice3/${subroute}${queryString ? '?' + queryString : ''}`;

        console.log(`🔄 [PROXY] Redirecionando para Ituran: ${ituranUrl.substring(0, 100)}...`);

        const response = await fetch(ituranUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/xml, text/xml, */*',
                'Cache-Control': 'no-cache'
            },
            cache: 'no-store'
        });

        const text = await response.text();

        res.set('Content-Type', 'application/xml');
        res.set('Access-Control-Allow-Origin', '*');
        res.send(text);

    } catch (error) {
        console.error('❌ [PROXY] Erro:', error.message);
        res.status(500).set('Content-Type', 'application/xml');
        res.send(`<?xml version="1.0"?><Error>${error.message}</Error>`);
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
        console.log('🔍 Buscando veículos do banco de dados...');
        const [vehicles] = await pool.query(`
            SELECT
                v.Id as id,
                v.LicensePlate as plate,
                v.VehicleName as model,
                v.VehicleYear as year,
                v.DriverId as driverId,
                v.LastSpeed as speed,
                v.LastAddress as location,
                v.EngineStatus as engineStatus,
                v.IgnitionStatus as ignitionStatus,
                v.Renavam as renavam,
                v.ChassisNumber as chassisNumber,
                v.EnginePower as enginePower,
                v.EngineDisplacement as engineDisplacement,
                v.is_munck as isMunck
            FROM Vehicles v
            ORDER BY v.LicensePlate
        `);

        console.log(`✅ ${vehicles.length} veículos encontrados no banco`);

        // Formatar dados para o frontend
        const formattedVehicles = vehicles.map(v => {
            // Determinar status baseado no ignitionStatus
            let status = 'Ativo';
            if (v.ignitionStatus === 'ON') {
                status = 'Ativo';
            } else if (v.ignitionStatus === 'OFF') {
                status = 'Ativo';
            }

            return {
                id: v.id,
                plate: v.plate,
                model: v.model || 'N/A',
                brand: 'N/A', // Pode ser extraído do VehicleName se necessário
                year: v.year || 'N/A',
                mileage: 0, // Não temos odômetro na tabela atual
                status: status,
                color: 'N/A',
                fuel: 'N/A',
                type: v.isMunck ? 'Munck' : 'N/A',
                base: 'Serra', // Valor padrão
                location: v.location || 'Localização desconhecida',
                speed: v.speed || 0,
                driverId: v.driverId,
                renavam: v.renavam,
                chassisNumber: v.chassisNumber,
                enginePower: v.enginePower,
                engineDisplacement: v.engineDisplacement,
                engineStatus: v.engineStatus,
                ignitionStatus: v.ignitionStatus
            };
        });

        res.json(formattedVehicles);
    } catch (error) {
        console.error('❌ ERRO ao buscar veículos do banco:', error.message);
        console.error('Stack:', error.stack);
        console.log('⚠️ Usando dados mockados como fallback');
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

// ==================== SERVIÇOS (CATÁLOGO) ====================

// GET - Listar todos os serviços do catálogo
app.get('/api/services', async (req, res) => {
    try {
        const { tipo, ativo, ocorrencia } = req.query;

        let query = 'SELECT * FROM servicos WHERE 1=1';
        const params = [];

        if (tipo) {
            query += ' AND tipo = ?';
            params.push(tipo);
        }

        if (ativo !== undefined) {
            query += ' AND ativo = ?';
            params.push(ativo === 'true' ? 1 : 0);
        }

        if (ocorrencia) {
            query += ' AND ocorrencia_padrao = ?';
            params.push(ocorrencia);
        }

        query += ' ORDER BY codigo ASC';

        const [services] = await pool.query(query, params);

        res.json(services);
    } catch (error) {
        console.error('Erro ao buscar serviços:', error);
        res.status(500).json({ error: 'Erro ao buscar serviços' });
    }
});

// GET - Buscar serviço específico
app.get('/api/services/:id', async (req, res) => {
    try {
        const [service] = await pool.query(
            'SELECT * FROM servicos WHERE id = ?',
            [req.params.id]
        );

        if (service.length === 0) {
            return res.status(404).json({ error: 'Serviço não encontrado' });
        }

        res.json(service[0]);
    } catch (error) {
        console.error('Erro ao buscar serviço:', error);
        res.status(500).json({ error: 'Erro ao buscar serviço' });
    }
});

// POST - Criar novo serviço
app.post('/api/services', async (req, res) => {
    try {
        const { codigo, nome, tipo, valor_padrao, ocorrencia_padrao } = req.body;

        const [result] = await pool.query(`
            INSERT INTO servicos (codigo, nome, tipo, valor_padrao, ocorrencia_padrao, ativo, criado_em)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        `, [codigo, nome, tipo, valor_padrao, ocorrencia_padrao]);

        res.status(201).json({
            id: result.insertId,
            codigo,
            nome,
            tipo,
            valor_padrao,
            ocorrencia_padrao
        });
    } catch (error) {
        console.error('Erro ao criar serviço:', error);
        res.status(500).json({ error: 'Erro ao criar serviço' });
    }
});

// PUT - Atualizar serviço
app.put('/api/services/:id', async (req, res) => {
    try {
        const { codigo, nome, tipo, valor_padrao, ocorrencia_padrao, ativo } = req.body;

        await pool.query(`
            UPDATE servicos
            SET codigo = ?, nome = ?, tipo = ?, valor_padrao = ?,
                ocorrencia_padrao = ?, ativo = ?, atualizado_em = NOW()
            WHERE id = ?
        `, [codigo, nome, tipo, valor_padrao, ocorrencia_padrao, ativo, req.params.id]);

        res.json({ message: 'Serviço atualizado com sucesso' });
    } catch (error) {
        console.error('Erro ao atualizar serviço:', error);
        res.status(500).json({ error: 'Erro ao atualizar serviço' });
    }
});

// DELETE - Remover serviço (soft delete)
app.delete('/api/services/:id', async (req, res) => {
    try {
        await pool.query(
            'UPDATE servicos SET ativo = 0 WHERE id = ?',
            [req.params.id]
        );

        res.json({ message: 'Serviço desativado com sucesso' });
    } catch (error) {
        console.error('Erro ao desativar serviço:', error);
        res.status(500).json({ error: 'Erro ao desativar serviço' });
    }
});

// ==================== PLANOS DE MANUTENÇÃO ====================

// GET - Listar todos os planos de manutenção
app.get('/api/maintenance-plans', async (req, res) => {
    try {
        const { ativo } = req.query;

        let query = `
            SELECT
                p.*,
                COUNT(DISTINCT vp.vehicle_id) as total_veiculos,
                COUNT(DISTINCT ps.id) as total_servicos
            FROM FF_MaintenancePlans p
            LEFT JOIN FF_VehicleMaintenancePlans vp ON vp.plano_id = p.id AND vp.ativo = 1
            LEFT JOIN FF_MaintenancePlanServices ps ON ps.plano_id = p.id
            WHERE 1=1
        `;

        const params = [];

        if (ativo !== undefined) {
            query += ' AND p.ativo = ?';
            params.push(ativo === 'true' ? 1 : 0);
        }

        query += ' GROUP BY p.id ORDER BY p.nome_plano ASC';

        const [plans] = await pool.query(query, params);

        res.json(plans);
    } catch (error) {
        console.error('Erro ao buscar planos:', error);
        res.status(500).json({ error: 'Erro ao buscar planos de manutenção' });
    }
});

// GET - Buscar plano específico com serviços
app.get('/api/maintenance-plans/:id', async (req, res) => {
    try {
        const [plan] = await pool.query(
            'SELECT * FROM FF_MaintenancePlans WHERE id = ?',
            [req.params.id]
        );

        if (plan.length === 0) {
            return res.status(404).json({ error: 'Plano não encontrado' });
        }

        // Buscar serviços do plano
        const [services] = await pool.query(`
            SELECT ps.*, s.codigo, s.nome, s.tipo
            FROM FF_MaintenancePlanServices ps
            LEFT JOIN servicos s ON s.id = ps.servico_id
            WHERE ps.plano_id = ?
        `, [req.params.id]);

        res.json({
            ...plan[0],
            servicos: services
        });
    } catch (error) {
        console.error('Erro ao buscar plano:', error);
        res.status(500).json({ error: 'Erro ao buscar plano' });
    }
});

// POST - Criar novo plano de manutenção
app.post('/api/maintenance-plans', async (req, res) => {
    try {
        const {
            nome_plano,
            descricao,
            tipo_gatilho,
            intervalo_km,
            intervalo_dias,
            alertar_antecipacao_km,
            alertar_antecipacao_dias,
            servicos // Array de serviços [{servico_id, custo_estimado}]
        } = req.body;

        // Criar plano
        const [result] = await pool.query(`
            INSERT INTO FF_MaintenancePlans
                (nome_plano, descricao, tipo_gatilho, intervalo_km, intervalo_dias,
                 alertar_antecipacao_km, alertar_antecipacao_dias)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        `, [nome_plano, descricao, tipo_gatilho, intervalo_km, intervalo_dias,
            alertar_antecipacao_km, alertar_antecipacao_dias]);

        const planoId = result.insertId;

        // Adicionar serviços ao plano
        if (servicos && servicos.length > 0) {
            for (const servico of servicos) {
                await pool.query(`
                    INSERT INTO FF_MaintenancePlanServices
                        (plano_id, servico_id, custo_estimado)
                    VALUES (?, ?, ?)
                `, [planoId, servico.servico_id, servico.custo_estimado]);
            }
        }

        res.status(201).json({
            id: planoId,
            nome_plano,
            message: 'Plano criado com sucesso'
        });
    } catch (error) {
        console.error('Erro ao criar plano:', error);
        res.status(500).json({ error: 'Erro ao criar plano de manutenção' });
    }
});

// PUT - Atualizar plano
app.put('/api/maintenance-plans/:id', async (req, res) => {
    try {
        const {
            nome_plano,
            descricao,
            tipo_gatilho,
            intervalo_km,
            intervalo_dias,
            alertar_antecipacao_km,
            alertar_antecipacao_dias,
            ativo
        } = req.body;

        await pool.query(`
            UPDATE FF_MaintenancePlans
            SET nome_plano = ?, descricao = ?, tipo_gatilho = ?,
                intervalo_km = ?, intervalo_dias = ?,
                alertar_antecipacao_km = ?, alertar_antecipacao_dias = ?,
                ativo = ?, atualizado_em = NOW()
            WHERE id = ?
        `, [nome_plano, descricao, tipo_gatilho, intervalo_km, intervalo_dias,
            alertar_antecipacao_km, alertar_antecipacao_dias, ativo, req.params.id]);

        res.json({ message: 'Plano atualizado com sucesso' });
    } catch (error) {
        console.error('Erro ao atualizar plano:', error);
        res.status(500).json({ error: 'Erro ao atualizar plano' });
    }
});

// DELETE - Desativar plano
app.delete('/api/maintenance-plans/:id', async (req, res) => {
    try {
        await pool.query(
            'UPDATE FF_MaintenancePlans SET ativo = 0 WHERE id = ?',
            [req.params.id]
        );

        res.json({ message: 'Plano desativado com sucesso' });
    } catch (error) {
        console.error('Erro ao desativar plano:', error);
        res.status(500).json({ error: 'Erro ao desativar plano' });
    }
});

// ==================== ASSOCIAÇÃO VEÍCULO-PLANO ====================

// GET - Listar planos de um veículo
app.get('/api/vehicles/:id/maintenance-plans', async (req, res) => {
    try {
        const [plans] = await pool.query(`
            SELECT
                vp.*,
                p.nome_plano,
                p.tipo_gatilho,
                p.intervalo_km,
                p.intervalo_dias,
                p.alertar_antecipacao_km,
                p.alertar_antecipacao_dias
            FROM FF_VehicleMaintenancePlans vp
            JOIN FF_MaintenancePlans p ON p.id = vp.plano_id
            WHERE vp.vehicle_id = ? AND vp.ativo = 1
            ORDER BY p.nome_plano ASC
        `, [req.params.id]);

        res.json(plans);
    } catch (error) {
        console.error('Erro ao buscar planos do veículo:', error);
        res.status(500).json({ error: 'Erro ao buscar planos do veículo' });
    }
});

// POST - Associar plano a veículo
app.post('/api/vehicles/:id/maintenance-plans', async (req, res) => {
    try {
        const vehicleId = req.params.id;
        const { plano_id, km_inicial, data_inicial } = req.body;

        // Buscar informações do veículo
        const [vehicle] = await pool.query(
            'SELECT LicensePlate FROM Vehicles WHERE Id = ?',
            [vehicleId]
        );

        if (vehicle.length === 0) {
            return res.status(404).json({ error: 'Veículo não encontrado' });
        }

        // Buscar km atual do veículo (se não especificado, buscar da telemetria)
        let kmAtual = km_inicial;

        if (!kmAtual) {
            // Buscar km atual da telemetria (último valor não-zero)
            const [telemetria] = await pool.query(`
                SELECT km_final
                FROM Telemetria_Diaria
                WHERE LicensePlate = ? AND km_final > 0
                ORDER BY data DESC
                LIMIT 1
            `, [vehicle[0].LicensePlate]);

            kmAtual = telemetria.length > 0 && telemetria[0].km_final ? telemetria[0].km_final : 0;
        }

        const dataAtual = data_inicial || new Date().toISOString().split('T')[0];

        // Buscar informações do plano
        const [plan] = await pool.query(
            'SELECT * FROM FF_MaintenancePlans WHERE id = ?',
            [plano_id]
        );

        if (plan.length === 0) {
            return res.status(404).json({ error: 'Plano não encontrado' });
        }

        // Calcular próxima execução
        let proximaExecucaoKm = null;
        let proximaExecucaoData = null;

        if (plan[0].tipo_gatilho.includes('Quilometragem')) {
            proximaExecucaoKm = kmAtual + plan[0].intervalo_km;
        }

        if (plan[0].tipo_gatilho.includes('Tempo')) {
            const data = new Date(dataAtual);
            data.setDate(data.getDate() + plan[0].intervalo_dias);
            proximaExecucaoData = data.toISOString().split('T')[0];
        }

        // Criar associação
        const [result] = await pool.query(`
            INSERT INTO FF_VehicleMaintenancePlans
                (vehicle_id, plano_id, km_inicial, data_inicial,
                 proxima_execucao_km, proxima_execucao_data)
            VALUES (?, ?, ?, ?, ?, ?)
        `, [vehicleId, plano_id, kmAtual, dataAtual, proximaExecucaoKm, proximaExecucaoData]);

        res.status(201).json({
            id: result.insertId,
            message: 'Plano associado ao veículo com sucesso',
            proxima_execucao_km: proximaExecucaoKm,
            proxima_execucao_data: proximaExecucaoData
        });
    } catch (error) {
        console.error('Erro ao associar plano:', error);
        res.status(500).json({ error: 'Erro ao associar plano ao veículo' });
    }
});

// DELETE - Desassociar plano de veículo
app.delete('/api/vehicles/:id/maintenance-plans/:planId', async (req, res) => {
    try {
        await pool.query(`
            UPDATE FF_VehicleMaintenancePlans
            SET ativo = 0
            WHERE vehicle_id = ? AND plano_id = ?
        `, [req.params.id, req.params.planId]);

        res.json({ message: 'Plano desassociado do veículo com sucesso' });
    } catch (error) {
        console.error('Erro ao desassociar plano:', error);
        res.status(500).json({ error: 'Erro ao desassociar plano' });
    }
});

// POST - Registrar execução de manutenção (atualiza próxima data)
app.post('/api/vehicles/:id/maintenance-plans/:planId/complete', async (req, res) => {
    try {
        const { km_execucao, data_execucao } = req.body;

        // Buscar associação e plano
        const [association] = await pool.query(`
            SELECT vp.*, p.*
            FROM FF_VehicleMaintenancePlans vp
            JOIN FF_MaintenancePlans p ON p.id = vp.plano_id
            WHERE vp.vehicle_id = ? AND vp.plano_id = ?
        `, [req.params.id, req.params.planId]);

        if (association.length === 0) {
            return res.status(404).json({ error: 'Associação não encontrada' });
        }

        const assoc = association[0];

        // Calcular próxima execução
        let proximaExecucaoKm = null;
        let proximaExecucaoData = null;

        if (assoc.tipo_gatilho.includes('Quilometragem')) {
            proximaExecucaoKm = km_execucao + assoc.intervalo_km;
        }

        if (assoc.tipo_gatilho.includes('Tempo')) {
            const data = new Date(data_execucao);
            data.setDate(data.getDate() + assoc.intervalo_dias);
            proximaExecucaoData = data.toISOString().split('T')[0];
        }

        // Atualizar associação
        await pool.query(`
            UPDATE FF_VehicleMaintenancePlans
            SET ultima_execucao_km = ?,
                ultima_execucao_data = ?,
                proxima_execucao_km = ?,
                proxima_execucao_data = ?,
                atualizado_em = NOW()
            WHERE vehicle_id = ? AND plano_id = ?
        `, [km_execucao, data_execucao, proximaExecucaoKm, proximaExecucaoData,
            req.params.id, req.params.planId]);

        res.json({
            message: 'Manutenção registrada com sucesso',
            proxima_execucao_km: proximaExecucaoKm,
            proxima_execucao_data: proximaExecucaoData
        });
    } catch (error) {
        console.error('Erro ao registrar execução:', error);
        res.status(500).json({ error: 'Erro ao registrar execução de manutenção' });
    }
});

// ==================== ALERTAS DE MANUTENÇÃO ====================

// GET - Listar alertas de manutenção ativos
app.get('/api/maintenance-alerts', async (req, res) => {
    try {
        const { status, prioridade, vehicle_id } = req.query;

        let query = `
            SELECT
                a.*,
                v.LicensePlate,
                v.VehicleName,
                p.nome_plano
            FROM FF_MaintenanceAlerts a
            JOIN Vehicles v ON v.Id = a.vehicle_id
            JOIN FF_MaintenancePlans p ON p.id = a.plano_id
            WHERE 1=1
        `;

        const params = [];

        if (status) {
            query += ' AND a.status = ?';
            params.push(status);
        } else {
            query += ' AND a.status = "Ativo"';
        }

        if (prioridade) {
            query += ' AND a.prioridade = ?';
            params.push(prioridade);
        }

        if (vehicle_id) {
            query += ' AND a.vehicle_id = ?';
            params.push(vehicle_id);
        }

        query += ' ORDER BY a.prioridade DESC, a.criado_em DESC';

        const [alerts] = await pool.query(query, params);

        res.json(alerts);
    } catch (error) {
        console.error('Erro ao buscar alertas:', error);
        res.status(500).json({ error: 'Erro ao buscar alertas de manutenção' });
    }
});

// PUT - Marcar alerta como visualizado
app.put('/api/maintenance-alerts/:id/viewed', async (req, res) => {
    try {
        await pool.query(`
            UPDATE FF_MaintenanceAlerts
            SET status = 'Visualizado', visualizado_em = NOW()
            WHERE id = ?
        `, [req.params.id]);

        res.json({ message: 'Alerta marcado como visualizado' });
    } catch (error) {
        console.error('Erro ao atualizar alerta:', error);
        res.status(500).json({ error: 'Erro ao atualizar alerta' });
    }
});

// PUT - Resolver alerta
app.put('/api/maintenance-alerts/:id/resolve', async (req, res) => {
    try {
        await pool.query(`
            UPDATE FF_MaintenanceAlerts
            SET status = 'Resolvido', resolvido_em = NOW()
            WHERE id = ?
        `, [req.params.id]);

        res.json({ message: 'Alerta resolvido' });
    } catch (error) {
        console.error('Erro ao resolver alerta:', error);
        res.status(500).json({ error: 'Erro ao resolver alerta' });
    }
});

// POST - Executar verificação manual de alertas (para testes)
app.post('/api/maintenance-alerts/check-now', async (req, res) => {
    try {
        console.log('🔔 Executando verificação manual de alertas...');

        // Buscar todos os veículos com planos ativos
        const [vehiclePlans] = await pool.query(`
            SELECT
                vp.*,
                v.LicensePlate,
                v.VehicleName,
                p.nome_plano,
                p.tipo_gatilho,
                p.intervalo_km,
                p.intervalo_dias,
                p.alertar_antecipacao_km,
                p.alertar_antecipacao_dias
            FROM FF_VehicleMaintenancePlans vp
            JOIN Vehicles v ON v.Id = vp.vehicle_id
            JOIN FF_MaintenancePlans p ON p.id = vp.plano_id
            WHERE vp.ativo = 1 AND p.ativo = 1
        `);

        let alertasGerados = 0;
        const detalhes = [];

        for (const vp of vehiclePlans) {
            // Buscar km atual do veículo (último valor não-zero)
            const [telemetria] = await pool.query(`
                SELECT km_final
                FROM Telemetria_Diaria
                WHERE LicensePlate = ? AND km_final > 0
                ORDER BY data DESC
                LIMIT 1
            `, [vp.LicensePlate]);

            const kmAtual = telemetria.length > 0 ? telemetria[0].km_final : 0;
            const dataAtual = new Date();

            let precisaAlerta = false;
            let tipoAlerta = null;
            let mensagem = '';
            let prioridade = 'Média';

            // Verificar alerta por quilometragem
            if (vp.tipo_gatilho.includes('Quilometragem') && vp.proxima_execucao_km) {
                const kmAlerta = vp.proxima_execucao_km - (vp.alertar_antecipacao_km || 0);

                if (kmAtual >= vp.proxima_execucao_km) {
                    precisaAlerta = true;
                    tipoAlerta = 'Quilometragem';
                    prioridade = 'Crítica';
                    mensagem = `${vp.nome_plano} VENCIDA! Veículo ${vp.LicensePlate} atingiu ${kmAtual} km (programado: ${vp.proxima_execucao_km} km)`;
                } else if (kmAtual >= kmAlerta) {
                    precisaAlerta = true;
                    tipoAlerta = 'Quilometragem';
                    prioridade = 'Alta';
                    const kmRestantes = vp.proxima_execucao_km - kmAtual;
                    mensagem = `${vp.nome_plano} próxima! Veículo ${vp.LicensePlate} faltam ${kmRestantes} km`;
                }
            }

            // Verificar alerta por data
            if (vp.tipo_gatilho.includes('Tempo') && vp.proxima_execucao_data) {
                const dataProxima = new Date(vp.proxima_execucao_data);
                const diasAntecipacao = vp.alertar_antecipacao_dias || 0;
                const dataAlerta = new Date(dataProxima);
                dataAlerta.setDate(dataAlerta.getDate() - diasAntecipacao);

                if (dataAtual >= dataProxima) {
                    precisaAlerta = true;
                    tipoAlerta = tipoAlerta ? 'Ambos' : 'Data';
                    prioridade = 'Crítica';
                    mensagem = `${vp.nome_plano} VENCIDA! Veículo ${vp.LicensePlate} - Data programada: ${vp.proxima_execucao_data}`;
                } else if (dataAtual >= dataAlerta) {
                    if (!precisaAlerta) {
                        precisaAlerta = true;
                        tipoAlerta = 'Data';
                        prioridade = 'Alta';
                        const diasRestantes = Math.ceil((dataProxima - dataAtual) / (1000 * 60 * 60 * 24));
                        mensagem = `${vp.nome_plano} próxima! Veículo ${vp.LicensePlate} em ${diasRestantes} dias`;
                    }
                }
            }

            if (precisaAlerta) {
                const [alertaExistente] = await pool.query(`
                    SELECT id FROM FF_MaintenanceAlerts
                    WHERE vehicle_id = ? AND plano_id = ? AND status = 'Ativo'
                    LIMIT 1
                `, [vp.vehicle_id, vp.plano_id]);

                if (alertaExistente.length === 0) {
                    await pool.query(`
                        INSERT INTO FF_MaintenanceAlerts
                            (vehicle_id, plano_id, vehicle_maintenance_plan_id,
                             tipo_alerta, mensagem, km_atual, km_programado,
                             data_programada, prioridade, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ativo')
                    `, [
                        vp.vehicle_id, vp.plano_id, vp.id, tipoAlerta, mensagem,
                        kmAtual, vp.proxima_execucao_km, vp.proxima_execucao_data, prioridade
                    ]);

                    alertasGerados++;
                }

                detalhes.push({
                    veiculo: vp.LicensePlate,
                    plano: vp.nome_plano,
                    km_atual: kmAtual,
                    km_programado: vp.proxima_execucao_km,
                    data_programada: vp.proxima_execucao_data,
                    mensagem,
                    prioridade,
                    novo_alerta: alertaExistente.length === 0
                });
            }
        }

        res.json({
            message: 'Verificação concluída',
            total_planos_verificados: vehiclePlans.length,
            alertas_gerados: alertasGerados,
            detalhes
        });

    } catch (error) {
        console.error('Erro ao verificar alertas:', error);
        res.status(500).json({ error: 'Erro ao verificar alertas' });
    }
});

// ==================== ITENS DE ORDEM DE SERVIÇO ====================

// GET - Buscar itens de uma OS
app.get('/api/workorders/:id/items', async (req, res) => {
    try {
        const [items] = await pool.query(`
            SELECT
                oi.*,
                s.codigo as servico_codigo,
                s.nome as servico_nome
            FROM ordemservico_itens oi
            LEFT JOIN servicos s ON s.id = oi.servico_id
            WHERE oi.ordemservico_id = ?
            ORDER BY oi.id ASC
        `, [req.params.id]);

        res.json(items);
    } catch (error) {
        console.error('Erro ao buscar itens:', error);
        res.status(500).json({ error: 'Erro ao buscar itens da OS' });
    }
});

// POST - Adicionar item à OS
app.post('/api/workorders/:id/items', async (req, res) => {
    try {
        const { servico_id, descricao, tipo, quantidade, valor_unitario } = req.body;
        const valor_total = quantidade * valor_unitario;

        const [result] = await pool.query(`
            INSERT INTO ordemservico_itens
                (ordemservico_id, servico_id, descricao, tipo, quantidade, valor_unitario, valor_total)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        `, [req.params.id, servico_id, descricao, tipo, quantidade, valor_unitario, valor_total]);

        res.status(201).json({
            id: result.insertId,
            message: 'Item adicionado à OS com sucesso'
        });
    } catch (error) {
        console.error('Erro ao adicionar item:', error);
        res.status(500).json({ error: 'Erro ao adicionar item à OS' });
    }
});

// DELETE - Remover item da OS
app.delete('/api/workorders/:osId/items/:itemId', async (req, res) => {
    try {
        await pool.query(
            'DELETE FROM ordemservico_itens WHERE id = ? AND ordemservico_id = ?',
            [req.params.itemId, req.params.osId]
        );

        res.json({ message: 'Item removido da OS com sucesso' });
    } catch (error) {
        console.error('Erro ao remover item:', error);
        res.status(500).json({ error: 'Erro ao remover item da OS' });
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

// ==========================================
// API DE MODELOS DE VEÍCULOS
// ==========================================

// GET - Listar todos os modelos
app.get('/api/modelos', async (req, res) => {
    try {
        console.log('🔍 Buscando modelos de veículos...');

        const [modelos] = await pool.query(`
            SELECT
                MIN(m.id) as id,
                m.marca,
                m.modelo,
                m.ano,
                m.tipo,
                m.motor,
                m.observacoes,
                MIN(m.created_at) as created_at,
                MAX(m.updated_at) as updated_at,
                (SELECT COUNT(*) FROM Vehicles v WHERE v.VehicleName LIKE CONCAT('%', m.modelo, '%')) as qtdVeiculos
            FROM FF_VehicleModels m
            GROUP BY m.marca, m.modelo, m.ano, m.tipo, m.motor, m.observacoes
            ORDER BY m.marca, m.modelo, m.ano
        `);

        console.log(`✅ ${modelos.length} modelos únicos encontrados`);
        res.json(modelos);
    } catch (error) {
        console.error('❌ Erro ao buscar modelos:', error);
        res.status(500).json({ error: 'Erro ao buscar modelos' });
    }
});

// GET - Buscar modelo por ID
app.get('/api/modelos/:id', async (req, res) => {
    try {
        const { id } = req.params;
        console.log(`🔍 Buscando modelo ID ${id}...`);

        const [modelos] = await pool.query(`
            SELECT
                m.id,
                m.marca,
                m.modelo,
                m.ano,
                m.tipo,
                m.motor,
                m.observacoes,
                m.created_at,
                m.updated_at,
                (SELECT COUNT(*) FROM Vehicles v WHERE v.VehicleName LIKE CONCAT('%', m.modelo, '%')) as qtdVeiculos
            FROM FF_VehicleModels m
            WHERE m.id = ?
        `, [id]);

        if (modelos.length === 0) {
            return res.status(404).json({ error: 'Modelo não encontrado' });
        }

        console.log('✅ Modelo encontrado');
        res.json(modelos[0]);
    } catch (error) {
        console.error('❌ Erro ao buscar modelo:', error);
        res.status(500).json({ error: 'Erro ao buscar modelo' });
    }
});

// POST - Criar novo modelo
app.post('/api/modelos', async (req, res) => {
    try {
        const { marca, modelo, ano, tipo, motor, observacoes } = req.body;

        console.log('➕ Criando novo modelo:', { marca, modelo, ano, tipo });

        // Validação básica
        if (!marca || !modelo || !ano || !tipo) {
            return res.status(400).json({ error: 'Marca, modelo, ano e tipo são obrigatórios' });
        }

        const [result] = await pool.query(`
            INSERT INTO FF_VehicleModels (marca, modelo, ano, tipo, motor, observacoes)
            VALUES (?, ?, ?, ?, ?, ?)
        `, [marca, modelo, ano, tipo, motor || null, observacoes || null]);

        console.log(`✅ Modelo criado com ID ${result.insertId}`);

        // Buscar o modelo criado para retornar
        const [novoModelo] = await pool.query(`
            SELECT * FROM FF_VehicleModels WHERE id = ?
        `, [result.insertId]);

        res.status(201).json(novoModelo[0]);
    } catch (error) {
        console.error('❌ Erro ao criar modelo:', error);

        // Verificar se é erro de duplicação
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({ error: 'Já existe um modelo com essa combinação de marca, modelo e ano' });
        }

        res.status(500).json({ error: 'Erro ao criar modelo' });
    }
});

// PUT - Atualizar modelo existente
app.put('/api/modelos/:id', async (req, res) => {
    try {
        const { id } = req.params;
        const { marca, modelo, ano, tipo, motor, observacoes } = req.body;

        console.log(`✏️ Atualizando modelo ID ${id}...`);

        // Validação básica
        if (!marca || !modelo || !ano || !tipo) {
            return res.status(400).json({ error: 'Marca, modelo, ano e tipo são obrigatórios' });
        }

        // Verificar se o modelo existe
        const [modeloExiste] = await pool.query('SELECT id FROM FF_VehicleModels WHERE id = ?', [id]);
        if (modeloExiste.length === 0) {
            return res.status(404).json({ error: 'Modelo não encontrado' });
        }

        await pool.query(`
            UPDATE FF_VehicleModels
            SET marca = ?, modelo = ?, ano = ?, tipo = ?, motor = ?, observacoes = ?
            WHERE id = ?
        `, [marca, modelo, ano, tipo, motor || null, observacoes || null, id]);

        console.log('✅ Modelo atualizado com sucesso');

        // Buscar o modelo atualizado para retornar
        const [modeloAtualizado] = await pool.query(`
            SELECT * FROM FF_VehicleModels WHERE id = ?
        `, [id]);

        res.json(modeloAtualizado[0]);
    } catch (error) {
        console.error('❌ Erro ao atualizar modelo:', error);

        // Verificar se é erro de duplicação
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({ error: 'Já existe um modelo com essa combinação de marca, modelo e ano' });
        }

        res.status(500).json({ error: 'Erro ao atualizar modelo' });
    }
});

// DELETE - Excluir modelo
app.delete('/api/modelos/:id', async (req, res) => {
    try {
        const { id } = req.params;

        console.log(`🗑️ Excluindo modelo ID ${id}...`);

        // Verificar se o modelo existe
        const [modeloExiste] = await pool.query('SELECT id, marca, modelo FROM FF_VehicleModels WHERE id = ?', [id]);
        if (modeloExiste.length === 0) {
            return res.status(404).json({ error: 'Modelo não encontrado' });
        }

        // Verificar se há veículos associados
        const modelo = modeloExiste[0];
        const [veiculosAssociados] = await pool.query(
            'SELECT COUNT(*) as count FROM Vehicles WHERE VehicleName LIKE ?',
            [`%${modelo.modelo}%`]
        );

        if (veiculosAssociados[0].count > 0) {
            return res.status(409).json({
                error: `Não é possível excluir este modelo pois existem ${veiculosAssociados[0].count} veículo(s) associado(s) a ele`
            });
        }

        await pool.query('DELETE FROM FF_VehicleModels WHERE id = ?', [id]);

        console.log('✅ Modelo excluído com sucesso');
        res.json({ message: 'Modelo excluído com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao excluir modelo:', error);
        res.status(500).json({ error: 'Erro ao excluir modelo' });
    }
});

// ==========================================
// API DE ITENS DE PLANO DE MANUTENÇÃO
// ==========================================

// GET - Listar itens de plano por modelo
app.get('/api/maintenance-plan-items', async (req, res) => {
    try {
        const { modelo_id } = req.query;

        if (!modelo_id) {
            return res.status(400).json({ error: 'modelo_id é obrigatório' });
        }

        console.log(`🔍 Buscando itens do plano para modelo ${modelo_id}...`);

        // Primeiro buscar o nome do modelo
        const [modelos] = await pool.query('SELECT marca, modelo FROM FF_VehicleModels WHERE id = ?', [modelo_id]);

        if (modelos.length === 0) {
            return res.json([]);
        }

        const modeloNome = `${modelos[0].marca} ${modelos[0].modelo}`;
        console.log(`📋 Modelo: ${modeloNome}`);

        // Buscar da tabela Planos_Manutenção
        const [items] = await pool.query(`
            SELECT
                id,
                modelo_carro,
                descricao_titulo as nome,
                km_recomendado as intervalo_km,
                intervalo_tempo,
                custo_estimado,
                CASE
                    WHEN criticidade = 'Crítica' OR criticidade = 'Alta' THEN 'alta'
                    WHEN criticidade = 'Baixa' THEN 'baixa'
                    ELSE 'media'
                END as criticidade,
                descricao_observacao as descricao
            FROM Planos_Manutenção
            WHERE modelo_carro LIKE ?
            ORDER BY km_recomendado ASC
        `, [`%${modeloNome}%`]);

        console.log(`✅ ${items.length} itens encontrados`);

        // Converter intervalo_tempo para intervalo_meses para compatibilidade
        const itemsFormatados = items.map(item => {
            let intervaloMeses = null;
            if (item.intervalo_tempo) {
                const texto = item.intervalo_tempo.toLowerCase();
                if (texto.includes('ano')) {
                    const match = texto.match(/(\d+)\s+ano/);
                    if (match) intervaloMeses = parseInt(match[1]) * 12;
                } else if (texto.includes('mês') || texto.includes('mes')) {
                    const match = texto.match(/(\d+)\s+m[eê]s/);
                    if (match) intervaloMeses = parseInt(match[1]);
                }
            }
            return {
                ...item,
                intervalo_meses: intervaloMeses
            };
        });

        res.json(itemsFormatados);
    } catch (error) {
        console.error('❌ Erro ao buscar itens do plano:', error);
        res.status(500).json({ error: 'Erro ao buscar itens do plano' });
    }
});

// POST - Criar novo item de plano
app.post('/api/maintenance-plan-items', async (req, res) => {
    try {
        const { modelo_id, nome, intervalo_km, intervalo_meses, custo_estimado, criticidade, descricao } = req.body;

        if (!modelo_id || !nome || !custo_estimado || !criticidade) {
            return res.status(400).json({ error: 'Campos obrigatórios: modelo_id, nome, custo_estimado, criticidade' });
        }

        console.log('📝 Criando novo item de plano...');

        // Buscar nome do modelo
        const [modelos] = await pool.query('SELECT marca, modelo FROM FF_VehicleModels WHERE id = ?', [modelo_id]);
        if (modelos.length === 0) {
            return res.status(400).json({ error: 'Modelo não encontrado' });
        }

        const modeloNome = `${modelos[0].marca} ${modelos[0].modelo}`;

        // Converter criticidade
        let criticidadeEnum = 'Média';
        if (criticidade === 'alta') criticidadeEnum = 'Alta';
        else if (criticidade === 'baixa') criticidadeEnum = 'Baixa';

        // Converter intervalo_meses para texto
        let intervaloTempo = null;
        if (intervalo_meses) {
            if (intervalo_meses < 12) {
                intervaloTempo = `${intervalo_meses} ${intervalo_meses === 1 ? 'mês' : 'meses'}`;
            } else {
                const anos = Math.floor(intervalo_meses / 12);
                intervaloTempo = `${anos} ${anos === 1 ? 'ano' : 'anos'}`;
            }
        }

        const [result] = await pool.query(`
            INSERT INTO Planos_Manutenção
            (modelo_carro, descricao_titulo, km_recomendado, intervalo_tempo, custo_estimado, criticidade, descricao_observacao)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        `, [modeloNome, nome, intervalo_km || null, intervaloTempo, custo_estimado, criticidadeEnum, descricao || null]);

        console.log('✅ Item criado com sucesso');
        res.status(201).json({
            id: result.insertId,
            message: 'Item criado com sucesso'
        });
    } catch (error) {
        console.error('❌ Erro ao criar item:', error);
        res.status(500).json({ error: 'Erro ao criar item de plano' });
    }
});

// PUT - Atualizar item de plano
app.put('/api/maintenance-plan-items/:id', async (req, res) => {
    try {
        const { id } = req.params;
        const { nome, intervalo_km, intervalo_meses, custo_estimado, criticidade, descricao } = req.body;

        if (!nome || !custo_estimado || !criticidade) {
            return res.status(400).json({ error: 'Campos obrigatórios: nome, custo_estimado, criticidade' });
        }

        console.log(`📝 Atualizando item ${id}...`);

        // Converter criticidade
        let criticidadeEnum = 'Média';
        if (criticidade === 'alta') criticidadeEnum = 'Alta';
        else if (criticidade === 'baixa') criticidadeEnum = 'Baixa';

        // Converter intervalo_meses para texto
        let intervaloTempo = null;
        if (intervalo_meses) {
            if (intervalo_meses < 12) {
                intervaloTempo = `${intervalo_meses} ${intervalo_meses === 1 ? 'mês' : 'meses'}`;
            } else {
                const anos = Math.floor(intervalo_meses / 12);
                intervaloTempo = `${anos} ${anos === 1 ? 'ano' : 'anos'}`;
            }
        }

        await pool.query(`
            UPDATE Planos_Manutenção
            SET descricao_titulo = ?, km_recomendado = ?, intervalo_tempo = ?,
                custo_estimado = ?, criticidade = ?, descricao_observacao = ?
            WHERE id = ?
        `, [nome, intervalo_km || null, intervaloTempo, custo_estimado, criticidadeEnum, descricao || null, id]);

        console.log('✅ Item atualizado com sucesso');
        res.json({ message: 'Item atualizado com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao atualizar item:', error);
        res.status(500).json({ error: 'Erro ao atualizar item' });
    }
});

// DELETE - Excluir item de plano
app.delete('/api/maintenance-plan-items/:id', async (req, res) => {
    try {
        const { id } = req.params;
        console.log(`🗑️ Excluindo item ${id}...`);

        await pool.query('DELETE FROM Planos_Manutenção WHERE id = ?', [id]);

        console.log('✅ Item excluído com sucesso');
        res.json({ message: 'Item excluído com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao excluir item:', error);
        res.status(500).json({ error: 'Erro ao excluir item' });
    }
});

// ==================== API DE ORDENS DE SERVIÇO ====================

// GET - Listar todas as ordens de serviço
app.get('/api/ordens-servico', async (req, res) => {
    try {
        const [ordens] = await pool.query(`
            SELECT
                os.id,
                os.ordem_numero,
                os.placa_veiculo as placa,
                os.km_veiculo,
                os.responsavel,
                os.status,
                os.observacoes,
                os.data_criacao as data_abertura,
                os.data_finalizacao as data_conclusao,
                os.ocorrencia as tipo_servico,
                COALESCE(
                    (SELECT SUM(valor_total) FROM ordemservico_itens WHERE ordem_numero = os.ordem_numero),
                    0
                ) as custo_total
            FROM ordemservico os
            ORDER BY
                CASE os.status
                    WHEN 'Aberta' THEN 1
                    WHEN 'Diagnóstico' THEN 2
                    WHEN 'Orçamento' THEN 3
                    WHEN 'Execução' THEN 4
                    WHEN 'Finalizada' THEN 5
                    WHEN 'Cancelada' THEN 6
                END,
                os.data_criacao DESC
        `);

        // Mapear status para formato esperado pelo frontend
        const ordensFormatadas = ordens.map(os => ({
            ...os,
            status: mapearStatus(os.status),
            prioridade: os.ocorrencia === 'Corretiva' ? 'Alta' : 'Normal'
        }));

        res.json(ordensFormatadas);
    } catch (error) {
        console.error('❌ Erro ao listar ordens de serviço:', error);
        res.status(500).json({ error: 'Erro ao listar ordens de serviço' });
    }
});

// Função para mapear status da tabela para o frontend
function mapearStatus(status) {
    const mapa = {
        'Aberta': 'Pendente',
        'Diagnóstico': 'Pendente',
        'Orçamento': 'Pendente',
        'Execução': 'Em Andamento',
        'Finalizada': 'Concluída',
        'Cancelada': 'Cancelada'
    };
    return mapa[status] || status;
}

// GET - Buscar ordem de serviço por ID
app.get('/api/ordens-servico/:id', async (req, res) => {
    try {
        const { id } = req.params;
        const [ordens] = await pool.query('SELECT * FROM ordemservico WHERE id = ?', [id]);

        if (ordens.length === 0) {
            return res.status(404).json({ error: 'Ordem de serviço não encontrada' });
        }

        // Buscar itens da OS
        const [itens] = await pool.query(
            'SELECT * FROM ordemservico_itens WHERE ordem_numero = ?',
            [ordens[0].ordem_numero]
        );

        res.json({ ...ordens[0], itens });
    } catch (error) {
        console.error('❌ Erro ao buscar ordem de serviço:', error);
        res.status(500).json({ error: 'Erro ao buscar ordem de serviço' });
    }
});

// POST - Criar nova ordem de serviço
app.post('/api/ordens-servico', async (req, res) => {
    try {
        const { placa_veiculo, km_veiculo, responsavel, status, observacoes, ocorrencia } = req.body;

        if (!placa_veiculo) {
            return res.status(400).json({ error: 'Campo obrigatório: placa_veiculo' });
        }

        // Gerar número da ordem
        const [lastOrder] = await pool.query('SELECT MAX(id) as maxId FROM ordemservico');
        const nextId = (lastOrder[0].maxId || 0) + 1;
        const ordem_numero = `OS-${String(nextId).padStart(6, '0')}`;

        const [result] = await pool.query(
            `INSERT INTO ordemservico
            (ordem_numero, placa_veiculo, km_veiculo, responsavel, status, observacoes, ocorrencia)
            VALUES (?, ?, ?, ?, ?, ?, ?)`,
            [ordem_numero, placa_veiculo, km_veiculo || 0, responsavel || null,
             status || 'Aberta', observacoes || null, ocorrencia || 'Corretiva']
        );

        console.log('✅ Ordem de serviço criada:', ordem_numero);
        res.status(201).json({ id: result.insertId, ordem_numero, message: 'Ordem de serviço criada com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao criar ordem de serviço:', error);
        res.status(500).json({ error: 'Erro ao criar ordem de serviço' });
    }
});

// PUT - Atualizar ordem de serviço
app.put('/api/ordens-servico/:id', async (req, res) => {
    try {
        const { id } = req.params;
        const { placa_veiculo, km_veiculo, responsavel, status, observacoes, ocorrencia } = req.body;

        // Atualizar datas conforme status
        let updateFields = 'placa_veiculo = ?, km_veiculo = ?, responsavel = ?, status = ?, observacoes = ?, ocorrencia = ?';
        let params = [placa_veiculo, km_veiculo || 0, responsavel || null, status || 'Aberta', observacoes || null, ocorrencia || 'Corretiva'];

        if (status === 'Diagnóstico') {
            updateFields += ', data_diagnostico = NOW()';
        } else if (status === 'Orçamento') {
            updateFields += ', data_orcamento = NOW()';
        } else if (status === 'Execução') {
            updateFields += ', data_execucao = NOW()';
        } else if (status === 'Finalizada') {
            updateFields += ', data_finalizacao = NOW()';
        }

        await pool.query(
            `UPDATE ordemservico SET ${updateFields} WHERE id = ?`,
            [...params, id]
        );

        console.log('✅ Ordem de serviço atualizada:', id);
        res.json({ message: 'Ordem de serviço atualizada com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao atualizar ordem de serviço:', error);
        res.status(500).json({ error: 'Erro ao atualizar ordem de serviço' });
    }
});

// DELETE - Excluir ordem de serviço
// DELETE por ordem_numero (query param)
app.delete('/api/ordens-servico', async (req, res) => {
    try {
        const { ordem_numero } = req.query;

        if (!ordem_numero) {
            return res.status(400).json({ error: 'Número da OS é obrigatório' });
        }

        // Excluir itens primeiro
        await pool.query('DELETE FROM ordemservico_itens WHERE ordem_numero = ?', [ordem_numero]);

        // Excluir a OS
        const [result] = await pool.query('DELETE FROM ordemservico WHERE ordem_numero = ?', [ordem_numero]);

        if (result.affectedRows === 0) {
            return res.status(404).json({ error: 'Ordem de serviço não encontrada' });
        }

        console.log('✅ Ordem de serviço excluída:', ordem_numero);
        res.json({ message: 'Ordem de serviço excluída com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao excluir ordem de serviço:', error);
        res.status(500).json({ error: 'Erro ao excluir ordem de serviço' });
    }
});

// DELETE por ID (path param)
app.delete('/api/ordens-servico/:id', async (req, res) => {
    try {
        const { id } = req.params;

        // Buscar ordem_numero para excluir itens
        const [ordem] = await pool.query('SELECT ordem_numero FROM ordemservico WHERE id = ?', [id]);
        if (ordem.length > 0) {
            await pool.query('DELETE FROM ordemservico_itens WHERE ordem_numero = ?', [ordem[0].ordem_numero]);
        }

        await pool.query('DELETE FROM ordemservico WHERE id = ?', [id]);

        console.log('✅ Ordem de serviço excluída:', id);
        res.json({ message: 'Ordem de serviço excluída com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao excluir ordem de serviço:', error);
        res.status(500).json({ error: 'Erro ao excluir ordem de serviço' });
    }
});

// ==================== API DE PEÇAS ====================

// GET - Listar todas as peças
app.get('/api/pecas', async (req, res) => {
    try {
        // Tenta com ativo, se falhar tenta sem
        let pecas;
        try {
            [pecas] = await pool.query('SELECT * FROM FF_Pecas WHERE ativo = 1 ORDER BY nome ASC');
        } catch (err) {
            // Se a coluna ativo não existe, busca todas
            [pecas] = await pool.query('SELECT * FROM FF_Pecas ORDER BY nome ASC');
        }
        res.json(pecas);
    } catch (error) {
        console.error('❌ Erro ao listar peças:', error);
        res.status(500).json({ error: 'Erro ao listar peças' });
    }
});

// GET - Buscar peça por ID
app.get('/api/pecas/:id', async (req, res) => {
    try {
        const { id } = req.params;
        const [pecas] = await pool.query('SELECT * FROM FF_Pecas WHERE id = ?', [id]);

        if (pecas.length === 0) {
            return res.status(404).json({ error: 'Peça não encontrada' });
        }

        res.json(pecas[0]);
    } catch (error) {
        console.error('❌ Erro ao buscar peça:', error);
        res.status(500).json({ error: 'Erro ao buscar peça' });
    }
});

// POST - Criar nova peça
app.post('/api/pecas', async (req, res) => {
    try {
        const { nome, codigo, unidade, custo_unitario, fornecedor, descricao, categoria, vida_util_km, vida_util_meses } = req.body;

        if (!nome || !custo_unitario) {
            return res.status(400).json({ error: 'Campos obrigatórios: nome, custo_unitario' });
        }

        const [result] = await pool.query(
            'INSERT INTO FF_Pecas (nome, codigo, unidade, custo_unitario, fornecedor, descricao, categoria, vida_util_km, vida_util_meses) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [nome, codigo || null, unidade || 'UN', custo_unitario, fornecedor || null, descricao || null, categoria || null, vida_util_km || null, vida_util_meses || null]
        );

        console.log('✅ Peça criada:', nome, '- Código:', codigo);
        res.status(201).json({ id: result.insertId, codigo: codigo, message: 'Peça criada com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao criar peça:', error);
        res.status(500).json({ error: 'Erro ao criar peça' });
    }
});

// PUT - Atualizar peça
app.put('/api/pecas/:id', async (req, res) => {
    try {
        const { id } = req.params;
        const { nome, codigo, unidade, custo_unitario, fornecedor, descricao, categoria, vida_util_km, vida_util_meses } = req.body;

        if (!nome || !custo_unitario) {
            return res.status(400).json({ error: 'Campos obrigatórios: nome, custo_unitario' });
        }

        await pool.query(
            'UPDATE FF_Pecas SET nome = ?, codigo = ?, unidade = ?, custo_unitario = ?, fornecedor = ?, descricao = ?, categoria = ?, vida_util_km = ?, vida_util_meses = ? WHERE id = ?',
            [nome, codigo || null, unidade || 'UN', custo_unitario, fornecedor || null, descricao || null, categoria || null, vida_util_km || null, vida_util_meses || null, id]
        );

        console.log('✅ Peça atualizada:', id);
        res.json({ message: 'Peça atualizada com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao atualizar peça:', error);
        res.status(500).json({ error: 'Erro ao atualizar peça' });
    }
});

// DELETE - Excluir peça
app.delete('/api/pecas/:id', async (req, res) => {
    try {
        const { id } = req.params;

        await pool.query('DELETE FROM FF_Pecas WHERE id = ?', [id]);

        console.log('✅ Peça excluída:', id);
        res.json({ message: 'Peça excluída com sucesso' });
    } catch (error) {
        console.error('❌ Erro ao excluir peça:', error);
        res.status(500).json({ error: 'Erro ao excluir peça' });
    }
});

// ==================== ASSOCIAÇÃO PEÇAS <-> ITENS DE PLANO ====================

// GET - Listar peças de um item de plano
app.get('/api/plano-pecas/:itemId', async (req, res) => {
    try {
        const [pecas] = await pool.query(`
            SELECT
                pp.id,
                pp.plano_item_id,
                pp.peca_id,
                pp.quantidade,
                p.codigo,
                p.nome,
                p.descricao,
                p.unidade,
                p.custo_unitario,
                p.vida_util_km,
                p.vida_util_meses,
                (pp.quantidade * p.custo_unitario) as custo_total
            FROM FF_PlanoManutencao_Pecas pp
            JOIN FF_Pecas p ON p.id = pp.peca_id
            WHERE pp.plano_item_id = ?
            ORDER BY p.nome ASC
        `, [req.params.itemId]);

        let custoTotal = 0;
        pecas.forEach(p => custoTotal += parseFloat(p.custo_total) || 0);

        res.json({
            success: true,
            count: pecas.length,
            custo_total_pecas: custoTotal,
            data: pecas
        });
    } catch (error) {
        console.error('❌ Erro ao listar peças do item:', error);
        res.status(500).json({ error: 'Erro ao listar peças do item' });
    }
});

// POST - Adicionar peça ao item de plano
app.post('/api/plano-pecas', async (req, res) => {
    try {
        const { plano_item_id, peca_id, quantidade = 1 } = req.body;

        if (!plano_item_id || !peca_id) {
            return res.status(400).json({ error: 'plano_item_id e peca_id são obrigatórios' });
        }

        // Buscar o código da peça
        const [pecaData] = await pool.query('SELECT codigo FROM FF_Pecas WHERE id = ?', [peca_id]);
        const codigo_peca = pecaData.length > 0 ? pecaData[0].codigo : null;

        // Verificar se já existe
        const [existing] = await pool.query(
            'SELECT id, quantidade FROM FF_PlanoManutencao_Pecas WHERE plano_item_id = ? AND peca_id = ?',
            [plano_item_id, peca_id]
        );

        if (existing.length > 0) {
            // Atualizar quantidade existente
            await pool.query(
                'UPDATE FF_PlanoManutencao_Pecas SET quantidade = quantidade + ? WHERE id = ?',
                [quantidade, existing[0].id]
            );
            res.json({
                success: true,
                message: 'Quantidade da peça atualizada',
                id: existing[0].id
            });
        } else {
            // Inserir nova associação com codigo_peca
            const [result] = await pool.query(`
                INSERT INTO FF_PlanoManutencao_Pecas (plano_item_id, peca_id, codigo_peca, quantidade, criado_em)
                VALUES (?, ?, ?, ?, NOW())
            `, [plano_item_id, peca_id, codigo_peca, quantidade]);

            res.status(201).json({
                success: true,
                message: 'Peça adicionada ao item com sucesso',
                id: result.insertId,
                codigo_peca: codigo_peca
            });
        }
    } catch (error) {
        console.error('❌ Erro ao adicionar peça ao item:', error);
        res.status(500).json({ error: 'Erro ao adicionar peça ao item' });
    }
});

// PUT - Atualizar quantidade de peça no item
app.put('/api/plano-pecas/:id', async (req, res) => {
    try {
        const { quantidade } = req.body;

        if (!quantidade) {
            return res.status(400).json({ error: 'quantidade é obrigatória' });
        }

        await pool.query(
            'UPDATE FF_PlanoManutencao_Pecas SET quantidade = ? WHERE id = ?',
            [quantidade, req.params.id]
        );

        res.json({
            success: true,
            message: 'Quantidade atualizada com sucesso'
        });
    } catch (error) {
        console.error('❌ Erro ao atualizar quantidade:', error);
        res.status(500).json({ error: 'Erro ao atualizar quantidade' });
    }
});

// DELETE - Remover peça do item de plano
app.delete('/api/plano-pecas/:id', async (req, res) => {
    try {
        const [result] = await pool.query(
            'DELETE FROM FF_PlanoManutencao_Pecas WHERE id = ?',
            [req.params.id]
        );

        if (result.affectedRows === 0) {
            return res.status(404).json({ error: 'Associação não encontrada' });
        }

        res.json({
            success: true,
            message: 'Peça removida do item com sucesso'
        });
    } catch (error) {
        console.error('❌ Erro ao remover peça do item:', error);
        res.status(500).json({ error: 'Erro ao remover peça do item' });
    }
});

// Servir arquivos estáticos DEPOIS de todas as rotas dinâmicas
app.use(express.static(__dirname));

// Função para criar tabela de itens de plano de manutenção
async function ensureMaintenancePlanItemsTable() {
    try {
        await pool.query(`
            CREATE TABLE IF NOT EXISTS FF_MaintenancePlanItems (
                id INT AUTO_INCREMENT PRIMARY KEY,
                modelo_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                intervalo_km INT NULL,
                intervalo_meses INT NULL,
                custo_estimado DECIMAL(10, 2) NOT NULL,
                criticidade ENUM('baixa', 'media', 'alta') NOT NULL DEFAULT 'media',
                descricao TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (modelo_id) REFERENCES FF_VehicleModels(id) ON DELETE CASCADE,
                INDEX idx_modelo (modelo_id),
                INDEX idx_criticidade (criticidade)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        `);
        console.log('✅ Tabela FF_MaintenancePlanItems verificada/criada');
    } catch (error) {
        console.error('❌ Erro ao criar tabela FF_MaintenancePlanItems:', error);
    }
}

// Função para criar tabelas de peças
async function ensurePecasTables() {
    try {
        // Tabela de peças
        await pool.query(`
            CREATE TABLE IF NOT EXISTS FF_Pecas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(50) NULL,
                nome VARCHAR(255) NOT NULL,
                descricao TEXT NULL,
                unidade VARCHAR(20) DEFAULT 'un',
                custo_unitario DECIMAL(10,2) DEFAULT 0,
                estoque_minimo INT DEFAULT 0,
                estoque_atual INT DEFAULT 0,
                fornecedor VARCHAR(255) NULL,
                categoria VARCHAR(100) NULL,
                ativo TINYINT(1) DEFAULT 1,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_codigo (codigo),
                INDEX idx_nome (nome),
                INDEX idx_categoria (categoria),
                INDEX idx_ativo (ativo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        `);

        // Adicionar colunas faltantes se a tabela já existir com estrutura antiga
        const columnsToAdd = [
            { name: 'categoria', sql: "ALTER TABLE FF_Pecas ADD COLUMN categoria VARCHAR(100) NULL" },
            { name: 'ativo', sql: "ALTER TABLE FF_Pecas ADD COLUMN ativo TINYINT(1) DEFAULT 1" },
            { name: 'estoque_minimo', sql: "ALTER TABLE FF_Pecas ADD COLUMN estoque_minimo INT DEFAULT 0" },
            { name: 'estoque_atual', sql: "ALTER TABLE FF_Pecas ADD COLUMN estoque_atual INT DEFAULT 0" },
            { name: 'criado_em', sql: "ALTER TABLE FF_Pecas ADD COLUMN criado_em DATETIME DEFAULT CURRENT_TIMESTAMP" },
            { name: 'atualizado_em', sql: "ALTER TABLE FF_Pecas ADD COLUMN atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP" },
            { name: 'vida_util_km', sql: "ALTER TABLE FF_Pecas ADD COLUMN vida_util_km INT NULL COMMENT 'Vida útil em KM'" },
            { name: 'vida_util_meses', sql: "ALTER TABLE FF_Pecas ADD COLUMN vida_util_meses INT NULL COMMENT 'Vida útil em meses'" }
        ];

        for (const col of columnsToAdd) {
            try {
                const [columns] = await pool.query(`SHOW COLUMNS FROM FF_Pecas LIKE '${col.name}'`);
                if (columns.length === 0) {
                    await pool.query(col.sql);
                    console.log(`✅ Coluna ${col.name} adicionada à tabela FF_Pecas`);
                }
            } catch (err) {
                // Ignora erro se coluna já existe
            }
        }

        console.log('✅ Tabela FF_Pecas verificada/criada');

        // Tabela de associação peças <-> itens de plano
        await pool.query(`
            CREATE TABLE IF NOT EXISTS FF_PlanoManutencao_Pecas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                plano_item_id INT NOT NULL,
                peca_id INT NOT NULL,
                codigo_peca VARCHAR(50) NULL,
                quantidade INT DEFAULT 1,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_plano_item (plano_item_id),
                INDEX idx_peca (peca_id),
                INDEX idx_codigo_peca (codigo_peca),
                UNIQUE KEY unique_plano_peca (plano_item_id, peca_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        `);

        // Adicionar coluna codigo_peca se não existir
        try {
            const [columns] = await pool.query("SHOW COLUMNS FROM FF_PlanoManutencao_Pecas LIKE 'codigo_peca'");
            if (columns.length === 0) {
                await pool.query("ALTER TABLE FF_PlanoManutencao_Pecas ADD COLUMN codigo_peca VARCHAR(50) NULL AFTER peca_id");
                console.log('✅ Coluna codigo_peca adicionada à tabela FF_PlanoManutencao_Pecas');
            }
        } catch (err) {
            // Ignora erro se coluna já existe
        }

        console.log('✅ Tabela FF_PlanoManutencao_Pecas verificada/criada');

        // Inserir peças de exemplo se a tabela estiver vazia
        const [count] = await pool.query('SELECT COUNT(*) as total FROM FF_Pecas');
        if (count[0].total === 0) {
            await pool.query(`
                INSERT INTO FF_Pecas (codigo, nome, descricao, unidade, custo_unitario, estoque_minimo, categoria) VALUES
                ('FLT-OL-001', 'Filtro de Óleo', 'Filtro de óleo do motor - universal', 'un', 35.00, 10, 'Filtros'),
                ('FLT-AR-001', 'Filtro de Ar', 'Filtro de ar do motor - universal', 'un', 45.00, 10, 'Filtros'),
                ('FLT-CB-001', 'Filtro de Combustível', 'Filtro de combustível - universal', 'un', 55.00, 5, 'Filtros'),
                ('FLT-AC-001', 'Filtro de Ar Condicionado', 'Filtro de cabine/ar condicionado', 'un', 65.00, 5, 'Filtros'),
                ('OLE-MOT-001', 'Óleo de Motor 5W30', 'Óleo sintético 5W30 - 1 litro', 'litros', 45.00, 20, 'Óleos e Fluidos'),
                ('OLE-MOT-002', 'Óleo de Motor 10W40', 'Óleo semi-sintético 10W40 - 1 litro', 'litros', 35.00, 20, 'Óleos e Fluidos'),
                ('FLU-FRE-001', 'Fluido de Freio DOT4', 'Fluido de freio DOT4 - 500ml', 'un', 38.00, 10, 'Óleos e Fluidos'),
                ('PAS-FRE-001', 'Pastilha de Freio Dianteira', 'Jogo de pastilhas dianteiras - universal', 'jogo', 120.00, 5, 'Freios'),
                ('PAS-FRE-002', 'Pastilha de Freio Traseira', 'Jogo de pastilhas traseiras - universal', 'jogo', 95.00, 5, 'Freios'),
                ('DIS-FRE-001', 'Disco de Freio Dianteiro', 'Par de discos dianteiros - universal', 'par', 280.00, 2, 'Freios'),
                ('VEL-IGN-001', 'Vela de Ignição', 'Vela de ignição iridium - unidade', 'un', 65.00, 20, 'Ignição'),
                ('COR-DEN-001', 'Correia Dentada', 'Kit correia dentada com tensor', 'kit', 320.00, 3, 'Correias'),
                ('BAT-60A-001', 'Bateria 60Ah', 'Bateria automotiva 60Ah', 'un', 480.00, 2, 'Elétrica')
            `);
            console.log('✅ Peças de exemplo inseridas');
        }
    } catch (error) {
        console.error('❌ Erro ao criar tabelas de peças:', error);
    }
}

// Iniciar servidor
app.listen(PORT, async () => {
    // Criar tabelas se não existirem
    await ensureMaintenancePlanItemsTable();
    await ensurePecasTables();
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

    // ========== FUNÇÃO DE VERIFICAÇÃO DE ALERTAS DE MANUTENÇÃO ==========
    async function verificarAlertasManutencao() {
        try {
            console.log('🔍 Buscando veículos com planos de manutenção ativos...');

            // Buscar todos os veículos com planos ativos
            const [vehiclePlans] = await pool.query(`
                SELECT
                    vp.*,
                    v.LicensePlate,
                    v.VehicleName,
                    p.nome_plano,
                    p.tipo_gatilho,
                    p.intervalo_km,
                    p.intervalo_dias,
                    p.alertar_antecipacao_km,
                    p.alertar_antecipacao_dias
                FROM FF_VehicleMaintenancePlans vp
                JOIN Vehicles v ON v.Id = vp.vehicle_id
                JOIN FF_MaintenancePlans p ON p.id = vp.plano_id
                WHERE vp.ativo = 1 AND p.ativo = 1
            `);

            console.log(`📊 Encontrados ${vehiclePlans.length} planos ativos`);

            let alertasGerados = 0;

            for (const vp of vehiclePlans) {
                // Buscar km atual do veículo (último valor não-zero)
                const [telemetria] = await pool.query(`
                    SELECT km_final
                    FROM Telemetria_Diaria
                    WHERE LicensePlate = ? AND km_final > 0
                    ORDER BY data DESC
                    LIMIT 1
                `, [vp.LicensePlate]);

                const kmAtual = telemetria.length > 0 ? telemetria[0].km_final : 0;
                const dataAtual = new Date();

                let precisaAlerta = false;
                let tipoAlerta = null;
                let mensagem = '';
                let prioridade = 'Média';

                // Verificar alerta por quilometragem
                if (vp.tipo_gatilho.includes('Quilometragem') && vp.proxima_execucao_km) {
                    const kmAlerta = vp.proxima_execucao_km - (vp.alertar_antecipacao_km || 0);

                    if (kmAtual >= vp.proxima_execucao_km) {
                        precisaAlerta = true;
                        tipoAlerta = 'Quilometragem';
                        prioridade = 'Crítica';
                        mensagem = `${vp.nome_plano} VENCIDA! Veículo ${vp.LicensePlate} atingiu ${kmAtual} km (programado: ${vp.proxima_execucao_km} km)`;
                    } else if (kmAtual >= kmAlerta) {
                        precisaAlerta = true;
                        tipoAlerta = 'Quilometragem';
                        prioridade = 'Alta';
                        const kmRestantes = vp.proxima_execucao_km - kmAtual;
                        mensagem = `${vp.nome_plano} próxima! Veículo ${vp.LicensePlate} faltam ${kmRestantes} km`;
                    }
                }

                // Verificar alerta por data
                if (vp.tipo_gatilho.includes('Tempo') && vp.proxima_execucao_data) {
                    const dataProxima = new Date(vp.proxima_execucao_data);
                    const diasAntecipacao = vp.alertar_antecipacao_dias || 0;
                    const dataAlerta = new Date(dataProxima);
                    dataAlerta.setDate(dataAlerta.getDate() - diasAntecipacao);

                    if (dataAtual >= dataProxima) {
                        precisaAlerta = true;
                        tipoAlerta = tipoAlerta ? 'Ambos' : 'Data';
                        prioridade = 'Crítica';
                        mensagem = `${vp.nome_plano} VENCIDA! Veículo ${vp.LicensePlate} - Data programada: ${vp.proxima_execucao_data}`;
                    } else if (dataAtual >= dataAlerta) {
                        if (!precisaAlerta) {
                            precisaAlerta = true;
                            tipoAlerta = 'Data';
                            prioridade = 'Alta';
                            const diasRestantes = Math.ceil((dataProxima - dataAtual) / (1000 * 60 * 60 * 24));
                            mensagem = `${vp.nome_plano} próxima! Veículo ${vp.LicensePlate} em ${diasRestantes} dias`;
                        }
                    }
                }

                // Se precisa alerta, verificar se já existe um alerta ativo
                if (precisaAlerta) {
                    const [alertaExistente] = await pool.query(`
                        SELECT id FROM FF_MaintenanceAlerts
                        WHERE vehicle_id = ?
                          AND plano_id = ?
                          AND status = 'Ativo'
                        LIMIT 1
                    `, [vp.vehicle_id, vp.plano_id]);

                    // Só criar se não existir alerta ativo
                    if (alertaExistente.length === 0) {
                        await pool.query(`
                            INSERT INTO FF_MaintenanceAlerts
                                (vehicle_id, plano_id, vehicle_maintenance_plan_id,
                                 tipo_alerta, mensagem, km_atual, km_programado,
                                 data_programada, prioridade, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ativo')
                        `, [
                            vp.vehicle_id,
                            vp.plano_id,
                            vp.id,
                            tipoAlerta,
                            mensagem,
                            kmAtual,
                            vp.proxima_execucao_km,
                            vp.proxima_execucao_data,
                            prioridade
                        ]);

                        alertasGerados++;
                        console.log(`   🔔 Alerta gerado: ${mensagem}`);
                    }
                }
            }

            console.log(`✅ Verificação concluída: ${alertasGerados} alertas gerados`);

        } catch (error) {
            console.error('❌ Erro ao verificar alertas:', error);
            throw error;
        }
    }

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

    // ========== CRON JOB DE VERIFICAÇÃO DE ALERTAS DE MANUTENÇÃO ==========
    // Verificar alertas de manutenção todos os dias às 06:00
    cron.schedule('0 6 * * *', async () => {
        console.log('\n🔔 [CRON] Iniciando verificação de alertas de manutenção...');
        try {
            await verificarAlertasManutencao();
            console.log('✅ [CRON] Verificação de alertas concluída!\n');
        } catch (error) {
            console.error('❌ [CRON] Erro na verificação de alertas:', error);
        }
    });

    console.log('⏰ Cron job configurado: Verificação de alertas de manutenção todos os dias às 06:00h\n');
});
