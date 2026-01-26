// Sistema de Impressão de Ordem de Serviço
// Gera versão profissional para impressão

class OSPrinter {
    constructor() {
        this.companyInfo = {
            name: 'i9 Engenharia',
            address: 'Endereço da Empresa',
            phone: '(27) 99687-8688',
            email: 'frota@in9automacao.com.br'
        };
    }

    /**
     * Imprime uma OS existente (já salva)
     * @param {number|string} osNumber - ID ou Número da OS
     */
    async printExistingOS(osNumber) {
        try {
            // Determinar se é ID numérico ou ordem_numero string
            const isId = !isNaN(osNumber) && !String(osNumber).includes('OS-');
            const param = isId ? `id=${osNumber}` : `ordem_numero=${osNumber}`;

            // Buscar dados da OS do servidor (incluindo itens)
            const response = await fetch(`https://floripa.in9automacao.com.br/get-workorders.php?${param}&with_items=true`);
            const result = await response.json();

            console.log('📄 Dados da OS recebidos:', result);

            if (!result.success) {
                throw new Error('OS não encontrada');
            }

            // Usar printOS para formatar corretamente os dados (incluindo fornecedor_servico_dados)
            this.printOS(result.data);
        } catch (error) {
            console.error('Erro ao imprimir OS:', error);
            alert('Erro ao carregar OS para impressão: ' + error.message);
        }
    }

    /**
     * Imprime a OS atual do formulário (antes de salvar)
     */
    async printCurrentForm() {
        const osData = await this.collectFormData();
        this.generateAndPrint(osData);
    }

    /**
     * Apenas visualiza a OS atual do formulário (sem abrir diálogo de impressão)
     */
    async previewCurrentForm() {
        const osData = await this.collectFormData();
        this.generateAndPreview(osData);
    }

    /**
     * Imprime uma OS a partir de um objeto de dados
     * @param {Object} osData - Objeto com dados da OS
     */
    printOS(osData) {
        console.log('🖨️ Imprimindo OS com dados:', osData);

        // Definir data de abertura
        const dataAbertura = osData.dateTime || osData.data_criacao || new Date().toISOString().split('T')[0];
        console.log('📅 Data de abertura:', dataAbertura, '| Tipo:', typeof dataAbertura);

        // Dados do fornecedor de serviço (se disponível)
        const fornecedorDados = osData.fornecedor_servico_dados || {};

        // Mapear dados para o formato esperado pelo generateHTML
        const formattedData = {
            numero: osData.osNumber || osData.ordem_numero,
            data_abertura: dataAbertura,
            status: osData.status || 'Aberta',
            veiculo: {
                placa: osData.plate || osData.placa_veiculo,
                modelo: osData.vehicleModel || osData.veiculo_modelo_nome || osData.modelo_veiculo || '',
                marca: osData.veiculo_marca || '',
                ano: osData.veiculo_ano || '',
                km: osData.km || osData.km_veiculo
            },
            fornecedor_servico: {
                nome: fornecedorDados.nome || '',
                razao_social: fornecedorDados.razao_social || '',
                cnpj: fornecedorDados.cnpj || '',
                endereco: fornecedorDados.endereco || '',
                bairro: fornecedorDados.bairro || '',
                cidade: fornecedorDados.cidade || '',
                estado: fornecedorDados.estado || '',
                cep: fornecedorDados.cep || '',
                telefone: fornecedorDados.telefone || ''
            },
            motorista: osData.driverName || osData.motorista || '',
            descricao: osData.observacoes || osData.defeito_reclamado || '',
            itens: (osData.items || osData.itens || []).map(item => ({
                tipo: item.type === 'Serviço' || item.tipo === 'Serviço' ? 'service' : 'product',
                categoria: item.category || item.categoria || '-',
                descricao: item.description || item.descricao || '-',
                codigo: item.codigo || item.codigo_peca || '-',
                fornecedor_produto: item.fornecedor_produto || '-',
                fornecedor_servico: item.fornecedor_servico || '-',
                quantidade: item.qty || item.quantidade || 1,
                valor_unitario: item.value || item.valor_unitario || 0,
                valor: item.total || item.valor_total || (item.qty * item.value) || 0
            })),
            totais: {
                produtos: 0,
                servicos: 0,
                geral: osData.totalGeral || 0
            }
        };

        // Calcular totais se não fornecidos
        if (formattedData.itens.length > 0) {
            formattedData.itens.forEach(item => {
                const valor = parseFloat(item.valor) || 0;
                if (item.tipo === 'product') {
                    formattedData.totais.produtos += valor;
                } else if (item.tipo === 'service') {
                    formattedData.totais.servicos += valor;
                }
            });
            formattedData.totais.geral = formattedData.totais.produtos + formattedData.totais.servicos;
        }

        this.generateAndPrint(formattedData);
    }

