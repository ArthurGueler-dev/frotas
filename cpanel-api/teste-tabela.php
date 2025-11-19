<?php
/**
 * Script de teste para verificar a tabela Telemetria_Diaria
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuração do banco
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

echo "========================================\n";
echo "TESTE DE BANCO DE DADOS - TELEMETRIA\n";
echo "========================================\n\n";

try {
    echo "1. Conectando ao banco...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✓ Conexão estabelecida!\n\n";

    echo "2. Verificando se a tabela Telemetria_Diaria existe...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'Telemetria_Diaria'");
    $existe = $stmt->rowCount() > 0;

    if ($existe) {
        echo "   ✓ Tabela Telemetria_Diaria existe!\n\n";

        echo "3. Estrutura da tabela:\n";
        $stmt = $pdo->query("DESCRIBE Telemetria_Diaria");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($colunas as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";

        echo "4. Contagem de registros:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Telemetria_Diaria");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Total de registros: {$row['total']}\n\n";

        echo "5. Últimos 5 registros:\n";
        $stmt = $pdo->query("
            SELECT LicensePlate, data, km_inicial, km_final, km_rodado,
                   velocidade_media, velocidade_maxima, ultima_atualizacao
            FROM Telemetria_Diaria
            ORDER BY ultima_atualizacao DESC
            LIMIT 5
        ");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($registros) > 0) {
            foreach ($registros as $reg) {
                echo "   - Placa: {$reg['LicensePlate']}, Data: {$reg['data']}, ";
                echo "KM: {$reg['km_inicial']} → {$reg['km_final']} (rodado: {$reg['km_rodado']}), ";
                echo "Vel: {$reg['velocidade_media']}/{$reg['velocidade_maxima']}, ";
                echo "Atualizado: {$reg['ultima_atualizacao']}\n";
            }
        } else {
            echo "   (Nenhum registro encontrado)\n";
        }
        echo "\n";

        echo "6. Testando INSERT de dados de exemplo...\n";
        $hoje = date('Y-m-d');
        $placaTeste = 'TESTE123';

        // Remove se já existe
        $stmt = $pdo->prepare("DELETE FROM Telemetria_Diaria WHERE LicensePlate = ?");
        $stmt->execute([$placaTeste]);

        // Tenta inserir
        $sql = "INSERT INTO Telemetria_Diaria (
            LicensePlate, data, km_inicial, km_final, km_rodado,
            tempo_ligado_minutos, velocidade_media, velocidade_maxima,
            status_atual, total_pontos_gps, fonte_api
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $placaTeste, $hoje,
            100.5, 150.8, 50.3,
            120, 45.5, 80.2,
            'Ligado', 100, 'Teste'
        ]);

        if ($resultado) {
            echo "   ✓ INSERT realizado com sucesso!\n";

            // Verifica se foi inserido
            $stmt = $pdo->prepare("SELECT * FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");
            $stmt->execute([$placaTeste, $hoje]);
            $reg = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reg) {
                echo "   ✓ Registro encontrado após INSERT:\n";
                echo "     - ID: {$reg['id']}\n";
                echo "     - Placa: {$reg['LicensePlate']}\n";
                echo "     - Data: {$reg['data']}\n";
                echo "     - KM Rodado: {$reg['km_rodado']}\n";

                // Remove registro de teste
                $stmt = $pdo->prepare("DELETE FROM Telemetria_Diaria WHERE LicensePlate = ?");
                $stmt->execute([$placaTeste]);
                echo "   ✓ Registro de teste removido\n";
            } else {
                echo "   ✗ ERRO: Registro não foi encontrado após INSERT!\n";
            }
        } else {
            echo "   ✗ ERRO ao executar INSERT!\n";
        }

    } else {
        echo "   ✗ Tabela Telemetria_Diaria NÃO existe!\n";
        echo "   Você precisa criar a tabela primeiro.\n";
    }

    echo "\n========================================\n";
    echo "TESTE CONCLUÍDO\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "\n✗ ERRO DE CONEXÃO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
}
?>
