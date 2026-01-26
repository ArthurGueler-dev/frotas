<?php
/**
 * Script para importar Plano de Manutenção Chevrolet S10 CD LS 2.8 Diesel
 * Gerado via Perplexity AI em 2026-01-14
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-s10-diesel.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-s10-diesel.php?confirmar=SIM'
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
    // MODELO - AJUSTAR CONFORME BANCO (verificar com verificar-modelos.php)
    $modeloNome = "S10 CD LS 2.8"; // Nome EXATO do campo modelo no banco

    // PASSO 1: Deletar planos antigos deste modelo (se existirem)
    $stmt = $conn->prepare("DELETE FROM Planos_Manutenção WHERE modelo_carro = ?");
    $stmt->bind_param("s", $modeloNome);
    $stmt->execute();
    $deletados = $stmt->affected_rows;
    $stmt->close();

    // PASSO 2: Definir todos os itens do plano de manutenção
    // Formato: [descrição, km, meses, custo_mao_obra, criticidade, observacao]
    $itens_plano = [
        // ==================== REVISÃO 10.000 KM ====================
        [
            'Troca de Óleo e Filtro do Motor',
            10000,
            '12',
            120.00,
            'Crítica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Drenagem completa do óleo lubrificante do motor diesel 2.8 16V Duramax através do bujão do cárter. Substituição do filtro de óleo tipo rosqueável e reabastecimento com óleo sintético especificação GM Dexos 2 SAE 5W-30 API SN/ACEA C2/C3. Capacidade: 6 litros.

**Criticidade:** CRÍTICA - O óleo é responsável pela lubrificação, refrigeração e limpeza interna do motor diesel.

**Consequências de não fazer:** Degradação do óleo lubrificante, acúmulo de resíduos metálicos e carbônicos, desgaste acelerado de bronzinas, pistões e eixo comando de válvulas, podendo causar gripagem e retífica completa do motor (custo superior a R$ 15.000,00).

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
SIMILAR|OX1016D|Mahle|Filtro Óleo S10 2.8 Diesel|1|52.00
SIMILAR|PEL726|Tecfil|Filtro Óleo S10 2.8 Diesel|1|48.00
SIMILAR|CH11724ECO|Fram|Filtro Óleo S10 2.8 Diesel|1|55.00
SIMILAR|WOE314|Wega|Filtro Óleo S10 2.8 Diesel|1|45.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
SIMILAR|HELIX-ULTRA-5W30|Shell|Óleo Helix Ultra Professional 5W-30|6L|310.00
SIMILAR|MOBIL1-ESP-5W30|Mobil|Óleo Mobil 1 ESP 5W-30|6L|340.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Combustível Diesel (Primário e Secundário)',
            10000,
            '12',
            80.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 30 minutos]

Substituição do conjunto de filtros de combustível diesel composto por filtro primário (separador de água) e filtro secundário (elemento fino). O sistema diesel da S10 2.8 utiliza dois estágios de filtragem para proteger os bicos injetores de alta pressão Common Rail. Após a troca, é necessário sangrar o sistema de combustível para eliminar bolhas de ar. Código original GM: 94771044.

**Consequências de não fazer:** Entupimento dos bicos injetores, perda de potência, aumento no consumo de combustível, falha na partida, oxidação do sistema de combustível, necessidade de limpeza ultrassônica dos injetores (R$ 800 a R$ 1.200) ou substituição completa do conjunto (R$ 4.000 a R$ 8.000).

[PECAS]
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
SIMILAR|FCD0777|Wega|Filtro Combustível Diesel S10 2.8|2|108.00
SIMILAR|WK8158|Mann|Filtro Combustível Diesel S10 2.8|2|125.00
SIMILAR|PS10096|Fram|Filtro Combustível Diesel S10 2.8|2|118.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            40.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 15 minutos]

Substituição do elemento filtrante de ar do motor diesel 2.8 16V localizado na caixa de ar próxima ao paralama direito. O filtro retém partículas sólidas (poeira, areia, fuligem) impedindo a entrada no turbocompressor e câmara de combustão. Em motores turbo diesel, o filtro de ar é ainda mais crítico pois protege as pás do turbo que giram a até 150.000 rpm.

**Consequências de não fazer:** Redução de potência e torque, aumento no consumo de combustível em até 15%, desgaste prematuro das pás do turbocompressor, entrada de partículas abrasivas nos cilindros causando riscamento das paredes e anéis.

[PECAS]
ORIGINAL|52102777|Filtro Ar Motor GM S10 2.8 Diesel|1|135.00
SIMILAR|LX3679|Mahle|Filtro Ar Motor S10 2.8 Diesel|1|82.00
SIMILAR|ARL9117|Tecfil|Filtro Ar Motor S10 2.8 Diesel|1|78.00
SIMILAR|CA12442|Fram|Filtro Ar Motor S10 2.8 Diesel|1|85.00
SIMILAR|FAP3679|Wega|Filtro Ar Motor S10 2.8 Diesel|1|75.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            50.00,
            'Média',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 20 minutos]

Substituição do filtro de ar condicionado/cabine localizado atrás do porta-luvas. Este filtro tipo HEPA retém poeira, pólen, bactérias e odores externos. A saturação causa redução do fluxo de ar interno, odor desagradável e proliferação de fungos e bactérias no evaporador. Recomenda-se higienização do sistema de ar condicionado com spray antibacteriano durante a troca.

**Consequências de não fazer:** Mau cheiro na cabine, redução do fluxo de ar interno em até 50%, sobrecarga do motor do ventilador interno (blower), proliferação de fungos e bactérias causando alergias e problemas respiratórios.

[PECAS]
ORIGINAL|52030952|Filtro Ar Condicionado GM S10|1|98.00
SIMILAR|AKX35657|Tecfil|Filtro Ar Condicionado S10|1|52.00
SIMILAR|LA985|Mann|Filtro Ar Condicionado S10|1|58.00
SIMILAR|CF10195|Fram|Filtro Ar Condicionado S10|1|55.00
SIMILAR|WP2141|Wega|Filtro Ar Condicionado S10|1|48.00
[/PECAS]'
        ],

        [
            'Inspeção Geral de Segurança',
            10000,
            '12',
            150.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 60 minutos]

Inspeção visual e funcional completa conforme manual do proprietário GM: verificação de níveis de fluidos (arrefecimento, freio, direção hidráulica, limpador), teste de funcionamento de luzes externas e internas, buzina, limpadores, travas elétricas; inspeção visual de pneus (pressão, desgaste, banda de rodagem mínima 1,6mm), sistema de freios, suspensão, direção, sistema de escapamento, bateria, correias auxiliares.

**Consequências de não fazer:** Não identificação de desgastes e defeitos iniciais que podem evoluir para falhas graves de segurança, acidentes por falta de freio ou estouro de pneu, reprovação em inspeção veicular.

[PECAS]
Não requer peças de substituição obrigatórias (apenas eventuais reposições identificadas na inspeção)
[/PECAS]'
        ],

        // ==================== REVISÃO 20.000 KM ====================
        [
            'Troca de Óleo e Filtro do Motor',
            20000,
            '24',
            120.00,
            'Crítica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Mesmos procedimentos da revisão de 10.000 km. Para uso de óleo sintético Dexos 2 5W-30, o fabricante GM permite intervalos de 20.000 km em uso normal (rodovias, temperatura moderada, baixa carga). Verificar sempre o nível e aparência do óleo entre as trocas.

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
SIMILAR|OX1016D|Mahle|Filtro Óleo S10 2.8 Diesel|1|52.00
SIMILAR|PEL726|Tecfil|Filtro Óleo S10 2.8 Diesel|1|48.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
SIMILAR|HELIX-ULTRA-5W30|Shell|Óleo Helix Ultra Professional 5W-30|6L|310.00
[/PECAS]'
        ],

        [
            'Troca de Filtros de Combustível e Ar',
            20000,
            '24',
            120.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 50 minutos]

Substituição do filtro de combustível diesel (primário e secundário) e filtro de ar do motor conforme especificações da revisão de 10.000 km.

[PECAS]
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
ORIGINAL|52102777|Filtro Ar Motor GM S10 2.8 Diesel|1|135.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
SIMILAR|LX3679|Mahle|Filtro Ar Motor S10 2.8 Diesel|1|82.00
SIMILAR|FCD0777|Wega|Filtro Combustível Diesel S10 2.8|2|108.00
SIMILAR|ARL9117|Tecfil|Filtro Ar Motor S10 2.8 Diesel|1|78.00
[/PECAS]'
        ],

        [
            'Troca de Óleo da Transmissão Manual',
            20000,
            '24',
            100.00,
            'Média',
            '[CATEGORIA: Transmissão] [TEMPO: 40 minutos]

Drenagem e reabastecimento do óleo da transmissão manual de 6 marchas. Utilizar óleo específico para transmissão manual especificação SAE 75W-85 GL-4+ sintético conforme manual GM. Capacidade aproximada: 2,2 litros. O óleo da transmissão lubrifica engrenagens, sincronizadores e rolamentos.

**Consequências de não fazer:** Desgaste prematuro dos sincronizadores causando dificuldade nas trocas de marcha, ruídos anormais (zunido, chiado), engripamento de rolamentos, necessidade de retífica da transmissão (R$ 3.500 a R$ 6.000).

[PECAS]
ORIGINAL|93165414|Óleo Transmissão Manual GM 75W-85|2.2L|165.00
SIMILAR|MTF-75W85|Castrol|Óleo Transmissão Syntrans 75W-85|2.2L|118.00
SIMILAR|GM-SYNTH-75W85|Lubrax|Óleo Transmissão Sintético 75W-85|2.2L|105.00
SIMILAR|GEAR-75W85|Shell|Óleo Spirax S5 ATE 75W-90|2.2L|125.00
[/PECAS]'
        ],

        [
            'Rodízio de Pneus e Verificação de Alinhamento/Balanceamento',
            20000,
            '24',
            180.00,
            'Média',
            '[CATEGORIA: Pneus] [TEMPO: 60 minutos]

Execução de rodízio dos pneus conforme padrão cruz. Verificação de pressão conforme especificação do manual (normalmente 32 PSI dianteiros e 40 PSI traseiros para veículo com carga). Inspeção de desgaste irregular indicando necessidade de alinhamento. Teste de balanceamento em balanceadora eletrônica. Alinhamento 3D e balanceamento com pesos adesivos se necessário.

**Consequências de não fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida útil em até 40%, vibrações no volante e carroceria, perda de estabilidade direcional, aumento no consumo de combustível.

[PECAS]
SIMILAR|PESO-BAL-5G|Universal|Peso de Balanceamento Adesivo|50G|15.00
SIMILAR|PESO-BAL-10G|Universal|Peso de Balanceamento Adesivo|100G|25.00
[/PECAS]'
        ],

        // ==================== REVISÃO 30.000 KM ====================
        [
            'Troca de Óleo e Filtro do Motor',
            30000,
            '36',
            120.00,
            'Crítica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Procedimentos conforme revisões anteriores.

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
SIMILAR|OX1016D|Mahle|Filtro Óleo S10 2.8 Diesel|1|52.00
SIMILAR|PEL726|Tecfil|Filtro Óleo S10 2.8 Diesel|1|48.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
[/PECAS]'
        ],

        [
            'Troca de Filtros Completa (Combustível, Ar Motor, Ar Cabine)',
            30000,
            '36',
            130.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 50 minutos]

Substituição completa de todos os filtros conforme especificações anteriores.

[PECAS]
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
ORIGINAL|52102777|Filtro Ar Motor GM S10 2.8 Diesel|1|135.00
ORIGINAL|52030952|Filtro Ar Condicionado GM S10|1|98.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
SIMILAR|LX3679|Mahle|Filtro Ar Motor S10 2.8 Diesel|1|82.00
SIMILAR|AKX35657|Tecfil|Filtro Ar Condicionado S10|1|52.00
[/PECAS]'
        ],

        [
            'Limpeza do Sistema de Injeção Diesel (Bicos Injetores)',
            30000,
            '36',
            280.00,
            'Média',
            '[CATEGORIA: Motor] [TEMPO: 90 minutos]

Limpeza profissional dos bicos injetores Common Rail por ultrassom ou equipamento de teste/limpeza específico para diesel. O sistema Common Rail trabalha com pressões de até 2.000 bar e os bicos podem acumular depósitos carboníferos que alteram o padrão de pulverização. Teste de vazão e pulverização de cada bico. Aplicação de aditivo limpador de sistema diesel de alta qualidade no tanque.

**Consequências de não fazer:** Perda gradual de potência e torque, aumento no consumo de combustível em até 20%, marcha lenta irregular, fumaça preta excessiva, dificuldade na partida a frio, possível necessidade de substituição dos injetores (R$ 1.000 a R$ 2.000 por unidade).

[PECAS]
SIMILAR|DIESEL-CLEAN-500|Wynn\'s|Aditivo Limpador Sistema Diesel|500ML|85.00
SIMILAR|DPFCL-500|Wynns|Limpador DPF e Injetores Diesel|500ML|95.00
SIMILAR|TECMA-DIESEL|Wurth|Aditivo Diesel System Cleaner|500ML|78.00
[/PECAS]'
        ],

        [
            'Inspeção do Sistema de Freios',
            30000,
            '36',
            100.00,
            'Crítica',
            '[CATEGORIA: Freios] [TEMPO: 45 minutos]

Inspeção detalhada do sistema de freios: medição de espessura das pastilhas dianteiras (mínimo 3mm), lonas traseiras (mínimo 2mm), estado dos discos de freio (espessura, empenamento, sulcos), tambores traseiros, cilindros de roda, tubulações rígidas e flexíveis, nível e aparência do fluido de freio DOT 4. Verificação de folgas e regulagens. Teste funcional em pista.

**Consequências de não fazer:** Falha total ou parcial do sistema de freios causando acidentes graves, desgaste de componentes além do limite causando danos ao disco/tambor, aumento da distância de frenagem.

[PECAS]
Peças substituídas apenas se identificada necessidade na inspeção
[/PECAS]'
        ],

        // ==================== REVISÃO 40.000 KM ====================
        [
            'Troca de Óleo e Filtros Completos',
            40000,
            '48',
            150.00,
            'Crítica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Serviço completo incluindo óleo do motor, filtro de óleo, filtros de combustível, ar do motor e ar condicionado conforme especificações anteriores.

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
ORIGINAL|52102777|Filtro Ar Motor GM S10 2.8 Diesel|1|135.00
ORIGINAL|52030952|Filtro Ar Condicionado GM S10|1|98.00
SIMILAR|OX1016D|Mahle|Filtro Óleo S10 2.8 Diesel|1|52.00
SIMILAR|PEL726|Tecfil|Filtro Óleo S10 2.8 Diesel|1|48.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
SIMILAR|LX3679|Mahle|Filtro Ar Motor S10 2.8 Diesel|1|82.00
SIMILAR|AKX35657|Tecfil|Filtro Ar Condicionado S10|1|52.00
[/PECAS]'
        ],

        [
            'Troca de Óleo do Diferencial (4x2 e 4x4)',
            40000,
            '48',
            120.00,
            'Alta',
            '[CATEGORIA: Transmissão] [TEMPO: 50 minutos]

Drenagem e reabastecimento do óleo do diferencial traseiro (ambas versões 4x2 e 4x4) e diferencial dianteiro (apenas 4x4). Utilizar óleo específico SAE 80W-90 ou 75W-90 GL-5 conforme especificação GM. Capacidade diferencial traseiro: aproximadamente 1,8L; diferencial dianteiro 4x4: aproximadamente 1,5L.

**Consequências de não fazer:** Desgaste prematuro das engrenagens do diferencial (coroa e pinhão), ruídos metálicos (ronco característico), superaquecimento do conjunto, quebra dos dentes da coroa necessitando substituição completa do conjunto (R$ 4.000 a R$ 7.000).

[PECAS]
ORIGINAL|93165096|Óleo Diferencial GM SAE 80W-90|2L|145.00
SIMILAR|DIFF-75W90|Castrol|Óleo Diferencial Axle EPX 80W-90|2L|98.00
SIMILAR|LUBRAX-80W90|Petrobrás|Óleo Diferencial GL-5 80W-90|2L|88.00
SIMILAR|SPIRAX-80W90|Shell|Óleo Spirax S4 G 80W-90|2L|105.00
[/PECAS]'
        ],

        [
            'Substituição de Correias Auxiliares',
            40000,
            '48',
            140.00,
            'Média',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Substituição da correia do alternador e correia do ar condicionado (correias poly-V ou multi-V). Verificação do estado dos tensionadores automáticos, polias lisas e rolamentos. A correia do alternador aciona também a bomba de direção hidráulica.

**Consequências de não fazer:** Rompimento da correia durante a operação causando perda de direção assistida, descarregamento da bateria, perda do ar condicionado, possível superaquecimento do motor.

[PECAS]
ORIGINAL|52084527|Correia Alternador/Direção GM S10 2.8|1|125.00
ORIGINAL|52084528|Correia Ar Condicionado GM S10 2.8|1|115.00
SIMILAR|6PK1840|Gates|Correia Poly-V Alternador S10 2.8|1|68.00
SIMILAR|6PK1520|Continental|Correia Poly-V Ar Cond S10 2.8|1|62.00
SIMILAR|AV13X1840|Dayco|Correia Poly-V Alternador S10 2.8|1|65.00
SIMILAR|AV13X1520|Goodyear|Correia Poly-V Ar Cond S10 2.8|1|58.00
[/PECAS]'
        ],

        [
            'Troca de Fluido de Freio DOT 4',
            40000,
            '48',
            110.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Drenagem completa e substituição do fluido de freio DOT 4 em todo o sistema hidráulico. O fluido de freio é higroscópico (absorve umidade do ar) reduzindo o ponto de ebulição e causando perda de eficiência. Procedimento: sangria de todas as rodas iniciando pela mais distante do cilindro mestre. Capacidade aproximada: 1 litro. Utilizar apenas fluido DOT 4 homologado FMVSS 116.

**Consequências de não fazer:** Fluido contaminado com umidade tem ponto de ebulição reduzido causando vaporização em frenagens intensas (fade), perda total de frenagem, oxidação interna do sistema hidráulico.

[PECAS]
ORIGINAL|93160364|Fluido de Freio DOT 4 GM|1L|55.00
SIMILAR|DOT4-500ML|Bosch|Fluido Freio DOT 4|1L|38.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|1L|42.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|1L|35.00
SIMILAR|DOT4-MOBIL|Mobil|Fluido Freio DOT 4|1L|40.00
[/PECAS]'
        ],

        // ==================== REVISÃO 50.000 KM ====================
        [
            'Troca de Óleo e Filtros Completos',
            50000,
            '60',
            150.00,
            'Crítica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Serviço completo conforme especificações anteriores.

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
ORIGINAL|52102777|Filtro Ar Motor GM S10 2.8 Diesel|1|135.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
SIMILAR|LX3679|Mahle|Filtro Ar Motor S10 2.8 Diesel|1|82.00
[/PECAS]'
        ],

        [
            'Substituição das Velas de Aquecimento (Velas Pré-Aquecedoras)',
            50000,
            '60',
            180.00,
            'Média',
            '[CATEGORIA: Motor] [TEMPO: 90 minutos]

Substituição das 4 velas de aquecimento (glow plugs) do motor diesel 2.8. As velas pré-aquecedoras aquecem a câmara de combustão facilitando a partida a frio do motor diesel. São resistências elétricas que atingem até 1.000°C em poucos segundos. Com o desgaste, aumenta o tempo de partida e emissão de fumaça branca. Teste elétrico de resistência antes da instalação. Torque de aperto: 15 Nm.

**Consequências de não fazer:** Dificuldade crescente para partida a frio, necessidade de múltiplas tentativas de partida, fumaça branca excessiva até aquecimento do motor, desgaste prematuro do motor de partida, bateria, possível quebra da vela dentro do cabeçote exigindo remoção complexa.

[PECAS]
ORIGINAL|55578331|Vela Aquecimento GM S10 2.8 Diesel|4|520.00
SIMILAR|GLP095|Bosch|Vela Aquecimento Duraterm S10 2.8|4|380.00
SIMILAR|DG-182|NGK|Vela Aquecimento Diesel S10 2.8|4|420.00
SIMILAR|GV627|Beru|Vela Aquecimento S10 2.8 Diesel|4|395.00
[/PECAS]'
        ],

        [
            'Revisão Completa do Sistema de Arrefecimento',
            50000,
            '60',
            150.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 120 minutos]

Inspeção completa do sistema de arrefecimento: teste de pressão do radiador (1,2 bar), verificação de vazamentos, estado das mangueiras (superior, inferior, aquecedor), abraçadeiras, válvula termostática (abertura 82°C), bomba d\'água (folgas, ruídos, vazamentos), reservatório de expansão, tampas de pressão. Limpeza externa do radiador e condensador do ar condicionado.

**Consequências de não fazer:** Não detecção de vazamentos iniciais que evoluem para superaquecimento, rompimento de mangueiras em viagem, falha da bomba d\'água, empenamento do cabeçote por superaquecimento (R$ 8.000 a R$ 15.000 de retífica).

[PECAS]
Peças substituídas apenas se identificada necessidade na inspeção
[/PECAS]'
        ],

        [
            'Higienização Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            180.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 90 minutos]

Limpeza profissional do sistema de ar condicionado incluindo: aplicação de espuma higienizadora no evaporador através da caixa de ar, aspiração da espuma e resíduos, aplicação de bactericida/fungicida por nebulização, limpeza do dreno do evaporador. Verificação de pressão do gás refrigerante R-134a, teste de vazamentos, temperatura de saída (deve atingir 5-8°C).

[PECAS]
ORIGINAL|52030952|Filtro Ar Condicionado GM S10|1|98.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado|500ML|65.00
SIMILAR|KLIMACLEAN|Wynn\'s|Limpador Ar Condicionado Automotivo|500ML|72.00
SIMILAR|AKX35657|Tecfil|Filtro Ar Condicionado S10|1|52.00
[/PECAS]'
        ],

        // ==================== REVISÃO 60.000 KM ====================
        [
            'Troca de Óleo e Filtros Completos',
            60000,
            '72',
            150.00,
            'Crítica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Serviço completo conforme especificações anteriores.

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
[/PECAS]'
        ],

        [
            'Substituição de Pastilhas e Discos de Freio Dianteiros',
            60000,
            '72',
            220.00,
            'Crítica',
            '[CATEGORIA: Freios] [TEMPO: 120 minutos]

Substituição do conjunto completo de pastilhas de freio dianteiras (4 peças) e discos ventilados dianteiros (2 peças). Limpeza das pinças, lubrificação dos pinos-guia com graxa específica para altas temperaturas, verificação dos pistões e coifas. Inspeção do desgaste: pastilhas mínimo 3mm, discos mínimo 28mm. Sangria do sistema se necessário. Teste em pista após instalação.

**Consequências de não fazer:** Pastilhas desgastadas até o metal causam sulcos profundos nos discos, perda completa de eficiência de frenagem, ruído metálico intenso, necessidade de substituição prematura dos discos, aumento da distância de frenagem em até 50%, risco elevado de acidentes.

[PECAS]
ORIGINAL|52088959|Jogo Pastilhas Freio Diant GM S10|1|285.00
ORIGINAL|52125487|Par Discos Freio Diant GM S10 2.8|2|820.00
SIMILAR|HI1414|Fras-le|Jogo Pastilhas Freio Diant S10 2.8|1|165.00
SIMILAR|N1404|Cobreq|Jogo Pastilhas Freio Diant S10 2.8|1|158.00
SIMILAR|PD1414|Jurid|Jogo Pastilhas Freio Diant S10 2.8|1|172.00
SIMILAR|BD9859|TRW|Par Discos Freio Ventilado S10 2.8|2|485.00
SIMILAR|DF6859|Fremax|Par Discos Freio Ventilado S10 2.8|2|465.00
SIMILAR|RC3244|Cobreq|Par Discos Freio Ventilado S10 2.8|2|475.00
[/PECAS]'
        ],

        [
            'Troca de Fluido do Sistema de Arrefecimento',
            60000,
            '72',
            140.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 90 minutos]

Drenagem completa e substituição do fluido de arrefecimento (aditivo + água desmineralizada) do motor diesel 2.8. GM recomenda fluido Dex-Cool (aditivo de longa duração cor laranja) diluído 50/50 com água desmineralizada. Capacidade total do sistema: aproximadamente 9 litros da mistura. Procedimento: drenagem pelo bujão do radiador e bloco, lavagem interna com água, reabastecimento da mistura, sangria do sistema.

**Consequências de não fazer:** Fluido contaminado causa corrosão interna do sistema (radiador, bloco, cabeçote, bomba d\'água), formação de borra e depósitos reduzindo eficiência de troca térmica, superaquecimento, danos ao radiador, bomba d\'água e motor.

[PECAS]
ORIGINAL|93302891|Aditivo Radiador Dex-Cool GM|5L|185.00
ORIGINAL|AGUA-DESM|Água Desmineralizada|5L|25.00
SIMILAR|DEXCOOL-5L|Valvoline|Aditivo Radiador Dex-Cool|5L|125.00
SIMILAR|COOLANT-ORG|Repsol|Aditivo Radiador Orgânico|5L|118.00
SIMILAR|PARAFLU-LL|Wurth|Aditivo Radiador Longa Duração|5L|135.00
[/PECAS]'
        ],

        [
            'Troca de Óleo da Transmissão e Diferencial',
            60000,
            '72',
            140.00,
            'Alta',
            '[CATEGORIA: Transmissão] [TEMPO: 60 minutos]

Troca de óleo da transmissão manual e diferencial(is) conforme especificações da revisão de 40.000 km.

[PECAS]
ORIGINAL|93165414|Óleo Transmissão Manual GM 75W-85|2.2L|165.00
ORIGINAL|93165096|Óleo Diferencial GM SAE 80W-90|2L|145.00
SIMILAR|MTF-75W85|Castrol|Óleo Transmissão Syntrans 75W-85|2.2L|118.00
SIMILAR|DIFF-75W90|Castrol|Óleo Diferencial Axle EPX 80W-90|2L|98.00
[/PECAS]'
        ],

        // ==================== REVISÃO 80.000 KM ====================
        [
            'Troca de Óleo e Filtros Completos',
            80000,
            '96',
            150.00,
            'Crítica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Serviço completo conforme especificações anteriores.

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
[/PECAS]'
        ],

        [
            'Substituição de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            280.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 150 minutos]

Substituição das lonas de freio traseiras (sapatas) e tambores de freio traseiros. Revisão completa do sistema: limpeza dos tambores, verificação dos cilindros de roda (vazamentos, pistões travados), molas de retorno, reguladores automáticos. Regulagem do freio de estacionamento. Espessura mínima das lonas: 2mm.

**Consequências de não fazer:** Desgaste das lonas até o rebite causando danos aos tambores, perda de eficiência do freio traseiro sobrecarregando o dianteiro, desbalanceamento da frenagem, freio de estacionamento inoperante.

[PECAS]
ORIGINAL|52102654|Jogo Lonas Freio Traseiro GM S10|1|245.00
ORIGINAL|52125489|Par Tambores Freio Traseiro GM S10|2|650.00
SIMILAR|HI1415|Fras-le|Jogo Lonas Freio Traseiro S10 2.8|1|145.00
SIMILAR|N1415|Cobreq|Jogo Lonas Freio Traseiro S10 2.8|1|138.00
SIMILAR|TT3245|TRW|Par Tambores Freio Traseiro S10 2.8|2|385.00
SIMILAR|RT3245|Fremax|Par Tambores Freio Traseiro S10 2.8|2|365.00
[/PECAS]'
        ],

        [
            'Substituição de Amortecedores Dianteiros e Traseiros',
            80000,
            '96',
            320.00,
            'Alta',
            '[CATEGORIA: Suspensão] [TEMPO: 180 minutos]

Substituição do conjunto completo de 4 amortecedores (2 dianteiros + 2 traseiros) incluindo kits de reparo (coxins, batentes, coifas). Amortecedores desgastados perdem capacidade de amortecimento causando perda de aderência, desconforto e desgaste irregular de pneus. Recomenda-se alinhamento após a troca.

**Consequências de não fazer:** Perda de aderência dos pneus ao solo em irregularidades, aumento da distância de frenagem, perda de estabilidade em curvas, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensão, desconforto aos ocupantes.

[PECAS]
ORIGINAL|52059638|Amortecedor Dianteiro GM S10|2|980.00
ORIGINAL|52059639|Amortecedor Traseiro GM S10|2|920.00
SIMILAR|HG33154|Monroe|Amortecedor Diant S10 2.8 Gas|2|685.00
SIMILAR|HG33155|Monroe|Amortecedor Tras S10 2.8 Gas|2|645.00
SIMILAR|AM33154|Cofap|Amortecedor Diant S10 Turbogas|2|575.00
SIMILAR|AM33155|Cofap|Amortecedor Tras S10 Turbogas|2|545.00
SIMILAR|N33154|Nakata|Amortecedor Diant S10 2.8|2|525.00
SIMILAR|N33155|Nakata|Amortecedor Tras S10 2.8|2|495.00
[/PECAS]'
        ],

        [
            'Troca de Correias Auxiliares',
            80000,
            '96',
            140.00,
            'Média',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Substituição das correias auxiliares conforme especificações da revisão de 40.000 km.

[PECAS]
ORIGINAL|52084527|Correia Alternador/Direção GM S10 2.8|1|125.00
ORIGINAL|52084528|Correia Ar Condicionado GM S10 2.8|1|115.00
SIMILAR|6PK1840|Gates|Correia Poly-V Alternador S10 2.8|1|68.00
SIMILAR|6PK1520|Continental|Correia Poly-V Ar Cond S10 2.8|1|62.00
[/PECAS]'
        ],

        // ==================== REVISÃO 100.000 KM - CRÍTICA ====================
        [
            'Troca de Óleo e Filtros Completos',
            100000,
            '120',
            150.00,
            'Crítica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Serviço completo conforme especificações anteriores.

[PECAS]
ORIGINAL|12636838|Filtro de Óleo Motor GM S10 2.8 Diesel|1|95.00
ORIGINAL|93165213|Óleo Motor GM Dexos 2 5W-30 Sintético|6L|380.00
ORIGINAL|94771044|Filtro Combustível Diesel GM S10 2.8|2|165.00
SIMILAR|DEXS10530|ACDelco|Óleo Dexos 2 5W-30 Sintético|6L|320.00
SIMILAR|PEC3029|Tecfil|Filtro Combustível Diesel S10 2.8|2|112.00
[/PECAS]'
        ],

        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT TENSIONADOR (CRITICO)',
            100000,
            '120',
            1200.00,
            'Crítica',
            '[CATEGORIA: Motor] [TEMPO: 360 minutos]

**ITEM CRÍTICO DE SEGURANÇA DO MOTOR.** Substituição obrigatória da correia dentada de sincronismo (171 dentes), tensor automático, polia tensora e rolamentos. O motor 2.8 Duramax da S10 é do tipo interferente: caso a correia se rompa, os pistões colidem com as válvulas causando danos catastróficos ao motor.

Manual GM recomenda troca aos 240.000 km em uso normal OU 100.000 km em uso severo. **FORTE RECOMENDAÇÃO: SEMPRE usar o critério de uso severo (100.000 km) independente do uso**, pois o clima brasileiro tropical acelera o envelhecimento da borracha.

Procedimento exige ferramentas especiais de sincronismo. Substituir também bomba d\'água preventivamente pois está na mesma região (economia de mão de obra).

**ATENCAO: FALHA CAUSA DANOS CATASTROFICOS AO MOTOR**

**Consequências de não fazer:** Rompimento da correia dentada causa colisão entre pistões e válvulas (motor interferente), empenamento/quebra de válvulas, danos aos pistões e cabeçote, necessidade de retífica completa ou substituição do motor. **CUSTO DE REPARO: R$ 15.000 a R$ 30.000**. Esta é a falha mecânica mais cara que pode ocorrer no veículo.

[PECAS]
ORIGINAL|12625215|Correia Dentada Motor 2.8 GM 171 dentes|1|485.00
ORIGINAL|12646241|Tensor Automático Correia Dentada GM|1|650.00
ORIGINAL|12646242|Polia Tensora Correia Dentada GM|1|320.00
ORIGINAL|12656125|Bomba D\'água Motor 2.8 GM|1|780.00
SIMILAR|CT1126|Gates|Correia Dentada 171 dentes S10 2.8|1|285.00
SIMILAR|TB314|Dayco|Correia Dentada S10 2.8 Diesel|1|295.00
SIMILAR|T43247|Gates|Tensor Automático S10 2.8|1|385.00
SIMILAR|TP43247|Continental|Tensor Correia Dentada S10 2.8|1|375.00
SIMILAR|PA7247|Nakata|Polia Tensora S10 2.8|1|185.00
SIMILAR|WP2828|Nakata|Bomba D\'água S10 2.8 Diesel|1|425.00
SIMILAR|PA880|Urba|Bomba D\'água S10 2.8 Diesel|1|445.00
[/PECAS]'
        ],

        [
            'Substituição da Bateria',
            100000,
            '120',
            50.00,
            'Média',
            '[CATEGORIA: Elétrica] [TEMPO: 30 minutos]

Substituição da bateria automotiva 12V. S10 2.8 Diesel requer bateria de alta capacidade devido ao motor de partida para diesel: especificação mínima 70Ah (ampères-hora) com corrente de partida (CCA) de 600A. Baterias seladas livre de manutenção têm vida útil de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicação de graxa protetora.

[PECAS]
ORIGINAL|52080656|Bateria 12V 70Ah GM|1|685.00
SIMILAR|60GD-700|Moura|Bateria 12V 70Ah 700A Selada|1|485.00
SIMILAR|70D-750|Heliar|Bateria 12V 70Ah 750A Free|1|495.00
SIMILAR|B70DH|Bosch|Bateria 12V 70Ah S5 Free|1|525.00
SIMILAR|70AH-700|Zetta|Bateria 12V 70Ah Selada|1|425.00
[/PECAS]'
        ],

        [
            'Limpeza/Descarbonização do Sistema de Admissão e EGR',
            100000,
            '120',
            350.00,
            'Média',
            '[CATEGORIA: Motor] [TEMPO: 180 minutos]

Limpeza profunda do sistema de admissão, válvula EGR (recirculação de gases) e coletor de admissão. Motores diesel modernos com sistema EGR acumulam depósitos carboníferos no coletor de admissão e válvula EGR reduzindo desempenho. Procedimento: remoção da válvula EGR, limpeza química com produto específico, limpeza do coletor de admissão, limpeza do sensor MAP e MAF. Em casos severos pode ser necessária remoção do coletor.

**Consequências de não fazer:** Perda gradual de potência, aumento no consumo, marcha lenta irregular, luz do motor (check engine) acesa por falha no sistema EGR, travamento da válvula EGR necessitando substituição (R$ 1.200 a R$ 2.000).

[PECAS]
SIMILAR|EGR-CLEAN|Wynn\'s|Limpador Válvula EGR Diesel|400ML|95.00
SIMILAR|CARB-CLEAN|Wurth|Limpador Carburador/Admissão|500ML|68.00
SIMILAR|INTAKE-CLEAN|Bardahl|Limpador Sistema Admissão|300ML|75.00
[/PECAS]'
        ],

        [
            'Troca Completa do Sistema de Embreagem (se transmissão manual)',
            100000,
            '120',
            800.00,
            'Alta',
            '[CATEGORIA: Transmissão] [TEMPO: 480 minutos]

Substituição do kit completo de embreagem: disco de embreagem, platô (placa de pressão), rolamento de acionamento (rolamento piloto) e graxa específica. A embreagem da S10 2.8 Diesel é dimensionada para alto torque (47 kgfm / 470 Nm) e tem desgaste acelerado em uso urbano, reboque e off-road. Verificar estado do volante do motor (superfície de contato), retificar se necessário. Regulagem do curso do pedal após montagem.

**Consequências de não fazer:** Embreagem patinando (perda de tração), dificuldade em subidas, odor de material queimado, ruídos ao acionar o pedal, impossibilidade de engatar marchas, necessidade de guincho.

[PECAS]
ORIGINAL|52285577|Kit Embreagem Completo GM S10 2.8|1|1850.00
SIMILAR|6433|LUK|Kit Embreagem S10 2.8 Diesel|1|1285.00
SIMILAR|CK10395|Sachs|Kit Embreagem S10 2.8 Diesel|1|1325.00
SIMILAR|EMB8010|Valeo|Kit Embreagem S10 2.8 Diesel|1|1295.00
SIMILAR|RK9010|Remax|Kit Embreagem S10 2.8 Diesel|1|985.00
[/PECAS]'
        ],

        // ==================== ITENS POR TEMPO (INDEPENDENTE DE KM) ====================
        [
            'Verificação e Substituição de Pneus',
            60000,
            '60',
            80.00,
            'Crítica',
            '[CATEGORIA: Pneus] [TEMPO: 120 minutos para jogo completo]

S10 CD LS utiliza pneus 255/70 R16. Vida útil média: 40.000 a 60.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidação, ressecamento). Verificar mensalmente: pressão (32 PSI diant / 40 PSI tras com carga), desgaste da banda (mínimo legal 1,6mm), deformações, cortes laterais, data de fabricação (código DOT nas laterais). Realizar rodízio a cada 10.000 km.

**Consequências de não fazer:** Pneus velhos/gastos: aumento de até 40% na distância de frenagem, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves, multa gravíssima (R$ 293,47) e 7 pontos na CNH.

[PECAS]
SIMILAR|255/70R16|Pirelli|Pneu Scorpion ATR 255/70 R16|4|2680.00
SIMILAR|255/70R16|Bridgestone|Pneu Dueler AT 255/70 R16|4|2580.00
SIMILAR|255/70R16|Goodyear|Pneu Wrangler AT 255/70 R16|4|2480.00
SIMILAR|255/70R16|Continental|Pneu CrossContact AT 255/70 R16|4|2380.00
[/PECAS]'
        ]
    ];

    // PASSO 3: Inserir itens na tabela Planos_Manutenção
    $stmt = $conn->prepare("
        INSERT INTO Planos_Manutenção
        (modelo_carro, descricao_titulo, km_recomendado, intervalo_tempo, custo_estimado, criticidade, descricao_observacao)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $inseridos = 0;
    foreach ($itens_plano as $item) {
        $descricao = $item[0];
        $km = $item[1];
        $meses = $item[2];
        $custo = $item[3];
        $criticidade = $item[4];
        $observacao = $item[5];

        $stmt->bind_param("ssissss", $modeloNome, $descricao, $km, $meses, $custo, $criticidade, $observacao);
        $stmt->execute();
        $inseridos++;
    }
    $stmt->close();

    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'modelo' => $modeloNome,
        'planos_deletados' => $deletados,
        'planos_inseridos' => $inseridos,
        'message' => "Plano de manutenção para {$modeloNome} importado com sucesso!",
        'proximo_passo' => 'Verificar em https://frotas.in9automacao.com.br/planos-manutencao-novo.html'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao importar plano de manutenção',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
