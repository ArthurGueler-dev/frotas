const fs = require('fs').promises;
const path = require('path');

// Caminhos dos arquivos JSON
const MODELOS_FILE = path.join(__dirname, 'data', 'modelos.json');
const PLANOS_FILE = path.join(__dirname, 'data', 'planos.json');
const MANUTENCOES_FILE = path.join(__dirname, 'data', 'manutencoes.json');

// Funções auxiliares para ler/escrever JSON
async function readJSON(filepath) {
    try {
        const data = await fs.readFile(filepath, 'utf8');
        return JSON.parse(data);
    } catch (error) {
        console.error(`Erro ao ler ${filepath}:`, error);
        return [];
    }
}

async function writeJSON(filepath, data) {
    try {
        await fs.writeFile(filepath, JSON.stringify(data, null, 2), 'utf8');
        return true;
    } catch (error) {
        console.error(`Erro ao escrever ${filepath}:`, error);
        return false;
    }
}

// Função para calcular alertas de manutenção
function calcularAlertas(veiculos, planos, historicoManutencoes) {
    const alertas = [];
    const hoje = new Date();

    veiculos.forEach(veiculo => {
        // Buscar plano do modelo do veículo
        const plano = planos.find(p => p.modeloId === veiculo.modeloId);
        if (!plano || !plano.itens) return;

        plano.itens.forEach(item => {
            // Buscar última manutenção deste tipo para este veículo
            const ultimaManutencao = historicoManutencoes
                .filter(m => m.veiculoId === veiculo.id && m.planoItemId === item.id)
                .sort((a, b) => new Date(b.data) - new Date(a.data))[0];

            // KM e data base (última manutenção ou aquisição)
            const kmBase = ultimaManutencao?.kmRealizado || veiculo.kmInicial || 0;
            const dataBase = ultimaManutencao?.data || veiculo.dataAquisicao || hoje.toISOString();

            // Calcular próxima manutenção
            const kmProximo = kmBase + (item.intervalo.km || 0);
            const dataProxima = new Date(dataBase);
            dataProxima.setMonth(dataProxima.getMonth() + (item.intervalo.meses || 0));

            // KM e dias faltando
            const kmFaltando = kmProximo - (veiculo.kmAtual || 0);
            const diasFaltando = Math.ceil((dataProxima - hoje) / (1000 * 60 * 60 * 24));

            // Determinar status
            let status = 'em_dia';
            if (kmFaltando <= 0 || diasFaltando <= 0) {
                status = 'vencido';
            } else if (kmFaltando <= 500 || diasFaltando <= 7) {
                status = 'urgente';
            } else if (kmFaltando <= 2000 || diasFaltando <= 30) {
                status = 'proximo';
            }

            if (status !== 'em_dia') {
                alertas.push({
                    id: `alerta_${veiculo.id}_${item.id}`,
                    veiculoId: veiculo.id,
                    placa: veiculo.placa,
                    modelo: veiculo.modelo || veiculo.model,
                    planoItemId: item.id,
                    tipo: item.tipo,
                    status: status,
                    kmAtual: veiculo.kmAtual || 0,
                    kmPrevisto: kmProximo,
                    dataPrevista: dataProxima.toISOString().split('T')[0],
                    kmFaltando: kmFaltando,
                    diasFaltando: diasFaltando,
                    criticidade: item.criticidade,
                    custoEstimado: item.custoEstimado || 0
                });
            }
        });
    });

    // Ordenar por status (vencido primeiro) e depois por km/dias faltando
    alertas.sort((a, b) => {
        const statusOrder = { vencido: 0, urgente: 1, proximo: 2 };
        if (statusOrder[a.status] !== statusOrder[b.status]) {
            return statusOrder[a.status] - statusOrder[b.status];
        }
        return a.kmFaltando - b.kmFaltando;
    });

    return alertas;
}

