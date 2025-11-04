# 🚀 Como Usar o Sistema de Quilometragem - Guia Rápido

## ✅ O que foi implementado?

1. **Banco de dados SQLite** (`frotas.db`) com 2 tabelas:
   - `quilometragem_diaria` - Dados diários
   - `quilometragem_mensal` - Totais mensais

2. **8 Endpoints de API** para gerenciar quilometragem

3. **Script de atualização automática** que roda todo dia

4. **Documentação completa** em `INTEGRA_QUILOMETRAGEM.md`

---

## 🎯 Primeiros Passos (FAÇA AGORA)

### Passo 1: Testar o Sistema

Abra o navegador e vá para o console (F12):

```javascript
// 1. Atualizar dados de ontem para um veículo
fetch('http://localhost:5000/api/quilometragem/atualizar/SFT4I72', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ data: '2025-11-02' })  // Ontem
}).then(r => r.json()).then(console.log);

// Resultado esperado:
// { success: true, data: { placa: "SFT4I72", kmRodados: 15.3, ... } }
```

✅ **Se funcionar**, significa que o sistema está OK!

### Passo 2: Verificar se Salvou no Banco

```javascript
// 2. Consultar o que foi salvo
fetch('http://localhost:5000/api/quilometragem/diaria/SFT4I72/2025-11-02')
    .then(r => r.json())
    .then(console.log);

// Resultado esperado:
// { success: true, data: { km_inicial: X, km_final: Y, km_rodados: Z } }
```

### Passo 3: Testar Atualização de Todos os Veículos

```javascript
// 3. Atualizar TODOS os veículos de uma vez
fetch('http://localhost:5000/api/quilometragem/atualizar-todos', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ data: '2025-11-02' })
}).then(r => r.json()).then(console.log);

// Resultado esperado:
// { success: true, data: { total: 10, sucessos: 9, falhas: 1 } }
```

---

## ⏰ Configurar Atualização Automática (IMPORTANTE!)

### Windows - Agendador de Tarefas

1. **Abra o Agendador:**
   ```
   Win + R → digite: taskschd.msc → Enter
   ```

2. **Crie a Tarefa:**
   - Clique: "Criar Tarefa Básica..."
   - Nome: `Atualização KM Diária`
   - Gatilho: Diariamente às 00:30
   - Ação: Iniciar programa
   - Programa: `C:\Users\SAMSUNG\Desktop\frotas\update-km-daily.bat`

3. **Teste Agora:**
   - Clique com botão direito na tarefa
   - Escolha "Executar"
   - Verifique o arquivo `logs\km-updates.log`

### Teste Manual (Antes de Agendar)

```bash
cd C:\Users\SAMSUNG\Desktop\frotas
node cron-update-km.js
```

Você verá:
```
═══════════════════════════════════════════════════════════
📊 Iniciando atualização automática de quilometragem
═══════════════════════════════════════════════════════════
🕐 Horário: 03/11/2025 10:30:15

📅 Atualizando dados de: 2025-11-02

✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!

📊 Total de veículos: 10
✅ Sucessos: 9
❌ Falhas: 1
```

---

## 📊 Consultas Úteis

### Ver KM do Mês Atual

```javascript
const hoje = new Date();
const ano = hoje.getFullYear();
const mes = hoje.getMonth() + 1; // Janeiro = 1

fetch(`http://localhost:5000/api/quilometragem/mensal/SFT4I72/${ano}/${mes}`)
    .then(r => r.json())
    .then(data => {
        console.log(`KM total do mês: ${data.data.km_total} km`);
        console.log(`Dias rodados: ${data.data.dias_rodados}`);
    });
```

### Ver KM dos Últimos 30 Dias

```javascript
const hoje = new Date();
const umMesAtras = new Date();
umMesAtras.setDate(hoje.getDate() - 30);

const dataInicio = umMesAtras.toISOString().split('T')[0];
const dataFim = hoje.toISOString().split('T')[0];

fetch(`http://localhost:5000/api/quilometragem/periodo/SFT4I72?dataInicio=${dataInicio}&dataFim=${dataFim}`)
    .then(r => r.json())
    .then(data => {
        const total = data.data.reduce((sum, dia) => sum + dia.km_rodados, 0);
        console.log(`KM nos últimos 30 dias: ${total.toFixed(2)} km`);
    });
```

### Estatísticas do Mês

```javascript
fetch('http://localhost:5000/api/quilometragem/estatisticas/SFT4I72?periodo=mes')
    .then(r => r.json())
    .then(data => {
        console.log(`Total do mês: ${data.data.totalKm} km`);
        console.log(`Média por dia: ${data.data.mediaKmDia} km`);
    });
