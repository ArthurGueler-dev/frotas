# 🗄️ Configuração MySQL + phpMyAdmin

Guia para usar o sistema com MySQL/MariaDB e phpMyAdmin.

## 🎯 Opções de Configuração

### Opção 1: Usar Banco Existente (Recomendado)

Se você já tem o banco MySQL rodando em `187.49.226.10`, use ele:

```bash
# .env
DATABASE_URL=mysql+pymysql://f137049_tool:In9@1234qwer@187.49.226.10:3306/f137049_in9aut
```

### Opção 2: MySQL Local via Docker

Use o MySQL que vem no docker-compose:

```bash
# .env
DATABASE_URL=mysql+pymysql://fleet_user:fleet_pass@mysql:3306/fleet_db
```

### Opção 3: MySQL Local Instalado

Se você já tem MySQL instalado localmente:

```bash
# .env
DATABASE_URL=mysql+pymysql://root:sua_senha@localhost:3306/fleet_db
```

---

## 🚀 Setup Completo com Docker

### 1. Iniciar Stack Completa

```bash
cd fleet-backend

# Copiar e configurar .env
cp .env.example .env
nano .env  # Editar credenciais

# Iniciar tudo (API + Celery + MySQL + phpMyAdmin + Redis + Flower)
docker-compose up -d
```

### 2. Acessar Serviços

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| **API REST** | http://localhost:5001 | - |
| **phpMyAdmin** | http://localhost:8080 | user: fleet_user, pass: fleet_pass |
| **Flower** | http://localhost:5555 | - |
| **MySQL** | localhost:3307 | user: fleet_user, pass: fleet_pass |

### 3. Criar Tabelas

```bash
# Dentro do container da API
docker-compose exec api flask db upgrade

# Ou se preferir, via phpMyAdmin:
# Acesse http://localhost:8080
# Execute as queries SQL abaixo
```

---

## 📊 SQL Schema (Criar Manualmente se Preferir)

### Criar Banco

```sql
CREATE DATABASE IF NOT EXISTS fleet_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fleet_db;
```

### Tabela: areas

```sql
CREATE TABLE areas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  geo_entity_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_areas_geo_entity (geo_entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: vehicles

```sql
CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plate VARCHAR(20) NOT NULL UNIQUE,
  brand VARCHAR(100) DEFAULT NULL,
  model VARCHAR(100) DEFAULT NULL,
  year INT DEFAULT NULL,
  area_id INT DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_vehicles_plate (plate),
  INDEX idx_vehicles_active (is_active),
  INDEX idx_vehicles_area (area_id),

  FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: daily_mileage

```sql
CREATE TABLE daily_mileage (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  date DATE NOT NULL,
  km_driven DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  start_odometer DECIMAL(10, 2) DEFAULT NULL,
  end_odometer DECIMAL(10, 2) DEFAULT NULL,
  calculation_method VARCHAR(50) NOT NULL DEFAULT 'mobile_api',
  data_source VARCHAR(50) DEFAULT NULL,
  record_count INT DEFAULT 0,
  calculation_status VARCHAR(20) DEFAULT 'pending',
  error_message TEXT DEFAULT NULL,
  retry_count INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_daily_mileage_vehicle_date (vehicle_id, date),
  INDEX idx_daily_mileage_date (date),
  INDEX idx_daily_mileage_status_date (calculation_status, date),
  INDEX idx_daily_mileage_vehicle (vehicle_id),

  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: sync_logs

```sql
CREATE TABLE sync_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id VARCHAR(100) DEFAULT NULL,
  task_name VARCHAR(100) NOT NULL,
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'running',
  vehicles_processed INT DEFAULT 0,
  vehicles_success INT DEFAULT 0,
  vehicles_failed INT DEFAULT 0,
  error_message TEXT DEFAULT NULL,

  INDEX idx_sync_logs_task_id (task_id),
  INDEX idx_sync_logs_started (started_at),
  INDEX idx_sync_logs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔧 Usar Banco Existente (187.49.226.10)

### 1. Configurar .env

```bash
# fleet-backend/.env
DATABASE_URL=mysql+pymysql://f137049_tool:In9@1234qwer@187.49.226.10:3306/f137049_in9aut
```

### 2. Criar Tabelas no Banco Existente

Acesse phpMyAdmin do cPanel:
- https://floripa.in9automacao.com.br/cpanel
- phpMyAdmin
- Banco: `f137049_in9aut`
- Execute as queries SQL acima

Ou via terminal:

```bash
mysql -h 187.49.226.10 -u f137049_tool -p f137049_in9aut < schema.sql
# Senha: In9@1234qwer
```

### 3. Popular Dados Iniciais

```sql
-- Criar área padrão
INSERT INTO areas (name, geo_entity_id)
VALUES ('Vitória - ES', NULL);

-- Importar veículos existentes
INSERT INTO vehicles (plate, brand, model, year, area_id, is_active)
SELECT
  placa,
  marca,
  modelo,
  ano,
  1 as area_id,  -- ID da área criada acima
  1 as is_active
FROM FF_Veiculos  -- Se você já tem tabela de veículos
WHERE ativo = 1;
```

---

## 🔍 Verificar Instalação

### 1. Via phpMyAdmin

Acesse http://localhost:8080 (ou cPanel phpMyAdmin)

Verificar tabelas criadas:
```sql
SHOW TABLES;
```

