<?php
// Configurar para não exibir erros na saída, mas registrá-los em log
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Definir cabeçalho JSON desde o início
header('Content-Type: application/json');

try {
    // Verificar se o usuário está logado e é um administrador
    session_start();
    
    // Log para depuração
    error_log("API salvar_meta_descricao.php acessada. Sessão: " . json_encode($_SESSION));
    
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/Database.php';
    require_once __DIR__ . '/../includes/Auth.php';

    // Verificar autenticação - verificando ambos os formatos de sessão
    $is_logged_in = isset($_SESSION['user_id']) || (isset($_SESSION['usuario']) && isset($_SESSION['usuario']['id']));
    $is_admin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') || 
                (isset($_SESSION['usuario']) && isset($_SESSION['usuario']['tipo']) && $_SESSION['usuario']['tipo'] === 'admin');
    
    error_log("is_logged_in: " . ($is_logged_in ? 'true' : 'false') . ", is_admin: " . ($is_admin ? 'true' : 'false'));
    
    if (!$is_logged_in || !$is_admin) {
        echo json_encode([
            'success' => false,
            'message' => 'Acesso restrito. Faça login como administrador.'
        ]);
        exit;
    }

    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Método não permitido. Use POST.'
        ]);
        exit;
    }

    // Obter dados da requisição
    $json = file_get_contents('php://input');
    error_log("Dados recebidos: " . $json);
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao decodificar JSON: ' . json_last_error_msg()
        ]);
        exit;
    }

    if (!$data || !isset($data['pagina']) || !isset($data['descricao'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos. Forneça pagina e descricao.'
        ]);
        exit;
    }

    $pagina = trim($data['pagina']);
    $descricao = trim($data['descricao']);

    // Validar dados
    if (empty($pagina) || empty($descricao)) {
        echo json_encode([
            'success' => false,
            'message' => 'Pagina e descricao não podem estar vazios.'
        ]);
        exit;
    }

    // Obter instância do banco de dados
    $db = Database::getInstance();

    // Verificar se a tabela existe
    try {
        $db->query("CREATE TABLE IF NOT EXISTS meta_descricoes_paginas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pagina VARCHAR(255) NOT NULL UNIQUE,
            descricao TEXT NOT NULL,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        error_log("Tabela verificada/criada com sucesso");
    } catch (Exception $e) {
        error_log("Erro ao criar tabela: " . $e->getMessage());
        throw $e;
    }
    
    try {
        // Iniciar transação
        $db->beginTransaction();
        error_log("Transação iniciada");
        
        // Verificar se a página já existe
        $existente = $db->fetch("SELECT id FROM meta_descricoes_paginas WHERE pagina = :pagina", ['pagina' => $pagina]);
        error_log("Verificação de existência: " . ($existente ? 'Página existe' : 'Página não existe'));
        
        if ($existente) {
            // Atualizar
            error_log("Atualizando registro existente");
            $db->update('meta_descricoes_paginas', [
                'descricao' => $descricao,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ], 'pagina = :pagina', ['pagina' => $pagina]);
        } else {
            // Inserir
            error_log("Inserindo novo registro");
            $db->insert('meta_descricoes_paginas', [
                'pagina' => $pagina,
                'descricao' => $descricao,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Confirmar transação
        $db->commit();
        error_log("Transação confirmada com sucesso");
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        try {
            $db->rollBack();
            error_log("Transação revertida devido a erro");
        } catch (Exception $rollbackError) {
            error_log("Erro ao reverter transação: " . $rollbackError->getMessage());
        }
        throw $e;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Meta descrição salva com sucesso.'
    ]);
} catch (Exception $e) {
    error_log("Erro em salvar_meta_descricao.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar meta descrição: ' . $e->getMessage()
    ]);
}