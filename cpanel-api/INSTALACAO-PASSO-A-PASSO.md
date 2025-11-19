# 🚀 Instalação Passo a Passo - API Planos de Manutenção

## ✅ Checklist Completo

### Fase 1: Preparação do cPanel
- [ ] 1.1 - Criar tabela no phpMyAdmin
- [ ] 1.2 - Anotar credenciais do banco
- [ ] 1.3 - Criar pasta `cpanel-api` no public_html

### Fase 2: Upload dos Arquivos
- [ ] 2.1 - Upload `planos-manutencao-api.php`
- [ ] 2.2 - Upload `config-db.php`
- [ ] 2.3 - Upload `testar-api-planos.html`
- [ ] 2.4 - Renomear `htaccess.txt` para `.htaccess` e fazer upload

### Fase 3: Configuração
- [ ] 3.1 - Editar `config-db.php` com credenciais corretas
- [ ] 3.2 - Testar acesso à API
- [ ] 3.3 - Verificar permissões dos arquivos

### Fase 4: Migração de Dados
- [ ] 4.1 - Ajustar URL da API no script de migração
- [ ] 4.2 - Executar migração dos planos
- [ ] 4.3 - Verificar dados no phpMyAdmin

### Fase 5: Integração com o Site
- [ ] 5.1 - Atualizar URLs da API no frontend
- [ ] 5.2 - Testar funcionalidades
- [ ] 5.3 - Deploy final

---

## 📋 PASSO 1: Criar a Tabela no phpMyAdmin

1. Acesse o **phpMyAdmin** no cPanel
2. Selecione seu banco de dados (`f137049_in9aut`)
3. Clique na aba **SQL**
4. Cole o seguinte código SQL:

```sql
CREATE TABLE Planos_Manutenção (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo_carro VARCHAR(50) NOT NULL,
    descricao_titulo VARCHAR(100) NOT NULL,
    km_recomendado INT NULL,
    intervalo_tempo VARCHAR(30) NULL COMMENT 'Ex: 6 meses, 12 meses, 2 anos',
    custo_estimado DECIMAL(10,2) NULL COMMENT 'Em R$',
    criticidade ENUM('Baixa', 'Média', 'Alta', 'Crítica') NOT NULL DEFAULT 'Média',
    descricao_observacao TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_modelo ON Planos_Manutenção(modelo_carro);
CREATE INDEX idx_km ON Planos_Manutenção(km_recomendado);
```

5. Clique em **Executar**
6. ✅ Verifique se a tabela foi criada com sucesso

---

## 📁 PASSO 2: Upload dos Arquivos para o cPanel

### 2.1 - Criar Pasta

1. Acesse o **Gerenciador de Arquivos** do cPanel
2. Navegue até `public_html` (ou `httpdocs`)
3. Clique em **+ Pasta**
4. Nome: `cpanel-api`
5. Clique em **Criar Nova Pasta**

### 2.2 - Upload dos Arquivos

Faça upload dos seguintes arquivos para `public_html/cpanel-api`:

- ✅ `planos-manutencao-api.php` (API principal)
- ✅ `config-db.php` (Configuração do banco)
- ✅ `testar-api-planos.html` (Interface de teste)
- ✅ `htaccess.txt` (Segurança - renomear depois)

**Como fazer upload:**
1. Clique em **Upload**
2. Selecione os arquivos
3. Aguarde o upload completar
4. ✅ Confirme que todos estão na pasta

### 2.3 - Renomear .htaccess

1. Localize o arquivo `htaccess.txt`
2. Clique com botão direito → **Renomear**
3. Novo nome: `.htaccess` (com ponto no início!)
4. ✅ Confirme a renomeação

---

## ⚙️ PASSO 3: Configurar o Banco de Dados

1. No Gerenciador de Arquivos, localize `config-db.php`
2. Clique com botão direito → **Editar**
3. Ajuste as seguintes linhas:

```php
define('DB_HOST', 'localhost'); // Geralmente é localhost
define('DB_USER', 'f137049_tool'); // SEU usuário do banco
define('DB_PASS', 'In9@1234qwer'); // SUA senha do banco
define('DB_NAME', 'f137049_in9aut'); // SEU banco de dados
```

4. Clique em **Salvar Alterações**
5. ✅ Configuração concluída

---

## 🧪 PASSO 4: Testar a API

### 4.1 - Teste Básico via Navegador

Abra no navegador:
```
https://ituran.iweb.i9tecnologia.com.br/cpanel-api/planos-manutencao-api.php
```

**Resultado esperado:**
```json
{
  "success": true,
  "total": 0,
  "count": 0,
  "data": []
}
```

✅ Se ver este JSON, a API está funcionando!

### 4.2 - Teste com Interface Web

Abra no navegador:
```
https://ituran.iweb.i9tecnologia.com.br/cpanel-api/testar-api-planos.html
```

1. Clique em **"Listar Todos os Planos"**
2. Deve aparecer um JSON (vazio por enquanto)
3. ✅ Teste bem-sucedido!

### 4.3 - Teste de Criação

Na interface de teste:

1. Preencha o formulário "Criar Novo Plano":
   - **Modelo:** `Toyota Hilux`
   - **Título:** `Teste de API`
   - **KM:** `10000`
   - **Criticidade:** `Alta`

2. Clique em **"Criar Plano"**

3. Deve aparecer:
```json
{
  "success": true,
  "message": "Plano criado com sucesso",
  "id": 1
}
```

4. ✅ Se funcionou, a API está 100% operacional!

---

## 🔄 PASSO 5: Migrar os Dados Existentes

### 5.1 - Preparar o Script

