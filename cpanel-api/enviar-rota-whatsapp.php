<?php
/**
 * API para enviar rota otimizada via WhatsApp
 * Usa Evolution API para envio
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
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

// Configuração da Evolution API
$EVOLUTION_API_URL = 'http://10.0.2.12:60010';
$EVOLUTION_API_KEY = 'b0faf368ea81f396469c0bd26fa07bf9d6076117cd3b6fab6e0ca6004b3d710e';
$EVOLUTION_INSTANCE = 'Thiago Costa';  // Nome da instância (será encoded automaticamente)

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,  // Aumentado para 10 segundos
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password, $options);
    $pdo->exec("SET NAMES utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        enviarRotaPorWhatsApp($pdo);
    } elseif ($method === 'GET' && isset($_GET['rota_id'])) {
        obterDadosRota($pdo, $_GET['rota_id']);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
    }

} catch (PDOException $e) {
    error_log("ENVIAR WHATSAPP - Erro PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    error_log("ENVIAR WHATSAPP - Erro geral: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no servidor',
        'message' => $e->getMessage()
    ]);
}

/**
 * Enviar rota por WhatsApp via Evolution API
 *
 * Body esperado:
 * {
 *   "rota_id": 123,
 *   "telefone": "5527999999999"  // Formato: código país + DDD + número
 * }
 */
function enviarRotaPorWhatsApp($pdo) {
    global $EVOLUTION_API_URL, $EVOLUTION_API_KEY, $EVOLUTION_INSTANCE;

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log("ENVIAR WHATSAPP - Dados recebidos: " . json_encode($data));

    if (!$data || !isset($data['rota_id']) || !isset($data['telefone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'rota_id e telefone são obrigatórios']);
        return;
    }

    $rota_id = intval($data['rota_id']);
    $telefone = preg_replace('/[^0-9]/', '', $data['telefone']);  // Apenas números

    error_log("ENVIAR WHATSAPP - Buscando rota #$rota_id para telefone $telefone");

    // Buscar dados da rota no banco (sem JOINs - tabelas ainda não criadas)
    $stmt = $pdo->prepare("
        SELECT
            id,
            bloco_id,
            distancia_total_km,
            tempo_estimado_min,
            sequencia_locais_json,
            link_google_maps
        FROM FF_Rotas
        WHERE id = ?
    ");

    $stmt->execute([$rota_id]);
    $rota = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("ENVIAR WHATSAPP - Rota encontrada: " . ($rota ? "SIM" : "NÃO"));

    if (!$rota) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rota não encontrada', 'rota_id' => $rota_id]);
        return;
    }

    error_log("ENVIAR WHATSAPP - Rota: " . json_encode($rota));

    // Decodificar sequência de locais
    $locais = json_decode($rota['sequencia_locais_json'], true);

    // Construir mensagem
    $mensagem = construirMensagemRota($rota, $locais);

    // Enviar via Evolution API
    $resultado = enviarMensagemEvolution($telefone, $mensagem);

    if ($resultado['success']) {
        // Atualizar status da rota
        $stmt = $pdo->prepare("
            UPDATE FF_Rotas
            SET status = 'enviada',
                data_envio = NOW(),
                telefone_destino = ?
            WHERE id = ?
        ");
        $stmt->execute([$telefone, $rota_id]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Rota enviada com sucesso via WhatsApp',
            'rota_id' => $rota_id,
            'telefone' => $telefone,
            'total_locais' => count($locais)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao enviar mensagem',
            'details' => $resultado['error']
        ]);
    }
}

/**
 * Construir mensagem formatada para WhatsApp
 */
function construirMensagemRota($rota, $locais) {
    $bloco_id = isset($rota['bloco_id']) ? $rota['bloco_id'] : 'N/A';
    $distancia = number_format($rota['distancia_total_km'], 2, ',', '.');
    $tempo = $rota['tempo_estimado_min'];

    $mensagem = "🚗 *Sua Rota de Hoje* 🚗\n\n";
    $mensagem .= "📍 *Bloco #:* {$bloco_id}\n";
    $mensagem .= "📏 *Distância Total:* {$distancia} km\n";
    $mensagem .= "⏱️ *Tempo Estimado:* {$tempo} minutos\n\n";

    $mensagem .= "📋 *Sequência de Visitas* (siga exatamente essa ordem):\n\n";

    foreach ($locais as $index => $local) {
        $numero = $index + 1;
        $nome = isset($local['nome']) ? $local['nome'] : "Local {$numero}";
        $endereco = isset($local['endereco']) ? $local['endereco'] : '';

        $mensagem .= "*{$numero}.* {$nome}\n";
        if ($endereco) {
            $mensagem .= "   📍 _{$endereco}_\n";
        }
        $mensagem .= "\n";
    }

    $mensagem .= "🗺️ *Navegue com Google Maps:*\n";
    $mensagem .= "{$rota['link_google_maps']}\n\n";

    $mensagem .= "✅ *Instruções:*\n";
    $mensagem .= "1️⃣ Clique no link acima\n";
    $mensagem .= "2️⃣ O Google Maps abrirá com todos os pontos\n";
    $mensagem .= "3️⃣ Siga a navegação ponto a ponto\n";
    $mensagem .= "4️⃣ Não altere a ordem dos pontos\n\n";

    $mensagem .= "_Boa viagem e bom trabalho!_ 🎯";

    return $mensagem;
}

/**
 * Enviar mensagem via Evolution API
 */
function enviarMensagemEvolution($telefone, $mensagem) {
    global $EVOLUTION_API_URL, $EVOLUTION_API_KEY, $EVOLUTION_INSTANCE;

    // Encode da instância para URL (espaços se tornam %20)
    $instance_encoded = urlencode($EVOLUTION_INSTANCE);
    $url = "{$EVOLUTION_API_URL}/message/sendText/{$instance_encoded}";

    error_log("ENVIAR WHATSAPP - URL Evolution: $url");

    $payload = [
        'number' => $telefone,
        'text' => $mensagem
    ];

    error_log("ENVIAR WHATSAPP - Payload: " . json_encode($payload));

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
        return [
            'success' => false,
            'error' => "Erro cURL: $curl_error"
        ];
    }

    if ($http_code >= 200 && $http_code < 300) {
        return [
            'success' => true,
            'response' => json_decode($response, true)
        ];
    } else {
        return [
            'success' => false,
            'error' => "HTTP $http_code: $response"
        ];
    }
}

/**
 * Obter dados da rota (para preview)
 */
function obterDadosRota($pdo, $rota_id) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM FF_Rotas
        WHERE id = ?
    ");

    $stmt->execute([$rota_id]);
    $rota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rota) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rota não encontrada']);
        return;
    }

    $rota['sequencia_locais'] = json_decode($rota['sequencia_locais_json'], true);
    unset($rota['sequencia_locais_json']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'rota' => $rota
    ]);
}
