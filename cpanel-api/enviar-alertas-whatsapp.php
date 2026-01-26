<?php
/**
 * API para enviar alertas de manutenção via WhatsApp
 * Usa Evolution API para envio
 *
 * Endpoints:
 * POST /enviar-alertas-whatsapp.php - Enviar mensagem customizada
 * POST /enviar-alertas-whatsapp.php?action=critical - Enviar resumo de alertas críticos
 *
 * @author Claude
 * @version 1.0
 * @date 2026-01-21
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração do banco de dados
require_once __DIR__ . '/db-config.php';

// Configuração da Evolution API
$EVOLUTION_API_URL = 'http://10.0.2.12:60010';
$EVOLUTION_API_KEY = 'b0faf368ea81f396469c0bd26fa07bf9d6076117cd3b6fab6e0ca6004b3d710e';
$EVOLUTION_INSTANCE = 'Thiago Costa';

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

/**
 * Enviar mensagem via Evolution API
 */
function enviarMensagemEvolution($telefone, $mensagem) {
    global $EVOLUTION_API_URL, $EVOLUTION_API_KEY, $EVOLUTION_INSTANCE;

    // Limpar telefone (apenas números)
    $telefone = preg_replace('/[^0-9]/', '', $telefone);

    // Adicionar código do país se não tiver
    if (strlen($telefone) == 11) {
        $telefone = '55' . $telefone;
    } elseif (strlen($telefone) == 10) {
        $telefone = '55' . $telefone;
    }

    // Encode da instância para URL
    $instance_encoded = urlencode($EVOLUTION_INSTANCE);
    $url = "{$EVOLUTION_API_URL}/message/sendText/{$instance_encoded}";

    error_log("ENVIAR ALERTA WHATSAPP - URL: $url, Telefone: $telefone");

    $payload = [
        'number' => $telefone,
        'text' => $mensagem
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $EVOLUTION_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("ENVIAR ALERTA WHATSAPP - Erro cURL: $curl_error");
        return [
            'success' => false,
            'error' => "Erro cURL: $curl_error"
        ];
    }

    if ($http_code >= 200 && $http_code < 300) {
        error_log("ENVIAR ALERTA WHATSAPP - Sucesso! HTTP $http_code");
        return [
            'success' => true,
            'response' => json_decode($response, true)
        ];
    } else {
        error_log("ENVIAR ALERTA WHATSAPP - Erro HTTP $http_code: $response");
        return [
            'success' => false,
            'error' => "HTTP $http_code: $response"
        ];
    }
}

/**
 * Formatar mensagem de alerta individual
 */
function formatarAlertaIndividual($alerta) {
    $placa = isset($alerta['placa_veiculo']) ? $alerta['placa_veiculo'] : 'N/A';
    $modelo = isset($alerta['modelo']) ? $alerta['modelo'] : '';
    $descricao = isset($alerta['plano_nome']) ? $alerta['plano_nome'] : (isset($alerta['mensagem']) ? $alerta['mensagem'] : 'Manutenção');
    $nivel = isset($alerta['nivel_alerta']) ? $alerta['nivel_alerta'] : 'N/A';
    $kmRestantes = isset($alerta['km_restantes']) ? intval($alerta['km_restantes']) : 0;
    $kmAtual = isset($alerta['km_atual_veiculo']) ? intval($alerta['km_atual_veiculo']) : 0;

    // Emoji baseado no nível
    $emojis = [
        'Critico' => '🔴',
        'Alto' => '🟠',
        'Medio' => '🟡',
        'Baixo' => '🔵'
    ];
    $emoji = isset($emojis[$nivel]) ? $emojis[$nivel] : '⚪';

    // Status KM
    if ($kmRestantes <= 0) {
        $statusKm = "*VENCIDO* há " . number_format(abs($kmRestantes), 0, ',', '.') . " km";
    } else {
        $statusKm = "Faltam " . number_format($kmRestantes, 0, ',', '.') . " km";
    }

    $mensagem = "$emoji *ALERTA DE MANUTENÇÃO*\n\n";
    $mensagem .= "*Veículo:* $placa";
    if ($modelo) {
        $mensagem .= " - $modelo";
    }
    $mensagem .= "\n";
    $mensagem .= "*Serviço:* $descricao\n";
    $mensagem .= "*Status:* $statusKm\n";
    $mensagem .= "*KM Atual:* " . number_format($kmAtual, 0, ',', '.') . " km\n";
    $mensagem .= "*Nível:* $nivel\n\n";
    $mensagem .= "_FleetFlow - Gestão de Frotas_";

    return $mensagem;
}

