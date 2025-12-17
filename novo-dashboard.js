// Novo Dashboard - Sistema de Busca Detalhada de Quilometragem
// ================================================================

const API_BASE = '/api';

// Estado global dos filtros
const filters = {
    period: 'today', // today, yesterday, week, month, custom
    preset: 'today', // NOVO: preset selecionado no dropdown
    startDate: null,
    endDate: null,
    plate: '',
    vehicleType: '',
    base: '',
    costCenter: '',
    status: ''
};

// ========== INICIALIZAÇÃO ==========

document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Novo Dashboard inicializado');

    // Carregar opções dos filtros
    await loadFilterOptions();

    // Configurar event listeners
    setupEventListeners();

    // Carregar dados iniciais
    await loadDashboardData();

    // Iniciar atualização automática a cada 5 minutos
    setInterval(loadDashboardData, 5 * 60 * 1000);
});

// ========== CARREGAR OPÇÕES DOS FILTROS ==========

async function loadFilterOptions() {
    try {
        const response = await fetch(`${API_BASE}/km/filters`);

        if (!response.ok) {
            console.warn('⚠️ API de filtros não disponível, continuando sem filtros');
            return;
        }

        const data = await response.json();

        if (data && data.success && data.filters) {
            // Popular selectores com opções reais
            if (Array.isArray(data.filters.vehicleTypes) && data.filters.vehicleTypes.length > 0) {
                populateSelect('vehicleTypeSelect', data.filters.vehicleTypes, 'Tipo de Veículo');
            }

            // Usar bases (localidades do Ituran)
            if (Array.isArray(data.filters.bases) && data.filters.bases.length > 0) {
                populateSelect('baseSelect', data.filters.bases, 'Base/Localidade');
            }

            if (Array.isArray(data.filters.statuses) && data.filters.statuses.length > 0) {
                populateSelect('statusSelect', data.filters.statuses, 'Status do Veículo');
            }

            console.log('✅ Filtros carregados:', data.filters);
        } else {
            console.warn('⚠️ Sem dados de filtros disponíveis');
        }
    } catch (error) {
        console.warn('⚠️ Erro ao carregar filtros (continuando sem filtros):', error.message);
    }
}

function populateSelect(selectId, options, placeholder) {
    const select = document.getElementById(selectId);
    if (!select) return;

    // Limpar opções existentes (exceto a primeira)
    while (select.options.length > 1) {
        select.remove(1);
    }

    // Adicionar novas opções
    options.forEach(option => {
        const opt = document.createElement('option');
        opt.value = option;
        opt.textContent = option;
        select.appendChild(opt);
    });
}

// ========== EVENT LISTENERS ==========

