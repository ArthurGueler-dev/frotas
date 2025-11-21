# Migração para Produção - Sem Proxy Local

## Resumo das Mudanças

Este documento descreve como o FleetFlow foi refatorado para funcionar em produção sem necessidade de um proxy local separado.

---

## 🔄 Antes vs Depois

### ❌ ANTES (Desenvolvimento com Proxy)

```
Navegador
    ↓
Server Express (5000)
    ↓
Proxy Local (8888)  ← Processo separado necessário
    ↓
API Ituran (HTTPS)
```

**Problemas:**
- Proxy rodava em processo separado (`ituran-proxy.js`)
- Precisava de porta 8888 aberta
- Aumentava complexidade de deployment
- Scripts .bat para iniciar/reiniciar

---

### ✅ DEPOIS (Produção)

```
Navegador
    ↓
Server Express (443/HTTPS - VPS)
    ↓
API Ituran (HTTPS)  ← Direto, sem intermediário
```

**Benefícios:**
- ✅ Sem processo proxy separado
- ✅ Backend faz requisições server-to-server
- ✅ Credenciais seguras no .env (não no frontend)
- ✅ Simples de deployar (um container Docker)

---

## 📝 Arquivos Modificados

### 1. **services/ituran-api-client.js**
```javascript
// ANTES:
apiUrl: 'http://localhost:8888/api/ituran'

// DEPOIS:
apiUrl: this.isNode
    ? 'https://iweb.ituran.com.br'           // Node.js: direto
    : 'http://localhost:5000/api/proxy/ituran' // Browser: proxy
```

**Mudança:** Client automaticamente usa API direta em produção.

---

### 2. **server.js**
```javascript
// Adicionado no topo:
require('dotenv').config();

// Comentado (endpoint obsoleto):
// app.get('/api/proxy/ituran/*', ...) ← Não mais necessário
```

---

### 3. **Novos Arquivos**

#### `.env` (variáveis de ambiente - NUNCA commitar!)
```
ITURAN_API_URL=https://iweb.ituran.com.br
ITURAN_USERNAME=api@i9tecnologia
ITURAN_PASSWORD=Api@In9Eng
NODE_ENV=production
```

#### `.env.example` (template - SEGURO commitar)
```
ITURAN_API_URL=https://iweb.ituran.com.br
ITURAN_USERNAME=seu_usuario
ITURAN_PASSWORD=sua_senha
NODE_ENV=development
```

#### **package.json**
```
Adicionado: "dotenv": "^16.0.3"
```

---

### 4. **Arquivos Removidos**
- ❌ `ituran-proxy.js` - Não mais necessário
- ❌ `start-ituran-proxy.bat` - Substituído por variáveis de ambiente
- ❌ `restart-proxy-*.bat` - Não mais necessário

---

## 🚀 Como Usar em Produção (VPS/VM)

### Passo 1: Clonar no Servidor
```bash
git clone seu-repo.git
cd frotas
npm install
```

### Passo 2: Configurar .env na VPS
```bash
# Criar arquivo .env com as credenciais
cat > .env << EOF
ITURAN_API_URL=https://iweb.ituran.com.br
ITURAN_USERNAME=api@i9tecnologia
ITURAN_PASSWORD=Api@In9Eng
NODE_ENV=production
DB_HOST=187.49.226.10
DB_PORT=3306
DB_USER=f137049_tool
DB_PASSWORD=sua_senha_mysql
DB_NAME=f137049_in9aut
EOF
```

### Passo 3: Iniciar o Servidor
```bash
# Opção 1: Direto
node server.js

# Opção 2: Com PM2 (recomendado para produção)
npm install -g pm2
pm2 start server.js --name "fleetflow"
pm2 startup
pm2 save

# Opção 3: Com Docker
docker build -t fleetflow .
docker run -d -p 443:5000 \
  --env-file .env \
  --name fleetflow \
  fleetflow
```

### Passo 4: Configurar SSL/HTTPS
```bash
# Usando Let's Encrypt (recomendado)
certbot certonly --standalone -d seu-dominio.com.br
```

