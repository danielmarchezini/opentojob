<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verificar se o usuário está logado e é uma empresa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'empresa') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Apenas empresas podem favoritar talentos'
    ]);
    exit;
}

// Adicionar debug para verificar a sessão
error_log("Sessão do usuário: " . print_r($_SESSION, true));

// Obter ID da empresa logada
$empresa_id = $_SESSION['user_id'];

// Verificar se o ID do talento foi fornecido
if (!isset($_POST['talento_id']) || empty($_POST['talento_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID do talento não fornecido'
    ]);
    exit;
}

$talento_id = intval($_POST['talento_id']);
$acao = isset($_POST['acao']) ? $_POST['acao'] : 'adicionar';

// Obter instância do banco de dados
$db = Database::getInstance();

// Criar a tabela talentos_favoritos se ela não existir
try {
    
    // Usar CREATE TABLE IF NOT EXISTS para criar a tabela se ela não existir
    $db->execute("
        CREATE TABLE IF NOT EXISTS `talentos_favoritos` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `empresa_id` int(11) NOT NULL COMMENT 'ID do usuário empresa',
          `talento_id` int(11) NOT NULL COMMENT 'ID do talento favoritado',
          `data_favoritado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `notas` text COMMENT 'Notas opcionais sobre o talento',
          PRIMARY KEY (`id`),
          UNIQUE KEY `empresa_talento_unique` (`empresa_id`, `talento_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (PDOException $e) {
    error_log("Erro ao verificar/criar tabela: " . $e->getMessage());
    // Continuar mesmo se houver erro, pois a tabela pode já existir de outra forma
}

try {
    // Verificar se o talento existe
    $talento_existe = $db->fetchColumn("
        SELECT COUNT(*) FROM usuarios 
        WHERE id = :talento_id AND tipo = 'talento' AND status = 'ativo'
    ", ['talento_id' => $talento_id]);
    
    if (!$talento_existe) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Talento não encontrado'
        ]);
        exit;
    }
    
    // Verificar se já está favoritado
    $ja_favoritado = $db->fetchColumn("
        SELECT COUNT(*) FROM talentos_favoritos 
        WHERE empresa_id = :empresa_id AND talento_id = :talento_id
    ", [
        'empresa_id' => $empresa_id,
        'talento_id' => $talento_id
    ]);
    
    if ($acao === 'adicionar') {
        if ($ja_favoritado) {
            echo json_encode([
                'status' => 'info',
                'message' => 'Este talento já está nos seus favoritos'
            ]);
            exit;
        }
        
        // Adicionar aos favoritos
        $db->execute("
            INSERT INTO talentos_favoritos (empresa_id, talento_id)
            VALUES (:empresa_id, :talento_id)
        ", [
            'empresa_id' => $empresa_id,
            'talento_id' => $talento_id
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Talento adicionado aos favoritos',
            'is_favorito' => true
        ]);
    } else if ($acao === 'remover') {
        if (!$ja_favoritado) {
            echo json_encode([
                'status' => 'info',
                'message' => 'Este talento não está nos seus favoritos'
            ]);
            exit;
        }
        
        // Remover dos favoritos
        $db->execute("
            DELETE FROM talentos_favoritos 
            WHERE empresa_id = :empresa_id AND talento_id = :talento_id
        ", [
            'empresa_id' => $empresa_id,
            'talento_id' => $talento_id
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Talento removido dos favoritos',
            'is_favorito' => false
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Ação inválida'
        ]);
    }
} catch (PDOException $e) {
    error_log("Erro ao processar favorito: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar a solicitação: ' . $e->getMessage()
    ]);
}
