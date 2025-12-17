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
let routeMarkers = [];  // Marcadores da rota (🚀, 🏁, números)
let currentOptimizedRoute = null;
let markerClusterGroup = null;
let allBlocks = [];
let blocksLoaded = false; // Flag para evitar carregamento duplicado

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
    console.log(`🗺️ visualizeBlocksOnMap chamado com ${blocks.length} blocos`);
    clearMapMarkers();

    // DEDUPLICAR blocos com coordenadas idênticas (problema na API)
    const uniqueBlocks = [];
    const seenCoords = new Set();

    blocks.forEach(block => {
        const centerLat = block.centerLatitude || block.center_latitude;
        const centerLon = block.centerLongitude || block.center_longitude;
        const coordKey = `${centerLat},${centerLon}`;

        if (!seenCoords.has(coordKey)) {
            seenCoords.add(coordKey);
            uniqueBlocks.push(block);
        } else {
            console.warn(`⚠️ Bloco duplicado ignorado: ${block.name} (${coordKey})`);
        }
    });

    console.log(`📊 ${blocks.length} blocos recebidos, ${uniqueBlocks.length} únicos após deduplicação`);

    // Criar grupo de clustering para os locais
    markerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
    });

    let totalLocations = 0;
    uniqueBlocks.forEach((block, index) => {
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
                totalLocations++;
            });
        }
    });

    // Adicionar cluster ao mapa
    map.addLayer(markerClusterGroup);

    // Ajustar zoom
    if (uniqueBlocks.length > 0) {
        const bounds = markerClusterGroup.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    console.log(`✅ ${uniqueBlocks.length} blocos únicos e ${totalLocations} locais visualizados no mapa com clustering`);
}

