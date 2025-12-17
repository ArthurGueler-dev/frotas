<?php
/**
 * Testar conexão cURL com Evolution API
 */

header('Content-Type: application/json; charset=utf-8');

$EVOLUTION_API_URL = 'http://10.0.2.12:60010';
$EVOLUTION_API_KEY = 'b0faf368ea81f396469c0bd26fa07bf9d6076117cd3b6fab6e0ca6004b3d710e';
$EVOLUTION_INSTANCE = 'Thiago Costa';
$instance_encoded = urlencode($EVOLUTION_INSTANCE);

$url = "{$EVOLUTION_API_URL}/message/sendText/{$instance_encoded}";

$payload = [
    'number' => '5527999999999',
    'text' => 'Teste de conexão'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . $EVOLUTION_API_KEY
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

// Ignorar verificação SSL para IPs locais
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_errno = curl_errno($ch);
curl_close($ch);

echo json_encode([
    'url' => $url,
    'curl_error' => $curl_error,
    'curl_errno' => $curl_errno,
    'http_code' => $http_code,
    'response' => $response,
    'success' => empty($curl_error) && $http_code >= 200 && $http_code < 300
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
