<?php
// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método não permitido');
}

// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verificar se o usuário está logado
$usuario_reportante_id = null;
if (isset($_SESSION['user_id'])) {
    $usuario_reportante_id = $_SESSION['user_id'];
}

// Obter dados do formulário
$usuario_reportado_id = isset($_POST['usuario_reportado_id']) ? (int)$_POST['usuario_reportado_id'] : 0;
$tipo_usuario_reportado = isset($_POST['tipo_usuario_reportado']) ? $_POST['tipo_usuario_reportado'] : '';
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

// Validar dados
$erros = [];

if (empty($usuario_reportado_id)) {
    $erros[] = 'ID do usuário reportado é obrigatório';
}

if (empty($tipo_usuario_reportado) || !in_array($tipo_usuario_reportado, ['talento', 'empresa'])) {
    $erros[] = 'Tipo de usuário reportado inválido';
}

if (empty($motivo)) {
    $erros[] = 'Motivo do reporte é obrigatório';
}

// Se houver erros, retornar mensagem de erro
if (!empty($erros)) {
    $_SESSION['flash_message'] = 'Erro ao processar reporte: ' . implode(', ', $erros);
    $_SESSION['flash_type'] = 'danger';
    
    // Redirecionar de volta para a página anterior
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Inserir reporte no banco de dados
    $db->query("INSERT INTO reportes (usuario_reportado_id, usuario_reportante_id, tipo_usuario_reportado, motivo, descricao, data_reporte, status) 
               VALUES (:usuario_reportado_id, :usuario_reportante_id, :tipo_usuario_reportado, :motivo, :descricao, NOW(), 'pendente')", [
        'usuario_reportado_id' => $usuario_reportado_id,
        'usuario_reportante_id' => $usuario_reportante_id,
        'tipo_usuario_reportado' => $tipo_usuario_reportado,
        'motivo' => $motivo,
        'descricao' => $descricao
    ]);
    
    // Enviar notificação para o administrador
    $db->query("INSERT INTO notificacoes (usuario_id, tipo, mensagem, link, data_criacao, lida) 
               VALUES (:admin_id, 'reporte', :mensagem, :link, NOW(), 0)", [
        'admin_id' => 1, // ID do administrador
        'mensagem' => "Novo reporte de " . ($tipo_usuario_reportado == 'talento' ? 'talento' : 'empresa'),
        'link' => SITE_URL . "/?route=gerenciar_reportes"
    ]);
    
    // Definir mensagem de sucesso
    $_SESSION['flash_message'] = 'Reporte enviado com sucesso. Nossa equipe irá analisar o caso em breve.';
    $_SESSION['flash_type'] = 'success';
} catch (Exception $e) {
    // Definir mensagem de erro
    $_SESSION['flash_message'] = 'Erro ao processar reporte: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

// Redirecionar de volta para a página anterior
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>
