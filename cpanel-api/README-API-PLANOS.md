# API de Planos de Manutenção - cPanel

## 📁 Arquivos Criados

1. **planos-manutencao-api.php** - API REST completa
2. **config-db.php** - Configuração do banco de dados
3. **testar-api-planos.html** - Interface de teste
4. **README-API-PLANOS.md** - Este arquivo

## 🚀 Instalação no cPanel

### Passo 1: Upload dos Arquivos

1. Acesse o **Gerenciador de Arquivos** do cPanel
2. Navegue até a pasta `public_html` ou `httpdocs`
3. Crie uma pasta chamada `cpanel-api` (se não existir)
4. Faça upload dos arquivos:
   - `planos-manutencao-api.php`
   - `config-db.php`
   - `testar-api-planos.html`

### Passo 2: Configurar o Banco de Dados

1. Abra o arquivo `config-db.php` no editor do cPanel
2. Ajuste as credenciais do banco:

```php
define('DB_HOST', 'localhost'); // geralmente é localhost
define('DB_USER', 'SEU_USUARIO_DB');
define('DB_PASS', 'SUA_SENHA_DB');
define('DB_NAME', 'SEU_BANCO_DB');
```

3. Salve o arquivo

### Passo 3: Criar a Tabela

A tabela já foi criada no phpMyAdmin:

```sql
CREATE TABLE Planos_Manutenção (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo_carro VARCHAR(50) NOT NULL,
    descricao_titulo VARCHAR(100) NOT NULL,
    km_recomendado INT NULL,
    intervalo_tempo VARCHAR(30) NULL,
    custo_estimado DECIMAL(10,2) NULL,
    criticidade ENUM('Baixa', 'Média', 'Alta', 'Crítica') NOT NULL DEFAULT 'Média',
    descricao_observacao TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_modelo ON Planos_Manutenção(modelo_carro);
CREATE INDEX idx_km ON Planos_Manutenção(km_recomendado);
```

## 📡 Endpoints da API

### URL Base
```
https://floripa.in9automacao.com.br/cpanel-api/planos-manutencao-api.php
```

### 1. **GET** - Listar Todos os Planos
```
GET /planos-manutencao-api.php
```

**Resposta:**
```json
{
  "success": true,
  "total": 250,
  "count": 250,
  "data": [...]
}
```

### 2. **GET** - Buscar por Modelo
```
GET /planos-manutencao-api.php?modelo=Toyota
```

**Resposta:**
```json
{
  "success": true,
  "count": 17,
  "data": [...]
}
```

### 3. **GET** - Buscar por ID
```
GET /planos-manutencao-api.php?id=1
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "modelo_carro": "Toyota Hilux",
    "descricao_titulo": "Revisão 10.000 km",
    "km_recomendado": 10000,
    "intervalo_tempo": "6 meses",
    "custo_estimado": "500.00",
    "criticidade": "Alta",
    "descricao_observacao": "Troca de óleo...",
    "criado_em": "2025-11-13 10:00:00",
    "atualizado_em": null
  }
}
```

### 4. **POST** - Criar Novo Plano
```
POST /planos-manutencao-api.php
Content-Type: application/json
```

**Body:**
```json
{
  "modelo_carro": "Toyota Hilux",
  "descricao_titulo": "Revisão 10.000 km - Óleo e Filtros",
  "km_recomendado": 10000,
  "intervalo_tempo": "6 meses",
  "custo_estimado": 500.00,
  "criticidade": "Alta",
  "descricao_observacao": "Troca de óleo e filtros básicos"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Plano criado com sucesso",
  "id": 251
}
```

### 5. **PUT** - Atualizar Plano
```
PUT /planos-manutencao-api.php?id=1
Content-Type: application/json
```

**Body:**
```json
{
  "custo_estimado": 550.00,
  "criticidade": "Crítica"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Plano atualizado com sucesso"
}
```

