# 📁 Fleet Management Backend - Estrutura do Projeto

## 🌳 Árvore de Arquivos

```
fleet-backend/
│
├── 📄 app.py                      # Flask app principal + API REST
├── 📄 celery_app.py               # Entrypoint do Celery worker
├── 📄 config.py                   # Configurações (Dev/Prod)
├── 📄 models.py                   # SQLAlchemy models
├── 📄 tasks.py                    # Celery tasks assíncronas
│
├── services/
│   └── 📄 ituran_service.py       # Integração com API Ituran
│
├── scripts/
│   ├── 📄 start-dev.sh            # Iniciar desenvolvimento
│   ├── 📄 stop-dev.sh             # Parar serviços
│   └── 📄 init-db.sh              # Inicializar banco
│
├── migrations/                    # Flask-Migrate (gerado)
│   ├── versions/
│   └── alembic.ini
│
├── tests/                         # Testes unitários
│   ├── test_models.py
│   ├── test_tasks.py
│   └── test_api.py
│
├── 📄 requirements.txt            # Dependências Python
├── 📄 .env.example                # Variáveis de ambiente (template)
├── 📄 .env                        # Variáveis de ambiente (real, não commitado)
│
├── 📄 Dockerfile                  # Imagem Docker
├── 📄 docker-compose.yml          # Orquestração completa
│
├── 📄 README.md                   # Documentação principal
├── 📄 DEPLOYMENT.md               # Guia de deploy em produção
├── 📄 EXAMPLES.md                 # Exemplos de uso da API
└── 📄 PROJECT_STRUCTURE.md        # Este arquivo
```

## 📦 Componentes Principais

### 1. **app.py** - Flask Application
- API REST completa
- Endpoints para veículos, mileage, areas, jobs
- Health checks
- Error handlers
- CORS habilitado

**Endpoints**:
```
GET  /                              # Info
GET  /health                        # Health check
GET  /api/vehicles                  # Listar veículos
POST /api/vehicles                  # Criar veículo
GET  /api/vehicles/{id}             # Buscar veículo
PUT  /api/vehicles/{id}             # Atualizar veículo
GET  /api/mileage/daily             # Listar registros diários
GET  /api/mileage/summary           # Resumo de período
POST /api/jobs/calculate-mileage    # Disparar cálculo
GET  /api/jobs/status/{task_id}     # Status do job
GET  /api/jobs/sync-logs            # Logs de sincronização
GET  /api/areas                     # Listar áreas
POST /api/areas                     # Criar área
```

### 2. **models.py** - Database Models

#### Vehicle
```python
- id: Integer (PK)
- plate: String(20) UNIQUE
- brand: String(100)
- model: String(100)
- year: Integer
- area_id: Integer (FK -> areas.id)
- is_active: Boolean (default True)
- created_at: DateTime
- updated_at: DateTime
```

#### DailyMileage
```python
- id: Integer (PK)
- vehicle_id: Integer (FK -> vehicles.id)
- date: Date (Index)
- km_driven: Float
- start_odometer: Float
- end_odometer: Float
- calculation_method: String(50)  # mobile_api | full_report
- data_source: String(50)
- record_count: Integer
- calculation_status: String(20)  # pending | success | error
- error_message: Text
- retry_count: Integer
- created_at: DateTime
- updated_at: DateTime

Índices:
- (vehicle_id, date) UNIQUE
- (calculation_status, date)
```

#### Area
```python
- id: Integer (PK)
- name: String(100)
- geo_entity_id: Integer  # Ituran GeoEntityId
- created_at: DateTime
```

#### SyncLog
```python
- id: Integer (PK)
- task_id: String(100)
- task_name: String(100)
- started_at: DateTime (Index)
- finished_at: DateTime
- status: String(20)  # running | success | failed
- vehicles_processed: Integer
- vehicles_success: Integer
- vehicles_failed: Integer
- error_message: Text
```

### 3. **tasks.py** - Celery Tasks

#### calculate_vehicle_mileage(vehicle_id, target_date)
- Calcula KM de um veículo específico
- Retry automático (max 3)
- Salva em DailyMileage
- Retorna resultado

