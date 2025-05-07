<?php
// Obter configurações do site
$db = Database::getInstance();
$configuracoes = [];

// Verificar se a tabela existe
$tabela_existe = $db->fetch("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'configuracoes'");

if ($tabela_existe && $tabela_existe['count'] > 0) {
    try {
        $configs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
        foreach ($configs as $config) {
            $configuracoes[$config['chave']] = $config['valor'];
        }
    } catch (Exception $e) {
        // Silenciar erros
    }
}

// Definir valores padrão para configurações não existentes
$defaults = [
    'site_titulo' => 'Open2W - Plataforma de Recrutamento',
    'site_descricao' => 'Conectando talentos e empresas',
    'email_contato' => 'contato@open2w.com',
    'telefone_contato' => '(11) 1234-5678',
    'endereco' => 'Av. Paulista, 1000 - São Paulo/SP',
    'redes_sociais_facebook' => 'https://facebook.com/open2w',
    'redes_sociais_instagram' => 'https://instagram.com/open2w',
    'redes_sociais_linkedin' => 'https://linkedin.com/company/open2w',
    'redes_sociais_twitter' => 'https://twitter.com/open2w'
];

// Mesclar configurações com valores padrão
foreach ($defaults as $chave => $valor) {
    if (!isset($configuracoes[$chave])) {
        $configuracoes[$chave] = $valor;
    }
}
?>

<div class="contact-header">
    <div class="container">
        <h1 class="contact-title">Entre em Contato</h1>
        <p class="contact-subtitle">Estamos aqui para ajudar. Entre em contato conosco para tirar dúvidas, fazer sugestões ou reportar problemas.</p>
    </div>
</div>

