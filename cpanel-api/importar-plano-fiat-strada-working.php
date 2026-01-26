<?php
/**
 * Script de Importação - Plano de Manutenção Fiat Strada 1.4 Working 2014-2015
 * Motor: Fire Evo 1.4 8V Flex (85cv)
 * Capacidade óleo: 3,0 litros
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-fiat-strada-working.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config-db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

try {
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'error' => 'Erro de conexão: ' . $e->getMessage()
    ]));
}

$modelo = "Fiat Strada 1.4 Working";

$itens_plano = [
    // ===== REVISÃO 10.000 KM / 12 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtro do Motor',
        'km_recomendado' => 10000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 85.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 35 minutos] Drenagem completa do óleo do motor Fire Evo 1.4 8V Flex. Capacidade: apenas 3 litros com filtro. Óleo recomendado: 5W-30 sintético API SN ou superior. Código filtro original: 55238304. SEMPRE trocar óleo E filtro juntos. Uso severo: reduzir para 5.000 km. [PECAS] ORIGINAL|55238304|Filtro de Óleo Motor Fiat Strada 1.4|1|75.00 SIMILAR|PSL318|Tecfil|Filtro Óleo Strada 1.4 Fire|1|28.00 SIMILAR|W7008|Mann|Filtro Óleo Fiat 1.4 8V|1|30.00 SIMILAR|PH5548A|Fram|Filtro Óleo Strada 1.4|1|32.00 SIMILAR|0986452041|Bosch|Filtro Óleo Fire 1.4|1|35.00 SIMILAR|5W30-SHELL|Shell|Óleo Helix HX8 5W-30 Sintético|3L|125.00 SIMILAR|5W30-MOBIL|Mobil|Óleo Super 3000 5W-30 Sintético|3L|115.00 SIMILAR|5W30-CASTROL|Castrol|Óleo Edge 5W-30 Sintético|3L|130.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Filtro de Ar do Motor',
        'km_recomendado' => 10000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 25.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Filtros] [TEMPO: 12 minutos] Substituição do filtro de ar do motor Fire Evo 1.4. Código original: 46420988. Verificar estado, limpar caixa de ar. Em uso severo (poeira), reduzir para 5.000 km. [PECAS] ORIGINAL|46420988|Filtro Ar Motor Fiat Strada 1.4 Original|1|125.00 SIMILAR|ARL4150|Tecfil|Filtro Ar Strada 1.4 8V|1|40.00 SIMILAR|HLP4150|Vox|Filtro Ar Fiat Strada 2014 2015|1|38.00 SIMILAR|FAP-2831|Wega|Filtro Ar Strada Fire 1.4|1|42.00 SIMILAR|CA5627|Fram|Filtro Ar Fiat 1.4|1|45.00 SIMILAR|C2583|Mann|Filtro Ar Strada 1.4|1|48.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Filtro de Combustível',
        'km_recomendado' => 10000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 42.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Filtros] [TEMPO: 22 minutos] Substituição do filtro de combustível. Motor Flex sensível a combustível de baixa qualidade. Despressurizar sistema antes da remoção. [PECAS] ORIGINAL|Verificar catálogo|Filtro Combustível Fiat Strada Original|1|85.00 SIMILAR|PSC142|Tecfil|Filtro Combustível Strada 1.4 Flex|1|28.00 SIMILAR|FCI1110S|Mann|Filtro Combustível Fiat 2003-2015|1|32.00 SIMILAR|GI04/8|Wega|Filtro Combustível Strada Fire|1|30.00 SIMILAR|G10590|Fram|Filtro Combustível Fiat 1.4|1|33.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Inspeção Geral de Segurança',
        'km_recomendado' => 10000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 95.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Geral] [TEMPO: 55 minutos] Inspeção completa: níveis de fluidos, luzes, buzina, pneus (pressão/desgaste mín. 1,6mm), freios, suspensão, direção, bateria, correias, velas. ATENÇÃO URGENTE: Verificar recall dos airbags Takata (modelos 2014-2016). Risco de ruptura do airbag dispersando fragmentos metálicos com danos fatais.'
    ],

    // ===== REVISÃO 20.000 KM / 24 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '24 meses',
        'custo_estimado' => 110.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos] Serviço completo: óleo motor 5W-30 (3L), filtros de óleo, ar e combustível. [PECAS] ORIGINAL|55238304|Filtro de Óleo Motor Fiat Strada 1.4|1|75.00 SIMILAR|PSL318|Tecfil|Filtro Óleo Strada 1.4 Fire|1|28.00 SIMILAR|5W30-SHELL|Shell|Óleo Helix HX8 5W-30 Sintético|3L|125.00 SIMILAR|ARL4150|Tecfil|Filtro Ar Strada 1.4 8V|1|40.00 SIMILAR|PSC142|Tecfil|Filtro Combustível Strada 1.4 Flex|1|28.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Velas de Ignição',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '24 meses',
        'custo_estimado' => 75.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 35 minutos] Substituição das 4 velas de ignição. Motor 8V possui 4 velas. Verificar folga (1,0mm). Torque: 20 Nm. Motor Flex desgasta velas mais rápido que gasolina pura. [PECAS] ORIGINAL|Verificar catálogo|Jogo Velas Ignição Fiat Strada 1.4 Original|4|185.00 SIMILAR|BKR6E|NGK|Vela Ignição Fiat Fire 1.4 Flex|4|95.00 SIMILAR|FR7LDC|Bosch|Vela Ignição Strada 1.4 8V|4|105.00 SIMILAR|IXUH22|Denso|Vela Iridium Fire 1.4|4|165.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Fluido de Freio DOT 4',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '24 meses',
        'custo_estimado' => 125.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 55 minutos] Drenagem e substituição do fluido DOT 4. Sangria de todas as rodas. Capacidade: 500ml. CRÍTICO: A cada 2 anos independente da quilometragem. [PECAS] ORIGINAL|Verificar catálogo|Fluido de Freio DOT 4 Fiat Original|500ML|52.00 SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|25.00 SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response|500ML|28.00 SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|500ML|23.00 SIMILAR|DOT4-ATE|ATE|Fluido Freio Super DOT 4|500ML|32.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição de Pastilhas de Freio Dianteiras',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '30 meses',
        'custo_estimado' => 145.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 75 minutos] Substituição das pastilhas dianteiras. Código Fras-le PD-2226. Limpeza das pinças, lubrificação dos pinos-guia. Espessura mínima: 2mm. Medição dos discos. [PECAS] ORIGINAL|Verificar catálogo|Jogo Pastilhas Freio Diant Fiat Strada|1|285.00 SIMILAR|PD-2226|Fras-le|Jogo Pastilhas Freio Strada 1.4 Diant|1|95.00 SIMILAR|PD-1482|Fras-le|Jogo Pastilhas Freio Strada 1.4 1.6 Diant|1|95.00 SIMILAR|N1425|Cobreq|Jogo Pastilhas Freio Diant Fiat|1|88.00 SIMILAR|HI1425|Jurid|Jogo Pastilhas Freio Diant Strada|1|105.00 SIMILAR|TRW1425|TRW|Jogo Pastilhas Freio Diant|1|98.00 [/PECAS]'
    ],

    // ===== REVISÃO 30.000 KM / 36 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 30000,
        'intervalo_tempo' => '36 meses',
        'custo_estimado' => 110.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos] Serviço completo: óleo 5W-30 (3L), filtros de óleo, ar e combustível. [PECAS] SIMILAR|PSL318|Tecfil|Filtro Óleo Strada 1.4 Fire|1|28.00 SIMILAR|5W30-SHELL|Shell|Óleo Helix HX8 5W-30 Sintético|3L|125.00 SIMILAR|ARL4150|Tecfil|Filtro Ar Strada 1.4 8V|1|40.00 SIMILAR|PSC142|Tecfil|Filtro Combustível Strada 1.4|1|28.00 [/PECAS]'
    ],

    // ===== REVISÃO 40.000 KM / 48 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 110.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos] Serviço completo conforme especificações anteriores. [PECAS] ORIGINAL|55238304|Filtro de Óleo Motor Fiat Strada 1.4|1|75.00 SIMILAR|PSL318|Tecfil|Filtro Óleo Strada 1.4 Fire|1|28.00 SIMILAR|5W30-SHELL|Shell|Óleo Helix HX8 5W-30 Sintético|3L|125.00 SIMILAR|ARL4150|Tecfil|Filtro Ar Strada 1.4 8V|1|40.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Correia Dentada e Tensor',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 385.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 145 minutos] Substituição da correia dentada, correia do alternador e tensor. CRÍTICO: Trocar aos 40.000 km ou 4 anos. Verificar bomba d\'água. Correia rompida causa motor TOP - pistão bate nas válvulas, danos catastróficos (R$ 7.000 a R$ 12.000). [PECAS] ORIGINAL|Verificar catálogo|Kit Correia Dentada Fiat Strada 1.4 Original|1|685.00 SIMILAR|K015607XS|Gates|Kit Correia Dentada Fire 1.4 8V|1|285.00 SIMILAR|CT1126K1|Dayco|Kit Correia Dentada Strada 1.4|1|295.00 SIMILAR|TB327K1|Continental|Kit Correia Dentada Fiat Fire|1|305.00 SIMILAR|5PK935|Gates|Correia Poly-V Alternador|1|45.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Fluido de Arrefecimento',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 135.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 65 minutos] Drenagem e substituição do fluido de arrefecimento. Diluição 50/50 anticongelante + água desmineralizada. Capacidade: ~5 litros. Sangria do sistema. A cada 2 anos ou 40.000 km. [PECAS] ORIGINAL|Verificar catálogo|Anticongelante Fiat Original Paraflu|3L|165.00 ORIGINAL|AGUA-DESM|Água Desmineralizada|3L|18.00 SIMILAR|PARAFLU-UP|Shell|Anticongelante Paraflu UP Universal|3L|85.00 SIMILAR|COOLANT|Castrol|Anticongelante Radicool|3L|92.00 SIMILAR|RADIEX|Valvoline|Anticongelante Universal|3L|78.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Velas de Ignição',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 75.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 35 minutos] Substituição das 4 velas de ignição. Motor Flex desgasta velas mais rápido. [PECAS] SIMILAR|BKR6E|NGK|Vela Ignição Fiat Fire 1.4 Flex|4|95.00 SIMILAR|FR7LDC|Bosch|Vela Ignição Strada 1.4 8V|4|105.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Fluido de Freio DOT 4',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 125.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 55 minutos] Drenagem e substituição do fluido DOT 4. A cada 2 anos independente da quilometragem. [PECAS] SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|25.00 SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|500ML|23.00 [/PECAS]'
    ],

    // ===== REVISÃO 50.000 KM / 60 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 50000,
        'intervalo_tempo' => '60 meses',
        'custo_estimado' => 110.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos] Serviço completo: óleo 5W-30 (3L), filtros. [PECAS] SIMILAR|PSL318|Tecfil|Filtro Óleo Strada 1.4 Fire|1|28.00 SIMILAR|5W30-SHELL|Shell|Óleo Helix HX8 5W-30 Sintético|3L|125.00 SIMILAR|ARL4150|Tecfil|Filtro Ar Strada 1.4 8V|1|40.00 SIMILAR|PSC142|Tecfil|Filtro Combustível Strada 1.4|1|28.00 [/PECAS]'
    ],

    // ===== REVISÃO 60.000 KM / 72 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '72 meses',
        'custo_estimado' => 110.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 50 minutos] Serviço completo conforme especificações anteriores. [PECAS] ORIGINAL|55238304|Filtro de Óleo Motor Fiat Strada 1.4|1|75.00 SIMILAR|PSL318|Tecfil|Filtro Óleo Strada 1.4 Fire|1|28.00 SIMILAR|5W30-SHELL|Shell|Óleo Helix HX8 5W-30 Sintético|3L|125.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição de Discos e Pastilhas de Freio Dianteiros',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '72 meses',
        'custo_estimado' => 225.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 115 minutos] Substituição completa: pastilhas + par de discos dianteiros. Limpeza das pinças, lubrificação. Discos sempre em par. Sangria completa. [PECAS] ORIGINAL|Verificar catálogo|Jogo Pastilhas Freio Diant Fiat Strada|1|285.00 ORIGINAL|Verificar catálogo|Par Discos Freio Diant Fiat Strada 1.4|2|685.00 SIMILAR|PD-2226|Fras-le|Jogo Pastilhas Freio Strada Diant|1|95.00 SIMILAR|DF2425|Fremax|Par Discos Freio Strada Ventilado|2|385.00 SIMILAR|RC2425|Cobreq|Par Discos Freio Diant Fiat|2|365.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição de Amortecedores Dianteiros e Traseiros',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '72 meses',
        'custo_estimado' => 365.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Suspensão] [TEMPO: 165 minutos] Substituição dos amortecedores dianteiros e traseiros com buchas e batentes. Suspensão dianteira McPherson, traseira eixo de torção. [PECAS] ORIGINAL|Verificar catálogo|Amortecedor Dianteiro Fiat Strada|2|885.00 ORIGINAL|Verificar catálogo|Amortecedor Traseiro Fiat Strada|2|785.00 SIMILAR|HG33120|Monroe|Amortecedor Diant Strada Gas|2|485.00 SIMILAR|HG33121|Monroe|Amortecedor Tras Strada Gas|2|445.00 SIMILAR|AM33120|Cofap|Amortecedor Diant Fiat Strada|2|425.00 SIMILAR|AM33121|Cofap|Amortecedor Tras Fiat Strada|2|385.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Fluido de Freio DOT 4',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '72 meses',
        'custo_estimado' => 125.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 55 minutos] Drenagem e substituição do fluido DOT 4. [PECAS] SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|25.00 SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4|500ML|23.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Velas de Ignição',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '72 meses',
        'custo_estimado' => 75.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 35 minutos] Substituição das 4 velas de ignição. [PECAS] SIMILAR|BKR6E|NGK|Vela Ignição Fiat Fire 1.4 Flex|4|95.00 SIMILAR|FR7LDC|Bosch|Vela Ignição Strada 1.4 8V|4|105.00 [/PECAS]'
    ],

    // ===== REVISÃO 80.000 KM / 96 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Correia Dentada e Tensor (Segunda Troca)',
        'km_recomendado' => 80000,
        'intervalo_tempo' => '96 meses',
        'custo_estimado' => 385.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 145 minutos] Segunda substituição da correia dentada aos 80.000 km. Verificar bomba d\'água. CRÍTICO: Rompimento causa danos catastróficos ao motor. [PECAS] SIMILAR|K015607XS|Gates|Kit Correia Dentada Fire 1.4 8V|1|285.00 SIMILAR|CT1126K1|Dayco|Kit Correia Dentada Strada 1.4|1|295.00 SIMILAR|5PK935|Gates|Correia Poly-V Alternador|1|45.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Fluido de Arrefecimento',
        'km_recomendado' => 80000,
        'intervalo_tempo' => '96 meses',
        'custo_estimado' => 135.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 65 minutos] Segunda troca do fluido de arrefecimento. [PECAS] SIMILAR|PARAFLU-UP|Shell|Anticongelante Paraflu UP Universal|3L|85.00 SIMILAR|RADIEX|Valvoline|Anticongelante Universal|3L|78.00 ORIGINAL|AGUA-DESM|Água Desmineralizada|3L|18.00 [/PECAS]'
    ],

    // ===== REVISÃO 100.000 KM / 120 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Revisão Completa 100.000 km',
        'km_recomendado' => 100000,
        'intervalo_tempo' => '120 meses',
        'custo_estimado' => 650.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Geral] [TEMPO: 280 minutos] Revisão completa: óleo e filtros, velas, fluido de freio, pastilhas (se necessário), revisão completa de suspensão, verificação de embreagem, revisão geral. [PECAS] SIMILAR|PSL318|Tecfil|Filtro Óleo Strada 1.4 Fire|1|28.00 SIMILAR|5W30-SHELL|Shell|Óleo Helix HX8 5W-30 Sintético|3L|125.00 SIMILAR|ARL4150|Tecfil|Filtro Ar Strada 1.4 8V|1|40.00 SIMILAR|PSC142|Tecfil|Filtro Combustível Strada 1.4|1|28.00 SIMILAR|BKR6E|NGK|Vela Ignição Fiat Fire 1.4 Flex|4|95.00 SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4|500ML|25.00 [/PECAS]'
    ],

    // ===== ITEM ESPECIAL - RECALL =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => '⚠️ RECALL AIRBAG TAKATA - VERIFICAR URGENTE',
        'km_recomendado' => 0,
        'intervalo_tempo' => 'Imediato',
        'custo_estimado' => 0.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Recall/Segurança] [TEMPO: Verificação] URGENTÍSSIMO: Fiat convocou modelos 2014-2016 para recall dos airbags Takata. RISCO DE MORTE: Em colisão, airbag pode romper dispersando fragmentos metálicos causando danos físicos graves ou fatais. Verificar IMEDIATAMENTE no site servicos.fiat.com.br/recall.html ou 0800-707-1000. SERVIÇO GRATUITO.'
    ]
];

$stmt = $conn->prepare(
    "INSERT INTO Planos_Manutenção
    (modelo_carro, descricao_titulo, km_recomendado, intervalo_tempo, custo_estimado, criticidade, descricao_observacao)
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$inseridos = 0;
$erros = [];

foreach ($itens_plano as $index => $item) {
    try {
        $stmt->bind_param(
            "ssissss",
            $item['modelo_carro'],
            $item['descricao_titulo'],
            $item['km_recomendado'],
            $item['intervalo_tempo'],
            $item['custo_estimado'],
            $item['criticidade'],
            $item['descricao_observacao']
        );

        if ($stmt->execute()) {
            $inseridos++;
        } else {
            $erros[] = "Item {$index}: " . $stmt->error;
        }
    } catch (Exception $e) {
        $erros[] = "Item {$index} ({$item['descricao_titulo']}): " . $e->getMessage();
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => count($erros) === 0,
    'modelo' => $modelo,
    'total_itens' => count($itens_plano),
    'inseridos' => $inseridos,
    'erros' => $erros,
    'mensagem' => $inseridos > 0
        ? "✅ Plano de manutenção do {$modelo} importado! {$inseridos} itens cadastrados."
        : "❌ Nenhum item inserido.",
    'observacoes' => [
        '⚠️ RECALL AIRBAG TAKATA - VERIFICAR URGENTE (modelos 2014-2016)',
        'Óleo: 5W-30 sintético API SN - Capacidade: apenas 3 litros',
        'SEMPRE trocar óleo E filtro juntos',
        'Uso severo: reduzir intervalos pela metade',
        'Correia dentada CRÍTICA aos 40.000 km - rompimento causa motor TOP',
        'Motor Flex: mais sensível a combustível de baixa qualidade'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
