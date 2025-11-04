// Estado da aplicação
let map;
let markers = [];
let routeLayer = null;
let currentRoute = null;
let stopCounter = 1;
let selectingMode = null; // 'partida', 'parada', 'destino'
let tempMarker = null;
let partidaMarker = null;
let destinoMarker = null;
let paradaMarkers = {}; // { 'parada1': marker, 'parada2': marker, ... }

// Inicialização
document.addEventListener('DOMContentLoaded', async () => {
    await initMap();
    await loadVehicles();
    await loadDrivers();
    await loadSavedRoutes();
    setupEventListeners();
});

// Inicializar mapa Leaflet
function initMap() {
    // Centro em Vitória, ES
    map = L.map('map').setView([-20.3155, -40.3128], 13);

    // Usar OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Adicionar evento de clique no mapa
    map.on('click', handleMapClick);
}

// Handler para clique no mapa
async function handleMapClick(e) {
    if (!selectingMode) return;

    const lat = e.latlng.lat;
    const lon = e.latlng.lng;

    showLoading('Buscando endereço...');

    try {
        // Geocodificação reversa
        const address = await reverseGeocode(lat, lon);

        if (selectingMode === 'partida') {
            document.getElementById('pontoPartida').value = address;
            document.getElementById('partidaLat').value = lat;
            document.getElementById('partidaLon').value = lon;

            // Remove marcador anterior de partida se existir
            if (partidaMarker) map.removeLayer(partidaMarker);
            partidaMarker = L.marker([lat, lon], {
                icon: createIcon('🟢', '#10B981')
            }).addTo(map).bindPopup(`<b>Partida</b><br>${address}`);

        } else if (selectingMode === 'destino') {
            document.getElementById('destinoFinal').value = address;
            document.getElementById('destinoLat').value = lat;
            document.getElementById('destinoLon').value = lon;

            // Remove marcador anterior de destino se existir
            if (destinoMarker) map.removeLayer(destinoMarker);
            destinoMarker = L.marker([lat, lon], {
                icon: createIcon('🔴', '#EF4444')
            }).addTo(map).bindPopup(`<b>Destino</b><br>${address}`);

        } else if (selectingMode.startsWith('parada')) {
            // É uma parada (parada1, parada2, etc)
            document.getElementById(selectingMode).value = address;
            document.getElementById(`${selectingMode}Lat`).value = lat;
            document.getElementById(`${selectingMode}Lon`).value = lon;

            // Remove marcador anterior desta parada específica se existir
            if (paradaMarkers[selectingMode]) {
                map.removeLayer(paradaMarkers[selectingMode]);
            }
            paradaMarkers[selectingMode] = L.marker([lat, lon], {
                icon: createIcon('🔵', '#3B82F6')
            }).addTo(map).bindPopup(`<b>Parada ${selectingMode.replace('parada', '')}</b><br>${address}`);
        }

        // Desativar modo de seleção
        selectingMode = null;
        map.getContainer().style.cursor = '';

    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao buscar endereço');
    } finally {
        hideLoading();
    }
}

