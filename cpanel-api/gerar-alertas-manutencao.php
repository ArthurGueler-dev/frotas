<?php
/**
 * API para Gerar Alertas de Manutenção Preventiva
 *
 * Esta API é o motor do sistema de alertas. Ela:
 * 1. Busca todos os veículos ativos
 * 2. Para cada veículo, busca o modelo e plano de manutenção
 * 3. Busca última OS preventiva finalizada (por item)
 * 4. Busca KM atual do veículo
 * 5. Calcula km_restantes e dias_restantes
 * 6. Cria/atualiza alertas em avisos_manutencao
 *
 * Endpoints:
 * POST /gerar-alertas-manutencao.php - Gerar alertas para todos os veículos
 * POST /gerar-alertas-manutencao.php?placa=XXX - Gerar alertas para um veículo específico
 * GET  /gerar-alertas-manutencao.php?action=stats - Retornar estatísticas de alertas
 *
 * @author Claude
 * @version 1.0
 * @date 2026-01-21
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuração do banco
require_once __DIR__ . '/db-config.php';

// Configurações de thresholds para níveis de alerta
define('THRESHOLD_CRITICO_KM', 0);      // Vencido (km <= 0)
define('THRESHOLD_ALTO_KM', 1000);      // < 1.000 km
define('THRESHOLD_MEDIO_KM', 3000);     // < 3.000 km
define('THRESHOLD_BAIXO_KM', 5000);     // < 5.000 km (mostrar mais veículos)

define('THRESHOLD_CRITICO_DIAS', 0);    // Vencido (dias <= 0)
define('THRESHOLD_ALTO_DIAS', 15);      // < 15 dias
define('THRESHOLD_MEDIO_DIAS', 30);     // < 30 dias
define('THRESHOLD_BAIXO_DIAS', 45);     // < 45 dias

// Função para enviar resposta JSON
function sendResponse($success, $data = null, $error = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Função para calcular nível de alerta baseado em KM
function calcularNivelAlertaKm($kmRestantes) {
    if ($kmRestantes <= THRESHOLD_CRITICO_KM) {
        return 'Critico';
    } elseif ($kmRestantes < THRESHOLD_ALTO_KM) {
        return 'Alto';
    } elseif ($kmRestantes < THRESHOLD_MEDIO_KM) {
        return 'Medio';
    } elseif ($kmRestantes < THRESHOLD_BAIXO_KM) {
        return 'Baixo';
    }
    return null; // Não precisa de alerta
}

// Função para calcular nível de alerta baseado em dias
function calcularNivelAlertaDias($diasRestantes) {
    if ($diasRestantes <= THRESHOLD_CRITICO_DIAS) {
        return 'Critico';
    } elseif ($diasRestantes < THRESHOLD_ALTO_DIAS) {
        return 'Alto';
    } elseif ($diasRestantes < THRESHOLD_MEDIO_DIAS) {
        return 'Medio';
    } elseif ($diasRestantes < THRESHOLD_BAIXO_DIAS) {
        return 'Baixo';
    }
    return null; // Não precisa de alerta
}

// Função para determinar o nível mais crítico entre KM e dias
function determinarNivelMaisCritico($nivelKm, $nivelDias) {
    $ordem = ['Critico' => 1, 'Alto' => 2, 'Medio' => 3, 'Baixo' => 4];

    if ($nivelKm === null && $nivelDias === null) {
        return null;
    }
    if ($nivelKm === null) {
        return $nivelDias;
    }
    if ($nivelDias === null) {
        return $nivelKm;
    }

    return $ordem[$nivelKm] <= $ordem[$nivelDias] ? $nivelKm : $nivelDias;
}

// Função para calcular status do alerta
function calcularStatus($kmRestantes, $diasRestantes) {
    if ($kmRestantes <= 0 || $diasRestantes <= 0) {
        return 'Vencido';
    }
    return 'Pendente';
}

// Função para buscar KM atual do veículo
function buscarKmAtual($conn, $placa, $vehicleId) {
    // Primeiro tenta buscar da tabela daily_mileage (usa vehicle_plate, não vehicle_id)
    $sql = "SELECT odometer_end
            FROM daily_mileage
            WHERE vehicle_plate = ? AND sync_status = 'success'
            ORDER BY date DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $placa);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['odometer_end'] > 0) {
        return intval($result['odometer_end']);
    }

    // Fallback: buscar última OS para estimar KM
    $sql2 = "SELECT MAX(km_veiculo) as ultimo_km
             FROM ordemservico
             WHERE placa_veiculo = ? AND km_veiculo > 0";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bindValue(1, $placa);
    $stmt2->execute();
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($result2 && $result2['ultimo_km'] > 0) {
        return intval($result2['ultimo_km']);
    }

    return 0;
}

// Função para buscar KM da última OS finalizada para um item de manutenção
function buscarKmUltimaOS($conn, $placa, $descricaoItem) {
    // Buscar última OS finalizada que contenha item similar
    $sql = "SELECT os.km_veiculo, os.data_criacao
            FROM ordemservico os
            INNER JOIN ordemservico_itens oi ON os.ordem_numero = oi.ordem_numero
            WHERE os.placa_veiculo = ?
              AND os.status IN ('Finalizada', 'Concluida', 'Fechada')
              AND os.ocorrencia = 'Preventiva'
              AND (oi.descricao LIKE ? OR oi.descricao LIKE ?)
            ORDER BY os.data_criacao DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $placa);
    $stmt->bindValue(2, '%' . $descricaoItem . '%');
    // Busca também por palavras-chave do item
    $palavrasChave = extrairPalavrasChave($descricaoItem);
    $stmt->bindValue(3, '%' . $palavrasChave . '%');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        return [
            'km' => intval($result['km_veiculo']),
            'data' => $result['data_criacao']
        ];
    }

    return null;
}

// Função para extrair palavras-chave de uma descrição
function extrairPalavrasChave($descricao) {
    // Lista de palavras-chave de manutenção
    $keywords = [
        'óleo', 'oleo', 'filtro', 'freio', 'pastilha', 'disco',
        'correia', 'vela', 'embreagem', 'suspensão', 'suspensao',
        'amortecedor', 'pneu', 'alinhamento', 'balanceamento',
        'ar condicionado', 'bateria', 'fluido', 'transmissão',
        'arrefecimento', 'radiador', 'bomba', 'direção', 'direcao'
    ];

    $descricaoLower = mb_strtolower($descricao, 'UTF-8');

    foreach ($keywords as $keyword) {
        if (mb_strpos($descricaoLower, $keyword) !== false) {
            return $keyword;
        }
    }

    // Se não encontrar, retorna primeiras 3 palavras
    $palavras = explode(' ', $descricao);
    return implode(' ', array_slice($palavras, 0, 3));
}

// Função para converter intervalo de tempo em dias
function converterIntervaloEmDias($intervalo) {
    if (empty($intervalo)) {
        return null;
    }

    $intervaloLower = mb_strtolower($intervalo, 'UTF-8');

    // Extrair número
    preg_match('/(\d+)/', $intervalo, $matches);
    $numero = isset($matches[1]) ? intval($matches[1]) : 0;

    if ($numero === 0) {
        return null;
    }

    // Detectar unidade
    if (strpos($intervaloLower, 'ano') !== false) {
        return $numero * 365;
    } elseif (strpos($intervaloLower, 'mes') !== false || strpos($intervaloLower, 'mês') !== false) {
        return $numero * 30;
    } elseif (strpos($intervaloLower, 'semana') !== false) {
        return $numero * 7;
    } elseif (strpos($intervaloLower, 'dia') !== false) {
        return $numero;
    }

    // Padrão: assumir meses
    return $numero * 30;
}

// Função principal para gerar alertas de um veículo
function gerarAlertasVeiculo($conn, $veiculo) {
    $alertasGerados = 0;
    $alertasAtualizados = 0;
    $erros = [];

    $placa = $veiculo['LicensePlate'];
    $vehicleId = $veiculo['ID'];
    $modelo = isset($veiculo['VehicleName']) ? $veiculo['VehicleName'] : '';

    // Buscar KM atual do veículo
    $kmAtual = buscarKmAtual($conn, $placa, $vehicleId);

    if ($kmAtual <= 0) {
        return [
            'gerados' => 0,
            'atualizados' => 0,
            'erros' => ['KM atual não encontrado para ' . $placa]
        ];
    }

    // Buscar plano de manutenção do modelo
    // Lista de palavras-chave de modelos conhecidos para fazer matching inteligente
    $modelosConhecidos = [
        'HILUX', 'S10', 'SAVEIRO', 'STRADA', 'MOBI', 'ONIX', 'HB20', 'HR-V', 'HRV',
        'MONTANA', 'CELTA', 'CLASSIC', 'L200', 'TRITON', 'SANDERO', 'CARGO', 'DAILY',
        'ACCELO', 'ATEGO', 'DELIVERY', '10.160', '11.180', 'TORO', 'RANGER', 'AMAROK',
        'DUSTER', 'CAPTUR', 'KICKS', 'COMPASS', 'RENEGADE', 'TRACKER', 'SPIN', 'COBALT'
    ];

    // Extrair palavra-chave do modelo do veículo
    $modeloUpper = mb_strtoupper($modelo, 'UTF-8');
    $palavraChaveModelo = null;

    foreach ($modelosConhecidos as $modeloConhecido) {
        if (strpos($modeloUpper, $modeloConhecido) !== false) {
            $palavraChaveModelo = $modeloConhecido;
            break;
        }
    }

    // Buscar plano usando palavra-chave encontrada ou modelo original
    $termoBusca = $palavraChaveModelo ? $palavraChaveModelo : $modelo;

    $sqlPlano = "SELECT * FROM `Planos_Manutenção` WHERE modelo_carro LIKE ? ORDER BY km_recomendado ASC";
    $stmtPlano = $conn->prepare($sqlPlano);
    $stmtPlano->bindValue(1, '%' . $termoBusca . '%');
    $stmtPlano->execute();
    $itensPlano = $stmtPlano->fetchAll(PDO::FETCH_ASSOC);

    if (empty($itensPlano) && $palavraChaveModelo) {
        // Se não encontrou com palavra-chave, tentar com modelo original
        $stmtPlano2 = $conn->prepare($sqlPlano);
        $stmtPlano2->bindValue(1, '%' . $modelo . '%');
        $stmtPlano2->execute();
        $itensPlano = $stmtPlano2->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($itensPlano)) {
        // Última tentativa: primeira palavra significativa (ignorando marcas)
        $palavrasIgnorar = ['VW', 'FIAT', 'CHEVROLET', 'TOYOTA', 'FORD', 'HYUNDAI', 'HONDA', 'RENAULT', 'IVECO', 'MB', 'MERCEDES', 'VOLKSWAGEN', 'GM', 'NOVA', 'NOVO'];
        $palavras = preg_split('/[\s\/]+/', $modelo);
        foreach ($palavras as $palavra) {
            $palavraUpper = mb_strtoupper(trim($palavra), 'UTF-8');
            if (strlen($palavraUpper) > 2 && !in_array($palavraUpper, $palavrasIgnorar)) {
                $stmtPlano3 = $conn->prepare($sqlPlano);
                $stmtPlano3->bindValue(1, '%' . $palavraUpper . '%');
                $stmtPlano3->execute();
                $itensPlano = $stmtPlano3->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($itensPlano)) {
                    break;
                }
            }
        }
    }

    if (empty($itensPlano)) {
        return [
            'gerados' => 0,
            'atualizados' => 0,
            'erros' => ['Plano não encontrado para: ' . $modelo . ' (buscou: ' . $termoBusca . ')']
        ];
    }

    // Processar cada item do plano
    foreach ($itensPlano as $item) {
        $planoId = $item['id'];
        $descricao = $item['descricao_titulo'];
        $kmRecomendado = intval($item['km_recomendado']);
        $intervaloTempo = isset($item['intervalo_tempo']) ? $item['intervalo_tempo'] : null;
        $custo = isset($item['custo_estimado']) ? floatval($item['custo_estimado']) : 0;
        $criticidade = isset($item['criticidade']) ? $item['criticidade'] : 'Média';

        if ($kmRecomendado <= 0) {
            continue; // Pular itens sem KM definido
        }

        // FILTRO: Ignorar manutenções ÚNICAS (revisão de amaciamento, pós-venda, etc)
        // Se o intervalo é muito pequeno (< 5.000 km) e o veículo já passou muito (> 3x o intervalo),
        // provavelmente é uma manutenção única que não se repete
        $descricaoLower = mb_strtolower($descricao, 'UTF-8');
        $ehManutencaoUnica = (
            strpos($descricaoLower, 'amaciamento') !== false ||
            strpos($descricaoLower, 'pós-venda') !== false ||
            strpos($descricaoLower, 'pos-venda') !== false ||
            strpos($descricaoLower, 'primeira revisão') !== false ||
            strpos($descricaoLower, 'primeira revisao') !== false ||
            strpos($descricaoLower, 'break-in') !== false ||
            ($kmRecomendado <= 5000 && $kmAtual > ($kmRecomendado * 5))
        );

        if ($ehManutencaoUnica) {
            continue; // Pular manutenções únicas para veículos com km alto
        }

        // Buscar última OS deste item
        $ultimaOS = buscarKmUltimaOS($conn, $placa, $descricao);

        // Calcular próximo KM programado
        // LÓGICA CORRETA: calcular do KM atual para FRENTE, não de trás pra frente
        if ($ultimaOS && $ultimaOS['km'] > 0) {
            // Se há histórico: próxima = última + intervalo
            $kmBase = $ultimaOS['km'];
            $kmProgramado = $kmBase + $kmRecomendado;

            // Se já passou do programado, calcular o PRÓXIMO intervalo
            // (não mostrar como vencido se nunca foi registrado que venceu)
            while ($kmProgramado < $kmAtual - 500) {
                // Tolerância de 500km para não pular manutenções realmente vencidas
                $kmProgramado += $kmRecomendado;
            }
        } else {
            // SEM histórico: calcular próximo múltiplo do intervalo a partir do KM atual
            // Ex: km atual = 47.000, intervalo = 10.000 → próximo = 50.000
            $kmProgramado = ceil($kmAtual / $kmRecomendado) * $kmRecomendado;

            // Se o veículo está exatamente no múltiplo, a próxima é o intervalo seguinte
            if ($kmProgramado == $kmAtual) {
                $kmProgramado += $kmRecomendado;
            }
        }

        $dataUltimaOS = $ultimaOS ? $ultimaOS['data'] : null;

        // Calcular KM restantes (agora sempre será positivo ou levemente negativo)
        $kmRestantes = $kmProgramado - $kmAtual;

        // Calcular dias restantes (se houver intervalo de tempo)
        $diasRestantes = null;
        $dataProxima = null;

        if ($intervaloTempo) {
            $diasIntervalo = converterIntervaloEmDias($intervaloTempo);

            if ($diasIntervalo && $dataUltimaOS) {
                // Se há histórico de OS, calcular próxima data baseada na última
                $dataBase = new DateTime($dataUltimaOS);
                $dataProximaObj = clone $dataBase;
                $dataProximaObj->modify("+{$diasIntervalo} days");
                $dataProxima = $dataProximaObj->format('Y-m-d');

                $hoje = new DateTime();
                $diff = $hoje->diff($dataProximaObj);
                $diasRestantes = $diff->invert ? -$diff->days : $diff->days;
            } elseif ($diasIntervalo && !$dataUltimaOS) {
                // SEM histórico: não gerar alerta de tempo vencido
                // Próxima manutenção será daqui a [intervalo] dias
                $dataProximaObj = new DateTime();
                $dataProximaObj->modify("+{$diasIntervalo} days");
                $dataProxima = $dataProximaObj->format('Y-m-d');
                $diasRestantes = $diasIntervalo; // Faltam X dias (positivo)
            }
        }

        // Determinar nível de alerta
        $nivelKm = calcularNivelAlertaKm($kmRestantes);
        $nivelDias = $diasRestantes !== null ? calcularNivelAlertaDias($diasRestantes) : null;
        $nivelAlerta = determinarNivelMaisCritico($nivelKm, $nivelDias);

        // Se não precisa de alerta, verificar se existe um para atualizar para "Em dia"
        if ($nivelAlerta === null) {
            // Verificar se existe alerta ativo para este item e marcar como resolvido
            $sqlVerifica = "SELECT id FROM avisos_manutencao
                            WHERE placa_veiculo = ? AND plano_id = ? AND status NOT IN ('Concluido', 'Cancelado')";
            $stmtVerifica = $conn->prepare($sqlVerifica);
            $stmtVerifica->bindValue(1, $placa);
            $stmtVerifica->bindValue(2, $planoId, PDO::PARAM_INT);
            $stmtVerifica->execute();
            $alertaExistente = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

            if ($alertaExistente) {
                // Atualizar status para "Em dia"
                $sqlUpdate = "UPDATE avisos_manutencao SET
                              status = 'EmDia',
                              km_atual_veiculo = ?,
                              km_restantes = ?,
                              dias_restantes = ?,
                              atualizado_em = NOW()
                              WHERE id = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bindValue(1, $kmAtual, PDO::PARAM_INT);
                $stmtUpdate->bindValue(2, $kmRestantes, PDO::PARAM_INT);
                $stmtUpdate->bindValue(3, $diasRestantes, PDO::PARAM_INT);
                $stmtUpdate->bindValue(4, $alertaExistente['id'], PDO::PARAM_INT);
                $stmtUpdate->execute();
                $alertasAtualizados++;
            }
            continue;
        }

        // Calcular status
        $status = calcularStatus($kmRestantes, $diasRestantes !== null ? $diasRestantes : PHP_INT_MAX);

        // Criar mensagem do alerta
        $mensagem = "Manutenção: {$descricao}. ";
        if ($kmRestantes <= 0) {
            $mensagem .= "VENCIDO há " . abs($kmRestantes) . " km. ";
        } else {
            $mensagem .= "Faltam {$kmRestantes} km. ";
        }
        if ($diasRestantes !== null) {
            if ($diasRestantes <= 0) {
                $mensagem .= "VENCIDO há " . abs($diasRestantes) . " dias.";
            } else {
                $mensagem .= "Faltam {$diasRestantes} dias.";
            }
        }

        // Verificar se já existe alerta para este item
        $sqlExiste = "SELECT id FROM avisos_manutencao
                      WHERE placa_veiculo = ? AND plano_id = ? AND status NOT IN ('Concluido', 'Cancelado')";
        $stmtExiste = $conn->prepare($sqlExiste);
        $stmtExiste->bindValue(1, $placa);
        $stmtExiste->bindValue(2, $planoId, PDO::PARAM_INT);
        $stmtExiste->execute();
        $alertaExiste = $stmtExiste->fetch(PDO::FETCH_ASSOC);

        if ($alertaExiste) {
            // Atualizar alerta existente
            $sqlUpdate = "UPDATE avisos_manutencao SET
                          km_programado = ?,
                          data_proxima = ?,
                          km_atual_veiculo = ?,
                          km_restantes = ?,
                          dias_restantes = ?,
                          status = ?,
                          nivel_alerta = ?,
                          mensagem = ?,
                          atualizado_em = NOW()
                          WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bindValue(1, $kmProgramado, PDO::PARAM_INT);
            $stmtUpdate->bindValue(2, $dataProxima);
            $stmtUpdate->bindValue(3, $kmAtual, PDO::PARAM_INT);
            $stmtUpdate->bindValue(4, $kmRestantes, PDO::PARAM_INT);
            $stmtUpdate->bindValue(5, $diasRestantes, PDO::PARAM_INT);
            $stmtUpdate->bindValue(6, $status);
            $stmtUpdate->bindValue(7, $nivelAlerta);
            $stmtUpdate->bindValue(8, $mensagem);
            $stmtUpdate->bindValue(9, $alertaExiste['id'], PDO::PARAM_INT);
            $stmtUpdate->execute();
            $alertasAtualizados++;
        } else {
            // Criar novo alerta
            $sqlInsert = "INSERT INTO avisos_manutencao
                          (vehicle_id, placa_veiculo, plano_id, km_programado, data_proxima,
                           km_atual_veiculo, km_restantes, dias_restantes, status, nivel_alerta,
                           mensagem, criado_em, atualizado_em)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bindValue(1, $vehicleId, PDO::PARAM_INT);
            $stmtInsert->bindValue(2, $placa);
            $stmtInsert->bindValue(3, $planoId, PDO::PARAM_INT);
            $stmtInsert->bindValue(4, $kmProgramado, PDO::PARAM_INT);
            $stmtInsert->bindValue(5, $dataProxima);
            $stmtInsert->bindValue(6, $kmAtual, PDO::PARAM_INT);
            $stmtInsert->bindValue(7, $kmRestantes, PDO::PARAM_INT);
            $stmtInsert->bindValue(8, $diasRestantes, PDO::PARAM_INT);
            $stmtInsert->bindValue(9, $status);
            $stmtInsert->bindValue(10, $nivelAlerta);
            $stmtInsert->bindValue(11, $mensagem);
            $stmtInsert->execute();
            $alertasGerados++;
        }
    }

    return [
        'gerados' => $alertasGerados,
        'atualizados' => $alertasAtualizados,
        'erros' => $erros
    ];
}

// ==================== ROTEAMENTO ====================

try {
    // Conectar ao banco
    $conn = getDBConnection();

    if ($conn === null) {
        sendResponse(false, null, 'Erro ao conectar ao banco de dados', 500);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $placaFiltro = isset($_GET['placa']) ? trim($_GET['placa']) : null;

    // GET - Reset: Limpar alertas antigos e regenerar
    if ($method === 'GET' && $action === 'reset') {
        // Limpar todos os alertas não concluídos (vão ser recalculados)
        $sqlDelete = "DELETE FROM avisos_manutencao WHERE status NOT IN ('Concluido')";
        $conn->exec($sqlDelete);
        $deletados = $conn->query("SELECT ROW_COUNT()")->fetchColumn();

        sendResponse(true, [
            'message' => 'Alertas limpos com sucesso. Execute POST para regenerar.',
            'alertas_removidos' => intval($deletados)
        ]);
    }

    // GET - Estatísticas
    if ($method === 'GET' && $action === 'stats') {
        $sqlStats = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Vencido' THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN nivel_alerta = 'Critico' THEN 1 ELSE 0 END) as criticos,
                SUM(CASE WHEN nivel_alerta = 'Alto' THEN 1 ELSE 0 END) as altos,
                SUM(CASE WHEN nivel_alerta = 'Medio' THEN 1 ELSE 0 END) as medios,
                SUM(CASE WHEN nivel_alerta = 'Baixo' THEN 1 ELSE 0 END) as baixos,
                SUM(CASE WHEN status = 'EmDia' THEN 1 ELSE 0 END) as emDia,
                SUM(CASE WHEN status = 'Concluido' THEN 1 ELSE 0 END) as concluidos
            FROM avisos_manutencao
            WHERE status NOT IN ('Cancelado')
        ";

        $stmtStats = $conn->query($sqlStats);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        sendResponse(true, [
            'total' => intval($stats['total']),
            'vencidos' => intval($stats['vencidos']),
            'criticos' => intval($stats['criticos']),
            'altos' => intval($stats['altos']),
            'medios' => intval($stats['medios']),
            'baixos' => intval($stats['baixos']),
            'emDia' => intval($stats['emDia']),
            'concluidos' => intval($stats['concluidos'])
        ]);
    }

    // POST - Gerar alertas
    if ($method === 'POST') {
        $inicio = microtime(true);

        // Buscar veículos
        if ($placaFiltro) {
            $sqlVeiculos = "SELECT ID, LicensePlate, VehicleName
                            FROM Vehicles
                            WHERE LicensePlate = ?";
            $stmtVeiculos = $conn->prepare($sqlVeiculos);
            $stmtVeiculos->bindValue(1, $placaFiltro);
            $stmtVeiculos->execute();
        } else {
            $sqlVeiculos = "SELECT ID, LicensePlate, VehicleName
                            FROM Vehicles
                            ORDER BY LicensePlate";
            $stmtVeiculos = $conn->query($sqlVeiculos);
        }

        $veiculos = $stmtVeiculos->fetchAll(PDO::FETCH_ASSOC);

        if (empty($veiculos)) {
            sendResponse(true, [
                'message' => 'Nenhum veículo encontrado para processar',
                'total_veiculos' => 0,
                'alertas_gerados' => 0,
                'alertas_atualizados' => 0
            ]);
        }

        // Processar cada veículo
        $totalGerados = 0;
        $totalAtualizados = 0;
        $totalErros = [];
        $veiculosProcessados = 0;

        foreach ($veiculos as $veiculo) {
            $resultado = gerarAlertasVeiculo($conn, $veiculo);

            $totalGerados += $resultado['gerados'];
            $totalAtualizados += $resultado['atualizados'];
            $veiculosProcessados++;

            if (!empty($resultado['erros'])) {
                foreach ($resultado['erros'] as $erro) {
                    $totalErros[] = $veiculo['LicensePlate'] . ': ' . $erro;
                }
            }
        }

        $tempoExecucao = round(microtime(true) - $inicio, 2);

        sendResponse(true, [
            'message' => 'Geração de alertas concluída',
            'total_veiculos' => count($veiculos),
            'veiculos_processados' => $veiculosProcessados,
            'alertas_gerados' => $totalGerados,
            'alertas_atualizados' => $totalAtualizados,
            'erros' => $totalErros,
            'tempo_execucao' => $tempoExecucao . 's'
        ]);
    }

    // Método não suportado
    sendResponse(false, null, 'Método não suportado. Use GET com action=stats ou POST.', 405);

} catch (Exception $e) {
    error_log('Erro em gerar-alertas-manutencao.php: ' . $e->getMessage());
    sendResponse(false, null, 'Erro interno: ' . $e->getMessage(), 500);
}