    /**
     * Busca dados completos do fornecedor pelo nome
     */
    async fetchFornecedorDados(nomeFornecedor) {
        console.log('🔍 fetchFornecedorDados chamado com:', nomeFornecedor);

        if (!nomeFornecedor || nomeFornecedor === '-') {
            console.log('❌ Nome do fornecedor vazio ou inválido');
            return null;
        }

        try {
            const url = `https://floripa.in9automacao.com.br/fornecedores-api.php?busca=${encodeURIComponent(nomeFornecedor)}`;
            console.log('🌐 Buscando fornecedor na URL:', url);

            const response = await fetch(url);
            const result = await response.json();

            console.log('📦 Resposta da API:', result);

            if (result.success && result.data && result.data.length > 0) {
                console.log('✅ Fornecedor encontrado:', result.data[0]);
                return result.data[0];
            } else {
                console.log('⚠️ Fornecedor não encontrado na busca');
            }
        } catch (error) {
            console.error('❌ Erro ao buscar dados do fornecedor:', error);
        }
        return null;
    }

    /**
     * Coleta dados do formulário atual
     */
    async collectFormData() {
        console.log('📝 Coletando dados do formulário para impressão...');

        // Dados básicos
        const osNumberInput = document.getElementById('os-number-input');
        const osNumber = osNumberInput ? osNumberInput.value : 'SEM NÚMERO';
        console.log('📄 Número da OS:', osNumber);

        const dateTimeInput = document.getElementById('datetime-input');
        const dateTime = dateTimeInput ? dateTimeInput.value : '';
        // Converter datetime-local para data simples, ou usar data atual
        const openDate = dateTime ? dateTime.split('T')[0] : new Date().toISOString().split('T')[0];

        const vehiclePlateInput = document.getElementById('vehicle-plate-input');
        const vehiclePlate = vehiclePlateInput ? vehiclePlateInput.value.trim().toUpperCase() : '';
        console.log('🚗 Placa capturada:', vehiclePlate);

        const kmInput = document.getElementById('km-input');
        const vehicleKm = kmInput ? kmInput.value : '';
        console.log('📏 KM capturada:', vehicleKm);

        const observacoesTextarea = document.getElementById('observacoes-textarea');
        const description = observacoesTextarea ? observacoesTextarea.value : '';

        // Buscar dados do veículo
        let vehicleModel = '';
        let vehicleBrand = '';
        let vehicleYear = '';
        let driverName = '';

        console.log('🔍 Buscando modelo do veículo...');

        // Primeira tentativa: usar o modelo armazenado no campo oculto
        const hiddenModelInput = document.getElementById('vehicle-model-hidden');
        if (hiddenModelInput && hiddenModelInput.value) {
            vehicleModel = hiddenModelInput.value;
            console.log('✅ Modelo obtido do campo oculto:', vehicleModel);
        }
        // Segunda tentativa: usar a variável global window.selectedVehicleModel
        else if (window.selectedVehicleModel) {
            vehicleModel = window.selectedVehicleModel;
            console.log('✅ Modelo obtido de window.selectedVehicleModel:', vehicleModel);
        }
        // Terceira tentativa: buscar na lista de veículos
        if (window.vehicles && window.vehicles.length > 0 && vehiclePlate) {
            console.log('🔍 Procurando veículo na lista com placa:', vehiclePlate);

            const vehicle = window.vehicles.find(v => {
                const vPlate = v.plate ? v.plate.trim().toUpperCase() : '';
                return vPlate === vehiclePlate;
            });

            if (vehicle) {
                console.log('✅ Veículo encontrado:', vehicle);
                vehicleBrand = vehicle.brand || '';
                if (!vehicleModel) {
                    vehicleModel = vehicle.model || '';
                }
                vehicleYear = vehicle.year || '';
                driverName = vehicle.driver || '';
                console.log('🚗 Dados do veículo:', { vehicleBrand, vehicleModel, vehicleYear });
            } else {
                console.error('❌ Veículo NÃO encontrado na lista');
            }
        } else {
            console.warn('⚠️ Não foi possível obter o modelo do veículo');
        }

        // Itens (usando osManager se disponível)
        let items = [];
        if (window.osManager) {
            const rawItems = window.osManager.getItems();
            console.log('📦 Itens capturados via osManager:', rawItems);

            // Mapear para o formato esperado
            items = rawItems.map(item => {
                const valorUnitario = parseFloat(item.value) || 0;
                const quantidade = parseFloat(item.qty) || 1;
                const total = parseFloat(item.total) || (valorUnitario * quantidade);

                return {
                    tipo: item.type === 'Serviço' ? 'service' : 'product',
                    categoria: item.category || '-',
                    descricao: item.description || '-',
                    codigo: item.codigo || '-',
                    fornecedor_produto: item.fornecedor_produto || '-',
                    fornecedor_servico: item.fornecedor_servico || '-',
                    quantidade: quantidade,
                    valor_unitario: valorUnitario,
                    valor: total
                };
            });
        } else {
            // Fallback: ler diretamente do DOM
            items = this.getItemsFromDOM();
            console.log('📦 Itens capturados via DOM:', items);
        }

        console.log('📦 Itens mapeados para impressão:', items);

        // Buscar fornecedor de serviço (de qualquer item que tenha esse campo preenchido)
        let fornecedorServicoNome = null;
        console.log('🔍 Procurando fornecedor de serviço nos itens...');
        for (const item of items) {
            console.log(`  Item: tipo=${item.tipo}, fornecedor_servico="${item.fornecedor_servico}"`);
            if (item.fornecedor_servico && item.fornecedor_servico !== '-' && item.fornecedor_servico.trim() !== '') {
                fornecedorServicoNome = item.fornecedor_servico.trim();
                console.log(`  ✅ Encontrado fornecedor de serviço: "${fornecedorServicoNome}"`);
                break;
            }
        }
        console.log('📋 Fornecedor de serviço final:', fornecedorServicoNome || '(nenhum)');

        // Buscar dados completos do fornecedor de serviço
        let fornecedorServicoDados = {};
        if (fornecedorServicoNome) {
            console.log('🔍 Buscando dados do fornecedor:', fornecedorServicoNome);
            const dadosForn = await this.fetchFornecedorDados(fornecedorServicoNome);
            if (dadosForn) {
                fornecedorServicoDados = dadosForn;
                console.log('✅ Dados do fornecedor encontrados:', dadosForn);
            }
        }

        // Calcular totais
        let totalProdutos = 0;
        let totalServicos = 0;

        items.forEach((item, index) => {
            console.log(`Item ${index + 1}:`, item);
            const valor = parseFloat(item.valor) || 0;
            console.log(`  - Tipo: ${item.tipo}, Categoria: ${item.categoria}, Descrição: ${item.descricao}, Valor: ${valor}`);

            if (item.tipo === 'product') {
                totalProdutos += valor;
            } else if (item.tipo === 'service') {
                totalServicos += valor;
            }
        });

        console.log('💰 Total Produtos:', totalProdutos);
        console.log('💰 Total Serviços:', totalServicos);

        return {
            numero: osNumber,
            data_abertura: openDate,
            status: 'Aberta',
            veiculo: {
                placa: vehiclePlate,
                modelo: vehicleModel,
                marca: vehicleBrand,
                ano: vehicleYear,
                km: vehicleKm
            },
            fornecedor_servico: {
                nome: fornecedorServicoDados.nome || fornecedorServicoNome || '',
                razao_social: fornecedorServicoDados.razao_social || '',
                cnpj: fornecedorServicoDados.cnpj || '',
                endereco: fornecedorServicoDados.endereco || '',
                bairro: fornecedorServicoDados.bairro || '',
                cidade: fornecedorServicoDados.cidade || '',
                estado: fornecedorServicoDados.estado || '',
                cep: fornecedorServicoDados.cep || '',
                telefone: fornecedorServicoDados.telefone || ''
            },
            motorista: driverName,
            descricao: description,
            itens: items,
            totais: {
                produtos: totalProdutos,
                servicos: totalServicos,
                geral: totalProdutos + totalServicos
            }
        };
    }

