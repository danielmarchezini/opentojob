<div class="auth-container">
    <div class="auth-box">
        <h2 class="auth-title">Entrar</h2>
        <p class="auth-subtitle">Acesse sua conta para gerenciar suas atividades</p>
        
        <?php
        // Processar o formulário quando enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
            $lembrar = isset($_POST['lembrar']) ? true : false;
            
            $erros = [];
            
            // Validação básica
            if (empty($email)) {
                $erros[] = "O e-mail é obrigatório.";
            }
            
            if (empty($senha)) {
                $erros[] = "A senha é obrigatória.";
            }
            
            // Se não houver erros, prosseguir com o login
            if (empty($erros)) {
                // Tentar fazer login
                if (Auth::login($email, $senha)) {
                    // Login bem-sucedido
                    
                    // Definir cookie de "lembrar-me" se solicitado
                    if ($lembrar) {
                        $token = generateToken();
                        setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 dias
                        
                        // Salvar token no banco de dados (em um sistema real)
                        // $db->update('usuarios', ['remember_token' => $token], 'id = :id', ['id' => $_SESSION['user_id']]);
                    }
                    
                    // Verificar se há um redirecionamento especificado
                    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                        $redirect_route = $_GET['redirect'];
                        $redirect_params = '';
                        
                        // Adicionar parâmetros adicionais se existirem
                        foreach ($_GET as $key => $value) {
                            if ($key != 'route' && $key != 'redirect') {
                                $redirect_params .= "&{$key}={$value}";
                            }
                        }
                        
                        echo "<script>window.location.href = '" . SITE_URL . "/?route={$redirect_route}{$redirect_params}';</script>";
                    } else {
                        // Redirecionar para a página apropriada com base no tipo de usuário
                        if (Auth::checkUserType('talento')) {
                            echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_talento';</script>";
                        } elseif (Auth::checkUserType('empresa')) {
                            echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_empresa';</script>";
                        } elseif (Auth::checkUserType('admin')) {
                            echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_admin';</script>";
                        } else {
                            echo "<script>window.location.href = '" . SITE_URL . "/?route=inicio';</script>";
                        }
                    }
                    exit;
                } else {
                    // Login falhou
                    echo '<div class="alert alert-danger">E-mail ou senha incorretos, ou sua conta ainda não foi aprovada.</div>';
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
        
        <?php
// Capturar parâmetros de redirecionamento para mantê-los após o envio do formulário
$redirect_params = '';
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect_params .= "&redirect=" . urlencode($_GET['redirect']);
    
    // Adicionar parâmetros adicionais se existirem
    foreach ($_GET as $key => $value) {
        if ($key != 'route' && $key != 'redirect') {
            $redirect_params .= "&{$key}=" . urlencode($value);
        }
    }
}
?>
<form method="post" action="<?php echo SITE_URL; ?>/?route=entrar<?php echo $redirect_params; ?>" class="auth-form">
            <div class="form-group">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group form-check">
                    <input type="checkbox" id="lembrar" name="lembrar" class="form-check-input" <?php echo isset($lembrar) && $lembrar ? 'checked' : ''; ?>>
                    <label for="lembrar" class="form-check-label">Lembrar-me</label>
                </div>
                
                <div class="form-group text-right">
                    <a href="<?php echo SITE_URL; ?>/?route=recuperar_senha" class="forgot-password">Esqueceu a senha?</a>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </div>
            
            <div class="auth-footer">
                Não tem uma conta? 
                <div class="auth-options">
                    <a href="<?php echo SITE_URL; ?>/?route=cadastro_talento" class="btn btn-outline">Cadastre-se como Talento</a>
                    <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="btn btn-outline">Cadastre-se como Empresa</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="auth-info">
        <h3>OpenToJob</h3>
        <p>Conectando talentos prontos a oportunidades imediatas. A plataforma que revoluciona a forma de encontrar profissionais disponíveis para contratação.</p>
        
        <div class="auth-features">
            <div class="auth-feature">
                <i class="fas fa-user-tie"></i>
                <h4>Para Talentos</h4>
                <p>Sinalize sua disponibilidade para novas oportunidades e seja encontrado por empresas que buscam seu perfil.</p>
            </div>
            
            <div class="auth-feature">
                <i class="fas fa-building"></i>
                <h4>Para Empresas</h4>
                <p>Encontre talentos qualificados que estão ativamente buscando novas posições e agilize seu processo de contratação.</p>
            </div>
        </div>
        
        <?php
        // Consultar os números reais do banco de dados
        $db = Database::getInstance();
        
        // Contar vagas internas ativas
        try {
            $vagas_internas = $db->fetchColumn("SELECT COUNT(*) FROM vagas WHERE status = 'ativa'") ?? 0;
        } catch (PDOException $e) {
            $vagas_internas = 0;
        }
        
        // Contar vagas externas ativas - verificar se a tabela existe
        try {
            // Verificar se a tabela existe
            $table_exists = $db->fetchColumn("SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = 'open2w' 
                AND table_name = 'vagas_externas'");
                
            if ($table_exists) {
                $vagas_externas = $db->fetchColumn("SELECT COUNT(*) FROM vagas_externas WHERE status = 'ativa'") ?? 0;
            } else {
                $vagas_externas = 0;
            }
        } catch (PDOException $e) {
            $vagas_externas = 0;
        }
        
        // Total de vagas ativas
        $total_vagas = $vagas_internas + $vagas_externas;
        
        // Contar empresas cadastradas
        try {
            $total_empresas = $db->fetchColumn("SELECT COUNT(*) FROM usuarios WHERE tipo = 'empresa' AND status = 'ativo'") ?? 0;
        } catch (PDOException $e) {
            $total_empresas = 0;
        }
        
        // Contar talentos cadastrados
        try {
            $total_talentos = $db->fetchColumn("SELECT COUNT(*) FROM usuarios WHERE tipo = 'talento' AND status = 'ativo'") ?? 0;
        } catch (PDOException $e) {
            $total_talentos = 0;
        }
        ?>
        <div class="auth-stats">
            <div class="auth-stat">
                <span class="stat-number"><?php echo number_format($total_vagas, 0, ',', '.'); ?></span>
                <span class="stat-label">Vagas ativas</span>
                <span class="stat-detail">(<?php echo number_format($vagas_internas, 0, ',', '.'); ?> internas + <?php echo number_format($vagas_externas, 0, ',', '.'); ?> externas)</span>
            </div>
            <div class="auth-stat">
                <span class="stat-number"><?php echo number_format($total_empresas, 0, ',', '.'); ?></span>
                <span class="stat-label">Empresas</span>
            </div>
            <div class="auth-stat">
                <span class="stat-number"><?php echo number_format($total_talentos, 0, ',', '.'); ?></span>
                <span class="stat-label">Talentos</span>
            </div>
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

.form-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.form-check {
    display: flex;
    align-items: center;
}

.form-check-input {
    margin-right: 10px;
}

.forgot-password {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.9rem;
}

.forgot-password:hover {
    text-decoration: underline;
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

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-color);
    color: white;
    text-decoration: none;
}

.btn-block {
    display: block;
    width: 100%;
}

.stat-label {
    display: block;
    font-size: 0.9rem;
    color: var(--gray-color);
}

.stat-detail {
    display: block;
    font-size: 0.8rem;
    color: var(--accent-color);
    margin-top: 5px;
}

.auth-footer {
    text-align: center;
    margin-top: 20px;
    color: var(--gray-color);
}

.auth-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
    justify-content: center;
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

.auth-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.auth-feature {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 5px;
    text-align: center;
}

.auth-feature i {
    font-size: 2rem;
    margin-bottom: 15px;
    color: #ffcc00;
}

.auth-feature h4 {
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.auth-feature p {
    font-size: 0.9rem;
    margin-bottom: 0;
    opacity: 0.8;
}

.auth-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    text-align: center;
}

.auth-stat {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffcc00;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.text-right {
    text-align: right;
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

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .form-row > div {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .text-right {
        text-align: left;
    }
    
    .auth-features {
        grid-template-columns: 1fr;
    }
    
    .auth-stats {
        flex-direction: column;
        gap: 15px;
    }
}
</style>
