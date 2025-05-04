<?php
// Inclui o arquivo de configuração
require_once '../includes/config.php';
require_once '../includes/Database.php';

// Verifica se o ID da vaga foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID da vaga não fornecido'
    ]);
    exit;
}

$id = (int) $_GET['id'];

// Conecta ao banco de dados
$db = Database::getInstance();

try {
    // Verificar se a vaga existe
    $vaga = $db->fetch("SELECT id, titulo FROM vagas WHERE id = :id", ['id' => $id]);
    
    if (!$vaga) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Vaga não encontrada'
        ]);
        exit;
    }
    
    // Excluir a vaga
    $result = $db->execute("DELETE FROM vagas WHERE id = :id", ['id' => $id]);
    
    if ($result) {
        // Registrar a ação no log
        $usuario_id = $_SESSION['usuario']['id'];
        $usuario_nome = $_SESSION['usuario']['nome'];
        $vaga_titulo = $vaga['titulo'];
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $db->execute(
            "INSERT INTO logs_sistema (usuario_id, acao, descricao, ip, data_hora) VALUES (:usuario_id, :acao, :descricao, :ip, NOW())",
            [
                'usuario_id' => $usuario_id,
                'acao' => 'excluir_vaga',
                'descricao' => "Usuário {$usuario_nome} excluiu a vaga '{$vaga_titulo}' (ID: {$id})",
                'ip' => $ip
            ]
        );
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Vaga excluída com sucesso'
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao excluir vaga'
        ]);
    }
} catch (PDOException $e) {
    // Registrar o erro em log para depuração
    error_log('Erro na API excluir_vaga.php: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir vaga: ' . $e->getMessage()
    ]);
}
?>
