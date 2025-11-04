// Script para criar as tabelas de quilometragem no MySQL

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

async function criarTabelas() {
    let connection;

    try {
        console.log('\n🔌 Conectando ao MySQL...');
        console.log(`   Host: ${dbConfig.host}`);
        console.log(`   Database: ${dbConfig.database}\n`);

        connection = await mysql.createConnection(dbConfig);

        console.log('✅ Conectado com sucesso!\n');

        // Ler arquivo SQL
        const sqlPath = path.join(__dirname, 'criar-tabelas-mysql.sql');
        const sql = fs.readFileSync(sqlPath, 'utf-8');

        console.log('📄 Executando SQL...\n');

        // Executar SQL
        await connection.query(sql);

        console.log('✅ Tabelas criadas com sucesso!\n');

        // Verificar se as tabelas foram criadas
        console.log('🔍 Verificando tabelas criadas:\n');

        const [tables] = await connection.query(`
            SHOW TABLES LIKE 'quilometragem%'
        `);

        for (const table of tables) {
            const tableName = Object.values(table)[0];
            console.log(`   ✅ ${tableName}`);

            // Mostrar estrutura
            const [columns] = await connection.query(`DESCRIBE ${tableName}`);
            console.log(`      Colunas: ${columns.length}`);
            columns.forEach(col => {
                console.log(`        - ${col.Field} (${col.Type})`);
            });
            console.log('');
        }

        // Contar registros
        console.log('📊 Registros existentes:\n');
        const [countDiaria] = await connection.query(`SELECT COUNT(*) as total FROM quilometragem_diaria`);
        const [countMensal] = await connection.query(`SELECT COUNT(*) as total FROM quilometragem_mensal`);

        console.log(`   quilometragem_diaria: ${countDiaria[0].total} registros`);
        console.log(`   quilometragem_mensal: ${countMensal[0].total} registros`);

        console.log('\n✅ Processo concluído com sucesso!\n');

    } catch (error) {
        console.error('\n❌ ERRO:', error.message);
        console.error('\nDetalhes:', error);
    } finally {
        if (connection) {
            await connection.end();
            console.log('🔌 Conexão fechada.\n');
        }
    }
}

// Executar
criarTabelas();
