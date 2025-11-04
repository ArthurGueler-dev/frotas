// Script para converter Excel para JSON
const XLSX = require('xlsx');
const fs = require('fs');

try {
    console.log('📖 Lendo arquivo modelos_carros.xlsx...');

    // Lê o arquivo Excel
    const workbook = XLSX.readFile('modelos_carros.xlsx');

    // Pega a primeira planilha
    const sheetName = workbook.SheetNames[0];
    console.log(`📄 Planilha: ${sheetName}`);

    const worksheet = workbook.Sheets[sheetName];

    // Converte para array (sem headers, pega valores crus)
    const data = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

    console.log(`📊 Total de linhas: ${data.length}`);
    console.log('');
    console.log('📋 Primeiras 5 linhas (cru):');
    data.slice(0, 5).forEach((row, i) => {
        console.log(`Linha ${i}: [${row.join(' | ')}]`);
    });
    console.log('');

    // Analisa a estrutura para encontrar as colunas de placa e modelo
    console.log('🔍 Analisando estrutura...');
    if (data.length > 0) {
        console.log(`   Linha 0 (${data[0].length} colunas): ${data[0].join(' | ')}`);
        console.log(`   Linha 1 (${data[1].length} colunas): ${data[1].join(' | ')}`);
    }
    console.log('');

    // Cria objeto mapeando placa -> modelo
    const vehicleModels = {};

    // Pula a primeira linha (cabeçalhos) e processa os dados
    // Assumindo que as colunas são: [coluna0, coluna1, coluna2, PLACA, coluna4, MODELO]
    // Vou tentar identificar automaticamente qual coluna tem placa (7 caracteres alfanuméricos)

    for (let i = 1; i < data.length; i++) {
        const row = data[i];

        if (!row || row.length === 0) continue;

        // Tenta encontrar placa (formato: 7 caracteres, ex: RNQ2H54)
        let plateIndex = -1;
        let modelIndex = -1;

        for (let j = 0; j < row.length; j++) {
            const cell = String(row[j] || '').trim();

            // Placa: 7 caracteres alfanuméricos
            if (cell.length === 7 && /^[A-Z0-9]{7}$/i.test(cell) && plateIndex === -1) {
                plateIndex = j;
            }

            // Modelo: texto com / ou espaço (ex: "FIAT/MOBI LIKE" ou "CHEVROLET S10")
            if ((cell.includes('/') || cell.includes(' ')) && cell.length > 5 && modelIndex === -1) {
                modelIndex = j;
            }
        }

        if (plateIndex !== -1 && modelIndex !== -1) {
            const plate = String(row[plateIndex]).trim();
            const model = String(row[modelIndex]).trim();
            vehicleModels[plate] = model;
        }
    }

    console.log(`✅ ${Object.keys(vehicleModels).length} veículos mapeados`);
    console.log('');
    console.log('📋 Exemplo de mapeamento:');
    const firstFive = Object.entries(vehicleModels).slice(0, 5);
    firstFive.forEach(([plate, model]) => {
        console.log(`   ${plate} -> ${model}`);
    });
    console.log('');

    // Salva no formato JSON
    const output = JSON.stringify(vehicleModels, null, 2);
    fs.writeFileSync('vehicle-models.json', output, 'utf8');

    console.log('✅ Arquivo vehicle-models.json criado com sucesso!');
    console.log(`📁 Total de ${Object.keys(vehicleModels).length} veículos salvos`);

} catch (error) {
    console.error('❌ Erro:', error.message);
    console.error('');
    console.error('Certifique-se de que:');
    console.error('1. O arquivo modelos_carros.xlsx existe');
    console.error('2. O módulo xlsx está instalado (npm install xlsx)');
    process.exit(1);
}
