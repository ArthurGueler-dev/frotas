# 🔧 Como Corrigir o Erro "Unknown column 'veiculo_id'"

## 📋 Problema
Ao tentar criar uma Ordem de Serviço, aparece o erro:
```
❌ Erro HTTP: 500 {"success":false,"error":"Erro ao criar OS","message":"Unknown column 'veiculo_id' in 'field list'"}
```

## 🎯 Causa
A tabela `ordemservico` no banco de dados não possui a coluna `veiculo_id`, que é necessária para vincular a OS a um veículo específico.

## ✅ Solução

### Opção 1: Usando phpMyAdmin (RECOMENDADO)

1. **Acesse o phpMyAdmin**
   - URL: https://www.in9automacao.com.br:2083/cpsess8254851949/3rdparty/phpMyAdmin/
   - Usuário: `f137049_fioforte`
   - Senha: (sua senha do banco)

2. **Selecione o banco de dados**
   - Clique em `f137049_in9aut` no menu lateral

3. **Execute o script de correção**
   - Clique na aba "SQL"
   - Copie e cole o conteúdo do arquivo `fix-ordemservico-table.sql`
   - Clique em "Executar"

4. **Verifique se funcionou**
   - Você deve ver a mensagem: "Estrutura da tabela ordemservico corrigida com sucesso!"
   - Volte para a página de lançar OS e tente criar uma nova ordem

### Opção 2: Verificar via Interface Web

1. **Abra o verificador automático**
   ```
   http://localhost:5000/verify-and-fix-db.html
   ```

2. **Clique em "Verificar Estrutura"**
   - Isso irá mostrar todas as colunas da tabela
   - Você verá se `veiculo_id` existe ou não

3. **Se a coluna não existir, clique em "Corrigir Estrutura"**
   - O sistema tentará adicionar a coluna automaticamente
   - Verifique se aparece a mensagem de sucesso

### Opção 3: Comando SQL Direto

Se você tem acesso ao MySQL via linha de comando, execute:

```sql
USE f137049_in9aut;

-- Adicionar a coluna veiculo_id
ALTER TABLE ordemservico
ADD COLUMN veiculo_id INT NULL DEFAULT NULL
AFTER ordem_numero;

-- Adicionar índice
ALTER TABLE ordemservico
ADD INDEX idx_veiculo (veiculo_id ASC);

-- Adicionar foreign key
ALTER TABLE ordemservico
ADD CONSTRAINT fk_os_veiculos
FOREIGN KEY (veiculo_id)
REFERENCES veiculos (id)
ON DELETE SET NULL
ON UPDATE CASCADE;
```

## 📊 Como Verificar se Está Correto

Após executar a correção, execute esta query no phpMyAdmin:

```sql
DESCRIBE ordemservico;
```

Você deve ver a coluna `veiculo_id` na lista, com tipo `INT` e NULL `YES`.

## 🔍 Estrutura Esperada

A tabela `ordemservico` deve ter estas colunas principais:

- `id` - INT (Primary Key)
- `ordem_numero` - VARCHAR(50)
- **`veiculo_id`** - INT (Esta é a que está faltando!) ⬅️
- `placa_veiculo` - VARCHAR(20)
- `motorista_id` - INT
- `km_veiculo` - INT
- `status` - ENUM
- ... (outras colunas)

## ❓ Ainda não Funciona?

Se após executar as correções o erro persistir:

1. **Verifique se a tabela existe**
   ```sql
   SHOW TABLES LIKE 'ordemservico';
   ```

2. **Se a tabela não existir, execute o script completo**
   - Arquivo: `bd_frotas.sql`
   - Este script cria TODAS as tabelas necessárias

3. **Verifique se outras tabelas existem**
   ```sql
   SHOW TABLES;
   ```
   Você deve ver: `veiculos`, `motoristas`, `ordemservico`, `ordemservico_itens`, `servicos`, etc.

## 📞 Suporte

Se o problema continuar, verifique:
- ✅ O arquivo `db-config.php` tem as credenciais corretas
- ✅ O servidor Node.js está rodando (porta 5000)
- ✅ Você está usando o banco de dados correto (`f137049_in9aut`)
- ✅ O usuário do banco tem permissão para ALTER TABLE

---

**Última atualização:** 2025-10-29
**Arquivo de correção:** `fix-ordemservico-table.sql`
**Verificador web:** `verify-and-fix-db.html`
