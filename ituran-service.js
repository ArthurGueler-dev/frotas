// Serviço de Integração com Ituran (SOAP/XML API)
// API Real: iweb.ituran.com.br

class IturanService {
    constructor() {
        // Configurações da API
        // Se estiver no navegador (typeof window !== 'undefined'), usa o proxy do servidor
        // Se estiver no Node.js (servidor), acessa direto localhost:8888
        const isNode = typeof window === 'undefined';

        this.config = {
            // Navegador: via proxy do servidor Node.js (evita CORS)
            // Node.js: acesso direto ao proxy Ituran
            apiUrl: isNode
                ? 'http://localhost:8888/api/ituran'  // Node.js: acesso direto
                : 'http://localhost:5000/api/proxy/ituran',  // Navegador: via servidor
            username: 'api@i9tecnologia',
            password: 'Api@In9Eng',
            timeout: 120000 // 120 segundos (2 minutos - API pode demorar com 80+ veículos)
        };

        console.log(`🔧 IturanService inicializado em: ${isNode ? 'Node.js' : 'Navegador'}`);
        console.log(`🔗 API URL: ${this.config.apiUrl}`);

        // Cache para otimizar requisições
        this.cache = new Map();
        this.cacheTimeout = 5000; // 5 segundos (tempo real)

        // Estado do proxy
        this.proxyStatus = null;

        // Mapa de modelos de veículos (placa -> modelo)
        this.vehicleModels = {};
        this.vehicleModelsLoaded = false;

        // Carrega os modelos automaticamente
        this.loadVehicleModels();
    }

    /**
     * Carrega o arquivo de mapeamento de modelos de veículos
     */
    async loadVehicleModels() {
        try {
            const response = await fetch('vehicle-models.json');
            if (response.ok) {
                this.vehicleModels = await response.json();
                this.vehicleModelsLoaded = true;
                console.log(`✅ Modelos de veículos carregados: ${Object.keys(this.vehicleModels).length} veículos`);
            } else {
                console.warn('⚠️ Arquivo vehicle-models.json não encontrado. Usando fallback.');
            }
        } catch (error) {
            console.warn('⚠️ Erro ao carregar vehicle-models.json:', error.message);
        }
    }

    /**
     * Obtém o modelo do veículo pela placa
     * @param {string} plate - Placa do veículo
     * @returns {string|null} Modelo do veículo ou null se não encontrado
     */
    getVehicleModel(plate) {
        return this.vehicleModels[plate] || null;
    }

    /**
     * Verifica se o proxy está rodando (ping rápido)
     */
    async checkProxyStatus() {
        try {
            // Faz um HEAD request ou um GET com abort rápido (500ms)
            // Isso só verifica se o proxy está respondendo, não carrega dados
            const response = await fetch('http://localhost:8888/api/ituran/health', {
                method: 'GET',
                signal: AbortSignal.timeout(1000) // 1 segundo é suficiente
            });
            this.proxyStatus = true;
            console.log(`✅ Proxy está respondendo`);
            return true;
        } catch (error) {
            // Se falhar, assume que pelo menos a porta está aberta
            // e deixa a requisição real acontecer
            console.warn(`⚠️ Health check rápido falhou, mas tentando requisição real mesmo assim`);
            return true; // Retorna true para tentar a requisição real
        }
    }

    /**
     * Método auxiliar para fazer requisições à API do Ituran
     * @private
     */
    async _makeRequest(endpoint, params = {}) {
        // Adiciona credenciais aos parâmetros
        const fullParams = {
            UserName: this.config.username,
            Password: this.config.password,
            ...params
        };

        // Constrói URL com query params
        const queryString = new URLSearchParams(fullParams).toString();
        const url = `${this.config.apiUrl}${endpoint}?${queryString}`;

        console.log(`📡 [_makeRequest] URL: ${url.substring(0, 100)}...`);
        console.log(`📡 [_makeRequest] Parâmetros:`, fullParams);

        try {
            console.log(`📡 [_makeRequest] Iniciando fetch com timeout de ${this.config.timeout}ms...`);
            console.log(`⏳ Aguarde... A API Ituran pode demorar até 60 segundos para responder com 80 veículos`);

            // Indicador de progresso a cada 10 segundos
            let progressInterval = setInterval(() => {
                console.log(`⏳ Ainda aguardando resposta da API Ituran...`);
            }, 10000);

            // Faz a requisição sem Promise.race (deixa o fetch nativo lidar com timeout)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/xml, text/xml, */*',
                    'Cache-Control': 'no-cache'
                },
                cache: 'no-store',
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // Limpa o indicador de progresso
            clearInterval(progressInterval);

            console.log(`📡 [_makeRequest] Status HTTP: ${response.status}`);

            if (!response.ok) {
                const errorText = await response.text();
                console.error(`❌ Erro na API Ituran: ${response.status} - ${response.statusText}`);
                console.error(`❌ Resposta: ${errorText.substring(0, 200)}`);
                throw new Error(`Erro na API Ituran: ${response.status} - ${response.statusText} - ${errorText}`);
            }

            // Converte XML para texto
            const xmlText = await response.text();
            console.log(`📡 [_makeRequest] Recebidos ${xmlText.length} bytes`);

            // Parse básico do XML
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlText, 'text/xml');