    /**
     * Lê itens diretamente do DOM (fallback)
     */
    getItemsFromDOM() {
        const items = [];
        const rows = document.querySelectorAll('.item-row');

        rows.forEach(row => {
            const tipo = row.querySelector('.item-type').value;
            const categoria = row.querySelector('.item-category').value;
            const descricaoInput = row.querySelector('.item-description');
            const descricao = descricaoInput?.value.trim() || '';
            const codigo = descricaoInput?.dataset?.codigo || '-';
            const fornecedorProduto = row.querySelector('.item-fornecedor-produto')?.value.trim() || '';
            const fornecedorServico = row.querySelector('.item-fornecedor-servico')?.value.trim() || '';
            const quantidade = row.querySelector('.item-quantity')?.value || row.querySelector('.item-qty')?.value || 1;
            const valorUnit = row.querySelector('.item-unit-price')?.value || row.querySelector('.item-value')?.value || 0;
            const valorTotal = row.querySelector('.item-total-price')?.value || row.querySelector('.item-total')?.textContent?.replace(/[^\d,.-]/g, '').replace(',', '.') || 0;

            if (descricao) {
                items.push({
                    tipo: tipo,
                    categoria: categoria,
                    descricao: descricao,
                    codigo: codigo,
                    fornecedor_produto: fornecedorProduto,
                    fornecedor_servico: fornecedorServico,
                    quantidade: quantidade,
                    valor_unitario: valorUnit,
                    valor: valorTotal
                });
            }
        });

        return items;
    }

