# 🚀 Deploy: Tempos Reais do OSRM

## 🎯 O Que Foi Implementado

Modificado o sistema para usar **tempos reais de viagem** do OSRM em vez de estimativas simples.

### ✅ Vantagens dos Tempos Reais:
- ✅ **Precisão**: OSRM considera limites de velocidade reais
- ✅ **Tipo de via**: Diferencia rodovias, urbanas, residenciais
- ✅ **Distâncias reais**: Usa rotas reais por ruas/estradas
- ✅ **Confiável**: Dados baseados em OpenStreetMap

### 📊 Comparação:

**ANTES (Estimativa simples)**:
- Velocidade fixa: 25 km/h
- Não considera tipo de via
- Impreciso para rotas longas

**AGORA (OSRM Real)**:
- Velocidade variável por via
- Rodovia: 80-100 km/h
- Urbana: 40-50 km/h
- Residencial: 20-30 km/h
- **+ 5 min por parada** (tempo de atendimento)

---

## 📁 Arquivos Modificados

### 1. Python API (`python-api/app.py`)
**Linhas modificadas**: 305-355, 373-494, 819-831

**Mudanças**:
- ✅ Criada função `get_osrm_matrices()` que retorna distância E duração
- ✅ Função `solve_cvrp()` agora calcula tempo real por rota
- ✅ Cada rota retorna: `duration_minutes` e `duration_with_stops_minutes`
- ✅ Blocos otimizados incluem: `tempo_total_min` (OSRM + paradas)

### 2. Frontend JS (`otimizador-blocos.js`)
**Linhas modificadas**: 445-473

**Mudanças**:
- ✅ Usa `bloco.tempo_total_min` (tempo real do OSRM)
- ✅ Fallback para estimativa se tempo não vier da API
- ✅ Logs de aviso se usar estimativa

---

## 🚀 Deploy - Passo a Passo

### Etapa 1: Deploy da API Python no VPS

```bash
# 1. Copiar arquivo atualizado para o VPS
scp C:\Users\SAMSUNG\Desktop\frotas\python-api\app.py root@31.97.169.36:/root/python-api/

# 2. Conectar ao VPS via SSH
ssh root@31.97.169.36

# 3. Navegar até pasta da API
cd /root/python-api

# 4. Verificar se arquivo foi copiado
ls -lh app.py

# 5. Recarregar PM2 (sem downtime)
pm2 reload python-route-api

# 6. Verificar logs
pm2 logs python-route-api --lines 50

# 7. Testar se API está respondendo
curl http://localhost:8000/health
```

**Resultado esperado**:
```json
{
  "status": "healthy",
  "osrm_status": "online",
  "timestamp": "2025-12-17T..."
}
```

---

### Etapa 2: Upload do JS para cPanel

**Arquivo**: `otimizador-blocos.js`
**Destino**: cPanel → `public_html/otimizador-blocos.js`

**Via cPanel File Manager**:
1. Login: https://floripa.in9automacao.com.br/cpanel
2. File Manager → public_html
3. Backup do arquivo atual (renomear para `.backup`)
4. Upload do arquivo novo
5. Verificar permissões: 644

---

### Etapa 3: Testar o Sistema

#### Teste 1: Limpar Cache
```
Ctrl + Shift + Delete → Cache → Limpar
```

#### Teste 2: Recarregar Página
```
http://localhost:5000/otimizador-blocos.html
Ctrl + F5 (hard refresh)
```

#### Teste 3: Importar Planilha
1. Deletar blocos antigos
2. Selecionar planilha Excel
3. Marcar "Criar blocos automaticamente"
4. Processar arquivo
5. **Observar logs no console** (F12)

**Logs esperados**:
```
🚗 Calculando distâncias e tempos OSRM (real) para X pontos...
✅ 1 rotas, 12.5km, 35.2min total
```

#### Teste 4: Verificar Mensagem WhatsApp
1. Gerar rota para um bloco
2. Selecionar motorista/veículo
3. Enviar WhatsApp
4. **Verificar tempo na mensagem**

**Exemplo esperado**:
```
📊 Detalhes da rota:
📍 Total de paradas: 5 locais
🛣 Distância total: 12.5 km
⏱ Tempo aproximado: 35 minutos (com paradas)
```

---

## 🧪 Exemplos de Tempos Reais

### Exemplo 1: Rota Urbana
- **Distância**: 10 km
- **Locais**: 4 paradas
- **Tempo OSRM** (só viagem): 18 min
- **Tempo paradas**: 4 × 5 = 20 min
- **Total**: **38 minutos** ✅

### Exemplo 2: Rota com Rodovia
- **Distância**: 25 km (15km rodovia + 10km urbana)
- **Locais**: 6 paradas
- **Tempo OSRM** (só viagem): 22 min
  - Rodovia (15km): 11 min (80 km/h)
  - Urbana (10km): 11 min (55 km/h)
