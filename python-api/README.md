# 🚗 API de Otimização de Rotas - FleetFlow

API Flask em Python para otimização de rotas com clustering geográfico e resolução de CVRP.

## ✨ Funcionalidades

- ✅ **Clustering Hierárquico** com scipy (blocos compactos ≤ 5km de diâmetro)
- ✅ **Otimização CVRP** com PyVRP (rotas otimizadas por bloco)
- ✅ **Distâncias Reais** via OSRM local (offline)
- ✅ **Geocodificação** com cache no MySQL (Nominatim)
- ✅ **Mapas Interativos** com Folium
- ✅ **100% Gratuito** após setup

---

## 📋 Requisitos

- Python 3.9+
- Docker (para OSRM)
- MySQL (já existente)
- 4GB RAM mínimo (VPS)
- ~10GB espaço em disco (dados OSRM)

---

## 🚀 Instalação

### 1. Setup OSRM (uma vez, na VPS)

```bash
# Fazer upload do script
scp setup-osrm.sh root@31.97.169.36:/root/

# No servidor VPS
ssh root@31.97.169.36
chmod +x /root/setup-osrm.sh
./setup-osrm.sh
```

**Tempo estimado:** 20-40 minutos (download + processamento)

### 2. Instalar API Python

```bash
# Na VPS
cd /root/frotas
mkdir python-api
cd python-api

# Fazer upload dos arquivos
# app.py, requirements.txt, etc.

# Criar virtualenv
python3 -m venv venv
source venv/bin/activate

# Instalar dependências
pip install -r requirements.txt
```

### 3. Configurar banco de dados

Editar `app.py` se necessário (já configurado):

```python
DB_CONFIG = {
    'host': '187.49.226.10',
    'port': 3306,
    'user': 'f137049_tool',
    'password': 'In9@1234qwer',
    'database': 'f137049_in9aut',
}
```

### 4. Testar

```bash
# Modo desenvolvimento
python app.py

# Testar health check
curl http://localhost:8000/health

# Testar OSRM
curl http://localhost:8000/test-osrm
```

### 5. Produção com Gunicorn

```bash
# Instalar Gunicorn
pip install gunicorn

# Rodar como serviço
gunicorn -w 4 -b 0.0.0.0:8000 --timeout 120 app:app
```

**Ou criar serviço systemd:**

```bash
sudo nano /etc/systemd/system/frotas-api.service
```

```ini
[Unit]
Description=FleetFlow Rotas API
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/root/frotas/python-api
Environment="PATH=/root/frotas/python-api/venv/bin"
ExecStart=/root/frotas/python-api/venv/bin/gunicorn -w 4 -b 0.0.0.0:8000 --timeout 120 app:app
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable frotas-api
sudo systemctl start frotas-api
sudo systemctl status frotas-api
```

---

## 📡 Uso da API

### Endpoint Principal: `POST /otimizar`

**Request:**

```json
{
  "base": {
    "lat": -20.21155,
    "lon": -40.25223,
    "name": "Base i9 Engenharia"
  },
  "locais": [
    {
      "id": 1,
      "endereco": "Shopping Vila Velha, Vila Velha-ES"
    },
    {
      "id": 2,
      "lat": -20.3155,
      "lon": -40.3128,
      "name": "Centro Vitória"
    },
    {
      "id": 3,
      "endereco": "Praia da Costa, Vila Velha-ES"
    }
  ],
  "max_diameter_km": 5.0,
  "max_locais_por_rota": 5
}
```

**Response:**

```json
{
  "success": true,
  "timestamp": "2025-12-11T15:30:00",
  "resumo": {
    "total_locais": 50,
    "total_blocos": 12,
    "total_rotas": 18,
    "distancia_total_km": 285.4
  },
  "blocos": [
    {
      "bloco_id": 1,
      "num_locais": 5,
      "num_rotas": 1,
      "distancia_total_km": 18.5,
      "rotas": [
        {
          "route_id": 1,
          "locations": [1, 3, 5, 2, 4],
          "distance_km": 18.5
        }
      ],
      "mapa_url": "/maps/rota_bloco_1_1702312345.html"
    }
  ]
}
```

### Outros Endpoints

