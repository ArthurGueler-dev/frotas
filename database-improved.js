/**
 * Gerenciamento de Banco de Dados de Quilometragem - MySQL (Melhorado)
 * Versão otimizada com melhor gestão de conexões e queries
 */

const mysql = require('mysql2/promise');

// Configuração do banco de dados MySQL
const dbConfig = {
    host: '187.49.226.10',
    port: 3306,
    user: 'f137049_tool',
    password: 'In9@1234qwer',
    database: 'f137049_in9aut',
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    enableKeepAlive: true,
    keepAliveInitialDelay: 0
};

// Pool de conexões
const pool = mysql.createPool(dbConfig);

/**
 * Cria as tabelas necessárias se não existirem
 */
async function initializeTables() {
    const connection = await pool.getConnection();
    try {
        // Tabela de quilometragem diária
        await connection.query(`
            CREATE TABLE IF NOT EXISTS quilometragem_diaria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                placa VARCHAR(10) NOT NULL,
                data DATE NOT NULL,
                ano INT NOT NULL,
                mes INT NOT NULL,
                dia INT NOT NULL,
                km_inicial DECIMAL(10,2) DEFAULT 0,
                km_final DECIMAL(10,2) DEFAULT 0,
                km_rodados DECIMAL(10,2) DEFAULT 0,
                tempo_ignicao_minutos INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_placa_data (placa, data),
                INDEX idx_placa (placa),
                INDEX idx_data (data),
                INDEX idx_ano_mes (ano, mes),
                INDEX idx_placa_ano_mes (placa, ano, mes)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        `);

        // Tabela de quilometragem mensal
        await connection.query(`
            CREATE TABLE IF NOT EXISTS quilometragem_mensal (
                id INT AUTO_INCREMENT PRIMARY KEY,
                placa VARCHAR(10) NOT NULL,
                ano INT NOT NULL,
                mes INT NOT NULL,
                km_total DECIMAL(10,2) DEFAULT 0,
                dias_rodados INT DEFAULT 0,
                tempo_ignicao_total_minutos INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_placa_ano_mes (placa, ano, mes),
                INDEX idx_placa (placa),
                INDEX idx_ano_mes (ano, mes)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        `);

        // Tabela de totais diários da frota
        await connection.query(`
            CREATE TABLE IF NOT EXISTS quilometragem_frota_diaria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                data DATE NOT NULL UNIQUE,
                ano INT NOT NULL,
                mes INT NOT NULL,
                dia INT NOT NULL,
                km_total DECIMAL(10,2) DEFAULT 0,
                total_veiculos INT DEFAULT 0,
                veiculos_em_movimento INT DEFAULT 0,
                tempo_ignicao_total_minutos INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_data (data),
                INDEX idx_ano_mes (ano, mes)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        `);

        console.log('✅ Tabelas de quilometragem verificadas/criadas');

    } catch (error) {
        console.error('❌ Erro ao criar tabelas:', error);
        throw error;
    } finally {
        connection.release();
    }
}

// Executar criação das tabelas ao carregar
initializeTables().catch(err => {
    console.error('❌ Erro fatal ao inicializar tabelas:', err);
});

/**
 * Funções de banco de dados
 */
