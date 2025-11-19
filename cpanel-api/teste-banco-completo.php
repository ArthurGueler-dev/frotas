<?php
/**
 * Teste completo de conexao e estrutura do banco
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Teste de Conexao e Estrutura do Banco</h2>\n";
echo "<pre>\n";

// Configuracao
$host = '187.49.226.10';
$port = 3306;
$user = 'f137049_tool';
$password = 'In9@1234qwer';
$database = 'f137049_in9aut';

try {
    echo "1. TESTANDO CONEXAO...\n";
    echo "   Host: $host:$port\n";
    echo "   Database: $database\n";
    echo "   User: $user\n\n";

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "   ✓ Conexao estabelecida com sucesso!\n\n";

    // Verifica se a tabela existe
    echo "2. VERIFICANDO TABELA Telemetria_Diaria...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'Telemetria_Diaria'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "   ✓ Tabela existe!\n\n";

        // Mostra estrutura
        echo "3. ESTRUTURA DA TABELA:\n";
        $stmt = $pdo->query("DESCRIBE Telemetria_Diaria");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            echo "   - {$col['Field']}: {$col['Type']} {$col['Null']} {$col['Key']}\n";
        }
        echo "\n";

        // Conta registros
        echo "4. REGISTROS EXISTENTES:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Telemetria_Diaria");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Total de registros: {$row['total']}\n\n";

        // Mostra ultimos 5 registros
        if ($row['total'] > 0) {
            echo "5. ULTIMOS 5 REGISTROS:\n";
            $stmt = $pdo->query("
                SELECT LicensePlate, data, km_rodado, velocidade_media,
                       tempo_ligado_minutos, total_pontos_gps, fonte_api
                FROM Telemetria_Diaria
                ORDER BY data DESC, LicensePlate
                LIMIT 5
            ");
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($registros as $reg) {
                echo "   - Placa: {$reg['LicensePlate']}, Data: {$reg['data']}, ";
                echo "KM: {$reg['km_rodado']}, Vel: {$reg['velocidade_media']}, ";
                echo "Tempo: {$reg['tempo_ligado_minutos']}min, ";
                echo "Pontos: {$reg['total_pontos_gps']}, Fonte: {$reg['fonte_api']}\n";
            }
            echo "\n";
        }

        // Testa INSERT
        echo "6. TESTANDO INSERT DE DADOS:\n";
        $hoje = date('Y-m-d');
        $placaTeste = 'TESTE999';

        // Remove se existir
        $stmt = $pdo->prepare("DELETE FROM Telemetria_Diaria WHERE LicensePlate = ?");
        $stmt->execute([$placaTeste]);

        // Insere
        $sql = "INSERT INTO Telemetria_Diaria (
            LicensePlate, data, km_inicial, km_final, km_rodado,
            tempo_ligado_minutos, velocidade_media, velocidade_maxima,
            status_atual, lat_inicio, lng_inicio, lat_fim, lng_fim,
            total_pontos_gps, fonte_api
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            $placaTeste, $hoje,
            100.5, 150.8, 50.3,
            120, 65.5, 95.0,
            'Ligado',
            -23.550520, -46.633308, -23.560520, -46.643308,
            25, 'Teste'
        ]);

        if ($resultado) {
            $id = $pdo->lastInsertId();
            echo "   ✓ INSERT executado com sucesso! ID: $id\n";

            // Verifica se foi inserido
            $stmt = $pdo->prepare("SELECT * FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");
            $stmt->execute([$placaTeste, $hoje]);
            $reg = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reg) {
                echo "   ✓ Registro confirmado no banco:\n";
                echo "     - ID: {$reg['id']}\n";
                echo "     - Placa: {$reg['LicensePlate']}\n";
                echo "     - Data: {$reg['data']}\n";
                echo "     - KM Rodado: {$reg['km_rodado']}\n";
                echo "     - Velocidade Media: {$reg['velocidade_media']}\n";
                echo "     - Total Pontos GPS: {$reg['total_pontos_gps']}\n";
            } else {
                echo "   ✗ ERRO: Registro nao encontrado apos INSERT!\n";
            }

            // Remove registro de teste
            $stmt = $pdo->prepare("DELETE FROM Telemetria_Diaria WHERE LicensePlate = ?");
            $stmt->execute([$placaTeste]);
            echo "   ✓ Registro de teste removido\n";
        } else {
            echo "   ✗ ERRO: INSERT retornou false!\n";
        }
        echo "\n";

        // Testa UPDATE
        echo "7. TESTANDO UPDATE DE DADOS:\n";

        // Busca uma placa real para testar
        $stmt = $pdo->query("SELECT LicensePlate FROM Vehicles LIMIT 1");
        $veiculo = $stmt->fetch(PDO::FETCH_COLUMN);

        if ($veiculo) {
            echo "   Usando placa real: $veiculo\n";

            // Insere ou atualiza
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");
            $stmt->execute([$veiculo, $hoje]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row['total'] == 0) {
                // Insere primeiro
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $veiculo, $hoje,
                    100.5, 150.8, 50.3,
                    120, 65.5, 95.0,
                    'Ligado',
                    -23.550520, -46.633308, -23.560520, -46.643308,
                    25, 'TesteUpdate'
                ]);
                echo "   ✓ Registro inicial criado\n";
            }

            // Testa UPDATE
            $sqlUpdate = "UPDATE Telemetria_Diaria SET
                km_final = ?, km_rodado = ?, tempo_ligado_minutos = ?
                WHERE LicensePlate = ? AND data = ?";

            $stmt = $pdo->prepare($sqlUpdate);
            $resultado = $stmt->execute([200.5, 100.0, 180, $veiculo, $hoje]);

            if ($resultado) {
                $rows = $stmt->rowCount();
                echo "   ✓ UPDATE executado com sucesso! Linhas afetadas: $rows\n";

                // Verifica valores
                $stmt = $pdo->prepare("SELECT km_final, km_rodado, tempo_ligado_minutos FROM Telemetria_Diaria WHERE LicensePlate = ? AND data = ?");
                $stmt->execute([$veiculo, $hoje]);
                $reg = $stmt->fetch(PDO::FETCH_ASSOC);

                echo "   ✓ Valores atualizados:\n";
                echo "     - KM Final: {$reg['km_final']}\n";
                echo "     - KM Rodado: {$reg['km_rodado']}\n";
                echo "     - Tempo: {$reg['tempo_ligado_minutos']}min\n";
            } else {
                echo "   ✗ ERRO: UPDATE retornou false!\n";
            }
        }

    } else {
        echo "   ✗ ERRO: Tabela nao encontrada!\n";
        echo "\n7. TABELAS DISPONIVEIS:\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "   - $table\n";
        }
    }

    echo "\n=== TESTE COMPLETO ===\n";
    echo "Status: ✓ SUCESSO\n";

} catch (PDOException $e) {
    echo "\n✗ ERRO PDO: " . $e->getMessage() . "\n";
    echo "Codigo: " . $e->getCode() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

echo "</pre>\n";
?>
