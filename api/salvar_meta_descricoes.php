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
    error_log("API salvar_meta_descricoes.php acessada. Sessão: " . json_encode($_SESSION));
    
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

    if (!$data || !isset($data['meta_descricoes']) || !is_array($data['meta_descricoes'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos. Forneça um array de meta_descricoes.'
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
    
    // Iniciar transação
    try {
        $db->beginTransaction();
        error_log("Transação iniciada");
    } catch (Exception $e) {
        error_log("Erro ao iniciar transação: " . $e->getMessage());
        throw $e;
    }
    
    $count = 0;
    foreach ($data['meta_descricoes'] as $meta) {
        if (!isset($meta['pagina']) || !isset($meta['descricao'])) {
            continue;
        }
        
        $pagina = trim($meta['pagina']);
        $descricao = trim($meta['descricao']);
        
        if (empty($pagina) || empty($descricao)) {
            continue;
        }
        
        try {
            // Verificar se a página já existe
            $existente = $db->fetch("SELECT id FROM meta_descricoes_paginas WHERE pagina = :pagina", ['pagina' => $pagina]);
            
            if ($existente) {
                // Atualizar
                $db->update('meta_descricoes_paginas', [
                    'descricao' => $descricao,
                    'data_atualizacao' => date('Y-m-d H:i:s')
                ], 'pagina = :pagina', ['pagina' => $pagina]);
            } else {
                // Inserir
                $db->insert('meta_descricoes_paginas', [
                    'pagina' => $pagina,
                    'descricao' => $descricao,
                    'data_atualizacao' => date('Y-m-d H:i:s')
                ]);
            }
            $count++;
        } catch (Exception $e) {
            error_log("Erro ao processar meta descrição para página '$pagina': " . $e->getMessage());
            // Continuar com as próximas meta descrições
        }
    }
    
    // Confirmar transação
    try {
        $db->commit();
        error_log("Transação confirmada com sucesso. $count meta descrições processadas.");
    } catch (Exception $e) {
        error_log("Erro ao confirmar transação: " . $e->getMessage());
        throw $e;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Meta descrições salvas com sucesso.',
        'count' => $count
    ]);
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if (isset($db) && $db instanceof Database) {
        try {
            $db->rollBack();
            error_log("Transação revertida devido a erro");
        } catch (Exception $rollbackError) {
            error_log("Erro ao reverter transação: " . $rollbackError->getMessage());
        }
    }
    
    error_log("Erro em salvar_meta_descricoes.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar meta descrições: ' . $e->getMessage()
    ]);
}