<div class="container contact-container">
    <div class="contact-info">
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="info-content">
                <h3>Endereço</h3>
                <p><?php echo nl2br(htmlspecialchars($configuracoes['endereco'])); ?></p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <div class="info-content">
                <h3>Telefone</h3>
                <p><?php echo htmlspecialchars($configuracoes['telefone_contato']); ?></p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="info-content">
                <h3>E-mail</h3>
                <p><?php echo htmlspecialchars($configuracoes['email_contato']); ?></p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="info-content">
                <h3>Horário de Atendimento</h3>
                <p>Segunda a Sexta: 9h às 18h<br>Sábado: 9h às 13h</p>
            </div>
        </div>
        
        <div class="social-links">
            <?php if (!empty($configuracoes['redes_sociais_facebook'])): ?>
                <a href="<?php echo htmlspecialchars($configuracoes['redes_sociais_facebook']); ?>" class="social-link" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if (!empty($configuracoes['redes_sociais_twitter'])): ?>
                <a href="<?php echo htmlspecialchars($configuracoes['redes_sociais_twitter']); ?>" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
            <?php endif; ?>
            <?php if (!empty($configuracoes['redes_sociais_linkedin'])): ?>
                <a href="<?php echo htmlspecialchars($configuracoes['redes_sociais_linkedin']); ?>" class="social-link" target="_blank"><i class="fab fa-linkedin-in"></i></a>
            <?php endif; ?>
            <?php if (!empty($configuracoes['redes_sociais_instagram'])): ?>
                <a href="<?php echo htmlspecialchars($configuracoes['redes_sociais_instagram']); ?>" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="contact-form-container">
        <h2>Envie uma mensagem</h2>
        
        <?php
        // Processar o formulário quando enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $assunto = isset($_POST['assunto']) ? trim($_POST['assunto']) : '';
            $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
            
            $erros = [];
            
            // Validação básica
            if (empty($nome)) {
                $erros[] = "O nome é obrigatório.";
            }
            
            if (empty($email)) {
                $erros[] = "O e-mail é obrigatório.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros[] = "Por favor, insira um e-mail válido.";
            }
            
            if (empty($assunto)) {
                $erros[] = "O assunto é obrigatório.";
            }
            
            if (empty($mensagem)) {
                $erros[] = "A mensagem é obrigatória.";
            }
            
            // Se não houver erros, processar a mensagem
            if (empty($erros)) {
                // Preparar o conteúdo do email
                $assunto_email = "Contato via site: " . $assunto;
                
                // Montar o corpo do email
                $corpo_email = "<html><body>";
                $corpo_email .= "<h2>Nova mensagem de contato</h2>";
                $corpo_email .= "<p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>";
                $corpo_email .= "<p><strong>E-mail:</strong> " . htmlspecialchars($email) . "</p>";
                $corpo_email .= "<p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>";
                $corpo_email .= "<p><strong>Mensagem:</strong></p>";
                $corpo_email .= "<p>" . nl2br(htmlspecialchars($mensagem)) . "</p>";
                $corpo_email .= "<p><small>Esta mensagem foi enviada através do formulário de contato do site " . htmlspecialchars(SITE_NAME) . ".</small></p>";
                $corpo_email .= "</body></html>";
                
                // Configurar cabeçalhos do e-mail
                $headers = [
                    'MIME-Version: 1.0',
                    'Content-type: text/html; charset=UTF-8',
                    'From: ' . $nome . ' <' . $email . '>',
                    'Reply-To: ' . $email,
                    'X-Mailer: PHP/' . phpversion()
                ];
                
                // Tentar enviar o email
                $email_destino = $configuracoes['email_contato'];
                $enviado = mail($email_destino, $assunto_email, $corpo_email, implode("\r\n", $headers));
                
                if ($enviado) {
                    // Registrar no log
                    error_log("Mensagem de contato enviada de {$email} para {$email_destino}");
                    
                    $_SESSION['flash_message'] = "Sua mensagem foi enviada com sucesso! Entraremos em contato em breve.";
                    $_SESSION['flash_type'] = "success";
                } else {
                    // Registrar erro no log
                    error_log("Falha ao enviar mensagem de contato de {$email} para {$email_destino}");
                    
                    $_SESSION['flash_message'] = "Houve um problema ao enviar sua mensagem. Por favor, tente novamente mais tarde ou entre em contato pelo e-mail " . htmlspecialchars($configuracoes['email_contato']) . ".";
                    $_SESSION['flash_type'] = "danger";
                }
                
                // Redirecionar para evitar reenvio do formulário
                echo "<script>window.location.href = '" . SITE_URL . "/?route=contato';</script>";
                exit;
            } else {
                // Exibir erros
                echo '<div class="alert alert-danger"><ul>';
                foreach ($erros as $erro) {
                    echo '<li>' . $erro . '</li>';
                }
                echo '</ul></div>';
            }
        }
        ?>
        
        <form method="post" action="<?php echo SITE_URL; ?>/?route=contato" class="contact-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="assunto">Assunto *</label>
                <input type="text" id="assunto" name="assunto" class="form-control" value="<?php echo isset($assunto) ? htmlspecialchars($assunto) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="mensagem">Mensagem *</label>
                <textarea id="mensagem" name="mensagem" class="form-control" rows="6" required><?php echo isset($mensagem) ? htmlspecialchars($mensagem) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Enviar mensagem</button>
            </div>
        </form>
    </div>
</div>

<div class="map-container">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.0976951333286!2d-46.65390548502204!3d-23.563203784683726!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce59c8da0aa315%3A0xd59f9431f2c9776a!2sAv.%20Paulista%2C%20S%C3%A3o%20Paulo%20-%20SP!5e0!3m2!1spt-BR!2sbr!4v1587567112026!5m2!1spt-BR!2sbr" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
</div>

