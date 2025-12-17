// Dashboard Statistics - Dados REAIS do Ituran
// Este arquivo carrega e exibe estatísticas REAIS dos veículos usando odômetro

// ============= CONFIGURAÇÃO DE DEBUG =============
const DEBUG_MODE = false; // MUDE PARA true APENAS PARA DEBUG
const log = (...args) => { if (DEBUG_MODE) console.log(...args); };
const warn = (...args) => console.warn(...args); // Sempre mostra warnings
const error = (...args) => console.error(...args); // Sempre mostra erros
// =================================================

const ODOMETER_STORAGE_KEY = 'fleetflow_odometer_snapshots';
const KM_CACHE_KEY = 'fleetflow_km_cache_v2';
const KM_CACHE_TIMEOUT = 60 * 60 * 1000; // 1 HORA de cache por veículo (evita recalcular muito)

// ============= CONFIGURAÇÃO DE SINCRONIZAÇÃO AUTOMÁTICA =============
const AUTO_SYNC_ENABLED = true; // Ativar/desativar sincronização automática
const AUTO_SYNC_TIMES = [
    '14:18', // TESTE: auto-sync daqui 2 minutos (remover depois)
    '08:00', // 8h da manhã (início do expediente)
    '12:00', // 12h meio-dia
    '18:00', // 18h final do expediente
    '23:55'  // 23:55 (5 minutos antes do cron do servidor)
];
const AUTO_SYNC_STORAGE_KEY = 'fleetflow_last_auto_sync';
const AUTO_SYNC_MIN_INTERVAL = 55 * 60 * 1000; // Mínimo 55 minutos entre syncs automáticos
// =====================================================================

// Web Worker para sincronização em background
let syncWorker = null;
let isSyncInProgress = false;
let autoSyncInterval = null;

// Detectar quando aba volta visível (retoma sincronização)
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && isSyncInProgress) {
        console.log('🔄 Aba visível novamente, verificando progresso...');
        resumeSyncFromCache();
    }
});

/**
 * Obtém a base/centro de custo selecionado no filtro
 * @returns {string} Base selecionada ou string vazia se "Todas"
 */
function getSelectedBase() {
    const baseSelect = document.getElementById('baseSelect');
    if (!baseSelect) {
        console.warn('⚠️ Elemento baseSelect não encontrado, usando base vazia');
        return '';
    }
    const value = baseSelect.value;
    return value === '' || value === 'Centro de Custo' ? '' : value;
}

// ============= FUNÇÕES DE SINCRONIZAÇÃO AUTOMÁTICA =============

/**
 * Verifica se deve executar sincronização automática
 * @returns {boolean} true se deve sincronizar
 */
function shouldAutoSync() {
    if (!AUTO_SYNC_ENABLED) {
        return false;
    }

    // Não sincronizar se já está sincronizando
    if (isSyncInProgress) {
        console.log('⏭️ Auto-sync cancelado: sincronização já em andamento');
        return false;
    }

    // Verificar último sync automático
    const lastAutoSync = localStorage.getItem(AUTO_SYNC_STORAGE_KEY);
    if (lastAutoSync) {
        const timeSinceLastSync = Date.now() - parseInt(lastAutoSync);
        if (timeSinceLastSync < AUTO_SYNC_MIN_INTERVAL) {
            console.log(`⏭️ Auto-sync cancelado: última sync há ${Math.round(timeSinceLastSync / 60000)} minutos`);
            return false;
        }
    }

    // Verificar se está no horário programado
    const now = new Date();
    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    const isScheduledTime = AUTO_SYNC_TIMES.some(scheduledTime => {
        return currentTime === scheduledTime;
    });

    if (!isScheduledTime) {
        return false;
    }

    return true;
}

/**
 * Executa sincronização automática em background
 */
async function executeAutoSync() {
    if (!shouldAutoSync()) {
        return;
    }

    console.log('🤖 ════════════════════════════════════════════════════');
    console.log('🤖 SINCRONIZAÇÃO AUTOMÁTICA INICIADA');
    console.log(`🤖 Horário: ${new Date().toLocaleString('pt-BR')}`);
    console.log('🤖 ════════════════════════════════════════════════════');

    try {
        // Marcar timestamp da sincronização
        localStorage.setItem(AUTO_SYNC_STORAGE_KEY, Date.now().toString());

        // Mostrar notificação discreta (se disponível)
        showAutoSyncNotification('Sincronizando quilometragem em segundo plano...');

        // Executar sincronização (mesmo código do botão manual)
        await calculateInBackground(0, null, (progress, plate) => {
            // Callback de progresso silencioso (não bloqueia UI)
            log(`🤖 Auto-sync: ${progress}% - ${plate}`);
        });

        console.log('✅ Sincronização automática concluída');
        showAutoSyncNotification('Quilometragem atualizada com sucesso!', 'success');

    } catch (error) {
        console.error('❌ Erro na sincronização automática:', error);
        showAutoSyncNotification('Erro ao sincronizar quilometragem', 'error');
    }
}

/**
 * Mostra notificação discreta de sincronização automática
 * @param {string} message - Mensagem a exibir
 * @param {string} type - Tipo: 'info', 'success', 'error'
 */
