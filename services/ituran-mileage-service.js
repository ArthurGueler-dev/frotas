/**
 * Serviço de Quilometragem Ituran
 * Responsável por obter e processar dados de quilometragem da API Ituran
 * Usa o IturanAPIClient para comunicação
 */

class IturanMileageService {
    constructor(apiClient) {
        this.api = apiClient;

        // Constantes de conversão
        this.METERS_TO_KM = 1000;
        this.KM_THRESHOLD = 1000000; // Se valor > 1M, provavelmente está em metros
    }

    /**
     * Converte valor de odômetro para KM
     * A API Ituran pode retornar em metros ou KM dependendo do endpoint
     * @param {number} value - Valor do odômetro
     * @param {boolean} isMeters - Se true, força conversão de metros para KM
     * @returns {number} Valor em KM (arredondado para baixo)
     */
    normalizeOdometer(value, isMeters = false) {
        const numValue = parseFloat(value) || 0;

        // Se explicitamente em metros, converte
        if (isMeters) {
            return Math.floor(numValue / this.METERS_TO_KM);
        }

        // Se valor muito alto, provavelmente está em metros
        if (numValue >= this.KM_THRESHOLD) {
            return Math.floor(numValue / this.METERS_TO_KM);
        }

        // Caso contrário, já está em KM
        return Math.floor(numValue);
    }

    /**
     * Valida coordenadas GPS
     * @param {number} lat - Latitude
     * @param {number} lon - Longitude
     * @returns {boolean} True se coordenadas são válidas
     */
    isValidCoordinate(lat, lon) {
        return !isNaN(lat) && !isNaN(lon) &&
               lat !== 0 && lon !== 0 &&
               Math.abs(lat) >= 0.01 && Math.abs(lon) >= 0.01 &&
               Math.abs(lat) <= 90 && Math.abs(lon) <= 180;
    }

    /**
     * Calcula quilometragem rodada entre dois valores
     * @param {number} kmInicial - KM inicial
     * @param {number} kmFinal - KM final
     * @returns {number} KM rodados (sempre >= 0)
     */
    calculateKmDriven(kmInicial, kmFinal) {
        const driven = kmFinal - kmInicial;

        // Valida: não pode ser negativo (erro nos dados)
        if (driven < 0) {
            console.warn(`⚠️ Quilometragem negativa detectada: ${kmInicial} -> ${kmFinal}`);
            return 0;
        }

        // Valida: não pode ser absurdamente alto (erro nos dados)
        if (driven > 5000) {
            console.warn(`⚠️ Quilometragem suspeita (>${driven} km em um período): ${kmInicial} -> ${kmFinal}`);
        }

        return Math.round(driven);
    }

    /**
     * Busca relatório completo de GPS para um veículo
     * @param {string} plate - Placa do veículo
     * @param {Date} startDate - Data inicial
     * @param {Date} endDate - Data final
     * @returns {Promise<Object>} Relatório com dados de GPS
     */
    async getFullReport(plate, startDate, endDate) {
        try {
            console.log(`📊 GetFullReport - ${plate} (${startDate.toISOString()} - ${endDate.toISOString()})`);

            // Valida período (API Ituran limita a 3 dias por requisição)
            const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

            if (diffDays > 3) {
                // Divide em chunks de 2.5 dias (seguro)
                return await this._getFullReportChunked(plate, startDate, endDate);
            }

            // Faz requisição única
            const xmlDoc = await this.api.request('/ituranwebservice3/Service3.asmx/GetFullReport', {
                Plate: plate,
                Start: this._formatDate(startDate),
                End: this._formatDate(endDate),
                UAID: '0',
                MaxNumberOfRecords: '5000'
            });

            this.api.checkReturnCode(xmlDoc);

            const records = this._parseFullReportRecords(xmlDoc);

            return {
                success: true,
                plate,
                startDate: this._formatDate(startDate),
                endDate: this._formatDate(endDate),
                records,
                totalRecords: records.length
            };

        } catch (error) {
            console.error(`❌ Erro em getFullReport:`, error);
            return {
                success: false,
                error: error.message,
                records: []
            };
        }
    }

