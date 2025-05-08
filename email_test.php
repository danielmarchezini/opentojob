<?php
/**
 * Script simples para testar o envio de e-mail
 * OpenToJob - Teste de envio de e-mail
 */

// Configurações básicas
$to = 'destinatario@exemplo.com'; // Substitua pelo e-mail de destino
$subject = 'Teste de envio de e-mail - OpenToJob';
$message = '
<html>
<head>
  <title>Teste de envio de e-mail</title>
</head>
<body>
  <h1>Teste de envio de e-mail</h1>
  <p>Este é um e-mail de teste enviado em: ' . date('d/m/Y H:i:s') . '</p>
  <p>Se você está vendo esta mensagem, o envio de e-mail está funcionando corretamente!</p>
</body>
</html>
';

// Cabeçalhos para envio de e-mail HTML
$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: OpenToJob <contato@opentojob.com.br>', // Substitua pelo e-mail do remetente
    'Reply-To: contato@opentojob.com.br' // Substitua pelo e-mail de resposta
];

// Tentar enviar o e-mail
$result = mail($to, $subject, $message, implode("\r\n", $headers));

// Exibir resultado
echo '<h1>Teste de Envio de E-mail</h1>';
echo '<p>Data e hora do teste: ' . date('d/m/Y H:i:s') . '</p>';

if ($result) {
    echo '<div style="padding: 15px; background-color: #d4edda; color: #155724; border-radius: 5px; margin: 15px 0;">';
    echo '<strong>Sucesso!</strong> O e-mail foi enviado com sucesso para ' . htmlspecialchars($to);
    echo '</div>';
} else {
    echo '<div style="padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin: 15px 0;">';
    echo '<strong>Erro!</strong> Não foi possível enviar o e-mail.';
    echo '</div>';
    
    // Exibir informações adicionais para debug
    echo '<h2>Informações de Debug</h2>';
    echo '<pre>';
    echo 'PHP Version: ' . phpversion() . "\n";
    echo 'Server Software: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
    echo 'Server Name: ' . $_SERVER['SERVER_NAME'] . "\n";
    echo 'Server Address: ' . $_SERVER['SERVER_ADDR'] . "\n";
    echo 'PHP mail.log: ' . ini_get('mail.log') . "\n";
    echo 'PHP sendmail_path: ' . ini_get('sendmail_path') . "\n";
    echo 'PHP SMTP: ' . ini_get('SMTP') . "\n";
    echo 'PHP smtp_port: ' . ini_get('smtp_port') . "\n";
    echo '</pre>';
}

// Formulário para enviar outro teste
echo '<h2>Enviar outro teste</h2>';
echo '<form method="post" action="">';
echo '<div style="margin-bottom: 15px;">';
echo '<label for="email" style="display: block; margin-bottom: 5px;">E-mail de destino:</label>';
echo '<input type="email" name="email" id="email" value="' . htmlspecialchars($to) . '" style="padding: 8px; width: 300px;">';
echo '</div>';
echo '<div style="margin-bottom: 15px;">';
echo '<label for="from" style="display: block; margin-bottom: 5px;">E-mail de origem:</label>';
echo '<input type="email" name="from" id="from" value="contato@opentojob.com.br" style="padding: 8px; width: 300px;">';
echo '</div>';
echo '<div>';
echo '<button type="submit" style="padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Enviar teste</button>';
echo '</div>';
echo '</form>';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $to = $_POST['email'];
        $from = isset($_POST['from']) && filter_var($_POST['from'], FILTER_VALIDATE_EMAIL) 
              ? $_POST['from'] : 'contato@opentojob.com.br';
        
        // Atualizar cabeçalhos com o novo e-mail de origem
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: OpenToJob <' . $from . '>',
            'Reply-To: ' . $from
        ];
        
        // Tentar enviar o e-mail novamente
        $result = mail($to, $subject, $message, implode("\r\n", $headers));
        
        echo '<script>window.location.href = window.location.pathname + "?sent=1&to=' . urlencode($to) . '&result=' . ($result ? '1' : '0') . '";</script>';
    }
}

// Exibir informações de configuração do PHP
echo '<h2>Configurações de E-mail do PHP</h2>';
echo '<pre>';
echo 'PHP mail function: ' . (function_exists('mail') ? 'Disponível' : 'Não disponível') . "\n";
echo 'PHP sendmail_from: ' . ini_get('sendmail_from') . "\n";
echo 'PHP SMTP: ' . ini_get('SMTP') . "\n";
echo 'PHP smtp_port: ' . ini_get('smtp_port') . "\n";
echo '</pre>';

// Exibir informações do servidor
echo '<h2>Informações do Servidor</h2>';
echo '<pre>';
echo 'PHP Version: ' . phpversion() . "\n";
echo 'Server Software: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo 'Server Name: ' . $_SERVER['SERVER_NAME'] . "\n";
echo 'Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo '</pre>';
?>
