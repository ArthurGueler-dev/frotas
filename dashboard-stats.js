// Dashboard Statistics - Dados REAIS do Ituran
// Este arquivo carrega e exibe estatísticas REAIS dos veículos usando odômetro

const ODOMETER_STORAGE_KEY = 'fleetflow_odometer_snapshots';
const KM_CACHE_KEY = 'fleetflow_km_cache_v2';
const KM_CACHE_TIMEOUT = 60 * 60 * 1000; // 1 HORA de cache por veículo (evita recalcular muito)

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
 * NOVA FUNÇÃO: Calcula em background sem travar
 * @param {number} startFrom - Índice do veículo para começar (default: 0)
 * @param {Object} initialData - Dados iniciais para continuar cálculo
 */
async function calculateInBackground(startFrom = 0, initialData = null) {
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

        // Datas do mês
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1, 0, 0, 0);
        const monthEnd = new Date();

        console.log(`📊 Dados iniciais: Hoje ${todayTotal}km, Ontem ${yesterdayTotal}km, Mês ${monthTotal}km, ${vehiclesData.length} veículos já processados`);

        // OTIMIZAÇÃO: Verifica se já tem cache de KM mensal válido
        const cachedMonthTotal = loadMonthCache();
        const shouldCalculateMonth = cachedMonthTotal === null;

        if (cachedMonthTotal !== null) {
            console.log(`⚡ Usando KM MENSAL do cache: ${cachedMonthTotal} km`);
            monthTotal = cachedMonthTotal;
            updateStatElement('stat-km-month', monthTotal);
        } else {
            console.log('🔄 Cache mensal expirado. Calculando KM do mês...');
        }

        // Processa 1 veículo por vez (sequencial, não trava)
        // Começa do índice startFrom (para continuar de onde parou)
        for (let i = startFrom; i < vehicles.length; i++) {
            const vehicle = vehicles[i];

            console.log(`🔄 Processando veículo ${i + 1}/${vehicles.length}: ${vehicle.plate}`);

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

                // Armazena dados do veículo para ranking
                vehiclesData.push({
                    plate: vehicle.plate,
                    model: vehicle.model || vehicle.platformName || 'N/A',
                    kmToday: kmToday,
                    kmYesterday: kmYesterday,
                    kmMonth: kmMonth
                });

                // ATUALIZA INTERFACE A CADA VEÍCULO (tempo real!)
                updateStatElement('stat-km-today', Math.round(todayTotal));
                updateStatElement('stat-km-yesterday', Math.round(yesterdayTotal));
                if (shouldCalculateMonth) {
                    updateStatElement('stat-km-month', Math.round(monthTotal));
                }
                updateStatElement('stat-vehicles-moving', vehiclesMoving);

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

                console.log(`✅ ${vehicle.plate}: Hoje ${kmToday}km, Ontem ${kmYesterday}km, Mês ${kmMonth}km`);

            } catch (error) {
                console.warn(`⚠️ Erro em ${vehicle.plate}:`, error.message);
            }

            // Pausa de 500ms entre veículos (não sobrecarrega API)
            await new Promise(resolve => setTimeout(resolve, 500));
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
        if (shouldCalculateMonth) {
            saveMonthCache(Math.round(monthTotal));
        }

        console.log('✅ Cálculo completo!');
        console.log(`   KM Hoje: ${Math.round(todayTotal)}`);
        console.log(`   KM Ontem: ${Math.round(yesterdayTotal)}`);
        console.log(`   KM Mês: ${Math.round(monthTotal)}`);

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
            updateStatElement('stat-km-month', cachedMonth !== null ? cachedMonth : (preCalculated.monthTotal || 0));

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
                const initialData = {
                    todayTotal: preCalculated.todayTotal || 0,
                    yesterdayTotal: preCalculated.yesterdayTotal || 0,
                    monthTotal: preCalculated.monthTotal || 0,
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
 * Inicializa o carregamento das estatísticas quando a página carregar
 * COM ATUALIZAÇÃO AUTOMÁTICA A CADA 30 SEGUNDOS (TEMPO REAL)
 */
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        // Aguarda um pouco para garantir que os outros scripts carregaram
        setTimeout(() => {
            if (typeof ituranService !== 'undefined') {
                // Limpa cache antigo ANTES de carregar
                cleanupOldCache();

                // Carrega imediatamente
                updateDashboardStats();

                // Atualiza a cada 10 minutos (reduz MUITO a carga na API)
                console.log('⏰ Timer de atualização automática iniciado (10 minutos)');
                setInterval(() => {
                    const now = new Date();
                    console.log(`\n🔄 [${now.toLocaleTimeString()}] Atualizando dashboard...`);
                    updateDashboardStats();
                }, 10 * 60 * 1000); // 10 minutos
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
