<?php
// Verificar se a requisição é via AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Verificar se o usuário está logado
if (!Auth::isLoggedIn()) {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Obter ID da candidatura
$candidatura_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar se o ID é válido
if ($candidatura_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de candidatura inválido']);
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];
$tipo_usuario = $_SESSION['user_type'];

// Obter instância do banco de dados
$db = Database::getInstance();

try {
    // Verificar se a candidatura pertence ao usuário ou se é admin
    if ($tipo_usuario == 'talento') {
        $candidatura_usuario = $db->fetchRow("
            SELECT id FROM candidaturas 
            WHERE id = :id AND talento_id = :usuario_id
        ", [
            'id' => $candidatura_id,
            'usuario_id' => $usuario_id
        ]);
        
        if (!$candidatura_usuario) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para acessar esta candidatura']);
            exit;
        }
    } else if ($tipo_usuario == 'empresa') {
        $candidatura_empresa = $db->fetchRow("
            SELECT c.id 
            FROM candidaturas c
            JOIN vagas v ON c.vaga_id = v.id
            JOIN empresas e ON v.empresa_id = e.id
            WHERE c.id = :id AND e.usuario_id = :usuario_id
        ", [
            'id' => $candidatura_id,
            'usuario_id' => $usuario_id
        ]);
        
        if (!$candidatura_empresa) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para acessar esta candidatura']);
            exit;
        }
    } else if ($tipo_usuario != 'admin') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tipo de usuário não autorizado']);
        exit;
    }
    
    // Obter detalhes da candidatura
    $candidatura = $db->fetchRow("
        SELECT c.*, v.titulo, v.cidade, v.estado, v.tipo_contrato, v.regime_trabalho, v.nivel_experiencia,
               e.razao_social, u.nome as empresa_nome
        FROM candidaturas c
        JOIN vagas v ON c.vaga_id = v.id
        LEFT JOIN empresas e ON v.empresa_id = e.id
        LEFT JOIN usuarios u ON e.usuario_id = u.id
        WHERE c.id = :id
    ", ['id' => $candidatura_id]);
    
    if (!$candidatura) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Candidatura não encontrada']);
        exit;
    }
    
    // Se for empresa ou admin, atualizar status para visualizada se estiver como enviada
    if (($tipo_usuario == 'empresa' || $tipo_usuario == 'admin') && $candidatura['status'] == 'enviada') {
        $db->execute("
            UPDATE candidaturas 
            SET status = 'visualizada', data_atualizacao = NOW() 
            WHERE id = :id
        ", ['id' => $candidatura_id]);
        
        $candidatura['status'] = 'visualizada';
    }
    
    // Retornar dados da candidatura
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'data' => [
            'candidatura' => $candidatura
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar detalhes da candidatura: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao buscar detalhes da candidatura: ' . $e->getMessage()
    ]);
}
