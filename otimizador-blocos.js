// ========== OTIMIZADOR DE ROTAS COM BLOCOS GEOGRÁFICOS ==========

console.log('🚀 otimizador-blocos.js CARREGADO - Versão com debug de filtros de cidade - ' + new Date().toLocaleTimeString());

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
let optimizedRoutesLayer = null; // Layer específico para rotas otimizadas coloridas
let uploadStartTime = null; // Tempo de início do upload
let batchTimes = []; // Tempos de processamento de cada lote

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

    // Carregar métricas do dashboard
    loadMetrics();
    console.log('✅ Métricas do dashboard carregando...');
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
        console.log(`🐍 Iniciando otimização Python SÍNCRONA com ${locations.length} locais...`);
        console.log(`⚙️  Configurações: max_diameter=${maxDiameterKm}km, max_locais=${maxLocaisPerRota}`);

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
            max_diameter_km: maxDiameterKm || 5.0,  // ✅ Usa valor do campo (fallback 5km)
            max_locais_por_rota: maxLocaisPerRota || 6   // ✅ Usa valor do campo (fallback 6)
        };

        // Chamar endpoint SÍNCRONO do Node.js (proxy para Python API)
        const response = await fetch('/api/routes/optimize-python', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erro HTTP:', response.status, errorText);
            throw new Error(`Erro na otimização: HTTP ${response.status}`);
        }

        const responseText = await response.text();
        if (!responseText || responseText.trim() === '') {
            throw new Error('Resposta vazia da API');
        }

        const result = JSON.parse(responseText);

        if (!result.success || !result.blocos) {
            throw new Error(result.error || 'Erro na resposta da API');
        }

        console.log(`✅ Otimização concluída!`, result.resumo);

        // Converter blocos
        const blocks = [];
        for (let i = 0; i < result.blocos.length; i++) {
            const bloco = result.blocos[i];

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

            // Usar tempo REAL do OSRM (já vem calculado com paradas)
            const distanciaKm = bloco.distancia_total_km || 0;
            const tempoTotalReal = bloco.tempo_total_min || 0; // ✅ TEMPO REAL DO OSRM + paradas

            // Se por algum motivo não vier tempo, calcular estimativa (fallback)
            let tempoFinal = tempoTotalReal;
            if (!tempoTotalReal) {
                const numLocais = bloco.num_locais || 0;
                const tempoViagem = (distanciaKm / 25) * 60; // minutos de viagem
                const tempoParadas = numLocais * 5; // 5 min por parada
                tempoFinal = Math.round(tempoViagem + tempoParadas);
                console.warn(`⚠️ Tempo não retornado pela API Python, usando estimativa: ${tempoFinal}min`);
            }

            // Calcular número de locais únicos
            const numLocaisUnicos = [...new Set(locationIds)].length;

            // Gerar nome descritivo baseado nos locais
            let nomeBloco = `Bloco #${i + 1}`;
            if (numLocaisUnicos > 0) {
                nomeBloco = `Bloco #${i + 1} - ${numLocaisUnicos} ${numLocaisUnicos > 1 ? 'locais' : 'local'}`;
                if (distanciaKm > 0) {
                    nomeBloco += ` (${distanciaKm.toFixed(1)}km)`;
                }
            }

            blocks.push({
                id: bloco.bloco_id,
                name: nomeBloco,
                center_latitude: bloco.center_lat,
                center_longitude: bloco.center_lon,
                diameterKm: bloco.diameter_km || 0,
                locationsCount: numLocaisUnicos, // ✅ Usar contagem calculada
                routesCount: bloco.num_rotas || 1,
                totalDistanceKm: distanciaKm,
                totalDurationMin: tempoFinal, // ✅ TEMPO REAL DO OSRM
                importBatch: importBatch,
                algorithm: 'python',
                routes: bloco.rotas,
                locationIds: [...new Set(locationIds)], // Deduplicate
                map_html: bloco.map_html // ✅ INCLUIR HTML DO MAPA
            });
        }
        return blocks;

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

                // Salvar também no histórico de otimizações
                try {
                    await saveToHistory(bloco);
                    console.log(`📊 Bloco ${bloco.name} salvo no histórico`);
                } catch (historyError) {
                    console.warn(`⚠️ Erro ao salvar histórico do bloco ${bloco.name}:`, historyError);
                    // Não falha a operação principal se o histórico falhar
                }
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

    // Adicionar spinner ao botão
    const btnProcessFile = document.getElementById('btnProcessFile');
    const originalBtnHTML = btnProcessFile.innerHTML;
    btnProcessFile.innerHTML = `
        <svg class="animate-spin h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Processando...</span>
    `;
    btnProcessFile.disabled = true;

    // Iniciar timer
    uploadStartTime = Date.now();
    batchTimes = [];

    try {
        updateProgress(10, '📂 Lendo arquivo Excel...');

        // Ler arquivo Excel no frontend
        const arrayBuffer = await selectedFile.arrayBuffer();
        const workbook = XLSX.read(arrayBuffer);
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const data = XLSX.utils.sheet_to_json(worksheet);

        console.log(`📊 ${data.length} linhas encontradas na planilha`);

        updateProgress(30, '📋 Validando e preparando dados...');

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

            // PROCESSAMENTO EM LOTES para grandes volumes
            const BATCH_SIZE = 500; // Processar 500 locais por vez
            let pythonBlocks = [];

            if (locations.length > BATCH_SIZE) {
                // DIVIDIR EM LOTES
                const totalBatches = Math.ceil(locations.length / BATCH_SIZE);
                console.log(`📦 Dividindo ${locations.length} locais em ${totalBatches} lotes de ${BATCH_SIZE}`);

                for (let batchIndex = 0; batchIndex < totalBatches; batchIndex++) {
                    const start = batchIndex * BATCH_SIZE;
                    const end = Math.min(start + BATCH_SIZE, locations.length);
                    const batchLocations = locations.slice(start, end);
                    const batchStartTime = Date.now();

                    const batchProgress = 70 + (batchIndex / totalBatches) * 20;

                    // Calcular estimativa de tempo restante
                    let estimateMsg = '';
                    if (batchTimes.length > 0) {
                        const avgBatchTime = batchTimes.reduce((a, b) => a + b, 0) / batchTimes.length;
                        const remainingBatches = totalBatches - batchIndex;
                        const estimatedMs = avgBatchTime * remainingBatches;
                        const estimatedMin = Math.ceil(estimatedMs / 60000);
                        estimateMsg = ` • ~${estimatedMin} min restantes`;
                    }

                    updateProgress(
                        batchProgress,
                        `🗺️ Lote ${batchIndex + 1}/${totalBatches}: Calculando rotas OSRM (${batchLocations.length} locais)${estimateMsg}`
                    );

                    console.log(`📊 Lote ${batchIndex + 1}/${totalBatches}: processando locais ${start + 1} a ${end}`);

                    const batchBlocks = await optimizeWithPythonAPI(
                        batchLocations,
                        maxDistanceKm,
                        maxLocationsPerBlock,
                        `${importBatch}_lote${batchIndex + 1}`
                    );

                    // Registrar tempo do lote
                    const batchTime = Date.now() - batchStartTime;
                    batchTimes.push(batchTime);

                    pythonBlocks.push(...batchBlocks);
                    console.log(`✅ Lote ${batchIndex + 1} concluído em ${Math.round(batchTime / 1000)}s: ${batchBlocks.length} blocos gerados`);
                }

                console.log(`✅ TODOS OS LOTES PROCESSADOS: ${pythonBlocks.length} blocos totais`);
            } else {
                // PROCESSAMENTO NORMAL (< 500 locais)
                pythonBlocks = await optimizeWithPythonAPI(locations, maxDistanceKm, maxLocationsPerBlock, importBatch);
            }

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
                                locationIds: block.locationIds || [],
                                map_html: block.map_html  // ✅ ADICIONAR HTML DO MAPA
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
        // Restaurar botão ao estado original
        const btnProcessFile = document.getElementById('btnProcessFile');
        btnProcessFile.innerHTML = originalBtnHTML;
        btnProcessFile.disabled = false;
    }
}