- **Tempo paradas**: 6 × 5 = 30 min
- **Total**: **52 minutos** ✅

### Exemplo 3: Rota Residencial
- **Distância**: 8 km
- **Locais**: 8 paradas
- **Tempo OSRM** (só viagem): 25 min (32 km/h média)
- **Tempo paradas**: 8 × 5 = 40 min
- **Total**: **65 minutos** ✅

---

## 📊 Estrutura de Dados

### Resposta da API Python:

```json
{
  "success": true,
  "blocos": [
    {
      "bloco_id": 1,
      "num_locais": 5,
      "num_rotas": 1,
      "distancia_total_km": 12.5,
      "tempo_total_min": 35.2,
      "rotas": [
        {
          "route_id": 1,
          "locations": [1, 3, 5, 2, 4],
          "distance_km": 12.5,
          "duration_minutes": 18.3,
          "duration_with_stops_minutes": 43.3
        }
      ]
    }
  ]
}
```

### Campo `tempo_total_min`:
- **Inclui**: Tempo de viagem (OSRM) + Tempo de paradas (5 min/local)
- **Unidade**: Minutos
- **Tipo**: Float (ex: 35.2)
- **Arredondamento**: 1 casa decimal

---

## ⚙️ Configurações

### Tempo de Parada por Local
**Atual**: 5 minutos

**Para alterar**:
```python
# Em python-api/app.py linha 473
route_duration_with_stops = route_duration + (len(route_visits) * 300)
#                                                                   ^^^ 300 segundos = 5 min
```

**Exemplos de ajuste**:
- 3 minutos: `180` segundos
- 7 minutos: `420` segundos
- 10 minutos: `600` segundos

---

## 🔍 Troubleshooting

### Problema: Tempo ainda está 0

**Verificar**:
1. API Python foi atualizada no VPS?
   ```bash
   ssh root@31.97.169.36
   cd /root/python-api
   grep -n "tempo_total_min" app.py
   # Deve encontrar na linha ~824
   ```

2. PM2 foi recarregado?
   ```bash
   pm2 list
   # python-route-api deve estar "online"

   pm2 logs python-route-api --lines 20
   # Verificar se há erros
   ```

3. Frontend foi atualizado no cPanel?
   ```
   Ver timestamp do arquivo otimizador-blocos.js
   Deve ser recente (hoje)
   ```

### Problema: API retorna erro

**Verificar OSRM**:
```bash
ssh root@31.97.169.36
curl "http://localhost:5000/route/v1/driving/-40.338,-20.319;-40.340,-20.320"
```

**Deve retornar**:
```json
{
  "code": "Ok",
  "routes": [...]
}
```

### Problema: Tempo muito alto/baixo

**Ajustar tempo de parada**:
- Se motoristas são rápidos: reduzir para 3 min
- Se precisam de mais tempo: aumentar para 7-10 min

---

## ✅ Checklist de Deploy

- [ ] Backup do `app.py` atual no VPS
- [ ] SCP do `app.py` atualizado para VPS
- [ ] Verificar arquivo copiado (`ls -lh`)
- [ ] Reload PM2: `pm2 reload python-route-api`
- [ ] Verificar logs PM2 (sem erros)
- [ ] Testar health endpoint
- [ ] Backup do `otimizador-blocos.js` no cPanel
- [ ] Upload do JS atualizado para cPanel
- [ ] Verificar permissões (644)
- [ ] Limpar cache do navegador
- [ ] Hard refresh (Ctrl + F5)
- [ ] Deletar blocos antigos
- [ ] Importar planilha teste
- [ ] Verificar logs no console (F12)
- [ ] Gerar uma rota
- [ ] Enviar WhatsApp
- [ ] Verificar tempo na mensagem ✅

---

## 🎯 Resultado Final

Após o deploy completo, você terá:

✅ **Tempos precisos e realistas** em todas as rotas
✅ **Motoristas com estimativas confiáveis**
✅ **Melhor planejamento de entregas/manutenções**
✅ **Diferenciação automática** entre rodovias e vias urbanas
✅ **Sistema profissional de nível empresarial**

---

## 📝 Próximas Melhorias (Futuro)

1. **Tempos de parada variáveis**
   - Por tipo de serviço
   - Por cliente (alguns demoram mais)

2. **Considerar horários de pico**
   - Multiplicar tempo por fator de tráfego
   - Manhã/tarde/noite

3. **Histórico real de tempos**
   - Aprender com rotas anteriores
   - Ajustar automaticamente

4. **Janelas de tempo**
   - Horário de atendimento do cliente
   - Otimizar considerando disponibilidade
