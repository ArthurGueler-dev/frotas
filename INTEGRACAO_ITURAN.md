# Integração Ituran - FleetFlow

## 📋 Visão Geral

Este documento descreve a integração do sistema FleetFlow com a API do Ituran para rastreamento e monitoramento de veículos em tempo real.

## 🚀 Funcionalidades Implementadas

### 1. **Rastreamento em Tempo Real**
- Localização GPS atual do veículo
- Velocidade em tempo real
- Status do motor (ligado/desligado)
- Nível de combustível

### 2. **Visualização de Rotas**
- Mapa interativo com Google Maps
- Histórico de rotas (últimas 3 horas)
- Marcador de posição atual
- Trajeto percorrido em polyline azul

### 3. **Telemetria do Veículo**
- Quilometragem atual (odômetro)
- Última atualização dos dados
- Coordenadas GPS (latitude/longitude)
- Endereço atual

### 4. **Gestão de Manutenção**
- Próxima manutenção (em KM)
- Quilômetros até a próxima manutenção
- Alertas de manutenção por criticidade:
  - 🔴 **Crítico**: < 1.000 km
  - 🟡 **Atenção**: < 2.000 km
  - 🟢 **OK**: > 2.000 km

## 📁 Arquivos da Integração

### 1. `ituran-service.js`
Serviço principal de comunicação com a API do Ituran.

**Métodos principais:**
- `getVehicleLocation(vehicleId)` - Obtém localização atual
- `getVehicleRoute(vehicleId, options)` - Obtém histórico de rotas
- `getVehicleTelemetry(vehicleId)` - Obtém dados de telemetria
- `getMaintenanceStatus(vehicleId)` - Obtém status de manutenção
- `getVehicleCompleteData(vehicleId)` - Obtém todos os dados de uma vez

### 2. `vehicle-tracking.js`
Gerenciamento de interface e visualização de dados.

**Funções principais:**
- `openVehicleDetails(vehicleId, vehicleName)` - Abre modal com detalhes
- `closeVehicleDetails()` - Fecha o modal
- `refreshVehicleData()` - Atualiza dados em tempo real
- `loadMapWithRoute()` - Carrega mapa com rota

### 3. `veiculos.html`
Tela principal de gerenciamento de veículos com modal integrado.

## ⚙️ Configuração

### 1. Credenciais da API Ituran

✅ **Credenciais configuradas e prontas para uso!**

O sistema usa a **API SOAP/XML** do Ituran em `iweb.ituran.com.br`.

Credenciais configuradas no arquivo `ituran-service.js`:
- **Username**: api@i9tecnologia
- **Password**: Api@In9Eng
- **Base URL**: https://iweb.ituran.com.br

O sistema:
- Faz requisições GET simples com credenciais nos query parameters
- Parse automático de respostas XML usando DOMParser
- Não requer tokens de autenticação ou OAuth2
- Cache de 30 segundos para otimizar performance

### ⚠️ IMPORTANTE: Servidor Proxy CORS

Devido a restrições de CORS do navegador, é necessário rodar um servidor proxy local.

**Como iniciar o proxy:**

1. **Opção 1 - Usando o script (Windows):**
   ```
   Duplo clique em: start-ituran-proxy.bat
   ```

2. **Opção 2 - Via linha de comando:**
   ```bash
   node ituran-proxy.js
   ```

O proxy será iniciado em `http://localhost:8888` e redirecionará as requisições para o Ituran.

**Deixe o proxy rodando enquanto usar o sistema!**

**Sistema pronto para uso!** Os veículos reais do Ituran serão carregados automaticamente.

### 2. Google Maps API

Para habilitar o mapa interativo, você precisa de uma API Key do Google Maps:

1. Acesse: https://console.cloud.google.com/
2. Crie um novo projeto ou selecione um existente
3. Ative a API "Maps JavaScript API"
4. Crie credenciais (API Key)
5. Edite `veiculos.html` e substitua:

```html
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
```

Por:

```html
<script src="https://maps.googleapis.com/maps/api/js?key=SUA_API_KEY_DO_GOOGLE&callback=initMap" async defer></script>
```

**Nota:** Se não configurar o Google Maps, o sistema mostrará um mapa estático com as coordenadas.

### 3. IDs dos Veículos no Ituran

Certifique-se de que seus veículos no sistema FleetFlow estejam mapeados com os IDs corretos do Ituran:

- Você pode usar a **placa** do veículo como ID
- Ou configurar um campo específico para o ID do Ituran

## 🔧 Endpoints da API Ituran

**Base URL:** `https://iweb.ituran.com.br`

### Principais Endpoints (SOAP/XML):

