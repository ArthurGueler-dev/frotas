/**
 * auth.js - Sistema de Autenticação Frontend
 *
 * Incluir este arquivo em TODAS as páginas protegidas (exceto login.html)
 * <script src="auth.js" defer></script>
 *
 * Funcionalidades:
 * - Verifica se usuário está logado (via sessionStorage)
 * - Redireciona para login se não autenticado
 * - Fornece informações do usuário logado
 * - Gerencia logout
 *
 * Integrado com: b_veicular_auth.php (tabela bbb_usuario)
 */

(function() {
    'use strict';

    // Configurações
    const LOGIN_PAGE = 'login.html';

    // Não verificar autenticação na página de login
    if (window.location.pathname.includes('login.html')) {
        return;
    }

    /**
     * Verificar se usuário está autenticado
     * Verifica se existe usuario_id e token na sessionStorage
     */
    async function checkAuth() {
        try {
            // Verificar se tem dados essenciais na sessionStorage
            const usuarioId = sessionStorage.getItem('usuario_id');
            const token = sessionStorage.getItem('token');

            if (!usuarioId || !token) {
                redirectToLogin();
                return false;
            }

            return true;
        } catch (error) {
            console.error('Erro ao verificar autenticação:', error);
            redirectToLogin();
            return false;
        }
    }

    /**
     * Redirecionar para página de login
     */
    function redirectToLogin() {
        // Salvar página atual para redirecionar depois do login
        sessionStorage.setItem('redirect_after_login', window.location.pathname);

        // Limpar dados de sessão
        sessionStorage.removeItem('usuario_id');
        sessionStorage.removeItem('usuario_nome');
        sessionStorage.removeItem('usuario_tipo');
        sessionStorage.removeItem('tutorial_concluido');
        sessionStorage.removeItem('token');

        // Redirecionar
        window.location.href = LOGIN_PAGE;
    }

    /**
     * Obter informações do usuário logado
     */
    function getUser() {
        return {
            id: sessionStorage.getItem('usuario_id'),
            nome: sessionStorage.getItem('usuario_nome'),
            tipo: sessionStorage.getItem('usuario_tipo'),
            tutorialConcluido: sessionStorage.getItem('tutorial_concluido') === '1',
            token: sessionStorage.getItem('token')
        };
    }

    /**
     * Fazer logout
     * Limpa sessionStorage e redireciona para login
     * Nota: b_veicular_auth.php não possui endpoint de logout
     */
    function logout() {
        // Limpar sessão local
        sessionStorage.clear();

        // Redirecionar para login
        window.location.href = LOGIN_PAGE;
    }

    /**
     * Verificar se usuário é admin
     */
    function isAdmin() {
        return sessionStorage.getItem('usuario_tipo') === 'admin';
    }

    /**
     * Atualizar informações do usuário na interface
     */
    function updateUserInterface() {
        const user = getUser();

        // Atualizar nome do usuário em elementos com classe 'user-name'
        document.querySelectorAll('.user-name').forEach(el => {
            el.textContent = user.nome || 'Usuário';
        });

        // Atualizar tipo de usuário em elementos com classe 'user-type'
        document.querySelectorAll('.user-type').forEach(el => {
            el.textContent = user.tipo === 'admin' ? 'Administrador' : 'Usuário';
        });

        // Esconder elementos apenas para admin se não for admin
        if (!isAdmin()) {
            document.querySelectorAll('.admin-only').forEach(el => {
                el.style.display = 'none';
            });
        }
    }

    /**
     * Adicionar botão de logout se existir elemento com id 'logoutButton'
     */
    function setupLogoutButton() {
        const logoutButton = document.getElementById('logoutButton');
        if (logoutButton) {
            logoutButton.addEventListener('click', (e) => {
                e.preventDefault();

                if (confirm('Deseja realmente sair do sistema?')) {
                    logout();
                }
            });
        }

        // Também procurar por elementos com classe 'logout-button'
        document.querySelectorAll('.logout-button').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();

                if (confirm('Deseja realmente sair do sistema?')) {
                    logout();
                }
            });
        });
    }

    // Expor funções globalmente
    window.Auth = {
        checkAuth: checkAuth,
        getUser: getUser,
        logout: logout,
        isAdmin: isAdmin,
        redirectToLogin: redirectToLogin
    };

    // Executar verificação de autenticação quando a página carregar
    document.addEventListener('DOMContentLoaded', async () => {
        const isAuthenticated = await checkAuth();

        if (isAuthenticated) {
            updateUserInterface();
            setupLogoutButton();
        }
    });

    // Também executar imediatamente (caso DOMContentLoaded já tenha disparado)
    if (document.readyState === 'loading') {
        // Ainda carregando, esperar DOMContentLoaded
    } else {
        // DOM já pronto
        checkAuth().then(isAuthenticated => {
            if (isAuthenticated) {
                updateUserInterface();
                setupLogoutButton();
            }
        });
    }
})();
