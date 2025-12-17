// ========== OTIMIZADOR DE ROTAS COM BLOCOS GEOGRÁFICOS ==========

// Estado da aplicação
let map;
let selectedFile = null;
let uploadedData = null;
let selectedBlocks = new Set();
let selectedLocations = new Set();
let blockMarkers = {};
let locationMarkers = {};
let blockCircles = {};
let routeLayer = null;
let currentOptimizedRoute = null;
let markerClusterGroup = null;
let allBlocks = [];

// Base i9 Engenharia (ponto de partida fixo)
const BASE_I9 = {
    name: 'Base i9 Engenharia',
    address: 'R. Francisco Sousa dos Santos, 320 - Jardim Limoeiro, Serra - ES, 29164-153',
    latitude: -20.21155061582265,
    longitude: -40.25223140622406
};

// Cores para os blocos
const BLOCK_COLORS = [
    '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
    '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16'
];

// ========== INICIALIZAÇÃO ==========

document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Inicializando Otimizador de Blocos v4 - FORCE AUTO LOAD...');
    console.log('📍 Inicializando componentes...');

    initMap();
    console.log('✅ Mapa inicializado');

    setupUploadHandlers();
    console.log('✅ Upload handlers configurados');

    setupBlocksHandlers();
    console.log('✅ Blocks handlers configurados');

    loadVehiclesAndDrivers();
    console.log('✅ Veículos e motoristas carregando...');

    // Carregar blocos existentes automaticamente
    console.log('🔄 Carregando blocos existentes automaticamente...');
    loadExistingBlocks();
});

// ========== MAPA ==========

function initMap() {
    // Inicializar mapa centrado na base i9
    map = L.map('map').setView([BASE_I9.latitude, BASE_I9.longitude], 12);

    // Tile layer OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Marcador fixo da base i9
    const baseIcon = L.divIcon({
        html: `<div style="background-color: #10B981; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">🏠</div>`,
        className: '',
        iconSize: [40, 40]
    });

    L.marker([BASE_I9.latitude, BASE_I9.longitude], { icon: baseIcon })
        .addTo(map)
        .bindPopup(`<b>${BASE_I9.name}</b><br>${BASE_I9.address}`)
        .openPopup();

    console.log('✅ Mapa inicializado');
}

function visualizeBlocksOnMap(blocks) {
    clearMapMarkers();

    // Criar grupo de clustering para os locais
    markerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
    });

    blocks.forEach((block, index) => {
        const color = BLOCK_COLORS[index % BLOCK_COLORS.length];

        // Normalizar nomes dos campos (API pode retornar com underscore ou camelCase)
        const centerLat = block.centerLatitude || block.center_latitude;
        const centerLon = block.centerLongitude || block.center_longitude;
        const radius = block.radiusKm || block.radius_km || 1;
        const locCount = block.locationsCount || block.locations_count || block.locations?.length || 0;

        console.log(`Bloco ${block.id}: lat=${centerLat}, lon=${centerLon}`);

        if (!centerLat || !centerLon) {
            console.warn(`⚠️ Bloco ${block.id} (${block.name}) sem coordenadas válidas`);
            return;
        }

        // Círculo ao redor do bloco (não adicionar ao mapa por padrão para não poluir)
        const circle = L.circle(
            [centerLat, centerLon],
            {
                radius: radius * 1000,
                color: color,
                fillColor: color,
                fillOpacity: 0.05,
                weight: 1,
                opacity: 0.3,
                dashArray: '5, 10'
            }
        );

        blockCircles[block.id] = circle;

        // Marcador do centro do bloco
        const centerIcon = L.divIcon({
            html: `<div style="background-color: ${color}; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${locCount}</div>`,
            className: '',
            iconSize: [30, 30]
        });

        const centerMarker = L.marker(
            [centerLat, centerLon],
            { icon: centerIcon }
        ).bindPopup(`<b>${block.name}</b><br>${locCount} locais`);

        blockMarkers[block.id] = centerMarker;
        markerClusterGroup.addLayer(centerMarker);

        // Marcadores dos locais (adicionar ao cluster)
        if (block.locations && Array.isArray(block.locations)) {
            block.locations.forEach((location, locIndex) => {
                const locLat = location.latitude;
                const locLon = location.longitude;
                const distToCenter = location.distanceToCenterKm || location.distance_to_center_km;

                if (!locLat || !locLon) {
                    console.warn(`⚠️ Local ${location.id} (${location.name}) sem coordenadas válidas`);
                    return;
                }

                const locationIcon = L.divIcon({
                    html: `<div style="background-color: ${color}; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">•</div>`,
                    className: '',
                    iconSize: [20, 20]
                });

                const locationMarker = L.marker(
                    [locLat, locLon],
                    { icon: locationIcon }
                ).bindPopup(`
                    <b>${location.name}</b><br>
                    ${distToCenter ? distToCenter.toFixed(2) + 'km do centro' : 'Local no bloco'}<br>
                    <small>${block.name}</small>
                `);

                locationMarkers[location.id] = locationMarker;
                markerClusterGroup.addLayer(locationMarker);
            });
        }
    });

    // Adicionar cluster ao mapa
    map.addLayer(markerClusterGroup);

    // Ajustar zoom
    if (blocks.length > 0) {
        const bounds = markerClusterGroup.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    console.log(`✅ ${blocks.length} blocos visualizados no mapa com clustering`);
}

