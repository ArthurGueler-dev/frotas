// Gerenciador de Itens da Ordem de Serviço
// Controla a adição/remoção de serviços e produtos
// INTEGRADO COM SISTEMA DE PEÇAS COMPATÍVEIS

class OSItemsManager {
    constructor() {
        this.servicesData = null;
        this.items = [];
        this.compatibleParts = []; // Peças compatíveis do veículo selecionado
        this.selectedVehicleModel = null; // Modelo do veículo selecionado
        this.init();
    }

    async init() {
        await this.loadServicesData();
        this.renderInitialRow();
        this.setupVehiclePlateListener(); // Escutar mudanças na placa
    }

    // NOVO: Escutar mudanças no campo de placa do veículo
    setupVehiclePlateListener() {
        const plateInput = document.getElementById('vehicle-plate-input');
        if (!plateInput) return;

        // Observar mudanças no valor da placa
        let lastPlate = '';

        // Usar MutationObserver para detectar mudanças programáticas
        const observer = new MutationObserver(() => {
            const currentPlate = plateInput.value.trim().toUpperCase();
            if (currentPlate && currentPlate !== lastPlate) {
                lastPlate = currentPlate;
                this.loadCompatiblePartsForVehicle(currentPlate);
            }
        });

        observer.observe(plateInput, {
            attributes: true,
            attributeFilter: ['value']
        });

        // Também escutar eventos de input
        plateInput.addEventListener('change', () => {
            const currentPlate = plateInput.value.trim().toUpperCase();
            if (currentPlate !== lastPlate) {
                lastPlate = currentPlate;
                this.loadCompatiblePartsForVehicle(currentPlate);
            }
        });

        // Adicionar listener no dropdown de veículos para capturar seleção
        setTimeout(() => {
            const vehicleOptions = document.querySelectorAll('.vehicle-option');
            vehicleOptions.forEach(option => {
                option.addEventListener('click', () => {
                    setTimeout(() => {
                        const plate = plateInput.value.trim().toUpperCase();
                        if (plate) {
                            this.loadCompatiblePartsForVehicle(plate);
                        }
                    }, 100);
                });
            });
        }, 1000);
    }

    // NOVO: Buscar peças compatíveis para o veículo selecionado
    async loadCompatiblePartsForVehicle(plate) {
        try {
            console.log('🔍 Buscando modelo do veículo pela placa:', plate);

            // 1. Buscar modelo do veículo pela placa
            const vehiclesResponse = await fetch('vehicles-data.json');
            const vehicles = await vehiclesResponse.json();
            const vehicle = vehicles.find(v => v.plate === plate);

            if (!vehicle) {
                console.warn('⚠️ Veículo não encontrado na lista local');
                this.compatibleParts = [];
                this.selectedVehicleModel = null;
                return;
            }

            // Extrair modelo do veículo (ex: "HILUX CD", "S10 CD LS 2.8", "HR")
            let modelName = vehicle.model;

            // Tentar mapear para o nome usado no banco de peças
            const modelMappings = {
                'HILUX': 'HILUX CD',
                'S10': 'S10 CD LS 2.8',
                'L200': 'L200',
                'MOBI': 'MOBI 1.0 Like',
                'CELTA': 'CELTA',
                'ONIX': 'ONIX',
                'CLASSIC': 'CLASSIC',
                'HB20': 'Hb20',
                'STRADA': 'STRADA 1.4 Endurance',
                'MONTANA': 'MONTANA',
                'SANDERO': 'SANDERO 1.6 Stepway',
                'HR-V': 'HR-V',
                'HR': 'HR'
            };

            // Tentar encontrar correspondência
            for (const [key, value] of Object.entries(modelMappings)) {
                if (modelName.toUpperCase().includes(key)) {
                    modelName = value;
                    break;
                }
            }

            this.selectedVehicleModel = modelName;
            console.log('🚗 Modelo identificado:', modelName);

            // 2. Buscar peças compatíveis do banco de dados
            console.log('🔍 Buscando peças compatíveis para modelo:', modelName);

            const partsResponse = await fetch(
                `https://floripa.in9automacao.com.br/pecas-compatibilidade-api.php?modelo=${encodeURIComponent(modelName)}`
            );

            if (!partsResponse.ok) {
                throw new Error(`HTTP ${partsResponse.status}`);
            }

            const partsData = await partsResponse.json();
            console.log('📦 Resposta da API de peças:', partsData);

            if (partsData.success && partsData.data) {
                // Transformar dados para o formato esperado
                this.compatibleParts = this.transformCompatibleParts(partsData.data);

                console.log(`✅ ${this.compatibleParts.length} peças compatíveis carregadas para ${modelName}`);
                console.log('📋 Exemplo de peças:', this.compatibleParts.slice(0, 3));

                // Mostrar notificação ao usuário
                if (typeof showToast === 'function') {
                    showToast('success', 'Peças Carregadas',
                        `${this.compatibleParts.length} peças compatíveis encontradas para ${modelName}`);
                }
            } else {
                console.warn('⚠️ Nenhuma peça compatível encontrada para este modelo');
                this.compatibleParts = [];
            }

        } catch (error) {
            console.error('❌ Erro ao buscar peças compatíveis:', error);
            this.compatibleParts = [];
            this.selectedVehicleModel = null;
        }
    }

