/**
 * Configuração das URLs das APIs PHP no cPanel
 */

const PHP_API_BASE_URL = 'https://floripa.in9automacao.com.br';

const PHP_API_ENDPOINTS = {
    vehicles: `${PHP_API_BASE_URL}/get-vehicles.php`,
    drivers: `${PHP_API_BASE_URL}/get-drivers.php`,
    maintenances: `${PHP_API_BASE_URL}/get-maintenances.php`,
    alerts: `${PHP_API_BASE_URL}/get-alerts.php`,
    workorders: `${PHP_API_BASE_URL}/ordens-servico.php`,
    stats: `${PHP_API_BASE_URL}/get-stats.php`,
    telemetria: `${PHP_API_BASE_URL}/get-telemetria.php`,
    modelos: `${PHP_API_BASE_URL}/modelos.php`,
    pecas: `${PHP_API_BASE_URL}/pecas-api.php`,
    planosManutencao: `${PHP_API_BASE_URL}/planos-manutencao-api.php`,
    planoPecas: `${PHP_API_BASE_URL}/plano-pecas-api.php`,
    sesmt_nrs: `${PHP_API_BASE_URL}/sesmt_get_nrs.php`,
    // Novos endpoints para busca detalhada de KM
    kmDetailed: `${PHP_API_BASE_URL}/km-detailed-api.php`,
    kmFilters: `${PHP_API_BASE_URL}/km-filters-api.php`,
    kmByPeriod: `${PHP_API_BASE_URL}/km-by-period-api.php`
};

module.exports = { PHP_API_BASE_URL, PHP_API_ENDPOINTS };
