#!/bin/bash

##############################################################################
# Script de Deploy para Produção - Sistema de Frotas
# Deploy do proxy WhatsApp no VPS (31.97.169.36)
##############################################################################

set -e  # Exit on error

VPS_HOST="root@31.97.169.36"
VPS_DIR="/root/frotas-whatsapp-proxy"
CPANEL_DIR="upload-cpanel"

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║        🚀 Deploy em Produção - Sistema de Frotas         ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# 1. Criar diretório de upload se não existir
echo "📁 [1/5] Preparando arquivos para upload..."
if [ ! -d "$CPANEL_DIR" ]; then
    mkdir -p "$CPANEL_DIR"
fi

# 2. Copiar arquivos PHP para cPanel
echo "📋 [2/5] Copiando arquivos PHP para cPanel..."
cp cpanel-api/get-rota.php "$CPANEL_DIR/"
cp cpanel-api/update-rota-status.php "$CPANEL_DIR/"
cp cpanel-api/enviar-rota-whatsapp.php "$CPANEL_DIR/"
echo "   ✅ Arquivos PHP preparados"

# 3. Upload para cPanel via FTP/SFTP
echo ""
echo "📤 [3/5] Fazendo upload para cPanel..."
echo "   ⚠️  ATENÇÃO: Você precisa fazer upload manual via cPanel ou FTP"
echo "   Arquivos em: $CPANEL_DIR/"
echo "   Destino: public_html/"
echo ""
read -p "Pressione ENTER após fazer upload dos arquivos PHP..."

# 4. Deploy do proxy WhatsApp no VPS
echo ""
echo "🚀 [4/5] Fazendo deploy do proxy WhatsApp no VPS..."

# Criar diretório no VPS
echo "   • Criando diretório no VPS..."
ssh $VPS_HOST "mkdir -p $VPS_DIR"

# Copiar arquivo do proxy
echo "   • Copiando enviar-whatsapp-proxy.js..."
scp enviar-whatsapp-proxy.js $VPS_HOST:$VPS_DIR/

# Copiar package.json
echo "   • Copiando package.json..."
scp package.json $VPS_HOST:$VPS_DIR/

# Instalar dependências no VPS
echo "   • Instalando dependências no VPS..."
ssh $VPS_HOST "cd $VPS_DIR && npm install axios express mysql2"

# 5. Configurar PM2 no VPS
echo ""
echo "⚙️  [5/5] Configurando PM2 no VPS..."

ssh $VPS_HOST << 'ENDSSH'
cd /root/frotas-whatsapp-proxy

# Instalar PM2 se não existir
if ! command -v pm2 &> /dev/null; then
    echo "   • Instalando PM2..."
    npm install -g pm2
fi

# Parar processo antigo se existir
echo "   • Parando processo antigo (se existir)..."
pm2 delete whatsapp-proxy 2>/dev/null || true

# Iniciar novo processo
echo "   • Iniciando proxy WhatsApp com PM2..."
pm2 start enviar-whatsapp-proxy.js --name whatsapp-proxy

# Salvar configuração
echo "   • Salvando configuração PM2..."
pm2 save

# Configurar auto-start no boot
echo "   • Configurando auto-start..."
pm2 startup systemd -u root --hp /root || true

echo ""
echo "✅ Proxy WhatsApp configurado no VPS!"
pm2 status
ENDSSH

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║             ✅ Deploy Concluído com Sucesso!             ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
echo "📊 Status:"
echo "   ✅ Arquivos PHP prontos para upload no cPanel"
echo "   ✅ Proxy WhatsApp rodando no VPS (porta 3001)"
echo ""
echo "🔗 URLs de Produção:"
echo "   • get-rota.php:            https://floripa.in9automacao.com.br/get-rota.php"
echo "   • update-rota-status.php:  https://floripa.in9automacao.com.br/update-rota-status.php"
echo "   • Proxy WhatsApp:          http://31.97.169.36:3001"
echo ""
echo "🧪 Testar:"
echo "   curl http://31.97.169.36:3001/health"
echo ""
echo "📋 Gerenciar no VPS:"
echo "   ssh $VPS_HOST"
echo "   pm2 status"
echo "   pm2 logs whatsapp-proxy"
echo "   pm2 restart whatsapp-proxy"
echo ""