#### calculate_daily_mileage_all(target_date)
- Processa TODOS os veículos ativos
- Cria SyncLog
- Atualiza progresso em tempo real
- Retorna estatísticas

#### cleanup_old_data()
- Remove registros > DATA_RETENTION_YEARS
- Roda mensalmente (dia 1, 03:00)
- Limpa DailyMileage e SyncLog

#### recalculate_failed_records()
- Recalcula registros com erro (últimos 7 dias)
- Max 5 tentativas por registro
- Roda diariamente (04:00)

### 4. **services/ituran_service.py** - API Integration

#### IturanService Class

**Métodos principais**:
```python
get_daily_km(plate, date, area_id) -> Dict
  ├─> _get_daily_km_mobile_api()     # Prioridade 1
  └─> _get_daily_km_full_report()    # Fallback

_get_daily_km_mobile_api()
  └─> GetDailyVehicleDistance (MobileService)

_get_daily_km_full_report()
  ├─> GetFullReport (Service3)
  ├─> GetFullReportWithFilters (com area_id)
  └─> _get_km_with_chunking() (se >3 dias)

_fetch_full_report() # Requisição SOAP
_parse_full_report_xml() # Parse resposta
_calculate_km_from_records() # Calcula KM de odômetros
```

**Lógica**:
1. Tenta GetDailyVehicleDistance (rápido, direto)
2. Se falhar, usa GetFullReport (cálculo por odômetro)
3. Normaliza metros/km (>1.000.000 = metros)
4. Trata zeros, negativos, chunks
5. Retorna resultado padronizado

### 5. **config.py** - Configuration

**Variáveis principais**:
```python
# Flask
SECRET_KEY, DEBUG

# Database
SQLALCHEMY_DATABASE_URI

# Redis/Celery
REDIS_URL, CELERY_BROKER_URL, CELERY_RESULT_BACKEND

# Ituran
ITURAN_USERNAME, ITURAN_PASSWORD
ITURAN_SERVICE3_URL, ITURAN_MOBILE_URL

# Cache
CACHE_TIMEOUT_DAILY (5 min)
CACHE_TIMEOUT_MONTHLY (24h)

# Retenção
DATA_RETENTION_YEARS (5)

# Schedule
SYNC_TIMES ['06:00', '12:00', '18:00', '23:59']

# Timezone
TIMEZONE ('America/Sao_Paulo')
```

## 🔄 Fluxo de Dados

### Cálculo de Quilometragem

```
1. User/Schedule triggers
   ↓
2. Celery Task: calculate_daily_mileage_all
   ↓
3. For each active vehicle:
   │
   ├─> Celery Task: calculate_vehicle_mileage
   │   ↓
   │   └─> IturanService.get_daily_km()
   │       │
   │       ├─> Try GetDailyVehicleDistance (MobileService)
   │       │   ✅ Success → Return km_driven
   │       │   ❌ Fail → Fallback
   │       │
   │       └─> GetFullReport (Service3)
   │           ├─> Fetch records
   │           ├─> Parse XML
   │           ├─> Calculate: end_odo - start_odo
   │           └─> Return km_driven
   │
   └─> Save to DailyMileage table
       ├─> calculation_status: success | error
       ├─> km_driven
       ├─> odometers
       └─> metadata

4. Update SyncLog with statistics
5. Return result to user/scheduler
```

### Agendamento Automático (Celery Beat)

```
Celery Beat Scheduler
│
├─> 06:00 → calculate_daily_mileage_all(yesterday)
├─> 12:00 → calculate_daily_mileage_all(yesterday)
├─> 18:00 → calculate_daily_mileage_all(yesterday)
├─> 23:59 → calculate_daily_mileage_all(yesterday)
├─> 00:05 → calculate_daily_mileage_all(yesterday)
├─> 04:00 → recalculate_failed_records()
└─> Monthly (dia 1, 03:00) → cleanup_old_data()
```

## 🧪 Testes

### Estrutura de Testes (a implementar)