```

---

## 🗄️ Localização dos Arquivos

```
C:\Users\SAMSUNG\Desktop\frotas\
├── frotas.db                    ← Banco de dados SQLite
├── database.js                  ← Gerenciador do banco
├── quilometragem-api.js         ← Lógica de negócio
├── cron-update-km.js            ← Script de atualização
├── update-km-daily.bat          ← Agendador Windows
├── INTEGRA_QUILOMETRAGEM.md     ← Documentação completa
├── COMO_USAR_QUILOMETRAGEM.md   ← Este arquivo
└── logs/
    └── km-updates.log           ← Logs de atualização
```

---

## 🔍 Como Saber se Está Funcionando?

### 1. Verificar o Banco de Dados

```javascript
const { db } = require('./database');

// Ver total de registros
const total = db.prepare('SELECT COUNT(*) as total FROM quilometragem_diaria').get();
console.log(`Total de registros: ${total.total}`);

// Ver último registro
const ultimo = db.prepare('SELECT * FROM quilometragem_diaria ORDER BY id DESC LIMIT 1').get();
console.log('Último registro:', ultimo);
```

### 2. Verificar os Logs

Abra o arquivo: `logs\km-updates.log`

Se houver erro, você verá a mensagem detalhada aqui.

### 3. API de Teste

```bash
# Via navegador ou Postman
GET http://localhost:5000/api/quilometragem/diaria/SFT4I72/2025-11-02
```

---

## ⚠️ Solução de Problemas

### Problema: "Database is locked"

**Solução:** Feche outros processos Node.js

```bash
taskkill /F /IM node.exe
```

Depois reinicie o servidor.

### Problema: Não salvou dados

**Verificar:**

1. O veículo existe no arquivo `data/veiculos.json`?
2. A API Ituran está respondendo?
3. A data está correta? (use data de ontem ou anterior)

**Teste manual:**

```javascript
// Salvar manualmente
fetch('http://localhost:5000/api/quilometragem/diaria', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        placa: 'SFT4I72',
        data: '2025-11-02',
        kmInicial: 14920.5,
        kmFinal: 14935.8,
        tempoIgnicao: 240
    })
}).then(r => r.json()).then(console.log);
```

### Problema: Atualização automática não roda

1. Verifique se a tarefa foi criada corretamente no Agendador
2. Teste manualmente: `node cron-update-km.js`
3. Veja os logs em `logs\km-updates.log`

---

## 📈 Próximos Passos (Opcional)

### 1. Adicionar ao Dashboard

No arquivo `dashboard.html`, adicione um botão:

```html
<button onclick="atualizarKmHoje()">
    Atualizar KM de Hoje
</button>

<script>
async function atualizarKmHoje() {
    const ontem = new Date();
    ontem.setDate(ontem.getDate() - 1);
    const data = ontem.toISOString().split('T')[0];

    const res = await fetch('/api/quilometragem/atualizar-todos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ data })
    });

    const result = await res.json();
    alert(`✅ ${result.data.sucessos} veículos atualizados!`);
}
</script>
```

### 2. Exportar para Excel

Instale o pacote:
```bash
npm install xlsx
```

Exemplo de uso:
```javascript
const XLSX = require('xlsx');
const db = require('./database');

// Buscar dados do mês
const dados = db.db.prepare(`
    SELECT * FROM quilometragem_diaria
    WHERE ano = 2025 AND mes = 11
    ORDER BY placa, data
`).all();

// Criar Excel
const ws = XLSX.utils.json_to_sheet(dados);
const wb = XLSX.utils.book_new();
XLSX.utils.book_append_sheet(wb, ws, 'Quilometragem');
XLSX.writeFile(wb, 'relatorio-km-nov-2025.xlsx');

console.log('✅ Relatório Excel criado!');
```

### 3. Gráficos no Dashboard

Use Chart.js para criar gráficos de evolução. Exemplo básico:

```html
<canvas id="graficoKm"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
async function carregarGrafico() {
    const res = await fetch('/api/quilometragem/periodo/SFT4I72?dataInicio=2025-10-01&dataFim=2025-10-31');
    const dados = await res.json();

    const labels = dados.data.map(d => d.data);
    const kms = dados.data.map(d => d.km_rodados);

    new Chart(document.getElementById('graficoKm'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'KM por Dia',
                data: kms,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        }
    });
}

carregarGrafico();
</script>
```

---

## 🎉 Pronto!

Agora você tem:

✅ Banco de dados de quilometragem funcionando
✅ APIs para consultar dados históricos
✅ Atualização automática diária
✅ Documentação completa

**Dúvidas?** Consulte `INTEGRA_QUILOMETRAGEM.md` para mais detalhes.

**Última atualização:** 03/11/2025
