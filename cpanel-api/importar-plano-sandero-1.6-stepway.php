<?php
/**
 * Script para importar Plano de Manutencao Renault Sandero 1.6 Stepway 2013-2014
 * Gerado via Perplexity AI em 2026-01-15
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-sandero-1.6-stepway.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-sandero-1.6-stepway.php?confirmar=SIM'
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
    $modeloNome = "SANDERO 1.6 STEPWAY";

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
            110.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Drenagem completa do oleo lubrificante do motor K4M 1.6 16V atraves do bujao do carter. Substituicao do filtro de oleo tipo cartucho codigo Renault 8200768927 ou equivalente Fram PH6607, Wega WO200, Tecfil PSL968. Reabastecimento com oleo sintetico especificacao SAE 5W-30 ou 5W-40 API SN, ACEA A5/B5, RN 0700. Capacidade: 4,3-4,5 litros com filtro. Motor K4M de 110 cv com 16 valvulas requer oleo sintetico de baixa viscosidade para maxima protecao e economia de combustivel. Criterio: o que ocorrer primeiro (10.000 km OU 12 meses).

**Criticidade:** CRITICA - Lubrificacao essencial para vida util do motor 16V.

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado de pistoes, bronzinas, eixo comando de valvulas e tuchos hidraulicos, acumulo de borra (especialmente com etanol), oxidacao interna, superaquecimento, perda de eficiencia em ate 18%, perda de garantia de fabrica, possivel travamento ou quebra do motor exigindo retifica completa (R$ 9.000 a R$ 15.000).

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|PSL968|Tecfil|Filtro Oleo Renault K4M|1|26.00
SIMILAR|PH6607|Fram|Filtro Oleo Sandero 1.6|1|30.00
SIMILAR|OC295|Mahle|Filtro Oleo Renault 1.6 16V|1|32.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
SIMILAR|5W30-SHELL|Shell|Oleo Helix HX7 5W-30 Semissintetico|5L|165.00
SIMILAR|5W40-MOBIL|Mobil|Oleo Super 3000 X1 5W-40 Sintetico|5L|168.00
SIMILAR|5W40-PETRONAS|Petronas|Oleo Syntium 5W-40 API SN|5L|158.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            30.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 12 minutos]

Substituicao do elemento filtrante de ar do motor K4M 1.6 16V localizado na caixa de ar. Codigo original Renault 8200431051, codigo Wega FAP3740, Tecfil ARL3740, Fram CA11135. Filtro retem particulas solidas impedindo entrada no coletor de admissao e camara de combustao. Motor 16V com injecao eletronica multiponto requer fluxo de ar limpo para perfeita mistura ar/combustivel, especialmente importante no uso com etanol que requer maior volume de ar. Verificar estado da vedacao e limpeza interna da caixa de ar.

**Criticidade:** ALTA - Filtro saturado reduz potencia e aumenta consumo.

**Consequencias de nao fazer:** Reducao de potencia em ate 10%, aumento no consumo de combustivel em ate 14% (mais critico com etanol), entrada de particulas abrasivas causando desgaste dos cilindros, pistoes e aneis, formacao de borra no coletor de admissao, sensor MAF/MAP sujo causando falhas de injecao, marcha lenta irregular.

[PECAS]
ORIGINAL|8200431051|Filtro Ar Motor Renault Sandero 1.6 16V|1|115.00
SIMILAR|FAP3740|Wega|Filtro Ar Sandero Logan 1.6|1|42.00
SIMILAR|ARL3740|Tecfil|Filtro Ar Renault K4M 1.6|1|45.00
SIMILAR|CA11135|Fram|Filtro Ar Sandero 1.6|1|48.00
SIMILAR|LX2846|Mahle|Filtro Ar Renault Sandero|1|50.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            55.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao do filtro de combustivel do sistema de injecao eletronica multiponto. Codigo original Renault 7700845973, codigo Wega FCI1658, Tecfil GI04/8, Mann WK612/5. Filtro tipo inline instalado na linha de combustivel. Motor Flex e extremamente sensivel a combustivel de baixa qualidade, especialmente etanol com agua. ATENCAO: Verificar bomba de combustivel - problema comum no Sandero. Despressurizar o sistema antes da remocao (retirar fusivel da bomba, dar partida ate motor morrer).

**Criticidade:** ALTA - Sistema de injecao + problema comum de bomba.

**Consequencias de nao fazer:** Entupimento dos bicos injetores (mais frequente com etanol), falha na partida, perda de potencia, aumento no consumo em ate 20%, marcha lenta irregular, engasgos, agravamento do problema da bomba de combustivel, necessidade de limpeza ultrassonica dos injetores (R$ 380 a R$ 600).

[PECAS]
ORIGINAL|7700845973|Filtro Combustivel Renault Sandero|1|88.00
SIMILAR|FCI1658|Wega|Filtro Combustivel Sandero Logan|1|32.00
SIMILAR|GI04/8|Tecfil|Filtro Combustivel Renault 1.6|1|30.00
SIMILAR|WK612/5|Mann|Filtro Combustivel Sandero|1|35.00
SIMILAR|G6860|Fram|Filtro Combustivel Renault|1|34.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            45.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 18 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas. Codigo original Renault 272775374R, codigo Wega AKX1399. Filtro tipo particulado/carvao ativado retem poeira, polen, bacterias, fuligem e odores externos. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador. Recomenda-se higienizacao do sistema com spray antibacteriano durante a troca.

**Criticidade:** MEDIA - Impacta qualidade do ar e eficiencia do sistema.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine (odor de mofo), reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 350 a R$ 550).

[PECAS]
ORIGINAL|272775374R|Filtro Ar Condicionado Renault Sandero|1|125.00
SIMILAR|AKX1399|Wega|Filtro Cabine Sandero Stepway 2014-2020|1|35.00
SIMILAR|ACP1399|Tecfil|Filtro Cabine Renault Sandero|1|38.00
SIMILAR|CF1399|Fram|Filtro Ar Condicionado Sandero|1|42.00
SIMILAR|LA1399|Mahle|Filtro Cabine Sandero Logan|1|44.00
[/PECAS]'
        ],
        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            120.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 55 minutos]

Inspecao visual e funcional completa conforme manual Renault: verificacao de niveis de fluidos (arrefecimento, freio, direcao hidraulica, limpador), teste de luzes externas/internas, buzina, limpadores, travas eletricas; inspecao de pneus (pressao 32 PSI dianteiros/30 PSI traseiros conforme manual, desgaste, banda minima 1,6mm), freios (pastilhas, discos, tubulacoes), suspensao (amortecedores, buchas, batentes), direcao hidraulica, escapamento, bateria (terminais, carga), correias, velas. ATENCAO ESPECIAL: Verificar recall do airbag para modelos 2014. Verificar sistema eletrico - problema comum de falhas eletricas e rele do eletroventilador.

**Criticidade:** ALTA - Detecta problemas e verifica recall critico.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos ou recall do airbag, falhas eletricas nao diagnosticadas, acidentes por falha de freios ou pneus, perda de direcao hidraulica, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular.

[PECAS]
Nao requer pecas de substituicao obrigatorias (apenas eventuais reposicoes identificadas)
[/PECAS]'
        ],

        // ==================== REVISAO 20.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            20000,
            '24',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo incluindo oleo do motor, filtros de oleo, ar, combustivel e ar condicionado conforme especificacoes da revisao de 10.000 km.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
ORIGINAL|8200431051|Filtro Ar Motor Renault Sandero 1.6 16V|1|115.00
ORIGINAL|7700845973|Filtro Combustivel Renault Sandero|1|88.00
ORIGINAL|272775374R|Filtro Ar Condicionado Renault Sandero|1|125.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|PSL968|Tecfil|Filtro Oleo Renault K4M|1|26.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
SIMILAR|FAP3740|Wega|Filtro Ar Sandero Logan 1.6|1|42.00
SIMILAR|FCI1658|Wega|Filtro Combustivel Sandero Logan|1|32.00
SIMILAR|AKX1399|Wega|Filtro Cabine Sandero Stepway 2014-2020|1|35.00
[/PECAS]'
        ],
        [
            'Rodizio de Pneus e Alinhamento',
            20000,
            '24',
            150.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 50 minutos]

Execucao de rodizio dos pneus 205/50 R17 (Stepway) seguindo padrao paralelo ou cruz. Pressao recomendada: 32 PSI dianteiros/30 PSI traseiros conforme manual. Verificacao de pressao, inspecao de desgaste irregular indicando necessidade de alinhamento. Verificacao de cortes, bolhas, deformacoes, data de fabricacao (codigo DOT). Alinhamento 3D das rodas dianteiras e traseiras (Stepway possui regulagem traseira). Balanceamento eletronico das 4 rodas.

**Criticidade:** MEDIA - Impacta seguranca, conforto e durabilidade.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 40%, vibracoes no volante, perda de estabilidade direcional, aumento no consumo de combustivel em ate 10%, perda de aderencia em piso molhado aumentando risco de aquaplanagem, desgaste irregular da direcao hidraulica.

[PECAS]
SIMILAR|PESO-BAL-5G|Universal|Peso de Balanceamento Adesivo|50G|12.00
SIMILAR|PESO-BAL-10G|Universal|Peso de Balanceamento Clip-on|100G|18.00
[/PECAS]'
        ],

        // ==================== REVISAO 30.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            30000,
            '36',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
ORIGINAL|8200431051|Filtro Ar Motor Renault Sandero 1.6 16V|1|115.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
SIMILAR|FAP3740|Wega|Filtro Ar Sandero Logan 1.6|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            30000,
            '24',
            105.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico com ABS. Fluido higroscopico absorve umidade do ar reduzindo ponto de ebulicao e causando perda de eficiencia. Procedimento: sangria de todas as rodas e modulo ABS iniciando pela mais distante do cilindro mestre (traseira direita, traseira esquerda, dianteira direita, dianteira esquerda). Capacidade aproximada: 500ml. Utilizar apenas fluido DOT 4 homologado FMVSS 116. Intervalo critico: a cada 2 anos independente da quilometragem.

**Criticidade:** ALTA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Fluido contaminado com umidade causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico (cilindros mestre e roda, pincas, modulo ABS), necessidade de substituicao completa do sistema, falha do ABS, acidentes graves.

[PECAS]
ORIGINAL|7711575504|Fluido de Freio DOT 4 Renault Original|500ML|55.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|30.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|35.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|500ML|28.00
SIMILAR|DOT4-ATE|ATE|Fluido Freio Super DOT 4|500ML|38.00
[/PECAS]'
        ],
        [
            'Limpeza do Sistema de Injecao Eletronica',
            30000,
            '36',
            50.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 65 minutos]

Limpeza preventiva dos bicos injetores multiponto atraves de aditivo de alta qualidade aplicado no tanque de combustivel. Motor K4M 1.6 16V possui 4 bicos injetores que podem acumular depositos carboniferos especialmente com uso de etanol de baixa qualidade ou adulterado. ATENCAO: Falhas de injecao sao problema comum no Sandero. Procedimento: abastecer tanque com gasolina aditivada, adicionar produto limpador de injetores especifico para Flex, rodar em rodovia por pelo menos 50 km. Em casos severos, realizar limpeza por ultrassom em oficina especializada.

**Criticidade:** ALTA - Preventiva + problema comum de falhas.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 14%, aumento no consumo em ate 18%, marcha lenta irregular, dificuldade na partida a frio com etanol, engasgos, luz de injecao no painel, formacao de depositos no coletor de admissao, necessidade de limpeza ultrassonica (R$ 380 a R$ 600).

[PECAS]
SIMILAR|FLEX-CLEAN|Wynns|Aditivo Limpador Sistema Flex|325ML|45.00
SIMILAR|INJ-CLEAN|Wurth|Limpador Injetores Flex|300ML|38.00
SIMILAR|TOP-CLEAN|Bardahl|Limpador Sistema Combustivel|200ML|42.00
[/PECAS]'
        ],

        // ==================== REVISAO 40.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            40000,
            '48',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
ORIGINAL|8200431051|Filtro Ar Motor Renault Sandero 1.6 16V|1|115.00
ORIGINAL|7700845973|Filtro Combustivel Renault Sandero|1|88.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
SIMILAR|FAP3740|Wega|Filtro Ar Sandero Logan 1.6|1|42.00
SIMILAR|FCI1658|Wega|Filtro Combustivel Sandero Logan|1|32.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            40000,
            '48',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Substituicao das 4 velas de ignicao do motor K4M 1.6 16V. Especificacoes: NGK BKR6E-11 ou Bosch FR7DCX+ (velas de platina/iridio), gap 1,1mm, rosca 14mm. Motor 16V com alta taxa de compressao e injecao multiponto requer velas de alta qualidade resistentes a corrosao do etanol. Limpar bem a regiao antes da remocao para evitar entrada de sujeira nos cilindros. Aplicar torque de aperto de 20-25 Nm. Verificar cor dos eletrodos (marrom claro = ideal).

**Criticidade:** ALTA - Velas desgastadas causam falhas de combustao.

**Consequencias de nao fazer:** Dificuldade na partida especialmente com etanol, falhas de ignicao (motor falhando/trepidando), perda de potencia em ate 18%, aumento no consumo de combustivel em ate 25%, marcha lenta irregular, engasgos, emissoes poluentes elevadas, possivel danificacao do catalisador (R$ 1.800 a R$ 3.400).

[PECAS]
ORIGINAL|7700500155|Jogo 4 Velas Ignicao Renault Sandero 1.6|4|245.00
SIMILAR|BKR6E11|NGK|Jogo 4 Velas Sandero 1.6 16V Flex|4|125.00
SIMILAR|FR7DCX+|Bosch|Jogo 4 Velas Platina Sandero K4M|4|135.00
SIMILAR|RC7PYC|Champion|Jogo 4 Velas Renault 1.6 16V|4|118.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            155.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas) sistema Teves/ATE. Codigo Cobreq N-457, Jurid HQJ2302/HQJ2319, Fras-le PD338/PD1483, original 410605612R. Freios a disco dianteiro (5 furos, diametro 280mm). Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas (Ceratec ou Molykote), verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos. Sangria se necessario. Teste em pista.

**Criticidade:** ALTA - Sistema de seguranca primaria.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos profundos nos discos, perda de eficiencia de frenagem em ate 45%, ruido metalico intenso, aumento da distancia de frenagem, necessidade de substituicao prematura dos discos, falha do ABS, risco de acidentes graves.

[PECAS]
ORIGINAL|410605612R|Jogo Pastilhas Freio Diant Renault Sandero|1|225.00
SIMILAR|N457|Cobreq|Jogo Pastilhas Freio Diant Sandero Logan|1|95.00
SIMILAR|HQJ2302|Jurid|Jogo Pastilhas Freio Diant Sandero|1|98.00
SIMILAR|HQJ2319|Jurid|Jogo Pastilhas Freio Diant Renault|1|98.00
SIMILAR|PD338|Fras-le|Jogo Pastilhas Freio Diant Sandero 1.6|1|92.00
SIMILAR|PD1483|Fras-le|Jogo Pastilhas Freio Diant Renault|1|92.00
SIMILAR|BB2302|Bosch|Jogo Pastilhas Freio Diant Sandero|1|105.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            90.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Substituicao da correia poly-V do alternador/acessorios. Motor K4M 1.6 16V utiliza correia 6PK1193 acionando alternador, bomba de direcao hidraulica e compressor do ar condicionado. Verificacao do tensionador automatico, polias e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao. Tensionamento adequado conforme especificacao do fabricante. ATENCAO: Verificar problema comum de rele do eletroventilador.

**Criticidade:** MEDIA - Correia desgastada pode romper.

**Consequencias de nao fazer:** Rompimento da correia causando descarregamento da bateria, perda do ar condicionado, perda de direcao hidraulica assistida, luz de bateria no painel, agravamento do problema do eletroventilador, possivel superaquecimento, necessidade de guincho.

[PECAS]
ORIGINAL|8200833439|Correia Alternador Renault Sandero 1.6|1|115.00
SIMILAR|6PK1193|Gates|Correia Poly-V Alternador Sandero|1|48.00
SIMILAR|6PK1193-CONT|Continental|Correia Poly-V Sandero 1.6|1|45.00
SIMILAR|K061193|Dayco|Correia Alternador Sandero K4M|1|47.00
SIMILAR|6PK1193-GY|Goodyear|Correia Auxiliar Sandero|1|42.00
[/PECAS]'
        ],

        // ==================== REVISAO 50.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            50000,
            '60',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            115.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor K4M 1.6. Renault recomenda Glaceol RX Type D (aditivo de longa duracao cor verde) diluido 50/50 com agua desmineralizada. Capacidade total: aproximadamente 6,0 litros da mistura. Procedimento: drenagem pelo bujao do radiador, lavagem interna com agua, reabastecimento da mistura, sangria do sistema (eliminacao de bolhas de ar), funcionamento ate atingir temperatura normal (ventoinha acionando), verificacao de vazamentos e nivel. ATENCAO: Verificar eletroventilador.

**Criticidade:** ALTA - Fluido degradado + problema de eletroventilador.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do radiador, bloco, cabecote e bomba dagua, formacao de borra e depositos reduzindo eficiencia de troca termica, superaquecimento, agravamento do problema do eletroventilador, danos ao radiador, bomba dagua (R$ 280 a R$ 450), termostato (R$ 120 a R$ 220) e motor.

[PECAS]
ORIGINAL|7711428132|Aditivo Radiador Glaceol RX Type D|2L|135.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|4L|24.00
SIMILAR|GLACEOL-LL|Repsol|Aditivo Radiador Longa Duracao|2L|72.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico|2L|75.00
SIMILAR|RAD-PROTEC|Valvoline|Aditivo Radiador Universal|2L|68.00
[/PECAS]'
        ],
        [
            'Higienizacao Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            185.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 85 minutos]

Limpeza profissional do sistema de ar condicionado: aplicacao de espuma higienizadora no evaporador atraves da caixa de ar, aspiracao da espuma e residuos, aplicacao de bactericida/fungicida por nebulizacao, limpeza do dreno do evaporador (frequentemente entupido), troca do filtro de cabine. Verificacao de pressao do gas refrigerante R-134a, teste de vazamentos com detector eletronico, temperatura de saida (deve atingir 4-7C). Teste de funcionamento do compressor, embreagem eletromagnetica e eletroventilador do condensador.

**Criticidade:** BAIXA - Conforto e qualidade do ar.

**Consequencias de nao fazer:** Proliferacao de fungos e bacterias no evaporador, mau cheiro persistente (odor de mofo), alergias respiratorias graves, obstrucao do dreno causando infiltracao de agua no assoalho e modulo eletronico, reducao da eficiencia do sistema em ate 40%.

[PECAS]
ORIGINAL|272775374R|Filtro Ar Condicionado Renault Sandero|1|125.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado|500ML|55.00
SIMILAR|KLIMACLEAN|Wynns|Limpador Ar Condicionado Automotivo|500ML|62.00
SIMILAR|AKX1399|Wega|Filtro Cabine Sandero Stepway 2014-2020|1|35.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM - CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '72',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
ORIGINAL|8200431051|Filtro Ar Motor Renault Sandero 1.6 16V|1|115.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
[/PECAS]'
        ],
        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT COMPLETO',
            60000,
            '48',
            650.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 210 minutos]

ITEM MAIS CRITICO DE MANUTENCAO DO MOTOR K4M. Substituicao obrigatoria da correia dentada de sincronismo, tensor automatico, polia louca e bomba dagua. Kit Contitech CT1126K1 ou INA 5300737100, codigo original Renault 7701474795. Motor K4M 1.6 16V e do tipo interferente: se a correia romper, os pistoes colidem com as 16 valvulas causando danos catastroficos. Manual Renault recomenda troca aos 60.000 km ou 4 anos. Procedimento exige ferramentas especiais de travamento (ponto morto superior PMS e comando de valvulas). OBRIGATORIO substituir tambem a bomba dagua preventivamente (acionada pela correia, economia de mao de obra).

**Criticidade:** CRITICA - FALHA CAUSA DANOS TOTAIS AO MOTOR

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e 16 valvulas (motor interferente), empenamento/quebra de todas as valvulas, danos severos aos pistoes, cabecote destruido, possivel quebra do bloco, necessidade de retifica completa do motor ou substituicao. CUSTO DE REPARO: R$ 10.000 a R$ 18.000. Esta e a falha mecanica mais cara que pode ocorrer no veiculo.

[PECAS]
ORIGINAL|7701474795|Kit Correia Dentada Renault Sandero 1.6|1|785.00
ORIGINAL|7701478505|Bomba Dagua Renault K4M 1.6|1|425.00
SIMILAR|CT1126K1|Contitech|Kit Correia Dentada Sandero Completo|1|585.00
SIMILAR|5300737100|INA|Kit Correia Dentada K4M 1.6|1|595.00
SIMILAR|TB1126|Dayco|Correia Dentada Sandero 1.6|1|195.00
SIMILAR|T41126|Gates|Tensor Automatico Sandero K4M|1|285.00
SIMILAR|WP1126|Nakata|Bomba Dagua Sandero 1.6 16V|1|195.00
SIMILAR|PA1126|Urba|Bomba Dagua Renault K4M|1|210.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            60000,
            '72',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Segunda troca das velas de ignicao (se primeira foi aos 40.000 km) ou primeira troca conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|7700500155|Jogo 4 Velas Ignicao Renault Sandero 1.6|4|245.00
SIMILAR|BKR6E11|NGK|Jogo 4 Velas Sandero 1.6 16V Flex|4|125.00
SIMILAR|FR7DCX+|Bosch|Jogo 4 Velas Platina Sandero K4M|4|135.00
[/PECAS]'
        ],
        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            195.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 100 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio dianteiros 5 furos, diametro 280mm. Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada no disco (geralmente 24mm). Sangria do sistema. Teste em pista. Discos devem ser substituidos em par sempre.

**Criticidade:** CRITICA - Sistema de seguranca primaria.

[PECAS]
ORIGINAL|410605612R|Jogo Pastilhas Freio Diant Renault Sandero|1|225.00
ORIGINAL|402063151R|Par Discos Freio Diant Renault Sandero|2|545.00
SIMILAR|N457|Cobreq|Jogo Pastilhas Freio Diant Sandero Logan|1|95.00
SIMILAR|HQJ2302|Jurid|Jogo Pastilhas Freio Diant Sandero|1|98.00
SIMILAR|DF2302|Fremax|Par Discos Freio Sandero|2|315.00
SIMILAR|RC2302|Cobreq|Par Discos Freio Diant Sandero|2|305.00
SIMILAR|BD2302|TRW|Par Discos Freio Sandero 1.6|2|325.00
[/PECAS]'
        ],

        // ==================== REVISAO 70.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            70000,
            '84',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            80000,
            '96',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
ORIGINAL|8200431051|Filtro Ar Motor Renault Sandero 1.6 16V|1|115.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
SIMILAR|FAP3740|Wega|Filtro Ar Sandero Logan 1.6|1|42.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            80000,
            '96',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Terceira troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|7700500155|Jogo 4 Velas Ignicao Renault Sandero 1.6|4|245.00
SIMILAR|BKR6E11|NGK|Jogo 4 Velas Sandero 1.6 16V Flex|4|125.00
SIMILAR|FR7DCX+|Bosch|Jogo 4 Velas Platina Sandero K4M|4|135.00
[/PECAS]'
        ],
        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            210.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 130 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores traseiros (sistema a tambor 203mm). Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos, cabo do freio de estacionamento. Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm. Sangria do sistema.

**Criticidade:** ALTA - Sistema de seguranca.

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro em ate 60%, desbalanceamento da frenagem, freio de estacionamento inoperante (reprovacao na inspecao), necessidade de substituicao dos tambores, acidentes por frenagem deficiente.

[PECAS]
ORIGINAL|440609572R|Jogo Lonas Freio Traseiro Renault Sandero|1|185.00
ORIGINAL|432000034R|Par Tambores Freio Traseiro Renault Sandero|2|425.00
SIMILAR|HI1220|Fras-le|Jogo Lonas Freio Traseiro Sandero|1|78.00
SIMILAR|N1220|Cobreq|Jogo Lonas Freio Traseiro Sandero|1|75.00
SIMILAR|TT2220|TRW|Par Tambores Freio Traseiro Sandero|2|245.00
SIMILAR|RT2220|Fremax|Par Tambores Freio Traseiro Sandero|2|235.00
[/PECAS]'
        ],
        [
            'Substituicao de Amortecedores',
            80000,
            '96',
            305.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 160 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo eixo de torcao) incluindo kits de reparo (coxins superiores, batentes, coifas). ATENCAO: Stepway possui suspensao elevada - usar amortecedores especificos para Stepway. Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar cada canto do veiculo, deve retornar sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento apos a troca.

**Criticidade:** ALTA - Impacta seguranca e estabilidade.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem em ate 20%, perda de estabilidade em curvas, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas), desconforto aos ocupantes, trepidacao, perda de controle.

[PECAS]
ORIGINAL|543028277R|Amortecedor Dianteiro Renault Stepway|2|685.00
ORIGINAL|562102894R|Amortecedor Traseiro Renault Stepway|2|625.00
SIMILAR|HG34120|Monroe|Amortecedor Diant Sandero Gas|2|395.00
SIMILAR|HG34121|Monroe|Amortecedor Tras Sandero Gas|2|375.00
SIMILAR|AM34120|Cofap|Amortecedor Diant Sandero Turbogas|2|335.00
SIMILAR|AM34121|Cofap|Amortecedor Tras Sandero Turbogas|2|315.00
SIMILAR|N34120|Nakata|Amortecedor Diant Sandero 1.6|2|295.00
SIMILAR|N34121|Nakata|Amortecedor Tras Sandero 1.6|2|275.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            80000,
            '96',
            90.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Segunda troca da correia do alternador conforme especificacoes da revisao de 40.000 km.

**Criticidade:** MEDIA

[PECAS]
ORIGINAL|8200833439|Correia Alternador Renault Sandero 1.6|1|115.00
SIMILAR|6PK1193|Gates|Correia Poly-V Alternador Sandero|1|48.00
SIMILAR|6PK1193-CONT|Continental|Correia Poly-V Sandero 1.6|1|45.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            100000,
            '120',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|8200768927|Filtro de Oleo Motor Renault Sandero 1.6|1|72.00
ORIGINAL|7711428132|Oleo Motor Elf Evolution 900 SXR 5W-30|5L|285.00
ORIGINAL|8200431051|Filtro Ar Motor Renault Sandero 1.6 16V|1|115.00
SIMILAR|WO200|Wega|Filtro Oleo Logan Sandero 1.6|1|28.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Magnatec 5W-30 API SN ACEA A5|5L|175.00
SIMILAR|FAP3740|Wega|Filtro Ar Sandero Logan 1.6|1|42.00
[/PECAS]'
        ],
        [
            'Substituicao da Bateria',
            100000,
            '60',
            45.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 30 minutos]

Substituicao da bateria automotiva 12V. Renault Sandero 1.6 Stepway utiliza bateria de 60Ah a 70Ah com corrente de partida (CCA) de 500A a 550A. Baterias seladas livre de manutencao tem vida util de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora. Configuracao de sistemas eletronicos (radio, relogio, computador de bordo, Medianav) apos troca. Dimensoes: 278mm x 175mm x 190mm.

**Criticidade:** MEDIA - Consumivel com vida util definida.

**Consequencias de nao fazer:** Falha de partida especialmente em dias frios, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, falha dos sistemas eletronicos, perda de memoria dos sistemas, necessidade de reboque.

[PECAS]
ORIGINAL|244101843R|Bateria 12V 60Ah Renault Original|1|625.00
SIMILAR|60GD-550|Moura|Bateria 12V 60Ah 550A Selada|1|365.00
SIMILAR|60D-570|Heliar|Bateria 12V 60Ah 570A Free|1|375.00
SIMILAR|B60DH|Bosch|Bateria 12V 60Ah S5 Free|1|415.00
SIMILAR|60AH-500|Zetta|Bateria 12V 60Ah Selada|1|315.00
[/PECAS]'
        ],

        // ==================== ITENS DE ATENCAO ESPECIAL POR TEMPO ====================
        [
            'Pneus - Verificacao Mensal / Substituicao a cada 5 anos',
            55000,
            '60',
            65.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 90 minutos para jogo completo]

Renault Sandero Stepway utiliza pneus 205/50 R17. Vida util media: 45.000 a 55.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). Pressao recomendada: 32 PSI dianteiros/30 PSI traseiros conforme manual. Verificar mensalmente: pressao, desgaste da banda (minimo legal 1,6mm medido nos TWI), deformacoes, cortes laterais, data de fabricacao (codigo DOT). Realizar rodizio a cada 10.000 km.

**Criticidade:** CRITICA - Seguranca ativa.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 40%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves, capotamento (Stepway possui centro de gravidade mais alto), multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular, reprovacao em inspecao veicular.

[PECAS]
SIMILAR|205-50R17-PI|Pirelli|Pneu Cinturato P7 205/50 R17|4|1880.00
SIMILAR|205-50R17-GY|Goodyear|Pneu Eagle Sport 205/50 R17|4|1780.00
SIMILAR|205-50R17-BS|Bridgestone|Pneu Turanza T001 205/50 R17|4|1820.00
SIMILAR|205-50R17-CT|Continental|Pneu PremiumContact 205/50 R17|4|1850.00
[/PECAS]'
        ],
        [
            'Fluido de Freio - Troca a cada 24 meses (independente de km)',
            0,
            '24',
            105.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Fluido de freio DOT 4 higroscopico degrada com o tempo mesmo sem uso do veiculo. Troca obrigatoria a cada 2 anos independente da quilometragem conforme especificacoes da revisao de 30.000 km.

**Criticidade:** ALTA - Seguranca ativa.

[PECAS]
ORIGINAL|7711575504|Fluido de Freio DOT 4 Renault Original|500ML|55.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|35.00
[/PECAS]'
        ],

        // ==================== PROBLEMAS CONHECIDOS E RECALLS ====================
        [
            'RECALL CRITICO - Airbag do Motorista (2014)',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Recall] [TEMPO: Servico gratuito em concessionaria]

Problema grave no airbag do lado do motorista: em razao de falha no processo de fabricacao do fornecedor do airbag do motorista, a qual pode gerar o nao funcionamento do componente. Afeta Sandero fabricados entre 7 e 19 de maio de 2014, chassis EJ347159 ate EJ396614.

Sinais de Alerta: Luz de airbag acesa no painel, mensagem de falha no sistema de airbag.

Riscos: Em caso de colisao, que seria necessario o acionamento do airbag, o componente pode nao ser acionado do lado do motorista, podendo causar lesoes graves e/ou fatais.

Procedimento: VERIFICAR URGENTEMENTE pelo site www.renault.com.br ou telefone Renault Fale Conosco. Reparo: substituicao do airbag do motorista. SERVICO GRATUITO EM CONCESSIONARIA.

[PECAS]
Servico de recall gratuito - sem custo de pecas para o proprietario
[/PECAS]'
        ],
        [
            'Problema GRAVE - Bomba de Combustivel e Sistema de Injecao',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao preventiva]

Problema extremamente comum no Sandero: bomba de combustivel que para de funcionar repentinamente, carro para do nada, nao liga de jeito nenhum, a bomba de combustivel nao faz barulho nenhum. Tambem relatados: falhas de injecao eletronica, luz de erro de injecao no painel, erro multifuncao, luz de ABS acesa.

Sinais de Alerta: Carro para repentinamente em movimento, bomba de combustivel sem ruido ao girar chave, falha na partida sem causa aparente, luz de injecao acesa no painel, carro falhando ou engasgando, luz de multifuncao e/ou ABS acesas.

Causas Provaveis: Falha da bomba de combustivel (comum), rele da bomba queimado, fusivel da bomba queimado, falha no modulo de injecao (erro de projeto), problemas na caixa de fusiveis.

Prevencao: Trocar filtro de combustivel rigorosamente a cada 10.000 km, usar sempre combustivel de qualidade, evitar rodar com tanque abaixo de 1/4, verificar estado da caixa de fusiveis regularmente. ATENCAO: Renault teve que consertar defeito causado por erro de projeto.

[PECAS]
Verificar itens de inspecao e filtro de combustivel
[/PECAS]'
        ],
        [
            'Problema COMUM - Falhas Eletricas e Rele do Eletroventilador',
            0,
            '0',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao preventiva]

Problema recorrente: carro apresenta falhas eletricas diversas, acendendo luzes do painel referente a erro de injecao, multi funcao, as vezes a luz do ABS. Constantemente derrete o rele do eletroventilador do radiador. Renault consertou o defeito porem de maneira porca, quebrando a caixa de fusiveis e deixando fios soltos.

Sinais de Alerta: Rele do eletroventilador derretendo com frequencia, luzes do painel acendendo aleatoriamente, carro acelerando sozinho (falha grave), caixa de fusiveis danificada, fios soltos no cofre do motor.

Prevencao: Inspecao periodica da caixa de fusiveis e chicote eletrico, substituir rele do eletroventilador por modelo reforcado, verificar temperatura de funcionamento do motor, limpar radiador e condensador periodicamente, verificar se reparo do recall foi feito corretamente.

[PECAS]
Verificar rele do eletroventilador e sistema eletrico na inspecao
[/PECAS]'
        ],
        [
            'Motor K4M - Correia Dentada CRITICA',
            60000,
            '48',
            0.00,
            'Critica',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao preventiva]

A correia dentada do Sandero 1.6 16V e o item de manutencao mais critico. Motor K4M e do tipo interferente: ruptura causa colisao entre pistoes e 16 valvulas.

Sinais de Alerta (ruptura iminente): Ruido agudo/chiado vindo da correia, estalidos ao acelerar, correia com aspecto ressecado ou trincas visiveis, motor chegando aos 60.000 km ou 4 anos, vazamento de oleo ou agua na regiao da correia.

Prevencao: Troca OBRIGATORIA aos 60.000 km ou 4 anos (o que vier primeiro), NUNCA ultrapassar esse intervalo, SEMPRE substituir tensor, polia louca e bomba dagua junto, usar apenas pecas de qualidade (original Renault ou Continental/INA), verificar visualmente a correia a cada revisao aos 50.000 km, evitar lavar motor com jato de agua diretamente na correia.

Custo de Substituicao Preventiva: R$ 1.200 a R$ 1.650
Custo de Reparo se Romper: R$ 10.000 a R$ 18.000

[PECAS]
Ver item Substituicao Obrigatoria da Correia Dentada na revisao 60.000 km
[/PECAS]'
        ],
        [
            'Oleo Sintetico 5W-30/5W-40 - OBRIGATORIO',
            10000,
            '12',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao a cada troca de oleo]

Motor K4M 1.6 16V EXIGE oleo sintetico 5W-30 ou 5W-40 especificacao API SN, ACEA A5/B5, RN 0700. Usar oleo inadequado causa desgaste prematuro especialmente dos tuchos hidraulicos.

Sinais de Alerta: Ruido de castanhola no motor (tuchos hidraulicos), consumo de oleo entre trocas, perda de potencia, luz de pressao de oleo piscando.

Prevencao: SEMPRE utilizar oleo 5W-30 ou 5W-40 sintetico, NUNCA usar oleo 10W-40, 15W-40 ou 20W-50, capacidade: 4,3-4,5 litros com filtro, trocar oleo rigorosamente a cada 10.000 km ou 12 meses, em uso com etanol predominante: reduzir para 7.500 km, verificar nivel semanalmente, usar filtro de qualidade (original ou Wega/Tecfil/Mahle).

[PECAS]
Ver item Troca de Oleo e Filtro do Motor na revisao 10.000 km
[/PECAS]'
        ],
        [
            'Sistema de Injecao Flex - Sensibilidade a Combustivel',
            30000,
            '36',
            0.00,
            'Media',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao preventiva]

Motor K4M com injecao multiponto e sensivel a combustivel de baixa qualidade, especialmente etanol adulterado ou com agua. Problema agravado por falhas conhecidas do modulo de injecao.

Sinais de Problema: Dificuldade na partida a frio com etanol, falhas e engasgos, marcha lenta irregular, perda de potencia, aumento no consumo, luz de injecao acesa, carro falhando.

Prevencao: Abastecer apenas em postos confiaveis, preferir gasolina aditivada ou adicionar aditivo de qualidade, limpeza dos bicos injetores a cada 30.000 km, trocar filtro de combustivel rigorosamente a cada 10.000 km, verificar bomba de combustivel e reles, monitorar sistema eletrico e fusiveis.

[PECAS]
Ver item Limpeza do Sistema de Injecao Eletronica na revisao 30.000 km
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

    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'modelo' => $modeloNome,
        'planos_deletados' => $deletados,
        'planos_inseridos' => $inseridos,
        'message' => "Plano de manutencao para {$modeloNome} importado com sucesso!",
        'proximo_passo' => 'Verificar em https://frotas.in9automacao.com.br/planos-manutencao-novo.html',
        'itens_incluidos' => [
            'Revisao 10.000 km - 5 itens (oleo, filtros, inspecao)',
            'Revisao 20.000 km - 2 itens (filtros completos, rodizio)',
            'Revisao 30.000 km - 3 itens (filtros, fluido freio, limpeza injecao)',
            'Revisao 40.000 km - 4 itens (filtros, velas, pastilhas, correias)',
            'Revisao 50.000 km - 3 itens (filtros, arrefecimento, ar condicionado)',
            'Revisao 60.000 km CRITICA - 4 itens (correia dentada obrigatoria, discos/pastilhas)',
            'Revisao 70.000 km - 1 item (filtros)',
            'Revisao 80.000 km - 5 itens (filtros, velas, lonas/tambores, amortecedores, correias)',
            'Revisao 100.000 km - 2 itens (filtros, bateria)',
            'Itens especiais - Pneus, fluido freio por tempo',
            'Recalls e alertas - Airbag 2014, bomba combustivel, falhas eletricas, correia dentada, oleo correto, sistema Flex'
        ]
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
