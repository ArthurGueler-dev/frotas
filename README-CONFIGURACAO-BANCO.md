# 📚 Configuração do Banco de Dados - FleetFlow

Este guia explica como configurar a conexão com o banco de dados MySQL no cPanel para carregar motoristas.

## 📋 Pré-requisitos

- Acesso ao cPanel
- Banco de dados MySQL criado
- Tabela `Drivers` com as colunas: `DriverID`, `FirstName`, `LastName`

---

## 🚀 Passo a Passo

### 1️⃣ Configurar Credenciais do Banco

Abra o arquivo `db-config.php` e edite as seguintes linhas:

```php
// ANTES (exemplo - seus valores serão diferentes)
define('DB_HOST', 'localhost');
define('DB_NAME', 'seu_banco_de_dados');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

**Como encontrar as credenciais no cPanel:**

1. Acesse o **cPanel**
2. Vá em **Bancos de Dados MySQL** ou **phpMyAdmin**
3. Anote:
   - **Host**: Geralmente é `localhost`
   - **Nome do Banco**: Ex: `cpanel_usuario_frotas`
   - **Usuário**: Ex: `cpanel_usuario`
   - **Senha**: A senha que você definiu

**Exemplo real:**

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpanel_joao_fleetflow');
define('DB_USER', 'cpanel_joao');
define('DB_PASS', 'MinH@S3nh@Segur@123');
```

---

### 2️⃣ Fazer Upload dos Arquivos PHP

Faça upload dos seguintes arquivos para o diretório raiz do seu site no cPanel:

```
public_html/
├── db-config.php              ✅ Configuração do banco
├── get-drivers.php            ✅ API para buscar motoristas
├── test-db-connection.php     ✅ Teste de conexão (REMOVER EM PRODUÇÃO)
└── ... (outros arquivos HTML, CSS, JS)
```

**Como fazer upload:**

1. No cPanel, vá em **Gerenciador de Arquivos**
2. Navegue até `public_html`
3. Clique em **Fazer Upload**
4. Selecione os 3 arquivos PHP
5. Aguarde o upload completar

---

### 3️⃣ Testar a Conexão

Antes de usar o sistema, teste se a conexão está funcionando:

1. Abra o navegador
2. Acesse: `https://seusite.com/test-db-connection.php`
3. Você verá uma página com testes automáticos:
   - ✅ Arquivo de configuração
   - ✅ Conexão com banco
   - ✅ Tabela Drivers
   - ✅ Contagem de registros
   - ✅ Estrutura da tabela
   - ✅ Primeiros 5 registros

**Se todos os testes passarem:**
- ✅ Configuração está correta!
- ✅ Pode usar o sistema normalmente

**Se houver erros:**
- ❌ Verifique as credenciais em `db-config.php`
- ❌ Verifique se o banco existe
- ❌ Verifique se a tabela `Drivers` existe
- ❌ Verifique as permissões do usuário

---

### 4️⃣ Usar a Página de Motoristas

Após a configuração:

1. Acesse a página: `https://seusite.com/motoristas.html`
2. A página vai automaticamente:
   - Buscar motoristas do banco MySQL
   - Exibir na tabela
   - Salvar cache no navegador
3. Abra o Console do navegador (F12) para ver logs:
   - `✅ X motoristas carregados do banco de dados MySQL`

---

## 🔐 Segurança

### ⚠️ IMPORTANTE - Remover arquivo de teste

Após confirmar que funciona, **DELETE** o arquivo de teste:

```bash
# NO GERENCIADOR DE ARQUIVOS DO CPANEL, DELETAR:
test-db-connection.php
```

Este arquivo expõe informações sensíveis do banco!

### 🛡️ Proteger db-config.php

Adicione estas linhas no arquivo `.htaccess` para proteger as configurações:

```apache
# Proteger arquivos de configuração
<Files "db-config.php">
    Order Allow,Deny
    Deny from all
</Files>
```

---

## 📊 Estrutura da Tabela Drivers

Certifique-se de que sua tabela tem estas colunas (mínimo):

```sql
CREATE TABLE Drivers (
    DriverID INT PRIMARY KEY AUTO_INCREMENT,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL
);
```

**Exemplo de dados:**

```sql
INSERT INTO Drivers (FirstName, LastName) VALUES
('João', 'Silva'),
('Maria', 'Santos'),
('Carlos', 'Oliveira');
```

---

## 🔄 Fluxo de Dados

```
                   ┌─────────────────┐
                   │  MySQL Database │
                   │  Tabela: Drivers│
                   └────────┬────────┘
                            │
                            │ SELECT DriverID, FirstName, LastName
                            ↓
                   ┌─────────────────┐
                   │ get-drivers.php │
                   │   (API Backend) │
                   └────────┬────────┘
                            │
                            │ JSON Response
                            ↓
                   ┌─────────────────┐
                   │  api-client.js  │
                   │  (JavaScript)   │
                   └────────┬────────┘
                            │
                            │ Fetch API
                            ↓
                   ┌─────────────────┐
                   │ motoristas.html │
                   │   (Frontend)    │
                   └─────────────────┘
```

---

## 🐛 Troubleshooting

### Erro: "Falha na conexão com o banco de dados"

**Soluções:**
1. Verifique se as credenciais em `db-config.php` estão corretas
2. Verifique se o banco de dados existe no phpMyAdmin
3. Teste a conexão acessando `test-db-connection.php`

### Erro: "Tabela Drivers não encontrada"

**Soluções:**
1. Verifique o nome da tabela (pode ser case-sensitive)
2. Certifique-se de que a tabela foi criada
3. No phpMyAdmin, vá em SQL e execute:
   ```sql
   SHOW TABLES LIKE 'Drivers';
   ```

### Erro: "Nenhum motorista encontrado"

**Soluções:**
1. Verifique se há dados na tabela:
   ```sql
   SELECT * FROM Drivers;
   ```
2. Se a tabela estiver vazia, insira alguns dados de teste

### Página não carrega motoristas

**Soluções:**
1. Abra o Console do navegador (F12)
2. Veja os erros em vermelho
3. Verifique se o arquivo `get-drivers.php` está acessível
4. Teste diretamente: `https://seusite.com/get-drivers.php`
   - Deve retornar JSON com os motoristas

---

## ✅ Checklist Final

- [ ] Editei `db-config.php` com minhas credenciais
- [ ] Fiz upload dos arquivos PHP para o cPanel
- [ ] Testei a conexão em `test-db-connection.php`
- [ ] Todos os testes passaram ✅
- [ ] Deletei `test-db-connection.php` (segurança)
- [ ] Acessei `motoristas.html` e vi os dados do banco
- [ ] Vi no console: "✅ X motoristas carregados do banco"

---

## 💡 Dicas

- **Cache**: Os motoristas são salvos no cache do navegador para funcionar offline
- **Atualizar dados**: Basta recarregar a página (F5)
- **Adicionar colunas**: Edite `get-drivers.php` para incluir mais campos do banco

---

## 📞 Suporte

Se precisar de ajuda:
1. Verifique os logs do console do navegador (F12)
2. Verifique os logs do PHP no cPanel
3. Execute `test-db-connection.php` para diagnóstico completo
