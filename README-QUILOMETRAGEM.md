# Sistema de Quilometragem Refatorado 2.0

Sistema completo de gerenciamento de quilometragem integrado com API Ituran.

## 🚀 Quick Start

### 1. Testar o Sistema

```bash
# Executar testes automatizados
node test-mileage-refactored.js
```

### 2. Iniciar o Servidor

```bash
# Iniciar servidor Node.js
node server.js

# Servidor rodará em: http://localhost:5000
```

### 3. Testar um Endpoint

```bash
# Atualizar quilometragem de um veículo
curl -X POST http://localhost:5000/api/v2/mileage/update/ABC1234 \
  -H "Content-Type: application/json" \
  -d '{"date": "2025-01-15"}'
```

## 📚 Documentação

| Arquivo | Descrição |
|---------|-----------|
| **API-QUILOMETRAGEM.md** | 📖 Documentação completa da API |
| **MIGRATION-GUIDE.md** | 🔄 Guia de migração do código antigo |
| **REFATORACAO-CONCLUIDA.md** | ✅ Resumo das alterações e melhorias |

## 🏗️ Arquitetura

```
┌─────────────────────┐
│   API REST v2       │  ← /api/v2/mileage/*
├─────────────────────┤
│  MileageManager     │  ← Lógica de negócio
├─────────────────────┤
│  IturanMileageService│  ← Processamento
├─────────────────────┤
│  IturanAPIClient    │  ← HTTP Client
├─────────────────────┤
│  MySQL Database     │  ← Persistência
└─────────────────────┘
```

## 🔧 Endpoints Principais

### Atualizar Quilometragem
```http
POST /api/v2/mileage/update/:plate
Body: { "date": "2025-01-15" }
```

### Buscar Período
```http
GET /api/v2/mileage/period/:plate?startDate=2025-01-01&endDate=2025-01-15
```

### Estatísticas
```http
GET /api/v2/mileage/stats/:plate?period=mes
```

### Sincronizar Dados Faltantes
```http
POST /api/v2/mileage/sync/:plate
Body: { "startDate": "2025-01-01", "endDate": "2025-01-15" }
```

## 💻 Uso Programático

```javascript
const { mileageService } = require('./services/index');

// Atualizar quilometragem
const result = await mileageService.updateDailyMileage('ABC1234', '2025-01-15');

// Buscar período
const period = await mileageService.getPeriodMileage(
  'ABC1234',
  '2025-01-01',
  '2025-01-15'
);

// Estatísticas do mês
const stats = await mileageService.getStatistics('ABC1234', 'mes');

// Sincronizar dados faltantes
const sync = await mileageService.syncMissingData(
  'ABC1234',
  '2025-01-01',
  '2025-01-15'
);
```

## ✨ Principais Melhorias

- ✅ **Conversão automática** metros ↔ quilômetros
- ✅ **Validações robustas** de coordenadas e quilometragem
- ✅ **Divisão automática** de períodos > 3 dias
- ✅ **Database otimizado** com índices apropriados
- ✅ **Sincronização inteligente** de dados faltantes
- ✅ **Atualização em lote** de múltiplos veículos
- ✅ **Logs detalhados** e estruturados
- ✅ **100% compatível** com código antigo

## 📊 Estrutura de Dados

### Resposta Padrão
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

### Resposta de Período
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

## ⚙️ Configuração

### Credenciais da API Ituran
Configuradas em `services/ituran-api-client.js`:
```javascript
{
  apiUrl: 'http://localhost:8888/api/ituran',
  username: 'api@i9tecnologia',
  password: 'Api@In9Eng',
  timeout: 120000  // 2 minutos
}
```

### Database MySQL
Configurado em `database-improved.js`:
```javascript
{
  host: '187.49.226.10',
  port: 3306,
  user: 'f137049_tool',
  password: 'In9@1234qwer',
  database: 'f137049_in9aut'
}
```

## 🔍 Logs

O sistema gera logs estruturados e detalhados:

```
✅ Serviços de quilometragem inicializados (Node.js)
✅ Conexão com banco de dados OK
✅ Tabelas de quilometragem verificadas/criadas

📊 GetFullReport - ABC1234 (2025-01-01 - 2025-01-15)
   Dividindo em 6 requisições
   📡 Chunk 1/6...
      ✅ 450 registros
✅ GetFullReport - 2700 pontos GPS válidos retornados

🔄 Atualizando quilometragem de ABC1234 para 2025-01-15...
   ✅ Salvo: 50000 → 50150 (150 km)
```

## 🧪 Testes

### Executar Testes
```bash
node test-mileage-refactored.js
```

### O que é testado
- ✅ Atualização de quilometragem
- ✅ Busca de dados diários
- ✅ Busca de período
- ✅ Estatísticas
- ✅ Quilometragem mensal
- ✅ Totais da frota

## 📦 Estrutura de Arquivos

```
frotas/
├── services/                          # Camada de serviços
│   ├── ituran-api-client.js          # Cliente HTTP Ituran
│   ├── ituran-mileage-service.js     # Processamento
│   ├── mileage-manager.js            # Lógica de negócio
│   └── index.js                       # Inicializador
├── database.js                        # Database otimizado
├── database.js.backup                 # Backup do original
├── server.js                          # API REST
├── test-mileage-refactored.js        # Testes
├── API-QUILOMETRAGEM.md              # Documentação API
├── MIGRATION-GUIDE.md                # Guia de migração
├── REFATORACAO-CONCLUIDA.md         # Resumo refatoração
└── README-QUILOMETRAGEM.md          # Este arquivo
```

## 🔄 Compatibilidade

### Rotas Antigas (ainda funcionam)
```javascript
POST /api/quilometragem/atualizar/:placa
GET  /api/quilometragem/diaria/:placa/:data
GET  /api/quilometragem/estatisticas/:placa
```

### Rotas Novas (recomendadas)
```javascript
POST /api/v2/mileage/update/:plate
GET  /api/v2/mileage/daily/:plate/:date
GET  /api/v2/mileage/stats/:plate
... e mais 5 novos endpoints
```

## 🛠️ Troubleshooting

### Erro: "Cannot find module './services/index'"
```bash
# Verifique se a pasta services/ foi criada
ls services/
```

### Erro: "Database connection failed"
```bash
# Teste a conexão
node -e "require('./database').testConnection()"
```

### Erro: "API Timeout"
```bash
# API Ituran pode demorar até 120s
# Verifique se o proxy está rodando em localhost:8888
```

## 📞 Suporte

- 📖 Leia: `API-QUILOMETRAGEM.md`
- 🔄 Migração: `MIGRATION-GUIDE.md`
- ✅ Resumo: `REFATORACAO-CONCLUIDA.md`
- 🧪 Teste: `node test-mileage-refactored.js`

## 📈 Status

**Versão:** 2.0.0
**Status:** ✅ Produção
**Data:** 2025-11-04
**Compatibilidade:** 100% com código antigo

---

**Desenvolvido com ❤️ para FleetFlow**
