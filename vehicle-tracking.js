// Gerenciamento de Rastreamento de Veículos com Integração Ituran
// Este arquivo contém funções para exibir detalhes, rotas e dados de telemetria dos veículos

// Variáveis globais
let currentVehicle = null;
let map = null;
let vehicleMarker = null;
let routePath = null;
let routeMarkersLayer = null; // Layer para marcadores da rota

/**
 * Abre o modal de detalhes do veículo e carrega todos os dados
 * @param {string} vehicleId - ID do veículo (pode ser placa ou ID Ituran)
 * @param {string} vehicleName - Nome/modelo do veículo
 */
async function openVehicleDetails(vehicleId, vehicleName) {
    currentVehicle = vehicleId;

    // Abre o modal
    const modal = document.getElementById('vehicle-details-modal');
    modal.classList.remove('hidden');

    // Atualiza o título
    document.getElementById('vehicle-modal-title').textContent = `Detalhes do Veículo - ${vehicleName}`;

    // Mostra estado de carregamento
    showLoadingState();

    // Carrega os dados do veículo
    await loadVehicleData(vehicleId);
}

/**
 * Fecha o modal de detalhes
 */
function closeVehicleDetails() {
    const modal = document.getElementById('vehicle-details-modal');
    modal.classList.add('hidden');
    currentVehicle = null;

    // Limpa e destrói o mapa corretamente
    if (map) {
        map.remove(); // Remove a instância do Leaflet
        map = null;
        vehicleMarker = null;
        routePath = null;
        routeMarkersLayer = null;
    }

    // Limpa o relatório de quilometragem
    const reportResult = document.getElementById('km-report-result');
    if (reportResult) {
        reportResult.innerHTML = '';
    }

    // Limpa os campos de data do relatório
    const startDateInput = document.getElementById('report-start-date');
    const endDateInput = document.getElementById('report-end-date');
    if (startDateInput) startDateInput.value = '';
    if (endDateInput) endDateInput.value = '';

    // Limpa os filtros de rota
    const routeStartDate = document.getElementById('route-start-date');
    const routeEndDate = document.getElementById('route-end-date');
    const routeStartTime = document.getElementById('route-start-time');
    const routeEndTime = document.getElementById('route-end-time');
    if (routeStartDate) routeStartDate.value = '';
    if (routeEndDate) routeEndDate.value = '';
    if (routeStartTime) routeStartTime.value = '00:00';
    if (routeEndTime) routeEndTime.value = '23:59';

    // Esconde estatísticas de rota
    const routeStats = document.getElementById('route-stats');
    if (routeStats) {
        routeStats.classList.add('hidden');
    }
}

/**
 * Mostra estado de carregamento
 */
function showLoadingState() {
    document.getElementById('vehicle-km').textContent = 'Carregando...';
    document.getElementById('vehicle-engine-status').innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-700">Carregando...</span>';
    document.getElementById('vehicle-next-maintenance').innerHTML = '<span class="text-gray-800 dark:text-white">Carregando...</span>';
    document.getElementById('vehicle-address').textContent = 'Endereço: Carregando...';
}

/**
 * Carrega todos os dados do veículo do Ituran
 * @param {string} vehicleId - ID do veículo
 */
async function loadVehicleData(vehicleId) {
    try {
        // Busca dados completos do veículo via Ituran
        const vehicleData = await ituranService.getVehicleCompleteData(vehicleId);

        // Atualiza a interface com os dados
        updateVehicleUI(vehicleData);

        // Carrega e exibe o mapa
        await loadMapWithRoute(vehicleData.location, vehicleId);

        console.log('✅ Dados do veículo carregados com sucesso!');
    } catch (error) {
        console.error('❌ Erro ao carregar dados do veículo:', error);
        showErrorState('Erro ao carregar dados do veículo');
    }
}

/**
 * Atualiza a interface com os dados do veículo
 * @param {Object} vehicleData - Dados completos do veículo
 */
