const db = require('./database');
const IturanService = require('./ituran-service');
const fs = require('fs').promises;
const path = require('path');

// Arquivo JSON com lista de veículos
const veiculosFile = path.join(__dirname, 'data', 'veiculos.json');

class QuilometragemAPI {
    // Salvar quilometragem de um dia específico
    async salvarDiaria(placa, data, kmInicial, kmFinal, tempoIgnicao = 0) {
        try {
            const result = await db.salvarDiaria(placa, data, kmInicial, kmFinal, tempoIgnicao);

            // Atualizar dados mensais
            const dataObj = new Date(data);
            const ano = dataObj.getFullYear();
            const mes = dataObj.getMonth() + 1;
            await db.atualizarMensal(placa, ano, mes);

            // Atualizar totais da frota para esse dia
            await db.atualizarTotalFrotaDiaria(data);

            return {
                success: true,
                data: result
            };
        } catch (error) {
            console.error('Erro ao salvar quilometragem diária:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Buscar quilometragem de um dia
    async buscarDiaria(placa, data) {
        try {
            const result = await db.buscarDiaria(placa, data);
            return {
                success: true,
                data: result
            };
        } catch (error) {
            console.error('Erro ao buscar quilometragem diária:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Buscar quilometragem de um período
    async buscarPeriodo(placa, dataInicio, dataFim) {
        try {
            const result = await db.buscarPeriodo(placa, dataInicio, dataFim);
            return {
                success: true,
                data: result
            };
        } catch (error) {
            console.error('Erro ao buscar quilometragem por período:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Buscar quilometragem mensal
    async buscarMensal(placa, ano, mes) {
        try {
            const result = await db.buscarMensal(placa, ano, mes);
            return {
                success: true,
                data: result
            };
        } catch (error) {
            console.error('Erro ao buscar quilometragem mensal:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Buscar quilometragem de vários meses
    async buscarMeses(placa, anoInicio, mesInicio, anoFim, mesFim) {
        try {
            const result = await db.buscarMeses(placa, anoInicio, mesInicio, anoFim, mesFim);
            return {
                success: true,
                data: result
            };
        } catch (error) {
            console.error('Erro ao buscar quilometragem de meses:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Buscar e salvar quilometragem atual de um veículo da API Ituran
    async atualizarDaIturan(placa, data = null) {
        try {
            if (!data) {
                data = new Date().toISOString().split('T')[0];
            }

            const dataObj = new Date(data + 'T00:00:00');
            const dataInicio = new Date(dataObj);
            const dataFim = new Date(dataObj);
            dataFim.setHours(23, 59, 59);

            // Buscar dados da API Ituran
            const ituranData = await IturanService.getVehicleReport(
                placa,
                dataInicio.toISOString(),
                dataFim.toISOString()
            );

            if (ituranData && ituranData.kmInicial !== undefined && ituranData.kmFinal !== undefined) {
                // Salvar no banco
                const result = await this.salvarDiaria(
                    placa,
                    data,
                    ituranData.kmInicial,
                    ituranData.kmFinal,
                    ituranData.tempoIgnicao || 0
                );

                return {
                    success: true,
                    data: {
                        placa,
                        data,
                        kmInicial: ituranData.kmInicial,
                        kmFinal: ituranData.kmFinal,
                        kmRodados: ituranData.kmFinal - ituranData.kmInicial,
                        tempoIgnicao: ituranData.tempoIgnicao
                    }
                };
            } else {
                return {
                    success: false,
                    error: 'Sem dados disponíveis da API Ituran'
                };
            }
        } catch (error) {
            console.error('Erro ao atualizar dados da Ituran:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Atualizar dados de todos os veículos para uma data específica
    async atualizarTodosVeiculos(data = null) {
        try {
            if (!data) {
                data = new Date().toISOString().split('T')[0];
            }

            // Ler lista de veículos
            let veiculos = [];
            try {
                const veiculosData = await fs.readFile(veiculosFile, 'utf-8');
                veiculos = JSON.parse(veiculosData);
            } catch (error) {
                console.error('Erro ao ler arquivo de veículos:', error);
                return {
                    success: false,
                    error: 'Não foi possível carregar lista de veículos'
                };
            }

            const resultados = [];
            for (const veiculo of veiculos) {
                console.log(`Atualizando dados de ${veiculo.placa} para ${data}...`);
                const resultado = await this.atualizarDaIturan(veiculo.placa, data);
                resultados.push({
                    placa: veiculo.placa,
                    ...resultado
                });
            }

            const sucessos = resultados.filter(r => r.success).length;
            const falhas = resultados.filter(r => !r.success).length;

            return {
                success: true,
                data: {
                    total: veiculos.length,
                    sucessos,
                    falhas,
                    resultados
                }
            };
        } catch (error) {
            console.error('Erro ao atualizar todos os veículos:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    // Buscar estatísticas de um veículo
    async buscarEstatisticas(placa, periodo = 'mes') {
        try {
            const hoje = new Date();
            let dataInicio, dataFim;

            if (periodo === 'semana') {
                dataInicio = new Date(hoje);
                dataInicio.setDate(hoje.getDate() - 7);
                dataFim = hoje;
            } else if (periodo === 'mes') {
                dataInicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                dataFim = hoje;
            } else if (periodo === 'ano') {
                dataInicio = new Date(hoje.getFullYear(), 0, 1);
                dataFim = hoje;
            }

            const dados = await db.buscarPeriodo(
                placa,
                dataInicio.toISOString(),
                dataFim.toISOString()
            );

            const totalKm = dados.reduce((sum, dia) => sum + (dia.km_rodados || 0), 0);
            const totalDias = dados.length;
            const mediaKmDia = totalDias > 0 ? totalKm / totalDias : 0;

            return {
                success: true,
                data: {
                    periodo,
                    totalKm: parseFloat(totalKm.toFixed(2)),
                    totalDias,
                    mediaKmDia: parseFloat(mediaKmDia.toFixed(2)),
                    dados
                }
            };
        } catch (error) {
            console.error('Erro ao buscar estatísticas:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

module.exports = new QuilometragemAPI();
