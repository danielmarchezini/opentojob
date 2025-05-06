<?php
/**
 * Redirecionador para páginas administrativas
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Iniciar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir configurações
require_once __DIR__ . '/../config/config.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Obter a rota da URL
$route = isset($_GET['route']) ? $_GET['route'] : '';

// Mapear rotas para páginas administrativas
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
    'configuracoes_monetizacao_admin' => 'configuracoes_monetizacao',
    'configuracoes_seo_admin' => 'configuracoes_seo'
];

// Verificar se a rota existe no mapeamento
if (isset($route_map[$route])) {
    $page = $route_map[$route];
    echo "<script>window.location.href = '" . SITE_URL . "/admin/?page=" . $page . "';</script>";
    exit;
} else {
    $_SESSION['flash_message'] = "Página administrativa não encontrada.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_admin';</script>";
    exit;
}
?>