```
GET /ituranwebservice3/Service3.asmx/GetAllPlatformsData
    - Lista todos os veículos da frota
    - Parâmetros: UserName, Password, ShowAreas, ShowStatuses, ShowMileageInMeters, ShowDriver

GET /ituranmobileservice/mobileservice.asmx/GetVehicleLocationWithActiveStatus
    - Localização atual de um veículo
    - Parâmetros: UserName, Password, strPlatformId

GET /ituranwebservice3/Service3.asmx/GetFullReport
    - Histórico de rotas de um veículo
    - Parâmetros: UserName, Password, Plate, Start, End, UAID, MaxNumberOfRecords

GET /ituranwebservice3/Service3.asmx/GetPlatformData
    - Dados detalhados de um veículo
    - Parâmetros: UserName, Password, Plate, ShowAreas, ShowStatuses
```

### Parâmetros de Autenticação:

Todas as requisições incluem:
- `UserName`: api@i9tecnologia
- `Password`: Api@In9Eng

### Formato de Resposta:

```xml
<?xml version="1.0" encoding="utf-8"?>
<Response xmlns="http://www.ituran.com/ituranWebService3">
    <ReturnCode>OK</ReturnCode>
    <!-- Dados específicos do endpoint -->
</Response>
```

## 📊 Estrutura de Dados

### Resposta de Localização:
```json
{
  "vehicleId": "ABC-1234",
  "latitude": -23.550520,
  "longitude": -46.633308,
  "heading": 90,
  "speed": 45,
  "timestamp": "2025-01-16T10:30:00Z",
  "address": "Av. Paulista, São Paulo - SP"
}
```

### Resposta de Telemetria:
```json
{
  "odometer": 87500,
  "currentSpeed": 0,
  "fuelLevel": 75,
  "engineStatus": "off",
  "lastUpdate": "2025-01-16T10:30:00Z"
}
```

### Resposta de Manutenção:
```json
{
  "nextMaintenance": 90000,
  "lastMaintenance": 80000,
  "kmUntilMaintenance": 2500,
  "alerts": [],
  "status": "ok"
}
```

## 🎯 Como Usar

### 1. Na Tela de Veículos

1. Navegue até **Veículos** no menu lateral
2. Na lista de veículos ativos, clique em **"Detalhes"**
3. O modal será aberto com todos os dados do Ituran

### 2. Visualizar Rota

- O mapa carrega automaticamente ao abrir os detalhes
- A rota das últimas 3 horas é exibida em azul
- O ponto atual é marcado com um círculo azul

### 3. Atualizar Dados

- Clique no botão **"Atualizar Dados"** no modal
- Os dados serão recarregados do Ituran
- O mapa e a rota serão atualizados

## 🔍 Desenvolvimento e Testes

### Modo Mock (Dados de Exemplo)

O sistema inclui dados de exemplo para desenvolvimento. Para testar sem a API real:

1. Os métodos `_getMock*` em `ituran-service.js` retornam dados simulados
2. Quando a API falhar, o sistema automaticamente usa dados mock
3. Útil para desenvolvimento local sem credenciais

### Cache de Dados

- Dados de localização são cacheados por 30 segundos
- Use `ituranService.clearCache()` para forçar atualização
- O botão "Atualizar" limpa o cache automaticamente

## 🐛 Solução de Problemas

### Erro: "Erro ao buscar localização"

**Possíveis causas:**
- API Key inválida ou expirada
- Vehicle ID não encontrado no Ituran
- Problemas de conexão com a API

**Solução:**
- Verifique as credenciais em `ituran-service.js`
- Confirme que o veículo está cadastrado no Ituran
- Verifique o console do navegador para detalhes do erro

### Mapa não aparece

**Possíveis causas:**
- Google Maps API Key não configurada
- API Key inválida ou com restrições
- API do Google Maps não foi ativada no projeto

**Solução:**
- Configure a API Key corretamente
- Verifique cotas e permissões no Google Cloud Console
- O sistema mostrará um mapa estático se o Google Maps falhar

### Dados desatualizados

**Possíveis causas:**
- Cache ainda válido (30 segundos)
- Veículo sem comunicação com Ituran

**Solução:**
- Clique em "Atualizar Dados"
- Verifique o campo "Última Atualização"
- Confirme se o rastreador do veículo está funcionando

## 📝 Notas Importantes

1. **Segurança:** Nunca exponha suas credenciais da API em repositórios públicos
2. **Performance:** O cache reduz chamadas desnecessárias à API
3. **Custos:** Verifique o plano contratado com o Ituran para limites de requisições
4. **Privacidade:** Respeite a Lei Geral de Proteção de Dados (LGPD)

## 🤝 Suporte

Para mais informações sobre a API do Ituran:
- Documentação: https://docs.ituran.com/
- Suporte: contatoapp@ituran.com.br
- Telefone: Consulte o site oficial

## 📄 Licença

Este código é parte do sistema FleetFlow e deve ser usado conforme os termos de licença do projeto.
