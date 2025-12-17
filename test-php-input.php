<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$input = file_get_contents('php://input');
$method = $_SERVER['REQUEST_METHOD'];
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'not set';

echo json_encode([
    'method' => $method,
    'content_type' => $contentType,
    'input_length' => strlen($input),
    'input_preview' => substr($input, 0, 100),
    'post_data' => $_POST,
    'get_data' => $_GET,
    'decoded' => json_decode($input, true),
    'json_error' => json_last_error_msg()
], JSON_PRETTY_PRINT);
?>
