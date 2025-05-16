<?php
/**
 * API para obter detalhes de uma avaliação
 * 
 * Este endpoint retorna os detalhes de uma avaliação específica
 * baseado no ID fornecido via parâmetro GET.
 * 
 * @param int $_GET['id'] ID da avaliação a ser consultada
 * @return json Dados da avaliação em formato JSON
 */

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID da avaliação não fornecido ou inválido'
    ]);
    exit;
}

$avaliacao_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Buscar detalhes da avaliação
    $avaliacao = $db->fetch("
        SELECT a.*, 
               u.nome as talento_nome, 
               t.profissao,
               COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
               a.pontuacao as nota,
               a.data_avaliacao as data_criacao,
               CASE 
                   WHEN a.status = 'aprovada' OR a.aprovada = 1 THEN 1
                   ELSE 0
               END as aprovada
        FROM avaliacoes a
        JOIN usuarios u ON a.talento_id = u.id
        LEFT JOIN talentos t ON u.id = t.usuario_id
        LEFT JOIN usuarios e ON a.empresa_id = e.id
        WHERE a.id = :id
    ", [
        'id' => $avaliacao_id
    ]);
    
    if (!$avaliacao) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Avaliação não encontrada'
        ]);
        exit;
    }
    
    // Verificar e ajustar campos nulos ou ausentes
    if (!isset($avaliacao['nome_avaliador']) || empty($avaliacao['nome_avaliador'])) {
        $avaliacao['nome_avaliador'] = 'Anônimo';
    }
    
    if (!isset($avaliacao['linkedin_avaliador'])) {
        $avaliacao['linkedin_avaliador'] = '';
    }
    
    if (!isset($avaliacao['avaliacao']) && isset($avaliacao['texto'])) {
        $avaliacao['avaliacao'] = $avaliacao['texto'];
    } else if (!isset($avaliacao['avaliacao']) && isset($avaliacao['comentario'])) {
        $avaliacao['avaliacao'] = $avaliacao['comentario'];
    } else if (!isset($avaliacao['avaliacao'])) {
        $avaliacao['avaliacao'] = 'Sem comentários';
    }
    
    // Retornar dados em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'avaliacao' => $avaliacao
    ]);
    
} catch (Exception $e) {
    // Log do erro
    error_log('Erro ao buscar detalhes da avaliação: ' . $e->getMessage());
    
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar detalhes da avaliação: ' . $e->getMessage()
    ]);
}
?>
