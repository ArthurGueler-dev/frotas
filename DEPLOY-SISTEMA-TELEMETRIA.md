# 🚀 Guia de Deploy - Sistema de Telemetria Automática

Este guia contém todas as instruções para fazer deploy do novo sistema de sincronização automática de quilometragem com telemetria diária.

---

## 📋 Resumo das Mudanças

### ✅ O que foi implementado:

1. **Tabela Telemetria_Diaria** - Armazena KM diária de cada veículo
2. **API PHP no cPanel** - Interface para salvar/consultar telemetria
3. **Web Worker** - Sincronização em background que persiste ao trocar de aba
4. **Cron Job às 23:59** - Calcula KM automaticamente no fim do dia
5. **Detecção de Lacunas** - Preenche automaticamente dias faltantes (últimos 30 dias)
6. **Filtros de Data** - Dropdown com presets + date pickers customizados
7. **Script Histórico** - Preenche dados de períodos passados manualmente

---

## 📝 Passo 1: Criar Tabela no phpMyAdmin

### 1.1 Acessar phpMyAdmin
- Acesse: https://floripa.in9automacao.com.br:2083 (ou URL do cPanel)
- Login com suas credenciais
- Navegue até **phpMyAdmin**

### 1.2 Executar Script SQL
- Selecione o banco de dados do sistema (ex: `f137049_in9aut`)
- Clique na aba **SQL**
- Copie e cole o conteúdo do arquivo `database-telemetria-melhorada.sql`
- **IMPORTANTE**: Execute APENAS a seção de CREATE TABLE (linhas 4-107)
- Clique em **Executar**

### 1.3 Verificar Criação
Execute esta query para verificar:
```sql
SHOW TABLES LIKE 'Telemetria_Diaria';
```

Deve retornar a tabela. Depois verifique a estrutura:
```sql
DESCRIBE Telemetria_Diaria;
```

---

## 📁 Passo 2: Upload da API PHP para o cPanel

### 2.1 Arquivos a fazer upload via FTP/File Manager

Faça upload dos seguintes arquivos para a pasta `public_html/cpanel-api/`:

```
cpanel-api/
└── telemetria-diaria-api.php    (NOVO)
```

### 2.2 Verificar Permissões
- Permissões dos arquivos PHP: `644`
- Permissões da pasta cpanel-api: `755`

### 2.3 Testar API
Acesse no navegador:
```
https://floripa.in9automacao.com.br/cpanel-api/telemetria-diaria-api.php
```

Deve retornar algo como:
```json
{"error": "Método não permitido"}
```
Isso é esperado (GET sem parâmetros).

Para testar POST, use o Postman ou curl:
```bash
curl -X POST https://floripa.in9automacao.com.br/cpanel-api/telemetria-diaria-api.php \
  -H "Content-Type: application/json" \
  -d '{
    "licensePlate": "ABC1234",
    "date": "2025-12-04",
    "kmInicial": 10000,
    "kmFinal": 10050,
    "kmRodado": 50
  }'
```

Deve retornar:
```json
{"success": true, "message": "Telemetria criado com sucesso"}
```

---

## 🖥️ Passo 3: Deploy do Sistema Node.js

### 3.1 Arquivos Modificados

Os seguintes arquivos foram modificados/criados:

**Novos:**
- `sync-worker.js` - Web Worker para sincronização
- `fill-historical-km.js` - Script de preenchimento histórico
- `cpanel-api/telemetria-diaria-api.php` - API PHP

**Modificados:**
- `server.js` - Proxies para API PHP, cron às 23:59
- `dashboard-stats.js` - Integração com Web Worker
- `cron-update-km.js` - Calcula KM de hoje + detecta lacunas
- `novo_dashboard.html` - Dropdown de presets + date pickers
- `novo-dashboard.js` - Lógica de filtros de data

### 3.2 Deploy via Git (Recomendado)

```bash
# No servidor (via SSH)
cd /root/frotas  # ou caminho do projeto

# Fazer backup antes
cp -r . ../frotas_backup_$(date +%Y%m%d)

# Pull das mudanças
git pull origin main

# Instalar dependências (se houver novas)
npm install

# Reiniciar servidor
pm2 restart frotas
# OU
node server.js
```

### 3.3 Deploy Manual (via FTP)

Se não usa Git, faça upload dos arquivos via FTP:

1. Conecte-se via FileZilla ou similar
2. Navegue até `/root/frotas/` (ou pasta do projeto)
3. Faça backup da pasta atual
4. Faça upload dos arquivos modificados