function clearMapMarkers() {
    console.log('🧹 Limpando marcadores do mapa...');

    // Remover cluster group
    if (markerClusterGroup) {
        console.log('  - Removendo cluster group');
        map.removeLayer(markerClusterGroup);
        markerClusterGroup = null;
    }

    // Remover todos os marcadores de blocos
    Object.keys(blockMarkers).forEach(blockId => {
        const marker = blockMarkers[blockId];
        if (marker && map.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    });

    // Remover todos os marcadores de locais
    Object.keys(locationMarkers).forEach(locationId => {
        const marker = locationMarkers[locationId];
        if (marker && map.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    });

    // Remover círculos
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

    console.log('✅ Marcadores limpos');
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

/**
 * Otimiza rotas usando a API Python (algoritmo avançado com OSRM + PyVRP)
 */
async function optimizeWithPythonAPI(locations, maxDiameterKm, maxLocaisPerRota, importBatch) {
    try {
        console.log(`🐍 Iniciando otimização Python ASSÍNCRONA com ${locations.length} locais...`);

        const payload = {
            base: {
                lat: BASE_I9.latitude,
                lon: BASE_I9.longitude,
                name: BASE_I9.name
            },
            locais: locations.map(loc => ({
                id: loc.id || Math.random(),
                lat: loc.latitude,
                lon: loc.longitude,
                name: loc.name,
                endereco: loc.address || ''
            })),
            max_diameter_km: 5.0,  // 5km de diâmetro máximo
            max_locais_por_rota: 6   // Máximo 6 locais por rota
        };

        // 1. Iniciar job assíncrono
        const startResponse = await fetch('https://floripa.in9automacao.com.br/otimizar-rotas-async.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!startResponse.ok) {
            const errorText = await startResponse.text();
            console.error('Erro HTTP:', startResponse.status, errorText);
            throw new Error(`Erro ao iniciar job: HTTP ${startResponse.status}`);
        }

        const responseText = await startResponse.text();
        if (!responseText || responseText.trim() === '') {
            throw new Error('Resposta vazia da API');
        }

        const startData = JSON.parse(responseText);
        if (!startData.success || !startData.job_id) {
            throw new Error(startData.error || 'Erro ao iniciar job');
        }

        const jobId = startData.job_id;
        console.log(`📋 Job ${jobId} iniciado. Aguardando processamento...`);

        // 2. Fazer polling até completar
        let attempts = 0;
        const maxAttempts = 1800; // 60 minutos (2s * 1800)

        while (attempts < maxAttempts) {
            await new Promise(resolve => setTimeout(resolve, 2000)); // Aguardar 2s
            attempts++;

            const statusResponse = await fetch(`https://floripa.in9automacao.com.br/otimizar-rotas-async.php?job_id=${jobId}`);

            if (!statusResponse.ok) {
                console.error(`Erro HTTP ${statusResponse.status} ao verificar status`);
                continue; // Tentar novamente
            }

            const statusText = await statusResponse.text();
            if (!statusText || statusText.trim() === '') {
                console.warn('Resposta vazia ao verificar status, tentando novamente...');
                continue;
            }

            const statusData = JSON.parse(statusText);

            if (!statusData.success) {
                throw new Error(statusData.error || 'Erro ao verificar status');
            }

            console.log(`⏳ Status: ${statusData.status} (${attempts}/${maxAttempts})`);

            if (statusData.status === 'completed') {
                console.log(`✅ Otimização concluída!`, statusData.result.resumo);

                // Converter blocos
                const blocks = [];
                for (let i = 0; i < statusData.result.blocos.length; i++) {
                    const bloco = statusData.result.blocos[i];

                    // Extrair IDs dos locais das rotas
                    const locationIds = [];
                    if (bloco.rotas && Array.isArray(bloco.rotas)) {
                        for (const rota of bloco.rotas) {
                            // API Python retorna "locations" (não "location_ids")
                            if (rota.locations && Array.isArray(rota.locations)) {
                                // Converter strings para números
                                const ids = rota.locations.map(id => parseInt(id));
                                locationIds.push(...ids);
                            }
                        }
                    }
                    console.log(`Bloco ${i + 1}: ${locationIds.length} location IDs extraídos:`, locationIds);

                    blocks.push({
                        id: bloco.bloco_id,
                        name: `Bloco Python #${i + 1}`,
                        center_latitude: bloco.center_lat,
                        center_longitude: bloco.center_lon,
                        diameterKm: bloco.diameter_km,
                        locationsCount: bloco.num_locais,
                        routesCount: bloco.num_rotas,
                        totalDistanceKm: bloco.distancia_total_km,
                        importBatch: importBatch,
                        algorithm: 'python',
                        routes: bloco.rotas,
                        locationIds: [...new Set(locationIds)] // Deduplicate
                    });
                }
                return blocks;

            } else if (statusData.status === 'failed') {
                throw new Error(`Job falhou: ${statusData.error}`);
            }
            // Status 'pending' ou 'processing' - continuar polling
        }

        throw new Error('Timeout: processamento demorou mais de 60 minutos');

    } catch (error) {
        console.error('❌ Erro ao otimizar com Python API:', error);
        throw new Error('Erro ao otimizar com algoritmo avançado: ' + error.message);
    }
}

/**
 * Salvar rotas otimizadas para tabela FF_Rotas (envio via WhatsApp)
 */
async function salvarRotasParaWhatsApp(blocos, importBatch) {
    console.log(`📱 Salvando ${blocos.length} rotas para envio via WhatsApp...`);

    let rotasSalvas = 0;

    for (const bloco of blocos) {
        try {
            // Preparar dados da rota
            const rotaData = {
                bloco_id: bloco.id, // ID do bloco salvo no banco
                motorista_id: null, // Será atribuído depois na interface
                veiculo_id: null,   // Será atribuído depois na interface
                base_lat: BASE_I9.latitude,
                base_lon: BASE_I9.longitude,
                locais_ordenados: (bloco.locations || []).map(loc => ({
                    id: loc.id,
                    lat: loc.latitude,
                    lon: loc.longitude,
                    nome: loc.name,
                    endereco: loc.address || ''
                })),
                distancia_total_km: bloco.totalDistanceKm || 0,
                tempo_total_min: Math.round((bloco.totalDurationMin || 0))
            };

            // Salvar rota
            const response = await fetch('https://floripa.in9automacao.com.br/salvar-rota-whatsapp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(rotaData)
            });

            const data = await response.json();

            if (data.success) {
                bloco.rota_id = data.rota_id;
                bloco.link_google_maps = data.link_google_maps;
                rotasSalvas++;
                console.log(`✅ Rota #${data.rota_id} salva para bloco ${bloco.name}`);
            } else {
                console.warn(`⚠️ Erro ao salvar rota do bloco ${bloco.name}:`, data.error);
            }

        } catch (error) {
            console.error(`❌ Erro ao salvar rota do bloco ${bloco.name}:`, error);
        }
    }

    console.log(`📱 ${rotasSalvas}/${blocos.length} rotas salvas com sucesso`);
    return rotasSalvas;
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

        // Associar IDs aos locais (converter para número)
        for (let i = 0; i < locations.length && i < insertedIds.length; i++) {
            locations[i].id = parseInt(insertedIds[i]);
        }

        let blocks = [];

        // Se clustering automático estiver ativado
        if (autoClustering && insertedIds.length > 0) {
            // Usar API Python com OSRM (distâncias reais) + PyVRP (otimização)
            updateProgress(70, 'Otimizando rotas com OSRM + PyVRP...');

            const pythonBlocks = await optimizeWithPythonAPI(locations, maxDistanceKm, maxLocationsPerBlock, importBatch);

            // Popular locations dos blocos Python usando locationIds
            console.log(`📊 Array locations tem ${locations.length} elementos`);
            console.log(`📊 TODOS os IDs do array locations:`, locations.map(l => l.id));
            console.log(`📊 Primeiros 3 IDs:`, locations.slice(0, 3).map(l => l.id));
            console.log(`📊 Tipos dos IDs:`, locations.slice(0, 3).map(l => typeof l.id));

            for (const block of pythonBlocks) {
                block.locations = [];
                if (block.locationIds && block.locationIds.length > 0) {
                    console.log(`🔍 Procurando IDs do bloco ${block.name}:`, block.locationIds.slice(0, 3));
                    console.log(`🔍 Tipos dos IDs procurados:`, block.locationIds.slice(0, 3).map(id => typeof id));

                    // Mapear os IDs para os objetos completos de locations
                    for (const locId of block.locationIds) {
                        const loc = locations.find(l => l.id === locId);
                        if (loc) {
                            block.locations.push({
                                id: loc.id,
                                name: loc.name,
                                latitude: loc.latitude,
                                longitude: loc.longitude,
                                address: loc.address || ''
                            });
                        } else {
                            console.warn(`⚠️ Local não encontrado para ID: ${locId}`);
                        }
                    }
                }
                console.log(`✅ Bloco ${block.name}: ${block.locations.length} locais populados`);
            }

                // Salvar blocos no banco de dados
                updateProgress(90, 'Salvando blocos otimizados no banco...');
                for (const block of pythonBlocks) {
                    // Salvar bloco via API (usando POST com createSingleBlock)
                    try {
                        const saveResponse = await fetch('https://floripa.in9automacao.com.br/blocks-api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                createSingleBlock: true,  // Flag para diferenciar de clustering
                                name: block.name,
                                center_latitude: block.center_latitude,
                                center_longitude: block.center_longitude,
                                diameterKm: block.diameterKm,
                                locationsCount: block.locationsCount,
                                routesCount: block.routesCount,
                                totalDistanceKm: block.totalDistanceKm,
                                importBatch: block.importBatch,
                                algorithm: block.algorithm,
                                locationIds: block.locationIds || []
                            })
                        });

                        if (saveResponse.ok) {
                            const saveData = await saveResponse.json();
                            if (saveData.success && saveData.block) {
                                block.id = saveData.block.id; // Atualizar com ID do banco
                            }
                        }
                    } catch (err) {
                        console.warn('⚠️ Erro ao salvar bloco:', err);
                    }
                }

            blocks = pythonBlocks;
            console.log(`✅ ${blocks.length} blocos criados e salvos com OSRM + PyVRP`);

            // Salvar rotas otimizadas para envio via WhatsApp
            await salvarRotasParaWhatsApp(pythonBlocks, importBatch);
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

    console.log('✅ Handlers de blocos configurados');
}

