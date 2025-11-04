# Como Usar a Integração Ituran

## 🚀 Início Rápido

### Passo 1: Iniciar o Proxy

O navegador bloqueia requisições diretas para o Ituran por questões de segurança (CORS).
Por isso, você precisa iniciar um servidor proxy local.

**Windows:**
```
Duplo clique em: start-ituran-proxy.bat
```

**Ou via terminal:**
```bash
node ituran-proxy.js
```

Você verá:
```
🚀 Proxy Ituran rodando em http://localhost:8888
📡 Redirecionando requisições para https://iweb.ituran.com.br
```

### Passo 2: Abrir o Sistema

Com o proxy rodando, abra o sistema FleetFlow normalmente:
```
http://localhost:5000/veiculos.html
```

### Passo 3: Ver os Veículos

Os veículos do Ituran serão carregados automaticamente na tela de Veículos!

---

## ❓ Solução de Problemas

### Erro: "Failed to fetch" ou "CORS policy"

**Causa:** O proxy não está rodando.

**Solução:**
1. Inicie o proxy com `start-ituran-proxy.bat`
2. Deixe a janela do proxy aberta
3. Recarregue a página do sistema

### Erro: "EADDRINUSE" (porta em uso)

**Causa:** Já existe um proxy rodando na porta 8888.

**Solução:**
1. Feche outras instâncias do proxy
2. Ou reinicie o computador

### Veículos não aparecem

**Causa:** Possível erro na API ou credenciais.

**Solução:**
1. Verifique o console do navegador (F12)
2. Verifique o terminal do proxy para ver os logs
3. Confirme que as credenciais estão corretas em `ituran-service.js`

---

## 📋 Checklist de Uso Diário

- [ ] Iniciar o proxy: `start-ituran-proxy.bat`
- [ ] Abrir o sistema: `http://localhost:5000`
- [ ] Navegar para: **Veículos**
- [ ] Ver os veículos carregados do Ituran

**IMPORTANTE:** Mantenha a janela do proxy aberta enquanto usar o sistema!

---

## 🔧 Arquivos da Integração

- `ituran-proxy.js` - Servidor proxy Node.js
- `start-ituran-proxy.bat` - Script para iniciar o proxy (Windows)
- `ituran-service.js` - Serviço de integração com API
- `INTEGRACAO_ITURAN.md` - Documentação completa

---

## 📞 Precisa de Ajuda?

Consulte a documentação completa em `INTEGRACAO_ITURAN.md`
