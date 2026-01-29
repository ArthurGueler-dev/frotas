// sidebar.js - Componente de Sidebar Unificado
// Este script injeta o sidebar completo em todas as páginas

function createSidebar(activePage = '') {
    return `
    <aside class="w-64 bg-white dark:bg-gray-800 flex flex-col border-r border-gray-200 dark:border-gray-700 h-screen fixed top-0 left-0 z-40" style="position: fixed !important; top: 0 !important; left: 0 !important; height: 100vh !important; z-index: 40 !important;">
        <!-- Logo/Header -->
        <div class="p-6 flex items-center gap-3 flex-shrink-0">
            <span class="material-symbols-outlined text-primary text-4xl">directions_car</span>
            <div class="flex flex-col">
                <h1 class="text-gray-800 dark:text-white text-xl font-bold leading-normal">FleetFlow</h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-normal leading-normal">Gestão Inteligente</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-2 overflow-y-auto min-h-0">
            <!-- Menu Principal -->
            <a class="flex items-center gap-3 px-4 py-2 rounded-lg ${activePage === 'dashboard' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="dashboard.html">
                <span class="material-symbols-outlined">dashboard</span>
                <p class="text-sm">Dashboard</p>
            </a>

            <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'veiculos' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="veiculos.html">
                <span class="material-symbols-outlined">directions_car</span>
                <p class="text-sm">Veículos</p>
            </a>

            <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'motoristas' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="motoristas.html">
                <span class="material-symbols-outlined">groups</span>
                <p class="text-sm">Motoristas</p>
            </a>

            <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'manutencao' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="manutencao.html">
                <span class="material-symbols-outlined">build</span>
                <p class="text-sm">Manutenção</p>
            </a>

            <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'lancar-os' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="lancar-os.html">
                <span class="material-symbols-outlined">add_box</span>
                <p class="text-sm">Lançar OS</p>
            </a>

            <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'otimizador-blocos' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="otimizador-blocos.html">
                <span class="material-symbols-outlined">route</span>
                <p class="text-sm">Rotas</p>
            </a>

            <!-- Seção Manutenção Preventiva -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Manutenção Preventiva</p>

                <a class="flex items-center gap-3 px-4 py-2 rounded-lg ${activePage === 'alertas' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="dashboard-manutencoes.html">
                    <span class="material-symbols-outlined">notification_important</span>
                    <p class="text-sm">Alertas</p>
                    <span id="badge-alertas-sidebar" class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full hidden">0</span>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'modelos' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="modelos.html">
                    <span class="material-symbols-outlined">directions_car</span>
                    <p class="text-sm">Modelos</p>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'planos' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="planos-manutencao-novo.html">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <p class="text-sm">Planos</p>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'pecas' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="pecas.html">
                    <span class="material-symbols-outlined">settings_input_component</span>
                    <p class="text-sm">Peças</p>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'servicos' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="servicos.html">
                    <span class="material-symbols-outlined">construction</span>
                    <p class="text-sm">Serviços</p>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'pecas-compatibilidade' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="pecas-compatibilidade.html">
                    <span class="material-symbols-outlined">settings</span>
                    <p class="text-sm">Peças Compatíveis</p>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'fornecedores' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="fornecedores.html">
                    <span class="material-symbols-outlined">store</span>
                    <p class="text-sm">Fornecedores</p>
                </a>
            </div>

            <!-- Seção CheckList -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">CheckList</p>

                <a class="flex items-center gap-3 px-4 py-2 rounded-lg ${activePage === 'dashboard-diario' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="dashboard-diario.html">
                    <span class="material-symbols-outlined">today</span>
                    <p class="text-sm">Dashboard Diário</p>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'checklist' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="admin-checklist.html">
                    <span class="material-symbols-outlined">checklist</span>
                    <p class="text-sm">CheckList</p>
                </a>
            </div>

            <!-- Seção Administração -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 admin-section">
                <p class="px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Administração</p>

                <a class="flex items-center gap-3 px-4 py-2 rounded-lg ${activePage === 'usuarios' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="usuarios.html">
                    <span class="material-symbols-outlined">manage_accounts</span>
                    <p class="text-sm">Usuários</p>
                </a>
            </div>

            <!-- Seção Monitoramento -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="px-4 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Monitoramento</p>

                <a class="flex items-center gap-3 px-4 py-2 rounded-lg ${activePage === 'compliance-monitor' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="compliance-monitor.html">
                    <span class="material-symbols-outlined">monitor_heart</span>
                    <p class="text-sm">Conformidade</p>
                    <span class="ml-auto bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full">NOVO</span>
                </a>

                <a class="flex items-center gap-3 px-4 py-2 mt-2 rounded-lg ${activePage === 'alert-recipients' ? 'bg-primary/10 dark:bg-primary/20 text-primary font-bold' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium'}" href="alert-recipients.html">
                    <span class="material-symbols-outlined">group</span>
                    <p class="text-sm">Destinatários</p>
                </a>
            </div>
        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex items-center justify-center bg-primary/20 text-primary rounded-full size-10">
                    <span class="material-symbols-outlined">person</span>
                </div>
                <div class="flex flex-col flex-1 min-w-0">
                    <h1 class="text-gray-800 dark:text-white text-sm font-medium leading-normal truncate user-name">Usuário</h1>
                    <p class="text-gray-500 dark:text-gray-400 text-xs font-normal leading-normal truncate user-type">Carregando...</p>
                </div>
            </div>
            <button id="logoutButton" class="logout-button flex w-full cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-10 px-4 bg-red-500/10 text-red-500 text-sm font-bold leading-normal tracking-[0.015em] hover:bg-red-500/20 transition-colors">
                <span class="material-symbols-outlined">logout</span>
                <span class="truncate">Sair</span>
            </button>
        </div>
    </aside>
    `;
}

// Função para carregar o badge de alertas
async function carregarBadgeAlertas() {
    try {
        const response = await fetch('https://floripa.in9automacao.com.br/avisos-manutencao-api.php?limit=1');
        const result = await response.json();
        const badge = document.getElementById('badge-alertas-sidebar');

        if (badge && result.success && result.data.stats) {
            // Mostrar apenas alertas críticos e vencidos no badge
            const vencidas = result.data.stats.vencidas || 0;
            const urgentes = result.data.stats.urgentes || 0;
            const totalCriticos = vencidas + urgentes;

            if (totalCriticos > 0) {
                badge.textContent = totalCriticos > 99 ? '99+' : totalCriticos;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    } catch (error) {
        console.error('Erro ao carregar alertas:', error);
        // Silenciosamente falha - não é crítico
    }
}

// Auto-inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Encontrar o container do sidebar
    const sidebarContainer = document.getElementById('sidebar-container');

    if (sidebarContainer) {
        // Obter a página ativa do atributo data-page
        const activePage = sidebarContainer.getAttribute('data-page') || '';

        // Injetar o sidebar
        sidebarContainer.innerHTML = createSidebar(activePage);

        // Adicionar margem ao conteúdo principal para compensar o sidebar fixo
        const mainContent = document.querySelector('main');
        if (mainContent && !mainContent.classList.contains('ml-64')) {
            mainContent.style.marginLeft = '256px';
        }

        // Carregar badge de alertas
        carregarBadgeAlertas();
    }
});

// Exportar função para uso manual se necessário
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { createSidebar, carregarBadgeAlertas };
}