    /**
     * Gera HTML e abre janela de impressão
     */
    generateAndPrint(osData) {
        const html = this.generateHTML(osData);

        // Criar janela de impressão
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(html);
        printWindow.document.close();

        // Aguardar carregamento e imprimir
        printWindow.onload = () => {
            setTimeout(() => {
                printWindow.print();
            }, 250);
        };
    }

    /**
     * Gera HTML e abre janela APENAS para visualização (sem impressão automática)
     */
    generateAndPreview(osData) {
        const html = this.generateHTML(osData);

        // Criar janela de visualização
        const previewWindow = window.open('', '_blank', 'width=900,height=700');
        previewWindow.document.write(html);
        previewWindow.document.close();

        // Não chama print() - apenas visualização
    }

    /**
     * Gera HTML completo para impressão - NOVO DESIGN v3
     */
    generateHTML(osData) {
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            let date;
            if (dateStr instanceof Date) {
                date = dateStr;
            } else if (typeof dateStr === 'string' && dateStr.includes('T')) {
                date = new Date(dateStr);
            } else if (typeof dateStr === 'string' && dateStr.includes(' ')) {
                date = new Date(dateStr.replace(' ', 'T'));
            } else if (typeof dateStr === 'string' && dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
                date = new Date(dateStr + 'T00:00:00');
            } else {
                date = new Date(dateStr);
            }
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleDateString('pt-BR');
        };

