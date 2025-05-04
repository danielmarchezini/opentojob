/**
 * Script para corrigir problemas com modais no painel administrativo
 * Atualizado para Bootstrap 5
 */

document.addEventListener('DOMContentLoaded', function() {
    // Impedir que modais sejam abertos automaticamente
    const allModals = document.querySelectorAll('.modal');
    
    allModals.forEach(modal => {
        // Remover atributos que podem causar abertura automática
        modal.removeAttribute('data-show');
        modal.removeAttribute('data-bs-backdrop');
        modal.removeAttribute('data-bs-keyboard');
        
        // Garantir que o modal comece fechado
        if (modal.classList.contains('show')) {
            modal.classList.remove('show');
        }
        
        // Adicionar evento para fechar modal quando o botão de fechar for clicado
        const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        });
    });
    
    // Garantir que os botões de edição funcionem corretamente
    const editButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSelector = button.getAttribute('data-bs-target');
            if (targetSelector) {
                const targetModal = document.querySelector(targetSelector);
                if (targetModal) {
                    const modalInstance = new bootstrap.Modal(targetModal);
                    modalInstance.show();
                }
            }
        });
    });
    
    // Corrigir problema com botões de ação que usam onclick
    const actionButtons = document.querySelectorAll('button[onclick]');
    actionButtons.forEach(button => {
        // Remover o atributo onclick e adicionar um event listener
        const onclickValue = button.getAttribute('onclick');
        if (onclickValue && (
            onclickValue.includes('visualizarTalento') || 
            onclickValue.includes('editarTalento') || 
            onclickValue.includes('alterarSenha') || 
            onclickValue.includes('confirmarAcao') ||
            onclickValue.includes('visualizarEmpresa') ||
            onclickValue.includes('editarEmpresa')
        )) {
            // Extrair a função e os parâmetros
            const match = onclickValue.match(/([a-zA-Z]+)\(([^)]+)\)/);
            if (match) {
                const functionName = match[1];
                const params = match[2].split(',').map(param => param.trim());
                
                // Remover aspas simples ou duplas dos parâmetros de string
                const cleanParams = params.map(param => {
                    if ((param.startsWith("'") && param.endsWith("'")) || 
                        (param.startsWith('"') && param.endsWith('"'))) {
                        return param.substring(1, param.length - 1);
                    }
                    return param;
                });
                
                button.removeAttribute('onclick');
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Executar o código do onclick diretamente
                    try {
                        eval(onclickValue);
                    } catch (error) {
                        console.error('Erro ao executar onclick:', error);
                    }
                });
            }
        }
    });
    
    // Corrigir problema de modais que não fecham corretamente
    document.addEventListener('hidden.bs.modal', function(event) {
        // Verificar se ainda há modais visíveis
        const visibleModals = document.querySelectorAll('.modal.show');
        if (visibleModals.length > 0) {
            // Garantir que o body mantenha a classe modal-open
            document.body.classList.add('modal-open');
        }
    });
});
