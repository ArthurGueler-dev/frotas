# 🚗 Fleet Management Backend API

Sistema backend completo para gerenciamento de frotas com cálculo automático de quilometragem via API Ituran.

## 📋 Features

- ✅ **Integração inteligente com API Ituran**
  - Prioriza `GetDailyVehicleDistance` (MobileService) - retorno direto de KM
  - Fallback automático para `GetFullReport` (Service3) com cálculo por odômetro
  - Normalização metros/km, chunking para períodos longos, tratamento de erros

- ✅ **Cálculo assíncrono de quilometragem**
  - Celery + Redis para processamento em background
  - Tasks automáticas em horários configuráveis
  - Retry inteligente de falhas (max 3 tentativas)

- ✅ **Agendamento automático (Celery Beat)**
  - Sincronização nos horários: 06:00, 12:00, 18:00, 23:59
  - Cálculo diário à meia-noite do dia anterior
  - Recálculo de registros falhados (04:00)
  - Limpeza de dados antigos (mensal)

- ✅ **API REST completa**
  - CRUD de veículos e áreas
  - Consulta de quilometragem diária/resumida
  - Status de jobs assíncronos
  - Logs de sincronização

- ✅ **Banco de dados robusto**
  - PostgreSQL/SQLite via SQLAlchemy
  - Migrations com Flask-Migrate
  - Índices otimizados para performance
  - Retenção configurável (padrão: 5 anos)

## 🏗️ Arquitetura

```
┌─────────────────────────────────────────────────┐
│  Flask API (Port 5001)                          │
│  - REST endpoints                               │
│  - Job triggers                                 │
└────────────┬────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────┐
│  Celery Workers + Beat Scheduler                │
│  - calculate_vehicle_mileage                    │
│  - calculate_daily_mileage_all                  │
│  - cleanup_old_data                             │
│  - recalculate_failed_records                   │
└────────────┬────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────┐
│  Redis (Broker + Result Backend)                │
└─────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────┐
│  Ituran API                                     │
│  - MobileService (GetDailyVehicleDistance)      │
│  - Service3 (GetFullReport)                     │
└─────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────┐
│  PostgreSQL Database                            │
│  - vehicles, areas                              │
│  - daily_mileage                                │
│  - sync_logs                                    │
└─────────────────────────────────────────────────┘
```

## 🚀 Quick Start

### 1. Requisitos

- Python 3.11+
- PostgreSQL 14+ (ou SQLite para dev)
- Redis 6+

### 2. Instalação

```bash
# Clone ou copie os arquivos
cd fleet-backend

# Crie virtual environment
python -m venv venv
source venv/bin/activate  # Linux/Mac
# ou
venv\Scripts\activate  # Windows

# Instale dependências
pip install -r requirements.txt

# Configure variáveis de ambiente
cp .env.example .env
# Edite .env com suas credenciais
```

### 3. Configuração do Banco de Dados

```bash
# Inicialize migrations
flask db init

# Crie migrations
flask db migrate -m "Initial migration"

# Aplique migrations
flask db upgrade
```

### 4. Inicie os Serviços

#### Terminal 1: Flask API
```bash
python app.py
# API rodando em http://localhost:5001
```

#### Terminal 2: Celery Worker
```bash
celery -A celery_app worker --loglevel=info
```

#### Terminal 3: Celery Beat (Scheduler)
```bash
celery -A celery_app beat --loglevel=info
```

#### Opcional: Flower (Monitoramento Celery)
```bash
celery -A celery_app flower
# Dashboard em http://localhost:5555
```

## 📚 API Endpoints

### Veículos

```bash
# Listar todos
GET /api/vehicles

# Buscar por ID
GET /api/vehicles/{id}

# Criar novo
POST /api/vehicles
{
  "plate": "ABC1234",
  "brand": "Toyota",
  "model": "Corolla",
  "year": 2023,
  "area_id": 1,
  "is_active": true
}

# Atualizar
PUT /api/vehicles/{id}
{
  "plate": "ABC1234",
  "is_active": false
}
```

### Quilometragem

```bash
# Listar registros diários
GET /api/mileage/daily?vehicle_id=1&start_date=2025-01-01&end_date=2025-01-31

# Resumo de período
GET /api/mileage/summary?vehicle_id=1&start_date=2025-01-01&end_date=2025-01-31
```

### Jobs/Tasks

```bash
# Disparar cálculo (todos os veículos, ontem)
POST /api/jobs/calculate-mileage
{}

# Disparar para veículo específico
POST /api/jobs/calculate-mileage
{
  "vehicle_id": 1,
  "date": "2025-01-15"
}

# Verificar status do job
GET /api/jobs/status/{task_id}

# Ver logs de sincronização
GET /api/jobs/sync-logs
```

### Áreas

```bash
# Listar áreas
GET /api/areas

# Criar área
POST /api/areas
{
  "name": "Vitória",
  "geo_entity_id": 123
}
```

## ⚙️ Configuração

