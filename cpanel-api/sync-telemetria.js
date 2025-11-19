#!/usr/bin/env node
/**
 * Script de Sincronização de Telemetria - cPanel
 * Executa a sincronização de dados da API Ituran para o MySQL
 *
 * Upload para: /home/f137049/public_html/api/sync-telemetria.js
 */

const mysql = require('mysql2/promise');
const https = require('https');
const { DOMParser } = require('xmldom');

// Configuração do banco de dados
const dbConfig = {
    host: '187.49.226.10',
    port: 3306,
    user: 'f137049_tool',
    password: 'In9@1234qwer',
    database: 'f137049_in9aut',
    charset: 'utf8mb4'
};

// Configuração da API Ituran
const ituranConfig = {
    host: 'iweb.ituran.com.br',
    username: 'api@i9tecnologia',
    password: 'Api@In9Eng'
};

/**
 * Faz requisição para a API Ituran
 */
async function getIturanData(placa, dataInicio, dataFim) {
    return new Promise((resolve, reject) => {
        const params = new URLSearchParams({
            UserName: ituranConfig.username,
            Password: ituranConfig.password,
            Plate: placa,
            Start: dataInicio.toISOString().replace('T', ' ').substring(0, 19),
            End: dataFim.toISOString().replace('T', ' ').substring(0, 19),
            UAID: '0',
            MaxNumberOfRecords: '10000'
        });

        const options = {
            hostname: ituranConfig.host,
            path: `/ituranwebservice3/Service3.asmx/GetFullReport?${params}`,
            method: 'GET',
            headers: {
                'Accept': 'application/xml'
            },
            timeout: 120000
        };

        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    const parser = new DOMParser();
                    const xmlDoc = parser.parseFromString(data, 'text/xml');

                    const getXmlValue = (tagName, defaultValue = '') => {
                        const element = xmlDoc.getElementsByTagName(tagName)[0];
                        return element ? element.textContent.trim() : defaultValue;
                    };

                    const startOdo = parseFloat(getXmlValue('StartOdometer', '0')) || 0;
                    const endOdo = parseFloat(getXmlValue('EndOdometer', '0')) || 0;
                    const tempoIgnicao = parseInt(getXmlValue('IgnitionOnTime', '0')) || 0;
                    const avgSpeed = parseFloat(getXmlValue('AvgSpeed', '0')) || 0;
                    const maxSpeed = parseFloat(getXmlValue('MaxSpeed', '0')) || 0;

                    const routes = xmlDoc.getElementsByTagName('Route');
                    const routeData = [];

                    for (let i = 0; i < routes.length; i++) {
                        const route = routes[i];
                        const lat = route.getElementsByTagName('Latitude')[0]?.textContent || null;
                        const lng = route.getElementsByTagName('Longitude')[0]?.textContent || null;
                        if (lat && lng) {
                            routeData.push({ latitude: parseFloat(lat), longitude: parseFloat(lng) });
                        }
                    }

                    resolve({
                        success: true,
                        startOdometer: startOdo,
                        endOdometer: endOdo,
                        kmDriven: Math.max(0, endOdo - startOdo),
                        tempoIgnicao: tempoIgnicao,
                        avgSpeed: avgSpeed,
                        maxSpeed: maxSpeed,
                        route: routeData,
                        totalRecords: routeData.length
                    });
                } catch (error) {
                    reject(new Error(`Erro ao parsear XML: ${error.message}`));
                }
            });
        });

        req.on('error', reject);
        req.on('timeout', () => reject(new Error('Timeout na requisição')));
        req.end();
    });
}

/**
 * Sincroniza telemetria de um veículo
 */
async function sincronizarVeiculo(connection, placa) {
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const agora = new Date();

        console.log(`[${placa}] Buscando dados da API Ituran...`);

        const dataInicio = new Date(`${hoje}T00:00:00`);
        const report = await getIturanData(placa, dataInicio, agora);

        if (!report.success) {
            return { success: false, error: 'API retornou erro', placa };
        }

        console.log(`[${placa}] API OK - KM: ${report.startOdometer} → ${report.endOdometer}`);

        // Verifica se já existe registro
        const [existente] = await connection.query(
            'SELECT id FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?',
            [placa, hoje]
        );

        const primeiraRota = report.route[0] || {};
        const ultimaRota = report.route[report.route.length - 1] || {};
        const kmRodado = report.kmDriven;

        if (existente.length === 0) {
            // INSERT
            await connection.query(`
                INSERT INTO Telemetria_Diaria (
                    LicensePlate, data,
                    km_inicial, km_final, km_rodado,
                    tempo_ligado_minutos,
                    velocidade_media, velocidade_maxima,
                    status_atual,
                    lat_inicio, lng_inicio,
                    lat_fim, lng_fim,
                    total_pontos_gps,
                    fonte_api
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            `, [
                placa, hoje,
                report.startOdometer, report.endOdometer, kmRodado,
                report.tempoIgnicao,
                report.avgSpeed, report.maxSpeed,
                'Ligado',
                primeiraRota.latitude || null, primeiraRota.longitude || null,
                ultimaRota.latitude || null, ultimaRota.longitude || null,
                report.totalRecords,
                'Ituran'
            ]);
            console.log(`[${placa}] INSERT realizado`);
        } else {
            // UPDATE
            await connection.query(`
                UPDATE Telemetria_Diaria SET
                    km_final = ?, km_rodado = ?,
                    tempo_ligado_minutos = ?,
                    velocidade_media = ?, velocidade_maxima = ?,
                    status_atual = ?,
                    lat_fim = ?, lng_fim = ?,
                    total_pontos_gps = ?
                WHERE LicensePlate = ? AND data = ?
            `, [
                report.endOdometer, kmRodado,
                report.tempoIgnicao,
                report.avgSpeed, report.maxSpeed,
                'Ligado',
                ultimaRota.latitude || null, ultimaRota.longitude || null,
                report.totalRecords,
                placa, hoje
            ]);
            console.log(`[${placa}] UPDATE realizado`);
        }

        return {
            success: true,
            placa,
            kmInicial: report.startOdometer,
            kmFinal: report.endOdometer,
            kmRodado: kmRodado
        };

    } catch (error) {
        console.error(`[${placa}] ERRO:`, error.message);
        return { success: false, error: error.message, placa };
    }
}

/**
 * Função principal
 */
async function main() {
    const connection = await mysql.createConnection(dbConfig);

    try {
        console.log('=== INICIANDO SINCRONIZAÇÃO ===');

        // Busca veículos ativos
        const [veiculos] = await connection.query(`
            SELECT DISTINCT LicensePlate
            FROM Vehicles
            WHERE LicensePlate IS NOT NULL AND LicensePlate != ''
            ORDER BY LicensePlate
        `);

        console.log(`Total de veículos: ${veiculos.length}`);

        const resultados = [];
        let sucessos = 0;
        let falhas = 0;

        for (const veiculo of veiculos) {
            const resultado = await sincronizarVeiculo(connection, veiculo.LicensePlate);
            resultados.push(resultado);

            if (resultado.success) {
                sucessos++;
            } else {
                falhas++;
            }

            // Aguarda 1s entre requisições
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        console.log(`=== CONCLUÍDO: ${sucessos} sucessos, ${falhas} falhas ===`);

        // Retorna JSON
        console.log(JSON.stringify({
            success: true,
            total: veiculos.length,
            sucessos,
            falhas,
            resultados
        }));

    } catch (error) {
        console.error('ERRO FATAL:', error);
        console.log(JSON.stringify({ success: false, error: error.message }));
    } finally {
        await connection.end();
    }
}

main();
