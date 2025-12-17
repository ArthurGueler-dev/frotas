# Deploy em Produção - Sistema de Frotas WhatsApp

Este guia explica como fazer deploy do sistema de envio de rotas via WhatsApp em **produção**.

---

## 🏗️ Arquitetura de Produção

```
Frontend (floripa.in9automacao.com.br)
   ↓
Server.js (floripa.in9automacao.com.br:5000)
   ↓
Proxy WhatsApp (VPS 31.97.169.36:3001)
   ↓ [túnel SSH]
Evolution API (10.0.2.12:60010)
   ↓
WhatsApp
```

---

## 📋 Pré-requisitos

### No cPanel (floripa.in9automacao.com.br)
- Acesso FTP ou File Manager
- PHP 7.4+
- Acesso ao banco MySQL (187.49.226.10)

### No VPS (31.97.169.36)
- Acesso SSH como root
- Node.js instalado
- PM2 instalado (ou será instalado automaticamente)
- Túnel SSH reverso configurado para Evolution API

---

## 🚀 Passos para Deploy

### Opção 1: Deploy Automático (Linux/Git Bash)

```bash
# Execute o script de deploy
bash deploy-production.sh
```

O script fará:
1. ✅ Preparar arquivos PHP
2. ✅ Upload para cPanel (manual)
3. ✅ Deploy do proxy no VPS
4. ✅ Configurar PM2 no VPS

---

### Opção 2: Deploy Manual

#### Passo 1: Upload de Arquivos PHP para cPanel

**Arquivos para fazer upload:**
```
cpanel-api/get-rota.php              → public_html/
cpanel-api/update-rota-status.php    → public_html/
cpanel-api/enviar-rota-whatsapp.php  → public_html/
```

**Via cPanel File Manager:**
1. Login no cPanel
2. Abra File Manager
3. Navegue até `public_html/`
4. Clique em "Upload"
5. Selecione os 3 arquivos PHP
6. Aguarde upload completar

**Via FTP:**
```bash
# Usando FileZilla ou WinSCP
Host: floripa.in9automacao.com.br
User: [seu_usuário_ftp]
Password: [sua_senha_ftp]
Port: 21

# Upload os arquivos para: /public_html/
```

**Via SFTP (linha de comando):**
```bash
scp cpanel-api/get-rota.php user@floripa.in9automacao.com.br:~/public_html/
scp cpanel-api/update-rota-status.php user@floripa.in9automacao.com.br:~/public_html/
scp cpanel-api/enviar-rota-whatsapp.php user@floripa.in9automacao.com.br:~/public_html/
```

#### Passo 2: Deploy do Proxy WhatsApp no VPS

```bash
# Conectar ao VPS
ssh root@31.97.169.36

# Criar diretório
mkdir -p /root/frotas-whatsapp-proxy
cd /root/frotas-whatsapp-proxy

# Criar arquivo enviar-whatsapp-proxy.js
nano enviar-whatsapp-proxy.js
# Cole o conteúdo do arquivo e salve (Ctrl+O, Enter, Ctrl+X)

# Instalar dependências
npm install axios express mysql2

# Instalar PM2 (se não tiver)
npm install -g pm2

# Iniciar com PM2
pm2 start enviar-whatsapp-proxy.js --name whatsapp-proxy

# Salvar configuração
pm2 save

# Configurar auto-start no boot
pm2 startup systemd -u root --hp /root
# Execute o comando que aparecer na tela

# Verificar status
pm2 status
pm2 logs whatsapp-proxy
```

#### Passo 3: Configurar Túnel SSH para Evolution API

A Evolution API está em uma rede local (`10.0.2.12:60010`). O VPS precisa acessá-la via túnel SSH.

**No servidor onde está a Evolution API:**
```bash
# Criar túnel SSH reverso
ssh -R 60010:10.0.2.12:60010 root@31.97.169.36 -N -f

# Ou adicionar ao crontab para iniciar no boot:
@reboot ssh -R 60010:10.0.2.12:60010 root@31.97.169.36 -N -f
```

Agora o VPS pode acessar `http://localhost:60010` que será redirecionado para `10.0.2.12:60010`.

#### Passo 4: Criar Tabela FF_Rotas no Banco de Dados

```bash
# Conectar ao MySQL
mysql -h 187.49.226.10 -P 3306 -u f137049_tool -p f137049_in9aut

# Executar SQL
source migrations/create_table_rotas_SIMPLES.sql

# Ou copiar e colar o conteúdo diretamente
```