// Geocodificação reversa
async function reverseGeocode(lat, lon) {
    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18`;
        const response = await fetch(url, {
            headers: { 'User-Agent': 'FleetFlow/1.0' }
        });
        const data = await response.json();
        return data.display_name || `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
    } catch (error) {
        return `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
    }
}

// Criar ícone customizado
function createIcon(emoji, color) {
    return L.divIcon({
        html: `<div style="background-color: ${color}; color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">${emoji}</div>`,
        className: '',
        iconSize: [35, 35]
    });
}

// Carregar veículos
async function loadVehicles() {
    try {
        const response = await fetch('/api/vehicles');
        const vehicles = await response.json();

        const select = document.getElementById('selectVeiculo');
        select.innerHTML = '<option value="">Selecione um veículo</option>';

        vehicles.forEach(vehicle => {
            const option = document.createElement('option');
            option.value = vehicle.id;
            option.textContent = `${vehicle.plate} - ${vehicle.model}`;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Erro ao carregar veículos:', error);
    }
}

// Carregar motoristas
async function loadDrivers() {
    try {
        const response = await fetch('/api/drivers');
        const drivers = await response.json();

        const select = document.getElementById('selectMotorista');
        select.innerHTML = '<option value="">Selecione um motorista</option>';

        drivers.forEach(driver => {
            const option = document.createElement('option');
            option.value = driver.id;
            option.textContent = driver.name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Erro ao carregar motoristas:', error);
    }
}

// Setup event listeners
function setupEventListeners() {
    document.getElementById('btnAdicionarParada').addEventListener('click', addStopInput);
    document.getElementById('btnLimpar').addEventListener('click', clearForm);
    document.getElementById('formPlanejamento').addEventListener('submit', handleOptimizeRoute);
    document.getElementById('btnSalvarRota').addEventListener('click', handleSaveRoute);
    document.getElementById('btnEnviarWhatsApp').addEventListener('click', handleSendWhatsApp);

    // Botões de seleção no mapa
    document.getElementById('btnSelecionarPartida').addEventListener('click', () => {
        selectingMode = 'partida';
        map.getContainer().style.cursor = 'crosshair';
        alert('Clique no mapa para selecionar o ponto de partida');
    });

    document.getElementById('btnSelecionarDestino').addEventListener('click', () => {
        selectingMode = 'destino';
        map.getContainer().style.cursor = 'crosshair';
        alert('Clique no mapa para selecionar o destino');
    });

    // Mostrar/ocultar campo de destino
    document.getElementById('radioOutroDestino').addEventListener('change', () => {
        document.getElementById('campoDestino').classList.remove('hidden');
        document.getElementById('destinoFinal').required = true;
    });
    document.getElementById('radioRetornar').addEventListener('change', () => {
        document.getElementById('campoDestino').classList.add('hidden');
        document.getElementById('destinoFinal').required = false;
    });

    // Autocomplete para os campos
    setupAutocomplete('pontoPartida', 'autocompletePartida', 'partidaLat', 'partidaLon');
    setupAutocomplete('destinoFinal', 'autocompleteDestino', 'destinoLat', 'destinoLon');
}

// Configurar autocomplete para um campo
let autocompleteTimeout = null;
function setupAutocomplete(inputId, dropdownId, latId, lonId) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);

    input.addEventListener('input', async (e) => {
        const query = e.target.value.trim();

        if (query.length < 3) {
            dropdown.style.display = 'none';
            return;
        }

        // Debounce
        clearTimeout(autocompleteTimeout);
        autocompleteTimeout = setTimeout(async () => {
            try {
                const results = await searchAddress(query);
                displayAutocompleteResults(results, dropdown, input, latId, lonId);
            } catch (error) {
                console.error('Erro ao buscar endereços:', error);
            }
        }, 300);
    });

    // Fechar dropdown ao clicar fora
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

// Buscar endereços via Nominatim
async function searchAddress(query) {
    try {
        // Fazer múltiplas buscas para ter mais resultados
        const searches = [
            // Busca 1: Específica em Vitória
            `${query}, Vitória, ES, Brasil`,
            // Busca 2: Mais ampla no ES
            `${query}, Espírito Santo, Brasil`,
            // Busca 3: Apenas com Brasil
            `${query}, Brasil`
        ];

        let allResults = [];

        // Fazer as buscas sequencialmente
        for (const searchQuery of searches) {
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&limit=10&addressdetails=1`;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 segundos por busca

            try {
                const response = await fetch(url, {
                    headers: {
                        'User-Agent': 'FleetFlow/1.0',
                        'Accept': 'application/json'
                    },
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (response.ok) {
                    const data = await response.json();
                    allResults = allResults.concat(data);
                }
            } catch (err) {
                console.log(`Busca falhou para: ${searchQuery}`);
            }

            // Se já temos resultados suficientes, não precisa fazer mais buscas
            if (allResults.length >= 15) break;
        }

        // Remover duplicatas (mesmo lat/lon)
        const uniqueResults = [];
        const seen = new Set();

        for (const result of allResults) {
            const key = `${result.lat},${result.lon}`;
            if (!seen.has(key)) {
                seen.add(key);
                uniqueResults.push(result);
            }
        }

        console.log(`🔍 Encontrados ${uniqueResults.length} resultados únicos para "${query}"`);
        return uniqueResults.slice(0, 20); // Retorna até 20 resultados

    } catch (error) {
        console.error('Erro na busca:', error);
        return [];
    }
}

