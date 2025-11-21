# Arquitetura do Sistema em Produção

## 📐 Visão Geral da Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│              NAVEGADOR DO USUÁRIO                           │
│         (qualquer dispositivo, qualquer lugar)              │
│  https://seu-dominio.com.br                                │
└──────────────┬──────────────────────────────────────────────┘
               │ HTTPS (seguro)
               │ ✅ Sem CORS, browser ↔ servidor mesmo domínio
               │
┌──────────────▼──────────────────────────────────────────────┐
│              SERVIDOR NA VPS/VM (seu-dominio.com.br)        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │   Node.js Express Server (porta 5000)                │   │
│  │   ├─ dashboard.html (interface)                      │   │
│  │   ├─ /api/quilometragem/* (rotas)                    │   │
│  │   ├─ /api/vehicles (CRUD de veículos)                │   │
│  │   ├─ /api/stats (estatísticas)                       │   │
│  │   └─ mileageService.js (lógica de negócio)           │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │   Base de Dados MySQL (local ou remoto)              │   │
│  │   ├─ Vehicles (lista de veículos)                    │   │
│  │   ├─ quilometragem_diaria (histórico)                │   │
│  │   ├─ quilometragem_mensal (totalizações)             │   │
│  │   └─ quilometragem_frota_diaria (agregações)         │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────┬──────────────────────────────────────────────┘
               │ HTTPS (server-to-server)
               │ ✅ Sem navegador, sem CORS, direto
               │ ✅ Credenciais em .env (seguro)
               │
┌──────────────▼──────────────────────────────────────────────┐
│          API ITURAN (SOAP/XML)                              │
│     https://iweb.ituran.com.br                             │
│  ├─ GetAllPlatformsData (lista de veículos)                │
│  ├─ GetFullReport (histórico de rota/KM)                   │
│  ├─ GetPlatformData (dados atuais)                         │
│  └─ GetVehicleLocationWithActiveStatus (localização)       │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Fluxo de Requisição - Cálculo de Quilometragem

### Cenário: Usuário abre Dashboard

```
┌─── TEMPO: 10:00 AM ───┐
│ Usuário abre dashboard│
└───────────┬───────────┘
            │
            ├─ Browser: GET /
            │  └─ Server retorna dashboard.html
            │
            ├─ Browser executa: atualizarDashboardKmComBanco()
            │  └─ JavaScript inicia cálculo de KM
            │
            ├─ Browser: GET /api/quilometragem/diaria/OVE4358/2025-11-21
            │  │
            │  ├─> Server (Express):
            │  │   1. Recebe requisição
            │  │   2. Verifica se dados existem no MySQL
            │  │      ├─ ✅ SIM: Retorna KM do banco (rápido!)
            │  │      └─ ❌ NÃO: Vai para próximo passo
            │  │   3. Chama: mileageService.getDailyMileage()
            │  │      └─ Método: IturanAPIClient.request()
            │  │   4. Faz requisição DIRETO para API Ituran
            │  │      └─ HTTPS://iweb.ituran.com.br
            │  │          /ituranwebservice3/Service3.asmx/GetFullReport
            │  │          ?Plate=OVE4358
            │  │          &Start=2025-11-21 00:00:00
            │  │          &End=2025-11-21 23:59:59
            │  │          &UserName=api@i9tecnologia
            │  │          &Password=Api@In9Eng
            │  │   5. Recebe resposta XML com pontos GPS
            │  │   6. Parseia XML:
            │  │      └─ Extrai: [kmInicial, kmFinal]
            │  │   7. Calcula: kmRodados = kmFinal - kmInicial
            │  │   8. Salva em MySQL (quilometragem_diaria)
            │  │   9. Retorna JSON ao navegador
            │
            └─> Browser exibe resultado no dashboard
```

---

## 💾 Persistência de Dados

### 1️⃣ **Dados em Tempo Real (Cache)**
```
Quando: Usuário abre dashboard
Duração: 5 minutos em cache

Dashboard ─── GET /api/quilometragem/diaria/{placa}/{data} ─── Server
                        ├─ Verifica MySQL
                        └─ Se não existir, busca da API Ituran
```

### 2️⃣ **Dados Históricos (MySQL)**
```
Quando: Automaticamente todo dia às 00:30 (cron job)
Duração: Permanente (histórico completo)

Cron Job ──── atualizarTodosVeiculos() ──── Server
                        │
                        └─ Para cada veículo:
                           ├─ Busca KM de ONTEM na API Ituran
                           ├─ Salva em: quilometragem_diaria
                           ├─ Atualiza: quilometragem_mensal
                           └─ Atualiza: quilometragem_frota_diaria
```

### 3️⃣ **Tabelas MySQL**
```
┌─ quilometragem_diaria
│  ├─ id
│  ├─ vehicle_id
│  ├─ placa
│  ├─ data
│  ├─ km_rodados (número)
│  ├─ created_at
│  └─ updated_at
│
├─ quilometragem_mensal
│  ├─ vehicle_id
│  ├─ placa
│  ├─ mes
│  ├─ km_total
│  └─ updated_at
│
└─ quilometragem_frota_diaria
   ├─ data
   ├─ km_total_frota
   └─ num_veiculos
```

---

## 🔐 Segurança - Como Funciona

### ✅ Credenciais Ituran

```
ANTES (Inseguro):
┌──────────────┐
│ Navegador    │ ← Credenciais visíveis no frontend!
│ tem acesso a │
│ credenciais   │
└──────────────┘

DEPOIS (Seguro):
┌──────────────┐         ┌──────────────┐
│ Navegador    │         │ Server       │
│ requisita    ├────────>│ (tem .env)   │
│ dados        │         │ acessa API   │
│ (sem creds)  │<────────┤ e retorna    │
└──────────────┘         └──────────────┘

Credenciais em: .env (servidor)
Credenciais NÃO em: navegador, localStorage, cookies
```

### ✅ CORS Desaparecido

```
ANTES (Problema CORS):
Navegador tenta acessar iweb.ituran.com.br diretamente
├─ Domínios diferentes
├─ Browser bloqueia por segurança
└─ Erro CORS

DEPOIS (Sem CORS):
1. Navegador ──> Server (mesmo domínio, OK!)
2. Server ──> API Ituran (servidor-para-servidor, sem bloqueios!)
3. Server ──> Navegador (respostas normais, sem erros)
```

### ✅ HTTPS Obrigatório

```
ANTES:
http://localhost:5000 (inseguro, só local)

DEPOIS:
https://seu-dominio.com.br (criptografado, seguro)

Certificado: Let's Encrypt (gratuito)
Validade: 90 dias (auto-renova com certbot)
```

---

## 🔧 Componentes do Sistema

### Backend (Node.js)

| Arquivo | Responsabilidade |
|---------|-----------------|
| `server.js` | Servidor Express principal |
| `services/ituran-api-client.js` | Cliente HTTP para API Ituran |
| `services/ituran-mileage-service.js` | Parse e cálculo de KM |
| `services/mileage-manager.js` | Orquestração (API + MySQL) |
| `database.js` | Funções MySQL |
| `.env` | Configurações seguras |

### Frontend (Browser)

| Arquivo | Responsabilidade |
|---------|-----------------|
| `dashboard.html` | Interface principal |
| `dashboard-quilometragem-db.js` | Lógica de atualização de KM |
| `dashboard-*.js` | Outros widgets |

### Processo em Background

| Tarefa | Frequency | Função |
|--------|-----------|--------|
| Cron Job | 00:30 todo dia | Salvar quilometragem de ontem no MySQL |
| WebSocket (opcional) | Em tempo real | Atualizar dashboard ao vivo |

---

## 📊 Exemplo: Fluxo Completo

### Dia: 21/11/2025

#### 10:00 AM - Usuário Abre Dashboard
```
1. Requisição chega ao server: GET /api/quilometragem/diaria/OVE4358/2025-11-21
2. Server verifica MySQL:
   └─ Existe registro para 2025-11-21?
      ├─ ✅ SIM: Retorna km_rodados do banco (cache)
      └─ ❌ NÃO: Vai para passo 3

3. Se não existe, busca da API Ituran:
   └─ GET https://iweb.ituran.com.br/ituranwebservice3/Service3.asmx/GetFullReport
      ├─ UserName: api@i9tecnologia
      ├─ Password: Api@In9Eng
      ├─ Plate: OVE4358
      ├─ Start: 2025-11-21 00:00:00
      ├─ End: 2025-11-21 23:59:59
      └─ ReturnCode: "OK"

4. API retorna XML com pontos GPS:
   ├─ Primeiro ponto: KM = 12500
   ├─ Último ponto: KM = 12850
   └─ Diferença = 350 KM

5. Server salva no MySQL:
   └─ INSERT INTO quilometragem_diaria
      (vehicle_id, placa, data, km_rodados)
      VALUES (1, 'OVE4358', '2025-11-21', 350)

6. Server retorna ao navegador:
   └─ { "km_rodados": 350, "data": "2025-11-21" }

7. Dashboard exibe: "350 km"
```

#### 00:30 AM (próximo dia) - Cron Job Atualiza Histórico
```
1. Cron job dispara automaticamente
   └─ atualizarTodosVeiculos(data_ontem)

2. Para cada veículo:
   ├─ Busca KM de ONTEM da API Ituran
   ├─ Se não salvo ainda no MySQL, salva
   └─ Atualiza totalizações mensais

3. Resultado:
   ├─ quilometragem_diaria: registros de todos os dias
   ├─ quilometragem_mensal: totais por mês
   └─ quilometragem_frota_diaria: agregações da frota
```

---

## 🚀 Performance

### Tempos Esperados

| Operação | Tempo | Motivo |
|----------|-------|--------|
| Dashboard carrega | 1-2s | Cache MySQL |
| Primeira quilometragem (API) | 10-30s | Ituran processa 80+ veículos |
| Próximas quilometragens | <1s | Retorna do MySQL cache |
| Atualizar manualmente | Mesmo que primeira | Busca nova API |

### Otimizações Implementadas

1. **Cache em Memória (5 min)**
   - Dashboard não faz requisição novamente em 5 minutos

2. **Persistência em MySQL**
   - Dados salvos permanecem mesmo se servidor reiniciar

3. **Requisições Paralelas**
   - Busca KM de vários veículos simultaneamente

4. **Divisão de Período (2.5 dias)**
   - API Ituran máximo 3 dias por requisição

---

## 🔄 Sincronização de Dados

```
Timeline do Dia:

00:00 ─────────────────────────────────────── 00:30 ─── 10:00
│                                             │          │
│ Veículos saem para trabalho                 │          │ Usuário abre
│ Ituran rastreia em tempo real               │          │ dashboard
│ Dados acumulam nos servidores Ituran        │          │
│                                             │          │
│                                    Cron: busca KM      Dashboard:
│                                    de ONTEM          busca KM de
│                                    salva em MySQL    HOJE (cache)
│
┌─────────────────────────────────────────────────────────┐
│  DADOS EM ITURAN                                        │
│  Sempre atualizados em tempo real (nosso rastreio)      │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  DADOS EM MYSQL (nosso banco)                           │
│  Atualizado 1x por dia (00:30)                          │
│  Histórico permanente                                   │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  DADOS NO NAVEGADOR (cache)                            │
│  Atualizado 5 minutos                                  │
│  Ou quando usuário clica "Atualizar Agora"             │
└─────────────────────────────────────────────────────────┘
```

---

## 📱 Escalabilidade

### Como crescer quando tiver mais veículos

```
Até 50 veículos:
├─ 1 servidor Node.js
├─ 1 banco MySQL
├─ Sem problemas

De 50 a 200 veículos:
├─ 1 servidor Node.js (upgrade RAM/CPU)
├─ 1 banco MySQL dedicado
├─ Aumentar timeout Ituran

Mais de 200 veículos:
├─ 2+ servidores Node.js (load balancer)
├─ 1 banco MySQL (replicação)
├─ Dividir cron job por ranges de placa
├─ Usar Redis para cache distribuído
```

---

## ✅ Checklist de Produção

- [ ] `.env` com credenciais corretas
- [ ] MySQL acessível
- [ ] Node.js v18+ instalado
- [ ] Dotenv carregando variáveis
- [ ] HTTPS com certificado válido
- [ ] Reverse proxy (nginx/apache) configurado
- [ ] PM2 ou Docker rodando
- [ ] Cron jobs funcionando
- [ ] Backups do MySQL automatizados
- [ ] Monitoramento de logs ativo
- [ ] Rate limit configurado na API
- [ ] Alertas de falha configurados

---

**Status:** Pronto para Produção ✅
**Última Atualização:** Nov 2025
