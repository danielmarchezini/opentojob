<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Função para retornar erro
function returnError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

try {
    // Incluir arquivos necessários
    require_once '../config/config.php';
    require_once '../includes/Database.php';
    require_once '../includes/Auth.php';

    // Verificar se o usuário está logado e é um administrador
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        returnError('Acesso restrito. Faça login como administrador.', 403);
    }

    // Obter instância do banco de dados
    $db = Database::getInstance();

    // Verificar se a ação foi especificada
    if (!isset($_GET['acao'])) {
        returnError('Ação não especificada');
    }

    $acao = $_GET['acao'];

    // Processar a ação solicitada
    switch ($acao) {
        case 'listar':
            // Listar todos os modelos de e-mail
            $modelos = $db->fetchAll("
                SELECT id, codigo, nome, assunto, data_criacao, data_atualizacao
                FROM modelos_email
                ORDER BY nome ASC
            ");
            
            echo json_encode([
                'success' => true,
                'modelos' => $modelos
            ]);
            break;
            
        case 'obter':
            // Verificar se o ID foi especificado
            if (!isset($_GET['id'])) {
                returnError('ID do modelo não especificado');
            }
            
            $id = (int)$_GET['id'];
            
            // Obter detalhes do modelo
            $modelo = $db->fetch("
                SELECT *
                FROM modelos_email
                WHERE id = :id
            ", [
                'id' => $id
            ]);
            
            if (!$modelo) {
                returnError('Modelo não encontrado', 404);
            }
            
            echo json_encode([
                'success' => true,
                'modelo' => $modelo
            ]);
            break;
            
        default:
            returnError('Ação inválida');
    }
} catch (PDOException $e) {
    returnError('Erro de banco de dados: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    returnError('Erro: ' . $e->getMessage(), 500);
}
?>
