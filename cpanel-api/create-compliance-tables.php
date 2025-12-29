<?php
/**
 * Script para criar tabelas do Sistema de Monitoramento de Conformidade de Rotas
 *
 * INSTRUÇÕES:
 * 1. Copie o conteúdo SQL abaixo
 * 2. Acesse phpMyAdmin: https://floripa.in9automacao.com.br:2083/phpMyAdmin/
 * 3. Selecione o banco: f137049_in9aut
 * 4. Cole no SQL e execute
 * 5. Verifique se as 3 tabelas foram criadas
 *
 * EXECUTAR APENAS UMA VEZ!
 */

// Script SQL para copiar e executar no phpMyAdmin
$sql = <<<'SQL'

-- =====================================================
-- Tabela 1: FF_RouteCompliance
-- Armazena análises periódicas (a cada 5 min) de cada rota
-- =====================================================

CREATE TABLE IF NOT EXISTS FF_RouteCompliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL COMMENT 'ID da rota em FF_Rotas',
    check_timestamp DATETIME NOT NULL COMMENT 'Quando foi verificado',
    vehicle_plate VARCHAR(20) NOT NULL COMMENT 'Placa do veículo',

    -- Posição atual do veículo
    current_latitude DECIMAL(10, 8) NOT NULL COMMENT 'Latitude atual',
    current_longitude DECIMAL(11, 8) NOT NULL COMMENT 'Longitude atual',
    current_address VARCHAR(500) NULL COMMENT 'Endereço aproximado',
    current_speed INT DEFAULT 0 COMMENT 'Velocidade em km/h',

    -- Análise de conformidade
    expected_sequence_index INT NULL COMMENT 'Qual local deveria estar visitando',
    distance_from_planned_route_km DECIMAL(8, 2) DEFAULT 0 COMMENT 'Distância do ponto planejado',

    -- Resultados
    is_compliant BOOLEAN DEFAULT TRUE COMMENT 'TRUE se está conforme',
    compliance_score DECIMAL(5, 2) DEFAULT 100.00 COMMENT 'Score 0-100',
    visits_completed INT DEFAULT 0 COMMENT 'Quantos locais já visitou',
    visits_total INT DEFAULT 0 COMMENT 'Total de locais na rota',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_route (route_id),
    INDEX idx_timestamp (check_timestamp),
    INDEX idx_compliance (is_compliant),

    FOREIGN KEY (route_id) REFERENCES FF_Rotas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de verificações de conformidade (a cada 5 min)';


-- =====================================================
-- Tabela 2: FF_RouteDeviations
-- Registro de desvios detectados
-- =====================================================

