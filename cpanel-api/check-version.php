<?php
header('Content-Type: text/plain');
echo "Verificação de arquivo users-frotas-api.php\n";
echo "===========================================\n\n";

$file = __DIR__ . '/users-frotas-api.php';

if (file_exists($file)) {
    echo "✓ Arquivo existe\n";
    echo "Local: $file\n";
    echo "Tamanho: " . filesize($file) . " bytes\n";
    echo "Modificado: " . date('Y-m-d H:i:s', filemtime($file)) . "\n\n";

    $content = file_get_contents($file);

    if (strpos($content, 'random_bytes') !== false) {
        echo "❌ ARQUIVO ANTIGO - Contém random_bytes()\n";
    } elseif (strpos($content, 'openssl_random_pseudo_bytes') !== false) {
        echo "✓ ARQUIVO ATUALIZADO - Contém openssl_random_pseudo_bytes()\n";
    } else {
        echo "? Não encontrou nenhuma das funções\n";
    }
} else {
    echo "❌ Arquivo não encontrado\n";
}
