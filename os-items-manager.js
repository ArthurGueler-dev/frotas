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
            const response = await fetch('services-data.json');
            this.servicesData = await response.json();

            // Carregar itens customizados do localStorage
            const storageKey = 'fleetflow_custom_items';
            const customItems = JSON.parse(localStorage.getItem(storageKey) || '{"services": [], "products": []}');

            // Adicionar itens customizados aos arrays
            if (customItems.services && customItems.services.length > 0) {
                this.servicesData.services = [...this.servicesData.services, ...customItems.services];
                console.log(`✅ ${customItems.services.length} serviços customizados carregados`);
            }

            if (customItems.products && customItems.products.length > 0) {
                this.servicesData.products = [...this.servicesData.products, ...customItems.products];
                console.log(`✅ ${customItems.products.length} produtos customizados carregados`);
            }

            console.log('✅ Dados de serviços e produtos carregados');
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
        const row = selectElement.closest('tr');
        const type = selectElement.value;
        const descriptionInput = row.querySelector('.item-description');
        const categorySelect = row.querySelector('.item-category');
        const dropdown = row.querySelector('.item-dropdown');

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

        // Habilita campo de categoria
        categorySelect.disabled = false;
        this.populateCategories(categorySelect);

        // Configura autocomplete
        this.setupAutocomplete(row, type);
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
        const input = row.querySelector('.item-description');
        const dropdown = row.querySelector('.item-dropdown');
        const categorySelect = row.querySelector('.item-category');
        const dropdownBtn = row.querySelector('.item-dropdown-btn');

        // Evento de digitação - sempre mostra dropdown ao digitar
        input.addEventListener('input', () => {
            const searchTerm = input.value.trim().toLowerCase();
            const selectedCategory = categorySelect.value;

            let items = type === 'service' ? this.servicesData.services : this.servicesData.products;

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

            this.renderDropdown(dropdown, items, input, row);
        });

        // Evento de clique no botão dropdown
        if (dropdownBtn) {
            dropdownBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const selectedCategory = categorySelect.value;
                let items = type === 'service' ? this.servicesData.services : this.servicesData.products;

                if (selectedCategory) {
                    items = items.filter(item => item.category === selectedCategory);
                }

                // Toggle dropdown
                if (dropdown.classList.contains('hidden')) {
                    this.renderDropdown(dropdown, items, input, row);
                } else {
                    dropdown.classList.add('hidden');
                }
            });
        }

        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!row.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Evento de mudança de categoria
        categorySelect.addEventListener('change', () => {
            if (input.value) {
                input.dispatchEvent(new Event('input'));
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
        // Fechar todos os dropdowns abertos antes de abrir o modal
        const allDropdowns = document.querySelectorAll('.item-dropdown');
        allDropdowns.forEach(dropdown => {
            dropdown.classList.add('hidden');
        });

        const modal = document.getElementById('new-item-modal');
        const typeLabel = document.getElementById('modal-type-label');
        const categorySelect = document.getElementById('modal-item-category');
        const form = document.getElementById('new-item-form');

        if (!modal) {
            console.error('❌ Modal não encontrado');
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

        // Configurar evento de submit
        form.onsubmit = (e) => {
            e.preventDefault();
            this.saveNewItem();
        };

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
        const modal = document.getElementById('new-item-modal');
        const type = modal.dataset.itemType;
        const name = document.getElementById('modal-item-name').value.trim();
        const category = document.getElementById('modal-item-category').value;
        const priceStr = document.getElementById('modal-item-price').value.trim();

        // Validações
        if (!name) {
            alert('Por favor, informe o nome do item!');
            return;
        }

        if (!category) {
            alert('Por favor, selecione uma categoria!');
            return;
        }

        if (!priceStr) {
            alert('Por favor, informe o preço padrão!');
            return;
        }

        // Converter preço
        const price = parseFloat(priceStr.replace(',', '.'));
        if (isNaN(price) || price <= 0) {
            alert('Por favor, informe um preço válido!');
            return;
        }

        // Criar novo item
        const newItem = {
            id: `${type === 'service' ? 'srv' : 'prd'}${Date.now()}`,
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

        console.log('✅ Novo item cadastrado:', newItem);

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
        alert(`${type === 'service' ? 'Serviço' : 'Produto'} "${name}" cadastrado com sucesso!`);
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
