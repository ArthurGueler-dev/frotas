// Web Worker para sincronização de KM em background
// Permite que a sincronização continue mesmo quando o usuário troca de aba
// Usa a API do servidor Node.js (/api/ituran/full-report) para evitar CORS

console.log('🔧 [WORKER] Web Worker iniciado');

self.addEventListener('message', async (event) => {
    console.log('📨 [WORKER] Mensagem recebida:', event.data);

    const { type, data } = event.data;

    switch (type) {
        case 'START_SYNC':
            console.log(`🚀 [WORKER] Iniciando processamento de ${data.vehicles?.length || 0} veículos`);
            console.log(`📍 [WORKER] Base selecionada: ${data.base || 'Todas'}`);
            console.log(`📊 [WORKER] Iniciando do índice: ${data.startIndex || 0}`);
            await processVehicles(data.vehicles, data.startIndex, data.base);
            break;
        case 'STOP_SYNC':
            console.log('🛑 [WORKER] Parando sincronização');
            self.close();
            break;
        default:
            console.warn('⚠️ [WORKER] Tipo de mensagem desconhecido:', type);
    }
});

async function processVehicles(vehicles, startIndex = 0, base = '') {
    console.log(`📊 [WORKER] processVehicles iniciado`);
    console.log(`   - Total de veículos: ${vehicles?.length || 0}`);
    console.log(`   - Índice inicial: ${startIndex}`);
    console.log(`   - Base: ${base || 'Todas'}`);

    if (!vehicles || vehicles.length === 0) {
        console.error('❌ [WORKER] Nenhum veículo para processar!');
        self.postMessage({
            type: 'COMPLETE',
            data: { totalToday: 0, totalYesterday: 0, totalMonth: 0 }
        });
        return;
    }

    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    let totalToday = 0;
    let totalYesterday = 0;
    let totalMonth = 0;

    console.log(`🔄 [WORKER] Processando ${vehicles.length} veículos...`);

    for (let i = startIndex; i < vehicles.length; i++) {
        const vehicle = vehicles[i];
        console.log(`🚗 [WORKER] [${i + 1}/${vehicles.length}] Processando: ${vehicle.plate}`);

        try {
            // Buscar dados via API do servidor
            const reportToday = await fetchVehicleReport(vehicle.plate, today, today);
            const reportYesterday = await fetchVehicleReport(vehicle.plate, yesterday, yesterday);
            const reportMonth = await fetchVehicleReport(vehicle.plate, firstDayOfMonth, today);

            const kmToday = reportToday.kmRodado || 0;
            const kmYesterday = reportYesterday.kmRodado || 0;
            const kmMonth = reportMonth.kmRodado || 0;

            totalToday += kmToday;
            totalYesterday += kmYesterday;
            totalMonth += kmMonth;

            // Enviar progresso para main thread
            self.postMessage({
                type: 'PROGRESS',
                data: {
                    index: i,
                    total: vehicles.length,
                    plate: vehicle.plate,
                    kmToday,
                    kmYesterday,
                    kmMonth,
                    totalToday,
                    totalYesterday,
                    totalMonth,
                    reportToday
                }
            });

            // Pausa de 500ms entre veículos (não sobrecarregar API Ituran)
            await sleep(500);

        } catch (error) {
            self.postMessage({
                type: 'ERROR',
                data: {
                    plate: vehicle.plate,
                    error: error.message
                }
            });
        }
    }

    self.postMessage({
        type: 'COMPLETE',
        data: {
            totalToday,
            totalYesterday,
            totalMonth
        }
    });
}

async function fetchVehicleReport(plate, startDate, endDate) {
    try {
        const start = startDate.toISOString().split('T')[0] + ' 00:00:00';
        const end = endDate.toISOString().split('T')[0] + ' 23:59:59';

        console.log(`🔍 [WORKER] Buscando relatório: ${plate} de ${start} até ${end}`);

        // Web Workers precisam usar URL absoluta (self.location.origin)
        const apiUrl = `${self.location.origin}/api/ituran/full-report?plate=${encodeURIComponent(plate)}&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;

        const response = await fetch(apiUrl);
        const data = await response.json();

        if (!data.success || !data.records || data.records.length === 0) {
            console.warn(`⚠️ [WORKER] Relatório sem sucesso para ${plate}`);
            return { success: false, kmRodado: 0 };
        }

        const records = data.records;
        const validRecords = records.filter(r => r.odometer && r.odometer > 0);

        if (validRecords.length === 0) {
            console.warn(`⚠️ [WORKER] Nenhum registro válido para ${plate}`);
            return { success: false, kmRodado: 0 };
        }

        const startOdometer = Math.min(...validRecords.map(r => r.odometer));
        const endOdometer = Math.max(...validRecords.map(r => r.odometer));
        const kmRodado = endOdometer - startOdometer;

        console.log(`✅ [WORKER] ${plate}: ${kmRodado} km rodados (${startOdometer} → ${endOdometer})`);

        return {
            success: true,
            kmRodado,
            startOdometer,
            endOdometer
        };
    } catch (error) {
        console.error(`❌ [WORKER] Erro ao buscar relatório para ${plate}:`, error);
        return { success: false, kmRodado: 0 };
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
