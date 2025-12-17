# Como Limpar Rotas Antigas

## 🎯 Objetivo
Deletar as rotas antigas que foram criadas com dados incorretos (todos os locais misturados).

---

## 📋 Passo a Passo

### Método 1: Via phpMyAdmin (Mais fácil)

1. **Acesse o phpMyAdmin** no cPanel
2. **Selecione o banco** `f137049_in9aut`
3. Clique em **"SQL"** no menu superior
4. **Cole e execute este comando:**
   ```sql
   DELETE FROM FF_Rotas;
   ```
5. Clique em **"Executar"**
6. Pronto! Todas as rotas foram deletadas

---

### Método 2: Via Terminal/SSH

```bash
# Conectar ao MySQL
mysql -h 187.49.226.10 -u f137049_tool -p f137049_in9aut

# Quando pedir senha, digite:
# In9@1234qwer

# Executar comando de deleção
DELETE FROM FF_Rotas;

# Ver quantas rotas restam (deve ser 0)
SELECT COUNT(*) FROM FF_Rotas;

# Sair
exit;
```

---

### Método 3: Usar o arquivo SQL criado

1. Abra o arquivo `limpar-rotas.sql`
2. Copie todo o conteúdo
3. Cole no phpMyAdmin → SQL
4. Execute

---

## ✅ Após Limpar

1. **Faça upload** do arquivo corrigido `locations-api.php` para o cPanel
2. **Delete** todos os blocos antigos no otimizador
3. **Importe novamente** a planilha
4. **Teste** enviar uma rota - agora deve funcionar corretamente!

---

## 🔍 Verificar se Limpou

No phpMyAdmin, execute:
```sql
SELECT COUNT(*) as total FROM FF_Rotas;
```

Deve retornar **0** (zero rotas).

---

## ⚠️ Importante

Depois de limpar as rotas:
- Os blocos continuam existindo
- Apenas as rotas salvas são deletadas
- Você precisará clicar em "Gerar Rota" novamente para cada bloco
- Ou deletar os blocos e importar tudo de novo (recomendado)
