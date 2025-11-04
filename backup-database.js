/**
 * Script de Backup do Banco de Dados MySQL
 *
 * Este script cria backups das tabelas de quilometragem
 * salvando em formato SQL ou JSON
 */

const mysql = require('mysql2/promise');
const fs = require('fs').promises;
const path = require('path');

// Configuração do banco de dados
const dbConfig = {
    host: '187.49.226.10',
    port: 3306,
    user: 'f137049_tool',
    password: 'In9@1234qwer',
    database: 'f137049_in9aut',
    charset: 'utf8mb4'
};

// Diretório de backups
const backupDir = path.join(__dirname, 'backups');

async function criarDiretorioBackup() {
    try {
        await fs.mkdir(backupDir, { recursive: true });
        console.log(`✅ Diretório de backup: ${backupDir}`);
    } catch (error) {
        console.error('❌ Erro ao criar diretório de backup:', error.message);
        throw error;
    }
}

async function exportarParaJSON(connection, tabela, nomeArquivo) {
    try {
        console.log(`📊 Exportando ${tabela} para JSON...`);

        const [rows] = await connection.query(`SELECT * FROM ${tabela}`);

        const jsonData = JSON.stringify(rows, null, 2);
        await fs.writeFile(nomeArquivo, jsonData, 'utf8');

        console.log(`✅ ${tabela}: ${rows.length} registros exportados`);
        return rows.length;
    } catch (error) {
        console.error(`❌ Erro ao exportar ${tabela}:`, error.message);
        throw error;
    }
}

async function criarBackup() {
    console.log('═══════════════════════════════════════════════════════════');
    console.log('💾 Iniciando Backup do Banco de Dados');
    console.log('═══════════════════════════════════════════════════════════');
    console.log(`🕐 Horário: ${new Date().toLocaleString('pt-BR')}`);
    console.log('');

    let connection;

    try {
        // Criar diretório de backups
        await criarDiretorioBackup();

        // Conectar ao banco
        console.log('🔌 Conectando ao banco de dados MySQL...');
        connection = await mysql.createConnection(dbConfig);
        console.log('✅ Conectado com sucesso!');
        console.log('');

        // Nome dos arquivos de backup
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').split('T')[0];
        const backupDiaria = path.join(backupDir, `quilometragem_diaria_${timestamp}.json`);
        const backupMensal = path.join(backupDir, `quilometragem_mensal_${timestamp}.json`);

        // Exportar tabelas
        console.log('📦 Exportando tabelas...');
        console.log('');

        const totalDiaria = await exportarParaJSON(connection, 'quilometragem_diaria', backupDiaria);
        const totalMensal = await exportarParaJSON(connection, 'quilometragem_mensal', backupMensal);

        console.log('');
        console.log('✅ BACKUP CONCLUÍDO COM SUCESSO!');
        console.log('');
        console.log('📊 Resumo:');
        console.log(`   • Registros diários: ${totalDiaria}`);
        console.log(`   • Registros mensais: ${totalMensal}`);
        console.log('');
        console.log('📁 Arquivos criados:');
        console.log(`   • ${path.basename(backupDiaria)}`);
        console.log(`   • ${path.basename(backupMensal)}`);
        console.log('');

        // Limpar backups antigos (manter últimos 30 dias)
        await limparBackupsAntigos(30);

        console.log('═══════════════════════════════════════════════════════════');
        console.log('🏁 Backup finalizado');
        console.log('═══════════════════════════════════════════════════════════');
        console.log('');

    } catch (error) {
        console.error('');
        console.error('❌ ERRO NO BACKUP!');
        console.error('');
        console.error('Detalhes:', error.message);
        console.error('');
        process.exit(1);
    } finally {
        if (connection) {
            await connection.end();
            console.log('🔌 Conexão com banco de dados encerrada');
        }
    }
}

async function limparBackupsAntigos(diasParaManter) {
    try {
        console.log(`🧹 Limpando backups com mais de ${diasParaManter} dias...`);

        const arquivos = await fs.readdir(backupDir);
        const agora = Date.now();
        const diasEmMs = diasParaManter * 24 * 60 * 60 * 1000;

        let removidos = 0;

        for (const arquivo of arquivos) {
            if (arquivo.endsWith('.json')) {
                const caminhoCompleto = path.join(backupDir, arquivo);
                const stats = await fs.stat(caminhoCompleto);
                const idade = agora - stats.mtime.getTime();

                if (idade > diasEmMs) {
                    await fs.unlink(caminhoCompleto);
                    removidos++;
                    console.log(`   ❌ Removido: ${arquivo}`);
                }
            }
        }

        if (removidos > 0) {
            console.log(`✅ ${removidos} backup(s) antigo(s) removido(s)`);
        } else {
            console.log(`✅ Nenhum backup antigo para remover`);
        }
        console.log('');
    } catch (error) {
        console.error('⚠️ Erro ao limpar backups antigos:', error.message);
    }
}

// Executar backup
criarBackup().catch(error => {
    console.error('Erro fatal:', error);
    process.exit(1);
});
