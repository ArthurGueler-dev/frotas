<?php
/**
 * Script para remover TODAS as peças dos planos de manutenção
 * ATENÇÃO: Este script remove TODAS as associações entre peças e itens de planos
 * Use com cuidado!
 *
 * Acesso: https://floripa.in9automacao.com.br/limpar-pecas-planos.php?confirmar=SIM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configuração do banco de dados
require_once 'config-db.php';

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Confirmação necessária',
        'message' => 'Para executar este script, adicione ?confirmar=SIM na URL',
        'warning' => 'ATENÇÃO: Esta ação irá remover TODAS as peças de TODOS os planos de manutenção!',
        'url_exemplo' => 'https://floripa.in9automacao.com.br/limpar-pecas-planos.php?confirmar=SIM'
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
    // Contar quantas associações existem antes de deletar
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM FF_PlanoManutencao_Pecas");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_antes = $row['total'];
    $stmt->close();

    // Deletar TODAS as associações
    $stmt = $conn->prepare("DELETE FROM FF_PlanoManutencao_Pecas");

    if ($stmt->execute()) {
        $total_removido = $stmt->affected_rows;

        echo json_encode([
            'success' => true,
            'message' => 'Todas as peças foram removidas dos planos de manutenção',
            'total_antes' => $total_antes,
            'total_removido' => $total_removido,
            'executado_em' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao deletar associações',
            'details' => $stmt->error
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao executar limpeza',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
