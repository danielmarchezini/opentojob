/**
 * JavaScript principal para o painel administrativo
 */

// Toggle da barra lateral em dispositivos móveis
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
            document.querySelector('.admin-content').classList.toggle('expanded');
        });
    }

    // Dropdown do perfil no topbar
    const profileDropdown = document.querySelector('.profile-dropdown-toggle');
    if (profileDropdown) {
        profileDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.profile-dropdown-menu').classList.toggle('show');
        });
    }

    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.profile-dropdown-toggle') && !e.target.closest('.profile-dropdown-menu')) {
            const dropdowns = document.querySelectorAll('.profile-dropdown-menu');
            dropdowns.forEach(dropdown => {
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            });
        }
    });

    // Inicializar tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Inicializar popovers
    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    popovers.forEach(popover => {
        new bootstrap.Popover(popover);
    });

    // Confirmação para ações de exclusão
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        });
    });

    // Inicializar datepickers
    const datepickers = document.querySelectorAll('.datepicker');
    datepickers.forEach(datepicker => {
        // Se estiver usando flatpickr ou outro plugin, inicialize aqui
    });

    // Inicializar select2 para selects avançados
    const select2Elements = document.querySelectorAll('.select2');
    select2Elements.forEach(select => {
        // Se estiver usando select2 ou outro plugin, inicialize aqui
    });
});

// Funções utilitárias
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('pt-BR').format(date);
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return new Intl.DateTimeFormat('pt-BR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

// Função para mostrar notificações
function showNotification(message, type = 'info') {
    // Implementar sistema de notificações
    console.log(`[${type}] ${message}`);
}
