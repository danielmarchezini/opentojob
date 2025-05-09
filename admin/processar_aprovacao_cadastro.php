<?php
/**
 * Processador de aprovação de cadastros
 * Este arquivo processa a aprovação de cadastros e envia e-mail de confirmação
 */

// Verificar se é uma requisição AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // Não é uma requisição AJAX, retornar erro
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Incluir configurações
require_once __DIR__ . '/../config/config.php';

// Verificar se o usuário está logado e é administrador
session_start();
require_once __DIR__ . '/../includes/Auth.php';
if (!Auth::checkUserType('admin')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Verificar se o ID do usuário foi fornecido
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário não fornecido']);
    exit;
}

$usuario_id = (int)$_POST['id'];

// Incluir dependências
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';

// Iniciar transação
$db = Database::getInstance();
try {
    $db->query("BEGIN");
    
    // Obter dados do usuário
    $usuario = $db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $usuario_id]);
    
    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Verificar se o usuário já está ativo
    if ($usuario['status'] === 'ativo') {
        echo json_encode(['success' => true, 'message' => 'Usuário já está ativo']);
        exit;
    }
    
    // Atualizar status do usuário para ativo
    $db->query("UPDATE usuarios SET status = 'ativo' WHERE id = :id", ['id' => $usuario_id]);
    
    // Registrar log de aprovação
    $db->query("INSERT INTO logs (usuario_id, usuario_nome, acao, ip, data_hora) VALUES (:usuario_id, :usuario_nome, :acao, :ip, NOW())", [
        'usuario_id' => $_SESSION['user_id'],
        'usuario_nome' => $_SESSION['user_name'] ?? 'Admin',
        'acao' => 'Aprovação de cadastro: ID ' . $usuario_id . ' - ' . $usuario['email'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
    
    // Enviar e-mail de aprovação
    $mailer = SmtpMailer::getInstance();
    $mailer->enviarEmailAprovacaoCadastro($usuario);
    
    // Confirmar transação
    $db->query("COMMIT");
    
    echo json_encode(['success' => true, 'message' => 'Cadastro aprovado com sucesso e e-mail enviado']);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $db->query("ROLLBACK");
    
    // Registrar erro
    error_log('Erro ao aprovar cadastro: ' . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Erro ao aprovar cadastro: ' . $e->getMessage()]);
}
?>
