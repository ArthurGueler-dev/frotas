<?php
/**
 * Script para criar/atualizar a tabela avisos_manutencao
 *
 * Esta tabela armazena os alertas de manutenção preventiva gerados automaticamente
 * pelo sistema baseado nos planos de manutenção e quilometragem atual dos veículos.
 *
 * Execute este script para criar a tabela ou adicionar colunas faltantes.
 *
 * @author Claude
 * @version 1.0
 * @date 2026-01-21
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db-config.php';

try {
    $conn = getDBConnection();

    if ($conn === null) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }

    $results = [];

    // ==================== CRIAR TABELA ====================
    $sqlCreate = "
        CREATE TABLE IF NOT EXISTS avisos_manutencao (
            id INT AUTO_INCREMENT PRIMARY KEY,

            -- Referências
            vehicle_id INT NULL,
            placa_veiculo VARCHAR(20) NOT NULL,
            plano_id INT NULL,

            -- Dados do alerta
            km_programado INT DEFAULT 0 COMMENT 'KM em que a manutenção deve ser feita',
            data_proxima DATE NULL COMMENT 'Data prevista para a manutenção',
            km_atual_veiculo INT DEFAULT 0 COMMENT 'KM atual do veículo no momento do cálculo',
            km_restantes INT DEFAULT 0 COMMENT 'KM restantes até a manutenção (negativo = vencido)',
            dias_restantes INT NULL COMMENT 'Dias restantes até a manutenção (negativo = vencido)',

            -- Status e nível
            status ENUM('Pendente', 'Vencido', 'EmDia', 'Concluido', 'Cancelado') DEFAULT 'Pendente',
            nivel_alerta ENUM('Critico', 'Alto', 'Medio', 'Baixo') DEFAULT 'Medio',

            -- Mensagem e descrição
            mensagem TEXT NULL COMMENT 'Mensagem descritiva do alerta',

            -- Notificação
            notificado TINYINT(1) DEFAULT 0 COMMENT '1 se já foi enviada notificação',
            data_notificacao DATETIME NULL COMMENT 'Data/hora em que a notificação foi enviada',

            -- Resolução
            concluido_em DATETIME NULL COMMENT 'Data/hora em que foi marcado como concluído',
            km_finalizacao INT NULL COMMENT 'KM do veículo quando a manutenção foi feita',
            os_numero VARCHAR(50) NULL COMMENT 'Número da OS que resolveu este alerta',

            -- Timestamps
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            -- Índices
            INDEX idx_placa (placa_veiculo),
            INDEX idx_status (status),
            INDEX idx_nivel (nivel_alerta),
            INDEX idx_plano (plano_id),
            INDEX idx_vehicle (vehicle_id),
            INDEX idx_notificado (notificado),
            INDEX idx_km_restantes (km_restantes),

            -- Constraints
            CONSTRAINT fk_avisos_vehicle FOREIGN KEY (vehicle_id)
                REFERENCES Vehicles(ID) ON DELETE SET NULL,
            CONSTRAINT fk_avisos_plano FOREIGN KEY (plano_id)
                REFERENCES `Planos_Manutenção`(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Alertas de manutenção preventiva gerados automaticamente';
    ";

    try {
        $conn->exec($sqlCreate);
        $results[] = ['action' => 'CREATE TABLE', 'status' => 'OK', 'message' => 'Tabela criada ou já existente'];
    } catch (PDOException $e) {
        // Se der erro de FK, tentar criar sem FK
        if (strpos($e->getMessage(), 'foreign key') !== false || strpos($e->getMessage(), 'Cannot add') !== false) {
            $sqlCreateSimple = "
                CREATE TABLE IF NOT EXISTS avisos_manutencao (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    vehicle_id INT NULL,
                    placa_veiculo VARCHAR(20) NOT NULL,
                    plano_id INT NULL,
                    km_programado INT DEFAULT 0,
                    data_proxima DATE NULL,
                    km_atual_veiculo INT DEFAULT 0,
                    km_restantes INT DEFAULT 0,
                    dias_restantes INT NULL,
                    status ENUM('Pendente', 'Vencido', 'EmDia', 'Concluido', 'Cancelado') DEFAULT 'Pendente',
                    nivel_alerta ENUM('Critico', 'Alto', 'Medio', 'Baixo') DEFAULT 'Medio',
                    mensagem TEXT NULL,
                    notificado TINYINT(1) DEFAULT 0,
                    data_notificacao DATETIME NULL,
                    concluido_em DATETIME NULL,
                    km_finalizacao INT NULL,
                    os_numero VARCHAR(50) NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_placa (placa_veiculo),
                    INDEX idx_status (status),
                    INDEX idx_nivel (nivel_alerta),
                    INDEX idx_plano (plano_id),
                    INDEX idx_vehicle (vehicle_id),
                    INDEX idx_notificado (notificado)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $conn->exec($sqlCreateSimple);
            $results[] = ['action' => 'CREATE TABLE (sem FK)', 'status' => 'OK', 'message' => 'Tabela criada sem foreign keys'];
        } else {
            throw $e;
        }
    }

    // ==================== ADICIONAR COLUNAS FALTANTES ====================

    // Lista de colunas que devem existir
    $requiredColumns = [
        'notificado' => "ALTER TABLE avisos_manutencao ADD COLUMN notificado TINYINT(1) DEFAULT 0 COMMENT '1 se já foi enviada notificação'",
        'data_notificacao' => "ALTER TABLE avisos_manutencao ADD COLUMN data_notificacao DATETIME NULL COMMENT 'Data/hora em que a notificação foi enviada'",
        'concluido_em' => "ALTER TABLE avisos_manutencao ADD COLUMN concluido_em DATETIME NULL COMMENT 'Data/hora em que foi marcado como concluído'",
        'km_finalizacao' => "ALTER TABLE avisos_manutencao ADD COLUMN km_finalizacao INT NULL COMMENT 'KM do veículo quando a manutenção foi feita'",
        'os_numero' => "ALTER TABLE avisos_manutencao ADD COLUMN os_numero VARCHAR(50) NULL COMMENT 'Número da OS que resolveu este alerta'",
        'atualizado_em' => "ALTER TABLE avisos_manutencao ADD COLUMN atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];

    // Verificar quais colunas já existem
    $sqlDescribe = "DESCRIBE avisos_manutencao";
    $stmtDescribe = $conn->query($sqlDescribe);
    $existingColumns = [];

    while ($row = $stmtDescribe->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }

    // Adicionar colunas faltantes
    foreach ($requiredColumns as $columnName => $alterSql) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $conn->exec($alterSql);
                $results[] = ['action' => "ADD COLUMN $columnName", 'status' => 'OK', 'message' => 'Coluna adicionada'];
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    $results[] = ['action' => "ADD COLUMN $columnName", 'status' => 'ERROR', 'message' => $e->getMessage()];
                } else {
                    $results[] = ['action' => "ADD COLUMN $columnName", 'status' => 'SKIP', 'message' => 'Coluna já existe'];
                }
            }
        } else {
            $results[] = ['action' => "CHECK COLUMN $columnName", 'status' => 'SKIP', 'message' => 'Coluna já existe'];
        }
    }

    // ==================== ADICIONAR ÍNDICES FALTANTES ====================

    // Verificar índice de notificado
    try {
        $conn->exec("CREATE INDEX idx_notificado ON avisos_manutencao(notificado)");
        $results[] = ['action' => 'CREATE INDEX idx_notificado', 'status' => 'OK', 'message' => 'Índice criado'];
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'exists') !== false) {
            $results[] = ['action' => 'CREATE INDEX idx_notificado', 'status' => 'SKIP', 'message' => 'Índice já existe'];
        }
    }

    // ==================== VERIFICAR ESTRUTURA FINAL ====================

    $stmtFinal = $conn->query("DESCRIBE avisos_manutencao");
    $finalStructure = $stmtFinal->fetchAll(PDO::FETCH_ASSOC);

    // Contar registros
    $stmtCount = $conn->query("SELECT COUNT(*) as total FROM avisos_manutencao");
    $countRow = $stmtCount->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Tabela avisos_manutencao configurada com sucesso',
        'results' => $results,
        'structure' => $finalStructure,
        'total_records' => intval($countRow['total']),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'results' => isset($results) ? $results : [],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