No seu computador, abra o arquivo:
```
migrar-planos-para-nova-tabela.js
```

Localize a linha:
```javascript
const API_URL = 'https://ituran.iweb.i9tecnologia.com.br/cpanel-api/planos-manutencao-api.php';
```

Confirme se a URL está correta.

### 5.2 - Executar a Migração

No terminal/prompt, navegue até a pasta do projeto:

```bash
cd C:\Users\SAMSUNG\Desktop\frotas
node migrar-planos-para-nova-tabela.js
```

### 5.3 - Acompanhar o Progresso

O script irá:
1. ✅ Buscar todos os planos da API local
2. ✅ Cadastrar cada um na nova API do cPanel
3. ✅ Exibir relatório de sucesso

**Saída esperada:**
```
🔄 MIGRAÇÃO DE PLANOS DE MANUTENÇÃO
══════════════════════════════════════════════════════════════════════
📥 Buscando planos existentes...
✅ 250 planos encontrados

📋 Planos por modelo:
   • Toyota Hilux: 17 itens
   • Mitsubishi L200: 20 itens
   ...

✅ Sucesso: 250
❌ Erro: 0
📊 Total: 250

✨ Migração concluída com 100% de sucesso!
```

### 5.4 - Verificar no phpMyAdmin

1. Acesse o phpMyAdmin
2. Selecione a tabela `Planos_Manutenção`
3. Clique em **Visualizar**
4. ✅ Confirme que os dados estão lá!

---

## 🌐 PASSO 6: Integrar com o Frontend

### 6.1 - Atualizar URLs no Site

Em todos os arquivos HTML/JS do seu site que usam planos de manutenção, substitua:

**Antiga URL (local):**
```javascript
const API_URL = 'http://localhost:5000/api/maintenance-plan-items';
```

**Nova URL (cPanel):**
```javascript
const API_URL = 'https://ituran.iweb.i9tecnologia.com.br/cpanel-api/planos-manutencao-api.php';
```

### 6.2 - Arquivos que Precisam Ser Atualizados

Procure e atualize em:
- ✅ `planos-manutencao.html`
- ✅ `dashboard.html` (se usar planos)
- ✅ Qualquer outro arquivo que consulte planos

### 6.3 - Adaptar as Requisições

A nova API retorna dados em formato ligeiramente diferente:

**Antigo (modelo_id):**
```javascript
fetch(`${API_URL}?modelo_id=14`)
```

**Novo (modelo por nome):**
```javascript
fetch(`${API_URL}?modelo=Toyota Hilux`)
```

---

## 🔒 PASSO 7: Segurança e Performance

### 7.1 - Verificar Permissões

No Gerenciador de Arquivos do cPanel:

1. Selecione todos os arquivos PHP
2. Clique em **Permissões**
3. Configure como: `644` (rw-r--r--)
4. ✅ Salvar

### 7.2 - Ativar HTTPS

Se ainda não tiver SSL:

1. No cPanel, vá em **SSL/TLS**
2. Instale um certificado Let's Encrypt (gratuito)
3. Force HTTPS no `.htaccess`:

Descomente as linhas:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 7.3 - Backup

1. No cPanel, vá em **Backup**
2. Faça backup da pasta `cpanel-api`
3. Faça backup do banco de dados
4. ✅ Guarde em local seguro

---

## ✅ Checklist Final

Antes de considerar concluído, verifique:

- [ ] ✅ Tabela criada no banco de dados
- [ ] ✅ Arquivos PHP no cPanel
- [ ] ✅ `.htaccess` configurado
- [ ] ✅ Credenciais do banco corretas
- [ ] ✅ API responde corretamente
- [ ] ✅ Interface de teste funciona
- [ ] ✅ Migração executada com sucesso
- [ ] ✅ Dados aparecendo no phpMyAdmin
- [ ] ✅ Frontend atualizado com nova URL
- [ ] ✅ HTTPS ativado
- [ ] ✅ Backup realizado

---

## 🐛 Problemas Comuns

### "Erro de conexão com o banco"
**Solução:**
1. Verifique `config-db.php`
2. Confirme credenciais no cPanel → MySQL Databases
3. Teste conexão no phpMyAdmin

### "CORS Error" no navegador
**Solução:**
1. Verifique se `.htaccess` foi criado corretamente
2. Verifique se o arquivo começa com ponto: `.htaccess`
3. Limpe cache do navegador (Ctrl+Shift+Delete)

### "404 Not Found"
**Solução:**
1. Verifique se os arquivos estão em `public_html/cpanel-api`
2. Teste a URL diretamente no navegador
3. Verifique permissões dos arquivos (644)

### Migração não funciona
**Solução:**
1. Verifique se a API local está rodando (`localhost:5000`)
2. Confirme URL correta no script de migração
3. Execute: `node migrar-planos-para-nova-tabela.js`

---

## 📞 Suporte

Se tiver problemas:

1. ✅ Verifique os logs do cPanel (Error Log)
2. ✅ Use a interface de teste para diagnóstico
3. ✅ Confirme permissões e credenciais
4. ✅ Teste endpoints um por um

---

## 🎉 Conclusão

Após seguir todos os passos:

✅ API REST funcionando no cPanel
✅ Tabela criada e populada
✅ Interface de teste disponível
✅ Frontend integrado
✅ Sistema pronto para produção!

**Próximos passos:**
- Integrar alertas de manutenção
- Criar relatórios por veículo
- Adicionar notificações automáticas

---

**Documentação criada em:** 13/11/2025
**Versão:** 1.0
**Sistema:** FleetFlow - Gestão de Frotas
