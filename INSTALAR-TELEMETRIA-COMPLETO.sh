#!/bin/bash

echo "🚀 ========== INSTALAÇÃO COMPLETA DO SISTEMA DE TELEMETRIA =========="
echo ""

# Ir para o diretório do projeto
cd ~/public_html/frotas

echo "📋 Passo 1: Fazendo backup dos arquivos atuais..."
mkdir -p backups
cp services/telemetria-updater.js backups/telemetria-updater-old-$(date +%Y%m%d-%H%M%S).js 2>/dev/null || true
echo "   ✅ Backup feito!"
echo ""

echo "📋 Passo 2: Substituindo arquivo telemetria-updater.js..."
mv services/telemetria-updater-v2.js services/telemetria-updater.js
echo "   ✅ Arquivo substituído!"
echo ""

echo "📋 Passo 3: Verificando se node_modules existe..."
if [ ! -d "node_modules" ]; then
    echo "   ⚠️ node_modules não existe! Instalando dependências..."
    npm install
else
    echo "   ✅ node_modules OK!"
fi
echo ""

echo "📋 Passo 4: Reiniciando servidor..."
pm2 restart fleetflow
echo "   ✅ Servidor reiniciado!"
echo ""

echo "📋 Passo 5: Aguardando 3 segundos para estabilizar..."
sleep 3
echo ""

echo "📋 Passo 6: Testando com veículo SFT4I72..."
curl -X POST http://localhost:5000/api/telemetria/atualizar-hoje/SFT4I72
echo ""
echo ""

echo "📋 Passo 7: Verificando logs..."
echo ""
pm2 logs fleetflow --lines 30 --nostream
echo ""

echo "✅ ========== INSTALAÇÃO CONCLUÍDA! =========="
echo ""
echo "📊 Próximos passos:"
echo "   1. Verifique os logs acima"
echo "   2. Execute: mysql -h 187.49.226.10 -u f137049_tool -p -e \"SELECT COUNT(*) FROM f137049_in9aut.Telemetria_Diaria\""
echo "   3. Acesse: https://seudominio.com.br/telemetria"
echo ""
