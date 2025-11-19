# 📡 Instalação da API de Telemetria no cPanel

## Arquivos para Upload

Faça upload dos seguintes arquivos para o cPanel:

### 1. Script Node.js
- **Arquivo local:** `sync-telemetria.js`
- **Destino no cPanel:** `/home/f137049/public_html/api/sync-telemetria.js`
- **Permissões:** `chmod +x sync-telemetria.js` (executável)

### 2. Endpoint PHP
- **Arquivo local:** `sincronizar.php`
- **Destino no cPanel:** `/home/f137049/public_html/api/sincronizar.php`
- **Permissões:** `chmod 644 sincronizar.php`

## Instalação via cPanel File Manager

1. **Acesse o cPanel** → File Manager
2. Navegue até `/public_html/`
3. Crie a pasta `api` (se não existir)
4. Faça upload dos 2 arquivos para `/public_html/api/`
5. Selecione `sync-telemetria.js` → Clique com botão direito → Change Permissions → `755` (executável)

## Instalação via SSH (Alternativa)

```bash
cd /home/f137049/public_html/
mkdir -p api
cd api

# Upload dos arquivos via FTP/SFTP para esta pasta

# Dar permissão de execução
chmod +x sync-telemetria.js
chmod 644 sincronizar.php
```

## Verificar Instalação do Node.js no cPanel

Execute via Terminal SSH:

```bash
node --version
```

Se Node.js não estiver instalado, instale via:
- **cPanel** → Software → Setup Node.js App
- Ou via SSH: `nvm install node`

## Instalar Dependências

```bash
cd /home/f137049/public_html/api/
npm install mysql2 xmldom
```

## Testar a API

### Via Terminal (SSH):
```bash
cd /home/f137049/public_html/api/
node sync-telemetria.js
```

### Via Browser/Postman:
```
POST https://floripa.in9automacao.com.br/api/sincronizar.php
```

Resposta esperada:
```json
{
  "success": true,
  "total": 77,
  "sucessos": 75,
  "falhas": 2,
  "resultados": [...]
}
```

## Configurar Dashboard Local

Após a instalação no cPanel, atualize o dashboard local para usar a API:

**Arquivo:** `dashboard.html`

```javascript
// Trocar:
const response = await fetch('/api/telemetria/atualizar-todos', {
    method: 'POST'
});

// Por:
const response = await fetch('https://floripa.in9automacao.com.br/api/sincronizar.php', {
    method: 'POST'
});
```

## Problemas Comuns

### 1. "node: command not found"
**Solução:** Instale Node.js no cPanel (Setup Node.js App)

### 2. "Permission denied"
**Solução:** `chmod +x sync-telemetria.js`

### 3. "Module not found: mysql2"
**Solução:** `npm install mysql2 xmldom`

### 4. "CORS error"
**Solução:** Já está configurado no `sincronizar.php` com headers CORS

## Estrutura Final no cPanel

```
/home/f137049/public_html/
├── api/
│   ├── sincronizar.php        (Endpoint HTTP)
│   ├── sync-telemetria.js     (Script Node.js)
│   ├── node_modules/          (Dependências)
│   └── package.json           (Opcional)
└── services/
    └── telemetria-updater.js  (Arquivo antigo - pode remover)
```

## URL Final da API

```
POST https://floripa.in9automacao.com.br/api/sincronizar.php
```

✅ Pronto! A API está instalada e funcionando!
