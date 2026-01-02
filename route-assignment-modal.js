/**
 * Modal de Atribuição de Veículo/Motorista ao Enviar WhatsApp
 *
 * Features:
 * - Autocomplete para veículos (digitar placa)
 * - Autocomplete para motoristas (digitar nome)
 * - Lista completa ao focar, filtra ao digitar
 * - Validação de campos
 */

let pendingRouteId = null;
let pendingBlockId = null;
let vehiclesList = [];
let driversList = [];
let selectedVehicle = null;
let selectedDriver = null;

// Flag para garantir que listeners são adicionados apenas uma vez
let listenersAdded = false;

/**
 * Abrir modal ao clicar "Enviar WhatsApp"
 */
async function abrirModalEnvioWhatsApp(blockId, rotaId) {
    console.log(`📱 Abrindo modal de envio: blockId=${blockId}, rotaId=${rotaId}`);

    pendingRouteId = rotaId;
    pendingBlockId = blockId;

    document.getElementById('pendingBlockId').value = blockId;
    document.getElementById('pendingRouteId').value = rotaId;

    // Reset selections
    selectedVehicle = null;
    selectedDriver = null;

    // Limpar campos
    document.getElementById('routeVehicleInput').value = '';
    document.getElementById('routeDriverInput').value = '';
    document.getElementById('routePhone').value = '';

    // Carregar veículos e motoristas
    await Promise.all([
        carregarVeiculosParaEnvio(),
        carregarMotoristasParaEnvio()
    ]);

    // Mostrar modal
    document.getElementById('assignRouteModal').classList.remove('hidden');

    // Adicionar event listeners (apenas uma vez)
    if (!listenersAdded) {
        setupEventListeners();
        listenersAdded = true;
        console.log('✅ Event listeners adicionados');
    }
}

/**
 * Configurar event listeners para os dropdowns
 */
function setupEventListeners() {
    console.log('🔧 setupEventListeners() chamado');

    // 🐛 DEBUG: Listener global para ver QUALQUER clique
    document.addEventListener('click', function(e) {
        console.log('🌍 CLIQUE GLOBAL:', e.target);
        console.log('   - classes:', e.target.className);
        console.log('   - id:', e.target.id);
        console.log('   - parent:', e.target.parentElement);
    }, true); // true = capture phase (antes de outros listeners)

    // Event delegation para cliques nas sugestões de veículos
    const vehicleSuggestions = document.getElementById('vehicleSuggestions');
    console.log('📋 vehicleSuggestions element:', vehicleSuggestions);

    if (vehicleSuggestions) {
        vehicleSuggestions.addEventListener('click', function(e) {
            console.log('🖱️ CLIQUE DETECTADO no container de veículos!');
            console.log('   - e.target:', e.target);
            console.log('   - e.target.className:', e.target.className);

            const item = e.target.closest('.suggestion-item');
            console.log('   - item encontrado:', item);

            if (item) {
                const plate = item.getAttribute('data-plate');
                const model = item.getAttribute('data-model');

                console.log('🚗 Clicou no veículo:', plate, model);

                // Encontrar veículo na lista global
                selectedVehicle = vehiclesList.find(v => v.plate === plate);

                if (selectedVehicle) {
                    document.getElementById('routeVehicleInput').value = `${plate} - ${model}`;
                    vehicleSuggestions.classList.add('hidden');
                    console.log('✅ Veículo selecionado:', selectedVehicle);
                } else {
                    console.error('❌ Veículo não encontrado na lista:', plate);
                }
            } else {
                console.warn('⚠️ Clique foi no container, mas não em um item válido');
            }
        });
        console.log('✅ Listener de veículos adicionado');
    } else {
        console.error('❌ vehicleSuggestions element NÃO encontrado!');
    }

    // Event delegation para cliques nas sugestões de motoristas
    const driverSuggestions = document.getElementById('driverSuggestions');
    console.log('📋 driverSuggestions element:', driverSuggestions);

    if (driverSuggestions) {
        driverSuggestions.addEventListener('click', function(e) {
            console.log('🖱️ CLIQUE DETECTADO no container de motoristas!');
            console.log('   - e.target:', e.target);
            console.log('   - e.target.className:', e.target.className);

            const item = e.target.closest('.suggestion-item');
            console.log('   - item encontrado:', item);

            if (item) {
                const driverId = parseInt(item.getAttribute('data-driver-id'));
                const driverName = item.getAttribute('data-driver-name');

                console.log('👤 Clicou no motorista:', driverId, driverName);

                // Encontrar motorista na lista global
                selectedDriver = driversList.find(d => d.id == driverId);

                if (selectedDriver) {
                    document.getElementById('routeDriverInput').value = driverName;
                    driverSuggestions.classList.add('hidden');
                    console.log('✅ Motorista selecionado:', selectedDriver);
                } else {
                    console.error('❌ Motorista não encontrado na lista:', driverId);
                }
            } else {
                console.warn('⚠️ Clique foi no container, mas não em um item válido');
            }
        });
        console.log('✅ Listener de motoristas adicionado');
    } else {
        console.error('❌ driverSuggestions element NÃO encontrado!');
    }
}

