<?php
header('Content-Type: text/html; charset=utf-8');

$file = 'manutencao.html';

if (file_exists($file)) {
    $content = file_get_contents($file);

    // Verificar se tem o script comentado
    $hasCommentedScript = strpos($content, '<!-- <script src="ituran-service.js"></script> -->') !== false;
    $hasActiveScript = preg_match('/<script src="ituran-service\.js"><\/script>/', $content);

    echo "<h1>Verificação do manutencao.html no Servidor</h1>";
    echo "<h2>Status:</h2>";

    if ($hasCommentedScript && !$hasActiveScript) {
        echo "<p style='color: green; font-weight: bold;'>✅ CORRETO: Script está comentado!</p>";
    } elseif ($hasActiveScript) {
        echo "<p style='color: red; font-weight: bold;'>❌ ERRO: Script está ATIVO (não comentado)!</p>";
        echo "<p>O arquivo no servidor NÃO foi atualizado.</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Status desconhecido</p>";
    }

    echo "<h2>Linhas 5-15 do arquivo:</h2>";
    echo "<pre>";
    $lines = explode("\n", $content);
    for ($i = 4; $i < 15 && $i < count($lines); $i++) {
        $lineNum = $i + 1;
        echo sprintf("%3d: %s\n", $lineNum, htmlspecialchars($lines[$i]));
    }
    echo "</pre>";

    echo "<h2>Data de modificação:</h2>";
    echo "<p>" . date("Y-m-d H:i:s", filemtime($file)) . "</p>";

} else {
    echo "<h1 style='color: red;'>❌ Arquivo manutencao.html não encontrado!</h1>";
}
?>
