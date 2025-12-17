# 📱 Sistema de Envio de Rotas via WhatsApp

Sistema completo para gerar rotas otimizadas com OSRM + PyVRP e enviar para colaboradores via WhatsApp usando Evolution API.

## 🎯 Funcionalidades

1. **Geração de Link Google Maps** com sequência exata de locais
2. **Armazenamento de rotas** no banco de dados MySQL
3. **Envio automático via WhatsApp** usando Evolution API
4. **Interface web** para gerenciar e enviar rotas
5. **Rastreamento de status** (pendente, enviada, em andamento, concluída)

## 📁 Arquivos Criados

### Python (API)
- `python-api/app.py` - Função `gerar_link_google_maps_exato()`

### PHP (APIs Backend)
- `cpanel-api/salvar-rota-whatsapp.php` - Salvar rota com link do Google Maps
- `cpanel-api/enviar-rota-whatsapp.php` - Enviar rota via WhatsApp
- `cpanel-api/rotas-api.php` - Listar e gerenciar rotas

### Frontend
- `gerenciar-rotas.html` - Interface para gerenciar e enviar rotas

### Database
- `migrations/create_table_rotas.sql` - SQL para criar tabela FF_Rotas

## 🚀 Instalação

### 1. Criar Tabela no Banco de Dados

Execute o SQL no phpMyAdmin do cPanel:

```bash
mysql -h 187.49.226.10 -u f137049_tool -p f137049_in9aut < migrations/create_table_rotas.sql
```

Ou copie o conteúdo de `migrations/create_table_rotas.sql` e execute no phpMyAdmin.

### 2. Fazer Upload dos Arquivos

**Python API:**
```bash
scp python-api/app.py root@31.97.169.36:/root/frotas/python-api/
ssh root@31.97.169.36 "systemctl restart frotas-api"
```

**APIs PHP (cPanel):**
```bash
# Fazer upload via FTP ou cPanel File Manager para:
# /root/frotas/
- salvar-rota-whatsapp.php
- enviar-rota-whatsapp.php
- rotas-api.php
```

**Frontend:**
```bash
# Fazer upload via FTP ou cPanel para:
# /root/frotas/
- gerenciar-rotas.html
```

### 3. Configurar Evolution API

Edite `cpanel-api/enviar-rota-whatsapp.php` e configure:

```php
$EVOLUTION_API_URL = 'https://sua-url-evolution.com.br';
$EVOLUTION_API_KEY = 'sua-chave-api';
$EVOLUTION_INSTANCE = 'nome-da-instancia';
```

## 📖 Como Usar

### 1. Gerar e Salvar Rota

Após otimizar rotas no sistema, salve no banco com link do Google Maps:

```javascript
// JavaScript - Após obter blocos otimizados
const rota = {
    bloco_id: 123,
    motorista_id: 45,
    veiculo_id: 67,
    base_lat: -20.319,
    base_lon: -40.338,
    locais_ordenados: [
        {
            id: 40810,
            lat: -20.32,
            lon: -40.34,
            nome: "Cliente A",
            endereco: "Rua A, 123"
        },
        {
            id: 40811,
            lat: -20.33,
            lon: -40.35,
            nome: "Cliente B",
            endereco: "Rua B, 456"
        }
    ],
    distancia_total_km: 15.5,
    tempo_total_min: 25
};

const response = await fetch('https://floripa.in9automacao.com.br/salvar-rota-whatsapp.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(rota)
});

const data = await response.json();
console.log('Rota ID:', data.rota_id);
console.log('Link Google Maps:', data.link_google_maps);
```

### 2. Gerenciar e Enviar Rotas

Acesse a interface web:

```
https://frotas.in9automacao.com.br/gerenciar-rotas.html
```

**Funcionalidades:**
1. **Filtrar rotas** por status, motorista
2. **Ver detalhes** da rota (distância, tempo, locais)
3. **Enviar por WhatsApp** - clique no botão verde
4. **Abrir no Google Maps** - clique no botão azul

### 3. Envio via WhatsApp

