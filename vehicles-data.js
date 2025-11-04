// Lista de veículos extraída do banco de dados (Vehicles.sql)
// Usado como fallback quando a API Ituran está lenta
const VEHICLES_LOCAL_DATA = [
    { plate: 'RTA9J00', model: 'STRADA 1.4 Endurance', status: 'active' },
    { plate: 'RQT8J28', model: 'STRADA 1.4 Endurance', status: 'active' },
    { plate: 'SGD9B96', model: 'VW 11.180', status: 'active' },
    { plate: 'PPG4B36', model: 'MONTANA', status: 'active' },
    { plate: 'MTQ7J93', model: 'CELTA', status: 'active' },
    { plate: 'OVE4358', model: 'CLASSIC', status: 'active' },
    { plate: 'RNH0A91', model: 'L200', status: 'active' },
    { plate: 'RTB4D56', model: 'STRADA 1.4 Endurance', status: 'active' },
    { plate: 'RQS3I74', model: 'STRADA 1.4 Endurance', status: 'active' },
    { plate: 'RTS9B92', model: 'STRADA 1.4 Endurance', status: 'active' }
];

// Função para obter lista local de veículos
function getLocalVehiclesList() {
    console.log(`📦 Usando lista LOCAL de veículos (${VEHICLES_LOCAL_DATA.length} veículos)`);
    return VEHICLES_LOCAL_DATA;
}

// Expõe globalmente
window.getLocalVehiclesList = getLocalVehiclesList;
window.VEHICLES_LOCAL_DATA = VEHICLES_LOCAL_DATA;