---

## ⏰ Passo 4: Configurar Cron Job

O cron job já está configurado no código (`server.js`), mas você pode verificar:

### 4.1 Verificar Cron no Código
No `server.js`, linha ~4972, deve ter:
```javascript
cron.schedule('59 23 * * *', async () => {
    // Executa às 23:59 todos os dias
});
```

### 4.2 Logs do Cron
Os logs aparecerão no console do servidor:
```
⏰ [CRON] Iniciando atualização automática de quilometragem (23:59)...
📅 Salvando dados de HOJE no banco: 2025-12-04
✅ [CRON] Atualização de quilometragem concluída com sucesso!
```

### 4.3 Testar Cron Manualmente
Para testar antes das 23:59:
```bash
node cron-update-km.js
```

---

## 🔄 Passo 5: Sincronização Inicial

### 5.1 Preencher Dados Históricos (Opcional)

Se quiser preencher dados de dias anteriores:

```bash
# Preencher últimos 30 dias
node fill-historical-km.js 2025-11-04 2025-12-03

# Ou preencher mês específico
node fill-historical-km.js 2025-11-01 2025-11-30
```

**Notas:**
- Máximo 90 dias por execução
- Pausa de 5 segundos entre dias
- Pode demorar várias horas
- O script pede confirmação antes de iniciar

### 5.2 Sincronização Manual via Dashboard

1. Acesse o dashboard: https://floripa.in9automacao.com.br/
2. Clique em **"Sincronizar KM"**
3. Aguarde a barra de progresso completar
4. Pode trocar de aba - a sincronização continua em background!

---

## 🧪 Passo 6: Testes

### 6.1 Testar Filtro de Data

1. Acesse: https://floripa.in9automacao.com.br/novo_dashboard.html
2. Selecione dropdown: **"Últimos 7 dias"**
3. Verifique se mostra dados dos últimos 7 dias
4. Selecione: **"Período customizado"**
5. Date pickers devem aparecer
6. Selecione 01/12/2025 até 03/12/2025
7. Clique **"Aplicar"**
8. Tabela deve mostrar dados agrupados por data

### 6.2 Verificar Dados no Banco

```sql
-- Total de registros
SELECT COUNT(*) FROM Telemetria_Diaria;

-- Registros de hoje
SELECT COUNT(*) FROM Telemetria_Diaria WHERE data = CURDATE();

-- Últimos 7 dias
SELECT data, COUNT(*) as veiculos, SUM(km_rodado) as km_total
FROM Telemetria_Diaria
WHERE data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY data
ORDER BY data DESC;

-- Top 10 veículos do mês
SELECT LicensePlate, SUM(km_rodado) as km_total
FROM Telemetria_Diaria
WHERE YEAR(data) = YEAR(CURDATE()) AND MONTH(data) = MONTH(CURDATE())
GROUP BY LicensePlate
ORDER BY km_total DESC
LIMIT 10;
```

### 6.3 Testar Web Worker

1. Abra dashboard no Chrome
2. Abra DevTools (F12) → Console
3. Clique "Sincronizar KM"
4. Aguarde 20% de progresso
5. Troque para outra aba (Google, por exemplo)
6. Aguarde 1 minuto
7. Volte para a aba do dashboard
8. **Resultado esperado**: Progresso deve continuar de onde parou

Se ver no console:
```
🔄 Aba visível novamente, verificando progresso...
🔄 Retomando sincronização do veículo X/Y
```
✅ Web Worker está funcionando!

---

## 📊 Passo 7: Monitoramento

### 7.1 Logs do Servidor

Os logs aparecem no console do Node.js:

**Inicialização:**
```
🔧 Verificando/criando tabela Telemetria_Diaria...
✅ Tabela Telemetria_Diaria criada/verificada com sucesso
⏰ Cron job configurado: Atualização de quilometragem todos os dias às 23:59h
```

**Cron Job (23:59):**
```
⏰ [CRON] Iniciando atualização automática de quilometragem (23:59)...
📅 Salvando dados de HOJE no banco: 2025-12-04
🔍 Verificando lacunas nos últimos 30 dias...
✅ Nenhuma lacuna encontrada nos últimos 30 dias
✅ [CRON] Atualização de quilometragem concluída com sucesso!
```

**Sincronização Manual:**
```
🔄 Iniciando cálculo em BACKGROUND com Web Worker (veículo 0)
📡 Tentando buscar veículos da API Ituran...
✅ 87 veículos encontrados da API
💾 ABC1234: Dados salvos no banco
✅ Sincronização completa!
```

