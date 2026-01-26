<?php
/**
 * Script para CORRIGIR peças universal
 *
 * Lógica correta:
 * - Peças que TÊM registro em FF_Pecas_Compatibilidade = universal=0 (específicas)
 * - Peças que NÃO TÊM registro em FF_Pecas_Compatibilidade = universal=1 (universais)
 *
 * Executar: https://floripa.in9automacao.com.br/fix-universal-pecas.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");

    $results = [];

    // 1. Verificar se coluna universal existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM FF_Pecas LIKE 'universal'");
    if ($checkColumn->num_rows == 0) {
        // Criar coluna se não existir
        $conn->query("ALTER TABLE FF_Pecas ADD COLUMN universal TINYINT(1) DEFAULT 1 AFTER ativo");
        $results[] = "Coluna 'universal' criada";
    }

    // 2. Buscar IDs de peças que TÊM compatibilidade específica
    $pecasEspecificas = $conn->query("
        SELECT DISTINCT peca_original_id as peca_id FROM FF_Pecas_Compatibilidade
        UNION
        SELECT DISTINCT peca_similar_id as peca_id FROM FF_Pecas_Compatibilidade WHERE peca_similar_id IS NOT NULL
    ");

    $idsEspecificas = [];
    while ($row = $pecasEspecificas->fetch_assoc()) {
        if ($row['peca_id']) {
            $idsEspecificas[] = $row['peca_id'];
        }
    }

    $results[] = "Peças com compatibilidade específica encontradas: " . count($idsEspecificas);

    // 3. Marcar peças específicas como universal=0
    if (count($idsEspecificas) > 0) {
        $idsString = implode(',', array_map('intval', $idsEspecificas));
        $updateEspecificas = $conn->query("UPDATE FF_Pecas SET universal = 0 WHERE id IN ({$idsString})");
        $results[] = "Peças marcadas como ESPECÍFICAS (universal=0): " . $conn->affected_rows;
    }

    // 4. Marcar peças SEM compatibilidade como universal=1
    if (count($idsEspecificas) > 0) {
        $idsString = implode(',', array_map('intval', $idsEspecificas));
        $updateUniversais = $conn->query("UPDATE FF_Pecas SET universal = 1 WHERE id NOT IN ({$idsString})");
    } else {
        $updateUniversais = $conn->query("UPDATE FF_Pecas SET universal = 1");
    }
    $results[] = "Peças marcadas como UNIVERSAIS (universal=1): " . $conn->affected_rows;

    // 5. Estatísticas finais
    $countUniv = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 1");
    $countEsp = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 0");
    $countTotal = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas");

    $totalUniversais = $countUniv->fetch_assoc()['total'];
    $totalEspecificas = $countEsp->fetch_assoc()['total'];
    $total = $countTotal->fetch_assoc()['total'];

    // 6. Listar algumas peças específicas para verificação
    $amostraEspecificas = $conn->query("
        SELECT p.id, p.nome, p.categoria
        FROM FF_Pecas p
        WHERE p.universal = 0
        LIMIT 10
    ");
    $amostra = [];
    while ($row = $amostraEspecificas->fetch_assoc()) {
        $amostra[] = $row;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Correção executada com sucesso',
        'results' => $results,
        'estatisticas' => [
            'total_pecas' => $total,
            'pecas_universais' => $totalUniversais,
            'pecas_especificas' => $totalEspecificas
        ],
        'amostra_especificas' => $amostra
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