function updateVehicleUI(vehicleData) {
    const { location, telemetry, maintenance } = vehicleData;

    // Atualiza Quilometragem
    document.getElementById('vehicle-km').textContent = `${telemetry.odometer.toLocaleString('pt-BR')} km`;
    document.getElementById('vehicle-km-update').textContent = `Atualizado ${formatTimeAgo(telemetry.lastUpdate)}`;

    // Atualiza Status do Motor
    const engineStatus = telemetry.engineStatus === 'on' ? 'Ligado' : 'Desligado';
    const engineClass = telemetry.engineStatus === 'on' ? 'bg-success/20 text-success' : 'bg-gray-200 text-gray-700';
    document.getElementById('vehicle-engine-status').innerHTML =
        `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${engineClass}">${engineStatus}</span>`;
    document.getElementById('vehicle-speed').textContent = `Velocidade: ${telemetry.currentSpeed} km/h`;

    // Atualiza Manutenção
    document.getElementById('vehicle-next-maintenance').innerHTML =
        `<span class="text-gray-800 dark:text-white">${maintenance.kmUntilMaintenance.toLocaleString('pt-BR')} km</span>`;

    const maintenanceClass = {
        'ok': 'text-success',
        'warning': 'text-warning',
        'critical': 'text-danger'
    }[maintenance.status] || 'text-gray-500';

    document.getElementById('vehicle-maintenance-status').className = `text-xs mt-1 ${maintenanceClass}`;
    document.getElementById('vehicle-maintenance-status').textContent =
        `Próxima manutenção em ${maintenance.nextMaintenance.toLocaleString('pt-BR')} km`;

    // Atualiza Alertas de Manutenção
    if (maintenance.alerts && maintenance.alerts.length > 0) {
        const alertsContainer = document.getElementById('maintenance-alerts-container');
        const alertsDiv = document.getElementById('maintenance-alerts');

        alertsContainer.style.display = 'block';
        alertsDiv.innerHTML = maintenance.alerts.map(alert => {
            const alertColor = {
                'high': 'danger',
                'medium': 'warning',
                'low': 'info'
            }[alert.severity] || 'info';

            return `
                <div class="flex items-center gap-3 p-3 mb-2 rounded-lg bg-${alertColor}/10 border border-${alertColor}/30">
                    <span class="material-symbols-outlined text-${alertColor}">warning</span>
                    <p class="text-sm text-gray-800 dark:text-white">${alert.message}</p>
                </div>
            `;
        }).join('');
    }

    // Atualiza Dados Técnicos
    document.getElementById('vehicle-fuel').textContent = `${telemetry.fuelLevel}%`;
    document.getElementById('vehicle-last-sync').textContent = formatTimeAgo(vehicleData.lastSync);
    document.getElementById('vehicle-lat').textContent = location.latitude.toFixed(6);
    document.getElementById('vehicle-lng').textContent = location.longitude.toFixed(6);
    document.getElementById('vehicle-address').textContent = `Endereço: ${location.address || 'Carregando...'}`;
}

/**
 * Carrega o mapa com a localização e rota do veículo usando Leaflet
 * @param {Object} location - Dados de localização do veículo
 * @param {string} vehicleId - ID do veículo
 */
