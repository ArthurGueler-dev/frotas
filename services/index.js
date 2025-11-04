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
    const apiClient = new IturanAPIClient({
        apiUrl: 'http://localhost:8888/api/ituran',
        username: 'api@i9tecnologia',
        password: 'Api@In9Eng',
        timeout: 120000
    });

    const ituranMileage = new IturanMileageService(apiClient);
    mileageService = new MileageManager(ituranMileage);

    console.log('✅ Serviços de quilometragem inicializados (Node.js)');

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
     * Chame esta função após carregar todos os scripts necessários
     */
    window.initMileageServices = function() {
        if (!window.IturanAPIClient || !window.IturanMileageService) {
            console.error('❌ Classes não encontradas. Certifique-se de carregar os scripts necessários.');
            return null;
        }

        const apiClient = new window.IturanAPIClient({
            apiUrl: 'http://localhost:5000/api/proxy/ituran',
            username: 'api@i9tecnologia',
            password: 'Api@In9Eng',
            timeout: 120000
        });

        const ituranMileage = new window.IturanMileageService(apiClient);

        console.log('✅ Serviços de quilometragem inicializados (Browser)');

        return {
            apiClient,
            ituranMileage
        };
    };
}
