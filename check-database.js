const mysql = require('mysql2/promise');

async function checkDatabase() {
    try {
        const connection = await mysql.createConnection({
            host: '187.49.226.10',
            port: 3306,
            user: 'f137049_tool',
            password: 'In9@1234qwer',
            database: 'f137049_in9aut'
        });

        console.log('✅ Conectado ao banco de dados!\n');

        // Listar todas as tabelas
        console.log('📋 Tabelas existentes:');
        const [tables] = await connection.query('SHOW TABLES');
        tables.forEach((table, index) => {
            console.log(`${index + 1}. ${Object.values(table)[0]}`);
        });

        console.log('\n📊 Verificando estrutura da tabela Drivers:');
        const [columns] = await connection.query('DESCRIBE Drivers');
        console.log(columns);

        await connection.end();
    } catch (error) {
        console.error('❌ Erro:', error.message);
    }
}

checkDatabase();
