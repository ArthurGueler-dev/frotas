# 📊 Sistema de Quilometragem - Guia de Integração Completo

## 📋 Índice
1. [Visão Geral](#visão-geral)
2. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
3. [Configuração Inicial](#configuração-inicial)
4. [Uso das APIs](#uso-das-apis)
5. [Atualização Automática](#atualização-automática)
6. [Migração de Dados Existentes](#migração-de-dados-existentes)
7. [Consultas e Relatórios](#consultas-e-relatórios)

---

## 🎯 Visão Geral

O Sistema de Quilometragem armazena e gerencia dados históricos de quilometragem dos veículos, permitindo:
- ✅ Consultar quilometragem de qualquer dia passado
- ✅ Ver totais mensais e anuais
- ✅ Gerar relatórios e estatísticas
- ✅ Exportar dados para Excel
- ✅ Atualizar automaticamente todos os dias

---

## 🗄️ Estrutura do Banco de Dados

**⚠️ IMPORTANTE:** O sistema utiliza **MySQL**, não SQLite! O banco de dados é remoto.

### Tabela: `quilometragem_diaria`

Armazena dados de quilometragem por dia para cada veículo.

```sql
CREATE TABLE quilometragem_diaria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(10) NOT NULL,
    data DATE NOT NULL,
    ano INT NOT NULL,
    mes INT NOT NULL,
    dia INT NOT NULL,
    km_inicial DECIMAL(10,2) DEFAULT 0,
    km_final DECIMAL(10,2) DEFAULT 0,
    km_rodados DECIMAL(10,2) DEFAULT 0,
    tempo_ignicao_minutos INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_placa_data (placa, data)
);
```

**Índices:**
```sql
CREATE INDEX idx_quilometragem_diaria_placa_data ON quilometragem_diaria(placa, data);
CREATE INDEX idx_quilometragem_diaria_data ON quilometragem_diaria(data);
```

### Tabela: `quilometragem_mensal`

Armazena totais mensais agregados (calculados automaticamente).

```sql
CREATE TABLE quilometragem_mensal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(10) NOT NULL,
    ano INT NOT NULL,
    mes INT NOT NULL,
    km_total DECIMAL(10,2) DEFAULT 0,
    dias_rodados INT DEFAULT 0,
    tempo_ignicao_total_minutos INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_placa_ano_mes (placa, ano, mes)
);
```

**Índices:**
```sql
CREATE INDEX idx_quilometragem_mensal_placa ON quilometragem_mensal(placa, ano, mes);
```

---

## ⚙️ Configuração Inicial

### 1. Instalação de Dependências

O sistema utiliza MySQL2 para conexão com o banco de dados:

```bash
npm install mysql2
```

### 2. Arquivos Criados

| Arquivo | Descrição |
|---------|-----------|
| `database.js` | Gerenciamento do banco MySQL remoto |
| `quilometragem-api.js` | Lógica de negócio |
| `cron-update-km.js` | Script de atualização diária |
| `update-km-daily.bat` | Agendador Windows |

### 3. Configuração do Banco

O banco MySQL já está configurado em `database.js` com as credenciais do servidor remoto (187.49.226.10).

**As tabelas devem ser criadas manualmente no MySQL** usando os scripts SQL acima.

---

## 🚀 Uso das APIs

### Endpoints Disponíveis

#### 1. Salvar Quilometragem Diária

```http
POST /api/quilometragem/diaria
Content-Type: application/json

{
  "placa": "SFT4I72",
  "data": "2025-11-03",
  "kmInicial": 14920.5,
  "kmFinal": 14935.8,
  "tempoIgnicao": 240
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "changes": 1,
    "lastInsertRowid": 1
  }
}
```

#### 2. Buscar Quilometragem de um Dia

```http
GET /api/quilometragem/diaria/SFT4I72/2025-11-03
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "placa": "SFT4I72",
    "data": "2025-11-03",
    "ano": 2025,
    "mes": 11,
    "dia": 3,
    "km_inicial": 14920.5,
    "km_final": 14935.8,
    "km_rodados": 15.3,
    "tempo_ignicao_minutos": 240,
    "created_at": "2025-11-03 10:30:00",
    "updated_at": "2025-11-03 10:30:00"
  }
}
```

#### 3. Buscar Quilometragem de um Período

```http
GET /api/quilometragem/periodo/SFT4I72?dataInicio=2025-10-01&dataFim=2025-10-31
```

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "placa": "SFT4I72",
      "data": "2025-10-01",
      "km_rodados": 45.2
    },
    {
      "id": 2,
      "placa": "SFT4I72",
      "data": "2025-10-02",
      "km_rodados": 38.7
    }
    // ... mais dias
  ]
}
```

#### 4. Buscar Total Mensal

```http
GET /api/quilometragem/mensal/SFT4I72/2025/10
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "placa": "SFT4I72",
    "ano": 2025,
    "mes": 10,
    "km_total": 1250.50,
    "dias_rodados": 28,
    "tempo_ignicao_total_minutos": 6720
  }
}
```

#### 5. Buscar Vários Meses

```http
GET /api/quilometragem/meses/SFT4I72?anoInicio=2025&mesInicio=1&anoFim=2025&mesFim=12
```

#### 6. Atualizar da API Ituran (Manual)

```http
POST /api/quilometragem/atualizar/SFT4I72
Content-Type: application/json

{
  "data": "2025-11-02"
}
```

Este endpoint:
1. Busca dados da API Ituran para a data especificada
2. Salva automaticamente no banco
3. Atualiza o total mensal

#### 7. Atualizar Todos os Veículos

```http
POST /api/quilometragem/atualizar-todos
Content-Type: application/json

{
  "data": "2025-11-02"
}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "total": 10,
    "sucessos": 9,
    "falhas": 1,
    "resultados": [
      {
        "placa": "SFT4I72",
        "success": true,
        "data": {
          "kmRodados": 15.3
        }
      }
      // ... outros veículos
    ]
  }
}
```

#### 8. Estatísticas

```http
GET /api/quilometragem/estatisticas/SFT4I72?periodo=mes
```

Períodos disponíveis: `semana`, `mes`, `ano`

**Resposta:**
```json
{
  "success": true,
  "data": {
    "periodo": "mes",
    "totalKm": 1250.50,
    "totalDias": 28,
    "mediaKmDia": 44.66,
    "dados": [...]
  }
}
```

---

## ⏰ Atualização Automática

### Opção 1: Agendador de Tarefas do Windows

1. **Abra o Agendador de Tarefas:**
   - Pressione `Win + R`
   - Digite `taskschd.msc`
   - Pressione Enter

2. **Crie Nova Tarefa:**
   - Clique em "Criar Tarefa Básica"
   - Nome: "Atualização de Quilometragem Diária"
   - Descrição: "Atualiza dados de KM de todos os veículos"

3. **Configure o Gatilho:**
   - Quando: Diariamente
   - Hora: 00:30 (meia-noite e meia)
   - Recorrência: Todos os dias

4. **Configure a Ação:**
   - Ação: Iniciar um programa
   - Programa/script: `C:\Users\SAMSUNG\Desktop\frotas\update-km-daily.bat`
   - Iniciar em: `C:\Users\SAMSUNG\Desktop\frotas`

5. **Finalize:**
   - Marque "Abrir caixa de diálogo Propriedades ao clicar em Concluir"
   - Em "Condições", desmarque "Iniciar tarefa apenas se o computador estiver conectado à energia CA"
   - Em "Configurações", marque "Executar tarefa assim que possível após uma inicialização agendada ter sido perdida"

### Opção 2: Teste Manual

Para testar antes de agendar:

```bash
cd C:\Users\SAMSUNG\Desktop\frotas
node cron-update-km.js
```

Você verá algo como:

```
═══════════════════════════════════════════════════════════
📊 Iniciando atualização automática de quilometragem
═══════════════════════════════════════════════════════════
🕐 Horário: 03/11/2025 00:30:00

