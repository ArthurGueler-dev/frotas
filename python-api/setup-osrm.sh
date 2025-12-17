#!/bin/bash
# Setup OSRM na VPS Hostinger
# Autor: FleetFlow
# Data: 2025-12-11

echo "════════════════════════════════════════════════════════"
echo "   🚀 Setup OSRM Local - Sudeste do Brasil"
echo "════════════════════════════════════════════════════════"

# Criar diretório
mkdir -p ~/osrm-data
cd ~/osrm-data

# 1. Baixar OSM do Sudeste
echo ""
echo "📥 Baixando mapa do Sudeste (~790 MB)..."
wget https://download.geofabrik.de/south-america/brazil/sudeste-latest.osm.pbf

# 2. Processar dados
echo ""
echo "⚙️  Processando dados (pode demorar 10-30 min)..."

echo "  1/3 Extract..."
docker run -t -v "${PWD}:/data" ghcr.io/project-osrm/osrm-backend \
    osrm-extract -p /opt/car.lua /data/sudeste-latest.osm.pbf

echo "  2/3 Partition..."
docker run -t -v "${PWD}:/data" ghcr.io/project-osrm/osrm-backend \
    osrm-partition /data/sudeste-latest.osrm

echo "  3/3 Customize..."
docker run -t -v "${PWD}:/data" ghcr.io/project-osrm/osrm-backend \
    osrm-customize /data/sudeste-latest.osrm

# 3. Parar container antigo se existir
echo ""
echo "🔄 Parando container OSRM antigo..."
docker stop osrm-server 2>/dev/null || true
docker rm osrm-server 2>/dev/null || true

# 4. Iniciar servidor OSRM
echo ""
echo "🚀 Iniciando servidor OSRM..."
docker run -d \
    --name osrm-server \
    --restart unless-stopped \
    -p 5001:5000 \
    -v "${PWD}:/data" \
    ghcr.io/project-osrm/osrm-backend \
    osrm-routed --algorithm mld /data/sudeste-latest.osrm

# 5. Verificar status
echo ""
echo "⏳ Aguardando OSRM inicializar (15 seg)..."
sleep 15

echo ""
echo "🔍 Testando OSRM..."
RESULT=$(curl -s "http://localhost:5001/route/v1/driving/-40.3128,-20.3155;-40.3089,-20.1284" | grep -o "Ok")

if [ "$RESULT" == "Ok" ]; then
    echo "✅ OSRM está rodando corretamente!"
    echo ""
    echo "URL: http://localhost:5001"
    echo "Container: docker ps | grep osrm"
    echo "Logs: docker logs osrm-server"
else
    echo "❌ Erro ao iniciar OSRM. Verifique logs:"
    echo "   docker logs osrm-server"
    exit 1
fi

echo ""
echo "════════════════════════════════════════════════════════"
echo "   ✅ Setup OSRM concluído com sucesso!"
echo "════════════════════════════════════════════════════════"
