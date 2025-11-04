/**
 * Gerenciador de Quilometragem
 * Coordena operações entre API Ituran e Banco de Dados
 * Camada de lógica de negócio
 */

const db = require('../database');

class MileageManager {
    constructor(ituranMileageService) {
        this.ituran = ituranMileageService;
    }

    /**
     * Atualiza quilometragem de um veículo para uma data específica
     * Busca dados da API Ituran e salva no banco
     * @param {string} plate - Placa do veículo
     * @param {string|Date} date - Data (formato YYYY-MM-DD ou Date)
     * @returns {Promise<Object>} Resultado da operação
     */
    async updateDailyMileage(plate, date) {
        try {
            // Converte data se necessário
            const targetDate = typeof date === 'string' ? new Date(date + 'T00:00:00') : date;
            const dateStr = targetDate.toISOString().split('T')[0];

            console.log(`🔄 Atualizando quilometragem de ${plate} para ${dateStr}...`);

            // Define período (início do dia até fim do dia)
            const startDate = new Date(targetDate);
            startDate.setHours(0, 0, 0, 0);

            const endDate = new Date(targetDate);
            endDate.setHours(23, 59, 59, 999);

            // Busca dados da API Ituran
            const report = await this.ituran.getMileageReport(plate, startDate, endDate);

            if (!report.success) {
                return {
                    success: false,
                    message: report.error || 'Erro ao buscar dados da API Ituran',
                    plate,
                    date: dateStr
                };
            }

            // Se não rodou nada, ainda salva com valores zerados
            if (report.kmRodados === 0) {
                console.log(`   ⚠️ Sem quilometragem rodada (veículo parado)`);
            }

            // Salva no banco de dados
            await db.salvarDiaria(
                plate,
                dateStr,
                report.kmInicial,
                report.kmFinal,
                report.tempoIgnicao || 0
            );

            console.log(`   ✅ Salvo: ${report.kmInicial} → ${report.kmFinal} (${report.kmRodados} km)`);

            // Atualiza totais mensais
            const ano = targetDate.getFullYear();
            const mes = targetDate.getMonth() + 1;
            await db.atualizarMensal(plate, ano, mes);

            // Atualiza totais da frota para esse dia
            await db.atualizarTotalFrotaDiaria(dateStr);

            return {
                success: true,
                plate,
                date: dateStr,
                kmInicial: report.kmInicial,
                kmFinal: report.kmFinal,
                kmRodados: report.kmRodados,
                tempoIgnicao: report.tempoIgnicao
            };

        } catch (error) {
            console.error(`❌ Erro ao atualizar quilometragem de ${plate}:`, error);
            return {
                success: false,
                error: error.message,
                plate,
                date: typeof date === 'string' ? date : date.toISOString().split('T')[0]
            };
        }
    }

