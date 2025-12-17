<?php
header('Content-Type: text/html; charset=utf-8');

$file = 'manutencao.html';

if (file_exists($file)) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    echo "<h1>Buscar TODAS as ocorrências de 'ituran' no arquivo</h1>";
    echo "<h2>Total de linhas: " . count($lines) . "</h2>";
    echo "<hr>";

    $found = 0;
    foreach ($lines as $index => $line) {
        $lineNum = $index + 1;
        if (stripos($line, 'ituran') !== false) {
            $found++;
            $isCommented = strpos(trim($line), '<!--') === 0;
            $color = $isCommented ? 'green' : 'red';
            $status = $isCommented ? '✅ Comentado' : '❌ ATIVO';

            echo "<div style='margin: 10px 0; padding: 10px; background: #f0f0f0;'>";
            echo "<strong style='color: $color;'>Linha $lineNum $status:</strong><br>";
            echo "<code style='display: block; margin-top: 5px; padding: 5px; background: white;'>";
            echo htmlspecialchars($line);
            echo "</code>";
            echo "</div>";
        }
    }

    echo "<hr>";
    echo "<h2>Total de ocorrências: $found</h2>";

    if ($found === 0) {
        echo "<p style='color: green;'>✅ Nenhuma referência ao ituran encontrada!</p>";
    }

} else {
    echo "<h1 style='color: red;'>❌ Arquivo manutencao.html não encontrado!</h1>";
}
?>