CREATE TABLE IF NOT EXISTS FF_RouteDeviations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL COMMENT 'ID da rota',
    compliance_check_id INT NULL COMMENT 'ID da verificação que detectou',

    deviation_type ENUM(
        'wrong_sequence',      -- Visitou local B antes do local A
        'excessive_distance',  -- Percorreu mais que 20% além do planejado
        'unplanned_stop',      -- Parou mais de 15min em local não planejado
        'skipped_location',    -- Pulou um local da rota
        'route_abandoned'      -- Está muito longe (>5km) de qualquer ponto
    ) NOT NULL,

    detected_at DATETIME NOT NULL COMMENT 'Quando foi detectado',
    location_latitude DECIMAL(10, 8) NOT NULL COMMENT 'Onde estava quando desviou',
    location_longitude DECIMAL(11, 8) NOT NULL,
    location_address VARCHAR(500) NULL,

    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',

    -- Sistema de alertas
    alert_sent BOOLEAN DEFAULT FALSE COMMENT 'Alerta foi enviado?',
    alert_sent_at DATETIME NULL COMMENT 'Quando enviou',
    alert_recipients TEXT NULL COMMENT 'JSON com telefones que receberam',

    -- Resolução do desvio
    is_resolved BOOLEAN DEFAULT FALSE COMMENT 'Foi resolvido/justificado?',
    resolved_at DATETIME NULL,
    resolution_notes TEXT NULL COMMENT 'Notas sobre a resolução',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_route (route_id),
    INDEX idx_type (deviation_type),
    INDEX idx_severity (severity),
    INDEX idx_alert_sent (alert_sent),
    INDEX idx_detected (detected_at),

    FOREIGN KEY (route_id) REFERENCES FF_Rotas(id) ON DELETE CASCADE,
    FOREIGN KEY (compliance_check_id) REFERENCES FF_RouteCompliance(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Desvios de rota detectados';


-- =====================================================
-- Tabela 3: FF_AlertRecipients
-- Cadastro de destinatários de alertas (diretores, gerentes)
-- =====================================================

CREATE TABLE IF NOT EXISTS FF_AlertRecipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Nome completo',
    role VARCHAR(100) NULL COMMENT 'Cargo (Diretor, Gerente, etc)',
    phone VARCHAR(20) NOT NULL UNIQUE COMMENT 'Telefone WhatsApp (5527999999999)',
    email VARCHAR(255) NULL COMMENT 'Email (opcional)',

    -- Filtros de severidade (quais alertas receber)
    receive_critical BOOLEAN DEFAULT TRUE COMMENT 'Recebe alertas CRÍTICOS',
    receive_high BOOLEAN DEFAULT TRUE COMMENT 'Recebe alertas ALTOS',
    receive_medium BOOLEAN DEFAULT FALSE COMMENT 'Recebe alertas MÉDIOS',
    receive_low BOOLEAN DEFAULT FALSE COMMENT 'Recebe alertas BAIXOS',

    -- Horários de recebimento
    receive_weekdays BOOLEAN DEFAULT TRUE COMMENT 'Recebe em dias úteis',
    receive_weekends BOOLEAN DEFAULT FALSE COMMENT 'Recebe em finais de semana',
    start_hour TIME DEFAULT '08:00:00' COMMENT 'Início do horário de alerta',
    end_hour TIME DEFAULT '18:00:00' COMMENT 'Fim do horário de alerta',

    is_active BOOLEAN DEFAULT TRUE COMMENT 'Destinatário ativo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_active (is_active),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Destinatários de alertas de desvio';


-- =====================================================
-- Verificação das tabelas criadas
-- =====================================================

SELECT
    'Tabelas criadas com sucesso!' as status,
    (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = 'f137049_in9aut'
     AND table_name = 'FF_RouteCompliance') as RouteCompliance_exists,
    (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = 'f137049_in9aut'
     AND table_name = 'FF_RouteDeviations') as RouteDeviations_exists,
    (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = 'f137049_in9aut'
     AND table_name = 'FF_AlertRecipients') as AlertRecipients_exists;

SQL;

// Exibir instruções
echo "<!DOCTYPE html>\n<html lang='pt-BR'>\n<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<title>Criar Tabelas de Monitoramento de Conformidade</title>\n";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
    h2 { color: #dc2626; margin-top: 30px; }
    .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
    .info { background: #dbeafe; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0; }
    .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; }
    pre { background: #1f2937; color: #f3f4f6; padding: 20px; border-radius: 6px; overflow-x: auto; }
    code { font-family: 'Courier New', monospace; }
    ol { line-height: 1.8; }
    strong { color: #dc2626; }
</style>
</head>\n<body>\n";

echo "<div class='container'>\n";
echo "<h1>🗄️ Sistema de Monitoramento de Conformidade - Criação de Tabelas</h1>\n";

echo "<div class='warning'>\n";
echo "<strong>⚠️ ATENÇÃO:</strong> Execute este script <strong>APENAS UMA VEZ</strong> no phpMyAdmin!\n";
echo "</div>\n";

echo "<h2>📋 Instruções de Instalação</h2>\n";
echo "<div class='info'>\n";
echo "<ol>\n";
echo "<li>Copie TODO o código SQL abaixo (desde CREATE até o SELECT final)</li>\n";
echo "<li>Acesse o phpMyAdmin: <a href='https://floripa.in9automacao.com.br:2083/phpMyAdmin/' target='_blank'>https://floripa.in9automacao.com.br:2083/phpMyAdmin/</a></li>\n";
echo "<li>Selecione o banco de dados: <code>f137049_in9aut</code></li>\n";
echo "<li>Clique na aba <strong>SQL</strong> (no topo)</li>\n";
echo "<li>Cole o código SQL na área de texto</li>\n";
echo "<li>Clique em <strong>Executar</strong> (ou \"Go\")</li>\n";
echo "<li>Verifique se apareceu: <span style='color: green;'>✓ Tabelas criadas com sucesso!</span></li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<h2>📊 Tabelas que serão criadas</h2>\n";
echo "<ol>\n";
echo "<li><strong>FF_RouteCompliance</strong> - Armazena análises periódicas (a cada 5 min)</li>\n";
echo "<li><strong>FF_RouteDeviations</strong> - Registro de desvios detectados</li>\n";
echo "<li><strong>FF_AlertRecipients</strong> - Cadastro de destinatários de alertas</li>\n";
echo "</ol>\n";

echo "<h2>💻 Código SQL (Copie Tudo Abaixo)</h2>\n";
echo "<pre><code>" . htmlspecialchars($sql) . "</code></pre>\n";

echo "<div class='success'>\n";
echo "<strong>✅ Próximos Passos:</strong><br>\n";
echo "Após executar o SQL com sucesso, prosseguir com:<br>\n";
echo "• Fase 2: Backend Python (criar serviço de monitoramento)<br>\n";
echo "• Fase 3: APIs PHP (criar endpoints)<br>\n";
echo "• Fase 4: Frontend (dashboard de monitoramento)\n";
echo "</div>\n";

echo "</div>\n";
echo "</body>\n</html>";
?>