    /**
     * Atualiza quilometragem de múltiplos veículos para uma data
     * @param {Array<string>} plates - Array de placas
     * @param {string|Date} date - Data
     * @returns {Promise<Object>} Resultado consolidado
     */
    async updateMultipleVehicles(plates, date) {
        console.log(`📦 Atualizando ${plates.length} veículos para ${date}...`);

        const results = [];
        let successCount = 0;
        let failCount = 0;

        for (const plate of plates) {
            const result = await this.updateDailyMileage(plate, date);
            results.push(result);

            if (result.success) {
                successCount++;
            } else {
                failCount++;
            }

            // Aguarda 1s entre requisições para não sobrecarregar a API
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        console.log(`✅ Concluído: ${successCount} sucessos, ${failCount} falhas`);

        return {
            success: true,
            total: plates.length,
            successCount,
            failCount,
            results
        };
    }

    /**
     * Busca quilometragem diária de um veículo
     * @param {string} plate - Placa do veículo
     * @param {string|Date} date - Data
     * @returns {Promise<Object>} Dados do dia
     */
    async getDailyMileage(plate, date) {
        try {
            const dateStr = typeof date === 'string' ? date : date.toISOString().split('T')[0];
            const data = await db.buscarDiaria(plate, dateStr);

            if (!data) {
                return {
                    success: false,
                    message: 'Dados não encontrados para esta data',
                    plate,
                    date: dateStr
                };
            }

            return {
                success: true,
                ...data
            };

        } catch (error) {
            console.error(`❌ Erro ao buscar quilometragem diária:`, error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Busca quilometragem de um período
     * @param {string} plate - Placa do veículo
     * @param {string|Date} startDate - Data inicial
     * @param {string|Date} endDate - Data final
     * @returns {Promise<Object>} Dados do período
     */
    async getPeriodMileage(plate, startDate, endDate) {
        try {
            const startStr = typeof startDate === 'string' ? startDate : startDate.toISOString().split('T')[0];
            const endStr = typeof endDate === 'string' ? endDate : endDate.toISOString().split('T')[0];

            const data = await db.buscarPeriodo(plate, startStr, endStr);

            const totalKm = data.reduce((sum, day) => sum + (day.km_rodados || 0), 0);
            const totalDays = data.length;

            return {
                success: true,
                plate,
                startDate: startStr,
                endDate: endStr,
                totalKm: Math.round(totalKm),
                totalDays,
                avgKmPerDay: totalDays > 0 ? Math.round(totalKm / totalDays) : 0,
                data
            };

        } catch (error) {
            console.error(`❌ Erro ao buscar período:`, error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Busca quilometragem mensal de um veículo
     * @param {string} plate - Placa do veículo
     * @param {number} year - Ano
     * @param {number} month - Mês (1-12)
     * @returns {Promise<Object>} Dados do mês
     */
    async getMonthlyMileage(plate, year, month) {
        try {
            const data = await db.buscarMensal(plate, year, month);

            if (!data) {
                return {
                    success: false,
                    message: 'Dados não encontrados para este mês',
                    plate,
                    year,
                    month
                };
            }

            return {
                success: true,
                ...data
            };

        } catch (error) {
            console.error(`❌ Erro ao buscar quilometragem mensal:`, error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Busca estatísticas de quilometragem
     * @param {string} plate - Placa do veículo
     * @param {string} period - Período ('semana', 'mes', 'ano')
     * @returns {Promise<Object>} Estatísticas
     */
    async getStatistics(plate, period = 'mes') {
        try {
            const today = new Date();
            let startDate, endDate;

            switch (period) {
                case 'semana':
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - 7);
                    endDate = today;
                    break;

                case 'mes':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = today;
                    break;

                case 'ano':
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = today;
                    break;

                default:
                    throw new Error('Período inválido. Use: semana, mes ou ano');
            }

            const result = await this.getPeriodMileage(plate, startDate, endDate);

            return {
                success: result.success,
                plate,
                period,
                ...result
            };

        } catch (error) {
            console.error(`❌ Erro ao buscar estatísticas:`, error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Busca totais da frota por dia
     * @param {string|Date} date - Data
     * @returns {Promise<Object>} Totais da frota
     */
    async getFleetDailyTotal(date) {
        try {
            const dateStr = typeof date === 'string' ? date : date.toISOString().split('T')[0];
            const data = await db.buscarTotalFrotaDia(dateStr);

            if (!data) {
                return {
                    success: false,
                    message: 'Dados não encontrados para esta data',
                    date: dateStr
                };
            }

            return {
                success: true,
                ...data
            };

        } catch (error) {
            console.error(`❌ Erro ao buscar totais da frota:`, error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Sincroniza dados faltantes de um veículo em um período
     * Verifica quais dias não têm dados e busca da API
     * @param {string} plate - Placa do veículo
     * @param {string|Date} startDate - Data inicial
     * @param {string|Date} endDate - Data final
     * @returns {Promise<Object>} Resultado da sincronização
     */
    async syncMissingData(plate, startDate, endDate) {
        try {
            const start = typeof startDate === 'string' ? new Date(startDate + 'T00:00:00') : startDate;
            const end = typeof endDate === 'string' ? new Date(endDate + 'T00:00:00') : endDate;

            console.log(`🔄 Sincronizando dados de ${plate} (${start.toISOString().split('T')[0]} - ${end.toISOString().split('T')[0]})`);

            // Busca dados existentes do período
            const existing = await this.getPeriodMileage(plate, start, end);
            const existingDates = new Set(existing.data.map(d => d.data));

            // Cria lista de datas do período
            const allDates = [];
            const current = new Date(start);

            while (current <= end) {
                allDates.push(current.toISOString().split('T')[0]);
                current.setDate(current.getDate() + 1);
            }

            // Identifica datas faltantes
            const missingDates = allDates.filter(d => !existingDates.has(d));

            console.log(`   ${missingDates.length} datas faltantes`);

            if (missingDates.length === 0) {
                return {
                    success: true,
                    message: 'Todos os dados já estão sincronizados',
                    plate,
                    missingDates: 0,
                    syncedDates: 0
                };
            }

            // Atualiza datas faltantes
            let syncedCount = 0;
            for (const date of missingDates) {
                const result = await this.updateDailyMileage(plate, date);
                if (result.success) {
                    syncedCount++;
                }

                // Aguarda 1s entre requisições
                await new Promise(resolve => setTimeout(resolve, 1000));
            }

            console.log(`   ✅ ${syncedCount}/${missingDates.length} datas sincronizadas`);

            return {
                success: true,
                plate,
                missingDates: missingDates.length,
                syncedDates: syncedCount
            };

        } catch (error) {
            console.error(`❌ Erro ao sincronizar dados:`, error);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

module.exports = MileageManager;
