# Como Iniciar os Servidores do Sistema

Este documento explica como iniciar todos os servidores necessários para o funcionamento do sistema de gestão de frotas.

## Servidores Necessários

O sistema possui 2 servidores que precisam estar rodando:

1. **Servidor Principal** (porta 5000)
   - Arquivo: `server.js`
   - Função: API principal, interface web, gerenciamento de dados

2. **Proxy WhatsApp** (porta 3001)
   - Arquivo: `enviar-whatsapp-proxy.js`
   - Função: Comunicação com Evolution API para envio de mensagens

---

## Método 1: Script Batch Simples (Recomendado para desenvolvimento)

### Iniciar Servidores

Duplo clique no arquivo:
```
start-servers.bat
```

Ou execute no terminal:
```bash
start-servers.bat
```

Isso irá:
- ✅ Abrir 2 janelas de terminal (uma para cada servidor)
- ✅ Iniciar servidor principal na porta 5000
- ✅ Iniciar proxy WhatsApp na porta 3001
- ✅ Abrir o sistema no navegador automaticamente

### Parar Servidores

**Opção 1:** Feche manualmente as janelas do terminal

**Opção 2:** Execute o script:
```bash
stop-servers.bat
```

---

## Método 2: PM2 (Recomendado para produção)

PM2 é um gerenciador de processos profissional que:
- ✅ Reinicia automaticamente em caso de crash
- ✅ Gerencia logs
- ✅ Permite monitoramento em tempo real
- ✅ Pode reiniciar automaticamente no boot do Windows

### Primeira vez (instalar PM2)

```bash
npm install -g pm2
```

### Iniciar com PM2

**Opção 1:** Duplo clique no arquivo:
```
start-pm2.bat
```

**Opção 2:** Execute manualmente:
```bash
pm2 start ecosystem.config.js
```

### Comandos PM2 Úteis

```bash
# Ver status de todos os servidores
pm2 status

# Ver logs em tempo real
pm2 logs

# Ver logs de um servidor específico
pm2 logs frotas-main
pm2 logs whatsapp-proxy

# Monitor visual (CPU, memória, etc)
pm2 monit

# Reiniciar todos
pm2 restart all

# Reiniciar servidor específico
pm2 restart frotas-main
pm2 restart whatsapp-proxy

# Parar todos
pm2 stop all

# Parar servidor específico
pm2 stop frotas-main

# Remover todos da lista PM2
pm2 delete all
```

### Configurar Reinício Automático no Boot

Para que os servidores iniciem automaticamente quando o Windows iniciar:

```bash
# 1. Salvar configuração atual
pm2 save

# 2. Gerar script de startup
pm2 startup

# 3. Execute o comando que o PM2 mostrar na tela
```

---

## Verificar se Servidores Estão Rodando

### Método 1: Navegador
- Servidor Principal: http://localhost:5000
- Proxy WhatsApp: http://localhost:3001/health

### Método 2: Terminal
```bash
# Verificar portas em uso
netstat -ano | findstr :5000
netstat -ano | findstr :3001

# Testar com curl
curl http://localhost:5000
curl http://localhost:3001/health
```

### Método 3: PM2
```bash
pm2 status
```

---

## Estrutura de Logs

### Com Script Batch
Os logs aparecem diretamente nas janelas do terminal.

### Com PM2
Os logs são salvos em:
```
logs/
  ├── main-error.log      # Erros do servidor principal
  ├── main-out.log        # Saída do servidor principal
  ├── whatsapp-error.log  # Erros do proxy WhatsApp
  └── whatsapp-out.log    # Saída do proxy WhatsApp
```

---

## Troubleshooting

### Erro: "Porta já em uso"

Se aparecer erro dizendo que a porta já está em uso:

```bash
# Parar todos os processos Node.js
stop-servers.bat

# Ou manualmente encontrar e matar o processo
netstat -ano | findstr :5000
taskkill /PID [número_do_pid] /F
```

### Erro: "PM2 não encontrado"

```bash
npm install -g pm2
```

### Erro: "Node.js não encontrado"

Instale o Node.js de https://nodejs.org/

### Servidor não responde

```bash
# Com PM2
pm2 restart all

# Com script batch
stop-servers.bat
start-servers.bat
```

### Ver erros detalhados

```bash
# Com PM2
pm2 logs --err

# Com script batch
Verifique as janelas do terminal que foram abertas
```

---

## Dicas

1. **Para desenvolvimento**: Use `start-servers.bat` para ver os logs em tempo real

2. **Para produção**: Use PM2 com `pm2 start ecosystem.config.js` para:
   - Reinício automático em caso de erro
   - Logs organizados
   - Monitoramento de recursos

3. **Manter servidores rodando 24/7**:
   ```bash
   pm2 start ecosystem.config.js
   pm2 save
   pm2 startup
   ```

4. **Atualizar código sem parar servidor**:
   ```bash
   git pull
   pm2 reload all
   ```

---

## Acesso ao Sistema

Após iniciar os servidores, acesse:

**Sistema principal:**
http://localhost:5000

**Páginas disponíveis:**
- http://localhost:5000/veiculos.html
- http://localhost:5000/motoristas.html
- http://localhost:5000/manutencao.html
- http://localhost:5000/dashboard.html
- http://localhost:5000/otimizador-blocos.html
- http://localhost:5000/gerenciar-rotas.html

---

## Suporte

Se encontrar problemas:

1. Verifique os logs (PM2: `pm2 logs` ou janelas do terminal)
2. Confirme que as portas 5000 e 3001 não estão sendo usadas por outros programas
3. Certifique-se de que o Node.js está instalado (`node --version`)
4. Verifique se todas as dependências foram instaladas (`npm install`)
