# Sistema de Cálculo Automático de Quilometragem

Sistema completo para calcular automaticamente a quilometragem diária de veículos usando a API Ituran.

## 📋 Visão Geral

O sistema busca o odômetro dos veículos via API Ituran e calcula automaticamente os quilômetros rodados por dia usando a fórmula:

```
KM_rodados = Odômetro_hoje - Odômetro_ontem
```

### Componentes

1. **Banco de Dados**:
   - `areas` - Áreas geográficas (Barra de São Francisco, Guarapari, etc.)
   - `daily_mileage` - Registros diários de quilometragem
   - `Vehicles.area_id` - Associação de veículos às áreas

2. **Python Backend** (`fleet-backend/`):
   - `services/mileage_service.py` - Serviço principal
   - `tasks.py` - Tarefas Celery para processamento assíncrono

3. **PHP API** (`cpanel-api/`):
   - `daily-mileage-api.php` - CRUD de quilometragem
   - `areas-api.php` - Gerenciamento de áreas

4. **Automação**:
   - Celery Beat executa sincronização automática 4x ao dia

## 🚀 Como Funciona

### Fluxo de Dados

```
┌─────────────┐
│ Celery Beat │  Dispara em horários programados
│  (Schedule) │  (06:00, 12:00, 18:00, 23:59)
└──────┬──────┘
       │
       ▼
┌──────────────────────────────────────┐
│  Task: sync_all_vehicles_mileage     │
│  (tasks.py)                          │
└──────┬───────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────┐
│  MileageService                      │
│  (mileage_service.py)                │
│                                      │
│  1. Busca veículos ativos            │
│  2. Para cada veículo:               │
│     a) Busca odômetro hoje (Ituran) │
│     b) Busca odômetro ontem (Ituran)│
│     c) Calcula: hoje - ontem         │
│     d) Salva via PHP API             │
└──────┬───────────────────────────────┘
       │
       ├─────────────┬───────────────┐
       ▼             ▼               ▼
  ┌─────────┐  ┌──────────┐   ┌──────────┐
  │ Ituran  │  │ PHP API  │   │  MySQL   │
  │   API   │  │(cPanel)  │   │ Database │
  └─────────┘  └──────────┘   └──────────┘
```

### API Ituran Utilizada

**Endpoint**: `GetVehicleMileage_JSON`

```
URL: https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx/GetVehicleMileage_JSON
Método: GET
Parâmetros:
  - Plate: Placa do veículo (ex: "RTS9B92")
  - LocTime: Data no formato YYYY-MM-DD
  - UserName: api@i9tecnologia
  - Password: Api@In9Eng

Resposta (JSON dentro de XML):
{
  "ResultCode": "OK",
  "resLocTime": "2025-12-28T00:04:56",
  "resMileage": 124325.0
}
```

## 📦 Estrutura do Banco de Dados

### Tabela: `areas`

```sql
CREATE TABLE areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    state VARCHAR(2) DEFAULT 'ES',
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Áreas cadastradas**:
- Barra de São Francisco
- Guarapari
- Santa Tereza
- Castelo
- Aracruz
- Nova Venécia

### Tabela: `daily_mileage`

```sql
CREATE TABLE daily_mileage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_plate VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    area_id INT NULL,
    odometer_start DECIMAL(10,2) NULL,
    odometer_end DECIMAL(10,2) NULL,
    km_driven DECIMAL(10,2) NOT NULL DEFAULT 0,
    source ENUM('API', 'Manual') DEFAULT 'API',
    sync_status ENUM('success', 'failed', 'pending', 'manual') DEFAULT 'pending',
    error_message TEXT NULL,
    synced_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vehicle_date (vehicle_plate, date)
);
```

## 🔧 Instalação e Configuração

### 1. Criar Tabelas no Banco

Execute os scripts PHP no phpMyAdmin:

```bash
# 1. Criar tabelas
https://floripa.in9automacao.com.br/cpanel-api/create-mileage-tables.php

# 2. Inserir áreas
https://floripa.in9automacao.com.br/cpanel-api/insert-correct-areas.php

# 3. Associar veículos às áreas
https://floripa.in9automacao.com.br/cpanel-api/associate-vehicles-areas.php
```

### 2. Fazer Upload das APIs PHP

Fazer upload manual no cPanel (File Manager):

- `cpanel-api/daily-mileage-api.php`
- `cpanel-api/areas-api.php`

### 3. Instalar Dependências Python

```bash
cd fleet-backend
pip install -r requirements.txt
```

### 4. Configurar Celery

O Celery Beat já está configurado em `tasks.py` para executar:

- **06:00** - Sincronização matinal
- **12:00** - Sincronização meio-dia
- **18:00** - Sincronização tarde
- **23:59** - Sincronização final do dia

## 🧪 Testes

### Teste Completo de Integração

```bash
cd fleet-backend
python test_mileage_integration.py
```

**Fases do teste**:
1. ✅ Conexão com API Ituran
2. ✅ Cálculo de quilometragem
3. ✅ Salvamento no banco via PHP API
4. ✅ Verificação de dados no banco
5. ⏭️ Sincronização completa (opcional)

### Teste com Sincronização Completa

```bash
python test_mileage_integration.py --full-sync
```

**ATENÇÃO**: Isso processará TODOS os veículos e pode levar vários minutos.

### Teste Manual de Funções Específicas

```python
from services.mileage_service import MileageService, test_api_connection, test_single_vehicle
from datetime import datetime

# Testar API Ituran
test_api_connection()

# Testar um veículo específico
test_single_vehicle('RTS9B92')