Deve mostrar:
```
+----------------------+
| Tables_in_fleet_db   |
+----------------------+
| areas               |
| daily_mileage       |
| sync_logs           |
| vehicles            |
+----------------------+
```

### 2. Via API

```bash
# Health check
curl http://localhost:5001/health

# Deve retornar:
{
  "status": "healthy",
  "database": "healthy",
  "timestamp": "2025-01-18T..."
}
```

### 3. Via Terminal

```bash
# Conectar ao MySQL
mysql -h localhost -P 3307 -u fleet_user -p
# Senha: fleet_pass

# Ou se usando banco existente:
mysql -h 187.49.226.10 -u f137049_tool -p
# Senha: In9@1234qwer

# Verificar tabelas
USE fleet_db;  -- ou f137049_in9aut
SHOW TABLES;
DESCRIBE vehicles;
SELECT COUNT(*) FROM vehicles;
```

---

## 📦 Dados de Teste

### Inserir via phpMyAdmin

```sql
-- Área
INSERT INTO areas (name, geo_entity_id) VALUES
('Vitória - ES', NULL),
('Vila Velha - ES', NULL);

-- Veículos
INSERT INTO vehicles (plate, brand, model, year, area_id, is_active) VALUES
('ABC1234', 'Toyota', 'Corolla', 2023, 1, 1),
('XYZ5678', 'Honda', 'Civic', 2022, 1, 1),
('DEF9012', 'Chevrolet', 'Onix', 2021, 2, 1);
```

### Ou via API

```bash
# Criar área
curl -X POST http://localhost:5001/api/areas \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Vitória - ES",
    "geo_entity_id": null
  }'

# Criar veículo
curl -X POST http://localhost:5001/api/vehicles \
  -H "Content-Type: application/json" \
  -d '{
    "plate": "ABC1234",
    "brand": "Toyota",
    "model": "Corolla",
    "year": 2023,
    "area_id": 1,
    "is_active": true
  }'
```

---

## 🔄 Migrations com Flask-Migrate

### Inicializar (primeira vez)

```bash
docker-compose exec api flask db init
```

### Criar Migration

```bash
docker-compose exec api flask db migrate -m "Create initial tables"
```

### Aplicar Migration

```bash
docker-compose exec api flask db upgrade
```

### Reverter Migration

```bash
docker-compose exec api flask db downgrade
```

---

## 🛠️ Comandos Úteis MySQL

### Backup

```bash
# Via Docker
docker-compose exec mysql mysqldump -u fleet_user -pfleet_pass fleet_db > backup.sql

# Banco remoto
mysqldump -h 187.49.226.10 -u f137049_tool -p f137049_in9aut > backup.sql
```

### Restore

```bash
# Via Docker
docker-compose exec -T mysql mysql -u fleet_user -pfleet_pass fleet_db < backup.sql

# Banco remoto
mysql -h 187.49.226.10 -u f137049_tool -p f137049_in9aut < backup.sql
```

### Ver Logs MySQL

```bash
docker-compose logs -f mysql
```

### Reiniciar MySQL

```bash
docker-compose restart mysql
```

---

## 🔒 Segurança

### Alterar Senhas (Produção)

```sql
-- No MySQL container
ALTER USER 'fleet_user'@'%' IDENTIFIED BY 'SENHA_FORTE_AQUI';
FLUSH PRIVILEGES;
```

Atualizar .env:
```bash
DATABASE_URL=mysql+pymysql://fleet_user:SENHA_FORTE_AQUI@mysql:3306/fleet_db
```

Reiniciar containers:
```bash
docker-compose restart api celery-worker celery-beat
```

---

## 📊 Monitorar Performance MySQL

### Via phpMyAdmin

- Status → Variáveis
- Status → Processos
- Consultas lentas

### Via SQL

```sql
-- Conexões ativas
SHOW PROCESSLIST;

-- Tabelas e tamanho
SELECT
  table_name,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = 'fleet_db'
ORDER BY (data_length + index_length) DESC;

-- Índices
SHOW INDEX FROM daily_mileage;

-- Queries lentas (configurar slow_query_log)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;  -- Queries > 2 segundos
```

---

## ⚠️ Troubleshooting

### Problema: Erro de conexão

```bash
# Verificar se MySQL está rodando
docker-compose ps mysql

# Ver logs
docker-compose logs mysql

# Testar conexão
docker-compose exec api python -c "
from app import app, db
with app.app_context():
    db.session.execute('SELECT 1')
    print('✅ Conexão OK!')
"
```

### Problema: Charset/Encoding

```sql
-- Verificar charset
SHOW VARIABLES LIKE 'character_set%';

-- Converter tabela
ALTER TABLE daily_mileage
  CONVERT TO CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### Problema: Too many connections

```sql
-- Ver max connections
SHOW VARIABLES LIKE 'max_connections';

-- Aumentar (temporário)
SET GLOBAL max_connections = 200;

-- Permanente: adicionar no docker-compose.yml
command: --max_connections=200
```

---

## ✅ Checklist de Setup

- [ ] MySQL rodando (Docker ou remoto)
- [ ] phpMyAdmin acessível
- [ ] Tabelas criadas (via migration ou SQL manual)
- [ ] .env configurado com DATABASE_URL correto
- [ ] Health check retorna "database": "healthy"
- [ ] Dados de teste inseridos
- [ ] API consegue criar/ler veículos
- [ ] Cálculo de KM salva em daily_mileage
- [ ] Backup configurado

---

**Pronto para usar com MySQL + phpMyAdmin!** 🎉