/**
 * Fechar modal
 */
function closeAssignRouteModal() {
    document.getElementById('assignRouteModal').classList.add('hidden');
    pendingRouteId = null;
    pendingBlockId = null;
    selectedVehicle = null;
    selectedDriver = null;

    // Limpar campos
    document.getElementById('routeVehicleInput').value = '';
    document.getElementById('routeDriverInput').value = '';
    document.getElementById('routePhone').value = '';

    // Esconder dropdowns
    document.getElementById('vehicleSuggestions').classList.add('hidden');
    document.getElementById('driverSuggestions').classList.add('hidden');
}

/**
 * Carregar lista de veículos
 */
async function carregarVeiculosParaEnvio() {
    try {
        const response = await fetch('https://floripa.in9automacao.com.br/get-vehicles.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error('Erro ao carregar veículos');
        }

        vehiclesList = data.data || [];
        console.log(`✅ ${vehiclesList.length} veículos carregados`);

    } catch (error) {
        console.error('❌ Erro ao carregar veículos:', error);
        showNotification('Erro ao carregar veículos', 'error');
    }
}

/**
 * Carregar lista de motoristas
 */
async function carregarMotoristasParaEnvio() {
    try {
        const response = await fetch('https://floripa.in9automacao.com.br/get-drivers.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error('Erro ao carregar motoristas');
        }

        driversList = data.data || [];
        console.log(`✅ ${driversList.length} motoristas carregados`);

    } catch (error) {
        console.error('❌ Erro ao carregar motoristas:', error);
        showNotification('Erro ao carregar motoristas', 'error');
    }
}

/**
 * Exibir lista completa de veículos
 */
function showAllVehicles() {
    console.log('📋 showAllVehicles() chamado');
    const suggestions = document.getElementById('vehicleSuggestions');

    if (vehiclesList.length === 0) {
        suggestions.innerHTML = '<div class="p-2 text-gray-500 text-sm">Nenhum veículo disponível</div>';
        suggestions.classList.remove('hidden');
        console.log('⚠️ Nenhum veículo disponível');
        return;
    }

    renderVehicleSuggestions(vehiclesList);
}

/**
 * Filtrar e exibir sugestões de veículos
 */
