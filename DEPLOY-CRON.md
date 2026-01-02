# 🚀 Guia de Deploy - Sincronização Automática via Cron

## 📋 O que foi criado

1. **sync-mileage-cron.js** - Script que sincroniza a quilometragem
2. **setup-cron.sh** - Script que configura os cron jobs automaticamente

## 🎯 Como funciona

- O **cron job** roda automaticamente no servidor VPS
- **NÃO depende** de ninguém estar no site
- Executa nos horários programados:
  - **08:00** - Início do expediente
  - **12:00** - Meio-dia
  - **18:00** - Final do expediente
  - **23:55** - Sincronização principal do dia

## 📦 Passo a Passo do Deploy

### 1️⃣ Fazer upload dos arquivos para o VPS

```bash
# No seu PC (PowerShell ou Git Bash)
cd C:\Users\SAMSUNG\Desktop\frotas

# Upload dos arquivos
scp sync-mileage-cron.js root@31.97.169.36:/root/frotas/
scp setup-cron.sh root@31.97.169.36:/root/frotas/
```

### 2️⃣ Conectar no VPS

```bash
ssh root@31.97.169.36
```

### 3️⃣ Dar permissão de execução

```bash
cd /root/frotas
chmod +x sync-mileage-cron.js
chmod +x setup-cron.sh
```

### 4️⃣ Configurar os cron jobs

```bash
bash setup-cron.sh
```

**Saída esperada:**
```
🔧 Configurando cron jobs de sincronização de quilometragem...
✅ Cron jobs configurados com sucesso!

📅 Horários programados:
   • 08:00 - Início do expediente
   • 12:00 - Meio-dia
   • 18:00 - Final do expediente
   • 23:55 - Sincronização principal do dia
```

### 5️⃣ Testar manualmente (IMPORTANTE!)

```bash
# Testar o script antes de esperar o cron
node sync-mileage-cron.js
```

**Saída esperada:**
```
🤖 ════════════════════════════════════════════════════
🤖 SINCRONIZAÇÃO AUTOMÁTICA DE QUILOMETRAGEM (CRON)
🤖 ════════════════════════════════════════════════════
📅 Data alvo: 2025-12-30
🔄 Chamando API: http://localhost:5000/api/mileage/sync
✅ Sincronização concluída com sucesso!
   Total de veículos: 78
   Sucessos: 71
   Falhas: 7
   Tempo total: 156s
📊 Total de KM sincronizados: 5234.50 km
🤖 ════════════════════════════════════════════════════
```

## 📊 Verificar logs

### Ver logs em tempo real
```bash
tail -f /root/frotas/logs/sync-cron.log
```

### Ver últimas 50 linhas
```bash
tail -50 /root/frotas/logs/sync-cron.log
```

### Ver cron jobs instalados
```bash
crontab -l
```

## 🔧 Manutenção

### Desabilitar temporariamente
```bash
# Comentar as linhas do cron
crontab -e
# Adicionar # no início das linhas de sync-mileage-cron.js
```

### Remover completamente
```bash
crontab -e
# Deletar as linhas de sync-mileage-cron.js
```

### Alterar horários
```bash
crontab -e
# Editar os horários conforme necessário
```

**Formato do cron:**
```
# Minuto Hora Dia Mês Dia-da-semana Comando
#    0     8    *   *       *         cd /root/frotas && node sync-mileage-cron.js
#    │     │    │   │       │
#    │     │    │   │       └─ Dia da semana (0-7, 0=domingo)
#    │     │    │   └───────── Mês (1-12)
#    │     │    └───────────── Dia do mês (1-31)
#    │     └────────────────── Hora (0-23)
#    └──────────────────────── Minuto (0-59)
```

## ⚠️ Troubleshooting

### Cron não está executando

1. Verificar se o cron service está rodando:
```bash
systemctl status cron
# ou
systemctl status crond
```

2. Verificar permissões:
```bash
ls -la /root/frotas/sync-mileage-cron.js
# Deve ter permissão de execução (x)
```

3. Verificar se o Node.js está no PATH:
```bash
which node
# Resultado esperado: /usr/bin/node ou similar
```

### Logs não aparecem

1. Criar diretório de logs:
```bash
mkdir -p /root/frotas/logs
chmod 755 /root/frotas/logs
```

2. Verificar permissões:
```bash
ls -ld /root/frotas/logs
```

### API não responde

1. Verificar se o servidor Node.js está rodando:
```bash
pm2 status
```

2. Se não estiver, iniciar:
```bash
pm2 start server.js --name frotas
pm2 save
```

## 📌 Notas Importantes

1. **Servidor deve estar rodando** - O script chama a API local (localhost:5000)
2. **PM2 recomendado** - Garante que o servidor sempre esteja ativo
3. **Logs rotativos** - Configure logrotate para não encher o disco
4. **Monitoramento** - Verifique os logs periodicamente

## 🎯 Próximo Deploy (produção final)

Antes de colocar em produção:

1. ✅ Testar script manualmente (node sync-mileage-cron.js)
2. ✅ Verificar logs após cada teste
3. ✅ Aguardar primeiro cron job automático (08:00 do dia seguinte)
4. ✅ Confirmar que funcionou verificando o banco de dados
5. ✅ Remover horário de teste (16:35) do dashboard-stats.js

---

**Criado em:** 30/12/2025
**Versão:** 1.0
**Autor:** Sistema i9 Engenharia
