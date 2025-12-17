<?php
/**
 * Testar conexão com Evolution API
 */

header('Content-Type: application/json; charset=utf-8');

$EVOLUTION_API_URL = 'http://10.0.2.12:60010';
$EVOLUTION_INSTANCE = 'Thiago Costa';
$instance_encoded = urlencode($EVOLUTION_INSTANCE);

$url = "{$EVOLUTION_API_URL}/message/sendText/{$instance_encoded}";

echo json_encode([
    'original_instance' => $EVOLUTION_INSTANCE,
    'encoded_instance' => $instance_encoded,
    'full_url' => $url,
    'url_is_valid' => filter_var($url, FILTER_VALIDATE_URL) !== false
], JSON_PRETTY_PRINT);
