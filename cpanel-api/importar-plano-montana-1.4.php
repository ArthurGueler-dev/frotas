<?php
/**
 * Script para importar Plano de Manutencao Chevrolet Montana 1.4 Econoflex 2015
 * Gerado via Perplexity AI em 2026-01-15
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-montana-1.4.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-montana-1.4.php?confirmar=SIM'
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
    $modeloNome = "MONTANA 1.4 ECONOFLEX";

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

Drenagem completa do oleo lubrificante do motor Econoflex 1.4 8V atraves do bujao do carter. Substituicao do filtro de oleo tipo cartucho codigo GM 93156245/24588463/94630907 e reabastecimento com oleo semissintetico especificacao SAE 15W-40 API SL. Capacidade: 3,5 litros com filtro para motor 1.4 Econoflex. Motor Econoflex de 100 cv com 8 valvulas e taxa de compressao 11,2:1 requer oleo de qualidade para protecao em uso Flex (gasolina/etanol). Criterio: o que ocorrer primeiro (10.000 km OU 12 meses).

**Criticidade:** CRITICA - Lubrificacao essencial para vida util do motor Flex.

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado de pistoes, bronzinas e eixo comando de valvulas, acumulo de borra (especialmente com etanol), oxidacao interna, superaquecimento, perda de eficiencia em ate 18%, perda de garantia de fabrica, possivel travamento ou quebra do motor exigindo retifica completa (R$ 7.000 a R$ 12.000).

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|PSL127|Tecfil|Filtro Oleo GM 1.4 Econoflex|1|20.00
SIMILAR|PH6607|Fram|Filtro Oleo Montana 1.4|1|24.00
SIMILAR|OC90|Mahle|Filtro Oleo GM Montana|1|26.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
SIMILAR|15W40-SHELL|Shell|Oleo Helix HX5 15W-40 Semissintetico|4L|135.00
SIMILAR|15W40-MOBIL|Mobil|Oleo Super 2000 15W-40 SL|4L|125.00
SIMILAR|15W40-PETRONAS|Petronas|Oleo Tutela 15W-40 SL|4L|115.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            28.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 10 minutos]

Substituicao do elemento filtrante de ar do motor Econoflex 1.4 localizado na caixa de ar. Codigo original GM 93321305, codigo Wega FAP2827, Tecfil ARL2827. Filtro retem particulas solidas impedindo entrada no coletor de admissao e camara de combustao. Motor Econoflex com injecao eletronica multiponto requer fluxo de ar limpo para perfeita mistura ar/combustivel, especialmente importante no uso com etanol que requer maior volume de ar. Verificar estado da vedacao e limpeza interna da caixa de ar.

**Criticidade:** ALTA - Filtro saturado reduz potencia e aumenta consumo.

**Consequencias de nao fazer:** Reducao de potencia em ate 12%, aumento no consumo de combustivel em ate 15% (mais critico com etanol), entrada de particulas abrasivas causando desgaste dos cilindros, pistoes e aneis, formacao de borra no coletor de admissao, sensor MAP sujo causando falhas de injecao, marcha lenta irregular.

[PECAS]
ORIGINAL|93321305|Filtro Ar Motor GM Montana 1.4|1|92.00
SIMILAR|FAP2827|Wega|Filtro Ar Montana 1.4 8V 2011-2021|1|35.00
SIMILAR|ARL2827|Tecfil|Filtro Ar Montana Econoflex|1|38.00
SIMILAR|CA11380|Fram|Filtro Ar Montana 1.4|1|40.00
SIMILAR|LX3280|Mahle|Filtro Ar GM Montana|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            52.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 20 minutos]

Substituicao do filtro de combustivel do sistema de injecao eletronica multiponto. Codigo original GM 93297915, codigo Wega FCI1660, Tecfil GI04/3. Filtro tipo inline instalado na linha de combustivel. Motor Flex e extremamente sensivel a combustivel de baixa qualidade, especialmente etanol com agua. ATENCAO: Verificar recall do tanque de combustivel. Despressurizar o sistema antes da remocao (retirar fusivel da bomba, dar partida ate motor morrer).

**Criticidade:** ALTA - Sistema de injecao + recall de tanque.

**Consequencias de nao fazer:** Entupimento dos bicos injetores (mais frequente com etanol), falha na partida, perda de potencia, aumento no consumo em ate 20%, marcha lenta irregular, engasgos, agravamento do problema do recall do tanque, necessidade de limpeza ultrassonica dos injetores (R$ 320 a R$ 520).

[PECAS]
ORIGINAL|93297915|Filtro Combustivel GM Montana 1.4|1|78.00
SIMILAR|FCI1660|Wega|Filtro Combustivel Montana Corsa|1|30.00
SIMILAR|GI04/3|Tecfil|Filtro Combustivel GM 1.4|1|28.00
SIMILAR|G6610|Fram|Filtro Combustivel Montana|1|32.00
SIMILAR|KL229/3|Mahle|Filtro Combustivel GM|1|34.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            42.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 15 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas. Codigo original GM 90590568, codigo Wega AKX3536. Filtro tipo particulado retem poeira, polen, bacterias, fuligem e odores externos. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador. Recomenda-se higienizacao do sistema com spray antibacteriano durante a troca.

**Criticidade:** MEDIA - Impacta qualidade do ar e eficiencia do sistema.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine (odor de mofo), reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 320 a R$ 520).

[PECAS]
ORIGINAL|90590568|Filtro Ar Condicionado GM Montana|1|105.00
SIMILAR|AKX3536|Wega|Filtro Cabine Montana 2011-2018|1|32.00
SIMILAR|ACP1012|Tecfil|Filtro Cabine Montana 1.4|1|35.00
SIMILAR|CF1012|Fram|Filtro Ar Condicionado Montana|1|38.00
SIMILAR|LA1012|Mahle|Filtro Cabine GM Montana|1|40.00
[/PECAS]'
        ],
        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            110.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 50 minutos]

Inspecao visual e funcional completa conforme manual Chevrolet: verificacao de niveis de fluidos (arrefecimento, freio, direcao hidraulica, limpador, bateria), teste de luzes externas/internas, buzina, limpadores, travas; inspecao de pneus (pressao 30 PSI dianteiros/32 PSI traseiros sem carga, 30/38 PSI com carga, desgaste, banda minima 1,6mm), freios (pastilhas, discos, lonas, tubulacoes), suspensao (amortecedores, buchas, batentes), direcao hidraulica, escapamento, bateria (terminais, carga), correias, velas. ATENCAO ESPECIAL: Verificar recall do tanque de combustivel e airbag. Veiculo comercial (picape) requer atencao especial na suspensao traseira e cacamba.

**Criticidade:** ALTA - Detecta problemas e verifica recalls.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos ou recalls de tanque e airbag, acidentes por falha de freios ou pneus, perda de direcao hidraulica, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular, perda de garantia.

[PECAS]
Nao requer pecas de substituicao obrigatorias (apenas eventuais reposicoes identificadas)
[/PECAS]'
        ],

        // ==================== REVISAO 20.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            20000,
            '24',
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo incluindo oleo do motor, filtros de oleo, ar, combustivel e ar condicionado conforme especificacoes da revisao de 10.000 km.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
ORIGINAL|93321305|Filtro Ar Motor GM Montana 1.4|1|92.00
ORIGINAL|93297915|Filtro Combustivel GM Montana 1.4|1|78.00
ORIGINAL|90590568|Filtro Ar Condicionado GM Montana|1|105.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|PSL127|Tecfil|Filtro Oleo GM 1.4 Econoflex|1|20.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
SIMILAR|FAP2827|Wega|Filtro Ar Montana 1.4 8V 2011-2021|1|35.00
SIMILAR|FCI1660|Wega|Filtro Combustivel Montana Corsa|1|30.00
SIMILAR|AKX3536|Wega|Filtro Cabine Montana 2011-2018|1|32.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            20000,
            '24',
            68.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 30 minutos]

Substituicao das 4 velas de ignicao do motor Econoflex 1.4 8V. Especificacoes: NGK BPR7E-D ou codigo GM 93230926, gap 1,0mm, rosca 14mm. Motor Flex com taxa de compressao 11,2:1 requer velas resistivas especificas resistentes a corrosao do etanol. Limpar bem a regiao antes da remocao para evitar entrada de sujeira nos cilindros. Aplicar torque de aperto de 20-25 Nm. Verificar cor dos eletrodos (marrom claro = ideal, preto = mistura rica, branco = mistura pobre).

**Criticidade:** ALTA - Velas desgastadas causam falhas de combustao.

**Consequencias de nao fazer:** Dificuldade na partida especialmente com etanol, falhas de ignicao (motor falhando/trepidando), perda de potencia em ate 18%, aumento no consumo de combustivel em ate 25%, marcha lenta irregular, engasgos, emissoes poluentes elevadas, possivel danificacao do catalisador (R$ 1.400 a R$ 2.600), perda de garantia.

[PECAS]
ORIGINAL|93230926|Jogo 4 Velas Ignicao GM Montana 1.4|4|122.00
SIMILAR|BPR7ED|NGK|Jogo 4 Velas Montana 1.4 8V Flex|4|68.00
SIMILAR|WR7DCX|Bosch|Jogo 4 Velas Montana Econoflex|4|72.00
SIMILAR|R7DC|Champion|Jogo 4 Velas GM 1.4|4|65.00
[/PECAS]'
        ],
        [
            'Rodizio de Pneus e Alinhamento',
            20000,
            '24',
            135.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 45 minutos]

Execucao de rodizio dos pneus 185/65 R15 (Montana LS) seguindo padrao paralelo ou cruz. ATENCAO: Picape tem distribuicao de peso diferente - verificar pressao conforme carga: 30 PSI dianteiros/32 PSI traseiros sem carga, 30 PSI dianteiros/38 PSI traseiros com carga maxima (570 kg). Verificacao de pressao, inspecao de desgaste irregular indicando necessidade de alinhamento. Verificacao de cortes, bolhas, deformacoes, data de fabricacao (codigo DOT). Alinhamento 3D das rodas dianteiras (veiculo possui eixo traseiro rigido). Balanceamento eletronico das 4 rodas.

**Criticidade:** MEDIA - Impacta seguranca, conforto e durabilidade.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 40%, vibracoes no volante, perda de estabilidade direcional, aumento no consumo de combustivel em ate 10%, perda de aderencia em piso molhado aumentando risco de aquaplanagem, desgaste irregular da direcao hidraulica.

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
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
ORIGINAL|93321305|Filtro Ar Motor GM Montana 1.4|1|92.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
SIMILAR|FAP2827|Wega|Filtro Ar Montana 1.4 8V 2011-2021|1|35.00
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

**Criticidade:** ALTA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Fluido contaminado com umidade causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico (cilindros mestre e roda, pincas, modulo ABS), necessidade de substituicao completa do sistema, falha do ABS, acidentes graves especialmente com carga.

[PECAS]
ORIGINAL|93160361|Fluido de Freio DOT 4 GM Original|500ML|48.00
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

Limpeza preventiva dos bicos injetores multiponto atraves de aditivo de alta qualidade aplicado no tanque de combustivel. Motor Econoflex 1.4 possui 4 bicos injetores que podem acumular depositos carboniferos especialmente com uso de etanol de baixa qualidade ou adulterado. Procedimento: abastecer tanque com gasolina aditivada, adicionar produto limpador de injetores especifico para Flex, rodar em rodovia por pelo menos 50 km. Em casos severos, realizar limpeza por ultrassom em oficina especializada.

**Criticidade:** MEDIA - Preventiva para manter desempenho.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 14%, aumento no consumo em ate 18%, marcha lenta irregular, dificuldade na partida a frio com etanol, engasgos, formacao de depositos no coletor de admissao, necessidade de limpeza ultrassonica (R$ 320 a R$ 520).

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
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
ORIGINAL|93321305|Filtro Ar Motor GM Montana 1.4|1|92.00
ORIGINAL|93297915|Filtro Combustivel GM Montana 1.4|1|78.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
SIMILAR|FAP2827|Wega|Filtro Ar Montana 1.4 8V 2011-2021|1|35.00
SIMILAR|FCI1660|Wega|Filtro Combustivel Montana Corsa|1|30.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            40000,
            '48',
            68.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 30 minutos]

Segunda troca das velas de ignicao conforme especificacoes da revisao de 20.000 km.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93230926|Jogo 4 Velas Ignicao GM Montana 1.4|4|122.00
SIMILAR|BPR7ED|NGK|Jogo 4 Velas Montana 1.4 8V Flex|4|68.00
SIMILAR|WR7DCX|Bosch|Jogo 4 Velas Montana Econoflex|4|72.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            140.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas) sistema TRW/Teves. Codigo Cobreq N-360 (ate 2011) ou N-377 (2011-2021), Fras-le PD46, Jurid HQJ2065. Freios a disco dianteiro (4 furos, diametro 256mm). Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas (Ceratec ou Molykote), verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos. Sangria se necessario. Teste em pista. ATENCAO: Picape com carga requer atencao especial aos freios.

**Criticidade:** ALTA - Sistema de seguranca primaria + veiculo comercial.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos profundos nos discos, perda de eficiencia de frenagem em ate 45%, ruido metalico intenso, aumento da distancia de frenagem, necessidade de substituicao prematura dos discos, falha do ABS, risco de acidentes graves especialmente com carga.

[PECAS]
ORIGINAL|93313816|Jogo Pastilhas Freio Diant GM Montana|1|195.00
SIMILAR|N360|Cobreq|Jogo Pastilhas Freio Diant Montana 04-11|1|78.00
SIMILAR|N377|Cobreq|Jogo Pastilhas Freio Diant Montana 11-21|1|82.00
SIMILAR|PD46|Fras-le|Jogo Pastilhas Freio Diant Montana 1.4|1|85.00
SIMILAR|HQJ2065|Jurid|Jogo Pastilhas Freio Diant Montana|1|88.00
SIMILAR|BB2065|Bosch|Jogo Pastilhas Freio Diant Montana|1|92.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            80.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Substituicao da correia poly-V do alternador e correia da direcao hidraulica/ar condicionado. Motor Econoflex 1.4 utiliza correia 5PK1170 acionando alternador, bomba de direcao hidraulica e compressor do ar condicionado. Verificacao do tensionador automatico, polias e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao. Tensionamento adequado conforme especificacao do fabricante.

**Criticidade:** MEDIA - Correia desgastada pode romper causando pane.

**Consequencias de nao fazer:** Rompimento da correia causando descarregamento da bateria, perda do ar condicionado, perda de direcao hidraulica assistida, luz de bateria no painel, possivel superaquecimento por sobrecarga eletrica prolongada, necessidade de guincho.

[PECAS]
ORIGINAL|93329149|Correia Alternador GM Montana 1.4|1|95.00
SIMILAR|5PK1170|Gates|Correia Poly-V Alternador Montana|1|38.00
SIMILAR|5PK1170-CONT|Continental|Correia Poly-V Montana 1.4|1|35.00
SIMILAR|K051170|Dayco|Correia Alternador Montana Econoflex|1|37.00
SIMILAR|5PK1170-GY|Goodyear|Correia Auxiliar Montana|1|32.00
[/PECAS]'
        ],

        // ==================== REVISAO 50.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            50000,
            '60',
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            105.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 65 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor Econoflex 1.4. GM recomenda aditivo de longa duracao cor laranja diluido 50/50 com agua desmineralizada. Capacidade total: aproximadamente 5,0 litros da mistura. Procedimento: drenagem pelo bujao do radiador, lavagem interna com agua, reabastecimento da mistura, sangria do sistema (eliminacao de bolhas de ar), funcionamento ate atingir temperatura normal (ventoinha acionando), verificacao de vazamentos e nivel.

**Criticidade:** ALTA - Fluido degradado perde propriedades anticorrosivas.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do radiador, bloco, cabecote e bomba dagua, formacao de borra e depositos reduzindo eficiencia de troca termica, superaquecimento, danos ao radiador, bomba dagua (R$ 250 a R$ 420), termostato (R$ 105 a R$ 195) e motor, possivel empenamento do cabecote.

[PECAS]
ORIGINAL|93397197|Aditivo Radiador GM Original|1.5L|115.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|3.5L|21.00
SIMILAR|PARAFLU-LL|Repsol|Aditivo Radiador Longa Duracao|1.5L|58.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico|1.5L|60.00
SIMILAR|RAD-PROTEC|Valvoline|Aditivo Radiador Universal|1.5L|52.00
[/PECAS]'
        ],
        [
            'Troca de Oleo do Cambio Manual',
            50000,
            '60',
            75.00,
            'Media',
            '[CATEGORIA: Transmissao] [TEMPO: 35 minutos]

Troca do oleo lubrificante do cambio manual de 5 marchas. Especificacao: oleo SAE 75W-85 API GL-4+ ou equivalente GM 1940758. Capacidade: aproximadamente 1,8 litros. Drenagem pelo bujao inferior e reabastecimento ate o nivel correto pelo bujao lateral. Intervalo: a cada 40.000-50.000 km ou 48 meses conforme uso. Verificar integridade dos retentores de eixo durante o procedimento.

**Criticidade:** MEDIA - Manutencao frequentemente negligenciada.

**Consequencias de nao fazer:** Oleo degradado causa desgaste acelerado de engrenagens e sincronizadores, dificuldade em engatar marchas (especialmente 1a e 2a), rangidos ao trocar marchas, marcha pulando fora, aquecimento excessivo, necessidade de retifica ou substituicao do cambio (R$ 3.500 a R$ 6.500).

[PECAS]
ORIGINAL|1940758|Oleo Cambio Manual GM SAE 75W-85|2L|125.00
SIMILAR|75W85-CASTROL|Castrol|Oleo Cambio Syntrans 75W-85 GL-4+|2L|85.00
SIMILAR|75W85-SHELL|Shell|Oleo Cambio Spirax S5 75W-85|2L|78.00
SIMILAR|75W85-MOBIL|Mobil|Oleo Cambio Dexron III Manual|2L|72.00
[/PECAS]'
        ],
        [
            'Higienizacao Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            170.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 80 minutos]

Limpeza profissional do sistema de ar condicionado: aplicacao de espuma higienizadora no evaporador atraves da caixa de ar, aspiracao da espuma e residuos, aplicacao de bactericida/fungicida por nebulizacao, limpeza do dreno do evaporador (frequentemente entupido), troca do filtro de cabine. Verificacao de pressao do gas refrigerante R-134a, teste de vazamentos com detector eletronico, temperatura de saida (deve atingir 4-7C). Teste de funcionamento do compressor, embreagem eletromagnetica e eletroventilador do condensador.

**Criticidade:** BAIXA - Conforto e qualidade do ar.

**Consequencias de nao fazer:** Proliferacao de fungos e bacterias no evaporador, mau cheiro persistente (odor de mofo), alergias respiratorias graves, obstrucao do dreno causando infiltracao de agua no assoalho e modulo eletronico, reducao da eficiencia do sistema em ate 40%.

[PECAS]
ORIGINAL|90590568|Filtro Ar Condicionado GM Montana|1|105.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado|500ML|52.00
SIMILAR|KLIMACLEAN|Wynns|Limpador Ar Condicionado Automotivo|500ML|58.00
SIMILAR|AKX3536|Wega|Filtro Cabine Montana 2011-2018|1|32.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM - CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '72',
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
ORIGINAL|93321305|Filtro Ar Motor GM Montana 1.4|1|92.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
[/PECAS]'
        ],
        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT COMPLETO',
            60000,
            '60',
            520.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 180 minutos]

ITEM MAIS CRITICO DE MANUTENCAO DO MOTOR ECONOFLEX. Substituicao obrigatoria da correia dentada de sincronismo, tensor automatico e polia tensora. Kit Continental CT874K3 completo com codigo original GM 90531677 (correia) e 93353848 (tensor). Motor Econoflex 1.4 8V e do tipo interferente: se a correia romper, os pistoes colidem com as valvulas causando danos catastroficos. Manual GM recomenda troca aos 50.000-60.000 km ou 5 anos. Procedimento exige ferramentas especiais de travamento (ponto morto superior e comando de valvulas). OBRIGATORIO substituir tambem a bomba dagua preventivamente (acionada pela correia, economia de mao de obra).

**Criticidade:** CRITICA - FALHA CAUSA DANOS TOTAIS AO MOTOR

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e valvulas (motor interferente), empenamento/quebra de valvulas (todas as 8), danos severos aos pistoes, cabecote destruido, necessidade de retifica completa do motor ou substituicao. CUSTO DE REPARO: R$ 7.000 a R$ 14.000. Esta e a falha mecanica mais cara que pode ocorrer no veiculo.

[PECAS]
ORIGINAL|90531677|Correia Dentada GM Montana 1.4|1|235.00
ORIGINAL|93353848|Tensor Automatico Correia Dentada GM|1|345.00
ORIGINAL|93384304|Bomba Dagua GM Montana 1.4|1|295.00
SIMILAR|CT874K3|Continental|Kit Correia Dentada Montana Completo|1|425.00
SIMILAR|TB874|Dayco|Correia Dentada Montana 1.4|1|138.00
SIMILAR|T41874|Gates|Tensor Automatico Montana 1.4|1|215.00
SIMILAR|PA874|Nakata|Bomba Dagua Montana Econoflex|1|152.00
SIMILAR|PA874-URBA|Urba|Bomba Dagua GM 1.4|1|165.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            60000,
            '72',
            68.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 30 minutos]

Terceira troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93230926|Jogo 4 Velas Ignicao GM Montana 1.4|4|122.00
SIMILAR|BPR7ED|NGK|Jogo 4 Velas Montana 1.4 8V Flex|4|68.00
SIMILAR|WR7DCX|Bosch|Jogo 4 Velas Montana Econoflex|4|72.00
[/PECAS]'
        ],
        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            170.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 90 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio dianteiros 4 furos, diametro 256mm. Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada no disco (geralmente 22mm). Sangria do sistema. Teste em pista. Discos devem ser substituidos em par sempre. ATENCAO: Veiculo de carga requer freios em perfeito estado.

**Criticidade:** CRITICA - Sistema de seguranca primaria + veiculo comercial.

[PECAS]
ORIGINAL|93313816|Jogo Pastilhas Freio Diant GM Montana|1|195.00
ORIGINAL|93313818|Par Discos Freio Diant GM Montana|2|445.00
SIMILAR|N377|Cobreq|Jogo Pastilhas Freio Diant Montana 11-21|1|82.00
SIMILAR|PD46|Fras-le|Jogo Pastilhas Freio Diant Montana 1.4|1|85.00
SIMILAR|DF1046|Fremax|Par Discos Freio Montana|2|265.00
SIMILAR|RC1046|Cobreq|Par Discos Freio Diant Montana|2|255.00
SIMILAR|BD1046|TRW|Par Discos Freio Montana 1.4|2|275.00
[/PECAS]'
        ],

        // ==================== REVISAO 70.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            70000,
            '84',
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            80000,
            '96',
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
ORIGINAL|93321305|Filtro Ar Motor GM Montana 1.4|1|92.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
SIMILAR|FAP2827|Wega|Filtro Ar Montana 1.4 8V 2011-2021|1|35.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            80000,
            '96',
            68.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 30 minutos]

Quarta troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93230926|Jogo 4 Velas Ignicao GM Montana 1.4|4|122.00
SIMILAR|BPR7ED|NGK|Jogo 4 Velas Montana 1.4 8V Flex|4|68.00
SIMILAR|WR7DCX|Bosch|Jogo 4 Velas Montana Econoflex|4|72.00
[/PECAS]'
        ],
        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            195.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 120 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores traseiros (sistema a tambor 203mm). Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos, cabo do freio de estacionamento. Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm. Sangria do sistema. ATENCAO: Veiculo de carga exige freios traseiros em perfeito estado.

**Criticidade:** ALTA - Picape de carga requer freios impecaveis.

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro em ate 60%, desbalanceamento da frenagem, freio de estacionamento inoperante (reprovacao na inspecao), necessidade de substituicao dos tambores, acidentes por frenagem deficiente especialmente com carga.

[PECAS]
ORIGINAL|93313820|Jogo Lonas Freio Traseiro GM Montana|1|158.00
ORIGINAL|93313822|Par Tambores Freio Traseiro GM Montana|2|385.00
SIMILAR|HI1203|Fras-le|Jogo Lonas Freio Traseiro Montana|1|68.00
SIMILAR|N1203|Cobreq|Jogo Lonas Freio Traseiro Montana|1|65.00
SIMILAR|TT2203|TRW|Par Tambores Freio Traseiro Montana|2|215.00
SIMILAR|RT2203|Fremax|Par Tambores Freio Traseiro Montana|2|205.00
[/PECAS]'
        ],
        [
            'Substituicao de Amortecedores',
            80000,
            '96',
            285.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 150 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo telescopico) incluindo kits de reparo (coxins superiores, batentes, coifas). ATENCAO: Picape tem suspensao reforcada para carga - usar amortecedores especificos para Montana. Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar cada canto do veiculo, deve retornar sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento apos a troca.

**Criticidade:** ALTA - Impacta seguranca, estabilidade especialmente com carga.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem em ate 20%, perda de estabilidade em curvas especialmente com carga, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas), desconforto aos ocupantes, trepidacao, risco de capotamento com carga lateral.

[PECAS]
ORIGINAL|93313824|Amortecedor Dianteiro GM Montana|2|585.00
ORIGINAL|93313826|Amortecedor Traseiro GM Montana|2|545.00
SIMILAR|HG31204|Monroe|Amortecedor Diant Montana Gas|2|355.00
SIMILAR|HG31205|Monroe|Amortecedor Tras Montana Gas|2|335.00
SIMILAR|AM31204|Cofap|Amortecedor Diant Montana Turbogas|2|295.00
SIMILAR|AM31205|Cofap|Amortecedor Tras Montana Turbogas|2|275.00
SIMILAR|N31204|Nakata|Amortecedor Diant Montana 1.4|2|265.00
SIMILAR|N31205|Nakata|Amortecedor Tras Montana 1.4|2|245.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            80000,
            '96',
            80.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Segunda troca da correia do alternador conforme especificacoes da revisao de 40.000 km.

**Criticidade:** MEDIA

[PECAS]
ORIGINAL|93329149|Correia Alternador GM Montana 1.4|1|95.00
SIMILAR|5PK1170|Gates|Correia Poly-V Alternador Montana|1|38.00
SIMILAR|5PK1170-CONT|Continental|Correia Poly-V Montana 1.4|1|35.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            100000,
            '120',
            130.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|93156245|Filtro de Oleo Motor GM Montana 1.4|1|55.00
ORIGINAL|93744570|Oleo Motor GM Semi-Sintetico 15W-40 SL|4L|185.00
ORIGINAL|93321305|Filtro Ar Motor GM Montana 1.4|1|92.00
SIMILAR|WO130|Wega|Filtro Oleo Montana Corsa 1.4|1|22.00
SIMILAR|15W40-CASTROL|Castrol|Oleo GTX 15W-40 SL Semissintetico|4L|145.00
SIMILAR|FAP2827|Wega|Filtro Ar Montana 1.4 8V 2011-2021|1|35.00
[/PECAS]'
        ],
        [
            'Substituicao da Bateria',
            100000,
            '60',
            35.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 25 minutos]

Substituicao da bateria automotiva 12V. Chevrolet Montana 1.4 utiliza bateria de 50Ah a 60Ah com corrente de partida (CCA) de 380A a 450A. Baterias seladas livre de manutencao tem vida util de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora. Configuracao de sistemas eletronicos (radio, relogio) apos troca se necessario. Dimensoes: 230mm x 175mm x 190mm.

**Criticidade:** MEDIA - Consumivel com vida util definida.

**Consequencias de nao fazer:** Falha de partida especialmente em dias frios, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, oxidacao dos terminais, perda de memoria dos sistemas eletronicos, necessidade de reboque.

[PECAS]
ORIGINAL|93390780|Bateria 12V 50Ah GM Original|1|465.00
SIMILAR|50GD-400|Moura|Bateria 12V 50Ah 400A Selada|1|285.00
SIMILAR|50D-420|Heliar|Bateria 12V 50Ah 420A Free|1|295.00
SIMILAR|B50DH|Bosch|Bateria 12V 50Ah S4 Free|1|325.00
SIMILAR|50AH-380|Zetta|Bateria 12V 50Ah Selada|1|255.00
[/PECAS]'
        ],

        // ==================== ITENS DE ATENCAO ESPECIAL POR TEMPO ====================
        [
            'Pneus - Verificacao Mensal / Substituicao a cada 5 anos',
            50000,
            '60',
            55.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 80 minutos para jogo completo]

Chevrolet Montana utiliza pneus 185/65 R15. Vida util media: 40.000 a 50.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). ATENCAO ESPECIAL: Picape tem distribuicao de peso diferente - calibrar 30 PSI dianteiros/32 PSI traseiros sem carga, 30/38 PSI com carga maxima (570 kg). Verificar mensalmente: pressao, desgaste da banda (minimo legal 1,6mm medido nos TWI), deformacoes, cortes laterais, data de fabricacao (codigo DOT). Realizar rodizio a cada 10.000 km.

**Criticidade:** CRITICA - Seguranca ativa + veiculo de carga.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 40%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves especialmente com carga, capotamento, multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular, reprovacao em inspecao veicular.

[PECAS]
SIMILAR|185-65R15-P1|Pirelli|Pneu Cinturato P1 185/65 R15|4|1180.00
SIMILAR|185-65R15-GY|Goodyear|Pneu Direction Sport 185/65 R15|4|1080.00
SIMILAR|185-65R15-BS|Bridgestone|Pneu Turanza ER300 185/65 R15|4|1120.00
SIMILAR|185-65R15-CT|Continental|Pneu ContiPowerContact 185/65 R15|4|1150.00
[/PECAS]'
        ],
        [
            'Fluido de Freio - Troca a cada 24 meses (independente de km)',
            0,
            '24',
            95.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Fluido de freio DOT 4 higroscopico degrada com o tempo mesmo sem uso do veiculo. Troca obrigatoria a cada 2 anos independente da quilometragem conforme especificacoes da revisao de 30.000 km.

**Criticidade:** ALTA - Seguranca ativa.

[PECAS]
ORIGINAL|93160361|Fluido de Freio DOT 4 GM Original|500ML|48.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|32.00
[/PECAS]'
        ],

        // ==================== PROBLEMAS CONHECIDOS E RECALLS ====================
        [
            'RECALL CRITICO - Tanque de Combustivel (2015)',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Recall] [TEMPO: Servico gratuito em concessionaria]

Problema grave identificado pela GM de possivel rompimento parcial ou total do tubo de abastecimento do tanque, o que pode gerar vazamento na regiao traseira inferior do carro, aumentando a chance de incendio e, naturalmente, ferimentos aos ocupantes.

Veiculos Afetados: Montana linha 2015 produzidas entre 14 de junho e 23 de setembro de 2014, com numeracao de chassi entre FB112093 a FB151541.

Sinais de Alerta: Cheiro forte de combustivel ao redor do veiculo, manchas de combustivel no chao embaixo do veiculo (regiao traseira), reducao anormal do nivel de combustivel no tanque, luz de anomalia acesa.

Riscos: Vazamento de combustivel com risco de incendio, podendo causar ferimentos graves ou fatais aos ocupantes e terceiros.

Procedimento: VERIFICAR URGENTEMENTE pelo telefone 0800 702 4200 ou site oficial da Chevrolet. Reparo: substituicao do tubo de abastecimento do tanque de combustivel. SERVICO GRATUITO EM CONCESSIONARIA.

[PECAS]
Servico de recall gratuito - sem custo de pecas para o proprietario
[/PECAS]'
        ],
        [
            'RECALL CRITICO - Airbag do Motorista (2019-2020)',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Recall] [TEMPO: Servico gratuito em concessionaria]

Problema no airbag do lado do motorista: em caso de colisao com acionamento do airbag, podera ocorrer a soltura do modulo do airbag do volante, comprometendo o seu correto funcionamento.

Veiculos Afetados: Principalmente Montana 2019 e 2020 (chassis KB218431 a LB180010), mas tambem componentes de reposicao aplicaveis aos veiculos 2014 a 2019.

Sinais de Alerta: Luz de airbag acesa no painel, mensagem de falha no sistema de airbag.

Riscos: Este defeito diminui a protecao do motorista, podendo causar danos materiais, lesoes fisicas graves ou ate mesmo fatais.

Procedimento: Verificar pelo telefone 0800 702 4200 ou site oficial da Chevrolet. Os proprietarios dos veiculos envolvidos deverao agendar junto a uma concessionaria da marca a substituicao do insuflador do airbag. Servico gratuito.

[PECAS]
Servico de recall gratuito - sem custo de pecas para o proprietario
[/PECAS]'
        ],
        [
            'Motor Econoflex - Correia Dentada CRITICA',
            50000,
            '60',
            0.00,
            'Critica',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao preventiva]

A correia dentada da Montana 1.4 Econoflex e o item de manutencao mais critico. Motor e do tipo interferente: ruptura causa colisao entre pistoes e valvulas.

Sinais de Alerta (ruptura iminente): Ruido agudo/chiado vindo do motor, estalidos na regiao da correia, correia com aspecto ressecado ou trincas visiveis, motor chegando aos 50.000-60.000 km ou 5 anos.

Prevencao: Troca OBRIGATORIA aos 50.000-60.000 km ou 5 anos (o que vier primeiro), NUNCA ultrapassar esse intervalo, SEMPRE substituir tensor e polia tensora junto, substituir bomba dagua preventivamente, usar apenas pecas de qualidade (original GM ou Continental/Gates), verificar visualmente a correia a cada revisao aos 40.000 km.

Custo de Substituicao Preventiva: R$ 1.000 a R$ 1.350
Custo de Reparo se Romper: R$ 7.000 a R$ 14.000

[PECAS]
Ver item Substituicao Obrigatoria da Correia Dentada na revisao 60.000 km
[/PECAS]'
        ],
        [
            'Motor Econoflex - Oleo Correto',
            10000,
            '12',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao a cada troca de oleo]

Motor Econoflex 1.4 requer oleo semissintetico 15W-40 API SL. Usar oleo inadequado causa desgaste prematuro, especialmente importante no uso Flex (etanol e mais corrosivo).

Sinais de Alerta: Ruido de cascalho no motor (desgaste do comando), consumo de oleo entre trocas, perda de potencia, luz de pressao de oleo piscando, fumaca azul no escapamento.

Prevencao: SEMPRE utilizar oleo 15W-40 semissintetico API SL, capacidade: exatos 3,5 litros com filtro, trocar oleo rigorosamente a cada 10.000 km ou 12 meses, em uso com etanol predominante: reduzir para 7.500 km, verificar nivel semanalmente, usar filtro de qualidade (original ou Wega/Tecfil/Mahle).

[PECAS]
Ver item Troca de Oleo e Filtro do Motor na revisao 10.000 km
[/PECAS]'
        ],
        [
            'Veiculo Comercial - Cuidados Especiais',
            10000,
            '12',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao continua]

Montana e veiculo comercial (picape) com capacidade de carga de 570 kg. Uso comercial intenso e transporte de carga requerem atencao especial em varios sistemas.

Manutencoes Criticas para Uso Comercial: Freios (pastilhas e lonas desgastam mais rapido com carga - verificar a cada 5.000 km), Suspensao (amortecedores e molas sofrem mais - inspecionar regularmente), Pneus (calibragem diferenciada: 30/32 PSI sem carga, 30/38 PSI com carga), Oleo (trocar a cada 7.500 km em uso severo com etanol), Fluido freio (trocar a cada 12 meses em uso intenso).

Uso Severo (reduzir intervalos pela metade): Trajetos curtos (<10 km) diarios, transporte frequente de carga maxima, trafego urbano congestionado constante, estradas de terra frequentes, mais de 8 horas/dia de uso, uso predominante de etanol.

[PECAS]
Verificar itens especificos em cada revisao conforme uso do veiculo
[/PECAS]'
        ],
        [
            'Sistema de Injecao Flex - Sensibilidade',
            30000,
            '36',
            0.00,
            'Media',
            '[CATEGORIA: Alerta Tecnico] [TEMPO: Verificacao preventiva]

Motor Econoflex com injecao multiponto e sensivel a combustivel de baixa qualidade, especialmente etanol adulterado ou com agua.

Sinais de Problema: Dificuldade na partida a frio com etanol, falhas e engasgos, marcha lenta irregular, perda de potencia, aumento no consumo.

Prevencao: Abastecer apenas em postos confiaveis, preferir gasolina aditivada ou adicionar aditivo de qualidade, limpeza dos bicos injetores a cada 30.000 km, trocar filtro de combustivel rigorosamente a cada 10.000 km, verificar recall do tanque de combustivel.

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
            'Revisao 20.000 km - 3 itens (filtros completos, velas, rodizio)',
            'Revisao 30.000 km - 3 itens (filtros, fluido freio, limpeza injecao)',
            'Revisao 40.000 km - 4 itens (filtros, velas, pastilhas, correias)',
            'Revisao 50.000 km - 4 itens (filtros, arrefecimento, cambio, ar condicionado)',
            'Revisao 60.000 km CRITICA - 4 itens (correia dentada obrigatoria, discos/pastilhas)',
            'Revisao 70.000 km - 1 item (filtros)',
            'Revisao 80.000 km - 5 itens (filtros, velas, lonas/tambores, amortecedores, correias)',
            'Revisao 100.000 km - 2 itens (filtros, bateria)',
            'Itens especiais - Pneus, fluido freio por tempo',
            'Recalls - Tanque combustivel e airbag',
            'Alertas tecnicos - Correia dentada, oleo correto, uso comercial, sistema Flex'
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
