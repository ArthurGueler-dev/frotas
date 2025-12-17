# Correções do Sidebar - 27/11/2025

## Problemas Corrigidos:

### 1. ✅ Link "Serviços" não aparecia no sidebar
**Causa:** Cache do navegador carregando versão antiga do `sidebar.js`

**Solução:**
- Adicionado parâmetro de versão em todas as páginas HTML
- Todas as páginas agora carregam `sidebar.js?v=20251127-003`

### 2. ✅ Sidebar sumia ao scrollar a página
**Causa:** Sidebar sem `position: fixed`

**Solução:**
- Adicionado `position: fixed` ao elemento `<aside>` do sidebar
- Adicionado `margin-left: 256px` automático ao elemento `<main>` para compensar o sidebar fixo
- Agora o sidebar permanece visível ao scrollar em todas as páginas

---

## Páginas Atualizadas:

✅ dashboard.html
✅ dashboard-manutencoes.html (Alertas)
✅ manutencao.html
✅ planos-manutencao-novo.html
✅ planos-manutencao.html
✅ planos-manutencao-funcional.html
✅ modelos.html
✅ pecas.html
✅ servicos.html
✅ motoristas.html
✅ veiculos.html
✅ rotas.html
✅ lancar-os.html
✅ admin-checklist.html

---

## Como Testar:

1. **Limpar cache do navegador:**
   - Pressione `Ctrl + F5` (Windows) ou `Cmd + Shift + R` (Mac)
   - Ou abra uma janela anônima/privada

2. **Verificar o link "Serviços":**
   - Deve aparecer no sidebar na seção "Manutenção Preventiva"
   - Localizado entre "Peças" e a seção "CheckList"

3. **Testar scroll:**
   - Acesse qualquer página (dashboard, motoristas, manutenção, etc.)
   - Role a página para baixo
   - O sidebar deve permanecer fixo no lado esquerdo

---

## Alterações Técnicas:

### sidebar.js (linha 6):
```javascript
// ANTES:
<aside class="w-64 bg-white dark:bg-gray-800 flex flex-col border-r border-gray-200 dark:border-gray-700 h-screen">

// DEPOIS:
<aside class="w-64 bg-white dark:bg-gray-800 flex flex-col border-r border-gray-200 dark:border-gray-700 h-screen fixed top-0 left-0">
```

### sidebar.js (linhas 139-143):
```javascript
// Adicionar margem ao conteúdo principal para compensar o sidebar fixo
const mainContent = document.querySelector('main');
if (mainContent && !mainContent.classList.contains('ml-64')) {
    mainContent.style.marginLeft = '256px';
}
```

### Todas as páginas HTML:
```html
<!-- ANTES: -->
<script src="sidebar.js"></script>
<script src="sidebar.js?v=20251126-2002"></script>

<!-- DEPOIS: -->
<script src="sidebar.js?v=20251127-003"></script>
```
