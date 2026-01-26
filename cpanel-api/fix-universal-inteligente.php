<?php
/**
 * Fix INTELIGENTE - Identifica peças específicas pelo nome
 *
 * - Peças com nomes de marcas/modelos = específicas (universal=0)
 * - Peças genéricas = universais (universal=1)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");

    $results = [];

    // Palavras-chave que indicam peça ESPECÍFICA de um modelo/marca
    $palavrasEspecificas = [
        // Marcas
        'Toyota', 'GM', 'Chevrolet', 'Ford', 'Fiat', 'Volkswagen', 'VW', 'Honda', 'Hyundai',
        'Mitsubishi', 'Nissan', 'Renault', 'Peugeot', 'Citroën', 'Citroen', 'Mercedes', 'MB',
        'Iveco', 'Scania', 'Volvo', 'MAN', 'DAF', 'Kia', 'Jeep', 'Ram', 'Dodge',
        // Modelos comuns
        'Hilux', 'S10', 'Ranger', 'L200', 'Amarok', 'Frontier', 'Triton',
        'Celta', 'Corsa', 'Onix', 'Prisma', 'Cobalt', 'Cruze', 'Tracker', 'Spin', 'Montana',
        'Gol', 'Voyage', 'Saveiro', 'Fox', 'Polo', 'Virtus', 'T-Cross', 'Nivus',
        'Uno', 'Mobi', 'Argo', 'Cronos', 'Strada', 'Toro', 'Fiorino', 'Ducato',
        'Ka', 'Fiesta', 'Focus', 'EcoSport', 'Territory', 'Maverick',
        'HB20', 'Creta', 'Tucson', 'Santa Fe', 'HR', 'HD',
        'Fit', 'City', 'Civic', 'HR-V', 'HRV', 'CR-V', 'CRV', 'WR-V',
        'Sandero', 'Logan', 'Duster', 'Captur', 'Kwid', 'Oroch',
        'Accelo', 'Atego', 'Axor', 'Actros', 'Sprinter',
        'Cargo', 'F-250', 'F-350', 'F-4000',
        'Delivery', 'Constellation', 'Worker',
        'Daily', 'Tector', 'Cursor',
        'Classic', 'Meriva', 'Zafira', 'Astra', 'Vectra',
        'Palio', 'Siena', 'Weekend', 'Punto', 'Linea', 'Bravo',
        '208', '2008', '308', '3008', 'Partner', 'Expert', 'Boxer',
        'C3', 'C4', 'Aircross', 'Jumper', 'Jumpy',
        'Compass', 'Renegade', 'Commander', 'Wrangler',
        'Kicks', 'Versa', 'Sentra', 'March', 'Livina',
        'Sportage', 'Sorento', 'Cerato', 'Soul', 'Picanto', 'Bongo'
    ];

    // PASSO 1: Setar TODAS como específicas primeiro
    $conn->query("UPDATE FF_Pecas SET universal = 0");
    $results[] = "RESET: Todas setadas para específicas (universal=0)";

    // PASSO 2: Setar como universais as que NÃO contêm palavras específicas
    // Construir condição WHERE NOT (nome LIKE '%palavra1%' OR nome LIKE '%palavra2%' ...)
    $conditions = [];
    foreach ($palavrasEspecificas as $palavra) {
        $palavraEscapada = $conn->real_escape_string($palavra);
        $conditions[] = "nome LIKE '%{$palavraEscapada}%'";
    }

    $whereEspecifica = implode(' OR ', $conditions);

    // Peças que NÃO contêm nenhuma palavra específica = universais
    $sqlUniversal = "UPDATE FF_Pecas SET universal = 1 WHERE NOT ({$whereEspecifica})";
    $conn->query($sqlUniversal);
    $universaisAtualizadas = $conn->affected_rows;
    $results[] = "Peças GENÉRICAS setadas como universais: " . $universaisAtualizadas;

    // PASSO 3: Estatísticas
    $countUniv = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 1");
    $countEsp = $conn->query("SELECT COUNT(*) as total FROM FF_Pecas WHERE universal = 0");

    $totalUniversais = $countUniv->fetch_assoc()['total'];
    $totalEspecificas = $countEsp->fetch_assoc()['total'];

    // Amostra de universais
    $amostraUniv = $conn->query("SELECT id, nome, categoria FROM FF_Pecas WHERE universal = 1 LIMIT 15");
    $universais = [];
    while ($row = $amostraUniv->fetch_assoc()) {
        $universais[] = $row;
    }

    // Amostra de específicas
    $amostraEsp = $conn->query("SELECT id, nome, categoria FROM FF_Pecas WHERE universal = 0 LIMIT 15");
    $especificas = [];
    while ($row = $amostraEsp->fetch_assoc()) {
        $especificas[] = $row;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Correção INTELIGENTE aplicada!',
        'results' => $results,
        'estatisticas' => [
            'pecas_universais' => $totalUniversais,
            'pecas_especificas' => $totalEspecificas
        ],
        'amostra_universais' => $universais,
        'amostra_especificas' => $especificas
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
