<?php
/**
 * Script para remover veiculos especificos da frota
 * Veiculos: MSX7995, RBE1J63, RBE1J59, PPW0562, PPX2803
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

// Veiculos a remover
$veiculosParaRemover = array('MSX7995', 'RBE1J63', 'RBE1J59', 'PPW0562', 'PPX2803');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resultado = array(
        'success' => true,
        'veiculos_solicitados' => $veiculosParaRemover,
        'detalhes' => array()
    );

    // 1. Verificar quais veiculos existem antes de remover
    $placeholders = implode(',', array_fill(0, count($veiculosParaRemover), '?'));
    $stmt = $pdo->prepare("SELECT Id, LicensePlate, VehicleName, Brand FROM Vehicles WHERE LicensePlate IN ($placeholders)");
    $stmt->execute($veiculosParaRemover);
    $veiculosEncontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado['veiculos_encontrados'] = $veiculosEncontrados;
    $resultado['total_encontrados'] = count($veiculosEncontrados);

    if (count($veiculosEncontrados) == 0) {
        $resultado['mensagem'] = 'Nenhum dos veiculos informados foi encontrado na frota';
        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 2. Deletar os veiculos
    $stmt = $pdo->prepare("DELETE FROM Vehicles WHERE LicensePlate IN ($placeholders)");
    $stmt->execute($veiculosParaRemover);
    $linhasAfetadas = $stmt->rowCount();

    $resultado['veiculos_removidos'] = $linhasAfetadas;

    // 3. Listar quais foram removidos
    $placasEncontradas = array();
    foreach ($veiculosEncontrados as $v) {
        $placasEncontradas[] = $v['LicensePlate'];
        $resultado['detalhes'][] = array(
            'placa' => $v['LicensePlate'],
            'modelo' => $v['VehicleName'],
            'marca' => $v['Brand'],
            'status' => 'removido'
        );
    }

    // 4. Verificar quais nao foram encontrados
    $naoEncontrados = array_diff($veiculosParaRemover, $placasEncontradas);
    foreach ($naoEncontrados as $placa) {
        $resultado['detalhes'][] = array(
            'placa' => $placa,
            'status' => 'nao_encontrado'
        );
    }

    // 5. Contar total de veiculos restantes na frota
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Vehicles");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado['total_veiculos_restantes'] = (int)$total['total'];

    $resultado['mensagem'] = "$linhasAfetadas veiculo(s) removido(s) com sucesso";

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Erro: ' . $e->getMessage()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
