<?php
/**
 * Processador para alteração de senha de usuários pelo administrador
 * Atualizado para suportar requisições AJAX
 */

// Iniciar sessão
session_start();

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit;
}

// Verificar se a ação é alterar_senha
if (!isset($_POST['acao']) || $_POST['acao'] !== 'alterar_senha') {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Ação inválida'
    ]);
    exit;
}

// Obter dados do formulário
$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
$nova_senha = isset($_POST['nova_senha']) ? trim($_POST['nova_senha']) : '';

// Validar dados
if ($usuario_id <= 0) {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuário inválido'
    ]);
    exit;
}

if (strlen($nova_senha) < 6) {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'A senha deve ter pelo menos 6 caracteres'
    ]);
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Verificar se o usuário existe
    $usuario = $db->fetch("SELECT id FROM usuarios WHERE id = :id", [
        'id' => $usuario_id
    ]);
    
    if (!$usuario) {
        // Retornar erro em formato JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não encontrado'
        ]);
        exit;
    }
    
    // Atualizar senha na tabela de usuários (para compatibilidade)
    $db->query("UPDATE usuarios SET senha = :senha WHERE id = :id", [
        'senha' => md5($nova_senha),
        'id' => $usuario_id
    ]);
    
    // Verificar se a tabela usuarios_senha existe
    try {
        $tabela_existe = $db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'usuarios_senha'");
        
        if ($tabela_existe) {
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
            } else {
                // Inserir nova senha
                $db->query("INSERT INTO usuarios_senha (usuario_id, senha) VALUES (:usuario_id, :senha)", [
                    'usuario_id' => $usuario_id,
                    'senha' => $senha_hash
                ]);
            }
        }
    } catch (Exception $e) {
        // Ignorar erros relacionados à tabela usuarios_senha
        error_log("Aviso: Tabela usuarios_senha não encontrada ou erro ao acessá-la: " . $e->getMessage());
    }
    
    // Retornar sucesso em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Senha alterada com sucesso'
    ]);
} catch (Exception $e) {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao alterar senha: ' . $e->getMessage()
    ]);
}
exit;
