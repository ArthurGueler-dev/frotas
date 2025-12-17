/**
 * Script de Atualização Automática de Quilometragem
 *
 * Este script deve ser executado diariamente (recomendado: meia-noite)
 * para buscar e armazenar os dados de quilometragem de todos os veículos
 */

const quilometragemAPI = require('./quilometragem-api');

async function atualizarQuilometragemDiaria() {
    console.log('═══════════════════════════════════════════════════════════');
    console.log('📊 ATUALIZAÇÃO AUTOMÁTICA DE QUILOMETRAGEM (23:59)');
    console.log('   SALVA DADOS DE HOJE NO BANCO DE DADOS');
    console.log('═══════════════════════════════════════════════════════════');
    console.log(`🕐 Horário: ${new Date().toLocaleString('pt-BR')}`);
    console.log('');

    try {
        // 1. Atualizar dados de HOJE (executado às 23:59)
        const hoje = new Date();
        const dataHoje = hoje.toISOString().split('T')[0];

        console.log(`📅 Salvando dados de HOJE no banco: ${dataHoje}`);
        console.log(`💡 Executado às 23:59, capturando dados do dia que está terminando`);
        console.log('');

        // Atualizar todos os veículos - busca da API Ituran e salva no banco
        const resultado = await quilometragemAPI.atualizarTodosVeiculos(dataHoje);

        if (resultado.success) {
            console.log('✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!');
            console.log('');
            console.log(`📊 Total de veículos: ${resultado.data.total}`);
            console.log(`✅ Sucessos: ${resultado.data.sucessos}`);
            console.log(`❌ Falhas: ${resultado.data.falhas}`);
            console.log('');

            // Mostrar detalhes de cada veículo
            console.log('📋 Detalhes por veículo:');
            console.log('───────────────────────────────────────────────────────────');

            resultado.data.resultados.forEach((r, index) => {
                if (r.success) {
                    console.log(`${index + 1}. ✅ ${r.placa}: ${r.data.kmRodados.toFixed(2)} km (SALVO NO BANCO)`);
                } else {
                    console.log(`${index + 1}. ❌ ${r.placa}: ${r.error}`);
                }
            });

            console.log('───────────────────────────────────────────────────────────');
        } else {
            console.error('❌ ERRO NA ATUALIZAÇÃO:', resultado.error);
        }

        // 2. Verificar e preencher lacunas nos últimos 30 dias
        console.log('');
        await verificarEPreencherLacunas();

    } catch (error) {
        console.error('❌ ERRO CRÍTICO:', error);
        process.exit(1);
    }

    console.log('');
    console.log('═══════════════════════════════════════════════════════════');
    console.log('🏁 Atualização finalizada');
    console.log('═══════════════════════════════════════════════════════════');
}

/**
 * Verifica e preenche lacunas nos últimos 30 dias
 * Processa máximo 5 dias por execução para não sobrecarregar
 */
async function verificarEPreencherLacunas() {
    try {
        console.log('🔍 Verificando lacunas nos últimos 30 dias...');

        const pool = require('./database').pool;

        // Buscar datas com dados nos últimos 30 dias
        const [rows] = await pool.query(`
            SELECT DISTINCT data
            FROM Telemetria_Diaria
            WHERE data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY data DESC
        `);

        const datasComDados = rows.map(r => r.data.toISOString().split('T')[0]);

        // Gerar lista de todos os dias dos últimos 30 dias
        const hoje = new Date();
        const dias30AtrasTimestamp = hoje.getTime() - (30 * 24 * 60 * 60 * 1000);
        const todosDias = [];

        for (let d = new Date(dias30AtrasTimestamp); d <= hoje; d.setDate(d.getDate() + 1)) {
            todosDias.push(new Date(d).toISOString().split('T')[0]);
        }

        // Encontrar dias faltantes
        const diasFaltantes = todosDias.filter(dia => !datasComDados.includes(dia));

        if (diasFaltantes.length > 0) {
            console.log(`⚠️ Encontrados ${diasFaltantes.length} dias sem dados.`);
            console.log(`🔄 Recalculando (máximo 5 dias por execução)...`);

            // Preencher em batches de 5 dias por vez (não sobrecarregar API Ituran)
            const diasAProcessar = diasFaltantes.slice(0, 5);

            for (const dia of diasAProcessar) {
                console.log(`📅 Recalculando ${dia}...`);
                await quilometragemAPI.atualizarTodosVeiculos(dia);

                // Pausa de 2 segundos entre dias
                await new Promise(resolve => setTimeout(resolve, 2000));
            }

            console.log(`✅ ${diasAProcessar.length} dias recalculados`);

            if (diasFaltantes.length > 5) {
                console.log(`ℹ️ Ainda restam ${diasFaltantes.length - 5} dias. Serão processados nas próximas execuções.`);
            }
        } else {
            console.log('✅ Nenhuma lacuna encontrada nos últimos 30 dias');
        }

    } catch (error) {
        console.error('❌ Erro ao verificar lacunas:', error);
    }
}

// Se executado diretamente (não importado)
if (require.main === module) {
    atualizarQuilometragemDiaria()
        .then(() => {
            console.log('\n👍 Processo concluído com sucesso!');
            process.exit(0);
        })
        .catch((error) => {
            console.error('\n❌ Processo falhou:', error);
            process.exit(1);
        });
}

module.exports = atualizarQuilometragemDiaria;