function clearMapMarkers() {
    // Remover cluster group
    if (markerClusterGroup) {
        map.removeLayer(markerClusterGroup);
        markerClusterGroup = null;
    }

    Object.values(blockCircles).forEach(circle => {
        if (map.hasLayer(circle)) {
            map.removeLayer(circle);
        }
    });

    blockMarkers = {};
    locationMarkers = {};
    blockCircles = {};

    if (routeLayer) {
        map.removeLayer(routeLayer);
        routeLayer = null;
    }
}

function updateMapSelection() {
    Object.keys(locationMarkers).forEach(locationId => {
        const marker = locationMarkers[locationId];
        const isSelected = selectedLocations.has(parseInt(locationId));

        if (marker && marker._icon) {
            if (isSelected) {
                marker._icon.style.transform = 'scale(1.3)';
                marker._icon.style.zIndex = '1000';
            } else {
                marker._icon.style.transform = 'scale(1)';
                marker._icon.style.zIndex = '600';
            }
        }
    });
}

function centerMapOnBlock(blockId) {
    const marker = blockMarkers[blockId];
    if (marker) {
        map.setView(marker.getLatLng(), 13);
        marker.openPopup();
    }
}

// ========== UPLOAD ==========

function setupUploadHandlers() {
    const fileInput = document.getElementById('fileInputLocations');
    const btnSelectFile = document.getElementById('btnSelectFileLocations');
    const btnProcessFile = document.getElementById('btnProcessFile');
    const btnRemoveFile = document.getElementById('btnRemoveFile');
    const autoClustering = document.getElementById('autoClustering');

    // Click to select
    btnSelectFile.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    // Remove file
    btnRemoveFile.addEventListener('click', () => {
        selectedFile = null;
        fileInput.value = '';
        document.getElementById('fileSelected').classList.add('hidden');
        document.getElementById('btnProcessFile').disabled = true;
    });

    // Toggle clustering options
    autoClustering.addEventListener('change', (e) => {
        const options = document.getElementById('clusteringOptions');
        options.style.display = e.target.checked ? 'grid' : 'none';
    });

    // Process button
    btnProcessFile.addEventListener('click', handleUpload);

    console.log('✅ Handlers de upload configurados');
}

function handleFileSelect(file) {
    const validExtensions = ['.xlsx', '.xls'];
    const fileExtension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();

    if (!validExtensions.includes(fileExtension)) {
        showNotification('Erro: Apenas arquivos Excel (.xlsx, .xls) são permitidos', 'error');
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        showNotification('Erro: Arquivo muito grande (máximo 10MB)', 'error');
        return;
    }

    selectedFile = file;

    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
    document.getElementById('fileSelected').classList.remove('hidden');
    document.getElementById('btnProcessFile').disabled = false;

    console.log('📁 Arquivo selecionado:', file.name);
}

