# 🐛 BUG CRÍTICO CORRIGIDO - Algoritmo de Clustering

**Data:** 2025-12-10 16:29
**Arquivo:** `cpanel-api/blocks-api.php`
**Status:** ✅ CORRIGIDO

---

## 📋 Descrição do Problema

O sistema estava criando blocos com pontos que ultrapassavam 5km de distância entre eles, violando a regra de proximidade máxima.

### Sintoma
- Blocos mostravam `maxPairDistanceKm > 5km` no console
- Locais dentro do mesmo bloco estavam muito distantes uns dos outros
- Ocorria mesmo após implementar validação na primeira passagem

---

## 🔍 Causa Raiz Identificada

O bug estava na **segunda passagem** do algoritmo (processamento de órfãos), nas linhas 520-554 do arquivo original.

### O que estava acontecendo:

```php
// ❌ CÓDIGO INCORRETO (ANTES)
foreach ($blocks as &$block) {
    // ...

    // PROBLEMA: Apenas verificava distância do órfão até o CENTRO do bloco
    $distance = haversineDistance(
        $block['centerLatitude'], $block['centerLongitude'],
        $orphan['latitude'], $orphan['longitude']
    );

    if ($distance <= $maxDistanceKm && $distance < $minDistance) {
        $minDistance = $distance;
        $bestBlock = &$block;
    }
}
```

### Exemplo prático do problema:

1. Bloco tem 3 pontos (A, B, C) agrupados no **canto norte** do raio de 5km
2. Centro geométrico do bloco fica no **meio**
3. Órfão D está a **4km do centro**, mas no **canto sul**
4. ❌ Algoritmo antigo: "4km ≤ 5km? SIM! Pode adicionar!"
5. ❌ **RESULTADO:** D está a **8km de A, B, C** → VIOLAÇÃO!

---

## ✅ Correção Implementada

Agora o algoritmo verifica se o órfão está próximo de **TODOS** os pontos do bloco, não apenas do centro:

```php
// ✅ CÓDIGO CORRETO (DEPOIS)
foreach ($blocks as &$block) {
    // ...

    // CORREÇÃO: Verificar distância do órfão para TODOS os pontos do bloco
    $canAddToBlock = true;
    $maxDistToAnyPoint = 0;

    foreach ($block['locations'] as $blockLoc) {
        $distance = haversineDistance(
            $blockLoc['latitude'], $blockLoc['longitude'],
            $orphan['latitude'], $orphan['longitude']
        );

        $maxDistToAnyPoint = max($maxDistToAnyPoint, $distance);

        // Se está longe demais de QUALQUER ponto, não pode adicionar
        if ($distance > $maxDistanceKm) {
            $canAddToBlock = false;
            break;
        }
    }

    // Só adiciona se passou na validação
    if ($canAddToBlock && $maxDistToAnyPoint < $minMaxDistance) {
        $minMaxDistance = $maxDistToAnyPoint;
        $bestBlock = &$block;
    }
}
```

---

## 📝 Mudanças Adicionais

### 1. Recálculo de `maxPairDistanceKm` após adicionar órfãos

Após adicionar um órfão a um bloco existente, recalculamos a distância máxima entre todos os pares:

```php
// Recalcular maxPairDistanceKm após adicionar órfão
$maxPairDistance = 0;
$blockLocs = $bestBlock['locations'];
for ($i = 0; $i < count($blockLocs); $i++) {
    for ($j = $i + 1; $j < count($blockLocs); $j++) {
        $pairDist = haversineDistance(
            $blockLocs[$i]['latitude'], $blockLocs[$i]['longitude'],
            $blockLocs[$j]['latitude'], $blockLocs[$j]['longitude']
        );
        $maxPairDistance = max($maxPairDistance, $pairDist);
    }
}
$bestBlock['maxPairDistanceKm'] = $maxPairDistance;
```

### 2. Blocos órfãos individuais

Blocos criados com apenas 1 local agora retornam `maxPairDistanceKm = 0`:

```php
$blocks[] = [
    // ...
    'radiusKm' => 0,
    'maxPairDistanceKm' => 0, // Bloco com apenas 1 local
    'locationsCount' => 1,
    // ...
];
```

---

## 🧪 Como Testar

1. **Deletar todos os blocos antigos:**
   - Clicar no botão "🗑️ Deletar Todos" no sistema

2. **Reimportar os dados:**
   - Fazer upload do arquivo Excel novamente
   - Aguardar processamento dos 3 batches (250 + 250 + 190 locais)

3. **Verificar distâncias no console:**
   - Abrir DevTools (F12)
   - Buscar por `📊 Distâncias máximas dos blocos:`
   - **TODOS os blocos devem mostrar ✅ OK**
   - **NENHUM bloco deve mostrar ❌ EXCEDE 5km!**

4. **Verificar lista de blocos:**
   - Todos os blocos na listagem devem mostrar distâncias em **verde**
   - Nenhum bloco deve ter texto vermelho com ⚠️

---

## 📂 Arquivos Modificados

- ✅ `cpanel-api/blocks-api.php` (linhas 520-591)
- ✅ `upload-cpanel/blocks-api.php` (cópia pronta para upload)

---

## 🚀 Próximos Passos

1. Fazer upload do arquivo `upload-cpanel/blocks-api.php` para o cPanel
2. Deletar todos os blocos antigos via interface
3. Reimportar os dados do Excel
4. Verificar se todos os blocos agora respeitam o limite de 5km
5. Testar geração de rotas com OSRM

---

## 📊 Resumo Técnico

| Item | Antes | Depois |
|------|-------|--------|
| **Validação na 1ª passagem** | ✅ Verifica todos os pontos | ✅ Mantido |
| **Validação na 2ª passagem** | ❌ Só verifica centro | ✅ Verifica todos os pontos |
| **Recálculo de distâncias** | ❌ Não recalcula | ✅ Recalcula após cada adição |
| **Órfãos individuais** | ⚠️ radiusKm = maxDistanceKm | ✅ radiusKm = 0, maxPairDistanceKm = 0 |
| **Garantia de proximidade** | ❌ Falha nos órfãos | ✅ 100% garantido |

---

**Desenvolvido por:** Claude Code
**Versão do algoritmo:** 2.0 (Corrigido)
