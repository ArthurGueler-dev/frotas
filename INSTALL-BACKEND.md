# Instalação do Backend de Monitoramento de Conformidade

## 📦 Passo 1: Upload do Diretório fleet-backend

### Opção A: Via WinSCP ou FileZilla (Recomendado)
1. Abra WinSCP ou FileZilla
2. Conecte no VPS:
   - Host: `31.97.169.36`
   - Usuário: `root`
   - Senha: [sua senha]
3. Navegue até `/root/frotas/`
4. Faça upload da pasta `fleet-backend` completa de:
   ```
   C:\Users\SAMSUNG\Desktop\frotas\fleet-backend
   ```
   Para:
   ```
   /root/frotas/fleet-backend
   ```

### Opção B: Via SSH + tar (se tiver WSL)
```bash
# No Windows (PowerShell)
cd C:\Users\SAMSUNG\Desktop\frotas
tar -czf fleet-backend.tar.gz fleet-backend/

# Depois via SSH
scp fleet-backend.tar.gz root@31.97.169.36:/root/frotas/
ssh root@31.97.169.36
cd /root/frotas
tar -xzf fleet-backend.tar.gz
rm fleet-backend.tar.gz
```

---

## 🔧 Passo 2: Configurar Ambiente no VPS

Conecte via SSH:
```bash
ssh root@31.97.169.36
cd /root/frotas/fleet-backend
```

### Criar arquivo .env

```bash
cat > .env << 'EOF'
# Flask Configuration
FLASK_ENV=production
SECRET_KEY=your-secret-key-change-me-in-production
DEBUG=False

# Database (MySQL via APIs PHP - não conectar direto)
DATABASE_URL=sqlite:///fleet.db

# Redis & Celery
REDIS_URL=redis://localhost:6379/0
CELERY_BROKER_URL=redis://localhost:6379/0
CELERY_RESULT_BACKEND=redis://localhost:6379/0

# Timezone
TIMEZONE=America/Sao_Paulo

# Data Retention
DATA_RETENTION_YEARS=5

# Celery Beat Schedule Times
CELERY_BEAT_SCHEDULE_TIMES=06:00,12:00,18:00,23:59

# Ituran API (se necessário para testes)
ITURAN_USERNAME=api@i9tecnologia
ITURAN_PASSWORD=Api@In9Eng
ITURAN_SERVICE3_URL=http://localhost:8888/api/ituran/ituranwebservice3/Service3.asmx
ITURAN_MOBILE_URL=http://localhost:8888/api/ituran/IturanMobileBr
EOF
```

### Instalar dependências Python

```bash
# Criar virtual environment
python3 -m venv venv

# Ativar venv
source venv/bin/activate

# Instalar dependências
pip install --upgrade pip
pip install flask flask-sqlalchemy flask-cors flask-migrate
pip install celery redis
pip install requests geopy
pip install gunicorn
```

### Verificar se Redis está rodando

```bash
# Verificar status
systemctl status redis

# Se não estiver rodando, iniciar
systemctl start redis
systemctl enable redis
```

---

## 🚀 Passo 3: Iniciar Celery

### Terminal 1: Celery Worker (processa as tarefas)

```bash
cd /root/frotas/fleet-backend
source venv/bin/activate

# Iniciar worker
celery -A app.celery worker --loglevel=info
```

Você deve ver:
```
-------------- celery@hostname v5.x.x
---- **** -----
--- * ***  * -- Linux-5.x.x
-- * - **** ---
- ** ---------- [config]
- ** ---------- .> app:         fleet-backend
- ** ---------- .> transport:   redis://localhost:6379/0
- ** ---------- .> results:     redis://localhost:6379/0
- *** --- * --- .> concurrency: 4 (prefork)
-- ******* ---- .> task events: OFF
--- ***** -----

[tasks]
  . tasks.calculate_vehicle_mileage
  . tasks.calculate_daily_mileage_all
  . tasks.check_all_routes_compliance  <-- NOVA TASK
  . tasks.cleanup_old_data
  . tasks.recalculate_failed_records
```

