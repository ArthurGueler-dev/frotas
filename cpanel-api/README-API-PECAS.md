# API de Peças - FleetFlow

## Instalação

### 1. Criar as tabelas no banco de dados

Execute o script SQL no phpMyAdmin ou via linha de comando:

```sql
SOURCE criar-tabelas-pecas.sql;
```

Ou copie e cole o conteúdo do arquivo `criar-tabelas-pecas.sql` no phpMyAdmin.

### 2. Upload dos arquivos

Faça upload dos seguintes arquivos para a pasta da API no cPanel:

- `pecas-api.php` - API CRUD de peças
- `plano-pecas-api.php` - API para associar peças aos planos de manutenção

---

## API de Peças (`pecas-api.php`)

### Listar todas as peças

```http
GET /pecas-api.php
```

**Resposta:**
```json
{
  "success": true,
  "total": 24,
  "count": 24,
  "categorias": ["Correias", "Elétrica", "Filtros", ...],
  "data": [...]
}
```

### Buscar peça por ID

```http
GET /pecas-api.php?id=1
```

### Filtrar por categoria

```http
GET /pecas-api.php?categoria=Filtros
```

### Buscar por nome ou código

```http
GET /pecas-api.php?busca=oleo
```

### Criar nova peça

```http
POST /pecas-api.php
Content-Type: application/json

{
  "codigo": "FLT-OL-002",
  "nome": "Filtro de Óleo Premium",
  "descricao": "Filtro de óleo de alta performance",
  "unidade": "un",
  "custo_unitario": 55.00,
  "estoque_minimo": 5,
  "estoque_atual": 10,
  "fornecedor": "AutoPeças Brasil",
  "categoria": "Filtros"
}
```

**Campos obrigatórios:** `nome`

### Atualizar peça

```http
PUT /pecas-api.php?id=1
Content-Type: application/json

{
  "custo_unitario": 60.00,
  "estoque_atual": 15
}
```

### Remover peça (soft delete)

```http
DELETE /pecas-api.php?id=1
```

---

## API de Associação Peças-Plano (`plano-pecas-api.php`)

### Listar peças de um item de plano

```http
GET /plano-pecas-api.php?plano_item_id=1
```

**Resposta:**
```json
{
  "success": true,
  "count": 3,
  "custo_total_pecas": 125.00,
  "data": [
    {
      "id": 1,
      "plano_item_id": 1,
      "peca_id": 1,
      "quantidade": 1,
      "codigo": "FLT-OL-001",
      "nome": "Filtro de Óleo",
      "custo_unitario": 35.00,
      "custo_total": 35.00
    },
    ...
  ]
}
```

### Adicionar peça ao item de plano

```http
POST /plano-pecas-api.php
Content-Type: application/json

{
  "plano_item_id": 1,
  "peca_id": 5,
  "quantidade": 4
}
```

**Nota:** Se a peça já estiver associada ao item, a quantidade será incrementada.

### Atualizar quantidade

```http
PUT /plano-pecas-api.php?id=1
Content-Type: application/json

{
  "quantidade": 5
}
```

### Remover peça do item

```http
DELETE /plano-pecas-api.php?id=1
```

---

## Estrutura das Tabelas

### FF_Pecas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | ID único |
| codigo | VARCHAR(50) | Código da peça |
| nome | VARCHAR(255) | Nome da peça |
| descricao | TEXT | Descrição detalhada |
| unidade | VARCHAR(20) | Unidade de medida (un, litros, kg, etc.) |
| custo_unitario | DECIMAL(10,2) | Custo por unidade |
| estoque_minimo | INT | Quantidade mínima em estoque |
| estoque_atual | INT | Quantidade atual em estoque |
| fornecedor | VARCHAR(255) | Nome do fornecedor |
| categoria | VARCHAR(100) | Categoria (Filtros, Óleos, Freios, etc.) |
| ativo | TINYINT | 1 = ativo, 0 = inativo |

### FF_PlanoManutencao_Pecas

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | ID único |
| plano_item_id | INT | ID do item do plano de manutenção |
| peca_id | INT | ID da peça |
| quantidade | INT | Quantidade necessária |

---

## Categorias de Peças Padrão

- Filtros
- Óleos e Fluidos
- Freios
- Suspensão
- Ignição
- Correias
- Iluminação
- Pneus
- Elétrica

---

## Exemplos de Uso com JavaScript

### Listar todas as peças

```javascript
const response = await fetch('https://seudominio.com/api/pecas-api.php');
const result = await response.json();
console.log(result.data);
```

### Criar nova peça

```javascript
const response = await fetch('https://seudominio.com/api/pecas-api.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    nome: 'Nova Peça',
    custo_unitario: 100.00,
    categoria: 'Filtros'
  })
});
const result = await response.json();
console.log(result);
```

### Adicionar peça a um item de plano

```javascript
const response = await fetch('https://seudominio.com/api/plano-pecas-api.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    plano_item_id: 1,
    peca_id: 5,
    quantidade: 4
  })
});
const result = await response.json();
console.log(result);
```
