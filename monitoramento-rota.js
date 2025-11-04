// Estado da aplicação
let map;
let routeId;
let plannedRoute = null;
let actualRoute = [];
let vehicleMarker = null;
let plannedRouteLayer = null;
let actualRouteLayer = null;
let plannedMarkers = [];
let updateInterval = null;

// Inicialização
document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    routeId = urlParams.get('id');

    if (!routeId) {
        alert('ID da rota não fornecido');
        window.location.href = 'rotas.html';
        return;
    }

    await initMap();
    await loadRouteData();
    setupEventListeners();
    startMonitoring();
});

// Inicializar mapa
function initMap() {
    map = L.map('map').setView([-20.3155, -40.3128], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
}

// Carregar dados da rota
async function loadRouteData() {
    try {
        const response = await fetch(`/api/routes/${routeId}`);
        const data = await response.json();

        if (data.success) {
            plannedRoute = data.route;
            displayRouteInfo();
            drawPlannedRoute();
        } else {
            alert('Erro ao carregar rota');
            window.location.href = 'rotas.html';
        }
    } catch (error) {
        console.error('Erro ao carregar rota:', error);
        alert('Erro ao carregar rota');
    }
}

// Exibir informações da rota
function displayRouteInfo() {
    document.getElementById('routeName').textContent = `Rota carregada - ${plannedRoute.waypoints.length} paradas`;
    document.getElementById('distanciaPlaneada').textContent = `${(plannedRoute.totalDistance / 1000).toFixed(2)} km`;

    // Preencher tabela de paradas
    const tbody = document.getElementById('tabelaParadas');
    tbody.innerHTML = '';

    plannedRoute.waypoints.forEach((waypoint, index) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700';
        tr.innerHTML = `
            <td class="px-4 py-3 text-sm font-medium">${index + 1}</td>
            <td class="px-4 py-3 text-sm">${waypoint.address}</td>
            <td class="px-4 py-3 text-sm">-</td>
            <td class="px-4 py-3 text-sm">-</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                    Pendente
                </span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Desenhar rota planejada no mapa
function drawPlannedRoute() {
    // Limpar marcadores antigos
    plannedMarkers.forEach(marker => map.removeLayer(marker));
    plannedMarkers = [];

    // Adicionar marcadores das paradas
    plannedRoute.waypoints.forEach((waypoint, index) => {
        const isStart = index === 0;
        const isEnd = index === plannedRoute.waypoints.length - 1;

        const icon = L.divIcon({
            html: `<div style="background-color: ${isStart ? '#10B981' : isEnd ? '#EF4444' : '#3B82F6'}; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white;">${index + 1}</div>`,
            className: '',
            iconSize: [30, 30]
        });

        const marker = L.marker([waypoint.lat, waypoint.lon], { icon })
            .addTo(map)
            .bindPopup(`<b>${isStart ? 'Partida' : isEnd ? 'Destino' : 'Parada ' + index}</b><br>${waypoint.address}`);

        plannedMarkers.push(marker);
    });

    // Desenhar linha da rota planejada
    const coordinates = plannedRoute.waypoints.map(wp => [wp.lat, wp.lon]);
    plannedRouteLayer = L.polyline(coordinates, {
        color: '#3B82F6',
        weight: 4,
        opacity: 0.7,
        dashArray: '10, 10'
    }).addTo(map);

    map.fitBounds(plannedRouteLayer.getBounds(), { padding: [50, 50] });
}

// Iniciar monitoramento em tempo real
function startMonitoring() {
    // Atualizar posição a cada 10 segundos
    updateInterval = setInterval(async () => {
        await updateVehiclePosition();
    }, 10000);
}

// Atualizar posição do veículo
async function updateVehiclePosition() {
    try {
        const response = await fetch(`/api/routes/${routeId}/monitor`);
        const data = await response.json();

        if (data.success) {
            updateMap(data);
            updateStats(data);
        }
    } catch (error) {
        console.error('Erro ao atualizar posição:', error);
    }
}

// Atualizar mapa com posição atual
function updateMap(data) {
    // Implementar lógica para desenhar rota real e posição do veículo
    // Por enquanto, apenas simular

    if (data.actualRoute && data.actualRoute.length > 0) {
        // Remover rota anterior
        if (actualRouteLayer) {
            map.removeLayer(actualRouteLayer);
        }

        // Desenhar rota executada
        const coordinates = data.actualRoute.map(point => [point.lat, point.lon]);
        actualRouteLayer = L.polyline(coordinates, {
            color: '#10B981',
            weight: 4,
            opacity: 0.9
        }).addTo(map);

        // Atualizar marcador do veículo
        const lastPosition = data.actualRoute[data.actualRoute.length - 1];

        if (vehicleMarker) {
            vehicleMarker.setLatLng([lastPosition.lat, lastPosition.lon]);
        } else {
            const vehicleIcon = L.divIcon({
                html: '<div style="background-color: #10B981; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"><span style="font-size: 12px;">🚗</span></div>',
                className: '',
                iconSize: [20, 20]
            });

            vehicleMarker = L.marker([lastPosition.lat, lastPosition.lon], { icon: vehicleIcon })
                .addTo(map)
                .bindPopup('<b>Posição Atual do Veículo</b>');
        }
    }
}

// Atualizar estatísticas
function updateStats(data) {
    document.getElementById('distanciaPercorrida').textContent = `${(data.actualDistance || 0).toFixed(2)} km`;
    document.getElementById('desvioRota').textContent = `${(data.deviation || 0).toFixed(2)} km`;
    document.getElementById('conformidade').textContent = `${(data.compliance || 100).toFixed(0)}%`;
}

// Setup event listeners
function setupEventListeners() {
    document.getElementById('btnIniciarRota').addEventListener('click', handleStartRoute);
    document.getElementById('btnConcluirRota').addEventListener('click', handleCompleteRoute);
    document.getElementById('btnTogglePlannedRoute').addEventListener('click', togglePlannedRoute);
    document.getElementById('btnToggleActualRoute').addEventListener('click', toggleActualRoute);
}

// Iniciar rota
async function handleStartRoute() {
    if (!confirm('Deseja iniciar esta rota?')) {
        return;
    }

    try {
        const response = await fetch(`/api/routes/${routeId}/start`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            alert('Rota iniciada!');
            document.getElementById('btnIniciarRota').classList.add('hidden');
            document.getElementById('btnConcluirRota').classList.remove('hidden');
        } else {
            alert('Erro ao iniciar rota: ' + data.error);
        }
    } catch (error) {
        console.error('Erro ao iniciar rota:', error);
        alert('Erro ao iniciar rota');
    }
}

// Concluir rota
async function handleCompleteRoute() {
    if (!confirm('Deseja concluir esta rota?')) {
        return;
    }

    try {
        const response = await fetch(`/api/routes/${routeId}/complete`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            alert('Rota concluída!');
            clearInterval(updateInterval);
            window.location.href = 'rotas.html';
        } else {
            alert('Erro ao concluir rota: ' + data.error);
        }
    } catch (error) {
        console.error('Erro ao concluir rota:', error);
        alert('Erro ao concluir rota');
    }
}

// Toggle rota planejada
function togglePlannedRoute() {
    if (plannedRouteLayer) {
        if (map.hasLayer(plannedRouteLayer)) {
            map.removeLayer(plannedRouteLayer);
            plannedMarkers.forEach(m => map.removeLayer(m));
        } else {
            map.addLayer(plannedRouteLayer);
            plannedMarkers.forEach(m => map.addLayer(m));
        }
    }
}

// Toggle rota executada
function toggleActualRoute() {
    if (actualRouteLayer) {
        if (map.hasLayer(actualRouteLayer)) {
            map.removeLayer(actualRouteLayer);
        } else {
            map.addLayer(actualRouteLayer);
        }
    }
}

// Cleanup ao sair da página
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});