/**
 * Formatar mensagem resumida de alertas
 */
function formatarResumoAlertas($alertas) {
    if (empty($alertas)) {
        return "✅ *Nenhum alerta crítico no momento.*\n\n_FleetFlow - Gestão de Frotas_";
    }

    // Agrupar por veículo
    $grupos = [];
    foreach ($alertas as $alerta) {
        $placa = isset($alerta['placa_veiculo']) ? $alerta['placa_veiculo'] : 'UNKNOWN';
        if (!isset($grupos[$placa])) {
            $grupos[$placa] = [];
        }
        $grupos[$placa][] = $alerta;
    }

    // Contar totais
    $totalCriticos = 0;
    $totalVencidos = 0;
    foreach ($alertas as $alerta) {
        if (isset($alerta['nivel_alerta']) && $alerta['nivel_alerta'] === 'Critico') {
            $totalCriticos++;
        }
        if (isset($alerta['km_restantes']) && intval($alerta['km_restantes']) <= 0) {
            $totalVencidos++;
        }
    }

    $mensagem = "🚨 *RESUMO DE ALERTAS DE MANUTENÇÃO*\n";
    $mensagem .= "📅 Data: " . date('d/m/Y H:i') . "\n\n";
    $mensagem .= "⚠️ Total de alertas: " . count($alertas) . "\n";
    $mensagem .= "🔴 Críticos: $totalCriticos\n";
    $mensagem .= "❌ Vencidos: $totalVencidos\n\n";
    $mensagem .= "*Por Veículo:*\n";

    foreach ($grupos as $placa => $alertasVeiculo) {
        $modelo = isset($alertasVeiculo[0]['modelo']) ? $alertasVeiculo[0]['modelo'] : '';
        $mensagem .= "\n📋 *$placa*";
        if ($modelo) {
            $mensagem .= " - $modelo";
        }
        $mensagem .= "\n";

        $count = 0;
        foreach ($alertasVeiculo as $alerta) {
            if ($count >= 3) {
                $restante = count($alertasVeiculo) - 3;
                $mensagem .= "   ... e mais $restante alerta(s)\n";
                break;
            }

            $descricao = isset($alerta['plano_nome']) ? substr($alerta['plano_nome'], 0, 30) : 'Manutenção';
            $kmRest = isset($alerta['km_restantes']) ? intval($alerta['km_restantes']) : 0;

            if ($kmRest <= 0) {
                $mensagem .= "   🔴 $descricao: VENCIDO (" . number_format(abs($kmRest), 0, ',', '.') . "km)\n";
            } else {
                $mensagem .= "   🟠 $descricao: " . number_format($kmRest, 0, ',', '.') . "km restantes\n";
            }
            $count++;
        }
    }

    $mensagem .= "\n_Acesse o sistema para mais detalhes._\n";
    $mensagem .= "_FleetFlow - Gestão de Frotas_";

    return $mensagem;
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

    if ($method !== 'POST') {
        sendResponse(false, null, 'Método não permitido. Use POST.', 405);
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Ação: enviar resumo de alertas críticos
    if ($action === 'critical') {
        // Buscar alertas críticos e vencidos
        $sql = "SELECT
                    av.id,
                    av.placa_veiculo,
                    av.km_restantes,
                    av.km_atual_veiculo,
                    av.nivel_alerta,
                    av.mensagem,
                    COALESCE(v.VehicleName, 'Veículo') as modelo,
                    COALESCE(pm.descricao_titulo, 'Manutenção') as plano_nome
                FROM avisos_manutencao av
                LEFT JOIN Vehicles v ON av.placa_veiculo = v.LicensePlate
                LEFT JOIN `Planos_Manutenção` pm ON av.plano_id = pm.id
                WHERE av.status IN ('Vencido', 'Pendente')
                  AND (av.nivel_alerta IN ('Critico', 'Alto') OR av.km_restantes <= 0)
                ORDER BY av.km_restantes ASC
                LIMIT 50";

        $stmt = $conn->query($sql);
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($alertas)) {
            sendResponse(true, ['message' => 'Nenhum alerta crítico para enviar']);
        }

        // Buscar destinatários
        $sqlDest = "SELECT * FROM FF_AlertRecipients WHERE is_active = 1 AND alert_type IN ('maintenance', 'all')";
        $stmtDest = $conn->query($sqlDest);
        $destinatarios = $stmtDest->fetchAll(PDO::FETCH_ASSOC);

        if (empty($destinatarios)) {
            // Fallback: verificar se foi passado telefone no body
            if (isset($data['telefone']) && !empty($data['telefone'])) {
                $destinatarios = [['phone_number' => $data['telefone'], 'name' => 'Manual']];
            } else {
                sendResponse(false, null, 'Nenhum destinatário configurado', 400);
            }
        }

        // Formatar mensagem resumida
        $mensagem = formatarResumoAlertas($alertas);

        // Enviar para cada destinatário
        $enviados = 0;
        $erros = [];

        foreach ($destinatarios as $dest) {
            $telefone = isset($dest['phone_number']) ? $dest['phone_number'] : $dest['telefone'];
            $nome = isset($dest['name']) ? $dest['name'] : 'Destinatário';

            $resultado = enviarMensagemEvolution($telefone, $mensagem);

            if ($resultado['success']) {
                $enviados++;
                error_log("ENVIAR ALERTA WHATSAPP - Enviado para $nome ($telefone)");
            } else {
                $erros[] = "$nome: " . $resultado['error'];
            }
        }

        // Marcar alertas como notificados
        $ids = array_column($alertas, 'id');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sqlUpdate = "UPDATE avisos_manutencao SET notificado = 1, data_notificacao = NOW() WHERE id IN ($placeholders)";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->execute($ids);
        }

        sendResponse(true, [
            'total_alertas' => count($alertas),
            'destinatarios' => count($destinatarios),
            'enviados' => $enviados,
            'erros' => $erros
        ]);
    }

    // Ação padrão: enviar mensagem customizada
    if (!isset($data['telefone']) || !isset($data['mensagem'])) {
        sendResponse(false, null, 'telefone e mensagem são obrigatórios', 400);
    }

    $telefone = $data['telefone'];
    $mensagem = $data['mensagem'];
    $tipo = isset($data['tipo']) ? $data['tipo'] : 'custom';

    // Se for um alerta específico, buscar dados e formatar
    if ($tipo === 'maintenance_alert' && isset($data['alerta_id'])) {
        $sqlAlerta = "SELECT
                        av.*,
                        COALESCE(v.VehicleName, 'Veículo') as modelo,
                        COALESCE(pm.descricao_titulo, 'Manutenção') as plano_nome
                      FROM avisos_manutencao av
                      LEFT JOIN Vehicles v ON av.placa_veiculo = v.LicensePlate
                      LEFT JOIN `Planos_Manutenção` pm ON av.plano_id = pm.id
                      WHERE av.id = ?";
        $stmtAlerta = $conn->prepare($sqlAlerta);
        $stmtAlerta->execute([$data['alerta_id']]);
        $alerta = $stmtAlerta->fetch(PDO::FETCH_ASSOC);

        if ($alerta) {
            $mensagem = formatarAlertaIndividual($alerta);
        }
    }

    // Enviar mensagem
    $resultado = enviarMensagemEvolution($telefone, $mensagem);

    if ($resultado['success']) {
        sendResponse(true, [
            'message' => 'Mensagem enviada com sucesso',
            'telefone' => $telefone,
            'tipo' => $tipo
        ]);
    } else {
        sendResponse(false, null, 'Erro ao enviar mensagem: ' . $resultado['error'], 500);
    }

} catch (Exception $e) {
    error_log('Erro em enviar-alertas-whatsapp.php: ' . $e->getMessage());
    sendResponse(false, null, 'Erro interno: ' . $e->getMessage(), 500);
}
