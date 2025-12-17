// Gerenciador de Itens da Ordem de Serviço
// Controla a adição/remoção de serviços e produtos

class OSItemsManager {
    constructor() {
        this.servicesData = null;
        this.items = [];
        this.init();
    }

    async init() {
        await this.loadServicesData();
        this.renderInitialRow();
    }

    async loadServicesData() {
        try {
            // Carregar dados do JSON estático
            const response = await fetch('services-data.json');
            this.servicesData = await response.json();

            // Carregar itens do banco de dados via API
            try {
                const apiResponse = await fetch('https://floripa.in9automacao.com.br/api-servicos.php');
                const apiData = await apiResponse.json();

                if (apiData.success && apiData.data) {
                    // Converter formato do banco para formato do sistema
                    const dbServices = apiData.data
                        .filter(item => item.tipo === 'Serviço' && item.ativo == 1)
                        .map(item => ({
                            id: `db-srv-${item.id}`,
                            name: item.nome,
                            category: 'geral', // categoria padrão
                            defaultPrice: parseFloat(item.valor_padrao)
                        }));

                    const dbProducts = apiData.data
                        .filter(item => item.tipo === 'Produto' && item.ativo == 1)
                        .map(item => ({
                            id: `db-prd-${item.id}`,
                            name: item.nome,
                            category: 'geral', // categoria padrão
                            defaultPrice: parseFloat(item.valor_padrao)
                        }));

                    // Adicionar itens do banco aos arrays
                    this.servicesData.services = [...this.servicesData.services, ...dbServices];
                    this.servicesData.products = [...this.servicesData.products, ...dbProducts];

                    console.log(`✅ ${dbServices.length} serviços do banco carregados`);
                    console.log(`✅ ${dbProducts.length} produtos do banco carregados`);
                }
            } catch (apiError) {
                console.warn('⚠️ Não foi possível carregar itens do banco:', apiError);
            }

            console.log('✅ Dados de serviços e produtos carregados');
            console.log('📊 Total de serviços:', this.servicesData.services.length);
            console.log('📊 Total de produtos:', this.servicesData.products.length);
        } catch (error) {
            console.error('❌ Erro ao carregar dados:', error);
        }
    }

    renderInitialRow() {
        const tbody = document.getElementById('items-tbody');
        if (!tbody) return;

        tbody.innerHTML = this.createNewRowHTML();
        this.attachEventListeners();
    }

    createNewRowHTML() {
        return `
            <tr class="bg-white dark:bg-gray-900/50 border-b dark:border-gray-700 item-row">
                <td class="px-2 py-3">
                    <select class="form-select w-full bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-type" onchange="osManager.onTypeChange(this)">
                        <option value="">Selecione...</option>
                        <option value="service">Serviço</option>
                        <option value="product">Produto</option>
                    </select>
                </td>
                <td class="px-2 py-3">
                    <div class="relative">
                        <input type="text"
                               class="form-input w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded h-10 text-xs item-description focus:ring-2 focus:ring-primary/50 focus:outline-none"
                               placeholder="Selecione o tipo primeiro..."
                               disabled
                               autocomplete="off"
                               style="padding-right: 30px;"/>
                        <button type="button" class="absolute right-1 top-1/2 -translate-y-1/2 text-primary item-dropdown-btn" style="pointer-events: auto;">
                            <span class="material-symbols-outlined text-lg">arrow_drop_down</span>
                        </button>
                        <div class="absolute w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-60 overflow-y-auto hidden item-dropdown" style="z-index: 999;">
                            <!-- Opções aparecerão aqui -->
                        </div>
                    </div>
                </td>
                <td class="px-2 py-3">
                    <select class="form-select w-full bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-category" disabled>
                        <option value="">Aguardando...</option>
                    </select>
                </td>
                <td class="px-2 py-3">
                    <input type="number"
                           class="form-input w-16 text-center bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-qty"
                           value="1"
                           min="1"
                           onchange="osManager.calculateRowTotal(this)"/>
                </td>
                <td class="px-2 py-3">
                    <input type="text"
                           class="form-input w-24 text-right bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-value"
                           placeholder="0,00"
                           onchange="osManager.calculateRowTotal(this)"
                           onblur="osManager.formatCurrency(this)"/>
                </td>
                <td class="px-2 py-3 text-right font-medium text-xs item-total">R$ 0,00</td>
                <td class="px-2 py-3">
                    <select class="form-select w-full bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-occurrence">
                        <option value="">Selecione...</option>
                        <option value="preventivo">Preventivo</option>
                        <option value="corretivo">Corretivo</option>
                    </select>
                </td>
                <td class="px-2 py-3 text-center">
                    <button type="button" class="text-red-500 hover:text-red-700" onclick="osManager.removeRow(this)">
                        <span class="material-symbols-outlined text-base">delete</span>
                    </button>
                </td>
            </tr>
        `;
    }

