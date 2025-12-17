/**
 * Cliente para comunicação com APIs PHP do cPanel
 * Gerencia requisições, cache e tratamento de erros
 */

const axios = require('axios');

class CpanelApiClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl || 'https://floripa.in9automacao.com.br';
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutos

        // Cliente axios configurado
        this.client = axios.create({
            baseURL: this.baseUrl,
            timeout: 30000, // 30 segundos
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        // Interceptor para logging
        this.client.interceptors.request.use(
            config => {
                console.log(`[cPanel API] ${config.method.toUpperCase()} ${config.url}`);
                return config;
            },
            error => {
                console.error('[cPanel API] Request error:', error.message);
                return Promise.reject(error);
            }
        );

        this.client.interceptors.response.use(
            response => {
                console.log(`[cPanel API] ✓ ${response.config.url} - ${response.status}`);
                return response;
            },
            error => {
                console.error(`[cPanel API] ✗ ${error.config?.url} - ${error.response?.status || 'TIMEOUT'}`);
                return Promise.reject(error);
            }
        );
    }

    /**
     * Gera chave de cache baseada na URL e parâmetros
     */
    getCacheKey(endpoint, params) {
        return `${endpoint}_${JSON.stringify(params || {})}`;
    }

    /**
     * Verifica se há cache válido
     */
    getFromCache(key) {
        const cached = this.cache.get(key);
        if (!cached) return null;

        const now = Date.now();
        if (now - cached.timestamp > this.cacheTimeout) {
            this.cache.delete(key);
            return null;
        }

        console.log(`[cPanel API] Cache hit: ${key}`);
        return cached.data;
    }

    /**
     * Salva no cache
     */
    saveToCache(key, data) {
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    /**
     * Limpa cache específico ou todo o cache
     */
    clearCache(key = null) {
        if (key) {
            this.cache.delete(key);
            console.log(`[cPanel API] Cache cleared: ${key}`);
        } else {
            this.cache.clear();
            console.log('[cPanel API] All cache cleared');
        }
    }

    // ============== PLANOS DE MANUTENÇÃO ==============

    /**
     * GET - Listar planos de manutenção
     * @param {Object} filters - {modelo, limit, offset}
     */
    async getPlanos(filters = {}) {
        const cacheKey = this.getCacheKey('planos', filters);
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/planos-manutencao-api.php', {
                params: filters
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar planos de manutenção');
        }
    }

    /**
     * GET - Buscar plano por ID
     */
    async getPlanoById(id) {
        const cacheKey = this.getCacheKey('plano', { id });
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/planos-manutencao-api.php', {
                params: { id }
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar plano');
        }
    }

    /**
     * POST - Criar plano de manutenção
     */
    async createPlano(dados) {
        try {
            const response = await this.client.post('/planos-manutencao-api.php', dados);

            // Limpar cache após criar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao criar plano de manutenção');
        }
    }

    /**
     * PUT - Atualizar plano de manutenção
     */
    async updatePlano(id, dados) {
        try {
            const response = await this.client.put(`/planos-manutencao-api.php?id=${id}`, dados);

            // Limpar cache após atualizar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao atualizar plano de manutenção');
        }
    }

    /**
     * DELETE - Deletar plano de manutenção
     */
    async deletePlano(id) {
        try {
            const response = await this.client.delete(`/planos-manutencao-api.php?id=${id}`);

            // Limpar cache após deletar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao deletar plano de manutenção');
        }
    }

    // ============== PEÇAS ==============

    /**
     * GET - Listar peças
     * @param {Object} filters - {categoria, busca, limit, offset}
     */
    async getPecas(filters = {}) {
        const cacheKey = this.getCacheKey('pecas', filters);
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/pecas-api.php', {
                params: filters
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar peças');
        }
    }

    /**
     * GET - Buscar peça por ID
     */
    async getPecaById(id) {
        const cacheKey = this.getCacheKey('peca', { id });
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/pecas-api.php', {
                params: { id }
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar peça');
        }
    }

    /**
     * POST - Criar peça
     */
    async createPeca(dados) {
        try {
            const response = await this.client.post('/pecas-api.php', dados);

            // Limpar cache após criar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao criar peça');
        }
    }

    /**
     * PUT - Atualizar peça
     */
    async updatePeca(id, dados) {
        try {
            const response = await this.client.put(`/pecas-api.php?id=${id}`, dados);

            // Limpar cache após atualizar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao atualizar peça');
        }
    }

    /**
     * DELETE - Deletar (desativar) peça
     */
    async deletePeca(id) {
        try {
            const response = await this.client.delete(`/pecas-api.php?id=${id}`);

            // Limpar cache após deletar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao deletar peça');
        }
    }

    // ============== ORDENS DE SERVIÇO ==============

    /**
     * GET - Listar ordens de serviço
     */
    async getOrdensServico(filters = {}) {
        // Não cachear ordens de serviço pois mudam frequentemente
        try {
            const response = await this.client.get('/ordens-servico.php', {
                params: filters
            });

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar ordens de serviço');
        }
    }

    /**
     * DELETE - Deletar ordem de serviço
     */
    async deleteOrdemServico(ordem_numero) {
        try {
            const response = await this.client.delete(`/ordens-servico.php?ordem_numero=${ordem_numero}`);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao deletar ordem de serviço');
        }
    }

    // ============== LOCATIONS (Locais) ==============

    /**
     * GET - Listar locais
     * @param {Object} filters - {importBatch, blockId, category}
     */
    async getLocations(filters = {}) {
        const cacheKey = this.getCacheKey('locations', filters);
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/locations-api.php', {
                params: filters
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar locais');
        }
    }

    /**
     * GET - Buscar local por ID
     */
    async getLocationById(id) {
        const cacheKey = this.getCacheKey('location', { id });
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/locations-api.php', {
                params: { id }
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar local');
        }
    }

    /**
     * POST - Inserir locais em batch
     * @param {Array} locations - Array de objetos {name, address, latitude, longitude, category, importBatch}
     */
    async createLocations(locations) {
        try {
            const response = await this.client.post('/locations-api.php', { locations });

            // Limpar cache após criar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao criar locais');
        }
    }

    /**
     * DELETE - Deletar local por ID
     */
    async deleteLocation(id) {
        try {
            const response = await this.client.delete(`/locations-api.php?id=${id}`);

            // Limpar cache após deletar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao deletar local');
        }
    }

    // ============== BLOCKS (Blocos) ==============

    /**
     * GET - Listar blocos
     * @param {Object} filters - {importBatch}
     */
    async getBlocks(filters = {}) {
        const cacheKey = this.getCacheKey('blocks', filters);
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/blocks-api.php', {
                params: filters
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar blocos');
        }
    }

    /**
     * GET - Buscar bloco por ID
     */
    async getBlockById(id) {
        const cacheKey = this.getCacheKey('block', { id });
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/blocks-api.php', {
                params: { id }
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar bloco');
        }
    }

    /**
     * POST - Criar blocos com clustering automático
     * @param {Object} data - {locationIds, maxLocationsPerBlock, maxDistanceKm, importBatch}
     */
    async createBlocks(data) {
        try {
            const response = await this.client.post('/blocks-api.php', data);

            // Limpar cache após criar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao criar blocos');
        }
    }

    /**
     * DELETE - Deletar bloco por ID
     */
    async deleteBlock(id) {
        try {
            const response = await this.client.delete(`/blocks-api.php?id=${id}`);

            // Limpar cache após deletar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao deletar bloco');
        }
    }

    /**
     * POST - Otimizar rota de um bloco
     * @param {Object} data - {blockId, includeReturn}
     */
    async optimizeRoute(data) {
        try {
            const response = await this.client.post('/optimize-route-api.php', data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao otimizar rota');
        }
    }

    // ============== PEÇAS DO PLANO ==============

    /**
     * GET - Buscar peças vinculadas a um item de plano
     */
    async getPecasDoPlano(plano_item_id) {
        const cacheKey = this.getCacheKey('plano_pecas', { plano_item_id });
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await this.client.get('/plano-pecas-api.php', {
                params: { plano_item_id }
            });

            this.saveToCache(cacheKey, response.data);
            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao buscar peças do plano');
        }
    }

    /**
     * POST - Vincular peça a um plano
     */
    async vincularPecaAoPlano(dados) {
        try {
            const response = await this.client.post('/plano-pecas-api.php', dados);

            // Limpar cache após vincular
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao vincular peça ao plano');
        }
    }

    /**
     * PUT - Atualizar quantidade de peça no plano
     */
    async updateQuantidadePeca(id, quantidade) {
        try {
            const response = await this.client.put(`/plano-pecas-api.php?id=${id}`, {
                quantidade: quantidade
            });

            // Limpar cache após atualizar
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao atualizar quantidade da peça');
        }
    }

    /**
     * DELETE - Desvincular peça de um plano
     */
    async desvincularPecaDoPlano(id) {
        try {
            const response = await this.client.delete(`/plano-pecas-api.php?id=${id}`);

            // Limpar cache após desvincular
            this.clearCache();

            return response.data;
        } catch (error) {
            throw this.handleError(error, 'Erro ao desvincular peça do plano');
        }
    }

    // ============== UTILITÁRIOS ==============

    /**
     * Trata erros das requisições
     */
    handleError(error, mensagemPadrao) {
        if (error.response) {
            // Resposta com erro do servidor
            const errorData = error.response.data;
            return {
                success: false,
                error: errorData.error || mensagemPadrao,
                details: errorData.details || error.response.statusText,
                statusCode: error.response.status
            };
        } else if (error.request) {
            // Requisição feita mas sem resposta
            return {
                success: false,
                error: 'Servidor não respondeu',
                details: 'Verifique sua conexão ou se o servidor está online',
                statusCode: 503
            };
        } else {
            // Erro ao configurar requisição
            return {
                success: false,
                error: mensagemPadrao,
                details: error.message,
                statusCode: 500
            };
        }
    }

    /**
     * Health check - verifica se APIs estão acessíveis
     */
    async healthCheck() {
        try {
            const checks = {
                planos: false,
                pecas: false,
                ordens: false
            };

            // Testar endpoint de planos
            try {
                await this.client.get('/planos-manutencao-api.php', {
                    params: { limit: 1 },
                    timeout: 5000
                });
                checks.planos = true;
            } catch (e) {
                console.error('Health check failed: planos-manutencao-api.php');
            }

            // Testar endpoint de peças
            try {
                await this.client.get('/pecas-api.php', {
                    params: { limit: 1 },
                    timeout: 5000
                });
                checks.pecas = true;
            } catch (e) {
                console.error('Health check failed: pecas-api.php');
            }

            // Testar endpoint de ordens
            try {
                await this.client.get('/ordens-servico.php', {
                    timeout: 5000
                });
                checks.ordens = true;
            } catch (e) {
                console.error('Health check failed: ordens-servico.php');
            }

            const allHealthy = checks.planos && checks.pecas && checks.ordens;

            return {
                healthy: allHealthy,
                checks,
                timestamp: new Date().toISOString()
            };
        } catch (error) {
            return {
                healthy: false,
                error: error.message,
                timestamp: new Date().toISOString()
            };
        }
    }
}

// Exportar instância única (singleton)
const cpanelApi = new CpanelApiClient();

module.exports = cpanelApi;
