/**
 * Gerenciamento de Destinatários de Alertas
 *
 * Funcionalidades:
 * - Listar destinatários cadastrados
 * - Cadastrar novo destinatário
 * - Editar destinatário existente
 * - Desativar destinatário
 */

const API_BASE = 'https://floripa.in9automacao.com.br';

/**
 * Inicializar ao carregar página
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Alert Recipients Manager carregado');

    // Carregar lista de destinatários
    loadRecipients();

    // Event listener do formulário
    document.getElementById('recipient-form').addEventListener('submit', handleFormSubmit);
});

/**
 * Carregar lista de destinatários
 */
async function loadRecipients() {
    try {
        console.log('📋 Carregando destinatários...');

        const response = await fetch(`${API_BASE}/alert-recipients-api.php?only_active=true`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar destinatários');
        }

        const recipients = data.recipients || [];
        renderRecipientsTable(recipients);

        console.log(`✅ ${recipients.length} destinatários carregados`);

    } catch (error) {
        console.error('❌ Erro ao carregar destinatários:', error);
        showError('Erro ao carregar destinatários');
    }
}

/**
 * Renderizar tabela de destinatários
 */
function renderRecipientsTable(recipients) {
    const tbody = document.getElementById('recipients-tbody');
    tbody.innerHTML = '';

    if (recipients.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                    <span class="material-symbols-outlined" style="font-size: 48px;">group_off</span><br>
                    Nenhum destinatário cadastrado
                </td>
            </tr>
        `;
        return;
    }

    recipients.forEach(recipient => {
        const row = tbody.insertRow();
        row.className = 'hover:bg-gray-50 transition';

        // Construir lista de severidades que recebe
        const severities = [];
        if (recipient.receive_critical) severities.push('🔴 Crítico');
        if (recipient.receive_high) severities.push('🟠 Alto');
        if (recipient.receive_medium) severities.push('🟡 Médio');
        if (recipient.receive_low) severities.push('🟢 Baixo');

        row.innerHTML = `
            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                ${recipient.name}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${recipient.role || '-'}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
                ${formatPhone(recipient.phone)}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
                ${recipient.email || '-'}
            </td>
            <td class="px-4 py-3 text-xs text-gray-600">
                ${severities.join(', ')}
            </td>
            <td class="px-4 py-3">
                ${recipient.is_active
                    ? '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Ativo</span>'
                    : '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Inativo</span>'}
            </td>
            <td class="px-4 py-3 text-sm">
                <button
                    onclick="editRecipient(${recipient.id})"
                    class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition mr-2"
                    title="Editar"
                >
                    <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                </button>
                <button
                    onclick="deleteRecipient(${recipient.id}, '${recipient.name}')"
                    class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                    title="Desativar"
                >
                    <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                </button>
            </td>
        `;
    });
}

/**
 * Formatar telefone para exibição
 */
function formatPhone(phone) {
    // 5527999999999 -> +55 (27) 99999-9999
    if (!phone || phone.length < 12) return phone;

    const ddi = phone.substring(0, 2);
    const ddd = phone.substring(2, 4);
    const part1 = phone.substring(4, phone.length - 4);
    const part2 = phone.substring(phone.length - 4);

    return `+${ddi} (${ddd}) ${part1}-${part2}`;
}

/**
 * Handle submit do formulário
 */
async function handleFormSubmit(e) {
    e.preventDefault();

    const recipientId = document.getElementById('recipient-id').value;
    const isEdit = !!recipientId;

    const recipientData = {
        name: document.getElementById('recipient-name').value.trim(),
        role: document.getElementById('recipient-role').value.trim() || null,
        phone: document.getElementById('recipient-phone').value.trim(),
        email: document.getElementById('recipient-email').value.trim() || null,
        receive_critical: document.getElementById('receive-critical').checked ? 1 : 0,
        receive_high: document.getElementById('receive-high').checked ? 1 : 0,
        receive_medium: document.getElementById('receive-medium').checked ? 1 : 0,
        receive_low: document.getElementById('receive-low').checked ? 1 : 0
    };

    // Validar telefone
    if (!/^55\d{10,11}$/.test(recipientData.phone)) {
        alert('Telefone inválido! Use o formato: 5527999999999');
        return;
    }

    try {
        if (isEdit) {
            await updateRecipient(recipientId, recipientData);
        } else {
            await createRecipient(recipientData);
        }

        // Limpar formulário
        resetForm();

        // Recarregar lista
        loadRecipients();

    } catch (error) {
        console.error('❌ Erro ao salvar destinatário:', error);
        alert('Erro ao salvar: ' + error.message);
    }
}

/**
 * Criar novo destinatário
 */
async function createRecipient(data) {
    const response = await fetch(`${API_BASE}/alert-recipients-api.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || 'Erro ao criar destinatário');
    }

    console.log('✅ Destinatário criado:', result.id);
    alert('Destinatário cadastrado com sucesso!');
}

