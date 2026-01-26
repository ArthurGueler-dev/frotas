<?php
/**
 * Script para criar tabela FF_Fornecedores e popular com dados do CSV
 * Data: 2026-01-12
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception('Erro de conexão: ' . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    $results = [];

    // 1. Criar tabela se não existir
    $createTableSQL = "CREATE TABLE IF NOT EXISTS FF_Fornecedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        razao_social VARCHAR(255) NULL,
        cnpj VARCHAR(20) NULL,
        telefone VARCHAR(20) NULL,
        celular VARCHAR(20) NULL,
        email VARCHAR(255) NULL,
        endereco VARCHAR(500) NULL,
        complemento VARCHAR(255) NULL,
        bairro VARCHAR(255) NULL,
        cep VARCHAR(15) NULL,
        cidade VARCHAR(255) NULL,
        estado VARCHAR(50) NULL,
        ativo TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_nome (nome),
        INDEX idx_cidade (cidade),
        INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($createTableSQL)) {
        $results['table_created'] = true;
    } else {
        throw new Exception('Erro ao criar tabela: ' . $conn->error);
    }

    // 2. Verificar se tabela está vazia
    $countResult = $conn->query("SELECT COUNT(*) as total FROM FF_Fornecedores");
    $count = $countResult->fetch_assoc()['total'];

    $results['current_count'] = $count;

    // 3. Ler dados do JSON e inserir
    $jsonFile = 'fornecedores-data.json';

    if (!file_exists($jsonFile)) {
        throw new Exception('Arquivo fornecedores-data.json não encontrado no diretório atual: ' . __DIR__);
    }

    $fornecedoresData = json_decode(file_get_contents($jsonFile), true);

    if (!$fornecedoresData) {
        throw new Exception('Erro ao ler arquivo JSON');
    }

    $results['json_count'] = count($fornecedoresData);

    // 4. Inserir fornecedores
    $stmt = $conn->prepare("INSERT INTO FF_Fornecedores (nome, razao_social, cnpj, telefone, celular, email, endereco, complemento, bairro, cep, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nome = VALUES(nome)");

    $inserted = 0;

    foreach ($fornecedoresData as $f) {
        $stmt->bind_param(
            "ssssssssssss",
            $f['nome'],
            $f['razao_social'],
            $f['cnpj'],
            $f['telefone'],
            $f['celular'],
            $f['email'],
            $f['endereco'],
            $f['complemento'],
            $f['bairro'],
            $f['cep'],
            $f['cidade'],
            $f['estado']
        );

        if ($stmt->execute()) {
            $inserted++;
        }
    }

    $results['inserted'] = $inserted;
    $results['message'] = "Tabela criada e {$inserted} fornecedores inseridos com sucesso!";

    $conn->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'results' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
