// Script de teste para endpoints de quilometragem

const axios = require('axios');

const BASE_URL = 'http://localhost:5000';

async function testarEndpoints() {
    console.log('\n=== TESTE DOS ENDPOINTS DE QUILOMETRAGEM ===\n');

    try {
        // 1. Atualizar dados de um veículo específico (ontem)
        console.log('1. Testando atualização de dados de um veículo (ontem)...');
        const ontem = new Date();
        ontem.setDate(ontem.getDate() - 1);
        const dataOntem = ontem.toISOString().split('T')[0];

        const resultAtualizar = await axios.post(`${BASE_URL}/api/quilometragem/atualizar/SFT4I72`, {
            data: dataOntem
        });

        if (resultAtualizar.data.success) {
            console.log('✅ Dados atualizados com sucesso!');
            console.log('   Dados:', resultAtualizar.data.data);
        } else {
            console.log('❌ Erro:', resultAtualizar.data.error);
        }

        // 2. Buscar quilometragem do dia que acabamos de inserir
        console.log(`\n2. Buscando quilometragem de SFT4I72 em ${dataOntem}...`);
        const resultBuscar = await axios.get(`${BASE_URL}/api/quilometragem/diaria/SFT4I72/${dataOntem}`);

        if (resultBuscar.data.success && resultBuscar.data.data) {
            console.log('✅ Dados encontrados!');
            console.log('   Data:', resultBuscar.data.data.data);
            console.log('   KM Inicial:', resultBuscar.data.data.km_inicial);
            console.log('   KM Final:', resultBuscar.data.data.km_final);
            console.log('   KM Rodados:', resultBuscar.data.data.km_rodados);
        } else {
            console.log('❌ Dados não encontrados');
        }

        // 3. Buscar período (últimos 7 dias)
        console.log('\n3. Buscando quilometragem dos últimos 7 dias...');
        const dataInicio = new Date();
        dataInicio.setDate(dataInicio.getDate() - 7);
        const dataFim = new Date();

        const resultPeriodo = await axios.get(
            `${BASE_URL}/api/quilometragem/periodo/SFT4I72?dataInicio=${dataInicio.toISOString().split('T')[0]}&dataFim=${dataFim.toISOString().split('T')[0]}`
        );

        if (resultPeriodo.data.success) {
            console.log(`✅ Encontrados ${resultPeriodo.data.data.length} registros`);
            resultPeriodo.data.data.forEach(reg => {
                console.log(`   ${reg.data}: ${reg.km_rodados} km`);
            });
        } else {
            console.log('❌ Erro ao buscar período');
        }

        // 4. Buscar dados mensais
        console.log('\n4. Buscando dados mensais...');
        const hoje = new Date();
        const ano = hoje.getFullYear();
        const mes = hoje.getMonth() + 1;

        const resultMensal = await axios.get(`${BASE_URL}/api/quilometragem/mensal/SFT4I72/${ano}/${mes}`);

        if (resultMensal.data.success && resultMensal.data.data) {
            console.log('✅ Dados mensais encontrados!');
            console.log(`   Ano/Mês: ${resultMensal.data.data.ano}/${resultMensal.data.data.mes}`);
            console.log(`   KM Total: ${resultMensal.data.data.km_total} km`);
            console.log(`   Dias rodados: ${resultMensal.data.data.dias_rodados}`);
        } else {
            console.log('⚠️  Dados mensais ainda não foram calculados');
        }

        // 5. Buscar estatísticas
        console.log('\n5. Buscando estatísticas do mês...');
        const resultEstatisticas = await axios.get(`${BASE_URL}/api/quilometragem/estatisticas/SFT4I72?periodo=mes`);

        if (resultEstatisticas.data.success) {
            console.log('✅ Estatísticas calculadas!');
            console.log(`   Total KM: ${resultEstatisticas.data.data.totalKm} km`);
            console.log(`   Total Dias: ${resultEstatisticas.data.data.totalDias}`);
            console.log(`   Média KM/dia: ${resultEstatisticas.data.data.mediaKmDia} km`);
        } else {
            console.log('❌ Erro ao calcular estatísticas');
        }

        console.log('\n=== TODOS OS TESTES CONCLUÍDOS ===\n');

    } catch (error) {
        console.error('❌ Erro durante os testes:', error.message);
        if (error.response) {
            console.error('   Resposta:', error.response.data);
        }
    }
}

testarEndpoints();
