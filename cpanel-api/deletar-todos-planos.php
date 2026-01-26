<?php
/**
 * Script para DELETAR TODOS os planos de manutenção do sistema
 * ATENÇÃO: Este script remove TODOS os planos e suas peças associadas
 * Use com EXTREMO cuidado!
 *
 * Acesso: https://floripa.in9automacao.com.br/deletar-todos-planos.php?confirmar=SIM_DELETAR_TUDO
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configuração do banco de dados
require_once 'config-db.php';

// Verificar confirmação FORTE
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM_DELETAR_TUDO') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Confirmação necessária',
        'message' => 'Para executar este script, adicione ?confirmar=SIM_DELETAR_TUDO na URL',
        'warning' => '⚠️ ATENÇÃO: Esta ação irá DELETAR TODOS OS PLANOS DE MANUTENÇÃO E SUAS PEÇAS!',
        'url_exemplo' => 'https://floripa.in9automacao.com.br/deletar-todos-planos.php?confirmar=SIM_DELETAR_TUDO'
    ]);
    exit();
}

// Criar conexão
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com o banco de dados',
        'details' => $e->getMessage()
    ]);
    exit();
}

try {
    // Iniciar transação
    $conn->begin_transaction();

    // PASSO 1: Contar peças associadas antes de deletar
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM FF_PlanoManutencao_Pecas");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_pecas_antes = $row['total'];
    $stmt->close();

    // PASSO 2: Contar planos antes de deletar
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Planos_Manutenção");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_planos_antes = $row['total'];
    $stmt->close();

    // PASSO 3: Deletar TODAS as peças associadas primeiro (integridade referencial)
    $stmt = $conn->prepare("DELETE FROM FF_PlanoManutencao_Pecas");
    $stmt->execute();
    $total_pecas_removidas = $stmt->affected_rows;
    $stmt->close();

    // PASSO 4: Deletar TODOS os planos de manutenção
    $stmt = $conn->prepare("DELETE FROM Planos_Manutenção");
    $stmt->execute();
    $total_planos_removidos = $stmt->affected_rows;
    $stmt->close();

    // Confirmar transação
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => '✅ Todos os planos de manutenção e peças foram deletados com sucesso',
        'resumo' => [
            'planos_antes' => $total_planos_antes,
            'planos_deletados' => $total_planos_removidos,
            'pecas_associadas_antes' => $total_pecas_antes,
            'pecas_associadas_deletadas' => $total_pecas_removidas
        ],
        'executado_em' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao executar deleção',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
