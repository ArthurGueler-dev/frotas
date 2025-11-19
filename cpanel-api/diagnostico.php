<?php
/**
 * Script de Diagnóstico da API de Telemetria
 *
 * Upload para: /home/f137049/public_html/api/diagnostico.php
 * Acesse: https://floripa.in9automacao.com.br/api/diagnostico.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Diagnóstico da API de Telemetria</h1>";
echo "<pre>";

// 1. Verificar versão do PHP
echo "1️⃣ <strong>PHP Version:</strong> " . phpversion() . "\n\n";

// 2. Verificar se Node.js está instalado
echo "2️⃣ <strong>Node.js:</strong>\n";
exec("which node 2>&1", $nodeWhich, $nodeWhichCode);
if ($nodeWhichCode === 0 && !empty($nodeWhich)) {
    echo "   ✅ Node.js encontrado: " . implode("\n", $nodeWhich) . "\n";
    exec("node --version 2>&1", $nodeVersion);
    echo "   📦 Versão: " . implode("\n", $nodeVersion) . "\n";
} else {
    echo "   ❌ Node.js NÃO encontrado!\n";
    echo "   💡 Instale via: cPanel > Setup Node.js App\n";
}
echo "\n";

// 3. Verificar se o script sync-telemetria.js existe
echo "3️⃣ <strong>Script sync-telemetria.js:</strong>\n";
$scriptPath = __DIR__ . '/sync-telemetria.js';
if (file_exists($scriptPath)) {
    echo "   ✅ Arquivo existe: $scriptPath\n";
    echo "   📊 Tamanho: " . filesize($scriptPath) . " bytes\n";

    // Verificar permissões
    $perms = substr(sprintf('%o', fileperms($scriptPath)), -4);
    echo "   🔐 Permissões: $perms\n";

    if (is_executable($scriptPath)) {
        echo "   ✅ Arquivo é executável\n";
    } else {
        echo "   ⚠️ Arquivo NÃO é executável\n";
        echo "   💡 Execute: chmod +x $scriptPath\n";
    }
} else {
    echo "   ❌ Arquivo NÃO encontrado em: $scriptPath\n";
    echo "   💡 Faça upload do arquivo sync-telemetria.js\n";
}
echo "\n";

// 4. Verificar node_modules
echo "4️⃣ <strong>Dependências Node.js:</strong>\n";
$nodeModulesPath = __DIR__ . '/node_modules';
if (is_dir($nodeModulesPath)) {
    echo "   ✅ Pasta node_modules existe\n";

    // Verificar mysql2
    if (is_dir($nodeModulesPath . '/mysql2')) {
        echo "   ✅ mysql2 instalado\n";
    } else {
        echo "   ❌ mysql2 NÃO instalado\n";
    }

    // Verificar xmldom
    if (is_dir($nodeModulesPath . '/xmldom')) {
        echo "   ✅ xmldom instalado\n";
    } else {
        echo "   ❌ xmldom NÃO instalado\n";
    }
} else {
    echo "   ❌ Pasta node_modules NÃO existe\n";
    echo "   💡 Execute: npm install mysql2 xmldom\n";
}
echo "\n";

// 5. Testar execução do Node.js
echo "5️⃣ <strong>Teste de Execução:</strong>\n";
if (file_exists($scriptPath) && $nodeWhichCode === 0) {
    echo "   🔄 Tentando executar script...\n";

    // Testa com um simples console.log
    $testScript = __DIR__ . '/test-node.js';
    file_put_contents($testScript, "console.log('✅ Node.js funcionando!');");

    exec("node $testScript 2>&1", $testOutput, $testCode);

    if ($testCode === 0) {
        echo "   ✅ Node.js executou com sucesso!\n";
        echo "   📤 Output: " . implode("\n", $testOutput) . "\n";
    } else {
        echo "   ❌ Erro ao executar Node.js\n";
        echo "   📤 Output: " . implode("\n", $testOutput) . "\n";
    }

    unlink($testScript);
} else {
    echo "   ⏭️ Pulando teste (Node.js ou script não encontrado)\n";
}
echo "\n";

// 6. Verificar conectividade com banco de dados
echo "6️⃣ <strong>Conexão MySQL:</strong>\n";
$mysqli = @new mysqli('187.49.226.10', 'f137049_tool', 'In9@1234qwer', 'f137049_in9aut');
if ($mysqli->connect_error) {
    echo "   ❌ Erro de conexão: " . $mysqli->connect_error . "\n";
} else {
    echo "   ✅ Conectado com sucesso!\n";

    // Verificar se a tabela Telemetria_Diaria existe
    $result = $mysqli->query("SHOW TABLES LIKE 'Telemetria_Diaria'");
    if ($result && $result->num_rows > 0) {
        echo "   ✅ Tabela Telemetria_Diaria existe\n";

        // Contar registros
        $count = $mysqli->query("SELECT COUNT(*) as total FROM Telemetria_Diaria");
        if ($count) {
            $row = $count->fetch_assoc();
            echo "   📊 Registros existentes: " . $row['total'] . "\n";
        }
    } else {
        echo "   ❌ Tabela Telemetria_Diaria NÃO existe\n";
        echo "   💡 Execute o script de criação de tabelas\n";
    }
    $mysqli->close();
}
echo "\n";

// 7. Informações do servidor
echo "7️⃣ <strong>Informações do Servidor:</strong>\n";
echo "   📁 Diretório atual: " . __DIR__ . "\n";
echo "   👤 Usuário PHP: " . get_current_user() . "\n";
echo "   🔐 Modo Safe Mode: " . (ini_get('safe_mode') ? 'Ativo' : 'Inativo') . "\n";
echo "   ⏱️ Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "   💾 Memory Limit: " . ini_get('memory_limit') . "\n";
echo "\n";

echo "</pre>";

echo "<hr>";
echo "<h2>📋 Resumo e Próximos Passos:</h2>";
echo "<ol>";

if ($nodeWhichCode !== 0) {
    echo "<li>❌ <strong>Instalar Node.js no cPanel</strong> (Setup Node.js App)</li>";
}

if (!file_exists($scriptPath)) {
    echo "<li>❌ <strong>Fazer upload do sync-telemetria.js</strong> para /public_html/api/</li>";
}

if (!is_dir($nodeModulesPath)) {
    echo "<li>❌ <strong>Instalar dependências:</strong> <code>cd /home/f137049/public_html/api/ && npm install mysql2 xmldom</code></li>";
}

if (file_exists($scriptPath) && !is_executable($scriptPath)) {
    echo "<li>⚠️ <strong>Dar permissão de execução:</strong> <code>chmod +x sync-telemetria.js</code></li>";
}

echo "<li>✅ Após corrigir os problemas acima, teste novamente!</li>";
echo "</ol>";
?>
