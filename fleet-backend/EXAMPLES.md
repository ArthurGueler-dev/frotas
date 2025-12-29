# 📖 Exemplos de Uso - Fleet Management API

Exemplos práticos de integração e uso da API.

## 🚀 Primeiros Passos

### 1. Criar Área Geográfica

```bash
curl -X POST http://localhost:5001/api/areas \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Vitória - ES",
    "geo_entity_id": 123
  }'

# Response:
{
  "id": 1,
  "name": "Vitória - ES",
  "geo_entity_id": 123,
  "created_at": "2025-01-18T10:30:00"
}
```

### 2. Cadastrar Veículos

```bash
# Veículo 1
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

# Veículo 2
curl -X POST http://localhost:5001/api/vehicles \
  -H "Content-Type: application/json" \
  -d '{
    "plate": "XYZ5678",
    "brand": "Honda",
    "model": "Civic",
    "year": 2022,
    "area_id": 1,
    "is_active": true
  }'
```

### 3. Disparar Cálculo de Quilometragem

```bash
# Calcular para todos os veículos (dia anterior)
curl -X POST http://localhost:5001/api/jobs/calculate-mileage \
  -H "Content-Type: application/json" \
  -d '{}'

# Response:
{
  "success": true,
  "task_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "status_url": "/api/jobs/status/a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}

# Calcular para veículo específico em data específica
curl -X POST http://localhost:5001/api/jobs/calculate-mileage \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_id": 1,
    "date": "2025-01-15"
  }'
```

### 4. Monitorar Progresso

```bash
# Verificar status do job
TASK_ID="a1b2c3d4-e5f6-7890-abcd-ef1234567890"
curl http://localhost:5001/api/jobs/status/$TASK_ID

# Response durante execução:
{
  "task_id": "a1b2c3d4-...",
  "status": "PROGRESS",
  "ready": false,
  "progress": {
    "current": 5,
    "total": 10,
    "vehicle": "ABC1234",
    "success": 4,
    "failed": 1
  }
}

# Response quando concluído:
{
  "task_id": "a1b2c3d4-...",
  "status": "SUCCESS",
  "ready": true,
  "result": {
    "success": true,
    "total": 10,
    "success_count": 9,
    "failed_count": 1,
    "results": [...]
  }
}
```

## 📊 Consultar Dados

### 1. Listar Registros Diários

```bash
# Todos os registros
curl "http://localhost:5001/api/mileage/daily"

# Filtrar por veículo
curl "http://localhost:5001/api/mileage/daily?vehicle_id=1"

# Filtrar por período
curl "http://localhost:5001/api/mileage/daily?start_date=2025-01-01&end_date=2025-01-31"

# Filtrar por status
curl "http://localhost:5001/api/mileage/daily?status=success"

# Paginação
curl "http://localhost:5001/api/mileage/daily?page=2&per_page=20"

# Response:
{
  "items": [
    {
      "id": 1,
      "vehicle_id": 1,
      "vehicle_plate": "ABC1234",
      "date": "2025-01-15",
      "km_driven": 145.5,
      "start_odometer": 45320.0,
      "end_odometer": 45465.5,
      "calculation_method": "mobile_api",
      "data_source": "GetDailyVehicleDistance",
      "record_count": 1,
      "calculation_status": "success",
      "error_message": null,
      "retry_count": 0,
      "created_at": "2025-01-16T01:05:00",
      "updated_at": "2025-01-16T01:05:00"
    }
  ],
  "total": 150,
  "page": 1,
  "per_page": 50,
  "pages": 3
}
```

### 2. Resumo de Quilometragem

