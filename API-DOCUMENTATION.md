# Documentação das APIs - Sistema de Frotas Fioforte

## Índice
1. [API de Serviços](#api-de-servicos)
2. [API de Ordens de Serviço](#api-de-ordens-de-servico)
3. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
4. [Exemplos de Uso](#exemplos-de-uso)

---

## API de Serviços

**Arquivo:** `api-servicos.php`

### 1. Listar Serviços (GET)

```http
GET /api-servicos.php
GET /api-servicos.php?tipo=Serviço
GET /api-servicos.php?search=filtro
GET /api-servicos.php?ativo=1
```

**Parâmetros:**
- `id` (opcional): ID específico do serviço
- `codigo` (opcional): Código do serviço
- `tipo` (opcional): Filtrar por tipo (Serviço, Produto, Mão de Obra)
- `ativo` (opcional): Filtrar por status (1 = ativo, 0 = inativo)
- `search` (opcional): Buscar em nome, código ou descrição

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "codigo": "SRV001",
      "nome": "Troca de Óleo",
      "tipo": "Serviço",
      "categoria": "Mecânica",
      "valor_padrao": 80.00,
      "ativo": 1
    }
  ],
  "total": 1
}
```

### 2. Criar Serviço (POST)

```http
POST /api-servicos.php
Content-Type: application/json

{
  "codigo": "SRV005",
  "nome": "Alinhamento",
  "tipo": "Serviço",
  "categoria": "Mecânica",
  "valor_padrao": 120.00,
  "ocorrencia_padrao": "Preventiva",
  "ativo": 1
}
```

**Campos obrigatórios:**
- `codigo`: Código único do serviço
- `nome`: Nome do serviço
- `tipo`: Tipo (Serviço, Produto, Mão de Obra)

**Resposta:**
```json
{
  "success": true,
  "message": "Serviço criado com sucesso",
  "id": 5
}
```

### 3. Atualizar Serviço (PUT)

```http
PUT /api-servicos.php
Content-Type: application/json

{
  "id": 5,
  "codigo": "SRV005",
  "nome": "Alinhamento e Balanceamento",
  "tipo": "Serviço",
  "valor_padrao": 150.00
}
```

### 4. Desativar Serviço (DELETE)

```http
DELETE /api-servicos.php?id=5
```

---

## API de Ordens de Serviço

### Arquivos disponíveis:
- `save-workorder.php` - Criar OS (POST)
- `get-workorders.php` - Listar OS (GET)
- `api-ordemservico.php` - API REST completa (GET, POST, PUT, PATCH, DELETE)

### 1. Listar Ordens de Serviço (GET)

```http
GET /get-workorders.php
GET /get-workorders.php?status=Aberta
GET /get-workorders.php?placa=ABC1234
GET /get-workorders.php?data_inicio=2025-10-01&data_fim=2025-10-29
GET /get-workorders.php?id=1&with_items=true
```

**Parâmetros:**
- `id` (opcional): ID específico da OS
- `ordem_numero` (opcional): Número da ordem
- `status` (opcional): Filtrar por status
- `placa` (opcional): Filtrar por placa
- `data_inicio` (opcional): Data início (formato: YYYY-MM-DD)
- `data_fim` (opcional): Data fim (formato: YYYY-MM-DD)
- `with_items` (opcional): Incluir itens da OS (true/false)

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "ordem_numero": "OS-2025-001",
      "placa_veiculo": "ABC1234",
      "km_veiculo": 45000,
      "status": "Aberta",
      "prioridade": "Média",
      "valor_total": 450.00,
      "veiculo_nome": "STRADA 1.4",
      "motorista_nome": "João Silva",
      "total_itens": 3
    }
  ],
  "stats": {
    "total": 10,
    "abertas": 3,
    "finalizadas": 7
  }
}
```

### 2. Criar Ordem de Serviço (POST)

```http
POST /save-workorder.php
Content-Type: application/json

{
  "ordem_numero": "OS-2025-002",
  "placa_veiculo": "ABC1234",
  "km_veiculo": 45500,
  "responsavel": "Mecânico João",
  "oficina": "Oficina Centro",
  "status": "Aberta",
  "prioridade": "Alta",
  "ocorrencia": "Corretiva",
  "tipo_servico": "Mecânica",
  "defeito_reclamado": "Barulho no motor",
  "observacoes": "Verificar urgente",
  "itens": [
    {
      "tipo": "Serviço",
      "codigo": "SRV001",
      "descricao": "Troca de óleo",
      "quantidade": 1,
      "valor_unitario": 80.00
    },
    {
      "tipo": "Produto",
      "codigo": "PRD001",
      "descricao": "Filtro de óleo",
      "quantidade": 1,
      "valor_unitario": 25.00
    }
  ]
}
```

**Campos obrigatórios:**
- `ordem_numero`: Número único da OS
- `placa_veiculo`: Placa do veículo
- `km_veiculo`: Quilometragem atual

**Resposta:**
```json
{
  "success": true,
  "message": "Ordem de serviço criada com sucesso",
  "id": 2,
  "ordem_numero": "OS-2025-002"
}
```

### 3. Atualizar Ordem de Serviço (PUT)

```http
PUT /api-ordemservico.php
Content-Type: application/json

{
  "id": 2,
  "ordem_numero": "OS-2025-002",
  "status": "Execução",
  "defeito_constatado": "Vazamento de óleo",
  "solucao_executada": "Substituído junta do cárter"
}
```

