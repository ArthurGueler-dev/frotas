# 🚗 Como Funciona o Sistema de Quilometragem

## 📊 Resumo

O sistema funciona em **DUAS ETAPAS**:

1. **TEMPO REAL** (durante o dia): Dashboard calcula KM da API Ituran dinamicamente
2. **MEIA-NOITE** (00:30): Cron job salva os dados de ontem no banco MySQL

---

## ⏰ Durante o Dia (Tempo Real)

### Dashboard Calcula KM da API Ituran

**Arquivo**: `dashboard-stats.js`

**O que faz**:
- Dashboard abre → Busca dados da API Ituran **em tempo real**
- Calcula KM de hoje, ontem e do mês atual
- Usa **cache de 5 minutos** para não sobrecarregar a API
- Mostra valores nos widgets do dashboard
- **NÃO SALVA NO BANCO** ainda

**Exemplo**:
```
Usuário abre dashboard às 14h30:
→ Calcula KM de hoje (00:00 até 14:30) da API Ituran
→ Mostra no widget "KM Rodados Hoje"
→ Cache válido por 5 minutos
```

---

## 🌙 À Meia-Noite (00:30)

### Cron Job Salva no Banco de Dados

**Arquivo**: `cron-update-km.js`

**O que faz**:
- Executa automaticamente às **00:30** todos os dias
- Busca dados de **ONTEM** (dia completo) da API Ituran
- **SALVA NO BANCO MYSQL** permanentemente
- Cria histórico de quilometragem para relatórios

**Fluxo**:
```
00:30 → Cron job inicia
     ↓
Busca KM de ontem de todos os veículos
     ↓
Salva em: quilometragem_diaria (MySQL)
     ↓
Atualiza: quilometragem_mensal (MySQL)
     ↓
Atualiza: quilometragem_frota_diaria (MySQL) ← Total da frota
```

**Exemplo**:
```
Dia 04/11/2025 às 00:30:
→ Busca dados do dia 03/11/2025 (00:00 até 23:59)
→ Salva no banco:
   • Placa ABC-1234: 150.5 km
   • Placa DEF-5678: 200.8 km
   • ... (todos os veículos)
   • Total da frota: 12.500 km
```

---

## 🗄️ Banco de Dados (MySQL)

### Tabelas Criadas

#### 1. `quilometragem_diaria`
Armazena KM de cada veículo por dia.

```sql
Exemplo de dados:
placa      | data       | km_inicial | km_final | km_rodados
-----------+------------+------------+----------+-----------
ABC-1234   | 2025-11-03 | 50000.00   | 50150.50 | 150.50
DEF-5678   | 2025-11-03 | 75000.00   | 75200.80 | 200.80
```

#### 2. `quilometragem_mensal`
Totais mensais de cada veículo (calculado automaticamente).

```sql
Exemplo de dados:
placa      | ano  | mes | km_total | dias_rodados
-----------+------+-----+----------+-------------
ABC-1234   | 2025 | 11  | 4500.50  | 25
DEF-5678   | 2025 | 11  | 6000.80  | 27
```

#### 3. `quilometragem_frota_diaria` ← NOVA!
Total de **TODOS** os veículos por dia.

```sql
Exemplo de dados:
data       | km_total  | total_veiculos | veiculos_em_movimento
-----------+-----------+----------------+----------------------
2025-11-03 | 12500.50  | 80             | 65
2025-11-02 | 11800.30  | 80             | 62
2025-11-01 | 13200.80  | 80             | 68
```

---

## 🎯 Por Que Funciona Assim?

### ✅ Vantagens

1. **Dashboard sempre atualizado**
   - Mostra KM em tempo real durante o dia
   - Não precisa esperar a meia-noite

2. **Histórico permanente**
   - Dados salvos no banco às 00:30
   - Pode consultar qualquer data passada

3. **Performance otimizada**
   - Cache de 5 minutos no dashboard
   - Salva no banco só 1x por dia

4. **Backup automático**
   - Dados no MySQL = seguro e permanente
   - Pode gerar relatórios históricos

---

## 📱 Como o Usuário Vê

### Dashboard às 10h da manhã:
```
KM Rodados Hoje:    1,250 km  (calculado em tempo real)
KM Rodados Ontem:   2,100 km  (do banco de dados)
KM Rodados no Mês: 35,000 km  (do banco de dados)
```

### Dashboard às 14h:
```
KM Rodados Hoje:    2,800 km  (atualizado em tempo real)
KM Rodados Ontem:   2,100 km  (mesmo valor do banco)
KM Rodados no Mês: 37,800 km  (atualizado)
```

### Dashboard às 00:35 (depois do cron):
```
KM Rodados Hoje:       50 km  (dia acabou de começar)
KM Rodados Ontem:   3,500 km  (NOVO! recém salvo no banco)
KM Rodados no Mês: 38,500 km  (atualizado com ontem)
```

---

## 🔧 Configuração

### Agendador do Windows

**Quando**: Todos os dias às 00:30
**O que executa**: `update-km-daily.bat`
**Log**: `logs/km-updates.log`

**Como configurar**: Veja `SETUP-AGENDADOR-WINDOWS.md`

---

## 🧪 Testar o Sistema

### 1. Dashboard em Tempo Real
```
1. Abra: http://localhost:5000/dashboard.html
2. Aguarde carregar (pode demorar 1-2 minutos)
3. Veja os widgets de KM preenchendo
4. Abra F12 → Console para ver logs
```

### 2. Salvar no Banco Manualmente
```bash
# Executa o script de salvar no banco (simula o cron)
node cron-update-km.js
```

### 3. Verificar Banco de Dados
```sql
-- Ver dados de um dia
SELECT * FROM quilometragem_diaria WHERE data = '2025-11-03';

-- Ver totais mensais
SELECT * FROM quilometragem_mensal WHERE ano = 2025 AND mes = 11;

-- Ver totais da frota
SELECT * FROM quilometragem_frota_diaria ORDER BY data DESC LIMIT 10;
```

---

## ❓ Perguntas Frequentes

### P: Por que o dashboard não mostra valores imediatamente?
**R:** Porque precisa calcular KM de 80 veículos da API Ituran. Leva 1-2 minutos na primeira vez. Depois fica em cache por 5 minutos.

### P: Os valores de "KM Hoje" mudam?
**R:** SIM! São calculados em tempo real. A cada 5 minutos recalcula da API.

### P: E "KM Ontem"?
**R:** Vem do banco de dados. Só atualiza quando o cron roda (00:30).

### P: O cron precisa estar sempre rodando?
**R:** NÃO. O Agendador do Windows executa automaticamente às 00:30. Não precisa deixar nada rodando.

### P: E se perder dados do banco?
**R:** Pode executar `backup-database.js` para fazer backup em JSON. Ou configurar backup automático.

---

## 🎉 Resumo Final

| Aspecto | Funcionamento |
|---------|---------------|
| **Dashboard** | Calcula em tempo real da API Ituran |
| **Cache** | 5 minutos (evita recalcular) |
| **Banco de Dados** | Salvo às 00:30 pelo cron job |
| **Histórico** | Permanente no MySQL |
| **Relatórios** | Exporta Excel dos dados do banco |

**Fluxo completo**:
```
Durante o dia:
API Ituran → Dashboard (tempo real) → Widget atualiza

À meia-noite:
API Ituran → Cron Job → MySQL (permanente) → Relatórios
```

---

**Data**: 03/11/2025
**Versão**: 2.0 (Sistema de Quilometragem Híbrido)
