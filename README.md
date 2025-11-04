# FleetFlow - Sistema de Gestão de Frotas

Sistema completo de gerenciamento de frotas de veículos com backend Node.js e frontend responsivo.

## 🚀 Tecnologias

- **Backend:** Node.js + Express
- **Frontend:** HTML5 + TailwindCSS + JavaScript
- **API:** RESTful

## 📋 Funcionalidades

- ✅ Dashboard com estatísticas em tempo real
- ✅ Gestão completa de veículos (CRUD)
- ✅ Sistema de manutenções (Kanban)
- ✅ Alertas e notificações
- ✅ Gestão de motoristas
- ✅ Design responsivo e modo escuro

## 🔧 Como Executar

### Método 1: Script Automático (Windows)
```bash
# Clique duplo no arquivo
start.bat
```

### Método 2: Manual
```bash
# Instalar dependências
npm install

# Iniciar servidor
npm start
```

## 🌐 Acessar o Sistema

Após iniciar o servidor, acesse:

- **Dashboard:** http://localhost:3000
- **Veículos:** http://localhost:3000/veiculos

## 📡 API Endpoints

### Estatísticas
- `GET /api/stats` - Estatísticas gerais

### Veículos
- `GET /api/vehicles` - Listar todos
- `GET /api/vehicles?status=Ativo` - Filtrar por status
- `GET /api/vehicles/:id` - Buscar por ID
- `POST /api/vehicles` - Criar novo
- `PUT /api/vehicles/:id` - Atualizar
- `DELETE /api/vehicles/:id` - Remover

### Manutenções
- `GET /api/maintenances` - Listar manutenções
- `POST /api/maintenances` - Criar manutenção

### Motoristas
- `GET /api/drivers` - Listar motoristas
- `POST /api/drivers` - Criar motorista

### Alertas
- `GET /api/alerts` - Buscar alertas

## 📦 Estrutura do Projeto

```
frotas/
├── server.js           # Servidor Express
├── api-client.js       # Cliente API (frontend)
├── dashboard.html      # Página do dashboard
├── veiculos.html       # Página de veículos
├── package.json        # Dependências
├── start.bat          # Script de inicialização
└── README.md          # Documentação
```

## 🎨 Recursos do Frontend

- Design moderno com TailwindCSS
- Gráficos e visualizações
- Sistema de tabs e modais
- Filtros e ordenação
- Responsivo (mobile-first)

## 📝 Exemplo de Uso da API

### Criar um veículo
```javascript
fetch('http://localhost:3000/api/vehicles', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        plate: 'XYZ-9999',
        brand: 'Toyota',
        model: 'Corolla',
        year: 2024,
        mileage: 0,
        status: 'Ativo',
        color: 'Prata',
        fuel: 'Flex',
        type: 'Passeio'
    })
});
```

## 🔐 Dados Iniciais

O sistema já vem com 9 veículos de exemplo, 3 manutenções e 3 motoristas para teste.

## 👨‍💻 Desenvolvido por

Sistema FleetFlow - Gestão Inteligente de Frotas
