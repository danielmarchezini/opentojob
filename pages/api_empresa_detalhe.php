<?php
// Verificar se o usuário está logado como admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Verificar se o ID da empresa foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da empresa não fornecido'
    ]);
    exit;
}

$empresa_id = (int)$_GET['id'];

try {
    // Obter instância do banco de dados
    $db = Database::getInstance();
    
    // Buscar dados da empresa
    $empresa = $db->fetch("
        SELECT 
            u.id, u.nome, u.email, u.status, u.data_cadastro, 
            e.id as empresa_id, e.razao_social as nome_empresa, e.cnpj, e.segmento, 
            e.descricao, e.logo, e.website, e.telefone, e.endereco, e.cidade, e.estado, e.cep
        FROM usuarios u
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE u.id = :id AND u.tipo = 'empresa'
    ", ['id' => $empresa_id]);
    
    if (!$empresa) {
        echo json_encode([
            'success' => false,
            'message' => 'Empresa não encontrada'
        ]);
        exit;
    }
    
    // Contar total de vagas da empresa
    $total_vagas = $db->fetchColumn("
        SELECT COUNT(*) FROM vagas WHERE empresa_id = :empresa_id
    ", ['empresa_id' => $empresa_id]);
    
    $empresa['total_vagas'] = $total_vagas;
    
    // Retornar dados da empresa
    echo json_encode([
        'success' => true,
        'data' => [
            'empresa' => $empresa
        ]
    ]);
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro ao buscar detalhes da empresa: " . $e->getMessage());
    
    // Retornar mensagem de erro
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar detalhes da empresa: ' . $e->getMessage()
    ]);
}
?>