            console.log(`✅ [_makeRequest] Resposta recebida e parseada com sucesso`);
            return xmlDoc;
        } catch (error) {
            console.error('❌ Erro ao fazer requisição para Ituran:', error.message);
            throw error;
        }
    }

    /**
     * Helper para extrair texto de um elemento XML
     */
    _getXmlValue(xmlDoc, tagName, defaultValue = '') {
        const element = xmlDoc.getElementsByTagName(tagName)[0];
        return element ? element.textContent : defaultValue;
    }

    /**
     * Obtém lista de todos os veículos
     * @returns {Promise<Array>} Lista de veículos
     */
    async getVehiclesList() {
        try {
            console.log('🔄 Buscando veículos do Ituran (iweb.ituran.com.br)...');

            // Verifica se o proxy está rodando (com timeout curto)
            const proxyRunning = await Promise.race([
                this.checkProxyStatus(),
                new Promise(resolve => setTimeout(() => resolve(false), 3000))
            ]);

            if (!proxyRunning) {
                console.warn('⚠️ Proxy não respondeu a tempo. Tentando requisição direta mesmo assim...');
                // Continua mesmo assim - pode funcionar
            }

            const xmlDoc = await this._makeRequest('/ituranwebservice3/Service3.asmx/GetAllPlatformsData', {
                ShowAreas: 'true',
                ShowStatuses: 'true',
                ShowMileageInMeters: 'true',  // true = API retorna em METROS
                ShowDriver: 'true'
            });

            // Verifica ReturnCode
            const returnCode = this._getXmlValue(xmlDoc, 'ReturnCode');
            if (returnCode !== 'OK') {
                throw new Error(`API retornou erro: ${returnCode}`);
            }

            // Parse dos veículos
            const vehicles = [];
            const platformsData = xmlDoc.getElementsByTagName('ServicePlatformData');

            console.log(`📦 Encontrados ${platformsData.length} elementos ServicePlatformData`);

            for (let i = 0; i < platformsData.length; i++) {
                const platform = platformsData[i];

                // DEBUG: mostra TODOS os campos do primeiro veículo
                if (i === 0) {
                    console.log(`🔍 DEBUG - Listando TODOS os campos do primeiro veículo:`);
                    const allElements = platform.getElementsByTagName('*');
                    for (let j = 0; j < Math.min(allElements.length, 30); j++) {
                        const elem = allElements[j];
                        if (elem.childNodes.length === 1 && elem.childNodes[0].nodeType === 3) {
                            console.log(`   ${elem.tagName}: "${elem.textContent}"`);
                        }
                    }
                }

                const plate = this._getXmlValue(platform, 'Plate');
                const platformId = this._getXmlValue(platform, 'PlatfromId'); // Note: API usa "PlatfromId" (typo da API)

                // PRIORIDADE 1: Busca modelo no arquivo vehicle-models.json
                let platformName = this.getVehicleModel(plate);

                // PRIORIDADE 2: Tenta pegar das áreas (primeiro string dentro de areas)
                if (!platformName) {
                    const areasElements = platform.getElementsByTagName('areas')[0];
                    if (areasElements) {
                        const firstArea = areasElements.getElementsByTagName('string')[0];
                        if (firstArea && firstArea.textContent) {
                            platformName = firstArea.textContent.trim();
                        }
                    }
                }

                // PRIORIDADE 3: Fallback - tenta múltiplos campos da API
                if (!platformName) {
                    platformName = this._getXmlValue(platform, 'PlatformName') ||
                                  this._getXmlValue(platform, 'PlatfromName') ||
                                  this._getXmlValue(platform, 'Description') ||
                                  this._getXmlValue(platform, 'Name') ||
                                  this._getXmlValue(platform, 'VehicleName');
                }

                const vin = this._getXmlValue(platform, 'VIN');
                const lastMilieage = this._getXmlValue(platform, 'LastMilieage'); // Note: API usa "LastMilieage" (typo da API)

                // CORREÇÃO: Detecta automaticamente se está em metros ou KM
                // Se ShowMileageInMeters=true: vem em metros (ex: 57345100 = 57345 km)
                // Se valor >= 1.000.000, está em metros
                const odometerValue = parseInt(lastMilieage) || 0;
                const odometerInKm = odometerValue >= 1000000
                    ? Math.floor(odometerValue / 1000)  // Metros → KM
                    : Math.floor(odometerValue);        // Já está em KM

                // Usa o nome configurado no Ituran, ou fallback para placa
                let displayName = platformName || plate;
                let brand = 'Veículo';
                let year = new Date().getFullYear();

                // Tenta extrair marca do nome (ex: "ÁGUA 04 - Strada RTG1G68" -> "Fiat")
                if (platformName) {
                    const nameLower = platformName.toLowerCase();
                    if (nameLower.includes('strada') || nameLower.includes('toro')) brand = 'Fiat';
                    else if (nameLower.includes('saveiro') || nameLower.includes('gol')) brand = 'Volkswagen';
                    else if (nameLower.includes('l200') || nameLower.includes('triton')) brand = 'Mitsubishi';
                    else if (nameLower.includes('ranger')) brand = 'Ford';
                    else if (nameLower.includes('hb20')) brand = 'Hyundai';
                    else if (nameLower.includes('kwid') || nameLower.includes('duster')) brand = 'Renault';
                }

                // Tenta extrair ano do VIN (10º caractere indica o ano)
                if (vin && vin.length >= 10) {
                    const yearCode = vin.charAt(9);
                    const yearMap = {
                        'M': 2021, 'N': 2022, 'P': 2023, 'R': 2024, 'S': 2025,
                        'J': 2018, 'K': 2019, 'L': 2020
                    };
                    year = yearMap[yearCode] || year;
                }

                const vehicle = {
                    id: platformId || plate,
                    plate: plate,
                    model: displayName,  // Usa o nome do Ituran diretamente
                    brand: brand,
                    year: year,
                    status: 'active',
                    odometer: odometerInKm,
                    lastUpdate: this._getXmlValue(platform, 'LastLocationTime') || new Date().toISOString(),
                    // Dados extras para detalhes
                    vin: vin,
                    platformName: platformName,
                    latitude: parseFloat(this._getXmlValue(platform, 'LastLatitude')) || 0,
                    longitude: parseFloat(this._getXmlValue(platform, 'LastLongitude')) || 0,
                    address: this._getXmlValue(platform, 'LastAddress') || 'Endereço não disponível',
                    speed: parseInt(this._getXmlValue(platform, 'LastSpeed')) || 0
                };

                vehicles.push(vehicle);
            }

            console.log(`✅ ${vehicles.length} veículos encontrados no Ituran!`);
            return vehicles;
        } catch (error) {
            console.error('❌ Erro ao buscar lista de veículos do Ituran:', error);

            // Retorna fallback com dados vazios em vez de lançar erro
            console.log('⚠️ Retornando lista vazia. Verifique se o proxy está rodando em http://localhost:8888');
            return [];
        }
    }

    /**
     * Obtém localização de um veículo específico
     * @param {string} plateOrId - Placa ou PlatformId do veículo
     * @returns {Promise<Object>} Dados de localização
     */
    async getVehicleLocation(plateOrId, forceRefresh = false) {
        const cacheKey = `location_${plateOrId}`;

        // Verifica cache (ignora se forceRefresh = true)
        if (!forceRefresh && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTimeout) {
                console.log(`📦 Localização do cache (${Math.round((Date.now() - cached.timestamp) / 1000)}s atrás)`);
                return cached.data;
            }
        }

        try {
            // Usa GetPlatformData que retorna localização também
            const xmlDoc = await this._makeRequest('/ituranwebservice3/Service3.asmx/GetPlatformData', {
                Plate: plateOrId,
                ShowAreas: 'true',
                ShowStatuses: 'true'
            });

            const platformData = xmlDoc.getElementsByTagName('ServicePlatformData')[0];

            if (!platformData) {
                throw new Error('Veículo não encontrado');
            }

            const location = {
                vehicleId: plateOrId,
                platformId: parseInt(this._getXmlValue(platformData, 'PlatfromId')),
                latitude: parseFloat(this._getXmlValue(platformData, 'LastLatitude')),
                longitude: parseFloat(this._getXmlValue(platformData, 'LastLongitude')),
                address: this._getXmlValue(platformData, 'LastAddress'),
                speed: parseInt(this._getXmlValue(platformData, 'LastSpeed')) || 0,
                heading: parseInt(this._getXmlValue(platformData, 'LastHead')) || 0,
                timestamp: this._getXmlValue(platformData, 'LastLocationTime') || new Date().toISOString(),
                age: ''
            };

            // Armazena no cache
            this.cache.set(cacheKey, {
                data: location,
                timestamp: Date.now()
            });

            return location;
        } catch (error) {
            console.error(`Erro ao obter localização do veículo ${plateOrId}:`, error);
            return this._getMockLocation(plateOrId);
        }
    }

    /**
     * Obtém telemetria do veículo (KM, etc)
     * @param {string} plate - Placa do veículo
     * @returns {Promise<Object>} Dados de telemetria
     */
    async getVehicleTelemetry(plate) {
        try {
            const xmlDoc = await this._makeRequest('/ituranwebservice3/Service3.asmx/GetPlatformData', {
                Plate: plate,
                ShowAreas: 'true',
                ShowStatuses: 'true'
            });

            // DEBUG EXTREMO: Mostra XML COMPLETO da primeira chamada
            if (!this._telemetryDebugShown) {
                this._telemetryDebugShown = true;
                const serializer = new XMLSerializer();
                const xmlString = serializer.serializeToString(xmlDoc);
                console.log(`📄 ========== XML COMPLETO GetPlatformData (primeiros 3000 chars) ==========`);
                console.log(xmlString.substring(0, 3000));
                console.log(`📄 ========== FIM XML ==========`);
            }

            // Busca o elemento ServicePlatformData
            const platformData = xmlDoc.getElementsByTagName('ServicePlatformData')[0];

            if (!platformData) {
                throw new Error('Dados não encontrados');
            }

            const lastMilieage = this._getXmlValue(platformData, 'LastMilieage');
            const odometerValue = parseFloat(lastMilieage) || 0;

            // GetPlatformData retorna em KM (ex: 57345.1), não em metros!
            // Só converte se o valor estiver claramente em metros (> 1000000 ou número inteiro grande)
            const odometerInKm = odometerValue >= 1000000
                ? Math.floor(odometerValue / 1000)
                : Math.floor(odometerValue);

            // PRIORIDADE 1: Busca modelo no arquivo vehicle-models.json
            let platformName = this.getVehicleModel(plate);

            // PRIORIDADE 2: Se não encontrou, pega das áreas (primeiro string)
            if (!platformName) {
                const areasElements = platformData.getElementsByTagName('areas')[0];
                if (areasElements) {
                    const firstArea = areasElements.getElementsByTagName('string')[0];
                    if (firstArea && firstArea.textContent) {
                        platformName = firstArea.textContent.trim();
                    }
                }
            }

            // PRIORIDADE 3: Fallback - tenta campos diretos (com typo da API!)
            if (!platformName) {
                platformName = this._getXmlValue(platformData, 'PlatfromName') ||
                              this._getXmlValue(platformData, 'PlatformName') ||
                              this._getXmlValue(platformData, 'Name');
            }

            console.log(`🔍 TELEMETRY - Placa: ${plate}, Modelo: "${platformName}", KM: ${odometerInKm}`);

            // Verifica status do motor através dos status
            const statusesElements = platformData.getElementsByTagName('string');
            let engineStatus = 'off';

            for (let i = 0; i < statusesElements.length; i++) {
                const status = statusesElements[i].textContent;
                if (status.includes('Motor Ligado') || status.includes('Motor ligado')) {
                    engineStatus = 'on';
                    break;
                } else if (status.includes('Motor desligado') || status.includes('Motor Desligado')) {
                    engineStatus = 'off';
                }
            }

            return {
                odometer: odometerInKm,
                currentSpeed: parseInt(this._getXmlValue(platformData, 'LastSpeed')) || 0,
                fuelLevel: 0, // API Ituran não fornece nível de combustível
                engineStatus: engineStatus,
                lastUpdate: this._getXmlValue(platformData, 'LastLocationTime') || new Date().toISOString(),
                platformName: platformName // Retorna o nome do veículo
            };
        } catch (error) {
            console.error(`Erro ao obter telemetria do veículo ${plate}:`, error);
            return this._getMockTelemetry(plate);
        }
    }

    /**
     * Obtém status de manutenção baseado na quilometragem real
     * @param {string} plateOrId - Placa ou ID do veículo
     * @param {number} currentOdometer - Quilometragem atual em KM
     * @returns {Promise<Object>} Status de manutenção
     */
    async getMaintenanceStatus(plateOrId, currentOdometer) {
        // Se não tiver odômetro, usa mock
        if (!currentOdometer || currentOdometer === 0) {
            return this._getMockMaintenanceStatus(plateOrId);
        }

        // Calcula próxima manutenção (a cada 10.000 km)
        const maintenanceInterval = 10000;
        const nextMaintenance = Math.ceil(currentOdometer / maintenanceInterval) * maintenanceInterval;
        const kmUntilMaintenance = nextMaintenance - currentOdometer;
        const lastMaintenance = nextMaintenance - maintenanceInterval;

        let status = 'ok';
        let alerts = [];

        // Define status e alertas baseado na proximidade da manutenção
        if (kmUntilMaintenance < 500) {
            status = 'critical';
            alerts.push({
                type: 'maintenance',
                severity: 'high',
                message: `⚠️ URGENTE: Manutenção obrigatória em ${kmUntilMaintenance.toLocaleString('pt-BR')} km`
            });
        } else if (kmUntilMaintenance < 1500) {
            status = 'warning';
            alerts.push({
                type: 'maintenance',
                severity: 'medium',
                message: `⚡ ATENÇÃO: Manutenção programada em ${kmUntilMaintenance.toLocaleString('pt-BR')} km`
            });
        }

        return {
            nextMaintenance,
            lastMaintenance,
            kmUntilMaintenance,
            alerts,
            status
        };
    }

    /**
     * Obtém histórico de rota
     * @param {string} plate - Placa do veículo
     * @param {Object} options - Opções (startDate, endDate)
     * @returns {Promise<Array>} Pontos da rota
     */
    async getVehicleRoute(plate, options = {}) {
        try {
            const { startDate, endDate } = options;
            // Aumenta para 24 horas para garantir que há dados
            const start = startDate || new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString();
            const end = endDate || new Date().toISOString();

            const startFormatted = start.replace('T', ' ').substring(0, 19);
            const endFormatted = end.replace('T', ' ').substring(0, 19);

            console.log(`🔄 Buscando rota da placa ${plate}`);
            console.log(`   Período: ${startFormatted} até ${endFormatted}`);

            const xmlDoc = await this._makeRequest('/ituranwebservice3/Service3.asmx/GetFullReport', {
                Plate: plate,
                Start: startFormatted,
                End: endFormatted,
                UAID: '0',
                MaxNumberOfRecords: '1000'
            });

            // DEBUG: Mostra XML completo da resposta
            const serializer = new XMLSerializer();
            const xmlString = serializer.serializeToString(xmlDoc);
            console.log(`📄 XML Resposta GetFullReport (primeiros 2000 chars):`);
            console.log(xmlString.substring(0, 2000));

            // Verifica ReturnCode
            const returnCode = this._getXmlValue(xmlDoc, 'ReturnCode');
            console.log(`📡 API GetFullReport ReturnCode: ${returnCode}`);

            if (returnCode && returnCode !== 'OK') {
                console.warn(`⚠️ API retornou status: ${returnCode}`);
            }

            // GetFullReport retorna <RecordWithPlate> dentro de <Records>
            let records = xmlDoc.getElementsByTagName('RecordWithPlate');
            console.log(`📍 Elementos <RecordWithPlate> encontrados: ${records.length}`);

            if (records.length === 0) {
                // Tenta outras possíveis tags
                records = xmlDoc.getElementsByTagName('Record');
                console.log(`📍 Tentando <Record>: ${records.length} elementos`);
            }

            if (records.length === 0) {
                records = xmlDoc.getElementsByTagName('ServiceRecord');
                console.log(`📍 Tentando <ServiceRecord>: ${records.length} elementos`);
            }

            const route = [];

            for (let i = 0; i < records.length; i++) {
                const record = records[i];

                // GetFullReport usa <Lat>, <Lon>, <Date>, <Speed>
                let lat = parseFloat(this._getXmlValue(record, 'Lat'));
                let lon = parseFloat(this._getXmlValue(record, 'Lon'));
                let timestamp = this._getXmlValue(record, 'Date') || this._getXmlValue(record, 'LocTime');
                let speed = parseInt(this._getXmlValue(record, 'Speed')) || 0;

                // DEBUG: Log das primeiras 3 coordenadas
                if (i < 3) {
                    console.log(`   Ponto ${i}: Lat=${lat}, Lon=${lon}, Speed=${speed}, Time=${timestamp}`);
                }

                // Valida coordenadas antes de adicionar
                if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                    route.push({
                        latitude: lat,
                        longitude: lon,
                        timestamp: timestamp,
                        speed: speed
                    });
                }
            }

            console.log(`✅ ${route.length} pontos válidos na rota`);

            if (route.length === 0) {
                console.warn(`⚠️ NENHUM ponto válido encontrado! Veja o XML acima para diagnosticar.`);
            }

            return route;
        } catch (error) {
            console.error(`❌ Erro ao obter rota do veículo ${plate}:`, error);
            // NÃO retorna mock - deixa o erro visível
            throw error;
        }
    }

    /**
     * Obtém relatório completo de GPS (todos os pontos)
     * @param {string} plate - Placa do veículo
     * @param {string} startDate - Data inicial (ISO string)
     * @param {string} endDate - Data final (ISO string)
     * @returns {Promise<Object>} Relatório com array de pontos GPS
     */
    async getFullReport(plate, startDate, endDate) {
        try {
            console.log(`📍 GetFullReport - Buscando trajeto para ${plate}...`);
            console.log(`   Período: ${startDate} até ${endDate}`);

            // API Ituran limita relatórios a 3 dias. Divide em chunks
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

            console.log(`   Total de dias: ${diffDays}`);

            if (diffDays > 3) {
                console.log(`⚠️ Período maior que 3 dias. Dividindo em múltiplas requisições...`);
            }

            // Divide em chunks de 2.5 dias (60 horas) para ficar BEM ABAIXO de 3 dias
            const chunks = [];
            let currentStart = new Date(start);

            while (currentStart < end) {
                let currentEnd = new Date(currentStart);
                // Adiciona 2 dias e 12 horas (60 horas = 2.5 dias)
                currentEnd.setHours(currentEnd.getHours() + 60);

                if (currentEnd > end) {
                    currentEnd = new Date(end);
                }

                chunks.push({
                    start: currentStart.toISOString(),
                    end: currentEnd.toISOString()
                });

                currentStart = new Date(currentEnd);
                currentStart.setSeconds(currentStart.getSeconds() + 1); // Evita overlap
            }

            console.log(`   Dividido em ${chunks.length} requisições`);

            // Busca dados de todos os chunks
            let allPoints = [];

            for (let i = 0; i < chunks.length; i++) {
                const chunk = chunks[i];
                console.log(`   📡 Buscando chunk ${i + 1}/${chunks.length}...`);

                const startFormatted = chunk.start.replace('T', ' ').substring(0, 19);
                const endFormatted = chunk.end.replace('T', ' ').substring(0, 19);

                const xmlDoc = await this._makeRequest('/ituranwebservice3/Service3.asmx/GetFullReport', {
                    Plate: plate,
                    Start: startFormatted,
                    End: endFormatted,
                    UAID: '0',
                    MaxNumberOfRecords: '5000'
                });

                // Verifica ReturnCode
                const returnCode = this._getXmlValue(xmlDoc, 'ReturnCode');
                console.log(`📡 API GetFullReport ReturnCode: ${returnCode}`);

                if (returnCode && returnCode !== 'OK') {
                    console.log(`⚠️ Chunk ${i + 1} retornou erro: ${returnCode}`);
                    continue; // Pula este chunk mas continua com os outros
                }

                // GetFullReport retorna <RecordWithPlate> dentro de <Records>
                const records = xmlDoc.getElementsByTagName('RecordWithPlate');
                console.log(`📍 Chunk ${i + 1}: ${records.length} registros GPS`);

                // Converte para array de pontos
                for (let j = 0; j < records.length; j++) {
                    const record = records[j];

                    const lat = parseFloat(this._getXmlValue(record, 'Lat'));
                    const lon = parseFloat(this._getXmlValue(record, 'Lon'));
                    const timestamp = this._getXmlValue(record, 'Date');
                    const speed = parseInt(this._getXmlValue(record, 'Speed')) || 0;
                    // CORREÇÃO: Detecta automaticamente se está em metros ou KM
                    const rawOdometer = parseFloat(this._getXmlValue(record, 'Mileage')) || 0;
                    const odometer = rawOdometer >= 1000000
                        ? Math.floor(rawOdometer / 1000)  // Metros → KM
                        : Math.floor(rawOdometer);        // Já está em KM

                    // Valida coordenadas - REMOVE pontos com (0,0) e coordenadas muito distantes
                    if (!isNaN(lat) && !isNaN(lon) &&
                        lat !== 0 && lon !== 0 &&
                        Math.abs(lat) > 0.01 && Math.abs(lon) > 0.01) { // Coordenadas mínimas válidas
                        allPoints.push({
                            latitude: lat,
                            longitude: lon,
                            timestamp: timestamp,
                            speed: speed,
                            odometer: Math.round(odometer)
                        });
                    }
                }
            }

            if (allPoints.length === 0) {
                return {
                    success: false,
                    message: 'Nenhum registro encontrado para este período',
                    records: []
                };
            }

            // Ordena por timestamp para garantir ordem cronológica
            allPoints.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));

            console.log(`✅ GetFullReport - ${allPoints.length} pontos GPS válidos retornados (de ${chunks.length} requisições)`);

            return {
                success: true,
                plate: plate,
                startDate: startDate.replace('T', ' ').substring(0, 19),
                endDate: endDate.replace('T', ' ').substring(0, 19),
                records: allPoints,
                totalRecords: allPoints.length
            };

        } catch (error) {
            console.error(`❌ Erro no GetFullReport:`, error);
            return {
                success: false,
                message: error.message,
                records: []
            };
        }
    }

    /**
     * Obtém relatório de quilometragem por período
     * @param {string} plate - Placa do veículo
     * @param {string} startDate - Data inicial (ISO string)
     * @param {string} endDate - Data final (ISO string)
     * @returns {Promise<Object>} Relatório com km rodados
     */
    async getKilometerReport(plate, startDate, endDate) {
        try {
            console.log(`📊 Gerando relatório de KM - ${plate}`);
            console.log(`   Período: ${startDate} até ${endDate}`);

            // API Ituran limita relatórios a 3 dias. Divide em chunks
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

            console.log(`   Total de dias: ${diffDays}`);

            if (diffDays > 3) {
                console.log(`⚠️ Período maior que 3 dias. Dividindo em múltiplas requisições...`);
            }

            // Divide em chunks de 2.5 dias (60 horas) para ficar BEM ABAIXO de 3 dias
            const chunks = [];
            let currentStart = new Date(start);

            while (currentStart < end) {
                let currentEnd = new Date(currentStart);
                // Adiciona 2 dias e 12 horas (60 horas = 2.5 dias)
                currentEnd.setHours(currentEnd.getHours() + 60);

                if (currentEnd > end) {
                    currentEnd = new Date(end);
                }

                chunks.push({
                    start: currentStart.toISOString(),
                    end: currentEnd.toISOString()
                });

                currentStart = new Date(currentEnd);
                currentStart.setSeconds(currentStart.getSeconds() + 1); // Evita overlap
            }

            console.log(`   Dividido em ${chunks.length} requisições de 3 dias`);

            // Busca dados de todos os chunks
            let allRecords = [];
            let allRoutePoints = [];

            for (let i = 0; i < chunks.length; i++) {
                const chunk = chunks[i];
                console.log(`   📡 Buscando chunk ${i + 1}/${chunks.length}...`);

                const startFormatted = chunk.start.replace('T', ' ').substring(0, 19);
                const endFormatted = chunk.end.replace('T', ' ').substring(0, 19);

                try {
                    const xmlDoc = await this._makeRequest('/ituranwebservice3/Service3.asmx/GetFullReport', {
                        Plate: plate,
                        Start: startFormatted,
                        End: endFormatted,
                        UAID: '0',
                        MaxNumberOfRecords: '5000'
                    });

                    const records = xmlDoc.getElementsByTagName('RecordWithPlate');

                    if (records.length > 0) {
                        // Converte HTMLCollection para Array e adiciona
                        for (let j = 0; j < records.length; j++) {
                            allRecords.push(records[j]);

                            // Adiciona ao array de rota também
                            const lat = parseFloat(this._getXmlValue(records[j], 'Lat'));
                            const lon = parseFloat(this._getXmlValue(records[j], 'Lon'));
                            if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
                                allRoutePoints.push({
                                    latitude: lat,
                                    longitude: lon,
                                    timestamp: this._getXmlValue(records[j], 'Date'),
                                    speed: parseInt(this._getXmlValue(records[j], 'Speed')) || 0
                                });
                            }
                        }
                        console.log(`      ✅ ${records.length} registros obtidos`);
                    }
                } catch (chunkError) {
                    console.warn(`      ⚠️ Erro no chunk ${i + 1}:`, chunkError.message);
                }

                // Aguarda 500ms entre requisições para não sobrecarregar
                if (i < chunks.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 500));
                }
            }

            if (allRecords.length === 0) {
                return {
                    success: false,
                    message: 'Nenhum registro encontrado para o período selecionado',
                    kmDriven: 0,
                    startOdometer: 0,
                    endOdometer: 0,
                    routePoints: 0
                };
            }

            // Pega primeiro e último registro (API retorna em ordem cronológica)
            const firstRecord = allRecords[0];
            const lastRecord = allRecords[allRecords.length - 1];

            // CORREÇÃO: Mileage pode vir em METROS ou KM dependendo da API
            // Se valor > 1.000.000, está em METROS e precisa converter
            // Se valor < 1.000.000, já está em KM
            const rawStartMileage = parseFloat(this._getXmlValue(firstRecord, 'Mileage')) || 0;
            const rawEndMileage = parseFloat(this._getXmlValue(lastRecord, 'Mileage')) || 0;

            console.log(`   🔍 DEBUG - Mileage bruto: Inicial=${rawStartMileage}, Final=${rawEndMileage}`);

            // Normaliza para KM
            let startOdometer = rawStartMileage >= 1000000 ? Math.floor(rawStartMileage / 1000) : Math.floor(rawStartMileage);
            let endOdometer = rawEndMileage >= 1000000 ? Math.floor(rawEndMileage / 1000) : Math.floor(rawEndMileage);

            // Se odômetros são zero, tenta encontrar registros com valores válidos
            if (startOdometer === 0 && endOdometer === 0 && allRecords.length > 2) {
                console.log(`   ⚠️ Odômetros inicial e final são zero. Buscando valores válidos...`);

                // Busca o primeiro registro com odômetro válido
                for (let i = 0; i < allRecords.length; i++) {
                    const rawMileage = parseFloat(this._getXmlValue(allRecords[i], 'Mileage')) || 0;
                    const mileage = rawMileage >= 1000000 ? Math.floor(rawMileage / 1000) : Math.floor(rawMileage);
                    if (mileage > 0) {
                        startOdometer = mileage;
                        console.log(`   ✅ Primeiro odômetro válido encontrado: ${startOdometer} km`);
                        break;
                    }
                }

                // Busca o último registro com odômetro válido
                for (let i = allRecords.length - 1; i >= 0; i--) {
                    const rawMileage = parseFloat(this._getXmlValue(allRecords[i], 'Mileage')) || 0;
                    const mileage = rawMileage >= 1000000 ? Math.floor(rawMileage / 1000) : Math.floor(rawMileage);
                    if (mileage > 0) {
                        endOdometer = mileage;
                        console.log(`   ✅ Último odômetro válido encontrado: ${endOdometer} km`);
                        break;
                    }
                }
            }

            // Calcula estatísticas
            let maxSpeed = 0;
            let speedCount = 0;
            let speedSum = 0;

            for (let i = 0; i < allRecords.length; i++) {
                const speed = parseInt(this._getXmlValue(allRecords[i], 'Speed')) || 0;
                if (speed > 0) {
                    speedSum += speed;
                    speedCount++;
                    if (speed > maxSpeed) maxSpeed = speed;
                }
            }

            const kmDriven = endOdometer - startOdometer;
            const avgSpeed = speedCount > 0 ? Math.round(speedSum / speedCount) : 0;

            console.log(`✅ Relatório gerado:`);
            console.log(`   KM Inicial: ${startOdometer}`);
            console.log(`   KM Final: ${endOdometer}`);
            console.log(`   KM Rodados: ${kmDriven}`);
            console.log(`   Total de registros: ${allRecords.length}`);

            // Se quilometragem for negativa, significa que há problema nos dados
            if (kmDriven < 0) {
                console.warn(`   ⚠️ AVISO: Quilometragem negativa detectada! Retornando 0.`);
                return {
                    success: true,
                    plate: plate,
                    startDate: this._getXmlValue(firstRecord, 'Date'),
                    endDate: this._getXmlValue(lastRecord, 'Date'),
                    startOdometer: Math.round(startOdometer),
                    endOdometer: Math.round(endOdometer),
                    kmDriven: 0,
                    routePoints: allRecords.length,
                    avgSpeed: avgSpeed,
                    maxSpeed: maxSpeed,
                    route: allRoutePoints
                };
            }

            return {
                success: true,
                plate: plate,
                startDate: this._getXmlValue(firstRecord, 'Date'),
                endDate: this._getXmlValue(lastRecord, 'Date'),
                startOdometer: Math.round(startOdometer),
                endOdometer: Math.round(endOdometer),
                kmDriven: Math.round(kmDriven),
                routePoints: allRecords.length,
                avgSpeed: avgSpeed,
                maxSpeed: maxSpeed,
                route: allRoutePoints
            };
        } catch (error) {
            console.error(`❌ Erro ao gerar relatório de KM:`, error);
            throw error;
        }
    }

    /**
     * Obtém todos os dados do veículo
     */
    async getVehicleCompleteData(plateOrId, forceRefresh = false) {
        try {
            // Busca telemetria primeiro para obter quilometragem
            const telemetry = await this.getVehicleTelemetry(plateOrId);

            // Busca localização e manutenção em paralelo
            const [location, maintenance] = await Promise.all([
                this.getVehicleLocation(plateOrId, forceRefresh),
                this.getMaintenanceStatus(plateOrId, telemetry.odometer)
            ]);

            return {
                location,
                telemetry,
                maintenance,
                lastSync: new Date().toISOString()
            };
        } catch (error) {
            console.error(`Erro ao obter dados completos do veículo ${plateOrId}:`, error);
            throw error;
        }
    }

    /**
     * Obtém dados em TEMPO REAL do veículo com TODAS as informações
     * SEMPRE ignora cache e busca dados frescos da API
     * @param {string} plate - Placa do veículo
     * @returns {Promise<Object>} Dados completos em tempo real
     */
    async getVehicleRealtimeData(plate) {
        try {
            console.log(`🔴 TEMPO REAL - Buscando dados FRESCOS para ${plate}...`);

            const xmlDoc = await this._makeRequest('/ituranwebservice3/Service3.asmx/GetPlatformData', {
                Plate: plate,
                ShowAreas: 'true',
                ShowStatuses: 'true'
            });

            const platformData = xmlDoc.getElementsByTagName('ServicePlatformData')[0];

            if (!platformData) {
                throw new Error('Veículo não encontrado');
            }

            // Extrai TODOS os dados
            const latitude = parseFloat(this._getXmlValue(platformData, 'LastLatitude'));
            const longitude = parseFloat(this._getXmlValue(platformData, 'LastLongitude'));
            const address = this._getXmlValue(platformData, 'LastAddress');
            const speed = parseInt(this._getXmlValue(platformData, 'LastSpeed')) || 0;
            const heading = parseInt(this._getXmlValue(platformData, 'LastHead')) || 0;
            const odometer = Math.floor(parseFloat(this._getXmlValue(platformData, 'LastMilieage')) || 0);
            const lastLocationTime = this._getXmlValue(platformData, 'LastLocationTime');
            const currentDriverId = parseInt(this._getXmlValue(platformData, 'currentDriverId')) || 0;

            // Extrai status (motor, ignição, etc)
            const statusElements = platformData.getElementsByTagName('string');
            const statuses = [];
            let engineStatus = 'desligado';
            let ignitionStatus = 'desligada';

            for (let i = 0; i < statusElements.length; i++) {
                const status = statusElements[i].textContent;
                statuses.push(status);

                if (status.includes('Motor ligado') || status.includes('Motor Ligado')) {
                    engineStatus = 'ligado';
                } else if (status.includes('Motor desligado') || status.includes('Motor Desligado')) {
                    engineStatus = 'desligado';
                }

                if (status.includes('Ignição ligada') || status.includes('Ignição Ligada')) {
                    ignitionStatus = 'ligada';
                } else if (status.includes('Ignição desligada') || status.includes('Ignição Desligada')) {
                    ignitionStatus = 'desligada';
                }
            }

            // Calcula há quanto tempo foi a última atualização
            const lastUpdate = new Date(lastLocationTime);
            const now = new Date();
            const ageInSeconds = Math.floor((now - lastUpdate) / 1000);
            const ageInMinutes = Math.floor(ageInSeconds / 60);
            const ageText = ageInMinutes < 1
                ? `${ageInSeconds} segundos atrás`
                : ageInMinutes === 1
                    ? '1 minuto atrás'
                    : `${ageInMinutes} minutos atrás`;

            // Determina se está em movimento
            const isMoving = speed > 5; // Considera em movimento se > 5 km/h
            const movementStatus = isMoving ? 'Em movimento' : 'Parado';

            const realtimeData = {
                plate: plate,
                position: {
                    latitude: latitude,
                    longitude: longitude,
                    address: address,
                    lastUpdate: lastLocationTime,
                    age: ageText,
                    ageSeconds: ageInSeconds
                },
                movement: {
                    speed: speed,
                    heading: heading,
                    status: movementStatus,
                    isMoving: isMoving
                },
                vehicle: {
                    odometer: odometer,
                    engineStatus: engineStatus,
                    ignitionStatus: ignitionStatus,
                    driverId: currentDriverId,
                    driverName: currentDriverId > 0 ? `Motorista #${currentDriverId}` : 'Sem motorista'
                },
                statuses: statuses,
                timestamp: now.toISOString()
            };

            console.log(`✅ TEMPO REAL - Dados atualizados:`, {
                endereço: address,
                velocidade: `${speed} km/h`,
                motor: engineStatus,
                ignição: ignitionStatus,
                atualização: ageText
            });

            return realtimeData;

        } catch (error) {
            console.error(`❌ Erro ao obter dados em tempo real do veículo ${plate}:`, error);
            throw error;
        }
    }

    // ==================== MÉTODOS DE MOCK (fallback) ====================

    _getMockVehiclesList() {
        return [
            {
                id: '2113630',
                plate: 'OVE4358',
                model: 'Veículo 1',
                brand: 'Ituran',
                year: 2022,
                status: 'active',
                odometer: 50000,
                lastUpdate: new Date().toISOString()
            }
        ];
    }

    _getMockLocation(vehicleId) {
        const baseLocations = [
            { lat: -23.550520, lng: -46.633308 },
            { lat: -23.561414, lng: -46.656147 },
            { lat: -23.533773, lng: -46.625290 }
        ];

        const location = baseLocations[Math.floor(Math.random() * baseLocations.length)];

        return {
            vehicleId,
            latitude: location.lat,
            longitude: location.lng,
            heading: Math.floor(Math.random() * 360),
            speed: Math.floor(Math.random() * 80),
            timestamp: new Date().toISOString(),
            address: 'Av. Paulista, São Paulo - SP'
        };
    }

    _getMockRoute(vehicleId) {
        const startLat = -23.550520;
        const startLng = -46.633308;
        const route = [];

        for (let i = 0; i < 10; i++) {
            route.push({
                latitude: startLat + (i * 0.001),
                longitude: startLng + (i * 0.001),
                timestamp: new Date(Date.now() - (10 - i) * 60000).toISOString(),
                speed: 40 + Math.random() * 20
            });
        }

        return route;
    }

    _getMockTelemetry(vehicleId) {
        return {
            odometer: 45000 + Math.floor(Math.random() * 50000),
            currentSpeed: Math.floor(Math.random() * 80),
            fuelLevel: 50 + Math.floor(Math.random() * 50),
            engineStatus: Math.random() > 0.5 ? 'on' : 'off',
            lastUpdate: new Date().toISOString()
        };
    }

    _getMockMaintenanceStatus(vehicleId) {
        const odometer = 45000 + Math.floor(Math.random() * 50000);
        const nextMaintenance = Math.ceil(odometer / 10000) * 10000;
        const kmUntilMaintenance = nextMaintenance - odometer;

        let status = 'ok';
        let alerts = [];

        if (kmUntilMaintenance < 1000) {
            status = 'critical';
            alerts.push({
                type: 'maintenance',
                severity: 'high',
                message: `Manutenção urgente em ${kmUntilMaintenance} km`
            });
        } else if (kmUntilMaintenance < 2000) {
            status = 'warning';
            alerts.push({
                type: 'maintenance',
                severity: 'medium',
                message: `Manutenção próxima em ${kmUntilMaintenance} km`
            });
        }

        return {
            nextMaintenance,
            lastMaintenance: odometer - (10000 - kmUntilMaintenance),
            kmUntilMaintenance,
            alerts,
            status
        };
    }

    /**
     * Limpa o cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Busca dados de quilometragem de um veículo para um período específico
     * Este método é usado pelo sistema de quilometragem para salvar dados históricos
     * @param {string} plate - Placa do veículo
     * @param {string} startDate - Data inicial (ISO format)
     * @param {string} endDate - Data final (ISO format)
     * @returns {Promise<Object|null>} Dados de quilometragem ou null se não houver dados
     */
    async getVehicleReport(plate, startDate, endDate) {
        try {
            console.log(`📊 [getVehicleReport] Buscando dados de ${plate} para ${startDate} - ${endDate}`);

            // Usa o método getKilometerReport que já existe
            const report = await this.getKilometerReport(plate, startDate, endDate);

            if (!report || !report.success) {
                console.warn(`⚠️ [getVehicleReport] Sem dados disponíveis para ${plate}`);
                return null;
            }

            // Calcula tempo de ignição (em minutos) - aproximado com base nos pontos GPS
            // Assume que cada ponto representa ~1 minuto de movimento
            const tempoIgnicao = report.routePoints || 0;

            const resultado = {
                kmInicial: report.startOdometer,
                kmFinal: report.endOdometer,
                kmRodados: report.kmDriven,
                tempoIgnicao: tempoIgnicao
            };

            console.log(`✅ [getVehicleReport] Dados obtidos:`, resultado);
            return resultado;

        } catch (error) {
            console.error(`❌ [getVehicleReport] Erro ao buscar dados de ${plate}:`, error);
            return null;
        }
    }
}

