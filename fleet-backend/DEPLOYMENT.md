# 🚀 Deployment Guide - Production

Guia completo para deploy em produção do Fleet Management Backend.

## 📋 Pré-requisitos

- Servidor Linux (Ubuntu 20.04+ recomendado)
- Docker & Docker Compose instalados
- Domínio configurado (opcional, mas recomendado)
- SSL/TLS certificate (Let's Encrypt)

## 🐳 Deploy com Docker (Recomendado)

### 1. Preparar Servidor

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo apt install docker-compose -y

# Criar diretório
mkdir -p /opt/fleet-backend
cd /opt/fleet-backend
```

### 2. Copiar Arquivos

```bash
# Copiar todos os arquivos do projeto
scp -r fleet-backend/* user@server:/opt/fleet-backend/

# Ou usar git
git clone <seu-repo> /opt/fleet-backend
```

### 3. Configurar Ambiente

```bash
# Criar .env de produção
cp .env.example .env
nano .env

# Configurar variáveis críticas:
FLASK_ENV=production
SECRET_KEY=$(openssl rand -hex 32)  # Gerar chave aleatória
DATABASE_URL=postgresql://fleet_user:SENHA_FORTE@db:5432/fleet_db
REDIS_URL=redis://redis:6379/0
ITURAN_USERNAME=seu_usuario
ITURAN_PASSWORD=sua_senha
```

### 4. Build e Start

```bash
# Build imagens
docker-compose build

# Iniciar serviços
docker-compose up -d

# Verificar status
docker-compose ps

# Ver logs
docker-compose logs -f
```

### 5. Inicializar Database

```bash
# Executar migrations
docker-compose exec api flask db upgrade

# Verificar tabelas
docker-compose exec api python -c "from app import app, db; \
  with app.app_context(): \
    print(list(db.metadata.tables.keys()))"
```

### 6. Popular Dados Iniciais

```bash
# Criar área padrão
curl -X POST http://localhost:5001/api/areas \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Vitória - ES",
    "geo_entity_id": 123
  }'

# Criar veículo de teste
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

### 7. Testar Sistema

```bash
# Health check
curl http://localhost:5001/health

# Disparar cálculo manual
curl -X POST http://localhost:5001/api/jobs/calculate-mileage \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_id": 1,
    "date": "2025-01-15"
  }'

# Verificar status do job
TASK_ID=$(curl -s http://localhost:5001/api/jobs/calculate-mileage | jq -r .task_id)
curl http://localhost:5001/api/jobs/status/$TASK_ID

# Verificar Flower
curl http://localhost:5555
```

## 🔒 Nginx Reverse Proxy + SSL

### 1. Instalar Nginx

```bash
sudo apt install nginx certbot python3-certbot-nginx -y
```

### 2. Configurar Site

```nginx
# /etc/nginx/sites-available/fleet-api
server {
    listen 80;
    server_name api.seudominio.com.br;

    location / {
        proxy_pass http://localhost:5001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket support (para Flower)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}

# Flower dashboard
server {
    listen 80;
    server_name flower.seudominio.com.br;

    location / {
        proxy_pass http://localhost:5555;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Autenticação básica (opcional)
        # auth_basic "Restricted Access";
        # auth_basic_user_file /etc/nginx/.htpasswd;
    }
}
```

### 3. Ativar e SSL

```bash
# Ativar site
sudo ln -s /etc/nginx/sites-available/fleet-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Configurar SSL com Let's Encrypt
sudo certbot --nginx -d api.seudominio.com.br
sudo certbot --nginx -d flower.seudominio.com.br

# Auto-renovação
sudo certbot renew --dry-run
```

## 🔐 Segurança

### 1. Firewall

```bash
# UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Bloquear portas diretas do Docker
sudo ufw deny 5001
sudo ufw deny 5555
sudo ufw deny 5432
sudo ufw deny 6379
```

### 2. Limitar Acesso ao Flower

```bash
# Criar senha para Flower
sudo apt install apache2-utils
sudo htpasswd -c /etc/nginx/.htpasswd admin

# Descomentar linhas de auth_basic no nginx
```

### 3. Secrets Management

```bash
# Usar Docker secrets (Swarm)
echo "SENHA_FORTE" | docker secret create db_password -
echo "OUTRA_SENHA" | docker secret create ituran_password -

# Atualizar docker-compose.yml para usar secrets
```

## 📊 Monitoring

### 1. Logs Centralizados

```bash
# Configurar logrotate
sudo nano /etc/logrotate.d/fleet-backend

# Conteúdo:
/opt/fleet-backend/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 fleet fleet
    sharedscripts
}
```

### 2. Health Checks

```bash
# Criar script de monitoramento
cat > /opt/fleet-backend/healthcheck.sh << 'EOF'
#!/bin/bash
API_URL="http://localhost:5001/health"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" $API_URL)

if [ $RESPONSE -ne 200 ]; then
    echo "❌ API is DOWN! Response: $RESPONSE"
    # Alertar via email/Slack/etc
    # Restart automático
    docker-compose restart api
else
    echo "✅ API is healthy"
fi
EOF

chmod +x /opt/fleet-backend/healthcheck.sh

# Adicionar ao cron (a cada 5 minutos)
crontab -e
*/5 * * * * /opt/fleet-backend/healthcheck.sh >> /var/log/fleet-healthcheck.log 2>&1
```

### 3. Prometheus + Grafana (Opcional)

```yaml
# Adicionar ao docker-compose.yml
  prometheus:
    image: prom/prometheus
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
    ports:
      - "9090:9090"

  grafana:
    image: grafana/grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
```

## 🔄 Backup & Recovery

### 1. Backup Automático do PostgreSQL

```bash
# Script de backup
cat > /opt/fleet-backend/backup-db.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/fleet-backend/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/fleet_db_$DATE.sql.gz"

mkdir -p $BACKUP_DIR

docker-compose exec -T db pg_dump -U fleet_user fleet_db | gzip > $BACKUP_FILE

# Manter apenas últimos 30 dias
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "✅ Backup criado: $BACKUP_FILE"
EOF

chmod +x /opt/fleet-backend/backup-db.sh

# Agendar backup diário (03:00 AM)
crontab -e
0 3 * * * /opt/fleet-backend/backup-db.sh >> /var/log/fleet-backup.log 2>&1
```

### 2. Recovery

```bash
# Restaurar backup
gunzip -c backup.sql.gz | docker-compose exec -T db psql -U fleet_user fleet_db
```

## 📈 Performance Tuning

### 1. PostgreSQL

```sql
-- /opt/fleet-backend/postgres-tuning.conf
# Adicionar ao volume do PostgreSQL

# Connection pooling
max_connections = 100
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 64MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 4MB
min_wal_size = 1GB
max_wal_size = 4GB
```

### 2. Celery

```python
# Ajustar concurrency baseado em CPU cores
# No docker-compose.yml:
command: celery -A celery_app worker --loglevel=info --concurrency=8

# Ou auto-detect:
command: celery -A celery_app worker --loglevel=info --autoscale=10,3
```

### 3. Redis

```bash
# redis.conf
maxmemory 1gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## 🔧 Troubleshooting

### Problema: Container não inicia

```bash
# Ver logs detalhados
docker-compose logs api

# Verificar configuração
docker-compose config

# Rebuild sem cache
docker-compose build --no-cache
docker-compose up -d
```

### Problema: Celery tasks não executam

```bash
# Verificar worker
docker-compose exec celery-worker celery -A celery_app inspect active

# Verificar beat schedule
docker-compose exec celery-beat celery -A celery_app inspect scheduled

# Limpar fila
docker-compose exec redis redis-cli FLUSHALL
```

### Problema: Banco de dados travado

```bash
# Verificar conexões
docker-compose exec db psql -U fleet_user -d fleet_db -c \
  "SELECT pid, usename, application_name, state FROM pg_stat_activity;"

# Matar conexões travadas
docker-compose exec db psql -U fleet_user -d fleet_db -c \
  "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE state = 'idle in transaction';"
```

## 🎯 Checklist de Deploy

- [ ] Servidor preparado (Docker instalado)
- [ ] Arquivos copiados
- [ ] .env configurado com credenciais de produção
- [ ] SECRET_KEY gerado aleatoriamente
- [ ] Docker containers iniciados
- [ ] Migrations aplicadas
- [ ] Dados iniciais populados
- [ ] Health check funcionando
- [ ] Nginx configurado
- [ ] SSL/TLS habilitado
- [ ] Firewall configurado
- [ ] Backup automático configurado
- [ ] Monitoring configurado
- [ ] Logs funcionando
- [ ] Teste completo de fluxo (criar veículo → calcular KM → verificar resultado)
- [ ] Celery Beat agendado e funcionando
- [ ] Documentação atualizada

## 📞 Suporte

Em caso de problemas:
1. Verificar logs: `docker-compose logs -f`
2. Health check: `curl http://localhost:5001/health`
3. Flower dashboard: `http://localhost:5555` ou `https://flower.seudominio.com.br`
4. Contato: suporte@i9automacao.com.br