function filterVehicles(searchTerm) {
    const suggestions = document.getElementById('vehicleSuggestions');

    if (!searchTerm || searchTerm.trim() === '') {
        // Se campo vazio, mostrar todos
        showAllVehicles();
        return;
    }

    const filtered = vehiclesList.filter(vehicle =>
        vehicle.plate.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (vehicle.model && vehicle.model.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    if (filtered.length === 0) {
        suggestions.innerHTML = '<div class="p-2 text-gray-500 text-sm">Nenhum veículo encontrado</div>';
        suggestions.classList.remove('hidden');
        return;
    }

    renderVehicleSuggestions(filtered);
}

/**
 * Renderizar sugestões de veículos
 */
function renderVehicleSuggestions(vehicles) {
    const suggestions = document.getElementById('vehicleSuggestions');

    console.log('🔄 renderVehicleSuggestions() chamado com', vehicles.length, 'veículos');

    suggestions.innerHTML = vehicles.slice(0, 50).map((vehicle) => `
        <div class="suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer border-b border-gray-200 dark:border-gray-600"
             data-plate="${vehicle.plate.replace(/"/g, '&quot;')}"
             data-model="${(vehicle.model || 'Sem modelo').replace(/"/g, '&quot;')}">
            <div class="font-semibold text-sm text-gray-900 dark:text-white">${vehicle.plate}</div>
            <div class="text-xs text-gray-600 dark:text-gray-400">${vehicle.model || 'Sem modelo'}</div>
        </div>
    `).join('');

    suggestions.classList.remove('hidden');
    console.log('📋 Lista de veículos EXIBIDA (hidden removido)');
    console.log('   - Element:', suggestions);
    console.log('   - Classes:', suggestions.className);
    console.log('   - Display:', window.getComputedStyle(suggestions).display);
    console.log('   - Visibility:', window.getComputedStyle(suggestions).visibility);
}

/**
 * Selecionar veículo
 */
function selectVehicle(plate, model) {
    selectedVehicle = vehiclesList.find(v => v.plate === plate);

    if (!selectedVehicle) {
        console.error('❌ Veículo não encontrado:', plate);
        return;
    }

    document.getElementById('routeVehicleInput').value = `${plate} - ${model}`;
    document.getElementById('vehicleSuggestions').classList.add('hidden');

    console.log('✅ Veículo selecionado:', selectedVehicle);
}

/**
 * Exibir lista completa de motoristas
 */
function showAllDrivers() {
    const suggestions = document.getElementById('driverSuggestions');

    if (driversList.length === 0) {
        suggestions.innerHTML = '<div class="p-2 text-gray-500 text-sm">Nenhum motorista disponível</div>';
        suggestions.classList.remove('hidden');
        return;
    }

    renderDriverSuggestions(driversList);
}

/**
 * Filtrar e exibir sugestões de motoristas
 */
function filterDrivers(searchTerm) {
    const suggestions = document.getElementById('driverSuggestions');

    if (!searchTerm || searchTerm.trim() === '') {
        // Se campo vazio, mostrar todos
        showAllDrivers();
        return;
    }

    const filtered = driversList.filter(driver =>
        driver.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        driver.id.toString().includes(searchTerm)
    );

    if (filtered.length === 0) {
        suggestions.innerHTML = '<div class="p-2 text-gray-500 text-sm">Nenhum motorista encontrado</div>';
        suggestions.classList.remove('hidden');
        return;
    }

    renderDriverSuggestions(filtered);
}

/**
 * Renderizar sugestões de motoristas
 */
function renderDriverSuggestions(drivers) {
    const suggestions = document.getElementById('driverSuggestions');

    suggestions.innerHTML = drivers.slice(0, 50).map((driver) => `
        <div class="suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer border-b border-gray-200 dark:border-gray-600"
             data-driver-id="${driver.id}"
             data-driver-name="${driver.name.replace(/"/g, '&quot;')}">
            <div class="font-semibold text-sm text-gray-900 dark:text-white">${driver.name}</div>
            <div class="text-xs text-gray-600 dark:text-gray-400">ID: ${driver.id}</div>
        </div>
    `).join('');

    suggestions.classList.remove('hidden');
}

/**
 * Selecionar motorista
 */
function selectDriver(driverId, driverName) {
    selectedDriver = driversList.find(d => d.id == driverId);

    if (!selectedDriver) {
        console.error('❌ Motorista não encontrado:', driverId);
        return;
    }

    document.getElementById('routeDriverInput').value = driverName;
    document.getElementById('driverSuggestions').classList.add('hidden');

    console.log('✅ Motorista selecionado:', selectedDriver);
}

/**
 * Configurar event listeners ao carregar página
 */
document.addEventListener('DOMContentLoaded', function() {
    // Input de veículo
    const vehicleInput = document.getElementById('routeVehicleInput');
    if (vehicleInput) {
        // Mostrar lista completa ao focar
        vehicleInput.addEventListener('focus', function() {
            if (vehiclesList.length > 0) {
                const currentValue = this.value.trim();
                if (currentValue === '') {
                    showAllVehicles();
                } else {
                    filterVehicles(currentValue);
                }
            }
        });

        // Filtrar ao digitar
        vehicleInput.addEventListener('input', function(e) {
            selectedVehicle = null; // Reset selection quando digitar
            filterVehicles(e.target.value);
        });

        // Esconder sugestões ao clicar fora
        vehicleInput.addEventListener('blur', function() {
            console.log('⏱️ Input BLUR - vai esconder lista em 500ms');
            setTimeout(() => {
                console.log('🙈 Escondendo lista de veículos');
                document.getElementById('vehicleSuggestions').classList.add('hidden');
            }, 500);
        });
    }

    // Input de motorista
    const driverInput = document.getElementById('routeDriverInput');
    if (driverInput) {
        // Mostrar lista completa ao focar
        driverInput.addEventListener('focus', function() {
            if (driversList.length > 0) {
                const currentValue = this.value.trim();
                if (currentValue === '') {
                    showAllDrivers();
                } else {
                    filterDrivers(currentValue);
                }
            }
        });

        // Filtrar ao digitar
        driverInput.addEventListener('input', function(e) {
            selectedDriver = null; // Reset selection quando digitar
            filterDrivers(e.target.value);
        });

        // Esconder sugestões ao clicar fora
        driverInput.addEventListener('blur', function() {
            console.log('⏱️ Input BLUR (motorista) - vai esconder lista em 500ms');
            setTimeout(() => {
                console.log('🙈 Escondendo lista de motoristas');
                document.getElementById('driverSuggestions').classList.add('hidden');
            }, 500);
        });
    }

    // ⚠️ LISTENERS DE CLICK movidos para setupEventListeners()
    // (chamado quando modal abre, para garantir que elementos existem)

    // Form submit
    const form = document.getElementById('assignRouteForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
});

/**
 * Handle submit do formulário
 */
async function handleFormSubmit(e) {
    e.preventDefault();

    console.log('🔍 Validando seleções:', {
        selectedVehicle,
        selectedDriver
    });

    // Validar seleções
    if (!selectedVehicle) {
        showNotification('⚠️ Por favor, selecione um veículo válido da lista', 'error');
        document.getElementById('routeVehicleInput').focus();
        return;
    }

    if (!selectedDriver) {
        showNotification('⚠️ Por favor, selecione um motorista válido da lista', 'error');
        document.getElementById('routeDriverInput').focus();
        return;
    }

    const phone = document.getElementById('routePhone').value.replace(/\D/g, '');

    if (!phone || phone.length < 12) {
        showNotification('⚠️ Digite um telefone válido (ex: 5527999999999)', 'error');
        document.getElementById('routePhone').focus();
        return;
    }

    // ⚠️ IMPORTANTE: Salvar valores ANTES de fechar o modal (que reseta as variáveis)
    const routeData = {
        rota_id: pendingRouteId,
        veiculo_placa: selectedVehicle.plate,
        motorista_id: selectedDriver.id,
        motorista_nome: selectedDriver.name,
        telefone: phone
    };

    // Fechar modal
    closeAssignRouteModal();

    try {
        showLoading('Atualizando rota e enviando WhatsApp...');

        console.log('📝 Atualizando rota com:', routeData);

        // 1. Atualizar rota com placa, motorista e status
        const updateResponse = await fetch('https://floripa.in9automacao.com.br/update-route-assignment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                rota_id: routeData.rota_id,
                veiculo_placa: routeData.veiculo_placa,
                motorista_id: routeData.motorista_id
            })
        });

        const updateData = await updateResponse.json();

        if (!updateData.success) {
            throw new Error(updateData.error || 'Erro ao atualizar rota');
        }

        console.log('✅ Rota atualizada com sucesso:', updateData);

        // 2. Enviar WhatsApp (via VPS Node.js que tem acesso à Evolution API)
        console.log(`📤 Enviando WhatsApp para: ${routeData.telefone}`);

        const whatsappResponse = await fetch('/enviar-rota-whatsapp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                rota_id: routeData.rota_id,
                telefone: routeData.telefone
            })
        });

        const whatsappData = await whatsappResponse.json();

        if (!whatsappData.success) {
            throw new Error(whatsappData.error || 'Erro ao enviar WhatsApp');
        }

        console.log('✅ WhatsApp enviado com sucesso');

        showNotification(
            `✅ Rota atribuída a ${routeData.veiculo_placa} e enviada para ${routeData.motorista_nome}! Monitoramento iniciado.`,
            'success'
        );

        // Recarregar blocos para atualizar UI
        if (typeof loadBlocks === 'function') {
            await loadBlocks();
        }

    } catch (error) {
        console.error('❌ Erro:', error);
        showNotification(`Erro: ${error.message}`, 'error');
    } finally {
        hideLoading();
    }
}

// Exportar funções globalmente
window.abrirModalEnvioWhatsApp = abrirModalEnvioWhatsApp;
window.closeAssignRouteModal = closeAssignRouteModal;
window.selectVehicle = selectVehicle;
window.selectDriver = selectDriver;
