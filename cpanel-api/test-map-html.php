<?php
// Teste para verificar se map_html está no banco
header('Content-Type: text/html; charset=utf-8');

// Configuração do banco (use as mesmas credenciais do blocks-api.php)
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$user = 'f137049_tool';
$pass = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar blocos recentes com map_html
    $stmt = $pdo->query("
        SELECT
            id,
            name,
            LENGTH(map_html) as map_html_size,
            SUBSTRING(map_html, 1, 200) as map_html_preview
        FROM FF_Blocks
        ORDER BY id DESC
        LIMIT 5
    ");

    echo "<h1>Teste de map_html nos Blocos</h1>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Tamanho map_html</th><th>Preview (200 chars)</th></tr>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . ($row['map_html_size'] ?: 'NULL/Vazio') . " bytes</td>";
        echo "<td>" . htmlspecialchars($row['map_html_preview'] ?: 'Vazio') . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Verificar se a coluna existe
    $stmt = $pdo->query("SHOW COLUMNS FROM FF_Blocks LIKE 'map_html'");
    $column = $stmt->fetch();

    echo "<br><br><strong>Coluna map_html existe:</strong> " . ($column ? 'SIM' : 'NÃO');
    if ($column) {
        echo "<br><strong>Tipo:</strong> " . $column['Type'];
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
