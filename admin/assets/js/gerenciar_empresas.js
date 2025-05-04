/**
 * JavaScript específico para a página de gerenciamento de empresas
 * Compatível com Bootstrap 5
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando gerenciador de empresas...');
    
    // Verificar se a tabela já foi inicializada pelo DataTables
    if (typeof DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#empresasTable')) {
        try {
            new DataTable('#empresasTable', {
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json',
                }
            });
            console.log('DataTable inicializado com sucesso');
        } catch (error) {
            console.error('Erro ao inicializar DataTable:', error);
        }
    } else {
        console.log('DataTable já inicializado ou não disponível');
    }
    
    // Corrigir botões de ação - usando diretamente o código onclick
    const actionButtons = document.querySelectorAll('button[onclick], a[onclick]');
    console.log('Encontrados', actionButtons.length, 'botões com onclick');
    
    actionButtons.forEach(button => {
        const onclickValue = button.getAttribute('onclick');
        if (!onclickValue) return;
        
        console.log('Processando botão com onclick:', onclickValue);
        
        // Remover o atributo onclick
        button.removeAttribute('onclick');
        
        // Adicionar um event listener que executa diretamente o código onclick
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botão clicado, executando:', onclickValue);
            
            try {
                // Executar o código onclick diretamente
                eval(onclickValue);
            } catch (error) {
                console.error('Erro ao executar onclick:', error);
                alert('Erro ao executar ação. Por favor, tente novamente.');
            }
        });
    });
    
    // Adicionar evento para mostrar prévia do logo
    document.getElementById('editar_logo')?.addEventListener('change', function(e) {
        const previewContainer = document.getElementById('previewLogoContainer');
        const previewImg = document.getElementById('previewLogo');
        
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('d-none');
            }
            
            reader.readAsDataURL(this.files[0]);
        } else {
            previewContainer.classList.add('d-none');
        }
    });
});