### 4. Atualizar Status da OS (PATCH)

```http
PATCH /api-ordemservico.php
Content-Type: application/json

{
  "id": 2,
  "status": "Finalizada"
}
```

**Status disponíveis:**
- `Aberta`
- `Diagnóstico`
- `Orçamento`
- `Execução`
- `Finalizada`
- `Cancelada`

### 5. Cancelar Ordem de Serviço (DELETE)

```http
DELETE /api-ordemservico.php?id=2
```

---

## Estrutura do Banco de Dados

### Tabela: `servicos`
Cadastro de serviços, produtos e mão de obra.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | ID único (PK) |
| codigo | VARCHAR(50) | Código único do serviço |
| nome | VARCHAR(255) | Nome do serviço |
| tipo | ENUM | Serviço, Produto ou Mão de Obra |
| categoria | VARCHAR(100) | Categoria (Mecânica, Elétrica, etc) |
| valor_padrao | DECIMAL(10,2) | Valor padrão |
| ativo | TINYINT(1) | Status (1=ativo, 0=inativo) |

### Tabela: `ordemservico`
Ordens de serviço para manutenção.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | ID único (PK) |
| ordem_numero | VARCHAR(50) | Número único da OS |
| veiculo_id | INT | FK para veiculos |
| placa_veiculo | VARCHAR(20) | Placa do veículo |
| km_veiculo | INT | Quilometragem |
| status | ENUM | Status atual da OS |
| prioridade | ENUM | Baixa, Média, Alta, Urgente |
| ocorrencia | ENUM | Corretiva, Preventiva, Garantia |
| valor_total | DECIMAL(10,2) | Valor total calculado |
| data_criacao | DATETIME | Data de abertura |

### Tabela: `ordemservico_itens`
Itens vinculados às ordens de serviço.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | ID único (PK) |
| os_id | INT | FK para ordemservico |
| servico_id | INT | FK para servicos (opcional) |
| tipo | ENUM | Serviço, Produto ou Mão de Obra |
| descricao | VARCHAR(255) | Descrição do item |
| quantidade | DECIMAL(10,3) | Quantidade |
| valor_unitario | DECIMAL(10,2) | Valor unitário |
| valor_total | DECIMAL(10,2) | Calculado automaticamente |

---

## Exemplos de Uso

### JavaScript/Fetch API

#### Criar Ordem de Serviço
```javascript
async function criarOS() {
  const dados = {
    ordem_numero: 'OS-2025-003',
    placa_veiculo: 'XYZ5678',
    km_veiculo: 32000,
    defeito_reclamado: 'Troca de pneus',
    itens: [
      {
        tipo: 'Serviço',
        descricao: 'Troca de 4 pneus',
        quantidade: 4,
        valor_unitario: 350.00
      }
    ]
  };

  const response = await fetch('save-workorder.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(dados)
  });

  const result = await response.json();
  console.log(result);
}
```

#### Listar Ordens Abertas
```javascript
async function listarOSAbertas() {
  const response = await fetch('get-workorders.php?status=Aberta');
  const result = await response.json();

  if (result.success) {
    console.log('Total de OS abertas:', result.stats.abertas);
    result.data.forEach(os => {
      console.log(`${os.ordem_numero} - ${os.placa_veiculo}`);
    });
  }
}
```

#### Atualizar Status
```javascript
async function finalizarOS(id) {
  const response = await fetch('api-ordemservico.php', {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id, status: 'Finalizada' })
  });

  const result = await response.json();
  console.log(result.message);
}
```

### jQuery

```javascript
// Buscar serviços ativos
$.get('api-servicos.php?ativo=1', function(data) {
  if (data.success) {
    data.data.forEach(servico => {
      console.log(servico.nome, servico.valor_padrao);
    });
  }
});

// Criar novo serviço
$.ajax({
  url: 'api-servicos.php',
  method: 'POST',
  contentType: 'application/json',
  data: JSON.stringify({
    codigo: 'SRV010',
    nome: 'Revisão dos 10.000 km',
    tipo: 'Serviço',
    valor_padrao: 350.00
  }),
  success: function(response) {
    alert('Serviço criado com ID: ' + response.id);
  }
});
```

---

## Códigos de Status HTTP

| Código | Significado |
|--------|-------------|
| 200 | OK - Requisição bem-sucedida |
| 201 | Created - Recurso criado com sucesso |
| 400 | Bad Request - Dados inválidos |
| 404 | Not Found - Recurso não encontrado |
| 405 | Method Not Allowed - Método HTTP não permitido |
| 409 | Conflict - Conflito (ex: código duplicado) |
| 500 | Internal Server Error - Erro no servidor |

---

## Notas Importantes

1. **Transações:** As APIs usam transações do MySQL para garantir integridade dos dados
2. **Validação:** Campos obrigatórios são validados antes de inserir no banco
3. **Cálculo Automático:** O `valor_total` dos itens é calculado automaticamente pelo banco
4. **Segurança:** Use prepared statements (PDO) para prevenir SQL Injection
5. **CORS:** As APIs já incluem headers CORS para permitir requisições cross-origin

---

## Suporte

Para reportar bugs ou solicitar melhorias, entre em contato com a equipe de desenvolvimento.

**Última atualização:** 29/10/2025
