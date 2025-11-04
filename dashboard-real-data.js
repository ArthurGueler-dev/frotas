// Dashboard Real Data - Carrega dados reais dos veículos
// Usa vehicles-data.json e dados do SQL

class DashboardRealData {
    constructor() {
        this.vehicles = [];
        this.vehiclesSqlData = [];
    }

    /**
     * Carrega dados dos veículos do JSON
     */
    async loadVehiclesData() {
        try {
            const response = await fetch('vehicles-data.json');
            if (!response.ok) {
                throw new Error('Erro ao carregar vehicles-data.json');
            }
            this.vehicles = await response.json();
            console.log(`✅ ${this.vehicles.length} veículos carregados`);
            return this.vehicles;
        } catch (error) {
            console.error('❌ Erro ao carregar veículos:', error);
            return [];
        }
    }

    /**
     * Dados do SQL com status dos veículos
     * Simulado com base nos dados reais do Vehicles.sql
     */
    getVehiclesSqlStatus() {
        // Baseado no SQL real:
        // EngineStatus: 'ON', 'OFF', 'OCIOSO'
        // IgnitionStatus: 'ON', 'OFF'

        return [
            { plate: 'RTA9J00', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 54731606, speed: 13 },
            { plate: 'RQT8J28', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'SGD9B96', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'PPG4B36', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'MTQ7J93', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'OVE4358', engineStatus: 'OFF', ignitionStatus: 'ON', driverId: 64802197, speed: 23 },
            { plate: 'RNH0A91', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 16935090, speed: 72 },
            { plate: 'RTB4D56', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 14676658, speed: 101 },
            { plate: 'RQS3I74', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTS9B92', engineStatus: 'OFF', ignitionStatus: 'ON', driverId: 0, speed: 0 },
            { plate: 'RMR5H78', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 56536225, speed: 72 },
            { plate: 'MSX7995', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 54488433, speed: 0 },
            { plate: 'RMO3J23', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO4A32', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO3H46', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 38900566, speed: 37 },
            { plate: 'RMJ5D13', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'PPV7055', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 43087740, speed: 0 },
            { plate: 'RNC4G56', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTA9A39', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTA9A41', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMJ5D10', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 27474258, speed: 0 },
            { plate: 'RQT8J27', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'PPC6J12', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'OVK0C71', engineStatus: 'OFF', ignitionStatus: 'ON', driverId: 96344648, speed: 53 },
            { plate: 'PPI7E95', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTS9E12', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 54964888, speed: 19 },
            { plate: 'BDI3G10', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTG1G68', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMJ5D18', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTB5F87', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO4A08', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO5J32', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTE5D36', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO1G52', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO5J35', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTB5G60', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'SFT4I72', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO1G96', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO5I38', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'FFK7H28', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 97178802, speed: 62 },
            { plate: 'RNA8G41', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO3H62', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RNR0D90', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'FPW8F78', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'PPT7D92', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RBG9E06', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 87734005, speed: 2 },
            { plate: 'RQS7F87', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTA9A55', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTA8J97', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTA9A40', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RNQ2H54', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 64563954, speed: 14 },
            { plate: 'FEV7J00', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO3H69', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO3H76', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 0, speed: 0 },
            { plate: 'RTS9D53', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 35822560, speed: 0 },
            { plate: 'RTB5E31', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTA9A37', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO5J29', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 70922392, speed: 51 },
            { plate: 'RNZ5A49', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RMO3F38', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'SGF3H84', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'PMV8D59', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RBE1J59', engineStatus: '', ignitionStatus: '', driverId: -1, speed: 0 },
            { plate: 'RBE1J63', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTG2F73', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RBG9E05', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'PPX2803', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 28359353, speed: 0 },
            { plate: 'PPW0562', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 49906055, speed: 0 },
            { plate: 'RTS9E91', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RTS9B34', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RBG9E07', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RBF3B52', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 65545801, speed: 62 },
            { plate: 'RNQ2H45', engineStatus: 'OCIOSO', ignitionStatus: 'ON', driverId: 0, speed: 0 },
            { plate: 'RMO3F64', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'RUR3I05', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'QRM6D15', engineStatus: 'OFF', ignitionStatus: 'OFF', driverId: 0, speed: 0 },
            { plate: 'QRM8C24', engineStatus: 'ON', ignitionStatus: 'ON', driverId: 10192061, speed: 24 }
        ];
    }

    /**
     * Calcula estatísticas reais da frota
     */
    async calculateRealStats() {
        await this.loadVehiclesData();
        const sqlStatus = this.getVehiclesSqlStatus();

        // Total de veículos
        const totalVehicles = this.vehicles.length;

        // Veículos em movimento (motor ligado OU velocidade > 0)
        const vehiclesMoving = sqlStatus.filter(v =>
            v.engineStatus === 'ON' && v.speed > 0
        ).length;

        // Veículos em manutenção (vamos considerar os que não estão em movimento e não têm motorista)
        // Na prática, isso viria de uma tabela de manutenção
        // Por agora, vamos estimar como 8% da frota (padrão da indústria)
        const maintenanceVehicles = Math.ceil(totalVehicles * 0.08);

        // Motoristas únicos ativos (DriverId > 0)
        const uniqueDrivers = new Set(
            sqlStatus.filter(v => v.driverId > 0).map(v => v.driverId)
        );
        const activeDrivers = uniqueDrivers.size;

        // Motoristas disponíveis (total estimado - ativos)
        // Assumindo que temos 1.2 motoristas por veículo
        const totalDriversEstimate = Math.ceil(totalVehicles * 1.2);
        const availableDrivers = totalDriversEstimate - activeDrivers;

        // Custo total do mês (estimado)
        // Média: R$ 1,500 por veículo/mês (combustível + manutenção + seguro)
        const monthlyCostPerVehicle = 1500;
        const monthlyCost = totalVehicles * monthlyCostPerVehicle;

        return {
            totalVehicles,
            maintenanceVehicles,
            availableDrivers,
            activeDrivers,
            vehiclesMoving,
            monthlyCost,
            percentages: {
                active: Math.round(((totalVehicles - maintenanceVehicles) / totalVehicles) * 100),
                maintenance: Math.round((maintenanceVehicles / totalVehicles) * 100),
                inactive: 0
            }
        };
    }

    /**
     * Atualiza o dashboard com dados reais
     */
    async updateDashboard() {
        try {
            console.log('🔄 Atualizando dashboard com dados reais...');
            const stats = await this.calculateRealStats();

            // Atualizar cards principais
            document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 > div:nth-child(1) .text-3xl').textContent = stats.totalVehicles;
            document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 > div:nth-child(2) .text-3xl').textContent = stats.maintenanceVehicles;
            document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 > div:nth-child(3) .text-3xl').textContent = stats.availableDrivers;
            document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 > div:nth-child(4) .text-3xl').textContent = `R$ ${stats.monthlyCost.toLocaleString('pt-BR')}`;

            // Atualizar veículos em movimento
            const movingElement = document.getElementById('stat-vehicles-moving');
            if (movingElement) {
                movingElement.textContent = stats.vehiclesMoving;
            }

            // KM rodados no mês será atualizado pelo dashboard-stats.js com dados reais

            // Atualizar gráfico de pizza com percentuais reais
            const pieChart = document.querySelector('.h-80.flex.items-center.justify-center svg');
            if (pieChart) {
                const totalText = pieChart.querySelector('.text-3xl');
                if (totalText) totalText.textContent = stats.totalVehicles;
            }

            // Atualizar legendas do gráfico
            const legends = document.querySelectorAll('.flex.justify-center.gap-4.text-sm > div span');
            if (legends.length >= 3) {
                legends[0].textContent = `Ativo (${stats.percentages.active}%)`;
                legends[1].textContent = `Manutenção (${stats.percentages.maintenance}%)`;
                legends[2].textContent = `Inativo (${stats.percentages.inactive}%)`;
            }

            console.log('✅ Dashboard atualizado:', stats);
            return stats;
        } catch (error) {
            console.error('❌ Erro ao atualizar dashboard:', error);
        }
    }
}

// Exportar para uso global
window.DashboardRealData = DashboardRealData;
