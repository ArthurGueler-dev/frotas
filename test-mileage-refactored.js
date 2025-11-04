/**
 * Script de Teste do Sistema de Quilometragem Refatorado
 * Execute com: node test-mileage-refactored.js
 */

const { mileageService } = require('./services/index');

async function runTests() {
    console.log('\n╔═══════════════════════════════════════════════════╗');
    console.log('║   TESTE DO SISTEMA DE QUILOMETRAGEM REFATORADO   ║');
    console.log('╚═══════════════════════════════════════════════════╝\n');

    // Placa de teste (ajuste conforme necessário)
    const testPlate = 'ABC1234';
    const testDate = new Date().toISOString().split('T')[0];

    try {
        // Teste 1: Atualizar quilometragem diária
        console.log('📝 Teste 1: Atualizar quilometragem diária');
        console.log('─'.repeat(50));
        const updateResult = await mileageService.updateDailyMileage(testPlate, testDate);
        console.log('Resultado:', JSON.stringify(updateResult, null, 2));
        console.log('✅ Teste 1 concluído\n');

        // Aguarda 2s
        await sleep(2000);

        // Teste 2: Buscar quilometragem diária
        console.log('📝 Teste 2: Buscar quilometragem diária');
        console.log('─'.repeat(50));
        const dailyResult = await mileageService.getDailyMileage(testPlate, testDate);
        console.log('Resultado:', JSON.stringify(dailyResult, null, 2));
        console.log('✅ Teste 2 concluído\n');

        // Aguarda 2s
        await sleep(2000);

        // Teste 3: Buscar período (últimos 7 dias)
        console.log('📝 Teste 3: Buscar período (últimos 7 dias)');
        console.log('─'.repeat(50));
        const today = new Date();
        const weekAgo = new Date(today);
        weekAgo.setDate(today.getDate() - 7);

        const periodResult = await mileageService.getPeriodMileage(
            testPlate,
            weekAgo.toISOString().split('T')[0],
            today.toISOString().split('T')[0]
        );
        console.log('Total KM:', periodResult.totalKm);
        console.log('Dias:', periodResult.totalDays);
        console.log('Média KM/dia:', periodResult.avgKmPerDay);
        console.log('✅ Teste 3 concluído\n');

        // Aguarda 2s
        await sleep(2000);

        // Teste 4: Buscar estatísticas do mês
        console.log('📝 Teste 4: Buscar estatísticas do mês');
        console.log('─'.repeat(50));
        const statsResult = await mileageService.getStatistics(testPlate, 'mes');
        console.log('Resultado:', JSON.stringify(statsResult, null, 2));
        console.log('✅ Teste 4 concluído\n');

        // Aguarda 2s
        await sleep(2000);

        // Teste 5: Buscar quilometragem mensal
        console.log('📝 Teste 5: Buscar quilometragem mensal');
        console.log('─'.repeat(50));
        const monthlyResult = await mileageService.getMonthlyMileage(
            testPlate,
            today.getFullYear(),
            today.getMonth() + 1
        );
        console.log('Resultado:', JSON.stringify(monthlyResult, null, 2));
        console.log('✅ Teste 5 concluído\n');

        // Aguarda 2s
        await sleep(2000);

        // Teste 6: Buscar totais da frota
        console.log('📝 Teste 6: Buscar totais da frota do dia');
        console.log('─'.repeat(50));
        const fleetResult = await mileageService.getFleetDailyTotal(testDate);
        console.log('Resultado:', JSON.stringify(fleetResult, null, 2));
        console.log('✅ Teste 6 concluído\n');

        console.log('\n╔═══════════════════════════════════════════════════╗');
        console.log('║           TODOS OS TESTES CONCLUÍDOS! ✅          ║');
        console.log('╚═══════════════════════════════════════════════════╝\n');

    } catch (error) {
        console.error('\n❌ ERRO NOS TESTES:', error);
        console.error('Stack:', error.stack);
    }

    // Encerra o processo
    process.exit(0);
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Executa os testes
runTests();