    // NOVO: Transformar dados da API em formato do sistema
    transformCompatibleParts(apiData) {
        const parts = [];
        const processedOriginals = new Set(); // Evitar duplicatas

        apiData.forEach(item => {
            const originalPart = item.peca_original;

            // Processar peça original apenas uma vez
            if (!processedOriginals.has(originalPart.id)) {
                processedOriginals.add(originalPart.id);

                parts.push({
                    id: `orig-${originalPart.id}`,
                    name: `${originalPart.nome} (Original)`,
                    category: this.mapCategory(item.categoria_aplicacao),
                    defaultPrice: parseFloat(originalPart.custo_unitario),
                    type: 'original',
                    fornecedor: originalPart.fornecedor,
                    codigo: originalPart.codigo,
                    descricao: originalPart.descricao
                });
            }

            // Processar peças similares
            if (item.pecas_similares && item.pecas_similares.length > 0) {
                item.pecas_similares.forEach(similar => {
                    parts.push({
                        id: `sim-${similar.id}`,
                        name: `${similar.nome} (Similar)`,
                        category: this.mapCategory(item.categoria_aplicacao),
                        defaultPrice: parseFloat(similar.custo_unitario),
                        type: 'similar',
                        fornecedor: similar.fornecedor,
                        codigo: similar.codigo,
                        descricao: similar.descricao,
                        observacoes: similar.observacoes // Contém info de economia
                    });
                });
            }
        });

        return parts;
    }

    // NOVO: Mapear categorias da API para categorias do sistema
    mapCategory(apiCategory) {
        const categoryMap = {
            'Filtros': 'filtros',
            'Óleos': 'oleos',
            'Fluidos': 'oleos', // Agrupar com óleos
            'Freios': 'freio',
            'Transmissão': 'cambio',
            'Motor': 'motor',
            'Suspensão': 'suspensao',
            'Direção': 'suspensao', // Agrupar com suspensão
            'Elétrica': 'eletrica'
        };

        return categoryMap[apiCategory] || 'geral';
    }

