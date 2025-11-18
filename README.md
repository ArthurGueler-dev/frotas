# 🚗 FleetFlow - Sistema de Gestão de Frotas

Sistema completo de gestão de frotas com integração em tempo real com API Ituran, planos de manutenção preventiva personalizados e interface moderna.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Node](https://img.shields.io/badge/node-%3E%3D14.0.0-brightgreen.svg)
![Status](https://img.shields.io/badge/status-active-success.svg)

## 📋 Índice

- [Sobre](#sobre)
- [Funcionalidades](#funcionalidades)
- [Tecnologias](#tecnologias)
- [Instalação](#instalação)
- [Uso](#uso)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [API](#api)
- [Contribuindo](#contribuindo)

## 🎯 Sobre

O **FleetFlow** é um sistema completo de gestão de frotas desenvolvido para empresas que precisam monitorar veículos, controlar manutenções preventivas e gerenciar motoristas de forma eficiente.

O sistema se integra com a API Ituran para obter dados de telemetria em tempo real e oferece planos de manutenção personalizados para 28 modelos diferentes de veículos.

## ✨ Funcionalidades

### 📊 Dashboard em Tempo Real
- KM rodados hoje, ontem e no mês
- Veículos em movimento
- Ranking dos 10 veículos que mais rodaram
- Alertas de manutenção pendente
- Sincronização automática com API Ituran

### 🔧 Planos de Manutenção Preventiva
- **28 modelos de veículos** com planos específicos
- Planos baseados em **manuais oficiais** dos fabricantes
- Intervalos em **km e tempo**
- **Custos estimados** para cada serviço
- Níveis de **criticidade** (alta, média, baixa)
- Alertas automáticos de manutenção vencida

### 🚙 Gestão de Veículos
- Cadastro completo de veículos
- Histórico de manutenções
- Controle de quilometragem
- Status (ativo, em manutenção, inativo)
- Integração com telemetria Ituran

### 👥 Gestão de Motoristas
- Cadastro de motoristas
- Vinculação com veículos
- Histórico de uso
- Controle de CNH

### 📍 Gestão de Rotas
- Cadastro de rotas
- Controle de quilometragem por rota
- Associação com veículos

### 🎨 Interface Moderna
- Design responsivo (mobile, tablet, desktop)
- Dark mode
- Sidebar unificado
- Tailwind CSS
- Material Icons

## 🛠 Tecnologias

### Backend
- **Node.js** - Runtime JavaScript
- **Express** - Framework web
- **MySQL** - Banco de dados
- **Axios** - Cliente HTTP para API Ituran

### Frontend
- **HTML5/CSS3**
- **JavaScript ES6+**
- **Tailwind CSS** - Framework CSS
- **Material Icons** - Ícones

### Integrações
- **API Ituran** - Telemetria em tempo real
- **LocalStorage** - Cache de dados

## 📦 Instalação

### Pré-requisitos

- Node.js >= 14.0.0
- MySQL >= 5.7
- NPM ou Yarn

### Passo a Passo

1. Clone o repositório:
```bash
git clone https://github.com/ArthurGueler-dev/frotas.git
cd frotas
```

2. Instale as dependências:
```bash
npm install
```

3. Inicie o servidor:
```bash
npm start
```

4. Acesse no navegador:
```
http://localhost:5000
```

## 🚀 Uso

### Dashboard
Acesse `http://localhost:5000/` para visualizar:
- Quilometragem em tempo real
- Status da frota
- Alertas de manutenção
- Ranking de veículos

### Sincronização Manual
Clique em **"Sincronizar Quilometragem"** para forçar atualização dos dados da API Ituran.

## 📁 Estrutura do Projeto

```
frotas/
├── server.js                    # Servidor Express
├── package.json                 # Dependências
├── dashboard.html               # Dashboard principal
├── veiculos.html               # Gestão de veículos
├── motoristas.html             # Gestão de motoristas
├── modelos.html                # Modelos de veículos
├── planos-manutencao.html      # Planos de manutenção
├── rotas.html                  # Gestão de rotas
├── dashboard-stats.js          # Cálculos de estatísticas
├── sidebar.js                  # Sidebar unificado
└── ituran-service.js           # Integração com API Ituran
```

## 🔌 API

### Endpoints Principais

#### Veículos
```javascript
GET    /api/vehicles           # Lista todos os veículos
POST   /api/vehicles           # Cria novo veículo
PUT    /api/vehicles/:id       # Atualiza veículo
DELETE /api/vehicles/:id       # Remove veículo
```

#### Manutenções
```javascript
GET    /api/maintenances                    # Lista manutenções
GET    /api/maintenance-plan-items          # Planos de manutenção
POST   /api/maintenance-plan-items          # Cria item de plano
```

## 🎨 Personalização

### Modo Debug
Para ativar logs detalhados, edite `dashboard-stats.js`:
```javascript
const DEBUG_MODE = true; // Ativa logs completos
```

## 🤝 Contribuindo

Contribuições são bem-vindas!

## 📝 Licença

Este projeto está sob a licença MIT.

## 👨‍💻 Autor

**Arthur Gueler**
- GitHub: [@ArthurGueler-dev](https://github.com/ArthurGueler-dev)

---

⭐ Se este projeto foi útil, considere dar uma estrela!

🤖 Desenvolvido com [Claude Code](https://claude.com/claude-code)
