/**
 * JavaScript específico para a página de gerenciamento de talentos
 * Compatível com Bootstrap 5
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando gerenciador de talentos...');
    
    // Inicializar DataTables se estiver disponível
    if (typeof $.fn.DataTable !== 'undefined') {
        try {
            $('#talentosTable').DataTable({
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
        console.error('DataTable não está disponível!');
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
    
    // Adicionar evento para mostrar prévia da imagem
    document.getElementById('editar_foto')?.addEventListener('change', function(e) {
        const previewContainer = document.getElementById('previewFotoContainer');
        const previewImg = document.getElementById('previewFoto');
        
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