### Variáveis de Ambiente (.env)

```bash
# Flask
FLASK_ENV=production
SECRET_KEY=your-secret-key-here

# Database
DATABASE_URL=postgresql://user:pass@localhost:5432/fleet_db

# Redis
REDIS_URL=redis://localhost:6379/0

# Ituran API
ITURAN_USERNAME=your-username
ITURAN_PASSWORD=your-password
ITURAN_SERVICE3_URL=https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx?WSDL
ITURAN_MOBILE_URL=https://iweb.ituran.com.br/ituranmobileservice/mobileservice.asmx?WSDL

# Agendamento
CELERY_BEAT_SCHEDULE_TIMES=06:00,12:00,18:00,23:59

# Retenção de dados
DATA_RETENTION_YEARS=5

# Timezone
TIMEZONE=America/Sao_Paulo
```

## 📊 Models

### Vehicle
- `id`, `plate`, `brand`, `model`, `year`
- `area_id` (FK), `is_active`
- Timestamps: `created_at`, `updated_at`

### DailyMileage
- `id`, `vehicle_id` (FK), `date`
- `km_driven`, `start_odometer`, `end_odometer`
- `calculation_method` ('mobile_api' | 'full_report')
- `calculation_status` ('pending' | 'success' | 'error')
- `error_message`, `retry_count`
- Índices: `(vehicle_id, date)` único

### Area
- `id`, `name`, `geo_entity_id`

### SyncLog
- `id`, `task_id`, `task_name`
- `started_at`, `finished_at`, `status`
- `vehicles_processed`, `vehicles_success`, `vehicles_failed`

## 🔄 Celery Tasks

### calculate_vehicle_mileage(vehicle_id, target_date)
- Calcula KM de um veículo específico
- Retry automático (max 3)
- Usa cache se já calculado

### calculate_daily_mileage_all(target_date)
- Calcula KM de TODOS os veículos ativos
- Progresso em tempo real
- Gera log de sincronização

### cleanup_old_data()
- Remove registros > 5 anos (configurável)
- Roda mensalmente (dia 1, 03:00)

### recalculate_failed_records()
- Recalcula registros com erro dos últimos 7 dias
- Max 5 tentativas por registro
- Roda diariamente (04:00)

## 📅 Schedule (Celery Beat)

```python
# Sincronizações diárias
06:00 - calculate_daily_mileage_all()
12:00 - calculate_daily_mileage_all()
18:00 - calculate_daily_mileage_all()
23:59 - calculate_daily_mileage_all()

# Meia-noite - calcular dia anterior
00:05 - calculate_daily_mileage_all()

# Recalcular falhas
04:00 - recalculate_failed_records()

# Limpeza mensal
Dia 1, 03:00 - cleanup_old_data()
```

## 🧪 Testing

```bash
# Testes unitários
pytest tests/

# Com coverage
pytest --cov=. tests/

# Testar manualmente
curl http://localhost:5001/health
curl http://localhost:5001/api/vehicles
```

## 📈 Monitoring

### Flower (Celery)
```bash
celery -A celery_app flower
# http://localhost:5555
```

### Health Check
```bash
curl http://localhost:5001/health
```

### Logs
- Flask: stdout/stderr
- Celery: `celery -A celery_app worker -l info`
- Database: SQLAlchemy logs (quando DEBUG=True)

## 🐳 Docker (Opcional)

```dockerfile
# Dockerfile
FROM python:3.11-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
CMD ["gunicorn", "-b", "0.0.0.0:5001", "app:app"]
```

```yaml
# docker-compose.yml
version: '3.8'
services:
  api:
    build: .
    ports:
      - "5001:5001"
    env_file: .env
    depends_on:
      - db
      - redis

  celery-worker:
    build: .
    command: celery -A celery_app worker --loglevel=info
    env_file: .env
    depends_on:
      - redis

  celery-beat:
    build: .
    command: celery -A celery_app beat --loglevel=info
    env_file: .env
    depends_on:
      - redis

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  db:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: fleet_db
      POSTGRES_USER: fleet_user
      POSTGRES_PASSWORD: fleet_pass
    volumes:
      - postgres_data:/var/lib/postgresql/data

volumes:
  postgres_data:
```

## 🔧 Troubleshooting

### Problema: Task não executa
```bash
# Verificar worker está rodando
celery -A celery_app inspect active

# Verificar conexão Redis
redis-cli ping

# Ver logs detalhados
celery -A celery_app worker -l debug
```

### Problema: Erro de conexão com Ituran
- Verificar credenciais no .env
- Testar URLs das APIs manualmente
- Verificar firewall/proxy

### Problema: Banco de dados travado
```bash
# Verificar conexões PostgreSQL
SELECT * FROM pg_stat_activity;

# Matar conexões travadas
SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'fleet_db';
```

## 📝 Licença

Proprietário - i9 Automação

## 👥 Suporte

Para dúvidas ou problemas, contate: suporte@i9automacao.com.br
