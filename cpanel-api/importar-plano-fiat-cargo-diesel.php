<?php
/**
 * Script de Importação - Plano de Manutenção Fiat Cargo 4.4 Diesel 2015
 * Motor: Iveco NEF 4.4 (F4AE)
 * Baseado em: Manual do fabricante + normas ABNT NBR 16369
 *
 * Acesso: https://floripa.in9automacao.com.br/importar-plano-fiat-cargo-diesel.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configuração do banco de dados
require_once 'config-db.php';

// Criar conexão
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

// Nome do modelo para o plano
$modelo = "Fiat Cargo 4.4 Diesel";

// Array com todos os itens do plano de manutenção
$itens_plano = [
    // ===== REVISÃO 20.000 KM / 12 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtro do Motor Diesel',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 130.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 45 minutos] Drenagem completa do óleo lubrificante do motor Iveco NEF 4.4 (F4AE) diesel. Especificação SAE 15W-40 API CJ-4 ou CK-4. Capacidade: 11-13 litros com filtro. Em uso severo/urbano, reduzir para 10.000 km. [PECAS] ORIGINAL|Verificar catálogo|Filtro de Óleo Motor Fiat Cargo Iveco NEF|1|125.00 SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|PSL283|Tecfil|Filtro Óleo Iveco NEF 4.4|1|40.00 SIMILAR|WOP1001|Mann|Filtro Óleo Cargo 815 915|1|45.00 SIMILAR|LF16015|Fleetguard|Filtro Óleo Iveco Original|1|48.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|15W40-MOBIL|Mobil|Óleo Delvac MX 15W-40 CJ-4|13L|395.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Filtro de Combustível Diesel',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 68.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Filtros] [TEMPO: 32 minutos] Substituição do filtro de combustível diesel do sistema de injeção direta. OBRIGATÓRIO drenar água do separador RACOR diariamente. Sangria do sistema após troca. [PECAS] ORIGINAL|51806073|Filtro Combustível Fiat Cargo Original|1|155.00 SIMILAR|PSC706|Tecfil|Filtro Diesel Cargo Separador de Água|1|52.00 SIMILAR|RC828|Wega|Filtro Combustível Fiat Cargo|1|48.00 SIMILAR|P555706|Mann|Filtro Diesel Iveco NEF 4.4|1|55.00 SIMILAR|FS19925|Fleetguard|Filtro Combustível Iveco Original|1|58.00 SIMILAR|RE120LJ10|Parker|Filtro Separador Cargo|1|62.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Filtro Separador de Combustível (Pré-Filtro)',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 45.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Filtros] [TEMPO: 25 minutos] Substituição do filtro separador (pré-filtro/filtro primário) tipo RACOR. Drenar água acumulada DIARIAMENTE. Verificar vedações e O-rings. [PECAS] ORIGINAL|Verificar catálogo|Filtro Separador Fiat Cargo RACOR|1|145.00 SIMILAR|PEC7177|Parker|Filtro Separador RACOR Iveco|1|58.00 SIMILAR|FS19925|Fleetguard|Separador Água/Combustível Iveco|1|62.00 SIMILAR|WK8158|Mann|Separador Combustível Cargo|1|65.00 SIMILAR|RC828P|Wega|Pré-Filtro Separador Cargo|1|55.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Filtro de Ar do Motor',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 35.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Filtros] [TEMPO: 15 minutos] Substituição do elemento filtrante de ar do motor Iveco NEF 4.4 diesel. Verificar indicador de restrição semanalmente. Em ambientes com poeira intensa, reduzir para 10.000 km. [PECAS] ORIGINAL|Verificar catálogo|Filtro Ar Motor Fiat Cargo Iveco NEF|1|185.00 SIMILAR|A1040|Tecfil|Filtro Ar Cargo Diesel|1|68.00 SIMILAR|C27160|Mann|Filtro Ar Motor Iveco 4.4|1|72.00 SIMILAR|AF27840|Fleetguard|Filtro Ar Iveco NEF|1|78.00 SIMILAR|CA11180|Fram|Filtro Ar Fiat Cargo|1|70.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Inspeção Sistema SCR e Arla 32 (se equipado Euro 5)',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 95.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Motor/Emissões] [TEMPO: 40 minutos] SOMENTE PARA MODELOS COM SCR: Inspeção do sistema de redução catalítica seletiva. Verificar nível/qualidade Arla 32 com refratômetro (32% ±2%), bomba dosadora, bico injetor, sensores NOx, catalisador. [PECAS] SIMILAR|ARLA-20L|Diversos|Arla 32 Certificado ISO 22241-1|20L|88.00 SIMILAR|REFRATOMETRO|Extech|Refratômetro Digital para Arla 32|1|155.00 SIMILAR|FITA-TESTE|Diversos|Fita Reagente Contaminação Arla|1|42.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Inspeção Geral de Segurança Veicular',
        'km_recomendado' => 20000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 195.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Geral] [TEMPO: 85 minutos] Inspeção conforme ABNT NBR 16369:2015: níveis de fluidos, luzes, buzina, limpadores, pneus (pressão/desgaste mín. 2,0mm), freios (pastilhas, lonas, discos, ABS), suspensão, direção, escapamento, bateria, extintor, triângulo. Documentar com checklist.'
    ],

    // ===== REVISÃO 40.000 KM / 24 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '24 meses',
        'custo_estimado' => 205.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 70 minutos] Serviço completo: óleo motor 15W-40 API CJ-4, filtros de óleo, combustível principal, combustível separador e ar. [PECAS] ORIGINAL|Verificar catálogo|Filtro de Óleo Motor Fiat Cargo Iveco NEF|1|125.00 ORIGINAL|51806073|Filtro Combustível Fiat Cargo Original|1|155.00 SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|PSL283|Tecfil|Filtro Óleo Iveco NEF 4.4|1|40.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|PSC706|Tecfil|Filtro Diesel Cargo Separador de Água|1|52.00 SIMILAR|A1040|Tecfil|Filtro Ar Cargo Diesel|1|68.00 SIMILAR|PEC7177|Parker|Filtro Separador RACOR Iveco|1|58.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Fluido de Freio DOT 4',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '24 meses',
        'custo_estimado' => 155.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 70 minutos] Drenagem completa e substituição do fluido DOT 4 (ponto de ebulição seco mín. 230°C). Capacidade aprox. 800ml. Sangria de todas as rodas e módulo ABS. CRÍTICO: A cada 2 anos independente da quilometragem. [PECAS] ORIGINAL|Verificar catálogo|Fluido de Freio DOT 4 Fiat Original|1L|95.00 SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Heavy Duty|1L|45.00 SIMILAR|DOT4-CASTROL|Castrol|Fluido Freio DOT 4 Response Heavy|1L|48.00 SIMILAR|DOT4-TRW|TRW|Fluido Freio DOT 4 Comercial|1L|40.00 SIMILAR|DOT5.1-ATE|ATE|Fluido Freio Super DOT 5.1|1L|58.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição de Pastilhas de Freio Dianteiras',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 195.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 90 minutos] Substituição das pastilhas dianteiras do sistema disco ventilado. Limpeza das pinças, lubrificação dos pinos-guia com graxa para altas temperaturas (800°C). Espessura mínima: 4mm (legislação veículos comerciais). [PECAS] ORIGINAL|Verificar catálogo|Jogo Pastilhas Freio Diant Fiat Cargo|1|465.00 SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Cargo|1|185.00 SIMILAR|N1425|Cobreq|Jogo Pastilhas Freio Diant Fiat|1|175.00 SIMILAR|PD1425|Fras-le|Jogo Pastilhas Freio Diant Cargo|1|182.00 SIMILAR|TRW1425|TRW|Jogo Pastilhas Freio Diant|1|188.00 SIMILAR|JJ1425|Jurid|Jogo Pastilhas Freio Diant Heavy Duty|1|195.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição de Lonas de Freio Traseiras',
        'km_recomendado' => 40000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 275.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 135 minutos] Substituição das lonas (sapatas) traseiras do sistema a tambor. Revisão: limpeza tambores, verificação cilindros de roda, molas de retorno, reguladores. Espessura mínima lonas: 3mm. Regulagem freio estacionamento. [PECAS] ORIGINAL|Verificar catálogo|Jogo Lonas Freio Traseiro Fiat Cargo|1|525.00 SIMILAR|HI2240|Fras-le|Jogo Lonas Freio Traseiro Cargo|1|205.00 SIMILAR|N2240|Cobreq|Jogo Lonas Freio Traseiro Fiat|1|195.00 SIMILAR|TRW2240|TRW|Jogo Lonas Freio Traseiro|1|208.00 SIMILAR|JJ2240|Jurid|Jogo Lonas Freio Traseiro Heavy Duty|1|215.00 [/PECAS]'
    ],

    // ===== REVISÃO 60.000 KM / 36 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '36 meses',
        'custo_estimado' => 205.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 70 minutos] Serviço completo conforme especificações anteriores: óleo motor 15W-40 API CJ-4, filtros de óleo, combustível e ar. [PECAS] ORIGINAL|Verificar catálogo|Filtro de Óleo Motor Fiat Cargo Iveco NEF|1|125.00 ORIGINAL|51806073|Filtro Combustível Fiat Cargo Original|1|155.00 SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|PSC706|Tecfil|Filtro Diesel Cargo Separador de Água|1|52.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Fluido do Sistema de Arrefecimento',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '36 meses',
        'custo_estimado' => 165.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Motor] [TEMPO: 90 minutos] Drenagem e substituição do fluido de arrefecimento (anticongelante 50/50 com água desmineralizada). Capacidade total: 21-24 litros. Drenagem pelos bujões do radiador e bloco, lavagem, reabastecimento, sangria, teste. [PECAS] ORIGINAL|Verificar catálogo|Anticongelante Fiat Diesel Heavy Duty|12L|325.00 ORIGINAL|AGUA-DESM|Água Desmineralizada Galão|12L|48.00 SIMILAR|HEAVY-DUTY|Shell|Anticongelante Diesel Heavy Duty|12L|195.00 SIMILAR|COOLANT-HD|Castrol|Anticongelante Radicool HD Diesel|12L|205.00 SIMILAR|RAD-HD|Valvoline|Anticongelante Heavy Duty Diesel|12L|185.00 [/PECAS]'
    ],

    // ===== REVISÃO 80.000 KM / 48 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 80000,
        'intervalo_tempo' => '48 meses',
        'custo_estimado' => 205.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 70 minutos] Serviço completo: óleo motor 15W-40 API CJ-4, filtros de óleo, combustível e ar. [PECAS] ORIGINAL|Verificar catálogo|Filtro de Óleo Motor Fiat Cargo Iveco NEF|1|125.00 SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição de Discos e Pastilhas de Freio Dianteiros',
        'km_recomendado' => 80000,
        'intervalo_tempo' => '60 meses',
        'custo_estimado' => 265.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Freios] [TEMPO: 145 minutos] Substituição completa: jogo de pastilhas + par de discos ventilados. Limpeza das pinças, lubrificação pinos, verificação pistões. Discos SEMPRE em par. Sangria completa. Teste com carga. [PECAS] ORIGINAL|Verificar catálogo|Jogo Pastilhas Freio Diant Fiat Cargo|1|465.00 ORIGINAL|Verificar catálogo|Par Discos Freio Diant Fiat Cargo|2|1085.00 SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Cargo|1|185.00 SIMILAR|DF2425|Fremax|Par Discos Freio Cargo Ventilado|2|625.00 SIMILAR|RC2425|Cobreq|Par Discos Freio Diant Fiat|2|605.00 SIMILAR|TRW2425|TRW|Par Discos Freio Diant Heavy Duty|2|645.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição de Amortecedores Dianteiros e Traseiros',
        'km_recomendado' => 80000,
        'intervalo_tempo' => '60 meses',
        'custo_estimado' => 425.00,
        'criticidade' => 'Alta',
        'descricao_observacao' => '[CATEGORIA: Suspensão] [TEMPO: 205 minutos] Substituição do conjunto de amortecedores dianteiros e traseiros incluindo buchas e batentes. Código Cofap L12591 para Cargo 712/814/815/915. Suspensão com feixes de molas. Verificar molas e grampos. [PECAS] ORIGINAL|Verificar catálogo|Amortecedor Dianteiro Fiat Cargo|2|1385.00 ORIGINAL|Verificar catálogo|Amortecedor Traseiro Fiat Cargo|2|1285.00 SIMILAR|L12591|Cofap|Par Amortecedor Diant Cargo 712/814/815/915|2|745.00 SIMILAR|L12592|Cofap|Par Amortecedor Tras Cargo 712/814/815/915|2|705.00 SIMILAR|HG35120|Monroe|Amortecedor Diant Cargo Gas HD|2|685.00 SIMILAR|HG35121|Monroe|Amortecedor Tras Cargo Gas HD|2|645.00 [/PECAS]'
    ],

    // ===== REVISÃO 100.000 KM / 60 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 100000,
        'intervalo_tempo' => '60 meses',
        'custo_estimado' => 205.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 70 minutos] Serviço completo: óleo motor 15W-40 API CJ-4, filtros de óleo, combustível e ar. [PECAS] ORIGINAL|Verificar catálogo|Filtro de Óleo Motor Fiat Cargo Iveco NEF|1|125.00 SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|PSC706|Tecfil|Filtro Diesel Cargo|1|52.00 [/PECAS]'
    ],

    // ===== REVISÃO 120.000 KM / 72 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 120000,
        'intervalo_tempo' => '72 meses',
        'custo_estimado' => 205.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 70 minutos] Serviço completo: óleo motor 15W-40 API CJ-4, filtros de óleo, combustível e ar. [PECAS] ORIGINAL|Verificar catálogo|Filtro de Óleo Motor Fiat Cargo Iveco NEF|1|125.00 SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 [/PECAS]'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Substituição Obrigatória do Filtro de Arla 32 (se equipado SCR)',
        'km_recomendado' => 120000,
        'intervalo_tempo' => '12 meses',
        'custo_estimado' => 255.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Emissões] [TEMPO: 80 minutos] SOMENTE MODELOS COM SCR: Troca do filtro de Arla 32 a cada 120.000 km ou 12 meses. Drenagem e limpeza do tanque, verificação da bomba dosadora, bico injetor, tubulações. Usar refratômetro para medir teor (32% ±2%). Verificar contaminação com fita reagente. [PECAS] ORIGINAL|Verificar catálogo|Filtro Arla 32 Fiat Cargo Original|1|385.00 SIMILAR|FILTRO-ARLA|Mann|Filtro Sistema SCR Arla 32|1|195.00 SIMILAR|LIMPA-SCR|Wynns|Limpador Sistema SCR Arla 32|500ML|135.00 SIMILAR|ARLA-PREMIUM|Petrobras|Arla 32 Premium ISO 22241-1|20L|88.00 [/PECAS]'
    ],

    // ===== REVISÃO 140.000 KM / 84 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos',
        'km_recomendado' => 140000,
        'intervalo_tempo' => '84 meses',
        'custo_estimado' => 205.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Filtros] [TEMPO: 70 minutos] Serviço completo: óleo motor 15W-40 API CJ-4, filtros de óleo, combustível e ar. [PECAS] SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|PSC706|Tecfil|Filtro Diesel Cargo|1|52.00 SIMILAR|A1040|Tecfil|Filtro Ar Cargo Diesel|1|68.00 [/PECAS]'
    ],

    // ===== REVISÃO 160.000 KM / 96 MESES =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros Completos + Revisão de Freios',
        'km_recomendado' => 160000,
        'intervalo_tempo' => '96 meses',
        'custo_estimado' => 470.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Freios] [TEMPO: 160 minutos] Serviço completo de óleo e filtros + substituição de pastilhas e lonas de freio. Revisão completa do sistema de freios. [PECAS] SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Cargo|1|185.00 SIMILAR|HI2240|Fras-le|Jogo Lonas Freio Traseiro Cargo|1|205.00 SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Heavy Duty|1L|45.00 [/PECAS]'
    ],

    // ===== ITENS ESPECIAIS =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Drenagem Diária do Separador de Água - CRÍTICO',
        'km_recomendado' => 500,
        'intervalo_tempo' => 'Diário',
        'custo_estimado' => 0.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Filtros] [TEMPO: 5 minutos diários] MANUTENÇÃO DIÁRIA OBRIGATÓRIA: Drenar água do filtro RACOR TODO DIA DE MANHÃ. Abrir válvula de dreno no fundo do copo separador até sair apenas diesel limpo. ATENÇÃO: Filtro RACOR só MOSTRA a água, NÃO retira - você deve drenar manualmente. Água no diesel causa desgaste prematuro da bomba injetora Bosch (R$ 7.500 a R$ 12.000).'
    ],
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Verificação e Substituição de Pneus',
        'km_recomendado' => 60000,
        'intervalo_tempo' => '60 meses',
        'custo_estimado' => 155.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Pneus] [TEMPO: 110 minutos jogo completo] Pneus 215/75 R17.5 ou 7.50 R16 conforme modelo. Vida útil: 60.000-90.000 km ou 5 anos. VERIFICAÇÃO DIÁRIA: pressão conforme tabela da porta, desgaste mínimo legal 2,0mm para caminhões, cortes, bolhas, objetos. Rodízio a cada 20.000 km. Calibragem incorreta é causa #1 de estouro. [PECAS] SIMILAR|215/75R17.5|Firestone|Pneu FS400 215/75 R17.5 Carga|6|3480.00 SIMILAR|215/75R17.5|Bridgestone|Pneu R268 215/75 R17.5|6|3580.00 SIMILAR|7.50R16|Pirelli|Pneu FG88 7.50 R16 Carga|6|2980.00 SIMILAR|7.50R16|Goodyear|Pneu G90 7.50 R16|6|2880.00 [/PECAS]'
    ],

    // ===== REVISÃO 180.000 KM =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Troca de Óleo e Filtros + Amortecedores',
        'km_recomendado' => 180000,
        'intervalo_tempo' => '108 meses',
        'custo_estimado' => 630.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Motor/Suspensão] [TEMPO: 275 minutos] Serviço completo de óleo e filtros + segunda substituição de amortecedores (vida útil 80.000-100.000 km). [PECAS] SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|L12591|Cofap|Par Amortecedor Diant Cargo|2|745.00 SIMILAR|L12592|Cofap|Par Amortecedor Tras Cargo|2|705.00 [/PECAS]'
    ],

    // ===== REVISÃO 200.000 KM =====
    [
        'modelo_carro' => $modelo,
        'descricao_titulo' => 'Revisão Completa 200.000 km - Motor/Freios/Suspensão',
        'km_recomendado' => 200000,
        'intervalo_tempo' => '120 meses',
        'custo_estimado' => 850.00,
        'criticidade' => 'Crítica',
        'descricao_observacao' => '[CATEGORIA: Geral] [TEMPO: 360 minutos] Revisão completa de 200.000 km: óleo e filtros, pastilhas e lonas de freio, discos dianteiros, fluido de freio, arrefecimento. Avaliação completa do motor, transmissão, embreagem e diferencial. [PECAS] SIMILAR|WO612|Wega|Filtro Óleo Fiat Cargo Diesel|1|42.00 SIMILAR|15W40-SHELL|Shell|Óleo Rimula R4 X 15W-40 CJ-4 Diesel|13L|405.00 SIMILAR|SYL1425|Bosch|Jogo Pastilhas Freio Diant Cargo|1|185.00 SIMILAR|HI2240|Fras-le|Jogo Lonas Freio Traseiro Cargo|1|205.00 SIMILAR|DF2425|Fremax|Par Discos Freio Cargo Ventilado|2|625.00 SIMILAR|DOT4-BOSCH|Bosch|Fluido Freio DOT 4 Heavy Duty|1L|45.00 SIMILAR|HEAVY-DUTY|Shell|Anticongelante Diesel Heavy Duty|12L|195.00 [/PECAS]'
    ]
];

// Preparar statement para inserção
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

// Resultado final
echo json_encode([
    'success' => count($erros) === 0,
    'modelo' => $modelo,
    'total_itens' => count($itens_plano),
    'inseridos' => $inseridos,
    'erros' => $erros,
    'mensagem' => $inseridos > 0
        ? "✅ Plano de manutenção do {$modelo} importado com sucesso! {$inseridos} itens cadastrados."
        : "❌ Nenhum item foi inserido. Verifique os erros.",
    'observacoes' => [
        'CRÍTICO: Drenar água do separador RACOR DIARIAMENTE',
        'Óleo: SAE 15W-40 API CJ-4 ou CK-4 - Capacidade 11-13L',
        'Uso severo/urbano: reduzir intervalo de óleo para 10.000 km',
        'Sistema SCR (se equipado): Arla 32 certificado ISO 22241-1',
        'Pneus: verificação DIÁRIA obrigatória (pressão/desgaste)'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