function updateProgress(percent, message, showTime = true) {
    document.getElementById('progressBar').style.width = percent + '%';
    document.getElementById('progressText').textContent = percent + '%';

    // Calcular tempo decorrido
    let fullMessage = message;
    if (showTime && uploadStartTime) {
        const elapsedMs = Date.now() - uploadStartTime;
        const elapsedSec = Math.floor(elapsedMs / 1000);
        const minutes = Math.floor(elapsedSec / 60);
        const seconds = elapsedSec % 60;
        const timeStr = minutes > 0
            ? `${minutes}min ${seconds}s`
            : `${seconds}s`;

        fullMessage = `${message} • ⏱️ ${timeStr} decorridos`;
    }

    document.getElementById('progressMessage').textContent = fullMessage;
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

        // Criar filtros dinâmicos de cidade (assíncrono para não travar)
        console.log('⏰ ANTES do setTimeout - vai processar filtros de cidade em 50ms');
        setTimeout(() => {
            console.log('⏰ DENTRO do setTimeout - iniciando processamento de cidades AGORA');
            // Resetar contadores de debug para ver logs dos primeiros blocos/endereços
            _extractCityCallCount = 0;
            _getBlockCityCallCount = 0;

            console.log('🏙️ Processando cidades...');
            console.log('📊 Total de blocos recebidos:', uniqueBlocks.length);
            console.log('📍 Primeiro bloco (sample):', uniqueBlocks[0]);

            if (uniqueBlocks[0] && uniqueBlocks[0].locations) {
                console.log('📍 Primeiro bloco tem', uniqueBlocks[0].locations.length, 'locais');
                console.log('📍 Primeiro local do primeiro bloco:', uniqueBlocks[0].locations[0]);
            }

            const cities = getAllCities(uniqueBlocks);
            console.log('🏙️ Cidades encontradas:', cities.length, '→', cities);

            createCityFilters(cities);
        }, 50);

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

        // Criar filtros dinâmicos de cidade (assíncrono para não travar)
        console.log('⏰ ANTES do setTimeout - vai processar filtros de cidade em 50ms');
        setTimeout(() => {
            console.log('⏰ DENTRO do setTimeout - iniciando processamento de cidades AGORA');
            // Resetar contadores de debug para ver logs dos primeiros blocos/endereços
            _extractCityCallCount = 0;
            _getBlockCityCallCount = 0;

            console.log('🏙️ Processando cidades...');
            console.log('📊 Total de blocos recebidos:', data.blocks.length);
            console.log('📍 Primeiro bloco (sample):', data.blocks[0]);

            if (data.blocks[0] && data.blocks[0].locations) {
                console.log('📍 Primeiro bloco tem', data.blocks[0].locations.length, 'locais');
                console.log('📍 Primeiro local do primeiro bloco:', data.blocks[0].locations[0]);
            }

            const cities = getAllCities(data.blocks);
            console.log('🏙️ Cidades encontradas:', cities.length, '→', cities);

            createCityFilters(cities);
        }, 50);

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

    // Garantir valores válidos
    const locationsCount = block.locationsCount || block.locations?.length || 0;
    const blockName = block.name || `Bloco #${block.id}`;

    const summary = document.createElement('summary');
    summary.className = 'flex items-center justify-between p-3 cursor-pointer';
    summary.innerHTML = `
        <div class="flex items-center gap-3" onclick="event.stopPropagation()">
            <input type="checkbox"
                   class="block-checkbox form-checkbox rounded text-primary focus:ring-primary"
                   data-block-id="${block.id}"
                   onchange="handleBlockCheckboxChange(${block.id})">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <p id="block-name-${block.id}" class="text-sm font-medium text-gray-800 dark:text-gray-200 text-left">
                        ${blockName}
                    </p>
                    <button onclick="event.stopPropagation(); renameBlock(${block.id}, '${blockName.replace(/'/g, "\\'")}')"
                            class="p-1 text-gray-400 hover:text-primary rounded hover:bg-primary/10 transition-colors"
                            title="Renomear bloco">
                        <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 text-left">
                    ${locationsCount} ${locationsCount !== 1 ? 'locais' : 'local'} ${distanceInfo}
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

    // Botão para visualizar mapa otimizado (com cores diferentes)
    const savedMapButton = block.map_html ? `
        <button onclick="viewSavedMap(${block.id})"
                class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center justify-center gap-2"
                title="Visualizar rota com segmentos coloridos por destino">
            <span class="material-symbols-outlined" style="font-size: 20px;">map</span>
            <span>🗺️ Ver Segmentos Coloridos</span>
        </button>
    ` : '';

    // Botão para enviar por WhatsApp OU gerar rota
    let secondButton = '';
    if (block.rota_id) {
        // Se já tem rota salva → mostrar botão de WhatsApp
        secondButton = `
            <button onclick="enviarRotaWhatsApp(${block.id}, ${block.rota_id})"
                    class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center gap-2"
                    title="Enviar rota otimizada via WhatsApp para o motorista">
                <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                <span>📱 Enviar por WhatsApp</span>
            </button>
        `;
    } else {
        // Se não tem rota → mostrar botão para gerar rota
        secondButton = `
            <button onclick="gerarRotaParaBloco(${block.id}, '${block.name.replace(/'/g, "\\'")}')"
                    class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center gap-2"
                    id="gerar-rota-${block.id}"
                    title="Gerar link do Google Maps e salvar para envio via WhatsApp">
                <span class="material-symbols-outlined" style="font-size: 20px;">autorenew</span>
                <span>🔄 Gerar Rota para WhatsApp</span>
            </button>
        `;
    }

    // Histórico removido - não é necessário
    const historyButton = '';

    // Botão para exportar rota
    const exportButton = block.rota_id ? `
        <button onclick="showExportOptions(${block.id}, '${block.name.replace(/'/g, "\\'")}')"
                class="w-full px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors flex items-center justify-center gap-2"
                title="Exportar rota para PDF ou Excel">
            <span class="material-symbols-outlined" style="font-size: 20px;">download</span>
            <span>📥 Exportar Rota</span>
        </button>
    ` : '';

    actionButtons.innerHTML = savedMapButton + secondButton + historyButton + exportButton;

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

    // Adicionar spinner ao botão
    const btnOptimize = document.getElementById('btnOptimizeRoute');
    const originalOptimizeBtnHTML = btnOptimize.innerHTML;
    btnOptimize.innerHTML = `
        <svg class="animate-spin h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Otimizando...</span>
    `;
    btnOptimize.disabled = true;

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
        // Restaurar botão ao estado original
        const btnOptimize = document.getElementById('btnOptimizeRoute');
        btnOptimize.innerHTML = originalOptimizeBtnHTML;
        btnOptimize.disabled = selectedBlocks.size === 0 && selectedLocations.size === 0;
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

            // 5. Salvar no histórico de otimizações
            try {
                await saveToHistory(blocoComLocations);
                console.log(`📊 Otimização salva no histórico`);
            } catch (historyError) {
                console.warn('⚠️ Erro ao salvar no histórico:', historyError);
                // Não falha a operação principal se o histórico falhar
            }

            // 6. Recarregar o bloco para atualizar UI e mostrar botão de WhatsApp
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

    // Abrir modal para selecionar veículo, motorista e telefone
    // A função está definida em route-assignment-modal.js
    if (typeof abrirModalEnvioWhatsApp === 'function') {
        await abrirModalEnvioWhatsApp(blockId, rotaId);
    } else {
        console.error('❌ Função abrirModalEnvioWhatsApp não encontrada');
        showNotification('Erro: Modal de envio não carregado', 'error');
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
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in flex items-center gap-3`;

    // Container para a mensagem
    const messageSpan = document.createElement('span');
    messageSpan.textContent = message;
    notification.appendChild(messageSpan);

    // Botão fechar (sempre visível, mas mais importante para erros)
    const closeButton = document.createElement('button');
    closeButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">close</span>';
    closeButton.className = 'hover:bg-white/20 rounded p-1 transition-colors';
    closeButton.onclick = () => notification.remove();
    notification.appendChild(closeButton);

    document.body.appendChild(notification);

    // Duração baseada no tipo
    const autoDismiss = type !== 'error'; // Erros não fecham sozinhos
    const duration = type === 'success' ? 5000 : 8000; // Success 5s, outros 8s

    if (autoDismiss) {
        setTimeout(() => {
            notification.remove();
        }, duration);
    }

    console.log(`[${type.toUpperCase()}] ${message}`);
}