📅 Atualizando dados de: 2025-11-02

✅ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!

📊 Total de veículos: 10
✅ Sucessos: 9
❌ Falhas: 1

📋 Detalhes por veículo:
───────────────────────────────────────────────────────────
1. ✅ SFT4I72: 15.30 km
2. ✅ ABC1234: 42.50 km
...
───────────────────────────────────────────────────────────
```

### Opção 3: Integrar ao Server.js

Se quiser que rode automaticamente enquanto o servidor está rodando, adicione ao `server.js`:

```javascript
const cron = require('node-cron');
const atualizarQuilometragem = require('./cron-update-km');

// Executar todos os dias à meia-noite e meia
cron.schedule('30 0 * * *', () => {
    console.log('🕐 Executando atualização automática de quilometragem...');
    atualizarQuilometragem();
});
```

Primeiro instale o pacote:
```bash
npm install node-cron
```

---

## 📦 Migração de Dados Existentes

### Se você já tem dados históricos em outro sistema:

#### Exemplo: Importar de CSV

```javascript
const fs = require('fs');
const db = require('./database');

// Ler CSV
const csv = fs.readFileSync('historico-km.csv', 'utf-8');
const linhas = csv.split('\n').slice(1); // Pular cabeçalho

// Importar
linhas.forEach(linha => {
    const [placa, data, kmInicial, kmFinal] = linha.split(',');

    db.salvarDiaria(
        placa.trim(),
        data.trim(),
        parseFloat(kmInicial),
        parseFloat(kmFinal),
        0 // tempo de ignição
    );
});

console.log(`✅ ${linhas.length} registros importados!`);
```

#### Exemplo: Importar do MySQL

```javascript
const mysql = require('mysql2/promise');
const db = require('./database');

async function migrarDoMySQL() {
    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'root',
        password: 'senha',
        database: 'antigo_sistema'
    });

    const [rows] = await connection.query(`
        SELECT placa, data, km_inicial, km_final
        FROM historico_km
        ORDER BY data ASC
    `);

    for (const row of rows) {
        db.salvarDiaria(
            row.placa,
            row.data,
            row.km_inicial,
            row.km_final,
            0
        );
    }

    console.log(`✅ ${rows.length} registros migrados!`);
    await connection.end();
}

