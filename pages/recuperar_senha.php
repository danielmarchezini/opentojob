<div class="auth-container">
    <div class="auth-box">
        <h2 class="auth-title">Recuperar Senha</h2>
        <p class="auth-subtitle">Informe seu e-mail para receber instruções de recuperação de senha</p>
        
        <?php
        // Processar o formulário quando enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            
            $erros = [];
            
            // Validação básica
            if (empty($email)) {
                $erros[] = "O e-mail é obrigatório.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros[] = "Por favor, insira um e-mail válido.";
            }
            
            // Se não houver erros, prosseguir com a recuperação
            if (empty($erros)) {
                $db = Database::getInstance();
                
                // Verificar se o e-mail existe
                $usuario = $db->fetch("SELECT id, nome FROM usuarios WHERE email = :email AND status = 'ativo'", ['email' => $email]);
                
                if ($usuario) {
                    // Gerar token de recuperação
                    $token = generateToken();
                    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Atualizar usuário com o token
                    $db->update('usuarios', 
                        ['token_recuperacao' => $token, 'expiracao_token' => $expiracao], 
                        'id = :id', 
                        ['id' => $usuario['id']]
                    );
                    
                    // Enviar e-mail com o link de recuperação usando SmtpMailer
                    require_once 'includes/SmtpMailer.php';
                    $mailer = SmtpMailer::getInstance();
                    $enviado = $mailer->enviarEmailRecuperacaoSenha($usuario, $token);
                    
                    if ($enviado) {
                        $_SESSION['flash_message'] = "Um e-mail com instruções para recuperar sua senha foi enviado para {$email}. Por favor, verifique sua caixa de entrada.";
                        $_SESSION['flash_type'] = "success";
                    } else {
                        error_log("Falha ao enviar e-mail de recuperação para {$email}");
                        $_SESSION['flash_message'] = "Houve um problema ao enviar o e-mail. Por favor, tente novamente mais tarde ou entre em contato com o suporte.";
                        $_SESSION['flash_type'] = "danger";
                    }
                    
                    // Redirecionar para a página de login
                    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
                    exit;
                } else {
                    // Não informar se o e-mail existe ou não por questões de segurança
                    $_SESSION['flash_message'] = "Se o e-mail estiver cadastrado em nosso sistema, você receberá instruções para recuperar sua senha.";
                    $_SESSION['flash_type'] = "info";
                    
                    // Redirecionar para a página de login
                    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
                    exit;
                }
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
        
        <form method="post" action="<?php echo SITE_URL; ?>/?route=recuperar_senha" class="auth-form">
            <div class="form-group">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars((string)$email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Enviar instruções</button>
            </div>
            
            <div class="auth-footer">
                <a href="<?php echo SITE_URL; ?>/?route=entrar">Voltar para o login</a>
            </div>
        </form>
    </div>
    
    <div class="auth-info">
        <h3>Esqueceu sua senha?</h3>
        <p>Não se preocupe, isso acontece. Siga os passos abaixo para recuperar o acesso à sua conta:</p>
        
        <div class="recovery-steps">
            <div class="recovery-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>Informe seu e-mail</h4>
                    <p>Digite o e-mail cadastrado na sua conta Open2W.</p>
                </div>
            </div>
            
            <div class="recovery-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>Verifique seu e-mail</h4>
                    <p>Você receberá um link para redefinir sua senha.</p>
                </div>
            </div>
            
            <div class="recovery-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>Crie uma nova senha</h4>
                    <p>Escolha uma senha forte e segura para sua conta.</p>
                </div>
            </div>
        </div>
        
        <div class="auth-help">
            <p>Não recebeu o e-mail? Verifique sua pasta de spam ou entre em contato com nosso suporte.</p>
            <a href="<?php echo SITE_URL; ?>/?route=contato" class="btn btn-outline-light">Contatar suporte</a>
        </div>
    </div>
</div>

<style>
.auth-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

@media (min-width: 992px) {
    .auth-container {
        grid-template-columns: 1fr 1fr;
    }
}

.auth-box {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    padding: 30px;
}

.auth-title {
    font-size: 1.8rem;
    margin-bottom: 10px;
    color: var(--primary-color);
}

.auth-subtitle {
    color: var(--gray-color);
    margin-bottom: 30px;
}

.auth-form .form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
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

.btn {
    display: inline-block;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    padding: 10px 20px;
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

.btn-outline-light {
    background-color: transparent;
    border: 1px solid white;
    color: white;
}

.btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.btn-block {
    display: block;
    width: 100%;
}

.auth-footer {
    text-align: center;
    margin-top: 20px;
    color: var(--gray-color);
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.auth-info {
    padding: 30px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 10px;
    align-self: center;
}

.auth-info h3 {
    font-size: 1.8rem;
    margin-bottom: 15px;
}

.auth-info p {
    margin-bottom: 30px;
    opacity: 0.9;
}

.recovery-steps {
    margin-bottom: 30px;
}

.recovery-step {
    display: flex;
    margin-bottom: 20px;
}

.step-number {
    width: 40px;
    height: 40px;
    background-color: white;
    color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-content h4 {
    margin-bottom: 5px;
    font-size: 1.1rem;
}

.step-content p {
    margin-bottom: 0;
    opacity: 0.8;
    font-size: 0.9rem;
}

.auth-help {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 5px;
    margin-top: 30px;
    text-align: center;
}

.auth-help p {
    margin-bottom: 15px;
    font-size: 0.9rem;
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

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>
