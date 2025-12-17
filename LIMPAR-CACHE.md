# Como Limpar o Cache do Navegador

O link "Serviços" foi adicionado ao sidebar, mas pode não aparecer devido ao cache do navegador.

## Soluções:

### 1. Hard Refresh (Recomendado - Mais Rápido)
Pressione as seguintes teclas enquanto estiver em qualquer página do sistema:

- **Windows/Linux**: `Ctrl + F5` ou `Ctrl + Shift + R`
- **Mac**: `Cmd + Shift + R`

### 2. Limpar Cache Completo do Site

#### Google Chrome:
1. Pressione `Ctrl + Shift + Delete` (Windows) ou `Cmd + Shift + Delete` (Mac)
2. Selecione "Imagens e arquivos em cache"
3. No período, escolha "Desde sempre" ou "Última hora"
4. Clique em "Limpar dados"

#### Microsoft Edge:
1. Pressione `Ctrl + Shift + Delete`
2. Marque "Imagens e arquivos armazenados em cache"
3. Clique em "Limpar agora"

#### Firefox:
1. Pressione `Ctrl + Shift + Delete`
2. Marque "Cache"
3. Clique em "Limpar agora"

### 3. Modo Anônimo/Privado (Para Teste)
Abra uma janela anônima/privada e acesse o sistema para verificar se o link aparece.

---

## O que foi corrigido:

### Correção 1: Link "Serviços" no Sidebar
Todas as páginas HTML agora carregam o sidebar com o link "Serviços" (`sidebar.js?v=20251127-002`).

### Correção 2: Sidebar Fixo Durante o Scroll
O sidebar agora tem `position: fixed` e permanece visível ao scrollar a página.

Páginas atualizadas (`sidebar.js?v=20251127-002`):

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

O link "Serviços" está no sidebar na seção "Manutenção Preventiva", entre "Peças" e a seção "CheckList".
