    </div><!-- Fim do admin-wrapper -->

    <!-- jQuery (mantido para compatibilidade com scripts existentes) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Bootstrap 5 JS Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript Principal -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/admin.js"></script>
    
    <!-- Script de correção de modais -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/modals-fix.js"></script>
    
    <!-- JavaScript específico da página (se existir) -->
    <?php 
    $page_js = ADMIN_PATH . '/assets/js/' . $page . '.js';
    if (file_exists($page_js)): 
    ?>
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/<?php echo $page; ?>.js"></script>
    <?php endif; ?>
    
    <!-- Script de diagnóstico e correção do CRUD -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Iniciando diagnóstico do CRUD...');
            
            // Verificar se o Bootstrap está disponível
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap não está disponível! Carregando novamente...');
                const bootstrapScript = document.createElement('script');
                bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
                document.body.appendChild(bootstrapScript);
            } else {
                console.log('Bootstrap disponível:', bootstrap.Modal);
            }
            
            // Solução direta para o problema: substituir todos os botões com onclick
            setTimeout(function() {
                console.log('Aplicando solução direta para botões...');
                
                // Encontrar todos os botões com onclick
                const buttons = document.querySelectorAll('button[onclick], a[onclick]');
                
                buttons.forEach(function(button) {
                    const onclickCode = button.getAttribute('onclick');
                    if (!onclickCode) return;
                    
                    console.log('Corrigindo botão com onclick:', onclickCode);
                    
                    // Criar um novo botão com as mesmas propriedades
                    const newButton = document.createElement(button.tagName);
                    
                    // Copiar todos os atributos
                    Array.from(button.attributes).forEach(attr => {
                        if (attr.name !== 'onclick') {
                            newButton.setAttribute(attr.name, attr.value);
                        }
                    });
                    
                    // Copiar o conteúdo HTML
                    newButton.innerHTML = button.innerHTML;
                    
                    // Adicionar o evento de clique usando o código original
                    newButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        try {
                            eval(onclickCode);
                        } catch (error) {
                            console.error('Erro ao executar onclick:', error);
                            alert('Erro ao executar ação. Por favor, tente novamente.');
                        }
                    });
                    
                    // Substituir o botão original
                    if (button.parentNode) {
                        button.parentNode.replaceChild(newButton, button);
                    }
                });
                
                // Verificar e corrigir modais
                const modals = document.querySelectorAll('.modal');
                console.log('Encontrados', modals.length, 'modais');
                
                modals.forEach((modal, index) => {
                    console.log(`Modal ${index}:`, modal.id);
                    
                    // Garantir que o modal tenha o backdrop correto
                    modal.setAttribute('data-bs-backdrop', 'static');
                    modal.setAttribute('data-bs-keyboard', 'false');
                });
            }, 500);
        });
    </script>
    
    <!-- Inicialização de componentes -->
    <script>
        $(document).ready(function() {
            // Toggle sidebar em dispositivos móveis
            $('.sidebar-toggle').on('click', function() {
                $('.admin-sidebar').toggleClass('active');
            });
            
            // Dropdown do perfil
            $('.profile-dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                $('.profile-dropdown').toggleClass('show');
            });
            
            // Fechar dropdown ao clicar fora
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.profile-dropdown-toggle').length) {
                    $('.profile-dropdown').removeClass('show');
                }
            });
            
            // Tooltips (atualizado para Bootstrap 5)
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
