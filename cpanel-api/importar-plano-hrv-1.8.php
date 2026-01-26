<?php
/**
 * Script para importar Plano de Manutencao Honda HR-V 1.8 16V i-VTEC Flex
 * Gerado via Perplexity AI em Janeiro/2026
 *
 * IMPORTANTE: Motor R18Z usa CORRENTE de comando (nao correia dentada)
 * Transmissao CVT - usar exclusivamente ATF DW-1
 * Oleo motor: 0W20 sintetico obrigatorio
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-hrv-1.8.php?confirmar=SIM
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
        'url_exemplo' => 'https://floripa.in9automacao.com.br/importar-plano-hrv-1.8.php?confirmar=SIM',
        'modelo' => 'Honda HR-V 1.8 16V i-VTEC Flex',
        'motor' => 'R18Z 1.8 16V SOHC i-VTEC (140cv)',
        'notas' => [
            'Corrente de comando (nao requer troca periodica)',
            'CVT - usar somente ATF DW-1',
            'Oleo 0W20 sintetico obrigatorio',
            'Freio a disco nas 4 rodas'
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
    $modeloNome = "HR-V";

    // PASSO 1: Deletar planos antigos deste modelo
    $stmt = $conn->prepare("DELETE FROM `Planos_Manutenção` WHERE modelo_carro = ?");
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
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Troca do oleo lubrificante e filtro de oleo do motor R18Z 1.8 16V i-VTEC. OBRIGATORIO usar oleo sintetico 0W20 API SN/ILSAC GF-5 - viscosidade especifica para motor Honda com sistema i-VTEC.

Capacidade: 3,7 litros com filtro. Motor possui sistema de acionamento variavel de valvulas que requer oleo de baixa viscosidade para funcionamento correto.

**Consequencias de nao fazer:** Desgaste prematuro do sistema i-VTEC, perda de potencia, aumento de consumo, danos ao motor.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|W610/10|Mann|Filtro Oleo HR-V 1.8|1|42.00
SIMILAR|PH8999|Fram|Filtro Oleo Honda 1.8|1|38.00
SIMILAR|0W20-SHELL|Shell|Oleo Shell Helix Ultra 0W20|3.7L|178.00
[/PECAS]'
        ],
        [
            'Inspecao Geral e Niveis',
            10000,
            '12',
            60.00,
            'Media',
            '[CATEGORIA: Geral] [TEMPO: 25 minutos]

Inspecao geral de todos os sistemas do veiculo: verificar niveis de fluidos (freio DOT 4, direcao hidraulica PSF-II, arrefecimento Type 2, CVT), condicao de correias e mangueiras, vazamentos, estado de pneus e pressao.

Verificar funcionamento de luzes, limpadores, freio de mao. Teste de bateria e sistema de carga.

**Consequencias de nao fazer:** Problemas nao detectados podem evoluir para falhas maiores e mais caras.

[PECAS]
[/PECAS]'
        ],
        [
            'Lubrificacao Geral - Chassis e Articulacoes',
            10000,
            '12',
            45.00,
            'Baixa',
            '[CATEGORIA: Suspensao] [TEMPO: 20 minutos]

Aplicar graxa nos pivos, terminais de direcao, articulacoes da suspensao e mecanismos de portas. Usar graxa a base de litio NLGI 2.

**Consequencias de nao fazer:** Desgaste prematuro de componentes da suspensao, ruidos, folgas.

[PECAS]
ORIGINAL|GREASE-HONDA|Graxa Multiuso Litio NLGI 2 Honda|0.5kg|45.00
SIMILAR|S2-V220|Shell|Graxa Shell Gadus S2 V220 NLGI 2|0.5kg|26.00
SIMILAR|EP2|Mobil|Graxa Mobil Mobilux EP2|0.5kg|28.00
[/PECAS]'
        ],

        // ==================== REVISAO 15.000 KM ====================
        [
            'Troca do Filtro de Cabine/Ar Condicionado',
            15000,
            '12',
            40.00,
            'Baixa',
            '[CATEGORIA: A/C] [TEMPO: 15 minutos]

Substituir filtro de ar da cabine (filtro de polen). Melhora qualidade do ar interno e eficiencia do ar condicionado.

**Consequencias de nao fazer:** Reducao do fluxo de ar, mau cheiro, alergias, sobrecarga do sistema de A/C.

[PECAS]
ORIGINAL|80292-TF0-G01|Filtro Ar Condicionado Cabine HR-V Original|1|115.00
SIMILAR|WKL201|Wega|Filtro Cabine HR-V 1.8|1|62.00
SIMILAR|ACP435|Tecfil|Filtro Ar Condicionado HR-V|1|58.00
SIMILAR|CU26004|Mann|Filtro Cabine Honda HR-V|1|65.00
[/PECAS]'
        ],

        // ==================== REVISAO 20.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            20000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Segunda troca de oleo. Manter rigorosamente oleo sintetico 0W20 - motor R18Z i-VTEC requer esta viscosidade para funcionamento correto do sistema variavel de valvulas.

**Consequencias de nao fazer:** Danos ao sistema i-VTEC, desgaste prematuro, perda de potencia e economia.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|W610/10|Mann|Filtro Oleo HR-V 1.8|1|42.00
SIMILAR|0W20-MOBIL|Mobil|Oleo Mobil 1 0W20|3.7L|192.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Ar do Motor',
            20000,
            '12',
            35.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 15 minutos]

Substituir elemento filtrante de ar do motor. Filtro saturado reduz potencia e aumenta consumo.

**Consequencias de nao fazer:** Perda de potencia (5-10%), aumento de consumo, contaminacao do motor.

[PECAS]
ORIGINAL|17220-RZA-000|Filtro de Ar Motor HR-V 1.8 Original|1|128.00
SIMILAR|JFA1103|Wega|Filtro Ar HR-V 1.8 16V|1|68.00
SIMILAR|ARL9792|Tecfil|Filtro Ar Honda HR-V|1|65.00
SIMILAR|C27009|Mann|Filtro Ar Motor HR-V|1|72.00
[/PECAS]'
        ],

        // ==================== REVISAO 30.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            30000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Terceira troca de oleo. Motor R18Z com 30.000 km - verificar consumo de oleo e condicao do sistema i-VTEC durante troca.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|PFM7|Tecfil|Filtro Oleo HR-V 1.8|1|40.00
SIMILAR|0W20-CASTROL|Castrol|Oleo Castrol Edge 0W20|3.7L|185.00
[/PECAS]'
        ],
        [
            'Inspecao das Pastilhas de Freio',
            30000,
            '24',
            50.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 30 minutos]

Inspecionar espessura das pastilhas dianteiras e traseiras. HR-V possui freio a disco nas 4 rodas (sistema Akebono). Medir desgaste e avaliar necessidade de troca.

Trocar se espessura menor que 3mm. Verificar tambem condicao dos discos.

**Consequencias de nao fazer:** Reducao da capacidade de frenagem, danos aos discos, risco de acidente.

[PECAS]
ORIGINAL|45022-TF0-G00|Pastilhas Freio Dianteiras HR-V Original|1|285.00
ORIGINAL|43022-TF0-G01|Pastilhas Freio Traseiras HR-V Original|1|245.00
SIMILAR|N-1780|Cobreq|Pastilhas Dianteiras HR-V 1.8|1|158.00
SIMILAR|PD/1782|Fras-le|Pastilhas Dianteiras HR-V|1|165.00
SIMILAR|PD/1495|Fras-le|Pastilhas Traseiras HR-V|1|138.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Cabine',
            30000,
            '12',
            40.00,
            'Baixa',
            '[CATEGORIA: A/C] [TEMPO: 15 minutos]

Segunda troca do filtro de cabine. Manter ar interno limpo e sistema de A/C funcionando com eficiencia.

[PECAS]
ORIGINAL|80292-TF0-G01|Filtro Ar Condicionado Cabine HR-V Original|1|115.00
SIMILAR|WKL201|Wega|Filtro Cabine HR-V 1.8|1|62.00
SIMILAR|ACP435|Tecfil|Filtro Ar Condicionado HR-V|1|58.00
[/PECAS]'
        ],

        // ==================== REVISAO 40.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            40000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Quarta troca de oleo. Manter oleo 0W20 sintetico. Com 40.000 km verificar detalhadamente sistema de lubrificacao.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|W610/10|Mann|Filtro Oleo HR-V 1.8|1|42.00
SIMILAR|0W20-SHELL|Shell|Oleo Shell Helix Ultra 0W20|3.7L|178.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Ar do Motor',
            40000,
            '12',
            35.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 15 minutos]

Segunda troca do filtro de ar. Importante para manter eficiencia do motor.

[PECAS]
ORIGINAL|17220-RZA-000|Filtro de Ar Motor HR-V 1.8 Original|1|128.00
SIMILAR|JFA1103|Wega|Filtro Ar HR-V 1.8 16V|1|68.00
SIMILAR|ARL9792|Tecfil|Filtro Ar Honda HR-V|1|65.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Combustivel',
            40000,
            '24',
            180.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 60 minutos]

Substituir filtro de combustivel (in-tank - dentro do tanque). Requer acesso pela parte superior ou remocao do tanque em alguns casos. Mao de obra mais complexa.

**Consequencias de nao fazer:** Entupimento pode danificar bomba de combustivel (peca cara), falhas de ignicao, perda de potencia.

[PECAS]
ORIGINAL|16010-RZA-000|Filtro Combustivel HR-V 1.8 Flex Original|1|145.00
SIMILAR|GI04/7|Tecfil|Filtro Combustivel HR-V Flex|1|72.00
SIMILAR|FCI1803|Wega|Filtro Combustivel Honda HR-V|1|68.00
SIMILAR|WK512|Mann|Filtro Combustivel HR-V|1|75.00
[/PECAS]'
        ],
        [
            'Troca da Correia Poly-V/Acessorios',
            40000,
            '36',
            90.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Substituir correia Poly-V de acessorios (alternador, ar condicionado, direcao hidraulica). Correia 6PK2115 ou 6PK2120.

NOTA: Motor R18Z usa corrente de comando de valvulas (nao correia dentada). A correia Poly-V e apenas para acessorios.

**Consequencias de nao fazer:** Rompimento deixa veiculo parado (sem carga bateria, sem direcao assistida, sem A/C).

[PECAS]
ORIGINAL|56992-RZA-003|Correia Poly-V Acessorios HR-V 1.8 Original|1|152.00
SIMILAR|6PK2115|Gates|Correia Poly-V HR-V 1.8|1|78.00
SIMILAR|6PK2120|Continental|Correia Poly-V Honda 1.8|1|82.00
SIMILAR|6PK2115-D|Dayco|Correia Poly-V HR-V|1|80.00
[/PECAS]'
        ],
        [
            'Troca das Pastilhas de Freio Dianteiras',
            40000,
            '24',
            100.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 45 minutos]

Substituir pastilhas de freio dianteiras. Sistema Akebono. Verificar condicao dos discos - trocar se abaixo do limite ou com sulcos profundos.

**Consequencias de nao fazer:** Danos aos discos, falha de frenagem, risco de acidente grave.

[PECAS]
ORIGINAL|45022-TF0-G00|Pastilhas Freio Dianteiras HR-V Original|1|285.00
SIMILAR|N-1780|Cobreq|Pastilhas Dianteiras HR-V 1.8|1|158.00
SIMILAR|5173|Tecpads|Pastilhas Dianteiras HR-V SYL4254|1|148.00
SIMILAR|PD/1782|Fras-le|Pastilhas Dianteiras HR-V|1|165.00
[/PECAS]'
        ],

        // ==================== REVISAO 45.000 KM ====================
        [
            'Troca do Fluido de Freio',
            45000,
            '36',
            80.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 40 minutos]

Substituir fluido de freio. Usar DOT 4 - HR-V requer DOT 4 (nao DOT 3). Fluido higroscopico absorve umidade e perde eficiencia com o tempo.

Capacidade aproximada: 0,8 litro. Sangrar sistema completo.

**Consequencias de nao fazer:** Reducao do ponto de ebulicao, falha de freios em uso intenso (fadiga termica), corrosao interna.

[PECAS]
ORIGINAL|DOT4-HONDA|Fluido de Freio DOT 4 Honda Original|1L|45.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Bosch|1L|26.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4 TRW|1L|24.00
SIMILAR|DOT4-TEXACO|Texaco|Fluido Freio DOT 4|1L|22.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Cabine',
            45000,
            '12',
            40.00,
            'Baixa',
            '[CATEGORIA: A/C] [TEMPO: 15 minutos]

Terceira troca do filtro de cabine.

[PECAS]
ORIGINAL|80292-TF0-G01|Filtro Ar Condicionado Cabine HR-V Original|1|115.00
SIMILAR|WKL201|Wega|Filtro Cabine HR-V 1.8|1|62.00
SIMILAR|CU26004|Mann|Filtro Cabine Honda HR-V|1|65.00
[/PECAS]'
        ],

        // ==================== REVISAO 50.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            50000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Quinta troca de oleo. Marco de 50.000 km - fazer inspecao mais detalhada do motor e seus sistemas.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|W610/10|Mann|Filtro Oleo HR-V 1.8|1|42.00
SIMILAR|0W20-SHELL|Shell|Oleo Shell Helix Ultra 0W20|3.7L|178.00
[/PECAS]'
        ],
        [
            'Inspecao da Suspensao Completa',
            50000,
            '36',
            80.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 45 minutos]

Inspecao completa da suspensao: amortecedores (vazamento, eficiencia), buchas, pivos, terminais de direcao, bandejas.

Com 50.000 km componentes comecam a apresentar desgaste. Avaliar necessidade de troca preventiva.

**Consequencias de nao fazer:** Desgaste irregular de pneus, instabilidade, ruidos, comprometimento da seguranca.

[PECAS]
ORIGINAL|KIT-BUCHAS-HRV|Kit Buchas Suspensao Dianteira HR-V|1|245.00
SIMILAR|KIT-NAKATA|Nakata|Kit Buchas Suspensao HR-V|1|145.00
SIMILAR|KIT-COFAP|Cofap|Kit Buchas HR-V 1.8|1|155.00
[/PECAS]'
        ],
        [
            'Troca das Pastilhas de Freio Traseiras',
            50000,
            '36',
            90.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 40 minutos]

Substituir pastilhas de freio traseiras. HR-V possui disco nas 4 rodas - pastilhas traseiras duram mais que dianteiras mas devem ser trocadas nesta quilometragem.

[PECAS]
ORIGINAL|43022-TF0-G01|Pastilhas Freio Traseiras HR-V Original|1|245.00
SIMILAR|N-1781|Cobreq|Pastilhas Traseiras HR-V 1.8|1|132.00
SIMILAR|PD/1495|Fras-le|Pastilhas Traseiras HR-V|1|138.00
[/PECAS]'
        ],

        // ==================== REVISAO 60.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            60000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Sexta troca de oleo.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|PFM7|Tecfil|Filtro Oleo HR-V 1.8|1|40.00
SIMILAR|0W20-MOBIL|Mobil|Oleo Mobil 1 0W20|3.7L|192.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Ar do Motor',
            60000,
            '12',
            35.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 15 minutos]

Terceira troca do filtro de ar.

[PECAS]
ORIGINAL|17220-RZA-000|Filtro de Ar Motor HR-V 1.8 Original|1|128.00
SIMILAR|JFA1103|Wega|Filtro Ar HR-V 1.8 16V|1|68.00
SIMILAR|ARL9792|Tecfil|Filtro Ar Honda HR-V|1|65.00
[/PECAS]'
        ],
        [
            'Troca do Tensor da Correia de Acessorios',
            60000,
            '48',
            150.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 60 minutos]

ATENCAO: Tensor de correia de acessorios e ponto fraco conhecido do HR-V. Pode apresentar ruidos (chiado, rangido) e falhar prematuramente.

Tensor possui amortecedor hidraulico que perde eficiencia. Trocar preventivamente para evitar rompimento da correia.

**Consequencias de nao fazer:** Rompimento da correia deixa veiculo parado, possivel superaquecimento (sem bomba dagua).

[PECAS]
ORIGINAL|31170-RZA-014|Tensor Correia Acessorios HR-V 1.8 Original|1|518.00
SIMILAR|534031610|INA|Tensor Correia HR-V 1.8|1|298.00
SIMILAR|TENS-GATES|Gates|Tensor Correia Honda 1.8|1|315.00
SIMILAR|TENS-DAYCO|Dayco|Tensor Correia HR-V|1|305.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Cabine',
            60000,
            '12',
            40.00,
            'Baixa',
            '[CATEGORIA: A/C] [TEMPO: 15 minutos]

Quarta troca do filtro de cabine.

[PECAS]
ORIGINAL|80292-TF0-G01|Filtro Ar Condicionado Cabine HR-V Original|1|115.00
SIMILAR|WKL201|Wega|Filtro Cabine HR-V 1.8|1|62.00
SIMILAR|ACP435|Tecfil|Filtro Ar Condicionado HR-V|1|58.00
[/PECAS]'
        ],
        [
            'Inspecao dos Discos de Freio',
            60000,
            '36',
            60.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 30 minutos]

Medir espessura dos discos de freio dianteiros (320mm ventilados) e traseiros. Verificar empenamento, sulcos, trincas.

Trocar se abaixo do limite minimo ou com defeitos visiveis.

[PECAS]
ORIGINAL|45251-TF0-G01|Disco Freio Dianteiro Ventilado HR-V 320mm|2|380.00
SIMILAR|BD-FREMAX|Fremax|Disco Freio Dianteiro HR-V 1.8 par|2|225.00
SIMILAR|BD-HIPPER|Hipper|Disco Dianteiro HR-V par|2|238.00
[/PECAS]'
        ],

        // ==================== REVISAO 70.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            70000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Setima troca de oleo. Motor com 70.000 km - manter oleo 0W20 de qualidade para preservar sistema i-VTEC.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|W610/10|Mann|Filtro Oleo HR-V 1.8|1|42.00
SIMILAR|0W20-CASTROL|Castrol|Oleo Castrol Edge 0W20|3.7L|185.00
[/PECAS]'
        ],
        [
            'Troca dos Amortecedores Dianteiros',
            70000,
            '48',
            200.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 90 minutos]

Substituir amortecedores dianteiros. Com 70.000 km amortecedores perdem eficiencia mesmo sem vazamento aparente.

Trocar sempre em par. Apos troca, fazer alinhamento e balanceamento.

**Consequencias de nao fazer:** Instabilidade em curvas, aumento de distancia de frenagem, desgaste irregular de pneus.

[PECAS]
ORIGINAL|51605-TF0-G01|Amortecedor Dianteiro HR-V Original par|2|680.00
SIMILAR|AMORT-NAKATA|Nakata|Amortecedor Dianteiro HR-V par|2|450.00
SIMILAR|AMORT-COFAP|Cofap|Amortecedor Dianteiro HR-V par|2|465.00
SIMILAR|AMORT-MONROE|Monroe|Amortecedor Dianteiro HR-V par|2|485.00
[/PECAS]'
        ],
        [
            'Troca dos Amortecedores Traseiros',
            70000,
            '48',
            180.00,
            'Alta',
            '[CATEGORIA: Suspensao] [TEMPO: 75 minutos]

Substituir amortecedores traseiros. Trocar junto com dianteiros para manter equilibrio do veiculo.

[PECAS]
ORIGINAL|52610-TF0-G01|Amortecedor Traseiro HR-V Original par|2|620.00
SIMILAR|AMORT-NAKATA-T|Nakata|Amortecedor Traseiro HR-V par|2|405.00
SIMILAR|AMORT-COFAP-T|Cofap|Amortecedor Traseiro HR-V par|2|420.00
SIMILAR|AMORT-MONROE-T|Monroe|Amortecedor Traseiro HR-V par|2|440.00
[/PECAS]'
        ],

        // ==================== REVISAO 80.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            80000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Oitava troca de oleo.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|PH8999|Fram|Filtro Oleo Honda 1.8|1|38.00
SIMILAR|0W20-SHELL|Shell|Oleo Shell Helix Ultra 0W20|3.7L|178.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Ar do Motor',
            80000,
            '12',
            35.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 15 minutos]

Quarta troca do filtro de ar.

[PECAS]
ORIGINAL|17220-RZA-000|Filtro de Ar Motor HR-V 1.8 Original|1|128.00
SIMILAR|JFA1103|Wega|Filtro Ar HR-V 1.8 16V|1|68.00
SIMILAR|C27009|Mann|Filtro Ar Motor HR-V|1|72.00
[/PECAS]'
        ],
        [
            'Troca do Oleo CVT - Transmissao Automatica',
            80000,
            '60',
            250.00,
            'Critica',
            '[CATEGORIA: Transmissao] [TEMPO: 75 minutos]

CRITICO: Trocar oleo da transmissao CVT. OBRIGATORIO usar Honda ATF DW-1 ou equivalente certificado. Capacidade aproximada 3,2 litros.

ATENCAO: Usar oleo incorreto danifica CVT. Nao usar ATF convencional de cambio automatico tradicional.

**Consequencias de nao fazer:** Desgaste prematuro da CVT, solavancos, ruidos, falha da transmissao (reparo extremamente caro).

[PECAS]
ORIGINAL|ATF-DW1|Oleo Transmissao CVT Honda ATF DW-1|3.2L|304.00
SIMILAR|CVTF-IDEM|Idemitsu|Oleo CVT Idemitsu|3.2L|218.00
SIMILAR|CVTF-CAST|Castrol|Oleo Castrol Transmax CVT|3.2L|230.00
SIMILAR|CVTF-MOBIL|Mobil|Oleo Mobil CVT|3.2L|224.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Combustivel',
            80000,
            '24',
            180.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 60 minutos]

Segunda troca do filtro de combustivel.

[PECAS]
ORIGINAL|16010-RZA-000|Filtro Combustivel HR-V 1.8 Flex Original|1|145.00
SIMILAR|GI04/7|Tecfil|Filtro Combustivel HR-V Flex|1|72.00
SIMILAR|FCI1803|Wega|Filtro Combustivel Honda HR-V|1|68.00
[/PECAS]'
        ],
        [
            'Troca da Correia Poly-V/Acessorios',
            80000,
            '36',
            90.00,
            'Alta',
            '[CATEGORIA: Motor] [TEMPO: 45 minutos]

Segunda troca da correia de acessorios.

[PECAS]
ORIGINAL|56992-RZA-003|Correia Poly-V Acessorios HR-V 1.8 Original|1|152.00
SIMILAR|6PK2115|Gates|Correia Poly-V HR-V 1.8|1|78.00
SIMILAR|6PK2120|Continental|Correia Poly-V Honda 1.8|1|82.00
[/PECAS]'
        ],
        [
            'Troca do Fluido de Direcao Hidraulica',
            80000,
            '60',
            100.00,
            'Media',
            '[CATEGORIA: Direcao] [TEMPO: 40 minutos]

Trocar fluido de direcao hidraulica. Usar Honda PSF-II ou ATF equivalente. Capacidade aproximada 1 litro.

**Consequencias de nao fazer:** Ruidos na direcao, desgaste da bomba, direcao pesada.

[PECAS]
ORIGINAL|PSF-II|Fluido Direcao Hidraulica Honda PSF-II|1L|52.00
SIMILAR|ATF134|Shell|Fluido ATF 134 Shell|1L|30.00
SIMILAR|ATF220|Mobil|Fluido ATF 220 Mobil|1L|32.00
[/PECAS]'
        ],
        [
            'Troca das Pastilhas de Freio Dianteiras',
            80000,
            '24',
            100.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 45 minutos]

Segunda troca das pastilhas dianteiras.

[PECAS]
ORIGINAL|45022-TF0-G00|Pastilhas Freio Dianteiras HR-V Original|1|285.00
SIMILAR|N-1780|Cobreq|Pastilhas Dianteiras HR-V 1.8|1|158.00
SIMILAR|5173|Tecpads|Pastilhas Dianteiras HR-V SYL4254|1|148.00
[/PECAS]'
        ],

        // ==================== REVISAO 90.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            90000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Nona troca de oleo. Motor proximo dos 100.000 km - manter oleo de qualidade.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|W610/10|Mann|Filtro Oleo HR-V 1.8|1|42.00
SIMILAR|0W20-SHELL|Shell|Oleo Shell Helix Ultra 0W20|3.7L|178.00
[/PECAS]'
        ],
        [
            'Troca do Fluido de Freio',
            90000,
            '36',
            80.00,
            'Critica',
            '[CATEGORIA: Freios] [TEMPO: 40 minutos]

Segunda troca do fluido de freio DOT 4.

[PECAS]
ORIGINAL|DOT4-HONDA|Fluido de Freio DOT 4 Honda Original|1L|45.00
SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Bosch|1L|26.00
SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4 TRW|1L|24.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Cabine',
            90000,
            '12',
            40.00,
            'Baixa',
            '[CATEGORIA: A/C] [TEMPO: 15 minutos]

Sexta troca do filtro de cabine.

[PECAS]
ORIGINAL|80292-TF0-G01|Filtro Ar Condicionado Cabine HR-V Original|1|115.00
SIMILAR|WKL201|Wega|Filtro Cabine HR-V 1.8|1|62.00
[/PECAS]'
        ],

        // ==================== REVISAO 100.000 KM ====================
        [
            'Troca de Oleo e Filtro do Motor',
            100000,
            '12',
            120.00,
            'Critica',
            '[CATEGORIA: Motor] [TEMPO: 35 minutos]

Decima troca de oleo. Marco de 100.000 km - motor R18Z bem mantido deve estar em excelentes condicoes.

[PECAS]
ORIGINAL|15400-PLM-A02|Filtro de Oleo Honda HR-V 1.8 16V Original|1|85.00
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|3.7L|215.00
SIMILAR|PFM7|Tecfil|Filtro Oleo HR-V 1.8|1|40.00
SIMILAR|0W20-MOBIL|Mobil|Oleo Mobil 1 0W20|3.7L|192.00
[/PECAS]'
        ],
        [
            'Troca do Filtro de Ar do Motor',
            100000,
            '12',
            35.00,
            'Media',
            '[CATEGORIA: Motor] [TEMPO: 15 minutos]

Quinta troca do filtro de ar.

[PECAS]
ORIGINAL|17220-RZA-000|Filtro de Ar Motor HR-V 1.8 Original|1|128.00
SIMILAR|JFA1103|Wega|Filtro Ar HR-V 1.8 16V|1|68.00
SIMILAR|ARL9792|Tecfil|Filtro Ar Honda HR-V|1|65.00
[/PECAS]'
        ],
        [
            'Troca da Bomba Dagua',
            100000,
            '60',
            280.00,
            'Alta',
            '[CATEGORIA: Arrefecimento] [TEMPO: 120 minutos]

Substituir bomba dagua preventivamente. Com 100.000 km a bomba pode apresentar vazamento ou perda de eficiencia.

Motor R18Z usa corrente de comando - bomba dagua nao e acionada por correia dentada, mas ainda assim deve ser trocada preventivamente.

**Consequencias de nao fazer:** Superaquecimento pode danificar motor (junta de cabecote, empenamento).

[PECAS]
ORIGINAL|19200-RZA-004|Bomba Dagua HR-V 1.8 16V R18Z Original|1|365.00
SIMILAR|BDA-URBA|Urba|Bomba Dagua HR-V 1.8|1|205.00
SIMILAR|BDA-MTE|MTE-Thomson|Bomba Dagua HR-V 1.8|1|218.00
SIMILAR|BDA-DOLZ|Dolz|Bomba Dagua Honda R18Z|1|210.00
[/PECAS]'
        ],
        [
            'Troca das Pastilhas de Freio Traseiras',
            100000,
            '36',
            90.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 40 minutos]

Segunda troca das pastilhas traseiras.

[PECAS]
ORIGINAL|43022-TF0-G01|Pastilhas Freio Traseiras HR-V Original|1|245.00
SIMILAR|N-1781|Cobreq|Pastilhas Traseiras HR-V 1.8|1|132.00
SIMILAR|PD/1495|Fras-le|Pastilhas Traseiras HR-V|1|138.00
[/PECAS]'
        ],
        [
            'Troca dos Discos de Freio Dianteiros',
            100000,
            '60',
            180.00,
            'Alta',
            '[CATEGORIA: Freios] [TEMPO: 60 minutos]

Substituir discos de freio dianteiros (320mm ventilados). Verificar desgaste, empenamento, sulcos.

**Consequencias de nao fazer:** Vibracao ao frear, reducao de eficiencia, danos as pastilhas novas.

[PECAS]
ORIGINAL|45251-TF0-G01|Disco Freio Dianteiro Ventilado HR-V 320mm par|2|380.00
SIMILAR|BD-FREMAX|Fremax|Disco Freio Dianteiro HR-V 1.8 par|2|225.00
SIMILAR|BD-HIPPER|Hipper|Disco Dianteiro HR-V par|2|238.00
SIMILAR|BD-TRW|TRW|Disco Freio HR-V par|2|245.00
[/PECAS]'
        ],
        [
            'Revisao Geral de 100.000 km',
            100000,
            '60',
            250.00,
            'Alta',
            '[CATEGORIA: Geral] [TEMPO: 180 minutos]

Revisao completa de 100.000 km. Inspecao detalhada de todos os sistemas:
- Motor: compressao, vazamentos, ruidos
- Suspensao: folgas, desgastes
- Direcao: alinhamento, folgas
- Freios: eficiencia, desgaste
- Sistema eletrico: bateria, alternador, partida
- Transmissao CVT: funcionamento, ruidos
- Arrefecimento: mangueiras, radiador, ventoinha
- Escapamento: vazamentos, fixacoes

Documentar todas as condicoes para planejamento de manutencoes futuras.

[PECAS]
[/PECAS]'
        ],

        // ==================== ALERTAS TECNICOS ====================
        [
            'ALERTA: Tensor de Correia - Ponto Fraco',
            0,
            '0',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

PONTO DE ATENCAO: O tensor da correia de acessorios do HR-V e um ponto fraco conhecido. Pode apresentar ruidos (chiado, rangido) antes da quilometragem recomendada de troca.

Sintomas:
- Chiado ao ligar o motor (especialmente frio)
- Rangido ao acelerar
- Ruido que varia com a rotacao do motor

Se apresentar sintomas, trocar imediatamente mesmo antes de 60.000 km. Tensor original Honda e mais duravel que similares neste caso especifico.

[PECAS]
ORIGINAL|31170-RZA-014|Tensor Correia Acessorios HR-V 1.8 Original|1|518.00
SIMILAR|534031610|INA|Tensor Correia HR-V 1.8|1|298.00
[/PECAS]'
        ],
        [
            'ALERTA: Oleo Motor - Especificacao Critica',
            0,
            '0',
            0.00,
            'Critica',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

OBRIGATORIO: Motor R18Z 1.8 i-VTEC REQUER oleo sintetico 0W20.

NAO USAR:
- 5W30 (comum em outros veiculos)
- 5W40 (muito espesso)
- 10W40 (incorreto para este motor)
- Oleos minerais ou semi-sinteticos

O sistema i-VTEC (acionamento variavel de valvulas) depende de oleo de baixa viscosidade para funcionar corretamente. Usar oleo errado causa:
- Mau funcionamento do i-VTEC
- Perda de potencia
- Aumento de consumo
- Desgaste prematuro

Especificacoes aceitas: API SN, ILSAC GF-5 ou superior, viscosidade 0W20.

[PECAS]
ORIGINAL|0W20-HONDA|Oleo Sintetico 0W20 Honda Genuine|1L|58.00
SIMILAR|0W20-SHELL|Shell|Oleo Shell Helix Ultra 0W20|1L|48.00
SIMILAR|0W20-MOBIL|Mobil|Oleo Mobil 1 0W20|1L|52.00
SIMILAR|0W20-CASTROL|Castrol|Oleo Castrol Edge 0W20|1L|50.00
[/PECAS]'
        ],
        [
            'ALERTA: Motor usa Corrente - Nao Correia Dentada',
            0,
            '0',
            0.00,
            'Media',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

INFORMACAO IMPORTANTE: O motor R18Z 1.8 16V do HR-V utiliza CORRENTE de comando de valvulas, NAO correia dentada.

Isso significa:
- Nao ha troca periodica de correia dentada
- Corrente e projetada para durar a vida util do motor
- Menor custo de manutencao comparado a motores com correia
- A correia existente e apenas para ACESSORIOS (alternador, A/C, direcao)

Se a corrente apresentar ruido (barulho de corrente), pode indicar problema no tensor de corrente ou desgaste - procurar oficina especializada.

[PECAS]
[/PECAS]'
        ],
        [
            'ALERTA: CVT - Cuidados Especiais',
            0,
            '0',
            0.00,
            'Alta',
            '[CATEGORIA: Alerta] [TEMPO: 0 minutos]

ATENCAO com a transmissao CVT:

1. OLEO ESPECIFICO: Usar SOMENTE Honda ATF DW-1 ou equivalente certificado para CVT Honda. NUNCA usar ATF convencional de cambio automatico tradicional.

2. AQUECIMENTO: Deixar aquecer alguns minutos antes de exigir do veiculo.

3. NAO REBOCAR: Evitar rebocar outros veiculos - CVT nao e projetada para isso.

4. TRANCOS/SOLAVANCOS: Se sentir trancos ou solavancos, verificar nivel e qualidade do oleo CVT imediatamente.

5. REVISAO: Trocar oleo CVT a cada 80.000 km ou antes se uso severo.

Reparo de CVT e extremamente caro (R$ 8.000-15.000). Manutencao correta evita problemas.

[PECAS]
ORIGINAL|ATF-DW1|Oleo Transmissao CVT Honda ATF DW-1|1L|95.00
SIMILAR|CVTF-IDEM|Idemitsu|Oleo CVT Idemitsu|1L|68.00
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
        'motor' => 'R18Z 1.8 16V SOHC i-VTEC (140cv)',
        'planos_deletados' => $deletados,
        'planos_inseridos' => $inseridos,
        'total_itens' => count($itens_plano),
        'message' => "Plano de manutencao para {$modeloNome} importado com sucesso!",
        'caracteristicas' => [
            'corrente_comando' => 'Motor usa corrente (nao correia dentada)',
            'transmissao' => 'CVT - usar ATF DW-1',
            'oleo_motor' => '0W20 sintetico obrigatorio',
            'freios' => 'Disco nas 4 rodas'
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
