// Gerenciamento de Banco de Dados de Quilometragem - MySQL
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
    queueLimit: 0
};

// Pool de conexões
const pool = mysql.createPool(dbConfig);

// Criar tabela de totais diários da frota (se não existir)
async function criarTabelaTotaisFrota() {
    const connection = await pool.getConnection();
    try {
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
        console.log('✅ Tabela quilometragem_frota_diaria verificada/criada');
    } catch (error) {
        console.error('❌ Erro ao criar tabela de totais da frota:', error);
    } finally {
        connection.release();
    }
}

// Executar criação da tabela ao carregar
criarTabelaTotaisFrota();

// Funções de conveniência
const dbFunctions = {
    // Salvar quilometragem diária
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

    // Buscar quilometragem de um dia
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

    // Buscar quilometragem de um período
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

    // Atualizar dados mensais
    async atualizarMensal(placa, ano, mes) {
        const connection = await pool.getConnection();
        try {
            const [totais] = await connection.query(`
                SELECT
                    SUM(km_rodados) as km_total,
                    COUNT(DISTINCT data) as dias_rodados,
                    SUM(tempo_ignicao_minutos) as tempo_ignicao_total_minutos
                FROM quilometragem_diaria
                WHERE placa = ? AND ano = ? AND mes = ?
            `, [placa, ano, mes]);

            if (totais[0] && totais[0].km_total > 0) {
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
                    totais[0].km_total || 0,
                    totais[0].dias_rodados || 0,
                    totais[0].tempo_ignicao_total_minutos || 0
                ]);

                return result;
            }
            return null;
        } finally {
            connection.release();
        }
    },

    // Buscar quilometragem mensal
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

    // Buscar quilometragem de vários meses
    async buscarMeses(placa, anoInicio, mesInicio, anoFim, mesFim) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT * FROM quilometragem_mensal
                WHERE placa = ? AND
                      ((ano = ? AND mes >= ?) OR (ano > ?))
                      AND ((ano = ? AND mes <= ?) OR (ano < ?))
                ORDER BY ano ASC, mes ASC
            `, [placa, anoInicio, mesInicio, anoInicio, anoFim, mesFim, anoFim]);

            return rows;
        } finally {
            connection.release();
        }
    },

    // Atualizar totais diários da frota
    async atualizarTotalFrotaDiaria(data) {
        const connection = await pool.getConnection();
        try {
            const dataObj = new Date(data);
            const ano = dataObj.getFullYear();
            const mes = dataObj.getMonth() + 1;
            const dia = dataObj.getDate();

            // Calcular totais do dia
            const [totais] = await connection.query(`
                SELECT
                    SUM(km_rodados) as km_total,
                    COUNT(DISTINCT placa) as total_veiculos,
                    COUNT(CASE WHEN km_rodados > 0 THEN 1 END) as veiculos_em_movimento,
                    SUM(tempo_ignicao_minutos) as tempo_ignicao_total_minutos
                FROM quilometragem_diaria
                WHERE data = ?
            `, [data.split('T')[0]]);

            if (totais[0]) {
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
                    data.split('T')[0],
                    ano,
                    mes,
                    dia,
                    totais[0].km_total || 0,
                    totais[0].total_veiculos || 0,
                    totais[0].veiculos_em_movimento || 0,
                    totais[0].tempo_ignicao_total_minutos || 0
                ]);
            }

            return totais[0];
        } finally {
            connection.release();
        }
    },

    // Buscar total da frota por dia
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

    // Buscar todas as placas com dados em uma data
    async buscarPlacasPorData(data) {
        const connection = await pool.getConnection();
        try {
            const [rows] = await connection.query(`
                SELECT DISTINCT placa FROM quilometragem_diaria
                WHERE data = ?
            `, [data.split('T')[0]]);

            return rows;
        } finally {
            connection.release();
        }
    }
};

module.exports = {
    pool,
    ...dbFunctions
};