async function handleUpload() {
    if (!selectedFile) {
        showNotification('Selecione um arquivo primeiro', 'error');
        return;
    }

    const autoClustering = document.getElementById('autoClustering').checked;
    const maxLocationsPerBlock = parseInt(document.getElementById('maxLocationsPerBlock').value);
    const maxDistanceKm = parseFloat(document.getElementById('maxDistanceKm').value);

    document.getElementById('uploadProgress').classList.remove('hidden');
    document.getElementById('btnProcessFile').disabled = true;

    try {
        updateProgress(10, 'Lendo arquivo Excel...');

        // Ler arquivo Excel no frontend
        const arrayBuffer = await selectedFile.arrayBuffer();
        const workbook = XLSX.read(arrayBuffer);
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const data = XLSX.utils.sheet_to_json(worksheet);

        console.log(`📊 ${data.length} linhas encontradas na planilha`);

        updateProgress(30, 'Preparando dados...');

        // Gerar identificador único para este lote
        const importBatch = `batch_${Date.now()}`;

        // Preparar locais para inserção
        const locations = [];
        for (const row of data) {
            if (!row.Nome || !row.Latitude || !row.Longitude) {
                console.warn('⚠️ Linha ignorada - dados incompletos:', row);
                continue;
            }

            locations.push({
                name: row.Nome,
                address: row['Endereço'] || row.Endereco || '',
                latitude: parseFloat(row.Latitude),
                longitude: parseFloat(row.Longitude),
                category: row.Camada || row.Categoria || null,
                importBatch
            });
        }

        if (locations.length === 0) {
            throw new Error('Nenhum local válido encontrado na planilha');
        }

        updateProgress(50, `Enviando ${locations.length} locais para o servidor...`);

        // Enviar para API PHP locations-api.php
        const locationsResponse = await fetch('https://floripa.in9automacao.com.br/locations-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ locations })
        });

        const locationsData = await locationsResponse.json();

        if (!locationsData.success) {
            throw new Error(locationsData.error || 'Erro ao inserir locais');
        }

        const insertedIds = locationsData.insertedIds;
        console.log(`✅ ${insertedIds.length} locais inseridos no banco`);

        let blocks = [];

        // Se clustering automático estiver ativado
        if (autoClustering && insertedIds.length > 0) {
            updateProgress(70, 'Criando blocos geográficos...');

            // Processar em lotes de 250 IDs para evitar timeout
            const batchSize = 250;
            const batches = [];

            for (let i = 0; i < insertedIds.length; i += batchSize) {
                batches.push(insertedIds.slice(i, i + batchSize));
            }

            console.log(`📦 Processando ${batches.length} lotes de até ${batchSize} locais`);

            for (let i = 0; i < batches.length; i++) {
                const batch = batches[i];
                const progress = 70 + (i / batches.length) * 20;
                updateProgress(progress, `Criando blocos (lote ${i + 1}/${batches.length})...`);

                const blocksResponse = await fetch('https://floripa.in9automacao.com.br/blocks-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        locationIds: batch,
                        maxLocationsPerBlock,
                        maxDistanceKm,
                        importBatch
                    })
                });

                if (!blocksResponse.ok) {
                    const errorText = await blocksResponse.text();
                    console.error(`⚠️ Erro HTTP ${blocksResponse.status} no lote ${i + 1}:`, errorText);
                    continue;
                }

                const blocksData = await blocksResponse.json();

                if (blocksData.success) {
                    blocks = blocks.concat(blocksData.blocks);
                    console.log(`✅ Lote ${i + 1}: ${blocksData.blocks.length} blocos criados`);
                } else {
                    console.warn(`⚠️ Erro no lote ${i + 1}:`, blocksData.error || blocksData.message);
                }
            }

            console.log(`✅ Total: ${blocks.length} blocos criados`);
        }

        updateProgress(100, 'Concluído!');

        const resultData = {
            success: true,
            totalImported: insertedIds.length,
            totalBlocks: blocks.length,
            importBatch,
            blocks
        };

        uploadedData = resultData;
        showUploadResult(resultData);

        await loadBlocks(importBatch);
        visualizeBlocksOnMap(blocks);

        // Mostrar container de blocos
        document.getElementById('blocksContainer').classList.remove('hidden');

        showNotification(`${insertedIds.length} locais importados em ${blocks.length} blocos`, 'success');

    } catch (error) {
        console.error('❌ Erro no upload:', error);
        showNotification('Erro ao importar: ' + error.message, 'error');
        document.getElementById('uploadProgress').classList.add('hidden');
    } finally {
        document.getElementById('btnProcessFile').disabled = false;
    }
}

function updateProgress(percent, message) {
    document.getElementById('progressBar').style.width = percent + '%';
    document.getElementById('progressText').textContent = percent + '%';
    document.getElementById('progressMessage').textContent = message;
}

function showUploadResult(data) {
    const resultDiv = document.getElementById('uploadResult');
    resultDiv.classList.remove('hidden');

    document.getElementById('totalImported').textContent = data.totalImported;
    document.getElementById('totalBlocks').textContent = data.totalBlocks;

    setTimeout(() => {
        document.getElementById('uploadProgress').classList.add('hidden');
    }, 1000);
}

// ========== BLOCOS ==========

