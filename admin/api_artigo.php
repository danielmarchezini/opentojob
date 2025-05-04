<?php
// API simplificada para obter detalhes de um artigo do blog
// Garantir que nenhum erro ou aviso seja exibido
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Definir o cabeçalho para JSON
header('Content-Type: application/json');

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o ID do artigo foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do artigo não fornecido'
    ]);
    exit;
}

// Obter ID do artigo
$artigo_id = (int)$_GET['id'];

try {
    // Obter instância do banco de dados
    $db = Database::getInstance();
    
    // Buscar detalhes do artigo
    $artigo = $db->fetch("
        SELECT * FROM artigos_blog WHERE id = :id
    ", [
        'id' => $artigo_id
    ]);
    
    if (!$artigo) {
        echo json_encode([
            'success' => false,
            'message' => 'Artigo não encontrado'
        ]);
        exit;
    }
    
    // Retornar dados do artigo no formato exato esperado pelo JavaScript
    echo json_encode([
        'success' => true,
        'data' => [
            'artigo' => $artigo
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar artigo: ' . $e->getMessage()
    ]);
}
