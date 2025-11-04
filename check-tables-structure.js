const mysql = require('mysql2/promise');

async function checkTablesStructure() {
    try {
        const connection = await mysql.createConnection({
            host: '187.49.226.10',
            port: 3306,
            user: 'f137049_tool',
            password: 'In9@1234qwer',
            database: 'f137049_in9aut'
        });

        console.log('✅ Conectado ao banco!\n');

        // Verificar tabela Vehicles
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('📊 Estrutura da tabela VEHICLES:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        const [vehicleColumns] = await connection.query('DESCRIBE Vehicles');
        vehicleColumns.forEach(col => {
            console.log(`  ${col.Field.padEnd(20)} | ${col.Type.padEnd(20)} | Null: ${col.Null}`);
        });

        // Contar veículos
        const [vehicleCount] = await connection.query('SELECT COUNT(*) as count FROM Vehicles');
        console.log(`\n  Total de veículos: ${vehicleCount[0].count}\n`);

        // Verificar tabela Servicos
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('📊 Estrutura da tabela SERVICOS:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        const [servicoColumns] = await connection.query('DESCRIBE Servicos');
        servicoColumns.forEach(col => {
            console.log(`  ${col.Field.padEnd(20)} | ${col.Type.padEnd(20)} | Null: ${col.Null}`);
        });

        // Contar serviços
        const [servicoCount] = await connection.query('SELECT COUNT(*) as count FROM Servicos');
        console.log(`\n  Total de serviços: ${servicoCount[0].count}\n`);

        // Verificar se existe tabela de manutenções
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('🔍 Procurando tabelas relacionadas a manutenção/OS:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        const [tables] = await connection.query("SHOW TABLES LIKE '%manut%' OR SHOW TABLES LIKE '%os%' OR SHOW TABLES LIKE '%ordem%'");
        if (tables.length > 0) {
            tables.forEach(table => console.log(`  ✓ ${Object.values(table)[0]}`));
        } else {
            console.log('  ⚠️  Nenhuma tabela específica de manutenção encontrada');
        }

        await connection.end();
    } catch (error) {
        console.error('❌ Erro:', error.message);
    }
}

checkTablesStructure();