function setupBlocksHandlers() {
    document.getElementById('btnSelectAll').addEventListener('click', handleSelectAll);
    document.getElementById('btnClearSelection').addEventListener('click', handleClearSelection);
    document.getElementById('searchBlocks').addEventListener('input', handleSearchBlocks);
    document.getElementById('btnOptimizeRoute').addEventListener('click', handleOptimizeRoute);
    document.getElementById('btnDeleteAllBlocks').addEventListener('click', handleDeleteAllBlocks);

    console.log('✅ Handlers de blocos configurados');
}

async function handleDeleteAllBlocks() {
    console.log('🗑️ Botão "Deletar Todos" clicado');

    if (!confirm('⚠️ ATENÇÃO!\n\nIsso vai DELETAR TODOS os blocos e locais do banco de dados!\n\nVocê terá que reimportar o Excel depois.\n\nTem certeza?')) {
        console.log('❌ Usuário cancelou (1ª confirmação)');
        return;
    }

    if (!confirm('Tem MESMO certeza? Essa ação NÃO pode ser desfeita!')) {
        console.log('❌ Usuário cancelou (2ª confirmação)');
        return;
    }

    try {
        console.log('🔄 Deletando todos os blocos...');
        showLoading('Deletando todos os blocos...');

        const url = 'https://floripa.in9automacao.com.br/blocks-api.php';
        console.log('📡 DELETE request para:', url);

        // Deletar blocos (locations serão removidas automaticamente via cascade)
        const response = await fetch(url, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ deleteAll: true })
        });

        console.log('📥 Response status:', response.status);

        const data = await response.json();
        console.log('📦 Response data:', data);

        if (!data.success) {
            throw new Error(data.error || 'Erro ao deletar blocos');
        }

        showNotification('Todos os blocos foram deletados!', 'success');

        // Recarregar lista vazia
        await loadBlocks();

        // Limpar mapa
        clearMapMarkers();

        console.log('✅ Todos os blocos deletados com sucesso');

    } catch (error) {
        console.error('❌ Erro ao deletar blocos:', error);
        showNotification('Erro ao deletar blocos: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// Carregar blocos existentes ao iniciar
async function loadExistingBlocks() {
    try {
        console.log('🔄 Carregando blocos existentes automaticamente...');
        console.log('📡 Buscando: https://floripa.in9automacao.com.br/blocks-api.php');

        const response = await fetch('https://floripa.in9automacao.com.br/blocks-api.php');
        console.log('📥 Response status:', response.status);

        const data = await response.json();
        console.log('📦 Response data:', data);

        if (!data.success) {
            console.warn('⚠️ API retornou success=false:', data.error || data.message);
            return;
        }

        if (!data.blocks || data.blocks.length === 0) {
            console.log('ℹ️ Nenhum bloco encontrado. Importe uma planilha para começar.');
            return;
        }

        console.log(`✅ ${data.blocks.length} blocos encontrados no servidor`);

        // Armazenar blocos globalmente
        allBlocks = data.blocks;

        // Renderizar blocos na lista
        const blocksListContainer = document.getElementById('blocksList');
        blocksListContainer.innerHTML = '';

        data.blocks.forEach(block => {
            const blockElement = createBlockElement(block);
            blocksListContainer.appendChild(blockElement);
        });

        // Atualizar contadores
        updateBlocksCount();

        // Visualizar os blocos no mapa
        visualizeBlocksOnMap(data.blocks);

        // Mostrar container de blocos
        document.getElementById('blocksContainer').classList.remove('hidden');

        console.log(`✅ ${data.blocks.length} blocos carregados e exibidos automaticamente no mapa e na lista`);

    } catch (error) {
        console.error('❌ Erro ao carregar blocos existentes:', error);
        console.error('Stack trace:', error.stack);
    }
}

async function loadBlocks(importBatch = null) {
    try {
        console.log('🔄 Carregando blocos do servidor...');
        showLoading('Carregando blocos...');

        const url = importBatch
            ? `https://floripa.in9automacao.com.br/blocks-api.php?importBatch=${importBatch}`
            : 'https://floripa.in9automacao.com.br/blocks-api.php';

        console.log('📡 URL:', url);
        const response = await fetch(url);
        const data = await response.json();
        console.log('📦 Dados recebidos:', data.blocks ? `${data.blocks.length} blocos` : 'nenhum bloco');

        if (!data.success) {
            throw new Error(data.error);
        }

        const blocksListContainer = document.getElementById('blocksList');
        blocksListContainer.innerHTML = '';

        if (data.blocks.length === 0) {
            blocksListContainer.innerHTML = `
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <span class="material-symbols-outlined text-4xl mb-2 block">folder_off</span>
                    <p class="text-sm">Nenhum bloco criado ainda</p>
                    <p class="text-xs mt-1">Importe uma planilha para começar</p>
                </div>
            `;
            return;
        }

        // Armazenar blocos globalmente
        allBlocks = data.blocks;

        // DEBUG: Mostrar distâncias máximas no console
        console.log('📊 Distâncias máximas dos blocos:');
        data.blocks.forEach(block => {
            if (block.maxPairDistanceKm !== undefined) {
                const status = block.maxPairDistanceKm > 5 ? '❌ EXCEDE 5km!' : '✅ OK';
                console.log(`  ${block.name}: ${block.maxPairDistanceKm.toFixed(2)}km ${status}`);
            }
        });

        data.blocks.forEach(block => {
            const blockElement = createBlockElement(block);
            blocksListContainer.appendChild(blockElement);
        });

        // Atualizar contadores
        updateBlocksCount();

        updateSelectionButtons();

        console.log(`✅ ${data.blocks.length} blocos carregados`);

    } catch (error) {
        console.error('❌ Erro ao carregar blocos:', error);
        showNotification('Erro ao carregar blocos: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function createBlockElement(block) {
    const blockDiv = document.createElement('details');
    blockDiv.className = 'group bg-white dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600';
    blockDiv.dataset.blockId = block.id;

    // Determinar cor baseado na distância máxima
    let distanceInfo = '';
    let colorClass = '';
    if (block.maxPairDistanceKm !== undefined) {
        const maxDist = block.maxPairDistanceKm.toFixed(2);
        if (block.maxPairDistanceKm > 5) {
            distanceInfo = `• <span class="text-red-600 font-semibold">Max: ${maxDist}km ⚠️</span>`;
        } else {
            distanceInfo = `• <span class="text-green-600">Max: ${maxDist}km</span>`;
        }
    }

    const summary = document.createElement('summary');
    summary.className = 'flex items-center justify-between p-3 cursor-pointer';
    summary.innerHTML = `
        <div class="flex items-center gap-3" onclick="event.stopPropagation()">
            <input type="checkbox"
                   class="block-checkbox form-checkbox rounded text-primary focus:ring-primary"
                   data-block-id="${block.id}"
                   onchange="handleBlockCheckboxChange(${block.id})">
            <div>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 text-left">
                    ${block.name}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 text-left">
                    ${block.locationsCount} locais ${distanceInfo}
                </p>
            </div>
        </div>
        <span class="material-symbols-outlined text-gray-500 dark:text-gray-400 group-open:rotate-180 transition-transform" style="font-size: 20px;">expand_more</span>
    `;

    blockDiv.appendChild(summary);

    const locationsContainer = document.createElement('div');
    locationsContainer.className = 'border-t border-gray-200 dark:border-gray-600 p-3 space-y-2';

    block.locations.forEach(location => {
        const locationDiv = document.createElement('div');
        locationDiv.className = 'flex items-center justify-between';
        locationDiv.innerHTML = `
            <div class="flex items-center gap-3">
                <input type="checkbox"
                       class="location-checkbox form-checkbox rounded text-primary focus:ring-primary"
                       data-location-id="${location.id}"
                       data-block-id="${block.id}"
                       onchange="handleLocationCheckboxChange(${location.id}, ${block.id})">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 text-left">
                        ${location.name}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        ${location.distanceToCenterKm ? location.distanceToCenterKm.toFixed(2) + ' km de distância' : 'Local no bloco'}
                    </p>
                </div>
            </div>
            <button onclick="event.stopPropagation(); centerMapOnLocation(${location.id})"
                    class="p-1.5 text-gray-500 dark:text-gray-400 hover:text-primary rounded-full hover:bg-primary/10">
                <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
            </button>
        `;

        locationsContainer.appendChild(locationDiv);
    });

    // Botão para gerar rota
    const routeButton = document.createElement('div');
    routeButton.className = 'px-3 pb-3';
    routeButton.innerHTML = `
        <button onclick="generateBlockRoute(${JSON.stringify(block).replace(/"/g, '&quot;')})"
                class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center justify-center gap-2">
            <span class="material-symbols-outlined" style="font-size: 20px;">route</span>
            <span>Gerar Rota deste Bloco</span>
        </button>
    `;

    blockDiv.appendChild(locationsContainer);
    blockDiv.appendChild(routeButton);

    return blockDiv;
}

function handleBlockCheckboxChange(blockId) {
    const checkbox = document.querySelector(`.block-checkbox[data-block-id="${blockId}"]`);
    const locationCheckboxes = document.querySelectorAll(`.location-checkbox[data-block-id="${blockId}"]`);

    if (checkbox.checked) {
        selectedBlocks.add(blockId);
        locationCheckboxes.forEach(cb => {
            cb.checked = true;
            selectedLocations.add(parseInt(cb.dataset.locationId));
        });
    } else {
        selectedBlocks.delete(blockId);
        locationCheckboxes.forEach(cb => {
            cb.checked = false;
            selectedLocations.delete(parseInt(cb.dataset.locationId));
        });
    }

    updateSelectionButtons();
    updateMapSelection();
}

function handleLocationCheckboxChange(locationId, blockId) {
    const checkbox = document.querySelector(`.location-checkbox[data-location-id="${locationId}"]`);
    const blockCheckbox = document.querySelector(`.block-checkbox[data-block-id="${blockId}"]`);
    const locationCheckboxes = document.querySelectorAll(`.location-checkbox[data-block-id="${blockId}"]`);

    if (checkbox.checked) {
        selectedLocations.add(locationId);

        const allChecked = Array.from(locationCheckboxes).every(cb => cb.checked);
        if (allChecked) {
            blockCheckbox.checked = true;
            selectedBlocks.add(blockId);
        }
    } else {
        selectedLocations.delete(locationId);
        blockCheckbox.checked = false;
        selectedBlocks.delete(blockId);
    }

    updateSelectionButtons();
    updateMapSelection();
}

function updateSelectionButtons() {
    const hasSelection = selectedBlocks.size > 0 || selectedLocations.size > 0;
    const btnOptimize = document.getElementById('btnOptimizeRoute');
    btnOptimize.disabled = !hasSelection;
}

function handleSelectAll() {
    const allBlockCheckboxes = document.querySelectorAll('.block-checkbox');
    const allChecked = Array.from(allBlockCheckboxes).every(cb => cb.checked);

    allBlockCheckboxes.forEach(cb => {
        cb.checked = !allChecked;
        const blockId = parseInt(cb.dataset.blockId);

        if (!allChecked) {
            selectedBlocks.add(blockId);
        } else {
            selectedBlocks.delete(blockId);
        }

        const locationCheckboxes = document.querySelectorAll(`.location-checkbox[data-block-id="${blockId}"]`);
        locationCheckboxes.forEach(locCb => {
            locCb.checked = !allChecked;
            const locId = parseInt(locCb.dataset.locationId);

            if (!allChecked) {
                selectedLocations.add(locId);
            } else {
                selectedLocations.delete(locId);
            }
        });
    });

    updateSelectionButtons();
    updateMapSelection();
}

function handleClearSelection() {
    document.querySelectorAll('.block-checkbox, .location-checkbox').forEach(cb => {
        cb.checked = false;
    });
    selectedBlocks.clear();
    selectedLocations.clear();
    updateSelectionButtons();
    updateMapSelection();
}

function handleSearchBlocks(e) {
    const searchTerm = e.target.value.toLowerCase();
    const blockElements = document.querySelectorAll('#blocksList > details');

    blockElements.forEach(block => {
        const blockName = block.querySelector('.text-sm.font-medium').textContent.toLowerCase();
        const match = blockName.includes(searchTerm);
        block.style.display = match ? 'block' : 'none';
    });
}

function centerMapOnLocation(locationId) {
    const marker = locationMarkers[locationId];
    if (marker) {
        map.setView(marker.getLatLng(), 15);
        marker.openPopup();
    }
}

// ========== OTIMIZAÇÃO ==========

async function handleOptimizeRoute() {
    if (selectedBlocks.size === 0 && selectedLocations.size === 0) {
        showNotification('Selecione pelo menos um bloco ou local', 'warning');
        return;
    }

    try {
        showLoading('Otimizando rota...');

        const returnToStart = document.getElementById('returnToStart').checked;

        const requestBody = {
            startPoint: BASE_I9,
            selectedBlocks: Array.from(selectedBlocks),
            selectedLocations: Array.from(selectedLocations),
            returnToStart: returnToStart
        };

        const response = await fetch('/api/blocks/optimize', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        currentOptimizedRoute = data.route;
        drawOptimizedRouteOnMap(data.route);
        showRouteInfo(data.route);

        showNotification('Rota otimizada com sucesso!', 'success');

    } catch (error) {
        console.error('❌ Erro ao otimizar:', error);
        showNotification('Erro ao otimizar rota: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function drawOptimizedRouteOnMap(route) {
    if (routeLayer) {
        map.removeLayer(routeLayer);
    }

    // Se temos geometria real da rota (OSRM), usar ela
    // Senão, usar linha reta entre pontos
    let coords;
    if (route.geometry && Array.isArray(route.geometry) && route.geometry.length > 0) {
        // Geometria OSRM vem como [lon, lat], Leaflet espera [lat, lon]
        coords = route.geometry.map(coord => [coord[1], coord[0]]);
        console.log(`✅ Usando geometria real da rota (${coords.length} pontos)`);
    } else {
        // Fallback: linha reta entre waypoints
        coords = route.waypoints.map(wp => [wp.lat, wp.lon]);
        console.log('⚠️ Usando linhas retas entre waypoints (geometria não disponível)');
    }

    routeLayer = L.polyline(coords, {
        color: '#3B82F6',
        weight: 5,
        opacity: 0.7,
        lineJoin: 'round',
        smoothFactor: 1.0
    }).addTo(map);

    route.waypoints.forEach((waypoint, index) => {
        let icon, popupContent;

        if (waypoint.type === 'start') {
            icon = L.divIcon({
                html: `<div style="background-color: #10B981; color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">🚀</div>`,
                className: '',
                iconSize: [35, 35]
            });
            popupContent = `<b>🚀 INÍCIO</b><br>${waypoint.address}`;

        } else if (waypoint.type === 'end') {
            icon = L.divIcon({
                html: `<div style="background-color: #EF4444; color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">🏁</div>`,
                className: '',
                iconSize: [35, 35]
            });
            popupContent = `<b>🏁 FIM</b><br>${waypoint.address}`;

        } else {
            icon = L.divIcon({
                html: `<div style="background-color: #3B82F6; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">${index}</div>`,
                className: '',
                iconSize: [30, 30]
            });
            popupContent = `<b>${index}. ${waypoint.address}</b>`;
        }

        L.marker([waypoint.lat, waypoint.lon], { icon })
            .addTo(map)
            .bindPopup(popupContent);
    });

    map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });

    console.log('✅ Rota desenhada no mapa');
}

function showRouteInfo(route) {
    const infoPanel = document.getElementById('routeInfo');

    const totalKm = (route.totalDistance / 1000).toFixed(1);
    const totalMinutes = Math.round(route.totalDuration / 60);
    const stops = route.waypoints.length - 2; // Excluir início e fim

    document.getElementById('routeDistance').textContent = totalKm + ' km';
    document.getElementById('routeDuration').textContent = totalMinutes + ' min';
    document.getElementById('routeStops').textContent = stops;

    infoPanel.classList.remove('hidden');
}

// ========== VEÍCULOS E MOTORISTAS ==========

async function loadVehiclesAndDrivers() {
    try {
        // Carregar veículos
        const vehiclesResponse = await fetch('https://floripa.in9automacao.com.br/veiculos-api.php');
        const vehiclesData = await vehiclesResponse.json();

        const selectVehicle = document.getElementById('selectVehicle');
        if (vehiclesData.success && vehiclesData.data) {
            vehiclesData.data.forEach(vehicle => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = `${vehicle.modelo} - ${vehicle.placa}`;
                selectVehicle.appendChild(option);
            });
        }

        // Carregar motoristas
        const driversResponse = await fetch('https://floripa.in9automacao.com.br/motoristas-api.php');
        const driversData = await driversResponse.json();

        const selectDriver = document.getElementById('selectDriver');
        if (driversData.success && driversData.data) {
            driversData.data.forEach(driver => {
                const option = document.createElement('option');
                option.value = driver.id;
                option.textContent = driver.nome;
                selectDriver.appendChild(option);
            });
        }

        console.log('✅ Veículos e motoristas carregados');

    } catch (error) {
        console.error('❌ Erro ao carregar veículos/motoristas:', error);
    }
}

// ========== GERAÇÃO DE ROTA POR BLOCO ==========

async function generateBlockRoute(block) {
    try {
        showLoading(`Gerando rota para ${block.name}...`);

        console.log(`🚗 Gerando rota para bloco: ${block.name}`);

        // Preparar dados da rota
        const routeData = {
            startPoint: {
                name: BASE_I9.name,
                address: BASE_I9.address,
                latitude: BASE_I9.latitude,
                longitude: BASE_I9.longitude
            },
            selectedBlocks: [block.id],
            selectedLocations: [],
            returnToStart: true
        };

        // Chamar API de otimização de rota
        const response = await fetch('https://floripa.in9automacao.com.br/optimize-route-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(routeData)
        });

        if (!response.ok) {
            throw new Error(`Erro HTTP ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao gerar rota');
        }

        // Exibir rota no mapa
        displayRouteOnMap(data.route);

        // Mostrar detalhes da rota
        const distanceKm = (data.route.totalDistance / 1000).toFixed(2);
        const durationMin = Math.round(data.route.totalDuration / 60);

        showNotification(
            `Rota gerada: ${distanceKm}km, ${durationMin} minutos, ${block.locationsCount} locais`,
            'success'
        );

        console.log(`✅ Rota gerada: ${distanceKm}km, ${durationMin}min`);

    } catch (error) {
        console.error('❌ Erro ao gerar rota:', error);
        showNotification(`Erro ao gerar rota: ${error.message}`, 'error');
    } finally {
        hideLoading();
    }
}

function displayRouteOnMap(route) {
    // Limpar rota anterior se existir
    if (routeLayer) {
        map.removeLayer(routeLayer);
        routeLayer = null;
    }

    // Se tiver geometria (rota real seguindo ruas), usar ela
    if (route.geometry && route.geometry.length > 0) {
        // OpenRouteService retorna [lon, lat], Leaflet precisa [lat, lon]
        const coordinates = route.geometry.map(coord => [coord[1], coord[0]]);

        // Criar linha da rota REAL (seguindo ruas)
        routeLayer = L.polyline(coordinates, {
            color: '#EF4444',
            weight: 5,
            opacity: 0.8,
            lineJoin: 'round',
            lineCap: 'round'
        }).addTo(map);

        console.log(`✅ Rota REAL exibida seguindo ruas: ${coordinates.length} pontos`);
    } else {
        // Fallback: linha reta entre waypoints
        const coordinates = route.waypoints.map(wp => [wp.lat, wp.lon]);

        routeLayer = L.polyline(coordinates, {
            color: '#1173d4',
            weight: 4,
            opacity: 0.7,
            dashArray: '10, 10',
            lineJoin: 'round',
            lineCap: 'round'
        }).addTo(map);

        console.log(`⚠️ Rota em linha reta (fallback): ${route.waypoints.length} pontos`);
    }

    // Ajustar zoom para mostrar toda a rota
    map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });
}

// ========== UTILIDADES ==========

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function showLoading(message) {
    const overlay = document.getElementById('loadingOverlay');
    document.getElementById('loadingMessage').textContent = message;
    overlay.classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);

    console.log(`[${type.toUpperCase()}] ${message}`);
}

// ========== FILTRO E CONTADORES ==========

function filterBlocks(searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    const blocksListContainer = document.getElementById('blocksList');
    const blockElements = blocksListContainer.querySelectorAll('details[data-block-id]');

    let visibleCount = 0;
    let visibleLocationsCount = 0;

    blockElements.forEach(blockEl => {
        const blockId = parseInt(blockEl.dataset.blockId);
        const block = allBlocks.find(b => b.id === blockId);

        if (!block) {
            blockEl.style.display = 'none';
            return;
        }

        const blockName = block.name.toLowerCase();
        const locationsText = `${block.locationsCount} locais`.toLowerCase();

        const matches = blockName.includes(term) || locationsText.includes(term);

        if (matches) {
            blockEl.style.display = '';
            visibleCount++;
            visibleLocationsCount += block.locationsCount;
        } else {
            blockEl.style.display = 'none';
        }
    });

    // Atualizar contadores com resultados filtrados
    document.getElementById('blocksCount').textContent = term
        ? `${visibleCount} de ${allBlocks.length} blocos`
        : `${allBlocks.length} blocos`;

    const totalLocations = allBlocks.reduce((sum, b) => sum + b.locationsCount, 0);
    document.getElementById('locationsCount').textContent = term
        ? `${visibleLocationsCount} de ${totalLocations} locais`
        : `${totalLocations} locais`;
}

function updateBlocksCount() {
    if (allBlocks.length === 0) {
        document.getElementById('blocksCount').textContent = '0 blocos';
        document.getElementById('locationsCount').textContent = '0 locais';
        return;
    }

    const totalLocations = allBlocks.reduce((sum, b) => sum + b.locationsCount, 0);

    document.getElementById('blocksCount').textContent = `${allBlocks.length} blocos`;
    document.getElementById('locationsCount').textContent = `${totalLocations} locais`;
}

// Expor funções globalmente para uso inline
window.handleBlockCheckboxChange = handleBlockCheckboxChange;
window.handleLocationCheckboxChange = handleLocationCheckboxChange;
window.centerMapOnBlock = centerMapOnBlock;
window.centerMapOnLocation = centerMapOnLocation;
window.generateBlockRoute = generateBlockRoute;
window.filterBlocks = filterBlocks;

console.log('✅ Otimizador de Blocos carregado - v20251210160831');
console.log('🔍 DEBUG MODE ATIVO - Verificando distâncias dos blocos');
