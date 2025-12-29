# 🚀 Quick Start - Fleet Backend System

Guia rápido para colocar o sistema em funcionamento.

## 📋 Pré-requisitos

- Docker e Docker Compose instalados
- Acesso ao banco MySQL existente (187.49.226.10)
- Credenciais da API Ituran

## ⚡ Setup em 5 Passos

### 1. Criar Tabelas no Banco de Dados

Acesse phpMyAdmin do cPanel:
- URL: https://floripa.in9automacao.com.br/cpanel
- Vá para phpMyAdmin
- Selecione o banco `f137049_in9aut`
- Clique em "SQL"
- Cole o conteúdo do arquivo `schema.sql`
- Clique em "Executar"

Ou via terminal:
```bash
cd fleet-backend
mysql -h 187.49.226.10 -u f137049_tool -p f137049_in9aut < schema.sql
# Senha: In9@1234qwer
```

### 2. Verificar Arquivo .env

O arquivo `.env` já foi criado com a configuração do banco existente:
```bash
cat .env
# Deve mostrar DATABASE_URL com 187.49.226.10
```

### 3. Iniciar Stack com Docker

```bash
cd fleet-backend
docker-compose up -d
```

Isso iniciará:
- ✅ Flask API (porta 5001)
- ✅ Celery Worker (background tasks)
- ✅ Celery Beat (scheduler)
- ✅ Redis (broker)
- ✅ Flower (monitoring, porta 5555)

Nota: O MySQL não será iniciado via Docker pois estamos usando o banco existente.

### 4. Testar API

```bash
# Health check
curl http://localhost:5001/health

# Deve retornar:
{
  "status": "healthy",
  "database": "connected",
  "redis": "connected",
  "celery": "running"
}
```

### 5. Verificar Celery Beat Schedule

Acesse Flower para ver as tarefas agendadas:
- URL: http://localhost:5555
- Veja as tarefas programadas para 06:00, 12:00, 18:00, 23:59

---

## 🎯 Testar Cálculo de Quilometragem

### Método 1: Via API (Manual)

```bash
# Disparar cálculo de TODOS os veículos (ontem)
curl -X POST http://localhost:5001/api/jobs/calculate-mileage \
  -H "Content-Type: application/json"

# Resposta:
{
  "success": true,
  "task_id": "abc-123-xyz",
  "status_url": "/api/jobs/status/abc-123-xyz"
}

# Verificar status do job
curl http://localhost:5001/api/jobs/status/abc-123-xyz
```

### Método 2: Via Celery (Background)

O Celery Beat executará automaticamente nos horários:
- 06:00 - Calcula KM do dia anterior
- 12:00 - Recalcula (caso tenha falhado)
- 18:00 - Recalcula
- 23:59 - Última execução do dia
- 00:05 - Cálculo final do dia anterior

### Método 3: Testar Veículo Específico

```bash
# Criar um veículo de teste primeiro
curl -X POST http://localhost:5001/api/vehicles \
  -H "Content-Type: application/json" \
  -d '{
    "plate": "ABC1234",
    "brand": "Toyota",
    "model": "Corolla",
    "year": 2023,
    "area_id": 1,
    "is_active": true
  }'

# Calcular KM para este veículo
curl -X POST http://localhost:5001/api/jobs/calculate-mileage \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_id": 1,
    "date": "2025-12-17"
  }'
```

---

## 📊 Consultar Resultados

### Via API

```bash
# Listar registros de quilometragem diária
curl "http://localhost:5001/api/mileage/daily?start_date=2025-12-01&end_date=2025-12-18"

# Resumo de um veículo específico
curl "http://localhost:5001/api/mileage/summary?vehicle_id=1&start_date=2025-12-01&end_date=2025-12-18"

# Ver logs de sincronização
curl "http://localhost:5001/api/jobs/sync-logs?limit=10"
```

### Via phpMyAdmin

```sql
-- Ver últimos cálculos
SELECT
  v.plate,
  dm.date,
  dm.km_driven,
  dm.calculation_status,
  dm.calculation_method,
  dm.created_at
FROM daily_mileage dm
JOIN vehicles v ON v.id = dm.vehicle_id
ORDER BY dm.date DESC, v.plate
LIMIT 50;

-- Estatísticas de sucesso/erro
SELECT
  calculation_status,
  COUNT(*) as total,
  ROUND(AVG(km_driven), 2) as avg_km
FROM daily_mileage
WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY calculation_status;

-- Últimas execuções do Celery
SELECT * FROM sync_logs
ORDER BY started_at DESC
LIMIT 10;
```

