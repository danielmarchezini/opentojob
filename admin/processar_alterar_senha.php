<?php
/**
 * Processador para alteração de senha de usuários pelo administrador
 */

// Iniciar sessão
session_start();

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';

// Função para retornar resposta JSON e encerrar
function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método não permitido');
}

// Obter dados do formulário
$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
$nova_senha = isset($_POST['nova_senha']) ? trim($_POST['nova_senha']) : '';

// Validar dados
if ($usuario_id <= 0) {
    jsonResponse(false, 'ID de usuário inválido');
}

if (strlen($nova_senha) < 6) {
    jsonResponse(false, 'A senha deve ter pelo menos 6 caracteres');
}

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Verificar se o usuário existe
    $usuario = $db->fetch("SELECT id FROM usuarios WHERE id = :id", [
        'id' => $usuario_id
    ]);
    
    if (!$usuario) {
        jsonResponse(false, 'Usuário não encontrado');
    }
    
    // Gerar hash da nova senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // Atualizar senha na tabela de usuários (para compatibilidade)
    $db->query("UPDATE usuarios SET senha = :senha WHERE id = :id", [
        'senha' => md5($nova_senha),
        'id' => $usuario_id
    ]);
    
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
    } else {
        // Inserir nova senha
        $db->query("INSERT INTO usuarios_senha (usuario_id, senha) VALUES (:usuario_id, :senha)", [
            'usuario_id' => $usuario_id,
            'senha' => $senha_hash
        ]);
    }
    
    jsonResponse(true, 'Senha alterada com sucesso');
} catch (Exception $e) {
    error_log("Erro ao alterar senha: " . $e->getMessage());
    jsonResponse(false, 'Erro ao alterar senha: ' . $e->getMessage());
}
?>
