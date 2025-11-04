# Documentação do Banco de Dados - FleetFlow

## Índice
1. [Visão Geral](#visão-geral)
2. [Arquitetura do Banco](#arquitetura-do-banco)
3. [Tabelas Principais](#tabelas-principais)
4. [Relacionamentos](#relacionamentos)
5. [Fluxos de Dados](#fluxos-de-dados)
6. [Exemplos Práticos](#exemplos-práticos)
7. [Queries Úteis](#queries-úteis)
8. [Índices e Performance](#índices-e-performance)
9. [Manutenção do Banco](#manutenção-do-banco)
10. [Segurança](#segurança)

---

## Visão Geral

O sistema FleetFlow utiliza um banco de dados MySQL remoto para gerenciar toda a operação de frota de veículos. O banco está hospedado em:

- **Host:** 187.49.226.10:3306
- **Database:** f137049_in9aut
- **User:** f137049_tool
- **Charset:** utf8mb4

### Propósito do Sistema

O banco de dados foi projetado para:
- Rastrear veículos em tempo real
- Gerenciar motoristas e suas atribuições
- Controlar ordens de serviço (OS) e manutenções
- Monitorar alertas e notificações
- Manter histórico completo de operações

---

## Arquitetura do Banco

### Estrutura Modular

O banco de dados é dividido em módulos funcionais:

```
FleetFlow Database
│
├── Módulo de Veículos
│   └── Vehicles (tabela principal)
│
├── Módulo de Motoristas
│   └── Drivers (tabela principal)
│
├── Módulo de Manutenção
│   ├── FF_WorkOrders (ordens de serviço)
│   ├── FF_WorkOrderItems (itens da OS)
│   ├── FF_Maintenances (manutenções agendadas)
│   └── FF_MaintenanceHistory (histórico)
│
└── Módulo de Alertas
    └── FF_Alerts (alertas do sistema)
```

### Prefixo FF_

As tabelas personalizadas do FleetFlow usam o prefixo `FF_` para diferenciá-las das tabelas base do sistema de rastreamento.

---

## Tabelas Principais

### 1. Vehicles

**Propósito:** Armazena todos os veículos da frota com informações de rastreamento.

```sql
CREATE TABLE Vehicles (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    LicensePlate VARCHAR(20) UNIQUE NOT NULL,
    VehicleName VARCHAR(100),
    VehicleYear VARCHAR(4),
    VehicleBrand VARCHAR(50),
    DriverId INT,
    LastSpeed DECIMAL(5,2),
    LastAddress TEXT,
    EngineStatus VARCHAR(20),
    IgnitionStatus VARCHAR(50),
    LastUpdate DATETIME,
    INDEX idx_plate (LicensePlate),
    INDEX idx_driver (DriverId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Campos Importantes:**
- `Id`: Identificador único do veículo
- `LicensePlate`: Placa do veículo (único)
- `IgnitionStatus`: Status atual (Ativo, Em Manutenção, Inativo, etc.)
- `DriverId`: Motorista atualmente atribuído
- `LastUpdate`: Última atualização de posição GPS

**Estados Possíveis do IgnitionStatus:**
- Ativo
- Em Manutenção
- Inativo
- Desconhecido

---

### 2. Drivers

**Propósito:** Cadastro de motoristas da frota.

```sql
CREATE TABLE Drivers (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    CPF VARCHAR(14) UNIQUE,
    CNH VARCHAR(20) UNIQUE,
    CNHCategory VARCHAR(5),
    CNHExpiry DATE,
    Phone VARCHAR(20),
    Email VARCHAR(100),
    HireDate DATE,
    Status VARCHAR(20) DEFAULT 'Ativo',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cpf (CPF),
    INDEX idx_status (Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Campos Importantes:**
- `Id`: Identificador único do motorista
- `CPF/CNH`: Documentos únicos
- `CNHCategory`: Categoria da CNH (A, B, C, D, E)
- `CNHExpiry`: Data de validade da CNH
- `Status`: Ativo, Inativo, Afastado

---

### 3. FF_WorkOrders

**Propósito:** Gerencia ordens de serviço (OS) abertas para manutenção de veículos.

```sql
CREATE TABLE FF_WorkOrders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT,
    service_type VARCHAR(50),
    description TEXT,
    priority ENUM('Baixa', 'Média', 'Alta', 'Urgente') DEFAULT 'Média',
    status ENUM('Aberta', 'Em Andamento', 'Aguardando Peças', 'Concluída', 'Cancelada') DEFAULT 'Aberta',
    scheduled_date DATETIME,
    completion_date DATETIME,
    labor_cost DECIMAL(10,2) DEFAULT 0.00,
    parts_cost DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(Id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES Drivers(Id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Campos Importantes:**
- `order_number`: Número único da OS (formato: OS-YYYYMMDD-XXXX)
- `vehicle_id`: FK para Vehicles (CASCADE)
- `status`: Estado atual da OS
- `labor_cost/parts_cost`: Custos separados
- `total_cost`: Custo total (mão de obra + peças)

**Ciclo de Vida de uma OS:**
1. Aberta → OS criada
2. Em Andamento → Serviço iniciado
3. Aguardando Peças → Parado esperando peças
4. Concluída → Serviço finalizado
5. Cancelada → OS cancelada

---

### 4. FF_WorkOrderItems

**Propósito:** Itens individuais (peças/serviços) de cada ordem de serviço.

```sql
CREATE TABLE FF_WorkOrderItems (
    id INT PRIMARY KEY AUTO_INCREMENT,
    work_order_id INT NOT NULL,
    item_type ENUM('Peça', 'Serviço', 'Mão de Obra') NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    total_price DECIMAL(10,2) DEFAULT 0.00,
    supplier VARCHAR(100),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES FF_WorkOrders(id) ON DELETE CASCADE,
    INDEX idx_work_order (work_order_id),
    INDEX idx_item_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Campos Importantes:**
- `work_order_id`: FK para FF_WorkOrders (CASCADE)
- `item_type`: Tipo do item (Peça, Serviço, Mão de Obra)
- `total_price`: quantity × unit_price

**Exemplo de Uso:**
Uma OS pode ter múltiplos itens:
- Item 1: Peça - Filtro de óleo - 1 un × R$ 45,00
- Item 2: Peça - Óleo lubrificante 5W30 - 4 L × R$ 35,00
- Item 3: Serviço - Troca de óleo - 1 × R$ 80,00

---

### 5. FF_Maintenances

**Propósito:** Manutenções preventivas agendadas.

```sql
CREATE TABLE FF_Maintenances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    scheduled_date DATETIME NOT NULL,
    completed_date DATETIME,
    mileage_at_maintenance INT,
    status ENUM('Pendente', 'Em Progresso', 'Concluída', 'Cancelada') DEFAULT 'Pendente',
    cost DECIMAL(10,2) DEFAULT 0.00,
    performed_by VARCHAR(100),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(Id) ON DELETE CASCADE,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Tipos Comuns de Manutenção:**
- Preventiva (revisões programadas)
- Corretiva (reparos de falhas)
- Preditiva (baseada em diagnóstico)

**Diferença entre FF_Maintenances e FF_WorkOrders:**
- **FF_Maintenances**: Manutenções agendadas/planejadas
- **FF_WorkOrders**: Ordens de serviço detalhadas com custos e peças

---

### 6. FF_MaintenanceHistory

**Propósito:** Histórico completo de todas as manutenções realizadas.

```sql
CREATE TABLE FF_MaintenanceHistory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    maintenance_date DATETIME NOT NULL,
    mileage INT,
    cost DECIMAL(10,2) DEFAULT 0.00,
    performed_by VARCHAR(100),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(Id) ON DELETE CASCADE,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_maintenance_date (maintenance_date),
    INDEX idx_maintenance_type (maintenance_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Uso:**
Mantém registro histórico permanente mesmo após a OS ou manutenção ser concluída. Útil para:
- Análise de custos ao longo do tempo
- Planejamento de manutenções futuras
- Relatórios gerenciais

---

### 7. FF_Alerts

**Propósito:** Sistema de alertas e notificações.

```sql
CREATE TABLE FF_Alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    severity ENUM('Info', 'Aviso', 'Urgente', 'Crítico') DEFAULT 'Info',
    alert_type VARCHAR(50),
    vehicle_id INT,
    driver_id INT,
    due_date DATETIME,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at DATETIME,
    resolved_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(Id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES Drivers(Id) ON DELETE CASCADE,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_driver (driver_id),
    INDEX idx_severity (severity),
    INDEX idx_resolved (is_resolved),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Tipos de Alertas:**
- CNH vencida ou a vencer
- Manutenção atrasada
- Documento do veículo vencido
- Velocidade excessiva
- Área restrita violada
- Combustível baixo

**Níveis de Severidade:**
- **Info**: Informativo
- **Aviso**: Requer atenção
- **Urgente**: Ação necessária em breve
- **Crítico**: Ação imediata necessária

---

## Relacionamentos

### Diagrama de Relacionamentos

```
┌─────────────┐
│  Vehicles   │
│  (1)        │
└──────┬──────┘
       │
       │ 1:N (um veículo, muitas OS)
       │
       ▼
┌─────────────────┐
│ FF_WorkOrders   │
│  (N)            │
└────────┬────────┘
         │
         │ 1:N (uma OS, muitos itens)
         │
         ▼
┌──────────────────────┐
│ FF_WorkOrderItems    │
│  (N)                 │
└──────────────────────┘

┌─────────────┐
│  Vehicles   │
│  (1)        │
└──────┬──────┘
       │
       │ 1:N (um veículo, muitas manutenções)
       │
       ▼
┌──────────────────┐
│ FF_Maintenances  │
│  (N)             │
└──────────────────┘

┌─────────────┐
│  Vehicles   │
│  (1)        │
└──────┬──────┘
       │
       │ 1:N (um veículo, muitos alertas)
       │
       ▼
┌─────────────┐
│  FF_Alerts  │
│  (N)        │
└─────────────┘

┌─────────────┐
│   Drivers   │
│  (1)        │
└──────┬──────┘
       │
       │ 1:N (um motorista, muitos alertas)
       │
       ▼
┌─────────────┐
│  FF_Alerts  │
│  (N)        │
└─────────────┘
```

### Descrição dos Relacionamentos

#### 1. Vehicles → FF_WorkOrders (1:N)
- **Tipo:** Um para Muitos
- **Chave Estrangeira:** FF_WorkOrders.vehicle_id → Vehicles.Id
- **ON DELETE:** CASCADE (se veículo é deletado, todas as OS são deletadas)
- **Significado:** Cada veículo pode ter múltiplas ordens de serviço

#### 2. FF_WorkOrders → FF_WorkOrderItems (1:N)
- **Tipo:** Um para Muitos
- **Chave Estrangeira:** FF_WorkOrderItems.work_order_id → FF_WorkOrders.id
- **ON DELETE:** CASCADE (se OS é deletada, todos os itens são deletados)
- **Significado:** Cada OS pode ter múltiplos itens (peças/serviços)

#### 3. Vehicles → FF_Maintenances (1:N)
- **Tipo:** Um para Muitos
- **Chave Estrangeira:** FF_Maintenances.vehicle_id → Vehicles.Id
- **ON DELETE:** CASCADE
- **Significado:** Cada veículo pode ter múltiplas manutenções agendadas

#### 4. Vehicles → FF_MaintenanceHistory (1:N)
- **Tipo:** Um para Muitos
- **Chave Estrangeira:** FF_MaintenanceHistory.vehicle_id → Vehicles.Id
- **ON DELETE:** CASCADE
- **Significado:** Cada veículo tem um histórico de manutenções

#### 5. Vehicles → FF_Alerts (1:N)
- **Tipo:** Um para Muitos
- **Chave Estrangeira:** FF_Alerts.vehicle_id → Vehicles.Id
- **ON DELETE:** CASCADE
- **Significado:** Cada veículo pode ter múltiplos alertas

#### 6. Drivers → FF_WorkOrders (1:N)
- **Tipo:** Um para Muitos
- **Chave Estrangeira:** FF_WorkOrders.driver_id → Drivers.Id
- **ON DELETE:** SET NULL (se motorista é deletado, OS mantém mas driver_id = NULL)
- **Significado:** Cada motorista pode estar associado a múltiplas OS

#### 7. Drivers → FF_Alerts (1:N)
- **Tipo:** Um para Muitos
- **Chave Estrangeira:** FF_Alerts.driver_id → Drivers.Id
- **ON DELETE:** CASCADE
- **Significado:** Cada motorista pode ter múltiplos alertas

---

## Fluxos de Dados

### Fluxo 1: Criação de Ordem de Serviço

**Passo a Passo:**

```
1. Usuário seleciona veículo no formulário
   ↓
2. Sistema busca ID do veículo no banco
   ↓
3. Usuário preenche dados da OS
   ↓
4. Sistema gera número único (OS-YYYYMMDD-XXXX)
   ↓
5. BEGIN TRANSACTION
   ↓
6. INSERT em FF_WorkOrders
   ↓
7. INSERT em FF_WorkOrderItems (para cada peça/serviço)
   ↓
8. UPDATE Vehicles SET IgnitionStatus = 'Em Manutenção'
   ↓
9. COMMIT TRANSACTION
   ↓
10. Sistema retorna sucesso
```

**Código SQL do Fluxo:**

```sql
-- Passo 5: Iniciar transação
START TRANSACTION;

-- Passo 6: Inserir OS
INSERT INTO FF_WorkOrders (
    order_number,
    vehicle_id,
    driver_id,
    service_type,
    description,
    priority,
    status,
    scheduled_date,
    labor_cost,
    parts_cost,
    total_cost,
    notes
) VALUES (
    'OS-20251028-0001',
    15,
    3,
    'Manutenção Preventiva',
    'Troca de óleo e filtros',
    'Média',
    'Aberta',
    '2025-10-30 08:00:00',
    80.00,
    120.00,
    200.00,
    'Cliente solicitou revisão completa'
);

-- Passo 7: Inserir itens da OS
INSERT INTO FF_WorkOrderItems (
    work_order_id,
    item_type,
    description,
    quantity,
    unit_price,
    total_price
) VALUES
    (LAST_INSERT_ID(), 'Peça', 'Filtro de óleo', 1, 45.00, 45.00),
    (LAST_INSERT_ID(), 'Peça', 'Óleo 5W30 - 4L', 1, 75.00, 75.00),
    (LAST_INSERT_ID(), 'Serviço', 'Troca de óleo', 1, 80.00, 80.00);

-- Passo 8: Atualizar status do veículo
UPDATE Vehicles
SET IgnitionStatus = 'Em Manutenção'
WHERE Id = 15;

-- Passo 9: Confirmar transação
COMMIT;
```

---

### Fluxo 2: Conclusão de Manutenção

**Passo a Passo:**

```
1. Mecânico finaliza serviço
   ↓
2. Sistema atualiza FF_WorkOrders.status = 'Concluída'
   ↓
3. Sistema define completion_date = NOW()
   ↓
4. Sistema cria registro em FF_MaintenanceHistory
   ↓
5. Sistema atualiza Vehicles.IgnitionStatus = 'Ativo'
   ↓
6. Sistema envia notificação
```

**Código SQL:**

```sql
START TRANSACTION;

-- Atualizar OS como concluída
UPDATE FF_WorkOrders
SET status = 'Concluída',
    completion_date = NOW(),
    updated_at = NOW()
WHERE id = 123;

-- Inserir no histórico
INSERT INTO FF_MaintenanceHistory (
    vehicle_id,
    maintenance_type,
    description,
    maintenance_date,
    mileage,
    cost,
    performed_by,
    notes
)
SELECT
    vehicle_id,
    service_type,
    description,
    NOW(),
    (SELECT LastMileage FROM Vehicles WHERE Id = vehicle_id),
    total_cost,
    'João Silva - Mecânico',
    CONCAT('OS: ', order_number)
FROM FF_WorkOrders
WHERE id = 123;

-- Atualizar status do veículo
UPDATE Vehicles v
INNER JOIN FF_WorkOrders wo ON v.Id = wo.vehicle_id
SET v.IgnitionStatus = 'Ativo'
WHERE wo.id = 123;

COMMIT;
```

---

### Fluxo 3: Criação de Alerta Automático

**Exemplo: Alerta de CNH Vencida**

```
1. Sistema executa job diário
   ↓
2. Query busca motoristas com CNH vencendo em 30 dias
   ↓
3. Para cada motorista encontrado:
   ↓
4. Verifica se já existe alerta não resolvido
   ↓
5. Se não existe, cria novo alerta
   ↓
6. Envia notificação por email/SMS
```

**Código SQL:**

```sql
-- Buscar motoristas com CNH vencendo
SELECT
    Id,
    Name,
    CNH,
    CNHExpiry,
    DATEDIFF(CNHExpiry, CURDATE()) as dias_restantes
FROM Drivers
WHERE Status = 'Ativo'
  AND CNHExpiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  AND CNHExpiry >= CURDATE()
  AND Id NOT IN (
      SELECT driver_id
      FROM FF_Alerts
      WHERE alert_type = 'CNH Vencendo'
        AND is_resolved = FALSE
        AND driver_id IS NOT NULL
  );

-- Criar alerta para cada motorista
INSERT INTO FF_Alerts (
    title,
    description,
    severity,
    alert_type,
    driver_id,
    due_date
)
SELECT
    CONCAT('CNH vencendo - ', Name),
    CONCAT('A CNH do motorista ', Name, ' (', CNH, ') vence em ', DATEDIFF(CNHExpiry, CURDATE()), ' dias.'),
    CASE
        WHEN DATEDIFF(CNHExpiry, CURDATE()) <= 7 THEN 'Urgente'
        WHEN DATEDIFF(CNHExpiry, CURDATE()) <= 15 THEN 'Aviso'
        ELSE 'Info'
    END,
    'CNH Vencendo',
    Id,
    CNHExpiry
FROM Drivers
WHERE [condições acima];
```

---

## Exemplos Práticos

### Exemplo 1: Buscar todos os veículos em manutenção

```sql
SELECT
    v.LicensePlate as Placa,
    v.VehicleName as Modelo,
    wo.order_number as NumeroOS,
    wo.service_type as TipoServico,
    wo.status as StatusOS,
    wo.scheduled_date as DataAgendada,
    wo.total_cost as CustoTotal
FROM Vehicles v
INNER JOIN FF_WorkOrders wo ON v.Id = wo.vehicle_id
WHERE v.IgnitionStatus = 'Em Manutenção'
  AND wo.status IN ('Aberta', 'Em Andamento', 'Aguardando Peças')
ORDER BY wo.scheduled_date;
```

**Resultado Esperado:**
```
Placa    | Modelo      | NumeroOS         | TipoServico | StatusOS      | DataAgendada | CustoTotal
---------|-------------|------------------|-------------|---------------|--------------|------------
ABC1234  | Fiat Uno    | OS-20251028-0001 | Preventiva  | Em Andamento  | 2025-10-30   | 350.00
XYZ5678  | Ford Cargo  | OS-20251028-0005 | Corretiva   | Aguard. Peças | 2025-10-29   | 1200.00
```

---

### Exemplo 2: Relatório de custos por veículo no mês

```sql
SELECT
    v.LicensePlate as Placa,
    v.VehicleName as Modelo,
    COUNT(wo.id) as TotalOS,
    SUM(wo.labor_cost) as CustoMaoObra,
    SUM(wo.parts_cost) as CustoPecas,
    SUM(wo.total_cost) as CustoTotal,
    AVG(wo.total_cost) as MediaPorOS
FROM Vehicles v
LEFT JOIN FF_WorkOrders wo ON v.Id = wo.vehicle_id
WHERE wo.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
  AND wo.status = 'Concluída'
GROUP BY v.Id, v.LicensePlate, v.VehicleName
ORDER BY CustoTotal DESC;
```

---

### Exemplo 3: Listar alertas críticos não resolvidos

```sql
SELECT
    a.title as Titulo,
    a.description as Descricao,
    a.severity as Severidade,
    v.LicensePlate as VeiculoPlaca,
    d.Name as MotoristaNome,
    CASE
        WHEN DATEDIFF(a.due_date, CURDATE()) < 0 THEN 'VENCIDO'
        WHEN DATEDIFF(a.due_date, CURDATE()) = 0 THEN 'HOJE'
        WHEN DATEDIFF(a.due_date, CURDATE()) = 1 THEN 'AMANHÃ'
        ELSE CONCAT('Em ', DATEDIFF(a.due_date, CURDATE()), ' dias')
    END as Prazo,
    a.created_at as CriadoEm
FROM FF_Alerts a
LEFT JOIN Vehicles v ON a.vehicle_id = v.Id
LEFT JOIN Drivers d ON a.driver_id = d.Id
WHERE a.is_resolved = FALSE
  AND a.severity IN ('Crítico', 'Urgente')
ORDER BY
    FIELD(a.severity, 'Crítico', 'Urgente'),
    a.due_date ASC;
```

---

### Exemplo 4: Histórico completo de um veículo

```sql
SELECT
    'Manutenção' as Tipo,
    mh.maintenance_type as Descricao,
    mh.maintenance_date as Data,
    mh.mileage as Quilometragem,
    mh.cost as Custo,
    mh.performed_by as Responsavel
FROM FF_MaintenanceHistory mh
WHERE mh.vehicle_id = 15

UNION ALL

SELECT
    'OS' as Tipo,
    CONCAT(wo.service_type, ' - ', wo.description) as Descricao,
    wo.completion_date as Data,
    NULL as Quilometragem,
    wo.total_cost as Custo,
    wo.notes as Responsavel
FROM FF_WorkOrders wo
WHERE wo.vehicle_id = 15
  AND wo.status = 'Concluída'

ORDER BY Data DESC;
```

---

### Exemplo 5: Buscar peças mais utilizadas

```sql
SELECT
    woi.description as Peca,
    COUNT(*) as QuantidadeVezesUsada,
    SUM(woi.quantity) as QuantidadeTotal,
    AVG(woi.unit_price) as PrecoMedio,
    SUM(woi.total_price) as CustoTotalAcumulado,
    MAX(wo.created_at) as UltimaVezUsada
FROM FF_WorkOrderItems woi
INNER JOIN FF_WorkOrders wo ON woi.work_order_id = wo.id
WHERE woi.item_type = 'Peça'
  AND wo.status = 'Concluída'
GROUP BY woi.description
ORDER BY QuantidadeVezesUsada DESC
LIMIT 10;
```

---

## Queries Úteis

### Dashboard - Estatísticas Gerais

```sql
-- Total de veículos
SELECT COUNT(*) as total_veiculos FROM Vehicles;

-- Veículos por status
SELECT
    IgnitionStatus,
    COUNT(*) as quantidade
FROM Vehicles
GROUP BY IgnitionStatus;

-- OS abertas
SELECT COUNT(*) as os_abertas
FROM FF_WorkOrders
WHERE status IN ('Aberta', 'Em Andamento', 'Aguardando Peças');

-- Custo total do mês
SELECT SUM(total_cost) as custo_mensal
FROM FF_WorkOrders
WHERE MONTH(created_at) = MONTH(CURDATE())
  AND YEAR(created_at) = YEAR(CURDATE())
  AND status = 'Concluída';

-- Alertas não resolvidos
SELECT
    severity,
    COUNT(*) as quantidade
FROM FF_Alerts
WHERE is_resolved = FALSE
GROUP BY severity;
```

---

### Manutenção - Próximas Manutenções

```sql
SELECT
    v.LicensePlate as Placa,
    v.VehicleName as Modelo,
    m.maintenance_type as Tipo,
    m.scheduled_date as DataAgendada,
    DATEDIFF(m.scheduled_date, CURDATE()) as DiasRestantes,
    m.status as Status
FROM FF_Maintenances m
INNER JOIN Vehicles v ON m.vehicle_id = v.Id
WHERE m.status IN ('Pendente', 'Em Progresso')
  AND m.scheduled_date >= CURDATE()
ORDER BY m.scheduled_date ASC
LIMIT 20;
```

---

### Motoristas - CNH vencidas ou a vencer

```sql
SELECT
    d.Name as Nome,
    d.CNH as NumCNH,
    d.CNHCategory as Categoria,
    d.CNHExpiry as Validade,
    DATEDIFF(d.CNHExpiry, CURDATE()) as DiasRestantes,
    CASE
        WHEN d.CNHExpiry < CURDATE() THEN 'VENCIDA'
        WHEN DATEDIFF(d.CNHExpiry, CURDATE()) <= 30 THEN 'VENCE EM BREVE'
        ELSE 'OK'
    END as Situacao
FROM Drivers d
WHERE d.Status = 'Ativo'
  AND (
      d.CNHExpiry < CURDATE()
      OR DATEDIFF(d.CNHExpiry, CURDATE()) <= 90
  )
ORDER BY d.CNHExpiry ASC;
```

---

### Veículos - Ranking de custos

```sql
SELECT
    v.LicensePlate as Placa,
    v.VehicleName as Modelo,
    v.VehicleYear as Ano,
    COUNT(wo.id) as TotalOS,
    SUM(wo.total_cost) as CustoTotal,
    AVG(wo.total_cost) as CustoMedio,
    MAX(wo.created_at) as UltimaOS
FROM Vehicles v
LEFT JOIN FF_WorkOrders wo ON v.Id = wo.vehicle_id
WHERE wo.status = 'Concluída'
  AND wo.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY v.Id, v.LicensePlate, v.VehicleName, v.VehicleYear
ORDER BY CustoTotal DESC
LIMIT 10;
```

---

## Índices e Performance

### Índices Existentes

Todas as tabelas possuem índices estratégicos para otimizar consultas:

#### Vehicles
- `PRIMARY KEY (Id)` - Busca por ID
- `UNIQUE INDEX (LicensePlate)` - Busca por placa
- `INDEX idx_driver (DriverId)` - Join com Drivers

#### FF_WorkOrders
- `PRIMARY KEY (id)`
- `UNIQUE INDEX (order_number)` - Busca por número da OS
- `INDEX idx_vehicle (vehicle_id)` - Join com Vehicles
- `INDEX idx_status (status)` - Filtro por status
- `INDEX idx_scheduled_date (scheduled_date)` - Ordenação por data

#### FF_Alerts
- `PRIMARY KEY (id)`
- `INDEX idx_vehicle (vehicle_id)`
- `INDEX idx_driver (driver_id)`
- `INDEX idx_severity (severity)` - Filtro por severidade
- `INDEX idx_resolved (is_resolved)` - Filtro por resolvido
- `INDEX idx_due_date (due_date)` - Ordenação por vencimento

### Dicas de Performance

#### 1. Use EXPLAIN para analisar queries

```sql
EXPLAIN SELECT * FROM FF_WorkOrders WHERE vehicle_id = 15;
```

#### 2. Evite SELECT *

Busque apenas as colunas necessárias:

```sql
-- Ruim
SELECT * FROM Vehicles;

-- Bom
SELECT Id, LicensePlate, VehicleName, IgnitionStatus FROM Vehicles;
```

#### 3. Use LIMIT em consultas grandes

```sql
SELECT * FROM FF_MaintenanceHistory
ORDER BY maintenance_date DESC
LIMIT 100;
```

#### 4. Prefira JOIN a subconsultas quando possível

```sql
-- Menos eficiente
SELECT * FROM Vehicles
WHERE Id IN (SELECT vehicle_id FROM FF_WorkOrders WHERE status = 'Aberta');

-- Mais eficiente
SELECT DISTINCT v.*
FROM Vehicles v
INNER JOIN FF_WorkOrders wo ON v.Id = wo.vehicle_id
WHERE wo.status = 'Aberta';
```

---

## Manutenção do Banco

### Backup Regular

Recomenda-se backup diário do banco de dados:

```bash
# Backup completo
mysqldump -h 187.49.226.10 -P 3306 -u f137049_tool -p f137049_in9aut > backup_$(date +%Y%m%d).sql

# Backup apenas das tabelas FF_
mysqldump -h 187.49.226.10 -P 3306 -u f137049_tool -p f137049_in9aut \
  FF_WorkOrders FF_WorkOrderItems FF_Maintenances FF_MaintenanceHistory FF_Alerts \
  > backup_ff_$(date +%Y%m%d).sql
```

### Limpeza de Dados Antigos

Execute periodicamente (ex: mensalmente):

```sql
-- Deletar alertas resolvidos há mais de 6 meses
DELETE FROM FF_Alerts
WHERE is_resolved = TRUE
  AND resolved_at < DATE_SUB(CURDATE(), INTERVAL 6 MONTH);

-- Arquivar OS concluídas há mais de 1 ano
-- (recomenda-se criar tabela FF_WorkOrders_Archive antes)
INSERT INTO FF_WorkOrders_Archive
SELECT * FROM FF_WorkOrders
WHERE status = 'Concluída'
  AND completion_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);

DELETE FROM FF_WorkOrders
WHERE status = 'Concluída'
  AND completion_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);
```

### Otimização de Tabelas

Execute semanalmente:

```sql
OPTIMIZE TABLE Vehicles;
OPTIMIZE TABLE FF_WorkOrders;
OPTIMIZE TABLE FF_WorkOrderItems;
OPTIMIZE TABLE FF_Maintenances;
OPTIMIZE TABLE FF_MaintenanceHistory;
OPTIMIZE TABLE FF_Alerts;
```

---

## Segurança

### Boas Práticas

1. **Prepared Statements**: SEMPRE use prepared statements para evitar SQL Injection

```php
// NUNCA faça isso:
$sql = "SELECT * FROM Vehicles WHERE LicensePlate = '" . $_POST['plate'] . "'";

// SEMPRE faça isso:
$stmt = $pdo->prepare("SELECT * FROM Vehicles WHERE LicensePlate = ?");
$stmt->execute([$_POST['plate']]);
```

2. **Validação de Input**: Valide todos os dados antes de inserir

```php
// Validar número da OS
if (!preg_match('/^OS-\d{8}-\d{4}$/', $orderNumber)) {
    throw new Exception('Número de OS inválido');
}

// Validar status ENUM
$validStatuses = ['Aberta', 'Em Andamento', 'Aguardando Peças', 'Concluída', 'Cancelada'];
if (!in_array($status, $validStatuses)) {
    throw new Exception('Status inválido');
}
```

3. **Transações**: Use transações para operações relacionadas

```php
try {
    $pdo->beginTransaction();

    // Inserir OS
    $stmt1 = $pdo->prepare("INSERT INTO FF_WorkOrders ...");
    $stmt1->execute($data);

    // Inserir itens
    $stmt2 = $pdo->prepare("INSERT INTO FF_WorkOrderItems ...");
    $stmt2->execute($items);

    // Atualizar veículo
    $stmt3 = $pdo->prepare("UPDATE Vehicles ...");
    $stmt3->execute($update);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

4. **Logs de Erro**: Configure error_log para não expor detalhes

```php
// Não retorne erro do banco ao usuário
catch (PDOException $e) {
    error_log("Erro no banco: " . $e->getMessage());

    // Retorne mensagem genérica
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar solicitação'
    ]);
}
```

5. **Permissões do Usuário**: O usuário do banco deve ter apenas as permissões necessárias

```sql
-- Usuário da aplicação NÃO deve ter:
-- DROP, ALTER, CREATE USER, GRANT

-- Usuário deve ter apenas:
-- SELECT, INSERT, UPDATE, DELETE
```

---

## Changelog

### Versão 1.0 - 28/10/2025

**Tabelas Criadas:**
- FF_WorkOrders - Gerenciamento de ordens de serviço
- FF_WorkOrderItems - Itens detalhados das OS
- FF_Maintenances - Manutenções agendadas
- FF_MaintenanceHistory - Histórico de manutenções
- FF_Alerts - Sistema de alertas

**Relacionamentos Estabelecidos:**
- Vehicles → FF_WorkOrders (1:N)
- FF_WorkOrders → FF_WorkOrderItems (1:N)
- Vehicles → FF_Maintenances (1:N)
- Vehicles → FF_MaintenanceHistory (1:N)
- Vehicles → FF_Alerts (1:N)
- Drivers → FF_Alerts (1:N)
- Drivers → FF_WorkOrders (1:N)

**Funcionalidades Implementadas:**
- Criação de OS com atualização automática de status do veículo
- Controle de custos (mão de obra + peças)
- Sistema de alertas com níveis de severidade
- Histórico completo de manutenções

---

## Suporte

Para dúvidas ou problemas relacionados ao banco de dados:

1. Consulte esta documentação primeiro
2. Verifique os logs de erro: `error_log` do PHP
3. Execute queries de diagnóstico da seção "Queries Úteis"
4. Contate o administrador do sistema

**Informações de Conexão:**
- Host: 187.49.226.10:3306
- Database: f137049_in9aut
- User: f137049_tool

---

*Documentação gerada em: 28/10/2025*
*FleetFlow - Sistema de Gerenciamento de Frotas*
