/**
 * Script de Atualização Automática de Quilometragem
 *
 * Este script deve ser executado diariamente (recomendado: meia-noite)
 * para buscar e armazenar os dados de quilometragem de todos os veículos
 */

const quilometragemAPI = require('./quilometragem-api');

async function atualizarQuilometragemDiaria() {
    console.log('═══════════════════════════════════════════════════════════');
    console.log('📊 Iniciando atualização automática de quilometragem');
    console.log('   SALVA DADOS DE ONTEM NO BANCO DE DADOS');
    console.log('═══════════════════════════════════════════════════════════');
    console.log(`🕐 Horário: ${new Date().toLocaleString('pt-BR')}`);
    console.log('');

    try {
        // Pegar a data de ONTEM (dados completos disponíveis)
        // Executado à meia-noite, então ontem é o dia que acabou de terminar
        const ontem = new Date();
        ontem.setDate(ontem.getDate() - 1);
        const dataOntem = ontem.toISOString().split('T')[0];

        console.log(`📅 Salvando dados de ONTEM no banco: ${dataOntem}`);
        console.log(`💡 Isso armazena o histórico permanente de KM rodados`);
        console.log('');

        // Atualizar todos os veículos - busca da API Ituran e salva no banco
        const resultado = await quilometragemAPI.atualizarTodosVeiculos(dataOntem);

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

    } catch (error) {
        console.error('❌ ERRO CRÍTICO:', error);
        process.exit(1);
    }

    console.log('');
    console.log('═══════════════════════════════════════════════════════════');
    console.log('🏁 Atualização finalizada');
    console.log('═══════════════════════════════════════════════════════════');
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
