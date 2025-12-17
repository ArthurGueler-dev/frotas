# ✅ Sistema Configurado em Produção

## 🎉 Deploy Concluído com Sucesso!

Todas as configurações de produção foram aplicadas e o sistema está pronto para uso.

---

## 📊 Status Atual

### ✅ Arquivos PHP no cPanel
- `get-rota.php` → https://floripa.in9automacao.com.br/get-rota.php
- `update-rota-status.php` → https://floripa.in9automacao.com.br/update-rota-status.php
- `enviar-rota-whatsapp.php` → https://floripa.in9automacao.com.br/enviar-rota-whatsapp.php

### ✅ Proxy WhatsApp no VPS
- **Servidor:** 31.97.169.36:3001
- **Status:** Online e rodando com PM2
- **Health Check:** http://31.97.169.36:3001/health
- **Gerenciado por:** PM2 (auto-restart habilitado)

### ✅ Banco de Dados
- **Tabela FF_Rotas:** Criada e funcionando
- **Servidor MySQL:** 187.49.226.10:3306

### ✅ Server.js Atualizado
- Configurado para usar proxy de produção no VPS
- URL do proxy: `http://31.97.169.36:3001/enviar-rota-whatsapp`

---

## 🔄 Fluxo Completo de Produção

```
1. Usuário clica "Enviar WhatsApp" no otimizador de blocos
   ↓
2. Frontend envia POST para: http://localhost:5000/enviar-rota-whatsapp
   ↓
3. Server.js faz proxy para: http://31.97.169.36:3001/enviar-rota-whatsapp
   ↓
4. Proxy no VPS:
   - Busca rota via: https://floripa.in9automacao.com.br/get-rota.php
   - Formata mensagem WhatsApp
   - Envia para Evolution API: http://localhost:60010 (via túnel)
   - Atualiza status via: https://floripa.in9automacao.com.br/update-rota-status.php
   ↓
5. Mensagem entregue no WhatsApp ✅
```

---

## 🧪 Como Testar

### Teste Rápido - Health Check

```bash
# Testar proxy no VPS
curl http://31.97.169.36:3001/health
# Deve retornar: {"status":"ok","service":"whatsapp-proxy"}
```

### Teste Completo - Envio de Rota

1. **Reinicie o servidor local** para aplicar as mudanças:
   ```bash
   # Parar servidor atual
   stop-servers.bat

   # Iniciar novamente
   start-servers.bat
   ```

2. **Abra o otimizador de blocos:**
   ```
   http://localhost:5000/otimizador-blocos.html
   ```

3. **Envie uma rota:**
   - Clique em "📱 Enviar por WhatsApp" em um bloco
   - Digite o telefone (ex: 5527999999999)
   - Clique em "Enviar"

4. **Verifique os logs:**
   ```bash
   # No VPS
   ssh root@31.97.169.36
   pm2 logs whatsapp-proxy --lines 50
   ```

---

## 📱 Gerenciamento do Proxy no VPS

### Comandos Úteis

```bash
# Conectar ao VPS
ssh root@31.97.169.36

# Ver status
pm2 status

# Ver logs em tempo real
pm2 logs whatsapp-proxy

# Reiniciar proxy
pm2 restart whatsapp-proxy

# Parar proxy
pm2 stop whatsapp-proxy

# Iniciar proxy
pm2 start whatsapp-proxy

# Ver informações detalhadas
pm2 info whatsapp-proxy
```

### Atualizar Código do Proxy

Se precisar atualizar o código:

```bash
# Do seu computador
scp enviar-whatsapp-proxy.js root@31.97.169.36:/root/frotas-whatsapp-proxy/

# No VPS
ssh root@31.97.169.36
pm2 reload whatsapp-proxy
```

---

## 🔍 Troubleshooting

### Erro: "socket hang up"
**Causa:** Proxy no VPS está offline
**Solução:**
```bash
ssh root@31.97.169.36
pm2 restart whatsapp-proxy
pm2 logs whatsapp-proxy
```

### Erro: 404 ao buscar rota
**Causa:** Arquivo get-rota.php não existe ou caminho incorreto
**Solução:**
```bash
# Testar se arquivo existe
curl https://floripa.in9automacao.com.br/get-rota.php?id=1
```

### Erro: Evolution API não responde
**Causa:** Túnel SSH não está ativo
**Solução:**
```bash
# No servidor onde está a Evolution API
ssh -R 60010:10.0.2.12:60010 root@31.97.169.36 -N -f
```

### Proxy não aceita conexões externas
**Causa:** Firewall bloqueando porta 3001
**Solução:**
```bash
ssh root@31.97.169.36
ufw allow 3001/tcp
# ou
iptables -A INPUT -p tcp --dport 3001 -j ACCEPT
```

---

## 📋 Checklist de Verificação

- [x] Arquivos PHP no cPanel
- [x] Tabela FF_Rotas criada
- [x] Proxy WhatsApp no VPS
- [x] PM2 configurado
- [x] Server.js atualizado
- [x] Health check funcionando
- [ ] Teste de envio completo

---

## 🎯 Próximos Passos

1. **Reinicie o servidor local** (`stop-servers.bat` + `start-servers.bat`)
2. **Teste o envio** no otimizador de blocos
3. **Verifique os logs** no VPS: `pm2 logs whatsapp-proxy`

---

## 🔐 Informações de Acesso

### VPS
- **Host:** 31.97.169.36
- **Usuário:** root
- **Diretório:** /root/frotas-whatsapp-proxy/

### MySQL
- **Host:** 187.49.226.10:3306
- **Banco:** f137049_in9aut
- **Usuário:** f137049_tool

### Evolution API
- **URL Local (VPS):** http://localhost:60010
- **Instância:** Thiago Costa
- **API Key:** [configurada no código]

---

## 📞 Suporte

Para problemas ou dúvidas:
1. Verifique os logs: `pm2 logs whatsapp-proxy`
2. Teste o health check: `curl http://31.97.169.36:3001/health`
3. Verifique se o túnel SSH está ativo: `ss -tlnp | grep 60010`

---

**Data de Deploy:** $(date)
**Versão:** 1.0.0
**Status:** ✅ Produção
