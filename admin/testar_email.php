<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
// Usar caminhos absolutos para evitar problemas de inclusão
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo "<h1>Acesso Restrito</h1>";
    echo "<p>Você precisa estar logado como administrador para acessar esta página.</p>";
    exit;
}

// Função para exibir mensagem de sucesso ou erro
function showMessage($message, $type = 'success') {
    echo '<div style="padding: 15px; margin: 15px 0; border-radius: 5px; background-color: ' . ($type == 'success' ? '#d4edda' : '#f8d7da') . '; color: ' . ($type == 'success' ? '#155724' : '#721c24') . ';">';
    echo $message;
    echo '</div>';
}

// Processar formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($codigo)) {
        showMessage('Por favor, selecione um modelo de e-mail.', 'error');
    } elseif (empty($email)) {
        showMessage('Por favor, informe um e-mail de destino.', 'error');
    } else {
        // Obter instância do SmtpMailer
        $mailer = SmtpMailer::getInstance();
        
        // Preparar dados de teste
        $dados = [
            'id' => 1,
            'nome' => 'Usuário de Teste',
            'email' => $email,
            'tipo' => 'talento',
            'tipo_usuario' => 'Talento',
            'data_cadastro' => date('Y-m-d H:i:s')
        ];
        
        // Adicionar dados específicos para cada modelo
        switch ($codigo) {
            case 'recuperar_senha':
                $dados['url_recuperacao'] = SITE_URL . '/?route=redefinir_senha&token=token_teste_123&email=' . urlencode($email);
                break;
                
            case 'nova_vaga':
                $dados['titulo_vaga'] = 'Desenvolvedor PHP Sênior';
                $dados['empresa_vaga'] = 'Empresa Teste';
                $dados['localizacao_vaga'] = 'São Paulo/SP';
                $dados['url_vaga'] = SITE_URL . '/?route=visualizar_vaga&id=123';
                break;
                
            case 'candidatura_recebida':
                $dados['nome_empresa'] = 'Empresa Teste';
                $dados['email_empresa'] = $email;
                $dados['titulo_vaga'] = 'Desenvolvedor PHP Sênior';
                $dados['nome_candidato'] = 'Candidato Teste';
                $dados['email_candidato'] = 'candidato@teste.com';
                $dados['url_perfil_candidato'] = SITE_URL . '/?route=perfil_talento&id=456';
                break;
                
            case 'instrucoes_aprovacao':
                $dados['url_linkedin'] = 'https://www.linkedin.com/company/opentojob/';
                $dados['email_suporte'] = ADMIN_EMAIL;
                break;
                
            case 'novo_cadastro_admin':
                $dados['url_admin'] = SITE_URL . '/?route=painel_admin';
                break;
        }
        
        // Enviar e-mail de teste
        $result = $mailer->enviarEmail($codigo, $email, $dados);
        
        if ($result) {
            showMessage('E-mail de teste enviado com sucesso para ' . $email);
        } else {
            showMessage('Erro ao enviar e-mail de teste.', 'error');
        }
    }
}

// Obter lista de modelos disponíveis
$db = Database::getInstance();
try {
    $modelos = $db->fetchAll("SELECT codigo, nome FROM modelos_email ORDER BY nome ASC");
} catch (PDOException $e) {
    $modelos = [];
    showMessage('Erro ao carregar modelos de e-mail: ' . $e->getMessage(), 'error');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testar Envio de E-mails - OpenToJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 30px;
            color: #0054a6;
        }
        .btn-primary {
            background-color: #0054a6;
            border-color: #0054a6;
        }
        .btn-primary:hover {
            background-color: #004080;
            border-color: #004080;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Testar Envio de E-mails</h1>
        
        <p class="alert alert-info">
            Esta página permite testar o envio de e-mails usando os modelos cadastrados no sistema.
            O e-mail será enviado com dados de teste para o endereço especificado.
        </p>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="codigo" class="form-label">Modelo de E-mail</label>
                <select class="form-select" id="codigo" name="codigo" required>
                    <option value="">Selecione um modelo...</option>
                    <?php foreach ($modelos as $modelo): ?>
                        <option value="<?php echo $modelo['codigo']; ?>"><?php echo $modelo['nome']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">E-mail de Destino</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Digite o e-mail de destino" required>
                <small class="form-text text-muted">O e-mail de teste será enviado para este endereço.</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Enviar E-mail de Teste</button>
            <a href="<?php echo SITE_URL; ?>/?route=gerenciar_emails_admin" class="btn btn-secondary ml-2">Voltar para Gerenciamento</a>
        </form>
        
        <hr>
        
        <h3>Informações Adicionais</h3>
        <p>Cada modelo de e-mail possui variáveis específicas que serão substituídas por valores de teste:</p>
        
        <ul>
            <li><strong>Boas-vindas:</strong> nome, email, url_site</li>
            <li><strong>Recuperação de Senha:</strong> nome, email, url_recuperacao</li>
            <li><strong>Aprovação de Cadastro:</strong> nome, email, tipo_usuario, url_site</li>
            <li><strong>Nova Vaga:</strong> nome, email, titulo_vaga, empresa_vaga, localizacao_vaga, url_vaga</li>
            <li><strong>Candidatura Recebida:</strong> nome_empresa, email_empresa, titulo_vaga, nome_candidato, email_candidato, url_perfil_candidato</li>
            <li><strong>Instruções para Aprovação:</strong> nome, email, url_linkedin, email_suporte</li>
            <li><strong>Notificação de Novo Cadastro (Admin):</strong> nome, email, tipo_usuario, data_cadastro, url_admin</li>
        </ul>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
