<?php
/**
 * Script de diagnóstico para identificar problemas com o envio de emails
 * Este arquivo deve ser removido após o uso
 */

// Incluir configurações
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Configurar cabeçalho
header('Content-Type: text/html; charset=UTF-8');

// Função para testar envio de email usando PHP mail()
function testar_mail_nativo($destinatario) {
    $assunto = 'Teste de Email - OpenToJob (' . date('Y-m-d H:i:s') . ')';
    $mensagem = '
    <html>
    <head>
        <title>Teste de Email</title>
    </head>
    <body>
        <h1>Teste de Email - OpenToJob</h1>
        <p>Este é um email de teste enviado em: ' . date('Y-m-d H:i:s') . '</p>
        <p>Se você está vendo esta mensagem, o sistema de email está funcionando corretamente.</p>
    </body>
    </html>
    ';
    
    // Cabeçalhos
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
    
    // Tentar enviar
    $resultado = mail($destinatario, $assunto, $mensagem, $headers);
    
    return [
        'sucesso' => $resultado,
        'metodo' => 'PHP mail()',
        'destinatario' => $destinatario,
        'assunto' => $assunto,
        'mensagem' => 'Email HTML com cabeçalhos MIME',
        'erro' => error_get_last()
    ];
}

// Função para obter informações do servidor
function obter_info_servidor() {
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
        'os' => PHP_OS,
        'smtp_host' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port'),
        'sendmail_path' => ini_get('sendmail_path'),
        'mail_function_enabled' => function_exists('mail'),
        'openssl_enabled' => extension_loaded('openssl'),
        'email_from' => EMAIL_FROM,
        'email_from_name' => EMAIL_FROM_NAME
    ];
}

// Processar formulário
$resultado_teste = null;
$destinatario = '';
$info_servidor = obter_info_servidor();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destinatario'])) {
    $destinatario = filter_var($_POST['destinatario'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        $resultado_teste = testar_mail_nativo($destinatario);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Email - OpenToJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .code { font-family: monospace; background-color: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Diagnóstico de Email - OpenToJob</h1>
        
        <div class="alert alert-warning">
            <strong>Atenção!</strong> Este script é apenas para diagnóstico e deve ser removido após o uso.
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Informações do Servidor</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <?php foreach ($info_servidor as $chave => $valor): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($chave); ?></th>
                                <td>
                                    <?php 
                                    if (is_bool($valor)) {
                                        echo $valor ? '<span class="text-success">Sim</span>' : '<span class="text-danger">Não</span>';
                                    } else {
                                        echo htmlspecialchars($valor ?: 'Não definido');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Testar Envio de Email</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="destinatario" class="form-label">Email de Destino</label>
                        <input type="email" class="form-control" id="destinatario" name="destinatario" value="<?php echo htmlspecialchars($destinatario); ?>" required>
                        <div class="form-text">Digite seu email para receber o teste.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Enviar Email de Teste</button>
                </form>
                
                <?php if ($resultado_teste): ?>
                    <div class="mt-4">
                        <h6>Resultado do Teste:</h6>
                        <div class="alert <?php echo $resultado_teste['sucesso'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($resultado_teste['sucesso']): ?>
                                <p><strong>Sucesso!</strong> O email foi enviado com sucesso usando <?php echo $resultado_teste['metodo']; ?>.</p>
                                <p>Verifique sua caixa de entrada (e pasta de spam) para confirmar o recebimento.</p>
                            <?php else: ?>
                                <p><strong>Falha!</strong> Não foi possível enviar o email usando <?php echo $resultado_teste['metodo']; ?>.</p>
                                <?php if ($resultado_teste['erro']): ?>
                                    <p>Erro: <?php echo htmlspecialchars($resultado_teste['erro']['message'] ?? 'Desconhecido'); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Detalhes:</h6>
                            <ul>
                                <li>Destinatário: <?php echo htmlspecialchars($resultado_teste['destinatario']); ?></li>
                                <li>Assunto: <?php echo htmlspecialchars($resultado_teste['assunto']); ?></li>
                                <li>Método: <?php echo htmlspecialchars($resultado_teste['metodo']); ?></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Recomendações</h5>
            </div>
            <div class="card-body">
                <h6>1. Verifique as configurações de SMTP no php.ini</h6>
                <p>Edite o arquivo php.ini e configure as seguintes diretivas:</p>
                <pre class="code">
[mail function]
SMTP = seu.servidor.smtp.com
smtp_port = 587
sendmail_from = <?php echo EMAIL_FROM; ?>
                </pre>
                
                <h6 class="mt-3">2. Use uma biblioteca como PHPMailer</h6>
                <p>A função mail() nativa do PHP pode ser problemática. Considere usar PHPMailer com SMTP:</p>
                <pre class="code">
// Exemplo de uso do PHPMailer
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'seu.servidor.smtp.com';
$mail->SMTPAuth = true;
$mail->Username = 'seu_email@example.com';
$mail->Password = 'sua_senha';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
                </pre>
                
                <h6 class="mt-3">3. Verifique as permissões e configurações do servidor</h6>
                <ul>
                    <li>Certifique-se de que o servidor permite envio de emails</li>
                    <li>Verifique se o domínio do remetente tem registros SPF e DKIM válidos</li>
                    <li>Alguns provedores de hospedagem bloqueiam a função mail() por padrão</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
