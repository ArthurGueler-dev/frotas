# API de Quilometragem - Documentação

Sistema refatorado de gerenciamento de quilometragem integrado com API Ituran.

## Arquitetura

O sistema foi completamente refatorado com uma arquitetura em camadas:

```
┌─────────────────────────────────────┐
│      API REST (server.js)          │  ← Endpoints HTTP
├─────────────────────────────────────┤
│   MileageManager                    │  ← Lógica de negócio
│   (services/mileage-manager.js)     │
├─────────────────────────────────────┤
│   IturanMileageService              │  ← Processamento de dados
│   (services/ituran-mileage-service) │
├─────────────────────────────────────┤
│   IturanAPIClient                   │  ← Comunicação HTTP
│   (services/ituran-api-client.js)   │
├─────────────────────────────────────┤
│   Database (database-improved.js)   │  ← Persistência (MySQL)
└─────────────────────────────────────┘
```

## Melhorias Implementadas

### 1. Separação de Responsabilidades
- **IturanAPIClient**: Apenas requisições HTTP
- **IturanMileageService**: Processamento e validação de dados
- **MileageManager**: Lógica de negócio e coordenação
- **Database**: Operações de banco de dados

### 2. Conversão de Unidades Consistente
- Normalização automática de metros para KM
- Detecção inteligente do formato retornado pela API
- Validações de valores inválidos

### 3. Validações Robustas
- Coordenadas GPS validadas
- Quilometragem negativa tratada
- Valores suspeitos alertados

### 4. Gestão de Períodos Longos
- Divisão automática de períodos > 3 dias
- Chunks de 2.5 dias (seguro)
- Retry e logging detalhado

### 5. Database Otimizado
- Índices adicionados
- Queries otimizadas
- Funções de estatísticas

## Endpoints da API

### Novos Endpoints (v2)

#### 1. Atualizar Quilometragem de um Veículo

```http
POST /api/v2/mileage/update/:plate
Content-Type: application/json

{
  "date": "2025-01-15"  // Opcional, padrão = hoje
}
```

**Resposta:**
```json
{
  "success": true,
  "plate": "ABC1234",
  "date": "2025-01-15",
  "kmInicial": 50000,
  "kmFinal": 50150,
  "kmRodados": 150,
  "tempoIgnicao": 120
}
```

#### 2. Atualizar Múltiplos Veículos

```http
POST /api/v2/mileage/update-multiple
Content-Type: application/json

{
  "plates": ["ABC1234", "DEF5678", "GHI9012"],
  "date": "2025-01-15"
}
```

**Resposta:**
```json
{
  "success": true,
  "total": 3,
  "successCount": 3,
  "failCount": 0,
  "results": [...]
}
```

#### 3. Buscar Quilometragem Diária

```http
GET /api/v2/mileage/daily/:plate/:date
```

**Exemplo:**
```http
GET /api/v2/mileage/daily/ABC1234/2025-01-15
```

#### 4. Buscar Quilometragem de Período

```http
GET /api/v2/mileage/period/:plate?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD
```

**Exemplo:**
```http
GET /api/v2/mileage/period/ABC1234?startDate=2025-01-01&endDate=2025-01-15
```

**Resposta:**
```json
{
  "success": true,
  "plate": "ABC1234",
  "startDate": "2025-01-01",
  "endDate": "2025-01-15",
  "totalKm": 2500,
  "totalDays": 15,
  "avgKmPerDay": 167,
  "data": [...]
}
```

#### 5. Buscar Quilometragem Mensal

```http
GET /api/v2/mileage/monthly/:plate/:year/:month
```

**Exemplo:**
```http
GET /api/v2/mileage/monthly/ABC1234/2025/1
```

#### 6. Buscar Estatísticas

```http
GET /api/v2/mileage/stats/:plate?period=semana|mes|ano
```

**Exemplo:**
```http
GET /api/v2/mileage/stats/ABC1234?period=mes
```

#### 7. Buscar Totais da Frota

```http
GET /api/v2/mileage/fleet-daily/:date
```

**Exemplo:**
```http
GET /api/v2/mileage/fleet-daily/2025-01-15
```

#### 8. Sincronizar Dados Faltantes

```http
POST /api/v2/mileage/sync/:plate
Content-Type: application/json

{
  "startDate": "2025-01-01",
  "endDate": "2025-01-15"
}
```

**Resposta:**
```json
{
  "success": true,
  "plate": "ABC1234",
  "missingDates": 5,
  "syncedDates": 5
}
```

## Uso Programático (Node.js)