---

## 🔍 Monitoramento

### Flower Dashboard
- URL: http://localhost:5555
- Veja tasks em execução
- Histórico de execuções
- Workers status
- Estatísticas

### Logs em Tempo Real

```bash
# API Flask
docker-compose logs -f api

# Celery Worker
docker-compose logs -f celery-worker

# Celery Beat (scheduler)
docker-compose logs -f celery-beat

# Todos os serviços
docker-compose logs -f
```

---

## 🛠️ Comandos Úteis

### Parar Tudo
```bash
docker-compose down
```

### Reiniciar Serviço Específico
```bash
docker-compose restart celery-worker
docker-compose restart api
```

### Ver Status dos Containers
```bash
docker-compose ps
```

### Executar Comando Dentro do Container
```bash
# Python shell
docker-compose exec api python

# Flask shell
docker-compose exec api flask shell
```

### Forçar Rebuild (se mudou código)
```bash
docker-compose down
docker-compose up -d --build
```

---

## 🐛 Troubleshooting

### Problema: API não conecta no banco

```bash
# Testar conexão MySQL
mysql -h 187.49.226.10 -u f137049_tool -p
# Senha: In9@1234qwer

# Ver logs da API
docker-compose logs api

# Verificar .env
cat .env | grep DATABASE_URL
```

### Problema: Celery não está executando

```bash
# Ver logs do worker
docker-compose logs celery-worker

# Ver logs do beat
docker-compose logs celery-beat

# Reiniciar Celery
docker-compose restart celery-worker celery-beat
```

### Problema: Cálculo retorna erro

```bash
# Ver detalhes do erro no banco
SELECT * FROM daily_mileage
WHERE calculation_status = 'error'
ORDER BY created_at DESC
LIMIT 10;

# Ver logs de sync
SELECT * FROM sync_logs
WHERE status = 'failed'
ORDER BY started_at DESC;

# Forçar recálculo dos erros
curl -X POST http://localhost:5001/api/jobs/calculate-mileage
```

---

## 📱 Integração com Frontend

### Exemplo: Buscar KM do Dashboard

```javascript
// No seu dashboard.js
async function getVehicleMileage(vehicleId, startDate, endDate) {
  const response = await fetch(
    `http://localhost:5001/api/mileage/summary?` +
    `vehicle_id=${vehicleId}&` +
    `start_date=${startDate}&` +
    `end_date=${endDate}`
  );
  const data = await response.json();
  return data.total_km;
}

// Usar na tabela de veículos
const kmOntem = await getVehicleMileage(1, '2025-12-17', '2025-12-17');
console.log(`KM rodados ontem: ${kmOntem}`);
```

### Exemplo: Disparar Cálculo ao Clicar Botão

```javascript
// Botão "Atualizar KM" no frontend
async function triggerKmCalculation() {
  const response = await fetch(
    'http://localhost:5001/api/jobs/calculate-mileage',
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    }
  );
  const result = await response.json();

  console.log(`Task iniciada: ${result.task_id}`);

  // Monitorar progresso
  const statusUrl = `http://localhost:5001${result.status_url}`;
  const interval = setInterval(async () => {
    const status = await fetch(statusUrl).then(r => r.json());
    console.log(`Progresso: ${status.current}/${status.total}`);

    if (status.state === 'SUCCESS') {
      clearInterval(interval);
      console.log('Cálculo concluído!');
      // Atualizar tabela
      refreshVehicleTable();
    }
  }, 2000);
}
```

---

## ✅ Próximos Passos

1. ✅ Criar tabelas no banco (schema.sql)
2. ✅ Iniciar Docker Compose
3. ✅ Testar API health check
4. ✅ Disparar primeiro cálculo manual
5. ✅ Verificar resultados no phpMyAdmin
6. ✅ Monitorar Flower
7. ⏳ Integrar com frontend existente
8. ⏳ Configurar deploy em produção
9. ⏳ Adicionar autenticação (JWT)
10. ⏳ Configurar alertas e notificações

---

**Sistema pronto para uso!** 🎉

Para mais detalhes, consulte:
- `README.md` - Documentação completa
- `MYSQL_SETUP.md` - Guia detalhado de MySQL
- `DEPLOYMENT.md` - Deploy em produção
- `EXAMPLES.md` - Mais exemplos de integração
