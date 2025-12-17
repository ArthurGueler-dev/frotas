// Cliente API para FleetFlow
// Em produção: usa URL relativa (resolve para frotas.in9automacao.com.br)
// Em desenvolvimento: usa URL relativa (resolve para localhost:5000)
const API_BASE_URL = '/api';

class FleetAPI {
    // Estatísticas
    static async getStats() {
        try {
            const response = await fetch(`${API_BASE_URL}/stats`);
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar estatísticas:', error);
            return null;
        }
    }

    // Veículos
    static async getVehicles(status = null) {
        try {
            const url = status ? `${API_BASE_URL}/vehicles?status=${status}` : `${API_BASE_URL}/vehicles`;
            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar veículos:', error);
            return [];
        }
    }

    static async getVehicle(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/vehicles/${id}`);
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar veículo:', error);
            return null;
        }
    }

    static async createVehicle(vehicleData) {
        try {
            const response = await fetch(`${API_BASE_URL}/vehicles`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(vehicleData)
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao criar veículo:', error);
            return null;
        }
    }

    static async updateVehicle(id, vehicleData) {
        try {
            const response = await fetch(`${API_BASE_URL}/vehicles/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(vehicleData)
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao atualizar veículo:', error);
            return null;
        }
    }

    static async deleteVehicle(id) {
        try {
            const response = await fetch(`${API_BASE_URL}/vehicles/${id}`, {
                method: 'DELETE'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao deletar veículo:', error);
            return null;
        }
    }

    // Manutenções
    static async getMaintenances(status = null) {
        try {
            const url = status ? `${API_BASE_URL}/maintenances?status=${status}` : `${API_BASE_URL}/maintenances`;
            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar manutenções:', error);
            return [];
        }
    }

    static async createMaintenance(maintenanceData) {
        try {
            const response = await fetch(`${API_BASE_URL}/maintenances`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(maintenanceData)
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao criar manutenção:', error);
            return null;
        }
    }

    // Motoristas
    static async getDrivers() {
        try {
            console.log('📡 Buscando motoristas do banco de dados...');

            // PRIMEIRO: Tentar buscar do PHP (banco de dados MySQL)
            try {
                const url = 'https://floripa.in9automacao.com.br/get-drivers.php';
                console.log('🌐 Tentando acessar:', url);

                const response = await fetch(url);
                console.log('📥 Resposta HTTP:', response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('📦 JSON recebido:', result);

                if (result.success && result.data) {
                    console.log(`✅ ${result.count} motoristas carregados do banco de dados MySQL`);
                    console.log('📊 Dados recebidos:', result.data);

                    // Mapear dados do banco para formato esperado pela aplicação
                    const drivers = result.data.map(driver => ({
                        id: driver.id,
                        name: driver.name, // Corrigido: era fullName, agora é name
                        firstName: driver.firstName,
                        lastName: driver.lastName,
                        cpf: driver.cpf || 'N/A',
                        cnhNumber: driver.cnhNumber || 'N/A',
                        status: driver.status || 'Disponível',
                        cnhStatus: driver.cnhStatus || 'N/A',
                        cnhCategory: driver.cnhCategory || 'N/A',
                        cnhExpiry: driver.cnhExpiry || 'N/A',
                        admissionDate: driver.admissionDate || 'N/A',
                        birthDate: driver.birthDate || 'N/A'
                    }));

                    // Salvar no localStorage como cache
                    localStorage.setItem('drivers', JSON.stringify(drivers));

                    return drivers;
                } else {
                    console.warn('⚠️ Resposta do PHP sem sucesso:', result);
                    throw new Error(result.error || 'Erro ao buscar motoristas');
                }

            } catch (phpError) {
                console.warn('⚠️ Erro ao buscar do PHP:', phpError.message);

                // FALLBACK 1: Tentar JSON mock (desenvolvimento local)
                try {
                    console.log('🧪 Tentando usar dados mock (desenvolvimento local)...');
                    const mockResponse = await fetch('drivers-mock.json');

                    if (mockResponse.ok) {
                        const mockResult = await mockResponse.json();
                        console.log('✅ Usando dados MOCK para desenvolvimento local');
                        console.log(`📊 ${mockResult.count} motoristas carregados do arquivo mock`);

                        // Salvar no localStorage como cache
                        localStorage.setItem('drivers', JSON.stringify(mockResult.data));

                        return mockResult.data;
                    }
                } catch (mockError) {
                    console.warn('⚠️ Arquivo mock não encontrado:', mockError.message);
                }

                // FALLBACK 2: Tentar carregar do localStorage (cache)
                const cachedDrivers = localStorage.getItem('drivers');
                if (cachedDrivers) {
                    console.log('📦 Usando motoristas do cache local');
                    return JSON.parse(cachedDrivers);
                }

                // ÚLTIMO RECURSO: Retornar array vazio
                console.error('❌ Nenhuma fonte de dados disponível');
                return [];
            }

        } catch (error) {
            console.error('❌ Erro ao buscar motoristas:', error);
            return [];
        }
    }

    static async createDriver(driverData) {
        try {
            const response = await fetch(`${API_BASE_URL}/drivers`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(driverData)
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao criar motorista:', error);
            return null;
        }
    }

    // Alertas
    static async getAlerts() {
        try {
            const response = await fetch(`${API_BASE_URL}/alerts`);
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar alertas:', error);
            return [];
        }
    }
}