### 6. **DELETE** - Deletar Plano
```
DELETE /planos-manutencao-api.php?id=1
```

**Resposta:**
```json
{
  "success": true,
  "message": "Plano deletado com sucesso"
}
```

## 🧪 Testando a API

### Método 1: Interface Web
Abra o arquivo `testar-api-planos.html` no navegador:
```
https://SEU-DOMINIO.com.br/cpanel-api/testar-api-planos.html
```

### Método 2: cURL
```bash
# Listar todos
curl https://SEU-DOMINIO.com.br/cpanel-api/planos-manutencao-api.php

# Buscar por modelo
curl "https://SEU-DOMINIO.com.br/cpanel-api/planos-manutencao-api.php?modelo=Toyota"

# Criar novo
curl -X POST https://SEU-DOMINIO.com.br/cpanel-api/planos-manutencao-api.php \
  -H "Content-Type: application/json" \
  -d '{"modelo_carro":"Toyota Hilux","descricao_titulo":"Teste","criticidade":"Alta"}'
```

### Método 3: JavaScript (Fetch)
```javascript
// Listar todos
fetch('https://SEU-DOMINIO.com.br/cpanel-api/planos-manutencao-api.php')
  .then(res => res.json())
  .then(data => console.log(data));

// Criar novo
fetch('https://SEU-DOMINIO.com.br/cpanel-api/planos-manutencao-api.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    modelo_carro: 'Toyota Hilux',
    descricao_titulo: 'Revisão 10.000 km',
    criticidade: 'Alta'
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

## 📊 Migração dos Dados Existentes

Após configurar a API no cPanel, execute o script de migração:

```bash
# Ajuste a URL no arquivo migrar-planos-para-nova-tabela.js
# Depois execute:
node migrar-planos-para-nova-tabela.js
```

Este script irá:
1. Buscar todos os planos da API local (Node.js)
2. Cadastrar cada um na nova API do cPanel
3. Exibir relatório de sucesso/erro

## 🔒 Segurança

### Recomendações:

1. **Proteger config-db.php:**
   Adicione no `.htaccess`:
   ```apache
   <Files "config-db.php">
       Order allow,deny
       Deny from all
   </Files>
   ```

2. **Limite de taxa (Rate Limiting):**
   Configure no cPanel ou use Cloudflare

3. **Autenticação (Opcional):**
   Adicione validação de token/API key se necessário

4. **HTTPS:**
   Certifique-se de que o SSL está ativo

## 🐛 Troubleshooting

### Erro: "Erro de conexão com o banco de dados"
- Verifique as credenciais em `config-db.php`
- Confirme que o usuário tem permissões na tabela
- Teste a conexão no phpMyAdmin

### Erro: "CORS blocked"
- A API já está configurada para aceitar CORS
- Se persistir, adicione no `.htaccess`:
  ```apache
  Header set Access-Control-Allow-Origin "*"
  Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
  ```

### Erro 500
- Ative o display de erros temporariamente:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Verifique os logs do cPanel

## 📝 Validações

A API valida automaticamente:
- ✅ `modelo_carro` (obrigatório)
- ✅ `descricao_titulo` (obrigatório)
- ✅ `criticidade` (deve ser: Baixa, Média, Alta ou Crítica)
- ✅ `custo_estimado` (deve ser numérico)
- ✅ `km_recomendado` (deve ser numérico)

## 🎯 Próximos Passos

1. ✅ Upload dos arquivos para o cPanel
2. ✅ Configuração do banco de dados
3. ✅ Teste da API usando `testar-api-planos.html`
4. ✅ Execução do script de migração
5. ⏳ Integração com o frontend do site

## 📞 Suporte

Em caso de dúvidas ou problemas:
1. Verifique os logs do cPanel
2. Teste os endpoints usando a interface de teste
3. Confirme as permissões do banco de dados

---

**Versão:** 1.0
**Data:** 13/11/2025
**Desenvolvido para:** Sistema de Gestão de Frotas FleetFlow