// ========== FILTRO E CONTADORES ==========

/**
 * Filtrar blocos com suporte a múltiplos critérios:
 * - Busca por nome: "bloco 5", "zona norte"
 * - Busca por quantidade: "5 locais", ">3", ">=5", "<10", "<=3"
 * - Busca por distância: ">5km", "<3km", ">=4km"
 */
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

        let matches = false;

        if (!term) {
            // Sem filtro, mostrar todos
            matches = true;
        } else {
            const blockName = block.name.toLowerCase();
            const locationsCount = block.locationsCount || 0;
            const maxDistance = block.maxPairDistanceKm || 0;

            // Busca por nome do bloco
            if (blockName.includes(term)) {
                matches = true;
            }

            // Busca por quantidade de locais exata: "5 locais", "3 local"
            if (term.match(/^\d+\s*(locais?)?$/)) {
                const num = parseInt(term);
                if (locationsCount === num) {
                    matches = true;
                }
            }

            // Busca por quantidade com operadores: ">5", ">=3", "<10", "<=3"
            const quantityMatch = term.match(/^(>=?|<=?)\s*(\d+)$/);
            if (quantityMatch) {
                const operator = quantityMatch[1];
                const value = parseInt(quantityMatch[2]);

                if (operator === '>' && locationsCount > value) matches = true;
                if (operator === '>=' && locationsCount >= value) matches = true;
                if (operator === '<' && locationsCount < value) matches = true;
                if (operator === '<=' && locationsCount <= value) matches = true;
            }

            // Busca por distância: ">5km", "<3km", ">=4km"
            const distanceMatch = term.match(/^(>=?|<=?)\s*(\d+(?:\.\d+)?)\s*km$/);
            if (distanceMatch) {
                const operator = distanceMatch[1];
                const value = parseFloat(distanceMatch[2]);

                if (operator === '>' && maxDistance > value) matches = true;
                if (operator === '>=' && maxDistance >= value) matches = true;
                if (operator === '<' && maxDistance < value) matches = true;
                if (operator === '<=' && maxDistance <= value) matches = true;
            }

            // Busca genérica por texto que contém números
            if (term.includes(locationsCount.toString())) {
                matches = true;
            }
        }

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

/**
 * Renomear um bloco existente
 */