### Passo 5: Atualizar Nginx/Apache
```nginx
# nginx.conf exemplo
server {
    listen 443 ssl http2;
    server_name seu-dominio.com.br;

    ssl_certificate /etc/letsencrypt/live/seu-dominio.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seu-dominio.com.br/privkey.pem;

    location / {
        proxy_pass http://localhost:5000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## 🧪 Testes

### Teste 1: Verificar Inicialização
```bash
node server.js
# Deve aparecer:
# 🔧 IturanAPIClient inicializado - Node.js
# API URL: https://iweb.ituran.com.br
```

### Teste 2: Chamar API Ituran
```bash
curl -X GET "http://localhost:5000/api/quilometragem/diaria/OVE4358/2025-11-21"
# Deve retornar JSON com quilometragem
```

### Teste 3: Verificar Dashboard
- Abrir: `http://localhost:5000`
- Deve carregar dados sem erros CORS
- Widget de quilometragem deve atualizar

---

## ⚠️ Troubleshooting

### Erro: "Cannot find module 'dotenv'"
```bash
npm install dotenv
```

### Erro: "API timeout"
Aumentar timeout em `.env`:
```
ITURAN_TIMEOUT=180000  # 3 minutos
```

### Erro: "Credenciais inválidas"
Verificar credentials no `.env`:
```bash
# Testar manualmente
curl "https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx/GetAllPlatformsData?UserName=api@i9tecnologia&Password=Api@In9Eng"
```

### Erro: "CORS blocked"
Em produção não deve mais ocorrer. Se ocorrer:
1. Verificar se está usando HTTPS
2. Verificar domínio no certificado SSL
3. Verificar se proxy está desabilitado

---

## 📊 Fluxo de Requisição em Produção

### Cenário: Calcular KM de Hoje

```
1. Usuário acessa: https://seu-dominio.com.br
   └─ Carrega dashboard.html

2. Dashboard chama: /api/quilometragem/diaria/OVE4358/2025-11-21
   └─ Server Express recebe requisição

3. Server chama mileageService.getDailyMileage()
   └─ Retorna dados do MySQL OU busca da API

4. Se precisar buscar da API:
   └─ IturanAPIClient.request()
      └─ Faz requisição direto para: https://iweb.ituran.com.br
         └─ Backend usa credenciais do .env
         └─ Sem envolver navegador (sem CORS!)
         └─ Retorna XML

5. Server parseia XML
   └─ Extrai quilometragem
   └─ Salva em MySQL
   └─ Retorna JSON ao navegador

6. Dashboard exibe resultado
```

---

## 🔒 Segurança

### ✅ O que Melhorou:
- Credenciais Ituran NUNCA viajam para o navegador
- Requisições server-to-server (ninguém intercepta)
- Sem portas abertas desnecessárias (porta 8888 fechada)

### ⚠️ Ainda Necessário:
- Proteger `.env` (não commitar no Git)
- HTTPS obrigatório (Let's Encrypt gratuito)
- Credenciais MySQL em .env separado
- Firewall bloqueando portas não usadas

---

## 📋 Checklist para Deploy

- [ ] `.env` criado com credenciais corretas
- [ ] `.env` adicionado ao `.gitignore`
- [ ] `npm install` executado
- [ ] `node server.js` inicia sem erros
- [ ] Dashboard carrega sem erros CORS
- [ ] Quilometragem atualiza corretamente
- [ ] PM2 ou Docker configurado
- [ ] SSL/HTTPS configurado
- [ ] Proxy reverse (nginx/apache) configurado
- [ ] Testes de carga realizados

---

## 📞 Suporte

Se encontrar problemas:
1. Verificar logs: `cat /var/log/frotas.log`
2. Testar conectividade: `curl https://iweb.ituran.com.br`
3. Verificar .env: `cat .env | grep ITURAN`
4. Restart: `pm2 restart fleetflow`

---

**Data**: Nov 2025
**Status**: Pronto para Produção ✅
