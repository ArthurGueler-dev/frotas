// Proxy Server para API do Ituran
// Resolve problemas de CORS fazendo requisições server-side

const http = require('http');
const https = require('https');
const url = require('url');

const PORT = 8888;
const ITURAN_BASE_URL = 'https://iweb.ituran.com.br';

const server = http.createServer((req, res) => {
    // Habilita CORS para todas as origens
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Cache-Control, Accept');

    // Responde OPTIONS (preflight)
    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    // Parse da URL
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;

    // Apenas aceita requisições para /api/ituran/*
    if (!pathname.startsWith('/api/ituran/')) {
        res.writeHead(404, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Endpoint não encontrado' }));
        return;
    }

    // Remove /api/ituran do path
    const ituranPath = pathname.replace('/api/ituran', '');
    const queryString = parsedUrl.search || '';
    const ituranUrl = `${ITURAN_BASE_URL}${ituranPath}${queryString}`;

    console.log(`🔄 Proxy: ${req.method} ${ituranUrl}`);

    // Faz requisição para Ituran
    https.get(ituranUrl, (ituranRes) => {
        let data = '';

        // Recebe dados
        ituranRes.on('data', (chunk) => {
            data += chunk;
        });

        // Quando terminar
        ituranRes.on('end', () => {
            // Retorna resposta com CORS habilitado
            res.writeHead(ituranRes.statusCode, {
                'Content-Type': ituranRes.headers['content-type'] || 'text/xml',
                'Access-Control-Allow-Origin': '*'
            });
            res.end(data);

            console.log(`✅ Proxy: ${ituranRes.statusCode} - ${data.length} bytes`);
        });

    }).on('error', (error) => {
        console.error('❌ Erro no proxy:', error.message);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: error.message }));
    });
});

server.listen(PORT, () => {
    console.log(`🚀 Proxy Ituran rodando em http://localhost:${PORT}`);
    console.log(`📡 Redirecionando requisições para ${ITURAN_BASE_URL}`);
    console.log('');
    console.log('Exemplo de uso:');
    console.log(`  http://localhost:${PORT}/api/ituran/ituranwebservice3/Service3.asmx/GetAllPlatformsData?...`);
    console.log('');
});
