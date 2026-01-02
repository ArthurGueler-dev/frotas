#!/bin/bash

# Script para configurar cron jobs de sincronização de quilometragem
# Uso: bash setup-cron.sh

echo "🔧 Configurando cron jobs de sincronização de quilometragem..."

# Criar diretório de logs se não existir
mkdir -p /root/frotas/logs

# Backup do crontab atual
crontab -l > /tmp/crontab-backup-$(date +%Y%m%d-%H%M%S).txt 2>/dev/null || true

# Remover cron jobs antigos de sincronização (se existirem)
crontab -l 2>/dev/null | grep -v "sync-mileage-cron.js" > /tmp/crontab-temp.txt || true

# Adicionar novos cron jobs
cat >> /tmp/crontab-temp.txt << 'EOF'

# ============================================================
# Sincronização Automática de Quilometragem (i9 Frotas)
# ============================================================
# 08:00 - Início do expediente
0 8 * * * cd /root/frotas && /usr/bin/node sync-mileage-cron.js >> logs/sync-cron.log 2>&1

# 12:00 - Meio-dia
0 12 * * * cd /root/frotas && /usr/bin/node sync-mileage-cron.js >> logs/sync-cron.log 2>&1

# 18:00 - Final do expediente
0 18 * * * cd /root/frotas && /usr/bin/node sync-mileage-cron.js >> logs/sync-cron.log 2>&1

# 23:55 - Antes da meia-noite (sincronização principal do dia)
55 23 * * * cd /root/frotas && /usr/bin/node sync-mileage-cron.js >> logs/sync-cron.log 2>&1
# ============================================================

EOF

# Instalar novo crontab
crontab /tmp/crontab-temp.txt

# Limpar arquivo temporário
rm /tmp/crontab-temp.txt

echo "✅ Cron jobs configurados com sucesso!"
echo ""
echo "📅 Horários programados:"
echo "   • 08:00 - Início do expediente"
echo "   • 12:00 - Meio-dia"
echo "   • 18:00 - Final do expediente"
echo "   • 23:55 - Sincronização principal do dia"
echo ""
echo "📋 Crontab atual:"
crontab -l | grep -A 10 "Sincronização Automática"
echo ""
echo "📊 Para ver os logs:"
echo "   tail -f /root/frotas/logs/sync-cron.log"
echo ""
echo "🧪 Para testar manualmente:"
echo "   cd /root/frotas && node sync-mileage-cron.js"
