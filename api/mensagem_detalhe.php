<?php
/**
 * API para obter detalhes de uma mensagem
 * 
 * Este arquivo retorna os detalhes de uma mensagem específica em formato JSON
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
require_once $base_path . 'includes/Auth.php';

// Função para retornar resposta JSON e encerrar
function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Verificar se o usuário está logado
if (!Auth::isLoggedIn()) {
    jsonResponse(false, 'Usuário não autenticado');
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Verificar se o ID da mensagem foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsonResponse(false, 'ID da mensagem não fornecido');
}

$mensagem_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter instância do banco de dados
$db = Database::getInstance();
$mensagem_id = (int)$_GET['id'];

try {
    // Buscar detalhes da mensagem
    $mensagem = $db->fetchRow("
        SELECT m.*, 
            ur.nome as remetente_nome,
            ud.nome as destinatario_nome,
            er.nome_empresa as remetente_razao_social,
            ed.nome_empresa as destinatario_razao_social
        FROM mensagens m
        LEFT JOIN usuarios ur ON m.remetente_id = ur.id
        LEFT JOIN usuarios ud ON m.destinatario_id = ud.id
        LEFT JOIN empresas er ON ur.id = er.usuario_id
        LEFT JOIN empresas ed ON ud.id = ed.usuario_id
        WHERE m.id = :id AND (m.remetente_id = :usuario_id OR m.destinatario_id = :usuario_id)
    ", [
        'id' => $mensagem_id,
        'usuario_id' => $usuario_id
    ]);
    
    if (!$mensagem) {
        jsonResponse(false, 'Mensagem não encontrada ou você não tem permissão para visualizá-la');
    }
    
    // Se o usuário for o destinatário e a mensagem não estiver lida, marcar como lida
    if ($mensagem['destinatario_id'] == $usuario_id && $mensagem['lida'] == 0) {
        $db->execute("
            UPDATE mensagens
            SET lida = 1
            WHERE id = :id
        ", ['id' => $mensagem_id]);
        
        // Atualizar o status na resposta
        $mensagem['lida'] = 1;
    }
    
    // Retornar dados da mensagem
    jsonResponse(true, 'Detalhes da mensagem obtidos com sucesso', [
        'mensagem' => $mensagem
    ]);
} catch (PDOException $e) {
    error_log("Erro ao buscar detalhes da mensagem: " . $e->getMessage());
    jsonResponse(false, 'Erro ao buscar detalhes da mensagem: ' . $e->getMessage());
}
