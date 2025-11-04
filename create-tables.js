const mysql = require('mysql2/promise');
const fs = require('fs');

async function createTables() {
    let connection;

    try {
        connection = await mysql.createConnection({
            host: '187.49.226.10',
            port: 3306,
            user: 'f137049_tool',
            password: 'In9@1234qwer',
            database: 'f137049_in9aut',
            multipleStatements: true
        });

        console.log('✅ Conectado ao banco de dados!\n');

        // Ler o arquivo SQL
        const sql = fs.readFileSync('create-tables.sql', 'utf8');

        // Dividir por comandos individuais
        const statements = sql
            .split(';')
            .map(s => s.trim())
            .filter(s => s.length > 0 && !s.startsWith('--'));

        console.log(`📝 Executando ${statements.length} comandos SQL...\n`);

        for (let i = 0; i < statements.length; i++) {
            const statement = statements[i];

            // Extrair nome da tabela do CREATE TABLE
            const match = statement.match(/CREATE TABLE IF NOT EXISTS (\w+)/);
            if (match) {
                const tableName = match[1];
                console.log(`${i + 1}. Criando tabela: ${tableName}...`);

                try {
                    await connection.query(statement);
                    console.log(`   ✅ Tabela ${tableName} criada com sucesso!`);
                } catch (error) {
                    if (error.code === 'ER_TABLE_EXISTS_ERR') {
                        console.log(`   ℹ️  Tabela ${tableName} já existe`);
                    } else {
                        console.error(`   ❌ Erro ao criar ${tableName}:`, error.message);
                    }
                }
            }
        }

        console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('📊 Verificando tabelas criadas:');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        const [tables] = await connection.query("SHOW TABLES LIKE 'FF_%'");
        if (tables.length > 0) {
            tables.forEach((table, index) => {
                console.log(`  ${index + 1}. ✓ ${Object.values(table)[0]}`);
            });
        } else {
            console.log('  ⚠️  Nenhuma tabela FF_ encontrada');
        }

        console.log('\n✅ Processo concluído!');

    } catch (error) {
        console.error('❌ Erro:', error.message);
        console.error(error);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

createTables();
