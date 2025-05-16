<?php
// Definir caminho base
$base_path = dirname(dirname(__FILE__));

// Incluir arquivos necessários
require_once $base_path . '/config/config.php';
require_once $base_path . '/includes/Database.php';
require_once $base_path . '/includes/functions.php';

// Verificar se o usuário está logado e é admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . SITE_URL . "/admin/?page=login");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar ação de aprovação
if (isset($_GET['aprovar']) && is_numeric($_GET['aprovar'])) {
    $avaliacao_id = (int)$_GET['aprovar'];
    
    try {
        // Obter os dados da avaliação
        $avaliacao = $db->fetch("SELECT * FROM avaliacoes WHERE id = :id", ['id' => $avaliacao_id]);
        
        if (!$avaliacao) {
            throw new Exception("Avaliação não encontrada");
        }
        
        // Verificar se a coluna status existe
        $colunas = $db->query("DESCRIBE avaliacoes")->fetchAll(PDO::FETCH_COLUMN);
        
        // Atualizar status na tabela avaliacoes
        if (in_array('status', $colunas)) {
            $db->update('avaliacoes', [
                'status' => 'aprovada',
                'aprovada' => 1
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
        } else {
            $db->update('avaliacoes', [
                'aprovada' => 1
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
        }
        
        // Inserir ou atualizar na tabela avaliacoes_talentos
        $avaliacao_existente = $db->fetch("SELECT id FROM avaliacoes_talentos WHERE talento_id = :talento_id AND empresa_id = :empresa_id", [
            'talento_id' => $avaliacao['talento_id'],
            'empresa_id' => $avaliacao['empresa_id'] ?? 0
        ]);
        
        $dados = [
            'talento_id' => $avaliacao['talento_id'],
            'empresa_id' => $avaliacao['empresa_id'] ?? null,
            'nome_avaliador' => $avaliacao['nome_avaliador'] ?? null,
            'email_avaliador' => $avaliacao['email_avaliador'] ?? null,
            'linkedin_avaliador' => $avaliacao['linkedin_avaliador'] ?? null,
            'pontuacao' => $avaliacao['pontuacao'] ?? $avaliacao['nota'] ?? 0,
            'avaliacao' => $avaliacao['avaliacao'] ?? $avaliacao['texto'] ?? $avaliacao['comentario'] ?? '',
            'data_avaliacao' => $avaliacao['data_avaliacao'] ?? date('Y-m-d H:i:s'),
            'status' => 'aprovada',
            'publica' => 1,
            'aprovada' => 1
        ];
        
        if ($avaliacao_existente) {
            $db->update('avaliacoes_talentos', $dados, 'id = :id', ['id' => $avaliacao_existente['id']]);
        } else {
            $db->insert('avaliacoes_talentos', $dados);
        }
        
        $_SESSION['flash_message'] = "Avaliação aprovada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Erro ao aprovar avaliação: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: " . SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=pendentes");
    exit;
}

// Processar ação de rejeição
if (isset($_GET['rejeitar']) && is_numeric($_GET['rejeitar'])) {
    $avaliacao_id = (int)$_GET['rejeitar'];
    
    try {
        // Verificar se a coluna status existe
        $colunas = $db->query("DESCRIBE avaliacoes")->fetchAll(PDO::FETCH_COLUMN);
        
        // Atualizar status na tabela avaliacoes
        if (in_array('status', $colunas)) {
            $db->update('avaliacoes', [
                'status' => 'rejeitada',
                'aprovada' => 0
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
        } else {
            $db->update('avaliacoes', [
                'aprovada' => 0
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
        }
        
        $_SESSION['flash_message'] = "Avaliação rejeitada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Erro ao rejeitar avaliação: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    header("Location: " . SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=pendentes");
    exit;
}

// Se nenhuma ação foi especificada, redirecionar para a página de gerenciamento
header("Location: " . SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=pendentes");
exit;
?>
