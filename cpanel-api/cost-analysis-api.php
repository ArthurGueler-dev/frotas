<?php
/**
 * API de Analise de Custos para Dashboard
 *
 * Endpoints:
 * GET ?action=summary                          - Resumo geral de custos
 * GET ?action=monthly&year=2026               - Custos mensais do ano
 * GET ?action=by_vehicle&plate=ABC1234        - Custos por veiculo
 * GET ?action=vehicles_list                   - Lista de veiculos para filtro
 * GET ?action=areas_list                      - Lista de areas para filtro
 * GET ?action=export                          - Exportar dados detalhados para Excel
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = isset($_GET['action']) ? $_GET['action'] : 'summary';

    switch ($action) {
        case 'summary':
            header('Content-Type: application/json; charset=utf-8');
            getSummary($pdo);
            break;
        case 'monthly':
            header('Content-Type: application/json; charset=utf-8');
            getMonthly($pdo);
            break;
        case 'by_vehicle':
            header('Content-Type: application/json; charset=utf-8');
            getByVehicle($pdo);
            break;
        case 'vehicles_list':
            header('Content-Type: application/json; charset=utf-8');
            getVehiclesList($pdo);
            break;
        case 'areas_list':
            header('Content-Type: application/json; charset=utf-8');
            getAreasList($pdo);
            break;
        case 'export':
            exportToExcel($pdo);
            break;
        case 'debug':
            header('Content-Type: application/json; charset=utf-8');
            getDebugInfo($pdo);
            break;
        default:
            header('Content-Type: application/json; charset=utf-8');
            getSummary($pdo);
    }

} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Erro: ' . $e->getMessage()
    ));
}

/**
 * Construir clausula WHERE baseada nos filtros
 */
function buildWhereClause($params) {
    $where = "WHERE os.status = 'Finalizada'";
    $bindings = array();

    // Filtro por mes/ano (se all=true, não filtra por data)
    $all = isset($params['all']) && $params['all'] === true;

    if (!$all) {
        if (isset($params['month']) && isset($params['year'])) {
            $where .= " AND MONTH(os.data_finalizacao) = :month AND YEAR(os.data_finalizacao) = :year";
            $bindings[':month'] = (int)$params['month'];
            $bindings[':year'] = (int)$params['year'];
        } elseif (isset($params['year'])) {
            $where .= " AND YEAR(os.data_finalizacao) = :year";
            $bindings[':year'] = (int)$params['year'];
        }
    }

    // Filtro por placa
    if (!empty($params['plate'])) {
        $where .= " AND os.placa_veiculo = :plate";
        $bindings[':plate'] = $params['plate'];
    }

    // Filtro por area (via JOIN com Vehicles)
    if (!empty($params['area_id'])) {
        $where .= " AND v.area_id = :area_id";
        $bindings[':area_id'] = (int)$params['area_id'];
    }

    return array('where' => $where, 'bindings' => $bindings);
}

/**
 * Resumo geral de custos
 */