### Terminal 2: Celery Beat (scheduler - a cada 5 min)

**Abra OUTRA conexão SSH** e execute:

```bash
cd /root/frotas/fleet-backend
source venv/bin/activate

# Iniciar beat (scheduler)
celery -A app.celery beat --loglevel=info
```

Você deve ver:
```
celery beat v5.x.x is starting.
LocalTime -> 2025-12-24 10:00:00
Configuration:
    . broker -> redis://localhost:6379/0
    . loader -> celery.loaders.app.AppLoader
    . scheduler -> celery.beat.PersistentScheduler
    . logfile -> [stderr]@%INFO
    . maxinterval -> 5.00 minutes (300s)
```

E a cada 5 minutos:
```
[2025-12-24 10:05:00] Scheduler: Sending due task check-route-compliance
```

---

## 📊 Passo 4: Verificar se Está Funcionando

### Verificar logs do worker

No terminal do Celery Worker, você deve ver a cada 5 minutos:

```
[2025-12-24 10:05:01] Task tasks.check_all_routes_compliance[abc123] received
[2025-12-24 10:05:01] 🔍 Starting route compliance check (task: abc123)
[2025-12-24 10:05:01] 📊 Found 0 routes in progress
[2025-12-24 10:05:01] Task tasks.check_all_routes_compliance[abc123] succeeded in 0.5s
```

### Testar manualmente via Python

```bash
cd /root/frotas/fleet-backend
source venv/bin/activate
python3

>>> from app import app
>>> from tasks import check_all_routes_compliance
>>>
>>> # Executar task manualmente
>>> result = check_all_routes_compliance.delay()
>>> print(result.get())
```

---

## 🧪 Passo 5: Criar Rota de Teste

Para testar o sistema completo:

1. **Acesse o sistema**: https://floripa.in9automacao.com.br/otimizador-blocos.html
2. **Crie uma rota** e mude o status para `em_andamento`
3. **Aguarde 5 minutos** (próxima execução do Celery Beat)
4. **Verifique logs** do Celery Worker
5. **Acesse dashboard**: https://floripa.in9automacao.com.br/compliance-monitor.html

---

## 🔧 Troubleshooting

### Erro: "No module named 'geopy'"
```bash
source venv/bin/activate
pip install geopy
```

### Erro: "Connection refused (Redis)"
```bash
systemctl start redis
systemctl status redis
```

### Erro: "No such table: FF_RouteCompliance"
Verifique se as tabelas foram criadas no MySQL via phpMyAdmin.

### Ver logs em tempo real
```bash
# Worker logs
tail -f /root/frotas/fleet-backend/celery-worker.log

# Beat logs
tail -f /root/frotas/fleet-backend/celery-beat.log
```

---

## 🎯 Manter Rodando (Produção)

### Usar Supervisor (recomendado)

```bash
# Instalar supervisor
apt-get install supervisor

# Criar config do worker
cat > /etc/supervisor/conf.d/celery-worker.conf << 'EOF'
[program:celery-worker]
command=/root/frotas/fleet-backend/venv/bin/celery -A app.celery worker --loglevel=info
directory=/root/frotas/fleet-backend
user=root
autostart=true
autorestart=true
stderr_logfile=/var/log/celery-worker.err.log
stdout_logfile=/var/log/celery-worker.out.log
EOF

# Criar config do beat
cat > /etc/supervisor/conf.d/celery-beat.conf << 'EOF'
[program:celery-beat]
command=/root/frotas/fleet-backend/venv/bin/celery -A app.celery beat --loglevel=info
directory=/root/frotas/fleet-backend
user=root
autostart=true
autorestart=true
stderr_logfile=/var/log/celery-beat.err.log
stdout_logfile=/var/log/celery-beat.out.log
EOF

# Recarregar supervisor
supervisorctl reread
supervisorctl update

# Verificar status
supervisorctl status
```

---

**Última atualização**: 2025-12-24
**Versão**: 1.0.0
