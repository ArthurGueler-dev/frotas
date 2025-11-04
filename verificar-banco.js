// Verificar se o banco de dados está funcionando corretamente

const Database = require('better-sqlite3');
const path = require('path');

console.log('\n=== VERIFICAÇÃO DO BANCO DE DADOS ===\n');

try {
    const dbPath = path.join(__dirname, 'frotas.db');
    console.log(`📁 Caminho do banco: ${dbPath}`);

    const db = new Database(dbPath);

    // 1. Listar TODAS as tabelas
    console.log('\n1. TODAS AS TABELAS no banco:');
    const allTables = db.prepare(`
        SELECT name FROM sqlite_master
        WHERE type='table'
        ORDER BY name
    `).all();

    console.log(`   Total de tabelas: ${allTables.length}`);
    allTables.forEach(t => {
        console.log(`   - ${t.name}`);
    });

    // 2. Verificar especificamente as tabelas de quilometragem
    console.log('\n2. Tabelas de quilometragem:');
    const kmTables = db.prepare(`
        SELECT name FROM sqlite_master
        WHERE type='table'
        AND (name='quilometragem_diaria' OR name='quilometragem_mensal')
        ORDER BY name
    `).all();

    if (kmTables.length === 0) {
        console.log('   ❌ NENHUMA tabela de quilometragem encontrada!');
        console.log('   ℹ️  Tentando criar as tabelas...\n');

        // Criar as tabelas
        db.exec(`
            CREATE TABLE IF NOT EXISTS quilometragem_diaria (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                placa VARCHAR(10) NOT NULL,
                data DATE NOT NULL,
                ano INTEGER NOT NULL,
                mes INTEGER NOT NULL,
                dia INTEGER NOT NULL,
                km_inicial DECIMAL(10,2) DEFAULT 0,
                km_final DECIMAL(10,2) DEFAULT 0,
                km_rodados DECIMAL(10,2) DEFAULT 0,
                tempo_ignicao_minutos INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(placa, data)
            )
        `);

        db.exec(`
            CREATE TABLE IF NOT EXISTS quilometragem_mensal (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                placa VARCHAR(10) NOT NULL,
                ano INTEGER NOT NULL,
                mes INTEGER NOT NULL,
                km_total DECIMAL(10,2) DEFAULT 0,
                dias_rodados INTEGER DEFAULT 0,
                tempo_ignicao_total_minutos INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(placa, ano, mes)
            )
        `);

        db.exec(`
            CREATE INDEX IF NOT EXISTS idx_quilometragem_diaria_placa_data
            ON quilometragem_diaria(placa, data);

            CREATE INDEX IF NOT EXISTS idx_quilometragem_diaria_data
            ON quilometragem_diaria(data);

            CREATE INDEX IF NOT EXISTS idx_quilometragem_mensal_placa
            ON quilometragem_mensal(placa, ano, mes);
        `);

        console.log('   ✅ Tabelas criadas com sucesso!\n');

        // Verificar novamente
        const kmTablesAfter = db.prepare(`
            SELECT name FROM sqlite_master
            WHERE type='table'
            AND (name='quilometragem_diaria' OR name='quilometragem_mensal')
            ORDER BY name
        `).all();

        kmTablesAfter.forEach(t => {
            console.log(`   ✅ ${t.name} criada`);
        });
    } else {
        console.log('   ✅ Tabelas de quilometragem existem:');
        kmTables.forEach(t => {
            console.log(`      - ${t.name}`);
        });
    }

    // 3. Contar registros
    console.log('\n3. Registros:');
    try {
        const countDiaria = db.prepare(`SELECT COUNT(*) as total FROM quilometragem_diaria`).get();
        const countMensal = db.prepare(`SELECT COUNT(*) as total FROM quilometragem_mensal`).get();
        console.log(`   - quilometragem_diaria: ${countDiaria.total} registros`);
        console.log(`   - quilometragem_mensal: ${countMensal.total} registros`);
    } catch (error) {
        console.log(`   ❌ Erro ao contar registros: ${error.message}`);
    }

    db.close();
    console.log('\n=== VERIFICAÇÃO CONCLUÍDA ===\n');

} catch (error) {
    console.error('\n❌ ERRO:', error);
    console.error('\nDetalhes:', error.message);
}