migrarDoMySQL();
```

---

## 📊 Consultas e Relatórios

### Consultas SQL Diretas

```javascript
const { db } = require('./database');

// Top 10 veículos que mais rodaram no mês
const ranking = db.prepare(`
    SELECT placa, SUM(km_rodados) as total_km
    FROM quilometragem_diaria
    WHERE ano = ? AND mes = ?
    GROUP BY placa
    ORDER BY total_km DESC
    LIMIT 10
`).all(2025, 11);

// Média de KM por dia da semana
const mediaPorDia = db.prepare(`
    SELECT
        CASE CAST(strftime('%w', data) AS INTEGER)
            WHEN 0 THEN 'Domingo'
            WHEN 1 THEN 'Segunda'
            WHEN 2 THEN 'Terça'
            WHEN 3 THEN 'Quarta'
            WHEN 4 THEN 'Quinta'
            WHEN 5 THEN 'Sexta'
            WHEN 6 THEN 'Sábado'
        END as dia_semana,
        AVG(km_rodados) as media_km
    FROM quilometragem_diaria
    WHERE placa = ?
    GROUP BY strftime('%w', data)
`).all('SFT4I72');

// Dias sem movimento
const diasParados = db.prepare(`
    SELECT data, placa
    FROM quilometragem_diaria
    WHERE km_rodados = 0
    AND data >= date('now', '-30 days')
    ORDER BY data DESC
`).all();
```

### Exemplo de Exportação para Excel

```javascript
const XLSX = require('xlsx');
const { db } = require('./database');

// Buscar dados
const dados = db.prepare(`
    SELECT *
    FROM quilometragem_diaria
    WHERE ano = ? AND mes = ?
    ORDER BY placa, data
`).all(2025, 11);

// Criar planilha
const ws = XLSX.utils.json_to_sheet(dados);
const wb = XLSX.utils.book_new();
XLSX.utils.book_append_sheet(wb, ws, 'Quilometragem');

// Salvar arquivo
XLSX.writeFile(wb, 'relatorio-km-nov-2025.xlsx');
console.log('✅ Relatório exportado!');
```

---

## 🔒 Backup do Banco de Dados

### Backup Manual

O arquivo do banco é `frotas.db`. Para fazer backup:

```bash
# Windows
copy frotas.db frotas-backup-%date:~-4,4%%date:~-7,2%%date:~-10,2%.db

# Ou simplesmente copie o arquivo
```

### Backup Automático

Adicione ao script de atualização diária:

```javascript
const fs = require('fs');

// Fazer backup antes de atualizar
const dataBackup = new Date().toISOString().split('T')[0];
fs.copyFileSync(
    'frotas.db',
    `backups/frotas-${dataBackup}.db`
);
```

---

## 🐛 Troubleshooting

### Problema: "Database is locked"

**Solução:** Certifique-se de que apenas uma instância do servidor está rodando.

```bash
# Fechar processos Node.js
taskkill /F /IM node.exe
```

### Problema: Dados não aparecem

**Verificação:**

```javascript
const { db } = require('./database');

// Verificar total de registros
const total = db.prepare('SELECT COUNT(*) as total FROM quilometragem_diaria').get();
console.log(`Total de registros: ${total.total}`);

// Verificar último registro
const ultimo = db.prepare('SELECT * FROM quilometragem_diaria ORDER BY id DESC LIMIT 1').get();
console.log('Último registro:', ultimo);
```

### Problema: Atualização automática não funciona

1. Verifique se o arquivo `.bat` está no local correto
2. Teste manualmente: `node cron-update-km.js`
3. Verifique os logs em `logs/km-updates.log`

---

## 📞 Suporte

Para dúvidas ou problemas:

1. Verifique os logs em `logs/km-updates.log`
2. Execute `node cron-update-km.js` manualmente para ver erros
3. Consulte este documento

---

## ✅ Checklist de Implementação

- [x] Banco de dados criado (MySQL)
- [x] Endpoints da API funcionando
- [x] Teste manual realizado
- [x] Bug do await corrigido
- [x] Dashboard atualizado com link para Quilometragem
- [x] Gráficos implementados (Chart.js)
  - [x] Gráfico de linha: KM diários (últimos 30 dias)
  - [x] Gráfico de barras: KM mensais (último ano)
- [x] Tarefa agendada configurada
  - [x] Script de atualização automática (cron-update-km.js)
  - [x] Arquivo batch para Windows (update-km-daily.bat)
  - [x] Documentação completa (SETUP-AGENDADOR-WINDOWS.md)
- [x] Backup configurado
  - [x] Script de backup automático (backup-database.js)
  - [x] Arquivo batch para Windows (backup-daily.bat)
  - [x] Limpeza automática de backups antigos (30 dias)
- [x] Exportação de relatórios funcionando
  - [x] Botão de exportação na interface
  - [x] Geração de arquivos Excel (.xlsx)
  - [x] Dados dos últimos 30 dias de todos os veículos

---

**Última atualização:** 03/11/2025
**Versão:** 1.0.0