```python
tests/
├── test_models.py
│   ├── test_vehicle_creation
│   ├── test_daily_mileage_unique_constraint
│   └── test_relationships
│
├── test_tasks.py
│   ├── test_calculate_vehicle_mileage
│   ├── test_calculate_daily_mileage_all
│   ├── test_retry_logic
│   └── test_cleanup_old_data
│
├── test_api.py
│   ├── test_create_vehicle
│   ├── test_get_mileage_summary
│   ├── test_trigger_calculation
│   └── test_job_status
│
└── test_ituran_service.py
    ├── test_get_daily_km_mobile_api
    ├── test_full_report_fallback
    ├── test_chunking
    └── test_error_handling
```

### Executar Testes

```bash
# Todos os testes
pytest

# Com coverage
pytest --cov=. --cov-report=html

# Teste específico
pytest tests/test_tasks.py::test_calculate_vehicle_mileage

# Com verbose
pytest -v
```

## 🐳 Docker

### Serviços

```yaml
api:           # Flask API (porta 5001)
celery-worker: # Worker para tasks
celery-beat:   # Scheduler
flower:        # Monitoring (porta 5555)
redis:         # Broker + Backend
db:            # PostgreSQL
```

### Comandos Docker

```bash
# Build e start
docker-compose up -d

# Logs
docker-compose logs -f api
docker-compose logs -f celery-worker

# Restart serviço
docker-compose restart celery-worker

# Executar comando no container
docker-compose exec api flask db upgrade

# Parar tudo
docker-compose down

# Limpar volumes (CUIDADO!)
docker-compose down -v
```

## 📊 Monitoramento

### Flower Dashboard
- URL: http://localhost:5555
- Monitoramento de tasks em tempo real
- Histórico de execuções
- Workers status

### Logs
```bash
# Flask API
docker-compose logs -f api

# Celery Worker
docker-compose logs -f celery-worker

# Celery Beat
docker-compose logs -f celery-beat

# Todos
docker-compose logs -f
```

### Health Checks
```bash
# API
curl http://localhost:5001/health

# Redis
docker-compose exec redis redis-cli ping

# PostgreSQL
docker-compose exec db pg_isready -U fleet_user
```

## 🔐 Segurança

### Variáveis Sensíveis (.env)
```bash
SECRET_KEY=***
DATABASE_URL=postgresql://user:SENHA@host/db
ITURAN_PASSWORD=***
```

### Não Commitar
```
.env
*.pyc
__pycache__/
.pytest_cache/
.coverage
htmlcov/
*.db
migrations/ (opcional)
.dev_pids
```

### Produção
- Usar HTTPS (Nginx + Let's Encrypt)
- Firewall (UFW)
- Secrets management (Docker secrets)
- Rate limiting
- Authentication/Authorization (JWT)

## 📈 Performance

### Database
- Índices em `daily_mileage(vehicle_id, date)`
- Índices em `daily_mileage(calculation_status, date)`
- Connection pooling

### Celery
- Concurrency: 4-8 workers (ajustar por CPU cores)
- Prefetch: 1 (evita memory issues)
- Max tasks per child: 1000

### Redis
- Max memory: 1GB
- Eviction policy: allkeys-lru
- Persistence: AOF + RDB

### Caching
- Daily mileage: 5 minutos
- Monthly data: 24 horas
- Evitar recálculo desnecessário

## 🚀 Próximas Features

### Implementar
- [ ] Authentication (JWT)
- [ ] Webhooks para notificações
- [ ] GraphQL API
- [ ] Exports (CSV, Excel)
- [ ] Dashboard web (React/Vue)
- [ ] Mobile app integration
- [ ] Alertas configuráveis
- [ ] Relatórios customizáveis
- [ ] Integração com outros rastreadores

### Melhorias
- [ ] Testes unitários completos
- [ ] CI/CD pipeline
- [ ] Kubernetes deployment
- [ ] Prometheus metrics
- [ ] Sentry error tracking
- [ ] Rate limiting
- [ ] API versioning

---

**Última atualização**: 18/01/2025
**Versão**: 1.0.0
