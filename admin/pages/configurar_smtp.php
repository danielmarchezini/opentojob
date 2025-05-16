<?php
// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/?route=login');
    exit;
}

// Incluir arquivos necessários
require_once __DIR__ . '/../../includes/SmtpMailer.php';

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';
$config = [];

// Instanciar o SmtpMailer
$mailer = SmtpMailer::getInstance();

// Processar formulário de teste
if (isset($_POST['testar_conexao'])) {
    $resultado = $mailer->testarConexao();
    $tipo_mensagem = $resultado['success'] ? 'success' : 'danger';
    $mensagem = $resultado['message'];
}

// Processar formulário de atualização
if (isset($_POST['salvar_config'])) {
    $novaConfig = [
        'host' => $_POST['host'],
        'porta' => (int)$_POST['porta'],
        'usuario' => $_POST['usuario'],
        'senha' => $_POST['senha'],
        'email_remetente' => $_POST['email_remetente'],
        'nome_remetente' => $_POST['nome_remetente'],
        'seguranca' => $_POST['seguranca'],
        'ativo' => isset($_POST['ativo']) ? 1 : 0
    ];
    
    $resultado = $mailer->atualizarConfig($novaConfig);
    
    if ($resultado) {
        $tipo_mensagem = 'success';
        $mensagem = 'Configurações de SMTP atualizadas com sucesso!';
    } else {
        $tipo_mensagem = 'danger';
        $mensagem = 'Erro ao atualizar configurações de SMTP. Verifique os logs para mais detalhes.';
    }
}

// Obter configurações atuais
try {
    $db = Database::getInstance();
    $config = $db->fetch("SELECT * FROM configuracoes_smtp ORDER BY id DESC LIMIT 1");
} catch (PDOException $e) {
    $tipo_mensagem = 'warning';
    $mensagem = 'A tabela de configurações SMTP pode não existir ainda. Execute o script SQL para criá-la.';
    $config = [
        'host' => 'smtp.gmail.com',
        'porta' => 587,
        'usuario' => EMAIL_FROM,
        'senha' => '',
        'email_remetente' => EMAIL_FROM,
        'nome_remetente' => EMAIL_FROM_NAME,
        'seguranca' => 'tls',
        'ativo' => 1
    ];
}
?>

<!-- Conteúdo da página -->
<div class="content-wrapper">
    <!-- Cabeçalho da página -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Configurações de SMTP</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">Configurações de SMTP</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Conteúdo principal -->
    <section class="content">
        <div class="container-fluid">
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                    <?php echo $mensagem; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Configurar Servidor SMTP</h3>
                        </div>
                        
                        <form method="post" action="">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="host">Servidor SMTP</label>
                                            <input type="text" class="form-control" id="host" name="host" 
                                                   value="<?php echo htmlspecialchars((string)$config['host'] ?? ''); ?>" required>
                                            <small class="form-text text-muted">Ex: smtp.gmail.com, smtp.office365.com</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="porta">Porta</label>
                                            <input type="number" class="form-control" id="porta" name="porta" 
                                                   value="<?php echo htmlspecialchars((string)$config['porta'] ?? 587); ?>" required>
                                            <small class="form-text text-muted">Portas comuns: 25, 465 (SSL), 587 (TLS)</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="usuario">Usuário</label>
                                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                                   value="<?php echo htmlspecialchars((string)$config['usuario'] ?? ''); ?>" required>
                                            <small class="form-text text-muted">Geralmente é o endereço de e-mail completo</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="senha">Senha</label>
                                            <input type="password" class="form-control" id="senha" name="senha" 
                                                   value="<?php echo htmlspecialchars((string)$config['senha'] ?? ''); ?>" required>
                                            <small class="form-text text-muted">Para Gmail, use senha de app</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email_remetente">E-mail do Remetente</label>
                                            <input type="email" class="form-control" id="email_remetente" name="email_remetente" 
                                                   value="<?php echo htmlspecialchars((string)$config['email_remetente'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nome_remetente">Nome do Remetente</label>
                                            <input type="text" class="form-control" id="nome_remetente" name="nome_remetente" 
                                                   value="<?php echo htmlspecialchars((string)$config['nome_remetente'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="seguranca">Segurança</label>
                                            <select class="form-control" id="seguranca" name="seguranca">
                                                <option value="tls" <?php echo (($config['seguranca'] ?? '') == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo (($config['seguranca'] ?? '') == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                                <option value="nenhuma" <?php echo (($config['seguranca'] ?? '') == 'nenhuma') ? 'selected' : ''; ?>>Nenhuma</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch mt-4">
                                                <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" 
                                                       <?php echo (($config['ativo'] ?? 1) == 1) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="ativo">Ativar configurações SMTP</label>
                                            </div>
                                            <small class="form-text text-muted">Se desativado, o sistema usará a função mail() do PHP</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" name="salvar_config" class="btn btn-primary">Salvar Configurações</button>
                                <button type="submit" name="testar_conexao" class="btn btn-info ml-2">Testar Conexão</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Informações Importantes</h3>
                        </div>
                        <div class="card-body">
                            <h5>Configurando Gmail</h5>
                            <p>Se você estiver usando o Gmail, siga estas etapas:</p>
                            <ol>
                                <li>Ative a verificação em duas etapas na sua conta Google</li>
                                <li>Crie uma <a href="https://myaccount.google.com/apppasswords" target="_blank">senha de app</a> específica para o OpenToJob</li>
                                <li>Use essa senha de app no campo "Senha" acima</li>
                            </ol>
                            
                            <h5>Configurando Office 365</h5>
                            <p>Para Office 365, use as seguintes configurações:</p>
                            <ul>
                                <li>Servidor: smtp.office365.com</li>
                                <li>Porta: 587</li>
                                <li>Segurança: TLS</li>
                            </ul>
                            
                            <h5>Problemas Comuns</h5>
                            <ul>
                                <li>Verifique se o servidor de e-mail permite conexões SMTP externas</li>
                                <li>Alguns provedores bloqueiam conexões de IPs desconhecidos</li>
                                <li>Verifique se o firewall do servidor não está bloqueando as portas SMTP</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar porta automaticamente ao mudar a segurança
    document.getElementById('seguranca').addEventListener('change', function() {
        const seguranca = this.value;
        const portaInput = document.getElementById('porta');
        
        if (seguranca === 'ssl') {
            portaInput.value = '465';
        } else if (seguranca === 'tls') {
            portaInput.value = '587';
        } else {
            portaInput.value = '25';
        }
    });
});
</script>