```javascript
const { mileageService } = require('./services/index');

// Atualizar quilometragem de um veículo
const result = await mileageService.updateDailyMileage('ABC1234', '2025-01-15');

// Atualizar múltiplos veículos
const results = await mileageService.updateMultipleVehicles(
  ['ABC1234', 'DEF5678'],
  '2025-01-15'
);

// Buscar período
const period = await mileageService.getPeriodMileage(
  'ABC1234',
  '2025-01-01',
  '2025-01-15'
);

// Buscar estatísticas
const stats = await mileageService.getStatistics('ABC1234', 'mes');

// Sincronizar dados faltantes
const sync = await mileageService.syncMissingData(
  'ABC1234',
  '2025-01-01',
  '2025-01-15'
);
```

## Estrutura do Banco de Dados

### Tabela: quilometragem_diaria
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- placa (VARCHAR(10), NOT NULL)
- data (DATE, NOT NULL)
- ano (INT, NOT NULL)
- mes (INT, NOT NULL)
- dia (INT, NOT NULL)
- km_inicial (DECIMAL(10,2))
- km_final (DECIMAL(10,2))
- km_rodados (DECIMAL(10,2))
- tempo_ignicao_minutos (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

UNIQUE KEY: (placa, data)
INDEX: placa, data, (ano, mes), (placa, ano, mes)
```

### Tabela: quilometragem_mensal
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- placa (VARCHAR(10), NOT NULL)
- ano (INT, NOT NULL)
- mes (INT, NOT NULL)
- km_total (DECIMAL(10,2))
- dias_rodados (INT)
- tempo_ignicao_total_minutos (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

UNIQUE KEY: (placa, ano, mes)
INDEX: placa, (ano, mes)
```

### Tabela: quilometragem_frota_diaria
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- data (DATE, UNIQUE, NOT NULL)
- ano (INT, NOT NULL)
- mes (INT, NOT NULL)
- dia (INT, NOT NULL)
- km_total (DECIMAL(10,2))
- total_veiculos (INT)
- veiculos_em_movimento (INT)
- tempo_ignicao_total_minutos (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

INDEX: data, (ano, mes)
```

## APIs do Ituran Utilizadas

### Service3.asmx (Principal)

1. **GetFullReport**
   - Busca registros GPS completos
   - Limitado a ~3 dias por requisição
   - Retorna: coordenadas, velocidade, odômetro (em metros)

2. **GetPlatformData**
   - Busca dados atuais de um veículo
   - Retorna: odômetro atual, localização, status

3. **GetAllPlatformsData**
   - Lista todos os veículos
   - Retorna: placa, modelo, odômetro (em metros se ShowMileageInMeters=true)

## Tratamento de Erros

Todos os métodos retornam um objeto com:
```javascript
{
  success: boolean,
  error?: string,
  message?: string,
  // ... outros dados
}
```

Erros comuns:
- `Timeout`: API Ituran demorou mais que 120s
- `API ReturnCode: ERROR`: Erro retornado pela API Ituran
- `Veículo não encontrado`: Placa inválida ou sem dados
- `Sem dados disponíveis`: Período sem registros GPS

## Conversão de Unidades

A API Ituran pode retornar odômetro em:
- **Metros**: GetFullReport, GetAllPlatformsData (com ShowMileageInMeters=true)
- **Quilômetros**: GetPlatformData

O sistema detecta automaticamente:
```javascript
// Se valor > 1.000.000, converte de metros para KM
if (value >= 1000000) {
  return Math.floor(value / 1000);
}
// Caso contrário, já está em KM
return Math.floor(value);
```

## Migração do Código Antigo

### Antes:
```javascript
const quilometragemAPI = require('./quilometragem-api');
const result = await quilometragemAPI.atualizarDaIturan(placa, data);
```

### Depois:
```javascript
const { mileageService } = require('./services/index');
const result = await mileageService.updateDailyMileage(placa, data);
```

## Compatibilidade

As rotas antigas ainda funcionam para compatibilidade:
- `/api/quilometragem/diaria/:placa/:data` → usa novo sistema
- `/api/quilometragem/atualizar/:placa` → usa novo sistema
- `/api/quilometragem/estatisticas/:placa` → usa novo sistema

## Testes

Execute o script de teste:
```bash
node test-mileage-refactored.js
```

## Logs

O sistema gera logs detalhados:
```
📊 GetFullReport - ABC1234 (2025-01-01 - 2025-01-15)
   Dividindo em 6 requisições
   📡 Chunk 1/6...
      ✅ 450 registros
   ...
✅ GetFullReport - 2700 pontos GPS válidos retornados
```

## Performance

- **Requisições em paralelo**: Não implementado (API Ituran limita)
- **Cache**: Não implementado (dados em tempo real)
- **Throttling**: 1s entre veículos, 500ms entre chunks
- **Timeout**: 120s por requisição

## Próximos Passos

1. Implementar cache com Redis para odômetro atual (5 min)
2. Adicionar fila de processamento (Bull/BullMQ)
3. Implementar webhooks para notificações
4. Dashboard de monitoramento em tempo real
5. Relatórios automatizados (PDF/Excel)