function getSummary($pdo) {
    $all = isset($_GET['all']) && $_GET['all'] === 'true';
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

    $filters = buildWhereClause(array(
        'all' => $all,
        'month' => $all ? null : $month,
        'year' => $all ? null : $year,
        'plate' => isset($_GET['plate']) ? $_GET['plate'] : null,
        'area_id' => isset($_GET['area_id']) ? $_GET['area_id'] : null
    ));

    $whereClause = $filters['where'];
    $params = $filters['bindings'];

    // Total de manutencao
    $sql = "SELECT
                COUNT(DISTINCT os.id) as total_os,
                COALESCE(SUM(
                    (SELECT COALESCE(SUM(oi.valor_total), 0)
                     FROM ordemservico_itens oi
                     WHERE oi.ordem_numero = os.ordem_numero)
                ), 0) as custo_total
            FROM ordemservico os
            LEFT JOIN Vehicles v ON v.LicensePlate = os.placa_veiculo
            $whereClause";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $totais = $stmt->fetch(PDO::FETCH_ASSOC);

    // Por tipo de ocorrencia
    $sql = "SELECT
                os.ocorrencia as tipo,
                COUNT(DISTINCT os.id) as quantidade,
                COALESCE(SUM(
                    (SELECT COALESCE(SUM(oi.valor_total), 0)
                     FROM ordemservico_itens oi
                     WHERE oi.ordem_numero = os.ordem_numero)
                ), 0) as custo
            FROM ordemservico os
            LEFT JOIN Vehicles v ON v.LicensePlate = os.placa_veiculo
            $whereClause
            GROUP BY os.ocorrencia";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $porTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $preventivas = 0;
    $corretivas = 0;
    $custoPorTipo = array();

    foreach ($porTipo as $tipo) {
        $custoPorTipo[$tipo['tipo'] ?: 'Outros'] = (float)$tipo['custo'];
        if ($tipo['tipo'] === 'Preventiva') {
            $preventivas = (int)$tipo['quantidade'];
        } elseif ($tipo['tipo'] === 'Corretiva') {
            $corretivas = (int)$tipo['quantidade'];
        }
    }

    // Contar pecas trocadas (tipo = Produto, ou qualquer coisa que não seja Serviço)
    $sql = "SELECT COUNT(*) as total_pecas, COALESCE(SUM(oi.quantidade), 0) as quantidade_total
            FROM ordemservico os
            LEFT JOIN Vehicles v ON v.LicensePlate = os.placa_veiculo
            JOIN ordemservico_itens oi ON oi.ordem_numero = os.ordem_numero
            $whereClause
            AND (oi.tipo = 'Produto' OR oi.tipo = 'Peça' OR oi.tipo = 'Peca' OR oi.tipo NOT LIKE '%ervi%')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pecas = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'success' => true,
        'periodo' => array(
            'todos' => $all,
            'mes' => $all ? null : $month,
            'ano' => $all ? null : $year,
            'nome_mes' => $all ? 'Todos' : getMonthName($month)
        ),
        'resumo' => array(
            'total_os' => (int)$totais['total_os'],
            'custo_total' => (float)$totais['custo_total'],
            'custo_formatado' => 'R$ ' . number_format((float)$totais['custo_total'], 2, ',', '.'),
            'preventivas_count' => $preventivas,
            'corretivas_count' => $corretivas,
            'pecas_trocadas' => (int)$pecas['quantidade_total']
        ),
        'custo_por_tipo' => $custoPorTipo
    ), JSON_UNESCAPED_UNICODE);
}

/**
 * Custos mensais do ano - para grafico
 */
function getMonthly($pdo) {
    $all = isset($_GET['all']) && $_GET['all'] === 'true';
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $plate = isset($_GET['plate']) ? $_GET['plate'] : null;
    $area_id = isset($_GET['area_id']) ? $_GET['area_id'] : null;

    if ($all) {
        $whereBase = "WHERE os.status = 'Finalizada'";
        $params = array();
    } else {
        $whereBase = "WHERE os.status = 'Finalizada' AND YEAR(os.data_finalizacao) = :year";
        $params = array(':year' => $year);
    }

    if ($plate && $plate !== '') {
        $whereBase .= " AND os.placa_veiculo = :plate";
        $params[':plate'] = $plate;
    }

    if ($area_id && $area_id !== '') {
        $whereBase .= " AND v.area_id = :area_id";
        $params[':area_id'] = (int)$area_id;
    }

    // Custos por mes e tipo
    $sql = "SELECT
                MONTH(os.data_finalizacao) as mes,
                os.ocorrencia as tipo,
                COUNT(DISTINCT os.id) as quantidade_os,
                COALESCE(SUM(
                    (SELECT COALESCE(SUM(oi.valor_total), 0)
                     FROM ordemservico_itens oi
                     WHERE oi.ordem_numero = os.ordem_numero)
                ), 0) as custo
            FROM ordemservico os
            LEFT JOIN Vehicles v ON v.LicensePlate = os.placa_veiculo
            $whereBase
            GROUP BY MONTH(os.data_finalizacao), os.ocorrencia
            ORDER BY mes";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar por mes
    $meses = array();
    for ($i = 1; $i <= 12; $i++) {
        $meses[$i] = array(
            'mes' => $i,
            'nome' => getMonthName($i),
            'Preventiva' => 0,
            'Corretiva' => 0,
            'Garantia' => 0,
            'total' => 0,
            'os_count' => 0
        );
    }

    foreach ($dados as $row) {
        $mes = (int)$row['mes'];
        $tipo = $row['tipo'] ?: 'Outros';
        if (isset($meses[$mes][$tipo])) {
            $meses[$mes][$tipo] = (float)$row['custo'];
        }
        $meses[$mes]['total'] += (float)$row['custo'];
        $meses[$mes]['os_count'] += (int)$row['quantidade_os'];
    }

    // Preparar dados para Chart.js
    $labels = array();
    $dataPreventiva = array();
    $dataCorretiva = array();
    $dataGarantia = array();

    foreach ($meses as $mes) {
        $labels[] = substr($mes['nome'], 0, 3);
        $dataPreventiva[] = $mes['Preventiva'];
        $dataCorretiva[] = $mes['Corretiva'];
        $dataGarantia[] = $mes['Garantia'];
    }

    echo json_encode(array(
        'success' => true,
        'ano' => $year,
        'meses' => array_values($meses),
        'chart_data' => array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'Preventiva',
                    'data' => $dataPreventiva,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)'
                ),
                array(
                    'label' => 'Corretiva',
                    'data' => $dataCorretiva,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)'
                ),
                array(
                    'label' => 'Garantia',
                    'data' => $dataGarantia,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)'
                )
            )
        )
    ), JSON_UNESCAPED_UNICODE);
}

