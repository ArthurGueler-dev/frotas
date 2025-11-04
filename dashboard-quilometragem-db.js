/**
 * Dashboard Quilometragem - Integração com Banco de Dados SQLite
 * Este arquivo integra o banco de dados de quilometragem ao dashboard
 */

const KM_DB_API_BASE = '/api/quilometragem';

/**
 * Busca quilometragem de um dia específico do banco de dados
 */
async function buscarQuilometragemDia(placa, data) {
    try {
        const response = await fetch(`${KM_DB_API_BASE}/diaria/${placa}/${data}`);
        const result = await response.json();

        if (result.success && result.data) {
            return result.data.km_rodados || 0;
        }
        return null;
    } catch (error) {
        console.error(`❌ Erro ao buscar KM do dia ${data} para ${placa}:`, error);
        return null;
    }
}

/**
 * Busca quilometragem de um período do banco de dados
 */
async function buscarQuilometragemPeriodo(placa, dataInicio, dataFim) {
    try {
        const response = await fetch(`${KM_DB_API_BASE}/periodo/${placa}?dataInicio=${dataInicio}&dataFim=${dataFim}`);
        const result = await response.json();

        if (result.success && result.data) {
            return result.data; // Array de registros
        }
        return [];
    } catch (error) {
        console.error(`❌ Erro ao buscar KM do período para ${placa}:`, error);
        return [];
    }
}

/**
 * Busca dados mensais do banco de dados
 */
async function buscarQuilometragemMensal(placa, ano, mes) {
    try {
        const response = await fetch(`${KM_DB_API_BASE}/mensal/${placa}/${ano}/${mes}`);
        const result = await response.json();

        if (result.success && result.data) {
            return result.data;
        }
        return null;
    } catch (error) {
        console.error(`❌ Erro ao buscar KM mensal para ${placa}:`, error);
        return null;
    }
}

/**
 * Atualiza quilometragem de um veículo da API Ituran e salva no banco
 */
