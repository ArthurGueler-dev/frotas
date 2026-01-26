<?php
/**
 * Script para importar TODAS as peças mencionadas no plano de manutenção da Hilux
 * Extraído da resposta do Perplexity AI
 * Data: 2026-01-09
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesse com ?confirmar=SIM para executar a importação',
        'warning' => 'Isso irá inserir ~100 peças no banco de dados'
    ], JSON_PRETTY_PRINT);
    exit;
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

// ===================================
// PEÇAS EXTRAÍDAS DO PERPLEXITY
// ===================================
// Formato: [codigo, nome, categoria, custo_original, custo_similar, tipo, estoque_minimo, observacoes]

$pecas = [
    // ============================================
    // FILTROS
    // ============================================
    // Filtro Óleo Motor
    ['90915-YZZJ2', 'Filtro de Óleo Motor Toyota Hilux 2.8 Original', 'filtros', 52.50, null, 'original', 10, 'Filtro blindado 3/4-16UNF rosca. Motor 1GD-FTV 2.8L Turbo Diesel'],
    ['PSL127', 'Filtro de Óleo Tecfil PSL127 Hilux 2.8', 'filtros', null, 30.00, 'similar', 10, 'Similar ao 90915-YZZJ2. Economia ~R$ 20,00'],
    ['W712/83', 'Filtro de Óleo Mann W712/83 Hilux 2.8', 'filtros', null, 40.00, 'similar', 8, 'Similar ao 90915-YZZJ2. Economia ~R$ 10,00'],
    ['JFO0211', 'Filtro de Óleo Wega JFO0211 Hilux 2.8', 'filtros', null, 30.00, 'similar', 8, 'Similar ao 90915-YZZJ2. Economia ~R$ 20,00'],

    // Filtro Ar Motor
    ['17801-0L050', 'Filtro de Ar Motor Toyota Hilux 2.8 Original', 'filtros', 300.00, null, 'original', 5, 'Elemento primário cartucho 322x239x60mm'],
    ['ARS7065', 'Filtro de Ar Tecfil ARS7065 Hilux 2.8', 'filtros', null, 100.00, 'similar', 5, 'Similar ao 17801-0L050. Economia ~R$ 180,00'],
    ['C25124/1', 'Filtro de Ar Mann C25124/1 Hilux 2.8', 'filtros', null, 125.00, 'similar', 4, 'Similar ao 17801-0L050. Economia ~R$ 160,00'],
    ['WA10448', 'Filtro de Ar Wix WA10448 Hilux 2.8', 'filtros', null, 110.00, 'similar', 4, 'Similar ao 17801-0L050. Economia ~R$ 170,00'],

    // Filtro Combustível
    ['23300-0L041', 'Filtro de Combustível Principal Toyota Hilux 2.8 Original', 'filtros', 220.00, null, 'original', 8, 'Filtro blindado separador água. Common rail 2.000 bar'],
    ['23390-0L070', 'Filtro de Combustível Secundário Toyota Hilux 2.8 Original', 'filtros', 175.00, null, 'original', 6, 'Pré-filtro sistema common rail'],
    ['PC953', 'Filtro de Combustível Tecfil PC953 Hilux 2.8', 'filtros', null, 140.00, 'similar', 8, 'Similar ao 23300-0L041. Economia ~R$ 70,00'],
    ['PU9023Z', 'Filtro de Combustível Mann PU9023Z Hilux 2.8', 'filtros', null, 170.00, 'similar', 6, 'Similar ao 23300-0L041. Economia ~R$ 40,00'],
    ['KX570D', 'Filtro de Combustível Mahle KX570D Hilux 2.8', 'filtros', null, 160.00, 'similar', 6, 'Similar ao 23300-0L041. Economia ~R$ 50,00'],

    // Filtro Cabine
    ['87139-0K030', 'Filtro de Cabine (Ar Condicionado) Toyota Hilux Original', 'filtros', 100.00, null, 'original', 10, 'Filtro pólen. Evita odores e reduz eficiência AC'],
    ['ACP889', 'Filtro de Cabine Tecfil ACP889 Hilux', 'filtros', null, 32.50, 'similar', 10, 'Similar ao 87139-0K030. Economia ~R$ 50,00'],
    ['CU22032', 'Filtro de Cabine Mann CU22032 Hilux', 'filtros', null, 47.50, 'similar', 8, 'Similar ao 87139-0K030. Economia ~R$ 35,00'],
    ['1987435590', 'Filtro de Cabine Bosch 1987435590 Hilux', 'filtros', null, 42.50, 'similar', 8, 'Similar ao 87139-0K030. Economia ~R$ 40,00'],

    // ============================================
    // ÓLEOS E FLUIDOS
    // ============================================
    // Óleo Motor
    ['08880-10705', 'Óleo de Motor 5W-30 ACEA C2/C3 Toyota Original (1L)', 'oleos', 52.50, null, 'original', 50, 'API SN, low-SAPS. Específico diesel com DPF. Capacidade: 8L'],
    ['LUBRAX-5W30', 'Óleo Lubrax SVU 5W-30 Sintético Diesel (1L)', 'oleos', null, 42.50, 'similar', 40, 'Similar ao original. ACEA C2/C3 compatível DPF'],
    ['DELVAC-5W30', 'Óleo Mobil Delvac 1 ESP 5W-30 Sintético (1L)', 'oleos', null, 65.00, 'similar', 30, 'Premium. ACEA C2/C3. Excelente proteção turbo'],
    ['RIMULA-5W30', 'Óleo Shell Rimula R6 LME 5W-30 Sintético (1L)', 'oleos', null, 65.00, 'similar', 30, 'Premium. ACEA C2/C3. Alto desempenho'],

    // Fluido Freio
    ['08823-80001', 'Fluido de Freio DOT 3 Toyota Original (1L)', 'oleos', 60.00, null, 'original', 15, 'DOT 3 ou superior. Trocar a cada 30.000 km ou 24 meses'],
    ['DOT4-ATE', 'Fluido de Freio ATE DOT 4 (1L)', 'oleos', null, 40.00, 'similar', 15, 'DOT 4. Superior ao DOT 3. Economia ~R$ 20,00'],
    ['DOT4-BOSCH', 'Fluido de Freio Bosch DOT 4 (1L)', 'oleos', null, 35.00, 'similar', 15, 'DOT 4. Ponto ebulição alto. Economia ~R$ 25,00'],
    ['DOT3-TEXACO', 'Fluido de Freio Texaco DOT 3 (1L)', 'oleos', null, 30.00, 'similar', 12, 'DOT 3. Custo-benefício. Economia ~R$ 30,00'],

    // ATF Transmissão Automática
    ['08886-02505', 'ATF WS (World Standard) Aisin Toyota Original (1L)', 'oleos', 200.00, null, 'original', 20, 'EXCLUSIVO transmissão Aisin 6 marchas. NÃO SUBSTITUIR. Capacidade: 9,5L'],

    // Óleo Diferencial
    ['08885-81080', 'Óleo Diferencial 80W90 API GL-5 Toyota Original (1L)', 'oleos', 90.00, null, 'original', 20, 'SAE 80W90 API GL-5 hipóide. Diferencial traseiro: 2,6L / Dianteiro: 1,7L'],
    ['80W90-IPIRANGA', 'Óleo Ipiranga 80W90 GL-5 Hipóide (1L)', 'oleos', null, 52.50, 'similar', 20, 'Similar ao original. Economia ~R$ 35,00/L'],
    ['MOBILUBE-80W90', 'Óleo Mobil Mobilube HD 80W90 GL-5 (1L)', 'oleos', null, 57.50, 'similar', 15, 'Similar ao original. Economia ~R$ 30,00/L'],
    ['SPIRAX-80W90', 'Óleo Shell Spirax S3 G 80W90 GL-5 (1L)', 'oleos', null, 62.50, 'similar', 15, 'Similar ao original. Economia ~R$ 25,00/L'],

    // Óleo Caixa Transferência
    ['08885-81081', 'Óleo Transfer Case 75W90 GL-4 Toyota Original (1L)', 'oleos', 100.00, null, 'original', 10, 'SAE 75W90 GL-4/GL-5. Específico 4x4. Capacidade: 1,3L'],
    ['75W90-IPIRANGA', 'Óleo Ipiranga 75W90 GL-4 (1L)', 'oleos', null, 57.50, 'similar', 10, 'Similar ao original. Economia ~R$ 40,00/L'],
    ['MOBILUBE-75W90', 'Óleo Mobil Mobilube 1 SHC 75W90 (1L)', 'oleos', null, 80.00, 'similar', 8, 'Sintético. Alta performance. Economia ~R$ 20,00/L'],

    // Fluido Direção Hidráulica
    ['08886-01206', 'Fluido Direção Hidráulica ATF Dexron III Toyota Original (1L)', 'oleos', 70.00, null, 'original', 12, 'Compatível Dexron II/III. Capacidade: 1,2L'],
    ['DEXRON-VALVOLINE', 'Fluido Valvoline Dexron III/Mercon (1L)', 'oleos', null, 40.00, 'similar', 12, 'Similar ao original. Economia ~R$ 30,00/L'],
    ['ATF220-MOBIL', 'Fluido Mobil ATF 220 Dexron III (1L)', 'oleos', null, 45.00, 'similar', 10, 'Similar ao original. Economia ~R$ 25,00/L'],
    ['DEXRON-IPIRANGA', 'Fluido Ipiranga ATF Dexron III (1L)', 'oleos', null, 35.00, 'similar', 10, 'Similar ao original. Economia ~R$ 35,00/L'],

    // Líquido Arrefecimento
    ['08889-80015', 'Líquido Arrefecimento SLLC Toyota Original -35°C (1L)', 'oleos', 52.50, null, 'original', 20, 'Super Long Life Coolant. Concentrado 50%. Capacidade: 10L. Trocar 100.000 km ou 60 meses'],
    ['GLYSANTIN-G40', 'Líquido Basf Glysantin G40 -35°C (1L)', 'oleos', null, 40.00, 'similar', 20, 'Similar ao SLLC. Economia ~R$ 10,00/L'],
    ['MOBIL-ANTIFREEZE', 'Líquido Mobil Antifreeze Extra -38°C (1L)', 'oleos', null, 35.00, 'similar', 15, 'Similar ao SLLC. Economia ~R$ 15,00/L'],
    ['KOILER-38', 'Líquido Ipiranga Koiler -38°C (1L)', 'oleos', null, 30.00, 'similar', 15, 'Similar ao SLLC. Economia ~R$ 20,00/L'],

    // ============================================
    // FREIOS
    // ============================================
    // Pastilhas Dianteiras
    ['04465-0K270', 'Pastilhas de Freio Dianteiras Toyota Hilux Original (Jogo)', 'freios', 250.00, null, 'original', 6, 'Jogo completo 4 pastilhas. Disco ventilado 296mm. Sistema Sumitomo'],
    ['PD528', 'Pastilhas Frasle PD528 Hilux Dianteiras (Jogo)', 'freios', null, 175.00, 'similar', 6, 'Similar ao original. Economia ~R$ 60,00'],
    ['BB528', 'Pastilhas Bosch BB528 Hilux Dianteiras (Jogo)', 'freios', null, 190.00, 'similar', 5, 'Similar ao original. Economia ~R$ 50,00'],
    ['RCPT09460', 'Pastilhas TRW RCPT09460 Hilux Dianteiras (Jogo)', 'freios', null, 182.50, 'similar', 5, 'Similar ao original. Economia ~R$ 55,00'],

    // Pastilhas Traseiras
    ['04466-60050', 'Pastilhas de Freio Traseiras Toyota Hilux Original (Jogo)', 'freios', 230.00, null, 'original', 6, 'Jogo completo 4 pastilhas. Sistema Sumitomo. Confirmar chassi'],
    ['PD695', 'Pastilhas Frasle PD695 Hilux Traseiras (Jogo)', 'freios', null, 155.00, 'similar', 6, 'Similar ao original. Economia ~R$ 60,00'],
    ['0986BB0973', 'Pastilhas Bosch 0986BB0973 Hilux Traseiras (Jogo)', 'freios', null, 165.00, 'similar', 5, 'Similar ao original. Economia ~R$ 50,00'],
    ['RCPT12020', 'Pastilhas TRW RCPT12020 Hilux Traseiras (Jogo)', 'freios', null, 160.00, 'similar', 5, 'Similar ao original. Economia ~R$ 55,00'],

    // Lonas Freio Traseiras
    ['04495-0K130', 'Lonas de Freio Traseiras Toyota Hilux Original (Jogo)', 'freios', 215.00, null, 'original', 4, 'Sapatas tambor 295mm. Duram 60.000-90.000 km'],
    ['FJ1869', 'Lonas Frasle FJ1869 Hilux Traseiras (Jogo)', 'freios', null, 145.00, 'similar', 4, 'Similar ao original. Economia ~R$ 60,00'],
    ['0986BB0341', 'Lonas Bosch 0986BB0341 Hilux Traseiras (Jogo)', 'freios', null, 155.00, 'similar', 3, 'Similar ao original. Economia ~R$ 50,00'],
    ['RCPT08670', 'Lonas TRW RCPT08670 Hilux Traseiras (Jogo)', 'freios', null, 150.00, 'similar', 3, 'Similar ao original. Economia ~R$ 55,00'],

    // Discos Freio Dianteiros
    ['43512-0K070', 'Discos de Freio Dianteiros Toyota Hilux Original (Par)', 'freios', 500.00, null, 'original', 4, 'Par 2 discos ventilados 296mm. Espessura mínima: 26mm'],
    ['BD5449', 'Discos Fremax BD5449 Hilux Dianteiros (Par)', 'freios', null, 300.00, 'similar', 4, 'Similar ao original. Economia ~R$ 180,00'],
    ['0986479464', 'Discos Bosch 0986479464 Hilux Dianteiros (Par)', 'freios', null, 330.00, 'similar', 3, 'Similar ao original. Economia ~R$ 150,00'],
    ['DF7087', 'Discos TRW DF7087 Hilux Dianteiros (Par)', 'freios', null, 310.00, 'similar', 3, 'Similar ao original. Economia ~R$ 170,00'],

    // ============================================
    // MOTOR
    // ============================================
    // Corrente Distribuição
    ['13506-11010', 'Kit Corrente Distribuição Toyota Hilux 2.8 Original (Kit)', 'motor', 2150.00, null, 'original', 2, 'Kit completo: corrente + tensores + guias. Motor 1GD-FTV'],
    ['35159', 'Kit Corrente Schadeck 35159 Hilux 2.8 (Kit)', 'motor', null, 1300.00, 'similar', 2, 'Kit 12 peças. Similar ao original. Economia ~R$ 800,00'],

    // Velas Aquecimento
    ['19850-0L020', 'Velas de Aquecimento Toyota Hilux 2.8 Original (Jogo 4un)', 'motor', 600.00, null, 'original', 8, 'Jogo 4 velas glow plugs Denso. Trocar sempre as 4 juntas'],
    ['Y-715R', 'Velas NGK Y-715R Hilux 2.8 (Jogo 4un)', 'motor', null, 400.00, 'similar', 8, 'Jogo 4 unidades. Economia ~R$ 200,00'],
    ['0250202096', 'Velas Bosch Duraterm 0250202096 Hilux 2.8 (Jogo 4un)', 'motor', null, 440.00, 'similar', 6, 'Jogo 4 unidades. Economia ~R$ 160,00'],
    ['GV968', 'Velas Beru GV968 Hilux 2.8 (Jogo 4un)', 'motor', null, 420.00, 'similar', 6, 'Jogo 4 unidades. Economia ~R$ 180,00'],

    // Correia Serpentina
    ['16620-59265', 'Kit Correia Serpentina Toyota Hilux 2.8 Original (Kit)', 'motor', 400.00, null, 'original', 4, 'Kit completo: correia poli-V + tensores + rolamentos'],
    ['K060965', 'Kit Correia Gates K060965 Hilux 2.8 (Kit)', 'motor', null, 215.00, 'similar', 4, 'Kit completo. Economia ~R$ 170,00'],
    ['6PK2465', 'Kit Correia Continental 6PK2465 Hilux 2.8 (Kit)', 'motor', null, 240.00, 'similar', 3, 'Kit completo. Economia ~R$ 150,00'],
    ['6PK2465-DAYCO', 'Kit Correia Dayco 6PK2465 Hilux 2.8 (Kit)', 'motor', null, 225.00, 'similar', 3, 'Kit completo. Economia ~R$ 160,00'],

    // Bomba d'Água
    ['16100-59275', 'Bomba d\'Água Toyota Hilux 2.8 Original', 'motor', 1000.00, null, 'original', 2, 'Trocar preventivamente aos 90.000 km. Falha causa superaquecimento'],
    ['NKBA080050', 'Bomba d\'Água Nakata NKBA080050 Hilux 2.8', 'motor', null, 550.00, 'similar', 2, 'Similar ao original. Economia ~R$ 400,00'],
    ['43588', 'Bomba d\'Água Gates 43588 Hilux 2.8', 'motor', null, 600.00, 'similar', 2, 'Similar ao original. Economia ~R$ 350,00'],
    ['UB0931', 'Bomba d\'Água Urba UB0931 Hilux 2.8', 'motor', null, 500.00, 'similar', 2, 'Similar ao original. Economia ~R$ 450,00'],

    // ============================================
    // SUSPENSÃO
    // ============================================
    // Amortecedores
    ['48510-0K260', 'Amortecedor Dianteiro Toyota Hilux Original (Unidade)', 'suspensao', 450.00, null, 'original', 4, 'Vende-se unitário. Trocar aos 100.000 km. Trocar em par'],
    ['48530-0K250', 'Amortecedor Traseiro Toyota Hilux Original (Unidade)', 'suspensao', 450.00, null, 'original', 4, 'Vende-se unitário. Trocar aos 100.000 km. Trocar em par'],
    ['TURBOGAS-DIANT', 'Amortecedor Cofap TurboGas Hilux Dianteiro (Unidade)', 'suspensao', null, 225.00, 'similar', 4, 'Similar ao original. Economia ~R$ 200,00/un'],
    ['TURBOGAS-TRAS', 'Amortecedor Cofap TurboGas Hilux Traseiro (Unidade)', 'suspensao', null, 225.00, 'similar', 4, 'Similar ao original. Economia ~R$ 200,00/un'],
    ['G16531', 'Amortecedor Monroe G16531 Hilux Dianteiro (Unidade)', 'suspensao', null, 250.00, 'similar', 3, 'Similar ao original. Economia ~R$ 175,00/un'],
    ['G16532', 'Amortecedor Monroe G16532 Hilux Traseiro (Unidade)', 'suspensao', null, 250.00, 'similar', 3, 'Similar ao original. Economia ~R$ 175,00/un'],

    // ============================================
    // ELÉTRICA
    // ============================================
    // Bateria
    ['MOURA-75AH-AGM', 'Bateria Moura 75Ah AGM Start/Stop 12V', 'eletrica', 650.00, null, 'original', 3, '12V 75Ah 600A CCA. AGM para start/stop. Trocar aos 60.000 km ou 36 meses'],
    ['HELIAR-75AH', 'Bateria Heliar 75Ah 12V', 'eletrica', null, 500.00, 'similar', 3, '12V 75Ah. Economia ~R$ 150,00'],
    ['BOSCH-S5-75AH', 'Bateria Bosch S5 75Ah 12V', 'eletrica', null, 550.00, 'similar', 2, '12V 75Ah. Economia ~R$ 100,00'],
    ['TUDOR-75AH', 'Bateria Tudor 75Ah 12V', 'eletrica', null, 450.00, 'similar', 2, '12V 75Ah. Economia ~R$ 200,00'],
];

// ===================================
// VERIFICAR MODELO EXISTE
// ===================================
$stmt = $conn->prepare("SELECT id, modelo FROM FF_VehicleModels WHERE modelo LIKE ?");
$modeloBusca = "%HILUX%";
$stmt->bind_param("s", $modeloBusca);
$stmt->execute();
$result = $stmt->get_result();
$modelos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($modelos) === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Nenhum modelo HILUX encontrado no banco'
    ]);
    $conn->close();
    exit();
}

// Pegar primeiro modelo Hilux encontrado
$modeloId = $modelos[0]['id'];
$modeloNome = $modelos[0]['modelo'];

// ===================================
// EXECUTAR IMPORTAÇÃO
// ===================================
$inseridas = 0;
$erros = [];
$duplicadas = 0;

$conn->begin_transaction();

try {
    // Preparar statement
    $stmt = $conn->prepare("
        INSERT INTO FF_Pecas
        (codigo, nome, categoria, custo_estimado, estoque_minimo, tipo, observacoes, modelo_compativel, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($pecas as $peca) {
        list($codigo, $nome, $categoria, $custoOriginal, $custoSimilar, $tipo, $estoqueMin, $obs) = $peca;

        // Usar custo correto baseado no tipo
        $custo = ($tipo === 'original') ? $custoOriginal : $custoSimilar;

        // Verificar se já existe
        $checkStmt = $conn->prepare("SELECT id FROM FF_Pecas WHERE codigo = ?");
        $checkStmt->bind_param("s", $codigo);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $duplicadas++;
            $checkStmt->close();
            continue;
        }
        $checkStmt->close();

        // Inserir
        $stmt->bind_param(
            "sssdisss",
            $codigo,
            $nome,
            $categoria,
            $custo,
            $estoqueMin,
            $tipo,
            $obs,
            $modeloNome
        );

        if ($stmt->execute()) {
            $inseridas++;
        } else {
            $erros[] = "Erro ao inserir '$nome': " . $stmt->error;
        }
    }

    $stmt->close();
    $conn->commit();

    echo json_encode([
        'success' => true,
        'modelo_id' => $modeloId,
        'modelo_nome' => $modeloNome,
        'estatisticas' => [
            'total_pecas_processadas' => count($pecas),
            'pecas_inseridas' => $inseridas,
            'pecas_duplicadas' => $duplicadas,
            'erros' => count($erros)
        ],
        'erros' => $erros
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao importar peças',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