// Exporta uma instância única do serviço
const ituranService = new IturanService();

// Disponibiliza globalmente (apenas no navegador)
if (typeof window !== 'undefined') {
    window.ituranService = ituranService;

    /**
     * Função para testar a conexão com o proxy direto do console
     */
    window.testProxyConnection = async function() {
    console.log('🧪 Testando conexão com proxy...');

    const url = 'http://localhost:8888/api/ituran/ituranwebservice3/Service3.asmx/GetAllPlatformsData?UserName=api@i9tecnologia&Password=Api@In9Eng&ShowAreas=true&ShowStatuses=true&ShowMileageInMeters=true&ShowDriver=true';

    console.log(`📡 Enviando requisição para: ${url.substring(0, 80)}...`);

    try {
        const response = await fetch(url, { method: 'GET' });
        console.log(`✅ Status HTTP: ${response.status}`);

        const text = await response.text();
        console.log(`✅ Resposta recebida (${text.length} bytes)`);
        console.log(`✅ Primeiras 500 caracteres:`, text.substring(0, 500));

        return response;
    } catch (error) {
        console.error(`❌ Erro na requisição:`, error);
        return null;
    }
    };

    console.log('💡 Dica: Digite testProxyConnection() no console para testar a conexão com o proxy');
}

// Exportar para Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ituranService;
}
