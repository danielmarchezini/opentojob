        </div>
    </main>
    
    <footer class="footer">
        <div class="container-wide footer-container">
            <div class="footer-column">
                <h3>OpenToJob</h3>
                <ul class="footer-links">
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=sobre">Sobre nós</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=contato">Contato</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=termos">Termos de uso</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=privacidade">Política de privacidade</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=cookies">Política de cookies</a></li>
                    <li class="footer-link"><a href="#" id="cookie-settings-btn">Gerenciar cookies</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Para Talentos</h3>
                <ul class="footer-links">
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=cadastro_talento">Cadastre-se</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=vagas">Buscar vagas</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=blog">Dicas de carreira</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=faq_talento">Perguntas frequentes</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Para Empresas</h3>
                <ul class="footer-links">
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa">Cadastre sua empresa</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=buscar_talentos">Encontre talentos</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=publicar_vaga">Publique uma vaga</a></li>
                    <li class="footer-link"><a href="<?php echo SITE_URL; ?>/?route=faq_empresa">Perguntas frequentes</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Conecte-se conosco</h3>
                <div class="social-icons">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                </div>
                <p class="mt-3">Inscreva-se para receber novidades</p>
                <form id="newsletterForm" class="newsletter-form">
                    <input type="email" name="email" id="newsletter_email" class="form-control" placeholder="Seu e-mail" required>
                    <button type="submit" class="btn btn-accent mt-2">Inscrever-se</button>
                    <div id="newsletterMessage" class="mt-2 alert" style="display: none;"></div>
                </form>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> OpenToJob - Conectando talentos prontos a oportunidades imediatas. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Script para o formulário de newsletter -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const newsletterForm = document.getElementById('newsletterForm');
        const newsletterMessage = document.getElementById('newsletterMessage');
        
        if (newsletterForm) {
            console.log('Formulário de newsletter encontrado');
            
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Formulário de newsletter enviado');
                
                const emailInput = document.getElementById('newsletter_email');
                const email = emailInput.value.trim();
                
                console.log('Email a ser enviado:', email);
                
                if (!email || !isValidEmail(email)) {
                    showNewsletterMessage('Por favor, informe um e-mail válido.', 'danger');
                    return;
                }
                
                // Desabilitar o botão durante o envio
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.textContent = 'Enviando...';
                
                // Usar XMLHttpRequest em vez de fetch para maior compatibilidade
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo SITE_URL; ?>/processar_newsletter.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        console.log('Status da resposta:', xhr.status);
                        console.log('Resposta recebida:', xhr.responseText);
                        
                        // Reabilitar o botão
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                        
                        if (xhr.status === 200) {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                console.log('Dados parseados:', data);
                                
                                if (data.success) {
                                    showNewsletterMessage(data.message, 'success');
                                    newsletterForm.reset();
                                } else {
                                    showNewsletterMessage(data.message || 'Erro ao processar inscrição', 'danger');
                                }
                            } catch (error) {
                                console.error('Erro ao processar resposta:', error);
                                showNewsletterMessage('Erro ao processar resposta do servidor', 'danger');
                            }
                        } else {
                            showNewsletterMessage('Erro na comunicação com o servidor', 'danger');
                        }
                    }
                };
                
                // Enviar os dados
                xhr.send('email=' + encodeURIComponent(email));
                console.log('Requisição enviada para o servidor');
            });
        } else {
            console.error('Formulário de newsletter não encontrado');
        }
        
        function showNewsletterMessage(message, type) {
            console.log('Exibindo mensagem:', message, 'tipo:', type);
            newsletterMessage.textContent = message;
            newsletterMessage.className = 'mt-2 alert alert-' + type;
            newsletterMessage.style.display = 'block';
            
            // Esconder a mensagem após 5 segundos
            setTimeout(function() {
                newsletterMessage.style.display = 'none';
            }, 5000);
        }
        
        function isValidEmail(email) {
            // Expressão regular simples para validação de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    });
    </script>
</body>
</html>

<!-- Incluir o banner de cookies -->
<?php include_once 'templates/cookie-banner.php'; ?>

<!-- Incluir o gerenciador de cookies -->
<script src="<?php echo SITE_URL; ?>/assets/js/cookie-manager.js"></script>
