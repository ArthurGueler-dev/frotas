<?php
/**
 * Diagnóstico do problema de peças universais
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

    $diagnostico = [];

    // 1. Verificar se coluna universal existe
    $checkColumn = $conn->query("SHOW COLUMNS FROM FF_Pecas LIKE 'universal'");
    $diagnostico['coluna_universal_existe'] = $checkColumn->num_rows > 0;

    // 2. Contar peças por status universal
    $countAll = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas");
    $countUniv1 = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 1");
    $countUniv0 = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 0");
    $countUnivNull = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal IS NULL");

    $diagnostico['pecas'] = [
        'total' => $countAll->fetch_assoc()['total'],
        'universal_1' => $countUniv1->fetch_assoc()['total'],
        'universal_0' => $countUniv0->fetch_assoc()['total'],
        'universal_null' => $countUnivNull->fetch_assoc()['total']
    ];

    // 3. Verificar tabela de compatibilidade
    $checkTable = $conn->query("SHOW TABLES LIKE 'FF_Pecas_Compatibilidade'");
    $diagnostico['tabela_compatibilidade_existe'] = $checkTable->num_rows > 0;

    if ($checkTable->num_rows > 0) {
        $countCompat = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas_Compatibilidade");
        $diagnostico['registros_compatibilidade'] = $countCompat->fetch_assoc()['total'];

        // Listar IDs únicos de peças com compatibilidade
        $pecasCompat = $conn->query("
            SELECT DISTINCT peca_original_id as peca_id FROM FF_Pecas_Compatibilidade WHERE peca_original_id IS NOT NULL
            UNION
            SELECT DISTINCT peca_similar_id as peca_id FROM FF_Pecas_Compatibilidade WHERE peca_similar_id IS NOT NULL
        ");
        $idsCompat = [];
        while ($row = $pecasCompat->fetch_assoc()) {
            if ($row['peca_id']) $idsCompat[] = $row['peca_id'];
        }
        $diagnostico['pecas_com_compatibilidade'] = count($idsCompat);
        $diagnostico['ids_pecas_compativeis'] = array_slice($idsCompat, 0, 20); // Primeiros 20

        // Verificar estrutura da tabela
        $estrutura = $conn->query("DESCRIBE FF_Pecas_Compatibilidade");
        $colunas = [];
        while ($row = $estrutura->fetch_assoc()) {
            $colunas[] = $row['Field'];
        }
        $diagnostico['colunas_compatibilidade'] = $colunas;
    }

    // 4. Amostra de peças
    $amostra = $conn->query("SELECT id, nome, categoria, universal FROM FF_Pecas LIMIT 10");
    $pecasAmostra = [];
    while ($row = $amostra->fetch_assoc()) {
        $pecasAmostra[] = $row;
    }
    $diagnostico['amostra_pecas'] = $pecasAmostra;

    echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