**Manualmente via Interface:**
1. Clique em "📱 Enviar WhatsApp"
2. Confirme ou altere o número de telefone
3. Clique em "Enviar WhatsApp"

**Programaticamente via API:**
```javascript
const response = await fetch('https://floripa.in9automacao.com.br/enviar-rota-whatsapp.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        rota_id: 123,
        telefone: '5527999999999'  // código país + DDD + número
    })
});

const data = await response.json();
if (data.success) {
    console.log('✅ Rota enviada com sucesso!');
}
```

## 📱 Formato da Mensagem WhatsApp

O colaborador receberá:

```
🚗 Sua Rota de Hoje 🚗

📍 Bloco: Bloco Python #1
📏 Distância Total: 15.50 km
⏱️ Tempo Estimado: 25 minutos
🚙 Veículo: ABC-1234

📋 Sequência de Visitas (siga exatamente essa ordem):

1. Cliente A
   📍 Rua A, 123

2. Cliente B
   📍 Rua B, 456

🗺️ Navegue com Google Maps:
https://www.google.com/maps/dir/?api=1&origin=-20.319,-40.338&destination=-20.33,-40.35&waypoints=-20.32,-40.34&travelmode=driving

✅ Instruções:
1️⃣ Clique no link acima
2️⃣ O Google Maps abrirá com todos os pontos
3️⃣ Siga a navegação ponto a ponto
4️⃣ Não altere a ordem dos pontos

Boa viagem e bom trabalho! 🎯
```

## 🔧 APIs Disponíveis

### Salvar Rota
**POST** `https://floripa.in9automacao.com.br/salvar-rota-whatsapp.php`

```json
{
  "bloco_id": 123,
  "motorista_id": 45,
  "veiculo_id": 67,
  "base_lat": -20.319,
  "base_lon": -40.338,
  "locais_ordenados": [...],
  "distancia_total_km": 15.5,
  "tempo_total_min": 25
}
```

### Listar Rotas
**GET** `https://floripa.in9automacao.com.br/rotas-api.php?status=pendente`

Query params: `status`, `motorista_id`, `data_inicio`, `data_fim`

### Enviar por WhatsApp
**POST** `https://floripa.in9automacao.com.br/enviar-rota-whatsapp.php`

```json
{
  "rota_id": 123,
  "telefone": "5527999999999"
}
```

### Atualizar Status
**PUT** `https://floripa.in9automacao.com.br/rotas-api.php`

```json
{
  "rota_id": 123,
  "status": "em_andamento",
  "observacoes": "Motorista iniciou a rota"
}
```

## 📊 Status de Rotas

- `pendente` - Rota criada, aguardando envio
- `enviada` - Enviada via WhatsApp
- `em_andamento` - Motorista iniciou a rota
- `concluida` - Rota finalizada
- `cancelada` - Rota cancelada

## 🔐 Segurança

1. Configure `.htaccess` para proteger dados sensíveis
2. Use HTTPS para todas as comunicações
3. Valide tokens da Evolution API
4. Limite de rate para APIs

## 🐛 Troubleshooting

### Rota não aparece no Google Maps
- Verifique se as coordenadas estão corretas
- Google Maps tem limite de 25 waypoints

### Mensagem não enviada
- Verifique configuração da Evolution API
- Confira formato do telefone (com código do país)
- Verifique logs da Evolution API

### Link muito longo
- Se tiver mais de 25 locais, divida em múltiplas rotas
- Google Maps não suporta mais de 25 waypoints

## 📝 Próximos Passos

1. ✅ Sistema de envio via WhatsApp implementado
2. 🔄 Adicionar confirmação de leitura
3. 🔄 Adicionar rastreamento GPS em tempo real
4. 🔄 Notificações automáticas de conclusão
5. 🔄 Relatórios de desempenho por motorista

## 💡 Dicas

- **Teste primeiro** com seu próprio número antes de enviar para colaboradores
- **Salve templates** de mensagens personalizadas
- **Configure webhooks** da Evolution API para rastrear status de entrega
- **Use grupos** do WhatsApp para comunicação em equipe

## 📞 Suporte

Para dúvidas ou problemas, entre em contato com a equipe de desenvolvimento.
