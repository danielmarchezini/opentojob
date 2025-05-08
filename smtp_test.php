<?php
/**
 * Script para testar o envio de e-mail usando as configurações SMTP do sistema
 * OpenToJob - Teste de envio de e-mail SMTP
 */

// Incluir configurações e classes necessárias
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SmtpMailer.php';

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';
$to = isset($_POST['email']) ? $_POST['email'] : 'destinatario@exemplo.com';
$subject = 'Teste de envio de e-mail SMTP - OpenToJob';
$message = '
<html>
<head>
  <title>Teste de envio de e-mail SMTP</title>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    h1 { color: #0056b3; }
    .footer { margin-top: 30px; font-size: 12px; color: #777; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Teste de envio de e-mail SMTP</h1>
    <p>Este é um e-mail de teste enviado em: ' . date('d/m/Y H:i:s') . '</p>
    <p>Se você está vendo esta mensagem, o envio de e-mail SMTP está funcionando corretamente!</p>
    <div class="footer">
      <p>Este é um e-mail automático de teste. Por favor, não responda.</p>
      <p>OpenToJob - ' . date('Y') . '</p>
    </div>
  </div>
</body>
</html>
';

// Obter as configurações SMTP do banco de dados
$db = Database::getInstance();
try {
    $config = $db->fetch("SELECT * FROM configuracoes_smtp WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
} catch (Exception $e) {
    $config = null;
    $mensagem = "Erro ao obter configurações SMTP: " . $e->getMessage();
    $tipo_mensagem = 'danger';
}

// Processar o envio de e-mail quando o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_teste'])) {
    $to = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : '';
    
    if (empty($to)) {
        $mensagem = "Por favor, informe um endereço de e-mail válido.";
        $tipo_mensagem = 'danger';
    } else {
        // Instanciar o SmtpMailer
        $mailer = SmtpMailer::getInstance();
        
        // Tentar enviar o e-mail
        $result = $mailer->enviarEmailDireto($to, $subject, $message);
        
        if ($result) {
            $mensagem = "E-mail enviado com sucesso para {$to}!";
            $tipo_mensagem = 'success';
        } else {
            $mensagem = "Falha ao enviar e-mail. Verifique as configurações SMTP e tente novamente.";
            $tipo_mensagem = 'danger';
        }
    }
}

// Função para exibir mensagem de alerta
function showAlert($message, $type) {
    if (empty($message)) return '';
    $icon = $type === 'success' ? '✓' : '✗';
    return '<div style="padding: 15px; margin: 15px 0; border-radius: 5px; background-color: ' . 
           ($type === 'success' ? '#d4edda' : '#f8d7da') . 
           '; color: ' . ($type === 'success' ? '#155724' : '#721c24') . ';">' .
           "<strong>{$icon}</strong> {$message}" .
           '</div>';
}

// Função para verificar se a extensão está carregada
function isExtensionLoaded($name) {
    return extension_loaded($name) ? 
        '<span style="color: green;">✓ Carregada</span>' : 
        '<span style="color: red;">✗ Não carregada</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Envio de E-mail SMTP - OpenToJob</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #0056b3; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        h2 { color: #0056b3; margin-top: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, button { padding: 8px; width: 100%; }
        button { background: #0056b3; color: white; border: none; cursor: pointer; margin-top: 10px; }
        button:hover { background: #003d82; }
        .info-box { background: #f8f9fa; border-left: 4px solid #0056b3; padding: 15px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Teste de Envio de E-mail SMTP</h1>
        
        <?php echo showAlert($mensagem, $tipo_mensagem); ?>
        
        <div class="info-box">
            <p>Este script testa o envio de e-mail usando as configurações SMTP configuradas no sistema OpenToJob.</p>
            <p>Data e hora do teste: <strong><?php echo date('d/m/Y H:i:s'); ?></strong></p>
        </div>
        
        <h2>Enviar E-mail de Teste</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="email">E-mail de Destino:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($to); ?>" required>
            </div>
            <button type="submit" name="enviar_teste">Enviar E-mail de Teste</button>
        </form>
        
        <h2>Configurações SMTP Atuais</h2>
        <?php if ($config): ?>
        <table>
            <tr>
                <th>Configuração</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Servidor SMTP</td>
                <td><?php echo htmlspecialchars($config['host']); ?></td>
            </tr>
            <tr>
                <td>Porta</td>
                <td><?php echo htmlspecialchars($config['porta']); ?></td>
            </tr>
            <tr>
                <td>Usuário</td>
                <td><?php echo htmlspecialchars($config['usuario']); ?></td>
            </tr>
            <tr>
                <td>Segurança</td>
                <td><?php echo htmlspecialchars($config['seguranca']); ?></td>
            </tr>
            <tr>
                <td>E-mail do Remetente</td>
                <td><?php echo htmlspecialchars($config['email_remetente']); ?></td>
            </tr>
            <tr>
                <td>Nome do Remetente</td>
                <td><?php echo htmlspecialchars($config['nome_remetente']); ?></td>
            </tr>
            <tr>
                <td>Status</td>
                <td><?php echo $config['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
            </tr>
        </table>
        <?php else: ?>
        <p>Nenhuma configuração SMTP encontrada ou configurações inativas.</p>
        <?php endif; ?>
        
        <h2>Informações do Ambiente</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Versão do PHP</td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td>Extensão OpenSSL</td>
                <td><?php echo isExtensionLoaded('openssl'); ?></td>
            </tr>
            <tr>
                <td>Extensão CURL</td>
                <td><?php echo isExtensionLoaded('curl'); ?></td>
            </tr>
            <tr>
                <td>Software do Servidor</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
            </tr>
            <tr>
                <td>Nome do Servidor</td>
                <td><?php echo $_SERVER['SERVER_NAME']; ?></td>
            </tr>
            <tr>
                <td>Diretório Raiz</td>
                <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
            </tr>
        </table>
        
        <h2>Configurações de E-mail do PHP</h2>
        <table>
            <tr>
                <th>Configuração</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>mail() function</td>
                <td><?php echo function_exists('mail') ? 'Disponível' : 'Não disponível'; ?></td>
            </tr>
            <tr>
                <td>sendmail_from</td>
                <td><?php echo ini_get('sendmail_from') ?: 'Não definido'; ?></td>
            </tr>
            <tr>
                <td>SMTP</td>
                <td><?php echo ini_get('SMTP') ?: 'Não definido'; ?></td>
            </tr>
            <tr>
                <td>smtp_port</td>
                <td><?php echo ini_get('smtp_port') ?: 'Não definido'; ?></td>
            </tr>
            <tr>
                <td>sendmail_path</td>
                <td><?php echo ini_get('sendmail_path') ?: 'Não definido'; ?></td>
            </tr>
        </table>
        
        <div class="footer">
            <p>OpenToJob - Teste de Envio de E-mail SMTP - <?php echo date('Y'); ?></p>
        </div>
    </div>
</body>
</html>
