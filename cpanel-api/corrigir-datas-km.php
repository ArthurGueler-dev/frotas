<?php
/**
 * Correção de Datas na Tabela daily_mileage
 *
 * BUG IDENTIFICADO: O sistema estava salvando o KM com a data 1 dia à frente.
 *
 * Explicação:
 * - API Ituran com LocTime=22/01 retorna odômetro de meia-noite (00:02) do dia 22
 * - Isso representa o FIM do dia 21/01, não o início do dia 22
 * - Quando calculamos: API(22/01) - API(21/01) = KM rodado no dia 21
 * - Mas o código salvava com date=22/01 (errado!)
 * - O correto é salvar com date=21/01
 *
 * Este script corrige os dados existentes subtraindo 1 dia de todas as datas.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

// Parâmetro para executar a correção (por segurança, default é apenas diagnóstico)
$executar = isset($_GET['executar']) && $_GET['executar'] === 'sim';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $resultado = array(
        'modo' => $executar ? 'EXECUÇÃO' : 'DIAGNÓSTICO (adicione ?executar=sim para corrigir)',
        'data_execucao' => date('Y-m-d H:i:s'),
        'diagnostico' => array(),
        'correcao' => array()
    );

    // ============================================
    // FASE 1: DIAGNÓSTICO
    // ============================================

    // 1. Total de registros na tabela
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM daily_mileage");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $resultado['diagnostico']['total_registros'] = $total;

    // 2. Registros por dia (últimos 10 dias)
    $stmt = $pdo->query("
        SELECT
            date,
            COUNT(*) as qtd_veiculos,
            ROUND(SUM(km_driven), 2) as total_km,
            ROUND(AVG(km_driven), 2) as media_km
        FROM daily_mileage
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 10 DAY)
        GROUP BY date
        ORDER BY date DESC
    ");
    $resultado['diagnostico']['registros_por_dia'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Verificar se há registros para HOJE (indicativo do bug)
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM daily_mileage WHERE date = ?");
    $stmt->execute(array($hoje));
    $registrosHoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $resultado['diagnostico']['registros_hoje'] = $registrosHoje;
    $resultado['diagnostico']['bug_confirmado'] = $registrosHoje > 0 ?
        'SIM - Existem registros para HOJE, o que indica o bug (dados deveriam ser de ONTEM)' :
        'Possivelmente não (não há registros para hoje)';

    // 4. Verificar estrutura da tabela (constraints)
    $stmt = $pdo->query("SHOW CREATE TABLE daily_mileage");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $createTable = isset($row['Create Table']) ? $row['Create Table'] : '';
    $temChaveUnica = strpos($createTable, 'UNIQUE') !== false;
    $resultado['diagnostico']['tem_chave_unica'] = $temChaveUnica;

    // 5. Verificar possíveis conflitos após correção
    $stmt = $pdo->query("
        SELECT
            vehicle_plate,
            DATE_SUB(date, INTERVAL 1 DAY) as nova_data,
            COUNT(*) as registros
        FROM daily_mileage
        GROUP BY vehicle_plate, DATE_SUB(date, INTERVAL 1 DAY)
        HAVING COUNT(*) > 1
        LIMIT 10
    ");
    $conflitos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resultado['diagnostico']['possiveis_conflitos'] = count($conflitos);
    if (count($conflitos) > 0) {
        $resultado['diagnostico']['exemplos_conflitos'] = $conflitos;
    }

    // ============================================
    // FASE 2: CORREÇÃO (se solicitado)
    // ============================================

    if ($executar) {
        $pdo->beginTransaction();

        try {
            // Estratégia: Como pode haver chave única, precisamos fazer em ordem
            // Do registro mais antigo para o mais recente para evitar conflitos

            // Primeiro, verificar se tem a constraint
            if ($temChaveUnica) {
                // Abordagem segura: criar tabela temporária
                $resultado['correcao']['estrategia'] = 'Usando tabela temporária (há chave única)';

                // 1. Criar tabela temporária com dados corrigidos
                $pdo->exec("DROP TEMPORARY TABLE IF EXISTS temp_daily_mileage");
                $pdo->exec("
                    CREATE TEMPORARY TABLE temp_daily_mileage AS
                    SELECT
                        vehicle_plate,
                        DATE_SUB(date, INTERVAL 1 DAY) as date,
                        odometer_start,
                        odometer_end,
                        km_driven,
                        source,
                        sync_status,
                        synced_at
                    FROM daily_mileage
                ");

                // 2. Contar registros na temp
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM temp_daily_mileage");
                $tempCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                $resultado['correcao']['registros_temp'] = $tempCount;

                // 3. Limpar tabela original
                $pdo->exec("DELETE FROM daily_mileage");
                $resultado['correcao']['tabela_limpa'] = true;

                // 4. Reinserir com agregação para evitar duplicatas (pega o último registro do dia)
                $pdo->exec("
                    INSERT INTO daily_mileage (vehicle_plate, date, odometer_start, odometer_end, km_driven, source, sync_status, synced_at)
                    SELECT
                        vehicle_plate,
                        date,
                        MAX(odometer_start) as odometer_start,
                        MAX(odometer_end) as odometer_end,
                        MAX(km_driven) as km_driven,
                        MAX(source) as source,
                        'success' as sync_status,
                        MAX(synced_at) as synced_at
                    FROM temp_daily_mileage
                    GROUP BY vehicle_plate, date
                ");

                $inserted = $pdo->query("SELECT ROW_COUNT() as affected")->fetch(PDO::FETCH_ASSOC);
                $resultado['correcao']['registros_inseridos'] = isset($inserted['affected']) ? $inserted['affected'] : 'N/A';

            } else {
                // Sem chave única, pode fazer UPDATE direto
                $resultado['correcao']['estrategia'] = 'UPDATE direto (sem chave única)';

                $stmt = $pdo->exec("UPDATE daily_mileage SET date = DATE_SUB(date, INTERVAL 1 DAY)");
                $resultado['correcao']['registros_atualizados'] = $stmt;
            }

            // Verificar resultado
            $stmt = $pdo->query("
                SELECT
                    date,
                    COUNT(*) as qtd_veiculos,
                    ROUND(SUM(km_driven), 2) as total_km
                FROM daily_mileage
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 10 DAY)
                GROUP BY date
                ORDER BY date DESC
            ");
            $resultado['correcao']['registros_apos_correcao'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->commit();
            $resultado['correcao']['status'] = 'SUCESSO';
            $resultado['correcao']['mensagem'] = 'Datas corrigidas com sucesso! Todos os registros foram movidos 1 dia para trás.';

        } catch (Exception $e) {
            $pdo->rollBack();
            $resultado['correcao']['status'] = 'ERRO';
            $resultado['correcao']['erro'] = $e->getMessage();
        }
    } else {
        $resultado['correcao']['instrucoes'] = 'Para executar a correção, acesse: corrigir-datas-km.php?executar=sim';
    }

    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_PRETTY_PRINT);
}
