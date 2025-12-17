<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

$tz = new DateTimeZone('America/Sao_Paulo');
$now = new DateTime('now', $tz);

echo json_encode([
    'php_timezone_config' => date_default_timezone_get(),
    'php_date_simples' => date('Y-m-d H:i:s'),
    'datetime_object' => $now->format('Y-m-d H:i:s'),
    'timestamp' => time(),
    'timezone_offset' => $now->format('P'),
    'server_timezone' => ini_get('date.timezone')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