```bash
# Resumo mensal de um veículo
curl "http://localhost:5001/api/mileage/summary?vehicle_id=1&start_date=2025-01-01&end_date=2025-01-31"

# Response:
{
  "vehicle": {
    "id": 1,
    "plate": "ABC1234",
    "brand": "Toyota",
    "model": "Corolla"
  },
  "period": {
    "start": "2025-01-01",
    "end": "2025-01-31"
  },
  "summary": {
    "total_km": 3250.5,
    "average_km_per_day": 104.8,
    "total_days": 31
  },
  "daily_records": [
    {
      "date": "2025-01-01",
      "km_driven": 120.5
    },
    {
      "date": "2025-01-02",
      "km_driven": 95.0
    }
    // ...
  ]
}
```

### 3. Ver Logs de Sincronização

```bash
# Últimos 20 logs
curl "http://localhost:5001/api/jobs/sync-logs"

# Response:
{
  "items": [
    {
      "id": 1,
      "task_id": "a1b2c3d4-...",
      "task_name": "calculate_daily_mileage_all",
      "started_at": "2025-01-16T06:00:00",
      "finished_at": "2025-01-16T06:05:32",
      "duration_seconds": 332,
      "status": "success",
      "vehicles_processed": 10,
      "vehicles_success": 9,
      "vehicles_failed": 1,
      "error_message": null
    }
  ],
  "total": 45,
  "page": 1,
  "per_page": 20,
  "pages": 3
}
```

## 🔄 Integração com Frontend

### React Example

```javascript
// services/fleetApi.js
import axios from 'axios';

const API_BASE_URL = 'http://localhost:5001/api';

class FleetAPI {
  // Calcular KM
  async calculateMileage(vehicleId = null, date = null) {
    const response = await axios.post(`${API_BASE_URL}/jobs/calculate-mileage`, {
      vehicle_id: vehicleId,
      date: date
    });
    return response.data;
  }

  // Verificar status
  async getJobStatus(taskId) {
    const response = await axios.get(`${API_BASE_URL}/jobs/status/${taskId}`);
    return response.data;
  }

  // Polling até completar
  async waitForJobCompletion(taskId, intervalMs = 2000) {
    return new Promise((resolve, reject) => {
      const interval = setInterval(async () => {
        try {
          const status = await this.getJobStatus(taskId);

          if (status.ready) {
            clearInterval(interval);
            if (status.status === 'SUCCESS') {
              resolve(status.result);
            } else {
              reject(new Error(status.error));
            }
          }
        } catch (error) {
          clearInterval(interval);
          reject(error);
        }
      }, intervalMs);
    });
  }

  // Buscar resumo
  async getMileageSummary(vehicleId, startDate, endDate) {
    const response = await axios.get(`${API_BASE_URL}/mileage/summary`, {
      params: {
        vehicle_id: vehicleId,
        start_date: startDate,
        end_date: endDate
      }
    });
    return response.data;
  }

  // Listar registros diários
  async getDailyMileage(filters = {}) {
    const response = await axios.get(`${API_BASE_URL}/mileage/daily`, {
      params: filters
    });
    return response.data;
  }
}

export const fleetApi = new FleetAPI();
```

```jsx
// components/MileageCalculator.jsx
import React, { useState } from 'react';
import { fleetApi } from '../services/fleetApi';

function MileageCalculator({ vehicleId }) {
  const [loading, setLoading] = useState(false);
  const [progress, setProgress] = useState(null);
  const [result, setResult] = useState(null);

  const handleCalculate = async () => {
    setLoading(true);
    setResult(null);

    try {
      // Disparar cálculo
      const { task_id } = await fleetApi.calculateMileage(vehicleId);

      // Polling de progresso
      const interval = setInterval(async () => {
        const status = await fleetApi.getJobStatus(task_id);

        if (status.status === 'PROGRESS') {
          setProgress(status.progress);
        }

        if (status.ready) {
          clearInterval(interval);
          setProgress(null);

          if (status.status === 'SUCCESS') {
            setResult(status.result);
          } else {
            alert(`Erro: ${status.error}`);
          }

          setLoading(false);
        }
      }, 2000);

    } catch (error) {
      console.error('Error calculating mileage:', error);
      alert('Erro ao calcular quilometragem');
      setLoading(false);
    }
  };

  return (
    <div>
      <button onClick={handleCalculate} disabled={loading}>
        {loading ? 'Calculando...' : 'Calcular Quilometragem'}
      </button>

      {progress && (
        <div className="progress">
          <p>Progresso: {progress.current} / {progress.total}</p>
          <p>Veículo atual: {progress.vehicle}</p>
          <p>Sucesso: {progress.success} | Falhas: {progress.failed}</p>
        </div>
      )}

      {result && (
        <div className="result">
          <h3>Resultado</h3>
          <p>Total processado: {result.total}</p>
          <p>Sucesso: {result.success_count}</p>
          <p>Falhas: {result.failed_count}</p>
        </div>
      )}
    </div>
  );
}

export default MileageCalculator;
```

