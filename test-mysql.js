const mysql = require('mysql2/promise');

async function testConnection() {
    const credentials = [
        { user: 'root', password: '' },
        { user: 'root', password: 'root' },
        { user: 'root', password: '123456' },
        { user: 'root', password: 'password' },
        { user: 'root', password: 'In9@1234qwer' },
        { user: 'f137049_tool', password: 'In9@1234qwer' }
    ];

    console.log('🔍 Testando conexões MySQL...\n');

    for (const cred of credentials) {
        try {
            const connection = await mysql.createConnection({
                host: 'localhost',
                user: cred.user,
                password: cred.password
            });

            console.log(`✅ SUCESSO! user: '${cred.user}', password: '${cred.password}'`);

            // Listar bancos de dados
            const [databases] = await connection.query('SHOW DATABASES');
            console.log('   Bancos disponíveis:', databases.map(db => Object.values(db)[0]).join(', '));

            // Verificar se o banco f137049_in9aut existe
            const dbExists = databases.some(db => Object.values(db)[0] === 'f137049_in9aut');
            if (dbExists) {
                console.log('   ✅ Banco f137049_in9aut encontrado!');

                // Testar consulta na tabela Drivers
                await connection.changeUser({ database: 'f137049_in9aut' });
                const [drivers] = await connection.query('SELECT COUNT(*) as count FROM Drivers');
                console.log(`   ✅ Tabela Drivers encontrada! Total de motoristas: ${drivers[0].count}`);
            } else {
                console.log('   ⚠️  Banco f137049_in9aut NÃO encontrado');
            }

            await connection.end();
            console.log('');

        } catch (error) {
            console.log(`❌ FALHOU: user: '${cred.user}', password: '${cred.password}' - ${error.message}`);
        }
    }
}

testConnection();
