<?php
/**
 * Adicionar campo seq_number na tabela ordemservico
 * Execute este arquivo UMA VEZ
 */

header('Content-Type: text/html; charset=utf-8');

// Conexão
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

echo "<h1>🔧 Adicionando campo seq_number</h1>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Verificar se o campo já existe
echo "<h2>1️⃣ Verificando se campo já existe...</h2>";

$stmt = $pdo->query("SHOW COLUMNS FROM ordemservico LIKE 'seq_number'");
$fieldExists = $stmt->rowCount() > 0;

if ($fieldExists) {
    echo "<p style='color: orange;'>⚠️ Campo seq_number já existe!</p>";
} else {
    echo "<p style='color: blue;'>ℹ️ Campo não existe, criando...</p>";

    // Adicionar campo seq_number
    echo "<h2>2️⃣ Adicionando campo seq_number...</h2>";

    $sql = "ALTER TABLE ordemservico
            ADD COLUMN seq_number INT NOT NULL DEFAULT 0 AFTER id,
            ADD INDEX idx_seq_number (seq_number)";

    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Campo seq_number adicionado com sucesso!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erro ao adicionar campo: " . $e->getMessage() . "</p>";
        exit;
    }

    // Popular seq_number com base nos IDs existentes
    echo "<h2>3️⃣ Populando seq_number para registros existentes...</h2>";

    $sql = "UPDATE ordemservico SET seq_number = id WHERE seq_number = 0";

    try {
        $pdo->exec($sql);
        $affected = $pdo->exec("SELECT ROW_COUNT()");
        echo "<p style='color: green;'>✅ Registros atualizados!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ Aviso: " . $e->getMessage() . "</p>";
    }
}

// Verificar estrutura
echo "<h2>4️⃣ Verificando estrutura da tabela...</h2>";

$stmt = $pdo->query("DESCRIBE ordemservico");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th>";
echo "</tr>";

foreach ($columns as $col) {
    $highlight = ($col['Field'] === 'seq_number') ? "style='background: #90EE90;'" : "";
    echo "<tr $highlight>";
    echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
    $defaultValue = isset($col['Default']) ? $col['Default'] : 'NULL';
    echo "<td>" . htmlspecialchars($defaultValue) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>✅ Conclusão</h2>";
echo "<p><strong>Campo seq_number configurado com sucesso!</strong></p>";

?>