### Python Client Example

```python
# fleet_client.py
import requests
import time
from typing import Optional, Dict

class FleetClient:
    def __init__(self, base_url: str = "http://localhost:5001/api"):
        self.base_url = base_url

    def calculate_mileage(
        self,
        vehicle_id: Optional[int] = None,
        date: Optional[str] = None
    ) -> Dict:
        """Trigger mileage calculation"""
        url = f"{self.base_url}/jobs/calculate-mileage"
        data = {}

        if vehicle_id:
            data['vehicle_id'] = vehicle_id
        if date:
            data['date'] = date

        response = requests.post(url, json=data)
        response.raise_for_status()
        return response.json()

    def get_job_status(self, task_id: str) -> Dict:
        """Get job status"""
        url = f"{self.base_url}/jobs/status/{task_id}"
        response = requests.get(url)
        response.raise_for_status()
        return response.json()

    def wait_for_completion(
        self,
        task_id: str,
        timeout: int = 600,
        poll_interval: int = 2
    ) -> Dict:
        """Wait for job completion with timeout"""
        start_time = time.time()

        while True:
            if time.time() - start_time > timeout:
                raise TimeoutError(f"Job {task_id} timeout after {timeout}s")

            status = self.get_job_status(task_id)

            if status['ready']:
                if status['status'] == 'SUCCESS':
                    return status['result']
                else:
                    raise Exception(f"Job failed: {status.get('error')}")

            # Show progress if available
            if status['status'] == 'PROGRESS':
                progress = status.get('progress', {})
                print(f"Progress: {progress.get('current')}/{progress.get('total')} "
                      f"- {progress.get('vehicle')}")

            time.time.sleep(poll_interval)

    def get_mileage_summary(
        self,
        vehicle_id: int,
        start_date: str,
        end_date: str
    ) -> Dict:
        """Get mileage summary"""
        url = f"{self.base_url}/mileage/summary"
        params = {
            'vehicle_id': vehicle_id,
            'start_date': start_date,
            'end_date': end_date
        }
        response = requests.get(url, params=params)
        response.raise_for_status()
        return response.json()

# Usage example
if __name__ == '__main__':
    client = FleetClient()

    # Calculate mileage for all vehicles
    print("Starting calculation...")
    job = client.calculate_mileage()
    print(f"Job ID: {job['task_id']}")

    # Wait for completion
    result = client.wait_for_completion(job['task_id'])
    print(f"Done! {result['success_count']} vehicles processed successfully")

    # Get summary for vehicle 1
    summary = client.get_mileage_summary(
        vehicle_id=1,
        start_date='2025-01-01',
        end_date='2025-01-31'
    )
    print(f"Total KM: {summary['summary']['total_km']}")
```

## 🔔 Webhooks (Feature futura)

Para notificações assíncronas quando o cálculo terminar:

```python
# No seu servidor que vai receber
@app.route('/webhooks/mileage-completed', methods=['POST'])
def mileage_webhook():
    data = request.get_json()

    print(f"Mileage calculation completed!")
    print(f"Total vehicles: {data['total']}")
    print(f"Success: {data['success_count']}")
    print(f"Failed: {data['failed_count']}")

    # Processar resultado...

    return {'status': 'received'}, 200
```

## 📈 Dashboard Integration