// Configurar rotas da API
function setupManutencaoRoutes(app, pool) {

    // ========== ROTAS PARA SERVIR AS PÁGINAS HTML ==========

    app.get('/dashboard-manutencoes', (req, res) => {
        res.sendFile(path.join(__dirname, 'dashboard-manutencoes.html'));
    });

    app.get('/modelos', (req, res) => {
        res.sendFile(path.join(__dirname, 'modelos.html'));
    });

    app.get('/planos-manutencao', (req, res) => {
        res.sendFile(path.join(__dirname, 'planos-manutencao.html'));
    });

    // ========== APIs DE MODELOS ==========

    // GET - Listar todos os modelos
    app.get('/api/modelos', async (req, res) => {
        try {
            const modelos = await readJSON(MODELOS_FILE);

            // Contar veículos por modelo
            const [veiculosCount] = await pool.query(
                'SELECT modeloId, COUNT(*) as count FROM Vehicles WHERE modeloId IS NOT NULL GROUP BY modeloId'
            );

            const modelosComContagem = modelos.map(modelo => {
                const count = veiculosCount.find(v => v.modeloId === modelo.id);
                return {
                    ...modelo,
                    qtdVeiculos: count ? count.count : 0
                };
            });

            res.json(modelosComContagem);
        } catch (error) {
            console.error('Erro ao buscar modelos:', error);
            res.status(500).json({ error: 'Erro ao buscar modelos' });
        }
    });

    // POST - Criar novo modelo
    app.post('/api/modelos', async (req, res) => {
        try {
            const modelos = await readJSON(MODELOS_FILE);

            const novoModelo = {
                id: `modelo_${Date.now()}`,
                marca: req.body.marca,
                modelo: req.body.modelo,
                ano: req.body.ano,
                tipo: req.body.tipo,
                motor: req.body.motor || '',
                observacoes: req.body.observacoes || ''
            };

            modelos.push(novoModelo);
            await writeJSON(MODELOS_FILE, modelos);

            res.status(201).json(novoModelo);
        } catch (error) {
            console.error('Erro ao criar modelo:', error);
            res.status(500).json({ error: 'Erro ao criar modelo' });
        }
    });

    // PUT - Atualizar modelo
    app.put('/api/modelos/:id', async (req, res) => {
        try {
            const modelos = await readJSON(MODELOS_FILE);
            const index = modelos.findIndex(m => m.id === req.params.id);

            if (index === -1) {
                return res.status(404).json({ error: 'Modelo não encontrado' });
            }

            modelos[index] = { ...modelos[index], ...req.body, id: req.params.id };
            await writeJSON(MODELOS_FILE, modelos);

            res.json(modelos[index]);
        } catch (error) {
            console.error('Erro ao atualizar modelo:', error);
            res.status(500).json({ error: 'Erro ao atualizar modelo' });
        }
    });

    // DELETE - Excluir modelo
    app.delete('/api/modelos/:id', async (req, res) => {
        try {
            // Verificar se há veículos com este modelo
            const [veiculos] = await pool.query(
                'SELECT COUNT(*) as count FROM Vehicles WHERE modeloId = ?',
                [req.params.id]
            );

            if (veiculos[0].count > 0) {
                return res.status(400).json({
                    error: 'Não é possível excluir modelo com veículos cadastrados'
                });
            }

            const modelos = await readJSON(MODELOS_FILE);
            const filtered = modelos.filter(m => m.id !== req.params.id);

            if (modelos.length === filtered.length) {
                return res.status(404).json({ error: 'Modelo não encontrado' });
            }

            await writeJSON(MODELOS_FILE, filtered);
            res.json({ success: true });
        } catch (error) {
            console.error('Erro ao excluir modelo:', error);
            res.status(500).json({ error: 'Erro ao excluir modelo' });
        }
    });

    // ========== APIs DE PLANOS ==========

    // GET - Listar planos (ou de um modelo específico)
    app.get('/api/planos', async (req, res) => {
        try {
            const planos = await readJSON(PLANOS_FILE);

            if (req.query.modeloId) {
                const plano = planos.find(p => p.modeloId === req.query.modeloId);
                return res.json(plano || null);
            }

            res.json(planos);
        } catch (error) {
            console.error('Erro ao buscar planos:', error);
            res.status(500).json({ error: 'Erro ao buscar planos' });
        }
    });

    // POST - Criar/Atualizar plano
    app.post('/api/planos', async (req, res) => {
        try {
            const planos = await readJSON(PLANOS_FILE);
            const existingIndex = planos.findIndex(p => p.modeloId === req.body.modeloId);

            const plano = {
                id: req.body.id || `plano_${Date.now()}`,
                modeloId: req.body.modeloId,
                nome: req.body.nome,
                itens: req.body.itens || []
            };

            if (existingIndex >= 0) {
                planos[existingIndex] = plano;
            } else {
                planos.push(plano);
            }

            await writeJSON(PLANOS_FILE, planos);
            res.json(plano);
        } catch (error) {
            console.error('Erro ao salvar plano:', error);
            res.status(500).json({ error: 'Erro ao salvar plano' });
        }
    });

    // POST - Adicionar item ao plano
    app.post('/api/planos/:planoId/itens', async (req, res) => {
        try {
            const planos = await readJSON(PLANOS_FILE);
            const plano = planos.find(p => p.id === req.params.planoId);

            if (!plano) {
                return res.status(404).json({ error: 'Plano não encontrado' });
            }

            const novoItem = {
                id: `item_${Date.now()}`,
                ...req.body
            };

            plano.itens = plano.itens || [];
            plano.itens.push(novoItem);

            await writeJSON(PLANOS_FILE, planos);
            res.status(201).json(novoItem);
        } catch (error) {
            console.error('Erro ao adicionar item:', error);
            res.status(500).json({ error: 'Erro ao adicionar item' });
        }
    });

    // DELETE - Remover item do plano
    app.delete('/api/planos/:planoId/itens/:itemId', async (req, res) => {
        try {
            const planos = await readJSON(PLANOS_FILE);
            const plano = planos.find(p => p.id === req.params.planoId);

            if (!plano) {
                return res.status(404).json({ error: 'Plano não encontrado' });
            }

            plano.itens = plano.itens.filter(i => i.id !== req.params.itemId);

            await writeJSON(PLANOS_FILE, planos);
            res.json({ success: true });
        } catch (error) {
            console.error('Erro ao remover item:', error);
            res.status(500).json({ error: 'Erro ao remover item' });
        }
    });

    // ========== APIs DE ALERTAS ==========

    // GET - Calcular e retornar alertas
    app.get('/api/alertas', async (req, res) => {
        try {
            // Buscar veículos do banco de dados
            const [veiculos] = await pool.query('SELECT * FROM Vehicles');

            // Buscar planos e histórico
            const planos = await readJSON(PLANOS_FILE);
            const manutencoes = await readJSON(MANUTENCOES_FILE);

            // Calcular alertas
            const alertas = calcularAlertas(veiculos, planos, manutencoes);

            // Filtros
            if (req.query.status) {
                const filtered = alertas.filter(a => a.status === req.query.status);
                return res.json(filtered);
            }

            if (req.query.veiculoId) {
                const filtered = alertas.filter(a => a.veiculoId == req.query.veiculoId);
                return res.json(filtered);
            }

            res.json(alertas);
        } catch (error) {
            console.error('Erro ao calcular alertas:', error);
            res.status(500).json({ error: 'Erro ao calcular alertas' });
        }
    });

    // GET - Dashboard de alertas (estatísticas)
    app.get('/api/alertas/dashboard', async (req, res) => {
        try {
            const [veiculos] = await pool.query('SELECT * FROM Vehicles');
            const planos = await readJSON(PLANOS_FILE);
            const manutencoes = await readJSON(MANUTENCOES_FILE);

            const alertas = calcularAlertas(veiculos, planos, manutencoes);

            const vencidas = alertas.filter(a => a.status === 'vencido').length;
            const urgentes = alertas.filter(a => a.status === 'urgente').length;
            const proximas = alertas.filter(a => a.status === 'proximo').length;
            const emDia = veiculos.length - vencidas - urgentes - proximas;

            const custoEstimado30Dias = alertas
                .filter(a => a.diasFaltando <= 30 && a.diasFaltando >= 0)
                .reduce((sum, a) => sum + (a.custoEstimado || 0), 0);

            res.json({
                vencidas,
                urgentes,
                proximas,
                emDia,
                custoEstimado30Dias,
                alertas: alertas.slice(0, 10) // Top 10 alertas
            });
        } catch (error) {
            console.error('Erro ao gerar dashboard:', error);
            res.status(500).json({ error: 'Erro ao gerar dashboard' });
        }
    });

    // ========== APIs DE MANUTENÇÕES (HISTÓRICO) ==========

    // GET - Listar manutenções
    app.get('/api/manutencoes', async (req, res) => {
        try {
            const manutencoes = await readJSON(MANUTENCOES_FILE);

            let filtered = manutencoes;

            if (req.query.veiculoId) {
                filtered = filtered.filter(m => m.veiculoId == req.query.veiculoId);
            }

            if (req.query.tipo) {
                filtered = filtered.filter(m => m.tipo.includes(req.query.tipo));
            }

            res.json(filtered);
        } catch (error) {
            console.error('Erro ao buscar manutenções:', error);
            res.status(500).json({ error: 'Erro ao buscar manutenções' });
        }
    });

    // POST - Registrar nova manutenção
    app.post('/api/manutencoes', async (req, res) => {
        try {
            const manutencoes = await readJSON(MANUTENCOES_FILE);

            const novaManutencao = {
                id: `manut_${Date.now()}`,
                veiculoId: req.body.veiculoId,
                planoItemId: req.body.planoItemId || null,
                tipo: req.body.tipo,
                data: req.body.data || new Date().toISOString().split('T')[0],
                kmRealizado: req.body.kmRealizado,
                oficina: req.body.oficina || '',
                mecanico: req.body.mecanico || '',
                pecasUtilizadas: req.body.pecasUtilizadas || [],
                custoReal: req.body.custoReal || 0,
                observacoes: req.body.observacoes || '',
                status: 'concluida',
                preventiva: req.body.preventiva || false
            };

            manutencoes.push(novaManutencao);
            await writeJSON(MANUTENCOES_FILE, manutencoes);

            res.status(201).json(novaManutencao);
        } catch (error) {
            console.error('Erro ao registrar manutenção:', error);
            res.status(500).json({ error: 'Erro ao registrar manutenção' });
        }
    });
}

module.exports = { setupManutencaoRoutes };
