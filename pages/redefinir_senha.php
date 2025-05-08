<?php
/**
 * Página de redefinição de senha
 * Esta página é acessada através do link enviado por e-mail na recuperação de senha
 */

// Verificar se os parâmetros necessários estão presentes
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($token) || empty($email)) {
    $_SESSION['flash_message'] = "Link de recuperação inválido ou expirado.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Inicializar variáveis
$erro = '';
$sucesso = false;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = isset($_POST['nova_senha']) ? trim($_POST['nova_senha']) : '';
    $confirmar_senha = isset($_POST['confirmar_senha']) ? trim($_POST['confirmar_senha']) : '';
    
    // Validação básica
    if (empty($nova_senha)) {
        $erro = "A nova senha é obrigatória.";
    } elseif (strlen($nova_senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        $db = Database::getInstance();
        
        // Verificar se o token é válido e não expirou
        $usuario = $db->fetch(
            "SELECT id FROM usuarios WHERE email = :email AND token_recuperacao = :token AND expiracao_token > NOW() AND status = 'ativo'", 
            ['email' => $email, 'token' => $token]
        );
        
        if ($usuario) {
            // Atualizar a senha
            $senha_hash = md5($nova_senha); // Usando MD5 para compatibilidade
            
            $db->update('usuarios', 
                ['senha' => $senha_hash, 'token_recuperacao' => NULL, 'expiracao_token' => NULL], 
                'id = :id', 
                ['id' => $usuario['id']]
            );
            
            // Verificar se a tabela usuarios_senha existe e atualizar também
            try {
                $tabela_existe = $db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'usuarios_senha'");
                
                if ($tabela_existe) {
                    // Gerar hash da nova senha com algoritmo mais seguro
                    $senha_hash_seguro = password_hash($nova_senha, PASSWORD_DEFAULT);
                    
                    // Verificar se já existe um registro na tabela usuarios_senha
                    $senha_existente = $db->fetch("SELECT usuario_id FROM usuarios_senha WHERE usuario_id = :usuario_id", [
                        'usuario_id' => $usuario['id']
                    ]);
                    
                    if ($senha_existente) {
                        // Atualizar senha existente
                        $db->query("UPDATE usuarios_senha SET senha = :senha WHERE usuario_id = :usuario_id", [
                            'senha' => $senha_hash_seguro,
                            'usuario_id' => $usuario['id']
                        ]);
                    } else {
                        // Inserir nova senha
                        $db->query("INSERT INTO usuarios_senha (usuario_id, senha) VALUES (:usuario_id, :senha)", [
                            'usuario_id' => $usuario['id'],
                            'senha' => $senha_hash_seguro
                        ]);
                    }
                }
            } catch (Exception $e) {
                // Ignorar erros relacionados à tabela usuarios_senha
                error_log("Aviso: Tabela usuarios_senha não encontrada ou erro ao acessá-la: " . $e->getMessage());
            }
            
            $sucesso = true;
            
            // Definir mensagem de sucesso
            $_SESSION['flash_message'] = "Sua senha foi redefinida com sucesso! Agora você pode fazer login com sua nova senha.";
            $_SESSION['flash_type'] = "success";
            
            // Redirecionar para a página de login após 3 segundos
            echo "<script>
                setTimeout(function() {
                    window.location.href = '" . SITE_URL . "/?route=entrar';
                }, 3000);
            </script>";
        } else {
            $erro = "Link de recuperação inválido ou expirado. Por favor, solicite um novo link.";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-box">
        <h2 class="auth-title">Redefinir Senha</h2>
        <p class="auth-subtitle">Defina sua nova senha para acessar sua conta</p>
        
        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                <h4 class="alert-heading">Senha redefinida com sucesso!</h4>
                <p>Sua senha foi redefinida com sucesso. Você será redirecionado para a página de login em alguns segundos...</p>
            </div>
        <?php else: ?>
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo SITE_URL; ?>/?route=redefinir_senha&token=<?php echo urlencode($token); ?>&email=<?php echo urlencode($email); ?>" class="auth-form">
                <div class="form-group">
                    <label for="nova_senha" class="form-label">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" class="form-control" required minlength="6">
                    <small class="form-text text-muted">A senha deve ter pelo menos 6 caracteres.</small>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Redefinir Senha</button>
                </div>
                
                <div class="auth-footer">
                    <a href="<?php echo SITE_URL; ?>/?route=entrar">Voltar para o login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="auth-info">
        <h3>Redefinição de Senha</h3>
        <p>Escolha uma senha forte que você não use em outros sites. Uma boa senha deve:</p>
        <ul>
            <li>Ter pelo menos 6 caracteres</li>
            <li>Incluir letras maiúsculas e minúsculas</li>
            <li>Incluir números e símbolos</li>
        </ul>
    </div>
</div>