        const formatCurrency = (value) => {
            const num = parseFloat(value) || 0;
            return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        };

        // Separar serviços e produtos
        const servicos = (osData.itens || []).filter(item => item.tipo === 'service' || item.tipo === 'Serviço');
        const produtos = (osData.itens || []).filter(item => item.tipo === 'product' || item.tipo === 'Produto');

        // Dados do fornecedor de serviço
        const forn = osData.fornecedor_servico || {};
        const fornNome = forn.nome || forn.razao_social || '-';
        const fornEndereco = forn.endereco || '-';
        const fornBairro = forn.bairro || '-';
        const fornCidade = forn.cidade || '-';
        const fornEstado = forn.estado || '-';
        const fornCep = forn.cep || '-';
        const fornCnpj = forn.cnpj || '-';

        // Calcular totais
        const totalServicos = servicos.reduce((sum, item) => sum + (parseFloat(item.valor) || parseFloat(item.valor_total) || 0), 0);
        const totalProdutos = produtos.reduce((sum, item) => sum + (parseFloat(item.valor) || parseFloat(item.valor_total) || 0), 0);
        const totalGeral = totalServicos + totalProdutos;

        // Status da OS
        const status = osData.status || 'Aberta';

        // Dados do veículo
        const veiculoPlaca = osData.veiculo?.placa || '-';
        const veiculoKm = osData.veiculo?.km ? parseInt(osData.veiculo.km).toLocaleString('pt-BR') + ' km' : '-';
        const veiculoAno = osData.veiculo?.ano || '-';
        const veiculoModelo = osData.veiculo?.modelo || '-';
        const veiculoMarca = osData.veiculo?.marca || '';

