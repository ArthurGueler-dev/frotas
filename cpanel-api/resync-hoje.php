<?php
/**
 * Re-sincroniza quilometragem de HOJE
 *
 * Este script:
 * 1. Deleta todos os registros de hoje (que estão com odometer_start errado)
 * 2. Recalcula corretamente usando odômetro de meia-noite de HOJE
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

$executar = isset($_GET['executar']) && $_GET['executar'] === 'sim';
$hoje = date('Y-m-d');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resultado = array(
        'modo' => $executar ? 'EXECUÇÃO' : 'DIAGNÓSTICO (adicione ?executar=sim)',
        'data_hoje' => $hoje,
        'hora' => date('H:i:s')
    );

    // Verificar registros de hoje
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            SUM(km_driven) as total_km,
            MIN(created_at) as primeiro_registro,
            MAX(synced_at) as ultima_sync
        FROM daily_mileage
        WHERE date = ?
    ");
    $stmt->execute(array($hoje));
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    $resultado['registros_antes'] = $info;

    // Verificar registros com created_at de ontem (bug)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as qtd_bugados
        FROM daily_mileage
        WHERE date = ? AND DATE(created_at) < ?
    ");
    $stmt->execute(array($hoje, $hoje));
    $bugados = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado['registros_bugados'] = $bugados['qtd_bugados'];

    if ($executar) {
        // Deletar registros de hoje (serão recriados corretamente pelo próximo sync)
        $stmt = $pdo->prepare("DELETE FROM daily_mileage WHERE date = ?");
        $stmt->execute(array($hoje));
        $deletados = $stmt->rowCount();

        $resultado['acao'] = 'Registros de hoje deletados';
        $resultado['registros_deletados'] = $deletados;
        $resultado['proximo_passo'] = 'Aguarde o próximo sync (06:00, 12:00, 18:00) ou force via Celery';
    }

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