**Ou via phpMyAdmin:**
1. Acesse phpMyAdmin no cPanel
2. Selecione o banco `f137049_in9aut`
3. Clique em "SQL"
4. Cole o conteúdo de `migrations/create_table_rotas_SIMPLES.sql`
5. Execute

---

## ✅ Verificar Deploy

### 1. Testar Arquivos PHP no cPanel

```bash
# Testar get-rota.php (deve retornar erro 400 sem ID)
curl https://floripa.in9automacao.com.br/get-rota.php

# Testar update-rota-status.php (deve retornar erro 400 sem body)
curl -X POST https://floripa.in9automacao.com.br/update-rota-status.php
```

### 2. Testar Proxy WhatsApp no VPS

```bash
# Health check
curl http://31.97.169.36:3001/health
# Deve retornar: {"status":"ok","service":"whatsapp-proxy"}

# Ver logs
ssh root@31.97.169.36
pm2 logs whatsapp-proxy
```

### 3. Teste Completo (End-to-End)

No navegador, abra:
```
http://localhost:5000/test-whatsapp-envio.html
```

Ou use curl:
```bash
curl -X POST http://localhost:5000/enviar-rota-whatsapp \
  -H "Content-Type: application/json" \
  -d '{"rota_id": 1, "telefone": "5527999999999"}'
```

---

## 🔧 Configuração de Firewall (se necessário)

### No VPS (31.97.169.36)

```bash
# Permitir porta 3001
ufw allow 3001/tcp

# Ou com iptables
iptables -A INPUT -p tcp --dport 3001 -j ACCEPT
```

---

## 📊 Gerenciamento em Produção

### Ver logs do proxy WhatsApp
```bash
ssh root@31.97.169.36
pm2 logs whatsapp-proxy
```

### Reiniciar proxy
```bash
ssh root@31.97.169.36
pm2 restart whatsapp-proxy
```

### Ver status
```bash
ssh root@31.97.169.36
pm2 status
```

### Atualizar código
```bash
# 1. Atualizar arquivo no VPS
scp enviar-whatsapp-proxy.js root@31.97.169.36:/root/frotas-whatsapp-proxy/

# 2. Reiniciar
ssh root@31.97.169.36
pm2 reload whatsapp-proxy
```

---

## 🐛 Troubleshooting

### Erro: "socket hang up"
**Causa:** Proxy WhatsApp não está rodando no VPS
**Solução:**
```bash
ssh root@31.97.169.36
pm2 restart whatsapp-proxy
```

### Erro: 404 ao buscar rota
**Causa:** Arquivo `get-rota.php` não foi enviado ao cPanel
**Solução:** Fazer upload do arquivo para `public_html/`

### Erro: "Request failed with status code 500"
**Causa:** Tabela `FF_Rotas` não existe
**Solução:** Criar tabela usando `create_table_rotas_SIMPLES.sql`

### Evolution API não responde
**Causa:** Túnel SSH não está ativo
**Solução:**
```bash
# No servidor da Evolution API
ssh -R 60010:10.0.2.12:60010 root@31.97.169.36 -N -f
```

---

## 📝 Checklist de Deploy

- [ ] Upload de `get-rota.php` para cPanel
- [ ] Upload de `update-rota-status.php` para cPanel
- [ ] Upload de `enviar-rota-whatsapp.php` para cPanel
- [ ] Criar tabela `FF_Rotas` no banco
- [ ] Deploy de `enviar-whatsapp-proxy.js` no VPS
- [ ] Instalar dependências no VPS (`npm install`)
- [ ] Configurar PM2 no VPS
- [ ] Configurar túnel SSH para Evolution API
- [ ] Testar health check: `curl http://31.97.169.36:3001/health`
- [ ] Testar envio completo via interface

---

## 🎯 URLs Finais de Produção

```
Frontend:           https://floripa.in9automacao.com.br
APIs PHP:           https://floripa.in9automacao.com.br/*.php
Proxy WhatsApp:     http://31.97.169.36:3001
Evolution API:      http://localhost:60010 (no VPS via túnel)
```

---

## 📞 Suporte

Se encontrar problemas:

1. Verificar logs: `pm2 logs whatsapp-proxy`
2. Verificar conexão: `curl http://31.97.169.36:3001/health`
3. Verificar banco de dados: Testar conexão MySQL
4. Verificar túnel SSH: `ss -tlnp | grep 60010`