const dbFunctions = {
    /**
     * Salvar quilometragem diária
     */
    async salvarDiaria(placa, data, kmInicial, kmFinal, tempoIgnicao = 0) {
        const connection = await pool.getConnection();
        try {
            const dataObj = new Date(data);
            const ano = dataObj.getFullYear();
            const mes = dataObj.getMonth() + 1;
            const dia = dataObj.getDate();
            const kmRodados = Math.max(0, kmFinal - kmInicial);

            const [result] = await connection.query(`
                INSERT INTO quilometragem_diaria
                    (placa, data, ano, mes, dia, km_inicial, km_final, km_rodados, tempo_ignicao_minutos)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    km_inicial = VALUES(km_inicial),
                    km_final = VALUES(km_final),
                    km_rodados = VALUES(km_rodados),
                    tempo_ignicao_minutos = VALUES(tempo_ignicao_minutos),
                    updated_at = CURRENT_TIMESTAMP
            `, [placa, data.split('T')[0], ano, mes, dia, kmInicial, kmFinal, kmRodados, tempoIgnicao]);

            return result;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar quilometragem de um dia
     */
    async buscarDiaria(placa, data) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT * FROM quilometragem_diaria
                WHERE placa = ? AND data = ?
            `, [placa, data.split('T')[0]]);

            return rows[0] || null;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar quilometragem de um período
     */
    async buscarPeriodo(placa, dataInicio, dataFim) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT * FROM quilometragem_diaria
                WHERE placa = ? AND data BETWEEN ? AND ?
                ORDER BY data ASC
            `, [placa, dataInicio.split('T')[0], dataFim.split('T')[0]]);

            return rows;
        } finally {
            connection.release();
        }
    },

    /**
     * Atualizar dados mensais (recalcula com base nos dados diários)
     */
    async atualizarMensal(placa, ano, mes) {
        const connection = await pool.getConnection();
        try {
            const [totais] = await connection.query(`
                SELECT
                    COALESCE(SUM(km_rodados), 0) as km_total,
                    COUNT(DISTINCT data) as dias_rodados,
                    COALESCE(SUM(tempo_ignicao_minutos), 0) as tempo_ignicao_total_minutos
                FROM quilometragem_diaria
                WHERE placa = ? AND ano = ? AND mes = ?
            `, [placa, ano, mes]);

            const dados = totais[0];

            // Sempre salva, mesmo que seja zero
            const [result] = await connection.query(`
                INSERT INTO quilometragem_mensal
                    (placa, ano, mes, km_total, dias_rodados, tempo_ignicao_total_minutos)
                VALUES
                    (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    km_total = VALUES(km_total),
                    dias_rodados = VALUES(dias_rodados),
                    tempo_ignicao_total_minutos = VALUES(tempo_ignicao_total_minutos),
                    updated_at = CURRENT_TIMESTAMP
            `, [
                placa,
                ano,
                mes,
                dados.km_total,
                dados.dias_rodados,
                dados.tempo_ignicao_total_minutos
            ]);

            return result;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar quilometragem mensal
     */
    async buscarMensal(placa, ano, mes) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT * FROM quilometragem_mensal
                WHERE placa = ? AND ano = ? AND mes = ?
            `, [placa, ano, mes]);

            return rows[0] || null;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar quilometragem de vários meses
     */
    async buscarMeses(placa, anoInicio, mesInicio, anoFim, mesFim) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT * FROM quilometragem_mensal
                WHERE placa = ?
                  AND (
                      (ano > ? OR (ano = ? AND mes >= ?))
                      AND
                      (ano < ? OR (ano = ? AND mes <= ?))
                  )
                ORDER BY ano ASC, mes ASC
            `, [placa, anoInicio, anoInicio, mesInicio, anoFim, anoFim, mesFim]);

            return rows;
        } finally {
            connection.release();
        }
    },

    /**
     * Atualizar totais diários da frota
     */
    async atualizarTotalFrotaDiaria(data) {
        const connection = await pool.getConnection();
        try {
            const dataObj = new Date(data);
            const ano = dataObj.getFullYear();
            const mes = dataObj.getMonth() + 1;
            const dia = dataObj.getDate();
            const dataStr = data.split('T')[0];

            // Calcular totais do dia
            const [totais] = await connection.query(`
                SELECT
                    COALESCE(SUM(km_rodados), 0) as km_total,
                    COUNT(DISTINCT placa) as total_veiculos,
                    COUNT(CASE WHEN km_rodados > 0 THEN 1 END) as veiculos_em_movimento,
                    COALESCE(SUM(tempo_ignicao_minutos), 0) as tempo_ignicao_total_minutos
                FROM quilometragem_diaria
                WHERE data = ?
            `, [dataStr]);

            const dados = totais[0];

            await connection.query(`
                INSERT INTO quilometragem_frota_diaria
                    (data, ano, mes, dia, km_total, total_veiculos, veiculos_em_movimento, tempo_ignicao_total_minutos)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    km_total = VALUES(km_total),
                    total_veiculos = VALUES(total_veiculos),
                    veiculos_em_movimento = VALUES(veiculos_em_movimento),
                    tempo_ignicao_total_minutos = VALUES(tempo_ignicao_total_minutos),
                    updated_at = CURRENT_TIMESTAMP
            `, [
                dataStr,
                ano,
                mes,
                dia,
                dados.km_total,
                dados.total_veiculos,
                dados.veiculos_em_movimento,
                dados.tempo_ignicao_total_minutos
            ]);

            return dados;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar total da frota por dia
     */
    async buscarTotalFrotaDia(data) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT * FROM quilometragem_frota_diaria
                WHERE data = ?
            `, [data.split('T')[0]]);

            return rows[0] || null;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar todas as placas com dados em uma data
     */
    async buscarPlacasPorData(data) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT DISTINCT placa FROM quilometragem_diaria
                WHERE data = ?
                ORDER BY placa
            `, [data.split('T')[0]]);

            return rows;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar últimas atualizações de uma placa
     */
    async buscarUltimasAtualizacoes(placa, limit = 7) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT * FROM quilometragem_diaria
                WHERE placa = ?
                ORDER BY data DESC
                LIMIT ?
            `, [placa, limit]);

            return rows;
        } finally {
            connection.release();
        }
    },

    /**
     * Buscar estatísticas gerais de uma placa
     */
    async buscarEstatisticasPlaca(placa) {
        const connection = await pool.getConnection();
        try {
            const [stats] = await connection.query(`
                SELECT
                    COUNT(DISTINCT data) as total_dias,
                    COALESCE(SUM(km_rodados), 0) as km_total,
                    COALESCE(AVG(km_rodados), 0) as km_media_dia,
                    COALESCE(MAX(km_rodados), 0) as km_max_dia,
                    COALESCE(MIN(data), NULL) as primeira_data,
                    COALESCE(MAX(data), NULL) as ultima_data
                FROM quilometragem_diaria
                WHERE placa = ? AND km_rodados > 0
            `, [placa]);

            return stats[0];
        } finally {
            connection.release();
        }
    }
};

/**
 * Testa a conexão com o banco
 */
async function testConnection() {
    try {
        const connection = await pool.getConnection();
        await connection.ping();
        connection.release();
        console.log('✅ Conexão com banco de dados OK');
        return true;
    } catch (error) {
        console.error('❌ Erro ao conectar ao banco de dados:', error.message);
        return false;
    }
}

// Testa conexão ao iniciar
testConnection();

module.exports = {
    pool,
    testConnection,
    initializeTables,
    ...dbFunctions
};
