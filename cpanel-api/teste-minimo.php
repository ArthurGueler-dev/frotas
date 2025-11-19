<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

echo json_encode([
    "status" => "OK",
    "mensagem" => "PHP funciona!",
    "timestamp" => date("Y-m-d H:i:s"),
    "php_version" => phpversion()
]);
?>