async function loadMapWithRoute(location, vehicleId) {
    const mapContainer = document.getElementById('map-container');

    // Remove o mapa anterior se existir
    if (map) {
        map.remove();
        map = null;
    }

    mapContainer.innerHTML = ''; // Limpa o container

    try {
        // Cria o mapa com Leaflet (gratuito!)
        map = L.map('map-container').setView([location.latitude, location.longitude], 15);

        // Adiciona o tile layer do OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(map);

        // Cria um ícone de carro personalizado
        const carIcon = L.divIcon({
            className: 'custom-car-marker',
            html: `<div style="
                background-color: #1E3A8A;
                border: 3px solid white;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            "></div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        // Adiciona marcador do veículo
        vehicleMarker = L.marker([location.latitude, location.longitude], { icon: carIcon })
            .addTo(map)
            .bindPopup(`
                <div style="padding: 8px;">
                    <strong>Posição Atual</strong><br>
                    Velocidade: ${location.speed || 0} km/h<br>
                    Atualizado: ${formatTimeAgo(location.timestamp)}
                </div>
            `);

        // Carrega e desenha a rota
        await drawVehicleRoute(vehicleId);

        console.log('✅ Mapa carregado com sucesso!');
    } catch (error) {
        console.error('❌ Erro ao carregar mapa:', error);
        mapContainer.innerHTML = `
            <div class="w-full h-full flex flex-col items-center justify-center bg-gray-200 dark:bg-gray-600">
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-2">map</span>
                <p class="text-gray-600 dark:text-gray-300 text-center px-4">
                    <strong>Localização:</strong><br>
                    Lat: ${location.latitude.toFixed(6)}, Lng: ${location.longitude.toFixed(6)}<br>
                    <span class="text-sm">${location.address || ''}</span>
                </p>
                <p class="text-xs text-danger mt-2">Erro ao carregar mapa: ${error.message}</p>
            </div>
        `;
    }
}

/**
 * Desenha a rota do veículo no mapa usando Leaflet
 * @param {string} vehicleId - ID do veículo
 */
async function drawVehicleRoute(vehicleId) {
    if (!map) return;

    try {
        console.log('🔄 Buscando rota do veículo...');

        // Busca a rota das últimas 24 horas
        const route = await ituranService.getVehicleRoute(vehicleId, {
            startDate: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString(),
            endDate: new Date().toISOString()
        });

        if (!route || route.length === 0) {
            console.warn('⚠️ Nenhuma rota disponível para as últimas 24 horas');

            // Mostra mensagem no mapa
            L.popup()
                .setLatLng(map.getCenter())
                .setContent('<div style="text-align: center; padding: 10px;"><strong>Sem dados de rota</strong><br><span style="font-size: 12px;">Nenhum movimento registrado nas últimas 24 horas</span></div>')
                .openOn(map);
            return;
        }

        console.log(`📍 ${route.length} pontos da rota encontrados`);

        // Converte pontos da rota para o formato do Leaflet
        const pathCoordinates = route.map(point => [point.latitude, point.longitude]);

        // Desenha a polyline da rota
        routePath = L.polyline(pathCoordinates, {
            color: '#3B82F6',
            weight: 3,
            opacity: 0.8,
            smoothFactor: 1
        }).addTo(map);

        // Adiciona marcador no início da rota
        if (route.length > 0) {
            const startPoint = route[0];
            L.circleMarker([startPoint.latitude, startPoint.longitude], {
                radius: 5,
                fillColor: '#10B981',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 1
            }).addTo(map).bindPopup(`<strong>Início da rota</strong><br>${startPoint.timestamp}`);
        }

        // Adiciona marcador no fim da rota (posição atual)
        if (route.length > 1) {
            const endPoint = route[route.length - 1];
            L.circleMarker([endPoint.latitude, endPoint.longitude], {
                radius: 5,
                fillColor: '#EF4444',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 1
            }).addTo(map).bindPopup(`<strong>Última posição</strong><br>${endPoint.timestamp}`);
        }

        // Ajusta o zoom para mostrar toda a rota
        const bounds = L.latLngBounds(pathCoordinates);
        map.fitBounds(bounds, { padding: [50, 50] });

        console.log('✅ Rota desenhada no mapa com sucesso!');
    } catch (error) {
        console.error('❌ Erro ao desenhar rota:', error);

        // Mostra mensagem de erro no mapa se possível
        if (map) {
            L.popup()
                .setLatLng(map.getCenter())
                .setContent(`<div style="text-align: center; padding: 10px; color: #EF4444;"><strong>Erro ao carregar rota</strong><br><span style="font-size: 12px;">${error.message}</span></div>`)
                .openOn(map);
        }
    }
}

/**
 * Atualiza os dados do veículo
 */
async function refreshVehicleData() {
    if (!currentVehicle) return;

    const refreshButton = document.querySelector('button[onclick="refreshVehicleData()"]');
    const originalText = refreshButton.innerHTML;

    refreshButton.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">refresh</span><span>Atualizando...</span>';
    refreshButton.disabled = true;

    try {
        // Limpa o cache antes de atualizar
        ituranService.clearCache();

        // Recarrega os dados
        await loadVehicleData(currentVehicle);

        // Feedback visual
        refreshButton.innerHTML = '<span class="material-symbols-outlined text-sm">check</span><span>Atualizado!</span>';
        setTimeout(() => {
            refreshButton.innerHTML = originalText;
            refreshButton.disabled = false;
        }, 2000);
    } catch (error) {
        refreshButton.innerHTML = '<span class="material-symbols-outlined text-sm">error</span><span>Erro</span>';
        setTimeout(() => {
            refreshButton.innerHTML = originalText;
            refreshButton.disabled = false;
        }, 2000);
    }
}

/**
 * Mostra estado de erro
 * @param {string} message - Mensagem de erro
 */
function showErrorState(message) {
    document.getElementById('vehicle-km').textContent = 'Erro';
    document.getElementById('vehicle-engine-status').innerHTML =
        `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-danger/20 text-danger">Erro</span>`;
    document.getElementById('vehicle-next-maintenance').innerHTML =
        `<span class="text-gray-800 dark:text-white">-</span>`;

    alert(message);
}

/**
 * Formata timestamp para texto relativo (ex: "há 5 minutos")
 * @param {string} timestamp - ISO timestamp
 * @returns {string} Texto formatado
 */
function formatTimeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diffMs = now - past;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'agora mesmo';
    if (diffMins === 1) return 'há 1 minuto';
    if (diffMins < 60) return `há ${diffMins} minutos`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours === 1) return 'há 1 hora';
    if (diffHours < 24) return `há ${diffHours} horas`;

    const diffDays = Math.floor(diffHours / 24);
    if (diffDays === 1) return 'há 1 dia';
    return `há ${diffDays} dias`;
}

/**
 * Gera relatório de quilometragem por período
 */
async function generateKmReport() {
    if (!currentVehicle) return;

    const startDateInput = document.getElementById('report-start-date');
    const endDateInput = document.getElementById('report-end-date');
    const resultDiv = document.getElementById('km-report-result');
    const button = document.getElementById('generate-report-btn');

    if (!startDateInput.value || !endDateInput.value) {
        resultDiv.innerHTML = `
            <div class="p-4 bg-warning/10 border border-warning/30 rounded-lg">
                <p class="text-sm text-warning">Por favor, selecione as datas de início e fim</p>
            </div>
        `;
        return;
    }

    // Mostra loading
    button.disabled = true;
    button.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">refresh</span><span>Gerando...</span>';

    resultDiv.innerHTML = `
        <div class="flex items-center justify-center p-6">
            <span class="material-symbols-outlined text-3xl animate-spin text-primary">refresh</span>
        </div>
    `;

    try {
        // Converte datas para ISO string
        const startDate = new Date(startDateInput.value).toISOString();
        const endDate = new Date(endDateInput.value + 'T23:59:59').toISOString();

        // Calcula dias para alertar sobre tempo de processamento
        const diffMs = new Date(endDate) - new Date(startDate);
        const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays > 7) {
            resultDiv.innerHTML = `
                <div class="p-4 bg-info/10 border border-info/30 rounded-lg">
                    <p class="text-sm text-info">⏳ Período longo detectado (${diffDays} dias). Isso pode levar alguns segundos...</p>
                </div>
            `;
        }

        // Gera relatório
        const report = await ituranService.getKilometerReport(currentVehicle, startDate, endDate);

        if (!report.success) {
            resultDiv.innerHTML = `
                <div class="p-4 bg-warning/10 border border-warning/30 rounded-lg">
                    <p class="text-sm text-warning">${report.message}</p>
                </div>
            `;
            return;
        }

        // Exibe relatório
        resultDiv.innerHTML = `
            <div class="bg-gradient-to-r from-primary/10 to-secondary/10 rounded-lg p-6 border border-primary/20">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">📊 Relatório do Período</h3>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Data Inicial</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">${new Date(report.startDate).toLocaleString('pt-BR')}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Data Final</p>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">${new Date(report.endDate).toLocaleString('pt-BR')}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">KM Inicial</p>
                        <p class="text-2xl font-bold text-primary">${report.startOdometer.toLocaleString('pt-BR')}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">KM Final</p>
                        <p class="text-2xl font-bold text-secondary">${report.endOdometer.toLocaleString('pt-BR')}</p>
                    </div>
                </div>

                <div class="bg-success/20 border-2 border-success rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-700 dark:text-gray-300">Quilometragem Rodada</p>
                    <p class="text-4xl font-bold text-success">${report.kmDriven.toLocaleString('pt-BR')} km</p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white dark:bg-gray-700 rounded p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pontos de Rota</p>
                        <p class="text-lg font-semibold text-gray-800 dark:text-white">${report.routePoints.toLocaleString('pt-BR')}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-700 rounded p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Vel. Média</p>
                        <p class="text-lg font-semibold text-gray-800 dark:text-white">${report.avgSpeed} km/h</p>
                    </div>
                    <div class="bg-white dark:bg-gray-700 rounded p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Vel. Máxima</p>
                        <p class="text-lg font-semibold text-gray-800 dark:text-white">${report.maxSpeed} km/h</p>
                    </div>
                </div>
            </div>
        `;

        // Atualiza mapa com a rota do período
        if (report.route && report.route.length > 0 && map) {
            // Remove rota anterior
            if (routePath) {
                map.removeLayer(routePath);
            }

            // Desenha nova rota
            const pathCoordinates = report.route.map(point => [point.latitude, point.longitude]);
            routePath = L.polyline(pathCoordinates, {
                color: '#10B981',
                weight: 3,
                opacity: 0.8,
                smoothFactor: 1
            }).addTo(map);

            // Ajusta zoom
            const bounds = L.latLngBounds(pathCoordinates);
            map.fitBounds(bounds, { padding: [50, 50] });

            console.log('✅ Rota do período desenhada no mapa');
        }

    } catch (error) {
        console.error('❌ Erro ao gerar relatório:', error);
        resultDiv.innerHTML = `
            <div class="p-4 bg-danger/10 border border-danger/30 rounded-lg">
                <p class="text-sm text-danger">Erro ao gerar relatório: ${error.message}</p>
            </div>
        `;
    } finally {
        button.disabled = false;
        button.innerHTML = '<span class="material-symbols-outlined text-sm">assessment</span><span>Gerar Relatório</span>';
    }
}