/**
 * Custos por veiculo
 */
function getByVehicle($pdo) {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $area_id = isset($_GET['area_id']) ? $_GET['area_id'] : null;

    $whereBase = "WHERE os.status = 'Finalizada' AND YEAR(os.data_finalizacao) = :year";
    $params = array(':year' => $year);

    if ($area_id && $area_id !== '') {
        $whereBase .= " AND v.area_id = :area_id";
        $params[':area_id'] = (int)$area_id;
    }

    $sql = "SELECT
                os.placa_veiculo as placa,
                v.VehicleName as modelo,
                a.name as area,
                COUNT(DISTINCT os.id) as total_os,
                COALESCE(SUM(
                    (SELECT COALESCE(SUM(oi.valor_total), 0)
                     FROM ordemservico_itens oi
                     WHERE oi.ordem_numero = os.ordem_numero)
                ), 0) as custo_total
            FROM ordemservico os
            LEFT JOIN Vehicles v ON v.LicensePlate = os.placa_veiculo
            LEFT JOIN areas a ON a.id = v.area_id
            $whereBase
            GROUP BY os.placa_veiculo, v.VehicleName, a.name
            ORDER BY custo_total DESC
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'success' => true,
        'ano' => $year,
        'ranking_veiculos' => $veiculos
    ), JSON_UNESCAPED_UNICODE);
}

/**
 * Lista de veiculos para dropdown
 */
function getVehiclesList($pdo) {
    $area_id = isset($_GET['area_id']) ? $_GET['area_id'] : null;

    $sql = "SELECT DISTINCT v.LicensePlate as placa, v.VehicleName as modelo
            FROM Vehicles v";
    $params = array();

    if ($area_id && $area_id !== '') {
        $sql .= " WHERE v.area_id = :area_id";
        $params[':area_id'] = (int)$area_id;
    }

    $sql .= " ORDER BY v.LicensePlate";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'success' => true,
        'total' => count($veiculos),
        'veiculos' => $veiculos
    ), JSON_UNESCAPED_UNICODE);
}

/**
 * Lista de areas para dropdown
 */
function getAreasList($pdo) {
    $sql = "SELECT id, name FROM areas ORDER BY name";
    $stmt = $pdo->query($sql);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'success' => true,
        'areas' => $areas
    ), JSON_UNESCAPED_UNICODE);
}

/**
 * Exportar dados detalhados para Excel (CSV)
 */
