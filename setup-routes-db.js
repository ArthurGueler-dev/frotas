const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

// Configuração do banco de dados
const dbConfig = {
    host: '187.49.226.10',
    port: 3306,
    user: 'f137049_tool',
    password: 'In9@1234qwer',
    database: 'f137049_in9aut',
    charset: 'utf8mb4',
    multipleStatements: true
};

async function setupDatabase() {
    let connection;

    try {
        console.log('🔌 Conectando ao banco de dados...');
        connection = await mysql.createConnection(dbConfig);
        console.log('✅ Conectado com sucesso!\n');

        // Ler arquivo SQL
        const sqlFile = path.join(__dirname, 'create-routes-table.sql');
        const sql = fs.readFileSync(sqlFile, 'utf8');

        console.log('📝 Executando script SQL...\n');

        // Dividir em comandos individuais e executar
        const commands = sql.split(';').filter(cmd => cmd.trim());

        for (const command of commands) {
            const trimmedCommand = command.trim();
            if (trimmedCommand) {
                try {
                    await connection.query(trimmedCommand);
                    console.log('✅', trimmedCommand.substring(0, 60) + '...');
                } catch (error) {
                    // Ignorar erros de "já existe"
                    if (!error.message.includes('already exists') && !error.message.includes('Duplicate')) {
                        console.error('❌ Erro:', error.message);
                    } else {
                        console.log('ℹ️', trimmedCommand.substring(0, 60) + '... (já existe)');
                    }
                }
            }
        }

        console.log('\n✅ Script executado com sucesso!');

        // Verificar se as tabelas foram criadas
        console.log('\n📊 Verificando tabelas criadas...');

        const [tables] = await connection.query("SHOW TABLES LIKE 'FF_Routes%'");
        console.log('Tabelas encontradas:');
        tables.forEach(table => {
            console.log('  -', Object.values(table)[0]);
        });

        // Verificar estrutura da tabela FF_Routes
        console.log('\n📋 Estrutura da tabela FF_Routes:');
        const [columns] = await connection.query("DESCRIBE FF_Routes");
        console.table(columns.map(col => ({
            Campo: col.Field,
            Tipo: col.Type,
            Nulo: col.Null,
            Padrão: col.Default
        })));

        console.log('\n🎉 Banco de dados configurado com sucesso!');
        console.log('Você já pode usar o sistema de rotas.\n');

    } catch (error) {
        console.error('❌ Erro ao configurar banco de dados:', error.message);
        process.exit(1);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

// Executar setup
setupDatabase();
