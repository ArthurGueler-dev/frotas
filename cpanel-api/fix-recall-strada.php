<?php
/**
 * Fix: Inserir item de RECALL que falhou por causa do emoji
 * Fiat Strada 1.4 Working 2014-2015
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Conexao falhou: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset('utf8mb4');

// Item do RECALL sem emoji
$item = [
    'modelo_carro' => 'Fiat Strada 1.4 Working 2014-2015',
    'descricao_titulo' => 'RECALL AIRBAG TAKATA - VERIFICAR URGENTE',
    'km_recomendado' => null,
    'intervalo_tempo' => 'Imediato',
    'custo_estimado' => 0.00,
    'criticidade' => 'Critica',
    'descricao_observacao' => '[CATEGORIA: Recall/Seguranca] [TEMPO: Verificacao] URGENTISSIMO: Fiat convocou modelos 2014-2016 para recall dos airbags Takata. RISCO DE MORTE: Em colisao, airbag pode romper dispersando fragmentos metalicos causando danos fisicos graves ou fatais. Verificar IMEDIATAMENTE no site servicos.fiat.com.br/recall.html ou 0800-707-1000. SERVICO GRATUITO.'
];

// Verificar se já existe
$check = $conn->prepare("SELECT id FROM Planos_Manutenção WHERE modelo_carro = ? AND descricao_titulo LIKE '%RECALL AIRBAG TAKATA%'");
$check->bind_param("s", $item['modelo_carro']);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Item RECALL ja existe no banco']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Inserir o item
$stmt = $conn->prepare(
    "INSERT INTO Planos_Manutenção
    (modelo_carro, descricao_titulo, km_recomendado, intervalo_tempo, custo_estimado, criticidade, descricao_observacao)
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "ssissss",
    $item['modelo_carro'],
    $item['descricao_titulo'],
    $item['km_recomendado'],
    $item['intervalo_tempo'],
    $item['custo_estimado'],
    $item['criticidade'],
    $item['descricao_observacao']
);

if ($stmt->execute()) {
    $newId = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Item RECALL inserido com sucesso',
        'item_id' => $newId,
        'modelo' => $item['modelo_carro']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
