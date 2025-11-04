// Script de teste para verificar se as tabelas de quilometragem foram criadas

const { db } = require('./database');

console.log('\n=== TESTE DO SISTEMA DE QUILOMETRAGEM ===\n');

// 1. Verificar se as tabelas existem
console.log('1. Verificando tabelas...');
const tabelas = db.prepare(`
    SELECT name FROM sqlite_master
    WHERE type='table'
    AND (name='quilometragem_diaria' OR name='quilometragem_mensal')
    ORDER BY name
`).all();

console.log('Tabelas encontradas:', tabelas);

if (tabelas.length === 2) {
    console.log('✅ Ambas as tabelas existem!\n');
} else {
    console.log('❌ Faltam tabelas!\n');
    process.exit(1);
}

// 2. Verificar estrutura da tabela quilometragem_diaria
console.log('2. Estrutura da tabela quilometragem_diaria:');
const estruturaDiaria = db.prepare(`PRAGMA table_info(quilometragem_diaria)`).all();
estruturaDiaria.forEach(col => {
    console.log(`   - ${col.name}: ${col.type}`);
});

// 3. Verificar estrutura da tabela quilometragem_mensal
console.log('\n3. Estrutura da tabela quilometragem_mensal:');
const estruturaMensal = db.prepare(`PRAGMA table_info(quilometragem_mensal)`).all();
estruturaMensal.forEach(col => {
    console.log(`   - ${col.name}: ${col.type}`);
});

// 4. Verificar índices
console.log('\n4. Verificando índices:');
const indices = db.prepare(`
    SELECT name FROM sqlite_master
    WHERE type='index'
    AND (tbl_name='quilometragem_diaria' OR tbl_name='quilometragem_mensal')
`).all();
indices.forEach(idx => {
    console.log(`   - ${idx.name}`);
});

// 5. Contar registros existentes
console.log('\n5. Contando registros existentes:');
const countDiaria = db.prepare(`SELECT COUNT(*) as total FROM quilometragem_diaria`).get();
const countMensal = db.prepare(`SELECT COUNT(*) as total FROM quilometragem_mensal`).get();
console.log(`   - quilometragem_diaria: ${countDiaria.total} registros`);
console.log(`   - quilometragem_mensal: ${countMensal.total} registros`);

// 6. Inserir um registro de teste
console.log('\n6. Inserindo registro de teste...');
const dbFunctions = require('./database');
const hoje = new Date().toISOString().split('T')[0];

try {
    const resultado = dbFunctions.salvarDiaria('TEST001', hoje, 1000.0, 1050.5, 60);
    console.log('✅ Registro de teste inserido com sucesso!');

    // Buscar o registro inserido
    const registroInserido = dbFunctions.buscarDiaria('TEST001', hoje);
    console.log('   Registro inserido:', registroInserido);

    // Limpar registro de teste
    db.prepare(`DELETE FROM quilometragem_diaria WHERE placa = 'TEST001'`).run();
    db.prepare(`DELETE FROM quilometragem_mensal WHERE placa = 'TEST001'`).run();
    console.log('✅ Registro de teste removido\n');
} catch (error) {
    console.log('❌ Erro ao inserir registro de teste:', error.message);
}

console.log('=== TESTE CONCLUÍDO ===\n');
db.close();