/**
 * Define filtros de período predefinidos
 * @param {string} filterType - Tipo de filtro (today, yesterday, last7days, etc.)
 */
function setRouteFilter(filterType) {
    const startDateInput = document.getElementById('route-start-date');
    const endDateInput = document.getElementById('route-end-date');
    const startTimeInput = document.getElementById('route-start-time');
    const endTimeInput = document.getElementById('route-end-time');

    const now = new Date();
    let startDate = new Date();
    let endDate = new Date();

    switch(filterType) {
        case 'today':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            endDate = now;
            startTimeInput.value = '00:00';
            endTimeInput.value = '23:59';
            break;

        case 'yesterday':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
            endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
            startTimeInput.value = '00:00';
            endTimeInput.value = '23:59';
            break;

        case 'last7days':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6);
            endDate = now;
            startTimeInput.value = '00:00';
            endTimeInput.value = '23:59';
            break;

        case 'last30days':
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29);
            endDate = now;
            startTimeInput.value = '00:00';
            endTimeInput.value = '23:59';
            break;

        case 'thisWeek':
            // Começa na segunda-feira
            const dayOfWeek = now.getDay();
            const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + diffToMonday);
            endDate = now;
            startTimeInput.value = '00:00';
            endTimeInput.value = '23:59';
            break;

        case 'lastWeek':
            // Semana passada (segunda a domingo)
            const lastWeekDay = now.getDay();
            const diffToLastMonday = lastWeekDay === 0 ? -13 : -6 - lastWeekDay;
            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + diffToLastMonday);
            endDate = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + 6);
            startTimeInput.value = '00:00';
            endTimeInput.value = '23:59';
            break;
    }

    // Formata as datas para o input date (YYYY-MM-DD)
    startDateInput.value = startDate.toISOString().split('T')[0];
    endDateInput.value = endDate.toISOString().split('T')[0];

    console.log(`📅 Filtro aplicado: ${filterType} - ${startDateInput.value} a ${endDateInput.value}`);
}

