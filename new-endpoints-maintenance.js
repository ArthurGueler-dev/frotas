// ============================================
// NOVOS ENDPOINTS DE MANUTENÇÃO
// Adicionar ao server.js após os endpoints existentes
// ============================================

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
            'SELECT LicensePlate, LastOdometer FROM Vehicles WHERE Id = ?',
            [vehicleId]
        );

        if (vehicle.length === 0) {
            return res.status(404).json({ error: 'Veículo não encontrado' });
        }

        const kmAtual = km_inicial || vehicle[0].LastOdometer || 0;
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
