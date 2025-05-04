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
        'message' => 'Acesso restrito. Faça login como administrador.'
    ]);
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se foi fornecido um ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID do talento não fornecido.'
    ]);
    exit;
}

$talento_id = (int)$_GET['id'];

// Buscar dados do talento com todos os campos explicitamente
$talento = $db->fetchRow("
    SELECT 
        u.id, u.nome, u.email, u.tipo, u.status, u.data_cadastro,
        t.profissao, t.experiencia, t.apresentacao, t.mostrar_perfil, t.telefone,
        t.foto_perfil, t.cidade, t.estado
    FROM usuarios u
    LEFT JOIN talentos t ON u.id = t.usuario_id
    WHERE u.id = :id AND u.tipo = 'talento'
", [
    'id' => $talento_id
]);

if (!$talento) {
    // Retornar erro em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Talento não encontrado.'
    ]);
    exit;
}

// Retornar dados em formato JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => [
        'talento' => $talento
    ]
]);
exit;
