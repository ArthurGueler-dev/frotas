<?php
/**
 * API para listar Alertas do banco de dados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Buscar alertas não resolvidos
    $sql = "SELECT 
                a.*,
                v.LicensePlate,
                v.VehicleName,
                DATEDIFF(a.due_date, NOW()) as days_remaining
            FROM FF_Alerts a
            LEFT JOIN Vehicles v ON a.vehicle_id = v.Id
            WHERE a.is_resolved = FALSE
            ORDER BY a.severity DESC, a.due_date ASC
            LIMIT 10";

    $stmt = $pdo->query($sql);
    $alerts = $stmt->fetchAll();

    // Formatar alertas para o frontend
    $formattedAlerts = array_map(function($alert) {
        $severityMap = array(
            'Crítico' => array('type' => 'urgent', 'icon' => 'error', 'color' => 'red'),
            'Urgente' => array('type' => 'urgent', 'icon' => 'warning', 'color' => 'red'),
            'Aviso' => array('type' => 'warning', 'icon' => 'warning', 'color' => 'yellow'),
            'Info' => array('type' => 'info', 'icon' => 'info', 'color' => 'blue')
        );

        $severity = isset($severityMap[$alert['severity']]) ? $severityMap[$alert['severity']] : $severityMap['Info'];
        
        $timeText = 'Sem data';
        if ($alert['days_remaining'] !== null) {
            if ($alert['days_remaining'] == 0) {
                $timeText = 'Hoje';
            } elseif ($alert['days_remaining'] == 1) {
                $timeText = 'Amanhã';
            } elseif ($alert['days_remaining'] < 0) {
                $timeText = 'Vencido';
            } else {
                $timeText = "Vence em {$alert['days_remaining']} dias";
            }
        }

        return array(
            'type' => $severity['type'],
            'title' => $alert['title'],
            'description' => $alert['description'],
            'time' => $timeText,
            'icon' => $severity['icon'],
            'color' => $severity['color']
        );
    }, $alerts);

    echo json_encode(array(
        'success' => true,
        'data' => $formattedAlerts
    ));

} catch (Exception $e) {
    error_log('Erro ao buscar alertas: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
