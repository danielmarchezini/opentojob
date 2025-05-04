<?php
/**
 * API para obter detalhes de uma vaga
 * 
 * Este arquivo retorna os detalhes de uma vaga específica em formato JSON
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

// Registrar a chamada da API para depuração
error_log("API vaga_detalhe.php - Chamada recebida com ID: {$_GET['id']}");

// Verificar se o usuário está logado e é um administrador
// Comentado temporariamente para depuração
/*
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id']) || $_SESSION['usuario']['tipo'] !== 'admin') {
    error_log("API vaga_detalhe.php - Acesso não autorizado");
    jsonResponse(false, 'Acesso não autorizado');
}
*/

// Verifica se o ID da vaga foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    error_log("API vaga_detalhe.php - ID da vaga não fornecido");
    jsonResponse(false, 'ID da vaga não fornecido');
}

$vaga_id = (int) $_GET['id'];

// Registrar a consulta para depuração
error_log("API vaga_detalhe.php - Consultando vaga ID: {$vaga_id}");

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Buscar detalhes da vaga
    $query = "
        SELECT 
            v.id, 
            v.titulo, 
            v.tipo_vaga,
            v.empresa_id, 
            v.empresa_externa,
            v.cidade, 
            v.estado, 
            v.tipo_contrato, 
            v.regime_trabalho, 
            v.nivel_experiencia, 
            v.status, 
            v.data_publicacao, 
            v.mostrar_salario, 
            v.salario_min, 
            v.salario_max, 
            v.descricao, 
            v.requisitos, 
            v.beneficios,
            u.nome as empresa_nome, 
            e.razao_social
        FROM vagas v
        LEFT JOIN usuarios u ON v.empresa_id = u.id
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE v.id = :id
    ";
    
    $params = ['id' => $vaga_id];
    $vaga = $db->fetch($query, $params);
    
    // Registrar a consulta para depuração
    error_log("API vaga_detalhe.php - Consultando vaga ID: {$vaga_id}");
    
    if (!$vaga) {
        error_log("API vaga_detalhe.php - Vaga não encontrada para ID: {$vaga_id}");
        jsonResponse(false, 'Vaga não encontrada');
    }
    
    // Garantir que todos os campos necessários existam
    $vaga = array_merge([
        'id' => null,
        'titulo' => '',
        'tipo_vaga' => 'interna',
        'empresa_id' => null,
        'empresa_externa' => '',
        'empresa_nome' => '',
        'razao_social' => '',
        'cidade' => '',
        'estado' => '',
        'tipo_contrato' => '',
        'regime_trabalho' => '',
        'nivel_experiencia' => '',
        'status' => 'pendente',
        'data_publicacao' => date('Y-m-d H:i:s'),
        'mostrar_salario' => 0,
        'salario_min' => 0,
        'salario_max' => 0,
        'descricao' => '',
        'requisitos' => '',
        'beneficios' => ''
    ], $vaga);
    
    error_log("API vaga_detalhe.php - Vaga encontrada: " . json_encode($vaga));
    
    // Retornar dados da vaga em formato JSON
    jsonResponse(true, 'Dados obtidos com sucesso', ['vaga' => $vaga]);
    
} catch (PDOException $e) {
    error_log("API vaga_detalhe.php - Erro ao buscar vaga: " . $e->getMessage());
    jsonResponse(false, 'Erro ao buscar detalhes da vaga: ' . $e->getMessage());
}
?>