/**
 * Carrega a rota do veículo com base nos filtros selecionados
 */
async function loadFilteredRoute() {
    if (!currentVehicle || !map) return;

    const startDateInput = document.getElementById('route-start-date');
    const endDateInput = document.getElementById('route-end-date');
    const startTimeInput = document.getElementById('route-start-time');
    const endTimeInput = document.getElementById('route-end-time');
    const button = document.getElementById('load-route-btn');
    const routeStatsDiv = document.getElementById('route-stats');

    if (!startDateInput.value || !endDateInput.value) {
        alert('Por favor, selecione as datas de início e fim');
        return;
    }

    // Mostra loading
    const originalButtonHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">refresh</span><span>Carregando...</span>';

    try {
        // Monta as datas com horário
        const startDateTime = `${startDateInput.value}T${startTimeInput.value}:00`;
        const endDateTime = `${endDateInput.value}T${endTimeInput.value}:59`;

        console.log(`🔄 Carregando rota de ${startDateTime} até ${endDateTime}`);

        // Busca a rota do período
        const route = await ituranService.getVehicleRoute(currentVehicle, {
            startDate: new Date(startDateTime).toISOString(),
            endDate: new Date(endDateTime).toISOString()
        });

        if (!route || route.length === 0) {
            alert('Nenhuma rota encontrada para o período selecionado');
            routeStatsDiv.classList.add('hidden');
            return;
        }

        console.log(`📍 ${route.length} pontos da rota encontrados`);

        // Limpa a rota anterior
        if (routePath) {
            map.removeLayer(routePath);
        }
        if (routeMarkersLayer) {
            map.removeLayer(routeMarkersLayer);
        }

        // Cria um layer group para os marcadores
        routeMarkersLayer = L.layerGroup().addTo(map);

        // Converte pontos da rota para o formato do Leaflet
        const pathCoordinates = route.map(point => [point.latitude, point.longitude]);

        // Desenha a polyline da rota
        routePath = L.polyline(pathCoordinates, {
            color: '#10B981',
            weight: 4,
            opacity: 0.8,
            smoothFactor: 1
        }).addTo(map);

        // Adiciona marcador no início da rota
        if (route.length > 0) {
            const startPoint = route[0];
            const startMarker = L.circleMarker([startPoint.latitude, startPoint.longitude], {
                radius: 8,
                fillColor: '#10B981',
                color: '#ffffff',
                weight: 3,
                fillOpacity: 1
            }).bindPopup(`
                <div style="padding: 8px;">
                    <strong>🚀 Início da Rota</strong><br>
                    Data: ${new Date(startPoint.timestamp).toLocaleString('pt-BR')}<br>
                    Velocidade: ${startPoint.speed || 0} km/h
                </div>
            `);
            routeMarkersLayer.addLayer(startMarker);
        }

        // Adiciona marcador no fim da rota
        if (route.length > 1) {
            const endPoint = route[route.length - 1];
            const endMarker = L.circleMarker([endPoint.latitude, endPoint.longitude], {
                radius: 8,
                fillColor: '#EF4444',
                color: '#ffffff',
                weight: 3,
                fillOpacity: 1
            }).bindPopup(`
                <div style="padding: 8px;">
                    <strong>🏁 Fim da Rota</strong><br>
                    Data: ${new Date(endPoint.timestamp).toLocaleString('pt-BR')}<br>
                    Velocidade: ${endPoint.speed || 0} km/h
                </div>
            `);
            routeMarkersLayer.addLayer(endMarker);
        }

        // Detecta e marca paradas (pontos onde velocidade = 0 por mais de 5 minutos)
        const stops = detectStops(route);
        stops.forEach((stop, index) => {
            const stopMarker = L.circleMarker([stop.latitude, stop.longitude], {
                radius: 6,
                fillColor: '#F59E0B',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 1
            }).bindPopup(`
                <div style="padding: 8px;">
                    <strong>⏸️ Parada ${index + 1}</strong><br>
                    Início: ${new Date(stop.startTime).toLocaleString('pt-BR')}<br>
                    Duração: ${stop.durationMinutes} min
                </div>
            `);
            routeMarkersLayer.addLayer(stopMarker);
        });

        // Ajusta o zoom para mostrar toda a rota
        const bounds = L.latLngBounds(pathCoordinates);
        map.fitBounds(bounds, { padding: [50, 50] });

        // Calcula e exibe estatísticas
        const stats = calculateRouteStats(route);
        displayRouteStats(stats, stops.length);

        console.log('✅ Rota carregada e exibida com sucesso!');

    } catch (error) {
        console.error('❌ Erro ao carregar rota filtrada:', error);
        alert(`Erro ao carregar rota: ${error.message}`);
        routeStatsDiv.classList.add('hidden');
    } finally {
        button.disabled = false;
        button.innerHTML = originalButtonHtml;
    }
}

