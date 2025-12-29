/**
 * Dashboard de Monitoramento de Conformidade de Rotas
 *
 * Funcionalidades:
 * - Visualização de KPIs em tempo real
 * - Mapa com desvios detectados
 * - Tabela de desvios não resolvidos
 * - Resolução de desvios com notas
 * - Auto-refresh a cada 30 segundos
 */

const API_BASE = 'https://floripa.in9automacao.com.br';
let map;
let refreshInterval;
let currentDeviationId = null;

/**
 * Inicializar dashboard ao carregar página
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Compliance Monitor carregado');

    // Inicializar mapa
    initializeMap();

    // Carregar dados iniciais
    loadDashboardData();

    // Auto-refresh a cada 30 segundos
    refreshInterval = setInterval(loadDashboardData, 30000);
});

/**
 * Inicializar mapa Leaflet
 */
function initializeMap() {
    // Centralizar no ES (Vitória)
    map = L.map('map').setView([-20.3155, -40.3128], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    console.log('🗺️ Mapa inicializado');
}

/**
 * Carregar todos os dados do dashboard
 */
async function loadDashboardData() {
    try {
        console.log('📊 Carregando dados do dashboard...');

        // Buscar desvios não resolvidos
        const severityFilter = document.getElementById('severity-filter').value;
        const params = new URLSearchParams({
            resolved: '0',
            limit: '100'
        });

        if (severityFilter) {
            params.append('severity', severityFilter);
        }

        const response = await fetch(`${API_BASE}/route-deviations-api.php?${params}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar desvios');
        }

        const deviations = data.deviations || [];

        // Atualizar KPIs
        updateKPIs(deviations);

        // Atualizar mapa
        updateMap(deviations);

        // Atualizar tabela
        updateDeviationsTable(deviations);

        // Atualizar timestamp
        document.getElementById('last-update').textContent = new Date().toLocaleTimeString('pt-BR');

        console.log(`✅ Dashboard atualizado: ${deviations.length} desvios`);

    } catch (error) {
        console.error('❌ Erro ao carregar dashboard:', error);
        showError('Erro ao carregar dados do dashboard');
    }
}

/**
 * Atualizar KPIs do dashboard
 */
function updateKPIs(deviations) {
    // Contar por severidade
    const criticalCount = deviations.filter(d => d.severity === 'critical').length;
    const highCount = deviations.filter(d => d.severity === 'high').length;
    const mediumCount = deviations.filter(d => d.severity === 'medium').length;
    const lowCount = deviations.filter(d => d.severity === 'low').length;

    const totalDeviations = deviations.length;

    // Extrair rotas únicas
    const uniqueRoutes = [...new Set(deviations.map(d => d.route_id))];
    const activeRoutes = uniqueRoutes.length;

    // Assumir que rotas sem desvios estão conformes (simplificação)
    // TODO: Buscar total de rotas ativas da API
    const compliantRoutes = 0; // Por enquanto

    // Atualizar DOM
    document.getElementById('kpi-active-routes').textContent = activeRoutes;
    document.getElementById('kpi-compliant').textContent = compliantRoutes;
    document.getElementById('kpi-deviations').textContent = totalDeviations;
    document.getElementById('kpi-critical').textContent = criticalCount;

    // Remover loading
    document.querySelectorAll('.loading').forEach(el => el.classList.remove('loading'));
}

/**
 * Atualizar mapa com marcadores de desvios
 */
function updateMap(deviations) {
    // Limpar marcadores anteriores
    map.eachLayer(layer => {
        if (layer instanceof L.Marker) {
            map.removeLayer(layer);
        }
    });

    if (deviations.length === 0) {
        console.log('📍 Nenhum desvio para exibir no mapa');
        return;
    }

    // Adicionar marcadores
    deviations.forEach(deviation => {
        const color = getSeverityColor(deviation.severity);

        const marker = L.circleMarker(
            [deviation.location_latitude, deviation.location_longitude],
            {
                radius: 10,
                fillColor: color,
                color: '#fff',
                weight: 3,
                opacity: 1,
                fillOpacity: 0.8
            }
        );

        // Popup com informações
        const popupContent = `
            <div class="text-sm">
                <strong>${translateDeviationType(deviation.deviation_type)}</strong><br>
                <span class="severity-badge ${deviation.severity}">${deviation.severity.toUpperCase()}</span><br>
                <strong>Rota:</strong> #${deviation.route_id}<br>
                <strong>Detectado:</strong> ${formatDateTime(deviation.detected_at)}<br>
                ${deviation.location_address ? `<strong>Local:</strong> ${deviation.location_address}` : ''}
            </div>
        `;

        marker.bindPopup(popupContent);
        marker.addTo(map);
    });

    // Ajustar zoom para mostrar todos os marcadores
    if (deviations.length > 0) {
        const group = L.featureGroup(
            deviations.map(d =>
                L.marker([d.location_latitude, d.location_longitude])
            )
        );
        map.fitBounds(group.getBounds().pad(0.1));
    }

    console.log(`📍 ${deviations.length} marcadores adicionados ao mapa`);
}

/**
 * Atualizar tabela de desvios
 */
function updateDeviationsTable(deviations) {
    const tbody = document.getElementById('deviations-tbody');
    tbody.innerHTML = '';

    if (deviations.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                    <span class="material-symbols-outlined" style="font-size: 48px;">check_circle</span><br>
                    Nenhum desvio não resolvido
                </td>
            </tr>
        `;
        return;
    }

    deviations.forEach(deviation => {
        const row = tbody.insertRow();
        row.className = 'hover:bg-gray-50 transition';

        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-900">
                ${formatDateTime(deviation.detected_at)}
            </td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                Rota #${deviation.route_id}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${translateDeviationType(deviation.deviation_type)}
            </td>
            <td class="px-4 py-3">
                <span class="severity-badge ${deviation.severity}">${deviation.severity}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
                ${deviation.location_latitude.toFixed(5)}, ${deviation.location_longitude.toFixed(5)}
            </td>
            <td class="px-4 py-3 text-sm">
                ${deviation.alert_sent
                    ? '<span class="text-green-600">✓ Sim</span>'
                    : '<span class="text-gray-400">✗ Não</span>'}
            </td>
            <td class="px-4 py-3 text-sm">
                <button
                    onclick="openResolveModal(${deviation.id})"
                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition mr-2"
                >
                    Resolver
                </button>
                <button
                    onclick="viewOnMap(${deviation.location_latitude}, ${deviation.location_longitude})"
                    class="px-3 py-1 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
                >
                    Ver no Mapa
                </button>
            </td>
        `;
    });
}

/**
 * Obter cor baseada na severidade
 */
function getSeverityColor(severity) {
    const colors = {
        'critical': '#dc2626',
        'high': '#ea580c',
        'medium': '#ca8a04',
        'low': '#16a34a'
    };
    return colors[severity] || '#6b7280';
}

/**
 * Traduzir tipo de desvio
 */
function translateDeviationType(type) {
    const translations = {
        'wrong_sequence': 'Sequência Errada',
        'excessive_distance': 'Distância Excessiva',
        'unplanned_stop': 'Parada Não Planejada',
        'skipped_location': 'Local Pulado',
        'route_abandoned': 'Rota Abandonada'
    };
    return translations[type] || type;
}

/**
 * Formatar data/hora
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Centralizar mapa em coordenadas
 */
function viewOnMap(lat, lng) {
    map.setView([lat, lng], 16);

    // Scroll suave até o mapa
    document.getElementById('map').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Abrir modal de resolução
 */
function openResolveModal(deviationId) {
    currentDeviationId = deviationId;
    document.getElementById('resolution-notes').value = '';
    document.getElementById('resolve-modal').classList.remove('hidden');
}

/**
 * Fechar modal de resolução
 */
function closeResolveModal() {
    currentDeviationId = null;
    document.getElementById('resolve-modal').classList.add('hidden');
}

/**
 * Confirmar resolução do desvio
 */
async function confirmResolveDeviation() {
    const notes = document.getElementById('resolution-notes').value.trim();

    if (!notes) {
        alert('Por favor, adicione notas de resolução');
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/route-deviations-api.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: currentDeviationId,
                resolution_notes: notes
            })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao resolver desvio');
        }

        console.log('✅ Desvio resolvido:', currentDeviationId);

        // Fechar modal
        closeResolveModal();

        // Recarregar dados
        loadDashboardData();

    } catch (error) {
        console.error('❌ Erro ao resolver desvio:', error);
        alert('Erro ao resolver desvio: ' + error.message);
    }
}

/**
 * Mostrar erro
 */
function showError(message) {
    // TODO: Implementar toast de erro bonito
    alert(message);
}