```javascript
// Dashboard com atualização em tempo real
import React, { useEffect, useState } from 'react';
import { fleetApi } from '../services/fleetApi';

function DashboardStats() {
  const [stats, setStats] = useState(null);

  useEffect(() => {
    const fetchStats = async () => {
      // Buscar veículos
      const vehicles = await fleetApi.getVehicles();

      // Buscar KM do mês para cada veículo
      const today = new Date();
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

      const promises = vehicles.map(v =>
        fleetApi.getMileageSummary(
          v.id,
          firstDay.toISOString().split('T')[0],
          today.toISOString().split('T')[0]
        )
      );

      const summaries = await Promise.all(promises);

      // Calcular totais
      const totalKm = summaries.reduce((sum, s) =>
        sum + s.summary.total_km, 0
      );

      setStats({
        totalVehicles: vehicles.length,
        totalKmMonth: totalKm,
        averageKmPerVehicle: totalKm / vehicles.length
      });
    };

    fetchStats();

    // Atualizar a cada 5 minutos
    const interval = setInterval(fetchStats, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  if (!stats) return <div>Carregando...</div>;

  return (
    <div className="dashboard-stats">
      <div className="stat-card">
        <h3>Veículos Ativos</h3>
        <p className="value">{stats.totalVehicles}</p>
      </div>
      <div className="stat-card">
        <h3>KM Total (Mês)</h3>
        <p className="value">{stats.totalKmMonth.toFixed(2)} km</p>
      </div>
      <div className="stat-card">
        <h3>Média por Veículo</h3>
        <p className="value">{stats.averageKmPerVehicle.toFixed(2)} km</p>
      </div>
    </div>
  );
}
```

## 🎯 Casos de Uso Comuns

### 1. Relatório Mensal Automático

```bash
#!/bin/bash
# generate-monthly-report.sh

MONTH=$(date +%Y-%m)
START_DATE="${MONTH}-01"
END_DATE=$(date -d "${START_DATE} +1 month -1 day" +%Y-%m-%d)

# Para cada veículo
for VEHICLE_ID in $(curl -s http://localhost:5001/api/vehicles | jq -r '.[].id'); do
    echo "Gerando relatório para veículo $VEHICLE_ID..."

    curl -s "http://localhost:5001/api/mileage/summary?vehicle_id=$VEHICLE_ID&start_date=$START_DATE&end_date=$END_DATE" \
        | jq -r '"\(.vehicle.plate): \(.summary.total_km) km"'
done
```

### 2. Alertas de KM Alto

```python
# check_high_mileage.py
from fleet_client import FleetClient
from datetime import date

client = FleetClient()
THRESHOLD = 300  # KM por dia

# Verificar ontem
yesterday = (date.today() - timedelta(days=1)).isoformat()

records = client.get_daily_mileage(filters={
    'date': yesterday,
    'status': 'success'
})

for record in records['items']:
    if record['km_driven'] > THRESHOLD:
        print(f"⚠️ ALERTA: {record['vehicle_plate']} rodou "
              f"{record['km_driven']} km em {yesterday}")
        # Enviar email/SMS/Slack...
```

### 3. Sincronização Programada com Sistema Externo

```python
# sync_to_erp.py
from fleet_client import FleetClient
import requests

fleet_client = FleetClient()
ERP_API_URL = "https://erp.empresa.com/api/frotas/km"

# Buscar KM do dia anterior
yesterday = (date.today() - timedelta(days=1)).isoformat()

records = fleet_client.get_daily_mileage(filters={
    'date': yesterday,
    'status': 'success'
})

# Enviar para ERP
for record in records['items']:
    erp_data = {
        'placa': record['vehicle_plate'],
        'data': record['date'],
        'km': record['km_driven']
    }

    response = requests.post(ERP_API_URL, json=erp_data)
    print(f"Sincronizado {record['vehicle_plate']}: {response.status_code}")
```

---

**Mais exemplos e dúvidas**: suporte@i9automacao.com.br
