<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Função para retornar resposta JSON padronizada
function jsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Verificar se o usuário está logado
// Temporariamente desabilitado para depuração
/*
if (!isset($_SESSION['usuario']['id']) || !isset($_SESSION['usuario']['tipo'])) {
    jsonResponse(false, 'Acesso não autorizado');
}
*/

// Verificar se o ID da avaliação foi fornecido
if (!isset($_GET['id'])) {
    jsonResponse(false, 'ID da avaliação não fornecido');
}

$avaliacao_id = (int) $_GET['id'];

// Registrar a consulta para depuração
error_log("API avaliacao_detalhe.php - Consultando avaliação ID: {$avaliacao_id}");

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Buscar detalhes da avaliação
    $query = "
        SELECT 
            a.id, 
            a.talento_id, 
            a.avaliador_id, 
            a.nota, 
            a.titulo,
            a.comentario, 
            a.data_avaliacao, 
            a.status,
            u.nome as talento_nome, 
            t.profissao,
            ua.nome as avaliador_nome,
            e.razao_social as empresa_razao_social
        FROM avaliacoes a
        JOIN usuarios u ON a.talento_id = u.id
        LEFT JOIN talentos t ON u.id = t.usuario_id
        JOIN usuarios ua ON a.avaliador_id = ua.id
        LEFT JOIN empresas e ON ua.id = e.usuario_id
        WHERE a.id = :id
    ";
    
    $params = ['id' => $avaliacao_id];
    $avaliacao = $db->fetch($query, $params);
    
    // Registrar a consulta para depuração
    error_log("API avaliacao_detalhe.php - Consultando avaliação ID: {$avaliacao_id}");
    
    if (!$avaliacao) {
        error_log("API avaliacao_detalhe.php - Avaliação não encontrada para ID: {$avaliacao_id}");
        jsonResponse(false, 'Avaliação não encontrada');
    }
    
    // Garantir que todos os campos necessários existam
    $avaliacao = array_merge([
        'id' => null,
        'talento_id' => null,
        'avaliador_id' => null,
        'nota' => 0,
        'titulo' => '',
        'comentario' => '',
        'data_avaliacao' => date('Y-m-d H:i:s'),
        'status' => 'pendente',
        'talento_nome' => '',
        'profissao' => '',
        'avaliador_nome' => '',
        'empresa_razao_social' => ''
    ], $avaliacao);
    
    error_log("API avaliacao_detalhe.php - Avaliação encontrada: " . json_encode($avaliacao));
    
    // Retornar dados da avaliação em formato JSON
    jsonResponse(true, 'Dados obtidos com sucesso', ['avaliacao' => $avaliacao]);
    
} catch (PDOException $e) {
    error_log("API avaliacao_detalhe.php - Erro ao buscar avaliação: " . $e->getMessage());
    jsonResponse(false, 'Erro ao buscar detalhes da avaliação: ' . $e->getMessage());
}