**Health Check:**
```bash
GET /health
```

**Testar OSRM:**
```bash
GET /test-osrm
```

**Visualizar Mapa:**
```bash
GET /maps/<filename>.html
```

---

## 🔄 Integração com Frontend PHP

### Opção 1: Chamar API via JavaScript

```javascript
async function otimizarRotas(base, locais) {
    const response = await fetch('http://localhost:8000/otimizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            base: base,
            locais: locais,
            max_diameter_km: 5.0,
            max_locais_por_rota: 5
        })
    });

    const data = await response.json();

    if (data.success) {
        // Exibir blocos e rotas
        data.blocos.forEach(bloco => {
            console.log(`Bloco ${bloco.bloco_id}: ${bloco.num_rotas} rotas`);

            // Abrir mapa
            window.open(`http://localhost:8000${bloco.mapa_url}`, '_blank');
        });
    }
}
```

### Opção 2: Proxy PHP

```php
<?php
function otimizarRotasAPI($base, $locais) {
    $data = [
        'base' => $base,
        'locais' => $locais,
        'max_diameter_km' => 5.0,
        'max_locais_por_rota' => 5
    ];

    $ch = curl_init('http://localhost:8000/otimizar');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Uso
$resultado = otimizarRotasAPI(
    ['lat' => -20.21155, 'lon' => -40.25223, 'name' => 'Base i9'],
    [
        ['id' => 1, 'endereco' => 'Shopping Vila Velha-ES'],
        ['id' => 2, 'lat' => -20.3155, 'lon' => -40.3128]
    ]
);

print_r($resultado);
?>
```

---

## 🐛 Troubleshooting

### OSRM não responde

```bash
# Verificar se está rodando
docker ps | grep osrm

# Ver logs
docker logs osrm-server

# Reiniciar
docker restart osrm-server
```

### Erro de conexão MySQL

```bash
# Testar conexão
mysql -h 187.49.226.10 -u f137049_tool -p f137049_in9aut

# Verificar firewall
telnet 187.49.226.10 3306
```

### API lenta

- Aumentar workers do Gunicorn: `-w 8`
- Aumentar timeout: `--timeout 180`
- Verificar RAM disponível: `free -h`

### Geocodificação falhando

- Nominatim tem rate limit (1 req/seg)
- Cache no MySQL evita requisições repetidas
- Considerar API paga se necessário (Google, Mapbox)

---

## 📊 Performance

| Locais | Tempo Estimado | RAM Usada |
|--------|----------------|-----------|
| 50     | ~10 segundos   | 200 MB    |
| 100    | ~25 segundos   | 400 MB    |
| 200    | ~60 segundos   | 800 MB    |
| 300    | ~120 segundos  | 1.2 GB    |

**Limitações:**
- OSRM local: ~2GB RAM base
- PyVRP: O(n²) complexidade para CVRP
- Geocodificação: 1 req/seg (Nominatim)

---

## 🔐 Segurança

**IMPORTANTE:** API rodando em localhost (porta 8000).

Para expor publicamente (não recomendado sem autenticação):

```bash
# Nginx reverse proxy
location /api/rotas/ {
    proxy_pass http://localhost:8000/;
}
```

Adicionar autenticação:

```python
from flask_httpauth import HTTPBasicAuth

auth = HTTPBasicAuth()

@auth.verify_password
def verify_password(username, password):
    # Validar credenciais
    return username == 'admin' and password == 'senha_segura'

@app.route('/otimizar', methods=['POST'])
@auth.login_required
def otimizar_rotas():
    # ...
```

---

## 📝 TODO / Melhorias Futuras

- [ ] Autenticação JWT
- [ ] Rate limiting
- [ ] Cache de rotas frequentes
- [ ] WebSocket para progresso em tempo real
- [ ] Suporte a múltiplas bases (multi-depot)
- [ ] Considerar janelas de tempo (time windows)
- [ ] Exportar rotas para GPS (.gpx)
- [ ] API de tracking (atualizar posição de veículos)

---

## 📄 Licença

Uso interno - FleetFlow / i9 Engenharia

---

## 👥 Suporte

Dúvidas ou problemas? Entre em contato com a equipe técnica.