    /**
     * Busca relatório completo dividido em chunks (períodos > 3 dias)
     * @private
     */
    async _getFullReportChunked(plate, startDate, endDate) {
        const chunks = this._createDateChunks(startDate, endDate, 2.5); // 2.5 dias por chunk
        console.log(`   Dividindo em ${chunks.length} requisições`);

        let allRecords = [];

        for (let i = 0; i < chunks.length; i++) {
            const chunk = chunks[i];
            console.log(`   📡 Chunk ${i + 1}/${chunks.length}...`);

            try {
                const xmlDoc = await this.api.request('/ituranwebservice3/Service3.asmx/GetFullReport', {
                    Plate: plate,
                    Start: this._formatDate(chunk.start),
                    End: this._formatDate(chunk.end),
                    UAID: '0',
                    MaxNumberOfRecords: '5000'
                });

                this.api.checkReturnCode(xmlDoc);

                const records = this._parseFullReportRecords(xmlDoc);
                allRecords = allRecords.concat(records);
                console.log(`      ✅ ${records.length} registros`);

            } catch (error) {
                console.warn(`      ⚠️ Erro no chunk ${i + 1}:`, error.message);
            }

            // Aguarda 500ms entre requisições
            if (i < chunks.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 500));
            }
        }

        // Ordena por timestamp
        allRecords.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));

        return {
            success: true,
            plate,
            startDate: this._formatDate(startDate),
            endDate: this._formatDate(endDate),
            records: allRecords,
            totalRecords: allRecords.length
        };
    }

    /**
     * Parseia registros do GetFullReport
     * @private
     */
    _parseFullReportRecords(xmlDoc) {
        const recordElements = xmlDoc.getElementsByTagName('RecordWithPlate');
        const records = [];

        for (let i = 0; i < recordElements.length; i++) {
            const record = recordElements[i];

            const lat = parseFloat(this.api.getXmlValue(record, 'Lat'));
            const lon = parseFloat(this.api.getXmlValue(record, 'Lon'));

            // Valida coordenadas
            if (!this.isValidCoordinate(lat, lon)) {
                continue;
            }

            // Mileage vem em METROS
            const odometerMeters = parseFloat(this.api.getXmlValue(record, 'Mileage')) || 0;
            const odometer = this.normalizeOdometer(odometerMeters, true);

            records.push({
                latitude: lat,
                longitude: lon,
                timestamp: this.api.getXmlValue(record, 'Date'),
                speed: parseInt(this.api.getXmlValue(record, 'Speed')) || 0,
                odometer
            });
        }

        return records;
    }

    /**
     * Calcula relatório de quilometragem de um período
     * @param {string} plate - Placa do veículo
     * @param {Date} startDate - Data inicial
     * @param {Date} endDate - Data final
     * @returns {Promise<Object>} Relatório com km inicial, final e rodados
     */
    async getMileageReport(plate, startDate, endDate) {
        try {
            console.log(`📊 MileageReport - ${plate}`);

            const fullReport = await this.getFullReport(plate, startDate, endDate);

            if (!fullReport.success || fullReport.records.length === 0) {
                return {
                    success: false,
                    message: 'Sem dados disponíveis para o período',
                    kmInicial: 0,
                    kmFinal: 0,
                    kmRodados: 0
                };
            }

            const records = fullReport.records;

            // Busca primeiro e último odômetro válido
            let startOdometer = 0;
            let endOdometer = 0;

            // Primeiro registro com odômetro válido
            for (let i = 0; i < records.length; i++) {
                if (records[i].odometer > 0) {
                    startOdometer = records[i].odometer;
                    break;
                }
            }

            // Último registro com odômetro válido
            for (let i = records.length - 1; i >= 0; i--) {
                if (records[i].odometer > 0) {
                    endOdometer = records[i].odometer;
                    break;
                }
            }

            const kmDriven = this.calculateKmDriven(startOdometer, endOdometer);

            // Calcula estatísticas de velocidade
            const speeds = records.map(r => r.speed).filter(s => s > 0);
            const maxSpeed = speeds.length > 0 ? Math.max(...speeds) : 0;
            const avgSpeed = speeds.length > 0
                ? Math.round(speeds.reduce((a, b) => a + b, 0) / speeds.length)
                : 0;

            // Calcula tempo de ignição aproximado (número de registros)
            const ignitionTime = records.length;

            return {
                success: true,
                plate,
                startDate: this._formatDate(startDate),
                endDate: this._formatDate(endDate),
                kmInicial: startOdometer,
                kmFinal: endOdometer,
                kmRodados: kmDriven,
                tempoIgnicao: ignitionTime,
                avgSpeed,
                maxSpeed,
                totalRecords: records.length,
                route: records
            };

        } catch (error) {
            console.error(`❌ Erro em getMileageReport:`, error);
            return {
                success: false,
                error: error.message,
                kmInicial: 0,
                kmFinal: 0,
                kmRodados: 0
            };
        }
    }

    /**
     * Busca odômetro atual de um veículo
     * @param {string} plate - Placa do veículo
     * @returns {Promise<Object>} Dados atuais do veículo
     */
    async getCurrentMileage(plate) {
        try {
            const xmlDoc = await this.api.request('/ituranwebservice3/Service3.asmx/GetPlatformData', {
                Plate: plate,
                ShowAreas: 'false',
                ShowStatuses: 'false'
            });

            this.api.checkReturnCode(xmlDoc);

            const platformData = xmlDoc.getElementsByTagName('ServicePlatformData')[0];
            if (!platformData) {
                throw new Error('Veículo não encontrado');
            }

            // LastMilieage pode vir em KM ou metros, dependendo
            const lastMileage = this.api.getXmlValue(platformData, 'LastMilieage');
            const odometer = this.normalizeOdometer(lastMileage);

            return {
                success: true,
                plate,
                odometer,
                lastUpdate: this.api.getXmlValue(platformData, 'LastLocationTime'),
                latitude: parseFloat(this.api.getXmlValue(platformData, 'LastLatitude')),
                longitude: parseFloat(this.api.getXmlValue(platformData, 'LastLongitude'))
            };

        } catch (error) {
            console.error(`❌ Erro em getCurrentMileage:`, error);
            return {
                success: false,
                error: error.message,
                odometer: 0
            };
        }
    }

    /**
     * Formata data para o formato da API Ituran
     * @param {Date} date - Data
     * @returns {string} Data formatada (yyyy-MM-dd HH:mm:ss)
     */
    _formatDate(date) {
        return date.toISOString().replace('T', ' ').substring(0, 19);
    }

    /**
     * Cria chunks de datas para divisão de requisições
     * @param {Date} startDate - Data inicial
     * @param {Date} endDate - Data final
     * @param {number} daysPerChunk - Dias por chunk
     * @returns {Array<{start: Date, end: Date}>} Array de chunks
     */
    _createDateChunks(startDate, endDate, daysPerChunk) {
        const chunks = [];
        let currentStart = new Date(startDate);
        const hoursPerChunk = daysPerChunk * 24;

        while (currentStart < endDate) {
            let currentEnd = new Date(currentStart);
            currentEnd.setHours(currentEnd.getHours() + hoursPerChunk);

            if (currentEnd > endDate) {
                currentEnd = new Date(endDate);
            }

            chunks.push({
                start: new Date(currentStart),
                end: new Date(currentEnd)
            });

            currentStart = new Date(currentEnd);
            currentStart.setSeconds(currentStart.getSeconds() + 1);
        }

        return chunks;
    }
}

// Exporta para Node.js e Browser
if (typeof module !== 'undefined' && module.exports) {
    module.exports = IturanMileageService;
}
if (typeof window !== 'undefined') {
    window.IturanMileageService = IturanMileageService;
}
