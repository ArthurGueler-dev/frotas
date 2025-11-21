/**
 * Cliente da API Ituran - Camada de Comunicação
 * Responsável apenas por fazer requisições HTTP à API do Ituran
 * Não contém lógica de negócio
 */

class IturanAPIClient {
    constructor(config = {}) {
        this.isNode = typeof window === 'undefined';

        this.config = {
            // Em produção: usar API diretamente
            // Em desenvolvimento: pode usar proxy se configurado
            apiUrl: config.apiUrl || (this.isNode
                ? 'https://iweb.ituran.com.br'
                : 'http://localhost:5000/api/proxy/ituran'),
            username: config.username || process.env?.ITURAN_USERNAME || 'api@i9tecnologia',
            password: config.password || process.env?.ITURAN_PASSWORD || 'Api@In9Eng',
            timeout: config.timeout || 120000 // 2 minutos
        };

        console.log(`🔧 IturanAPIClient inicializado - ${this.isNode ? 'Node.js' : 'Browser'}`);
        console.log(`   API URL: ${this.config.apiUrl.split('?')[0]}`);
    }

    /**
     * Faz uma requisição à API do Ituran
     * @param {string} endpoint - Caminho do endpoint (ex: '/ituranwebservice3/Service3.asmx/GetAllPlatformsData')
     * @param {Object} params - Parâmetros da requisição
     * @returns {Promise<Document>} Documento XML parseado
     */
    async request(endpoint, params = {}) {
        const fullParams = {
            UserName: this.config.username,
            Password: this.config.password,
            ...params
        };

        const queryString = new URLSearchParams(fullParams).toString();
        const url = `${this.config.apiUrl}${endpoint}?${queryString}`;

        console.log(`📡 [API] ${endpoint}`);
        console.log(`   Parâmetros:`, Object.keys(params).join(', ') || 'nenhum');

        try {
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

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`API Error: ${response.status} - ${errorText.substring(0, 200)}`);
            }

            const xmlText = await response.text();
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlText, 'text/xml');

            // Verifica se há erro de parsing
            const parseError = xmlDoc.getElementsByTagName('parsererror')[0];
            if (parseError) {
                throw new Error(`XML Parse Error: ${parseError.textContent}`);
            }

            console.log(`✅ [API] Resposta recebida (${xmlText.length} bytes)`);
            return xmlDoc;

        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error(`Timeout após ${this.config.timeout}ms`);
            }
            console.error(`❌ [API] Erro:`, error.message);
            throw error;
        }
    }

    /**
     * Extrai valor de um elemento XML
     * @param {Document|Element} xmlDoc - Documento ou elemento XML
     * @param {string} tagName - Nome da tag
     * @param {*} defaultValue - Valor padrão se não encontrado
     * @returns {string} Valor do elemento
     */
    getXmlValue(xmlDoc, tagName, defaultValue = '') {
        const element = xmlDoc.getElementsByTagName(tagName)[0];
        return element ? element.textContent.trim() : defaultValue;
    }

    /**
     * Extrai todos os valores de elementos com o mesmo nome
     * @param {Document|Element} xmlDoc - Documento ou elemento XML
     * @param {string} tagName - Nome da tag
     * @returns {Array<string>} Array de valores
     */
    getXmlValues(xmlDoc, tagName) {
        const elements = xmlDoc.getElementsByTagName(tagName);
        const values = [];
        for (let i = 0; i < elements.length; i++) {
            values.push(elements[i].textContent.trim());
        }
        return values;
    }

    /**
     * Verifica o ReturnCode da API
     * @param {Document} xmlDoc - Documento XML
     * @throws {Error} Se ReturnCode não for 'OK'
     */
    checkReturnCode(xmlDoc) {
        const returnCode = this.getXmlValue(xmlDoc, 'ReturnCode');
        if (returnCode && returnCode !== 'OK') {
            throw new Error(`API ReturnCode: ${returnCode}`);
        }
    }
}

// Exporta para Node.js e Browser
if (typeof module !== 'undefined' && module.exports) {
    module.exports = IturanAPIClient;
}
if (typeof window !== 'undefined') {
    window.IturanAPIClient = IturanAPIClient;
}
