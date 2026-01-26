<?php
/**
 * Script para importar Plano de Manutencao Fiat Strada 1.4 Endurance 2021-2022
 * Gerado via Perplexity AI em 2026-01-14
 * Motor: Fire Evo 1.4 8V Flex - 85 cv
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-strada-1.4.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-strada-1.4.php?confirmar=SIM',
        'modelo' => 'Fiat Strada 1.4 Endurance 2021-2022',
        'motor' => 'Fire Evo 1.4 8V Flex - 85 cv',
        'oleo' => '5W-30 Semissintetico API SN - 3.5 litros',
        'correia_dentada' => '60.000 km ou 6 anos - MOTOR INTERFERENTE',
        'atencao' => [
            'CORREIA DENTADA CRITICA - 60.000 km ou 6 anos (motor interferente)',
            'RECALL bomba de combustivel 2021-2022',
            'Veiculo comercial - cuidados especiais com freios e suspensao'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
    // MODELO - Nome EXATO conforme banco de dados (verificado via verificar-modelos.php)
    $modeloNome = "STRADA 1.4 Endurance";

    // PASSO 1: Deletar planos antigos deste modelo
    $stmt = $conn->prepare("DELETE FROM Planos_Manutenção WHERE modelo_carro = ?");
    $stmt->bind_param("s", $modeloNome);
    $stmt->execute();
    $deletados = $stmt->affected_rows;
    $stmt->close();

    // PASSO 2: Definir itens do plano
    // Formato: [descricao_titulo, km, meses, custo_mao_obra, criticidade, observacao]
    $itens_plano = [
        // ==================== REVISAO 10.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            10000,
            '12',
            105.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Drenagem completa do oleo lubrificante do motor Fire Evo 1.4 8V Flex atraves do bujao do carter. Substituicao do filtro de oleo tipo cartucho codigo Fiat/Mopar 46751179 e reabastecimento com oleo semissintetico especificacao SAE 5W-30 API SN ou ACEA A3/B4 para modelos 2017+ (inclui 2021-2022). Capacidade: 3,5 litros com filtro. Motor Fire Evo de 85 cv possui taxa de compressao 10,55:1 e requer oleo de baixa viscosidade para partida a frio e maxima protecao. Criterio: o que ocorrer primeiro (10.000 km OU 12 meses).

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado de pistoes, bronzinas e eixo comando de valvulas, acumulo de borra, oxidacao interna, superaquecimento, perda de eficiencia em ate 18%, possivel travamento ou quebra do motor exigindo retifica completa (R$ 6.500 a R$ 11.000).

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Strada Uno Palio Fire|1|22.00
SIMILAR|PH6607|Fram|Filtro Oleo Fire 1.4|1|26.00
SIMILAR|W610/3|Mann|Filtro Oleo Fire Evo|1|28.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN|4L|155.00
SIMILAR|5W30-SHELL|Shell|Oleo Helix HX7 5W-30 Semissintetico|4L|135.00
SIMILAR|5W30-PETRONAS|Petronas|Oleo Syntium 3000 5W-30|4L|125.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            30.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 10 minutos]

Substituicao do elemento filtrante de ar do motor Fire Evo 1.4 localizado na caixa de ar. Codigo Wega FAP2829, Tecfil ARL4161, Mann C30904, codigo original 52046268. Filtro retem particulas solidas impedindo entrada no coletor de admissao e camara de combustao. Motor Fire Evo com injecao eletronica multiponto requer fluxo de ar limpo para perfeita mistura ar/combustivel. Verificar estado da vedacao e limpeza interna da caixa de ar.

**Consequencias de nao fazer:** Reducao de potencia em ate 12%, aumento no consumo de combustivel em ate 15%, entrada de particulas abrasivas causando desgaste dos cilindros, pistoes e aneis, formacao de borra no coletor de admissao, sensor MAP sujo causando falhas de injecao, marcha lenta irregular.

[PECAS]
ORIGINAL|52046268|Filtro Ar Motor Fiat Strada Fire 1.4|1|95.00
SIMILAR|FAP2829|Wega|Filtro Ar Strada 1.3 1.4 8V 2021|1|38.00
SIMILAR|ARL4161|Tecfil|Filtro Ar Strada Firefly Fire 1.4|1|42.00
SIMILAR|C30904|Mann|Filtro Ar Strada Argo Cronos|1|48.00
SIMILAR|CA12342|Fram|Filtro Ar Strada 1.4|1|43.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            55.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 20 minutos]

Substituicao do filtro de combustivel do sistema de injecao eletronica multiponto. O filtro remove impurezas da gasolina/etanol protegendo bicos injetores e bomba de combustivel. ATENCAO: Verificar bomba de combustivel conforme recall 2021. Despressurizar o sistema antes da remocao (retirar fusivel da bomba, dar partida ate motor morrer). Filtro tipo inline instalado na linha de combustivel. Motor Flex e sensivel a combustivel de baixa qualidade.

**Consequencias de nao fazer:** Entupimento dos bicos injetores, falha na partida, perda de potencia, aumento no consumo em ate 20%, marcha lenta irregular, engasgos, agravamento do problema do recall da bomba, necessidade de limpeza ultrassonica dos injetores (R$ 350 a R$ 550).

[PECAS]
ORIGINAL|77366607|Filtro Combustivel Fiat Strada Original|1|82.00
SIMILAR|GI04/1|Tecfil|Filtro Combustivel Strada Fire|1|35.00
SIMILAR|JFC210|Wega|Filtro Combustivel Strada 1.4|1|32.00
SIMILAR|G5835|Fram|Filtro Combustivel Fire|1|38.00
SIMILAR|WK58/1|Mann|Filtro Combustivel Strada|1|40.00
[/PECAS]'
        ],
        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            115.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 50 minutos]

Inspecao visual e funcional completa conforme manual Fiat: verificacao de niveis de fluidos (arrefecimento, freio, limpador, bateria), teste de luzes externas/internas, buzina, limpadores, travas; inspecao de pneus (pressao 35 PSI dianteiros/38 PSI traseiros com carga, desgaste, banda minima 1,6mm), freios (pastilhas, discos, lonas, tubulacoes), suspensao (amortecedores, buchas, batentes), direcao mecanica/hidraulica, escapamento, bateria (terminais, carga), correias, velas. IMPORTANTE: Veiculo comercial (picape) requer atencao especial na suspensao traseira e cacamba.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos ou recalls, acidentes por falha de freios ou pneus, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular, agravamento de problemas simples em defeitos graves.

[PECAS]
Nao requer pecas de substituicao obrigatorias (apenas eventuais reposicoes identificadas)
[/PECAS]'
        ],

        // ==================== REVISAO 20.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            20000,
            '24',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo incluindo oleo do motor, filtros de oleo, ar e combustivel conforme especificacoes da revisao de 10.000 km.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
ORIGINAL|52046268|Filtro Ar Motor Fiat Strada Fire 1.4|1|95.00
ORIGINAL|77366607|Filtro Combustivel Fiat Strada Original|1|82.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Strada Uno Palio Fire|1|22.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
SIMILAR|FAP2829|Wega|Filtro Ar Strada 1.3 1.4 8V 2021|1|38.00
SIMILAR|GI04/1|Tecfil|Filtro Combustivel Strada Fire|1|35.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao e Cabos',
            20000,
            '24',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Substituicao das 4 velas de ignicao e 4 cabos de vela do motor Fire Evo 1.4 8V Flex. Codigo kit Fiat: 55226520 / 1BP30016AA (jogo completo com velas e cabos). Especificacoes: velas NGK ou Bosch resistivas, gap 1,0mm, rosca 14mm. Motor Flex requer velas especificas resistentes a corrosao do etanol e alta taxa de compressao. Limpar bem a regiao antes da remocao para evitar entrada de sujeira nos cilindros. Aplicar torque de aperto de 25 Nm. Verificar cor dos eletrodos (marrom claro = ideal).

**Consequencias de nao fazer:** Dificuldade na partida especialmente com etanol, falhas de ignicao (motor falhando/trepidando), perda de potencia em ate 18%, aumento no consumo de combustivel em ate 25%, marcha lenta irregular, engasgos, emissoes poluentes elevadas, possivel danificacao do catalisador (R$ 1.500 a R$ 2.800).

[PECAS]
ORIGINAL|55226520|Kit Jogo Velas e Cabos Fire Evo 1.4 Fiat|1|285.00
ORIGINAL|1BP30016AA|Kit Velas e Cabos Mopar Strada 1.4|1|285.00
SIMILAR|VELA-NGK-FIRE|NGK|Jogo 4 Velas Ignicao Fire 1.4 Flex|4|75.00
SIMILAR|CABO-NGK-FIRE|NGK|Jogo 4 Cabos Vela Fire 1.4|1|125.00
SIMILAR|VELA-BOSCH-FIRE|Bosch|Jogo 4 Velas Fire Evo 1.4|4|82.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            20000,
            '24',
            45.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 15 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas ou sob o painel. Filtro tipo particulado retem poeira, polen, bacterias, fuligem e odores externos. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador. Recomenda-se higienizacao do sistema com spray antibacteriano durante a troca.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine (odor de mofo), reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 350 a R$ 550).

[PECAS]
ORIGINAL|51774652|Filtro Ar Condicionado Fiat Strada|1|95.00
SIMILAR|AKX3537|Wega|Filtro Cabine Strada Palio Uno|1|32.00
SIMILAR|ACP911|Tecfil|Filtro Cabine Strada Fire|1|35.00
SIMILAR|CF911|Fram|Filtro Ar Condicionado Strada|1|38.00
SIMILAR|CU1742|Mann|Filtro Cabine Strada|1|40.00
[/PECAS]'
        ],
        [
            'Rodizio de Pneus e Alinhamento',
            20000,
            '24',
            140.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 45 minutos]

Execucao de rodizio dos pneus 195/65 R15 (Endurance) seguindo padrao paralelo ou cruz. ATENCAO: Picape tem distribuicao de peso diferente - 35 PSI dianteiros/38 PSI traseiros com carga. Verificacao de pressao, inspecao de desgaste irregular indicando necessidade de alinhamento. Verificacao de cortes, bolhas, deformacoes. Alinhamento 3D das rodas dianteiras (veiculo nao possui regulagem traseira - eixo rigido). Balanceamento eletronico das 4 rodas.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 35%, vibracoes no volante, perda de estabilidade direcional, aumento no consumo de combustivel em ate 10%, perda de aderencia em piso molhado, desgaste irregular da direcao.

[PECAS]
SIMILAR|PESO-BAL-5G|Universal|Peso de Balanceamento Adesivo|50G|10.00
SIMILAR|PESO-BAL-10G|Universal|Peso de Balanceamento Clip-on|100G|15.00
[/PECAS]'
        ],

        // ==================== REVISAO 30.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            30000,
            '36',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
ORIGINAL|52046268|Filtro Ar Motor Fiat Strada Fire 1.4|1|95.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
SIMILAR|FAP2829|Wega|Filtro Ar Strada 1.3 1.4 8V 2021|1|38.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            30000,
            '24',
            95.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico com ABS. Fluido higroscopico absorve umidade do ar reduzindo ponto de ebulicao e causando perda de eficiencia. Procedimento: sangria de todas as rodas e modulo ABS iniciando pela mais distante do cilindro mestre (traseira direita, traseira esquerda, dianteira direita, dianteira esquerda). Capacidade aproximada: 500ml. Utilizar apenas fluido DOT 4 homologado FMVSS 116. Intervalo critico: a cada 2 anos independente da quilometragem.

**Consequencias de nao fazer:** Fluido contaminado com umidade causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico (cilindros mestre e roda, pincas, modulo ABS), necessidade de substituicao completa do sistema, falha do ABS, acidentes graves.

[PECAS]
ORIGINAL|7082293694|Fluido de Freio DOT 4 Tutela Fiat|500ML|45.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|28.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|32.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|500ML|26.00
SIMILAR|DOT4-ATE|ATE|Fluido Freio Super DOT 4|500ML|35.00
[/PECAS]'
        ],
        [
            'Limpeza do Sistema de Injecao Eletronica',
            30000,
            '36',
            45.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 60 minutos]

Limpeza preventiva dos bicos injetores multiponto atraves de aditivo de alta qualidade aplicado no tanque de combustivel. Motor Fire Evo 1.4 Flex possui 4 bicos injetores que podem acumular depositos carboniferos especialmente com uso de etanol de baixa qualidade. Procedimento: abastecer tanque com gasolina aditivada, adicionar produto limpador de injetores, rodar em rodovia por pelo menos 50 km. Em casos severos, realizar limpeza por ultrassom em oficina especializada.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 14%, aumento no consumo em ate 18%, marcha lenta irregular, dificuldade na partida a frio, engasgos, formacao de depositos no coletor de admissao, necessidade de limpeza ultrassonica (R$ 350 a R$ 550).

[PECAS]
SIMILAR|FLEX-CLEAN|Wynns|Aditivo Limpador Sistema Flex|325ML|42.00
SIMILAR|INJ-CLEAN|Wurth|Limpador Injetores Flex|300ML|35.00
SIMILAR|TOP-CLEAN|Bardahl|Limpador Sistema Combustivel|200ML|38.00
[/PECAS]'
        ],

        // ==================== REVISAO 40.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            40000,
            '48',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
ORIGINAL|52046268|Filtro Ar Motor Fiat Strada Fire 1.4|1|95.00
ORIGINAL|77366607|Filtro Combustivel Fiat Strada Original|1|82.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
SIMILAR|FAP2829|Wega|Filtro Ar Strada 1.3 1.4 8V 2021|1|38.00
SIMILAR|GI04/1|Tecfil|Filtro Combustivel Strada Fire|1|35.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao e Cabos',
            40000,
            '48',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Segunda troca das velas de ignicao e cabos conforme especificacoes da revisao de 20.000 km.

[PECAS]
ORIGINAL|55226520|Kit Jogo Velas e Cabos Fire Evo 1.4 Fiat|1|285.00
SIMILAR|VELA-NGK-FIRE|NGK|Jogo 4 Velas Ignicao Fire 1.4 Flex|4|75.00
SIMILAR|CABO-NGK-FIRE|NGK|Jogo 4 Cabos Vela Fire 1.4|1|125.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            145.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas) sistema Teves. Codigo Cobreq N-2129, Fras-le PD/2226, Jurid HQJ-2492. Freios a disco ventilado dianteiro (4 furos, diametro 256mm). Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas, verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos. Sangria se necessario. Teste em pista. ATENCAO: Picape com carga requer atencao especial aos freios.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos profundos nos discos, perda de eficiencia de frenagem em ate 45%, ruido metalico intenso, aumento da distancia de frenagem, necessidade de substituicao prematura dos discos, falha do ABS, risco de acidentes graves especialmente com carga.

[PECAS]
ORIGINAL|77367476|Jogo Pastilhas Freio Diant Fiat Strada|1|195.00
SIMILAR|N2129|Cobreq|Jogo Pastilhas Freio Diant Strada 2020|1|85.00
SIMILAR|PD2226|Fras-le|Jogo Pastilhas Freio Diant Strada 1.4|1|88.00
SIMILAR|HQJ2492|Jurid|Jogo Pastilhas Freio Diant Strada|1|92.00
SIMILAR|BB2226|Bosch|Jogo Pastilhas Freio Diant Strada|1|95.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            85.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Substituicao da correia do alternador e correia da direcao hidraulica/ar condicionado (correias poly-V). Motor Fire 1.4 utiliza correias multiplas acionando alternador, bomba de direcao hidraulica (se equipada) e compressor do ar condicionado. Verificacao do estado dos tensionadores, polias lisas e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao. Tensionamento adequado conforme especificacao do fabricante.

**Consequencias de nao fazer:** Rompimento da correia causando descarregamento da bateria, perda do ar condicionado, perda de direcao assistida hidraulica, possivel superaquecimento por sobrecarga eletrica prolongada, necessidade de guincho.

[PECAS]
ORIGINAL|71736717|Correia Alternador Fiat Strada Fire|1|92.00
SIMILAR|4PK855|Gates|Correia Poly-V Alternador Strada|1|38.00
SIMILAR|4PK855|Continental|Correia Poly-V Strada 1.4|1|35.00
SIMILAR|K040855|Dayco|Correia Alternador Fire 1.4|1|37.00
SIMILAR|4PK855|Goodyear|Correia Auxiliar Strada|1|33.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            40000,
            '48',
            45.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 15 minutos]

Segunda troca do filtro de cabine conforme especificacoes anteriores.

[PECAS]
ORIGINAL|51774652|Filtro Ar Condicionado Fiat Strada|1|95.00
SIMILAR|AKX3537|Wega|Filtro Cabine Strada Palio Uno|1|32.00
SIMILAR|ACP911|Tecfil|Filtro Cabine Strada Fire|1|35.00
[/PECAS]'
        ],

        // ==================== REVISAO 50.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            50000,
            '60',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            110.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor Fire 1.4. Fiat recomenda fluido Paraflu (aditivo de longa duracao cor vermelha) diluido 50/50 com agua desmineralizada. Capacidade total: aproximadamente 5,5 litros da mistura. Procedimento: drenagem pelo bujao do radiador, lavagem interna com agua, reabastecimento da mistura, sangria do sistema (eliminacao de bolhas de ar), funcionamento ate atingir temperatura normal (ventoinha acionando), verificacao de vazamentos e nivel.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do radiador, bloco, cabecote e bomba dagua, formacao de borra e depositos reduzindo eficiencia de troca termica, superaquecimento, danos ao radiador, bomba dagua (R$ 220 a R$ 380), termostato (R$ 95 a R$ 180) e motor, possivel empenamento do cabecote.

[PECAS]
ORIGINAL|71735513|Aditivo Radiador Paraflu Fiat|2L|115.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|3L|18.00
SIMILAR|PARAFLU-LL|Repsol|Aditivo Radiador Longa Duracao|2L|60.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico|2L|62.00
SIMILAR|RAD-PROTEC|Valvoline|Aditivo Radiador Universal|2L|55.00
[/PECAS]'
        ],
        [
            'Higienizacao Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            175.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 85 minutos]

Limpeza profissional do sistema de ar condicionado: aplicacao de espuma higienizadora no evaporador atraves da caixa de ar, aspiracao da espuma e residuos, aplicacao de bactericida/fungicida por nebulizacao, limpeza do dreno do evaporador (frequentemente entupido), troca do filtro de cabine. Verificacao de pressao do gas refrigerante R-134a, teste de vazamentos com detector eletronico, temperatura de saida (deve atingir 4-7C). Teste de funcionamento do compressor, embreagem eletromagnetica e eletroventilador do condensador.

**Consequencias de nao fazer:** Proliferacao de fungos e bacterias no evaporador, mau cheiro persistente (odor de mofo), alergias respiratorias graves, obstrucao do dreno causando infiltracao de agua no assoalho e modulo eletronico, reducao da eficiencia do sistema em ate 40%.

[PECAS]
ORIGINAL|51774652|Filtro Ar Condicionado Fiat Strada|1|95.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado|500ML|52.00
SIMILAR|KLIMACLEAN|Wynns|Limpador Ar Condicionado Automotivo|500ML|58.00
SIMILAR|AKX3537|Wega|Filtro Cabine Strada Palio Uno|1|32.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM - CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '72',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
ORIGINAL|52046268|Filtro Ar Motor Fiat Strada Fire 1.4|1|95.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
[/PECAS]'
        ],
        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT COMPLETO',
            60000,
            '72',
            550.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 210 minutos]

ITEM MAIS CRITICO DE MANUTENCAO DO MOTOR FIRE. Substituicao obrigatoria da correia dentada de sincronismo (137 dentes), tensor automatico e polia tensora. O MOTOR FIRE 1.4 E DO TIPO INTERFERENTE: se a correia romper, os pistoes colidem com as valvulas causando danos catastroficos. Manual Fiat recomenda troca aos 60.000 km ou 6 anos. Procedimento exige ferramentas especiais de travamento (ponto morto superior e comando de valvulas). OBRIGATORIO substituir tambem a bomba dagua preventivamente (acionada pela correia, economia de mao de obra).

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e valvulas (motor interferente), empenamento/quebra de valvulas (todas as 8), danos severos aos pistoes, cabecote destruido, necessidade de retifica completa do motor ou substituicao. CUSTO DE REPARO: R$ 6.500 a R$ 13.000. Esta e a falha mecanica mais cara que pode ocorrer no veiculo.

[PECAS]
ORIGINAL|71771582|Correia Dentada Fiat Fire 1.4 137 dentes|1|245.00
ORIGINAL|71770546|Tensor Automatico Correia Dentada Fire|1|365.00
ORIGINAL|71770547|Polia Tensora Correia Dentada Fire|1|165.00
ORIGINAL|55221121|Bomba Dagua Fiat Fire 1.4|1|325.00
SIMILAR|CT1028K3|Gates|Kit Correia Dentada Fire 1.4 Completo|1|395.00
SIMILAR|TB1028|Dayco|Correia Dentada Fire 1.4|1|145.00
SIMILAR|T41028|Gates|Tensor Automatico Fire 1.4|1|215.00
SIMILAR|PA1028|Nakata|Polia Tensora Fire 1.4|1|95.00
SIMILAR|WP1028|Nakata|Bomba Dagua Fire 1.0 1.4|1|155.00
SIMILAR|PA1028|Urba|Bomba Dagua Fire Evo|1|175.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao e Cabos',
            60000,
            '72',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Terceira troca das velas de ignicao e cabos conforme especificacoes anteriores.

[PECAS]
ORIGINAL|55226520|Kit Jogo Velas e Cabos Fire Evo 1.4 Fiat|1|285.00
SIMILAR|VELA-NGK-FIRE|NGK|Jogo 4 Velas Ignicao Fire 1.4 Flex|4|75.00
SIMILAR|CABO-NGK-FIRE|NGK|Jogo 4 Cabos Vela Fire 1.4|1|125.00
[/PECAS]'
        ],
        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            175.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 90 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio ventilados dianteiros 4 furos, diametro 256mm. Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada no disco (geralmente 21mm). Sangria do sistema. Teste em pista. Discos devem ser substituidos em par sempre. ATENCAO: Veiculo de carga requer freios em perfeito estado.

[PECAS]
ORIGINAL|77367476|Jogo Pastilhas Freio Diant Fiat Strada|1|195.00
ORIGINAL|51860916|Par Discos Freio Diant Fiat Strada|2|425.00
SIMILAR|N2129|Cobreq|Jogo Pastilhas Freio Diant Strada 2020|1|85.00
SIMILAR|PD2226|Fras-le|Jogo Pastilhas Freio Diant Strada 1.4|1|88.00
SIMILAR|DF2226|Fremax|Par Discos Freio Ventilado Strada|2|245.00
SIMILAR|RC2226|Cobreq|Par Discos Freio Diant Strada|2|235.00
SIMILAR|BD2226|TRW|Par Discos Freio Strada 1.4|2|255.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            60000,
            '24',
            95.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Terceira troca do fluido de freio (intervalo a cada 2 anos independente da km).

[PECAS]
ORIGINAL|7082293694|Fluido de Freio DOT 4 Tutela Fiat|500ML|45.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|28.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|32.00
[/PECAS]'
        ],

        // ==================== REVISAO 70.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            70000,
            '84',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            80000,
            '96',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
ORIGINAL|52046268|Filtro Ar Motor Fiat Strada Fire 1.4|1|95.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
SIMILAR|FAP2829|Wega|Filtro Ar Strada 1.3 1.4 8V 2021|1|38.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao e Cabos',
            80000,
            '96',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Quarta troca das velas de ignicao e cabos conforme especificacoes anteriores.

[PECAS]
ORIGINAL|55226520|Kit Jogo Velas e Cabos Fire Evo 1.4 Fiat|1|285.00
SIMILAR|VELA-NGK-FIRE|NGK|Jogo 4 Velas Ignicao Fire 1.4 Flex|4|75.00
SIMILAR|CABO-NGK-FIRE|NGK|Jogo 4 Cabos Vela Fire 1.4|1|125.00
[/PECAS]'
        ],
        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            195.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 120 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores traseiros (sistema a tambor 228mm). Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos, cabo do freio de estacionamento. Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm. Sangria do sistema. ATENCAO: Veiculo de carga exige freios traseiros em perfeito estado.

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro em ate 60%, desbalanceamento da frenagem, freio de estacionamento inoperante (reprovacao na inspecao), necessidade de substituicao dos tambores, acidentes por frenagem deficiente especialmente com carga.

[PECAS]
ORIGINAL|77367478|Jogo Lonas Freio Traseiro Fiat Strada|1|165.00
ORIGINAL|51860918|Par Tambores Freio Traseiro Fiat Strada|2|395.00
SIMILAR|HI1228|Fras-le|Jogo Lonas Freio Traseiro Strada|1|72.00
SIMILAR|N1228|Cobreq|Jogo Lonas Freio Traseiro Strada|1|68.00
SIMILAR|TT2228|TRW|Par Tambores Freio Traseiro Strada|2|225.00
SIMILAR|RT2228|Fremax|Par Tambores Freio Traseiro Strada|2|215.00
[/PECAS]'
        ],
        [
            'Substituicao de Amortecedores',
            80000,
            '96',
            275.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 150 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo telescopico) incluindo kits de reparo (coxins superiores, batentes, coifas). ATENCAO: Picape tem suspensao reforcada para carga - usar amortecedores especificos para Strada. Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar cada canto do veiculo, deve retornar sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento apos a troca.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem em ate 20%, perda de estabilidade em curvas especialmente com carga, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas), desconforto aos ocupantes, trepidacao, risco de capotamento com carga lateral.

[PECAS]
ORIGINAL|51893872|Amortecedor Dianteiro Fiat Strada|2|585.00
ORIGINAL|51893874|Amortecedor Traseiro Fiat Strada|2|545.00
SIMILAR|HG28145|Monroe|Amortecedor Diant Strada Gas|2|365.00
SIMILAR|HG28146|Monroe|Amortecedor Tras Strada Gas|2|345.00
SIMILAR|AM28145|Cofap|Amortecedor Diant Strada Turbogas|2|305.00
SIMILAR|AM28146|Cofap|Amortecedor Tras Strada Turbogas|2|285.00
SIMILAR|N28145|Nakata|Amortecedor Diant Strada 1.4|2|275.00
SIMILAR|N28146|Nakata|Amortecedor Tras Strada 1.4|2|255.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            80000,
            '96',
            85.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Segunda troca da correia do alternador conforme especificacoes da revisao de 40.000 km.

[PECAS]
ORIGINAL|71736717|Correia Alternador Fiat Strada Fire|1|92.00
SIMILAR|4PK855|Gates|Correia Poly-V Alternador Strada|1|38.00
SIMILAR|4PK855|Continental|Correia Poly-V Strada 1.4|1|35.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            80000,
            '96',
            145.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Terceira troca das pastilhas de freio dianteiras conforme especificacoes anteriores.

[PECAS]
ORIGINAL|77367476|Jogo Pastilhas Freio Diant Fiat Strada|1|195.00
SIMILAR|N2129|Cobreq|Jogo Pastilhas Freio Diant Strada 2020|1|85.00
SIMILAR|PD2226|Fras-le|Jogo Pastilhas Freio Diant Strada 1.4|1|88.00
[/PECAS]'
        ],

        // ==================== REVISAO 90.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            90000,
            '108',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            90000,
            '24',
            95.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Quarta troca do fluido de freio (intervalo a cada 2 anos).

[PECAS]
ORIGINAL|7082293694|Fluido de Freio DOT 4 Tutela Fiat|500ML|45.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|28.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            100000,
            '120',
            125.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|46751179|Filtro de Oleo Motor Fire Fiat Strada|1|52.00
ORIGINAL|55226517|Oleo Motor Selenia K Pure Energy 5W-30|4L|245.00
ORIGINAL|52046268|Filtro Ar Motor Fiat Strada Fire 1.4|1|95.00
SIMILAR|WO120|Wega|Filtro Oleo Fire 1.0 1.4 8V|1|24.00
SIMILAR|5W30-MOBIL|Mobil|Oleo Mobil Super 3000 5W-30 Sintetico|4L|145.00
SIMILAR|FAP2829|Wega|Filtro Ar Strada 1.3 1.4 8V 2021|1|38.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao e Cabos',
            100000,
            '120',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Quinta troca das velas de ignicao e cabos conforme especificacoes anteriores.

[PECAS]
ORIGINAL|55226520|Kit Jogo Velas e Cabos Fire Evo 1.4 Fiat|1|285.00
SIMILAR|VELA-NGK-FIRE|NGK|Jogo 4 Velas Ignicao Fire 1.4 Flex|4|75.00
SIMILAR|CABO-NGK-FIRE|NGK|Jogo 4 Cabos Vela Fire 1.4|1|125.00
[/PECAS]'
        ],
        [
            'Substituicao da Bateria',
            100000,
            '60',
            35.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 25 minutos]

Substituicao da bateria automotiva 12V. Fiat Strada 1.4 utiliza bateria de 50Ah a 60Ah com corrente de partida (CCA) de 380A a 450A. Baterias seladas livre de manutencao tem vida util de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora. Configuracao de sistemas eletronicos (radio, relogio) apos troca se necessario. Dimensoes: 230mm x 175mm x 190mm.

**Consequencias de nao fazer:** Falha de partida especialmente em dias frios, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, oxidacao dos terminais, perda de memoria dos sistemas eletronicos, necessidade de reboque.

[PECAS]
ORIGINAL|51892003|Bateria 12V 60Ah Fiat Original|1|485.00
SIMILAR|60GD-450|Moura|Bateria 12V 60Ah 450A Selada|1|325.00
SIMILAR|60D-480|Heliar|Bateria 12V 60Ah 480A Free|1|335.00
SIMILAR|B60DH|Bosch|Bateria 12V 60Ah S4 Free|1|375.00
SIMILAR|60AH-380|Zetta|Bateria 12V 60Ah Selada|1|275.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            100000,
            '120',
            110.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Segunda troca do fluido de arrefecimento conforme especificacoes da revisao de 50.000 km.

[PECAS]
ORIGINAL|71735513|Aditivo Radiador Paraflu Fiat|2L|115.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|3L|18.00
SIMILAR|PARAFLU-LL|Repsol|Aditivo Radiador Longa Duracao|2L|60.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico|2L|62.00
[/PECAS]'
        ],

        // ==================== ITENS ESPECIAIS ====================
        [
            'Substituicao de Pneus (por tempo ou desgaste)',
            50000,
            '60',
            55.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 80 minutos para jogo completo]

Fiat Strada Endurance utiliza pneus 195/65 R15. Vida util media: 40.000 a 50.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). ATENCAO ESPECIAL: Picape tem distribuicao de peso diferente - calibrar 35 PSI dianteiros/38 PSI traseiros sem carga, 35/42 PSI com carga maxima (650 kg). Verificar mensalmente: pressao, desgaste da banda (minimo legal 1,6mm medido nos TWI), deformacoes, cortes laterais, data de fabricacao (codigo DOT). Realizar rodizio a cada 10.000 km.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 40%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves especialmente com carga, capotamento, multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular, reprovacao em inspecao veicular.

[PECAS]
SIMILAR|195/65R15|Pirelli|Pneu Cinturato P4 195/65 R15|4|1280.00
SIMILAR|195/65R15|Goodyear|Pneu Direction Sport 195/65 R15|4|1180.00
SIMILAR|195/65R15|Bridgestone|Pneu Turanza ER300 195/65 R15|4|1220.00
SIMILAR|195/65R15|Continental|Pneu ContiPowerContact 195/65 R15|4|1250.00
[/PECAS]'
        ],

        // ==================== RECALL E PROBLEMAS CONHECIDOS ====================
        [
            'RECALL CRITICO - Bomba de Combustivel (2021-2022)',
            1000,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: Verificacao imediata]

PROBLEMA GRAVE na bomba de combustivel que pode apresentar falhas internas, INTERROMPENDO O ENVIO DE COMBUSTIVEL AO MOTOR COM O VEICULO EM MOVIMENTO. Afeta Nova Strada ano/modelo 2022 com chassis NYW49930 a NYW64032. Bombas produzidas entre 02 a 12 de julho de 2021.

SINAIS DE ALERTA:
- Motor morre durante a conducao
- Falha na partida
- Engasgos e perda de potencia
- Motor falha em movimento

RISCOS: POTENCIALIZA A OCORRENCIA DE ACIDENTES COM DANOS MATERIAIS, DANOS FISICOS GRAVES OU ATE MESMO FATAIS AOS OCUPANTES DO VEICULO E/OU TERCEIROS.

PROCEDIMENTO: VERIFICAR URGENTEMENTE no site www.fiat.com.br ou telefone 0800 707 1000. Reparo: analise e, se necessaria, substituicao da bomba de combustivel. SERVICO GRATUITO EM CONCESSIONARIA.

[PECAS]
Servico gratuito em concessionaria autorizada Fiat
[/PECAS]'
        ],
        [
            'Motor Fire - Correia Dentada CRITICA (Problema Conhecido)',
            50000,
            '60',
            0.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: Monitoramento continuo]

O MOTOR FIRE 1.4 8V E INTERFERENTE: se a correia dentada romper, os pistoes colidem com as valvulas causando danos catastroficos. Esta e a manutencao mais importante do veiculo.

SINAIS DE ALERTA (ruptura iminente):
- Ruido agudo/chiado vindo do motor
- Estalidos na regiao da correia
- Correia com aspecto ressecado ou trincas visiveis
- Motor chegando aos 60.000 km ou 6 anos

PREVENCAO:
- Troca OBRIGATORIA aos 60.000 km ou 6 anos (o que vier primeiro)
- NUNCA ultrapassar esse intervalo, mesmo que correia pareca boa
- SEMPRE substituir tensor e polia tensora junto
- Substituir bomba dagua preventivamente
- Usar apenas pecas de qualidade (original ou Gates/Dayco)
- Verificar visualmente a correia a cada revisao aos 50.000 km

Custo de substituicao preventiva: R$ 1.100 a R$ 1.400
Custo de reparo se romper: R$ 6.500 a R$ 13.000

[PECAS]
Monitoramento e prevencao - verificar a cada revisao apos 50.000 km
[/PECAS]'
        ],
        [
            'Veiculo Comercial - Cuidados Especiais (Picape de Carga)',
            10000,
            '12',
            0.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: Monitoramento continuo]

Strada e veiculo comercial (picape) com capacidade de carga de 650 kg. Uso comercial intenso e transporte de carga requerem atencao especial em varios sistemas.

MANUTENCOES CRITICAS PARA USO COMERCIAL:
- Freios: Pastilhas e lonas desgastam mais rapido com carga - verificar a cada 5.000 km
- Suspensao: Amortecedores e molas sofrem mais - inspecionar regularmente
- Pneus: Calibragem diferenciada (35/38 PSI sem carga, 35/42 PSI com carga)
- Oleo: Trocar a cada 7.500 km em uso severo (trajetos curtos, carga constante)
- Fluido freio: Trocar a cada 12 meses em uso intenso

USO SEVERO (reduzir intervalos pela metade):
- Trajetos curtos (<10 km) diarios
- Transporte frequente de carga maxima
- Trafego urbano congestionado constante
- Estradas de terra frequentes
- Mais de 8 horas/dia de uso

[PECAS]
Monitoramento e prevencao - sem pecas especificas
[/PECAS]'
        ],
        [
            'Motor Fire - Sensibilidade a Oleo (Problema Conhecido)',
            10000,
            '12',
            0.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: Monitoramento continuo]

Motor Fire Evo possui componentes internos sensiveis a qualidade do oleo. Modelos 2017+ (inclui 2021-2022) EXIGEM oleo 5W-30 semissintetico ou sintetico. Usar oleo inadequado causa desgaste prematuro.

SINAIS DE ALERTA:
- Ruido tipo cascalho no motor (desgaste do comando)
- Consumo de oleo entre trocas
- Perda de potencia
- Luz de pressao de oleo piscando

PREVENCAO:
- SEMPRE utilizar oleo 5W-30 API SN ou ACEA A3/B4
- NUNCA usar oleo 15W-40 ou 20W-50 em modelos 2017+
- Trocar oleo rigorosamente a cada 10.000 km ou 12 meses
- Verificar nivel semanalmente
- Usar filtro de qualidade (original ou Wega/Tecfil)

[PECAS]
Monitoramento e prevencao - sem pecas especificas
[/PECAS]'
        ]
    ];

    // PASSO 3: Inserir na tabela
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

    // Resposta
    echo json_encode([
        'success' => true,
        'modelo' => $modeloNome,
        'planos_deletados' => $deletados,
        'planos_inseridos' => $inseridos,
        'message' => "Plano de manutencao para {$modeloNome} importado com sucesso!",
        'detalhes' => [
            'motor' => 'Fire Evo 1.4 8V Flex - 85 cv',
            'oleo' => '5W-30 Semissintetico API SN - 3.5 litros',
            'velas' => 'NGK ou Bosch resistivas - a cada 20.000 km',
            'correia_dentada' => '60.000 km ou 6 anos - MOTOR INTERFERENTE',
            'atencao_especial' => [
                'CORREIA DENTADA CRITICA - 60.000 km ou 6 anos (motor interferente)',
                'RECALL bomba de combustivel 2021-2022 - verificar www.fiat.com.br',
                'Veiculo comercial - cuidados especiais com freios e suspensao',
                'NUNCA usar oleo 15W-40 ou 20W-50 - apenas 5W-30'
            ]
        ],
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