function exportToExcel($pdo) {
    $all = isset($_GET['all']) && $_GET['all'] === 'true';
    $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $plate = isset($_GET['plate']) ? $_GET['plate'] : null;
    $area_id = isset($_GET['area_id']) ? $_GET['area_id'] : null;

    $whereBase = "WHERE os.status = 'Finalizada'";
    $params = array();

    if (!$all) {
        if ($month) {
            $whereBase .= " AND MONTH(os.data_finalizacao) = :month";
            $params[':month'] = $month;
        }

        $whereBase .= " AND YEAR(os.data_finalizacao) = :year";
        $params[':year'] = $year;
    }

    if ($plate && $plate !== '') {
        $whereBase .= " AND os.placa_veiculo = :plate";
        $params[':plate'] = $plate;
    }

    if ($area_id && $area_id !== '') {
        $whereBase .= " AND v.area_id = :area_id";
        $params[':area_id'] = (int)$area_id;
    }

    // Buscar OS com detalhes
    $sql = "SELECT
                os.ordem_numero,
                os.placa_veiculo,
                v.VehicleName as modelo,
                a.name as area,
                os.ocorrencia as tipo_manutencao,
                os.status,
                DATE_FORMAT(os.data_criacao, '%d/%m/%Y') as data_abertura,
                DATE_FORMAT(os.data_finalizacao, '%d/%m/%Y') as data_finalizacao,
                os.km_veiculo,
                os.responsavel,
                COALESCE(
                    (SELECT SUM(oi.valor_total) FROM ordemservico_itens oi WHERE oi.ordem_numero = os.ordem_numero),
                    0
                ) as custo_total
            FROM ordemservico os
            LEFT JOIN Vehicles v ON v.LicensePlate = os.placa_veiculo
            LEFT JOIN areas a ON a.id = v.area_id
            $whereBase
            ORDER BY os.data_finalizacao DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ordens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar itens de cada OS
    $ordensComItens = array();
    foreach ($ordens as $os) {
        $stmtItens = $pdo->prepare("SELECT tipo, categoria, descricao, quantidade, valor_unitario, valor_total
                                     FROM ordemservico_itens WHERE ordem_numero = :ordem");
        $stmtItens->execute(array(':ordem' => $os['ordem_numero']));
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        $os['itens'] = $itens;
        $ordensComItens[] = $os;
    }

    // Gerar CSV
    $filename = 'analise_custos_' . $year . ($month ? '_' . str_pad($month, 2, '0', STR_PAD_LEFT) : '') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // BOM para Excel reconhecer UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Cabecalho
    fputcsv($output, array(
        'Numero OS', 'Placa', 'Modelo', 'Area', 'Tipo Manutencao',
        'Data Abertura', 'Data Finalizacao', 'KM', 'Responsavel', 'Custo Total',
        'Item Tipo', 'Item Categoria', 'Item Descricao', 'Qtd', 'Valor Unit', 'Valor Total Item'
    ), ';');

    // Dados
    foreach ($ordensComItens as $os) {
        if (empty($os['itens'])) {
            fputcsv($output, array(
                $os['ordem_numero'],
                $os['placa_veiculo'],
                $os['modelo'],
                $os['area'],
                $os['tipo_manutencao'],
                $os['data_abertura'],
                $os['data_finalizacao'],
                $os['km_veiculo'],
                $os['responsavel'],
                number_format($os['custo_total'], 2, ',', '.'),
                '', '', '', '', '', ''
            ), ';');
        } else {
            foreach ($os['itens'] as $index => $item) {
                fputcsv($output, array(
                    $index === 0 ? $os['ordem_numero'] : '',
                    $index === 0 ? $os['placa_veiculo'] : '',
                    $index === 0 ? $os['modelo'] : '',
                    $index === 0 ? $os['area'] : '',
                    $index === 0 ? $os['tipo_manutencao'] : '',
                    $index === 0 ? $os['data_abertura'] : '',
                    $index === 0 ? $os['data_finalizacao'] : '',
                    $index === 0 ? $os['km_veiculo'] : '',
                    $index === 0 ? $os['responsavel'] : '',
                    $index === 0 ? number_format($os['custo_total'], 2, ',', '.') : '',
                    $item['tipo'],
                    $item['categoria'],
                    $item['descricao'],
                    $item['quantidade'],
                    number_format($item['valor_unitario'], 2, ',', '.'),
                    number_format($item['valor_total'], 2, ',', '.')
                ), ';');
            }
        }
    }

    fclose($output);
    exit;
}

/**
 * Helper - Nome do mes
 */
function getMonthName($month) {
    $meses = array(
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    );
    return isset($meses[$month]) ? $meses[$month] : '';
}

/**
 * Debug - Ver valores do campo tipo em ordemservico_itens
 */
function getDebugInfo($pdo) {
    $resultado = array('success' => true);

    // 1. Valores distintos do campo tipo
    $stmt = $pdo->query("SELECT DISTINCT tipo, COUNT(*) as quantidade FROM ordemservico_itens GROUP BY tipo");
    $resultado['tipos_distintos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Itens de OS finalizadas
    $stmt = $pdo->query("SELECT oi.tipo, oi.categoria, oi.descricao, oi.quantidade, os.ordem_numero, os.status
                         FROM ordemservico_itens oi
                         JOIN ordemservico os ON os.ordem_numero = oi.ordem_numero
                         WHERE os.status = 'Finalizada'
                         ORDER BY oi.id DESC
                         LIMIT 20");
    $resultado['itens_os_finalizadas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Total de itens em OS finalizadas por tipo
    $stmt = $pdo->query("SELECT oi.tipo, COUNT(*) as total, SUM(oi.quantidade) as qtd_total
                         FROM ordemservico_itens oi
                         JOIN ordemservico os ON os.ordem_numero = oi.ordem_numero
                         WHERE os.status = 'Finalizada'
                         GROUP BY oi.tipo");
    $resultado['totais_por_tipo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. OS finalizadas recentes
    $stmt = $pdo->query("SELECT os.ordem_numero, os.placa_veiculo, os.status, os.data_finalizacao,
                         (SELECT COUNT(*) FROM ordemservico_itens oi WHERE oi.ordem_numero = os.ordem_numero) as qtd_itens
                         FROM ordemservico os
                         WHERE os.status = 'Finalizada'
                         ORDER BY os.data_finalizacao DESC
                         LIMIT 5");
    $resultado['os_finalizadas_recentes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