async function atualizarQuilometragemDaIturan(placa, data) {
    try {
        const response = await fetch(`${KM_DB_API_BASE}/atualizar/${placa}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data })
        });

        const result = await response.json();

        if (result.success) {
            console.log(`✅ Quilometragem de ${placa} atualizada para ${data}:`, result.data);
            return result.data;
        } else {
            console.warn(`⚠️ Não foi possível atualizar ${placa}: ${result.error}`);
            return null;
        }
    } catch (error) {
        console.error(`❌ Erro ao atualizar KM de ${placa}:`, error);
        return null;
    }
}

/**
 * Busca estatísticas de quilometragem
 */
async function buscarEstatisticasQuilometragem(placa, periodo = 'mes') {
    try {
        const response = await fetch(`${KM_DB_API_BASE}/estatisticas/${placa}?periodo=${periodo}`);
        const result = await response.json();

        if (result.success) {
            return result.data;
        }
        return null;
    } catch (error) {
        console.error(`❌ Erro ao buscar estatísticas de ${placa}:`, error);
        return null;
    }
}

/**
 * Calcula KM de hoje, ontem e mês usando o banco de dados
 */
async function calcularKmStats(placas) {
    const hoje = new Date();
    const ontem = new Date(hoje);
    ontem.setDate(hoje.getDate() - 1);

    const dataHoje = hoje.toISOString().split('T')[0];
    const dataOntem = ontem.toISOString().split('T')[0];

    const ano = hoje.getFullYear();
    const mes = hoje.getMonth() + 1;

    let kmHoje = 0;
    let kmOntem = 0;
    let kmMes = 0;

    console.log('📊 Calculando estatísticas de KM do banco de dados...');

    // Busca dados de todos os veículos em paralelo
    const promessasHoje = [];
    const promessasOntem = [];
    const promessasMensal = [];

    for (const placa of placas) {
        promessasHoje.push(buscarQuilometragemDia(placa, dataHoje));
        promessasOntem.push(buscarQuilometragemDia(placa, dataOntem));
        promessasMensal.push(buscarQuilometragemMensal(placa, ano, mes));
    }

    // Aguarda todas as promessas
    const resultadosHoje = await Promise.all(promessasHoje);
    const resultadosOntem = await Promise.all(promessasOntem);
    const resultadosMensal = await Promise.all(promessasMensal);

    // Soma os resultados - garantir que são números
    kmHoje = resultadosHoje.reduce((sum, km) => sum + (parseFloat(km) || 0), 0);
    kmOntem = resultadosOntem.reduce((sum, km) => sum + (parseFloat(km) || 0), 0);
    kmMes = resultadosMensal.reduce((sum, dados) => sum + (parseFloat(dados?.km_total) || 0), 0);

    // Garantir que são números válidos
    kmHoje = isNaN(kmHoje) ? 0 : kmHoje;
    kmOntem = isNaN(kmOntem) ? 0 : kmOntem;
    kmMes = isNaN(kmMes) ? 0 : kmMes;

    console.log(`✅ KM Hoje: ${kmHoje.toFixed(2)} km`);
    console.log(`✅ KM Ontem: ${kmOntem.toFixed(2)} km`);
    console.log(`✅ KM Mês: ${kmMes.toFixed(2)} km`);

    return {
        kmHoje: parseFloat(kmHoje.toFixed(2)),
        kmOntem: parseFloat(kmOntem.toFixed(2)),
        kmMes: parseFloat(kmMes.toFixed(2))
    };
}

/**
 * Atualiza estatísticas do dashboard usando dados do banco
 */
async function atualizarDashboardKmComBanco() {
    try {
        // Busca lista de veículos
        const response = await fetch('/api/vehicles');
        const vehicles = await response.json();

        if (!vehicles || vehicles.length === 0) {
            console.warn('⚠️ Nenhum veículo encontrado');
            return;
        }

        const placas = vehicles.map(v => v.plate); // Corrigido: plate em vez de placa

        // Calcula estatísticas
        const stats = await calcularKmStats(placas);

        // Atualiza elementos do DOM
        const kmHojeEl = document.getElementById('stat-km-today');
        const kmOntemEl = document.getElementById('stat-km-yesterday');
        const kmMesEl = document.getElementById('stat-km-month');

        if (kmHojeEl) kmHojeEl.textContent = `${stats.kmHoje.toFixed(1)} km`;
        if (kmOntemEl) kmOntemEl.textContent = `${stats.kmOntem.toFixed(1)} km`;
        if (kmMesEl) kmMesEl.textContent = `${stats.kmMes.toFixed(1)} km`;

        console.log('✅ Dashboard atualizado com dados do banco de dados!');

    } catch (error) {
        console.error('❌ Erro ao atualizar dashboard com dados do banco:', error);
    }
}

/**
 * Sincroniza dados do dia anterior de todos os veículos
 * Esta função deve ser chamada uma vez por dia para garantir que temos dados históricos
 */
async function sincronizarDadosHistoricos() {
    console.log('🔄 Iniciando sincronização de dados históricos...');

    try {
        // Busca lista de veículos
        const response = await fetch('/api/vehicles');
        const vehicles = await response.json();

        if (!vehicles || vehicles.length === 0) {
            console.warn('⚠️ Nenhum veículo encontrado para sincronizar');
            return;
        }

        // Data de ontem
        const ontem = new Date();
        ontem.setDate(ontem.getDate() - 1);
        const dataOntem = ontem.toISOString().split('T')[0];

        let sucessos = 0;
        let falhas = 0;

        for (const veiculo of vehicles) {
            const placa = veiculo.plate || veiculo.placa; // Suporta ambos os formatos

            // Verifica se já existe dados para ontem
            const dadosExistentes = await buscarQuilometragemDia(placa, dataOntem);

            if (dadosExistentes !== null && dadosExistentes > 0) {
                console.log(`✓ ${placa}: dados de ${dataOntem} já existem (${dadosExistentes} km)`);
                sucessos++;
                continue;
            }

            // Se não existe, busca da API Ituran
            console.log(`🔄 ${placa}: buscando dados de ${dataOntem}...`);
            const resultado = await atualizarQuilometragemDaIturan(placa, dataOntem);

            if (resultado) {
                console.log(`✅ ${placa}: ${resultado.kmRodados} km rodados em ${dataOntem}`);
                sucessos++;
            } else {
                console.warn(`⚠️ ${placa}: sem dados disponíveis`);
                falhas++;
            }

            // Aguarda 500ms entre requisições para não sobrecarregar a API
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        console.log(`\n✅ Sincronização concluída!`);
        console.log(`   ✓ Sucessos: ${sucessos}`);
        console.log(`   ✗ Falhas: ${falhas}`);

    } catch (error) {
        console.error('❌ Erro durante sincronização:', error);
    }
}

/**
 * Adiciona botão de sincronização manual ao dashboard
 */
function adicionarBotaoSincronizacao() {
    // Verifica se o botão já existe
    if (document.getElementById('btn-sync-km')) {
        return;
    }

    // Cria botão
    const btn = document.createElement('button');
    btn.id = 'btn-sync-km';
    btn.innerHTML = '🔄 Sincronizar KM Histórico';
    btn.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        z-index: 1000;
    `;

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.innerHTML = '⏳ Sincronizando...';
        btn.style.backgroundColor = '#ff9800';

        await sincronizarDadosHistoricos();
        await atualizarDashboardKmComBanco();

        btn.disabled = false;
        btn.innerHTML = '✅ Concluído!';
        btn.style.backgroundColor = '#2196F3';

        setTimeout(() => {
            btn.innerHTML = '🔄 Sincronizar KM Histórico';
            btn.style.backgroundColor = '#4CAF50';
        }, 3000);
    });

    document.body.appendChild(btn);
    console.log('✅ Botão de sincronização adicionado');
}

// Inicializa quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('🚀 Dashboard Quilometragem DB carregado!');
        adicionarBotaoSincronizacao();

        // Atualiza dashboard na primeira carga
        setTimeout(() => atualizarDashboardKmComBanco(), 2000);

        // Atualiza a cada 5 minutos
        setInterval(atualizarDashboardKmComBanco, 5 * 60 * 1000);
    });
} else {
    console.log('🚀 Dashboard Quilometragem DB carregado!');
    adicionarBotaoSincronizacao();

    // Atualiza dashboard na primeira carga
    setTimeout(() => atualizarDashboardKmComBanco(), 2000);

    // Atualiza a cada 5 minutos
    setInterval(atualizarDashboardKmComBanco, 5 * 60 * 1000);
}

// Expõe funções globalmente para uso no console
window.dashboardKmDB = {
    buscarQuilometragemDia,
    buscarQuilometragemPeriodo,
    buscarQuilometragemMensal,
    atualizarQuilometragemDaIturan,
    buscarEstatisticasQuilometragem,
    calcularKmStats,
    atualizarDashboardKmComBanco,
    sincronizarDadosHistoricos
};

console.log('📊 Dashboard Quilometragem DB: Funções disponíveis em window.dashboardKmDB');
