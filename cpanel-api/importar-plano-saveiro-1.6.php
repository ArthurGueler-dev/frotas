<?php
/**
 * Script para importar Plano de Manutencao Volkswagen Saveiro 1.6 Robust 2019-2020
 * Gerado via Perplexity AI em 2026-01-15
 * Motor: MSI EA211 1.6 16V Flex - 120 cv
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-saveiro-1.6.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-saveiro-1.6.php?confirmar=SIM',
        'modelo' => 'Volkswagen Saveiro 1.6 Robust 2019-2020',
        'motor' => 'MSI EA211 1.6 16V Flex - 120 cv',
        'oleo' => '5W-40 100% Sintetico VW 508/509 - 4.0 litros',
        'correia_dentada' => '60.000 km ou 6 anos - MOTOR INTERFERENTE',
        'atencao' => [
            'CORREIA DENTADA CRITICA - 60.000 km ou 6 anos (motor interferente)',
            'RECALL polia do motor 2020-2021',
            'MOTOR BATENDO - problema comum relatado',
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
    // NOTA: No banco esta como "Roboust" (erro de digitacao)
    $modeloNome = "SAVEIRO 1.6 Roboust";

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
            115.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 40 minutos]

Drenagem completa do oleo lubrificante do motor MSI (Modular Standard Injection) EA211 1.6 16V atraves do bujao do carter. Substituicao do filtro de oleo tipo cartucho codigo VW 030115561AR ou equivalentes Mann W7125, Tecfil PSL560, Wega WO340 e reabastecimento com oleo 100% sintetico especificacao SAE 5W-40 API SN ACEA A3/B4 ou VW 508 88/509 99. Capacidade: 4,0 litros com filtro para motor MSI. Motor MSI de 120 cv com 16 valvulas requer oleo sintetico de baixa viscosidade para maxima protecao e economia de combustivel. Criterio: o que ocorrer primeiro (10.000 km OU 12 meses).

**Consequencias de nao fazer:** Degradacao do oleo causando desgaste acelerado de pistoes, bronzinas e eixo comando de valvulas, acumulo de borra, oxidacao interna, superaquecimento, perda de eficiencia em ate 18%, perda de garantia de fabrica, possivel travamento ou quebra do motor exigindo retifica completa (R$ 8.500 a R$ 14.000).

[PECAS]
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|PSL560|Tecfil|Filtro Oleo VW MSI EA211|1|28.00
SIMILAR|WO340|Wega|Filtro Oleo Saveiro 1.6|1|26.00
SIMILAR|OC250|Mahle|Filtro Oleo VW 1.6 MSI|1|32.00
SIMILAR|PH5548|Fram|Filtro Oleo Saveiro Gol|1|29.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
SIMILAR|5W40-SHELL|Shell|Oleo Helix HX8 5W-40 Sintetico|4L|175.00
SIMILAR|5W40-MOBIL|Mobil|Oleo Super 3000 X1 5W-40 Sintetico|4L|158.00
SIMILAR|5W40-PETRONAS|Petronas|Oleo Syntium 5W-40 API SN|4L|148.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar do Motor',
            10000,
            '12',
            32.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 10 minutos]

Substituicao do elemento filtrante de ar do motor MSI 1.6 localizado na caixa de ar. Codigo original VW 04E129620A/B, codigo Mann C21014/1, Wega FAP2219, Tecfil ARL6080. Filtro retem particulas solidas impedindo entrada no coletor de admissao e camara de combustao. Motor MSI com injecao eletronica multiponto e 16 valvulas requer fluxo de ar limpo para perfeita mistura ar/combustivel e maximo rendimento dos 120 cv. Verificar estado da vedacao e limpeza interna da caixa de ar.

**Consequencias de nao fazer:** Reducao de potencia em ate 10%, aumento no consumo de combustivel em ate 12%, entrada de particulas abrasivas causando desgaste dos cilindros, pistoes e aneis, formacao de borra no coletor de admissao, sensor MAF sujo causando falhas de injecao, marcha lenta irregular, possivel perda de garantia.

[PECAS]
ORIGINAL|04E129620A|Filtro Ar Motor VW Saveiro 1.6 MSI|1|125.00
SIMILAR|C21014/1|Mann|Filtro Ar Saveiro Gol Polo 1.6|1|52.00
SIMILAR|FAP2219|Wega|Filtro Ar Saveiro Fox Gol 1.6|1|48.00
SIMILAR|ARL6080|Tecfil|Filtro Ar VW MSI 1.6|1|50.00
SIMILAR|CA12104|Fram|Filtro Ar Saveiro 1.6|1|51.00
SIMILAR|LX2876/1|Mahle|Filtro Ar VW Saveiro|1|54.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Combustivel',
            10000,
            '12',
            60.00,
            'Alta',
            '[CATEGORIA: Filtros] [TEMPO: 25 minutos]

Substituicao do filtro de combustivel do sistema de injecao eletronica multiponto. Codigo Mann WK58/3, filtro tipo inline instalado na linha de combustivel. Motor Flex com injecao multiponto requer filtragem eficiente da gasolina/etanol para protecao dos bicos injetores e bomba de combustivel. ATENCAO: Despressurizar o sistema antes da remocao (retirar fusivel da bomba, dar partida ate motor morrer). Verificar possivel recall relacionado a polia do motor.

**Consequencias de nao fazer:** Entupimento dos bicos injetores, falha na partida, perda de potencia, aumento no consumo em ate 20%, marcha lenta irregular, engasgos, necessidade de limpeza ultrassonica dos injetores (R$ 380 a R$ 600) ou substituicao completa (R$ 1.400 a R$ 2.200).

[PECAS]
ORIGINAL|6Q0201051J|Filtro Combustivel Original VW Saveiro|1|95.00
SIMILAR|WK58/3|Mann|Filtro Combustivel Saveiro Gol 1.6|1|42.00
SIMILAR|GI04/5|Tecfil|Filtro Combustivel VW MSI|1|38.00
SIMILAR|JFC215|Wega|Filtro Combustivel Saveiro 1.6|1|36.00
SIMILAR|G6845|Fram|Filtro Combustivel VW 1.6|1|40.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            10000,
            '12',
            50.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 18 minutos]

Substituicao do filtro de ar condicionado/cabine localizado atras do porta-luvas. Codigo Mann CU2545/1. Filtro tipo particulado/carvao ativado retem poeira, polen, bacterias, fuligem e odores externos. Saturacao causa reducao do fluxo de ar, odor desagradavel, proliferacao de fungos no evaporador e sobrecarga do motor do ventilador. Recomenda-se higienizacao do sistema com spray antibacteriano durante a troca.

**Consequencias de nao fazer:** Mau cheiro persistente na cabine (odor de mofo), reducao de ate 50% no fluxo de ar, embacamento excessivo dos vidros, alergias e problemas respiratorios aos ocupantes, queima do motor do ventilador interno (R$ 380 a R$ 580).

[PECAS]
ORIGINAL|6R0820367|Filtro Ar Condicionado Original VW Saveiro|1|115.00
SIMILAR|CU2545/1|Mann|Filtro Cabine Saveiro Gol Voyage|1|45.00
SIMILAR|AKX35107|Wega|Filtro Cabine VW Saveiro|1|38.00
SIMILAR|ACP185|Tecfil|Filtro Cabine Saveiro 1.6|1|42.00
SIMILAR|CF185|Fram|Filtro Ar Condicionado Saveiro|1|44.00
[/PECAS]'
        ],
        [
            'Inspecao Geral de Seguranca',
            10000,
            '12',
            125.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 55 minutos]

Inspecao visual e funcional completa conforme manual Volkswagen: verificacao de niveis de fluidos (arrefecimento, freio, direcao, limpador, bateria), teste de luzes externas/internas, buzina, limpadores, travas eletricas, vidros eletricos; inspecao de pneus (pressao 35 PSI dianteiros/40 PSI traseiros com carga, desgaste, banda minima 1,6mm), freios (pastilhas, discos, lonas, tubulacoes), suspensao (amortecedores, buchas, batentes), direcao eletrica assistida, escapamento, bateria (terminais, carga 12,6V), correias, velas. ATENCAO ESPECIAL: Verificar polia do motor conforme recall. Veiculo comercial (picape) requer atencao especial na suspensao traseira e cacamba.

**Consequencias de nao fazer:** Nao identificacao de desgastes criticos ou recall da polia, acidentes por falha de freios ou pneus, perda de direcao assistida, multas por equipamentos obrigatorios inoperantes (R$ 293,47 gravissima + 7 pontos CNH), reprovacao em inspecao veicular, perda de garantia.

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

Servico completo incluindo oleo do motor, filtros de oleo, ar, combustivel e ar condicionado conforme especificacoes da revisao de 10.000 km.

[PECAS]
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
ORIGINAL|04E129620A|Filtro Ar Motor VW Saveiro 1.6 MSI|1|125.00
ORIGINAL|6Q0201051J|Filtro Combustivel Original VW Saveiro|1|95.00
ORIGINAL|6R0820367|Filtro Ar Condicionado Original VW Saveiro|1|115.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|PSL560|Tecfil|Filtro Oleo VW MSI EA211|1|28.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
SIMILAR|C21014/1|Mann|Filtro Ar Saveiro Gol Polo 1.6|1|52.00
SIMILAR|WK58/3|Mann|Filtro Combustivel Saveiro Gol 1.6|1|42.00
SIMILAR|CU2545/1|Mann|Filtro Cabine Saveiro Gol Voyage|1|45.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            20000,
            '24',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Substituicao das 4 velas de ignicao do motor MSI 1.6 16V. Especificacoes: NGK BKR7ESB-D (Laser Iridium) ou equivalente, gap 0,9-1,0mm, rosca 14mm, hexagono 16mm. Motor MSI com alta taxa de compressao e injecao multiponto requer velas de alta qualidade resistentes a corrosao do etanol. Limpar bem a regiao antes da remocao para evitar entrada de sujeira nos cilindros. Aplicar torque de aperto de 25-30 Nm. Verificar cor dos eletrodos (marrom claro = ideal).

**Consequencias de nao fazer:** Dificuldade na partida especialmente com etanol, falhas de ignicao (motor falhando/trepidando), perda de potencia em ate 15%, aumento no consumo de combustivel em ate 22%, marcha lenta irregular, engasgos, emissoes poluentes elevadas, possivel danificacao do catalisador (R$ 1.800 a R$ 3.200), perda de garantia.

[PECAS]
ORIGINAL|04E905612|Jogo 4 Velas Ignicao VW Saveiro 1.6 MSI|4|295.00
SIMILAR|BKR7ESBD|NGK|Jogo 4 Velas Laser Iridium Saveiro 1.6|4|185.00
SIMILAR|FR7DPX|Bosch|Jogo 4 Velas Platina Saveiro MSI|4|195.00
[/PECAS]'
        ],
        [
            'Rodizio de Pneus e Alinhamento',
            20000,
            '24',
            145.00,
            'Media',
            '[CATEGORIA: Pneus] [TEMPO: 50 minutos]

Execucao de rodizio dos pneus 195/70 R15 (Robust) ou 195/65 R15 (outras versoes) seguindo padrao paralelo ou cruz. ATENCAO: Picape tem distribuicao de peso diferente - verificar pressao conforme carga: 35 PSI dianteiros/40 PSI traseiros com carga maxima (740 kg). Verificacao de pressao, inspecao de desgaste irregular indicando necessidade de alinhamento. Verificacao de cortes, bolhas, deformacoes, data de fabricacao (codigo DOT). Alinhamento 3D das rodas dianteiras. Balanceamento eletronico das 4 rodas.

**Consequencias de nao fazer:** Desgaste irregular e prematuro dos pneus reduzindo vida util em ate 40%, vibracoes no volante, perda de estabilidade direcional, aumento no consumo de combustivel em ate 8%, perda de aderencia em piso molhado aumentando risco de aquaplanagem, desgaste irregular da direcao eletrica.

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

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
ORIGINAL|04E129620A|Filtro Ar Motor VW Saveiro 1.6 MSI|1|125.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
SIMILAR|C21014/1|Mann|Filtro Ar Saveiro Gol Polo 1.6|1|52.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            30000,
            '24',
            105.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Drenagem completa e substituicao do fluido de freio DOT 4 em todo o sistema hidraulico com ABS e EBD. Fluido higroscopico absorve umidade do ar reduzindo ponto de ebulicao e causando perda de eficiencia. Procedimento: sangria de todas as rodas e modulo ABS iniciando pela mais distante do cilindro mestre (traseira direita, traseira esquerda, dianteira direita, dianteira esquerda). Capacidade aproximada: 500ml. Utilizar apenas fluido DOT 4 homologado FMVSS 116 especificacao VW 501 14. Intervalo critico: a cada 2 anos independente da quilometragem.

**Consequencias de nao fazer:** Fluido contaminado com umidade causa vaporizacao em frenagens intensas (fade), perda total de frenagem, oxidacao interna do sistema hidraulico (cilindros mestre e roda, pincas, modulo ABS), necessidade de substituicao completa do sistema, falha do ABS, acidentes graves especialmente com carga.

[PECAS]
ORIGINAL|B000750M3|Fluido de Freio DOT 4 VW Original|500ML|52.00
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
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 65 minutos]

Limpeza preventiva dos bicos injetores multiponto atraves de aditivo de alta qualidade aplicado no tanque de combustivel. Motor MSI 1.6 16V possui 4 bicos injetores que podem acumular depositos carboniferos especialmente com uso de etanol de baixa qualidade. Procedimento: abastecer tanque com gasolina aditivada, adicionar produto limpador de injetores, rodar em rodovia por pelo menos 50 km. Em casos severos, realizar limpeza por ultrassom em oficina especializada.

**Consequencias de nao fazer:** Perda gradual de potencia em ate 14%, aumento no consumo em ate 18%, marcha lenta irregular, dificuldade na partida a frio, engasgos, formacao de depositos no coletor de admissao, necessidade de limpeza ultrassonica (R$ 380 a R$ 600).

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
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
ORIGINAL|04E129620A|Filtro Ar Motor VW Saveiro 1.6 MSI|1|125.00
ORIGINAL|6Q0201051J|Filtro Combustivel Original VW Saveiro|1|95.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
SIMILAR|C21014/1|Mann|Filtro Ar Saveiro Gol Polo 1.6|1|52.00
SIMILAR|WK58/3|Mann|Filtro Combustivel Saveiro Gol 1.6|1|42.00
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

[PECAS]
ORIGINAL|04E905612|Jogo 4 Velas Ignicao VW Saveiro 1.6 MSI|4|295.00
SIMILAR|BKR7ESBD|NGK|Jogo 4 Velas Laser Iridium Saveiro 1.6|4|185.00
SIMILAR|FR7DPX|Bosch|Jogo 4 Velas Platina Saveiro MSI|4|195.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            40000,
            '48',
            150.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Substituicao do jogo de pastilhas de freio dianteiras (4 pecas) sistema Teves/ATE. Codigo Cobreq N-2176, Jurid HQJ2185/HQJ2286. Freios a disco ventilado dianteiro (4 furos, diametro 280mm). Limpeza das pincas, lubrificacao dos pinos-guia com graxa especifica para altas temperaturas (Ceratec ou Molykote), verificacao dos pistoes e coifas. Espessura minima das pastilhas: 3mm. Medicao da espessura dos discos. Sangria se necessario. Teste em pista. ATENCAO: Picape com carga requer atencao especial aos freios.

**Consequencias de nao fazer:** Pastilhas desgastadas ate o metal causam sulcos profundos nos discos, perda de eficiencia de frenagem em ate 45%, ruido metalico intenso, aumento da distancia de frenagem, necessidade de substituicao prematura dos discos, falha do ABS, risco de acidentes graves especialmente com carga.

[PECAS]
ORIGINAL|5C0698151|Jogo Pastilhas Freio Diant VW Saveiro|1|215.00
SIMILAR|N2176|Cobreq|Jogo Pastilhas Freio Diant Saveiro 2017|1|88.00
SIMILAR|HQJ2185|Jurid|Jogo Pastilhas Freio Diant Saveiro|1|95.00
SIMILAR|HQJ2286|Jurid|Jogo Pastilhas Freio Diant Saveiro G7|1|95.00
SIMILAR|BB2185|Bosch|Jogo Pastilhas Freio Diant Saveiro|1|102.00
SIMILAR|PD2185|Fras-le|Jogo Pastilhas Freio Diant Saveiro|1|92.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            40000,
            '48',
            90.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Substituicao da correia poly-V do alternador/acessorios. Motor MSI 1.6 utiliza correia unica acionando alternador e compressor do ar condicionado. Verificacao do tensionador automatico, polias e rolamentos. Inspecao visual de trincas, desgaste das nervuras, vitrificacao. Tensionamento adequado conforme especificacao do fabricante. IMPORTANTE: Verificar polia do motor conforme recall.

**Consequencias de nao fazer:** Rompimento da correia causando descarregamento da bateria, perda do ar condicionado, luz de bateria no painel, possivel perda de direcao assistida eletrica, superaquecimento por sobrecarga eletrica, agravamento do problema do recall da polia, necessidade de guincho.

[PECAS]
ORIGINAL|04E260849|Correia Alternador VW Saveiro 1.6 MSI|1|105.00
SIMILAR|6PK1194|Gates|Correia Poly-V Alternador Saveiro|1|45.00
SIMILAR|6PK1194|Continental|Correia Poly-V Saveiro 1.6|1|42.00
SIMILAR|K061194|Dayco|Correia Alternador Saveiro MSI|1|44.00
SIMILAR|6PK1194|Goodyear|Correia Auxiliar Saveiro|1|38.00
[/PECAS]'
        ],
        [
            'Troca de Filtro de Ar Condicionado (Cabine)',
            40000,
            '48',
            50.00,
            'Media',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 18 minutos]

Segunda troca do filtro de cabine conforme especificacoes anteriores.

[PECAS]
ORIGINAL|6R0820367|Filtro Ar Condicionado Original VW Saveiro|1|115.00
SIMILAR|CU2545/1|Mann|Filtro Cabine Saveiro Gol Voyage|1|45.00
SIMILAR|AKX35107|Wega|Filtro Cabine VW Saveiro|1|38.00
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
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            50000,
            '60',
            115.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Drenagem completa e substituicao do fluido de arrefecimento (aditivo + agua desmineralizada) do motor MSI 1.6. Volkswagen recomenda fluido G13 (aditivo de longa duracao cor violeta) diluido 50/50 com agua desmineralizada. Capacidade total: aproximadamente 5,7 litros da mistura. Procedimento: drenagem pelo bujao do radiador, lavagem interna com agua, reabastecimento da mistura, sangria do sistema (eliminacao de bolhas de ar), funcionamento ate atingir temperatura normal (ventoinha acionando), verificacao de vazamentos e nivel.

**Consequencias de nao fazer:** Fluido contaminado causa corrosao interna do radiador, bloco, cabecote e bomba dagua, formacao de borra e depositos reduzindo eficiencia de troca termica, superaquecimento, danos ao radiador, bomba dagua (R$ 280 a R$ 450), termostato (R$ 120 a R$ 220) e motor, possivel empenamento do cabecote.

[PECAS]
ORIGINAL|G013A8JM1|Aditivo Radiador G13 VW Original|1.5L|125.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|4L|24.00
SIMILAR|RADIADOR-G13|Repsol|Aditivo Radiador G13 Longa Duracao|1.5L|65.00
SIMILAR|COOLANT-G13|Wurth|Aditivo Radiador G13 Organico|1.5L|68.00
SIMILAR|G13-VALVOLINE|Valvoline|Aditivo Radiador G13 Universal|1.5L|62.00
[/PECAS]'
        ],
        [
            'Higienizacao Profunda do Sistema de Ar Condicionado',
            50000,
            '60',
            180.00,
            'Baixa',
            '[CATEGORIA: Ar-condicionado] [TEMPO: 85 minutos]

Limpeza profissional do sistema de ar condicionado: aplicacao de espuma higienizadora no evaporador atraves da caixa de ar, aspiracao da espuma e residuos, aplicacao de bactericida/fungicida por nebulizacao, limpeza do dreno do evaporador (frequentemente entupido), troca do filtro de cabine. Verificacao de pressao do gas refrigerante R-134a, teste de vazamentos com detector eletronico, temperatura de saida (deve atingir 4-7C). Teste de funcionamento do compressor, embreagem eletromagnetica e eletroventilador do condensador.

**Consequencias de nao fazer:** Proliferacao de fungos e bacterias no evaporador, mau cheiro persistente (odor de mofo), alergias respiratorias graves, obstrucao do dreno causando infiltracao de agua no assoalho e modulo eletronico, reducao da eficiencia do sistema em ate 40%.

[PECAS]
ORIGINAL|6R0820367|Filtro Ar Condicionado Original VW Saveiro|1|115.00
SIMILAR|HIGIAR-500|Wurth|Higienizador Sistema Ar Condicionado|500ML|55.00
SIMILAR|KLIMACLEAN|Wynns|Limpador Ar Condicionado Automotivo|500ML|60.00
SIMILAR|CU2545/1|Mann|Filtro Cabine Saveiro Gol Voyage|1|45.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM - CRITICA ====================
        [
            'Troca de Oleo e Filtros Completos',
            60000,
            '72',
            140.00,
            'Critica',
            '[CATEGORIA: Motor/Filtros] [TEMPO: 55 minutos]

Servico completo conforme especificacoes anteriores.

[PECAS]
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
ORIGINAL|04E129620A|Filtro Ar Motor VW Saveiro 1.6 MSI|1|125.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
[/PECAS]'
        ],
        [
            'SUBSTITUICAO OBRIGATORIA DA CORREIA DENTADA + KIT COMPLETO',
            60000,
            '72',
            580.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 180 minutos]

ITEM MAIS CRITICO DE MANUTENCAO DO MOTOR MSI. Substituicao obrigatoria da correia dentada de sincronismo, tensor automatico e polia tensora. Codigo Continental/Contitech CT1167K1. MOTOR MSI 1.6 16V E DO TIPO INTERFERENTE: se a correia romper, os pistoes colidem com as valvulas causando danos catastroficos. Manual VW recomenda troca aos 60.000 km ou 6 anos. Procedimento exige ferramentas especiais de travamento (ponto morto superior PMS e comando de valvulas). OBRIGATORIO substituir tambem a bomba dagua preventivamente (acionada pela correia, economia de mao de obra).

**Consequencias de nao fazer:** Rompimento da correia dentada causa colisao entre pistoes e 16 valvulas (motor interferente), empenamento/quebra de todas as valvulas, danos severos aos pistoes, cabecote destruido, necessidade de retifica completa do motor ou substituicao. CUSTO DE REPARO: R$ 8.500 a R$ 16.000. Esta e a falha mecanica mais cara que pode ocorrer no veiculo.

[PECAS]
ORIGINAL|04E121011R|Correia Dentada VW Saveiro 1.6 MSI|1|285.00
ORIGINAL|04E109479G|Tensor Automatico Correia Dentada VW|1|395.00
ORIGINAL|04E109243E|Polia Tensora Correia Dentada VW|1|185.00
ORIGINAL|04E121600L|Bomba Dagua VW Saveiro 1.6 MSI|1|385.00
SIMILAR|CT1167K1|Contitech|Kit Correia Dentada MSI 1.6 Completo|1|485.00
SIMILAR|TB1167|Dayco|Correia Dentada Saveiro 1.6|1|165.00
SIMILAR|T41167|Gates|Tensor Automatico Saveiro MSI|1|245.00
SIMILAR|PA1167|Nakata|Polia Tensora Saveiro 1.6|1|108.00
SIMILAR|WP1167|Nakata|Bomba Dagua Saveiro MSI 1.6|1|185.00
SIMILAR|PA1167|Urba|Bomba Dagua VW MSI|1|195.00
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

[PECAS]
ORIGINAL|04E905612|Jogo 4 Velas Ignicao VW Saveiro 1.6 MSI|4|295.00
SIMILAR|BKR7ESBD|NGK|Jogo 4 Velas Laser Iridium Saveiro 1.6|4|185.00
SIMILAR|FR7DPX|Bosch|Jogo 4 Velas Platina Saveiro MSI|4|195.00
[/PECAS]'
        ],
        [
            'Substituicao de Discos e Pastilhas de Freio Dianteiros',
            60000,
            '72',
            180.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 95 minutos]

Substituicao completa do conjunto: jogo de pastilhas (4 pecas) + par de discos de freio ventilados dianteiros 4 furos, diametro 280mm. Limpeza das pincas, lubrificacao dos pinos-guia, verificacao dos pistoes e coifas. Espessura minima dos discos: verificar marcacao gravada no disco (geralmente 24mm). Sangria do sistema. Teste em pista. Discos devem ser substituidos em par sempre. ATENCAO: Veiculo de carga requer freios em perfeito estado.

[PECAS]
ORIGINAL|5C0698151|Jogo Pastilhas Freio Diant VW Saveiro|1|215.00
ORIGINAL|5C0615301B|Par Discos Freio Diant VW Saveiro|2|495.00
SIMILAR|N2176|Cobreq|Jogo Pastilhas Freio Diant Saveiro 2017|1|88.00
SIMILAR|HQJ2185|Jurid|Jogo Pastilhas Freio Diant Saveiro|1|95.00
SIMILAR|DF2185|Fremax|Par Discos Freio Ventilado Saveiro|2|285.00
SIMILAR|RC2185|Cobreq|Par Discos Freio Diant Saveiro|2|275.00
SIMILAR|BD2185|TRW|Par Discos Freio Saveiro 1.6|2|295.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            60000,
            '24',
            105.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Terceira troca do fluido de freio (intervalo a cada 2 anos independente da km).

[PECAS]
ORIGINAL|B000750M3|Fluido de Freio DOT 4 VW Original|500ML|52.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|30.00
SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|35.00
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
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
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
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
ORIGINAL|04E129620A|Filtro Ar Motor VW Saveiro 1.6 MSI|1|125.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
SIMILAR|C21014/1|Mann|Filtro Ar Saveiro Gol Polo 1.6|1|52.00
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

[PECAS]
ORIGINAL|04E905612|Jogo 4 Velas Ignicao VW Saveiro 1.6 MSI|4|295.00
SIMILAR|BKR7ESBD|NGK|Jogo 4 Velas Laser Iridium Saveiro 1.6|4|185.00
SIMILAR|FR7DPX|Bosch|Jogo 4 Velas Platina Saveiro MSI|4|195.00
[/PECAS]'
        ],
        [
            'Substituicao de Lonas e Tambores de Freio Traseiros',
            80000,
            '96',
            205.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 125 minutos]

Substituicao das lonas de freio traseiras (sapatas) e verificacao/retifica ou substituicao dos tambores traseiros. Sistema de freio a tambor traseiro. Revisao completa: limpeza dos tambores, verificacao dos cilindros de roda (vazamentos, pistoes travados), molas de retorno, reguladores automaticos, cabo do freio de estacionamento. Retifica ou substituicao dos tambores conforme diametro interno maximo gravado. Regulagem do freio de estacionamento. Espessura minima das lonas: 2mm. Sangria do sistema. ATENCAO: Veiculo de carga exige freios traseiros em perfeito estado.

**Consequencias de nao fazer:** Desgaste das lonas ate o rebite causando danos aos tambores, perda de eficiencia do freio traseiro sobrecarregando o dianteiro em ate 65%, desbalanceamento da frenagem, freio de estacionamento inoperante (reprovacao na inspecao), necessidade de substituicao dos tambores, acidentes por frenagem deficiente especialmente com carga.

[PECAS]
ORIGINAL|5C0698525A|Jogo Lonas Freio Traseiro VW Saveiro|1|175.00
ORIGINAL|5C0609617|Par Tambores Freio Traseiro VW Saveiro|2|425.00
SIMILAR|HI1285|Fras-le|Jogo Lonas Freio Traseiro Saveiro|1|75.00
SIMILAR|N1285|Cobreq|Jogo Lonas Freio Traseiro Saveiro|1|70.00
SIMILAR|TT2285|TRW|Par Tambores Freio Traseiro Saveiro|2|245.00
SIMILAR|RT2285|Fremax|Par Tambores Freio Traseiro Saveiro|2|235.00
[/PECAS]'
        ],
        [
            'Substituicao de Amortecedores',
            80000,
            '96',
            290.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 155 minutos]

Substituicao do conjunto de 4 amortecedores (2 dianteiros tipo McPherson + 2 traseiros tipo telescopico) incluindo kits de reparo (coxins superiores, batentes, coifas). ATENCAO: Picape tem suspensao reforcada para carga - usar amortecedores especificos para Saveiro. Amortecedores desgastados perdem capacidade causando perda de aderencia, desconforto e desgaste irregular de pneus. Teste: pressionar cada canto do veiculo, deve retornar sem oscilar. Inspecao de vazamento de oleo. Recomenda-se alinhamento apos a troca.

**Consequencias de nao fazer:** Perda de aderencia dos pneus ao solo, aumento da distancia de frenagem em ate 20%, perda de estabilidade em curvas especialmente com carga, desgaste irregular e acelerado dos pneus, fadiga de componentes da suspensao (bandejas, buchas), desconforto aos ocupantes, trepidacao, risco de capotamento com carga lateral, perda de controle.

[PECAS]
ORIGINAL|5C5413031D|Amortecedor Dianteiro VW Saveiro|2|625.00
ORIGINAL|5C5513025R|Amortecedor Traseiro VW Saveiro|2|585.00
SIMILAR|HG34785|Monroe|Amortecedor Diant Saveiro Gas|2|385.00
SIMILAR|HG34786|Monroe|Amortecedor Tras Saveiro Gas|2|365.00
SIMILAR|AM34785|Cofap|Amortecedor Diant Saveiro Turbogas|2|325.00
SIMILAR|AM34786|Cofap|Amortecedor Tras Saveiro Turbogas|2|305.00
SIMILAR|N34785|Nakata|Amortecedor Diant Saveiro 1.6|2|285.00
SIMILAR|N34786|Nakata|Amortecedor Tras Saveiro 1.6|2|265.00
[/PECAS]'
        ],
        [
            'Substituicao de Correias Auxiliares',
            80000,
            '96',
            90.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Segunda troca da correia do alternador conforme especificacoes da revisao de 40.000 km. Verificar polia do motor conforme recall.

[PECAS]
ORIGINAL|04E260849|Correia Alternador VW Saveiro 1.6 MSI|1|105.00
SIMILAR|6PK1194|Gates|Correia Poly-V Alternador Saveiro|1|45.00
SIMILAR|6PK1194|Continental|Correia Poly-V Saveiro 1.6|1|42.00
[/PECAS]'
        ],
        [
            'Substituicao de Pastilhas de Freio Dianteiras',
            80000,
            '96',
            150.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 65 minutos]

Terceira troca das pastilhas de freio dianteiras conforme especificacoes anteriores.

[PECAS]
ORIGINAL|5C0698151|Jogo Pastilhas Freio Diant VW Saveiro|1|215.00
SIMILAR|N2176|Cobreq|Jogo Pastilhas Freio Diant Saveiro 2017|1|88.00
SIMILAR|HQJ2185|Jurid|Jogo Pastilhas Freio Diant Saveiro|1|95.00
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
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
[/PECAS]'
        ],
        [
            'Troca de Fluido de Freio DOT 4',
            90000,
            '24',
            105.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 55 minutos]

Quarta/quinta troca do fluido de freio (intervalo a cada 2 anos).

[PECAS]
ORIGINAL|B000750M3|Fluido de Freio DOT 4 VW Original|500ML|52.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|30.00
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
ORIGINAL|030115561AR|Filtro de Oleo Motor VW Saveiro 1.6 MSI|1|68.00
ORIGINAL|G052577M2|Oleo Motor VW Maxi Performance 5W-40|4L|295.00
ORIGINAL|04E129620A|Filtro Ar Motor VW Saveiro 1.6 MSI|1|125.00
SIMILAR|W7125|Mann|Filtro Oleo Saveiro Gol Fox 1.6|1|30.00
SIMILAR|5W40-CASTROL|Castrol|Oleo Magnatec 5W-40 Sintetico API SN|4L|165.00
SIMILAR|C21014/1|Mann|Filtro Ar Saveiro Gol Polo 1.6|1|52.00
[/PECAS]'
        ],
        [
            'Substituicao de Velas de Ignicao',
            100000,
            '120',
            75.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Quinta troca das velas de ignicao conforme especificacoes anteriores.

[PECAS]
ORIGINAL|04E905612|Jogo 4 Velas Ignicao VW Saveiro 1.6 MSI|4|295.00
SIMILAR|BKR7ESBD|NGK|Jogo 4 Velas Laser Iridium Saveiro 1.6|4|185.00
SIMILAR|FR7DPX|Bosch|Jogo 4 Velas Platina Saveiro MSI|4|195.00
[/PECAS]'
        ],
        [
            'Substituicao da Bateria',
            100000,
            '60',
            40.00,
            'Media',
            '[CATEGORIA: Eletrica] [TEMPO: 30 minutos]

Substituicao da bateria automotiva 12V. Volkswagen Saveiro 1.6 utiliza bateria de 60Ah a 70Ah com corrente de partida (CCA) de 480A a 550A. Baterias seladas livre de manutencao tem vida util de 3 a 5 anos. Teste de carga e alternador antes da troca. Limpeza dos terminais e aplicacao de graxa protetora. IMPORTANTE: Veiculo com multiplos sistemas eletronicos requer programacao apos troca. Backup de configuracoes. Dimensoes: 275mm x 175mm x 190mm.

**Consequencias de nao fazer:** Falha de partida especialmente em dias frios, necessidade de carga/chupeta frequente, danos ao alternador por sobrecarga, falha dos sistemas eletronicos, perda de memoria dos sistemas, necessidade de reboque.

[PECAS]
ORIGINAL|000915105CF|Bateria 12V 60Ah VW Original|1|565.00
SIMILAR|60GD-500|Moura|Bateria 12V 60Ah 500A Selada|1|345.00
SIMILAR|60D-520|Heliar|Bateria 12V 60Ah 520A Free|1|355.00
SIMILAR|B60DH|Bosch|Bateria 12V 60Ah S4 Free|1|395.00
SIMILAR|60AH-480|Zetta|Bateria 12V 60Ah Selada|1|295.00
[/PECAS]'
        ],
        [
            'Troca de Fluido do Sistema de Arrefecimento',
            100000,
            '120',
            115.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 70 minutos]

Segunda troca do fluido de arrefecimento conforme especificacoes da revisao de 50.000 km.

[PECAS]
ORIGINAL|G013A8JM1|Aditivo Radiador G13 VW Original|1.5L|125.00
ORIGINAL|AGUA-DESM|Agua Desmineralizada|4L|24.00
SIMILAR|RADIADOR-G13|Repsol|Aditivo Radiador G13 Longa Duracao|1.5L|65.00
SIMILAR|COOLANT-G13|Wurth|Aditivo Radiador G13 Organico|1.5L|68.00
[/PECAS]'
        ],

        // ==================== ITENS ESPECIAIS ====================
        [
            'Substituicao de Pneus (por tempo ou desgaste)',
            55000,
            '60',
            60.00,
            'Critica',
            '[CATEGORIA: Pneus] [TEMPO: 85 minutos para jogo completo]

Volkswagen Saveiro Robust utiliza pneus 195/70 R15. Vida util media: 45.000 a 55.000 km ou 5 anos (o que vier primeiro). Borracha envelhece mesmo sem uso (oxidacao, ressecamento). ATENCAO ESPECIAL: Picape tem distribuicao de peso diferente - calibrar 35 PSI dianteiros sem carga, 40 PSI traseiros com carga maxima (740 kg). Verificar mensalmente: pressao, desgaste da banda (minimo legal 1,6mm medido nos TWI), deformacoes, cortes laterais, data de fabricacao (codigo DOT). Realizar rodizio a cada 10.000 km.

**Consequencias de nao fazer:** Pneus velhos/gastos aumentam distancia de frenagem em ate 40%, aquaplanagem em piso molhado, estouro em velocidade causando acidentes graves especialmente com carga, capotamento, multa gravissima (R$ 293,47) e 7 pontos na CNH por pneu irregular, reprovacao em inspecao veicular.

[PECAS]
SIMILAR|195/70R15|Pirelli|Pneu Cinturato P4 195/70 R15|4|1380.00
SIMILAR|195/70R15|Goodyear|Pneu Direction Sport 195/70 R15|4|1280.00
SIMILAR|195/70R15|Bridgestone|Pneu Turanza ER300 195/70 R15|4|1320.00
SIMILAR|195/70R15|Continental|Pneu ContiPowerContact 195/70 R15|4|1350.00
[/PECAS]'
        ],

        // ==================== RECALL E PROBLEMAS CONHECIDOS ====================
        [
            'RECALL CRITICO - Polia do Motor (2020-2021)',
            1000,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: Verificacao imediata]

PROBLEMA GRAVE de possivel soltura da polia do motor com consequente PERDA DA ASSISTENCIA DE DIRECAO E FALHA DE FUNCIONAMENTO DO ALTERNADOR. Afeta Saveiro com chassis NP006511 a NP012437 (nao sequenciais).

SINAIS DE ALERTA:
- Ruido metalico vindo do motor
- Luz de bateria no painel
- Direcao mais pesada
- Vibracao anormal no motor

RISCOS: ESSE DEFEITO AUMENTA O RISCO DE ACIDENTES COM DANOS MATERIAIS E LESOES FISICAS OU FATAIS AOS OCUPANTES E A TERCEIROS.

PROCEDIMENTO: VERIFICAR URGENTEMENTE no site www.volkswagen.com.br ou telefone 0800 770 4571. Reparo: verificacao e, se necessario, substituicao da polia do motor. SERVICO GRATUITO EM CONCESSIONARIA.

[PECAS]
Servico gratuito em concessionaria autorizada Volkswagen
[/PECAS]'
        ],
        [
            'Motor MSI - Motor Batendo (Problema Comum)',
            20000,
            '24',
            0.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: Monitoramento continuo]

PROBLEMA MUITO COMUM relatado por donos de Saveiro: barulhos continuos vindos da parte dianteira da picape, que surgem do motor. E comum relatos de donos de Saveiro que dizem que o automovel comecou a bater o motor com pouco tempo de uso.

SINAIS DE ALERTA:
- Ruido de batida metalica no motor (tec-tec-tec)
- Ruido aparece em aceleracoes
- Som aumenta com motor quente
- Ruido vem da parte superior do motor

CAUSAS PROVAVEIS:
- Folga excessiva em balancins/tuchos
- Problema no eixo comando de valvulas
- Tensionador da correia dentada com folga
- Lubrificacao inadequada

PREVENCAO:
- SEMPRE utilizar oleo 5W-40 sintetico VW 508/509
- Trocar oleo rigorosamente a cada 10.000 km ou 12 meses
- Verificar nivel de oleo semanalmente
- NUNCA usar oleo inferior a especificacao

[PECAS]
Monitoramento e prevencao - diagnostico especializado se ruido aparecer
[/PECAS]'
        ],
        [
            'Motor MSI - Correia Dentada CRITICA (Problema Conhecido)',
            50000,
            '60',
            0.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: Monitoramento continuo]

A correia dentada da Saveiro 1.6 MSI e o item de manutencao mais critico. Motor MSI e do tipo interferente: ruptura causa colisao entre pistoes e valvulas. Observadores relatam casos de motor batendo piorando apos ultrapassar 60.000 km sem trocar a correia.

SINAIS DE ALERTA (ruptura iminente):
- Ruido agudo/chiado vindo da correia
- Estalidos ao acelerar
- Correia com aspecto ressecado ou trincas visiveis
- Motor chegando aos 60.000 km ou 6 anos
- Ruido de batida metalica aumentando

PREVENCAO:
- Troca OBRIGATORIA aos 60.000 km ou 6 anos (o que vier primeiro)
- NUNCA ultrapassar esse intervalo
- SEMPRE substituir tensor e polia tensora junto
- Substituir bomba dagua preventivamente
- Usar apenas pecas de qualidade (original ou Continental/Gates)
- Verificar visualmente a correia a cada revisao aos 50.000 km

Custo de substituicao preventiva: R$ 1.100 a R$ 1.500
Custo de reparo se romper: R$ 8.500 a R$ 16.000

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

Saveiro e veiculo comercial (picape) com capacidade de carga de 740 kg. Uso comercial intenso e transporte de carga requerem atencao especial em varios sistemas.

MANUTENCOES CRITICAS PARA USO COMERCIAL:
- Freios: Pastilhas e lonas desgastam mais rapido com carga - verificar a cada 5.000 km
- Suspensao: Amortecedores e molas sofrem mais - inspecionar regularmente
- Pneus: Calibragem diferenciada (35 PSI dianteiros/40 PSI traseiros com carga)
- Oleo: Trocar a cada 7.500 km em uso severo
- Fluido freio: Trocar a cada 12 meses em uso intenso

USO SEVERO (reduzir intervalos pela metade):
- Trajetos curtos (<10 km) diarios
- Transporte frequente de carga maxima
- Trafego urbano congestionado constante
- Estradas de terra frequentes
- Mais de 8 horas/dia de uso
- Reboque de carretinha

[PECAS]
Monitoramento e prevencao - sem pecas especificas
[/PECAS]'
        ],
        [
            'Oleo Sintetico 5W-40 - OBRIGATORIO',
            10000,
            '12',
            0.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: Monitoramento continuo]

Motor MSI 1.6 EA211 EXIGE oleo 100% sintetico 5W-40 especificacao VW 508 88/509 99. Usar oleo inadequado causa desgaste prematuro e pode estar relacionado ao problema de motor batendo.

SINAIS DE ALERTA:
- Ruido de cascalho no motor
- Consumo de oleo entre trocas
- Perda de potencia
- Luz de pressao de oleo piscando
- Motor batendo

PREVENCAO:
- SEMPRE utilizar oleo 5W-40 sintetico VW 508/509
- NUNCA usar oleo 10W-40, 15W-40 ou 20W-50
- Capacidade: exatos 4,0 litros com filtro
- Trocar oleo rigorosamente a cada 10.000 km ou 12 meses
- Verificar nivel semanalmente
- Usar filtro de qualidade (original ou Mann/Tecfil)

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
            'motor' => 'MSI EA211 1.6 16V Flex - 120 cv',
            'oleo' => '5W-40 100% Sintetico VW 508/509 - 4.0 litros',
            'velas' => 'NGK BKR7ESB-D Laser Iridium - a cada 20.000 km',
            'correia_dentada' => '60.000 km ou 6 anos - MOTOR INTERFERENTE',
            'atencao_especial' => [
                'CORREIA DENTADA CRITICA - 60.000 km ou 6 anos (motor interferente)',
                'RECALL polia do motor 2020-2021 - verificar www.volkswagen.com.br',
                'MOTOR BATENDO - problema comum, usar apenas oleo 5W-40 sintetico',
                'Veiculo comercial - cuidados especiais com freios e suspensao',
                'NUNCA usar oleo 10W-40, 15W-40 ou 20W-50'
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
