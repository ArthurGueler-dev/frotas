<?php
/**
 * Script para inserir as áreas corretas do sistema
 *
 * Executa:
 * 1. Remove áreas padrão antigas
 * 2. Insere as 6 áreas corretas
 */

header('Content-Type: text/plain; charset=utf-8');

// Conexão com banco
$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Conectado ao banco de dados\n\n";

    // ============================================================
    // 1. LIMPAR áreas antigas
    // ============================================================
    echo "🧹 Limpando áreas antigas...\n";

    $pdo->exec("DELETE FROM areas");
    echo "   ✅ Áreas antigas removidas\n\n";

    // ============================================================
    // 2. INSERIR áreas corretas
    // ============================================================
    echo "📍 Inserindo áreas corretas...\n";

    $areas_corretas = [
        ['Barra de São Francisco', 'ES', 'Região Noroeste do Espírito Santo'],
        ['Guarapari', 'ES', 'Região Metropolitana Sul'],
        ['Santa Tereza', 'ES', 'Região Serrana'],
        ['Castelo', 'ES', 'Região Sul do Espírito Santo'],
        ['Aracruz', 'ES', 'Região Norte'],
        ['Nova Venécia', 'ES', 'Região Norte do Espírito Santo']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO areas (name, state, description, is_active)
        VALUES (?, ?, ?, 1)
    ");

    $inserted = 0;
    foreach ($areas_corretas as $area) {
        $stmt->execute($area);
        $inserted++;
        echo "   ✅ {$area[0]} (ID: {$pdo->lastInsertId()})\n";
    }

    echo "\n";
    echo "=" . str_repeat("=", 60) . "\n";
    echo "✅ ÁREAS CADASTRADAS COM SUCESSO!\n";
    echo "=" . str_repeat("=", 60) . "\n\n";

    echo "📊 Total: $inserted áreas cadastradas\n\n";

    // ============================================================
    // 3. LISTAR áreas cadastradas
    // ============================================================
    echo "📋 Áreas disponíveis no sistema:\n\n";

    $stmt = $pdo->query("
        SELECT id, name, state, is_active
        FROM areas
        ORDER BY name
    ");

    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($areas as $area) {
        $status = $area['is_active'] ? '🟢 Ativa' : '🔴 Inativa';
        echo "   ID {$area['id']}: {$area['name']} ({$area['state']}) - $status\n";
    }

    echo "\n";
    echo "🎯 Próximo passo:\n";
    echo "   Associar veículos às áreas usando:\n";
    echo "   UPDATE Vehicles SET area_id = X WHERE LicensePlate = 'ABC1234'\n\n";

    echo json_encode([
        'success' => true,
        'areas_inserted' => $inserted,
        'areas' => $areas
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit(1);
}
?>
