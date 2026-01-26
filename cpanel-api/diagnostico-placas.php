<?php
/**
 * Diagnóstico de placas - compara CSV com banco de dados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host = '187.49.226.10';
$dbname = 'f137049_in9aut';
$username = 'f137049_tool';
$password = 'In9@1234qwer';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(array('success' => false, 'error' => 'Erro de conexao: ' . $e->getMessage())));
}

// Placas do CSV que não foram encontradas
$placasNaoEncontradas = array(
    "OXX3A48", "OWZ2B59", "OVN3I55", "MTP9H28", "MTP4B46", "RIH2E87", "RIO0G91", "RIN0G99",
    "RIN3A92", "RIP2G20", "RIQ3J03", "RIP7A81", "RIQ7G33", "RIS3E06", "RIS0H09", "OYM3E86",
    "RIL9J23", "RJF5E97", "RJF5C14", "RJF4H30", "RJF5B44", "RJK5J83", "OWW1D60", "PPH5H88",
    "QRQ1E80", "OWD6G38", "OXP0A39", "OVJ8G49", "PPH6J26", "OVV8J15", "RHK6I69", "RGZ5E63",
    "RIN7F33", "RHL1F73", "RIO7D10", "MTP7F59", "RIQ3B96", "RIO3E44", "PPH8A18", "MTP7C67",
    "PPH7E81", "PPJ2F91", "PPL8D24", "PPL8J02", "QRB4F19", "QRB4C96", "QRB4E10", "QRE6I70",
    "RHK2I69", "RIN5J06", "RIN1E61", "RIO3A98", "RIO3D93", "RIO5G46", "RIO5D21", "RIS3B18",
    "RIS3G36", "RJC2J69", "RJC1I22", "RJC7H94", "RJF6G45", "RJJ1D72", "RJJ9F46", "OWZ7E62",
    "QRB5D93", "OWX2J91", "MTP1C26", "RHL6H65", "RIO6G68"
);

// Buscar todas as placas do banco
$stmt = $pdo->query("SELECT Id, LicensePlate FROM Vehicles ORDER BY LicensePlate");
$veiculosBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);

$placasBanco = array();
foreach ($veiculosBanco as $v) {
    $placasBanco[] = $v['LicensePlate'];
}

// Verificar formatos
$resultado = array(
    'total_banco' => count($placasBanco),
    'placas_banco_amostra' => array_slice($placasBanco, 0, 20),
    'placas_csv_nao_encontradas' => $placasNaoEncontradas,
    'analise' => array()
);

// Tentar encontrar correspondências com diferentes formatos
foreach ($placasNaoEncontradas as $placaCSV) {
    $encontrada = false;
    $match = null;

    // Formato original
    if (in_array($placaCSV, $placasBanco)) {
        $encontrada = true;
        $match = $placaCSV;
    }

    // Com hífen (ABC-1234 ou ABC-1D23)
    if (!$encontrada) {
        $comHifen = substr($placaCSV, 0, 3) . '-' . substr($placaCSV, 3);
        if (in_array($comHifen, $placasBanco)) {
            $encontrada = true;
            $match = $comHifen;
        }
    }

    // Busca parcial (primeiros 3 caracteres)
    if (!$encontrada) {
        $prefixo = substr($placaCSV, 0, 3);
        foreach ($placasBanco as $placaBanco) {
            if (strpos($placaBanco, $prefixo) === 0) {
                $resultado['analise'][] = array(
                    'placa_csv' => $placaCSV,
                    'similar_banco' => $placaBanco,
                    'status' => 'similar_encontrada'
                );
                break;
            }
        }
    }

    if ($encontrada) {
        $resultado['analise'][] = array(
            'placa_csv' => $placaCSV,
            'match_banco' => $match,
            'status' => 'encontrada'
        );
    }
}

// Verificar se há placas no banco que não estão no CSV
$placasCSVCompletas = array(
    "OVE4358", "MTQ7J93", "PPG4B36", "PPW0562", "PPX2803", "RNQ2H45", "MTQ3874", "PPV1E52", "RHL3B76",
    "OXX3A48", "OWZ2B59", "OVN3I55", "MTP9H28", "MTP4B46", "RIH2E87", "RIO0G91", "RIN0G99",
    "RIN3A92", "RIP2G20", "RIQ3J03", "RIP7A81", "RIQ7G33", "RIS3E06", "RIS0H09", "OYM3E86",
    "RIL9J23", "RJF5E97", "RJF5C14", "RJF4H30", "RJF5B44", "RJK5J83", "OWW1D60", "PPH5H88",
    "QRQ1E80", "OWD6G38", "OXP0A39", "OVJ8G49", "PPH6J26", "OVV8J15", "RHK6I69", "RGZ5E63",
    "RIN7F33", "RHL1F73", "RIO7D10", "MTP7F59", "RIQ3B96", "RIO3E44", "PPH8A18", "MTP7C67",
    "PPH7E81", "PPJ2F91", "PPL8D24", "PPL8J02", "QRB4F19", "QRB4C96", "QRB4E10", "QRE6I70",
    "RHK2I69", "RIN5J06", "RIN1E61", "RIO3A98", "RIO3D93", "RIO5G46", "RIO5D21", "RIS3B18",
    "RIS3G36", "RJC2J69", "RJC1I22", "RJC7H94", "RJF6G45", "RJJ1D72", "RJJ9F46", "OWZ7E62",
    "QRB5D93", "OWX2J91", "MTP1C26", "RHL6H65", "RIO6G68"
);

// Normalizar placas (remover hífen) para comparação
$placasBancoNormalizadas = array();
foreach ($placasBanco as $placa) {
    $placasBancoNormalizadas[str_replace('-', '', $placa)] = $placa;
}

$correspondencias = array();
$semCorrespondencia = array();

foreach ($placasNaoEncontradas as $placaCSV) {
    $placaNormalizada = str_replace('-', '', $placaCSV);
    if (isset($placasBancoNormalizadas[$placaNormalizada])) {
        $correspondencias[] = array(
            'csv' => $placaCSV,
            'banco' => $placasBancoNormalizadas[$placaNormalizada]
        );
    } else {
        $semCorrespondencia[] = $placaCSV;
    }
}

$resultado['correspondencias_encontradas'] = $correspondencias;
$resultado['sem_correspondencia'] = $semCorrespondencia;
$resultado['total_sem_correspondencia'] = count($semCorrespondencia);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
