<?php
/**
 * FIX DIRETO - Corrigir peças universal AGORA
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");

    $results = [];

    // PASSO 1: Setar TODAS as peças como universal=0 primeiro
    $conn->query("UPDATE FF_Pecas SET universal = 0");
    $results[] = "RESET: Todas as peças setadas para universal=0: " . $conn->affected_rows;

    // PASSO 2: Buscar IDs de peças que TÊM compatibilidade
    $sql = "
        SELECT DISTINCT peca_original_id as peca_id FROM FF_Pecas_Compatibilidade WHERE peca_original_id IS NOT NULL
        UNION
        SELECT DISTINCT peca_similar_id as peca_id FROM FF_Pecas_Compatibilidade WHERE peca_similar_id IS NOT NULL
    ";
    $result = $conn->query($sql);

    $idsComCompatibilidade = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['peca_id']) {
            $idsComCompatibilidade[] = intval($row['peca_id']);
        }
    }
    $results[] = "Peças COM compatibilidade específica: " . count($idsComCompatibilidade);

    // PASSO 3: Peças COM compatibilidade ficam universal=0 (já estão assim)
    // Peças SEM compatibilidade ficam universal=1
    if (count($idsComCompatibilidade) > 0) {
        $idsString = implode(',', $idsComCompatibilidade);
        $sqlUniversal = "UPDATE FF_Pecas SET universal = 1 WHERE id NOT IN ({$idsString})";
        $conn->query($sqlUniversal);
        $results[] = "Peças SEM compatibilidade setadas para universal=1: " . $conn->affected_rows;
    }

    // PASSO 4: Verificar resultado
    $countUniv1 = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 1");
    $countUniv0 = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 0");

    $totalUniversais = $countUniv1->fetch_assoc()['total'];
    $totalEspecificas = $countUniv0->fetch_assoc()['total'];

    // Amostra de peças específicas (universal=0)
    $amostraEsp = $conn->query("SELECT id, nome, categoria FROM FF_Pecas WHERE universal = 0 LIMIT 5");
    $especificas = [];
    while ($row = $amostraEsp->fetch_assoc()) {
        $especificas[] = $row;
    }

    // Amostra de peças universais (universal=1)
    $amostraUniv = $conn->query("SELECT id, nome, categoria FROM FF_Pecas WHERE universal = 1 LIMIT 5");
    $universais = [];
    while ($row = $amostraUniv->fetch_assoc()) {
        $universais[] = $row;
    }

    echo json_encode([
        'success' => true,
        'message' => 'CORRIGIDO COM SUCESSO!',
        'results' => $results,
        'estatisticas_finais' => [
            'pecas_universais' => $totalUniversais,
            'pecas_especificas' => $totalEspecificas
        ],
        'amostra_especificas' => $especificas,
        'amostra_universais' => $universais
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
