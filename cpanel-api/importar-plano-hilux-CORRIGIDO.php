<?php
/**
 * Script para importar Plano de Manutenção Toyota HILUX CD (ID 18)
 * CORRIGIDO: Usa o modelo exato do banco
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-hilux-CORRIGIDO.php?confirmar=SIM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verificar confirmação
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Confirmação necessária',
        'message' => 'Para executar, adicione ?confirmar=SIM na URL',
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-hilux-CORRIGIDO.php?confirmar=SIM'
    ], JSON_PRETTY_PRINT);
    exit();
}

// Configuração do banco de dados
require_once 'config-db.php';

// Criar conexão
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

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
    // MODELO CORRETO DO BANCO (ID 18)
    $modeloId = 18;
    $modeloNome = "HILUX CD"; // Nome EXATO do campo modelo

    // PASSO 1: Deletar planos antigos deste modelo (se existirem)
    $stmt = $conn->prepare("DELETE FROM Planos_Manutenção WHERE modelo_carro = ?");
    $stmt->bind_param("s", $modeloNome);
    $stmt->execute();
    $deletados = $stmt->affected_rows;
    $stmt->close();

    // PASSO 2: Definir todos os itens do plano (40+ itens)
    $itens_plano = [
        // 1.000 km
        ['Inspeção Pós-Venda (Revisão de Amaciamento)', 1000, '1', 0.00, 'Média', '[CATEGORIA: Geral] [TEMPO: 45min] Inspeção visual completa: verificação de vazamentos, ruídos anormais, funcionamento de controles, ajuste de portas/capô, calibragem pneus (36 PSI dianteira/33 PSI traseira), nível de fluidos, torque de rodas (140 Nm). Revisão gratuita (cortesia). Verificar aperto correto de componentes montados em fábrica. Essencial para validar garantia.'],

        // 10.000 km
        ['Troca de Óleo Motor + Filtro', 10000, '12', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Substituição de 8L óleo sintético 5W30 ACEA C2/C3 (API SN) específico para diesel com DPF. Troca filtro blindado 3/4-16UNF rosca. Resetar computador de bordo. OBRIGATÓRIO para garantia. Óleo low-SAPS (baixo teor cinzas) para proteger DPF. Nunca use óleo comum. Código filtro: 90915-YZZJ2 ou equivalente Wega JFO0211.'],
        ['Inspeção Sistema Freios', 10000, '12', 80.00, 'Alta', '[CATEGORIA: Freios] [TEMPO: 30min] Verificação visual pastilhas dianteiras (disco ventilado 296mm), lonas traseiras (tambor 295mm), nível fluido DOT 3, vazamentos, desgaste discos, funcionamento freio de mão. Pastilhas dianteiras devem ter mín. 3mm. Trocar se <2mm. Sistema com ABS+EBD+BA.'],
        ['Inspeção Visual Geral do Veículo', 10000, '12', 0.00, 'Média', '[CATEGORIA: Geral] [TEMPO: 25min] Verificar: pneus (calibragem/desgaste/profundidade mín 1,6mm), luzes externas/internas, limpadores, nível líquidos (arrefecimento, direção, lavador), bateria (12V 75Ah bornes limpos), correias, mangueiras. Inspeção preventiva. Calibragem: 36/33 PSI (vazio) ou 36/42 PSI (carregado). Rodízio pneus sugerido.'],
        ['Inspeção Suspensão e Direção', 10000, '12', 0.00, 'Média', '[CATEGORIA: Suspensão] [TEMPO: 20min] Verificar folgas: pivôs, terminais, coifas homocinéticas, batentes, amortecedores (vazamento), buchas bandeja, alinhamento visual. Suspensão dianteira independente duplo A. Traseira rígida com molas parabólicas.'],

        // 20.000 km
        ['Troca de Óleo Motor + Filtro', 20000, '24', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Substituição 8L óleo 5W30 ACEA C2/C3 + filtro blindado. Reset painel. Usar APENAS óleo aprovado Toyota/ACEA C2/C3. Viscosidade inadequada danifica turbo e DPF.'],
        ['Troca de Filtro de Ar do Motor', 20000, '24', 50.00, 'Alta', '[CATEGORIA: Filtros] [TEMPO: 15min] Substituição elemento filtro ar primário. Motor diesel muito sensível a impurezas. Filtro sujo reduz potência e aumenta consumo 8-12%. Código original: 17801-0L050.'],
        ['Troca de Filtro de Combustível', 20000, '24', 120.00, 'Crítica', '[CATEGORIA: Filtros] [TEMPO: 35min] Substituição filtro combustível principal (blindado separador água) + pré-filtro. Drenar água acumulada. Purgar sistema. ESSENCIAL diesel S10. Sistema common rail 2.000 bar pressão. Filtro entupido causa perda potência, falhas injeção, danos bicos injetores. Drenar água semanalmente. Códigos: 23300-0L041/23390-0L070.'],
        ['Inspeção Completa de Freios', 20000, '24', 100.00, 'Alta', '[CATEGORIA: Freios] [TEMPO: 40min] Medição espessura pastilhas/lonas, discos (mín 26mm), tambores, fluido freio, linhas/mangueiras, regulagem freio mão, teste ABS.'],
        ['Inspeção Transmissão e Sistema 4x4', 20000, '24', 90.00, 'Alta', '[CATEGORIA: Transmissão] [TEMPO: 35min] Verificar níveis: óleo transmissão automática ATF WS (Aisin), diferencial traseiro 80W90 GL-5, caixa transferência 75W90 GL-4/5, funcionamento tração 4H/4L.'],
        ['Troca de Filtro Ar-Condicionado', 20000, '24', 60.00, 'Média', '[CATEGORIA: Ar-condicionado] [TEMPO: 20min] Substituição filtro cabine/pólen. Limpeza evaporador (spray específico). Código: 87139-0K030.'],

        // 30.000 km
        ['Troca de Óleo Motor + Filtro', 30000, '36', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Intervalos rigorosos protegem turbo VGT (geometria variável).'],
        ['Troca Completa de Fluido de Freio', 30000, '36', 150.00, 'Crítica', '[CATEGORIA: Freios] [TEMPO: 50min] Substituição completa fluido freio DOT 3 (~1L). Sangria 4 rodas. Fluido higroscópico absorve umidade. Após 2 anos perde 30-40% eficiência.'],
        ['Limpeza Sistema Injeção Diesel', 30000, '36', 280.00, 'Alta', '[CATEGORIA: Motor] [TEMPO: 60min] Limpeza bicos injetores common rail. Motor 1GD-FTV tem 4 injetores piezoelétricos ultra-precisos. Limpeza preventiva evita troca (~R$ 3.500 cada).'],
        ['Geometria e Balanceamento', 30000, '36', 180.00, 'Média', '[CATEGORIA: Pneus] [TEMPO: 50min] Alinhamento 4 rodas. Balanceamento computadorizado.'],

        // 40.000 km
        ['Troca de Óleo Motor + Filtro', 40000, '48', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção rigorosa. Diesel com DPF exige óleo específico.'],
        ['Troca de Óleo Diferencial Traseiro', 40000, '48', 140.00, 'Crítica', '[CATEGORIA: Transmissão] [TEMPO: 40min] Substituição 2,6L SAE 80W90 GL-5. Código: 08885-81080.'],
        ['Troca de Óleo Caixa Transferência 4x4', 40000, '48', 110.00, 'Alta', '[CATEGORIA: Transmissão] [TEMPO: 35min] Substituição óleo transfer case 4x4 (~1,3L SAE 75W90 GL-4).'],
        ['Inspeção Correias de Acessórios', 40000, '48', 80.00, 'Alta', '[CATEGORIA: Motor] [TEMPO: 25min] Verificar tensão, trincas, desgaste correia serpentina poli-V.'],
        ['Verificação Sistema DPF', 40000, '48', 200.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Inspeção filtro partículas diesel: leitura pressão diferencial, histórico regenerações.'],

        // 50.000 km
        ['Troca de Óleo Motor + Filtro', 50000, '60', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção preventiva rigorosa.'],
        ['Troca de Óleo Transmissão Automática', 50000, '60', 420.00, 'Crítica', '[CATEGORIA: Transmissão] [TEMPO: 90min] Substituição ATF WS Aisin (~9,5L). Usar APENAS ATF WS Toyota (08886-02505). ATF errado destrói transmissão.'],
        ['Troca de Fluido Direção Hidráulica', 50000, '60', 130.00, 'Alta', '[CATEGORIA: Direção] [TEMPO: 40min] Substituição ATF Dexron III (~1,2L).'],
        ['Substituição Velas de Aquecimento', 50000, '60', 180.00, 'Alta', '[CATEGORIA: Motor] [TEMPO: 50min] Troca 4 velas aquecimento (glow plugs). Códigos: 19850-0L020.'],

        // 60.000 km
        ['Troca de Óleo Motor + Filtro', 60000, '72', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção preventiva.'],
        ['Revisão Completa de Filtros', 60000, '72', 200.00, 'Crítica', '[CATEGORIA: Filtros] [TEMPO: 60min] Troca: filtro ar motor, filtro combustível primário + secundário, filtro ar-condicionado, filtro óleo.'],
        ['Troca de Pastilhas de Freio Dianteiras', 60000, '72', 200.00, 'Alta', '[CATEGORIA: Freios] [TEMPO: 60min] Substituição jogo pastilhas dianteiras. Código: 04465-0K270.'],
        ['Troca de Lonas de Freio Traseiras', 60000, '72', 180.00, 'Alta', '[CATEGORIA: Freios] [TEMPO: 70min] Substituição lonas tambor traseiro. Código: 04495-0K130.'],
        ['Substituição de Bateria', 60000, '72', 100.00, 'Alta', '[CATEGORIA: Elétrica] [TEMPO: 30min] Troca bateria 12V 75Ah. Bateria dura 2,5-4 anos.'],
        ['Verificação Turbocompressor VGT', 60000, '72', 250.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 50min] Inspeção turbina VGT: folga eixo, vazamento óleo, carbonização.'],

        // 70.000 km
        ['Troca de Óleo Motor + Filtro', 70000, '84', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção rigorosa.'],
        ['Inspeção Completa Sistema Suspensão', 70000, '84', 150.00, 'Alta', '[CATEGORIA: Suspensão] [TEMPO: 50min] Verificar: amortecedores, molas, buchas, pivôs. Trocar em pares.'],
        ['Verificação Cardans 4x4', 70000, '84', 120.00, 'Alta', '[CATEGORIA: Transmissão] [TEMPO: 40min] Inspeção cardans: cruzetas, coifas, balanceamento. Lubrificar a cada 10.000km.'],

        // 80.000 km
        ['Troca de Óleo Motor + Filtro', 80000, '96', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção preventiva.'],
        ['Troca de Óleo Diferencial Traseiro', 80000, '96', 140.00, 'Crítica', '[CATEGORIA: Transmissão] [TEMPO: 40min] Substituição 2,6L SAE 80W90 GL-5.'],
        ['Troca de Óleo Diferencial Dianteiro 4x4', 80000, '96', 130.00, 'Alta', '[CATEGORIA: Transmissão] [TEMPO: 40min] Substituição óleo diferencial dianteiro (~1,7L SAE 80W90 GL-5).'],
        ['Substituição Correia Serpentina', 80000, '96', 180.00, 'Alta', '[CATEGORIA: Motor] [TEMPO: 55min] Troca correia poli-V acessórios + tensores. Código kit: 16620-59265.'],

        // 90.000 km
        ['Troca de Óleo Motor + Filtro', 90000, '108', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção rigorosa.'],
        ['Troca de Fluido de Freio DOT 3', 90000, '108', 150.00, 'Crítica', '[CATEGORIA: Freios] [TEMPO: 50min] Substituição completa. A cada 3 anos ou 60.000km.'],
        ['Substituição Preventiva Bomba Água', 90000, '108', 350.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 120min] Troca bomba água motor. Falha causa superaquecimento. Código: 16100-59275.'],

        // 100.000 km
        ['Troca de Óleo Motor + Filtro', 100000, '120', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção preventiva.'],
        ['Troca de Líquido de Arrefecimento', 100000, '120', 280.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 90min] Substituição completa (~10L SLLC). Código: 08889-80015.'],
        ['Troca de Óleo Transmissão Automática', 100000, '120', 420.00, 'Crítica', '[CATEGORIA: Transmissão] [TEMPO: 90min] Substituição ATF WS (~9,5L) + filtro. 2ª troca crucial.'],
        ['Substituição de Amortecedores', 100000, '120', 300.00, 'Alta', '[CATEGORIA: Suspensão] [TEMPO: 120min] Troca 4 amortecedores. Trocar em pares.'],
        ['Revisão Completa Sistema 4x4', 100000, '120', 200.00, 'Alta', '[CATEGORIA: Transmissão] [TEMPO: 60min] Verificação completa: cubos roda livre, atuadores, sensores.'],

        // 110.000 km
        ['Troca de Óleo Motor + Filtro', 110000, '132', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção preventiva.'],
        ['Inspeção e Teste Bicos Injetores', 110000, '132', 350.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 80min] Teste vazão, pulverização, estanqueidade 4 bicos piezoelétricos.'],

        // 120.000 km
        ['Troca de Óleo Motor + Filtro', 120000, '144', 180.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 40min] Manutenção rigorosa.'],
        ['Troca Pastilhas + Discos Freio Dianteiros', 120000, '144', 280.00, 'Alta', '[CATEGORIA: Freios] [TEMPO: 90min] Substituição pastilhas + discos ventilados.'],
        ['Troca Óleo Diferencial + Transfer', 120000, '144', 280.00, 'Crítica', '[CATEGORIA: Transmissão] [TEMPO: 80min] Manutenção completa tração 4x4.'],

        // 150.000 km
        ['Troca de Óleo Transmissão Automática', 150000, '180', 420.00, 'Crítica', '[CATEGORIA: Transmissão] [TEMPO: 90min] Substituição ATF WS + filtro. 3ª troca.'],
        ['Revisão Completa Motor', 150000, '180', 600.00, 'Crítica', '[CATEGORIA: Motor] [TEMPO: 180min] Inspeção: compressão cilindros, folga válvulas, vazamentos, vedações.'],

        // 200.000 km
        ['Revisão Geral Completa', 200000, '240', 1200.00, 'Crítica', '[CATEGORIA: Geral] [TEMPO: 360min] Revisão extensiva: todos fluidos, filtros, inspeções completas, testes dinâmicos.'],
    ];

    // PASSO 3: Inserir todos os itens
    $stmt = $conn->prepare("
        INSERT INTO Planos_Manutenção (
            modelo_carro,
            descricao_titulo,
            km_recomendado,
            intervalo_tempo,
            custo_estimado,
            criticidade,
            descricao_observacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $inseridos = 0;
    $erros = [];

    foreach ($itens_plano as $item) {
        try {
            $stmt->bind_param(
                "ssissss",
                $modeloNome,
                $item[0], // descricao_titulo
                $item[1], // km_recomendado
                $item[2], // intervalo_tempo
                $item[3], // custo_estimado
                $item[4], // criticidade
                $item[5]  // descricao_observacao
            );

            if ($stmt->execute()) {
                $inseridos++;
            }
        } catch (Exception $e) {
            $erros[] = $item[0] . ": " . $e->getMessage();
        }
    }

    $stmt->close();

    // Resultado final
    echo json_encode([
        'success' => true,
        'message' => '✅ Plano de manutenção importado com sucesso!',
        'modelo' => [
            'id' => $modeloId,
            'marca' => 'Toyota',
            'modelo' => $modeloNome,
            'ano' => '21/21'
        ],
        'estatisticas' => [
            'itens_deletados_antigos' => $deletados,
            'itens_inseridos' => $inseridos,
            'total_itens_plano' => count($itens_plano),
            'erros' => count($erros)
        ],
        'erros_detalhes' => $erros,
        'proximo_passo' => 'Acesse: https://frotas.in9automacao.com.br/planos-manutencao.html?modeloId=18'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao importar plano',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
