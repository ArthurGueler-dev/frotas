/**
 * Proxy para enviar mensagens via Evolution API
 * Roda na VPS que tem acesso à rede local onde está a Evolution API
 */

const express = require('express');
const axios = require('axios');
const mysql = require('mysql2/promise');

const app = express();
app.use(express.json());

// Configuração Evolution API (via túnel SSH reverso)
const EVOLUTION_API_URL = 'http://localhost:60010';
const EVOLUTION_API_KEY = 'b0faf368ea81f396469c0bd26fa07bf9d6076117cd3b6fab6e0ca6004b3d710e';
const EVOLUTION_INSTANCE = 'Thiago Costa';

// Configuração do banco de dados
const DB_CONFIG = {
    host: '187.49.226.10',
    port: 3306,
    user: 'f137049_tool',
    password: 'In9@1234qwer',
    database: 'f137049_in9aut',
    connectTimeout: 10000
};

// CORS para aceitar requests do frontend
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    next();
});

/**
 * POST /enviar-rota-whatsapp
 * Body: { rota_id: number, telefone: string }
 */
app.post('/enviar-rota-whatsapp', async (req, res) => {
    try {
        const { rota_id, telefone } = req.body;

        if (!rota_id || !telefone) {
            return res.status(400).json({
                success: false,
                error: 'rota_id e telefone são obrigatórios'
            });
        }

        console.log(`📱 Enviando rota #${rota_id} para ${telefone}...`);

        // 1. Buscar dados da rota via API PHP do cPanel (evita timeout do MySQL)
        const rotaResponse = await axios.get(`https://floripa.in9automacao.com.br/get-rota.php?id=${rota_id}`, {
            timeout: 10000
        });

        if (!rotaResponse.data || !rotaResponse.data.success) {
            return res.status(404).json({
                success: false,
                error: 'Rota não encontrada',
                rota_id
            });
        }

        const rota = rotaResponse.data.rota;
        const locais = JSON.parse(rota.sequencia_locais_json || '[]');

        console.log(`✅ Rota encontrada: Rota ID #${rota_id}, Bloco #${rota.bloco_id}, ${locais.length} locais`);
        console.log(`📊 Primeiros locais:`, locais.slice(0, 3).map(l => l.nome));

        // 2. Construir mensagem
        const mensagem = construirMensagemRota(rota, locais);

        // 3. Enviar via Evolution API
        const instanceEncoded = encodeURIComponent(EVOLUTION_INSTANCE);
        const url = `${EVOLUTION_API_URL}/message/sendText/${instanceEncoded}`;

        console.log(`📤 Enviando para Evolution API: ${url}`);

        const response = await axios.post(url, {
            number: telefone.replace(/\D/g, ''),
            text: mensagem
        }, {
            headers: {
                'Content-Type': 'application/json',
                'apikey': EVOLUTION_API_KEY
            },
            timeout: 30000
        });

        console.log(`✅ Mensagem enviada! Status: ${response.status}`);

        // 4. Atualizar status da rota via API PHP
        await axios.post('https://floripa.in9automacao.com.br/update-rota-status.php', {
            rota_id,
            status: 'enviada',
            telefone_destino: telefone
        }, { timeout: 5000 }).catch(err => {
            console.warn('⚠️ Falha ao atualizar status da rota:', err.message);
        });

        res.json({
            success: true,
            message: 'Rota enviada com sucesso via WhatsApp',
            rota_id,
            telefone,
            total_locais: locais.length
        });

    } catch (error) {
        console.error('❌ Erro ao enviar WhatsApp:', error);
        console.error('Stack:', error.stack);
        if (error.response) {
            console.error('Response data:', error.response.data);
            console.error('Response status:', error.response.status);
        }
        res.status(500).json({
            success: false,
            error: 'Erro ao enviar mensagem',
            details: error.message,
            response: error.response?.data
        });
    }
});

/**
 * Construir mensagem formatada para WhatsApp
 */
function construirMensagemRota(rota, locais) {
    const blocoId = rota.bloco_id || 'N/A';
    const distancia = parseFloat(rota.distancia_total_km || 0).toFixed(1).replace('.', ',');
    const tempo = rota.tempo_estimado_min || 0;

    // Deduplicate locations (mesmas coordenadas = mesmo local)
    const locaisUnicos = [];
    const seenCoords = new Set();

    locais.forEach(local => {
        const lat = parseFloat(local.lat || 0).toFixed(6);
        const lon = parseFloat(local.lon || 0).toFixed(6);
        const coordKey = `${lat},${lon}`;

        if (!seenCoords.has(coordKey)) {
            seenCoords.add(coordKey);
            locaisUnicos.push(local);
        }
    });

    const numLocais = locaisUnicos.length;

    let mensagem = "🚗 *Rota de Manutenção - Hoje*\n\n";
    mensagem += "Olá! Aqui está a sua rota otimizada para hoje. ";
    mensagem += "Siga exatamente essa ordem para economizar tempo e combustível.\n\n";

    mensagem += "*Partida e retorno:* Base da Empresa\n";
    mensagem += "(Rua Francisco Sousa dos Santos, 320 - Jardim Limoeiro, Serra - ES)\n\n";

    mensagem += "*Ordem das visitas:*\n\n";

    locaisUnicos.forEach((local, index) => {
        const numero = index + 1;
        const nome = local.nome || `Local ${numero}`;
        const endereco = local.endereco || 'Endereço não informado';

        mensagem += `${numero}️⃣ Local: ${nome}\n`;
        mensagem += `   Endereço: ${endereco}\n\n`;
    });

    mensagem += `➡️ *Distância total estimada:* ${distancia} km\n`;
    mensagem += `⏱ *Tempo aproximado:* ${tempo} minutos (sem trânsito)\n\n`;

    mensagem += "🔗 *Clique no link abaixo para abrir a rota completa no Google Maps (já na ordem certa):*\n\n";
    mensagem += `${rota.link_google_maps}\n\n`;

    mensagem += "Basta clicar em \"Iniciar\" no Google Maps e seguir a navegação ponto a ponto.\n\n";
    mensagem += "Qualquer dúvida, me avise!\n";
    mensagem += "Boa rota e bom trabalho! 👍";

    return mensagem;
}

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', service: 'whatsapp-proxy' });
});

const PORT = 3001;
app.listen(PORT, () => {
    console.log(`🚀 WhatsApp Proxy rodando na porta ${PORT}`);
    console.log(`📱 Evolution API: ${EVOLUTION_API_URL}`);
});
