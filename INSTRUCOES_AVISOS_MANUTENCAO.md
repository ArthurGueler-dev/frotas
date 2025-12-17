# Instruções: Integração Sistema de Avisos de Manutenção

## ✅ Arquivos Atualizados

1. **server.js** - Backend atualizado para usar `avisos_manutencao`
2. **dashboard-manutencoes.html** - Frontend integrado
3. **create_avisos_manutencao.sql** - Script de criação da tabela

---

## 📋 PASSO A PASSO PARA INTEGRAR

### **PASSO 1: Executar SQL no Banco de Dados**

Você precisa executar o arquivo SQL no seu banco de dados MySQL.

**Opção A - Via phpMyAdmin:**
1. Acesse phpMyAdmin no cPanel
2. Selecione o banco de dados
3. Vá na aba "SQL"
4. Abra o arquivo `create_avisos_manutencao.sql`
5. Copie todo o conteúdo e cole no campo SQL
6. Clique em "Executar"

**Opção B - Via linha de comando:**
```bash
# Conectar ao servidor
ssh root@31.97.169.36

# Executar o SQL
mysql -u seu_usuario -p seu_banco_de_dados < /root/frotas/create_avisos_manutencao.sql
```

**O que o SQL faz:**
- ✅ Remove a tabela `avisos_manutencao` antiga (se existir)
- ✅ Cria nova tabela com estrutura correta
- ✅ Adiciona todos os índices necessários
- ✅ Configura chaves estrangeiras

---

### **PASSO 2: Estrutura da Tabela Criada**

A tabela `avisos_manutencao` terá os seguintes campos:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | ID único do alerta |
| `vehicle_id` | INT | ID do veículo (FK para Vehicles) |
| `placa_veiculo` | VARCHAR(20) | Placa do veículo |
| `plano_id` | INT | ID do plano (FK para planos_manutencao) |
| `vehicle_maintenance_plan_id` | INT | ID da associação veículo-plano |
| `km_atual_veiculo` | INT | KM atual do veículo |
| `km_programado` | INT | KM programado para manutenção |
| `km_restantes` | INT | KM restantes (negativo se atrasado) |
| `data_proxima` | DATE | Data programada |
| `dias_restantes` | INT | Dias restantes (negativo se atrasado) |
| `tipo_alerta` | ENUM | Quilometragem / Data / Ambos |
| `nivel_alerta` | ENUM | Baixo / Medio / Alto / Critico |
| `status` | ENUM | Pendente / Vence_hoje / Vencido / Concluido / Ativo |
| `mensagem` | TEXT | Mensagem do alerta |
| `notificado_em` | DATETIME | Data de notificação |
| `notificado` | BOOLEAN | Flag de notificação |
| `concluido_em` | DATETIME | Data de conclusão |
| `observacoes` | TEXT | Observações da conclusão |
| `criado_em` | DATETIME | Data de criação |
| `atualizado_em` | DATETIME | Data de atualização |

---

### **PASSO 3: Verificar Tabelas Relacionadas**

Certifique-se que existem dados nas tabelas:

```sql
-- Verificar se há veículos
SELECT COUNT(*) FROM Vehicles;

-- Verificar se há planos de manutenção
SELECT COUNT(*) FROM planos_manutencao;

-- Verificar se há associações veículo-plano
SELECT COUNT(*) FROM FF_VehicleMaintenancePlans WHERE ativo = 1;

-- Verificar se há telemetria
SELECT COUNT(*) FROM Telemetria_Diaria;
```

---

### **PASSO 4: Gerar Alertas Iniciais**

Após criar a tabela, você tem 3 opções para popular com dados:

**Opção A - Aguardar Cron Job (Automático):**
- O cron job executa automaticamente **todos os dias às 6h da manhã**
- Ele vai verificar todos os planos ativos e gerar alertas

**Opção B - Executar Manualmente via API:**
```bash
# Forçar sincronização de KM e geração de alertas
curl -X POST https://frotas.in9automacao.com.br/api/maintenance-alerts/sync-km
```

**Opção C - Inserir dados de teste manualmente:**
```sql
INSERT INTO avisos_manutencao
(vehicle_id, placa_veiculo, plano_id, km_atual_veiculo, km_programado, km_restantes,
 data_proxima, dias_restantes, tipo_alerta, nivel_alerta, status, mensagem)
VALUES
(1, 'ABC-1234', 1, 45000, 50000, 5000, '2025-12-31', 30, 'Quilometragem', 'Alto', 'Ativo',
 'Troca de óleo próxima! Veículo ABC-1234 faltam 5000 km');
```

---

### **PASSO 5: Testar no Dashboard**

1. Acesse: **https://frotas.in9automacao.com.br/dashboard-manutencoes.html**
2. Faça **Ctrl + Shift + R** (hard refresh)
3. Você deverá ver:
   - Estatísticas atualizadas nos cards
   - Alertas na tabela (se houver dados)
   - Botões funcionais: Agendar, Histórico, Atualizar KM

---

## 🔧 APIs Disponíveis

### GET /api/maintenance-alerts
Lista alertas com filtros:
- `status` - Pendente, Vence_hoje, Vencido, Concluido, Ativo
- `nivel_alerta` - Baixo, Medio, Alto, Critico
- `busca` - Busca por placa
- `page` - Página (default: 1)
- `limit` - Itens por página (default: 10)

**Exemplo:**
```bash
curl "https://frotas.in9automacao.com.br/api/maintenance-alerts?status=Ativo&limit=5"
```

### PUT /api/maintenance-alerts/:id/resolve
Marca alerta como concluído:
```bash
curl -X PUT https://frotas.in9automacao.com.br/api/maintenance-alerts/1/resolve \
  -H "Content-Type: application/json" \
  -d '{"data_resolucao":"2025-11-27","observacoes":"Manutenção realizada"}'
```

### POST /api/maintenance-alerts/sync-km
Atualiza KM de todos os veículos e recalcula alertas:
```bash
curl -X POST https://frotas.in9automacao.com.br/api/maintenance-alerts/sync-km
```

### GET /api/maintenance-alerts/:placa/history
Busca histórico de manutenções por placa:
```bash
curl "https://frotas.in9automacao.com.br/api/maintenance-alerts/ABC-1234/history"
```

---

## 🔍 Verificações e Troubleshooting

### Verificar se a tabela foi criada:
```sql
SHOW TABLES LIKE 'avisos_manutencao';
DESC avisos_manutencao;
```

### Verificar se há alertas:
```sql
SELECT COUNT(*) FROM avisos_manutencao;
SELECT * FROM avisos_manutencao LIMIT 10;
```

### Ver logs do servidor:
```bash
ssh root@31.97.169.36 "ps aux | grep 'node server' | grep -v grep"
```

### Testar API diretamente:
```bash
curl -s "https://frotas.in9automacao.com.br/api/maintenance-alerts?limit=1" | jq .
```

---

## ✅ Checklist Final

- [ ] SQL executado com sucesso
- [ ] Tabela `avisos_manutencao` criada
- [ ] Servidor Node.js reiniciado
- [ ] API retorna dados (mesmo que vazio)
- [ ] Dashboard carrega sem erros
- [ ] Alertas aparecem na tabela (após popular)
- [ ] Botões "Agendar" e "Histórico" funcionam
- [ ] Botão "Atualizar KM" executa sem erro

---

## 📞 Suporte

Se encontrar erros:
1. Verifique os logs do servidor
2. Verifique o console do navegador (F12)
3. Teste as APIs individualmente
4. Certifique-se que as tabelas relacionadas existem

Sistema 100% pronto para uso!