async function renameBlock(blockId, currentName) {
    const newName = prompt('Digite o novo nome para o bloco:', currentName);

    if (!newName || newName === currentName || newName.trim() === '') {
        return;
    }

    try {
        const response = await fetch(`https://floripa.in9automacao.com.br/blocks-api.php?id=${blockId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: newName.trim()
            })
        });

        const result = await response.json();

        if (result.success) {
            // Atualizar o nome no DOM
            const nameElement = document.getElementById(`block-name-${blockId}`);
            if (nameElement) {
                nameElement.textContent = newName.trim();
            }

            // Atualizar no array de blocos
            const block = allBlocks.find(b => b.id === blockId);
            if (block) {
                block.name = newName.trim();
            }

            // Mostrar mensagem de sucesso
            alert(`✅ Bloco renomeado com sucesso para "${newName.trim()}"`);
        } else {
            throw new Error(result.error || 'Erro ao renomear bloco');
        }
    } catch (error) {
        console.error('Erro ao renomear bloco:', error);
        alert(`❌ Erro ao renomear bloco: ${error.message}`);
    }
}

// Expor funções globalmente para uso inline
window.handleBlockCheckboxChange = handleBlockCheckboxChange;
window.handleLocationCheckboxChange = handleLocationCheckboxChange;
/**
 * Visualizar mapa salvo do bloco (com cores diferentes por segmento)
 */
async function viewSavedMap(blockId) {
    try {
        // Buscar bloco do banco para pegar o map_html
        const response = await fetch(`https://floripa.in9automacao.com.br/blocks-api.php?id=${blockId}`);
        const data = await response.json();

        if (!data.success || !data.block) {
            throw new Error('Bloco não encontrado');
        }

        const block = data.block;

        // Buscar locais do bloco para desenhar rota (já vem em ordem otimizada)
        const locationsResponse = await fetch(`https://floripa.in9automacao.com.br/locations-api.php?block_id=${blockId}`);
        const locationsData = await locationsResponse.json();

        if (!locationsData.success || !locationsData.locations) {
            showNotification('Não foi possível carregar os locais do bloco', 'error');
            return;
        }

        const locations = locationsData.locations;

        // Cores diferentes para cada segmento
        const colors = ['#0066FF', '#FF0000', '#00CC00', '#FF6600', '#9900FF', '#FF0099', '#00CCCC'];

        // Estilos alternados para melhor diferenciação visual
        const lineStyles = [
            { dashArray: null, weight: 5 },           // Sólido grosso
            { dashArray: '10, 5', weight: 4 },        // Tracejado médio
            { dashArray: '2, 8', weight: 5 },         // Pontilhado grosso
            { dashArray: '15, 10, 5, 10', weight: 4 },// Traço-ponto médio
            { dashArray: null, weight: 6 },           // Sólido muito grosso
            { dashArray: '5, 5', weight: 5 },         // Tracejado curto grosso
            { dashArray: '20, 10', weight: 4 }        // Tracejado longo médio
        ];

        // Limpar rotas otimizadas anteriores usando layer dedicado
        if (optimizedRoutesLayer) {
            map.removeLayer(optimizedRoutesLayer);
        }
        optimizedRoutesLayer = L.layerGroup().addTo(map);

        const base = { lat: BASE_I9.latitude, lng: BASE_I9.longitude };

        showNotification('Carregando rotas otimizadas...', 'info');

        // Desenhar rota sequencial: Base → Loc1 → Loc2 → Loc3 → ...
        let prevPoint = base;

        for (let i = 0; i < locations.length; i++) {
            const loc = locations[i];
            const currentPoint = { lat: loc.latitude, lng: loc.longitude };
            const color = colors[i % colors.length];
            const style = lineStyles[i % lineStyles.length];

            try {
                // Buscar geometria real da rota via OSRM (proxy Node.js)
                const osrmUrl = `/api/osrm/route/${prevPoint.lng},${prevPoint.lat};${currentPoint.lng},${currentPoint.lat}?overview=full&geometries=geojson`;
                const osrmResponse = await fetch(osrmUrl);
                const osrmData = await osrmResponse.json();

                if (osrmData.code === 'Ok' && osrmData.routes && osrmData.routes.length > 0) {
                    // Usar geometria real do OSRM
                    const geometry = osrmData.routes[0].geometry.coordinates;
                    const latlngs = geometry.map(coord => [coord[1], coord[0]]); // [lng, lat] → [lat, lng]

                    // Desenhar borda preta semi-transparente (linha mais grossa por baixo)
                    const borderLine = L.polyline(latlngs, {
                        color: '#000000',
                        weight: style.weight + 3,
                        opacity: 0.4,
                        dashArray: style.dashArray
                    }).addTo(optimizedRoutesLayer);

                    // Desenhar linha colorida por cima com estilo específico
                    const line = L.polyline(latlngs, {
                        color: color,
                        weight: style.weight,
                        opacity: 1,
                        dashArray: style.dashArray
                    }).addTo(optimizedRoutesLayer);

                    const fromName = i === 0 ? 'Base i9' : locations[i-1].name || `Local ${i}`;
                    const toName = loc.name || `Local ${i + 1}`;
                    const distanceKm = (osrmData.routes[0].distance / 1000).toFixed(2);

                    // Adicionar número do segmento ao popup
                    line.bindPopup(`<strong>Segmento ${i + 1}</strong><br>🚗 ${fromName} → ${toName}<br>📏 ${distanceKm} km`);
                } else {
                    // Fallback: linha reta se OSRM falhar
                    // Borda preta
                    const borderLine = L.polyline([
                        [prevPoint.lat, prevPoint.lng],
                        [currentPoint.lat, currentPoint.lng]
                    ], {
                        color: '#000000',
                        weight: style.weight + 3,
                        opacity: 0.4,
                        dashArray: style.dashArray
                    }).addTo(optimizedRoutesLayer);

                    // Linha colorida
                    const line = L.polyline([
                        [prevPoint.lat, prevPoint.lng],
                        [currentPoint.lat, currentPoint.lng]
                    ], {
                        color: color,
                        weight: style.weight,
                        opacity: 1,
                        dashArray: style.dashArray
                    }).addTo(optimizedRoutesLayer);

                    const fromName = i === 0 ? 'Base i9' : locations[i-1].name || `Local ${i}`;
                    const toName = loc.name || `Local ${i + 1}`;

                    line.bindPopup(`<strong>Segmento ${i + 1}</strong><br>🚗 ${fromName} → ${toName}<br>⚠️ Rota estimada`);
                }
            } catch (osrmError) {
                console.warn('Erro ao buscar rota OSRM, usando linha reta:', osrmError);

                // Fallback: linha reta
                // Borda preta
                const borderLine = L.polyline([
                    [prevPoint.lat, prevPoint.lng],
                    [currentPoint.lat, currentPoint.lng]
                ], {
                    color: '#000000',
                    weight: style.weight + 3,
                    opacity: 0.4,
                    dashArray: style.dashArray
                }).addTo(optimizedRoutesLayer);

                // Linha colorida
                const line = L.polyline([
                    [prevPoint.lat, prevPoint.lng],
                    [currentPoint.lat, currentPoint.lng]
                ], {
                    color: color,
                    weight: style.weight,
                    opacity: 1,
                    dashArray: style.dashArray
                }).addTo(optimizedRoutesLayer);

                const fromName = i === 0 ? 'Base i9' : locations[i-1].name || `Local ${i}`;
                const toName = loc.name || `Local ${i + 1}`;

                line.bindPopup(`<strong>Segmento ${i + 1}</strong><br>🚗 ${fromName} → ${toName}<br>⚠️ Rota estimada`);
            }

            prevPoint = currentPoint;
        }

        // Adicionar marcadores com números de parada
        // Marcador da base (ponto de partida)
        const baseMarker = L.marker([base.lat, base.lng], {
            icon: L.divIcon({
                className: 'custom-stop-marker',
                html: `<div style="background: #10B981; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">🏠</div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            })
        }).addTo(optimizedRoutesLayer);
        baseMarker.bindPopup('<strong>🏠 Base i9 Engenharia</strong><br>Ponto de partida');

        // Marcadores dos locais (paradas)
        locations.forEach((loc, index) => {
            const isLastStop = index === locations.length - 1;
            const stopNumber = index + 1;
            const label = isLastStop ? '🏁' : stopNumber;
            const bgColor = isLastStop ? '#EF4444' : colors[index % colors.length];
            const title = isLastStop ? 'Parada Final' : `Parada ${stopNumber}`;

            const marker = L.marker([loc.latitude, loc.longitude], {
                icon: L.divIcon({
                    className: 'custom-stop-marker',
                    html: `<div style="background: ${bgColor}; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">${label}</div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                })
            }).addTo(optimizedRoutesLayer);

            marker.bindPopup(`<strong>${title}</strong><br>${loc.name || 'Local ' + stopNumber}<br>${loc.address || ''}`);
        });

        // Centralizar mapa nos locais
        const allCoords = locations.map(l => [l.latitude, l.longitude]);
        allCoords.push([base.lat, base.lng]);
        map.fitBounds(allCoords);

        showNotification(`✅ Rota sequencial exibida com ${locations.length} paradas! 🎨`, 'success');

    } catch (error) {
        console.error('Erro ao visualizar mapa:', error);
        showNotification(`Erro ao abrir mapa: ${error.message}`, 'error');
    }
}

