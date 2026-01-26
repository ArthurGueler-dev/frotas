<?php
/**
 * Script para importar Plano de Manutencao Chevrolet Onix 1.0 Flex 2018-2019
 * Gerado via Perplexity AI em 2026-01-14
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-onix-1.0.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-onix-1.0.php?confirmar=SIM',
        'aviso' => 'Este script vai DELETAR todos os planos existentes do Onix e importar o novo plano completo'
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
    $modeloNome = "Onix";

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
            110.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Drenagem completa do oleo lubrificante do motor 1.0 12V SPE/4 (Small Petrol Engine 4 cilindros) 82cv atraves do bujao do carter. Substituicao do filtro de oleo tipo cartucho codigo GM 94797556 ou Wega WUNI0003 e reabastecimento com oleo 100% sintetico especificacao SAE 0W-20 API SN norma Dexos1 Gen3. Capacidade: 3,75 litros com filtro. Motor moderno de 3 cilindros requer oleo de viscosidade ultra-baixa para maxima eficiencia e economia de combustivel. ATENCAO: Oleo 5W-30 so para versoes turbo. Para motor 1.0 aspirado: obrigatorio 0W-20. Criterio: o que ocorrer primeiro (10.000 km OU 12 meses).

**Criticidade:** CRITICA - Motor moderno com folgas reduzidas requer lubrificacao premium especifica.

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado de pistoes, bronzinas, eixo comando de valvulas e turbocompressor (se equipado), acumulo de borra, oxidacao interna, superaquecimento, perda de eficiencia em ate 20%, possivel travamento ou quebra do motor exigindo retifica completa (R$ 8.000 a R$ 15.000).

[PECAS]
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|W712/22|Mann|Filtro Oleo Onix Prisma 1.0|1|32.00
SIMILAR|PH10600|Fram|Filtro Oleo Onix 1.0|1|30.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
SIMILAR|0W20-CASTROL-1L|Castrol|Oleo Edge 0W-20 Sintetico 4L|4|175.00
SIMILAR|0W20-SHELL-1L|Shell|Oleo Helix Ultra 0W-20 Sintetico 4L|4|168.00
SIMILAR|0W20-VALVOLINE-1L|Valvoline|Oleo Advanced 0W-20 Sintetico 4L|4|155.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            35.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 12 minutos]

Substituicao do elemento filtrante de ar do motor 1.0 SPE/4 localizado na caixa de ar. Codigo original GM: 52126118. Codigos similares: Mann C24034, Tecfil ARL8830. O filtro retem particulas solidas impedindo entrada no coletor de admissao e camara de combustao. Motor moderno com injecao eletronica multiponto e gerenciamento avancado requer fluxo de ar limpo para perfeita mistura ar/combustivel e maxima eficiencia. Verificar estado da vedacao e limpeza interna da caixa de ar. Dimensoes: 242mm x 212mm x 52mm.

**Criticidade:** ALTA - Filtro saturado reduz potencia e aumenta consumo.

**Consequencias de nao fazer:** Reducao de potencia em ate 10%, aumento no consumo de combustivel em ate 12%, entrada de particulas abrasivas causando desgaste dos cilindros, pistoes e aneis, formacao de borra no coletor de admissao, sensor MAF sujo causando falhas de injecao, marcha lenta irregular.

[PECAS]
ORIGINAL|52126118|Filtro Ar Motor GM Onix 1.0|1|95.00
SIMILAR|C24034|Mann|Filtro Ar Onix Prisma Spin 1.0 1.4|1|45.00
SIMILAR|ARL8830|Tecfil|Filtro Ar Onix 1.0|1|42.00
SIMILAR|FAP3300|Wega|Filtro Ar Onix 1.0|1|38.00
SIMILAR|CA11849|Fram|Filtro Ar Onix 1.0|1|43.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            65.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao do filtro de combustivel do sistema de injecao eletronica multiponto. Codigo ACDelco: 25FC0225. Codigo similar Mann: WK58. O filtro remove impurezas da gasolina/etanol protegendo bicos injetores e bomba de combustivel. ATENCAO: Despressurizar o sistema antes da remocao (retirar fusivel da bomba, dar partida ate motor morrer). Filtro tipo inline instalado na linha de combustivel sob o veiculo. Motor Flex e sensivel a combustivel de baixa qualidade - sempre abastecer em postos confiaveis.

**Criticidade:** ALTA - Sistema de injecao moderno e sensivel a impurezas.

**Consequencias de nao fazer:** Entupimento dos bicos injetores, falha na partida especialmente com etanol, perda de potencia, aumento no consumo em ate 18%, marcha lenta irregular, engasgos, necessidade de limpeza ultrassonica dos injetores (R$ 400 a R$ 600) ou substituicao completa (R$ 1.200 a R$ 2.000).

[PECAS]
ORIGINAL|25FC0225|Filtro Combustivel ACDelco Onix|1|82.00
SIMILAR|WK58|Mann|Filtro Combustivel Onix 1.0|1|38.00
SIMILAR|GI04/6|Tecfil|Filtro Combustivel Onix 1.0|1|36.00
SIMILAR|JFC235|Wega|Filtro Combustivel Onix 1.0|1|34.00
SIMILAR|G5835|Fram|Filtro Combustivel Onix 1.0|1|40.00
[/PECAS]'
        ],

        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            50.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 18 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas ou sob o painel. Codigo Mann: CU2442/2. Filtro tipo particulado retem poeira, polen, bacterias, fuligem e odores externos. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador (blower). Recomenda-se higienizacao do sistema com spray antibacteriano durante a troca.

**Criticidade:** MEDIA - Impacta qualidade do ar e eficiencia do sistema.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine (odor de mofo), reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 380 a R$ 600).

[PECAS]
ORIGINAL|52102242|Filtro Ar Condicionado GM Onix|1|88.00
SIMILAR|CU2442/2|Mann|Filtro Cabine Onix Prisma|1|40.00
SIMILAR|ACP910|Tecfil|Filtro Cabine Onix 1.0|1|38.00
SIMILAR|AKX9010|Wega|Filtro Cabine Onix 1.0|1|35.00
SIMILAR|CF10910|Fram|Filtro Ar Condicionado Onix|1|42.00
[/PECAS]'
        ],

        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            130.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 55 minutos]

Inspecao visual e funcional completa conforme manual Chevrolet: verificacao de niveis de fluidos (arrefecimento, freio, limpador, bateria), teste de luzes externas/internas (LED/halogeno), buzina, limpadores, travas eletricas, vidros eletricos; inspecao de pneus (pressao 32 PSI dianteiros/traseiros, desgaste, banda minima 1,6mm), sistema de freios (pastilhas, discos, lonas, tubulacoes), suspensao (amortecedores, buchas, batentes), direcao eletrica, escapamento, bateria (terminais, carga 12,6V), correias, velas, sensor de pressao TPMS (se equipado).

**Criticidade:** ALTA - Detecta problemas em estagio inicial.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos, acidentes por falha de freios ou pneus, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular, agravamento de problemas simples em defeitos graves.

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

Servico completo incluindo oleo do motor 0W-20, filtros de oleo, ar, combustivel e ar condicionado conforme especificacoes da revisao de 10.000 km.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
ORIGINAL|52126118|Filtro Ar Motor GM Onix 1.0|1|95.00
ORIGINAL|25FC0225|Filtro Combustivel ACDelco Onix|1|82.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
SIMILAR|C24034|Mann|Filtro Ar Onix Prisma Spin 1.0 1.4|1|45.00
SIMILAR|WK58|Mann|Filtro Combustivel Onix 1.0|1|38.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            20000,
            '24',
            85.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Substituicao das 4 velas de ignicao do motor 1.0 12V SPE/4 Flex. Codigo GM: 93363296, NGK BR8ES, codigo PI0108. Especificacoes: rosca longa 19mm, diametro 14mm, grau termico 8, construcao resistiva. Motor Flex de 3 cilindros requer velas especificas resistentes a corrosao do etanol e alta taxa de compressao. Limpar bem a regiao antes da remocao para evitar entrada de sujeira nos cilindros. Aplicar torque de aperto de 25 Nm. Verificar cor dos eletrodos (branco = mistura pobre, preto = mistura rica, marrom claro = ideal).

**Criticidade:** ALTA - Velas desgastadas causam falhas de combustao.

**Consequencias de nao fazer:** Dificuldade na partida especialmente com etanol, falhas de ignicao (motor falhando/trepidando), perda de potencia em ate 18%, aumento no consumo de combustivel em ate 25%, marcha lenta irregular, engasgos, emissoes poluentes elevadas, possivel danificacao do catalisador (R$ 1.800 a R$ 3.200).

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Onix 1.0 SPE/4 4un|4|178.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Onix 1.0 Flex|4|82.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Onix 1.0|4|88.00
SIMILAR|PI0108|Peca Nova|Jogo 4 Velas Ignicao Onix 1.0|4|85.00
[/PECAS]'
        ],

        [
            'Rodizio de Pneus e Alinhamento',
            20000,
            '24',
            155.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 50 minutos]

Execucao de rodizio dos pneus 185/65 R15 ou 185/60 R16 (conforme versao) seguindo padrao paralelo ou cruz. Verificacao de pressao (32 PSI dianteiros e traseiros sem carga, 34 PSI com carga). Inspecao de desgaste irregular indicando necessidade de alinhamento. Verificacao de cortes, bolhas, deformacoes. Alinhamento 3D das rodas dianteiras (veiculo nao possui regulagem traseira). Balanceamento eletronico das 4 rodas. Calibracao do sensor TPMS (se equipado).

**Criticidade:** MEDIA - Impacta seguranca, conforto e durabilidade.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 40%, vibracoes no volante e carroceria, perda de estabilidade direcional, aumento no consumo de combustivel em ate 8%, perda de aderencia em piso molhado aumentando risco de aquaplanagem, desgaste anormal da direcao eletrica.

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
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
ORIGINAL|52126118|Filtro Ar Motor GM Onix 1.0|1|95.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
SIMILAR|C24034|Mann|Filtro Ar Onix Prisma Spin 1.0 1.4|1|45.00
[/PECAS]'
        ],

        [
            'Troca de Fluido de Freio DOT 4',
            30000,
            '24',
            115.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico com ABS. Fluido higroscopico absorve umidade do ar reduzindo ponto de ebulicao e causando perda de eficiencia. Procedimento: sangria de todas as rodas e modulo ABS iniciando pela mais distante do cilindro mestre (traseira direita, traseira esquerda, dianteira direita, dianteira esquerda). Capacidade aproximada: 600ml. Utilizar apenas fluido DOT 4 homologado FMVSS 116. Intervalo critico: a cada 2 anos independente da quilometragem.

**Criticidade:** ALTA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Fluido contaminado com umidade causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico (cilindros mestre e roda, pincas, modulo ABS), necessidade de substituicao completa do sistema, falha do ABS, acidentes graves.

[PECAS]
ORIGINAL|93160364|Fluido de Freio DOT 4 GM 500ml|1|48.00
SIMILAR|DOT4-500ML|Bosch|Fluido Freio DOT 4 500ml|1|30.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response 500ml|1|35.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4 500ml|1|28.00
SIMILAR|DOT4-ATE|ATE|Fluido Freio Super DOT 4 500ml|1|38.00
[/PECAS]'
        ],

        [
            'Limpeza do Sistema de Injecao Eletronica',
            30000,
            '36',
            55.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 65 minutos]

Limpeza preventiva dos bicos injetores multiponto atraves de aditivo de alta qualidade aplicado no tanque de combustivel. Motor 1.0 SPE/4 Flex possui 4 bicos injetores que podem acumular depositos carboniferos especialmente com uso de etanol de baixa qualidade. Procedimento: abastecer tanque com gasolina aditivada, adicionar produto limpador de injetores, rodar em rodovia por pelo menos 50 km. Em casos severos, realizar limpeza por ultrassom em oficina especializada.

**Criticidade:** MEDIA - Preventiva para manter desempenho.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 15%, aumento no consumo em ate 20%, marcha lenta irregular, dificuldade na partida a frio, engasgos, formacao de depositos no coletor de admissao, necessidade de limpeza ultrassonica (R$ 400 a R$ 600).

[PECAS]
SIMILAR|FLEX-CLEAN|Wynns|Aditivo Limpador Sistema Flex 325ml|1|45.00
SIMILAR|INJ-CLEAN|Wurth|Limpador Injetores Flex 300ml|1|38.00
SIMILAR|TOP-CLEAN|Bardahl|Limpador Sistema Combustivel 200ml|1|42.00
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
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
ORIGINAL|52126118|Filtro Ar Motor GM Onix 1.0|1|95.00
ORIGINAL|25FC0225|Filtro Combustivel ACDelco Onix|1|82.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
SIMILAR|C24034|Mann|Filtro Ar Onix Prisma Spin 1.0 1.4|1|45.00
SIMILAR|WK58|Mann|Filtro Combustivel Onix 1.0|1|38.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            40000,
            '48',
            85.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Segunda troca das velas de ignicao conforme especificacoes da revisao de 20.000 km.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Onix 1.0 SPE/4 4un|4|178.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Onix 1.0 Flex|4|82.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Onix 1.0|4|88.00
[/PECAS]'
        ],

        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            155.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas) sistema Teves com ABS. Codigo Cobreq N-367, Fras-le PD/94, Jurid HQJ2294A, original GM 94748947. Freios a disco ventilado dianteiro (4 furos, diametro 258mm). Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas, verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos. Sangria se necessario. Teste em pista. Resetar indicador de desgaste no painel se equipado.

**Criticidade:** ALTA - Sistema de seguranca primaria.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos profundos nos discos, perda de eficiencia de frenagem em ate 45%, ruido metalico intenso, aumento da distancia de frenagem, necessidade de substituicao prematura dos discos, falha do sistema ABS, risco de acidentes graves.

[PECAS]
ORIGINAL|94748947|Jogo Pastilhas Freio Diant GM Onix|1|185.00
SIMILAR|N367|Cobreq|Jogo Pastilhas Freio Diant Onix 1.0|1|82.00
SIMILAR|HQJ2294A|Jurid|Jogo Pastilhas Freio Diant Onix|1|88.00
SIMILAR|PD/94|Fras-le|Jogo Pastilhas Freio Diant Onix|1|85.00
SIMILAR|BB1367|Bosch|Jogo Pastilhas Freio Diant Onix|1|92.00
[/PECAS]'
        ],

        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            95.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 50 minutos]

Substituicao da correia do alternador/ar condicionado (correia poly-V 5PK1051). Motor 1.0 SPE/4 utiliza correia unica acionando alternador, compressor do ar condicionado e bomba de direcao eletro-hidraulica (se equipada, versoes com direcao eletrica nao possuem). Verificacao do estado do tensionador automatico, polias lisas e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao. Tensionamento adequado conforme especificacao do fabricante.

**Criticidade:** MEDIA - Correia desgastada pode romper causando pane.

**Consequencias de nao fazer:** Rompimento da correia causando descarregamento da bateria, perda do ar condicionado, perda de direcao assistida (se eletro-hidraulica), possivel superaquecimento por sobrecarga eletrica prolongada, necessidade de guincho.

[PECAS]
ORIGINAL|93363200|Correia Alternador GM Onix 1.0|1|98.00
SIMILAR|5PK1051|Gates|Correia Poly-V Alternador Onix|1|42.00
SIMILAR|5PK1051-CONT|Continental|Correia Poly-V Onix 1.0|1|38.00
SIMILAR|K051051|Dayco|Correia Alternador Onix 1.0|1|40.00
SIMILAR|5PK1051-GY|Goodyear|Correia Auxiliar Onix 1.0|1|36.00
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
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
[/PECAS]'
        ],

        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            125.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 75 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor 1.0 SPE/4. GM recomenda fluido Dex-Cool (aditivo de longa duracao cor laranja) diluido 50/50 com agua desmineralizada. Capacidade total: aproximadamente 5,5 litros da mistura. Procedimento: drenagem pelo bujao do radiador, lavagem interna com agua, reabastecimento da mistura, sangria do sistema (eliminacao de bolhas de ar), funcionamento ate atingir temperatura normal (ventoinha acionando), verificacao de vazamentos e nivel.

**Criticidade:** ALTA - Fluido degradado perde propriedades anticorrosivas.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do radiador, bloco, cabecote e bomba dagua, formacao de borra e depositos reduzindo eficiencia de troca termica, superaquecimento, danos ao radiador, bomba dagua (R$ 280 a R$ 450), termostato (R$ 120 a R$ 220) e motor, possivel empenamento do cabecote.

[PECAS]
ORIGINAL|93302891|Aditivo Radiador Dex-Cool GM 3L|1|115.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada 3L|1|18.00
SIMILAR|PARAFLU-LL|Repsol|Aditivo Radiador Longa Duracao 3L|1|62.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico 3L|1|65.00
SIMILAR|RAD-PROTEC|Valvoline|Aditivo Radiador Universal 3L|1|58.00
[/PECAS]'
        ],

        [
            'Higienizacao Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            175.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 85 minutos]

Limpeza profissional do sistema de ar condicionado: aplicacao de espuma higienizadora no evaporador atraves da caixa de ar, aspiracao da espuma e residuos, aplicacao de bactericida/fungicida por nebulizacao, limpeza do dreno do evaporador (frequentemente entupido), troca do filtro de cabine. Verificacao de pressao do gas refrigerante R-134a ou R-1234yf (conforme ano), teste de vazamentos com detector eletronico, temperatura de saida (deve atingir 4-7 graus C). Teste de funcionamento do compressor, embreagem eletromagnetica e eletroventilador do condensador.

**Criticidade:** BAIXA - Conforto e qualidade do ar.

**Consequencias de nao fazer:** Proliferacao de fungos e bacterias no evaporador, mau cheiro persistente (odor de mofo), alergias respiratorias graves, obstrucao do dreno causando infiltracao de agua no assoalho e modulo eletronico, reducao da eficiencia do sistema em ate 40%.

[PECAS]
ORIGINAL|52102242|Filtro Ar Condicionado GM Onix|1|88.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado 500ml|1|52.00
SIMILAR|KLIMACLEAN|Wynns|Limpador Ar Condicionado Automotivo 500ml|1|58.00
SIMILAR|CU2442/2|Mann|Filtro Cabine Onix Prisma|1|40.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM ====================
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
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
ORIGINAL|52126118|Filtro Ar Motor GM Onix 1.0|1|95.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
SIMILAR|C24034|Mann|Filtro Ar Onix Prisma Spin 1.0 1.4|1|45.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            60000,
            '72',
            85.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Terceira troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Onix 1.0 SPE/4 4un|4|178.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Onix 1.0 Flex|4|82.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Onix 1.0|4|88.00
[/PECAS]'
        ],

        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            190.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 95 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio ventilados dianteiros 4 furos, diametro 258mm. Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada no disco (geralmente 10mm). Sangria do sistema. Teste em pista. Discos devem ser substituidos em par sempre. Resetar indicador de desgaste no painel se equipado.

**Criticidade:** CRITICA - Sistema de seguranca primaria.

[PECAS]
ORIGINAL|94748947|Jogo Pastilhas Freio Diant GM Onix|1|185.00
ORIGINAL|52126120|Par Discos Freio Diant GM Onix|2|445.00
SIMILAR|N367|Cobreq|Jogo Pastilhas Freio Diant Onix 1.0|1|82.00
SIMILAR|HQJ2294A|Jurid|Jogo Pastilhas Freio Diant Onix|1|88.00
SIMILAR|DF2367|Fremax|Par Discos Freio Ventilado Onix|2|255.00
SIMILAR|RC2367|Cobreq|Par Discos Freio Diant Onix|2|245.00
SIMILAR|BD2367|TRW|Par Discos Freio Onix 1.0|2|268.00
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
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
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
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
ORIGINAL|52126118|Filtro Ar Motor GM Onix 1.0|1|95.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
SIMILAR|C24034|Mann|Filtro Ar Onix Prisma Spin 1.0 1.4|1|45.00
[/PECAS]'
        ],

        [
            'Substituicao de Velas de Ignicao',
            80000,
            '96',
            85.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Quarta troca das velas de ignicao conforme especificacoes anteriores.

**Criticidade:** ALTA

[PECAS]
ORIGINAL|93363296|Jogo Velas Ignicao GM Onix 1.0 SPE/4 4un|4|178.00
SIMILAR|BR8ES|NGK|Jogo 4 Velas Ignicao Onix 1.0 Flex|4|82.00
SIMILAR|F000KE0P32|Bosch|Jogo 4 Velas Ignicao Onix 1.0|4|88.00
[/PECAS]'
        ],

        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            210.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 125 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores traseiros (sistema a tambor 200mm). Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos, cabo do freio de estacionamento. Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm. Sangria do sistema.

**Criticidade:** ALTA

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro em ate 60%, desbalanceamento da frenagem, freio de estacionamento inoperante (reprovacao na inspecao), necessidade de substituicao dos tambores, acidentes por frenagem deficiente.

[PECAS]
ORIGINAL|52126122|Jogo Lonas Freio Traseiro GM Onix|1|168.00
ORIGINAL|52126121|Par Tambores Freio Traseiro GM Onix|2|395.00
SIMILAR|HI1400|Fras-le|Jogo Lonas Freio Traseiro Onix|1|75.00
SIMILAR|N1400|Cobreq|Jogo Lonas Freio Traseiro Onix|1|70.00
SIMILAR|TT2400|TRW|Par Tambores Freio Traseiro Onix|2|225.00
SIMILAR|RT2400|Fremax|Par Tambores Freio Traseiro Onix|2|215.00
[/PECAS]'
        ],

        [
            'Substituicao de Amortecedores Dianteiros e Traseiros',
            80000,
            '96',
            295.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 160 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo telescopico) incluindo kits de reparo (coxins superiores, batentes, coifas). Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar repetidamente cada canto do veiculo, deve retornar a posicao sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento 3D apos a troca.

**Criticidade:** ALTA - Impacta seguranca, estabilidade e conforto.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo em ate 30%, aumento da distancia de frenagem em ate 25%, perda de estabilidade em curvas, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas, barra estabilizadora), desconforto severo aos ocupantes, trepidacao.

[PECAS]
ORIGINAL|52126124|Amortecedor Dianteiro GM Onix par|2|625.00
ORIGINAL|52126125|Amortecedor Traseiro GM Onix par|2|595.00
SIMILAR|HG33210|Monroe|Amortecedor Diant Onix Gas par|2|395.00
SIMILAR|HG33211|Monroe|Amortecedor Tras Onix Gas par|2|375.00
SIMILAR|AM33210|Cofap|Amortecedor Diant Onix Turbogas par|2|335.00
SIMILAR|AM33211|Cofap|Amortecedor Tras Onix Turbogas par|2|315.00
SIMILAR|N33210|Nakata|Amortecedor Diant Onix 1.0 par|2|305.00
SIMILAR|N33211|Nakata|Amortecedor Tras Onix 1.0 par|2|285.00
[/PECAS]'
        ],

        [
            'Substituicao de Correias Auxiliares',
            80000,
            '96',
            95.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 50 minutos]

Segunda troca da correia do alternador conforme especificacoes da revisao de 40.000 km.

**Criticidade:** MEDIA

[PECAS]
ORIGINAL|93363200|Correia Alternador GM Onix 1.0|1|98.00
SIMILAR|5PK1051|Gates|Correia Poly-V Alternador Onix|1|42.00
SIMILAR|5PK1051-CONT|Continental|Correia Poly-V Onix 1.0|1|38.00
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
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
[/PECAS]'
        ],

        [
            'Substituicao da Bateria',
            100000,
            '60',
            45.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 30 minutos]

Substituicao da bateria automotiva 12V. Chevrolet Onix 1.0 utiliza bateria de 50Ah a 60Ah com corrente de partida (CCA) de 420A a 480A. Baterias seladas livre de manutencao tem vida util de 4 a 6 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora. Configuracao de sistemas eletronicos (radio, relogio, computador de bordo, MyLink) apos troca. Dimensoes: 230mm x 175mm x 175mm. Resetar sistema Start-Stop se equipado.

**Criticidade:** MEDIA - Consumivel com vida util definida.

**Consequencias de nao fazer:** Falha de partida especialmente em dias frios, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, oxidacao dos terminais, perda de memoria dos sistemas eletronicos (MyLink, computador de bordo), falha do sistema Start-Stop, necessidade de reboque.

[PECAS]
ORIGINAL|52126130|Bateria 12V 60Ah ACDelco GM|1|485.00
SIMILAR|60GD-480|Moura|Bateria 12V 60Ah 480A Selada|1|335.00
SIMILAR|60D-500|Heliar|Bateria 12V 60Ah 500A Free|1|345.00
SIMILAR|B60DH|Bosch|Bateria 12V 60Ah S4 Free|1|375.00
SIMILAR|60AH-450|Zetta|Bateria 12V 60Ah Selada|1|295.00
[/PECAS]'
        ],

        [
            'Limpeza e Descarbonizacao do Sistema de Admissao',
            100000,
            '120',
            265.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 130 minutos]

Limpeza profunda do sistema de admissao, corpo de borboleta eletronico (TBI), coletor de admissao, sensores MAP e temperatura. Motor 1.0 SPE/4 Flex acumula depositos carboniferos no corpo de borboleta reduzindo desempenho especialmente com uso frequente de etanol. Procedimento: remocao e limpeza quimica do corpo de borboleta com produto especifico (spray limpa TBI), limpeza do coletor de admissao, limpeza dos sensores. Apos limpeza, realizar procedimento de reaprendizagem da marcha lenta com scanner OBD2.

**Criticidade:** MEDIA - Preventiva para manter desempenho.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 18%, aumento no consumo de combustivel em ate 22%, marcha lenta irregular (abaixo de 700 rpm), engasgos, luz do motor (check engine) acesa por falha na leitura dos sensores, aceleracao sem resposta (delay), falha no sistema Start-Stop.

[PECAS]
SIMILAR|TBI-CLEAN|Wynns|Limpador Corpo Borboleta 400ml|1|52.00
SIMILAR|CARB-CLEAN|Wurth|Limpador TBI/Admissao 500ml|1|48.00
SIMILAR|INTAKE-CLEAN|Bardahl|Limpador Sistema Admissao 300ml|1|55.00
[/PECAS]'
        ],

        // ==================== REVISAO 120.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            120000,
            '144',
            145.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

**Criticidade:** CRITICA

[PECAS]
ORIGINAL|94797556|Filtro de Oleo Motor GM Onix 1.0|1|72.00
ORIGINAL|93165557|Oleo Motor ACDelco 0W-20 Sintetico Dexos1 4L|4|280.00
ORIGINAL|52126118|Filtro Ar Motor GM Onix 1.0|1|95.00
SIMILAR|WUNI0003|Wega|Filtro Oleo Onix 1.0 2017-2019|1|28.00
SIMILAR|0W20-MOBIL-1L|Mobil|Oleo Mobil 1 0W-20 Sintetico 4L|4|165.00
SIMILAR|C24034|Mann|Filtro Ar Onix Prisma Spin 1.0 1.4|1|45.00
[/PECAS]'
        ],

        [
            'Substituicao de Correias Auxiliares',
            120000,
            '60',
            95.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 50 minutos]

Terceira troca da correia do alternador. Intervalo recomendado: 120.000 km ou 5 anos. Conforme especificacoes das revisoes anteriores.

**Criticidade:** MEDIA

[PECAS]
ORIGINAL|93363200|Correia Alternador GM Onix 1.0|1|98.00
SIMILAR|5PK1051|Gates|Correia Poly-V Alternador Onix|1|42.00
SIMILAR|5PK1051-CONT|Continental|Correia Poly-V Onix 1.0|1|38.00
[/PECAS]'
        ],

        // ==================== MANUTENCAO ESPECIAL 240.000 KM ====================
        [
            'SUBSTITUICAO DA CORREIA DENTADA + KIT COMPLETO',
            240000,
            '180',
            720.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 280 minutos]

ITEM DE MANUTENCAO PROGRAMADA DE LONGO PRAZO. Substituicao da correia dentada de sincronismo (137 dentes, dimensao 137x200), tensor automatico e polia tensora. Motor 1.0 SPE/4 possui correia dentada com intervalo de troca extremamente longo: 240.000 km ou 15 anos, o que ocorrer primeiro. Codigo Dayco: 137STP200HT. ATENCAO: Embora o intervalo seja longo, nao postergar alem do prazo pois o motor e do tipo interferente. Procedimento exige ferramentas especiais de sincronismo. Substituir tambem bomba dagua preventivamente.

**Criticidade:** ALTA - Falha causa danos severos ao motor.

**Consequencias de nao fazer:** Embora o intervalo seja longo, rompimento da correia dentada causa colisao entre pistoes e valvulas (motor interferente), empenamento/quebra de valvulas, danos aos pistoes e cabecote, necessidade de retifica do motor. CUSTO DE REPARO: R$ 9.000 a R$ 16.000.

[PECAS]
ORIGINAL|52126135|Correia Dentada GM Onix 1.0 137 dentes|1|385.00
ORIGINAL|52126136|Tensor Automatico Correia Dentada GM|1|485.00
ORIGINAL|52126137|Polia Tensora Correia Dentada GM|1|225.00
ORIGINAL|52126138|Bomba Dagua GM Onix 1.0|1|425.00
SIMILAR|137STP200HT|Dayco|Correia Dentada Onix 1.0 137x200|1|215.00
SIMILAR|CT137HT|Gates|Correia Dentada Onix 1.0|1|228.00
SIMILAR|T43137|Gates|Tensor Automatico Onix 1.0|1|285.00
SIMILAR|PA7137|Nakata|Polia Tensora Onix 1.0|1|135.00
SIMILAR|WP137|Nakata|Bomba Dagua Onix 1.0 SPE/4|1|225.00
SIMILAR|PA137|Urba|Bomba Dagua Onix 1.0|1|245.00
[/PECAS]'
        ],

        // ==================== ITENS ESPECIAIS POR TEMPO ====================
        [
            'Substituicao de Pneus 185/65 R15',
            50000,
            '60',
            65.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 85 minutos]

Chevrolet Onix utiliza pneus 185/65 R15 (versoes basicas) ou 185/60 R16 (versoes superiores). Vida util media: 45.000 a 60.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). Verificar mensalmente: pressao (32 PSI dianteiros e traseiros sem carga, 34 PSI com carga), desgaste da banda (minimo legal 1,6mm medido nos TWI - indicadores de desgaste), deformacoes, cortes laterais, data de fabricacao (codigo DOT nas laterais). Realizar rodizio a cada 10.000 km. Calibrar sensor TPMS se equipado.

**Criticidade:** CRITICA - Seguranca ativa do veiculo.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 40%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves, multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular, reprovacao em inspecao veicular, falha do sistema ABS/ESP.

[PECAS]
SIMILAR|185/65R15-PIR|Pirelli|Pneu Cinturato P1 185/65 R15 jogo 4un|4|1380.00
SIMILAR|185/65R15-BRI|Bridgestone|Pneu Turanza ER300 185/65 R15 jogo 4un|4|1320.00
SIMILAR|185/65R15-GY|Goodyear|Pneu Assurance 185/65 R15 jogo 4un|4|1280.00
SIMILAR|185/65R15-CONT|Continental|Pneu PowerContact 185/65 R15 jogo 4un|4|1350.00
SIMILAR|185/65R15-DUN|Dunlop|Pneu SP Touring R1 185/65 R15 jogo 4un|4|1220.00
[/PECAS]'
        ],

        [
            'Troca de Fluido de Freio DOT 4 - Por Tempo',
            0,
            '24',
            115.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Fluido de freio DOT 4 higroscopico degrada com o tempo mesmo sem uso do veiculo. Troca obrigatoria a cada 2 anos independente da quilometragem. Fluido absorve umidade do ar reduzindo ponto de ebulicao. Em frenagens intensas pode vaporizar causando perda de frenagem (fade). Este item e baseado em TEMPO, nao em quilometragem.

**Criticidade:** ALTA - Seguranca ativa.

**Consequencias de nao fazer:** Fluido contaminado causa vaporizacao em frenagens intensas, perda total de frenagem, oxidacao do sistema hidraulico, falha do ABS, acidentes graves.

[PECAS]
ORIGINAL|93160364|Fluido de Freio DOT 4 GM 500ml|1|48.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response 500ml|1|35.00
[/PECAS]'
        ],

        // ==================== ATENCAO ESPECIAL ====================
        [
            'VERIFICACAO - Oleo 0W-20 Obrigatorio Motor SPE/4',
            10000,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Atencao Especial] [TEMPO: Verificacao 5 minutos]

ESPECIFICACAO CRITICA DO OLEO: Motor 1.0 SPE/4 EXIGE obrigatoriamente oleo 0W-20 API SN norma Dexos1 Gen3. NAO utilizar 5W-30 em motores aspirados (5W-30 e apenas para versoes turbo). Uso de viscosidade incorreta causa desgaste prematuro, aumento de consumo, perda de potencia.

**SINAIS DE PROBLEMAS:**
- Aumento no consumo de combustivel
- Perda de potencia
- Ruido no motor na partida a frio
- Desgaste prematuro

**PREVENCAO:**
- SEMPRE utilizar oleo 0W-20 com certificacao Dexos1 Gen3
- Verificar especificacao no manual do proprietario
- Trocar rigorosamente a cada 10.000 km ou 12 meses
- Evitar misturas com oleos de viscosidade diferente
- Verificar nivel mensalmente

[PECAS]
Verificacao preventiva - sem custo de pecas
[/PECAS]'
        ],

        [
            'VERIFICACAO - Recall Modulo Controle Motor',
            0,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Atencao Especial] [TEMPO: Verificacao online 10 minutos]

RECALL CRITICO: Verificar se o veiculo esta incluido no recall do modulo de controle do motor (ECU). Defeito na calibracao pode causar aumento de pressao e temperatura na camara de combustao, causando danos no pistao e possivel quebra do bloco do motor. Risco de vazamento de oleo, incendio e explosao. Afetou principalmente Onix Plus 2020, mas veiculos 2018-2019 com motor similar devem ser verificados.

**PROCEDIMENTO:**
Verificar URGENTEMENTE no site www.chevrolet.com.br/servicos/recall ou telefone 0800 702 4200.
Reparo: atualizacao da calibracao do modulo de controle do motor.
SERVICO GRATUITO EM CONCESSIONARIA.

**SINAIS DE ALERTA:**
- Perda subita de potencia
- Ruido de batida metalica no motor (detonacao)
- Fumaca azul ou branca pelo escapamento
- Vazamento de oleo no compartimento do motor
- Cheiro de queimado

[PECAS]
Verificacao de recall - servico gratuito em concessionaria
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