function setupEventListeners() {
    // Dropdown de período preset
    const periodPreset = document.getElementById('periodPreset');
    if (periodPreset) {
        periodPreset.addEventListener('change', () => {
            const preset = periodPreset.value;
            filters.preset = preset;

            const customDateRange = document.getElementById('customDateRange');
            if (preset === 'custom') {
                // Mostrar date pickers
                customDateRange.classList.remove('hidden');

                // Definir datas padrão (último mês)
                const hoje = new Date();
                const umMesAtras = new Date(hoje.getTime() - 30 * 24 * 60 * 60 * 1000);

                document.getElementById('startDatePicker').value = umMesAtras.toISOString().split('T')[0];
                document.getElementById('endDatePicker').value = hoje.toISOString().split('T')[0];
            } else {
                // Ocultar date pickers
                customDateRange.classList.add('hidden');

                // Calcular datas baseado no preset
                const dates = getPresetDates(preset);
                filters.startDate = dates.startDate;
                filters.endDate = dates.endDate;
                filters.period = preset;

                loadDashboardData();
            }
        });
    }

    // Botão "Aplicar" do período customizado
    const applyCustomDate = document.getElementById('applyCustomDate');
    if (applyCustomDate) {
        applyCustomDate.addEventListener('click', () => {
            const startDate = document.getElementById('startDatePicker').value;
            const endDate = document.getElementById('endDatePicker').value;

            if (!startDate || !endDate) {
                alert('Por favor, selecione ambas as datas');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Data inicial não pode ser maior que data final');
                return;
            }

            const hoje = new Date().toISOString().split('T')[0];
            if (endDate > hoje) {
                alert('Data final não pode ser no futuro');
                return;
            }

            filters.startDate = startDate;
            filters.endDate = endDate;
            filters.period = 'custom';

            loadDashboardData();
        });
    }

    // Campos de busca
    const searchInputs = document.querySelectorAll('[data-filter]');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(() => {
            filters[input.dataset.filter] = input.value;
            loadDashboardData();
        }, 500));
    });

    // Selectores
    const selects = document.querySelectorAll('[data-filter-select]');
    selects.forEach(select => {
        select.addEventListener('change', () => {
            filters[select.dataset.filterSelect] = select.value;
            loadDashboardData();
        });
    });

    // Botão de sincronizar
    const syncBtn = document.getElementById('syncBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', () => {
            console.log('🔄 Sincronizando quilometragem...');
            syncKilometers();
        });
    }

    // Botão de limpar cache
    const clearCacheBtn = document.getElementById('clearCacheBtn');
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', () => {
            if (confirm('Deseja limpar o cache? Isso forçará um recálculo completo.')) {
                clearFleetFlowData();
                window.location.reload();
            }
        });
    }

    // Botão de exportar
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportToExcel);
    }
}

// Debounce para otimizar chamadas de API
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ========== CARREGAR DADOS DO DASHBOARD ==========

async function loadDashboardData() {
    try {
        console.log('📊 Carregando dados do dashboard com filtros:', filters);

        // Mostrar loading
        showLoading();

        // Buscar dados por período
        await loadPeriodData();

        // Buscar dados detalhados com filtros
        await loadDetailedData();

        // Esconder loading
        hideLoading();

    } catch (error) {
        console.error('❌ Erro ao carregar dados do dashboard:', error);
        hideLoading();
    }
}

// ========== BUSCAR DADOS POR PERÍODO ==========

async function loadPeriodData() {
    try {
        const response = await fetch(`${API_BASE}/km/by-period?period=${filters.period}`);
        const data = await response.json();

        if (data.success) {
            updatePeriodCards(data);
            console.log(`✅ Dados de ${filters.period} carregados: ${data.totalKm} km`);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar dados do período:', error);
    }
}

function updatePeriodCards(data) {
    // Atualizar card de quilometragem diária
    const dailyKmCard = document.getElementById('dailyKmCard');
    if (dailyKmCard && filters.period === 'today') {
        dailyKmCard.textContent = `${data.totalKm.toLocaleString('pt-BR')} km`;
    }

    // Atualizar card de quilometragem ontem
    const yesterdayKmCard = document.getElementById('yesterdayKmCard');
    if (yesterdayKmCard && filters.period === 'yesterday') {
        yesterdayKmCard.textContent = `${data.totalKm.toLocaleString('pt-BR')} km`;
    }

    // Atualizar card de quilometragem mensal
    const monthlyKmCard = document.getElementById('monthlyKmCard');
    if (monthlyKmCard && filters.period === 'month') {
        monthlyKmCard.textContent = `${data.totalKm.toLocaleString('pt-BR')} km`;
    }

    // Atualizar total de carros em movimento
    const movingCarsCard = document.getElementById('movingCarsCard');
    if (movingCarsCard) {
        const movingCount = data.data.filter(v => v.totalKm > 0).length;
        movingCarsCard.textContent = movingCount;
    }
}

// ========== CALCULAR DATAS POR PRESET ==========

function getPresetDates(preset) {
    const now = new Date();
    let startDate, endDate;

    switch (preset) {
        case 'today':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
            break;

        case 'yesterday':
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate());
            endDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 23, 59, 59);
            break;

        case 'last7days':
            startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            endDate = new Date();
            break;

        case 'last15days':
            startDate = new Date(now.getTime() - 15 * 24 * 60 * 60 * 1000);
            endDate = new Date();
            break;

        case 'last30days':
            startDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            endDate = new Date();
            break;

        case 'lastweek':
            // Semana passada (segunda a domingo)
            const dayOfWeek = now.getDay();
            const diffToLastMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
            const lastMonday = new Date(now.getTime() - (diffToLastMonday + 7) * 24 * 60 * 60 * 1000);
            const lastSunday = new Date(lastMonday.getTime() + 6 * 24 * 60 * 60 * 1000);

            startDate = new Date(lastMonday.getFullYear(), lastMonday.getMonth(), lastMonday.getDate());
            endDate = new Date(lastSunday.getFullYear(), lastSunday.getMonth(), lastSunday.getDate(), 23, 59, 59);
            break;

        case 'lastmonth':
            // Mês passado (1º ao último dia)
            const lastMonthDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            startDate = new Date(lastMonthDate.getFullYear(), lastMonthDate.getMonth(), 1);
            endDate = new Date(lastMonthDate.getFullYear(), lastMonthDate.getMonth() + 1, 0, 23, 59, 59);
            break;

        default:
            startDate = new Date();
            endDate = new Date();
    }

    return {
        startDate: startDate.toISOString().split('T')[0],
        endDate: endDate.toISOString().split('T')[0]
    };
}

