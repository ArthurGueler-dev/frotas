<?php
/**
 * Proxy para enviar alertas de desvio via WhatsApp (Evolution API)
 *
 * Chamado pelo backend Python para enviar mensagens formatadas
 *
 * POST / - Enviar alerta
 * Body: {
 *   "phone": "5527999999999",
 *   "message": "texto do alerta"
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração Evolution API
$EVOLUTION_API_URL = 'http://10.0.2.12:60010';
$EVOLUTION_API_KEY = 'b0faf368ea81f396469c0bd26fa07bf9d6076117cd3b6fab6e0ca6004b3d710e';
$EVOLUTION_INSTANCE = 'Thiago Costa';

// ========== POST - Enviar Alerta ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validações
        if (!isset($input['phone']) || !isset($input['message'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Telefone e mensagem são obrigatórios'
            ]);
            exit;
        }

        $phone = $input['phone'];
        $message = $input['message'];

        // Validar formato do telefone (deve ser 5527999999999)
        if (!preg_match('/^55\d{10,11}$/', $phone)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Formato de telefone inválido. Use: 5527999999999'
            ]);
            exit;
        }

        // Preparar requisição para Evolution API
        $url = "$EVOLUTION_API_URL/message/sendText/$EVOLUTION_INSTANCE";

        $payload = [
            'number' => $phone,
            'text' => $message
        ];

        // Enviar via cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "apikey: $EVOLUTION_API_KEY"
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Verificar resposta
        if ($curlError) {
            throw new Exception("Erro cURL: $curlError");
        }

        $responseData = json_decode($response, true);

        if ($httpCode === 200 || $httpCode === 201) {
            echo json_encode([
                'success' => true,
                'message' => 'Alerta enviado com sucesso',
                'phone' => $phone,
                'evolution_response' => $responseData
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code($httpCode);
            echo json_encode([
                'success' => false,
                'error' => 'Falha ao enviar mensagem',
                'http_code' => $httpCode,
                'evolution_response' => $responseData
            ], JSON_PRETTY_PRINT);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Método não suportado
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Método não suportado. Use POST.'
]);
?>
