<?php
/**
 * Script para importar Plano de Manutencao Mitsubishi L200 Triton 2.4 Diesel
 * Gerado via Perplexity AI em 2026-01-14
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-l200-diesel.php?confirmar=SIM
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verificar confirmacao
if (!isset($_GET['confirmar']) || $_GET['confirmar'] !== 'SIM') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Confirmacao necessaria',
        'message' => 'Para executar, adicione ?confirmar=SIM na URL',
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-l200-diesel.php?confirmar=SIM'
    ], JSON_PRETTY_PRINT);
    exit();
}

// Configuracao do banco de dados
require_once 'config-db.php';

// Criar conexao
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
        'error' => 'Erro de conexao com o banco de dados',
        'details' => $e->getMessage()
    ]);
    exit();
}

try {
    // MODELO - AJUSTAR CONFORME BANCO (verificar com verificar-modelos.php)
    $modeloNome = "L200 TRITON"; // Nome EXATO do campo modelo no banco

    // PASSO 1: Deletar planos antigos deste modelo (se existirem)
    $stmt = $conn->prepare("DELETE FROM Planos_Manutenção WHERE modelo_carro = ?");
    $stmt->bind_param("s", $modeloNome);
    $stmt->execute();
    $deletados = $stmt->affected_rows;
    $stmt->close();

    // PASSO 2: Definir todos os itens do plano de manutencao
    // Formato: [descricao, km, meses, custo_mao_obra, criticidade, observacao]
    $itens_plano = [
        // ==================== REVISAO 10.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            10000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Drenagem completa do oleo lubrificante do motor diesel 2.4 MIVEC Turbo (4N15) atraves do bujao do carter. Substituicao do filtro de oleo tipo rosqueavel e reabastecimento com oleo sintetico especificacao 5W-30 ACEA C3 API CJ-4. Capacidade: 7,3 litros com filtro.

**Criticidade:** CRITICA - O oleo e responsavel pela lubrificacao, refrigeracao e limpeza interna do motor diesel turbo.

**Consequencias de nao fazer:** Degradacao do oleo lubrificante, acumulo de residuos metalicos e carbonicos, desgaste acelerado de bronzinas, pistoes e eixo comando de valvulas, podendo causar gripagem e retifica completa do motor (custo superior a R$ 20.000,00).

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|W713/28|Mann|Filtro Oleo L200 2.4 Diesel|1|33.00
SIMILAR|OC47|Mahle|Filtro Oleo L200 2.4 Diesel|1|31.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
SIMILAR|DELVAC1-5W30|Mobil|Oleo Delvac 1 LE 5W-30|7.3L|423.40
SIMILAR|EDGE-5W30|Castrol|Oleo Edge 5W-30 Diesel|7.3L|401.50
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            40.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 15 minutos]

Substituicao do elemento filtrante de ar do motor diesel 2.4 16V localizado na caixa de ar. O filtro retém particulas solidas (poeira, areia, fuligem) impedindo a entrada no turbocompressor e camara de combustao. Em motores turbo diesel, o filtro de ar e ainda mais critico pois protege as pas do turbo que giram a ate 150.000 rpm.

**ATENCAO ESPECIAL L200:** Filtro de ar sujo e causa conhecida de falha do turbo neste modelo. O turbo da L200 2.4 e sensivel a contaminacao.

**Consequencias de nao fazer:** Reducao de potencia e torque, aumento no consumo de combustivel em ate 15%, desgaste prematuro das pas do turbocompressor, entrada de particulas abrasivas nos cilindros causando riscamento das paredes e aneis, FALHA PREMATURA DO TURBO.

[PECAS]
ORIGINAL|1500A023|Filtro de Ar Motor Original Mitsubishi|1|82.00
SIMILAR|ARL9472|Tecfil|Filtro Ar Motor L200 2.4 Diesel|1|46.00
SIMILAR|C27009|Mann|Filtro Ar Motor L200 2.4 Diesel|1|53.00
SIMILAR|LX3580|Mahle|Filtro Ar Motor L200 2.4 Diesel|1|49.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            50.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 20 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas. Este filtro retém poeira, polen, bacterias e odores externos. A saturacao causa reducao do fluxo de ar interno, odor desagradavel e proliferacao de fungos e bacterias no evaporador. Recomenda-se filtro com carvao ativado.

**Consequencias de nao fazer:** Mau cheiro na cabine, reducao do fluxo de ar interno em ate 50%, sobrecarga do motor do ventilador interno (blower), proliferacao de fungos e bacterias causando alergias e problemas respiratorios.

[PECAS]
ORIGINAL|7803A004|Filtro Ar Condicionado Original Mitsubishi|1|65.00
SIMILAR|ACP194|Tecfil|Filtro Ar Condicionado L200|1|33.00
SIMILAR|CU22020|Mann|Filtro Ar Condicionado L200|1|40.00
SIMILAR|0986BF0688|Bosch|Filtro Ar Condicionado L200|1|37.00
[/PECAS]'
        ],

        [
            'Lubrificacao de Chassis e Cruzetas do Cardan',
            10000,
            '12',
            80.00,
            'Media',
            '[CATEGORIA: Transmissao] [TEMPO: 45 minutos]

Lubrificacao de todos os pontos graxeiros do chassis (14-16 pontos), incluindo cruzetas do eixo cardan, articulacoes da suspensao, terminais de direcao e pontos de graxa. Utilizar graxa especificacao NLGI 2 Litio EP (extrema pressao).

**Consequencias de nao fazer:** Desgaste prematuro das cruzetas do cardan (item caro de substituir), ruidos nas articulacoes, travamento de componentes, perda de mobilidade da suspensao.

[PECAS]
ORIGINAL|MZ101164|Graxa NLGI 2 Litio EP Mitsubishi|1kg|27.00
SIMILAR|S2V220|Shell|Graxa Gadus S2 V220|1kg|18.00
SIMILAR|MOBILUX-EP2|Mobil|Graxa Mobilux EP2|1kg|20.00
SIMILAR|MULTIFAK-EP2|Texaco|Graxa Multifak EP2|1kg|17.00
[/PECAS]'
        ],

        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            150.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 60 minutos]

Inspecao visual e funcional completa conforme manual do proprietario Mitsubishi: verificacao de niveis de fluidos (arrefecimento, freio, embreagem), teste de funcionamento de luzes externas e internas, buzina, limpadores, travas eletricas; inspecao visual de pneus (pressao, desgaste, banda de rodagem minima 1,6mm), sistema de freios, suspensao, direcao eletrica, sistema de escapamento, bateria, correias auxiliares.

**Consequencias de nao fazer:** Nao identificacao de desgastes e defeitos iniciais que podem evoluir para falhas graves de seguranca, acidentes por falta de freio ou estouro de pneu, reprovacao em inspecao veicular.

[PECAS]
Nao requer pecas de substituicao obrigatorias (apenas eventuais reposicoes identificadas na inspecao)
[/PECAS]'
        ],

        // ==================== REVISAO 20.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            20000,
            '24',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Mesmos procedimentos da revisao de 10.000 km. Para uso de oleo sintetico 5W-30 ACEA C3, o fabricante permite intervalos de ate 15.000 km em uso normal. Verificar sempre o nivel e aparencia do oleo entre as trocas.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
SIMILAR|DELVAC1-5W30|Mobil|Oleo Delvac 1 LE 5W-30|7.3L|423.40
[/PECAS]'
        ],

        [
            'Troca de Filtros (Ar Motor e Ar Condicionado)',
            20000,
            '24',
            90.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 35 minutos]

Substituicao do filtro de ar do motor e filtro de ar condicionado conforme especificacoes da revisao de 10.000 km.

[PECAS]
ORIGINAL|1500A023|Filtro de Ar Motor Original Mitsubishi|1|82.00
ORIGINAL|7803A004|Filtro Ar Condicionado Original Mitsubishi|1|65.00
SIMILAR|ARL9472|Tecfil|Filtro Ar Motor L200 2.4 Diesel|1|46.00
SIMILAR|ACP194|Tecfil|Filtro Ar Condicionado L200|1|33.00
[/PECAS]'
        ],

        [
            'Rodizio de Pneus e Verificacao de Alinhamento/Balanceamento',
            20000,
            '24',
            180.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 60 minutos]

Execucao de rodizio dos pneus conforme padrao cruz. Verificacao de pressao conforme especificacao do manual (normalmente 32 PSI dianteiros e 36 PSI traseiros). Inspecao de desgaste irregular indicando necessidade de alinhamento. Teste de balanceamento em balanceadora eletronica. Alinhamento 3D e balanceamento com pesos adesivos se necessario.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 40%, vibracoes no volante e carroceria, perda de estabilidade direcional, aumento no consumo de combustivel.

[PECAS]
SIMILAR|PESO-BAL-5G|Universal|Peso de Balanceamento Adesivo|50g|15.00
SIMILAR|PESO-BAL-10G|Universal|Peso de Balanceamento Adesivo|100g|25.00
[/PECAS]'
        ],

        // ==================== REVISAO 30.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            30000,
            '36',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Procedimentos conforme revisoes anteriores.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
[/PECAS]'
        ],

        [
            'Inspecao do Sistema de Freios',
            30000,
            '36',
            100.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 45 minutos]

Inspecao detalhada do sistema de freios a disco nas 4 rodas: medicao de espessura das pastilhas dianteiras (minimo 3mm), pastilhas traseiras (minimo 2mm), estado dos discos de freio (espessura, empenamento, sulcos), tubulacoes rigidas e flexiveis, nivel e aparencia do fluido de freio DOT 4. Verificacao de folgas e regulagens. Teste funcional em pista.

**Consequencias de nao fazer:** Falha total ou parcial do sistema de freios causando acidentes graves, desgaste de componentes alem do limite causando danos aos discos, aumento da distancia de frenagem.

[PECAS]
Pecas substituidas apenas se identificada necessidade na inspecao
[/PECAS]'
        ],

        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            30000,
            '36',
            150.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Substituicao do jogo completo de pastilhas de freio dianteiras. Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas, verificacao dos pistoes e coifas.

[PECAS]
ORIGINAL|4605A569|Jogo Pastilhas Dianteiras Original Mitsubishi|1|275.00
SIMILAR|GDB3488|TRW|Jogo Pastilhas Freio Diant L200|1|162.00
SIMILAR|PD1688|Frasle|Jogo Pastilhas Freio Diant L200|1|170.00
SIMILAR|N1422|Cobreq|Jogo Pastilhas Freio Diant L200|1|155.00
[/PECAS]'
        ],

        // ==================== REVISAO 40.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            40000,
            '48',
            150.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Servico completo incluindo oleo do motor, filtro de oleo, filtros de combustivel, ar do motor e ar condicionado.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
ORIGINAL|1770A052|Filtro de Combustivel Diesel Original|1|125.00
ORIGINAL|1770A053|Pre-Filtro Separador de Agua Original|1|92.00
ORIGINAL|1500A023|Filtro de Ar Motor Original Mitsubishi|1|82.00
ORIGINAL|7803A004|Filtro Ar Condicionado Original|1|65.00
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
SIMILAR|PSC965|Tecfil|Filtro Combustivel Diesel L200|1|66.00
SIMILAR|PSC638|Tecfil|Pre-Filtro Separador Agua L200|1|50.00
SIMILAR|ARL9472|Tecfil|Filtro Ar Motor L200 2.4 Diesel|1|46.00
SIMILAR|ACP194|Tecfil|Filtro Ar Condicionado L200|1|33.00
[/PECAS]'
        ],

        [
            'Troca de Oleo do Diferencial Traseiro',
            40000,
            '48',
            100.00,
            'Alta',
            '[CATEGORIA: Transmissao] [TEMPO: 40 minutos]

Drenagem e reabastecimento do oleo do diferencial traseiro 4x4. Utilizar oleo especifico 75W-90 GL-5 LSD conforme especificacao Mitsubishi. Capacidade: 2,7 litros.

**Consequencias de nao fazer:** Desgaste prematuro das engrenagens do diferencial (coroa e pinhao), ruidos metalicos (ronco caracteristico), superaquecimento do conjunto, quebra dos dentes da coroa necessitando substituicao completa do conjunto (R$ 5.000 a R$ 8.000).

[PECAS]
ORIGINAL|MZ320356|Oleo Diferencial 75W-90 GL-5 LSD Mitsubishi|2.7L|189.00
SIMILAR|S5ATE-75W90|Shell|Oleo Spirax S5 ATE 75W-90|2.7L|129.60
SIMILAR|DELVAC-75W90|Mobil|Oleo Delvac SYN 75W-90|2.7L|140.40
SIMILAR|MAXGEAR-75W90|Ipiranga|Oleo Maxgear 75W-90|2.7L|121.50
[/PECAS]'
        ],

        [
            'Troca de Oleo dos Cubos de Roda (4x4)',
            40000,
            '48',
            80.00,
            'Media',
            '[CATEGORIA: Transmissao] [TEMPO: 45 minutos]

Drenagem e reabastecimento do oleo dos cubos de roda dianteiros (sistema 4x4). Verificar retentores por vazamentos. Utilizar oleo 75W-90 GL-5.

[PECAS]
ORIGINAL|MZ320357|Oleo Cubo de Roda 75W-90 GL-5|0.75L|67.00
SIMILAR|SPIRAX-75W90|Shell|Oleo Spirax 75W-90|0.75L|45.00
SIMILAR|GEAR-75W90|Mobil|Oleo Gear 75W-90|0.75L|48.00
SIMILAR|TOPGEAR-75W90|Lubrax|Oleo Top Gear 75W-90|0.75L|42.00
[/PECAS]'
        ],

        [
            'Substituicao de Correia Poly-V (Acessorios)',
            40000,
            '48',
            100.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 50 minutos]

Substituicao da correia do alternador/ar condicionado (correia poly-V 6PK2095). Verificacao do estado do tensionador automatico, polias lisas e rolamentos.

**Consequencias de nao fazer:** Rompimento da correia durante a operacao causando descarregamento da bateria, perda do ar condicionado, possivel superaquecimento do motor.

[PECAS]
ORIGINAL|1345A065|Correia Alternador/AC 6PK2095 Original|1|142.00
SIMILAR|K060955|Gates|Correia Poly-V L200 2.4|1|82.00
SIMILAR|6PK2095|Dayco|Correia Poly-V L200 2.4|1|88.00
SIMILAR|6PK2095-CONT|Continental|Correia Poly-V L200 2.4|1|85.00
[/PECAS]'
        ],

        [
            'Substituicao do Tensor de Correia',
            40000,
            '48',
            80.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 30 minutos]

Substituicao do tensor automatico da correia poly-v. Verificar rolamento antes da instalacao.

[PECAS]
ORIGINAL|1345A066|Tensor Correia Original Mitsubishi|1|348.00
SIMILAR|T38632|Gates|Tensor Correia L200 2.4|1|205.00
SIMILAR|APV2830|Dayco|Tensor Correia L200 2.4|1|222.00
SIMILAR|VKM38632|SKF|Tensor Correia L200 2.4|1|212.00
[/PECAS]'
        ],

        [
            'Troca de Fluido de Freio DOT 4',
            40000,
            '24',
            110.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico. O fluido de freio e higroscopico (absorve umidade do ar) reduzindo o ponto de ebulicao e causando perda de eficiencia. Procedimento: sangria de todas as rodas iniciando pela mais distante do cilindro mestre. Capacidade aproximada: 0,85 litros.

**Consequencias de nao fazer:** Fluido contaminado com umidade tem ponto de ebulicao reduzido causando vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico.

[PECAS]
ORIGINAL|MZ310444|Fluido de Freio DOT 4 Mitsubishi|1L|36.00
SIMILAR|DOT4-FRASLE|Fras-le|Fluido Freio DOT 4|1L|22.00
SIMILAR|PFB440|TRW|Fluido Freio DOT 4|1L|25.00
SIMILAR|DOT4-ESI|Bosch|Fluido Freio DOT 4 ESI|1L|24.00
[/PECAS]'
        ],

        [
            'Troca de Liquido de Arrefecimento',
            40000,
            '24',
            120.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 90 minutos]

Drenagem completa e substituicao do liquido de arrefecimento. Utilizar aditivo Long Life -37C concentrado diluido 50/50 com agua desmineralizada. Capacidade do sistema: 9,5 litros (diluido).

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do sistema (radiador, bloco, cabecote, bomba d agua), formacao de borra e depositos reduzindo eficiencia de troca termica, superaquecimento, danos ao radiador, bomba d agua e motor.

[PECAS]
ORIGINAL|MZ320291|Aditivo Arrefecimento Long Life Mitsubishi|4.75L|218.50
SIMILAR|TURBO-DIESEL-LL|Rads|Aditivo Arrefecimento Turbo Diesel LL|4.75L|133.00
SIMILAR|LONGLIFE-37|Wurth|Aditivo Arrefecimento Long Life -37C|4.75L|152.00
SIMILAR|EXTENDED-LIFE|Prestone|Aditivo Extended Life|4.75L|142.50
[/PECAS]'
        ],

        [
            'Substituicao das Buchas de Suspensao',
            40000,
            '48',
            180.00,
            'Media',
            '[CATEGORIA: Suspensao] [TEMPO: 120 minutos]

Substituicao das buchas da barra estabilizadora e buchas dos braceos da suspensao. A borracha deteriora com o tempo e causa folgas e ruidos.

[PECAS]
ORIGINAL|MR418048|Buchas Barra Estabilizadora Original (par)|1|65.00
SIMILAR|NJB1026|Nakata|Buchas Barra Estabilizadora L200 (par)|1|37.00
SIMILAR|BU120|Cofap|Buchas Barra Estabilizadora L200 (par)|1|40.00
SIMILAR|JBU1026|TRW|Buchas Barra Estabilizadora L200 (par)|1|39.00
[/PECAS]'
        ],

        // ==================== REVISAO 50.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            50000,
            '60',
            150.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
ORIGINAL|1770A052|Filtro de Combustivel Diesel Original|1|125.00
ORIGINAL|1500A023|Filtro de Ar Motor Original Mitsubishi|1|82.00
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
SIMILAR|PSC965|Tecfil|Filtro Combustivel Diesel L200|1|66.00
SIMILAR|ARL9472|Tecfil|Filtro Ar Motor L200 2.4 Diesel|1|46.00
[/PECAS]'
        ],

        [
            'Revisao Completa do Sistema de Arrefecimento',
            50000,
            '60',
            150.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 120 minutos]

Inspecao completa do sistema de arrefecimento: teste de pressao do radiador (1,2 bar), verificacao de vazamentos, estado das mangueiras (superior, inferior, aquecedor), abracadeiras, valvula termostatica (abertura 82C), bomba d agua (folgas, ruidos, vazamentos), reservatorio de expansao, tampas de pressao. Limpeza externa do radiador e condensador do ar condicionado.

**Consequencias de nao fazer:** Nao deteccao de vazamentos iniciais que evoluem para superaquecimento, rompimento de mangueiras em viagem, falha da bomba d agua, empenamento do cabecote por superaquecimento (R$ 10.000 a R$ 20.000 de retifica).

[PECAS]
Pecas substituidas apenas se identificada necessidade na inspecao
[/PECAS]'
        ],

        [
            'Substituicao de Pastilhas de Freio Traseiras',
            50000,
            '60',
            140.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Substituicao do jogo completo de pastilhas de freio traseiras. L200 possui freio a disco nas 4 rodas.

[PECAS]
ORIGINAL|4605A570|Jogo Pastilhas Traseiras Original Mitsubishi|1|238.00
SIMILAR|GDB3489|TRW|Jogo Pastilhas Freio Tras L200|1|142.00
SIMILAR|PD1689|Frasle|Jogo Pastilhas Freio Tras L200|1|150.00
SIMILAR|N1423|Cobreq|Jogo Pastilhas Freio Tras L200|1|138.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM - CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '72',
            150.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
ORIGINAL|1770A052|Filtro de Combustivel Diesel Original|1|125.00
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
SIMILAR|PSC965|Tecfil|Filtro Combustivel Diesel L200|1|66.00
[/PECAS]'
        ],

        [
            'Substituicao das Cruzetas do Cardan',
            60000,
            '72',
            200.00,
            'Alta',
            '[CATEGORIA: Transmissao] [TEMPO: 120 minutos]

Substituicao das 4 cruzetas do eixo cardan. As cruzetas transmitem o torque do motor para o diferencial e sofrem desgaste significativo, especialmente em uso off-road.

**Consequencias de nao fazer:** Vibracoes na transmissao, ruidos metalicos (estalidos), quebra da cruzeta durante a marcha causando perda de tracao e possivel danos ao cambio e diferencial.

[PECAS]
ORIGINAL|2503A025|Cruzeta Cardan Original Mitsubishi|4|712.00
SIMILAR|SPL90|Spicer|Cruzeta Cardan L200|4|472.00
SIMILAR|5-3006X|GWB|Cruzeta Cardan L200|4|500.00
SIMILAR|NCJ1025|Nakata|Cruzeta Cardan L200|4|460.00
[/PECAS]'
        ],

        [
            'Troca de Oleo do Cambio Automatico (se aplicavel)',
            60000,
            '72',
            200.00,
            'Alta',
            '[CATEGORIA: Transmissao] [TEMPO: 90 minutos]

Para versoes com cambio automatico: drenagem e reabastecimento do fluido ATF SP-IV. Capacidade: 8,2 litros. Para cambio manual: verificar nivel e condicao do oleo 75W-85 GL-4.

[PECAS]
ORIGINAL|MZ320289|Fluido ATF SP-IV Mitsubishi|8.2L|672.40
SIMILAR|S4ATF|Shell|Fluido Spirax S4 ATF|8.2L|459.20
SIMILAR|ATF320|Mobil|Fluido ATF 320|8.2L|508.40
SIMILAR|TRANSMAX-Z|Castrol|Fluido Transmax Z|8.2L|475.60
[/PECAS]'
        ],

        [
            'Substituicao de Discos de Freio Dianteiros',
            60000,
            '72',
            180.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 90 minutos]

Substituicao dos discos de freio dianteiros ventilados. Verificar espessura minima e empenamento antes de decidir pela troca.

[PECAS]
ORIGINAL|4615A143|Par Discos Freio Dianteiro Original|2|655.00
SIMILAR|BD9622|Fremax|Par Discos Freio Dianteiro L200|2|408.00
SIMILAR|DF6888S|TRW|Par Discos Freio Dianteiro L200|2|438.00
SIMILAR|0986479588|Bosch|Par Discos Freio Dianteiro L200|2|425.00
[/PECAS]'
        ],

        [
            'Substituicao de Amortecedores Dianteiros',
            60000,
            '72',
            280.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 150 minutos]

Substituicao dos amortecedores dianteiros em par. Verificar estado das molas e batentes de suspensao.

[PECAS]
ORIGINAL|MR992869|Par Amortecedores Dianteiros Original|2|1225.00
SIMILAR|G16544|Monroe|Par Amortecedores Dianteiros L200|2|748.00
SIMILAR|TF20042|Cofap|Par Amortecedores Dianteiros L200|2|708.00
SIMILAR|341482|KYB|Par Amortecedores Dianteiros L200|2|775.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            80000,
            '96',
            150.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
ORIGINAL|1770A052|Filtro de Combustivel Diesel Original|1|125.00
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
[/PECAS]'
        ],

        [
            'Substituicao de Amortecedores Traseiros',
            80000,
            '96',
            240.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 120 minutos]

Substituicao dos amortecedores traseiros em par. Verificar estado das molas e batentes de suspensao.

[PECAS]
ORIGINAL|MR992870|Par Amortecedores Traseiros Original|2|1025.00
SIMILAR|G16545|Monroe|Par Amortecedores Traseiros L200|2|625.00
SIMILAR|TF20043|Cofap|Par Amortecedores Traseiros L200|2|595.00
SIMILAR|341483|KYB|Par Amortecedores Traseiros L200|2|645.00
[/PECAS]'
        ],

        [
            'Substituicao de Terminais de Direcao',
            80000,
            '96',
            160.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 90 minutos]

Substituicao dos terminais de direcao externos e axiais. Fazer alinhamento apos a troca. Total de 4 terminais.

[PECAS]
ORIGINAL|MR992866|Par Terminais Direcao Externos Original|2|275.00
SIMILAR|JTE1118|TRW|Par Terminais Direcao L200|2|162.00
SIMILAR|NTD1118|Nakata|Par Terminais Direcao L200|2|170.00
SIMILAR|VB511118|Viebrake|Par Terminais Direcao L200|2|155.00
[/PECAS]'
        ],

        [
            'Substituicao de Pivos de Direcao',
            80000,
            '96',
            200.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 120 minutos]

Substituicao dos pivos de suspensao. Folga nos pivos e critica para a seguranca do veiculo.

[PECAS]
ORIGINAL|MN101482|Pivo de Suspensao Original|2|930.00
SIMILAR|NPV1026|Nakata|Pivo de Suspensao L200|2|556.00
SIMILAR|CP3725|Cofap|Pivo de Suspensao L200|2|590.00
SIMILAR|JPV1026|TRW|Pivo de Suspensao L200|2|570.00
[/PECAS]'
        ],

        [
            'Kit de Embreagem (se aplicavel - cambio manual)',
            80000,
            '96',
            600.00,
            'Alta',
            '[CATEGORIA: Transmissao] [TEMPO: 360 minutos]

Substituicao do kit completo de embreagem: disco de embreagem, plato (placa de pressao), rolamento de acionamento. A embreagem da L200 2.4 Diesel e dimensionada para alto torque e tem desgaste acelerado em uso urbano, reboque e off-road. Verificar estado do volante do motor.

[PECAS]
ORIGINAL|MBR5295|Kit Embreagem Completo Mitsubishi|1|1725.00
SIMILAR|632305409|LUK|Kit Embreagem L200 2.4 Diesel|1|1195.00
SIMILAR|3000970095|Sachs|Kit Embreagem L200 2.4 Diesel|1|1245.00
SIMILAR|832522|Valeo|Kit Embreagem L200 2.4 Diesel|1|1215.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM - SUPER CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            100000,
            '120',
            150.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
ORIGINAL|1770A052|Filtro de Combustivel Diesel Original|1|125.00
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
SIMILAR|PSC965|Tecfil|Filtro Combustivel Diesel L200|1|66.00
[/PECAS]'
        ],

        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT COMPLETO (CRITICO)',
            100000,
            '120',
            1200.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 360 minutos]

**ITEM CRITICO DE SEGURANCA DO MOTOR.** Substituicao obrigatoria da correia dentada de sincronismo, tensor automatico, polia tensora, bomba d agua e rolamentos. O motor 4N15 2.4 MIVEC Diesel da L200 e do tipo INTERFERENTE: caso a correia se rompa, os pistoes colidem com as valvulas causando danos catastroficos ao motor.

**ATENCAO ESPECIAL L200:** A L200 2.4 tem historico de problemas com eixo balanceador. Verificar estado durante a troca da correia dentada.

**ATENCAO: FALHA CAUSA DANOS CATASTROFICOS AO MOTOR**

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e valvulas (motor interferente), empenamento/quebra de valvulas, danos aos pistoes e cabecote, necessidade de retifica completa ou substituicao do motor. **CUSTO DE REPARO: R$ 20.000 a R$ 40.000**. Esta e a falha mecanica mais cara que pode ocorrer no veiculo.

[PECAS]
ORIGINAL|1145A153|Kit Correia Dentada Completo Original|1|985.00
ORIGINAL|1300A113|Bomba D Agua Original Mitsubishi|1|748.00
SIMILAR|K025669XS|Gates|Kit Correia Dentada L200 2.4|1|625.00
SIMILAR|KTB869|Dayco|Kit Correia Dentada L200 2.4|1|658.00
SIMILAR|CT1158K1|Continental|Kit Correia Dentada L200 2.4|1|640.00
SIMILAR|7.09724.08.0|Pierburg|Bomba D Agua L200 2.4|1|465.00
SIMILAR|31.30.118|Starke|Bomba D Agua L200 2.4|1|495.00
SIMILAR|VKPC81426|SKF|Bomba D Agua L200 2.4|1|478.00
[/PECAS]'
        ],

        [
            'Substituicao das Velas de Aquecimento (Glow Plugs)',
            100000,
            '120',
            200.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 90 minutos]

Substituicao das 4 velas de aquecimento (glow plugs) do motor diesel 2.4. As velas pre-aquecedoras aquecem a camara de combustao facilitando a partida a frio do motor diesel. Com o desgaste, aumenta o tempo de partida e emissao de fumaca branca.

[PECAS]
ORIGINAL|1640A045|Vela de Aquecimento Original Mitsubishi|4|488.00
SIMILAR|Y-8003AS|NGK|Vela Aquecimento L200 2.4|4|300.00
SIMILAR|0250203003|Bosch|Vela Aquecimento L200 2.4|4|328.00
SIMILAR|GV150|Beru|Vela Aquecimento L200 2.4|4|312.00
[/PECAS]'
        ],

        [
            'Substituicao da Bateria',
            100000,
            '36',
            50.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 30 minutos]

Substituicao da bateria automotiva 12V 75Ah 650A (CCA minimo). Baterias seladas livre de manutencao tem vida util de 2 a 3 anos. Teste de carga e alternador antes da troca.

[PECAS]
ORIGINAL|MZ360165|Bateria 12V 75Ah 650A Original|1|645.00
SIMILAR|M75JD|Moura|Bateria 12V 75Ah Selada|1|458.00
SIMILAR|HG75JD|Heliar|Bateria 12V 75Ah Free|1|488.00
SIMILAR|S5X75D|Bosch|Bateria 12V 75Ah S5|1|468.00
[/PECAS]'
        ],

        [
            'Substituicao de Discos de Freio Traseiros',
            100000,
            '120',
            160.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 80 minutos]

Substituicao dos discos de freio traseiros. Verificar espessura minima e empenamento antes de decidir pela troca.

[PECAS]
ORIGINAL|4615A144|Par Discos Freio Traseiro Original|2|575.00
SIMILAR|BD9623|Fremax|Par Discos Freio Traseiro L200|2|358.00
SIMILAR|DF6889S|TRW|Par Discos Freio Traseiro L200|2|378.00
[/PECAS]'
        ],

        // ==================== REVISAO 120.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            120000,
            '144',
            150.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 60 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|MZ690115|Filtro de Oleo Original Mitsubishi L200|1|48.00
ORIGINAL|MZ320282|Oleo Motor Mitsubishi Genuine 5W-30 Diesel|7.3L|467.20
ORIGINAL|1770A052|Filtro de Combustivel Diesel Original|1|125.00
SIMILAR|PSL933|Tecfil|Filtro Oleo L200 2.4 Diesel|1|29.00
SIMILAR|HX8-5W30|Shell|Oleo Helix HX8 5W-30|7.3L|379.60
SIMILAR|PSC965|Tecfil|Filtro Combustivel Diesel L200|1|66.00
[/PECAS]'
        ],

        [
            'Segunda Substituicao de Embreagem (se aplicavel)',
            120000,
            '144',
            600.00,
            'Alta',
            '[CATEGORIA: Transmissao] [TEMPO: 360 minutos]

Substituicao do kit completo de embreagem conforme especificacoes da revisao de 80.000 km.

[PECAS]
ORIGINAL|MBR5295|Kit Embreagem Completo Mitsubishi|1|1725.00
SIMILAR|632305409|LUK|Kit Embreagem L200 2.4 Diesel|1|1195.00
SIMILAR|3000970095|Sachs|Kit Embreagem L200 2.4 Diesel|1|1245.00
[/PECAS]'
        ],

        // ==================== PROBLEMAS CONHECIDOS L200 2.4 DIESEL ====================
        [
            'VERIFICACAO - Coletor de Escapamento (Problema Conhecido)',
            40000,
            '48',
            100.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 30 minutos]

**PROBLEMA CONHECIDO L200 2.4:** O coletor de escapamento da L200 2.4 tem historico de trincas e rachaduras devido ao ciclo termico intenso. Verificar visualmente a cada revisao. Sintomas: ruido de escapamento vazando, perda de potencia, fumaca.

**ATENCAO:** Se identificado trincas, substituir imediatamente pois vazamento de gases quentes pode danificar componentes proximos.

[PECAS]
Peca substituida apenas se identificada necessidade na inspecao (custo aproximado R$ 2.500 - R$ 4.000 original)
[/PECAS]'
        ],

        [
            'VERIFICACAO - Sistema do Turbo (Problema Conhecido)',
            30000,
            '36',
            100.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

**PROBLEMA CONHECIDO L200 2.4:** Falha do turbo e comum quando o filtro de ar nao e trocado regularmente. Verificar:
- Estado do filtro de ar (TROCAR A CADA 10.000 KM SEM FALTA)
- Folga axial do eixo do turbo
- Vazamentos de oleo pelo turbo
- Ruidos anormais (assovio, zunido metalico)

**ATENCAO:** Custo de turbo novo: R$ 8.000 - R$ 15.000

[PECAS]
Peca substituida apenas se identificada necessidade na inspecao
[/PECAS]'
        ],

        [
            'VERIFICACAO - Eixo Balanceador (Problema Conhecido)',
            80000,
            '96',
            150.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 60 minutos]

**PROBLEMA CONHECIDO L200 2.4:** O motor 4N15 tem historico de problemas com o eixo balanceador. Verificar:
- Ruidos metalicos anormais no motor
- Vibracoes excessivas
- Nivel de oleo (consumo excessivo pode indicar problema)

**ATENCAO:** Reparar eixo balanceador pode custar R$ 5.000 - R$ 10.000

[PECAS]
Peca substituida apenas se identificada necessidade na inspecao
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
        'message' => "Plano de manutencao para {$modeloNome} importado com sucesso!",
        'proximo_passo' => 'Verificar em https://frotas.in9automacao.com.br/planos-manutencao-novo.html'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao importar plano de manutencao',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>
