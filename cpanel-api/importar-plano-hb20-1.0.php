<?php
/**
 * Script para importar Plano de Manutencao Hyundai HB20 1.0 T-GDI Turbo Flex 2020-2021
 * Gerado via Perplexity AI em 2026-01-14
 * Motor: Kappa 1.0 T-GDI 3 cilindros 12V Turbo - Injecao Direta
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-hb20-1.0.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-hb20-1.0.php?confirmar=SIM',
        'modelo' => 'Hyundai HB20 1.0 T-GDI Turbo Flex 2020-2021',
        'motor' => 'Kappa 1.0 T-GDI 3 cilindros 12V Turbo - Injecao Direta',
        'oleo' => '5W-30 100% Sintetico API SN ACEA A5 - 3.6 litros',
        'velas' => 'NGK SILZKR8E8D Laser Iridium (3 unidades)',
        'atencao' => [
            'RECALL CRITICO - Cilindro mestre de freio 2021',
            'Motor GDI requer walnut blasting aos 100.000 km',
            'Turbo sensivel - NUNCA desligar motor quente imediatamente'
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
    $modeloNome = "HB20";

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
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Drenagem completa do oleo lubrificante do motor Kappa 1.0 T-GDI (Turbo Gasoline Direct Injection) de 3 cilindros 12V atraves do bujao do carter. Substituicao do filtro de oleo tipo cartucho codigo Hyundai 2630002503 e reabastecimento com oleo 100% sintetico especificacao SAE 5W-30 API SN ou superior ACEA A5/B5. Capacidade: 3,6 litros com filtro para motor turbo. Motor turboalimentado com injecao direta de alta pressao (350 bar) requer oleo sintetico de baixa viscosidade para maxima eficiencia, protecao do turbocompressor e economia de combustivel. Criterio: o que ocorrer primeiro (10.000 km OU 12 meses).

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado do turbocompressor (componente de R$ 3.500 a R$ 6.500), pistoes, bronzinas e eixo comando de valvulas, acumulo de borra e depositos carboniferos no sistema de injecao direta, oxidacao interna, superaquecimento do turbo, perda de eficiencia em ate 20%, possivel travamento ou quebra do motor exigindo retifica completa (R$ 12.000 a R$ 18.000).

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|PSL166|Tecfil|Filtro Oleo HB20 Creta 1.0|1|28.00
SIMILAR|PH6607|Fram|Filtro Oleo HB20 1.0 12V|1|30.00
SIMILAR|W6730|Mann|Filtro Oleo HB20 1.0 Turbo|1|35.00
SIMILAR|JFO0H00|Wega|Filtro Oleo HB20 1.0|1|26.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|5W30-CASTROL|Castrol|Oleo Edge 5W-30 Sintetico|4L|195.00
SIMILAR|5W30-SHELL|Shell|Oleo Helix Ultra 5W-30 Sintetico|4L|178.00
SIMILAR|5W30-PETRONAS|Petronas|Oleo Syntium 5W-30 API SN|4L|165.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            70.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao do filtro de combustivel de alta pressao do sistema GDI (Gasoline Direct Injection). Motor T-GDI possui bomba de alta pressao que opera a 350 bar injetando combustivel diretamente na camara de combustao. Filtro remove impurezas microscopicas da gasolina/etanol protegendo sistema de injecao de alta precisao e turbocompressor. ATENCAO: Despressurizar o sistema antes da remocao (chave na posicao ON sem dar partida por 5 segundos, OFF por 10 segundos, repetir 3 vezes). Sistema de alta pressao requer cuidado especial.

**Consequencias de nao fazer:** Entupimento dos injetores de alta pressao (R$ 1.800 a R$ 3.200 cada), falha na partida especialmente com etanol, perda de potencia, aumento no consumo em ate 25%, depositos carboniferos nas valvulas de admissao e camara de combustao, marcha lenta irregular, engasgos, danos a bomba de alta pressao (R$ 2.800 a R$ 4.500).

[PECAS]
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
SIMILAR|JFC1022|Wega|Filtro Combustivel HB20 1.0|1|38.00
SIMILAR|G7945|Fram|Filtro Combustivel HB20 1.0|1|45.00
SIMILAR|WK8047|Mann|Filtro Combustivel HB20 1.0|1|48.00
[/PECAS]'
        ],
        [
            'Troca de Anel de Vedacao do Bujao de Oleo',
            10000,
            '12',
            0.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: incluido na troca de oleo]

Substituicao obrigatoria do anel de vedacao (arruela de aluminio) do bujao de drenagem de oleo a cada troca de oleo. Anel deforma plasticamente durante o aperto criando vedacao. Reutilizacao causa vazamento de oleo. Torque de aperto: 35-45 Nm conforme especificacao Hyundai.

**Consequencias de nao fazer:** Vazamento de oleo pelo bujao, perda gradual de lubrificante, risco de dano ao motor por nivel baixo, contaminacao ambiental, piso escorregadio sob o veiculo.

[PECAS]
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|ARR-CARTER-14|Universal|Arruela Vedacao Aluminio 14mm|1|3.50
SIMILAR|ARR-CARTER-HYU|Apex|Arruela Carter Hyundai|1|4.00
[/PECAS]'
        ],
        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            135.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 60 minutos]

Inspecao visual e funcional completa conforme manual Hyundai: verificacao de niveis (arrefecimento, freio, direcao, limpador, bateria), teste de luzes LED/halogenio, buzina, limpadores, travas, vidros eletricos, sistemas ADAS (se equipado); inspecao de pneus (pressao 33 PSI frio, desgaste, banda minima 1,6mm), freios (pastilhas, discos, tubulacoes, ATENCAO ESPECIAL: verificar cilindro mestre por recall), suspensao (amortecedores, buchas, batentes), direcao eletrica assistida MDPS, escapamento com catalisador SCR, bateria 12V, correias, velas, sensor TPMS.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos ou recall de freio, acidentes por falha de freios ou pneus, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular, agravamento de problemas simples em defeitos graves.

[PECAS]
Nao requer pecas de substituicao obrigatorias (apenas eventuais reposicoes identificadas)
[/PECAS]'
        ],

        // ==================== REVISAO 20.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            20000,
            '24',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo incluindo oleo do motor, filtros de oleo e combustivel conforme especificacoes da revisao de 10.000 km.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|PSL166|Tecfil|Filtro Oleo HB20 Creta 1.0|1|28.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            20000,
            '24',
            50.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 18 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas. Filtro tipo carvao ativado retem poeira, polen, bacterias, fuligem, odores e particulas PM2.5. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador. Sistema automatico de climatizacao requer fluxo adequado para maxima eficiencia.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine (odor de mofo), reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 420 a R$ 650).

[PECAS]
ORIGINAL|97133B1000|Filtro Ar Condicionado Hyundai HB20|1|125.00
SIMILAR|AKX1813|Wega|Filtro Cabine HB20 Carvao Ativado|1|38.00
SIMILAR|ACP1813|Tecfil|Filtro Cabine HB20 1.0|1|42.00
SIMILAR|CF1813|Fram|Filtro Ar Condicionado HB20|1|45.00
SIMILAR|CU1813|Mann|Filtro Cabine HB20 Carvao|1|48.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar do Motor',
            20000,
            '24',
            35.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 12 minutos]

Substituicao do elemento filtrante de ar do motor T-GDI localizado na caixa de ar. Codigo Tecfil ARL2338 ou ARL2349, codigo original Hyundai 28113R1200. Filtro retem particulas impedindo entrada no turbocompressor e camara de combustao. Motor turbo com injecao direta requer fluxo de ar limpo e constante para perfeita combustao e maximo rendimento do turbo. Verificar estado da vedacao da caixa de ar. Limpeza interna da caixa.

**Consequencias de nao fazer:** Reducao de potencia e pressao de turbo (boost) em ate 15%, aumento no consumo de combustivel em ate 18%, entrada de particulas no turbocompressor causando desgaste das palhetas (danos de R$ 3.500 a R$ 6.500), formacao de depositos carboniferos no sistema GDI, sensor MAP sujo causando falhas, marcha lenta irregular.

[PECAS]
ORIGINAL|28113R1200|Filtro Ar Motor Hyundai HB20 1.0|1|115.00
SIMILAR|ARL2349|Tecfil|Filtro Ar HB20 1.0 Turbo|1|45.00
SIMILAR|ARL2338|Tecfil|Filtro Ar HB20 1.0|1|42.00
SIMILAR|JFA0H41|Wega|Filtro Ar HB20 1.0|1|38.00
SIMILAR|C23031|Mann|Filtro Ar HB20 1.0|1|48.00
SIMILAR|CA11843|Fram|Filtro Ar HB20 1.0|1|43.00
[/PECAS]'
        ],
        [
            'Rodizio de Pneus e Alinhamento',
            20000,
            '24',
            150.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 50 minutos]

Execucao de rodizio dos pneus 175/70 R14 ou 195/55 R16 (conforme versao) seguindo padrao paralelo. Verificacao de pressao (33 PSI frio todas as rodas, 36 PSI com carga). Inspecao de desgaste irregular indicando necessidade de alinhamento. Verificacao de cortes, bolhas, deformacoes, data de fabricacao (codigo DOT). Alinhamento 3D das rodas dianteiras. Balanceamento eletronico se necessario. Calibracao do sensor TPMS (monitoramento de pressao dos pneus).

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 40%, vibracoes no volante, perda de estabilidade direcional, aumento no consumo de combustivel em ate 8%, perda de aderencia em piso molhado aumentando risco de aquaplanagem, falha do sistema TPMS, desgaste irregular da direcao eletrica.

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
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo incluindo oleo do motor e filtros conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            30000,
            '24',
            120.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico com ABS e EBD. ATENCAO ESPECIAL: Verificar cilindro mestre conforme recall 2021. Fluido higroscopico absorve umidade reduzindo ponto de ebulicao. Procedimento: sangria de todas as rodas e modulo ABS iniciando pela mais distante do cilindro mestre (traseira direita, traseira esquerda, dianteira direita, dianteira esquerda). Capacidade: 650ml. Utilizar DOT 4 homologado FMVSS 116. Intervalo: a cada 2 anos independente da quilometragem.

**Consequencias de nao fazer:** Fluido contaminado causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao do sistema (cilindros, pincas, modulo ABS), agravamento do problema do recall do cilindro mestre, necessidade de substituicao completa do sistema, falha do ABS, acidentes gravissimos.

[PECAS]
ORIGINAL|04700000A0|Fluido de Freio DOT 4 Hyundai|500ML|52.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|32.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|38.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|500ML|30.00
SIMILAR|DOT4-ATE|ATE|Fluido Freio Super DOT 4|500ML|40.00
[/PECAS]'
        ],
        [
            'Limpeza do Sistema de Injecao Direta GDI',
            30000,
            '36',
            60.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Limpeza preventiva do sistema de injecao direta de alta pressao (350 bar) atraves de aditivo especifico para GDI aplicado no tanque de combustivel. Motor T-GDI possui injecao direta: combustivel e injetado diretamente na camara, nao passando pelas valvulas de admissao. Isso causa acumulo de depositos carboniferos nas valvulas que nao sao lavadas pela gasolina. Procedimento: abastecer tanque com gasolina premium (95+ octanas), adicionar produto limpador especifico para GDI, rodar em rodovia. Em casos severos, realizar limpeza por hidrojateamento/walnut blasting das valvulas (servico especializado).

**Consequencias de nao fazer:** Acumulo severo de depositos carboniferos nas valvulas de admissao causando perda de potencia em ate 20%, aumento no consumo em ate 25%, marcha lenta irregular, dificuldade na partida, trepidacao, falha do turbo por falta de fluxo de ar, necessidade de limpeza por walnut blasting (R$ 800 a R$ 1.400).

[PECAS]
SIMILAR|GDI-CLEAN|Wynns|Limpador Sistema GDI/Injecao Direta|325ML|58.00
SIMILAR|GDI-WURTH|Wurth|Limpador Injecao Direta Turbo|300ML|52.00
SIMILAR|TOP-GDI|Bardahl|Limpador GDI Premium|200ML|65.00
[/PECAS]'
        ],

        // ==================== REVISAO 40.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            40000,
            '48',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Filtros (Ar Motor e Ar Condicionado)',
            40000,
            '48',
            65.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao dos filtros de ar do motor e ar condicionado conforme especificacoes anteriores.

[PECAS]
ORIGINAL|28113R1200|Filtro Ar Motor Hyundai HB20 1.0|1|115.00
ORIGINAL|97133B1000|Filtro Ar Condicionado Hyundai HB20|1|125.00
SIMILAR|ARL2349|Tecfil|Filtro Ar HB20 1.0 Turbo|1|45.00
SIMILAR|AKX1813|Wega|Filtro Cabine HB20 Carvao Ativado|1|38.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            40000,
            '48',
            95.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Substituicao das 3 velas de ignicao do motor T-GDI 1.0 3 cilindros 12V. Codigo NGK SILZKR8E8D (Laser Iridium), codigo Hyundai 1884908082. Especificacoes: eletrodo central Iridium super fino, eletrodo lateral com pastilha de Platina, grau termico 8, gap 0,8mm, rosca 12mm comprimento 26,5mm. Motor turbo de alta compressao com injecao direta requer velas premium resistentes a altas temperaturas e pressoes. Limpar regiao antes da remocao. Aplicar torque de 20-25 Nm. IMPORTANTE: Usar apenas velas Iridium originais ou NGK - velas convencionais danificam o motor turbo.

**Consequencias de nao fazer:** Dificuldade na partida, falhas de ignicao graves, perda de potencia em ate 25%, aumento no consumo em ate 30%, marcha lenta irregular severa, trepidacao, engasgos, emissoes poluentes elevadas, pre-ignicao (knocking) danificando pistoes e turbo, danificacao do catalisador (R$ 2.200 a R$ 4.500).

[PECAS]
ORIGINAL|1884908082|Jogo 3 Velas Ignicao Hyundai HB20 1.0 Turbo|3|385.00
SIMILAR|SILZKR8E8D|NGK|Jogo 3 Velas Laser Iridium HB20 1.0 T-GDI|3|285.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            160.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas) sistema Mando. Codigo Cobreq N-2131, Fras-le PD/2224, Jurid. Freios a disco ventilado dianteiro (4 furos, diametro 256mm). ATENCAO: Verificar cilindro mestre conforme recall. Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas, verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos. Sangria se necessario. Teste em pista. Resetar indicador no painel se equipado.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos profundos nos discos, perda de eficiencia de frenagem em ate 50%, ruido metalico intenso, aumento da distancia de frenagem, necessidade de substituicao prematura dos discos, agravamento do problema do recall do cilindro mestre, falha do ABS, risco de acidentes graves.

[PECAS]
ORIGINAL|58101G4A20|Jogo Pastilhas Freio Diant Hyundai HB20|1|225.00
SIMILAR|N2131|Cobreq|Jogo Pastilhas Freio Diant HB20 1.0 2020|1|80.00
SIMILAR|PD2224|Fras-le|Jogo Pastilhas Freio Diant HB20 1.0 2020|1|85.00
SIMILAR|HQJ2131A|Jurid|Jogo Pastilhas Freio Diant HB20 2020|1|92.00
SIMILAR|BB2131|Bosch|Jogo Pastilhas Freio Diant HB20|1|98.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            90.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 50 minutos]

Substituicao da correia poly-V do alternador/acessorios. Motor T-GDI 1.0 utiliza correia unica acionando alternador e compressor do ar condicionado. IMPORTANTE: HB20 possui correia dentada de sincronismo de longa duracao - verificar manual. Verificacao do tensionador automatico, polias e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao. Tensionamento adequado conforme especificacao.

**Consequencias de nao fazer:** Rompimento da correia causando descarregamento da bateria, perda do ar condicionado, luz de bateria no painel, possivel superaquecimento por sobrecarga eletrica prolongada, necessidade de guincho.

[PECAS]
ORIGINAL|25212C9100|Correia Alternador Hyundai HB20 1.0|1|105.00
SIMILAR|5PK1105|Gates|Correia Poly-V Alternador HB20|1|45.00
SIMILAR|5PK1105|Continental|Correia Poly-V HB20 1.0|1|42.00
SIMILAR|K051105|Dayco|Correia Alternador HB20 1.0|1|43.00
SIMILAR|5PK1105|Goodyear|Correia Auxiliar HB20 1.0|1|38.00
[/PECAS]'
        ],

        // ==================== REVISAO 50.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            50000,
            '60',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            125.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 75 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor T-GDI 1.0 turbo. Hyundai recomenda fluido de longa duracao (Long Life Coolant) cor verde ou vermelho diluido 50/50 com agua desmineralizada. Capacidade total: aproximadamente 5,5 litros da mistura. Turbocompressor opera a temperaturas muito elevadas (900C nos gases de escape) exigindo sistema de arrefecimento perfeito. Procedimento: drenagem, lavagem, reabastecimento, sangria (eliminacao de bolhas), funcionamento ate temperatura normal (90C), verificacao de vazamentos e nivel.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna, formacao de borra reduzindo eficiencia termica, superaquecimento do motor e especialmente do turbocompressor (componente de R$ 3.500 a R$ 6.500), danos ao radiador, bomba dagua (R$ 320 a R$ 550), termostato, empenamento do cabecote.

[PECAS]
ORIGINAL|0710000200|Aditivo Radiador Hyundai Long Life|2L|125.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|3L|18.00
SIMILAR|PARAFLU-LL|Repsol|Aditivo Radiador Longa Duracao|2L|65.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico|2L|68.00
SIMILAR|RAD-PROTEC|Valvoline|Aditivo Radiador Premium|2L|60.00
[/PECAS]'
        ],
        [
            'Higienizacao Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            185.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 90 minutos]

Limpeza profissional do sistema de ar condicionado: aplicacao de espuma higienizadora no evaporador atraves da caixa de ar, aspiracao da espuma e residuos, aplicacao de bactericida/fungicida por nebulizacao, limpeza do dreno do evaporador, troca do filtro de cabine. Verificacao de pressao do gas refrigerante R-134a, teste de vazamentos com detector eletronico, temperatura de saida (deve atingir 4-7C). Sistema automatico de climatizacao requer manutencao periodica. Teste de todos os sensores e atuadores.

**Consequencias de nao fazer:** Proliferacao de fungos e bacterias no evaporador, mau cheiro persistente (sindrome do carro sujo), alergias respiratorias, obstrucao do dreno causando infiltracao de agua no assoalho e modulo eletronico, reducao da eficiencia do sistema em ate 40%.

[PECAS]
ORIGINAL|97133B1000|Filtro Ar Condicionado Hyundai HB20|1|125.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado|500ML|55.00
SIMILAR|KLIMACLEAN|Wynns|Limpador Ar Condicionado Automotivo|500ML|60.00
SIMILAR|AKX1813|Wega|Filtro Cabine HB20 Carvao Ativado|1|38.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '72',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Filtros (Ar Motor e Ar Condicionado)',
            60000,
            '72',
            65.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao dos filtros de ar do motor e ar condicionado conforme especificacoes anteriores.

[PECAS]
ORIGINAL|28113R1200|Filtro Ar Motor Hyundai HB20 1.0|1|115.00
ORIGINAL|97133B1000|Filtro Ar Condicionado Hyundai HB20|1|125.00
SIMILAR|ARL2349|Tecfil|Filtro Ar HB20 1.0 Turbo|1|45.00
SIMILAR|AKX1813|Wega|Filtro Cabine HB20 Carvao Ativado|1|38.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            60000,
            '72',
            95.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Segunda troca das velas de ignicao conforme especificacoes da revisao de 40.000 km.

[PECAS]
ORIGINAL|1884908082|Jogo 3 Velas Ignicao Hyundai HB20 1.0 Turbo|3|385.00
SIMILAR|SILZKR8E8D|NGK|Jogo 3 Velas Laser Iridium HB20 1.0 T-GDI|3|285.00
[/PECAS]'
        ],
        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            195.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 95 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio ventilados dianteiros 4 furos, diametro 256mm. ATENCAO: Verificar cilindro mestre conforme recall. Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada (geralmente 22mm). Sangria do sistema. Teste em pista. Discos devem ser substituidos em par sempre.

[PECAS]
ORIGINAL|58101G4A20|Jogo Pastilhas Freio Diant Hyundai HB20|1|225.00
ORIGINAL|51712H5100|Par Discos Freio Diant Hyundai HB20|2|485.00
SIMILAR|N2131|Cobreq|Jogo Pastilhas Freio Diant HB20 1.0 2020|1|80.00
SIMILAR|PD2224|Fras-le|Jogo Pastilhas Freio Diant HB20 1.0 2020|1|85.00
SIMILAR|DF2567|Fremax|Par Discos Freio Ventilado HB20|2|275.00
SIMILAR|RC2567|Cobreq|Par Discos Freio Diant HB20|2|265.00
SIMILAR|BD2567|TRW|Par Discos Freio HB20 1.0|2|285.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            60000,
            '24',
            120.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Terceira troca do fluido de freio (intervalo a cada 2 anos). Verificar cilindro mestre conforme recall 2021.

[PECAS]
ORIGINAL|04700000A0|Fluido de Freio DOT 4 Hyundai|500ML|52.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|32.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|38.00
[/PECAS]'
        ],
        [
            'Limpeza do Sistema de Injecao Direta GDI',
            60000,
            '72',
            60.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Segunda limpeza preventiva do sistema de injecao direta conforme especificacoes da revisao de 30.000 km.

[PECAS]
SIMILAR|GDI-CLEAN|Wynns|Limpador Sistema GDI/Injecao Direta|325ML|58.00
SIMILAR|GDI-WURTH|Wurth|Limpador Injecao Direta Turbo|300ML|52.00
[/PECAS]'
        ],

        // ==================== REVISAO 70.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            70000,
            '84',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            80000,
            '96',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Filtros (Ar Motor e Ar Condicionado)',
            80000,
            '96',
            65.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao dos filtros conforme especificacoes anteriores.

[PECAS]
ORIGINAL|28113R1200|Filtro Ar Motor Hyundai HB20 1.0|1|115.00
ORIGINAL|97133B1000|Filtro Ar Condicionado Hyundai HB20|1|125.00
SIMILAR|ARL2349|Tecfil|Filtro Ar HB20 1.0 Turbo|1|45.00
SIMILAR|AKX1813|Wega|Filtro Cabine HB20 Carvao Ativado|1|38.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            80000,
            '96',
            95.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Terceira troca das velas de ignicao conforme especificacoes anteriores.

[PECAS]
ORIGINAL|1884908082|Jogo 3 Velas Ignicao Hyundai HB20 1.0 Turbo|3|385.00
SIMILAR|SILZKR8E8D|NGK|Jogo 3 Velas Laser Iridium HB20 1.0 T-GDI|3|285.00
[/PECAS]'
        ],
        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            215.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 125 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores traseiros (sistema a tambor 203mm). ATENCAO: Verificar cilindro mestre conforme recall. Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos, cabo do freio de estacionamento eletronico EPB. Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm. Sangria do sistema.

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro em ate 65%, desbalanceamento da frenagem, freio de estacionamento EPB inoperante (reprovacao na inspecao), necessidade de substituicao dos tambores, acidentes por frenagem deficiente.

[PECAS]
ORIGINAL|58305G4A00|Jogo Lonas Freio Traseiro Hyundai HB20|1|185.00
ORIGINAL|52711H5100|Par Tambores Freio Traseiro Hyundai HB20|2|425.00
SIMILAR|HI1567|Fras-le|Jogo Lonas Freio Traseiro HB20|1|78.00
SIMILAR|N1567|Cobreq|Jogo Lonas Freio Traseiro HB20|1|72.00
SIMILAR|TT2567|TRW|Par Tambores Freio Traseiro HB20|2|245.00
SIMILAR|RT2567|Fremax|Par Tambores Freio Traseiro HB20|2|235.00
[/PECAS]'
        ],
        [
            'Substituicao de Amortecedores',
            80000,
            '96',
            285.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 160 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo telescopico) incluindo kits de reparo (coxins superiores, batentes, coifas). Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar cada canto do veiculo, deve retornar sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento apos a troca. Veiculo com suspensao independente nas 4 rodas.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem em ate 20%, perda de estabilidade em curvas, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas), desconforto aos ocupantes, trepidacao, falha do sensor TPMS.

[PECAS]
ORIGINAL|54651H5100|Amortecedor Dianteiro Hyundai HB20|2|645.00
ORIGINAL|55311H5100|Amortecedor Traseiro Hyundai HB20|2|595.00
SIMILAR|HG34567|Monroe|Amortecedor Diant HB20 Gas|2|385.00
SIMILAR|HG34568|Monroe|Amortecedor Tras HB20 Gas|2|365.00
SIMILAR|AM34567|Cofap|Amortecedor Diant HB20 Turbogas|2|325.00
SIMILAR|AM34568|Cofap|Amortecedor Tras HB20 Turbogas|2|305.00
SIMILAR|N34567|Nakata|Amortecedor Diant HB20 1.0|2|295.00
SIMILAR|N34568|Nakata|Amortecedor Tras HB20 1.0|2|275.00
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

[PECAS]
ORIGINAL|25212C9100|Correia Alternador Hyundai HB20 1.0|1|105.00
SIMILAR|5PK1105|Gates|Correia Poly-V Alternador HB20|1|45.00
SIMILAR|5PK1105|Continental|Correia Poly-V HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            80000,
            '96',
            160.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Terceira troca das pastilhas de freio dianteiras. Verificar cilindro mestre conforme recall.

[PECAS]
ORIGINAL|58101G4A20|Jogo Pastilhas Freio Diant Hyundai HB20|1|225.00
SIMILAR|N2131|Cobreq|Jogo Pastilhas Freio Diant HB20 1.0 2020|1|80.00
SIMILAR|PD2224|Fras-le|Jogo Pastilhas Freio Diant HB20 1.0 2020|1|85.00
[/PECAS]'
        ],

        // ==================== REVISAO 90.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            90000,
            '108',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Limpeza do Sistema de Injecao Direta GDI',
            90000,
            '108',
            60.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Terceira limpeza preventiva do sistema de injecao direta. Preparacao para walnut blasting aos 100.000 km.

[PECAS]
SIMILAR|GDI-CLEAN|Wynns|Limpador Sistema GDI/Injecao Direta|325ML|58.00
SIMILAR|GDI-WURTH|Wurth|Limpador Injecao Direta Turbo|300ML|52.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM ====================
        [
            'Troca de Oleo e Filtros Completos',
            100000,
            '120',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|2630002503|Filtro de Oleo Motor Hyundai HB20 1.0 Turbo|1|85.00
ORIGINAL|0510000451|Oleo Motor Hyundai Xteer 5W-30 Sintetico|4L|285.00
ORIGINAL|31112C9000|Filtro Combustivel Hyundai HB20 1.0|1|95.00
ORIGINAL|2151323001|Anel Vedacao Bujao Carter Hyundai|1|8.00
SIMILAR|OC1225|Mahle|Filtro Oleo HB20 1.0 12V|1|32.00
SIMILAR|5W30-MOBIL1|Mobil|Oleo Mobil 1 Advanced 5W-30 Sintetico|4L|185.00
SIMILAR|GI47/1|Tecfil|Filtro Combustivel HB20 1.0|1|42.00
[/PECAS]'
        ],
        [
            'Troca de Filtros (Ar Motor e Ar Condicionado)',
            100000,
            '120',
            65.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao dos filtros conforme especificacoes anteriores.

[PECAS]
ORIGINAL|28113R1200|Filtro Ar Motor Hyundai HB20 1.0|1|115.00
ORIGINAL|97133B1000|Filtro Ar Condicionado Hyundai HB20|1|125.00
SIMILAR|ARL2349|Tecfil|Filtro Ar HB20 1.0 Turbo|1|45.00
SIMILAR|AKX1813|Wega|Filtro Cabine HB20 Carvao Ativado|1|38.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            100000,
            '120',
            95.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Quarta troca das velas de ignicao conforme especificacoes anteriores.

[PECAS]
ORIGINAL|1884908082|Jogo 3 Velas Ignicao Hyundai HB20 1.0 Turbo|3|385.00
SIMILAR|SILZKR8E8D|NGK|Jogo 3 Velas Laser Iridium HB20 1.0 T-GDI|3|285.00
[/PECAS]'
        ],
        [
            'Substituicao da Bateria',
            100000,
            '60',
            45.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 30 minutos]

Substituicao da bateria automotiva 12V. Hyundai HB20 utiliza bateria de 50Ah a 60Ah com corrente de partida (CCA) de 420A a 480A. Baterias seladas livre de manutencao tem vida util de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora. IMPORTANTE: Veiculo com multiplos sistemas eletronicos (TPMS, ESC, climatizacao automatica, Start/Stop se equipado) requer programacao apos troca. Backup de configuracoes. Dimensoes: 230mm x 175mm x 185mm.

**Consequencias de nao fazer:** Falha de partida especialmente em dias frios, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, falha dos sistemas eletronicos (TPMS, ESC, Start/Stop), perda de memoria dos sistemas, necessidade de reboque.

[PECAS]
ORIGINAL|37110G4100|Bateria 12V 60Ah Hyundai Original|1|525.00
SIMILAR|60GD-480|Moura|Bateria 12V 60Ah 480A Selada|1|335.00
SIMILAR|60D-500|Heliar|Bateria 12V 60Ah 500A Free|1|345.00
SIMILAR|B60DH|Bosch|Bateria 12V 60Ah S4 Free|1|385.00
SIMILAR|60AH-420|Zetta|Bateria 12V 60Ah Selada|1|285.00
[/PECAS]'
        ],
        [
            'Limpeza/Descarbonizacao Profunda do Sistema GDI (Walnut Blasting)',
            100000,
            '120',
            650.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 180 minutos]

Limpeza profunda do sistema de injecao direta por metodo walnut blasting (jateamento com casca de noz moida). Remocao do coletor de admissao para acesso as valvulas. Jateamento remove completamente os depositos carboniferos acumulados nas valvulas de admissao e camara de combustao. Motor GDI acumula depositos severos pois o combustivel nao lava as valvulas. Procedimento: remocao do coletor, vedacao dos cilindros, jateamento, aspiracao dos residuos, remontagem com juntas novas, limpeza do corpo de borboleta, limpeza dos sensores MAP/temperatura. Apos servico, realizar teste de compressao e aprendizagem dos parametros com scanner.

SERVICO OBRIGATORIO PARA MOTORES T-GDI - Principal problema do motor e a carbonizacao das valvulas de admissao.

**Consequencias de nao fazer:** Acumulo severo de depositos carboniferos causando perda de potencia em ate 30%, aumento no consumo em ate 35%, marcha lenta irregular severa, dificuldade na partida, trepidacao intensa, falha do turbo por falta de fluxo, pre-ignicao (knocking), possivel dano aos pistoes e valvulas.

[PECAS]
SIMILAR|WALNUT-5KG|Wurth|Casca de Noz p/ Walnut Blasting|5KG|285.00
SIMILAR|GASKET-INTAKE|Hyundai|Jogo Juntas Coletor Admissao HB20|1|125.00
SIMILAR|TBI-CLEAN|Wynns|Limpador Corpo Borboleta|400ML|48.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            100000,
            '120',
            125.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 75 minutos]

Segunda troca do fluido de arrefecimento conforme especificacoes da revisao de 50.000 km.

[PECAS]
ORIGINAL|0710000200|Aditivo Radiador Hyundai Long Life|2L|125.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|3L|18.00
SIMILAR|PARAFLU-LL|Repsol|Aditivo Radiador Longa Duracao|2L|65.00
SIMILAR|COOLANT-LL|Wurth|Aditivo Radiador Organico|2L|68.00
[/PECAS]'
        ],

        // ==================== ITENS ESPECIAIS POR TEMPO ====================
        [
            'Substituicao de Pneus (por tempo ou desgaste)',
            50000,
            '60',
            65.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 90 minutos para jogo completo]

Hyundai HB20 utiliza pneus 175/70 R14 (padrao) ou 195/55 R16 (Evolution). Vida util media: 40.000 a 50.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). Verificar mensalmente: pressao (33 PSI frio, 36 PSI com carga), desgaste da banda (minimo legal 1,6mm medido nos TWI), deformacoes, cortes laterais, data de fabricacao (codigo DOT). Sistema TPMS monitora pressao em tempo real. Realizar rodizio a cada 10.000 km.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 45%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves, falha do sistema TPMS, multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular, reprovacao em inspecao veicular.

[PECAS]
SIMILAR|175/70R14|Pirelli|Pneu Cinturato P1 175/70 R14|4|1180.00
SIMILAR|175/70R14|Goodyear|Pneu Direction Touring 175/70 R14|4|1080.00
SIMILAR|195/55R16|Pirelli|Pneu Cinturato P7 195/55 R16|4|1680.00
SIMILAR|195/55R16|Michelin|Pneu Energy XM2 195/55 R16|4|1780.00
SIMILAR|195/55R16|Bridgestone|Pneu Turanza T005 195/55 R16|4|1720.00
[/PECAS]'
        ],

        // ==================== RECALL E PROBLEMAS CONHECIDOS ====================
        [
            'RECALL CRITICO - Cilindro Mestre de Freio (2021)',
            1000,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: Verificacao imediata]

PROBLEMA GRAVE na usinagem do cilindro mestre de freio que pode causar danos aos selos de vedacao. PODE CAUSAR REDUCAO DA EFICIENCIA OU PERDA DE FRENAGEM. Afeta modelos HB20 e HB20S 2021.

SINAIS DE ALERTA:
- Pedal de freio mais macio ou esponjoso
- Necessidade de maior pressao no pedal
- Perda de eficiencia na frenagem
- Luz de advertencia de freio no painel
- Vazamento de fluido no cilindro mestre

RISCOS: Em casos extremos pode gerar acidentes com consequentes danos materiais e lesoes fisicas graves ou ate fatais.

PROCEDIMENTO: VERIFICAR URGENTEMENTE no site www.hyundai.com.br ou telefone 0800 0191 134. Reparo: substituicao do cilindro mestre de freio. SERVICO GRATUITO EM CONCESSIONARIA.

[PECAS]
Servico gratuito em concessionaria autorizada Hyundai
[/PECAS]'
        ],
        [
            'Motor T-GDI - Carbonizacao das Valvulas (Problema Conhecido)',
            30000,
            '36',
            0.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: Monitoramento continuo]

O motor T-GDI (Turbo Gasoline Direct Injection) possui injecao direta: combustivel e injetado diretamente na camara de combustao, NAO passando pelas valvulas de admissao. Isso causa acumulo progressivo de depositos carboniferos nas valvulas que nao sao lavadas pela gasolina. Este e o PROBLEMA MAIS COMUM e SERIO dos motores GDI.

SINAIS DE ALERTA:
- Perda gradual de potencia ao longo do tempo
- Aumento progressivo no consumo de combustivel
- Marcha lenta irregular ou trepidacao
- Dificuldade na partida a frio
- Ruido de batida (pinking) em aceleracoes

PREVENCAO:
- Abastecer SEMPRE com gasolina premium (octanagem 95+) - nunca usar etanol puro
- Utilizar aditivo de qualidade a cada abastecimento
- Realizar limpeza preventiva aos 30.000 km
- REALIZAR WALNUT BLASTING AOS 100.000 KM (OBRIGATORIO)
- Evitar trajetos curtos (<10 km) sempre que possivel
- Rodar em alta rotacao (3.500-4.500 rpm) periodicamente em rodovia

[PECAS]
Monitoramento e prevencao - sem pecas especificas
[/PECAS]'
        ],
        [
            'Turbocompressor - Sensibilidade a Oleo (Cuidados Especiais)',
            10000,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: Monitoramento continuo]

O turbocompressor T04 opera em temperaturas extremamente elevadas (900C nos gases de escape) e rotacoes de ate 150.000 rpm. E EXTREMAMENTE SENSIVEL a qualidade e troca do oleo.

SINAIS DE ALERTA:
- Perda de potencia (boost baixo)
- Fumaca azul no escapamento
- Ruido agudo tipo assobio
- Consumo de oleo
- Luz de pressao de oleo piscando

PREVENCAO:
- SEMPRE utilizar oleo 100% sintetico 5W-30 API SN ou superior
- Trocar oleo rigorosamente a cada 10.000 km ou 12 meses
- NUNCA desligar motor quente imediatamente - aguardar 30-60 segundos em marcha lenta
- Evitar aceleracoes bruscas com motor frio
- Verificar nivel de oleo semanalmente
- Trocar filtro de ar regularmente (ar sujo danifica turbina)

Custo de substituicao do turbo: R$ 3.500 a R$ 6.500

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
            'motor' => 'Kappa 1.0 T-GDI 3 cilindros 12V Turbo',
            'oleo' => '5W-30 100% Sintetico API SN ACEA A5 - 3.6 litros',
            'velas' => 'NGK SILZKR8E8D Laser Iridium (3 unidades a cada 20.000 km)',
            'atencao_especial' => [
                'RECALL CRITICO - Cilindro mestre de freio 2021 - verificar www.hyundai.com.br',
                'Motor GDI requer walnut blasting OBRIGATORIO aos 100.000 km',
                'Turbo sensivel - NUNCA desligar motor quente imediatamente',
                'Usar APENAS gasolina premium (95+ octanas)'
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
