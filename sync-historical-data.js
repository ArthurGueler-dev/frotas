#!/usr/bin/env node
/**
 * Script para sincronizar dados históricos de quilometragem
 * Busca dados da API Ituran e salva via API do cPanel
 */

const fetch = require('node-fetch');
const IturanService = require('./ituran-service.js');

// Configurações
const CPANEL_API_URL = 'https://floripa.in9automacao.com.br/telemetria-diaria-api.php';
const ituranService = new IturanService();

// Lista de veículos (simplificada - em produção, buscar do banco)
const VEHICLES_FILE = './vehicle-list.json';
let vehicles = [];

try {
    vehicles = require(VEHICLES_FILE);
    console.log(`✅ Carregados ${vehicles.length} veículos`);
} catch (error) {
    console.error('❌ Erro ao carregar lista de veículos:', error.message);
    console.log('💡 Buscando veículos da API...');
}

/**
 * Busca dados de um veículo para uma data específica
 */
async function fetchVehicleData(plate, date) {
    try {
        const startDate = new Date(date + ' 00:00:00');
        const endDate = new Date(date + ' 23:59:59');

        console.log(`   📡 Buscando dados de ${plate} para ${date}...`);

        const report = await ituranService.getKilometerReport(plate, startDate, endDate);

        if (!report.success || report.kmRodados === 0) {
            console.log(`   ⚠️  ${plate}: Sem dados`);
            return null;
        }

        console.log(`   ✅ ${plate}: ${report.kmRodados} km`);
        return {
            plate,
            date,
            kmInicial: report.kmInicial,
            kmFinal: report.kmFinal,
            kmRodado: report.kmRodados,
            tempoLigado: report.tempoIgnicao || 0
        };
    } catch (error) {
        console.error(`   ❌ ${plate}: ${error.message}`);
        return null;
    }
}

/**
 * Salva dados no banco via API do cPanel
 */
async function saveToDatabase(vehicleData) {
    try {
        const response = await fetch(CPANEL_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                licensePlate: vehicleData.plate,
                date: vehicleData.date,
                kmInicial: vehicleData.kmInicial,
                kmFinal: vehicleData.kmFinal,
                kmRodado: vehicleData.kmRodado,
                tempoLigado: vehicleData.tempoLigado
            })
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Erro ao salvar');
        }

        return true;
    } catch (error) {
        console.error(`   ❌ Erro ao salvar ${vehicleData.plate}:`, error.message);
        return false;
    }
}

/**
 * Sincroniza todos os veículos para uma data específica
 */
async function syncDate(date) {
    console.log(`\n${'═'.repeat(60)}`);
    console.log(`📅 Sincronizando data: ${date}`);
    console.log(`${'═'.repeat(60)}`);

    if (vehicles.length === 0) {
        console.log('⚠️  Lista de veículos vazia - buscando da API...');
        // Em produção, buscar lista de veículos da API
        console.error('❌ Não foi possível buscar lista de veículos');
        return;
    }

    let saved = 0;
    let errors = 0;

    for (let i = 0; i < vehicles.length; i++) {
        const vehicle = vehicles[i];
        console.log(`\n[${i + 1}/${vehicles.length}] Processando ${vehicle.LicensePlate || vehicle.plate}...`);

        const data = await fetchVehicleData(vehicle.LicensePlate || vehicle.plate, date);

        if (data) {
            const success = await saveToDatabase(data);
            if (success) {
                saved++;
            } else {
                errors++;
            }
        }

        // Pausa de 500ms entre veículos para não sobrecarregar a API
        await new Promise(resolve => setTimeout(resolve, 500));
    }

    console.log(`\n${'═'.repeat(60)}`);
    console.log(`✅ Sincronização de ${date} concluída!`);
    console.log(`   📊 Salvos: ${saved} | Erros: ${errors} | Sem dados: ${vehicles.length - saved - errors}`);
    console.log(`${'═'.repeat(60)}\n`);
}

/**
 * Sincroniza múltiplas datas
 */
async function syncMultipleDates(dates) {
    console.log(`\n${'═'.repeat(60)}`);
    console.log(`🚀 SINCRONIZAÇÃO HISTÓRICA DE DADOS`);
    console.log(`${'═'.repeat(60)}`);
    console.log(`📅 Datas a sincronizar: ${dates.join(', ')}`);
    console.log(`🚗 Total de veículos: ${vehicles.length}`);
    console.log(`${'═'.repeat(60)}\n`);

    for (const date of dates) {
        await syncDate(date);
    }

    console.log(`\n${'🎉'.repeat(30)}`);
    console.log(`✅ SINCRONIZAÇÃO COMPLETA!`);
    console.log(`${'🎉'.repeat(30)}\n`);
}

// Executar sincronização
const datesToSync = process.argv.slice(2);

if (datesToSync.length === 0) {
    console.log('❌ Erro: Nenhuma data especificada');
    console.log('💡 Uso: node sync-historical-data.js 2025-12-01 2025-12-03 2025-12-06');
    process.exit(1);
}

syncMultipleDates(datesToSync).catch(error => {
    console.error('❌ Erro fatal:', error);
    process.exit(1);
});
