<?php
/**
 * ANÁLISE - Mostra todas as peças SEM compatibilidade cadastrada
 * para você decidir quais devem ser universais
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

    // Buscar IDs de peças que TÊM compatibilidade
    $sqlCompat = "
        SELECT DISTINCT peca_original_id as peca_id FROM FF_Pecas_Compatibilidade WHERE peca_original_id IS NOT NULL
        UNION
        SELECT DISTINCT peca_similar_id as peca_id FROM FF_Pecas_Compatibilidade WHERE peca_similar_id IS NOT NULL
    ";
    $resultCompat = $conn->query($sqlCompat);

    $idsComCompatibilidade = [];
    while ($row = $resultCompat->fetch_assoc()) {
        if ($row['peca_id']) {
            $idsComCompatibilidade[] = intval($row['peca_id']);
        }
    }

    // Buscar peças SEM compatibilidade (candidatas a universal)
    $idsString = count($idsComCompatibilidade) > 0 ? implode(',', $idsComCompatibilidade) : '0';
    $sqlSemCompat = "
        SELECT id, nome, categoria, fornecedor
        FROM FF_Pecas
        WHERE id NOT IN ({$idsString}) AND ativo = 1
        ORDER BY categoria, nome
    ";
    $resultSemCompat = $conn->query($sqlSemCompat);

    $pecasSemCompatibilidade = [];
    while ($row = $resultSemCompat->fetch_assoc()) {
        $pecasSemCompatibilidade[] = $row;
    }

    // Agrupar por categoria
    $porCategoria = [];
    foreach ($pecasSemCompatibilidade as $peca) {
        $cat = $peca['categoria'] ?: 'Sem Categoria';
        if (!isset($porCategoria[$cat])) {
            $porCategoria[$cat] = [];
        }
        $porCategoria[$cat][] = $peca;
    }

    // Contar peças COM compatibilidade
    $sqlComCompat = "
        SELECT id, nome, categoria
        FROM FF_Pecas
        WHERE id IN ({$idsString}) AND ativo = 1
        LIMIT 20
    ";
    $resultComCompat = $conn->query($sqlComCompat);
    $amostraComCompat = [];
    while ($row = $resultComCompat->fetch_assoc()) {
        $amostraComCompat[] = $row;
    }

    echo json_encode([
        'resumo' => [
            'total_pecas_com_compatibilidade' => count($idsComCompatibilidade),
            'total_pecas_sem_compatibilidade' => count($pecasSemCompatibilidade),
            'info' => 'As peças SEM compatibilidade são candidatas a serem UNIVERSAIS'
        ],
        'pecas_SEM_compatibilidade_por_categoria' => $porCategoria,
        'amostra_pecas_COM_compatibilidade' => $amostraComCompat
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
