<?php
/**
 * Criar Tabela de Histórico de OS
 *
 * Execute este arquivo UMA VEZ para criar a tabela ordemservico_historico
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

echo "<h1>🔧 Criação da Tabela de Histórico de OS</h1>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Criar tabela ordemservico_historico
echo "<h2>1️⃣ Criando tabela ordemservico_historico...</h2>";

$sql = "
CREATE TABLE IF NOT EXISTS ordemservico_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    os_id INT NOT NULL,
    os_numero VARCHAR(50) NOT NULL,

    tipo_mudanca VARCHAR(50) NOT NULL COMMENT 'status_change, data_change, item_added, item_removed, item_updated, created',

    campo_alterado VARCHAR(100) COMMENT 'Nome do campo alterado',
    valor_anterior TEXT COMMENT 'Valor antes da mudança',
    valor_novo TEXT COMMENT 'Valor após a mudança',

    data_mudanca DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    usuario_nome VARCHAR(100) COMMENT 'Nome do usuário',
    usuario_email VARCHAR(150) COMMENT 'Email do usuário',

    observacao TEXT COMMENT 'Observação adicional',

    INDEX idx_os_id (os_id),
    INDEX idx_os_numero (os_numero),
    INDEX idx_data_mudanca (data_mudanca),
    INDEX idx_tipo_mudanca (tipo_mudanca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

try {
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Tabela ordemservico_historico criada com sucesso!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao criar tabela: " . $e->getMessage() . "</p>";
}

// Criar índice composto
echo "<h2>2️⃣ Criando índice composto...</h2>";

$sql = "CREATE INDEX IF NOT EXISTS idx_os_timeline ON ordemservico_historico(os_id, data_mudanca DESC)";

try {
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Índice idx_os_timeline criado com sucesso!</p>";
} catch (PDOException $e) {
    echo "<p style='color: orange;'>⚠️ Índice pode já existir: " . $e->getMessage() . "</p>";
}

// Verificar estrutura
echo "<h2>3️⃣ Verificando estrutura da tabela...</h2>";

$stmt = $pdo->query("DESCRIBE ordemservico_historico");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th>";
echo "</tr>";

foreach ($columns as $col) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Contar registros
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ordemservico_historico");
$count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<hr>";
echo "<h2>✅ Conclusão</h2>";
echo "<p><strong>Tabela criada com sucesso!</strong></p>";
echo "<p>Total de registros: <strong>$count</strong></p>";
echo "<p>Próximo passo: <a href='os-historico-api.php'>Acessar API de Histórico</a></p>";

?>