// Carregar blocos existentes ao iniciar
async function loadExistingBlocks() {
    // Evitar carregamento duplicado
    if (blocksLoaded) {
        console.log('⚠️ Blocos já carregados, pulando carregamento duplicado');
        return;
    }

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

        // DEDUPLICAR blocos antes de processar
        const uniqueBlocks = [];
        const seenCoords = new Set();

        data.blocks.forEach(block => {
            const centerLat = block.centerLatitude || block.center_latitude;
            const centerLon = block.centerLongitude || block.center_longitude;
            const coordKey = `${centerLat},${centerLon}`;

            if (!seenCoords.has(coordKey)) {
                seenCoords.add(coordKey);
                uniqueBlocks.push(block);
            } else {
                console.warn(`⚠️ Bloco duplicado ignorado na lista: ${block.name} (ID: ${block.id})`);
            }
        });

        console.log(`📊 ${data.blocks.length} blocos recebidos da API, ${uniqueBlocks.length} únicos`);

        // Armazenar blocos globalmente (apenas os únicos)
        allBlocks = uniqueBlocks;

        // Renderizar blocos na lista
        const blocksListContainer = document.getElementById('blocksList');
        blocksListContainer.innerHTML = '';

        uniqueBlocks.forEach(block => {
            const blockElement = createBlockElement(block);
            blocksListContainer.appendChild(blockElement);
        });

        // Atualizar contadores
        updateBlocksCount();

        // Visualizar os blocos no mapa (apenas os únicos)
        visualizeBlocksOnMap(uniqueBlocks);

        // Mostrar container de blocos
        document.getElementById('blocksContainer').classList.remove('hidden');

        // Marcar como carregado
        blocksLoaded = true;

        console.log(`✅ ${uniqueBlocks.length} blocos únicos carregados e exibidos automaticamente no mapa e na lista`);

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

        console.log('📋 Blocos com rota_id:');
        data.blocks.forEach(block => {
            if (block.rota_id) {
                console.log(`  - ${block.name} (ID ${block.id}): rota_id = ${block.rota_id}`);
            }
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

    // Botões de ação
    const actionButtons = document.createElement('div');
    actionButtons.className = 'px-3 pb-3 space-y-2';

    // Botão para gerar rota no mapa
    const routeButton = `
        <button onclick="generateBlockRoute(${JSON.stringify(block).replace(/"/g, '&quot;')})"
                class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center justify-center gap-2">
            <span class="material-symbols-outlined" style="font-size: 20px;">route</span>
            <span>Ver Rota no Mapa</span>
        </button>
    `;

    // Botão para enviar por WhatsApp OU gerar rota
    let secondButton = '';
    if (block.rota_id) {
        // Se já tem rota salva → mostrar botão de WhatsApp
        secondButton = `
            <button onclick="enviarRotaWhatsApp(${block.id}, ${block.rota_id})"
                    class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                <span>📱 Enviar por WhatsApp</span>
            </button>
        `;
    } else {
        // Se não tem rota → mostrar botão para gerar rota
        secondButton = `
            <button onclick="gerarRotaParaBloco(${block.id}, '${block.name.replace(/'/g, "\\'")}')"
                    class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center gap-2"
                    id="gerar-rota-${block.id}">
                <span class="material-symbols-outlined" style="font-size: 20px;">autorenew</span>
                <span>🔄 Gerar Rota para WhatsApp</span>
            </button>
        `;
    }

    actionButtons.innerHTML = routeButton + secondButton;

    blockDiv.appendChild(locationsContainer);
    blockDiv.appendChild(actionButtons);

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
    // Limpar rota anterior
    if (routeLayer) {
        map.removeLayer(routeLayer);
        routeLayer = null;
    }

    // Limpar marcadores antigos da rota
    routeMarkers.forEach(marker => {
        if (map.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    });
    routeMarkers = [];

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

        const marker = L.marker([waypoint.lat, waypoint.lon], { icon })
            .addTo(map)
            .bindPopup(popupContent);

        routeMarkers.push(marker);  // Salvar para poder remover depois
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
            returnToStart: false  // Mostrar apenas IDA (sem volta para base)
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

/**
 * Gerar rota para bloco existente (sem rota salva)
 */
async function gerarRotaParaBloco(blockId, blockName) {
    try {
        showLoading(`Gerando rota para ${blockName}...`);
        console.log(`🔄 Gerando rota para bloco #${blockId} (${blockName})...`);

        // 1. Buscar localizações do bloco via API
        const response = await fetch(`https://floripa.in9automacao.com.br/locations-api.php?block_id=${blockId}`);

        if (!response.ok) {
            throw new Error(`Erro ao buscar localizações: HTTP ${response.status}`);
        }

        const data = await response.json();

        if (!data.success || !data.locations || data.locations.length === 0) {
            throw new Error('Bloco não possui localizações');
        }

        const locations = data.locations;
        console.log(`📍 ${locations.length} localizações encontradas`);

        // 2. Calcular distância aproximada (0.5km por local como estimativa)
        const estimatedDistanceKm = locations.length * 0.5;
        const estimatedTimeMin = locations.length * 5; // 5 min por local

        // 3. Preparar objeto do bloco para salvar rota
        const blocoComLocations = {
            id: blockId,
            name: blockName,
            locations: locations.map(loc => ({
                id: loc.id,
                latitude: loc.latitude,
                longitude: loc.longitude,
                name: loc.name,
                address: loc.address || ''
            })),
            totalDistanceKm: estimatedDistanceKm,
            totalDurationMin: estimatedTimeMin
        };

        // 4. Salvar rota no banco
        const rotasSalvas = await salvarRotasParaWhatsApp([blocoComLocations], null);

        if (rotasSalvas > 0) {
            showNotification(`✅ Rota gerada e salva com sucesso!`, 'success');
            console.log(`✅ Rota gerada para bloco ${blockName}`);

            // 5. Recarregar o bloco para atualizar UI e mostrar botão de WhatsApp
            await recarregarBloco(blockId);
        } else {
            throw new Error('Erro ao salvar rota no banco de dados');
        }

    } catch (error) {
        console.error('❌ Erro ao gerar rota:', error);
        showNotification(`Erro ao gerar rota: ${error.message}`, 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Recarregar um bloco específico da API e atualizar na UI
 */
async function recarregarBloco(blockId) {
    try {
        // Buscar dados atualizados do bloco (com rota_id agora)
        const response = await fetch(`https://floripa.in9automacao.com.br/blocks-api.php?id=${blockId}`);
        const data = await response.json();

        if (data.success && data.block) {
            const blockElement = document.querySelector(`[data-block-id="${blockId}"]`);
            if (blockElement) {
                // Substituir elemento do bloco com versão atualizada
                const novoElemento = createBlockElement(data.block);
                blockElement.parentNode.replaceChild(novoElemento, blockElement);
                console.log(`🔄 Bloco #${blockId} atualizado na UI`);
            }
        }
    } catch (error) {
        console.error('Erro ao recarregar bloco:', error);
    }
}

/**
 * Enviar rota por WhatsApp
 */
async function enviarRotaWhatsApp(blockId, rotaId) {
    console.log(`📱 enviarRotaWhatsApp() chamado com: blockId=${blockId}, rotaId=${rotaId}`);

    const telefone = prompt('Digite o telefone (com código do país):\nExemplo: 5527999999999');

    if (!telefone) {
        return;
    }

    try {
        showLoading('Enviando rota por WhatsApp...');

        console.log(`📤 Enviando request: rota_id=${rotaId}, telefone=${telefone}`);

        const response = await fetch('https://frotas.in9automacao.com.br/enviar-rota-whatsapp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                rota_id: rotaId,
                telefone: telefone.replace(/\D/g, '')
            })
        });

        const data = await response.json();

        console.log('RESPOSTA COMPLETA DA API:', data);

        if (data.success) {
            showNotification(`✅ Rota enviada para ${telefone}!`, 'success');
            console.log('📱 Mensagem enviada com sucesso via WhatsApp');
        } else {
            console.error('[ERROR] Detalhes do erro:', JSON.stringify(data, null, 2));
            showNotification(`❌ Erro ao enviar: ${data.error}`, 'error');
        }

    } catch (error) {
        console.error('❌ Erro ao enviar WhatsApp:', error);
        showNotification(`Erro ao enviar WhatsApp: ${error.message}`, 'error');
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

    // Limpar marcadores antigos da rota
    routeMarkers.forEach(marker => {
        if (map.hasLayer(marker)) {
            map.removeLayer(marker);
        }
    });
    routeMarkers = [];

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

    // Adicionar marcadores dos waypoints
    if (route.waypoints && route.waypoints.length > 0) {
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
                    html: `<div style="background-color: #EF4444; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">${index}</div>`,
                    className: '',
                    iconSize: [30, 30]
                });
                popupContent = `<b>${index}. ${waypoint.address}</b>`;
            }

            const marker = L.marker([waypoint.lat, waypoint.lon], { icon })
                .addTo(map)
                .bindPopup(popupContent);

            routeMarkers.push(marker);
        });
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
