<?php
/**
 * Obter detalhes do usuário para exibição no modal
 * Este arquivo retorna os detalhes de um usuário específico em formato JSON
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

// Obter dados do usuário
$db = Database::getInstance();
try {
    // Obter dados básicos do usuário
    $usuario = $db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $usuario_id]);
    
    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Formatar a data de cadastro
    if (isset($usuario['data_cadastro'])) {
        $data_cadastro = new DateTime($usuario['data_cadastro']);
        $usuario['data_cadastro'] = $data_cadastro->format('d/m/Y H:i:s');
    }
    
    // Preparar resposta
    $resposta = [
        'success' => true,
        'usuario' => $usuario
    ];
    
    // Obter informações adicionais baseadas no tipo de usuário
    if ($usuario['tipo'] === 'talento') {
        // Obter perfil do talento
        $perfil_talento = $db->fetch("SELECT * FROM perfil_talentos WHERE usuario_id = :usuario_id", ['usuario_id' => $usuario_id]);
        if ($perfil_talento) {
            $resposta['perfil_talento'] = $perfil_talento;
        }
    } elseif ($usuario['tipo'] === 'empresa') {
        // Obter perfil da empresa
        $perfil_empresa = $db->fetch("SELECT * FROM perfil_empresas WHERE usuario_id = :usuario_id", ['usuario_id' => $usuario_id]);
        if ($perfil_empresa) {
            $resposta['perfil_empresa'] = $perfil_empresa;
        }
    }
    
    echo json_encode($resposta);
    
} catch (Exception $e) {
    // Registrar erro
    error_log('Erro ao obter detalhes do usuário: ' . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Erro ao obter detalhes do usuário: ' . $e->getMessage()]);
}
?>
