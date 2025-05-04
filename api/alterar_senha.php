<?php
/**
 * API para alteração de senha de usuários
 * 
 * Este arquivo permite que administradores alterem a senha de qualquer usuário
 */

// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Definir o caminho base para inclusões
$base_path = dirname(__DIR__) . '/';

// Incluir arquivos necessários
require_once $base_path . 'config/config.php';
require_once $base_path . 'includes/Database.php';

// Função para retornar resposta JSON e encerrar
function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Registrar a requisição para depuração
error_log("API alterar_senha.php - Requisição recebida: " . json_encode($_POST));

// Verificar se o usuário está logado como administrador
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id']) || $_SESSION['usuario']['tipo'] !== 'admin') {
    // Verificar se é um usuário normal tentando alterar sua própria senha
    if (!isset($_SESSION['user_id'])) {
        error_log("API alterar_senha.php - Usuário não logado");
        jsonResponse(false, 'Usuário não logado');
    }
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("API alterar_senha.php - Método não permitido: " . $_SERVER['REQUEST_METHOD']);
    jsonResponse(false, 'Método não permitido');
}

// Obter dados do formulário
$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
$nova_senha = isset($_POST['nova_senha']) ? trim($_POST['nova_senha']) : '';

// Verificar permissões
$is_admin = isset($_SESSION['usuario']) && $_SESSION['usuario']['tipo'] === 'admin';
$is_own_account = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $usuario_id;

if (!$is_admin && !$is_own_account) {
    error_log("API alterar_senha.php - Acesso não autorizado: usuário não é admin e está tentando alterar senha de outro usuário");
    jsonResponse(false, 'Acesso não autorizado');
}

// Validar dados
if ($usuario_id <= 0) {
    error_log("API alterar_senha.php - ID de usuário inválido: {$usuario_id}");
    jsonResponse(false, 'ID de usuário inválido');
}

if (strlen($nova_senha) < 6) {
    error_log("API alterar_senha.php - Senha muito curta");
    jsonResponse(false, 'A senha deve ter pelo menos 6 caracteres');
}

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Verificar se o usuário existe
    $usuario = $db->fetch("SELECT id, tipo FROM usuarios WHERE id = :id", [
        'id' => $usuario_id
    ]);
    
    if (!$usuario) {
        error_log("API alterar_senha.php - Usuário não encontrado para ID: {$usuario_id}");
        jsonResponse(false, 'Usuário não encontrado');
    }
    
    // Gerar hash da nova senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // Verificar se já existe um registro na tabela usuarios_senha
    $senha_existente = $db->fetch("SELECT usuario_id FROM usuarios_senha WHERE usuario_id = :usuario_id", [
        'usuario_id' => $usuario_id
    ]);
    
    if ($senha_existente) {
        // Atualizar senha existente
        $db->query("UPDATE usuarios_senha SET senha = :senha WHERE usuario_id = :usuario_id", [
            'senha' => $senha_hash,
            'usuario_id' => $usuario_id
        ]);
        
        error_log("API alterar_senha.php - Senha atualizada para o usuário ID: {$usuario_id}");
    } else {
        // Inserir nova senha
        $db->query("INSERT INTO usuarios_senha (usuario_id, senha) VALUES (:usuario_id, :senha)", [
            'usuario_id' => $usuario_id,
            'senha' => $senha_hash
        ]);
        
        error_log("API alterar_senha.php - Nova senha inserida para o usuário ID: {$usuario_id}");
    }
    
    // Registrar a ação no log
    $admin_id = $_SESSION['usuario']['id'];
    $admin_nome = $_SESSION['usuario']['nome'] ?? 'Desconhecido';
    
    try {
        $db->query(
            "INSERT INTO logs_sistema (usuario_id, acao, descricao, ip, data_hora) VALUES (:usuario_id, :acao, :descricao, :ip, NOW())",
            [
                'usuario_id' => $admin_id,
                'acao' => 'alterar_senha',
                'descricao' => "Administrador {$admin_nome} alterou a senha do usuário ID: {$usuario_id}",
                'ip' => $_SERVER['REMOTE_ADDR']
            ]
        );
        error_log("API alterar_senha.php - Registro de log criado para alteração de senha do usuário ID: {$usuario_id}");
    } catch (PDOException $e) {
        // Apenas registrar o erro, mas não impedir a conclusão da operação principal
        error_log("API alterar_senha.php - Erro ao registrar log: " . $e->getMessage());
    }
    
    jsonResponse(true, 'Senha alterada com sucesso');
    
} catch (PDOException $e) {
    error_log("API alterar_senha.php - Erro ao alterar senha: " . $e->getMessage());
    jsonResponse(false, 'Erro ao alterar senha: ' . $e->getMessage());
}

