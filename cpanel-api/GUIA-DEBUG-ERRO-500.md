# Guia de Debug - Erro 500 na API

## Problema Atual
A URL `https://floripa.in9automacao.com.br/api/sincronizar.php` retorna erro 500.

## Causa do Erro 500
Erro 500 = erro interno do servidor PHP. Pode ser:
1. Erro de sintaxe no PHP
2. Extensao PHP faltando (PDO, mysqli, simplexml)
3. Timeout de execucao
4. Erro de conexao com banco
5. Permissoes incorretas do arquivo

---

## Plano de Acao - Testar do Simples ao Complexo

### PASSO 1: Verificar se PHP funciona
**Arquivo:** `teste-minimo.php`
**Upload para:** `/home/f137049/public_html/api/teste-minimo.php`

**Teste:**
```
https://floripa.in9automacao.com.br/api/teste-minimo.php
```

**Resposta esperada:**
```json
{
  "status": "OK",
  "mensagem": "PHP funciona!",
  "timestamp": "2025-11-05 17:30:00",
  "php_version": "5.6.40"
}
```

**Se der erro 500:**
- O problema e de permissoes ou configuracao do servidor
- Verifique permissoes do arquivo (deve ser 644)
- Verifique se a pasta /api existe

**Se funcionar:**
- PHP esta OK, va para o PASSO 2

---

### PASSO 2: Verificar conexao com banco
**Arquivo:** `sincronizar-simples.php`
**Upload para:** `/home/f137049/public_html/api/sincronizar-simples.php`

**Teste:**
```
https://floripa.in9automacao.com.br/api/sincronizar-simples.php
```

**Resposta esperada:**
```json
{
  "success": true,
  "mensagem": "Conexao OK",
  "total_veiculos": 77,
  "timestamp": "2025-11-05 17:30:00"
}
```

**Se der erro 500:**
- O problema e com PDO ou conexao MySQL
- Verifique se extensao pdo_mysql esta habilitada
- Verifique se o IP 187.49.226.10 esta acessivel

**Se funcionar:**
- Conexao com banco esta OK, va para o PASSO 3

---

### PASSO 3: Verificar diagnostico completo
**Arquivo:** `teste-conexao.php`
**Upload para:** `/home/f137049/public_html/api/teste-conexao.php`

**Teste:**
```
https://floripa.in9automacao.com.br/api/teste-conexao.php
```

**Resposta esperada:**
```json
{
  "timestamp": "2025-11-05 17:30:00",
  "php_version": "5.6.40",
  "testes": {
    "mysql": { "status": "OK" },
    "tabela_vehicles": { "status": "OK", "total": 77 },
    "tabela_telemetria": { "status": "OK", "total": 150 },
    "extensoes": {
      "pdo": "OK",
      "pdo_mysql": "OK",
      "simplexml": "OK",
      "openssl": "OK"
    }
  }
}
```

**Se algum teste falhar:**
- Identifique qual extensao esta faltando
- Contate o suporte do cPanel para habilitar

**Se funcionar:**
- Tudo OK para rodar a API completa, va para o PASSO 4

---

### PASSO 4: Testar API completa
**Arquivo:** `sincronizar-v4.php`
**Upload para:** `/home/f137049/public_html/api/sincronizar.php` (renomeie!)

**Teste:**
```
https://floripa.in9automacao.com.br/api/sincronizar.php
```

**Resposta esperada (demora 1-2 minutos):**
```json
{
  "success": true,
  "total": 77,
  "sucessos": 70,
  "falhas": 7,
  "resultados": [...]
}
```

**Se der erro 500:**
- O problema e com a API Ituran ou timeout
- Aumente o timeout no php.ini
- Verifique se consegue acessar https://iweb.ituran.com.br

**Se funcionar:**
- TUDO OK! API funcionando

---

## Como Ver os Logs de Erro

### No cPanel:
1. Va em **Metrics** > **Errors**
2. Ou acesse: **File Manager** > `/home/f137049/logs/error_log`

### O que procurar:
- `PHP Parse error` = erro de sintaxe
- `Fatal error` = extensao faltando ou funcao nao existe
- `Connection refused` = problema com MySQL
- `Maximum execution time` = timeout

---

## Arquivos para Upload (em ordem de teste)

1. `teste-minimo.php` - Testa se PHP funciona
2. `sincronizar-simples.php` - Testa conexao com banco
3. `teste-conexao.php` - Diagnostico completo
4. `sincronizar-v4.php` - API completa (renomear para sincronizar.php)

---

## Checklist de Debug

- [ ] Upload teste-minimo.php
- [ ] Teste: https://floripa.in9automacao.com.br/api/teste-minimo.php
- [ ] Se OK: Upload sincronizar-simples.php
- [ ] Teste: https://floripa.in9automacao.com.br/api/sincronizar-simples.php
- [ ] Se OK: Upload teste-conexao.php
- [ ] Teste: https://floripa.in9automacao.com.br/api/teste-conexao.php
- [ ] Se OK: Upload sincronizar-v4.php como sincronizar.php
- [ ] Teste: https://floripa.in9automacao.com.br/api/sincronizar.php
- [ ] Se OK: Teste pelo dashboard

---

## Permissoes Corretas

Todos os arquivos .php devem ter permissao **644**:
- Dono: Leitura + Escrita (6)
- Grupo: Leitura (4)
- Outros: Leitura (4)

Para alterar no cPanel File Manager:
1. Clique direito no arquivo
2. Change Permissions
3. Digite: 644

---

## Proximos Passos Apos Funcionar

1. Configurar cron job para sincronizacao automatica
2. Monitorar logs regularmente
3. Ajustar timeout se necessario

---

**Data:** 2025-11-05
**Status:** Aguardando testes no cPanel
