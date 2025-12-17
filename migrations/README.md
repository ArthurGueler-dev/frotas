# Migração: Adicionar AUTO_INCREMENT

## Objetivo
Adicionar coluna `seq_number` com AUTO_INCREMENT para eliminar race conditions e retry loops na criação de OS.

## ⚠️ Se Você Já Tentou Executar Antes

**Se você já tentou executar a migração e deu erro de "coluna duplicada":**

1. Execute primeiro `cleanup.sql` para remover a coluna antiga
2. Depois execute `add_seq_number_clean.sql`

### Passos com Cleanup:
1. Abra phpMyAdmin
2. Selecione o banco `f137049_in9aut`
3. Clique na aba "SQL"
4. **Cole e execute primeiro:** `cleanup.sql`
5. **Depois cole e execute:** `add_seq_number_clean.sql`

## 🆕 Se É a Primeira Vez

**Se você nunca executou a migração antes:**

1. Execute apenas `add_seq_number_clean.sql`

### Passos:
1. Abra phpMyAdmin
2. Selecione o banco `f137049_in9aut`
3. Clique na aba "SQL"
4. Cole o conteúdo do arquivo `add_seq_number_clean.sql`
5. Clique em "Executar"

## Via MySQL CLI (Alternativa)

### Se já tentou antes:
```bash
mysql -h 187.49.226.10 -P 3306 -u f137049_tool -p f137049_in9aut < migrations/cleanup.sql
mysql -h 187.49.226.10 -P 3306 -u f137049_tool -p f137049_in9aut < migrations/add_seq_number_clean.sql
```

### Se é primeira vez:
```bash
mysql -h 187.49.226.10 -P 3306 -u f137049_tool -p f137049_in9aut < migrations/add_seq_number_clean.sql
```

## O que esta migração faz?
1. Adiciona coluna `seq_number INT AUTO_INCREMENT UNIQUE` na tabela `ordemservico`
2. Preenche valores existentes sequencialmente (necessário para AUTO_INCREMENT funcionar)
3. Mostra a estrutura da tabela para verificação
4. Lista os 10 últimos registros para conferência

## Verificação Pós-Migração
Execute no SQL:
```sql
SELECT id, ordem_numero, seq_number
FROM ordemservico
ORDER BY id DESC
LIMIT 10;
```

Você deve ver:
- Todos os registros antigos com `seq_number` preenchido
- Novos registros terão `seq_number` gerado automaticamente

## Rollback (se necessário)
```sql
ALTER TABLE ordemservico DROP COLUMN seq_number;
```

**⚠️ AVISO:** Após executar a migração, você DEVE fazer upload dos arquivos PHP modificados:
- `save-workorder.php`
- `get-next-os-number.php`