function showAutoSyncNotification(message, type = 'info') {
    // Criar ou atualizar elemento de notificação
    let notification = document.getElementById('auto-sync-notification');

    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'auto-sync-notification';
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            transition: opacity 0.3s ease, transform 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        `;
        document.body.appendChild(notification);
    }

    // Definir cor baseado no tipo
    const colors = {
        info: 'background: #3B82F6; color: white;',
        success: 'background: #10B981; color: white;',
        error: 'background: #EF4444; color: white;'
    };

    notification.style.cssText += colors[type] || colors.info;
    notification.textContent = message;

    // Animar entrada
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 10);

    // Auto-ocultar após 4 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(20px)';
    }, 4000);
}

/**
 * Inicializa sistema de sincronização automática
 */
function initAutoSync() {
    if (!AUTO_SYNC_ENABLED) {
        console.log('ℹ️ Sincronização automática DESATIVADA');
        return;
    }

    console.log('🤖 Sistema de sincronização automática ATIVADO');
    console.log('📅 Horários programados:', AUTO_SYNC_TIMES.join(', '));

    // Verificar a cada 1 minuto se deve sincronizar
    autoSyncInterval = setInterval(() => {
        executeAutoSync();
    }, 60 * 1000); // 60 segundos

    // Verificar imediatamente (caso esteja no horário ao carregar página)
    setTimeout(() => {
        executeAutoSync();
    }, 5000); // Aguardar 5 segundos após load
}

// ===============================================================

/**
 * Cache de KM por veículo para evitar variações bruscas
 * Estrutura: { "placa_hoje": { km: 1000, timestamp: Date }, "placa_ontem": { km: 500, timestamp: Date } }
 */
function getKmCache() {
    try {
        const data = localStorage.getItem(KM_CACHE_KEY);
        return data ? JSON.parse(data) : {};
    } catch (error) {
        console.error('❌ Erro ao carregar KM cache:', error);
        return {};
    }
}

/**
 * Salva KM no cache com timestamp
 */
function saveKmCache(plate, period, km) {
    try {
        const cache = getKmCache();
        const cacheKey = `${plate}_${period}`;
        cache[cacheKey] = {
            km: km,
            timestamp: Date.now()
        };
        localStorage.setItem(KM_CACHE_KEY, JSON.stringify(cache));
        console.log(`💾 KM cache salvo: ${plate} (${period}) = ${km} km`);
    } catch (error) {
        console.error('❌ Erro ao salvar KM cache:', error);
    }
}

/**
 * Obtém KM do cache se estiver válido (menos de 5 minutos)
 */
function getKmFromCache(plate, period) {
    const cache = getKmCache();
    const cacheKey = `${plate}_${period}`;

    if (cache[cacheKey]) {
        const age = Date.now() - cache[cacheKey].timestamp;
        if (age < KM_CACHE_TIMEOUT) {
            console.log(`📦 KM do cache: ${plate} (${period}) = ${cache[cacheKey].km} km (${Math.round(age / 1000)}s atrás)`);
            return cache[cacheKey].km;
        }
    }

    return null;
}

/**
 * Obtém snapshots de odômetro armazenados
 */
function getOdometerSnapshots() {
    try {
        const data = localStorage.getItem(ODOMETER_STORAGE_KEY);
        return data ? JSON.parse(data) : {};
    } catch (error) {
        console.error('❌ Erro ao carregar snapshots:', error);
        return {};
    }
}

/**
 * Salva snapshot de odômetro
 */
function saveOdometerSnapshot(plate, odometer, timestamp) {
    try {
        const snapshots = getOdometerSnapshots();
        const dateKey = new Date(timestamp).toISOString().split('T')[0];

        if (!snapshots[plate]) {
            snapshots[plate] = {};
        }

        // Salva apenas se não existir ou se for maior (evita regressão)
        if (!snapshots[plate][dateKey] || odometer > snapshots[plate][dateKey]) {
            snapshots[plate][dateKey] = odometer;
            localStorage.setItem(ODOMETER_STORAGE_KEY, JSON.stringify(snapshots));
        }
    } catch (error) {
        console.error('❌ Erro ao salvar snapshot:', error);
    }
}

/**
 * Busca odômetro de um veículo em uma data específica
 */
function getOdometerForDate(plate, date) {
    const snapshots = getOdometerSnapshots();
    const dateKey = date.toISOString().split('T')[0];

    if (snapshots[plate] && snapshots[plate][dateKey]) {
        return snapshots[plate][dateKey];
    }

    return null;
}

/**
 * Calcula quilometragem REAL usando GetFullReport do Ituran
 * Usa o ODÔMETRO da API (muito mais preciso que calcular por GPS)
 * COM CACHE de 5 minutos para evitar variações bruscas
 * @param {string} vehiclePlate - Placa do veículo
 * @param {Date} startDate - Data início
 * @param {Date} endDate - Data fim
 * @returns {Promise<number>} Quilometragem REAL rodada no período
 */
async function calculateKmForPeriod(vehiclePlate, startDate, endDate) {
    try {
        // Define período para usar como chave do cache
        const startKey = startDate.toISOString().split('T')[0];
        const endKey = endDate.toISOString().split('T')[0];
        const period = startKey === endKey ? startKey : `${startKey}_${endKey}`;

        // STEP 1: Verifica cache primeiro
        const cachedKm = getKmFromCache(vehiclePlate, period);
        if (cachedKm !== null) {
            return cachedKm; // Retorna valor em cache
        }

        // STEP 2: Se não tem cache, busca da API
        console.log(`🔄 Buscando KM da API para ${vehiclePlate} (${period})`);
        const report = await ituranService.getKilometerReport(
            vehiclePlate,
            startDate.toISOString(),
            endDate.toISOString()
        );

        if (!report || !report.success) {
            console.warn(`⚠️ ${vehiclePlate}: ${report?.message || 'Sem dados no período'}`);
            return 0;
        }

        const kmDriven = report.kmDriven || 0;

        // STEP 3: Salva no cache para os próximos 5 minutos
        if (kmDriven > 0) {
            saveKmCache(vehiclePlate, period, kmDriven);
            console.log(`🚗 ${vehiclePlate}: ${kmDriven} km (odômetro ${report.startOdometer} → ${report.endOdometer})`);
        }

        return Math.round(kmDriven);

    } catch (error) {
        console.error(`❌ Erro ao calcular km para ${vehiclePlate}:`, error);
        return 0;
    }
}

/**
 * Salva snapshot do odômetro à meia-noite para referência do dia seguinte
 */
async function saveSnapshotAtMidnight(plate) {
    try {
        const telemetry = await ituranService.getVehicleTelemetry(plate);
        if (!telemetry || !telemetry.odometer) {
            return;
        }

        const currentOdometer = Math.round(telemetry.odometer / 1000);
        const now = new Date();

        // Verifica se já existe snapshot de hoje
        const todaySnapshot = getOdometerForDate(plate, now);

        if (!todaySnapshot) {
            // Salva snapshot apenas se não existir
            saveOdometerSnapshot(plate, currentOdometer, now);
            console.log(`💾 Snapshot inicial salvo para ${plate}: ${currentOdometer} km`);
        }
    } catch (error) {
        console.error(`❌ Erro ao salvar snapshot para ${plate}:`, error);
    }
}

/**
 * Inicializa snapshots históricos na primeira execução
 */
async function initializeHistoricalSnapshots(plate) {
    await saveSnapshotAtMidnight(plate);
}

/**
 * Limpa todos os dados históricos (útil para reset)
 */
function clearAllData() {
    localStorage.removeItem(ODOMETER_STORAGE_KEY);
    localStorage.removeItem('fleetflow_dashboard_stats_cache_v4');
    localStorage.removeItem('fleetflow_dashboard_stats_cache_v5');
    localStorage.removeItem('fleetflow_dashboard_stats_cache_v6');
    console.log('🗑️ Todos os dados foram limpos!');
    console.log('🔄 Recarregue a página agora (Ctrl+Shift+R)');
}

/**
 * Mostra informações de debug do cache e snapshots
 */
function debugCache() {
    const cache = loadCache();
    const snapshots = getOdometerSnapshots();

    console.log('=== DEBUG FLEETFLOW ===');
    console.log('Cache:', cache);
    console.log('Snapshots:', snapshots);
    console.log('=======================');
}

// Expõe funções globalmente para debug
window.clearFleetFlowData = clearAllData;
window.debugFleetFlowCache = debugCache;

/**
 * Configuração do cache do dashboard
 */
const CACHE_KEY = 'fleetflow_dashboard_stats_cache_realtime';
const CACHE_KEY_MONTH = 'fleetflow_dashboard_stats_cache_month';
const CACHE_TIMEOUT = 5 * 60 * 1000; // 5 MINUTOS - Reduz carga na API
const CACHE_TIMEOUT_MONTH = 24 * 60 * 60 * 1000; // 24 HORAS - KM mensal só atualiza 1x por dia

/**
 * Carrega o cache do localStorage (cache de 45s para tempo real)
 * MAS VERIFICA SE É DO MESMO DIA
 */
function loadCache() {
    try {
        const cached = localStorage.getItem(CACHE_KEY);
        if (!cached) {
            return null;
        }

        const data = JSON.parse(cached);
        const now = Date.now();
        const today = new Date().toISOString().split('T')[0];

        // IMPORTANTE: Verifica se o cache é do mesmo dia
        if (data.cacheDate && data.cacheDate !== today) {
            console.warn(`⚠️ Cache é de outro dia (${data.cacheDate}). Descartando e recalculando para hoje (${today})`);
            return null; // Força recalcular
        }

        // Cache válido por 45 segundos (tempo real)
        if (data.lastUpdate && (now - data.lastUpdate) < CACHE_TIMEOUT) {
            const secondsLeft = Math.round((CACHE_TIMEOUT - (now - data.lastUpdate)) / 1000);
            console.log(`⚡ Usando cache de hoje (próxima atualização em ${secondsLeft}s)`);
            return data;
        }

        return null;
    } catch (error) {
        console.error('❌ Erro ao carregar cache:', error);
        return null;
    }
}

/**
 * Salva o cache no localStorage (45s para tempo real)
 * INCLUI A DATA PARA VERIFICAR SE É DO MESMO DIA
 */
function saveCache(stats) {
    try {
        const today = new Date().toISOString().split('T')[0];
        const data = {
            ...stats,
            lastUpdate: Date.now(),
            cacheDate: today // IMPORTANTE: salva a data para verificação
        };
        localStorage.setItem(CACHE_KEY, JSON.stringify(data));
        console.log(`💾 Cache salvo para ${today} (válido por 5 minutos)`);
    } catch (error) {
        console.error('❌ Erro ao salvar cache:', error);
    }
}

/**
 * Carrega cache de KM mensal (válido por 24 horas)
 */
function loadMonthCache() {
    try {
        const cached = localStorage.getItem(CACHE_KEY_MONTH);
        if (!cached) return null;

        const data = JSON.parse(cached);
        const age = Date.now() - data.timestamp;

        if (age < CACHE_TIMEOUT_MONTH) {
            console.log(`📦 Cache MENSAL válido (${Math.round(age / 3600000)}h atrás)`);
            return data.monthTotal;
        }

        console.log(`⏰ Cache MENSAL expirado (${Math.round(age / 3600000)}h)`);
        return null;
    } catch (error) {
        console.error('❌ Erro ao carregar cache mensal:', error);
        return null;
    }
}

/**
 * Salva cache de KM mensal (válido por 24 horas)
 */
function saveMonthCache(monthTotal) {
    try {
        const data = {
            monthTotal,
            timestamp: Date.now()
        };
        localStorage.setItem(CACHE_KEY_MONTH, JSON.stringify(data));
        console.log(`💾 Cache MENSAL salvo (${monthTotal} km) - válido por 24h`);
    } catch (error) {
        console.error('❌ Erro ao salvar cache mensal:', error);
    }
}

/**
 * Verifica se o cache está válido (45 segundos)
 */
function isCacheValid(cache) {
    if (!cache || !cache.lastUpdate) return false;
    return (Date.now() - cache.lastUpdate) < CACHE_TIMEOUT;
}

/**
 * Carrega estatísticas de KM para hoje
 * @param {Array} vehicles - Lista de veículos
 * @param {Object} cache - Cache existente
 * @returns {Promise<Object>} Estatísticas do dia
 */
async function loadTodayStats(vehicles, cache) {
    // Verifica se há cache válido
    if (cache && cache.today) {
        console.log('📦 Usando cache para estatísticas de hoje');
        return cache.today;
    }

    console.log('🔄 Calculando estatísticas de hoje...');
    const today = new Date();
    const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0);
    const endOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59);

    let totalKmToday = 0;
    let vehiclesInMovement = 0;

    console.log(`🔍 Calculando KM HOJE em tempo real para ${vehicles.length} veículos...`);

    // Processa em lotes PEQUENOS de 3 veículos para não sobrecarregar a API
    const batchSize = 3;
    for (let i = 0; i < vehicles.length; i += batchSize) {
        const batch = vehicles.slice(i, i + batchSize);

        const promises = batch.map(async (v) => {
            // Inicializa snapshots históricos se necessário
            await initializeHistoricalSnapshots(v.plate);

            const km = await calculateKmForPeriod(v.plate, startOfDay, endOfDay);
            if (km > 0) {
                console.log(`   ✓ ${v.plate}: ${km} km hoje`);
            }
            totalKmToday += km;
            if (km > 0) vehiclesInMovement++;
        });

        await Promise.all(promises);

        // Aguarda 2 segundos entre lotes para não sobrecarregar
        if (i + batchSize < vehicles.length) {
            console.log(`   📊 Progresso: ${i + batchSize}/${vehicles.length} veículos (${Math.round((i + batchSize) / vehicles.length * 100)}%)`);
            await new Promise(resolve => setTimeout(resolve, 2000));
        }
    }

    const result = {
        totalKmToday: Math.round(totalKmToday),
        vehiclesInMovement,
        avgKmPerVehicle: vehicles.length > 0 ? Math.round(totalKmToday / vehicles.length) : 0,
        vehiclesAnalyzed: vehicles.length  // Quantos veículos foram analisados
    };

    console.log(`📊 TOTAL KM HOJE: ${result.totalKmToday} km de ${vehicles.length} veículos analisados (${vehiclesInMovement} em movimento)`);

    return result;
}

/**
 * Carrega estatísticas de KM para ontem
 * @param {Array} vehicles - Lista de veículos
 * @param {Object} cache - Cache existente
 * @returns {Promise<Object>} Estatísticas de ontem
 */
async function loadYesterdayStats(vehicles, cache) {
    // Verifica se há cache válido
    if (cache && cache.yesterday) {
        console.log('📦 Usando cache para estatísticas de ontem');
        return cache.yesterday;
    }

    console.log('🔄 Calculando estatísticas de ontem...');
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    const startOfDay = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 0, 0, 0);
    const endOfDay = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 23, 59, 59);

    let totalKmYesterday = 0;

    const promises = vehicles.map(async (v) => {
        const km = await calculateKmForPeriod(v.plate, startOfDay, endOfDay);
        totalKmYesterday += km;
    });

    await Promise.all(promises);

    const result = {
        totalKmYesterday: Math.round(totalKmYesterday),
        avgKmPerVehicle: vehicles.length > 0 ? Math.round(totalKmYesterday / vehicles.length) : 0
    };

    return result;
}

/**
 * Carrega estatísticas da semana
 * @param {Array} vehicles - Lista de veículos
 * @param {Object} cache - Cache existente
 * @returns {Promise<Object>} Estatísticas da semana
 */
async function loadWeekStats(vehicles, cache) {
    // Verifica se há cache válido
    if (cache && cache.week) {
        console.log('📦 Usando cache para estatísticas da semana');
        return cache.week;
    }

    console.log('🔄 Calculando estatísticas da semana...');
    const today = new Date();
    const dayOfWeek = today.getDay();
    const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;

    const startOfWeek = new Date(today.getFullYear(), today.getMonth(), today.getDate() + diffToMonday, 0, 0, 0);
    const endOfWeek = new Date();

    let totalKmWeek = 0;

    const promises = vehicles.map(async (v) => {
        const km = await calculateKmForPeriod(v.plate, startOfWeek, endOfWeek);
        totalKmWeek += km;
    });

    await Promise.all(promises);

    const result = {
        totalKmWeek: Math.round(totalKmWeek),
        avgKmPerVehicle: vehicles.length > 0 ? Math.round(totalKmWeek / vehicles.length) : 0
    };

    return result;
}

/**
 * Carrega estatísticas do mês
 * @param {Array} vehicles - Lista de veículos
 * @param {Object} cache - Cache existente
 * @returns {Promise<Object>} Estatísticas do mês
 */
async function loadMonthStats(vehicles, cache) {
    // Verifica se há cache válido
    if (cache && cache.month) {
        console.log('📦 Usando cache para estatísticas do mês');
        return cache.month;
    }

    console.log('🔄 Calculando estatísticas do mês...');
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1, 0, 0, 0);
    const endOfMonth = new Date();

    let totalKmMonth = 0;

    const promises = vehicles.map(async (v) => {
        const km = await calculateKmForPeriod(v.plate, startOfMonth, endOfMonth);
        totalKmMonth += km;
    });

    await Promise.all(promises);

    const result = {
        totalKmMonth: Math.round(totalKmMonth),
        avgKmPerVehicle: vehicles.length > 0 ? Math.round(totalKmMonth / vehicles.length) : 0
    };

    return result;
}

/**
 * Carrega dados dos Top 10 veículos que mais rodaram hoje
 * @param {Array} vehicles - Lista de veículos
 * @returns {Promise<Array>} Array com Top 10 veículos e seus dados
 */
async function loadTopVehiclesToday(vehicles) {
    console.log('🏆 Calculando Top 10 veículos com maior KM hoje...');

    const today = new Date();
    const startOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0);
    const endOfDay = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59);

    const vehiclesKm = [];

    // Processa em lotes de 10 veículos
    const batchSize = 10;
    for (let i = 0; i < vehicles.length; i += batchSize) {
        const batch = vehicles.slice(i, i + batchSize);

        const promises = batch.map(async (v) => {
            const km = await calculateKmForPeriod(v.plate, startOfDay, endOfDay);
            if (km > 0) {
                vehiclesKm.push({
                    plate: v.plate,
                    model: v.model || v.platformName || 'Desconhecido',
                    km: km,
                    status: v.status === 'active' ? 'Em movimento' : 'Parado'
                });
            }
        });

        await Promise.all(promises);
    }

    // Ordena por KM decrescente e pega os Top 10
    const topVehicles = vehiclesKm
        .sort((a, b) => b.km - a.km)
        .slice(0, 10);

    console.log(`🏆 Top 10 veículos:`);
    topVehicles.forEach((v, idx) => {
        console.log(`   ${idx + 1}. ${v.plate} - ${v.km.toLocaleString('pt-BR')} km`);
    });

    return topVehicles;
}

/**
 * Atualiza a tabela de Top 10 veículos no HTML
 */
/**
 * Atualiza ranking dos veículos que mais rodaram (hoje e ontem)
 * @param {Array} vehiclesData - Array com dados de cada veículo
 */
function updateTopVehiclesRanking(vehiclesData) {
    console.log(`📊 Criando ranking com ${vehiclesData.length} veículos`);

    // Ordena por KM de hoje (maior para menor)
    const sortedByToday = [...vehiclesData]
        .sort((a, b) => b.kmToday - a.kmToday)
        .slice(0, 10)
        .map(v => ({
            plate: v.plate,
            model: v.model,
            km: v.kmToday,
            status: v.kmToday > 0 ? 'Em movimento' : 'Parado'
        }));

    // Ordena por KM de ontem (maior para menor)
    const sortedByYesterday = [...vehiclesData]
        .sort((a, b) => b.kmYesterday - a.kmYesterday)
        .slice(0, 10)
        .map(v => ({
            plate: v.plate,
            model: v.model,
            km: v.kmYesterday,
            status: v.kmYesterday > 0 ? 'Em movimento' : 'Parado'
        }));

    console.log('🏆 Top 10 Hoje:', sortedByToday.map(v => `${v.plate} (${v.km}km)`).join(', '));
    console.log('🏆 Top 10 Ontem:', sortedByYesterday.map(v => `${v.plate} (${v.km}km)`).join(', '));

    // Atualiza tabela de hoje
    updateTopVehiclesTable(sortedByToday);

    // Atualiza tabela de ontem (se existir)
    updateTopVehiclesTableYesterday(sortedByYesterday);
}

/**
 * Atualiza tabela de veículos que mais rodaram HOJE
 */
function updateTopVehiclesTable(topVehicles) {
    const tableBody = document.getElementById('top-vehicles-list');
    if (!tableBody) {
        console.warn('⚠️ Elemento "top-vehicles-list" não encontrado');
        return;
    }

    if (topVehicles.length === 0) {
        tableBody.innerHTML = `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Nenhum veículo em movimento hoje
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = topVehicles.map((vehicle, index) => {
        // Pega o modelo correto do vehicle-models.json se disponível
        const modeloCorreto = window.ituranService?.getVehicleModel(vehicle.plate) || vehicle.model;

        return `
        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer transition-colors"
            onclick="window.location.href='veiculos.html?plate=${vehicle.plate}'">
            <td class="py-3 px-4 text-[#111418] dark:text-gray-300">
                <span class="font-medium text-blue-600 dark:text-blue-400">#${index + 1}</span>
                <span class="font-semibold">${vehicle.plate}</span>
            </td>
            <td class="py-3 px-4 text-[#111418] dark:text-gray-300">${modeloCorreto}</td>
            <td class="py-3 px-4 text-right">
                <span class="font-semibold text-green-600 dark:text-green-400">
                    ${vehicle.km.toLocaleString('pt-BR')} km
                </span>
            </td>
            <td class="py-3 px-4 text-right">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium ${
                    vehicle.status === 'Em movimento'
                        ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200'
                }">
                    ${vehicle.status}
                </span>
            </td>
        </tr>
        `;
    }).join('');

    console.log(`✅ Tabela "Hoje" atualizada com ${topVehicles.length} veículos`);
}