// ========== BUSCAR DADOS DETALHADOS ==========

async function loadDetailedData() {
    try {
        // Construir query string com filtros
        const params = new URLSearchParams();

        // Adicionar datas: usar startDate/endDate se disponíveis (período customizado)
        // Caso contrário, calcular baseado no período
        if (filters.startDate && filters.endDate) {
            params.append('startDate', filters.startDate);
            params.append('endDate', filters.endDate);
        } else {
            const dates = getPeriodDates(filters.period);
            if (dates.startDate) params.append('startDate', dates.startDate);
            if (dates.endDate) params.append('endDate', dates.endDate);
        }

        // Adicionar outros filtros
        if (filters.plate) params.append('plate', filters.plate);
        if (filters.vehicleType) params.append('vehicleType', filters.vehicleType);
        if (filters.base) params.append('base', filters.base);
        if (filters.costCenter) params.append('costCenter', filters.costCenter);
        if (filters.status) params.append('status', filters.status);

        // Usar o novo endpoint de telemetria que consulta a API PHP
        const response = await fetch(`${API_BASE}/telemetry/daily?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            updateDetailedTable(data.data);
            console.log(`✅ ${data.data.length} registros de telemetria carregados`);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar dados detalhados:', error);
    }
}

function getPeriodDates(period) {
    const now = new Date();
    let startDate, endDate;

    switch (period) {
        case 'today':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
            break;
        case 'yesterday':
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate());
            endDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 23, 59, 59);
            break;
        case 'week':
            const dayOfWeek = now.getDay();
            const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + diffToMonday);
            endDate = new Date();
            break;
        case 'month':
            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            endDate = new Date();
            break;
        default:
            return {};
    }

    return {
        startDate: startDate.toISOString().split('T')[0],
        endDate: endDate.toISOString().split('T')[0]
    };
}

// ========== ATUALIZAR TABELA DETALHADA ==========

function updateDetailedTable(vehicles) {
    const tbody = document.getElementById('detailedTableBody');
    if (!tbody) return;

    if (vehicles.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    Nenhum veículo encontrado com os filtros selecionados
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = vehicles.map(vehicle => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer" onclick="window.location.href='veiculos.html?plate=${vehicle.plate}'">
            <td class="p-4 font-medium text-[#111418] dark:text-white">${vehicle.plate}</td>
            <td class="p-4">${vehicle.model || 'N/A'}</td>
            <td class="p-4">${(vehicle.totalKm || 0).toLocaleString('pt-BR')} km</td>
            <td class="p-4">-</td>
            <td class="p-4">-</td>
        </tr>
    `).join('');
}

// ========== ATUALIZAR TOP 10 VEÍCULOS ==========

function updateTopVehicles(vehicles) {
    const container = document.getElementById('topVehiclesContainer');
    if (!container) return;

    // Ordenar por KM e pegar os Top 10
    const top10 = vehicles
        .sort((a, b) => b.totalKm - a.totalKm)
        .slice(0, 10);

    if (top10.length === 0) {
        container.innerHTML = `
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                Nenhum veículo em movimento
            </div>
        `;
        return;
    }

    container.innerHTML = top10.map((vehicle, index) => `
        <div class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <p class="text-sm font-medium text-[#111418] dark:text-white">
                ${index + 1}. Placa ${vehicle.plate}
            </p>
            <p class="text-sm font-bold text-primary">
                ${vehicle.totalKm.toLocaleString('pt-BR')} km
            </p>
        </div>
    `).join('');
}

// ========== SINCRONIZAR QUILOMETRAGEM ==========

async function syncKilometers() {
    try {
        console.log('🔄 Iniciando sincronização de quilometragem...');

        // Mostrar barra de progresso
        const progressBar = document.getElementById('syncProgressBar');
        if (progressBar) {
            progressBar.classList.remove('hidden');
        }

        // Chamar dashboard-stats.js para recalcular
        if (typeof calculateInBackground === 'function') {
            await calculateInBackground();

            // Recarregar dados após sincronização
            await loadDashboardData();

            console.log('✅ Sincronização concluída!');
        } else {
            console.warn('⚠️ Função calculateInBackground não encontrada');
        }

    } catch (error) {
        console.error('❌ Erro na sincronização:', error);
    }
}

// ========== EXPORTAR PARA EXCEL ==========

async function exportToExcel() {
    try {
        console.log('📥 Exportando dados para Excel...');

        // Buscar todos os dados sem paginação
        const params = new URLSearchParams();
        const dates = getPeriodDates(filters.period);
        if (dates.startDate) params.append('startDate', dates.startDate);
        if (dates.endDate) params.append('endDate', dates.endDate);
        if (filters.plate) params.append('plate', filters.plate);
        if (filters.vehicleType) params.append('vehicleType', filters.vehicleType);
        if (filters.base) params.append('base', filters.base);
        if (filters.costCenter) params.append('costCenter', filters.costCenter);
        if (filters.status) params.append('status', filters.status);

        const response = await fetch(`${API_BASE}/km/detailed?${params.toString()}`);
        const data = await response.json();

        if (!data.success || data.data.length === 0) {
            alert('Nenhum dado para exportar');
            return;
        }

        // Criar CSV
        let csv = 'Placa,Modelo,Tipo,Base,Centro de Custo,Status,KM Total\n';

        data.data.forEach(vehicle => {
            csv += `${vehicle.plate},${vehicle.model || 'N/A'},${vehicle.vehicleType || 'N/A'},${vehicle.base || 'N/A'},${vehicle.costCenter || 'N/A'},${vehicle.status || 'N/A'},${vehicle.totalKm}\n`;
        });

        // Download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `relatorio_km_${filters.period}_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();

        console.log('✅ Exportação concluída!');

    } catch (error) {
        console.error('❌ Erro ao exportar:', error);
        alert('Erro ao exportar dados');
    }
}

// ========== LOADING ==========

function showLoading() {
    // Você pode adicionar um indicador de loading aqui
    console.log('⏳ Carregando...');
}

function hideLoading() {
    console.log('✅ Carregado!');
}

// ========== FUNÇÕES AUXILIARES ==========

// Expor funções globalmente para acesso via console
window.novoDashboard = {
    filters,
    loadDashboardData,
    syncKilometers,
    exportToExcel
};

console.log('✅ Novo Dashboard JavaScript carregado!');
console.log('💡 Acesse window.novoDashboard para debug');
