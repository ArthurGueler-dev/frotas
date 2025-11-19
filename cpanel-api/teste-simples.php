<?php
/**
 * Script de Teste Simplificado
 * Upload para: /home/f137049/public_html/api/teste-simples.php
 * Acesse: https://floripa.in9automacao.com.br/api/teste-simples.php
 */

// Desabilita exibição de erros
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Teste 1: Conexão com banco
    $mysqli = new mysqli('187.49.226.10', 'f137049_tool', 'In9@1234qwer', 'f137049_in9aut', 3306);

    if ($mysqli->connect_error) {
        throw new Exception("Erro MySQL: " . $mysqli->connect_error);
    }

    // Teste 2: Buscar um veículo
    $result = $mysqli->query("SELECT LicensePlate FROM Vehicles LIMIT 1");
    $veiculo = $result->fetch_assoc();

    if (!$veiculo) {
        throw new Exception("Nenhum veículo encontrado");
    }

    $placa = $veiculo['LicensePlate'];

    // Teste 3: Fazer requisição à API Ituran
    $hoje = date('Y-m-d');
    $agora = date('Y-m-d H:i:s');

    $params = http_build_query([
        'UserName' => 'api@i9tecnologia',
        'Password' => 'Api@In9Eng',
        'Plate' => $placa,
        'Start' => "$hoje 00:00:00",
        'End' => $agora,
        'UAID' => '0',
        'MaxNumberOfRecords' => '100'
    ]);

    $url = "https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx/GetFullReport?{$params}";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => "Accept: application/xml\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $xmlData = @file_get_contents($url, false, $context);

    if ($xmlData === false) {
        throw new Exception("Erro ao buscar API Ituran");
    }

    $xml = @simplexml_load_string($xmlData);
    if (!$xml) {
        throw new Exception("Erro ao parsear XML");
    }

    $startOdo = isset($xml->StartOdometer) ? (float)$xml->StartOdometer : 0;
    $endOdo = isset($xml->EndOdometer) ? (float)$xml->EndOdometer : 0;

    // Teste 4: Verificar se pode inserir no banco
    $stmt = $mysqli->prepare("SELECT id FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ? LIMIT 1");
    $stmt->bind_param("ss", $placa, $hoje);
    $stmt->execute();
    $result2 = $stmt->get_result();
    $existe = $result2->num_rows > 0;
    $stmt->close();

    $mysqli->close();

    ob_end_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Todos os testes passaram!',
        'testes' => [
            'mysql' => 'OK',
            'veiculo_encontrado' => $placa,
            'api_ituran' => 'OK',
            'km_inicial' => $startOdo,
            'km_final' => $endOdo,
            'registro_existe' => $existe ? 'sim' : 'não'
        ]
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
