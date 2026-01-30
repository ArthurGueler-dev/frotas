#!/usr/bin/env node

/**
 * Script de Sincronização de Quilometragem via Cron
 *
 * Este script deve ser executado via cron job no servidor para
 * sincronizar automaticamente a quilometragem de todos os veículos
 * nos horários programados, SEM depender de navegador aberto.
 *
 * Uso:
 *   node sync-mileage-cron.js
 *
 * Cron jobs recomendados (adicionar ao crontab):
 *   0 8 * * *   cd /root/frotas && node sync-mileage-cron.js >> logs/sync-cron.log 2>&1
 *   0 12 * * *  cd /root/frotas && node sync-mileage-cron.js >> logs/sync-cron.log 2>&1
 *   0 18 * * *  cd /root/frotas && node sync-mileage-cron.js >> logs/sync-cron.log 2>&1
 *   55 23 * * * cd /root/frotas && node sync-mileage-cron.js >> logs/sync-cron.log 2>&1
 */

const axios = require('axios');

// Configuração
const API_URL = process.env.API_URL || 'http://localhost:5000';
const SYNC_ENDPOINT = `${API_URL}/api/mileage/sync`;
const TIMEOUT = 60 * 60 * 1000; // 60 minutos

// Cores para log (opcional, funciona no terminal)
const colors = {
    reset: '\x1b[0m',
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    blue: '\x1b[36m'
};

function log(message, color = 'reset') {
    const timestamp = new Date().toISOString();
    console.log(`${colors[color]}[${timestamp}] ${message}${colors.reset}`);
}

async function syncMileage() {
    log('🤖 ════════════════════════════════════════════════════', 'blue');
    log('🤖 SINCRONIZAÇÃO AUTOMÁTICA DE QUILOMETRAGEM (CRON)', 'blue');
    log('🤖 ════════════════════════════════════════════════════', 'blue');

    try {
        // Calcular data de hoje
        const today = new Date().toLocaleDateString('en-CA', { timeZone: 'America/Sao_Paulo' });
        log(`📅 Data alvo: ${today}`, 'blue');

        // Chamar endpoint de sincronização
        log(`🔄 Chamando API: ${SYNC_ENDPOINT}`, 'blue');

        const startTime = Date.now();
        const response = await axios.post(SYNC_ENDPOINT, {
            date: today
            // plates não enviado = sincroniza TODOS os veículos
        }, {
            timeout: TIMEOUT,
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'Cron-Sync/1.0'
            }
        });

        const duration = Math.round((Date.now() - startTime) / 1000);

        if (response.data.success) {
            const { results } = response.data;

            log('✅ Sincronização concluída com sucesso!', 'green');
            log(`   Total de veículos: ${results.total}`, 'green');
            log(`   Sucessos: ${results.success}`, 'green');
            log(`   Falhas: ${results.failed}`, results.failed > 0 ? 'yellow' : 'green');
            log(`   Tempo total: ${duration}s`, 'green');

            // Mostrar detalhes das falhas (se houver)
            if (results.failed > 0 && results.details) {
                log('⚠️ Detalhes das falhas:', 'yellow');
                results.details
                    .filter(d => !d.success)
                    .forEach(detail => {
                        log(`   ❌ ${detail.plate}: ${detail.error}`, 'yellow');
                    });
            }

            // Calcular total de KM sincronizado
            if (results.details) {
                const totalKm = results.details
                    .filter(d => d.success && d.km_driven)
                    .reduce((sum, d) => sum + parseFloat(d.km_driven || 0), 0);

                log(`📊 Total de KM sincronizados: ${totalKm.toFixed(2)} km`, 'green');
            }

            log('🤖 ════════════════════════════════════════════════════', 'blue');

            // Exit code 0 = sucesso
            process.exit(0);

        } else {
            throw new Error(response.data.error || 'Resposta de erro da API');
        }

    } catch (error) {
        log('❌ ERRO na sincronização:', 'red');

        if (error.response) {
            // Erro HTTP
            log(`   Status: ${error.response.status}`, 'red');
            log(`   Mensagem: ${JSON.stringify(error.response.data)}`, 'red');
        } else if (error.request) {
            // Sem resposta
            log(`   Sem resposta do servidor`, 'red');
            log(`   URL: ${SYNC_ENDPOINT}`, 'red');
        } else {
            // Outro erro
            log(`   ${error.message}`, 'red');
        }

        log('🤖 ════════════════════════════════════════════════════', 'blue');

        // Exit code 1 = erro
        process.exit(1);
    }
}

// Executar sincronização
syncMileage();
