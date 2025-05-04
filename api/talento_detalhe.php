<?php
/**
 * API para obter detalhes de um talento
 * 
 * Este arquivo retorna os detalhes de um talento específico em formato JSON
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

// Verificar se o usuário está logado e é um administrador
// Comentado temporariamente para depuração
/*
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id']) || $_SESSION['usuario']['tipo'] !== 'admin') {
    jsonResponse(false, 'Acesso não autorizado');
}
*/

// Registrar a chamada da API para depuração
error_log("API talento_detalhe.php - Chamada recebida com ID: {$_GET['id']}");

// Obter ID do talento
$talento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($talento_id <= 0) {
    jsonResponse(false, 'ID de talento inválido');
}

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Buscar detalhes do talento
    $query = "
        SELECT 
            u.id, 
            u.nome, 
            u.email, 
            u.tipo, 
            u.status, 
            u.data_cadastro,
            t.profissao, 
            t.experiencia, 
            t.apresentacao, 
            t.habilidades, 
            t.mostrar_perfil, 
            t.curriculo, 
            t.foto_perfil
        FROM usuarios u
        LEFT JOIN talentos t ON u.id = t.usuario_id
        WHERE u.id = :id AND u.tipo = 'talento'
    ";
    
    $params = ['id' => $talento_id];
    $talento = $db->fetch($query, $params);
    
    // Registrar a consulta para depuração
    error_log("API talento_detalhe.php - Consultando talento ID: {$talento_id}");
    
    if (!$talento) {
        error_log("API talento_detalhe.php - Talento não encontrado para ID: {$talento_id}");
        jsonResponse(false, 'Talento não encontrado');
    }
    
    // Garantir que todos os campos necessários existam
    $talento = array_merge([
        'id' => null,
        'nome' => '',
        'email' => '',
        'tipo' => 'talento',
        'status' => 'ativo',
        'data_cadastro' => date('Y-m-d H:i:s'),
        'profissao' => '',
        'experiencia' => '',
        'apresentacao' => '',
        'habilidades' => '',
        'mostrar_perfil' => 0,
        'curriculo' => '',
        'foto_perfil' => ''
    ], $talento);
    
    error_log("API talento_detalhe.php - Talento encontrado: " . json_encode($talento));
    
    // Retornar dados do talento em formato JSON
    jsonResponse(true, 'Dados obtidos com sucesso', ['talento' => $talento]);
    
} catch (PDOException $e) {
    error_log("API talento_detalhe.php - Erro ao buscar talento: " . $e->getMessage());
    jsonResponse(false, 'Erro ao buscar detalhes do talento: ' . $e->getMessage());
}