# Testar cálculo manual
service = MileageService()
result = service.calculate_daily_mileage('RTS9B92', datetime.now())
print(result)
```

## 📊 Uso da API PHP

### GET - Listar Registros

```bash
# Todos os registros (últimos 100)
curl "https://floripa.in9automacao.com.br/cpanel-api/daily-mileage-api.php"

# Por placa
curl "https://floripa.in9automacao.com.br/cpanel-api/daily-mileage-api.php?plate=RTS9B92"

# Por área
curl "https://floripa.in9automacao.com.br/cpanel-api/daily-mileage-api.php?area_id=1"

# Por período
curl "https://floripa.in9automacao.com.br/cpanel-api/daily-mileage-api.php?date_from=2025-12-01&date_to=2025-12-31"
```

**Resposta**:
```json
{
  "success": true,
  "records": [
    {
      "id": 1,
      "vehicle_plate": "RTS9B92",
      "date": "2025-12-28",
      "area_id": 1,
      "area_name": "Barra de São Francisco",
      "odometer_start": 124000.00,
      "odometer_end": 124325.00,
      "km_driven": 325.00,
      "source": "API",
      "sync_status": "success",
      "synced_at": "2025-12-29 06:05:23"
    }
  ],
  "total": 1,
  "statistics": {
    "total_km": 325.00,
    "success_count": 1,
    "failed_count": 0,
    "avg_km_per_day": 325.00
  }
}
```

### POST - Salvar/Atualizar (UPSERT)

```bash
curl -X POST "https://floripa.in9automacao.com.br/cpanel-api/daily-mileage-api.php" \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_plate": "RTS9B92",
    "date": "2025-12-28",
    "odometer_start": 124000.00,
    "odometer_end": 124325.00,
    "km_driven": 325.00,
    "source": "API",
    "sync_status": "success",
    "synced_at": "2025-12-29 06:05:23"
  }'
```

**Resposta**:
```json
{
  "success": true,
  "message": "Quilometragem salva com sucesso",
  "id": 1,
  "vehicle_plate": "RTS9B92",
  "date": "2025-12-28",
  "km_driven": 325.00,
  "area_id": 1
}
```

**IMPORTANTE**: O sistema usa UPSERT, então se já existir um registro para a mesma placa e data, ele será atualizado ao invés de criar duplicata.

## 🔄 Execução Manual

### Sincronizar um Veículo Específico

```python
from services.mileage_service import MileageService
from datetime import datetime, timedelta

service = MileageService()

# Sincronizar para ontem (padrão)
service.sync_vehicle_mileage('RTS9B92')

# Sincronizar para data específica
target_date = datetime(2025, 12, 25)
service.sync_vehicle_mileage('RTS9B92', target_date)
```

### Sincronizar Todos os Veículos

```python
from services.mileage_service import MileageService

service = MileageService()

# Sincronizar todos para ontem
stats = service.sync_all_vehicles()
print(f"Sucesso: {stats['success']}, Falhas: {stats['failed']}")
```

### Executar Task Celery Manualmente

```python
from tasks import sync_all_vehicles_mileage

# Executar imediatamente (não esperar schedule)
result = sync_all_vehicles_mileage.delay()
print(result.get())
```

## 📈 Monitoramento

### Verificar Logs do Celery

```bash
# Ver logs em tempo real
tail -f /var/log/celery/fleet-backend.log

# Verificar erros
grep ERROR /var/log/celery/fleet-backend.log
```

### Verificar Status das Tasks

```python
from celery import Celery
from tasks import celery

# Ver tasks agendadas
i = celery.control.inspect()
print(i.scheduled())

# Ver tasks ativas
print(i.active())

# Ver estatísticas
print(i.stats())
```

### Dashboard do Celery (Flower)

```bash
# Instalar Flower
pip install flower

# Iniciar dashboard
celery -A tasks flower --port=5555

# Acessar: http://localhost:5555
```

## ⚠️ Troubleshooting

### Problema: API Ituran retorna erro

**Possíveis causas**:
- Credenciais incorretas
- Placa não existe no sistema Ituran
- Data muito antiga (sem dados)
- Timeout de rede

**Solução**:
```python
# Testar com placa conhecida
from services.mileage_service import test_api_connection
test_api_connection()
```

### Problema: KM negativo

**Causa**: Odômetro pode ter sido resetado ou erro de leitura

**Comportamento**: Sistema registra como 0 km e marca warning nos logs

### Problema: PHP API não responde

**Verificar**:
1. Arquivo foi feito upload no cPanel?
2. Permissões do arquivo (deve ser 644)
3. Conexão com banco de dados

```bash
# Testar API diretamente
curl "https://floripa.in9automacao.com.br/cpanel-api/daily-mileage-api.php?limit=1"
```

### Problema: Celery não executa tasks

**Verificar**:
1. Celery worker está rodando?
2. Celery Beat está rodando?
3. Redis está acessível?

```bash
# Verificar processos
ps aux | grep celery

# Reiniciar Celery
supervisorctl restart celery-worker
supervisorctl restart celery-beat
```

## 📚 Próximos Passos

- [ ] Criar API de relatórios por área e período
- [ ] Implementar frontend para visualização
- [ ] Adicionar alertas para anomalias (KM muito alto/baixo)
- [ ] Implementar correção manual de dados
- [ ] Cache Redis para otimização

## 📞 Suporte

Para problemas ou dúvidas, consulte:
- `claude.md` - Documentação do projeto
- Logs do Celery em `/var/log/celery/`
- Logs do Python em `fleet-backend/logs/`
