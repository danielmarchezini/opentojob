<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Obter ID do artigo
$artigo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($artigo_id <= 0) {
    // Retornar erro se ID for inválido
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID de artigo inválido'
    ]);
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Buscar detalhes do artigo
$artigo = $db->fetchRow("
    SELECT a.*, c.nome as categoria_nome, u.nome as autor_nome
    FROM blog_artigos a
    JOIN blog_categorias c ON a.categoria_id = c.id
    JOIN usuarios u ON a.autor_id = u.id
    WHERE a.id = :id
", [
    'id' => $artigo_id
]);

if (!$artigo) {
    // Retornar erro se artigo não for encontrado
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Artigo não encontrado'
    ]);
    exit;
}

// Retornar dados do artigo em formato JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'artigo' => $artigo
]);
exit;