/**
 * Visualizar histórico de otimizações de um bloco
 */
async function viewRouteHistory(blockId) {
    try {
        showLoading('Carregando histórico...');

        // Buscar histórico da API
        const response = await fetch(`https://floripa.in9automacao.com.br/route-history-api.php?block_id=${blockId}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar histórico');
        }

        const history = data.history;

        if (history.length === 0) {
            showNotification('Ainda não há histórico de otimizações para este bloco', 'info');
            hideLoading();
            return;
        }

        // Criar modal com histórico
        const modalHTML = `
            <div id="historyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target.id === 'historyModal') this.remove()">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-symbols-outlined">history</span>
                            Histórico de Otimizações - ${history[0].block_name}
                        </h2>
                        <button onclick="document.getElementById('historyModal').remove()"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Total de otimizações: <strong>${history.length}</strong>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data/Hora</th>
                                    <th class="px-4 py-3 text-left">Distância</th>
                                    <th class="px-4 py-3 text-left">Duração</th>
                                    <th class="px-4 py-3 text-left">Paradas</th>
                                    <th class="px-4 py-3 text-left">Veículo</th>
                                    <th class="px-4 py-3 text-left">Motorista</th>
                                    <th class="px-4 py-3 text-left">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                ${history.map((h, index) => {
                                    const date = new Date(h.optimization_date);
                                    const dateStr = date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                                    const timeStr = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                                    const isRecent = index === 0;

                                    return `
                                        <tr class="${isRecent ? 'bg-green-50 dark:bg-green-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'}">
                                            <td class="px-4 py-3">
                                                ${isRecent ? '<span class="inline-block px-2 py-1 text-xs bg-green-600 text-white rounded-full mr-2">Atual</span>' : ''}
                                                <div class="font-medium">${dateStr}</div>
                                                <div class="text-gray-500 text-xs">${timeStr}</div>
                                            </td>
                                            <td class="px-4 py-3 font-semibold">${h.total_distance_km ? h.total_distance_km + ' km' : '-'}</td>
                                            <td class="px-4 py-3">${h.total_duration_min ? h.total_duration_min + ' min' : '-'}</td>
                                            <td class="px-4 py-3">${h.num_stops || '-'}</td>
                                            <td class="px-4 py-3">
                                                ${h.vehicle_plate ? `<div class="font-medium">${h.vehicle_plate}</div>` : '-'}
                                                ${h.vehicle_name ? `<div class="text-xs text-gray-500">${h.vehicle_name}</div>` : ''}
                                            </td>
                                            <td class="px-4 py-3">${h.driver_name || '-'}</td>
                                            <td class="px-4 py-3">
                                                <button onclick="viewHistoricalRouteDetails(${h.id})"
                                                        class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                                    Ver Detalhes
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button onclick="document.getElementById('historyModal').remove()"
                                class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Adicionar modal ao DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);

    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
        showNotification(`Erro ao carregar histórico: ${error.message}`, 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Visualizar detalhes de uma otimização histórica
 */
async function viewHistoricalRouteDetails(historyId) {
    try {
        showLoading('Carregando detalhes...');

        const response = await fetch(`https://floripa.in9automacao.com.br/route-history-api.php?id=${historyId}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar detalhes');
        }

        const record = data.record;
        const routeData = record.route_data;

        // Criar modal com detalhes
        let detailsHTML = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Data:</span>
                        <span class="font-semibold ml-2">${new Date(record.optimization_date).toLocaleString('pt-BR')}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Distância:</span>
                        <span class="font-semibold ml-2">${record.total_distance_km} km</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Duração:</span>
                        <span class="font-semibold ml-2">${record.total_duration_min} min</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Paradas:</span>
                        <span class="font-semibold ml-2">${record.num_stops}</span>
                    </div>
                </div>
        `;

        if (routeData) {
            detailsHTML += `
                <div class="mt-4">
                    <h4 class="font-bold mb-2">Dados da Rota:</h4>
                    <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-auto max-h-64 text-xs">${JSON.stringify(routeData, null, 2)}</pre>
                </div>
            `;
        }

        detailsHTML += '</div>';

        // Usar função showModal se existir, senão criar modal simples
        showModalWithContent('Detalhes da Otimização', detailsHTML);

    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        showNotification(`Erro ao carregar detalhes: ${error.message}`, 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Função auxiliar para mostrar modal com conteúdo customizado
 */
function showModalWithContent(title, content) {
    const modalHTML = `
        <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target.id === 'detailsModal') this.remove()">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">${title}</h2>
                    <button onclick="document.getElementById('detailsModal').remove()"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div>${content}</div>
                <div class="mt-6 flex justify-end">
                    <button onclick="document.getElementById('detailsModal').remove()"
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Salvar otimização no histórico
 */
async function saveToHistory(blocoData) {
    try {
        // Preparar dados para salvar no histórico
        const historyData = {
            block_id: blocoData.id,
            vehicle_id: null, // Pode ser passado como parâmetro se disponível
            driver_id: null,  // Pode ser passado como parâmetro se disponível
            total_distance_km: blocoData.totalDistanceKm || 0,
            total_duration_min: blocoData.totalDurationMin || 0,
            num_stops: blocoData.locations ? blocoData.locations.length : 0,
            route_data: {
                locations: blocoData.locations,
                timestamp: new Date().toISOString()
            },
            created_by: 'frontend'
        };

        const response = await fetch('https://floripa.in9automacao.com.br/route-history-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(historyData)
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao salvar histórico');
        }

        return data;

    } catch (error) {
        console.error('Erro ao salvar no histórico:', error);
        throw error;
    }
}

// ========== EXPORTAÇÃO DE ROTAS ==========

/**
 * Exportar rota para PDF
 */
async function exportRouteToPDF(blockId) {
    try {
        showLoading('Gerando PDF...');

        // Buscar dados do bloco
        const response = await fetch(`https://floripa.in9automacao.com.br/blocks-api.php?id=${blockId}`);
        const data = await response.json();

        if (!data.success || !data.block) {
            throw new Error('Bloco não encontrado');
        }

        const block = data.block;

        // Buscar locais do bloco
        const locationsResponse = await fetch(`https://floripa.in9automacao.com.br/locations-api.php?block_id=${blockId}`);
        const locationsData = await locationsResponse.json();

        if (!locationsData.success) {
            throw new Error('Erro ao carregar locais');
        }

        const locations = locationsData.locations || [];

        // Criar PDF
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();

        // Título
        pdf.setFontSize(18);
        pdf.setFont(undefined, 'bold');
        pdf.text(`Rota: ${block.name}`, 20, 20);

        // Metadados
        pdf.setFontSize(12);
        pdf.setFont(undefined, 'normal');
        pdf.text(`Data: ${new Date().toLocaleDateString('pt-BR')}`, 20, 35);
        pdf.text(`Total de Paradas: ${locations.length}`, 20, 42);

        // Capturar screenshot do mapa (se visível)
        const mapElement = document.getElementById('map');
        if (mapElement && mapElement.offsetHeight > 0) {
            try {
                const canvas = await html2canvas(mapElement, {
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                });
                const imgData = canvas.toDataURL('image/png');
                pdf.addImage(imgData, 'PNG', 20, 50, 170, 100);
            } catch (mapError) {
                console.warn('Erro ao capturar mapa:', mapError);
                pdf.setFontSize(10);
                pdf.text('(Mapa não disponível para captura)', 20, 55);
            }
        }

        // Lista de paradas
        let yPos = mapElement && mapElement.offsetHeight > 0 ? 160 : 55;
        pdf.setFontSize(14);
        pdf.setFont(undefined, 'bold');
        pdf.text('Sequência de Paradas:', 20, yPos);

        yPos += 10;
        pdf.setFontSize(10);
        pdf.setFont(undefined, 'normal');

        locations.forEach((loc, idx) => {
            if (yPos > 270) {
                pdf.addPage();
                yPos = 20;
            }

            pdf.setFont(undefined, 'bold');
            pdf.text(`${idx + 1}. ${loc.name || 'Local ' + (idx + 1)}`, 25, yPos);
            yPos += 5;

            pdf.setFont(undefined, 'normal');
            const address = loc.address || 'Endereço não disponível';
            pdf.text(`   ${address}`, 25, yPos);
            yPos += 4;

            pdf.setFontSize(8);
            pdf.setTextColor(100, 100, 100);
            pdf.text(`   Lat: ${loc.latitude}, Lng: ${loc.longitude}`, 25, yPos);
            pdf.setTextColor(0, 0, 0);
            pdf.setFontSize(10);
            yPos += 8;
        });

        // Rodapé
        const pageCount = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            pdf.setPage(i);
            pdf.setFontSize(8);
            pdf.setTextColor(150, 150, 150);
            pdf.text(
                `Gerado por Sistema de Otimização de Rotas - Página ${i} de ${pageCount}`,
                105,
                pdf.internal.pageSize.height - 10,
                { align: 'center' }
            );
            pdf.setTextColor(0, 0, 0);
        }

        // Download
        const fileName = `rota-${block.name.replace(/[^a-zA-Z0-9]/g, '_')}-${new Date().toISOString().split('T')[0]}.pdf`;
        pdf.save(fileName);

        showNotification('PDF gerado com sucesso! 📄', 'success');

    } catch (error) {
        console.error('Erro ao gerar PDF:', error);
        showNotification(`Erro ao gerar PDF: ${error.message}`, 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Exportar rota para Excel
 */
async function exportRouteToExcel(blockId) {
    try {
        showLoading('Gerando Excel...');

        // Buscar dados do bloco
        const response = await fetch(`https://floripa.in9automacao.com.br/blocks-api.php?id=${blockId}`);
        const data = await response.json();

        if (!data.success || !data.block) {
            throw new Error('Bloco não encontrado');
        }

        const block = data.block;

        // Buscar locais do bloco
        const locationsResponse = await fetch(`https://floripa.in9automacao.com.br/locations-api.php?block_id=${blockId}`);
        const locationsData = await locationsResponse.json();

        if (!locationsData.success) {
            throw new Error('Erro ao carregar locais');
        }

        const locations = locationsData.locations || [];

        // Preparar dados para Excel
        const excelData = [
            ['Rota: ' + block.name],
            ['Data: ' + new Date().toLocaleDateString('pt-BR')],
            ['Total de Paradas: ' + locations.length],
            [],
            ['Sequência', 'Nome', 'Endereço', 'Latitude', 'Longitude', 'Observações'],
            ...locations.map((loc, idx) => [
                idx + 1,
                loc.name || '',
                loc.address || '',
                loc.latitude,
                loc.longitude,
                ''
            ])
        ];

        // Criar workbook
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(excelData);

        // Ajustar larguras das colunas
        ws['!cols'] = [
            { wch: 10 },  // Sequência
            { wch: 30 },  // Nome
            { wch: 50 },  // Endereço
            { wch: 12 },  // Latitude
            { wch: 12 },  // Longitude
            { wch: 30 }   // Observações
        ];

        // Estilizar células de cabeçalho (linha 5)
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const cellAddress = XLSX.utils.encode_cell({ r: 4, c: C });
            if (!ws[cellAddress]) continue;
            ws[cellAddress].s = {
                font: { bold: true },
                fill: { fgColor: { rgb: "CCCCCC" } }
            };
        }

        XLSX.utils.book_append_sheet(wb, ws, 'Rota');

        // Download
        const fileName = `rota-${block.name.replace(/[^a-zA-Z0-9]/g, '_')}-${new Date().toISOString().split('T')[0]}.xlsx`;
        XLSX.writeFile(wb, fileName);

        showNotification('Excel gerado com sucesso! 📊', 'success');

    } catch (error) {
        console.error('Erro ao gerar Excel:', error);
        showNotification(`Erro ao gerar Excel: ${error.message}`, 'error');
    } finally {
        hideLoading();
    }
}

/**
 * Mostrar opções de exportação
 */
function showExportOptions(blockId, blockName) {
    const modalHTML = `
        <div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target.id === 'exportModal') this.remove()">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined">download</span>
                        Exportar Rota
                    </h2>
                    <button onclick="document.getElementById('exportModal').remove()"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    <strong>${blockName}</strong>
                </div>

                <div class="space-y-3">
                    <button onclick="exportRouteToPDF(${blockId}); document.getElementById('exportModal').remove();"
                            class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                        <span>📄 Exportar como PDF</span>
                    </button>

                    <button onclick="exportRouteToExcel(${blockId}); document.getElementById('exportModal').remove();"
                            class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">table_chart</span>
                        <span>📊 Exportar como Excel</span>
                    </button>

                    <button onclick="Promise.all([exportRouteToPDF(${blockId}), exportRouteToExcel(${blockId})]); document.getElementById('exportModal').remove();"
                            class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">download</span>
                        <span>📦 Exportar Ambos</span>
                    </button>
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="document.getElementById('exportModal').remove()"
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// ========== FILTROS POR CIDADE ==========

/**
 * Extrair cidade de um endereço (OTIMIZADO)
 * Formato esperado: "Rua X, Bairro - Cidade - ES"
 */
let _extractCityCallCount = 0; // Contador para logar apenas algumas chamadas
function extractCityFromAddress(address) {
    const shouldLog = _extractCityCallCount < 3; // Logar apenas os 3 primeiros
    _extractCityCallCount++;

    if (!address) {
        if (shouldLog) console.log('⚠️ Address vazio ou null');
        return 'Não identificada';
    }

    // Busca rápida por traço (formato mais comum)
    const dashIndex = address.lastIndexOf(' - ');
    if (dashIndex > 0) {
        const secondDashIndex = address.lastIndexOf(' - ', dashIndex - 1);
        if (secondDashIndex > 0) {
            const city = address.substring(secondDashIndex + 3, dashIndex).trim();
            if (shouldLog) console.log(`📍 Extraído: "${city}" de "${address}"`);
            return city;
        }
    }

    if (shouldLog) console.log(`⚠️ Formato não reconhecido: "${address}"`);
    return 'Não identificada';
}

/**
 * Determinar cidade predominante de um bloco
 */
let _getBlockCityCallCount = 0; // Contador para logar apenas alguns blocos
function getBlockCity(block) {
    const shouldLog = _getBlockCityCallCount < 3; // Logar apenas os 3 primeiros
    _getBlockCityCallCount++;

    if (!block.locations || block.locations.length === 0) {
        if (shouldLog) console.log(`⚠️ Bloco ${block.name || block.id} sem locais`);
        return 'Não identificada';
    }

    if (shouldLog) console.log(`🔍 Processando bloco ${block.name || block.id} com ${block.locations.length} locais`);

    // Contar frequência de cada cidade
    const cityCounts = {};

    block.locations.forEach(loc => {
        const city = extractCityFromAddress(loc.address);
        cityCounts[city] = (cityCounts[city] || 0) + 1;
    });

    // Retornar cidade mais frequente
    let maxCount = 0;
    let predominantCity = 'Não identificada';

    for (const [city, count] of Object.entries(cityCounts)) {
        if (count > maxCount) {
            maxCount = count;
            predominantCity = city;
        }
    }

    if (shouldLog) console.log(`✅ Bloco ${block.name || block.id} → Cidade: ${predominantCity}`, cityCounts);
    return predominantCity;
}

/**
 * Coletar todas as cidades únicas dos blocos
 */
function getAllCities(blocks) {
    console.log('🔄 getAllCities() iniciado com', blocks.length, 'blocos');
    const cities = new Set();

    blocks.forEach(block => {
        const city = getBlockCity(block);
        if (city !== 'Não identificada') {
            cities.add(city);
        }
    });

    const citiesArray = Array.from(cities).sort();
    console.log('✅ getAllCities() retornou', citiesArray.length, 'cidades:', citiesArray);
    return citiesArray;
}

/**
 * Criar filtros dinâmicos de cidade
 */
function createCityFilters(cities) {
    console.log('🎨 createCityFilters() iniciado com', cities.length, 'cidades:', cities);

    const filterContainer = document.getElementById('city-filters-container');
    console.log('📦 Container encontrado:', !!filterContainer, filterContainer);

    if (!filterContainer) {
        console.error('❌ Container de filtros de cidade não encontrado no DOM!');
        return;
    }

    // Limpar filtros existentes
    filterContainer.innerHTML = '';
    console.log('🧹 Container limpo');

    if (cities.length === 0) {
        filterContainer.innerHTML = '<span class="text-xs text-gray-500">Nenhuma cidade identificada</span>';
        console.log('⚠️ Nenhuma cidade para mostrar - exibindo mensagem');
        return;
    }

    // Adicionar filtro para cada cidade
    cities.forEach(city => {
        const button = document.createElement('button');
        button.className = 'city-filter-chip px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-xs font-medium hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors';
        button.textContent = `📍 ${city}`;
        button.title = `Filtrar blocos em ${city}`;
        button.onclick = () => filterBlocksByCity(city);

        filterContainer.appendChild(button);
        console.log(`✅ Botão criado para: ${city}`);
    });

    // Adicionar botão "Limpar"
    const clearButton = document.createElement('button');
    clearButton.className = 'city-filter-chip px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors';
    clearButton.innerHTML = '✕ Limpar';
    clearButton.title = 'Limpar filtro de cidade';
    clearButton.onclick = () => filterBlocksByCity('');

    filterContainer.appendChild(clearButton);
    console.log('✅ Botão "Limpar" adicionado');

    console.log(`✅ ${cities.length} filtros de cidade criados:`, cities.join(', '));
    console.log('📦 Container HTML final:', filterContainer.innerHTML.substring(0, 200) + '...');
}

/**
 * Filtrar blocos por cidade
 */
function filterBlocksByCity(city) {
    const blockCards = document.querySelectorAll('[data-block-id]');
    let visibleCount = 0;

    blockCards.forEach(card => {
        const blockId = parseInt(card.getAttribute('data-block-id'));
        const block = blocks.find(b => b.id === blockId);

        if (!block) {
            card.style.display = 'none';
            return;
        }

        const blockCity = getBlockCity(block);

        if (!city || blockCity === city) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Atualizar chips visuais (marcar o ativo)
    document.querySelectorAll('.city-filter-chip').forEach(chip => {
        if (city && chip.textContent.includes(city)) {
            chip.classList.add('ring-2', 'ring-indigo-500');
        } else {
            chip.classList.remove('ring-2', 'ring-indigo-500');
        }
    });

    console.log(`🔍 Filtro por cidade: ${city || 'Todos'} - ${visibleCount} blocos visíveis`);
}

// ========== DASHBOARD DE MÉTRICAS ==========

let metricsChart = null; // Variável global para o gráfico

/**
 * Carregar métricas do dashboard
 */
async function loadMetrics() {
    try {
        // Buscar métricas gerais
        const response = await fetch('https://floripa.in9automacao.com.br/metrics-api.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar métricas');
        }

        const metrics = data.metrics;

        // Atualizar cards
        document.getElementById('metric-total-routes').textContent = metrics.total_routes;
        document.getElementById('metric-total-distance').textContent = `${metrics.total_distance} km`;
        document.getElementById('metric-avg-stops').textContent = metrics.avg_stops;
        document.getElementById('metric-active-blocks').textContent = metrics.active_blocks;

        // Buscar histórico para o gráfico
        await loadMetricsChart();

        console.log('✅ Métricas carregadas com sucesso');

    } catch (error) {
        console.error('Erro ao carregar métricas:', error);
        // Mostrar valores padrão em caso de erro
        document.getElementById('metric-total-routes').textContent = '0';
        document.getElementById('metric-total-distance').textContent = '0 km';
        document.getElementById('metric-avg-stops').textContent = '0';
        document.getElementById('metric-active-blocks').textContent = '0';
    }
}

/**
 * Carregar e renderizar gráfico de métricas
 */
async function loadMetricsChart() {
    try {
        const response = await fetch('https://floripa.in9automacao.com.br/metrics-api.php?period=6months');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar histórico');
        }

        const history = data.history;

        // Preparar dados para o gráfico
        const labels = history.map(h => h.label);
        const routesData = history.map(h => h.total_routes);
        const distanceData = history.map(h => h.total_distance);

        // Renderizar gráfico
        renderMetricsChart(labels, routesData, distanceData);

    } catch (error) {
        console.error('Erro ao carregar gráfico:', error);
    }
}

/**
 * Renderizar gráfico de métricas com Chart.js
 */
function renderMetricsChart(labels, routesData, distanceData) {
    const ctx = document.getElementById('metrics-chart');

    if (!ctx) {
        console.warn('Canvas metrics-chart não encontrado');
        return;
    }

    // Destruir gráfico anterior se existir
    if (metricsChart) {
        metricsChart.destroy();
    }

    // Criar novo gráfico
    metricsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Rotas Otimizadas',
                    data: routesData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Distância Total (km)',
                    data: distanceData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 1) {
                                label += context.parsed.y.toFixed(2) + ' km';
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Rotas Otimizadas'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Distância (km)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

window.centerMapOnBlock = centerMapOnBlock;
window.centerMapOnLocation = centerMapOnLocation;
window.generateBlockRoute = generateBlockRoute;
window.filterBlocks = filterBlocks;
window.renameBlock = renameBlock;
window.viewSavedMap = viewSavedMap;
window.viewRouteHistory = viewRouteHistory;
window.viewHistoricalRouteDetails = viewHistoricalRouteDetails;
window.exportRouteToPDF = exportRouteToPDF;
window.exportRouteToExcel = exportRouteToExcel;
window.showExportOptions = showExportOptions;
window.loadMetrics = loadMetrics;

/**
 * Mostrar modal de ajuda/glossário
 */
function showHelpModal() {
    const modal = document.getElementById('helpModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

window.showHelpModal = showHelpModal;

console.log('✅ Otimizador de Blocos carregado - v20251210160831');
console.log('🔍 DEBUG MODE ATIVO - Verificando distâncias dos blocos');
