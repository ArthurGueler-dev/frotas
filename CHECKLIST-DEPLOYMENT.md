# ✅ Checklist de Deployment - FleetFlow

## 🎯 Objetivo
Garantir que todas as otimizações sejam aplicadas corretamente e na ordem certa.

---

## 📋 PASSO 1: Executar Migração SQL

### 1.1. Limpar coluna antiga (se existir)
1. Abra phpMyAdmin
2. Selecione o banco `f137049_in9aut`
3. Clique na aba "SQL"
4. Cole e execute:
```sql
ALTER TABLE ordemservico DROP COLUMN IF EXISTS seq_number;
```
**Se der erro "syntax error"**, execute apenas:
```sql
ALTER TABLE ordemservico DROP COLUMN seq_number;
```

### 1.2. Criar coluna seq_number
Cole e execute todo o conteúdo de `migrations/add_seq_number_simple.sql`:

```sql
-- Passo 1: Adicionar coluna seq_number (sem AUTO_INCREMENT)
ALTER TABLE ordemservico
ADD COLUMN seq_number INT NULL AFTER id;

-- Passo 2: Criar índice UNIQUE para evitar duplicatas
ALTER TABLE ordemservico
ADD UNIQUE INDEX idx_seq_number (seq_number);

-- Passo 3: Preencher valores existentes sequencialmente
SET @seq = 0;
UPDATE ordemservico
SET seq_number = (@seq := @seq + 1)
ORDER BY id ASC;

-- Passo 4: Modificar para NOT NULL agora que está preenchido
ALTER TABLE ordemservico
MODIFY COLUMN seq_number INT NOT NULL;

-- Passo 5: Verificar estrutura da tabela
DESCRIBE ordemservico;

-- Passo 6: Verificar registros atualizados
SELECT id, ordem_numero, seq_number
FROM ordemservico
ORDER BY id DESC
LIMIT 10;
```

### 1.3. Verificar Resultado
Você deve ver a coluna `seq_number` na estrutura da tabela e valores preenchidos nos registros.

---

## 📦 PASSO 2: Upload dos Arquivos

Faça upload dos seguintes arquivos para o cPanel (pasta `/public_html` ou `/frotas`):

### Arquivos PHP (obrigatórios):
- [ ] `save-workorder.php`
- [ ] `get-next-os-number.php`
- [ ] `get-workorders.php`
- [ ] `db-config.php`

### Arquivo HTML (CRÍTICO):
- [ ] `manutencao.html` ⚠️ **IMPORTANTE!**

### Arquivo de Teste (opcional):
- [ ] `test-seq-number.php`

---

## 🧪 PASSO 3: Testar Migração SQL

1. Abra no navegador: `https://floripa.in9automacao.com.br/test-seq-number.php`
2. Verifique a resposta JSON:

**✅ Resposta Esperada (CORRETO):**
```json
{
  "success": true,
  "has_seq_number_column": true,
  "table_structure": [...],
  "last_5_orders": [
    {
      "id": "15",
      "ordem_numero": "OS-2025-00015",
      "seq_number": "15"
    },
    ...
  ]
}
```

**❌ Resposta Incorreta:**
- `has_seq_number_column: false` = Migração SQL não foi executada
- `seq_number: null` = Passo 3 da migração falhou

---

## 🧪 PASSO 4: Testar Página de Manutenção

1. Limpe o cache do navegador (Ctrl+Shift+Del)
2. Abra: `https://floripa.in9automacao.com.br/manutencao.html`
3. Abra o Console do navegador (F12)

**✅ Console Esperado (CORRETO):**
```
🚀 Inicializando página de gestão de manutenção...
📡 Response status: 200 true
📦 Dados da API: {success: true, data: Array(13), ...}
✅ 13 OS carregadas da API
```

**❌ Console Incorreto:**
```
❌ IturanService inicializado em: Browser
❌ ✅ Modelos de veículos carregados: 81 veículos
```
→ O arquivo `manutencao.html` não foi atualizado no servidor

---

## 🧪 PASSO 5: Testar Criação de OS

1. Abra: `https://floripa.in9automacao.com.br/lancar-os.html`
2. Preencha o formulário
3. Clique em "Criar OS"
4. Cronometre o tempo

**✅ Resultado Esperado:**
- ⏱️ Tempo: **2-5 segundos** (antes eram ~20s)
- ✅ OS criada sem duplicatas
- ✅ Redirecionamento rápido para `manutencao.html`
- ✅ Página de manutenção carrega instantaneamente

**❌ Resultado Incorreto:**
- ⏱️ Tempo: **>10 segundos** = Arquivos PHP não foram atualizados
- ❌ OS duplicada = Migração SQL não foi executada ou falhou
- ❌ Página lenta = `manutencao.html` não foi atualizado

---

## 🔍 PASSO 6: Verificar no Banco de Dados

Execute no phpMyAdmin:

```sql
-- Ver últimas 5 OS criadas
SELECT id, ordem_numero, seq_number, data_criacao
FROM ordemservico
ORDER BY id DESC
LIMIT 5;
```

**✅ Resultado Esperado:**
- Cada OS tem um `seq_number` único
- Não há `seq_number` duplicados
- `seq_number` está sequencial (ex: 15, 16, 17, 18, 19)

---

## ❌ Solução de Problemas

### Problema: "OS duplicada"
**Causa:** Migração SQL não foi executada ou arquivos PHP não foram atualizados
**Solução:**
1. Execute o PASSO 1 novamente
2. Verifique com `test-seq-number.php` (PASSO 3)
3. Faça upload dos arquivos PHP novamente (PASSO 2)

### Problema: "IturanService ainda aparece nos logs"
**Causa:** Arquivo `manutencao.html` não foi atualizado no servidor
**Solução:**
1. Verifique se fez upload do arquivo correto
2. Limpe o cache do navegador (Ctrl+Shift+Del)
3. Recarregue a página (Ctrl+Shift+R)
4. Verifique se o arquivo no servidor tem a linha:
   ```html
   <!-- <script src="ituran-service.js"></script> -->
   ```

### Problema: "Ainda lento (>10 segundos)"
**Causa:** Arquivos PHP não foram atualizados
**Solução:**
1. Verifique se fez upload de TODOS os 4 arquivos PHP
2. Verifique se sobrescreveu os arquivos existentes
3. Teste com `test-seq-number.php` para verificar migração

---

## ✅ Checklist Final

- [ ] Migração SQL executada com sucesso
- [ ] `test-seq-number.php` retorna `has_seq_number_column: true`
- [ ] 5 arquivos PHP/HTML foram enviados para o servidor
- [ ] Console não mostra mensagens do IturanService
- [ ] Criação de OS leva 2-5 segundos
- [ ] Não há OS duplicadas
- [ ] Página de manutenção carrega instantaneamente

---

## 🎉 Sucesso!

Se todos os itens acima estão marcados, as otimizações foram aplicadas com sucesso!

**Ganho Total:** ~3.7-6.7 segundos (85-95% melhoria)
