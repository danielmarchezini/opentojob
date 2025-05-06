<?php
/**
 * Router para páginas administrativas
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir configurações e funções principais
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_functions.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=entrar");
    exit;
}

// Obter a rota da URL
$route = isset($_GET['route']) ? $_GET['route'] : 'dashboard';

// Mapear rotas para páginas
$route_map = [
    'gerenciar_depoimentos_admin' => 'gerenciar_depoimentos',
    'gerenciar_equipe_admin' => 'gerenciar_equipe',
    'configurar_smtp' => 'configurar_smtp',
    'gerenciar_blog_admin' => 'gerenciar_blog',
    'gerenciar_webhooks_admin' => 'gerenciar_webhooks',
    'gerenciar_contratacoes' => 'gerenciar_contratacoes',
    'gerenciar_reportes' => 'gerenciar_reportes',
    'gerenciar_avaliacoes_admin' => 'gerenciar_avaliacoes',
    'estatisticas_interacoes' => 'estatisticas_interacoes',
    'configuracoes_admin' => 'configuracoes',
    'configuracoes_monetizacao_admin' => 'configuracoes_monetizacao'
];

// Verificar se a rota existe no mapeamento
if (isset($route_map[$route])) {
    $page = $route_map[$route];
    $page_file = __DIR__ . '/pages/' . $page . '.php';
    
    // Verificar se o arquivo da página existe
    if (file_exists($page_file)) {
        // Incluir cabeçalho e barra lateral
        include __DIR__ . '/includes/header.php';
        include __DIR__ . '/includes/sidebar.php';
        
        // Incluir a página
        include $page_file;
        
        // Incluir rodapé
        include __DIR__ . '/includes/footer.php';
    } else {
        $_SESSION['flash_message'] = "Página não encontrada: " . $page;
        $_SESSION['flash_type'] = "danger";
        header("Location: " . SITE_URL . "/?route=painel_admin");
        exit;
    }
} else {
    $_SESSION['flash_message'] = "Rota não encontrada: " . $route;
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=painel_admin");
    exit;
}
?>
