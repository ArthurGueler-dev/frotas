# 📤 Arquivos para Fazer Upload no cPanel

## 🎯 Problema Atual
Erro de CORS ao importar locais - falta fazer upload dos arquivos PHP corrigidos.

---

## 📋 Lista de Arquivos para Upload

### ✅ Obrigatórios (fazer upload agora)

**1. `cpanel-api/locations-api.php`**
- **O que faz:** Busca locais filtrados por bloco (BUG CORRIGIDO)
- **Destino:** `public_html/locations-api.php`

**2. `otimizar-rotas-async.php`**
- **O que faz:** Proxy para API Python de otimização
- **Destino:** `public_html/otimizar-rotas-async.php`

---

## 🚀 Como Fazer Upload

### Via cPanel File Manager (Recomendado)

1. **Login no cPanel**
   - URL: https://floripa.in9automacao.com.br/cpanel

2. **Abrir File Manager**
   - Clique em "Gerenciador de Arquivos" / "File Manager"

3. **Navegar até public_html**
   - Clique na pasta `public_html` no lado esquerdo

4. **Fazer Upload**
   - Clique no botão **"Upload"** no topo
   - Clique em **"Selecionar Arquivo"**
   - Navegue até `C:\Users\SAMSUNG\Desktop\frotas\cpanel-api\`
   - Selecione **`locations-api.php`**
   - Aguarde upload completar (barra verde 100%)

5. **Repetir para o segundo arquivo**
   - Ainda na tela de upload
   - Clique em **"Selecionar Arquivo"** novamente
   - Navegue até `C:\Users\SAMSUNG\Desktop\frotas\`
   - Selecione **`otimizar-rotas-async.php`**
   - Aguarde upload completar

6. **Verificar se os arquivos estão lá**
   - Volte para o File Manager
   - Você deve ver os arquivos em `public_html/`:
     - ✅ `locations-api.php`
     - ✅ `otimizar-rotas-async.php`

---

## 🧪 Testar Após Upload

### Teste 1: Verificar locations-api.php
Abra no navegador:
```
https://floripa.in9automacao.com.br/locations-api.php
```
Deve retornar JSON (não erro 404)

### Teste 2: Verificar otimizar-rotas-async.php
Abra no navegador:
```
https://floripa.in9automacao.com.br/otimizar-rotas-async.php
```
Deve retornar JSON (não erro 404)

### Teste 3: Importar Planilha
1. Abra: http://localhost:5000/otimizador-blocos.html
2. Selecione sua planilha Excel
3. Marque "Criar blocos automaticamente"
4. Clique em "Processar Arquivo"
5. **Não deve dar erro de CORS!** ✅

---

## ⚠️ Se Continuar Dando Erro

### Verificar Permissões
No File Manager, clique com botão direito no arquivo → Permissions
- Deve estar: **644** (rw-r--r--)

### Limpar Cache do Navegador
- Pressione `Ctrl + Shift + Delete`
- Marque "Cache"
- Limpar

### Verificar se arquivo foi para local correto
Os arquivos DEVEM estar em:
```
public_html/
  ├── locations-api.php        ✅
  ├── otimizar-rotas-async.php ✅
  └── (outros arquivos...)
```

**NÃO devem estar em:**
```
public_html/cpanel-api/...     ❌ ERRADO!
```

---

## 📝 Checklist

- [ ] Upload `locations-api.php` para `public_html/`
- [ ] Upload `otimizar-rotas-async.php` para `public_html/`
- [ ] Testar URL: https://floripa.in9automacao.com.br/locations-api.php
- [ ] Testar URL: https://floripa.in9automacao.com.br/otimizar-rotas-async.php
- [ ] Tentar importar planilha novamente
- [ ] Verificar se não dá erro de CORS

---

## 🎯 Após Upload Bem-Sucedido

O erro de CORS deve desaparecer e você poderá:
1. Importar a planilha
2. Sistema vai criar os blocos automaticamente
3. Blocos terão apenas os locais corretos (sem duplicação)
4. Mensagem WhatsApp vai sair limpa

---

## 💡 Dica

Se preferir, pode usar um cliente FTP como FileZilla:
- Host: floripa.in9automacao.com.br
- Porta: 21
- Arraste os arquivos para `public_html/`