/**
 * Atualizar destinatário existente
 */
async function updateRecipient(id, data) {
    data.id = parseInt(id);

    const response = await fetch(`${API_BASE}/alert-recipients-api.php`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!result.success) {
        throw new Error(result.error || 'Erro ao atualizar destinatário');
    }

    console.log('✅ Destinatário atualizado:', id);
    alert('Destinatário atualizado com sucesso!');
}

/**
 * Editar destinatário
 */
async function editRecipient(id) {
    try {
        // Buscar dados do destinatário
        const response = await fetch(`${API_BASE}/alert-recipients-api.php?only_active=true`);
        const data = await response.json();

        if (!data.success) {
            throw new Error('Erro ao buscar destinatário');
        }

        const recipient = data.recipients.find(r => r.id === id);

        if (!recipient) {
            throw new Error('Destinatário não encontrado');
        }

        // Preencher formulário
        document.getElementById('recipient-id').value = recipient.id;
        document.getElementById('recipient-name').value = recipient.name;
        document.getElementById('recipient-role').value = recipient.role || '';
        document.getElementById('recipient-phone').value = recipient.phone;
        document.getElementById('recipient-email').value = recipient.email || '';
        document.getElementById('receive-critical').checked = recipient.receive_critical;
        document.getElementById('receive-high').checked = recipient.receive_high;
        document.getElementById('receive-medium').checked = recipient.receive_medium;
        document.getElementById('receive-low').checked = recipient.receive_low;

        // Alterar UI para modo edição
        document.getElementById('form-title').textContent = 'Editar Destinatário';
        document.getElementById('submit-text').textContent = 'Atualizar';
        document.getElementById('cancel-btn').classList.remove('hidden');

        // Scroll até o formulário
        document.getElementById('recipient-form').scrollIntoView({ behavior: 'smooth' });

    } catch (error) {
        console.error('❌ Erro ao editar:', error);
        alert('Erro ao carregar dados: ' + error.message);
    }
}

/**
 * Desativar destinatário
 */
async function deleteRecipient(id, name) {
    if (!confirm(`Deseja realmente desativar o destinatário "${name}"?`)) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/alert-recipients-api.php?id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Erro ao desativar destinatário');
        }

        console.log('✅ Destinatário desativado:', id);
        alert('Destinatário desativado com sucesso!');

        // Recarregar lista
        loadRecipients();

    } catch (error) {
        console.error('❌ Erro ao desativar:', error);
        alert('Erro ao desativar: ' + error.message);
    }
}

/**
 * Cancelar edição
 */
function cancelEdit() {
    resetForm();
}

/**
 * Resetar formulário
 */
function resetForm() {
    document.getElementById('recipient-form').reset();
    document.getElementById('recipient-id').value = '';
    document.getElementById('form-title').textContent = 'Cadastrar Novo Destinatário';
    document.getElementById('submit-text').textContent = 'Cadastrar';
    document.getElementById('cancel-btn').classList.add('hidden');
}

/**
 * Mostrar erro
 */
function showError(message) {
    alert(message);
}