// Exibir resultados do autocomplete
function displayAutocompleteResults(results, dropdown, input, latId, lonId) {
    dropdown.innerHTML = '';

    if (results.length === 0) {
        dropdown.innerHTML = '<div class="autocomplete-item"><div class="autocomplete-item-name">Nenhum resultado encontrado</div></div>';
        dropdown.style.display = 'block';
        return;
    }

    results.forEach(result => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';

        const name = result.display_name.split(',')[0];
        const address = result.display_name;

        item.innerHTML = `
            <div class="autocomplete-item-name">${name}</div>
            <div class="autocomplete-item-address">${address}</div>
        `;

        item.addEventListener('click', () => {
            input.value = result.display_name;
            document.getElementById(latId).value = result.lat;
            document.getElementById(lonId).value = result.lon;
            dropdown.style.display = 'none';

            // Adicionar marcador temporário
            if (tempMarker) map.removeLayer(tempMarker);
            const color = latId.includes('partida') ? '#10B981' : '#EF4444';
            const emoji = latId.includes('partida') ? '🟢' : '🔴';
            tempMarker = L.marker([result.lat, result.lon], {
                icon: createIcon(emoji, color)
            }).addTo(map).bindPopup(`<b>${name}</b><br>${address}`);
            map.setView([result.lat, result.lon], 15);
        });

        dropdown.appendChild(item);
    });

    dropdown.style.display = 'block';
}

// Adicionar campo de parada
function addStopInput() {
    const container = document.getElementById('listaParadas');

    // Remover mensagem "nenhuma parada" se existir
    const emptyMsg = container.querySelector('.text-gray-500');
    if (emptyMsg) emptyMsg.remove();

    const stopIndex = stopCounter;
    const stopDiv = document.createElement('div');
    stopDiv.className = 'relative';
    stopDiv.innerHTML = `
        <div class="flex gap-2">
            <input type="text"
                   id="parada${stopIndex}"
                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white stop-address"
                   placeholder="Parada ${stopIndex} - Digite ou clique no botão"
                   autocomplete="off">
            <button type="button" class="px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 btn-selecionar-parada">
                <span class="material-symbols-outlined text-sm">location_on</span>
            </button>
            <button type="button" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 btn-remover-parada">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <div id="autocompleteParada${stopIndex}" class="autocomplete-dropdown"></div>
        <input type="hidden" id="parada${stopIndex}Lat">
        <input type="hidden" id="parada${stopIndex}Lon">
    `;

    // Botão de remover
    stopDiv.querySelector('.btn-remover-parada').addEventListener('click', () => {
        stopDiv.remove();
        // Se não sobrar nenhuma parada, mostrar mensagem novamente
        if (container.querySelectorAll('.relative').length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400 italic">Nenhuma parada intermediária adicionada</p>';
        }
    });

    // Botão de selecionar no mapa
    stopDiv.querySelector('.btn-selecionar-parada').addEventListener('click', () => {
        selectingMode = `parada${stopIndex}`;
        map.getContainer().style.cursor = 'crosshair';
        alert(`Clique no mapa para selecionar a Parada ${stopIndex}`);
    });

    container.appendChild(stopDiv);

    // Configurar autocomplete para esta parada
    setupAutocomplete(`parada${stopIndex}`, `autocompleteParada${stopIndex}`, `parada${stopIndex}Lat`, `parada${stopIndex}Lon`);

    stopCounter++;
}