    async loadServicesData() {
        try {
            // Carregar dados do JSON estático
            const response = await fetch('services-data.json');
            this.servicesData = await response.json();

            // Adicionar categorias de peças
            if (!this.servicesData.categories.includes('filtros')) {
                this.servicesData.categories.push('filtros', 'oleos', 'freio', 'cambio');
            }

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
        // MODIFICADO: Ordem alterada para Tipo → Categoria → Descrição
        return `
            <tr class="bg-white dark:bg-gray-900/50 border-b dark:border-gray-700 item-row">
                <td class="px-2 py-3">
                    <select class="form-select w-full bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-type" onchange="osManager.onTypeChange(this)">
                        <option value="">Selecione...</option>
                        <option value="service">Serviço</option>
                        <option value="product">Produto/Peça</option>
                    </select>
                </td>
                <td class="px-2 py-3">
                    <select class="form-select w-full bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-category" disabled onchange="osManager.onCategoryChange(this)">
                        <option value="">Aguardando tipo...</option>
                    </select>
                </td>
                <td class="px-2 py-3">
                    <div class="relative">
                        <input type="text"
                               class="form-input w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded h-10 text-xs item-description focus:ring-2 focus:ring-primary/50 focus:outline-none"
                               placeholder="Selecione tipo e categoria..."
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

    // MODIFICADO: Agora busca peças compatíveis se tipo = produto
    onTypeChange(selectElement) {
        console.log('🔵 onTypeChange chamado');

        const row = selectElement.closest('tr');
        const type = selectElement.value;
        const descriptionInput = row.querySelector('.item-description');
        const categorySelect = row.querySelector('.item-category');
        const dropdown = row.querySelector('.item-dropdown');

        console.log('🔵 Tipo selecionado:', type);
        console.log('🔵 Peças compatíveis disponíveis:', this.compatibleParts.length);

        if (!type) {
            descriptionInput.disabled = true;
            descriptionInput.placeholder = 'Selecione o tipo primeiro...';
            categorySelect.disabled = true;
            categorySelect.innerHTML = '<option value="">Aguardando tipo...</option>';
            return;
        }

        // Habilitar campo de categoria
        categorySelect.disabled = false;
        this.populateCategories(categorySelect, type);

        // Campo de descrição só habilita após selecionar categoria
        descriptionInput.disabled = true;
        descriptionInput.placeholder = 'Selecione a categoria primeiro...';
        descriptionInput.value = '';

        console.log('🔵 Categorias populadas para tipo:', type);
    }

    // NOVO: Evento quando categoria é alterada
    onCategoryChange(selectElement) {
        const row = selectElement.closest('tr');
        const type = row.querySelector('.item-type').value;
        const category = selectElement.value;
        const descriptionInput = row.querySelector('.item-description');

        console.log('🟢 onCategoryChange - Tipo:', type, 'Categoria:', category);

        if (!category) {
            descriptionInput.disabled = true;
            descriptionInput.placeholder = 'Selecione a categoria primeiro...';
            return;
        }

        // Habilitar descrição
        descriptionInput.disabled = false;
        descriptionInput.placeholder = `Digite para buscar...`;
        descriptionInput.value = '';

        // Configurar autocomplete com filtro de categoria
        this.setupAutocomplete(row, type);

        console.log('🟢 Campo de descrição habilitado');
    }

    // MODIFICADO: Popular categorias baseado no tipo e peças disponíveis
    populateCategories(selectElement, type) {
        if (!this.servicesData) return;

        selectElement.innerHTML = '<option value="">Selecione categoria...</option>';

        if (type === 'product' && this.compatibleParts.length > 0) {
            // Se há peças compatíveis, usar categorias das peças
            const categories = [...new Set(this.compatibleParts.map(p => p.category))];

            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = this.formatCategoryName(cat);
                selectElement.appendChild(option);
            });

            console.log(`✅ ${categories.length} categorias de peças disponíveis`);
        } else {
            // Usar categorias padrão do sistema
            this.servicesData.categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = this.formatCategoryName(cat);
                selectElement.appendChild(option);
            });
        }
    }

    formatCategoryName(category) {
        const names = {
            'pneus': 'Pneus',
            'munck': 'Munck',
            'carroceria': 'Carroceria',
            'motor': 'Motor',
            'caixa': 'Caixa',
            'limpeza': 'Limpeza',
            'cambio': 'Câmbio/Transmissão',
            'suspensao': 'Suspensão/Direção',
            'eletrica': 'Elétrica',
            'freio': 'Freios',
            'filtros': 'Filtros',
            'oleos': 'Óleos e Fluidos'
        };
        return names[category] || category;
    }

    // MODIFICADO: Filtrar por categoria ANTES de mostrar itens
    setupAutocomplete(row, type) {
        console.log('🟦 setupAutocomplete iniciado para tipo:', type);

        const input = row.querySelector('.item-description');
        const dropdown = row.querySelector('.item-dropdown');
        const categorySelect = row.querySelector('.item-category');
        const dropdownBtn = row.querySelector('.item-dropdown-btn');

        // Remover event listeners antigos
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

        console.log('🟦 Event listeners limpos');

        // Função para obter itens filtrados
        const getFilteredItems = (searchTerm = '') => {
            const selectedCategory = categorySelect.value;

            if (!selectedCategory) {
                return [];
            }

            let items = [];

            // Se tipo = produto E há peças compatíveis, usar peças compatíveis
            if (type === 'product' && this.compatibleParts.length > 0) {
                items = this.compatibleParts;
                console.log(`🟦 Usando ${items.length} peças compatíveis`);
            } else {
                // Caso contrário, usar dados padrão
                items = type === 'service' ? this.servicesData.services : this.servicesData.products;
                console.log(`🟦 Usando ${items.length} itens padrão`);
            }

            // Filtrar por categoria
            items = items.filter(item => item.category === selectedCategory);
            console.log(`🟦 Após filtro categoria "${selectedCategory}": ${items.length} itens`);

            // Filtrar por termo de busca
            if (searchTerm) {
                items = items.filter(item =>
                    item.name.toLowerCase().includes(searchTerm.toLowerCase())
                );
                console.log(`🟦 Após filtro busca "${searchTerm}": ${items.length} itens`);
            }

            return items;
        };

        // Evento de digitação
        finalInput.addEventListener('input', () => {
            const searchTerm = finalInput.value.trim();
            const items = getFilteredItems(searchTerm);
            this.renderDropdown(finalDropdown, items, finalInput, row);
        });

        // Evento de clique no botão dropdown
        if (finalDropdownBtn) {
            finalDropdownBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const items = getFilteredItems();

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

        dropdown.innerHTML = items.map(item => {
            // Se for peça compatível, mostrar mais informações
            const extraInfo = item.observacoes ? `<div class="text-xs text-green-600 dark:text-green-400 mt-1">${item.observacoes}</div>` : '';
            const badge = item.type === 'original' ? '<span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded">Original</span>' :
                         item.type === 'similar' ? '<span class="px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded">Similar</span>' : '';

            return `
                <div class="item-option px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                     data-id="${item.id}"
                     data-name="${item.name}"
                     data-price="${item.defaultPrice}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <div class="font-semibold text-primary dark:text-blue-400">${item.name}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                ${this.formatCategoryName(item.category)} • R$ ${item.defaultPrice.toFixed(2).replace('.', ',')}
                                ${item.fornecedor ? ` • ${item.fornecedor}` : ''}
                            </div>
                            ${extraInfo}
                        </div>
                        ${badge}
                    </div>
                </div>
            `;
        }).join('') + `
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

        if (!modal || !form) {
            console.error('❌ Modal ou formulário não encontrado');
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

            let isSubmitting = false;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (isSubmitting) return;
                isSubmitting = true;

                const type = modal.dataset.itemType;
                const name = document.getElementById('modal-item-name').value.trim();
                const category = document.getElementById('modal-item-category').value;
                const priceStr = document.getElementById('modal-item-price').value.trim();
                const occurrence = document.getElementById('modal-item-occurrence').value;

                // Validações
                if (!name || !category || !priceStr || !occurrence) {
                    alert('Por favor, preencha todos os campos!');
                    isSubmitting = false;
                    return;
                }

                const price = parseFloat(priceStr.replace(',', '.'));
                if (isNaN(price) || price <= 0) {
                    alert('Por favor, informe um preço válido!');
                    isSubmitting = false;
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

                try {
                    const response = await fetch('https://floripa.in9automacao.com.br/api-servicos.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(requestData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        const newItem = {
                            id: result.id || `${type === 'service' ? 'srv' : 'prd'}${Date.now()}`,
                            name,
                            category,
                            defaultPrice: price
                        };

                        if (type === 'service') {
                            this.servicesData.services.push(newItem);
                        } else {
                            this.servicesData.products.push(newItem);
                        }

                        modal.classList.add('hidden');
                        form.reset();

                        if (typeof showToast === 'function') {
                            showToast('success', 'Sucesso', `${typeName} "${name}" cadastrado!`);
                        }
                    } else {
                        throw new Error(result.error || 'Erro ao salvar');
                    }
                } catch (error) {
                    console.error('❌ Erro:', error);
                    if (typeof showToast === 'function') {
                        showToast('error', 'Erro', error.message);
                    }
                } finally {
                    isSubmitting = false;
                }
            });
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
        // Event listeners são adicionados via onclick no HTML ou no setupAutocomplete
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
