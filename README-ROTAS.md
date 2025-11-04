# Sistema de Otimização de Rotas - FleetFlow

## Visão Geral

Sistema completo de otimização, planejamento e monitoramento de rotas para a frota, com as seguintes funcionalidades:

### Funcionalidades Implementadas

1. **Planejamento de Rotas**
   - Interface intuitiva para adicionar múltiplas paradas
   - Seleção de veículo e motorista
   - Opção de retornar ao ponto de partida
   - Geocodificação automática de endereços

2. **Otimização de Rotas**
   - Cálculo automático da melhor sequência de paradas
   - Estimativa de distância total
   - Estimativa de tempo de viagem
   - Visualização no mapa com Leaflet

3. **Envio via WhatsApp**
   - Envio da rota otimizada diretamente para o motorista
   - Mensagem formatada com todas as paradas
   - Link do Google Maps para navegação

4. **Monitoramento em Tempo Real**
   - Comparação entre rota planejada vs. rota executada
   - Cálculo de desvio da rota
   - Indicador de conformidade (%)
   - Histórico de posições

5. **Gestão de Rotas**
   - Listar todas as rotas salvas
   - Visualizar detalhes de rotas
   - Iniciar e concluir rotas
   - Excluir rotas

## Instalação

### 1. Criar Tabelas no Banco de Dados

Execute o script de setup:

```bash
node setup-routes-db.js
```

Este script criará as seguintes tabelas:
- `FF_Routes` - Armazena as rotas planejadas
- `FF_RouteTracking` - Armazena o histórico de posições durante a execução

### 2. Reiniciar o Servidor

```bash
node server.js
```

### 3. Acessar o Sistema

Abra o navegador e acesse:
- **Planejamento de Rotas**: http://localhost:5000/rotas.html
- **Dashboard**: http://localhost:5000/dashboard.html

## Como Usar

### Planejar uma Nova Rota

1. Acesse a página "Rotas" no menu lateral
2. Preencha o formulário:
   - Nome da rota
   - Selecione o veículo
   - Selecione o motorista
   - Digite o endereço de partida
   - Adicione as paradas (clique em "Adicionar Parada")
   - Marque "Retornar ao ponto de partida" se necessário
3. Clique em "Otimizar Rota"
4. O sistema calculará a melhor rota e exibirá no mapa
5. Clique em "Salvar Rota" para armazenar
6. Clique em "Enviar WhatsApp" para enviar ao motorista

### Monitorar uma Rota

1. Na lista de "Rotas Salvas", clique no ícone de localização (🗺️)
2. Na página de monitoramento:
   - Clique em "Iniciar Rota" quando o motorista começar
   - O sistema atualizará a posição a cada 10 segundos
   - Visualize o desvio e conformidade em tempo real
   - Clique em "Concluir Rota" quando finalizar

### Enviar Rota via WhatsApp

O sistema gera uma mensagem formatada com:
- Nome da rota
- Distância total e tempo estimado
- Lista de todas as paradas em ordem
- Link do Google Maps para navegação

**Importante**: Para o envio funcionar, o motorista precisa ter o campo "Phone" preenchido no banco de dados.

## APIs Utilizadas

### Geocodificação
- **Nominatim (OpenStreetMap)**: Gratuito, sem necessidade de API key
- Converte endereços em coordenadas (latitude/longitude)

### Mapas
- **Leaflet + OpenStreetMap**: Visualização de mapas gratuita
- Alternativa open source ao Google Maps

### Otimização de Rotas
Atualmente usando algoritmo simples (nearest neighbor). Pode ser melhorado com:
- **OpenRouteService API** (gratuito até 2000 requests/dia)
- **Google Maps Directions API** (pago, mais preciso)
- **OSRM (Open Source Routing Machine)** (gratuito, pode hospedar localmente)

## Estrutura do Banco de Dados

### Tabela FF_Routes
```sql
- id (PK)
- name (nome da rota)
- vehicle_id (FK para Vehicles)
- driver_id (FK para Drivers)
- route_data (JSON com todos os dados da rota)
- total_distance (distância total em metros)
- total_duration (tempo total em segundos)
- stops_count (número de paradas)
- status (Planejada, Em Andamento, Concluída, Cancelada)
- created_at, started_at, completed_at
```

### Tabela FF_RouteTracking
```sql
- id (PK)
- route_id (FK para FF_Routes)
- latitude, longitude
- speed
- recorded_at (timestamp)
```

## Integração com API Ituran

Para integração futura com a API Ituran para rastreamento em tempo real:

1. Adicionar endpoint no backend que busca posições da API Ituran
2. Atualizar função `updateVehiclePosition()` em `monitoramento-rota.js`
3. Implementar polling ou WebSocket para atualizações em tempo real

Exemplo de integração:
```javascript
// No backend (server.js)
app.get('/api/vehicles/:id/current-position', async (req, res) => {
    // Chamar API Ituran
    const ituranData = await fetchIturanPosition(vehicleId);

    // Salvar no FF_RouteTracking se houver rota ativa
    if (activeRoute) {
        await pool.query(
            'INSERT INTO FF_RouteTracking (route_id, latitude, longitude, speed) VALUES (?, ?, ?, ?)',
            [routeId, lat, lon, speed]
        );
    }

    res.json({ lat, lon, speed });
});
```

## Melhorias Futuras

1. **Otimização Avançada**
   - Integrar com API profissional de otimização de rotas
   - Considerar tráfego em tempo real
   - Otimização por janelas de tempo (time windows)

2. **Notificações**
   - Alertas quando motorista desvia muito da rota
   - Notificações de chegada em cada parada
   - Alertas de atraso

3. **Relatórios**
   - Relatório de conformidade por motorista
   - Análise de eficiência de rotas
   - Economia de combustível

4. **Interface Mobile**
   - App para motoristas visualizarem a rota
   - Check-in em cada parada
   - Navegação integrada

## Suporte

Em caso de dúvidas ou problemas:
1. Verifique se o banco de dados está configurado corretamente
2. Verifique se o servidor está rodando
3. Abra o console do navegador (F12) para ver erros JavaScript
4. Verifique os logs do servidor no terminal

## Arquivos Criados

- `rotas.html` - Interface de planejamento de rotas
- `rotas.js` - Lógica do frontend de rotas
- `monitoramento-rota.html` - Interface de monitoramento
- `monitoramento-rota.js` - Lógica do monitoramento
- `create-routes-table.sql` - Script SQL para criar tabelas
- `setup-routes-db.js` - Script Node.js para executar o SQL
- `README-ROTAS.md` - Este arquivo

## Endpoints da API

### Rotas
- `POST /api/routes/optimize` - Otimizar rota
- `GET /api/routes` - Listar todas as rotas
- `GET /api/routes/:id` - Buscar rota por ID
- `POST /api/routes` - Salvar nova rota
- `DELETE /api/routes/:id` - Excluir rota
- `POST /api/routes/send-whatsapp` - Enviar rota via WhatsApp
- `GET /api/routes/:id/monitor` - Monitorar rota em tempo real
- `POST /api/routes/:id/start` - Iniciar execução da rota
- `POST /api/routes/:id/complete` - Concluir rota