// Limpar formulário
function clearForm() {
    document.getElementById('formPlanejamento').reset();
    document.getElementById('listaParadas').innerHTML = '';
    document.getElementById('infoRotaOtimizada').classList.add('hidden');
    document.getElementById('campoDestino').classList.add('hidden');
    stopCounter = 1;
    clearMap();
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Limpar mapa
function clearMap() {
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    if (routeLayer) {
        map.removeLayer(routeLayer);
        routeLayer = null;
    }
}

// Otimizar rota
async function handleOptimizeRoute(e) {
    e.preventDefault();

    const nomeRota = document.getElementById('nomeRota').value;
    const pontoPartida = document.getElementById('pontoPartida').value;
    const partidaLat = document.getElementById('partidaLat').value;
    const partidaLon = document.getElementById('partidaLon').value;

    const destinoTipo = document.querySelector('input[name="destinoTipo"]:checked').value;
    const retornar = destinoTipo === 'retornar';

    let destinationCoords = null;
    if (!retornar) {
        const destinoFinal = document.getElementById('destinoFinal').value;
        const destinoLat = document.getElementById('destinoLat').value;
        const destinoLon = document.getElementById('destinoLon').value;

        if (!destinoFinal) {
            alert('Selecione um destino ou escolha "Retornar ao ponto de partida"');
            return;
        }

        if (destinoLat && destinoLon) {
            destinationCoords = {
                lat: parseFloat(destinoLat),
                lon: parseFloat(destinoLon),
                address: destinoFinal
            };
        }
    }

    // Coletar paradas
    const stopInputs = document.querySelectorAll('.stop-address');
    const paradas = Array.from(stopInputs)
        .map(input => input.value)
        .filter(v => v.trim());

    try {
        showLoading('Otimizando rota...');

        const requestBody = {
            start: pontoPartida,
            returnToStart: retornar
        };

        // Adicionar coordenadas se foram selecionadas no mapa
        if (partidaLat && partidaLon) {
            requestBody.startCoords = {
                lat: parseFloat(partidaLat),
                lon: parseFloat(partidaLon),
                address: pontoPartida
            };
        }

        if (paradas.length > 0) {
            requestBody.stops = paradas;
        }

        if (destinationCoords) {
            requestBody.destinationCoords = destinationCoords;
        }

        // Enviar para backend
        const response = await fetch('/api/routes/optimize', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();

        if (data.success) {
            currentRoute = data.route;
            displayOptimizedRoute(data.route);
            drawRouteOnMap(data.route);
        } else {
            alert('Erro ao otimizar rota: ' + data.error);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao otimizar rota: ' + error.message);
    } finally {
        hideLoading();
    }
}

// Exibir rota otimizada
function displayOptimizedRoute(route) {
    document.getElementById('distanciaTotal').textContent =
        `${(route.totalDistance / 1000).toFixed(2)} km`;
    document.getElementById('tempoTotal').textContent =
        formatDuration(route.totalDuration);

    const sequenciaList = document.getElementById('sequenciaParadas');
    sequenciaList.innerHTML = '';

    route.waypoints.forEach((waypoint, index) => {
        const li = document.createElement('li');
        li.textContent = waypoint.address;
        li.className = 'text-gray-700 dark:text-gray-300';
        sequenciaList.appendChild(li);
    });

    document.getElementById('infoRotaOtimizada').classList.remove('hidden');
}

// Desenhar rota no mapa
function drawRouteOnMap(route) {
    clearMap();
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }

    if (!route.geometry) {
        // Sem geometria, desenhar linha reta
        const coordinates = route.waypoints.map(wp => [wp.lat, wp.lon]);
        routeLayer = L.polyline(coordinates, {
            color: '#3B82F6',
            weight: 4,
            opacity: 0.6,
            dashArray: '10, 10'
        }).addTo(map);
    } else {
        // Com geometria, desenhar rota real
        const routeCoords = decodePolyline(route.geometry);
        routeLayer = L.polyline(routeCoords, {
            color: '#3B82F6',
            weight: 5,
            opacity: 0.8
        }).addTo(map);
    }

    // Adicionar marcadores
    route.waypoints.forEach((waypoint, index) => {
        const isStart = index === 0;
        const isEnd = index === route.waypoints.length - 1;

        let emoji, color;
        if (isStart) {
            emoji = '🟢';
            color = '#10B981';
        } else if (isEnd) {
            emoji = '🔴';
            color = '#EF4444';
        } else {
            emoji = String(index);
            color = '#3B82F6';
        }

        const marker = L.marker([waypoint.lat, waypoint.lon], {
            icon: createIcon(emoji, color)
        }).addTo(map).bindPopup(`
            <div style="font-family: sans-serif; padding: 4px;">
                <strong>${isStart ? '🟢 Partida' : isEnd ? '🔴 Destino' : '🔵 Parada ' + index}</strong><br>
                <span style="font-size: 13px;">${waypoint.address}</span>
            </div>
        `);

        markers.push(marker);
    });

    // Ajustar zoom
    map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
}

// Decodificar polyline
function decodePolyline(encoded) {
    const points = [];
    let index = 0;
    const len = encoded.length;
    let lat = 0;
    let lng = 0;

    while (index < len) {
        let b, shift = 0, result = 0;
        do {
            b = encoded.charCodeAt(index++) - 63;
            result |= (b & 0x1f) << shift;
            shift += 5;
        } while (b >= 0x20);
        const dlat = ((result & 1) ? ~(result >> 1) : (result >> 1));
        lat += dlat;

        shift = 0;
        result = 0;
        do {
            b = encoded.charCodeAt(index++) - 63;
            result |= (b & 0x1f) << shift;
            shift += 5;
        } while (b >= 0x20);
        const dlng = ((result & 1) ? ~(result >> 1) : (result >> 1));
        lng += dlng;

        points.push([lat / 1e5, lng / 1e5]);
    }
    return points;
}

// Salvar rota
async function handleSaveRoute() {
    if (!currentRoute) {
        alert('Nenhuma rota otimizada para salvar');
        return;
    }

    const vehicleId = document.getElementById('selectVeiculo').value;
    const driverId = document.getElementById('selectMotorista').value;
    const nomeRota = document.getElementById('nomeRota').value;

    if (!vehicleId || !driverId) {
        alert('Selecione veículo e motorista');
        return;
    }

    try {
        showLoading('Salvando rota...');

        const response = await fetch('/api/routes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: nomeRota,
                vehicleId,
                driverId,
                route: currentRoute
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ Rota salva com sucesso!');
            await loadSavedRoutes();
            clearForm();
        } else {
            alert('Erro ao salvar rota: ' + data.error);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao salvar rota');
    } finally {
        hideLoading();
    }
}

// Enviar por WhatsApp
async function handleSendWhatsApp() {
    if (!currentRoute) {
        alert('Nenhuma rota para enviar');
        return;
    }

    const driverId = document.getElementById('selectMotorista').value;
    if (!driverId) {
        alert('Selecione um motorista');
        return;
    }

    const phone = prompt('Digite o número do WhatsApp\n(com DDD, exemplo: 27999887766):');
    if (!phone) return;

    const phoneClean = phone.replace(/\D/g, '');
    if (phoneClean.length < 10) {
        alert('Número de telefone inválido!');
        return;
    }

    try {
        showLoading('Enviando via WhatsApp...');

        const response = await fetch('/api/routes/send-whatsapp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                phone: phoneClean,
                route: currentRoute,
                routeName: document.getElementById('nomeRota').value,
                instanceName: 'Thiago Costa'
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ Mensagem enviada com sucesso via WhatsApp!');
        } else {
            alert('❌ Erro ao enviar WhatsApp:\n' + (data.error || JSON.stringify(data.details)));
            console.error('Detalhes do erro:', data);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao enviar WhatsApp: ' + error.message);
    } finally {
        hideLoading();
    }
}

// Carregar rotas salvas
async function loadSavedRoutes() {
    try {
        const response = await fetch('/api/routes');
        const routes = await response.json();

        const tbody = document.getElementById('tabelaRotas');
        tbody.innerHTML = '';

        if (routes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma rota salva</td></tr>';
            return;
        }

        routes.forEach(route => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700';
            tr.innerHTML = `
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">${route.name}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">${route.vehicle_plate || 'N/A'}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">${route.driver_name || 'N/A'}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">${route.stops_count}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">${(route.total_distance / 1000).toFixed(2)} km</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadgeClass(route.status)}">
                        ${route.status}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        <button onclick="viewRoute(${route.id})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Visualizar">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                        </button>
                        <button onclick="monitorRoute(${route.id})" class="text-green-600 hover:text-green-800 dark:text-green-400" title="Monitorar">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                        </button>
                        <button onclick="deleteRoute(${route.id})" class="text-red-600 hover:text-red-800 dark:text-red-400" title="Excluir">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Erro ao carregar rotas:', error);
    }
}

// Visualizar rota
async function viewRoute(routeId) {
    try {
        showLoading('Carregando rota...');

        const response = await fetch(`/api/routes/${routeId}`);
        const data = await response.json();

        if (data.success) {
            currentRoute = data.route;
            displayOptimizedRoute(data.route);
            drawRouteOnMap(data.route);
            document.getElementById('map').scrollIntoView({ behavior: 'smooth' });
        }
    } catch (error) {
        console.error('Erro ao visualizar rota:', error);
        alert('Erro ao visualizar rota');
    } finally {
        hideLoading();
    }
}

// Monitorar rota
function monitorRoute(routeId) {
    window.location.href = `monitoramento-rota.html?id=${routeId}`;
}

// Excluir rota
async function deleteRoute(routeId) {
    if (!confirm('Deseja realmente excluir esta rota?')) return;

    try {
        const response = await fetch(`/api/routes/${routeId}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.success) {
            alert('Rota excluída com sucesso!');
            await loadSavedRoutes();
        } else {
            alert('Erro ao excluir rota: ' + data.error);
        }
    } catch (error) {
        console.error('Erro ao excluir rota:', error);
        alert('Erro ao excluir rota');
    }
}

// Funções auxiliares
function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return hours > 0 ? `${hours}h ${minutes}min` : `${minutes}min`;
}

function getStatusBadgeClass(status) {
    const classes = {
        'Planejada': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'Em Andamento': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'Concluída': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'Cancelada': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

let loadingDiv = null;

function showLoading(message = 'Carregando...') {
    if (loadingDiv) return;

    loadingDiv = document.createElement('div');
    loadingDiv.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    loadingDiv.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl flex flex-col items-center gap-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            <p class="text-gray-900 dark:text-white font-medium">${message}</p>
        </div>
    `;
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    if (loadingDiv) {
        loadingDiv.remove();
        loadingDiv = null;
    }
}