        return `
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OS ${osData.numero} - Impressão</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
            padding: 5mm;
        }
        .container { max-width: 100%; }

        /* Cabeçalho Fornecedor */
        .header-fornecedor {
            border: 2px solid #1a365d;
            padding: 6px 8px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
        }
        .fornecedor-info {
            flex: 1;
        }
        .fornecedor-info h2 {
            font-size: 9pt;
            margin-bottom: 3px;
            color: #1a365d;
            text-transform: uppercase;
            border-bottom: 1px solid #1a365d;
            padding-bottom: 2px;
        }
        .fornecedor-info .forn-nome {
            font-size: 10pt;
            font-weight: bold;
            margin: 3px 0;
            color: #000;
        }
        .fornecedor-info .forn-dados {
            font-size: 7pt;
            margin: 1px 0;
            color: #333;
        }
        .fornecedor-info .forn-dados strong {
            color: #666;
            font-weight: normal;
            min-width: 50px;
            display: inline-block;
        }
        .os-numero {
            text-align: right;
            min-width: 140px;
            padding-left: 10px;
            border-left: 1px solid #ccc;
        }
        .os-numero .label {
            font-size: 7pt;
            color: #666;
        }
        .os-numero .numero {
            font-size: 14pt;
            font-weight: bold;
            color: #1a365d;
        }
        .os-numero .data {
            font-size: 7pt;
            color: #333;
            margin-top: 3px;
        }

        /* Seção Veículo */
        .secao {
            border: 1px solid #ccc;
            margin-bottom: 5px;
            padding: 5px;
        }
        .secao-titulo {
            font-size: 8pt;
            font-weight: bold;
            color: #fff;
            background: #1a365d;
            padding: 2px 5px;
            margin: -5px -5px 5px -5px;
        }
        .veiculo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }
        .veiculo-item label {
            font-size: 5pt;
            color: #666;
            text-transform: uppercase;
            display: block;
        }
        .veiculo-item span {
            font-size: 8pt;
            font-weight: bold;
        }

        /* Tabelas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            font-size: 6.5pt;
        }
        th {
            background: #2c5282;
            color: white;
            padding: 3px 2px;
            text-align: left;
            font-size: 6.5pt;
            font-weight: bold;
        }
        td {
            padding: 2px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        tr:nth-child(even) { background: #f7fafc; }
        .col-desc { width: 35%; }
        .col-tipo { width: 10%; text-align: center; }
        .col-qtd { width: 6%; text-align: center; }
        .col-valor { width: 12%; text-align: right; }
        .col-total { width: 12%; text-align: right; font-weight: bold; }
        .col-forn { width: 25%; font-size: 6pt; padding-left: 8px; }

        /* Status */
        .status-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
            padding: 5px;
            border: 1px solid #1a365d;
            background: #f8fafc;
        }
        .status-box {
            display: flex;
            gap: 3px;
        }
        .status-item {
            padding: 2px 6px;
            font-size: 5.5pt;
            border: 1px solid #ccc;
            border-radius: 2px;
            background: #fff;
        }
        .status-item.active {
            background: #1a365d;
            color: white;
            border-color: #1a365d;
            font-weight: bold;
        }

        /* Totais */
        .totais-box {
            text-align: right;
        }
        .totais-box .linha {
            font-size: 7pt;
            margin: 1px 0;
        }
        .totais-box .total-geral {
            font-size: 9pt;
            font-weight: bold;
            color: #1a365d;
            border-top: 2px solid #1a365d;
            padding-top: 2px;
            margin-top: 2px;
        }

        /* Assinatura */
        .assinatura-footer {
            position: fixed;
            bottom: 10mm;
            left: 5mm;
            right: 5mm;
        }
        .assinatura-box {
            width: 50%;
            margin: 0 auto;
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid #000;
        }
        .assinatura-box span {
            font-size: 7pt;
            color: #333;
        }

        @media print {
            body { padding: 3mm; }
            .secao { page-break-inside: avoid; }
        }
        @page { size: A4; margin: 3mm; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho com Fornecedor de Serviço -->
        <div class="header-fornecedor">
            <div class="fornecedor-info">
                <h2>Fornecedor de Serviço</h2>
                <p class="forn-nome">${fornNome}</p>
                <p class="forn-dados"><strong>Endereço:</strong> ${fornEndereco}</p>
                <p class="forn-dados"><strong>Bairro:</strong> ${fornBairro} | <strong>Cidade:</strong> ${fornCidade} | <strong>UF:</strong> ${fornEstado} | <strong>CEP:</strong> ${fornCep}</p>
                <p class="forn-dados"><strong>CNPJ/CPF:</strong> ${fornCnpj}</p>
            </div>
            <div class="os-numero">
                <div class="label">ORDEM DE SERVIÇO</div>
                <div class="numero">${osData.numero || '---'}</div>
                <div class="data">Data: ${formatDate(osData.data_abertura)}</div>
            </div>
        </div>

        <!-- Dados do Veículo -->
        <div class="secao">
            <div class="secao-titulo">DADOS DO VEÍCULO</div>
            <div class="veiculo-grid">
                <div class="veiculo-item">
                    <label>Placa</label>
                    <span>${veiculoPlaca}</span>
                </div>
                <div class="veiculo-item">
                    <label>Quilometragem</label>
                    <span>${veiculoKm}</span>
                </div>
                <div class="veiculo-item">
                    <label>Ano / Modelo</label>
                    <span>${veiculoAno} ${veiculoMarca ? veiculoMarca + ' ' : ''}${veiculoModelo}</span>
                </div>
            </div>
        </div>

        <!-- Seção de Serviços -->
        <div class="secao">
            <div class="secao-titulo">SERVIÇOS (${servicos.length})</div>
            <table>
                <thead>
                    <tr>
                        <th class="col-desc">Descrição</th>
                        <th class="col-tipo">Tipo</th>
                        <th class="col-qtd">Qtd</th>
                        <th class="col-valor">Valor Unit.</th>
                        <th class="col-total">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${servicos.length > 0 ? servicos.map(item => {
                        const qtd = parseFloat(item.quantidade) || 1;
                        const valorUnit = parseFloat(item.valor_unitario) || parseFloat(item.valor) || 0;
                        const total = qtd * valorUnit;
                        return `
                        <tr>
                            <td class="col-desc">${item.descricao || '-'}</td>
                            <td class="col-tipo">Serviço</td>
                            <td class="col-qtd">${qtd}</td>
                            <td class="col-valor">${formatCurrency(valorUnit)}</td>
                            <td class="col-total">${formatCurrency(total)}</td>
                        </tr>
                    `}).join('') : '<tr><td colspan="5" style="text-align:center;color:#999;padding:8px;">Nenhum serviço</td></tr>'}
                </tbody>
            </table>
        </div>

        <!-- Seção de Produtos/Peças -->
        <div class="secao">
            <div class="secao-titulo">PRODUTOS / PEÇAS (${produtos.length})</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:12%;">Código</th>
                        <th class="col-desc">Descrição</th>
                        <th class="col-qtd">Qtd</th>
                        <th class="col-valor">Valor Unit.</th>
                        <th class="col-total">Total</th>
                        <th class="col-forn">Fornecedor</th>
                    </tr>
                </thead>
                <tbody>
                    ${produtos.length > 0 ? produtos.map(item => {
                        const qtd = parseFloat(item.quantidade) || 1;
                        const valorUnit = parseFloat(item.valor_unitario) || parseFloat(item.valor) || 0;
                        const total = qtd * valorUnit;
                        return `
                        <tr>
                            <td style="font-size:5.5pt;">${item.codigo || '-'}</td>
                            <td class="col-desc">${item.descricao || '-'}</td>
                            <td class="col-qtd">${qtd}</td>
                            <td class="col-valor">${formatCurrency(valorUnit)}</td>
                            <td class="col-total">${formatCurrency(total)}</td>
                            <td class="col-forn">${item.fornecedor_produto || '-'}</td>
                        </tr>
                    `}).join('') : '<tr><td colspan="6" style="text-align:center;color:#999;padding:8px;">Nenhum produto</td></tr>'}
                </tbody>
            </table>
        </div>

        <!-- Status e Totais -->
        <div class="status-section">
            <div class="status-box">
                <span class="status-item ${status === 'Aberta' ? 'active' : ''}">Aberta</span>
                <span class="status-item ${status === 'Diagnóstico' ? 'active' : ''}">Diagnóstico</span>
                <span class="status-item ${status === 'Orçamento' ? 'active' : ''}">Orçamento</span>
                <span class="status-item ${status === 'Execução' ? 'active' : ''}">Execução</span>
                <span class="status-item ${status === 'Finalizada' ? 'active' : ''}">Finalizada</span>
            </div>
            <div class="totais-box">
                <div class="linha">Total Serviços: <strong>${formatCurrency(totalServicos)}</strong></div>
                <div class="linha">Total Produtos: <strong>${formatCurrency(totalProdutos)}</strong></div>
                <div class="total-geral">TOTAL: ${formatCurrency(totalGeral)}</div>
            </div>
        </div>

        <!-- Assinatura no Footer -->
        <div class="assinatura-footer">
            <div class="assinatura-box">
                <span>Cliente / Recebedor</span>
            </div>
        </div>
    </div>
</body>
</html>
        `;
    }
}

// Instância global
window.osPrinter = new OSPrinter();

// Função helper para facilitar uso
function printCurrentOS() {
    window.osPrinter.printCurrentForm();
}

function printOSById(osNumber) {
    window.osPrinter.printExistingOS(osNumber);
}

function previewCurrentOS() {
    window.osPrinter.previewCurrentForm();
}