/**
 * Detecta paradas na rota (velocidade zero por período prolongado)
 * @param {Array} route - Array de pontos da rota
 * @returns {Array} Array de paradas detectadas
 */
function detectStops(route) {
    const stops = [];
    let currentStop = null;
    const MIN_STOP_DURATION_MS = 5 * 60 * 1000; // 5 minutos

    for (let i = 0; i < route.length; i++) {
        const point = route[i];
        const speed = point.speed || 0;

        if (speed < 1) { // Considerando parado se velocidade < 1 km/h
            if (!currentStop) {
                currentStop = {
                    latitude: point.latitude,
                    longitude: point.longitude,
                    startTime: point.timestamp,
                    startIndex: i
                };
            }
        } else {
            if (currentStop) {
                const endTime = route[i - 1].timestamp;
                const duration = new Date(endTime) - new Date(currentStop.startTime);

                if (duration >= MIN_STOP_DURATION_MS) {
                    stops.push({
                        ...currentStop,
                        endTime,
                        durationMinutes: Math.round(duration / 60000)
                    });
                }
                currentStop = null;
            }
        }
    }

    // Verifica última parada
    if (currentStop && route.length > 0) {
        const endTime = route[route.length - 1].timestamp;
        const duration = new Date(endTime) - new Date(currentStop.startTime);

        if (duration >= MIN_STOP_DURATION_MS) {
            stops.push({
                ...currentStop,
                endTime,
                durationMinutes: Math.round(duration / 60000)
            });
        }
    }

    return stops;
}

