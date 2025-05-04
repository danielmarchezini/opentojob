<?php
/**
 * API para obter detalhes de uma empresa
 * 
 * Este arquivo retorna os detalhes de uma empresa específica em formato JSON
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
error_log("API empresa_detalhe.php - Chamada recebida com ID: {$_GET['id']}");

// Obter ID da empresa
$empresa_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($empresa_id <= 0) {
    jsonResponse(false, 'ID de empresa inválido');
}

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Buscar detalhes da empresa
    $query = "
        SELECT 
            u.id, 
            u.nome, 
            u.email, 
            u.tipo, 
            u.status, 
            u.data_cadastro,
            e.razao_social as nome_empresa, 
            e.cnpj, 
            e.segmento, 
            e.descricao, 
            e.logo,
            (SELECT COUNT(*) FROM vagas v WHERE v.empresa_id = u.id) as total_vagas
        FROM usuarios u
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE u.id = :id AND u.tipo = 'empresa'
    ";
    
    $params = ['id' => $empresa_id];
    $empresa = $db->fetch($query, $params);
    
    // Registrar a consulta para depuração
    error_log("API empresa_detalhe.php - Consultando empresa ID: {$empresa_id}");
    
    if (!$empresa) {
        error_log("API empresa_detalhe.php - Empresa não encontrada para ID: {$empresa_id}");
        jsonResponse(false, 'Empresa não encontrada');
    }
    
    // Garantir que todos os campos necessários existam
    $empresa = array_merge([
        'id' => null,
        'nome' => '',
        'email' => '',
        'tipo' => 'empresa',
        'status' => 'ativo',
        'data_cadastro' => date('Y-m-d H:i:s'),
        'nome_empresa' => '',
        'cnpj' => '',
        'segmento' => '',
        'descricao' => '',
        'logo' => '',
        'total_vagas' => 0
    ], $empresa);
    
    error_log("API empresa_detalhe.php - Empresa encontrada: " . json_encode($empresa));
    
    // Retornar dados da empresa em formato JSON
    jsonResponse(true, 'Dados obtidos com sucesso', ['empresa' => $empresa]);
    
} catch (PDOException $e) {
    error_log("API empresa_detalhe.php - Erro ao buscar empresa: " . $e->getMessage());
    jsonResponse(false, 'Erro ao buscar detalhes da empresa: ' . $e->getMessage());
}

