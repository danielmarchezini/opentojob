<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../../config/config.php';
require_once '../../includes/Database.php';

// Configuração de cabeçalhos para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Ajuste conforme necessário para segurança
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Verificar método OPTIONS (para CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar chave de API para autenticação
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$webhook_secret = getenv('WEBHOOK_SECRET') ?: 'seu_webhook_secret_aqui'; // Substitua por um segredo real em produção

if ($api_key !== $webhook_secret) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Obter dados da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verificar tipo de webhook
$webhook_type = isset($_GET['type']) ? $_GET['type'] : '';

// Processar diferentes tipos de webhooks
switch ($webhook_type) {
    case 'talento_cadastro':
        // Webhook para quando um talento é cadastrado
        processar_cadastro_talento($data, $db);
        break;
        
    case 'vaga_cadastro':
        // Webhook para quando uma vaga é cadastrada
        processar_cadastro_vaga($data, $db);
        break;
        
    case 'atualizar_status':
        // Webhook para atualizar status de usuários
        atualizar_status_usuario($data, $db);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de webhook inválido ou não especificado'
        ]);
        exit;
}

// Função para processar cadastro de talento
function processar_cadastro_talento($data, $db) {
    // Verificar se os dados necessários foram fornecidos
    if (!isset($data['usuario_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do usuário não fornecido'
        ]);
        return;
    }
    
    $usuario_id = $data['usuario_id'];
    
    // Buscar dados do talento
    $talento = $db->fetchRow("
        SELECT u.nome, u.email, t.telefone
        FROM usuarios u
        LEFT JOIN talentos t ON u.id = t.usuario_id
        WHERE u.id = :id AND u.tipo = 'talento'
    ", [
        'id' => $usuario_id
    ]);
    
    if (!$talento) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Talento não encontrado'
        ]);
        return;
    }
    
    // Registrar evento no log (para debug)
    $db->insert('logs_sistema', [
        'tipo' => 'webhook',
        'acao' => 'talento_cadastro',
        'descricao' => 'Webhook de cadastro de talento processado para o usuário ID: ' . $usuario_id,
        'data' => date('Y-m-d H:i:s')
    ]);
    
    // Retornar sucesso com dados do talento
    echo json_encode([
        'success' => true,
        'message' => 'Webhook de cadastro de talento processado com sucesso',
        'data' => [
            'talento' => $talento
        ]
    ]);
}

// Função para processar cadastro de vaga
function processar_cadastro_vaga($data, $db) {
    // Verificar se os dados necessários foram fornecidos
    if (!isset($data['vaga_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da vaga não fornecido'
        ]);
        return;
    }
    
    $vaga_id = $data['vaga_id'];
    
    // Buscar dados da vaga
    $vaga = $db->fetchRow("
        SELECT v.*, e.nome as empresa_nome
        FROM vagas v
        LEFT JOIN empresas e ON v.empresa_id = e.id
        WHERE v.id = :id
    ", [
        'id' => $vaga_id
    ]);
    
    if (!$vaga) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Vaga não encontrada'
        ]);
        return;
    }
    
    // Registrar evento no log (para debug)
    $db->insert('logs_sistema', [
        'tipo' => 'webhook',
        'acao' => 'vaga_cadastro',
        'descricao' => 'Webhook de cadastro de vaga processado para a vaga ID: ' . $vaga_id,
        'data' => date('Y-m-d H:i:s')
    ]);
    
    // Retornar sucesso com dados da vaga
    echo json_encode([
        'success' => true,
        'message' => 'Webhook de cadastro de vaga processado com sucesso',
        'data' => [
            'vaga' => $vaga
        ]
    ]);
}

// Função para atualizar status de usuário
function atualizar_status_usuario($data, $db) {
    // Verificar se os dados necessários foram fornecidos
    if (!isset($data['usuario_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do usuário ou status não fornecido'
        ]);
        return;
    }
    
    $usuario_id = $data['usuario_id'];
    $status = $data['status'];
    
    // Validar status
    $status_validos = ['ativo', 'inativo', 'pendente', 'bloqueado'];
    if (!in_array($status, $status_validos)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Status inválido. Valores aceitos: ' . implode(', ', $status_validos)
        ]);
        return;
    }
    
    // Verificar se o usuário existe
    $usuario = $db->fetchRow("
        SELECT id, nome, tipo
        FROM usuarios
        WHERE id = :id
    ", [
        'id' => $usuario_id
    ]);
    
    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não encontrado'
        ]);
        return;
    }
    
    // Atualizar status do usuário
    $db->update('usuarios', [
        'status' => $status
    ], 'id = :id', [
        'id' => $usuario_id
    ]);
    
    // Registrar evento no log (para debug)
    $db->insert('logs_sistema', [
        'tipo' => 'webhook',
        'acao' => 'atualizar_status',
        'descricao' => 'Status do usuário ID: ' . $usuario_id . ' atualizado para: ' . $status,
        'data' => date('Y-m-d H:i:s')
    ]);
    
    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Status do usuário atualizado com sucesso',
        'data' => [
            'usuario_id' => $usuario_id,
            'nome' => $usuario['nome'],
            'tipo' => $usuario['tipo'],
            'status' => $status
        ]
    ]);
}
