/**
 * Ponto de Entrada dos Serviços de Quilometragem
 * Inicializa e exporta todos os serviços
 */

// Detecta ambiente (Node.js ou Browser)
const isNode = typeof window === 'undefined';

let IturanAPIClient, IturanMileageService, MileageManager;
let mileageService = null;

if (isNode) {
    // Node.js - usa require
    IturanAPIClient = require('./ituran-api-client');
    IturanMileageService = require('./ituran-mileage-service');
    MileageManager = require('./mileage-manager');

    // Inicializa os serviços
    // Em produção, apiUrl será https://iweb.ituran.com.br (veja ituran-api-client.js)
    // As credenciais podem vir de variáveis de ambiente (.env)
    const apiClient = new IturanAPIClient({
        apiUrl: process.env.ITURAN_API_URL || 'https://iweb.ituran.com.br',
        username: process.env.ITURAN_USERNAME || 'api@i9tecnologia',
        password: process.env.ITURAN_PASSWORD || 'Api@In9Eng',
        timeout: parseInt(process.env.ITURAN_TIMEOUT || '120000')
    });

    const ituranMileage = new IturanMileageService(apiClient);
    mileageService = new MileageManager(ituranMileage);

    console.log('✅ Serviços de quilometragem inicializados (Node.js)');
    console.log(`🔗 API URL: ${process.env.ITURAN_API_URL || 'https://iweb.ituran.com.br'}`);

    // Exporta para Node.js
    module.exports = {
        IturanAPIClient,
        IturanMileageService,
        MileageManager,
        mileageService
    };

} else {
    // Browser - espera scripts serem carregados
    console.log('✅ Módulo de serviços carregado (Browser)');

    /**
     * Inicializa os serviços no browser
     * Em produção, o frontend consulta /api/quilometragem/* do servidor
     * O servidor é responsável por consultar API Ituran diretamente
     * Chame esta função após carregar todos os scripts necessários
     */
    window.initMileageServices = function() {
        if (!window.IturanAPIClient || !window.IturanMileageService) {
            console.error('❌ Classes não encontradas. Certifique-se de carregar os scripts necessários.');
            return null;
        }

        // Em produção: API do servidor que cuida de CORS
        // O endpoint /api/quilometragem do servidor redireciona para Ituran
        const apiClient = new window.IturanAPIClient({
            apiUrl: '/api/quilometragem',
            username: 'api@i9tecnologia',
            password: 'Api@In9Eng',
            timeout: 120000
        });

        const ituranMileage = new window.IturanMileageService(apiClient);

        console.log('✅ Serviços de quilometragem inicializados (Browser)');
        console.log('🔗 API URL: /api/quilometragem (servidor cuida de CORS)');

        return {
            apiClient,
            ituranMileage
        };
    };
}