    onTypeChange(selectElement) {
        console.log('🔵 onTypeChange chamado');

        const row = selectElement.closest('tr');
        const type = selectElement.value;
        const descriptionInput = row.querySelector('.item-description');
        const categorySelect = row.querySelector('.item-category');
        const dropdown = row.querySelector('.item-dropdown');

        console.log('🔵 Tipo selecionado:', type);
        console.log('🔵 servicesData disponível:', !!this.servicesData);

        if (!type) {
            descriptionInput.disabled = true;
            descriptionInput.placeholder = 'Selecione o tipo primeiro...';
            categorySelect.disabled = true;
            categorySelect.innerHTML = '<option value="">Aguardando...</option>';
            return;
        }

        // Habilita campo de descrição
        descriptionInput.disabled = false;
        descriptionInput.placeholder = `Digite para buscar ${type === 'service' ? 'serviço' : 'produto'}...`;
        descriptionInput.value = '';

        console.log('🔵 Campo de descrição habilitado');

        // Habilita campo de categoria
        categorySelect.disabled = false;
        this.populateCategories(categorySelect);

        console.log('🔵 Categorias populadas');

        // Configura autocomplete
        this.setupAutocomplete(row, type);

        console.log('🔵 Autocomplete configurado');
    }

    populateCategories(selectElement) {
        if (!this.servicesData) return;

        selectElement.innerHTML = '<option value="">Todos</option>';
        this.servicesData.categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = this.formatCategoryName(cat);
            selectElement.appendChild(option);
        });
    }

    formatCategoryName(category) {
        const names = {
            'pneus': 'Pneus',
            'munck': 'Munck',
            'carroceria': 'Carroceria',
            'motor': 'Motor',
            'caixa': 'Caixa',
            'limpeza': 'Limpeza',
            'cambio': 'Câmbio',
            'suspensao': 'Suspensão',
            'eletrica': 'Elétrica',
            'freio': 'Freio'
        };
        return names[category] || category;
    }

    setupAutocomplete(row, type) {
        console.log('🟦 setupAutocomplete iniciado para tipo:', type);

        const input = row.querySelector('.item-description');
        const dropdown = row.querySelector('.item-dropdown');
        const categorySelect = row.querySelector('.item-category');
        const dropdownBtn = row.querySelector('.item-dropdown-btn');

        console.log('🟦 Elementos encontrados:', {
            input: !!input,
            dropdown: !!dropdown,
            categorySelect: !!categorySelect,
            dropdownBtn: !!dropdownBtn
        });

        // Remover event listeners antigos para evitar duplicação
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);

        const newDropdownBtn = dropdownBtn ? dropdownBtn.cloneNode(true) : null;
        if (dropdownBtn && newDropdownBtn) {
            dropdownBtn.parentNode.replaceChild(newDropdownBtn, dropdownBtn);
        }

        // Atualizar referências
        const finalInput = row.querySelector('.item-description');
        const finalDropdown = row.querySelector('.item-dropdown');
        const finalDropdownBtn = row.querySelector('.item-dropdown-btn');

        console.log('🟦 Event listeners limpos e prontos para reconfiguração');

        // Evento de digitação - sempre mostra dropdown ao digitar
        finalInput.addEventListener('input', () => {
            console.log('🟦 Input event disparado');
            const searchTerm = finalInput.value.trim().toLowerCase();
            const selectedCategory = categorySelect.value;

            if (!this.servicesData) {
                console.error('❌ servicesData está undefined!');
                return;
            }

            let items = type === 'service' ? this.servicesData.services : this.servicesData.products;
            console.log(`🟦 Total de ${type === 'service' ? 'serviços' : 'produtos'}:`, items.length);

            // Filtrar por categoria se selecionada
            if (selectedCategory) {
                items = items.filter(item => item.category === selectedCategory);
            }

            // Filtrar por termo de busca
            if (searchTerm) {
                items = items.filter(item =>
                    item.name.toLowerCase().includes(searchTerm)
                );
            }

            this.renderDropdown(finalDropdown, items, finalInput, row);
        });

        // Evento de clique no botão dropdown
        if (finalDropdownBtn) {
            finalDropdownBtn.addEventListener('click', (e) => {
                console.log('🟦 Botão dropdown clicado');
                e.preventDefault();
                e.stopPropagation();

                const selectedCategory = categorySelect.value;
                let items = type === 'service' ? this.servicesData.services : this.servicesData.products;

                console.log(`🟦 Items disponíveis para dropdown: ${items.length}`);

                if (selectedCategory) {
                    items = items.filter(item => item.category === selectedCategory);
                }

                // Toggle dropdown
                if (finalDropdown.classList.contains('hidden')) {
                    this.renderDropdown(finalDropdown, items, finalInput, row);
                } else {
                    finalDropdown.classList.add('hidden');
                }
            });
        }

        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!row.contains(e.target)) {
                finalDropdown.classList.add('hidden');
            }
        });

        // Evento de mudança de categoria
        categorySelect.addEventListener('change', () => {
            if (finalInput.value) {
                finalInput.dispatchEvent(new Event('input'));
            }
        });
    }

    renderDropdown(dropdown, items, input, row) {
        if (items.length === 0) {
            dropdown.innerHTML = `
                <div class="p-4 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Nenhum item encontrado</p>
                    <button type="button" class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary/90" onclick="osManager.showAddNewModal('${input.closest('tr').querySelector('.item-type').value}')">
                        + Cadastrar novo item
                    </button>
                </div>
            `;
            dropdown.classList.remove('hidden');
            return;
        }

        dropdown.innerHTML = items.map(item => `
            <div class="item-option px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                 data-id="${item.id}"
                 data-name="${item.name}"
                 data-price="${item.defaultPrice}">
                <div class="font-semibold text-primary dark:text-blue-400">${item.name}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ${this.formatCategoryName(item.category)} • R$ ${item.defaultPrice.toFixed(2).replace('.', ',')}
                </div>
            </div>
        `).join('') + `
            <div class="border-t border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-800/50">
                <button type="button" class="text-primary hover:text-primary/80 text-sm font-medium w-full text-center" onclick="osManager.showAddNewModal('${input.closest('tr').querySelector('.item-type').value}')">
                    + Cadastrar novo item
                </button>
            </div>
        `;

        // Adicionar event listeners
        dropdown.querySelectorAll('.item-option').forEach(option => {
            option.addEventListener('click', () => {
                const name = option.dataset.name;
                const price = parseFloat(option.dataset.price);

                input.value = name;
                row.querySelector('.item-value').value = price.toFixed(2).replace('.', ',');
                this.calculateRowTotal(row.querySelector('.item-value'));

                dropdown.classList.add('hidden');
            });
        });

        dropdown.classList.remove('hidden');
    }

    showAddNewModal(type) {
        console.log('🔵 showAddNewModal chamado com tipo:', type);

        // Fechar todos os dropdowns abertos antes de abrir o modal
        const allDropdowns = document.querySelectorAll('.item-dropdown');
        allDropdowns.forEach(dropdown => {
            dropdown.classList.add('hidden');
        });

        const modal = document.getElementById('new-item-modal');
        const typeLabel = document.getElementById('modal-type-label');
        const categorySelect = document.getElementById('modal-item-category');
        const form = document.getElementById('new-item-form');

        console.log('🔵 Elementos encontrados:', { modal: !!modal, form: !!form });

        if (!modal) {
            console.error('❌ Modal não encontrado');
            return;
        }

        if (!form) {
            console.error('❌ Formulário não encontrado');
            return;
        }

        // Definir tipo do item
        const typeName = type === 'service' ? 'Serviço' : 'Produto';
        typeLabel.textContent = typeName;
        modal.dataset.itemType = type;

        // Preencher categorias
        categorySelect.innerHTML = '<option value="">Selecione...</option>';
        this.servicesData.categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat;
            option.textContent = this.formatCategoryName(cat);
            categorySelect.appendChild(option);
        });

        // Limpar campos
        document.getElementById('modal-item-name').value = '';
        document.getElementById('modal-item-category').value = '';
        document.getElementById('modal-item-price').value = '';
        document.getElementById('modal-item-occurrence').value = '';

        // Configurar evento de submit apenas se ainda não foi configurado
        if (!form.dataset.listenerConfigured) {
            form.dataset.listenerConfigured = 'true';

            let isSubmitting = false; // Flag para prevenir submits duplos

            form.addEventListener('submit', async (e) => {
                console.log('🟢 Form submit disparado!');
                e.preventDefault();
                e.stopPropagation();

                // Prevenir submits duplos
                if (isSubmitting) {
                    console.warn('⚠️ Submit já em andamento, ignorando...');
                    return;
                }

                isSubmitting = true;

                // Toda a lógica inline para evitar problemas com 'this'
                const type = modal.dataset.itemType;
                const name = document.getElementById('modal-item-name').value.trim();
                const category = document.getElementById('modal-item-category').value;
                const priceStr = document.getElementById('modal-item-price').value.trim();
                const occurrence = document.getElementById('modal-item-occurrence').value;

                console.log('🟡 Dados coletados:', { type, name, category, priceStr, occurrence });

                // Validações
                if (!name) {
                    console.warn('⚠️ Nome não informado');
                    alert('Por favor, informe o nome do item!');
                    return;
                }

                if (!category) {
                    console.warn('⚠️ Categoria não selecionada');
                    alert('Por favor, selecione uma categoria!');
                    return;
                }

                if (!priceStr) {
                    console.warn('⚠️ Preço não informado');
                    alert('Por favor, informe o preço padrão!');
                    return;
                }

                if (!occurrence) {
                    console.warn('⚠️ Ocorrência não selecionada');
                    alert('Por favor, selecione o tipo de ocorrência!');
                    return;
                }

                // Converter preço
                const price = parseFloat(priceStr.replace(',', '.'));
                if (isNaN(price) || price <= 0) {
                    console.warn('⚠️ Preço inválido:', price);
                    alert('Por favor, informe um preço válido!');
                    return;
                }

                const requestData = {
                    codigo: `${type === 'service' ? 'SRV' : 'PRD'}${Date.now()}`,
                    nome: name,
                    tipo: type === 'service' ? 'Serviço' : 'Produto',
                    valor_padrao: price,
                    ocorrencia_padrao: occurrence,
                    ativo: 1
                };

                console.log('🟡 Dados a serem enviados:', requestData);

                try {
                    console.log('🟡 Fazendo requisição POST para API...');

                    // Salvar no banco de dados via API
                    const response = await fetch('https://floripa.in9automacao.com.br/api-servicos.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(requestData)
                    });

                    console.log('🟡 Resposta recebida, status:', response.status);

                    const result = await response.json();
                    console.log('🟡 Resposta JSON:', result);

                    if (result.success) {
                        console.log('✅ Item cadastrado no banco com sucesso! ID:', result.id);

                        // Salvar no localStorage para cache
                        const storageKey = 'fleetflow_custom_items';
                        const customItems = JSON.parse(localStorage.getItem(storageKey) || '{"services": [], "products": []}');

                        const newItem = {
                            id: result.id || `${type === 'service' ? 'srv' : 'prd'}${Date.now()}`,
                            name,
                            category,
                            defaultPrice: price
                        };

                        if (type === 'service') {
                            customItems.services.push(newItem);
                        } else {
                            customItems.products.push(newItem);
                        }

                        localStorage.setItem(storageKey, JSON.stringify(customItems));

                        // Fechar modal
                        modal.classList.add('hidden');
                        document.getElementById('new-item-form').reset();

                        // Mostrar mensagem de sucesso
                        if (typeof showToast === 'function') {
                            showToast('success', 'Sucesso', `${type === 'service' ? 'Serviço' : 'Produto'} "${name}" cadastrado com sucesso!`);
                        } else {
                            alert(`${type === 'service' ? 'Serviço' : 'Produto'} "${name}" cadastrado com sucesso!`);
                        }

                        // Liberar flag
                        isSubmitting = false;
                    } else {
                        isSubmitting = false;
                        throw new Error(result.error || 'Erro ao salvar no banco de dados');
                    }
                } catch (error) {
                    console.error('❌ Erro ao salvar item:', error);

                    // Salvar localmente como fallback
                    const storageKey = 'fleetflow_custom_items';
                    const customItems = JSON.parse(localStorage.getItem(storageKey) || '{"services": [], "products": []}');

                    const newItem = {
                        id: `${type === 'service' ? 'srv' : 'prd'}${Date.now()}`,
                        name,
                        category,
                        defaultPrice: price
                    };

                    if (type === 'service') {
                        customItems.services.push(newItem);
                    } else {
                        customItems.products.push(newItem);
                    }

                    localStorage.setItem(storageKey, JSON.stringify(customItems));

                    // Fechar modal
                    modal.classList.add('hidden');
                    document.getElementById('new-item-form').reset();

                    if (typeof showToast === 'function') {
                        showToast('warning', 'Aviso', `Item salvo apenas localmente. Erro ao salvar no banco: ${error.message}`);
                    } else {
                        alert(`⚠️ Item salvo apenas localmente. Erro: ${error.message}`);
                    }

                    // Liberar flag
                    isSubmitting = false;
                }
            });

            console.log('🔵 Evento de submit configurado pela primeira vez');
        } else {
            console.log('🔵 Evento de submit já estava configurado');
        }

        // Mostrar modal
        modal.classList.remove('hidden');
    }

    closeAddNewModal() {
        const modal = document.getElementById('new-item-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    async saveNewItem() {
        console.log('🟡 saveNewItem iniciado');

        const modal = document.getElementById('new-item-modal');
        const type = modal.dataset.itemType;
        const name = document.getElementById('modal-item-name').value.trim();
        const category = document.getElementById('modal-item-category').value;
        const priceStr = document.getElementById('modal-item-price').value.trim();
        const occurrence = document.getElementById('modal-item-occurrence').value;

        console.log('🟡 Dados coletados:', { type, name, category, priceStr, occurrence });

        // Validações
        if (!name) {
            console.warn('⚠️ Nome não informado');
            alert('Por favor, informe o nome do item!');
            return;
        }

        if (!category) {
            console.warn('⚠️ Categoria não selecionada');
            alert('Por favor, selecione uma categoria!');
            return;
        }

        if (!priceStr) {
            console.warn('⚠️ Preço não informado');
            alert('Por favor, informe o preço padrão!');
            return;
        }

        if (!occurrence) {
            console.warn('⚠️ Ocorrência não selecionada');
            alert('Por favor, selecione o tipo de ocorrência!');
            return;
        }

        // Converter preço
        const price = parseFloat(priceStr.replace(',', '.'));
        if (isNaN(price) || price <= 0) {
            console.warn('⚠️ Preço inválido:', price);
            alert('Por favor, informe um preço válido!');
            return;
        }

        const requestData = {
            codigo: `${type === 'service' ? 'SRV' : 'PRD'}${Date.now()}`,
            nome: name,
            tipo: type === 'service' ? 'Serviço' : 'Produto',
            valor_padrao: price,
            ocorrencia_padrao: occurrence,
            ativo: 1
        };

        console.log('🟡 Dados a serem enviados:', requestData);

        try {
            console.log('🟡 Fazendo requisição POST para API...');

            // Salvar no banco de dados via API
            const response = await fetch('https://floripa.in9automacao.com.br/api-servicos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            });

            console.log('🟡 Resposta recebida, status:', response.status);

            const result = await response.json();

            if (result.success) {
                // Criar novo item para adicionar localmente
                const newItem = {
                    id: result.id || `${type === 'service' ? 'srv' : 'prd'}${Date.now()}`,
                    name,
                    category,
                    defaultPrice: price
                };

                // Adicionar ao array apropriado
                if (type === 'service') {
                    this.servicesData.services.push(newItem);
                } else {
                    this.servicesData.products.push(newItem);
                }

                // Salvar no localStorage (como cache temporário)
                const storageKey = 'fleetflow_custom_items';
                const customItems = JSON.parse(localStorage.getItem(storageKey) || '{"services": [], "products": []}');

                if (type === 'service') {
                    customItems.services.push(newItem);
                } else {
                    customItems.products.push(newItem);
                }

                localStorage.setItem(storageKey, JSON.stringify(customItems));

                console.log('✅ Novo item cadastrado no banco e localmente:', newItem);

                // Fechar modal
                this.closeAddNewModal();

                // Auto-selecionar o item criado na linha atual
                const activeRow = document.querySelector('.item-row:has(.item-type:focus), .item-row:has(.item-description:focus)');
                if (activeRow) {
                    const descriptionInput = activeRow.querySelector('.item-description');
                    const valueInput = activeRow.querySelector('.item-value');

                    descriptionInput.value = name;
                    valueInput.value = price.toFixed(2).replace('.', ',');
                    this.calculateRowTotal(valueInput);
                }

                // Mostrar mensagem de sucesso
                if (typeof showToast === 'function') {
                    showToast('success', 'Sucesso', `${type === 'service' ? 'Serviço' : 'Produto'} "${name}" cadastrado com sucesso!`);
                } else {
                    alert(`${type === 'service' ? 'Serviço' : 'Produto'} "${name}" cadastrado com sucesso!`);
                }
            } else {
                throw new Error(result.error || 'Erro ao salvar no banco de dados');
            }
        } catch (error) {
            console.error('❌ Erro ao salvar item:', error);

            // Em caso de erro, salvar apenas localmente como fallback
            const newItem = {
                id: `${type === 'service' ? 'srv' : 'prd'}${Date.now()}`,
                name,
                category,
                defaultPrice: price
            };

            if (type === 'service') {
                this.servicesData.services.push(newItem);
            } else {
                this.servicesData.products.push(newItem);
            }

            const storageKey = 'fleetflow_custom_items';
            const customItems = JSON.parse(localStorage.getItem(storageKey) || '{"services": [], "products": []}');

            if (type === 'service') {
                customItems.services.push(newItem);
            } else {
                customItems.products.push(newItem);
            }

            localStorage.setItem(storageKey, JSON.stringify(customItems));

            this.closeAddNewModal();

            if (typeof showToast === 'function') {
                showToast('warning', 'Aviso', `Item salvo apenas localmente. Erro: ${error.message}`);
            } else {
                alert(`⚠️ Item salvo apenas localmente. Erro ao salvar no banco: ${error.message}`);
            }
        }
    }

    calculateRowTotal(input) {
        const row = input.closest('tr');
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const valueStr = row.querySelector('.item-value').value.replace(',', '.');
        const value = parseFloat(valueStr) || 0;
        const total = qty * value;

        row.querySelector('.item-total').textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
        this.updateTotalGeral();
    }

    formatCurrency(input) {
        const value = parseFloat(input.value.replace(',', '.')) || 0;
        input.value = value.toFixed(2).replace('.', ',');
    }

    updateTotalGeral() {
        const rows = document.querySelectorAll('.item-row');
        let total = 0;

        rows.forEach(row => {
            const totalText = row.querySelector('.item-total').textContent;
            const value = parseFloat(totalText.replace('R$', '').replace(',', '.').trim()) || 0;
            total += value;
        });

        const totalCell = document.getElementById('total-geral');
        if (totalCell) {
            totalCell.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
        }
    }

    removeRow(button) {
        const row = button.closest('tr');
        const tbody = document.getElementById('items-tbody');

        // Se é a última linha, apenas limpa
        if (tbody.querySelectorAll('tr').length === 1) {
            this.renderInitialRow();
        } else {
            row.remove();
        }

        this.updateTotalGeral();
    }

    addNewRow() {
        const tbody = document.getElementById('items-tbody');
        const newRow = document.createElement('tr');
        newRow.className = 'bg-white dark:bg-gray-900/50 border-b dark:border-gray-700 item-row';
        newRow.innerHTML = this.createNewRowHTML().replace(/<tr[^>]*>|<\/tr>/g, '');

        tbody.appendChild(newRow);
        this.attachEventListeners();
    }

    attachEventListeners() {
        // Os event listeners são adicionados diretamente no HTML via onclick
        // ou no setupAutocomplete quando necessário
    }

    getItems() {
        const rows = document.querySelectorAll('.item-row');
        const items = [];

        rows.forEach(row => {
            const type = row.querySelector('.item-type').value;
            const description = row.querySelector('.item-description').value.trim();
            const category = row.querySelector('.item-category').value;
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const value = parseFloat(row.querySelector('.item-value').value.replace(',', '.')) || 0;
            const occurrence = row.querySelector('.item-occurrence').value;

            if (type && description) {
                items.push({
                    type: type === 'service' ? 'Serviço' : 'Produto',
                    description,
                    category,
                    qty,
                    value,
                    occurrence,
                    total: qty * value
                });
            }
        });

        return items;
    }
}

// Inicializar quando o DOM carregar
let osManager;
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        osManager = new OSItemsManager();
        window.osManager = osManager; // Expor globalmente
    });
}
