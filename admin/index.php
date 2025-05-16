<?php
// Iniciar sessão
session_start();

// Definir constantes
if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', __DIR__);
}
define('SITE_PATH', dirname(__DIR__));

// Incluir arquivos necessários
require_once SITE_PATH . '/config/config.php';
require_once SITE_PATH . '/includes/Database.php';
require_once SITE_PATH . '/includes/Auth.php';
require_once SITE_PATH . '/includes/functions.php';
require_once ADMIN_PATH . '/includes/admin_functions.php';

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Redirecionar para a página de login com mensagem
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=entrar");
    exit;
}

// Obter informações do usuário administrador
$db = Database::getInstance();
$admin = $db->fetch("SELECT * FROM usuarios WHERE id = :id AND tipo = 'admin'", ['id' => $_SESSION['user_id']]);

if (!$admin) {
    // Sessão inválida, fazer logout
    session_destroy();
    header("Location: " . SITE_URL . "/?route=entrar");
    exit;
}

// Definir página atual
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Lista de páginas válidas
$valid_pages = [
    'dashboard', 
    'usuarios', 
    'talentos', 
    'empresas', 
    'vagas', 
    'blog', 
    'categorias', 
    'configuracoes',
    'configuracoes_seo',
    'configuracoes_monetizacao',
    'perfil',
    'gerenciar_depoimentos',
    'gerenciar_opcoes_vagas',
    'gerenciar_vagas_admin',
    'gerenciar_cache',
    'gerenciar_webhooks',
    'gestao_de_vagas',
    'gerenciar_newsletter',
    'gerenciar_equipe',
    'editar_membro_equipe',
    'gerenciar_emails',
    'enviar_newsletter'
];

// Verificar se a página solicitada é válida
if (!in_array($page, $valid_pages)) {
    $page = 'dashboard';
}

// Incluir o cabeçalho
include_once ADMIN_PATH . '/includes/header.php';
?>

<div class="admin-container">
    <!-- Sidebar -->
    <?php include_once ADMIN_PATH . '/includes/sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <div class="admin-content">
        <!-- Barra superior -->
        <?php include_once ADMIN_PATH . '/includes/topbar.php'; ?>

        <!-- Conteúdo da página -->
        <div class="content-wrapper">
            <?php
            // Exibir mensagens flash
            if (isset($_SESSION['flash_message'])) {
                $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
                echo '<div class="alert alert-' . $type . '">' . $_SESSION['flash_message'] . '</div>';
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            }
            
            // Incluir o arquivo da página solicitada
            $page_file = ADMIN_PATH . '/pages/' . $page . '.php';
            
            if (file_exists($page_file)) {
                include_once $page_file;
            } else {
                echo '<div class="alert alert-danger">Página não encontrada.</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php
// Incluir o rodapé
include_once ADMIN_PATH . '/includes/footer.php';
?>