### 7.2 Verificar Saúde do Sistema

Execute estas queries periódicamente:

```sql
-- Dias com dados nos últimos 30 dias
SELECT COUNT(DISTINCT data) as dias_com_dados
FROM Telemetria_Diaria
WHERE data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
-- Deve retornar ~30

-- Última atualização
SELECT MAX(sincronizado_em) as ultima_sync
FROM Telemetria_Diaria;
-- Deve ser recente (últimas 24h)

-- Veículos sincronizados hoje
SELECT COUNT(DISTINCT LicensePlate) as veiculos_hoje
FROM Telemetria_Diaria
WHERE data = CURDATE();
-- Deve ser próximo do total de veículos ativos
```

---

## 🔧 Troubleshooting

### Erro: "Tabela Telemetria_Diaria não existe"

**Causa:** Tabela não foi criada no phpMyAdmin
**Solução:** Execute o script SQL (Passo 1)

### Erro 500 ao salvar telemetria

**Causa:** API PHP não está acessível ou tem erro
**Solução:**
1. Verifique URL: https://floripa.in9automacao.com.br/cpanel-api/telemetria-diaria-api.php
2. Verifique permissões do arquivo (644)
3. Verifique logs do PHP no cPanel
4. Teste com curl (ver Passo 2.3)

### Cron não está executando

**Causa:** Servidor Node.js parou ou reiniciou
**Solução:**
1. Verifique se servidor está rodando: `pm2 list` ou `ps aux | grep node`
2. Reinicie: `pm2 restart frotas` ou `node server.js`
3. Verifique logs: procure por "Cron job configurado"

### Web Worker não funciona

**Causa:** Arquivo sync-worker.js não está no local correto
**Solução:**
1. Verifique se existe: `ls -la sync-worker.js`
2. Deve estar na raiz do projeto (mesma pasta que dashboard.html)
3. Permissões: `chmod 644 sync-worker.js`

### Dados não aparecem no filtro de data

**Causa:** API não está retornando dados ou formato incorreto
**Solução:**
1. Abra DevTools → Network
2. Procure requisição para `/api/telemetry/daily`
3. Verifique resposta
4. Se erro 500: verificar logs do server.js
5. Se sem dados: verificar se há registros no banco (Passo 6.2)

---

## 📈 Manutenção

### Backup Regular

```bash
# Backup da tabela Telemetria_Diaria (semanal)
mysqldump -u usuario -p database Telemetria_Diaria > backup_telemetria_$(date +%Y%m%d).sql

# Comprimir
gzip backup_telemetria_*.sql
```

### Limpeza de Dados Antigos (Opcional)

Se quiser limpar dados muito antigos (> 1 ano):

```sql
-- CUIDADO: Backup antes!
DELETE FROM Telemetria_Diaria
WHERE data < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);
```

### Otimização

```sql
-- Otimizar tabela periodicamente (mensal)
OPTIMIZE TABLE Telemetria_Diaria;

-- Verificar tamanho
SELECT
    table_name AS 'Tabela',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Tamanho (MB)'
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
  AND table_name = 'Telemetria_Diaria';
```

---

## 📞 Suporte

Em caso de problemas:

1. **Verifique logs** do servidor Node.js
2. **Verifique logs** do PHP no cPanel
3. **Execute queries** de verificação (Passo 6.2)
4. **Teste APIs** individualmente (curl/Postman)

---

## ✅ Checklist Final de Deploy

- [ ] Tabela `Telemetria_Diaria` criada no phpMyAdmin
- [ ] API PHP (`telemetria-diaria-api.php`) no cPanel e funcionando
- [ ] Código Node.js atualizado no servidor
- [ ] Servidor Node.js reiniciado
- [ ] Cron job configurado (23:59)
- [ ] Teste de sincronização manual OK
- [ ] Teste de Web Worker OK (troca de aba)
- [ ] Teste de filtros de data OK
- [ ] Dados aparecendo no banco
- [ ] Logs do cron funcionando
- [ ] Documentação lida e entendida

---

## 🎉 Conclusão

Após seguir todos os passos, o sistema estará:

- ✅ Calculando KM automaticamente às 23:59
- ✅ Salvando dados na tabela Telemetria_Diaria via API PHP
- ✅ Detectando e preenchendo lacunas automaticamente
- ✅ Permitindo consulta por data customizada
- ✅ Sincronização em background persistente (Web Worker)

**Data de implementação:** 04/12/2025
**Versão:** 2.0
