<?php
/**
 * Script para importar Plano de Manutencao Fiat Mobi 1.0 Like
 * Gerado via Perplexity AI em 2026-01-14
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-mobi-1.0.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-mobi-1.0.php?confirmar=SIM'
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
    // MODELO - Nome EXATO conforme banco de dados (verificar com verificar-modelos.php)
    $modeloNome = "MOBI 1.0 LIKE";

    // PASSO 1: Deletar planos antigos deste modelo
    $stmt = $conn->prepare("DELETE FROM Planos_Manutenção WHERE modelo_carro = ?");
    $stmt->bind_param("s", $modeloNome);
    $stmt->execute();
    $deletados = $stmt->affected_rows;
    $stmt->close();

    // PASSO 2: Definir itens do plano de manutencao
    // Formato: [descricao_titulo, km, meses, custo_mao_obra, criticidade, observacao]
    $itens_plano = [
        // ==================== REVISAO 10.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            10000,
            '12',
            100.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Drenagem completa do oleo lubrificante do motor Fire Evo 1.0 8V atraves do bujao do carter. Substituicao do filtro de oleo tipo rosqueavel e reabastecimento com oleo semissintetico ou sintetico especificacao SAE 5W-30 API SM ou superior. Capacidade total: 2,7 litros com filtro. O motor Fire Evo possui sistema de gerenciamento eletronico avancado que requer oleo de baixa viscosidade para partida a frio e economia de combustivel.

**Criticidade:** CRITICA - Motor moderno com folgas reduzidas requer lubrificacao premium constante.

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado de pistoes, bronzinas e eixo comando de valvulas, acumulo de borra, oxidacao interna, superaquecimento, perda de eficiencia do motor, possivel travamento exigindo retifica completa (R$ 6.000 a R$ 10.000).

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|W712/30|Mann|Filtro Oleo Mobi 1.0|1|32.00
SIMILAR|JFO955|Wega|Filtro Oleo Mobi 1.0|1|26.00
SIMILAR|PH6018|Fram|Filtro Oleo Mobi 1.0|1|30.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
SIMILAR|MAGNATEC-5W30|Castrol|Oleo Magnatec 5W-30 API SN|3L|115.00
SIMILAR|MOBIL-SUPER-5W30|Mobil|Oleo Mobil Super 3000 5W-30|3L|125.00
SIMILAR|LUBRAX-TECNO-5W30|Petrobras|Oleo Lubrax Tecno 5W-30|3L|98.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            30.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 10 minutos]

Substituicao do elemento filtrante de ar do motor Fire Evo 1.0 localizado na caixa de ar. O filtro retem particulas solidas impedindo entrada no coletor de admissao e camara de combustao. Motor Fire Evo com injecao eletronica multiponto requer fluxo de ar limpo para perfeita mistura ar/combustivel. Verificar estado da vedacao da borracha e limpeza interna da caixa de ar.

**Criticidade:** ALTA - Filtro saturado reduz potencia e aumenta consumo.

**Consequencias de nao fazer:** Reducao de potencia em ate 10%, aumento no consumo de combustivel em ate 12%, entrada de particulas abrasivas causando desgaste dos cilindros, pistoes e aneis, formacao de borra no coletor de admissao, sensor MAF sujo causando falhas de injecao.

[PECAS]
ORIGINAL|51836363|Filtro Ar Motor Fiat Mobi 1.0|1|95.00
SIMILAR|ARL4152|Tecfil|Filtro Ar Mobi 1.0 Fire Evo|1|42.00
SIMILAR|C30904|Mann|Filtro Ar Mobi 1.0|1|48.00
SIMILAR|JFA955|Wega|Filtro Ar Mobi 1.0|1|38.00
SIMILAR|CA12855|Fram|Filtro Ar Mobi 1.0|1|45.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            60.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 20 minutos]

Substituicao do filtro de combustivel do sistema de injecao eletronica multiponto. O filtro remove impurezas da gasolina/etanol protegendo bicos injetores e bomba de combustivel. **ATENCAO:** Despressurizar o sistema antes da remocao (retirar fusivel da bomba, dar partida ate motor morrer). Filtro tipo inline instalado na linha de combustivel. Sempre utilizar combustivel de qualidade.

**Criticidade:** ALTA - Sistema de injecao e sensivel a impurezas.

**Consequencias de nao fazer:** Entupimento dos bicos injetores, falha na partida, perda de potencia, aumento no consumo, marcha lenta irregular, engasgos, necessidade de limpeza ultrassonica dos injetores (R$ 400 a R$ 600) ou substituicao completa (R$ 1.200 a R$ 2.000).

[PECAS]
ORIGINAL|51806073|Filtro Combustivel Fiat Mobi 1.0|1|85.00
SIMILAR|GI04/7|Tecfil|Filtro Combustivel Mobi 1.0|1|38.00
SIMILAR|WK58|Mann|Filtro Combustivel Mobi 1.0|1|42.00
SIMILAR|JFC455|Wega|Filtro Combustivel Mobi 1.0|1|35.00
SIMILAR|G6652|Fram|Filtro Combustivel Mobi 1.0|1|40.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            45.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 15 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas ou sob o painel. Filtro tipo particulado retem poeira, polen, bacterias, fuligem e odores externos. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador (blower). Recomenda-se higienizacao do sistema com spray antibacteriano durante a troca.

**Criticidade:** MEDIA - Impacta qualidade do ar e eficiencia do sistema.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine, reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 350 a R$ 550).

[PECAS]
ORIGINAL|52046268|Filtro Ar Condicionado Fiat Mobi|1|82.00
SIMILAR|ACP906|Tecfil|Filtro Cabine Mobi 1.0|1|35.00
SIMILAR|CU20006|Mann|Filtro Ar Condicionado Mobi|1|38.00
SIMILAR|AKX9006|Wega|Filtro Cabine Mobi 1.0|1|32.00
SIMILAR|CF10906|Fram|Filtro Ar Condicionado Mobi|1|36.00
[/PECAS]'
        ],

        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            120.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 50 minutos]

Inspecao visual e funcional completa conforme manual Fiat: verificacao de niveis de fluidos (arrefecimento, freio, limpador, bateria), teste de luzes externas/internas, buzina, limpadores, travas eletricas; inspecao de pneus (pressao 30 PSI dianteiros/traseiros, desgaste, banda minima 1,6mm), sistema de freios (pastilhas, discos, lonas, tubulacoes), suspensao (amortecedores, buchas, batentes), direcao, escapamento, bateria (terminais, carga), correias auxiliares, velas de ignicao.

**Criticidade:** ALTA - Detecta problemas em estagio inicial.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos, acidentes por falha de freios ou pneus, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular.

[PECAS]
Nao requer pecas de substituicao obrigatorias (apenas eventuais reposicoes identificadas)
[/PECAS]'
        ],

        // ==================== REVISAO 20.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            20000,
            '24',
            135.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo incluindo oleo do motor, filtros de oleo, ar, combustivel e ar condicionado conforme especificacoes da revisao de 10.000 km.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
ORIGINAL|51836363|Filtro Ar Motor Fiat Mobi 1.0|1|95.00
ORIGINAL|51806073|Filtro Combustivel Fiat Mobi 1.0|1|85.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|ARL4152|Tecfil|Filtro Ar Mobi 1.0 Fire Evo|1|42.00
SIMILAR|GI04/7|Tecfil|Filtro Combustivel Mobi 1.0|1|38.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
SIMILAR|LUBRAX-TECNO-5W30|Petrobras|Oleo Lubrax Tecno 5W-30|3L|98.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            20000,
            '24',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Substituicao das 4 velas de ignicao do motor Fire Evo 1.0 8V Flex. Utilizar velas NGK ou Bosch codigo F 000 KE0 P07. Especificacoes: rosca M14x1,25, alcance 19mm, abertura de eletrodos 0,8mm. Aplicar torque de aperto de 28 Nm. Motor flex requer velas especificas resistentes a corrosao do etanol. Limpar bem a regiao antes da remocao para evitar entrada de sujeira nos cilindros. Verificar cor dos eletrodos (branco = mistura pobre, preto = mistura rica).

**Criticidade:** ALTA - Velas desgastadas causam falhas de combustao.

**Consequencias de nao fazer:** Dificuldade na partida, falhas de ignicao (motor falhando), perda de potencia em ate 15%, aumento no consumo de combustivel em ate 20%, marcha lenta irregular, trepidacao, engasgos, emissoes poluentes elevadas, possivel danificacao do catalisador (R$ 1.500 a R$ 2.500).

[PECAS]
ORIGINAL|55228404|Jogo Velas Ignicao Fiat Mobi 1.0 Fire|4|185.00
SIMILAR|F000KE0P07|Bosch|Jogo 4 Velas Ignicao Mobi 1.0|4|95.00
SIMILAR|BKR6E|NGK|Jogo 4 Velas Ignicao Mobi Fire 1.0|4|88.00
SIMILAR|R42XL|Bosch|Vela Ignicao Mobi 1.0 Fire Evo|4|92.00
[/PECAS]'
        ],

        [
            'Rodizio de Pneus e Verificacao de Alinhamento',
            20000,
            '24',
            150.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 45 minutos]

Execucao de rodizio dos pneus 175/65 R15 (medida padrao Mobi) conforme padrao cruz ou paralelo. Verificacao de pressao (30 PSI dianteiros e traseiros sem carga). Inspecao de desgaste irregular indicando necessidade de alinhamento/balanceamento. Verificacao de cortes, bolhas, deformacoes na banda e laterais. Alinhamento 3D das rodas dianteiras se necessario. Balanceamento eletronico das 4 rodas.

**Criticidade:** MEDIA - Impacta seguranca, conforto e durabilidade.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 35%, vibracoes no volante e carroceria, perda de estabilidade direcional, aumento no consumo de combustivel em ate 8%, perda de aderencia em piso molhado aumentando risco de aquaplanagem.

[PECAS]
SIMILAR|PESO-BAL-5G|Universal|Peso de Balanceamento Adesivo|50G|12.00
SIMILAR|PESO-BAL-10G|Universal|Peso de Balanceamento Clip-on|100G|18.00
[/PECAS]'
        ],

        [
            'Limpeza do Sistema de Injecao Eletronica',
            20000,
            '24',
            50.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 60 minutos]

Limpeza preventiva dos bicos injetores multiponto atraves de aditivo de alta qualidade aplicado no tanque de combustivel. Motor Fire Evo 1.0 Flex possui 4 bicos injetores que podem acumular depositos carboniferos (principalmente com uso de etanol de baixa qualidade). Procedimento: abastecer tanque com gasolina aditivada, adicionar produto limpador de injetores, rodar em rodovia para facilitar limpeza.

**Criticidade:** MEDIA - Preventiva para manter desempenho.

**Consequencias de nao fazer:** Perda gradual de potencia, aumento no consumo em ate 15%, marcha lenta irregular, dificuldade na partida a frio, engasgos, formacao de depositos no coletor de admissao, necessidade de limpeza ultrassonica (R$ 400 a R$ 600).

[PECAS]
SIMILAR|FLEX-CLEAN|Wynns|Aditivo Limpador Sistema Flex|325ML|45.00
SIMILAR|INJ-CLEAN|Wurth|Limpador Injetores Flex|300ML|38.00
SIMILAR|TOP-CLEAN|Bardahl|Limpador Sistema Combustivel|200ML|42.00
[/PECAS]'
        ],

        // ==================== REVISAO 30.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            30000,
            '36',
            135.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
ORIGINAL|51836363|Filtro Ar Motor Fiat Mobi 1.0|1|95.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|ARL4152|Tecfil|Filtro Ar Mobi 1.0 Fire Evo|1|42.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
[/PECAS]'
        ],

        [
            'Troca de Fluido de Freio DOT 4',
            30000,
            '24',
            100.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico. Fluido higroscopico absorve umidade do ar reduzindo ponto de ebulicao e causando perda de eficiencia. Procedimento: sangria de todas as rodas iniciando pela mais distante do cilindro mestre (traseira direita, traseira esquerda, dianteira direita, dianteira esquerda). Capacidade aproximada: 500ml. Utilizar apenas fluido DOT 4 homologado FMVSS 116. **Intervalo critico: a cada 2 anos independente da quilometragem.**

**Criticidade:** ALTA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Fluido contaminado com umidade causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico (cilindros mestre e roda, pincas), necessidade de substituicao completa do sistema.

[PECAS]
ORIGINAL|71748645|Fluido de Freio DOT 4 Fiat|500ML|45.00
SIMILAR|DOT4-500ML|Bosch|Fluido Freio DOT 4|500ML|28.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|32.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|500ML|26.00
SIMILAR|DOT4-ATE|ATE|Fluido Freio Super DOT 4|500ML|34.00
[/PECAS]'
        ],

        [
            'Inspecao do Sistema de Freios',
            30000,
            '36',
            90.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 40 minutos]

Inspecao detalhada do sistema de freios: medicao de espessura das pastilhas dianteiras (minimo 3mm), lonas traseiras (minimo 2mm), estado dos discos de freio dianteiros (espessura, empenamento, sulcos), tambores traseiros, cilindros de roda, tubulacoes rigidas e flexiveis, nivel e aparencia do fluido de freio. **ATENCAO RECALL:** Verificar freio de estacionamento (campanha recall 2024-2025) e interruptor de luz de freio (campanha 2016-2020). Teste funcional em pista.

**Criticidade:** CRITICA - Sistema de seguranca primaria.

**Consequencias de nao fazer:** Falha total ou parcial do sistema causando acidentes graves, desgaste de componentes alem do limite causando danos ao disco/tambor, aumento da distancia de frenagem, perda de eficiencia em frenagens de emergencia.

[PECAS]
Pecas substituidas apenas se identificada necessidade na inspecao
[/PECAS]'
        ],

        // ==================== REVISAO 40.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            40000,
            '48',
            135.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
ORIGINAL|51836363|Filtro Ar Motor Fiat Mobi 1.0|1|95.00
ORIGINAL|51806073|Filtro Combustivel Fiat Mobi 1.0|1|85.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|ARL4152|Tecfil|Filtro Ar Mobi 1.0 Fire Evo|1|42.00
SIMILAR|GI04/7|Tecfil|Filtro Combustivel Mobi 1.0|1|38.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            40000,
            '48',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Segunda troca das velas de ignicao conforme especificacoes da revisao de 20.000 km.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|55228404|Jogo Velas Ignicao Fiat Mobi 1.0 Fire|4|185.00
SIMILAR|F000KE0P07|Bosch|Jogo 4 Velas Ignicao Mobi 1.0|4|95.00
SIMILAR|BKR6E|NGK|Jogo 4 Velas Ignicao Mobi Fire 1.0|4|88.00
[/PECAS]'
        ],

        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            90.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 50 minutos]

Substituicao da correia do alternador (correia poly-V ou multi-V). Motor Fire Evo 1.0 utiliza correia unica acionando alternador e bomba de direcao (quando equipado, senao direcao e eletrica). Verificacao do estado do tensionador automatico, polia lisa e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao.

**Criticidade:** MEDIA - Correia desgastada pode romper causando pane.

**Consequencias de nao fazer:** Rompimento da correia durante operacao causando descarregamento da bateria, perda de direcao assistida (se hidraulica), possivel superaquecimento se houver sobrecarga eletrica prolongada.

[PECAS]
ORIGINAL|55271508|Correia Alternador Fiat Mobi 1.0|1|95.00
SIMILAR|4PK855|Gates|Correia Poly-V Alternador Mobi|1|42.00
SIMILAR|4PK855-CONT|Continental|Correia Poly-V Mobi 1.0|1|38.00
SIMILAR|K040855|Dayco|Correia Alternador Mobi 1.0|1|40.00
SIMILAR|4PK855-GY|Goodyear|Correia Auxiliar Mobi Fire|1|36.00
[/PECAS]'
        ],

        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            150.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas). Sistema de freios Teves com discos solidos. Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas, verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos (minimo gravado no disco). Sangria se necessario. Teste em pista.

**Criticidade:** ALTA - Sistema de seguranca primaria.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos nos discos, perda de eficiencia de frenagem, ruido metalico, aumento da distancia de frenagem em ate 40%, necessidade de substituicao prematura dos discos, risco de acidentes.

[PECAS]
ORIGINAL|77367764|Jogo Pastilhas Freio Diant Fiat Mobi|1|185.00
SIMILAR|N1770|Cobreq|Jogo Pastilhas Freio Diant Mobi 1.0|1|85.00
SIMILAR|HQJ2279|Jurid|Jogo Pastilhas Freio Diant Mobi|1|92.00
SIMILAR|HI1770|Fras-le|Jogo Pastilhas Freio Diant Mobi|1|88.00
SIMILAR|BB1770|Bosch|Jogo Pastilhas Freio Diant Mobi|1|95.00
[/PECAS]'
        ],

        // ==================== REVISAO 50.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            50000,
            '60',
            135.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
[/PECAS]'
        ],

        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            120.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor Fire Evo 1.0. Fiat recomenda fluido Paraflu (aditivo de longa duracao) diluido 50/50 com agua desmineralizada. Capacidade total do sistema: aproximadamente 5 litros da mistura. Procedimento: drenagem pelo bujao do radiador, lavagem interna com agua, reabastecimento da mistura, sangria do sistema, funcionamento ate atingir temperatura normal, verificacao de vazamentos e nivel.

**Criticidade:** ALTA - Fluido degradado perde propriedades anticorrosivas.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do radiador, bloco, cabecote e bomba d agua, formacao de borra e depositos reduzindo eficiencia de troca termica, superaquecimento, danos ao radiador, bomba d agua (R$ 250 a R$ 450) e motor.

[PECAS]
ORIGINAL|71775127|Aditivo Radiador Paraflu Fiat|3L|95.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|3L|18.00
SIMILAR|PARAFLU-UP|Repsol|Aditivo Radiador Longa Duracao|3L|58.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico|3L|62.00
SIMILAR|RAD-PROTEC|Valvoline|Aditivo Radiador Universal|3L|55.00
[/PECAS]'
        ],

        [
            'Higienizacao Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            160.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 80 minutos]

Limpeza profissional do sistema de ar condicionado: aplicacao de espuma higienizadora no evaporador atraves da caixa de ar, aspiracao da espuma e residuos, aplicacao de bactericida/fungicida por nebulizacao, limpeza do dreno do evaporador (frequentemente entupido), troca do filtro de cabine. Verificacao de pressao do gas refrigerante R-134a, teste de vazamentos, temperatura de saida (deve atingir 5-8C).

**Criticidade:** BAIXA - Conforto e qualidade do ar.

**Consequencias de nao fazer:** Proliferacao de fungos e bacterias no evaporador, mau cheiro persistente (odor de mofo), alergias respiratorias, obstrucao do dreno causando infiltracao de agua no assoalho, reducao da eficiencia do sistema.

[PECAS]
ORIGINAL|52046268|Filtro Ar Condicionado Fiat Mobi|1|82.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado|500ML|48.00
SIMILAR|KLIMACLEAN|Wynns|Limpador Ar Condicionado Automotivo|500ML|55.00
SIMILAR|ACP906|Tecfil|Filtro Cabine Mobi 1.0|1|35.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM - CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '72',
            135.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
ORIGINAL|51836363|Filtro Ar Motor Fiat Mobi 1.0|1|95.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
[/PECAS]'
        ],

        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT COMPLETO (CRITICO)',
            60000,
            '48',
            650.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 240 minutos]

**ITEM CRITICO DE SEGURANCA DO MOTOR.** Substituicao obrigatoria da correia dentada de sincronismo, tensor automatico, polia tensora e bomba d agua. O motor Fire Evo 1.0 8V e do tipo INTERFERENTE: caso a correia se rompa, os pistoes colidem com as valvulas causando danos catastroficos. Manual Fiat recomenda troca aos 60.000 km OU 48 meses (4 anos), **o que ocorrer primeiro**.

**ATENCAO:** Em veiculos com uso urbano intenso, parados por longos periodos, ou regioes muito quentes, considerar troca aos 48 meses mesmo sem atingir 60.000 km, pois a borracha resseca. Procedimento exige ferramentas especiais de sincronismo. Substituir bomba d agua preventivamente (acionada pela correia).

**ATENCAO: FALHA CAUSA DANOS CATASTROFICOS AO MOTOR**

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e valvulas (motor interferente), empenamento/quebra de valvulas, danos aos pistoes e cabecote, necessidade de retifica completa do motor. **CUSTO DE REPARO: R$ 6.000 a R$ 12.000**. Esta e a falha mecanica mais cara que pode ocorrer no veiculo.

[PECAS]
ORIGINAL|55263125|Correia Dentada Motor Fire 1.0|1|285.00
ORIGINAL|55263126|Tensor Automatico Correia Dentada|1|420.00
ORIGINAL|55263127|Polia Tensora Correia Dentada|1|195.00
ORIGINAL|55270853|Bomba D Agua Motor Fire 1.0|1|380.00
SIMILAR|CT1096|Gates|Correia Dentada Fire 1.0 Mobi|1|165.00
SIMILAR|TB196|Dayco|Correia Dentada Mobi 1.0|1|172.00
SIMILAR|T43196|Gates|Tensor Automatico Fire 1.0|1|245.00
SIMILAR|TP43196|Continental|Tensor Correia Dentada Mobi|1|235.00
SIMILAR|PA7196|Nakata|Polia Tensora Fire 1.0|1|115.00
SIMILAR|WP196|Nakata|Bomba D Agua Fire 1.0 8V|1|195.00
SIMILAR|PA196|Urba|Bomba D Agua Mobi 1.0|1|215.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            60000,
            '72',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Terceira troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|55228404|Jogo Velas Ignicao Fiat Mobi 1.0 Fire|4|185.00
SIMILAR|F000KE0P07|Bosch|Jogo 4 Velas Ignicao Mobi 1.0|4|95.00
SIMILAR|BKR6E|NGK|Jogo 4 Velas Ignicao Mobi Fire 1.0|4|88.00
[/PECAS]'
        ],

        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            180.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 90 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio dianteiros (2 pecas). Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada no disco (geralmente 9mm). Sangria do sistema. Teste em pista. **Discos devem ser substituidos em par sempre.**

**Criticidade:** CRITICA - Sistema de seguranca primaria.

**Consequencias de nao fazer:** Discos desgastados alem do limite causam frenagem ineficiente, trepidacao, empenamento, trincas termicas, possivel ruptura em frenagens de emergencia, acidente grave.

[PECAS]
ORIGINAL|77367764|Jogo Pastilhas Freio Diant Fiat Mobi|1|185.00
ORIGINAL|51887389|Par Discos Freio Diant Fiat Mobi|2|420.00
SIMILAR|N1770|Cobreq|Jogo Pastilhas Freio Diant Mobi 1.0|1|85.00
SIMILAR|HQJ2279|Jurid|Jogo Pastilhas Freio Diant Mobi|1|92.00
SIMILAR|DF2770|Fremax|Par Discos Freio Solido Mobi|2|245.00
SIMILAR|RC2770|Cobreq|Par Discos Freio Diant Mobi|2|235.00
SIMILAR|BD2770|TRW|Par Discos Freio Mobi 1.0|2|255.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            80000,
            '96',
            135.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
ORIGINAL|51836363|Filtro Ar Motor Fiat Mobi 1.0|1|95.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|ARL4152|Tecfil|Filtro Ar Mobi 1.0 Fire Evo|1|42.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            80000,
            '96',
            80.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Quarta troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|55228404|Jogo Velas Ignicao Fiat Mobi 1.0 Fire|4|185.00
SIMILAR|F000KE0P07|Bosch|Jogo 4 Velas Ignicao Mobi 1.0|4|95.00
SIMILAR|BKR6E|NGK|Jogo 4 Velas Ignicao Mobi Fire 1.0|4|88.00
[/PECAS]'
        ],

        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            200.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 120 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores de freio traseiros. Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos. Retifica ou substituicao dos tambores conforme diametro interno. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm.

**Criticidade:** ALTA

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro, desbalanceamento da frenagem, freio de estacionamento inoperante (problema de recall do modelo).

[PECAS]
ORIGINAL|77365321|Jogo Lonas Freio Traseiro Fiat Mobi|1|165.00
ORIGINAL|51938847|Par Tambores Freio Traseiro Fiat Mobi|2|385.00
SIMILAR|HI1821|Fras-le|Jogo Lonas Freio Traseiro Mobi|1|78.00
SIMILAR|N1821|Cobreq|Jogo Lonas Freio Traseiro Mobi|1|72.00
SIMILAR|TT2821|TRW|Par Tambores Freio Traseiro Mobi|2|225.00
SIMILAR|RT2821|Fremax|Par Tambores Freio Traseiro Mobi|2|215.00
[/PECAS]'
        ],

        [
            'Substituicao de Amortecedores Dianteiros e Traseiros',
            80000,
            '96',
            280.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 150 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo telescopico) incluindo kits de reparo (coxins superiores, batentes, coifas). Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar repetidamente cada canto do veiculo, deve retornar a posicao sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento apos a troca.

**Criticidade:** ALTA - Impacta seguranca, estabilidade e conforto.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem, perda de estabilidade em curvas, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas), desconforto aos ocupantes.

[PECAS]
ORIGINAL|51993254|Amortecedor Dianteiro Fiat Mobi|2|620.00
ORIGINAL|51993255|Amortecedor Traseiro Fiat Mobi|2|580.00
SIMILAR|HG33421|Monroe|Amortecedor Diant Mobi Gas|2|385.00
SIMILAR|HG33422|Monroe|Amortecedor Tras Mobi Gas|2|365.00
SIMILAR|AM33421|Cofap|Amortecedor Diant Mobi Turbogas|2|325.00
SIMILAR|AM33422|Cofap|Amortecedor Tras Mobi Turbogas|2|305.00
SIMILAR|N33421|Nakata|Amortecedor Diant Mobi 1.0|2|295.00
SIMILAR|N33422|Nakata|Amortecedor Tras Mobi 1.0|2|275.00
[/PECAS]'
        ],

        [
            'Substituicao de Correias Auxiliares',
            80000,
            '96',
            90.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 50 minutos]

Segunda troca da correia do alternador conforme especificacoes da revisao de 40.000 km.

**Criticidade:** MEDIA

[PECAS]
ORIGINAL|55271508|Correia Alternador Fiat Mobi 1.0|1|95.00
SIMILAR|4PK855|Gates|Correia Poly-V Alternador Mobi|1|42.00
SIMILAR|4PK855-CONT|Continental|Correia Poly-V Mobi 1.0|1|38.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            100000,
            '120',
            135.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55256049|Filtro de Oleo Motor Fiat Mobi 1.0|1|68.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
SIMILAR|PSL55|Tecfil|Filtro Oleo Mobi 1.0 Fire Evo|1|28.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
[/PECAS]'
        ],

        [
            'Substituicao da Bateria',
            100000,
            '48',
            40.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 25 minutos]

Substituicao da bateria automotiva 12V. Fiat Mobi 1.0 utiliza bateria de 45Ah ou 50Ah com corrente de partida (CCA) de 380A a 420A. Baterias seladas livre de manutencao tem vida util de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora.

**Criticidade:** MEDIA - Consumivel com vida util definida.

**Consequencias de nao fazer:** Falha de partida, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, oxidacao dos terminais, perda de memoria dos sistemas eletronicos, necessidade de reboque.

[PECAS]
ORIGINAL|71770301|Bateria 12V 45Ah Mopar Fiat|1|485.00
SIMILAR|45GD-420|Moura|Bateria 12V 45Ah 420A Selada|1|325.00
SIMILAR|45D-450|Heliar|Bateria 12V 45Ah 450A Free|1|335.00
SIMILAR|B45DH|Bosch|Bateria 12V 45Ah S4 Free|1|365.00
SIMILAR|45AH-400|Zetta|Bateria 12V 45Ah Selada|1|285.00
[/PECAS]'
        ],

        [
            'Limpeza/Descarbonizacao do Sistema de Admissao',
            100000,
            '120',
            250.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 120 minutos]

Limpeza profunda do sistema de admissao, corpo de borboleta eletronico (TBI), coletor de admissao e sensor MAP. Motor Fire Evo 1.0 Flex acumula depositos carboniferos no corpo de borboleta reduzindo desempenho. Procedimento: remocao e limpeza quimica do corpo de borboleta com produto especifico (spray limpa TBI), limpeza do coletor de admissao, limpeza dos sensores MAP e temperatura. Apos limpeza, realizar procedimento de reaprendizagem da marcha lenta com scanner (se necessario).

**Criticidade:** MEDIA - Preventiva para manter desempenho.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 12%, aumento no consumo de combustivel em ate 15%, marcha lenta irregular, engasgos, luz do motor (check engine) acesa por falha na leitura dos sensores, aceleracao sem resposta (delay).

[PECAS]
SIMILAR|TBI-CLEAN|Wynns|Limpador Corpo Borboleta|400ML|52.00
SIMILAR|CARB-CLEAN|Wurth|Limpador TBI/Admissao|500ML|48.00
SIMILAR|INTAKE-CLEAN|Bardahl|Limpador Sistema Admissao|300ML|55.00
[/PECAS]'
        ],

        // ==================== REVISAO 120.000 KM - SEGUNDA CORREIA DENTADA ====================
        [
            'Troca de Oleo, Filtros e Correia Dentada',
            120000,
            '96',
            785.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 290 minutos]

**SEGUNDA TROCA OBRIGATORIA DA CORREIA DENTADA.** Servico completo incluindo oleo, filtros e substituicao do kit completo de correia dentada conforme especificacoes da revisao de 60.000 km. Intervalo: 60.000 km ou 48 meses, o que ocorrer primeiro.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|55263125|Correia Dentada Motor Fire 1.0|1|285.00
ORIGINAL|55263126|Tensor Automatico Correia Dentada|1|420.00
ORIGINAL|55263127|Polia Tensora Correia Dentada|1|195.00
ORIGINAL|55270853|Bomba D Agua Motor Fire 1.0|1|380.00
ORIGINAL|71754237|Oleo Motor Mopar 5W-30 Sintetico|3L|195.00
SIMILAR|CT1096|Gates|Correia Dentada Fire 1.0 Mobi|1|165.00
SIMILAR|T43196|Gates|Tensor Automatico Fire 1.0|1|245.00
SIMILAR|WP196|Nakata|Bomba D Agua Fire 1.0 8V|1|195.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico|3L|105.00
[/PECAS]'
        ],

        // ==================== ITENS POR TEMPO ====================
        [
            'Verificacao e Substituicao de Pneus',
            50000,
            '60',
            60.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 90 minutos para jogo completo]

Fiat Mobi utiliza pneus 175/65 R15 (medida padrao). Vida util media: 40.000 a 50.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). Verificar mensalmente: pressao (30 PSI dianteiros e traseiros sem carga), desgaste da banda (minimo legal 1,6mm medido nos TWI - indicadores de desgaste), deformacoes, cortes laterais, data de fabricacao (codigo DOT nas laterais - formato semana/ano ex: 1520 = 15a semana de 2020). Realizar rodizio a cada 10.000 km.

**Criticidade:** CRITICA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 40%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves, multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular.

[PECAS]
SIMILAR|175/65R15-PIR|Pirelli|Pneu Cinturato P1 175/65 R15|4|1480.00
SIMILAR|175/65R15-BRI|Bridgestone|Pneu Turanza ER300 175/65 R15|4|1420.00
SIMILAR|175/65R15-GY|Goodyear|Pneu Kelly Edge Touring 175/65 R15|4|1280.00
SIMILAR|175/65R15-CONT|Continental|Pneu ContiPowerContact 175/65 R15|4|1380.00
SIMILAR|175/65R15-DUN|Dunlop|Pneu SP Touring R1 175/65 R15|4|1220.00
[/PECAS]'
        ],

        // ==================== PROBLEMAS CONHECIDOS E RECALLS ====================
        [
            'VERIFICACAO - Recall Freio de Estacionamento (2024-2025)',
            10000,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Recalls] [TEMPO: Verificacao 5 minutos]

**RECALL ATIVO:** Problema na alavanca do freio de estacionamento que pode nao funcionar corretamente, permitindo que o veiculo se mova sozinho em inclinacoes. Afeta modelos 2024 e 2025.

**PROCEDIMENTO:** Consultar site Fiat Recall (servicos.fiat.com.br/recall.html) com placa/chassi e agendar reparo gratuito em concessionaria autorizada.

**ATENCAO:** Reparo gratuito e obrigatorio por lei.

[PECAS]
Reparo gratuito em concessionaria - sem custo de pecas
[/PECAS]'
        ],

        [
            'VERIFICACAO - Recall Tubulacao de Combustivel (2022-2023)',
            10000,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Recalls] [TEMPO: Verificacao 5 minutos]

**RECALL ATIVO:** Possibilidade de degradacao da tubulacao de alimentacao do motor, especialmente com uso de etanol, com riscos de vazamento de combustivel e desligamento inesperado do motor. **RISCO DE INCENDIO.**

**SINAIS DE ALERTA:**
- Cheiro de combustivel na cabine ou garagem
- Motor morrendo sem motivo
- Dificuldade na partida

**PROCEDIMENTO:** Verificar se veiculo esta incluido no recall e realizar substituicao imediata.

[PECAS]
Reparo gratuito em concessionaria - sem custo de pecas
[/PECAS]'
        ],

        [
            'VERIFICACAO - Recall Interruptor de Luz de Freio (2016-2020)',
            10000,
            '12',
            0.00,
            'Alta',
            '[CATEGORIA: Recalls] [TEMPO: Verificacao 5 minutos]

**RECALL:** Circuito eletrico do interruptor de freio abaixo do especificado para a corrente eletrica, provocando sobrecarga e comprometendo acendimento das luzes de freio. Afeta 192.534 unidades.

**SINAIS DE ALERTA:**
- Luzes de freio nao acendem
- Fusivel queimando frequentemente
- Odor de queimado no interruptor do pedal

**PROCEDIMENTO:** Verificar recall e substituir interruptor gratuitamente em concessionaria.

[PECAS]
Reparo gratuito em concessionaria - sem custo de pecas
[/PECAS]'
        ],

        [
            'VERIFICACAO - Recall Pedal do Acelerador (2025)',
            10000,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Recalls] [TEMPO: Verificacao 5 minutos]

**RECALL ATIVO:** Problema no conector eletrico do pedal do acelerador com possivel mau contato causando perda repentina de aceleracao durante conducao. Aumenta risco de acidentes.

**SINAIS DE ALERTA:**
- Perda subita de aceleracao
- Luz do motor acesa
- Modo de seguranca (limp mode) ativado

**PROCEDIMENTO:** Verificar se veiculo esta incluido e realizar reparo imediato.

[PECAS]
Reparo gratuito em concessionaria - sem custo de pecas
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
