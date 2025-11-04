const { exec, spawn } = require('child_process');

console.log('🔄 Parando proxy antigo...');

// Mata processos na porta 8888
exec('netstat -ano | findstr :8888 | findstr LISTENING', (err, stdout) => {
    if (stdout) {
        const lines = stdout.trim().split('\n');
        lines.forEach(line => {
            const parts = line.trim().split(/\s+/);
            const pid = parts[parts.length - 1];
            console.log(`   Matando PID ${pid}...`);
            exec(`taskkill /F /PID ${pid}`, () => {});
        });
    }

    // Aguarda 2 segundos e inicia novo proxy
    setTimeout(() => {
        console.log('');
        console.log('✅ Iniciando NOVO proxy com CORS corrigido...');
        console.log('');

        const proxy = spawn('node', ['ituran-proxy.js'], {
            detached: true,
            stdio: 'inherit'
        });

        proxy.unref();

        setTimeout(() => {
            console.log('');
            console.log('========================================');
            console.log('  ✅ PROXY REINICIADO!');
            console.log('========================================');
            console.log('');
            console.log('👉 Agora recarregue o navegador com Ctrl+F5');
            console.log('');
            process.exit(0);
        }, 2000);

    }, 2000);
});
