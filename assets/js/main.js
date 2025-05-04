/**
 * Open2W - JavaScript Principal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Menu de usuário dropdown
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    const userMenuDropdown = document.querySelector('.user-menu-dropdown');
    
    if (userMenuToggle && userMenuDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            userMenuDropdown.classList.toggle('active');
        });
        
        // Fechar o menu quando clicar fora dele
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                userMenuDropdown.classList.remove('active');
            }
        });
    }
    
    // Menu mobile (hambúrguer)
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.getElementById('main-nav');
    
    if (mobileMenuToggle && mainNav) {
        mobileMenuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            // Alternar ícone entre hambúrguer e X
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Fechar o menu mobile ao clicar em um link
        const mobileNavLinks = mainNav.querySelectorAll('.nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    mainNav.classList.remove('active');
                    const icon = mobileMenuToggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });
    }
    
    // Formulário de busca na página inicial
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('.search-input');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                alert('Por favor, digite uma palavra-chave para buscar vagas.');
            }
        });
    }
    
    // Formulário de filtros na página de vagas
    const filtersForm = document.querySelector('.filters-form');
    const clearFiltersBtn = document.querySelector('.clear-filters');
    
    if (filtersForm && clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Limpar todos os campos do formulário
            const inputs = filtersForm.querySelectorAll('input:not([type="hidden"]), select');
            inputs.forEach(input => {
                if (input.type === 'text') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                }
            });
            
            // Enviar o formulário
            filtersForm.submit();
        });
    }
    
    // Botão de salvar vaga
    const saveJobBtn = document.querySelector('.job-save-btn');
    if (saveJobBtn) {
        saveJobBtn.addEventListener('click', function() {
            const isSaved = this.classList.contains('saved');
            
            if (isSaved) {
                this.innerHTML = '<i class="far fa-bookmark"></i> Salvar vaga';
                this.classList.remove('saved');
                alert('Vaga removida dos favoritos!');
            } else {
                this.innerHTML = '<i class="fas fa-bookmark"></i> Vaga salva';
                this.classList.add('saved');
                alert('Vaga salva nos favoritos!');
            }
        });
    }
    
    // Botões de compartilhamento
    const shareButtons = document.querySelectorAll('.job-share-btn');
    if (shareButtons.length > 0) {
        shareButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const url = window.location.href;
                const title = document.querySelector('.job-detail-title').textContent;
                
                if (this.classList.contains('share-facebook')) {
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
                } else if (this.classList.contains('share-twitter')) {
                    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
                } else if (this.classList.contains('share-linkedin')) {
                    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`, '_blank');
                } else if (this.classList.contains('share-whatsapp')) {
                    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(title + ' - ' + url)}`, '_blank');
                }
            });
        });
    }
    
    // Animação para os cards de vagas
    const jobCards = document.querySelectorAll('.job-card, .job-item');
    if (jobCards.length > 0) {
        jobCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.05)';
            });
        });
    }
    
    // Validação do formulário de newsletter
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();
            
            if (!email) {
                alert('Por favor, digite seu e-mail.');
                return;
            }
            
            if (!isValidEmail(email)) {
                alert('Por favor, digite um e-mail válido.');
                return;
            }
            
            alert('Obrigado por se inscrever em nossa newsletter!');
            emailInput.value = '';
        });
    }
    
    // Função para validar e-mail
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
});
