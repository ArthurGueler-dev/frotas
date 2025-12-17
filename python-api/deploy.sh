#!/bin/bash
# Deploy da API Python Flask para VPS
# Autor: FleetFlow
# Data: 2025-12-11

VPS_HOST="root@31.97.169.36"
VPS_PATH="/root/frotas/python-api"
LOCAL_PATH="."

echo "════════════════════════════════════════════════════════"
echo "   📦 Deploy API de Otimização de Rotas"
echo "════════════════════════════════════════════════════════"

# 1. Criar diretório no servidor
echo ""
echo "📁 Criando diretório no servidor..."
ssh $VPS_HOST "mkdir -p $VPS_PATH"

# 2. Fazer upload dos arquivos
echo ""
echo "📤 Enviando arquivos..."
scp app.py requirements.txt setup-osrm.sh README.md test_api.py $VPS_HOST:$VPS_PATH/

# 3. Instalar dependências
echo ""
echo "📦 Instalando dependências..."
ssh $VPS_HOST << 'EOF'
cd /root/frotas/python-api

# Criar virtualenv se não existir
if [ ! -d "venv" ]; then
    echo "Criando virtualenv..."
    python3 -m venv venv
fi

# Ativar e instalar
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt

echo "✅ Dependências instaladas"
EOF

# 4. Verificar se OSRM está rodando
echo ""
echo "🔍 Verificando OSRM..."
OSRM_STATUS=$(ssh $VPS_HOST "docker ps | grep osrm-server | wc -l")

if [ "$OSRM_STATUS" -eq "0" ]; then
    echo "⚠️  OSRM não está rodando!"
    echo "Execute: ./setup-osrm.sh no servidor"
else
    echo "✅ OSRM está rodando"
fi

# 5. Parar serviço antigo
echo ""
echo "🔄 Reiniciando serviço..."
ssh $VPS_HOST "systemctl stop frotas-api 2>/dev/null || true"

# 6. Criar/atualizar serviço systemd
echo ""
echo "⚙️  Configurando serviço systemd..."
ssh $VPS_HOST << 'EOF'
cat > /etc/systemd/system/frotas-api.service << 'SYSTEMD'
[Unit]
Description=FleetFlow Rotas API
After=network.target docker.service
Requires=docker.service

[Service]
Type=simple
User=root
WorkingDirectory=/root/frotas/python-api
Environment="PATH=/root/frotas/python-api/venv/bin:/usr/local/bin:/usr/bin:/bin"
ExecStart=/root/frotas/python-api/venv/bin/gunicorn -w 4 -b 0.0.0.0:8000 --timeout 180 --access-logfile - app:app
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
SYSTEMD

systemctl daemon-reload
systemctl enable frotas-api
systemctl start frotas-api

sleep 3

# Verificar status
systemctl status frotas-api --no-pager
EOF

# 7. Testar API
echo ""
echo "🧪 Testando API..."
sleep 5

HEALTH_CHECK=$(ssh $VPS_HOST "curl -s http://localhost:8000/health | grep -o online")

if [ "$HEALTH_CHECK" == "online" ]; then
    echo "✅ API está online!"
else
    echo "❌ API não respondeu ao health check"
    echo "Verifique logs: ssh $VPS_HOST 'journalctl -u frotas-api -f'"
    exit 1
fi

echo ""
echo "════════════════════════════════════════════════════════"
echo "   ✅ Deploy concluído com sucesso!"
echo "════════════════════════════════════════════════════════"
echo ""
echo "📋 Comandos úteis:"
echo "   • Ver logs:     ssh $VPS_HOST 'journalctl -u frotas-api -f'"
echo "   • Reiniciar:    ssh $VPS_HOST 'systemctl restart frotas-api'"
echo "   • Status:       ssh $VPS_HOST 'systemctl status frotas-api'"
echo "   • Health check: curl http://31.97.169.36:8000/health"
echo ""
