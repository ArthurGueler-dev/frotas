/**
 * Script para preencher dados históricos de quilometragem
 *
 * Este script busca dados da API Ituran para um período específico e salva na tabela Telemetria_Diaria
 * via API PHP no cPanel.
 *
 * Uso: node fill-historical-km.js 2025-11-01 2025-11-30
 */

const quilometragemAPI = require('./quilometragem-api');

async function preencherHistorico(dataInicio, dataFim) {
    console.log('═══════════════════════════════════════════════════════════');
    console.log('📦 PREENCHIMENTO DE DADOS HISTÓRICOS');
    console.log(`📅 Período: ${dataInicio} até ${dataFim}`);
    console.log('═══════════════════════════════════════════════════════════');
    console.log('');

    // Validar datas
    const inicio = new Date(dataInicio);
    const fim = new Date(dataFim);

    if (isNaN(inicio) || isNaN(fim)) {
        console.error('❌ Datas inválidas. Use formato YYYY-MM-DD');
        console.error('Exemplo: node fill-historical-km.js 2025-11-01 2025-11-30');
        process.exit(1);
    }

    if (inicio > fim) {
        console.error('❌ Data inicial não pode ser maior que data final');
        process.exit(1);
    }

    // Limitar a 90 dias para não sobrecarregar
    const diasDiferenca = Math.ceil((fim - inicio) / (1000 * 60 * 60 * 24)) + 1;
    if (diasDiferenca > 90) {
        console.error('❌ Período muito longo (máximo 90 dias)');
        console.error(`   Você solicitou ${diasDiferenca} dias. Por favor, divida em períodos menores.`);
        process.exit(1);
    }

    // Gerar lista de datas
    const datas = [];
    for (let d = new Date(inicio); d <= fim; d.setDate(d.getDate() + 1)) {
        datas.push(new Date(d).toISOString().split('T')[0]);
    }

    console.log(`📊 Total de dias a processar: ${datas.length}`);
    console.log('⚠️  ATENÇÃO: Este processo pode demorar várias horas!');
    console.log('⚠️  Não feche o terminal até a conclusão.');
    console.log('⚠️  Pausas de 5 segundos entre dias para não sobrecarregar API Ituran');
    console.log('');

    // Confirmar antes de iniciar
    if (process.stdin.isTTY) {
        const readline = require('readline').createInterface({
            input: process.stdin,
            output: process.stdout
        });

        const answer = await new Promise(resolve => {
            readline.question('Deseja continuar? (s/n): ', resolve);
        });

        readline.close();

        if (answer.toLowerCase() !== 's') {
            console.log('❌ Operação cancelada pelo usuário');
            process.exit(0);
        }
    }

    console.log('');
    console.log('🚀 Iniciando preenchimento histórico...');
    console.log('');

    let sucessos = 0;
    let falhas = 0;
    const erros = [];

    const startTime = Date.now();

    for (let i = 0; i < datas.length; i++) {
        const data = datas[i];
        const progress = Math.round(((i + 1) / datas.length) * 100);

        console.log(`\n📅 [${i + 1}/${datas.length}] [${progress}%] Processando ${data}...`);

        try {
            const resultado = await quilometragemAPI.atualizarTodosVeiculos(data);

            if (resultado.success) {
                console.log(`   ✅ Sucesso: ${resultado.data.sucessos} veículos salvos`);
                if (resultado.data.falhas > 0) {
                    console.log(`   ⚠️  Falhas: ${resultado.data.falhas} veículos`);
                }
                sucessos++;
            } else {
                console.error(`   ❌ Erro: ${resultado.error}`);
                falhas++;
                erros.push({ data, erro: resultado.error });
            }

            // Pausa de 5 segundos entre dias (não sobrecarregar API Ituran)
            if (i < datas.length - 1) {
                console.log('   ⏳ Aguardando 5 segundos...');
                await new Promise(resolve => setTimeout(resolve, 5000));
            }

        } catch (error) {
            console.error(`   ❌ Erro inesperado: ${error.message}`);
            falhas++;
            erros.push({ data, erro: error.message });

            // Pausa de 10 segundos após erro (pode ser temporário)
            if (i < datas.length - 1) {
                console.log('   ⏳ Aguardando 10 segundos após erro...');
                await new Promise(resolve => setTimeout(resolve, 10000));
            }
        }
    }

    const endTime = Date.now();
    const durationMs = endTime - startTime;
    const durationMin = Math.round(durationMs / 1000 / 60);

    console.log('\n');
    console.log('═══════════════════════════════════════════════════════════');
    console.log('🏁 PREENCHIMENTO HISTÓRICO CONCLUÍDO');
    console.log('═══════════════════════════════════════════════════════════');
    console.log(`✅ Sucessos: ${sucessos} dias`);
    console.log(`❌ Falhas: ${falhas} dias`);
    console.log(`⏱️  Duração: ${durationMin} minutos`);
    console.log('');

    if (erros.length > 0) {
        console.log('📋 Dias com erros:');
        console.log('───────────────────────────────────────────────────────────');
        erros.forEach(({ data, erro }) => {
            console.log(`   ${data}: ${erro}`);
        });
        console.log('───────────────────────────────────────────────────────────');
        console.log('');
    }

    // Estatísticas finais
    console.log('📊 Para verificar os dados salvos, execute:');
    console.log(`   SELECT COUNT(*) FROM Telemetria_Diaria WHERE data BETWEEN '${dataInicio}' AND '${dataFim}';`);
    console.log('');
    console.log('═══════════════════════════════════════════════════════════');
}

// Argumentos da linha de comando
if (require.main === module) {
    const args = process.argv.slice(2);

    if (args.length !== 2) {
        console.error('Uso: node fill-historical-km.js <dataInicio> <dataFim>');
        console.error('Exemplo: node fill-historical-km.js 2025-11-01 2025-11-30');
        console.error('');
        console.error('Notas:');
        console.error('  • Use formato YYYY-MM-DD para as datas');
        console.error('  • Máximo de 90 dias por execução');
        console.error('  • Pausa de 5 segundos entre cada dia');
        console.error('  • Dados são salvos via API PHP no cPanel');
        process.exit(1);
    }

    const [dataInicio, dataFim] = args;

    preencherHistorico(dataInicio, dataFim)
        .then(() => {
            console.log('\n👍 Processo concluído com sucesso!');
            process.exit(0);
        })
        .catch((error) => {
            console.error('\n❌ Processo falhou:', error);
            process.exit(1);
        });
}

module.exports = preencherHistorico;
