<?php
/**
 * Script para importar Plano de Manutencao VW 10.160 Delivery 2017-2020
 * Motor Cummins ISF 3.8 Diesel Euro V/VI com SCR (Arla 32)
 * Gerado via Perplexity AI em Janeiro/2026
 *
 * ATENCAO - VEICULO COMERCIAL COM REQUISITOS ESPECIAIS:
 * - Motor diesel common rail 1.800 bar
 * - Sistema SCR com Arla 32 (problema comum)
 * - Oleo OBRIGATORIO: 15W-40 CJ-4/CK-4 Low SAPS
 * - Capacidade oleo: 10,6 a 13 litros
 * - RECALL GRAVE: Solda eixo traseiro 2017-2019
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-vw-10160-delivery.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-vw-10160-delivery.php?confirmar=SIM',
        'modelo' => 'Volkswagen 10.160 Delivery',
        'motor' => 'Cummins ISF 3.8 Diesel Euro V/VI (160-175cv)',
        'alertas_criticos' => [
            'RECALL GRAVE: Solda eixo traseiro 2017-2019 - verificar urgente!',
            'Sistema Arla 32 e problema comum - atencao especial',
            'Oleo OBRIGATORIO: 15W-40 CJ-4/CK-4 Low SAPS',
            'Sistema common rail 1.800 bar - drenar agua semanalmente'
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
    // MODELO - Nome EXATO conforme banco de dados (verificado em verificar-modelos.php)
    $modeloNome = "10.160";

    // PASSO 1: Deletar planos antigos deste modelo
    $stmt = $conn->prepare("DELETE FROM `Planos_Manutenção` WHERE modelo_carro = ?");
    $stmt->bind_param("s", $modeloNome);
    $stmt->execute();
    $deletados = $stmt->affected_rows;
    $stmt->close();

    // PASSO 2: Definir itens do plano
    // Formato: [descricao_titulo, km, meses, custo_mao_obra, criticidade, observacao]
    $itens_plano = [
        // ==================== REVISAO 20.000 KM / 12 MESES ====================
        [
            'Troca de Oleo e Filtro do Motor Diesel',
            20000,
            '12',
            135.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Drenagem completa do oleo lubrificante do motor Cummins ISF 3.8 diesel. Substituicao do filtro de oleo tipo rosqueado. Reabastecimento com oleo diesel especificacao SAE 15W-40 API CJ-4 ou CK-4 para motores diesel com DPF.

CAPACIDADE: 10,6 a 13 litros

Motor ISF 3.8 de 160-175 cv com sistema common rail e pos-tratamento SCR requer oleo especifico de baixo teor de cinzas (Low SAPS) para nao danificar o filtro DPF.

ATENCAO: Em uso severo/urbano, reduzir para 10.000 km.

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste de pistoes, bronzinas, contaminacao do filtro DPF (R$ 8.000-14.000), entupimento dos bicos injetores common rail, possivel travamento do motor (R$ 18.000-35.000 retifica).

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|PSL283|Tecfil|Filtro Oleo VW 10.160 Diesel|1|40.00
SIMILAR|WOP1001|Mann|Filtro Oleo Cummins ISF 3.8|1|45.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
SIMILAR|15W40-MOBIL|Mobil|Oleo Delvac MX 15W-40 CJ-4|13L|385.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Combustivel Diesel',
            20000,
            '12',
            68.00,
            'Critica',
            '[CATEGORIA: Filtros] [TEMPO: 30 minutos]

Substituicao do filtro de combustivel diesel do sistema common rail de alta pressao. Filtro tipo blindado com separador de agua integrado.

Motor Cummins ISF possui sistema de injecao common rail com pressao de 1.800 bar, extremamente sensivel a impurezas e agua no diesel.

OBRIGATORIO drenar agua do separador a cada 5.000 km ou semanalmente. Sangria do sistema apos a troca.

**Consequencias de nao fazer:** Entupimento dos bicos injetores common rail, desgaste da bomba de alta pressao (R$ 8.500-12.000), falha na partida, aumento consumo diesel em ate 25%, necessidade de substituicao dos 4 bicos injetores (R$ 7.200 total).

[PECAS]
ORIGINAL|2P0127177|Filtro Combustivel VW Delivery Cummins ISF|1|155.00
SIMILAR|PSC706|Tecfil|Filtro Diesel 10.160 Separador de Agua|1|52.00
SIMILAR|RC828|Wega|Filtro Combustivel Delivery 10.160|1|48.00
SIMILAR|P555706|Mann|Filtro Diesel Cummins ISF 3.8|1|55.00
SIMILAR|FF5706|Fleetguard|Filtro Combustivel Cummins Original|1|58.00
[/PECAS]'
        ],
        [
            'Troca de Filtro Separador de Combustivel',
            20000,
            '12',
            45.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao do filtro separador de combustivel (pre-filtro) instalado antes do filtro principal. Este filtro remove agua e particulas maiores antes do filtro principal, protegendo o sistema common rail.

Sistema duplo de filtragem e essencial para motores diesel common rail. Drenar agua acumulada no copo do separador. Verificar vedacoes e O-rings.

**Consequencias de nao fazer:** Passagem de agua e impurezas para o filtro principal, saturacao precoce, corrosao do sistema de injecao, perda de potencia especialmente em subidas.

[PECAS]
SIMILAR|PEC7177|Parker|Filtro Separador Cummins ISF|1|58.00
SIMILAR|FS19925|Fleetguard|Separador Agua/Combustivel Cummins|1|62.00
SIMILAR|WK8158|Mann|Separador Combustivel Delivery|1|65.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar do Motor',
            20000,
            '12',
            35.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 15 minutos]

Substituicao do elemento filtrante de ar do motor Cummins ISF 3.8 diesel localizado na caixa de ar com indicador de restricao.

Motor diesel turbo e extremamente sensivel a impurezas que causam desgaste abrasivo nas palhetas do turbo (velocidade de 150.000 rpm).

Verificar indicador de restricao semanalmente - trocar se vermelho. Limpar pre-filtro (ciclone) a cada 5.000 km. Em ambientes com poeira, reduzir intervalo para 10.000 km.

**Consequencias de nao fazer:** Reducao de potencia ate 15%, aumento consumo ate 18%, desgaste catastrofico do turbocompressor (R$ 4.500-7.800), fumaça preta excessiva.

[PECAS]
SIMILAR|A1040|Tecfil|Filtro Ar Delivery Cummins ISF|1|68.00
SIMILAR|C27160|Mann|Filtro Ar Motor Diesel 10.160|1|72.00
SIMILAR|AF27840|Fleetguard|Filtro Ar Cummins ISF 3.8|1|78.00
SIMILAR|CA11180|Fram|Filtro Ar Delivery 10.160|1|75.00
[/PECAS]'
        ],
        [
            'Inspecao Sistema SCR e Arla 32',
            20000,
            '12',
            95.00,
            'Alta',
            '[CATEGORIA: Emissoes] [TEMPO: 35 minutos]

Inspecao completa do sistema de reducao catalitica seletiva (SCR) que utiliza Arla 32 (solucao de ureia 32%) para reduzir emissoes de NOx.

Verificacao: nivel e qualidade do Arla 32 (teor de ureia 32% +/-2% medido com refratometro), bomba Denoxtronic 2.2 (pressao 9 bar), bico injetor de Arla, sensores de NOx, catalisador DOC, filtro DPF, catalisador SCR.

ATENCAO: Problemas no sistema Arla sao defeitos comuns do VW 10.160. Verificar ausencia de contaminacao por oleo diesel no Arla.

**Consequencias de nao fazer:** Perda de potencia progressiva, luz de falha de motor, modo de emergencia, entupimento do filtro DPF (R$ 8.000-14.000), veiculo pode parar de funcionar, multas ambientais.

[PECAS]
SIMILAR|ARLA-20L|Diversos|Arla 32 Certificado ISO 22241|20L|85.00
SIMILAR|REFRATOMETRO|Diversos|Refratometro para Arla 32|1|145.00
[/PECAS]'
        ],
        [
            'Inspecao Geral de Seguranca Veicular',
            20000,
            '12',
            185.00,
            'Critica',
            '[CATEGORIA: Geral] [TEMPO: 75 minutos]

Inspecao visual e funcional completa conforme manual VW e normas ABNT NBR 16369 para frotas:

- Verificacao de niveis de fluidos (arrefecimento, freio, direcao hidraulica, Arla 32)
- Teste de luzes obrigatorias (farol, lanterna, freio, re, setas, emergencia), buzina, limpadores
- Inspecao de pneus (pressao conforme carga, desgaste, banda minima 1,6mm)
- Sistema de freios (pastilhas, lonas, discos, tambores, tubulacoes, ABS)
- Suspensao (amortecedores, molas, buchas, batentes, barra estabilizadora)
- Direcao hidraulica, escapamento, bateria, correias, sistema eletrico

ATENCAO ESPECIAL: Verificar recall do eixo traseiro. Verificar sistema ABS e embreagem - problemas comuns.

**Consequencias de nao fazer:** Nao identificacao de recall grave de solda do eixo traseiro (risco de quebra), falhas do sistema ABS, acidentes graves, multas pesadas (R$ 293-1.467), apreensao do veiculo.

[PECAS]
[/PECAS]'
        ],

        // ==================== REVISAO 40.000 KM / 24 MESES ====================
        [
            'Troca de Oleo e Filtros Completos',
            40000,
            '24',
            195.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 65 minutos]

Servico completo incluindo oleo do motor (13L 15W-40 CJ-4), filtros de oleo, combustivel (principal e separador) e ar conforme especificacoes da revisao de 20.000 km.

**Consequencias de nao fazer:** Acumulo de problemas das manutencoes anteriores, danos progressivos ao motor e sistemas.

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
ORIGINAL|2P0127177|Filtro Combustivel VW Delivery Cummins ISF|1|155.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
SIMILAR|PSC706|Tecfil|Filtro Diesel 10.160 Separador de Agua|1|52.00
SIMILAR|A1040|Tecfil|Filtro Ar Delivery Cummins ISF|1|68.00
SIMILAR|PEC7177|Parker|Filtro Separador Cummins ISF|1|58.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            40000,
            '24',
            145.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico pneumatico com ABS. Veiculo comercial possui sistema de freios a ar comprimido com circuito hidraulico auxiliar.

Fluido higroscopico absorve umidade do ar reduzindo ponto de ebulicao. Procedimento: sangria de todas as rodas e modulo ABS, drenagem dos reservatorios de ar (compressor), teste de vazamentos. Capacidade aproximada: 800ml. Utilizar apenas fluido DOT 4 ou DOT 5.1 homologado FMVSS 116.

INTERVALO CRITICO: a cada 2 anos independente da quilometragem. ATENCAO: Sistema ABS e problema comum.

**Consequencias de nao fazer:** Fluido contaminado causa vaporizacao em frenagens intensas (fade), perda total de frenagem em caminhao carregado, falha do ABS, acidentes gravissimos.

[PECAS]
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Veiculos Pesados|1L|42.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response Heavy|1L|48.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4 Comercial|1L|38.00
SIMILAR|DOT5.1-ATE|ATE|Fluido Freio Super DOT 5.1|1L|55.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            185.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 85 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 ou 8 pecas conforme sistema). Freios a disco dianteiro.

Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas (acima de 800C), verificacao dos pistoes e coifas. Espessura minima das pastilhas: 4mm. Medicao da espessura dos discos.

Verificacao do sistema de freio a ar (compressor, valvulas, reservatorios). Sangria se necessario. Teste em pista com peso.

CAMINHAO EXIGE ATENCAO ESPECIAL NOS FREIOS - CARGAS PESADAS.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos profundos nos discos, perda de eficiencia de frenagem em ate 50%, aumento da distancia de frenagem (critico com carga), risco de acidentes gravissimos.

[PECAS]
SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Delivery|1|165.00
SIMILAR|N1425|Cobreq|Jogo Pastilhas Freio Diant VW Comercial|1|158.00
SIMILAR|PD1425|Fras-le|Jogo Pastilhas Freio Diant 10.160|1|162.00
SIMILAR|TRW1425|TRW|Jogo Pastilhas Freio Diant Delivery|1|168.00
[/PECAS]'
        ],
        [
            'Substituicao de Lonas de Freio Traseiras',
            40000,
            '48',
            265.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 120 minutos]

Substituicao das lonas de freio traseiras (sapatas) do sistema a tambor. Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos, camaras de freio pneumaticas, valvulas de descarga rapida, cabo do freio de estacionamento mecanico.

Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento e freio motor (se equipado). Espessura minima das lonas: 3mm. Teste de pressao do sistema pneumatico (7-8 bar).

ATENCAO: Verificar recall do eixo traseiro.

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro, freio de estacionamento inoperante, agravamento do problema do recall do eixo traseiro.

[PECAS]
SIMILAR|HI2240|Fras-le|Jogo Lonas Freio Traseiro Delivery|1|185.00
SIMILAR|N2240|Cobreq|Jogo Lonas Freio Traseiro 10.160|1|178.00
SIMILAR|TRW2240|TRW|Jogo Lonas Freio Traseiro VW Comercial|1|188.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM / 36 MESES ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '36',
            195.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 65 minutos]

Servico completo conforme especificacoes anteriores. Motor com 60.000 km - verificar detalhadamente sistema de lubrificacao e injecao.

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
ORIGINAL|2P0127177|Filtro Combustivel VW Delivery Cummins ISF|1|155.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
SIMILAR|PSC706|Tecfil|Filtro Diesel 10.160 Separador de Agua|1|52.00
[/PECAS]'
        ],
        [
            'Troca do Sistema de Arrefecimento',
            60000,
            '36',
            155.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 85 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (anticongelante/aditivo + agua desmineralizada) do motor Cummins ISF 3.8 diesel.

OBRIGATORIO utilizar anticongelante para servicos pesados compativel com CES 14603. Diluicao 50/50 com agua desmineralizada.

Capacidade total: aproximadamente 12 litros da mistura. Procedimento: drenagem pelos bujoes do radiador e bloco, lavagem interna com agua, reabastecimento da mistura, sangria do sistema, funcionamento ate atingir temperatura normal, verificacao de vazamentos.

Intervalo: 80.000 km, 2.000 horas ou 2 anos (o que ocorrer primeiro).

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna severa do radiador, bloco, cabecote e bomba dagua, superaquecimento do motor diesel, danos ao radiador, bomba dagua (R$ 850-1.450), possivel empenamento ou trinca do cabecote (R$ 8.500 retifica).

[PECAS]
SIMILAR|HEAVY-DUTY|Shell|Anticongelante Diesel Heavy Duty CES14603|6L|175.00
SIMILAR|COOLANT-HD|Castrol|Anticongelante Radicool HD Diesel|6L|185.00
SIMILAR|RAD-HD|Valvoline|Anticongelante Heavy Duty Diesel|6L|165.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|6L|36.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM / 48 MESES - CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            80000,
            '48',
            195.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 65 minutos]

Servico completo conforme especificacoes anteriores. Marco de 80.000 km - motor diesel deve passar por inspecao mais detalhada.

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
[/PECAS]'
        ],
        [
            'Limpeza Sistema de Arrefecimento Completo',
            80000,
            '48',
            185.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 95 minutos]

MANUTENCAO OBRIGATORIA CUMMINS ISF: Limpeza completa do sistema de arrefecimento com produto quimico especifico.

Intervalo: 80.000 km, 2.000 horas ou 2 anos (o que ocorrer primeiro).

Drenagem total, limpeza quimica para remocao de borra e depositos, lavagem com agua desmineralizada, substituicao completa do anticongelante/aditivo, verificacao de mangueiras, abracadeiras, tampa de pressao do radiador. Teste de pressurizacao do sistema.

[PECAS]
SIMILAR|FLUSH-HD|Wynns|Limpador Sistema Arrefecimento Diesel|500ML|55.00
SIMILAR|HEAVY-DUTY|Shell|Anticongelante Diesel Heavy Duty CES14603|6L|175.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|6L|36.00
[/PECAS]'
        ],
        [
            'Substituicao de Discos e Pastilhas Dianteiros',
            80000,
            '60',
            245.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 135 minutos]

Substituicao completa do conjunto: jogo de pastilhas + par de discos de freio dianteiros.

Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada. Sangria do sistema. Teste em pista com peso.

DISCOS DEVEM SER SUBSTITUIDOS EM PAR SEMPRE. CAMINHAO CARREGADO EXIGE FREIOS PERFEITOS.

**Consequencias de nao fazer:** Freios ineficientes com carga, acidentes gravissimos, responsabilizacao criminal.

[PECAS]
SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Delivery|1|165.00
SIMILAR|DF2425|Fremax|Par Discos Freio 10.160|2|585.00
SIMILAR|RC2425|Cobreq|Par Discos Freio Diant Delivery|2|565.00
[/PECAS]'
        ],
        [
            'Substituicao de Amortecedores Completo',
            80000,
            '60',
            385.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 185 minutos]

Substituicao do conjunto de amortecedores dianteiros e traseiros incluindo buchas e batentes.

Caminhao VW 10.160 possui suspensao dianteira com feixe de molas e suspensao traseira parabolizada ou convencional. Amortecedores desgastados perdem capacidade causando perda de estabilidade com carga, desconforto e desgaste irregular de pneus.

Teste: pressionar cada canto do veiculo, deve retornar sem oscilar. Inspecao de vazamento de oleo. Verificar molas, grampos, pinos.

CAMINHAO COM CARGA EXIGE AMORTECEDORES EM PERFEITO ESTADO.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem em ate 25%, perda de estabilidade com carga (risco de tombamento em curvas), desgaste irregular dos pneus, risco de tombamento com carga lateral.

[PECAS]
SIMILAR|HG36120|Monroe|Amortecedor Diant Delivery Gas HD|2|685.00
SIMILAR|HG36121|Monroe|Amortecedor Tras Delivery Gas HD|2|645.00
SIMILAR|AM36120|Cofap|Amortecedor Diant VW Comercial|2|585.00
SIMILAR|AM36121|Cofap|Amortecedor Tras VW Comercial|2|545.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            80000,
            '24',
            145.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Segunda troca do fluido de freio DOT 4. Manter intervalo de 2 anos independente da quilometragem.

[PECAS]
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Veiculos Pesados|1L|42.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response Heavy|1L|48.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4 Comercial|1L|38.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM / 60 MESES ====================
        [
            'Troca de Oleo e Filtros Completos',
            100000,
            '60',
            195.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 65 minutos]

Servico completo conforme especificacoes anteriores. Marco de 100.000 km.

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
SIMILAR|PSC706|Tecfil|Filtro Diesel 10.160 Separador de Agua|1|52.00
[/PECAS]'
        ],

        // ==================== REVISAO 120.000 KM / 72 MESES - CRITICA ARLA ====================
        [
            'Troca de Oleo e Filtros Completos',
            120000,
            '72',
            195.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 65 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
[/PECAS]'
        ],
        [
            'Substituicao Obrigatoria do Filtro de Arla 32',
            120000,
            '12',
            225.00,
            'Critica',
            '[CATEGORIA: Emissoes] [TEMPO: 75 minutos]

MANUTENCAO CRITICA DO SISTEMA SCR: Substituicao do filtro de Arla 32 (ureia).

INTERVALO OBRIGATORIO: 120.000 km ou 12 meses (o que ocorrer primeiro).

Drenagem e limpeza completa do tanque de Arla 32, substituicao do filtro, verificacao da bomba Denoxtronic 2.2, bico injetor de Arla, tubulacoes. Teste de contaminacao com fita reagente (nao pode ter oleo diesel). Teste de concentracao com refratometro (32% +/-2% de ureia). Verificacao de cristalizacao.

SISTEMA DE ARLA E PROBLEMA COMUM DO VW 10.160. Limpeza do tanque e essencial.

**Consequencias de nao fazer:** Entupimento progressivo do sistema de Arla por cristalizacao de ureia, perda de potencia severa, modo de emergencia (torque reduzido), bico injetor de Arla entupido (R$ 1.800-3.200), bomba Denoxtronic danificada (R$ 3.500-5.800), necessidade de substituicao do tanque, tubulacoes, bomba e sensores (R$ 8.000-15.000), veiculo pode parar de funcionar, multas ambientais.

[PECAS]
SIMILAR|FILTRO-ARLA|Diversos|Filtro Sistema SCR Arla 32|1|185.00
SIMILAR|LIMPA-SCR|Wynns|Limpador Sistema SCR Arla 32|500ML|125.00
[/PECAS]'
        ],
        [
            'Limpeza e Manutencao Sistema DPF/DOC/SCR',
            120000,
            '72',
            485.00,
            'Alta',
            '[CATEGORIA: Emissoes] [TEMPO: 180 minutos]

Manutencao completa do sistema de pos-tratamento de emissoes: limpeza do filtro DPF (filtro de particulas diesel), catalisador DOC (oxidacao diesel), catalisador SCR (reducao catalitica seletiva).

Procedimento: remocao do conjunto, limpeza profissional com equipamento especifico (forno de limpeza ou hidrojateamento reverso), teste de pressao diferencial, verificacao dos sensores de pressao diferencial, sensores de temperatura, sensores de NOx (pre e pos catalisador).

Regeneracao forcada do DPF se necessario. Sistema complexo com DOC, DPF e SCR.

**Consequencias de nao fazer:** Entupimento progressivo do filtro DPF por acumulo de fuligem, aumento da contrapressao do escape em ate 300%, perda severa de potencia, aumento consumo diesel em ate 35%, necessidade de substituicao do DPF (R$ 8.000-14.000), veiculo pode parar de funcionar.

[PECAS]
SIMILAR|LIMPA-DPF|Wynns|Limpador Filtro DPF Diesel|500ML|135.00
SIMILAR|ADITIVO-DPF|Bardahl|Aditivo Limpeza DPF|200ML|145.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            120000,
            '24',
            145.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Terceira troca do fluido de freio DOT 4. Manter intervalo de 2 anos.

[PECAS]
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Veiculos Pesados|1L|42.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4 Comercial|1L|38.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            120000,
            '48',
            185.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 85 minutos]

Terceira troca das pastilhas dianteiras.

[PECAS]
SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Delivery|1|165.00
SIMILAR|N1425|Cobreq|Jogo Pastilhas Freio Diant VW Comercial|1|158.00
SIMILAR|PD1425|Fras-le|Jogo Pastilhas Freio Diant 10.160|1|162.00
[/PECAS]'
        ],
        [
            'Substituicao de Lonas de Freio Traseiras',
            120000,
            '48',
            265.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 120 minutos]

Terceira troca das lonas traseiras.

[PECAS]
SIMILAR|HI2240|Fras-le|Jogo Lonas Freio Traseiro Delivery|1|185.00
SIMILAR|N2240|Cobreq|Jogo Lonas Freio Traseiro 10.160|1|178.00
SIMILAR|TRW2240|TRW|Jogo Lonas Freio Traseiro VW Comercial|1|188.00
[/PECAS]'
        ],

        // ==================== REVISAO 160.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            160000,
            '96',
            195.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 65 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
SIMILAR|PSC706|Tecfil|Filtro Diesel 10.160 Separador de Agua|1|52.00
[/PECAS]'
        ],
        [
            'Substituicao de Discos e Pastilhas Dianteiros',
            160000,
            '60',
            245.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 135 minutos]

Segunda substituicao completa do conjunto de freio dianteiro.

[PECAS]
SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Delivery|1|165.00
SIMILAR|DF2425|Fremax|Par Discos Freio 10.160|2|585.00
SIMILAR|RC2425|Cobreq|Par Discos Freio Diant Delivery|2|565.00
[/PECAS]'
        ],

        // ==================== REVISAO 200.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            200000,
            '120',
            195.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 65 minutos]

Servico completo conforme especificacoes anteriores. Marco de 200.000 km.

[PECAS]
ORIGINAL|2P0115403|Filtro de Oleo Motor VW 10.160 Cummins ISF|1|125.00
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|13L|585.00
SIMILAR|WO612|Wega|Filtro Oleo Delivery Cummins ISF|1|42.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|395.00
[/PECAS]'
        ],

        // ==================== REVISAO 240.000 KM - REGULAGEM VALVULAS ====================
        [
            'Regulagem das Valvulas no Cabecote',
            240000,
            '0',
            685.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 240 minutos]

MANUTENCAO OBRIGATORIA CUMMINS ISF: Regulagem da folga das valvulas no cabecote do motor.

Recomenda-se que a folga das valvulas seja verificada com 240.000 km e, depois dessa verificacao, a cada 81.000 km.

Motor diesel Cummins ISF possui valvulas que requerem regulagem periodica diferente de motores a gasolina. Procedimento complexo: remocao da tampa de valvulas, medicao da folga de cada valvula com calibrador de laminas, ajuste com ferramentas especiais.

FOLGAS INCORRETAS CAUSAM PERDA DE DESEMPENHO SEVERA. Substituir junta da tampa se necessario.

**Consequencias de nao fazer:** Folgas inadequadas (muito abertas ou fechadas) causam perda de compressao, perda de potencia em ate 20%, aumento consumo diesel em ate 25%, ruido excessivo de castanholas no cabecote, dificuldade na partida, fumaca excessiva, desgaste prematuro das valvulas, possivel quebra de valvulas, danos ao cabecote (R$ 8.500 retifica).

[PECAS]
SIMILAR|JUNTA-TAMPA|Mahle|Junta Tampa Valvulas Diesel 3.8|1|125.00
[/PECAS]'
        ],

        // ==================== ALERTAS E PROBLEMAS CONHECIDOS ====================
        [
            'RECALL GRAVISSIMO - Solda Eixo Traseiro 2017-2019',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

PROBLEMA ESTRUTURAL GRAVE: Falha no processo de solda da friccao da ponteira da carcaca do eixo traseiro. Em alguns casos, pode ocorrer trinca ou quebra dos suportes, ocasionando o desacoplamento da suspensao em relacao ao eixo traseiro.

VEICULOS AFETADOS: Aproximadamente 7.800 unidades de onibus e caminhoes Delivery, incluindo VW 10.160, fabricados entre 2017 e 2019.

SINAIS DE ALERTA:
- Ruidos metalicos vindos do eixo traseiro
- Vibracoes anormais na traseira
- Desalinhamento visivel do eixo
- Trincas visiveis nos suportes da suspensao
- Deslocamento lateral do eixo

RISCOS: Quebra do eixo traseiro em movimento causa PERDA TOTAL DE CONTROLE DO VEICULO, TOMBAMENTO, ACIDENTES GRAVISSIMOS, MORTES. Risco de responsabilizacao criminal do proprietario/empresa.

PROCEDIMENTO: VERIFICAR URGENTEMENTE pelo site www.vwco.com.br ou telefone VW Caminhoes 0800-770-7099. Reparo: reforco ou substituicao da carcaca do eixo traseiro. SERVICO GRATUITO EM CONCESSIONARIA AUTORIZADA.

NAO OPERAR O VEICULO ATE VERIFICAR E REALIZAR O RECALL.

[PECAS]
[/PECAS]'
        ],
        [
            'ALERTA GRAVE - Sistema de Arla 32 (SCR)',
            0,
            '0',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

Problema extremamente comum e recorrente no VW 10.160: falhas no sistema SCR de Arla 32, bomba Denoxtronic, sensores, cristalizacao de ureia, contaminacao.

SINAIS DE ALERTA:
- Luz de advertencia de Arla 32 acesa no painel
- Mensagem Sistema de Tratamento de Emissoes com Falha
- Perda progressiva de potencia
- Modo de emergencia (torque reduzido)
- Consumo anormal de Arla (muito alto ou muito baixo)
- Motor nao atinge potencia maxima

CAUSAS PROVAVEIS:
- Arla 32 de baixa qualidade ou contaminado
- Contaminacao do tanque por oleo diesel
- Cristalizacao de ureia nos bicos e tubulacoes
- Bomba Denoxtronic defeituosa
- Sensores de NOx defeituosos
- Filtro de Arla entupido

PREVENCAO:
- Usar SEMPRE Arla 32 certificado ISO 22241
- Abastecer apenas em postos confiaveis
- Trocar filtro de Arla rigorosamente aos 120.000 km ou 12 meses
- Limpar tanque de Arla na troca do filtro
- Verificar nivel diariamente
- NUNCA misturar agua comum no tanque de Arla
- Usar refratometro para testar concentracao (32% +/-2%)
- Verificar ausencia de contaminacao por oleo com fita reagente

Se acontecer contaminacao, e um problema grave e sera necessario fazer uma manutencao geral no sistema: tirar tanque, examinar filtro e a bomba de Arla.

CUSTO DE REPARO: R$ 3.500 a R$ 15.000 (conforme gravidade)

[PECAS]
SIMILAR|ARLA-20L|Diversos|Arla 32 Certificado ISO 22241|20L|85.00
[/PECAS]'
        ],
        [
            'ALERTA - Falha 559 (Valvula Controle Pressao Rail)',
            0,
            '0',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

Problema comum relatado: VW DELIVERY 10.160 COM PERDA DE POTENCIA, FALHA 559. Codigo de falha 559 indica problema no controle de pressao de combustivel do sistema common rail.

CAUSA COMUM: Obstrucao no retorno de oleo por mangueira dobrada.

SINAIS DE ALERTA:
- Perda de potencia principalmente em subidas
- Codigo de falha 559 no scanner
- Motor nao atinge rotacao maxima
- Falha na injecao
- Possivel troca de pecas sem resultado (MCV, sensor de pressao)

CAUSA REAL IDENTIFICADA: Mangueira de retorno dobrada impedindo a passagem, o retorno fica obstruido, a bomba de alta nao consegue controlar a pressao. A bomba de alta pressao tenta modular atraves da valvula reguladora (MCV), mas o oleo nao retorna causando excesso de pressao no rail.

PREVENCAO:
- Inspecao visual periodica das mangueiras de retorno de combustivel
- Verificar se mangueiras nao estao dobradas, amassadas ou obstruidas
- Ao trocar componentes do sistema de injecao, verificar retorno livre
- ANTES de trocar pecas caras (MCV, sensores), verificar retorno

CUSTO DE REPARO: R$ 150 (mangueira) a R$ 8.500 (se trocar pecas erradas sem diagnosticar)

[PECAS]
[/PECAS]'
        ],
        [
            'ALERTA - Oleo Motor OBRIGATORIO 15W-40 CJ-4/CK-4',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

Motor Cummins ISF 3.8 com sistema SCR e filtro DPF EXIGE oleo diesel especificacao SAE 15W-40 API CJ-4 ou CK-4 Low SAPS (baixo teor de cinzas).

NAO USAR:
- Oleo CH-4, CI-4 ou especificacoes antigas
- Oleo com alto teor de cinzas (SAPS normal)
- Oleo mineral ou semi-sintetico de baixa qualidade

SINAIS DE PROBLEMA COM OLEO ERRADO:
- Entupimento prematuro do filtro DPF
- Regeneracoes frequentes do DPF
- Perda de potencia
- Consumo de oleo elevado
- Luz de falha de motor

PREVENCAO:
- SEMPRE utilizar oleo 15W-40 CJ-4 ou CK-4 Low SAPS
- NUNCA usar CH-4, CI-4 ou especificacoes antigas
- Oleo errado contamina o filtro DPF (R$ 8.000 a R$ 14.000)
- Capacidade: 10,6 a 13 litros
- Trocar rigorosamente a cada 20.000 km ou 12 meses
- Em uso severo/urbano: reduzir para 10.000 km
- Usar filtro de oleo de qualidade
- Verificar nivel semanalmente

[PECAS]
ORIGINAL|G055577M2|Oleo Motor VW Diesel 15W-40 CJ-4 Low SAPS|1L|45.00
SIMILAR|15W40-SHELL|Shell|Oleo Rimula R4 X 15W-40 CJ-4 Diesel|1L|30.00
SIMILAR|15W40-MOBIL|Mobil|Oleo Delvac MX 15W-40 CJ-4|1L|29.00
[/PECAS]'
        ],
        [
            'ALERTA - Sistema ABS e Embreagem',
            0,
            '0',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

Falhas recorrentes no sistema ABS e embreagem do VW 10.160.

SISTEMA ABS - SINAIS DE ALERTA:
- Luz de ABS acesa no painel
- ABS nao atuando em frenagens
- Ruidos anormais ao frear
- Pedal de freio pulsando excessivamente

PREVENCAO ABS:
- Trocar fluido de freio rigorosamente a cada 2 anos
- Verificar sensores de roda (sujeira, folga, danos)
- Verificar conexoes eletricas dos sensores
- Sangria correta incluindo modulo ABS
- Diagnostico com scanner especifico

EMBREAGEM - SINAIS DE ALERTA:
- Embreagem patinando
- Dificuldade em engatar marchas
- Ruidos ao pisar na embreagem
- Pedal muito duro ou muito mole
- Cheiro de queimado

PREVENCAO EMBREAGEM:
- Evitar cavalos de pau e arrancadas bruscas com carga
- Nao apoiar o pe no pedal da embreagem durante conducao
- Trocar fluido da embreagem hidraulica junto com freio
- Regulagem correta do curso do pedal
- Inspecao periodica do disco, plato e rolamento

CUSTO SUBSTITUICAO EMBREAGEM: R$ 2.800 a R$ 4.500 (kit completo + mao de obra)

[PECAS]
[/PECAS]'
        ],
        [
            'Verificacao Diaria - Arla 32 e Separador de Agua',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Geral] [TEMPO: 15 minutos]

VERIFICACOES OBRIGATORIAS DIARIAS PELO MOTORISTA:

1. NIVEL DE ARLA 32:
- Verificar nivel no tanque (minimo 10%)
- Verificar qualidade visual (nao pode estar turvo, escuro ou com cheiro forte)
- Verificar ausencia de contaminacao
- Reabastecer apenas com Arla 32 certificado ISO 22241
- A falta do liquido pode fazer o sistema falhar e danificar os componentes, alem de limitar o desempenho do veiculo
- Verificar luz de advertencia de Arla no painel

2. SEPARADOR DE AGUA DO DIESEL:
- Drenar agua acumulada no copo do filtro separador de combustivel
- Drenar semanalmente ou a cada 5.000 km, o que ocorrer primeiro
- Abrir valvula de dreno no fundo do copo ate sair apenas diesel limpo (sem agua)
- Agua no sistema common rail causa danos catastroficos

3. VERIFICACOES ADICIONAIS:
- Pressao dos pneus conforme carga
- Niveis de fluidos
- Funcionamento de luzes
- Freios (teste inicial)

[PECAS]
SIMILAR|ARLA-20L|Diversos|Arla 32 Certificado ISO 22241|20L|85.00
[/PECAS]'
        ],
        [
            'Verificacao Pneus - Semanal/Conforme Carga',
            0,
            '0',
            95.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 30 minutos]

VW 10.160 Delivery utiliza pneus 215/75 R17.5 ou 215/80 R16 (conferir especificacao). Vida util media: 60.000 a 80.000 km ou 5 anos (o que vier primeiro).

PRESSAO VARIA CONFORME CARGA: consultar tabela no batente da porta. Geralmente 80-110 PSI dianteiros e 90-120 PSI traseiros conforme carga.

VERIFICAR DIARIAMENTE ANTES DE OPERAR:
- Pressao
- Desgaste da banda (minimo legal 2,0mm para caminhoes)
- Deformacoes
- Cortes laterais
- Objetos cravados
- Data de fabricacao (codigo DOT)

Realizar rodizio a cada 20.000 km.

CALIBRAGEM INCORRETA E A PRINCIPAL CAUSA DE ESTOURO DE PNEUS EM CAMINHOES.

**Consequencias de nao fazer:** Pneus velhos/gastos/mal calibrados aumentam distancia de frenagem em ate 50%, aquaplanagem em piso molhado, estouro em velocidade causando perda total de controle (tombamento, saida de pista), multa gravissima especifica para caminhoes (R$ 1.467,35) e 7 pontos na CNH, apreensao do veiculo e carga.

[PECAS]
SIMILAR|215/75R17.5|Firestone|Pneu FS400 215/75 R17.5 Carga|6|3580.00
SIMILAR|215/75R17.5|Bridgestone|Pneu R268 215/75 R17.5|6|3680.00
SIMILAR|215/75R17.5|Continental|Pneu HSC1 215/75 R17.5|6|3620.00
[/PECAS]'
        ],
        [
            'Verificacao Compressor de Ar - 10.000 km',
            10000,
            '6',
            65.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 25 minutos]

Verificacao do compressor de ar do sistema de freios pneumatico:

- Teste de pressao maxima (7-8 bar)
- Tempo de recarga dos reservatorios
- Verificacao de vazamentos
- Drenagem de agua dos reservatorios
- Inspecao da correia do compressor
- Teste do governador de pressao
- Verificacao das valvulas de descarga rapida

Drenagem DIARIA dos reservatorios e essencial.

[PECAS]
[/PECAS]'
        ]
    ];

    // PASSO 3: Inserir na tabela
    $stmt = $conn->prepare("
        INSERT INTO `Planos_Manutenção`
        (modelo_carro, descricao_titulo, km_recomendado, intervalo_tempo, custo_estimado, criticidade, descricao_observacao)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $inseridos = 0;
    $erros = [];

    foreach ($itens_plano as $index => $item) {
        try {
            $descricao = $item[0];
            $km = $item[1];
            $meses = $item[2];
            $custo = $item[3];
            $criticidade = $item[4];
            $observacao = $item[5];

            $stmt->bind_param("ssissss", $modeloNome, $descricao, $km, $meses, $custo, $criticidade, $observacao);
            $stmt->execute();
            $inseridos++;
        } catch (Exception $e) {
            $erros[] = "Item " . ($index + 1) . " ({$item[0]}): " . $e->getMessage();
        }
    }
    $stmt->close();

    // Resposta
    $response = [
        'success' => true,
        'modelo' => $modeloNome,
        'motor' => 'Cummins ISF 3.8 Diesel Euro V/VI (160-175cv)',
        'planos_deletados' => $deletados,
        'planos_inseridos' => $inseridos,
        'total_itens' => count($itens_plano),
        'message' => "Plano de manutencao para {$modeloNome} importado com sucesso!",
        'caracteristicas' => [
            'tipo' => 'Caminhao comercial leve',
            'motor' => 'Cummins ISF 3.8 diesel common rail 1.800 bar',
            'sistema_emissoes' => 'SCR com Arla 32 + DPF + DOC',
            'oleo' => '15W-40 CJ-4/CK-4 Low SAPS - 10,6 a 13 litros',
            'freios' => 'Disco dianteiro + Tambor traseiro + Pneumatico + ABS'
        ],
        'alertas_criticos' => [
            'RECALL GRAVE: Solda eixo traseiro 2017-2019',
            'Sistema Arla 32 e problema comum',
            'Falha 559 - verificar mangueira de retorno',
            'Sistema ABS e embreagem - problemas recorrentes'
        ],
        'proximo_passo' => 'Verificar em https://frotas.in9automacao.com.br/planos-manutencao-novo.html'
    ];

    if (!empty($erros)) {
        $response['avisos'] = $erros;
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