/**
 * Calcula estatísticas da rota
 * @param {Array} route - Array de pontos da rota
 * @returns {Object} Objeto com estatísticas calculadas
 */
function calculateRouteStats(route) {
    if (!route || route.length === 0) {
        return {
            distance: 0,
            duration: 0,
            avgSpeed: 0,
            maxSpeed: 0
        };
    }

    // Calcula distância total usando odômetro
    const startOdometer = route[0].odometer || 0;
    const endOdometer = route[route.length - 1].odometer || 0;
    const distance = endOdometer - startOdometer;

    // Calcula duração
    const startTime = new Date(route[0].timestamp);
    const endTime = new Date(route[route.length - 1].timestamp);
    const durationMs = endTime - startTime;
    const durationHours = durationMs / (1000 * 60 * 60);

    // Calcula velocidades
    let totalSpeed = 0;
    let speedCount = 0;
    let maxSpeed = 0;

    route.forEach(point => {
        const speed = point.speed || 0;
        if (speed > 0) {
            totalSpeed += speed;
            speedCount++;
        }
        if (speed > maxSpeed) {
            maxSpeed = speed;
        }
    });

    const avgSpeed = speedCount > 0 ? Math.round(totalSpeed / speedCount) : 0;

    return {
        distance: Math.round(distance * 10) / 10, // Arredonda para 1 casa decimal
        durationMs,
        durationHours: Math.round(durationHours * 10) / 10,
        avgSpeed,
        maxSpeed: Math.round(maxSpeed)
    };
}

/**
 * Exibe as estatísticas da rota na interface
 * @param {Object} stats - Estatísticas calculadas
 * @param {number} stopsCount - Número de paradas
 */
function displayRouteStats(stats, stopsCount) {
    const routeStatsDiv = document.getElementById('route-stats');

    // Formata duração para texto legível
    const durationHours = Math.floor(stats.durationMs / (1000 * 60 * 60));
    const durationMinutes = Math.floor((stats.durationMs % (1000 * 60 * 60)) / (1000 * 60));
    const durationText = durationHours > 0
        ? `${durationHours}h ${durationMinutes}min`
        : `${durationMinutes}min`;

    // Atualiza os valores
    document.getElementById('route-distance').textContent = `${stats.distance.toLocaleString('pt-BR')} km`;
    document.getElementById('route-duration').textContent = durationText;
    document.getElementById('route-avg-speed').textContent = `${stats.avgSpeed} km/h`;
    document.getElementById('route-max-speed').textContent = `${stats.maxSpeed} km/h`;
    document.getElementById('route-stops').textContent = stopsCount;

    // Mostra o painel de estatísticas
    routeStatsDiv.classList.remove('hidden');
}

// Expõe funções globalmente
window.openVehicleDetails = openVehicleDetails;
window.closeVehicleDetails = closeVehicleDetails;
window.refreshVehicleData = refreshVehicleData;
window.generateKmReport = generateKmReport;
window.setRouteFilter = setRouteFilter;
window.loadFilteredRoute = loadFilteredRoute;