/**
 * Atualiza tabela de veículos que mais rodaram ONTEM
 */
function updateTopVehiclesTableYesterday(topVehicles) {
    const tableBody = document.getElementById('top-vehicles-list-yesterday');
    if (!tableBody) {
        console.warn('⚠️ Elemento "top-vehicles-list-yesterday" não encontrado');
        return;
    }

    if (topVehicles.length === 0) {
        tableBody.innerHTML = `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Nenhum veículo em movimento ontem
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = topVehicles.map((vehicle, index) => {
        // Pega o modelo correto do vehicle-models.json se disponível
        const modeloCorreto = window.ituranService?.getVehicleModel(vehicle.plate) || vehicle.model;

        return `
        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer transition-colors"
            onclick="window.location.href='veiculos.html?plate=${vehicle.plate}'">
            <td class="py-3 px-4 text-[#111418] dark:text-gray-300">
                <span class="font-medium text-blue-600 dark:text-blue-400">#${index + 1}</span>
                <span class="font-semibold">${vehicle.plate}</span>
            </td>
            <td class="py-3 px-4 text-[#111418] dark:text-gray-300">${modeloCorreto}</td>
            <td class="py-3 px-4 text-right">
                <span class="font-semibold text-blue-600 dark:text-blue-400">
                    ${vehicle.km.toLocaleString('pt-BR')} km
                </span>
            </td>
            <td class="py-3 px-4 text-right">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium ${
                    vehicle.status === 'Em movimento'
                        ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200'
                }">
                    ${vehicle.status}
                </span>
            </td>
        </tr>
        `;
    }).join('');

    console.log(`✅ Tabela "Ontem" atualizada com ${topVehicles.length} veículos`);
}

/**
 * Carrega dados pré-calculados do localStorage
 * Cache válido por 2 HORAS (não precisa ser do mesmo dia)
 * Aceita caches incompletos (em progresso) para não reiniciar contagem
 */
function loadPreCalculatedData() {
    try {
        const data = localStorage.getItem('fleetflow_daily_km_data');
        if (!data) return null;

        const parsed = JSON.parse(data);
        const today = new Date().toISOString().split('T')[0];
        const cacheAge = Date.now() - parsed.timestamp;
        const TWO_HOURS = 2 * 60 * 60 * 1000; // 2 horas em ms

        // Verifica se o cache tem menos de 2 horas
        if (cacheAge > TWO_HOURS) {
            console.warn(`⚠️ Cache expirado! Idade: ${Math.round(cacheAge / 60000)} minutos (máx: 120 min)`);
            return null;
        }

        // Se mudou de dia, invalida o cache (para recalcular ontem/hoje)
        if (parsed.date !== today) {
            console.warn(`⚠️ Cache é de outro dia (${parsed.date}), hoje é ${today}`);
            return null;
        }

        // Se está incompleto, mostra progresso
        if (parsed.isComplete === false) {
            console.log(`⏳ Cache INCOMPLETO encontrado! Progresso: ${parsed.progress}/${parsed.totalVehicles} veículos`);
            console.log(`   💡 Usando dados parciais até o cálculo terminar`);
        }

        return parsed;
    } catch (error) {
        console.error('Erro ao carregar dados pré-calculados:', error);
        return null;
    }
}

/**
 * NOVA FUNÇÃO: Calcula em background usando Web Worker
 * Permite que sincronização continue mesmo ao trocar de aba
 * @param {number} startFrom - Índice do veículo para começar (default: 0)
 * @param {Object} initialData - Dados iniciais para continuar cálculo
 * @param {Function} progressCallback - Callback chamado a cada veículo processado
 */
async function calculateInBackground(startFrom = 0, initialData = null, progressCallback = null) {
    console.log(`🔄 Iniciando cálculo em BACKGROUND com Web Worker (veículo ${startFrom})`);

    // Verificar suporte a Web Worker
    if (!window.Worker) {
        console.warn('⚠️ Web Worker não suportado, usando método legado');
        return calculateInBackgroundLegacy(startFrom, initialData, progressCallback);
    }

    // Se já está sincronizando, não iniciar outro
    if (isSyncInProgress && syncWorker) {
        console.warn('⚠️ Sincronização já em andamento');
        return;
    }

    // Mostrar barra de progresso
    showProgressBar();

    try {
        // Criar Worker
        syncWorker = new Worker('sync-worker.js?v=' + Date.now());
        isSyncInProgress = true;

        // Buscar lista de veículos
        let vehicles;
        try {
            console.log('📡 Tentando buscar veículos da API Ituran...');
            vehicles = await Promise.race([
                ituranService.getVehiclesList(),
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Timeout')), 30000)
                )
            ]);
            console.log(`✅ ${vehicles.length} veículos encontrados da API`);
        } catch (error) {
            console.warn(`⚠️ API demorou ou falhou: ${error.message}`);
            console.log('🔄 Usando lista LOCAL de veículos...');

            if (typeof getLocalVehiclesList === 'function') {
                vehicles = getLocalVehiclesList();
                console.log(`✅ ${vehicles.length} veículos carregados da lista LOCAL`);
            } else {
                throw new Error('Lista local não disponível e API falhou');
            }
        }

        const base = getSelectedBase(); // Função existente

        // Configurar listeners
        syncWorker.addEventListener('message', (event) => {
            const { type, data } = event.data;

            switch (type) {
                case 'PROGRESS':
                    handleWorkerProgress(data);
                    break;
                case 'COMPLETE':
                    handleWorkerComplete(data);
                    break;
                case 'ERROR':
                    handleWorkerError(data);
                    break;
            }
        });

        syncWorker.addEventListener('error', (error) => {
            console.error('❌ Erro no Worker:', error);
            handleWorkerComplete({ error: true });
        });

        // Iniciar sincronização
        console.log(`📤 Enviando mensagem START_SYNC ao Worker:`);
        console.log(`   - Veículos: ${vehicles?.length || 0}`);
        console.log(`   - Índice inicial: ${startFrom}`);
        console.log(`   - Base: ${base || 'Todas'}`);
        console.log(`   - Primeiro veículo:`, vehicles[0]);

        syncWorker.postMessage({
            type: 'START_SYNC',
            data: {
                vehicles: vehicles,
                startIndex: startFrom,
                base: base
            }
        });

        console.log(`✅ Mensagem START_SYNC enviada ao Worker`);

    } catch (error) {
        console.error('❌ Erro ao iniciar sincronização com Worker:', error);
        hideProgressBar();
        isSyncInProgress = false;

        // Fallback para método legado
        console.log('🔄 Tentando método legado...');
        return calculateInBackgroundLegacy(startFrom, initialData, progressCallback);
    }
}

/**
 * Handler do progresso do Worker
 */
function handleWorkerProgress(data) {
    const { index, total, plate, kmToday, kmYesterday, kmMonth, totalToday, totalYesterday, totalMonth, reportToday } = data;

    // Atualizar UI
    const percent = Math.round(((index + 1) / total) * 100);
    updateProgressBar(percent, `${plate} (${index + 1}/${total})`);

    // Adicionar placa ao container visual
    const platesContainer = document.getElementById('syncPlatesContainer');
    if (platesContainer) {
        const plateBadge = document.createElement('span');
        plateBadge.className = 'px-2 py-1 text-xs font-medium bg-primary/10 text-primary rounded';
        plateBadge.textContent = plate;
        platesContainer.appendChild(plateBadge);

        // Auto-scroll para mostrar últimas placas
        platesContainer.scrollTop = platesContainer.scrollHeight;
    }

    // Atualizar cache no localStorage
    const cacheData = JSON.parse(localStorage.getItem('fleetflow_daily_km_data') || '{}');
    cacheData.date = new Date().toISOString().split('T')[0];
    cacheData.timestamp = Date.now();
    cacheData.todayTotal = totalToday;
    cacheData.yesterdayTotal = totalYesterday;
    cacheData.monthTotal = totalMonth;
    cacheData.isComplete = false;
    cacheData.progress = index + 1;
    cacheData.totalVehicles = total;

    if (!cacheData.vehiclesData) cacheData.vehiclesData = [];
    cacheData.vehiclesData.push({ plate, kmToday, kmYesterday, kmMonth });

    localStorage.setItem('fleetflow_daily_km_data', JSON.stringify(cacheData));

    // Salvar no banco de dados
    if (kmToday > 0 && reportToday.success) {
        saveTelemetryToDatabase({
            licensePlate: plate,
            date: new Date().toISOString().split('T')[0],
            kmInicial: reportToday.startOdometer || 0,
            kmFinal: reportToday.endOdometer || 0,
            kmRodado: kmToday,
            base: getSelectedBase()
        });
    }

    // Atualizar totais na UI
    updateStatElement('stat-km-today', totalToday);
    updateStatElement('stat-km-yesterday', totalYesterday);
    updateStatElement('stat-km-month', totalMonth);
}

/**
 * Handler de conclusão do Worker
 */
function handleWorkerComplete(data) {
    isSyncInProgress = false;

    if (syncWorker) {
        syncWorker.terminate();
        syncWorker = null;
    }

    // Marcar cache como completo
    const cache = JSON.parse(localStorage.getItem('fleetflow_daily_km_data') || '{}');
    cache.isComplete = true;
    cache.completedAt = Date.now();
    localStorage.setItem('fleetflow_daily_km_data', JSON.stringify(cache));

    console.log('✅ Sincronização completa!');
    console.log(`📊 KM Hoje: ${Math.round(data.totalToday || 0).toLocaleString('pt-BR')} km`);
    console.log(`📊 KM Ontem: ${Math.round(data.totalYesterday || 0).toLocaleString('pt-BR')} km`);
    console.log(`📊 KM Mês: ${Math.round(data.totalMonth || 0).toLocaleString('pt-BR')} km`);

    hideProgressBar();
}

/**
 * Handler de erro do Worker
 */
function handleWorkerError(data) {
    console.error(`❌ Erro ao processar ${data.plate}:`, data.error);
}

/**
 * Retoma sincronização do cache
 */
function resumeSyncFromCache() {
    const cache = JSON.parse(localStorage.getItem('fleetflow_daily_km_data') || '{}');

    if (cache && !cache.isComplete && cache.progress < cache.totalVehicles) {
        console.log(`🔄 Retomando sincronização do veículo ${cache.progress + 1}/${cache.totalVehicles}`);
        calculateInBackground(cache.progress);
    }
}

/**
 * Salva telemetria no banco de dados
 */
async function saveTelemetryToDatabase(data) {
    try {
        await fetch('/api/telemetry/save-daily', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        log(`💾 ${data.licensePlate}: Dados salvos no banco`);
    } catch (error) {
        console.error('❌ Erro ao salvar telemetria:', error);
    }
}

/**
 * Busca telemetria do banco de dados para evitar recálculo
 * @param {string} plate - Placa do veículo
 * @param {string} date - Data no formato YYYY-MM-DD
 * @returns {Object|null} Dados do banco ou null se não existir
 */
async function getTelemetryFromDatabase(plate, date) {
    try {
        const params = new URLSearchParams({
            plate: plate,
            startDate: date,
            endDate: date,
            limit: 1
        });

        const response = await fetch(`/api/telemetry/daily?${params.toString()}`);
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            const record = result.data[0];
            log(`📦 ${plate} (${date}): Dados encontrados no banco (KM: ${record.kmRodado})`);
            return {
                kmInicial: record.kmInicial,
                kmFinal: record.kmFinal,
                kmRodado: record.kmRodado,
                fromCache: true
            };
        }

        log(`🔍 ${plate} (${date}): Não encontrado no banco, será recalculado`);
        return null;

    } catch (error) {
        console.error(`❌ Erro ao buscar telemetria do banco (${plate}):`, error);
        return null;
    }
}

/**
 * FUNÇÃO LEGADA: Calcula em background sem travar (FALLBACK para navegadores sem Web Worker)
 * @param {number} startFrom - Índice do veículo para começar (default: 0)
 * @param {Object} initialData - Dados iniciais para continuar cálculo
 * @param {Function} progressCallback - Callback chamado a cada veículo processado (progress, currentPlate)
 */
async function calculateInBackgroundLegacy(startFrom = 0, initialData = null, progressCallback = null) {
    console.log(`🔄 Iniciando cálculo em BACKGROUND (começando do veículo ${startFrom})`);

    // Mostra barra de progresso
    showProgressBar();

    try {
        // Busca veículos (com fallback para lista local)
        let vehicles;
        try {
            console.log('📡 Tentando buscar veículos da API Ituran...');
            vehicles = await Promise.race([
                ituranService.getVehiclesList(),
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Timeout')), 30000) // 30 segundos
                )
            ]);
            console.log(`✅ ${vehicles.length} veículos encontrados da API`);
        } catch (error) {
            console.warn(`⚠️ API demorou ou falhou: ${error.message}`);
            console.log('🔄 Usando lista LOCAL de veículos...');

            if (typeof getLocalVehiclesList === 'function') {
                vehicles = getLocalVehiclesList();
                console.log(`✅ ${vehicles.length} veículos carregados da lista LOCAL`);
            } else {
                throw new Error('Lista local não disponível e API falhou');
            }
        }

        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        // IMPORTANTE: Usar horário local, não UTC!
        // A API Ituran espera horários no fuso do Brasil (GMT-3)
        const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0);
        const todayEnd = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59);
        const yesterdayStart = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 0, 0, 0);
        const yesterdayEnd = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 23, 59, 59);

        // DEBUG: Mostra as datas que serão usadas
        console.log(`📅 Data de HOJE calculada: ${today.toLocaleDateString('pt-BR')}`);
        console.log(`   ➜ todayStart ISO: ${todayStart.toISOString()}`);
        console.log(`   ➜ todayEnd ISO: ${todayEnd.toISOString()}`);
        console.log(`📅 Data de ONTEM calculada: ${yesterday.toLocaleDateString('pt-BR')}`);
        console.log(`   ➜ yesterdayStart ISO: ${yesterdayStart.toISOString()}`);
        console.log(`   ➜ yesterdayEnd ISO: ${yesterdayEnd.toISOString()}`);

        // Se tem dados iniciais (continuando cálculo), usa eles. Senão, começa do zero
        let todayTotal = initialData?.todayTotal || 0;
        let yesterdayTotal = initialData?.yesterdayTotal || 0;
        let monthTotal = initialData?.monthTotal || 0;
        let vehiclesMoving = initialData?.vehiclesMoving || 0;
        const vehiclesData = initialData?.vehiclesData || []; // Array para armazenar dados de cada veículo

        // IMPORTANTE: No início do mês (dias 1-2), KM mensal = KM hoje + KM ontem
        // Não precisa calcular o mês todo novamente
        const dayOfMonth = today.getDate();
        const isStartOfMonth = dayOfMonth <= 2;

        console.log(`📊 Dados iniciais: Hoje ${todayTotal}km, Ontem ${yesterdayTotal}km, Dia do mês: ${dayOfMonth}`);

        // Se estamos no início do mês, não precisa calcular o mês separadamente
        const shouldCalculateMonth = !isStartOfMonth;

        if (isStartOfMonth) {
            console.log('📅 Início do mês detectado - KM mensal será calculado como Hoje + Ontem');
        } else {
            // Verificar se tem cache de KM mensal válido
            const cachedMonthTotal = loadMonthCache();

            if (cachedMonthTotal !== null && monthTotal === 0) {
                console.log(`⚡ Usando KM MENSAL do cache: ${cachedMonthTotal} km`);
                monthTotal = cachedMonthTotal;
                updateStatElement('stat-km-month', monthTotal);
            } else {
                console.log('🔄 Calculando KM do mês do zero...');
            }
        }

        // Datas do mês (apenas se precisar calcular)
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1, 0, 0, 0);
        const monthEnd = new Date();

        // Processa 1 veículo por vez (sequencial, não trava)
        // Começa do índice startFrom (para continuar de onde parou)
        for (let i = startFrom; i < vehicles.length; i++) {
            const vehicle = vehicles[i];

            // Log apenas a cada 10 veículos ou no primeiro/último
            if (i === 0 || i === vehicles.length - 1 || (i + 1) % 10 === 0) {
                console.log(`🔄 Processando ${i + 1}/${vehicles.length} veículos... (${vehicle.plate})`);
            }

            try {
                // KM hoje
                const reportToday = await ituranService.getKilometerReport(
                    vehicle.plate,
                    todayStart.toISOString(),
                    todayEnd.toISOString()
                );

                const kmToday = reportToday.success ? reportToday.kmDriven : 0;
                todayTotal += kmToday;
                if (kmToday > 0) vehiclesMoving++;

                // KM ontem
                const reportYesterday = await ituranService.getKilometerReport(
                    vehicle.plate,
                    yesterdayStart.toISOString(),
                    yesterdayEnd.toISOString()
                );

                const kmYesterday = reportYesterday.success ? reportYesterday.kmDriven : 0;
                yesterdayTotal += kmYesterday;

                // KM no mês - APENAS se não tiver cache válido
                let kmMonth = 0;
                if (shouldCalculateMonth) {
                    const reportMonth = await ituranService.getKilometerReport(
                        vehicle.plate,
                        monthStart.toISOString(),
                        monthEnd.toISOString()
                    );
                    kmMonth = reportMonth.success ? reportMonth.kmDriven : 0;
                    monthTotal += kmMonth;
                }

                // Extrair cidade/base do endereço do veículo (se disponível)
                let base = 'N/A';
                if (vehicle.lastAddress) {
                    const parts = vehicle.lastAddress.split(',');
                    base = parts.length > 0 ? parts[parts.length - 1].trim() : 'N/A';
                }

                // Armazena dados do veículo para ranking
                vehiclesData.push({
                    plate: vehicle.plate,
                    model: vehicle.model || vehicle.platformName || 'N/A',
                    base: base,
                    kmToday: kmToday,
                    kmYesterday: kmYesterday,
                    kmMonth: kmMonth
                });

                // ATUALIZA INTERFACE A CADA VEÍCULO (tempo real!)
                updateStatElement('stat-km-today', Math.round(todayTotal));
                updateStatElement('stat-km-yesterday', Math.round(yesterdayTotal));

                // SEMPRE atualiza KM mensal (mesmo se estiver usando cache)
                updateStatElement('stat-km-month', Math.round(monthTotal));

                updateStatElement('stat-vehicles-moving', vehiclesMoving);

                // Chama callback de progresso se fornecido
                if (typeof progressCallback === 'function') {
                    const progress = ((i + 1) / vehicles.length) * 100;
                    progressCallback(progress, vehicle.plate);
                }

                // SALVA CACHE A CADA VEÍCULO (não perde progresso ao trocar de aba!)
                const cacheData = {
                    date: today.toISOString().split('T')[0],
                    timestamp: Date.now(),
                    monthTotal: Math.round(monthTotal),
                    todayTotal: Math.round(todayTotal),
                    yesterdayTotal: Math.round(yesterdayTotal),
                    vehiclesData: vehiclesData,
                    isComplete: false, // Marca como incompleto durante o cálculo
                    progress: i + 1,
                    totalVehicles: vehicles.length
                };
                localStorage.setItem('fleetflow_daily_km_data', JSON.stringify(cacheData));

                // Atualiza progresso
                const percent = Math.round(((i + 1) / vehicles.length) * 100);
                updateProgressBar(percent, `${vehicle.plate} (${i + 1}/${vehicles.length})`);

                // Log detalhado apenas se DEBUG_MODE estiver ativado
                log(`✅ ${vehicle.plate}: Hoje ${kmToday}km, Ontem ${kmYesterday}km, Mês ${kmMonth}km`);

                // SALVAR NO BANCO DE DADOS (telemetria de hoje)
                if (kmToday > 0 && reportToday.success) {
                    try {
                        await fetch('/api/telemetry/save-daily', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                licensePlate: vehicle.plate,
                                date: today.toISOString().split('T')[0],
                                kmInicial: parseFloat(reportToday.startOdometer) || 0,
                                kmFinal: parseFloat(reportToday.endOdometer) || 0,
                                kmRodado: kmToday,
                                base: base  // Adiciona a base/localidade
                            })
                        });
                        log(`💾 ${vehicle.plate}: Dados salvos no banco (Base: ${base})`);
                    } catch (dbError) {
                        warn(`⚠️ Erro ao salvar ${vehicle.plate} no banco:`, dbError.message);
                    }
                }

            } catch (error) {
                warn(`⚠️ Erro em ${vehicle.plate}:`, error.message);
            }

            // Pausa de 500ms entre veículos (não sobrecarrega API)
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        // Se estamos no início do mês, KM mensal = hoje + ontem
        if (isStartOfMonth) {
            monthTotal = todayTotal + yesterdayTotal;
            console.log(`📅 KM mensal calculado (início do mês): ${todayTotal} + ${yesterdayTotal} = ${monthTotal} km`);
            updateStatElement('stat-km-month', Math.round(monthTotal));
        }

        // Salva no cache FINAL (marca como completo)
        const finalCacheData = {
            date: today.toISOString().split('T')[0],
            timestamp: Date.now(),
            monthTotal: Math.round(monthTotal),
            todayTotal: Math.round(todayTotal),
            yesterdayTotal: Math.round(yesterdayTotal),
            vehiclesData: vehiclesData,
            isComplete: true, // Marca como completo
            progress: vehicles.length,
            totalVehicles: vehicles.length
        };
        localStorage.setItem('fleetflow_daily_km_data', JSON.stringify(finalCacheData));
        console.log(`💾 Cache FINAL salvo com ${vehiclesData.length} veículos (COMPLETO)`);

        // Salva cache mensal separado (válido por 24h)
        if (shouldCalculateMonth || isStartOfMonth) {
            const roundedMonthTotal = Math.round(monthTotal);
            saveMonthCache(roundedMonthTotal);
            console.log(`💾 Cache mensal salvo: ${roundedMonthTotal} km`);
        } else {
            console.log(`ℹ️ Cache mensal NÃO salvo (usando cache existente)`);
        }

        console.log('\n✅ ========== CÁLCULO COMPLETO ==========');
        console.log(`📊 KM Hoje: ${Math.round(todayTotal).toLocaleString('pt-BR')} km`);
        console.log(`📊 KM Ontem: ${Math.round(yesterdayTotal).toLocaleString('pt-BR')} km`);
        console.log(`📊 KM Mês: ${Math.round(monthTotal).toLocaleString('pt-BR')} km`);
        console.log(`🚗 Veículos em movimento: ${vehiclesMoving}`);
        console.log('=========================================\n');

        // Atualiza ranking dos 10 veículos que mais rodaram
        console.log('🏆 Atualizando ranking de veículos...');
        updateTopVehiclesRanking(vehiclesData);

        hideProgressBar();

    } catch (error) {
        console.error('❌ Erro no cálculo background:', error);
        hideProgressBar();
    }
}

/**
 * Mostra barra de progresso
 */
function showProgressBar() {
    const progressBar = document.getElementById('sync-progress-bar');
    if (progressBar) {
        progressBar.classList.remove('hidden');

        // Limpar placas da sincronização anterior
        const platesContainer = document.getElementById('syncPlatesContainer');
        if (platesContainer) {
            platesContainer.innerHTML = '';
        }
    }
}

/**
 * Atualiza barra de progresso
 */
function updateProgressBar(percent, detail) {
    const progressFill = document.getElementById('sync-progress-fill');
    const progressText = document.getElementById('sync-progress-text');
    const statusText = document.getElementById('sync-status-text');

    if (progressFill) progressFill.style.width = `${percent}%`;
    if (progressText) progressText.textContent = `${percent}%`;
    if (statusText) statusText.textContent = detail || '';
}

/**
 * Esconde barra de progresso
 */
function hideProgressBar() {
    const progressBar = document.getElementById('sync-progress-bar');
    if (progressBar) {
        setTimeout(() => progressBar.classList.add('hidden'), 2000);
    }
}

/**
 * Atualiza os cards da dashboard com dados reais
 */
async function updateDashboardStats() {
    console.log('='.repeat(60));
    console.log('🚀 updateDashboardStats() INICIADO');
    console.log('='.repeat(60));

    try {
        console.log('🔄 Carregando estatísticas da frota...');
        console.log(`   Timestamp: ${new Date().toLocaleTimeString()}`);

        // NOVA LÓGICA: Tenta carregar dados pré-calculados primeiro
        console.log('📦 Tentando carregar cache...');
        const preCalculated = loadPreCalculatedData();

        if (preCalculated) {
            console.log('✅ Cache encontrado e válido!');
            console.log(`   Data do cache: ${preCalculated.date}`);
            console.log(`   KM Hoje: ${preCalculated.todayTotal}`);
            console.log(`   KM Ontem: ${preCalculated.yesterdayTotal}`);
            console.log(`   KM Mês: ${preCalculated.monthTotal}`);
            console.log(`   Idade: ${Math.round((Date.now() - preCalculated.timestamp) / 60000)} minutos`);
            console.log(`   Status: ${preCalculated.isComplete ? 'COMPLETO' : 'EM PROGRESSO'}`);

            // Atualiza interface diretamente
            console.log('📝 Atualizando interface com dados do cache...');
            updateStatElement('stat-km-today', preCalculated.todayTotal);
            updateStatElement('stat-km-yesterday', preCalculated.yesterdayTotal);

            // KM mensal: tenta cache separado primeiro, depois do cache principal
            const cachedMonth = loadMonthCache();
            const monthKm = cachedMonth !== null ? cachedMonth : (preCalculated.monthTotal || 0);
            updateStatElement('stat-km-month', monthKm);

            // Se não tem KM mensal em cache, precisa calcular
            if (monthKm === 0 && !cachedMonth) {
                console.log('⚠️ KM mensal não encontrado em nenhum cache. Será recalculado.');
            }

            // Atualiza ranking se existir no cache
            if (preCalculated.vehiclesData) {
                console.log('🏆 Atualizando ranking do cache...');
                updateTopVehiclesRanking(preCalculated.vehiclesData);
            }

            // Se o cache está INCOMPLETO, continua o cálculo em background
            if (preCalculated.isComplete === false) {
                console.log('⏳ Cache INCOMPLETO detectado! Continuando cálculo em background...');
                console.log(`   Progresso atual: ${preCalculated.progress}/${preCalculated.totalVehicles} veículos`);
                console.log('💡 Os valores na tela vão continuar atualizando conforme o cálculo avança');

                // CONTINUA o cálculo de onde parou
                const startFrom = preCalculated.progress || 0;

                // Usa KM mensal do cache separado se disponível, senão usa do cache principal
                const monthTotalFromCache = cachedMonth !== null ? cachedMonth : (preCalculated.monthTotal || 0);

                const initialData = {
                    todayTotal: preCalculated.todayTotal || 0,
                    yesterdayTotal: preCalculated.yesterdayTotal || 0,
                    monthTotal: monthTotalFromCache,
                    vehiclesData: preCalculated.vehiclesData || [],
                    vehiclesMoving: preCalculated.vehiclesData?.filter(v => v.kmToday > 0).length || 0
                };

                calculateInBackground(startFrom, initialData);
            } else {
                console.log('✅ Dashboard atualizado com cache COMPLETO! NÃO vai recalcular.');
                console.log('💡 Para forçar recálculo, limpe o cache ou aguarde 2 horas.');
            }

        } else {
            // Se não tem cache válido, mostra valores zerados e inicia cálculo
            console.warn('⚠️ Nenhum cache encontrado ou cache expirado!');
            console.log('📝 Zerando valores na interface...');
            updateStatElement('stat-km-today', 0);
            updateStatElement('stat-km-yesterday', 0);
            updateStatElement('stat-km-month', 0);

            // Inicia cálculo em background
            console.log('🔄 Iniciando calculateInBackground()...');
            calculateInBackground();
            console.log('✅ calculateInBackground() chamado (rodando em paralelo)');
        }

    } catch (error) {
        console.error('❌ ERRO CRÍTICO em updateDashboardStats:', error);
        console.error('Stack trace:', error.stack);
    }

    console.log('='.repeat(60));
    console.log('✅ updateDashboardStats() FINALIZADO');
    console.log('='.repeat(60));
}

// CÓDIGO ANTIGO REMOVIDO - Agora usa calculateInBackground()
// O código abaixo não é mais necessário mas foi mantido para compatibilidade
// CÓDIGO ANTIGO REMOVIDO - Causava erro de sintaxe
// A função antiga foi completamente substituída por calculateInBackground()
/**
 * Atualiza um elemento de estatística na dashboard
 * @param {string} elementId - ID do elemento
 * @param {number} value - Valor a exibir
 * @param {boolean} isPercentage - Se é porcentagem
 */
function updateStatElement(elementId, value, isPercentage = false) {
    const element = document.getElementById(elementId);

    // DEBUG: Log completo
    console.log(`🔍 updateStatElement('${elementId}', ${value}, isPercentage=${isPercentage})`);
    console.log(`   - Elemento encontrado: ${element !== null}`);

    if (element) {
        if (isPercentage) {
            element.textContent = `${value > 0 ? '+' : ''}${value}%`;
            // Atualiza cor baseado no valor
            element.className = value >= 0 ? 'text-success' : 'text-danger';
            console.log(`   - Texto atualizado: ${element.textContent}`);
        } else {
            // Formata número com separador de milhares e adiciona "km" se for KM
            const formattedValue = value.toLocaleString('pt-BR');

            if (elementId.includes('km') || elementId.includes('stat-km')) {
                element.textContent = `${formattedValue} km`;
                console.log(`   - ✅ KM atualizado: ${element.textContent}`);
            } else {
                element.textContent = formattedValue;
                console.log(`   - ✅ Valor atualizado: ${element.textContent}`);
            }
        }
    } else {
        console.error(`   - ❌ ERRO: Elemento '${elementId}' NÃO foi encontrado no DOM!`);
    }
}

/**
 * Limpa cache antigo (de outros dias) no início
 */
function cleanupOldCache() {
    try {
        const cached = localStorage.getItem(CACHE_KEY);
        if (cached) {
            const data = JSON.parse(cached);
            const today = new Date().toISOString().split('T')[0];

            if (data.cacheDate && data.cacheDate !== today) {
                console.log(`🧹 Limpando cache antigo (${data.cacheDate}). Hoje é ${today}`);
                localStorage.removeItem(CACHE_KEY);
            }
        }
    } catch (error) {
        console.warn('⚠️ Erro ao limpar cache antigo:', error);
    }
}

/**
 * Carrega dados do banco de dados ao iniciar o dashboard
 */
async function loadDataFromDatabase() {
    try {
        console.log('🗄️ Carregando dados do banco de dados...');

        const response = await fetch('/api/telemetry/summary');
        const result = await response.json();

        if (result.success && result.data) {
            const { kmToday, kmYesterday, kmMonth, lastSync } = result.data;

            // Atualizar cards
            updateStatElement('stat-km-today', kmToday);
            updateStatElement('stat-km-yesterday', kmYesterday);
            updateStatElement('stat-km-month', kmMonth);

            // Atualizar timestamp da última sincronização
            if (lastSync) {
                const lastSyncDate = new Date(lastSync);
                const now = new Date();
                const diffMs = now - lastSyncDate;
                const diffMins = Math.floor(diffMs / 60000);

                let timeAgo = '';
                if (diffMins < 1) {
                    timeAgo = 'Agora mesmo';
                } else if (diffMins < 60) {
                    timeAgo = `${diffMins} min atrás`;
                } else if (diffMins < 1440) {
                    const hours = Math.floor(diffMins / 60);
                    timeAgo = `${hours}h atrás`;
                } else {
                    timeAgo = lastSyncDate.toLocaleString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }

                const lastSyncEl = document.getElementById('last-sync-time');
                if (lastSyncEl) {
                    lastSyncEl.textContent = `Última sync: ${timeAgo}`;
                }
            }

            console.log(`✅ Dados carregados do banco: Hoje=${kmToday}km, Ontem=${kmYesterday}km, Mês=${kmMonth}km`);
            return true;
        }
    } catch (error) {
        console.warn('⚠️ Erro ao carregar dados do banco:', error);
        return false;
    }
}

/**
 * Inicializa o carregamento das estatísticas quando a página carregar
 * COM ATUALIZAÇÃO AUTOMÁTICA A CADA 30 SEGUNDOS (TEMPO REAL)
 */
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        // Aguarda um pouco para garantir que os outros scripts carregaram
        setTimeout(async () => {
            if (typeof ituranService !== 'undefined') {
                // Limpa cache antigo ANTES de carregar
                cleanupOldCache();

                // PRIMEIRO: Tenta carregar dados do banco de dados
                const loadedFromDB = await loadDataFromDatabase();

                if (!loadedFromDB) {
                    // FALLBACK: Carrega APENAS do cache local (não recalcula automaticamente)
                    console.log('📦 Carregando dados do cache ao iniciar página...');
                    const preCalculated = loadPreCalculatedData();

                if (preCalculated && preCalculated.isComplete) {
                    // Atualiza interface com cache completo
                    updateStatElement('stat-km-today', preCalculated.todayTotal);
                    updateStatElement('stat-km-yesterday', preCalculated.yesterdayTotal);

                    const cachedMonth = loadMonthCache();
                    const monthKm = cachedMonth !== null ? cachedMonth : (preCalculated.monthTotal || 0);
                    updateStatElement('stat-km-month', monthKm);

                    if (preCalculated.vehiclesData) {
                        updateTopVehiclesRanking(preCalculated.vehiclesData);
                    }

                    console.log('✅ Dashboard carregado do cache. Use "Sincronizar KM" para atualizar.');
                } else {
                    console.log('⚠️ Nenhum cache completo. Use "Sincronizar KM" para calcular.');
                }
                }

                // NÃO atualiza automaticamente a cada 10 minutos (evita recálculos desnecessários)
                // O usuário deve clicar em "Sincronizar KM" quando quiser atualizar
                console.log('💡 Dashboard prioriza dados do banco. Clique em "Sincronizar KM" para atualizar.');

                // ============= INICIALIZAR SINCRONIZAÇÃO AUTOMÁTICA =============
                initAutoSync();
                // ================================================================
            } else {
                console.warn('⚠️ Serviço Ituran não disponível. Estatísticas não foram carregadas.');
            }
        }, 1000);
    });
}

// Expõe funções globalmente
window.updateDashboardStats = updateDashboardStats;
window.calculateKmForPeriod = calculateKmForPeriod;
window.calculateInBackground = calculateInBackground;
