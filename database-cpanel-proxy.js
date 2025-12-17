// Gerenciamento de Banco de Dados de Quilometragem - Proxy para cPanel APIs
// Esta versão usa HTTP para chamar APIs PHP do cPanel em vez de conectar diretamente ao MySQL
// Usada quando o servidor Node.js não tem acesso direto ao MySQL

const API_BASE_URL = process.env.CPANEL_API_URL || 'https://floripa.in9automacao.com.br/cpanel-api';

// Funções de conveniência que usam HTTP
const dbFunctions = {
    // Salvar quilometragem diária
    async salvarDiaria(placa, data, kmInicial, kmFinal, tempoIgnicao = 0) {
        try {
            const response = await fetch(`${API_BASE_URL}/telemetria-diaria-api.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    licensePlate: placa,
                    date: data.split('T')[0],
                    kmInicial,
                    kmFinal,
                    tempoLigado: tempoIgnicao
                })
            });

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Erro ao salvar telemetria');
            }
            return result;
        } catch (error) {
            console.error('❌ Erro ao salvar diaria:', error);
            throw error;
        }
    },

    // Buscar quilometragem de um dia
    async buscarDiaria(placa, data) {
        try {
            const response = await fetch(
                `${API_BASE_URL}/telemetria-diaria-api.php?plate=${placa}&date=${data.split('T')[0]}`
            );
            const result = await response.json();

            if (!result.success || !result.data || result.data.length === 0) {
                return null;
            }

            return result.data[0];
        } catch (error) {
            console.error('❌ Erro ao buscar diaria:', error);
            return null;
        }
    },

    // Buscar quilometragem de um período
    async buscarPeriodo(placa, dataInicio, dataFim) {
        try {
            const response = await fetch(
                `${API_BASE_URL}/telemetria-diaria-api.php?plate=${placa}&startDate=${dataInicio.split('T')[0]}&endDate=${dataFim.split('T')[0]}`
            );
            const result = await response.json();

            if (!result.success) {
                return [];
            }

            return result.data || [];
        } catch (error) {
            console.error('❌ Erro ao buscar período:', error);
            return [];
        }
    },

    // Atualizar dados mensais (mantido por compatibilidade mas não faz nada)
    async atualizarMensal(placa, ano, mes) {
        return null;
    },

    // Buscar quilometragem mensal (calculado dinamicamente via API)
    async buscarMensal(placa, ano, mes) {
        try {
            const response = await fetch(
                `${API_BASE_URL}/km-by-period-api.php?plate=${placa}&year=${ano}&month=${mes}`
            );
            const result = await response.json();

            if (!result.success) {
                return null;
            }

            return result.data;
        } catch (error) {
            console.error('❌ Erro ao buscar mensal:', error);
            return null;
        }
    },

    // Buscar quilometragem de vários meses
    async buscarMeses(placa, anoInicio, mesInicio, anoFim, mesFim) {
        try {
            const startDate = `${anoInicio}-${String(mesInicio).padStart(2, '0')}-01`;
            const endDate = `${anoFim}-${String(mesFim).padStart(2, '0')}-28`;

            const response = await fetch(
                `${API_BASE_URL}/telemetria-diaria-api.php?plate=${placa}&startDate=${startDate}&endDate=${endDate}`
            );
            const result = await response.json();

            if (!result.success) {
                return [];
            }

            return result.data || [];
        } catch (error) {
            console.error('❌ Erro ao buscar meses:', error);
            return [];
        }
    },

    // Atualizar totais diários da frota
    async atualizarTotalFrotaDiaria(data) {
        // Por enquanto, não implementado via API
        console.warn('⚠️ atualizarTotalFrotaDiaria não implementado via cPanel API');
        return null;
    },

    // Buscar total da frota por dia
    async buscarTotalFrotaDia(data) {
        // Por enquanto, não implementado via API
        console.warn('⚠️ buscarTotalFrotaDia não implementado via cPanel API');
        return null;
    },

    // Buscar todas as placas com dados em uma data
    async buscarPlacasPorData(data) {
        try {
            const response = await fetch(
                `${API_BASE_URL}/telemetria-diaria-api.php?date=${data.split('T')[0]}`
            );
            const result = await response.json();

            if (!result.success) {
                return [];
            }

            return result.data.map(d => ({ placa: d.plate })) || [];
        } catch (error) {
            console.error('❌ Erro ao buscar placas por data:', error);
            return [];
        }
    }
};

module.exports = {
    pool: null, // Não há pool quando usa APIs
    ...dbFunctions
};
