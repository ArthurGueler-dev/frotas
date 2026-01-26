<?php
/**
 * Script para importar Plano de Manutencao Chevrolet Classic 1.0 VHCE Flex 2013
 * Gerado via Perplexity AI em 2026-01-14
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-classic-1.0.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-classic-1.0.php?confirmar=SIM',
        'aviso' => 'Este script vai DELETAR todos os planos existentes do Classic e importar o novo plano completo'
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
    // MODELO - Nome EXATO conforme banco de dados (verificado via verificar-modelos.php)
    $modeloNome = "Classic";

    // PASSO 1: Deletar planos antigos deste modelo
    $stmt = $conn->prepare("DELETE FROM `Planos_Manutenção` WHERE modelo_carro = ?");
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
            95.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Drenagem completa do oleo lubrificante do motor VHCE (Variable High Compression Engine) 1.0 8V Flex atraves do bujao do carter. Substituicao do filtro de oleo tipo rosqueavel codigo GM 88905845 ou 94632619 e reabastecimento com oleo semissintetico especificacao SAE 5W-30 ou SAE 10W-40 API SM. Capacidade: 3,5 litros com filtro. O motor VHCE possui taxa de compressao variavel que exige oleo de qualidade para partida a frio e protecao em altas temperaturas. Criterio: o que ocorrer primeiro (10.000 km OU 12 meses).

**Criticidade:** CRITICA - Motor com taxa de compressao variavel requer lubrificacao constante.

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado do sistema VHC (pistao de compressao variavel), pistoes, bronzinas e eixo comando de valvulas, acumulo de borra, oxidacao interna, superaquecimento, perda de eficiencia em ate 18%, possivel travamento exigindo retifica completa (R$ 5.500 a R$ 9.000).

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|PSL947|Tecfil|Filtro Oleo Classic 1.0|1|23.00
SIMILAR|PH6018|Fram|Filtro Oleo Classic 1.0|1|27.00
SIMILAR|W610/3|Mann|Filtro Oleo Classic 1.0|1|29.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
SIMILAR|MAGNATEC-5W30|Castrol|Oleo Magnatec 5W-30 API SN 4L|4|125.00
SIMILAR|MOBIL-SUPER-5W30|Mobil|Oleo Mobil Super 3000 5W-30 4L|4|135.00
SIMILAR|LUBRAX-TECNO-10W40|Petrobras|Oleo Lubrax Tecno 10W-40 4L|4|95.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            30.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 10 minutos]

Substituicao do elemento filtrante de ar do motor VHCE 1.0 localizado na caixa de ar. O filtro retem particulas solidas impedindo entrada no coletor de admissao e camara de combustao. Motor VHCE com injecao eletronica multiponto e taxa de compressao variavel requer fluxo de ar limpo para perfeita mistura ar/combustivel. Verificar estado da vedacao e limpeza interna da caixa de ar.

**Criticidade:** ALTA - Filtro saturado reduz potencia e aumenta consumo.

**Consequencias de nao fazer:** Reducao de potencia em ate 12%, aumento no consumo de combustivel em ate 15%, entrada de particulas abrasivas causando desgaste dos cilindros especialmente do sistema VHC, pistoes e aneis, formacao de borra no coletor, sensor MAP sujo causando falhas de injecao.

[PECAS]
ORIGINAL|93260511|Filtro Ar Motor GM Classic 1.0|1|88.00
SIMILAR|C1944|Mann|Filtro Ar Classic 1.0|1|42.00
SIMILAR|ARL8834|Tecfil|Filtro Ar Classic 1.0|1|38.00
SIMILAR|FAP3289|Wega|Filtro Ar Classic 1.0|1|35.00
SIMILAR|CA10813|Fram|Filtro Ar Classic 1.0|1|40.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            55.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 20 minutos]

Substituicao do filtro de combustivel do sistema de injecao eletronica multiponto. O filtro remove impurezas da gasolina/etanol protegendo bicos injetores e bomba de combustivel. ATENCAO: Despressurizar o sistema antes da remocao (retirar fusivel da bomba, dar partida ate motor morrer). Filtro tipo inline instalado na linha de combustivel. Motor Flex e sensivel a combustivel de baixa qualidade - sempre abastecer em postos confiaveis.

**Criticidade:** ALTA - Sistema de injecao sensivel a impurezas.

**Consequencias de nao fazer:** Entupimento dos bicos injetores, falha na partida, perda de potencia, aumento no consumo em ate 20%, marcha lenta irregular, engasgos, necessidade de limpeza ultrassonica dos injetores (R$ 350 a R$ 550) ou substituicao completa (R$ 1.000 a R$ 1.800).

[PECAS]
ORIGINAL|25FC0225|Filtro Combustivel ACDelco Classic|1|78.00
SIMILAR|GI04/6|Tecfil|Filtro Combustivel Classic 1.0|1|35.00
SIMILAR|JFC235|Wega|Filtro Combustivel Classic 1.0|1|32.00
SIMILAR|G5835|Fram|Filtro Combustivel Classic 1.0|1|38.00
SIMILAR|WK58/1|Mann|Filtro Combustivel Classic 1.0|1|40.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            45.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 15 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas ou sob o painel. Codigo Wega: AKX3536. Filtro tipo particulado retem poeira, polen, bacterias, fuligem e odores externos. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador (blower). Recomenda-se higienizacao do sistema com spray antibacteriano durante a troca.

**Criticidade:** MEDIA - Impacta qualidade do ar e eficiencia do sistema.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine (odor de mofo), reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 350 a R$ 550).

[PECAS]
ORIGINAL|52046268|Filtro Ar Condicionado GM Classic|1|82.00
SIMILAR|AKX3536|Wega|Filtro Cabine Classic 1.0|1|32.00
SIMILAR|ACP906|Tecfil|Filtro Cabine Classic 1.0|1|35.00
SIMILAR|CU4251|Mann|Filtro Ar Condicionado Classic|1|38.00
SIMILAR|CF10906|Fram|Filtro Ar Condicionado Classic|1|36.00
[/PECAS]'
        ],

        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            115.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 50 minutos]

Inspecao visual e funcional completa conforme manual Chevrolet: verificacao de niveis de fluidos (arrefecimento, freio, limpador, bateria), teste de luzes externas/internas, buzina, limpadores, travas; inspecao de pneus (pressao 30 PSI dianteiros/traseiros, desgaste, banda minima 1,6mm), sistema de freios (pastilhas, discos, lonas, tubulacoes), suspensao (amortecedores, buchas, batentes), direcao mecanica, escapamento, bateria (terminais, carga), correias, velas de ignicao.

**Criticidade:** ALTA - Detecta problemas em estagio inicial.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos, acidentes por falha de freios ou pneus, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular, agravamento de problemas simples.

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

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
ORIGINAL|93260511|Filtro Ar Motor GM Classic 1.0|1|88.00
ORIGINAL|25FC0225|Filtro Combustivel ACDelco Classic|1|78.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|PSL947|Tecfil|Filtro Oleo Classic 1.0|1|23.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
SIMILAR|ARL8834|Tecfil|Filtro Ar Classic 1.0|1|38.00
SIMILAR|GI04/6|Tecfil|Filtro Combustivel Classic 1.0|1|35.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            20000,
            '24',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Substituicao das 4 velas de ignicao do motor VHCE 1.0 8V Flex. Codigo GM: 93363296, NGK BR8ES, Bosch F000KE0P32, codigo PI0108. Especificacoes: rosca longa 19mm, diametro 14mm, hexagono 20,8mm, grau termico 8, construcao resistiva. Motor Flex requer velas especificas resistentes a corrosao do etanol. Limpar bem a regiao antes da remocao. Aplicar torque de aperto de 25 Nm. Verificar cor dos eletrodos (branco = mistura pobre, preto = mistura rica, marrom claro = ideal).

**Criticidade:** ALTA - Velas desgastadas causam falhas de combustao.

**Consequencias de nao fazer:** Dificuldade na partida especialmente com etanol, falhas de ignicao (motor falhando), perda de potencia em ate 15%, aumento no consumo de combustivel em ate 22%, marcha lenta irregular, trepidacao, engasgos, emissoes poluentes elevadas, possivel danificacao do catalisador (R$ 1.200 a R$ 2.200).

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Classic 1.0 VHCE 4un|4|165.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Classic 1.0 Flex|4|75.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Classic 1.0|4|82.00
SIMILAR|PI0108|Peca Nova|Jogo 4 Velas Ignicao Classic 1.0|4|78.00
[/PECAS]'
        ],

        [
            'Rodizio de Pneus e Alinhamento',
            20000,
            '24',
            140.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 45 minutos]

Execucao de rodizio dos pneus 175/70 R13 ou 185/70 R14 (conforme versao) seguindo padrao paralelo ou cruz. Verificacao de pressao (30 PSI dianteiros e traseiros sem carga, 32 PSI com carga). Inspecao de desgaste irregular indicando necessidade de alinhamento. Verificacao de cortes, bolhas, deformacoes. Alinhamento 3D das rodas dianteiras (veiculo nao possui regulagem traseira). Balanceamento eletronico das 4 rodas se necessario.

**Criticidade:** MEDIA - Impacta seguranca, conforto e durabilidade.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 35%, vibracoes no volante, perda de estabilidade direcional, aumento no consumo de combustivel em ate 10%, perda de aderencia em piso molhado, desgaste irregular da direcao mecanica.

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

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
ORIGINAL|93260511|Filtro Ar Motor GM Classic 1.0|1|88.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
SIMILAR|ARL8834|Tecfil|Filtro Ar Classic 1.0|1|38.00
[/PECAS]'
        ],

        [
            'Troca de Fluido de Freio DOT 3',
            30000,
            '24',
            95.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Drenagem completa e substituicao do fluido de freio DOT 3 ou DOT 4 em todo o sistema hidraulico. Fluido higroscopico absorve umidade do ar reduzindo ponto de ebulicao e causando perda de eficiencia. Procedimento: sangria de todas as rodas iniciando pela mais distante do cilindro mestre (traseira direita, traseira esquerda, dianteira direita, dianteira esquerda). Capacidade aproximada: 500ml. Intervalo critico: a cada 2 anos independente da quilometragem.

**Criticidade:** ALTA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Fluido contaminado com umidade causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico (cilindros mestre e roda), necessidade de substituicao completa do sistema, acidentes graves.

[PECAS]
ORIGINAL|93160364|Fluido de Freio DOT 3 GM 500ml|1|42.00
SIMILAR|DOT3-500ML|Bosch|Fluido Freio DOT 3 500ml|1|25.00
SIMILAR|DOT3-CASTROL|Castrol|Fluido Freio DOT 3 500ml|1|28.00
SIMILAR|DOT3-TRW|TRW|Fluido Freio DOT 3 500ml|1|23.00
SIMILAR|DOT4-ATE|ATE|Fluido Freio DOT 4 500ml|1|30.00
[/PECAS]'
        ],

        [
            'Limpeza do Sistema de Injecao Eletronica',
            30000,
            '36',
            45.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 60 minutos]

Limpeza preventiva dos bicos injetores multiponto atraves de aditivo de alta qualidade aplicado no tanque de combustivel. Motor VHCE 1.0 Flex possui 4 bicos injetores que podem acumular depositos carboniferos especialmente com uso de etanol de baixa qualidade. Procedimento: abastecer tanque com gasolina aditivada, adicionar produto limpador de injetores, rodar em rodovia. Em casos severos, realizar limpeza por ultrassom.

**Criticidade:** MEDIA - Preventiva para manter desempenho.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 14%, aumento no consumo em ate 18%, marcha lenta irregular, dificuldade na partida a frio, engasgos, formacao de depositos no coletor, necessidade de limpeza ultrassonica (R$ 350 a R$ 550).

[PECAS]
SIMILAR|FLEX-CLEAN|Wynns|Aditivo Limpador Sistema Flex 325ml|1|42.00
SIMILAR|INJ-CLEAN|Wurth|Limpador Injetores Flex 300ml|1|35.00
SIMILAR|TOP-CLEAN|Bardahl|Limpador Sistema Combustivel 200ml|1|38.00
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

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
ORIGINAL|93260511|Filtro Ar Motor GM Classic 1.0|1|88.00
ORIGINAL|25FC0225|Filtro Combustivel ACDelco Classic|1|78.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
SIMILAR|ARL8834|Tecfil|Filtro Ar Classic 1.0|1|38.00
SIMILAR|GI04/6|Tecfil|Filtro Combustivel Classic 1.0|1|35.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            40000,
            '48',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Segunda troca das velas de ignicao conforme especificacoes da revisao de 20.000 km.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Classic 1.0 VHCE 4un|4|165.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Classic 1.0 Flex|4|75.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Classic 1.0|4|82.00
[/PECAS]'
        ],

        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            85.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Substituicao da correia do alternador e correia da direcao hidraulica (correias poly-V). Motor VHCE 1.0 utiliza correias acionando alternador e bomba de direcao hidraulica. Verificacao do estado dos tensionadores, polias lisas e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao. Tensionamento adequado conforme especificacao.

**Criticidade:** MEDIA - Correia desgastada pode romper.

**Consequencias de nao fazer:** Rompimento da correia causando descarregamento da bateria, perda de direcao assistida hidraulica, possivel superaquecimento se houver sobrecarga eletrica prolongada, necessidade de guincho.

[PECAS]
ORIGINAL|93363185|Correia Alternador GM Classic 1.0|1|88.00
SIMILAR|4PK840|Gates|Correia Poly-V Alternador Classic|1|38.00
SIMILAR|4PK840-CONT|Continental|Correia Poly-V Classic 1.0|1|35.00
SIMILAR|K040840|Dayco|Correia Alternador Classic 1.0|1|37.00
SIMILAR|4PK840-GY|Goodyear|Correia Auxiliar Classic 1.0|1|33.00
[/PECAS]'
        ],

        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            140.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas) sistema Teves. Codigo Cobreq N-377, Fras-le PD/82. Freios a disco solido dianteiro (4 furos, diametro 236mm). Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica, verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos. Sangria se necessario. Teste em pista.

**Criticidade:** ALTA - Sistema de seguranca primaria.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos nos discos, perda de eficiencia de frenagem, ruido metalico, aumento da distancia de frenagem em ate 40%, necessidade de substituicao prematura dos discos, risco de acidentes graves.

[PECAS]
ORIGINAL|93384816|Jogo Pastilhas Freio Diant GM Classic|1|168.00
SIMILAR|N377|Cobreq|Jogo Pastilhas Freio Diant Classic 1.0|1|75.00
SIMILAR|PD/82|Fras-le|Jogo Pastilhas Freio Diant Classic|1|78.00
SIMILAR|HI1377|Fras-le|Jogo Pastilhas Freio Diant Classic|1|78.00
SIMILAR|BB1377|Bosch|Jogo Pastilhas Freio Diant Classic|1|85.00
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

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
[/PECAS]'
        ],

        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            110.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor VHCE 1.0. GM recomenda fluido Dex-Cool (aditivo de longa duracao cor laranja) diluido 50/50 com agua desmineralizada. Capacidade total: aproximadamente 5 litros da mistura. Procedimento: drenagem pelo bujao do radiador, lavagem interna, reabastecimento, sangria (eliminacao de bolhas), funcionamento ate temperatura normal (ventoinha acionando), verificacao de vazamentos e nivel.

**Criticidade:** ALTA - Fluido degradado perde propriedades anticorrosivas.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do radiador, bloco, cabecote e bomba dagua, formacao de borra reduzindo eficiencia termica, superaquecimento, danos ao radiador, bomba dagua (R$ 200 a R$ 380) e motor, possivel empenamento do cabecote.

[PECAS]
ORIGINAL|93302891|Aditivo Radiador Dex-Cool GM 3L|1|105.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada 3L|1|18.00
SIMILAR|PARAFLU-LL|Repsol|Aditivo Radiador Longa Duracao 3L|1|55.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico 3L|1|58.00
SIMILAR|RAD-PROTEC|Valvoline|Aditivo Radiador Universal 3L|1|52.00
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

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
ORIGINAL|93260511|Filtro Ar Motor GM Classic 1.0|1|88.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
[/PECAS]'
        ],

        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT COMPLETO',
            60000,
            '60',
            600.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 240 minutos]

ITEM CRITICO DE SEGURANCA DO MOTOR. Substituicao obrigatoria da correia dentada de sincronismo (111 dentes), tensor automatico e polia tensora. O motor VHCE 1.0 8V e do tipo interferente: caso a correia se rompa, os pistoes colidem com as valvulas causando danos catastroficos. Manual GM recomenda troca entre 40.000 e 60.000 km. Recomendacao forte: trocar aos 50.000-60.000 km em uso urbano intenso. Procedimento exige ferramentas especiais de sincronismo. Substituir tambem bomba dagua preventivamente (acionada pela correia, economia de mao de obra).

**Criticidade:** CRITICA - FALHA CAUSA DANOS CATASTROFICOS AO MOTOR

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e valvulas (motor interferente), empenamento/quebra de valvulas, danos aos pistoes, sistema VHC e cabecote, necessidade de retifica completa do motor. CUSTO DE REPARO: R$ 5.500 a R$ 11.000. Esta e a falha mecanica mais cara que pode ocorrer no veiculo.

[PECAS]
ORIGINAL|93396203|Correia Dentada GM Classic 1.0 111 dentes|1|265.00
ORIGINAL|93396204|Tensor Automatico Correia Dentada GM|1|385.00
ORIGINAL|93396205|Polia Tensora Correia Dentada GM|1|175.00
ORIGINAL|93271922|Bomba Dagua GM Classic 1.0|1|345.00
SIMILAR|CT874K3|Gates|Kit Correia Dentada Classic 1.0 Completo|1|425.00
SIMILAR|TB874|Dayco|Correia Dentada Classic 1.0|1|155.00
SIMILAR|T43874|Gates|Tensor Automatico Classic 1.0|1|225.00
SIMILAR|PA7874|Nakata|Polia Tensora Classic 1.0|1|105.00
SIMILAR|WP874|Nakata|Bomba Dagua Classic 1.0 8V|1|165.00
SIMILAR|PA874|Urba|Bomba Dagua Classic 1.0|1|185.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            60000,
            '72',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Terceira troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Classic 1.0 VHCE 4un|4|165.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Classic 1.0 Flex|4|75.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Classic 1.0|4|82.00
[/PECAS]'
        ],

        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            170.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 90 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio solidos dianteiros 4 furos, diametro 236mm. Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada (geralmente 9mm). Sangria do sistema. Teste em pista. Discos devem ser substituidos em par sempre.

**Criticidade:** CRITICA - Sistema de seguranca primaria.

[PECAS]
ORIGINAL|93384816|Jogo Pastilhas Freio Diant GM Classic|1|168.00
ORIGINAL|90111242|Par Discos Freio Diant GM Classic|2|385.00
SIMILAR|N377|Cobreq|Jogo Pastilhas Freio Diant Classic 1.0|1|75.00
SIMILAR|PD/82|Fras-le|Jogo Pastilhas Freio Diant Classic|1|78.00
SIMILAR|DF2377|Fremax|Par Discos Freio Solido Classic|2|225.00
SIMILAR|RC2377|Cobreq|Par Discos Freio Diant Classic|2|215.00
SIMILAR|BD2377|TRW|Par Discos Freio Classic 1.0|2|235.00
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

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
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

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|88905845|Filtro de Oleo Motor GM Classic 1.0|1|65.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
ORIGINAL|93260511|Filtro Ar Motor GM Classic 1.0|1|88.00
SIMILAR|WO130|Wega|Filtro Oleo Classic 1.0 VHCE|1|25.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
SIMILAR|ARL8834|Tecfil|Filtro Ar Classic 1.0|1|38.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            80000,
            '96',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Quarta troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Classic 1.0 VHCE 4un|4|165.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Classic 1.0 Flex|4|75.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Classic 1.0|4|82.00
[/PECAS]'
        ],

        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            190.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 120 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores traseiros. Sistema de freio a tambor traseiro. Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos. Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm.

**Criticidade:** ALTA

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro, desbalanceamento da frenagem, freio de estacionamento inoperante, necessidade de substituicao dos tambores, acidentes.

[PECAS]
ORIGINAL|93384821|Jogo Lonas Freio Traseiro GM Classic|1|148.00
ORIGINAL|93271928|Par Tambores Freio Traseiro GM Classic|2|355.00
SIMILAR|HI1378|Fras-le|Jogo Lonas Freio Traseiro Classic|1|68.00
SIMILAR|N1378|Cobreq|Jogo Lonas Freio Traseiro Classic|1|63.00
SIMILAR|TT2378|TRW|Par Tambores Freio Traseiro Classic|2|205.00
SIMILAR|RT2378|Fremax|Par Tambores Freio Traseiro Classic|2|195.00
[/PECAS]'
        ],

        [
            'Substituicao de Amortecedores Dianteiros e Traseiros',
            80000,
            '96',
            270.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 150 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo telescopico) incluindo kits de reparo (coxins superiores, batentes, coifas). Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar cada canto do veiculo, deve retornar sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento apos a troca.

**Criticidade:** ALTA - Impacta seguranca, estabilidade e conforto.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem em ate 20%, perda de estabilidade em curvas, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas), desconforto aos ocupantes, trepidacao.

[PECAS]
ORIGINAL|93271930|Amortecedor Dianteiro GM Classic par|2|565.00
ORIGINAL|93271931|Amortecedor Traseiro GM Classic par|2|535.00
SIMILAR|HG32145|Monroe|Amortecedor Diant Classic Gas par|2|365.00
SIMILAR|HG32146|Monroe|Amortecedor Tras Classic Gas par|2|345.00
SIMILAR|AM32145|Cofap|Amortecedor Diant Classic Turbogas par|2|305.00
SIMILAR|AM32146|Cofap|Amortecedor Tras Classic Turbogas par|2|285.00
SIMILAR|N32145|Nakata|Amortecedor Diant Classic 1.0 par|2|275.00
SIMILAR|N32146|Nakata|Amortecedor Tras Classic 1.0 par|2|255.00
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

**Criticidade:** MEDIA

[PECAS]
ORIGINAL|93363185|Correia Alternador GM Classic 1.0|1|88.00
SIMILAR|4PK840|Gates|Correia Poly-V Alternador Classic|1|38.00
SIMILAR|4PK840-CONT|Continental|Correia Poly-V Classic 1.0|1|35.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM ====================
        [
            'Troca de Oleo, Filtros e Correia Dentada - Segunda Troca',
            100000,
            '120',
            725.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 290 minutos]

SEGUNDA TROCA OBRIGATORIA DA CORREIA DENTADA. Servico completo incluindo oleo, filtros e substituicao do kit completo de correia dentada conforme especificacoes da revisao de 60.000 km. Intervalo: 50.000-60.000 km conforme uso.

**Criticidade:** CRITICA

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e valvulas, danos catastroficos ao motor. CUSTO DE REPARO: R$ 5.500 a R$ 11.000.

[PECAS]
ORIGINAL|93396203|Correia Dentada GM Classic 1.0 111 dentes|1|265.00
ORIGINAL|93396204|Tensor Automatico Correia Dentada GM|1|385.00
ORIGINAL|93396205|Polia Tensora Correia Dentada GM|1|175.00
ORIGINAL|93271922|Bomba Dagua GM Classic 1.0|1|345.00
ORIGINAL|98550168|Oleo Motor ACDelco 5W-30 Sintetico 4L|4|175.00
SIMILAR|CT874K3|Gates|Kit Correia Dentada Classic 1.0 Completo|1|425.00
SIMILAR|T43874|Gates|Tensor Automatico Classic 1.0|1|225.00
SIMILAR|WP874|Nakata|Bomba Dagua Classic 1.0 8V|1|165.00
SIMILAR|HELIX-HX7-5W30|Shell|Oleo Helix HX7 5W-30 Semissintetico 4L|4|115.00
[/PECAS]'
        ],

        [
            'Substituicao da Bateria',
            100000,
            '60',
            35.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 25 minutos]

Substituicao da bateria automotiva 12V. Chevrolet Classic 1.0 utiliza bateria de 40Ah a 45Ah com corrente de partida (CCA) de 330A a 380A. Baterias seladas livre de manutencao tem vida util de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora. Configuracao de sistemas eletronicos (radio, relogio) apos troca se necessario. Dimensoes: 175mm x 175mm x 175mm.

**Criticidade:** MEDIA - Consumivel com vida util definida.

**Consequencias de nao fazer:** Falha de partida especialmente em dias frios, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, oxidacao dos terminais, perda de memoria dos sistemas eletronicos, necessidade de reboque.

[PECAS]
ORIGINAL|93390948|Bateria 12V 40Ah ACDelco GM|1|425.00
SIMILAR|40GD-380|Moura|Bateria 12V 40Ah 380A Selada|1|285.00
SIMILAR|40D-400|Heliar|Bateria 12V 40Ah 400A Free|1|295.00
SIMILAR|B40DH|Bosch|Bateria 12V 40Ah S4 Free|1|325.00
SIMILAR|40AH-350|Zetta|Bateria 12V 40Ah Selada|1|245.00
[/PECAS]'
        ],

        [
            'Limpeza e Descarbonizacao do Sistema de Admissao',
            100000,
            '120',
            240.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 120 minutos]

Limpeza profunda do sistema de admissao, corpo de borboleta eletronico (TBI), coletor de admissao e sensores MAP/temperatura. Motor VHCE 1.0 Flex acumula depositos carboniferos no corpo de borboleta reduzindo desempenho especialmente com uso frequente de etanol. Procedimento: remocao e limpeza quimica do corpo de borboleta com produto especifico, limpeza do coletor, limpeza dos sensores. Apos limpeza, realizar procedimento de reaprendizagem da marcha lenta com scanner se necessario.

**Criticidade:** MEDIA - Preventiva para manter desempenho.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 15%, aumento no consumo de combustivel em ate 18%, marcha lenta irregular, engasgos, luz do motor (check engine) acesa por falha na leitura dos sensores, aceleracao sem resposta (delay), falha no sistema VHC.

[PECAS]
SIMILAR|TBI-CLEAN|Wynns|Limpador Corpo Borboleta 400ml|1|48.00
SIMILAR|CARB-CLEAN|Wurth|Limpador TBI/Admissao 500ml|1|45.00
SIMILAR|INTAKE-CLEAN|Bardahl|Limpador Sistema Admissao 300ml|1|52.00
[/PECAS]'
        ],

        // ==================== ITENS ESPECIAIS POR TEMPO ====================
        [
            'Substituicao de Pneus 175/70 R13',
            45000,
            '60',
            55.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 80 minutos]

Chevrolet Classic utiliza pneus 175/70 R13 (padrao) ou 185/70 R14 (opcional). Vida util media: 35.000 a 45.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). Verificar mensalmente: pressao (30 PSI dianteiros e traseiros sem carga, 32 PSI com carga), desgaste da banda (minimo legal 1,6mm medido nos TWI), deformacoes, cortes laterais, data de fabricacao (codigo DOT). Realizar rodizio a cada 10.000 km.

**Criticidade:** CRITICA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 40%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves, multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular, reprovacao em inspecao veicular.

[PECAS]
SIMILAR|175/70R13-PIR|Pirelli|Pneu Cinturato P1 175/70 R13 jogo 4un|4|1080.00
SIMILAR|175/70R13-GY|Goodyear|Pneu Direction Touring 175/70 R13 jogo 4un|4|980.00
SIMILAR|175/70R13-DUN|Dunlop|Pneu SP Touring R1 175/70 R13 jogo 4un|4|920.00
SIMILAR|175/70R13-CONT|Continental|Pneu ContiPowerContact 175/70 R13 jogo 4un|4|1020.00
SIMILAR|175/70R13-BRI|Bridgestone|Pneu Turanza ER300 175/70 R13 jogo 4un|4|1040.00
[/PECAS]'
        ],

        [
            'Troca de Fluido de Freio DOT 3 - Por Tempo',
            0,
            '24',
            95.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 50 minutos]

Fluido de freio DOT 3 higroscopico degrada com o tempo mesmo sem uso do veiculo. Troca obrigatoria a cada 2 anos independente da quilometragem. Fluido absorve umidade do ar reduzindo ponto de ebulicao. Em frenagens intensas pode vaporizar causando perda de frenagem (fade). Este item e baseado em TEMPO, nao em quilometragem.

**Criticidade:** ALTA - Seguranca ativa.

**Consequencias de nao fazer:** Fluido contaminado causa vaporizacao em frenagens intensas, perda total de frenagem, oxidacao do sistema hidraulico, acidentes graves.

[PECAS]
ORIGINAL|93160364|Fluido de Freio DOT 3 GM 500ml|1|42.00
SIMILAR|DOT3-CASTROL|Castrol|Fluido Freio DOT 3 500ml|1|28.00
[/PECAS]'
        ],

        // ==================== ATENCAO ESPECIAL ====================
        [
            'VERIFICACAO - Recall Airbag e Barra de Impacto',
            0,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Atencao Especial] [TEMPO: Verificacao online 10 minutos]

RECALL CRITICO: Problema na barra de impacto do para-choque dianteiro que pode interferir no funcionamento das bolsas de ar (airbags). Deixa os passageiros vulneraveis em caso de colisoes frontais. Afeta modelos Classic 2012-2013 equipados com airbag mas sem ar-condicionado. Chassis afetados: DB146382 a DB172060 e DC100004 a DC113546.

**SINAIS DE ALERTA:**
- Luz do airbag acesa no painel
- Barulho na suspensao dianteira

**PROCEDIMENTO:**
VERIFICAR URGENTEMENTE no site www.chevrolet.com.br ou telefone 0800 702 4200.
Reparo: troca da barra de impacto.
SERVICO GRATUITO EM CONCESSIONARIA.

[PECAS]
Verificacao de recall - servico gratuito em concessionaria
[/PECAS]'
        ],

        [
            'VERIFICACAO - Motor VHCE Sensibilidade a Oleo e Combustivel',
            10000,
            '12',
            0.00,
            'Alta',
            '[CATEGORIA: Atencao Especial] [TEMPO: Verificacao 5 minutos]

PROBLEMA CONHECIDO: O motor VHCE (Variable High Compression Engine) possui sistema de taxa de compressao variavel com pistao secundario movel que e sensivel a qualidade do oleo e combustivel. Oleo inadequado ou combustivel de baixa qualidade causam acumulo de depositos no sistema VHC causando perda de desempenho.

**SINAIS DE ALERTA:**
- Perda de potencia progressiva
- Aumento no consumo de combustivel
- Ruido de batida metalica (detonacao)
- Dificuldade na partida

**PREVENCAO:**
- SEMPRE utilizar oleo 5W-30 API SN ou 10W-40 API SM
- Trocar oleo rigorosamente a cada 10.000 km ou 12 meses
- Abastecer apenas em postos confiaveis
- Utilizar aditivo de qualidade a cada tanque cheio
- Realizar limpeza preventiva do sistema de admissao aos 100.000 km

[PECAS]
Verificacao preventiva - sem custo de pecas
[/PECAS]'
        ],

        [
            'VERIFICACAO - Correia Dentada (Item Mais Critico)',
            30000,
            '36',
            0.00,
            'Critica',
            '[CATEGORIA: Atencao Especial] [TEMPO: Verificacao visual 15 minutos]

ATENCAO MAXIMA: A correia dentada do Classic 1.0 VHCE e o item de manutencao mais critico. Motor interferente: ruptura causa colisao entre pistoes e valvulas. Casos documentados de ruptura antes dos 60.000 km em uso urbano severo.

**SINAIS DE ALERTA (ruptura iminente):**
- Ruido agudo vindo do motor
- Estalidos na regiao da correia
- Correia com aspecto ressecado ou trincado

**PREVENCAO:**
- Troca obrigatoria entre 50.000 e 60.000 km
- NUNCA ultrapassar 60.000 km ou 5 anos
- SEMPRE substituir tensor e polia tensora junto
- Substituir bomba dagua preventivamente
- Verificar visualmente a correia a cada revisao

[PECAS]
Verificacao visual - sem custo de pecas
[/PECAS]'
        ]
    ];

    // PASSO 3: Inserir itens na tabela Planos_Manutenção
    $stmt = $conn->prepare("
        INSERT INTO `Planos_Manutenção`
        (modelo_carro, descricao_titulo, km_recomendado, intervalo_tempo, custo_estimado, criticidade, descricao_observacao)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $inseridos = 0;
    $erros = [];

    foreach ($itens_plano as $index => $item) {
        $descricao = $item[0];
        $km = $item[1];
        $meses = $item[2];
        $custo = $item[3];
        $criticidade = $item[4];
        $observacao = $item[5];

        try {
            $stmt->bind_param("ssissss", $modeloNome, $descricao, $km, $meses, $custo, $criticidade, $observacao);
            $stmt->execute();
            $inseridos++;
        } catch (Exception $e) {
            $erros[] = "Item {$index} ({$descricao}): " . $e->getMessage();
        }
    }
    $stmt->close();

    // Resposta de sucesso
    $response = [
        'success' => true,
        'modelo' => $modeloNome,
        'planos_deletados' => $deletados,
        'planos_inseridos' => $inseridos,
        'total_itens' => count($itens_plano),
        'message' => "Plano de manutencao para {$modeloNome} importado com sucesso!",
        'proximo_passo' => 'Verificar em https://frotas.in9automacao.com.br/planos-manutencao-novo.html'
    ];

    if (!empty($erros)) {
        $response['erros'] = $erros;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

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
