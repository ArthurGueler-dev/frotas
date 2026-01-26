// Gerenciador de Itens da Ordem de Serviço
// Controla a adição/remoção de serviços e produtos
// INTEGRADO COM SISTEMA DE PEÇAS COMPATÍVEIS

class OSItemsManager {
    constructor() {
        this.servicesData = null;
        this.items = [];
        this.compatibleParts = []; // Peças compatíveis do veículo selecionado
        this.selectedVehicleModel = null; // Modelo do veículo selecionado
        this.fornecedores = []; // Lista de fornecedores
        this.init();
    }

    async init() {
        console.log('🚀 Iniciando OSItemsManager...');
        await this.loadServicesData();
        await this.loadFornecedores();
        this.renderInitialRow();
        this.setupVehiclePlateListener(); // Escutar mudanças na placa
        console.log('✅ OSItemsManager inicializado com sucesso!');
    }

    // NOVO: Carregar lista de fornecedores
    async loadFornecedores() {
        console.log('📡 Carregando fornecedores...');
        try {
            const response = await fetch('fornecedores-nomes.json');
            console.log('📡 Resposta recebida:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            this.fornecedores = await response.json();
            console.log(`✅ ${this.fornecedores.length} fornecedores carregados:`, this.fornecedores.slice(0, 3));
        } catch (error) {
            console.error('❌ Erro ao carregar fornecedores:', error);
            console.error('Stack:', error.stack);
            this.fornecedores = [];
        }
    }

    // NOVO: Escutar mudanças no campo de placa do veículo (MÚLTIPLAS ESTRATÉGIAS)
    setupVehiclePlateListener() {
        const plateInput = document.getElementById('vehicle-plate-input');
        if (!plateInput) {
            console.warn('⚠️ Campo de placa não encontrado');
            return;
        }

        console.log('✅ Configurando listeners de placa do veículo');

        let lastPlate = '';

        // Função para processar mudança de placa
        const processPlateChange = () => {
            const currentPlate = plateInput.value.trim().toUpperCase();
            console.log('🔍 Verificando placa:', currentPlate, '| Última:', lastPlate);

            if (currentPlate && currentPlate !== lastPlate && currentPlate.length >= 7) {
                lastPlate = currentPlate;
                console.log('🚗 Nova placa detectada:', currentPlate);
                this.loadCompatiblePartsForVehicle(currentPlate);
            }
        };

        // ESTRATÉGIA 1: Polling (verificação a cada 500ms)
        setInterval(processPlateChange, 500);
        console.log('✅ Polling ativado (500ms)');

        // ESTRATÉGIA 2: Event listeners diretos
        plateInput.addEventListener('change', processPlateChange);
        plateInput.addEventListener('blur', processPlateChange);
        plateInput.addEventListener('input', processPlateChange);
        console.log('✅ Event listeners configurados (change, blur, input)');

        // ESTRATÉGIA 3: MutationObserver (mudanças programáticas)
        const observer = new MutationObserver(processPlateChange);
        observer.observe(plateInput, {
            attributes: true,
            attributeFilter: ['value'],
            characterData: true,
            childList: true
        });
        console.log('✅ MutationObserver configurado');

        // ESTRATÉGIA 4: Interceptar cliques no dropdown de veículos
        const interceptVehicleClick = () => {
            const vehicleDropdown = document.getElementById('vehicle-dropdown');
            if (vehicleDropdown) {
                vehicleDropdown.addEventListener('click', (e) => {
                    if (e.target.classList.contains('vehicle-option') || e.target.closest('.vehicle-option')) {
                        console.log('🖱️ Clique no dropdown de veículos detectado');
                        setTimeout(processPlateChange, 200);
                        setTimeout(processPlateChange, 500);
                        setTimeout(processPlateChange, 1000);
                    }
                });
                console.log('✅ Listener do dropdown de veículos configurado');
            }
        };

        // Tentar configurar imediatamente e depois de 1s e 3s
        interceptVehicleClick();
        setTimeout(interceptVehicleClick, 1000);
        setTimeout(interceptVehicleClick, 3000);

        console.log('🎯 Sistema de detecção de placa COMPLETO e ATIVO!');
    }

    // NOVO: Buscar peças compatíveis para o veículo selecionado
    async loadCompatiblePartsForVehicle(plate) {
        try {
            console.log('═══════════════════════════════════════════════');
            console.log('🔍 INICIANDO BUSCA DE PEÇAS COMPATÍVEIS');
            console.log('📋 Placa:', plate);
            console.log('═══════════════════════════════════════════════');

            // 1. Buscar modelo do veículo pela placa
            console.log('📡 Buscando vehicles-data.json...');
            const vehiclesResponse = await fetch('vehicles-data.json');
            const vehicles = await vehiclesResponse.json();
            console.log(`✅ ${vehicles.length} veículos carregados`);

            const vehicle = vehicles.find(v => v.plate === plate);
            console.log('🔎 Procurando veículo com placa:', plate);

            if (!vehicle) {
                console.error('❌ Veículo NÃO encontrado na lista local!');
                console.log('📋 Placas disponíveis:', vehicles.map(v => v.plate).slice(0, 10).join(', ') + '...');
                // Mesmo sem encontrar o veículo, buscar peças universais
                await this.loadUniversalParts();
                return;
            }

            console.log('✅ Veículo encontrado:', vehicle);

            // Extrair modelo do veículo (ex: "HILUX CD", "S10 CD LS 2.8", "HR")
            let modelName = vehicle.model;
            console.log('🚗 Modelo original do veículo:', modelName);

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
            console.log('🎯 Modelo FINAL identificado:', modelName);

            // 2. Buscar peças em paralelo: específicas do modelo + universais
            console.log('═══════════════════════════════════════════════');
            console.log('📡 BUSCANDO PEÇAS (ESPECÍFICAS + UNIVERSAIS)');
            console.log('═══════════════════════════════════════════════');

            const [modelPartsResponse, universalPartsResponse] = await Promise.all([
                fetch(`https://floripa.in9automacao.com.br/pecas-compatibilidade-api.php?modelo=${encodeURIComponent(modelName)}`),
                fetch('https://floripa.in9automacao.com.br/pecas-api.php?universal=1')
            ]);

            // Processar peças específicas do modelo
            let modelParts = [];
            if (modelPartsResponse.ok) {
                const modelPartsData = await modelPartsResponse.json();
                if (modelPartsData.success && modelPartsData.data && modelPartsData.data.length > 0) {
                    modelParts = this.transformCompatibleParts(modelPartsData.data);
                    console.log(`✅ ${modelParts.length} peças ESPECÍFICAS do modelo ${modelName}`);
                }
            }

            // Processar peças universais
            let universalParts = [];
            if (universalPartsResponse.ok) {
                const universalPartsData = await universalPartsResponse.json();
                if (universalPartsData.success && universalPartsData.data && universalPartsData.data.length > 0) {
                    universalParts = this.transformUniversalParts(universalPartsData.data);
                    console.log(`✅ ${universalParts.length} peças UNIVERSAIS carregadas`);
                }
            }

            // Combinar peças (específicas primeiro, depois universais)
            this.compatibleParts = [...modelParts, ...universalParts];

            console.log(`✅ TOTAL: ${this.compatibleParts.length} peças disponíveis para ${modelName}`);
            console.log(`   - ${modelParts.length} específicas do modelo`);
            console.log(`   - ${universalParts.length} universais`);
            console.log('📋 Categorias encontradas:', [...new Set(this.compatibleParts.map(p => p.category))]);

            // Mostrar notificação ao usuário
            if (typeof showToast === 'function') {
                if (this.compatibleParts.length > 0) {
                    showToast('success', 'Peças Carregadas',
                        `${modelParts.length} peças específicas + ${universalParts.length} universais`);
                } else {
                    showToast('warning', 'Sem Peças', 'Nenhuma peça encontrada');
                }
            }

        } catch (error) {
            console.error('❌ Erro ao buscar peças compatíveis:', error);
            // Em caso de erro, tentar carregar apenas universais
            await this.loadUniversalParts();
        }
    }

    // NOVO: Carregar apenas peças universais (fallback)
    async loadUniversalParts() {
        try {
            console.log('📡 Carregando apenas peças UNIVERSAIS...');
            const response = await fetch('https://floripa.in9automacao.com.br/pecas-api.php?universal=1');

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            if (data.success && data.data && data.data.length > 0) {
                this.compatibleParts = this.transformUniversalParts(data.data);
                console.log(`✅ ${this.compatibleParts.length} peças UNIVERSAIS carregadas`);

                if (typeof showToast === 'function') {
                    showToast('info', 'Peças Universais',
                        `${this.compatibleParts.length} peças compatíveis com todos os veículos`);
                }
            } else {
                this.compatibleParts = [];
            }
        } catch (error) {
            console.error('❌ Erro ao carregar peças universais:', error);
            this.compatibleParts = [];
        }
    }

    // NOVO: Transformar peças universais para o formato do sistema
    transformUniversalParts(apiData) {
        console.log('🔄 Transformando peças universais...');

        return apiData.map(peca => ({
            id: `univ-${peca.id}`,
            name: `${peca.nome}`,
            category: this.mapCategory(peca.categoria),
            defaultPrice: parseFloat(peca.custo_unitario) || 0,
            type: 'universal',
            fornecedor: peca.fornecedor || '',
            codigo: peca.codigo || '',
            descricao: peca.descricao || ''
        }));
    }

    // NOVO: Transformar dados da API em formato do sistema
    transformCompatibleParts(apiData) {
        console.log('🔄 Transformando dados da API...');
        console.log('📊 Items recebidos da API:', apiData.length);

        const parts = [];
        const processedOriginals = new Set(); // Evitar duplicatas
        const categoriesFromAPI = new Set(); // Track categorias da API

        apiData.forEach((item, index) => {
            categoriesFromAPI.add(item.categoria_aplicacao);

            const originalPart = item.peca_original;
            const mappedCategory = this.mapCategory(item.categoria_aplicacao);

            // Processar peça original apenas uma vez
            if (!processedOriginals.has(originalPart.id)) {
                processedOriginals.add(originalPart.id);

                parts.push({
                    id: `orig-${originalPart.id}`,
                    name: `${originalPart.nome} (Original)`,
                    category: mappedCategory,
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
                        category: mappedCategory,
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

        console.log('\n═══════════════════════════════════════════════');
        console.log('📊 RESUMO DA TRANSFORMAÇÃO:');
        console.log('  Total de peças:', parts.length);

        const apiCategoriesArray = Array.from(categoriesFromAPI);
        console.log('  Categorias da API (DETALHADO):');
        apiCategoriesArray.forEach(cat => {
            console.log(`    - "${cat}"`);
        });

        const mappedCategories = [...new Set(parts.map(p => p.category))];
        console.log('  Categorias mapeadas (DETALHADO):');
        mappedCategories.forEach(cat => {
            console.log(`    - "${cat}"`);
        });

        // Contar peças por categoria
        console.log('\n📊 CONTAGEM POR CATEGORIA:');
        mappedCategories.forEach(cat => {
            const count = parts.filter(p => p.category === cat).length;
            console.log(`  ${cat}: ${count} peças`);
        });

        console.log('═══════════════════════════════════════════════\n');

        return parts;
    }

    // NOVO: Mapear categorias da API para categorias do sistema
    mapCategory(apiCategory) {
        if (!apiCategory) {
            console.warn('⚠️ Categoria vazia recebida');
            return 'geral';
        }

        // Normalizar (remover acentos, minúsculas, trim)
        const normalized = apiCategory
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();

        const categoryMap = {
            // Filtros
            'filtros': 'filtros',
            'filtro': 'filtros',

            // Óleos e Fluidos
            'oleos': 'oleos',
            'oleo': 'oleos',
            'fluidos': 'oleos',
            'fluido': 'oleos',
            'oleos e fluidos': 'oleos',
            'oleo e fluido': 'oleos',

            // Freios
            'freios': 'freio',
            'freio': 'freio',

            // Transmissão/Câmbio/Correias
            'transmissao': 'cambio',
            'cambio': 'cambio',
            'caixa': 'cambio',
            'correias': 'cambio',
            'correia': 'cambio',
            'correias e transmissao': 'cambio',
            'correia e transmissao': 'cambio',

            // Motor
            'motor': 'motor',

            // Suspensão e Direção
            'suspensao': 'suspensao',
            'direcao': 'suspensao',
            'suspensao e direcao': 'suspensao',
            'direcao e suspensao': 'suspensao',

            // Elétrica
            'eletrica': 'eletrica',
            'eletrico': 'eletrica',
            'sistema eletrico': 'eletrica',

            // Outros (mapeamento padrão)
            'outros': 'geral',
            'outro': 'geral'
        };

        const mapped = categoryMap[normalized];

        // Logar apenas se não foi mapeado (caiu no fallback)
        if (!mapped) {
            console.warn(`⚠️ Categoria não mapeada: "${apiCategory}" (normalized: "${normalized}") → fallback para "geral"`);
        }

        return mapped || 'geral';
    }

    async loadServicesData() {
        try {
            // Carregar dados do JSON estático
            const response = await fetch('services-data.json');
            this.servicesData = await response.json();

            console.log('📚 Categorias ANTES de adicionar peças:', this.servicesData.categories);

            // Adicionar categorias de peças se não existirem
            const pecasCategories = ['filtros', 'oleos', 'freio', 'cambio', 'motor', 'suspensao', 'eletrica'];

            pecasCategories.forEach(cat => {
                if (!this.servicesData.categories.includes(cat)) {
                    this.servicesData.categories.push(cat);
                    console.log(`  ➕ Adicionada categoria: ${cat}`);
                }
            });

            console.log('📚 Categorias DEPOIS de adicionar peças:', this.servicesData.categories);

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
                            defaultPrice: parseFloat(item.valor_padrao),
                            codigo: item.codigo || ''
                        }));

                    const dbProducts = apiData.data
                        .filter(item => item.tipo === 'Produto' && item.ativo == 1)
                        .map(item => ({
                            id: `db-prd-${item.id}`,
                            name: item.nome,
                            category: 'geral', // categoria padrão
                            defaultPrice: parseFloat(item.valor_padrao),
                            codigo: item.codigo || ''
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
        // MODIFICADO: Ordem alterada para Tipo → Categoria → Descrição + Fornecedor com Autocomplete
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
                               placeholder="Selecione o tipo primeiro..."
                               disabled
                               autocomplete="off"
                               style="padding-right: 30px;"/>
                        <button type="button" class="absolute right-1 top-1/2 -translate-y-1/2 text-primary item-dropdown-btn" style="pointer-events: auto;">
                            <span class="material-symbols-outlined text-lg">arrow_drop_down</span>
                        </button>
                        <div class="absolute mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-80 overflow-y-auto hidden item-dropdown" style="z-index: 999; min-width: 500px; width: max-content; max-width: 700px; left: 0;">
                            <!-- Opções aparecerão aqui -->
                        </div>
                    </div>
                </td>
                <td class="px-2 py-3">
                    <div class="relative">
                        <input type="text"
                               class="form-input w-full bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-fornecedor-produto"
                               placeholder="Fornecedor do produto"
                               autocomplete="off"/>
                        <div class="absolute w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-48 overflow-y-auto hidden fornecedor-dropdown" style="z-index: 998;">
                            <!-- Sugestões de fornecedores -->
                        </div>
                    </div>
                </td>
                <td class="px-2 py-3">
                    <div class="relative">
                        <input type="text"
                               class="form-input w-full bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded h-10 text-xs item-fornecedor-servico"
                               placeholder="Fornecedor do serviço"
                               autocomplete="off"/>
                        <div class="absolute w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl max-h-48 overflow-y-auto hidden fornecedor-dropdown" style="z-index: 998;">
                            <!-- Sugestões de fornecedores -->
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

        // MODIFICADO: Habilitar descrição imediatamente após selecionar tipo
        descriptionInput.disabled = false;
        descriptionInput.placeholder = 'Digite para buscar...';
        descriptionInput.value = '';

        console.log('✅✅✅ DESCRIÇÃO HABILITADA! disabled =', descriptionInput.disabled);
        console.log('✅✅✅ Placeholder:', descriptionInput.placeholder);

        // Configurar autocomplete com tipo selecionado
        this.setupAutocomplete(row, type);

        console.log('🔵 Categorias populadas e descrição habilitada para tipo:', type);
    }

    // NOVO: Evento quando categoria é alterada
    onCategoryChange(selectElement) {
        const row = selectElement.closest('tr');
        const type = row.querySelector('.item-type').value;
        const category = selectElement.value;
        const descriptionInput = row.querySelector('.item-description');

        console.log('🟢 onCategoryChange - Tipo:', type, 'Categoria:', category);

        // MODIFICADO: Não desabilitar descrição, apenas reconfigurar autocomplete
        descriptionInput.value = '';
        descriptionInput.placeholder = category ?
            `Digite para buscar em ${this.formatCategoryName(category)}...` :
            'Digite para buscar...';

        // Reconfigurar autocomplete com o novo filtro de categoria
        this.setupAutocomplete(row, type);

        console.log('🟢 Autocomplete reconfigurado com categoria:', category || 'todas');
    }

    // MODIFICADO: Popular categorias baseado no tipo e peças disponíveis
    populateCategories(selectElement, type) {
        console.log('🎨 populateCategories chamado');
        console.log('📊 Tipo:', type);
        console.log('📊 Peças compatíveis disponíveis:', this.compatibleParts.length);

        if (!this.servicesData) {
            console.error('❌ servicesData não está disponível!');
            return;
        }

        selectElement.innerHTML = '<option value="">Todas as categorias</option>';

        if (type === 'product' && this.compatibleParts.length > 0) {
            console.log('✅ Usando categorias das PEÇAS COMPATÍVEIS');

            // Se há peças compatíveis, usar categorias das peças
            const categories = [...new Set(this.compatibleParts.map(p => p.category))];

            console.log('📋 Categorias extraídas:', categories);

            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = this.formatCategoryName(cat);
                selectElement.appendChild(option);
                console.log(`  ✅ Adicionada categoria: ${cat} (${this.formatCategoryName(cat)})`);
            });

            console.log(`✅ ${categories.length} categorias de peças ADICIONADAS ao select`);
        } else {
            console.log('⚠️ Usando categorias PADRÃO do sistema');
            console.log('   Motivo: type=' + type + ', compatibleParts.length=' + this.compatibleParts.length);

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

            // MODIFICADO: Filtrar por categoria apenas se uma categoria estiver selecionada
            if (selectedCategory) {
                items = items.filter(item => item.category === selectedCategory);
                console.log(`🟦 Após filtro categoria "${selectedCategory}": ${items.length} itens`);
            } else {
                console.log(`🟦 Sem filtro de categoria - mostrando todos os ${items.length} itens`);
            }

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
                     data-price="${item.defaultPrice}"
                     data-fornecedor="${item.fornecedor || ''}"
                     data-codigo="${item.codigo || ''}"
                     data-tipo-peca="${item.type || ''}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <div class="font-semibold text-primary dark:text-blue-400">${item.name}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                ${item.codigo ? `Cód: ${item.codigo} • ` : ''}${this.formatCategoryName(item.category)} • R$ ${item.defaultPrice.toFixed(2).replace('.', ',')}
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
                const fornecedor = option.dataset.fornecedor || '';
                const codigo = option.dataset.codigo || '';
                const tipoPeca = option.dataset.tipoPeca || '';
                const type = row.querySelector('.item-type').value;

                input.value = name;
                // Armazenar dados adicionais para impressão
                input.dataset.codigo = codigo;
                input.dataset.tipoPeca = tipoPeca;
                row.querySelector('.item-value').value = price.toFixed(2).replace('.', ',');

                // Preencher fornecedor automaticamente no campo correto
                if (fornecedor) {
                    if (type === 'product') {
                        const fornecedorProdutoInput = row.querySelector('.item-fornecedor-produto');
                        if (fornecedorProdutoInput) {
                            fornecedorProdutoInput.value = fornecedor;
                        }
                    } else if (type === 'service') {
                        const fornecedorServicoInput = row.querySelector('.item-fornecedor-servico');
                        if (fornecedorServicoInput) {
                            fornecedorServicoInput.value = fornecedor;
                        }
                    }
                }

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
        const occurrenceField = document.getElementById('occurrence-field');
        const productExtraFields = document.getElementById('product-extra-fields');

        if (!modal || !form) {
            console.error('❌ Modal ou formulário não encontrado');
            return;
        }

        // Definir tipo do item
        const typeName = type === 'service' ? 'Serviço' : 'Peça';
        typeLabel.textContent = typeName;
        modal.dataset.itemType = type;

        // Mostrar/ocultar campos baseado no tipo
        if (type === 'product') {
            // Para peças: mostrar campos extras, ocultar ocorrência
            if (occurrenceField) occurrenceField.classList.add('hidden');
            if (productExtraFields) productExtraFields.classList.remove('hidden');
            // Remover required do campo ocorrência
            const occurrenceSelect = document.getElementById('modal-item-occurrence');
            if (occurrenceSelect) occurrenceSelect.removeAttribute('required');
        } else {
            // Para serviços: mostrar ocorrência, ocultar campos extras
            if (occurrenceField) occurrenceField.classList.remove('hidden');
            if (productExtraFields) productExtraFields.classList.add('hidden');
            // Adicionar required no campo ocorrência
            const occurrenceSelect = document.getElementById('modal-item-occurrence');
            if (occurrenceSelect) occurrenceSelect.setAttribute('required', 'required');
        }

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
        // Limpar campos extras de peça
        const codeField = document.getElementById('modal-item-code');
        const supplierField = document.getElementById('modal-item-supplier');
        const universalField = document.getElementById('modal-item-universal');
        if (codeField) codeField.value = '';
        if (supplierField) supplierField.value = '';
        if (universalField) universalField.checked = false;

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

                // Validações básicas
                if (!name || !category || !priceStr) {
                    alert('Por favor, preencha Nome, Categoria e Preço!');
                    isSubmitting = false;
                    return;
                }

                // Validação de ocorrência apenas para serviços
                if (type === 'service' && !occurrence) {
                    alert('Por favor, selecione o Tipo de Ocorrência!');
                    isSubmitting = false;
                    return;
                }

                const price = parseFloat(priceStr.replace(',', '.'));
                if (isNaN(price) || price <= 0) {
                    alert('Por favor, informe um preço válido!');
                    isSubmitting = false;
                    return;
                }

                try {
                    let response, result;

                    if (type === 'product') {
                        // PEÇA: Salvar em pecas-api.php
                        const code = document.getElementById('modal-item-code')?.value.trim() || '';
                        const supplier = document.getElementById('modal-item-supplier')?.value.trim() || '';
                        const universal = document.getElementById('modal-item-universal')?.checked ? 1 : 0;

                        const pecaData = {
                            codigo: code || null,
                            nome: name,
                            categoria: this.formatCategoryName(category),
                            custo_unitario: price,
                            fornecedor: supplier || null,
                            universal: universal,
                            unidade: 'UN'
                        };

                        console.log('📦 Salvando peça:', pecaData);

                        response = await fetch('https://floripa.in9automacao.com.br/pecas-api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(pecaData)
                        });

                        result = await response.json();

                        if (result.success) {
                            const newItem = {
                                id: `univ-${result.id}`,
                                name: name,
                                category: category,
                                defaultPrice: price,
                                type: universal ? 'universal' : 'specific',
                                fornecedor: supplier,
                                codigo: code
                            };

                            // Adicionar à lista de peças compatíveis se for universal
                            if (universal) {
                                this.compatibleParts.push(newItem);
                            }
                            this.servicesData.products.push(newItem);

                            modal.classList.add('hidden');
                            form.reset();

                            if (typeof showToast === 'function') {
                                showToast('success', 'Sucesso', `Peça "${name}" cadastrada!`);
                            }
                        } else {
                            throw new Error(result.error || 'Erro ao salvar peça');
                        }
                    } else {
                        // SERVIÇO: Salvar em api-servicos.php
                        const requestData = {
                            codigo: `SRV${Date.now()}`,
                            nome: name,
                            tipo: 'Serviço',
                            valor_padrao: price,
                            ocorrencia_padrao: occurrence,
                            ativo: 1
                        };

                        response = await fetch('https://floripa.in9automacao.com.br/api-servicos.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(requestData)
                        });

                        result = await response.json();

                        if (result.success) {
                            const newItem = {
                                id: result.id || `srv${Date.now()}`,
                                name,
                                category,
                                defaultPrice: price
                            };

                            this.servicesData.services.push(newItem);

                            modal.classList.add('hidden');
                            form.reset();

                            if (typeof showToast === 'function') {
                                showToast('success', 'Sucesso', `Serviço "${name}" cadastrado!`);
                            }
                        } else {
                            throw new Error(result.error || 'Erro ao salvar');
                        }
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
        // Configurar autocomplete de fornecedores para todos os campos
        this.setupFornecedorAutocomplete();
    }

    // NOVO: Configurar autocomplete de fornecedores
    setupFornecedorAutocomplete() {
        const fornecedorInputs = document.querySelectorAll('.item-fornecedor-produto, .item-fornecedor-servico');
        console.log(`🔍 Configurando autocomplete para ${fornecedorInputs.length} campos`);
        console.log(`📦 ${this.fornecedores.length} fornecedores disponíveis`);

        fornecedorInputs.forEach((input, index) => {
            // Verificar se já tem event listener configurado
            if (input.dataset.autocompleteConfigured) {
                console.log(`⏭️ Campo ${index + 1} já configurado, pulando...`);
                return;
            }
            input.dataset.autocompleteConfigured = 'true';

            const dropdown = input.nextElementSibling;
            if (!dropdown || !dropdown.classList.contains('fornecedor-dropdown')) {
                console.error(`❌ Dropdown não encontrado para campo ${index + 1}`);
                return;
            }
            console.log(`✅ Campo ${index + 1} configurado com sucesso`);

            // Evento de input (digitação)
            input.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();

                if (query.length === 0) {
                    dropdown.classList.add('hidden');
                    return;
                }

                // Filtrar fornecedores
                const filtered = this.fornecedores.filter(f =>
                    f.nome.toLowerCase().includes(query)
                ).slice(0, 10); // Limitar a 10 resultados

                if (filtered.length === 0) {
                    dropdown.innerHTML = `
                        <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                            Nenhum fornecedor encontrado
                        </div>
                    `;
                    dropdown.classList.remove('hidden');
                    return;
                }

                // Renderizar sugestões
                dropdown.innerHTML = filtered.map(f => `
                    <div class="fornecedor-option px-4 py-2 hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                         data-nome="${f.nome}">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">${f.nome}</div>
                    </div>
                `).join('');

                // Adicionar event listeners nas opções (usar mousedown para capturar antes do blur)
                dropdown.querySelectorAll('.fornecedor-option').forEach(option => {
                    option.addEventListener('mousedown', (e) => {
                        e.preventDefault(); // Prevenir blur
                        input.value = option.dataset.nome;
                        dropdown.classList.add('hidden');
                        input.focus(); // Manter foco no input
                        console.log('✅ Fornecedor selecionado:', option.dataset.nome);
                    });
                });

                dropdown.classList.remove('hidden');
            });

            // Fechar dropdown ao clicar fora
            input.addEventListener('blur', () => {
                setTimeout(() => dropdown.classList.add('hidden'), 300);
            });

            // Abrir dropdown ao focar (mostrar todos se já tiver valor)
            input.addEventListener('focus', (e) => {
                if (e.target.value.trim().length > 0) {
                    e.target.dispatchEvent(new Event('input'));
                }
            });
        });
    }

    getItems() {
        const rows = document.querySelectorAll('.item-row');
        const items = [];

        rows.forEach(row => {
            const type = row.querySelector('.item-type').value;
            const descriptionInput = row.querySelector('.item-description');
            const description = descriptionInput.value.trim();
            const category = row.querySelector('.item-category').value;
            const fornecedorProduto = row.querySelector('.item-fornecedor-produto').value.trim();
            const fornecedorServico = row.querySelector('.item-fornecedor-servico').value.trim();
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const value = parseFloat(row.querySelector('.item-value').value.replace(',', '.')) || 0;
            const occurrence = row.querySelector('.item-occurrence').value;
            // Dados adicionais da peça (código e tipo original/similar)
            const codigo = descriptionInput.dataset.codigo || '';
            const tipoPeca = descriptionInput.dataset.tipoPeca || '';

            if (type && description) {
                items.push({
                    type: type === 'service' ? 'Serviço' : 'Produto',
                    description,
                    category,
                    fornecedor_produto: fornecedorProduto,
                    fornecedor_servico: fornecedorServico,
                    qty,
                    value,
                    occurrence,
                    total: qty * value,
                    codigo: codigo,
                    tipoPeca: tipoPeca
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