<div class="container faq-section">
    <h2 class="section-title text-center">Perguntas Frequentes</h2>
    
    <div class="faq-container">
        <div class="faq-item">
            <div class="faq-question">
                <h3>Como funciona o status #opentowork?</h3>
                <span class="toggle-icon"><i class="fas fa-plus"></i></span>
            </div>
            <div class="faq-answer">
                <p>O status #opentowork é uma funcionalidade que permite que profissionais sinalizem sua disponibilidade para novas oportunidades. Ao ativar este status, você se torna mais visível para recrutadores e empresas que estão buscando talentos disponíveis. Você pode configurar a visibilidade deste status como pública (visível para todos) ou privada (visível apenas para recrutadores).</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <h3>Como as empresas podem publicar vagas?</h3>
                <span class="toggle-icon"><i class="fas fa-plus"></i></span>
            </div>
            <div class="faq-answer">
                <p>Para publicar vagas, as empresas precisam criar uma conta e ter a função de publicação habilitada pelo administrador. Após a aprovação, é possível acessar o painel da empresa e selecionar a opção "Publicar Nova Vaga". As vagas passam por uma moderação antes de serem publicadas na plataforma.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <h3>O cadastro na plataforma é gratuito?</h3>
                <span class="toggle-icon"><i class="fas fa-plus"></i></span>
            </div>
            <div class="faq-answer">
                <p>Sim, o cadastro básico na plataforma é gratuito tanto para talentos quanto para empresas. Oferecemos também planos premium com recursos adicionais para empresas que desejam ter mais ferramentas de recrutamento e seleção.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <h3>Como funciona o sistema de chat?</h3>
                <span class="toggle-icon"><i class="fas fa-plus"></i></span>
            </div>
            <div class="faq-answer">
                <p>Nosso sistema de chat permite a comunicação direta entre empresas e talentos. Por questões de privacidade e para evitar abordagens indesejadas, apenas empresas podem iniciar conversas. Os talentos podem responder às mensagens recebidas e gerenciar suas conversas através do painel de mensagens.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                <h3>Como posso excluir minha conta?</h3>
                <span class="toggle-icon"><i class="fas fa-plus"></i></span>
            </div>
            <div class="faq-answer">
                <p>Para excluir sua conta, acesse seu perfil e selecione a opção "Configurações de Conta". Na seção "Privacidade", você encontrará a opção "Excluir Conta". Por questões de segurança, será solicitada sua senha para confirmar a exclusão. Todos os seus dados serão removidos permanentemente de nosso sistema.</p>
            </div>
        </div>
    </div>
</div>

<style>
.contact-header {
    background-color: var(--primary-color);
    color: white;
    padding: 60px 0;
    text-align: center;
}

.contact-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.contact-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 700px;
    margin: 0 auto;
}

.contact-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 40px;
    margin: 60px auto;
}

@media (min-width: 992px) {
    .contact-container {
        grid-template-columns: 1fr 2fr;
    }
}

.contact-info {
    background-color: var(--primary-color);
    color: white;
    border-radius: 10px;
    padding: 30px;
}

.info-item {
    display: flex;
    margin-bottom: 30px;
}

.info-icon {
    width: 50px;
    height: 50px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-right: 15px;
    flex-shrink: 0;
}

.info-content h3 {
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.info-content p {
    opacity: 0.8;
    line-height: 1.6;
    margin: 0;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.social-link {
    width: 40px;
    height: 40px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.3s ease;
}

.social-link:hover {
    background-color: white;
    color: var(--primary-color);
}

.contact-form-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    padding: 30px;
}

.contact-form-container h2 {
    font-size: 1.8rem;
    margin-bottom: 30px;
    color: var(--primary-color);
}

.contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (min-width: 768px) {
    .contact-form .form-row {
        grid-template-columns: 1fr 1fr;
    }
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 84, 166, 0.1);
}

textarea.form-control {
    resize: vertical;
}

.btn {
    display: inline-block;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    padding: 12px 25px;
    font-size: 1rem;
    border-radius: 5px;
    transition: all 0.3s;
    border: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: #004080;
}

.map-container {
    margin-bottom: 60px;
}

.faq-section {
    margin-bottom: 60px;
}

.section-title {
    font-size: 2rem;
    margin-bottom: 40px;
    color: var(--primary-color);
}

.text-center {
    text-align: center;
}

.faq-container {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    overflow: hidden;
}

.faq-question {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.faq-question h3 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--dark-color);
}

.toggle-icon {
    width: 30px;
    height: 30px;
    background-color: var(--light-gray-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.faq-item.active .toggle-icon {
    background-color: var(--primary-color);
    color: white;
    transform: rotate(45deg);
}

.faq-answer {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.faq-item.active .faq-answer {
    padding: 0 20px 20px;
    max-height: 500px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ accordion
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Close all other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });
});
</script>
