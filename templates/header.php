<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <?php
    // Carregar configurações de SEO e Google Tag Manager
    $db = Database::getInstance();
    $meta_description = '';
    $meta_keywords = '';
    $gtm_code_head = '';
    
    try {
        $seo_configs = $db->fetchAll("SELECT chave, valor FROM configuracoes_seo WHERE chave IN ('meta_description', 'meta_keywords', 'google_tag_manager_code_head')");
        
        foreach ($seo_configs as $config) {
            if ($config['chave'] === 'meta_description') {
                $meta_description = $config['valor'];
            } elseif ($config['chave'] === 'meta_keywords') {
                $meta_keywords = $config['valor'];
            } elseif ($config['chave'] === 'google_tag_manager_code_head') {
                $gtm_code_head = $config['valor'];
            }
        }
    } catch (Exception $e) {
        // Silenciar erros para não afetar a exibição da página
        error_log('Erro ao carregar configurações SEO: ' . $e->getMessage());
    }
    
    // Carregar configurações de AdSense
    require_once 'includes/AdSense.php';
    $adsense = AdSense::getInstance();
    ?>
    
    <!-- Meta tags SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    
    <!-- Google Tag Manager -->
    <?php echo $gtm_code_head; ?>
    
    <!-- Google AdSense -->
    <?php echo $adsense->getScriptHeader(); ?>
    <?php echo $adsense->getPlaceholderCSS(); ?>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/favicons/favicon.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>/assets/favicons/favicon.svg" type="image/svg+xml">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/home.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/vagas.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/layout-wide.css">
    <?php if (isset($route) && $route === 'perfil_empresa'): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/perfil_empresa.css">
    <?php endif; ?>
</head>
<body>
    <?php
    // Carregar código do Google Tag Manager para o corpo da página
    $gtm_code_body = '';
    
    try {
        $gtm_body_config = $db->fetch("SELECT valor FROM configuracoes_seo WHERE chave = 'google_tag_manager_code_body'");
        if ($gtm_body_config) {
            $gtm_code_body = $gtm_body_config['valor'];
        }
    } catch (Exception $e) {
        // Silenciar erros para não afetar a exibição da página
        error_log('Erro ao carregar código GTM para o corpo: ' . $e->getMessage());
    }
    
    // Exibir código GTM para o corpo
    echo $gtm_code_body;
    ?>
    <header class="header">
        <div class="container-wide header-container">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo_opentojob.svg" alt="OpenToJob" height="50">
                </a>
            </div>
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav" id="main-nav">
                <a href="<?php echo SITE_URL; ?>/?route=inicio" class="nav-link">Início</a>
                <a href="<?php echo SITE_URL; ?>/?route=vagas" class="nav-link">Vagas</a>
                <a href="<?php echo SITE_URL; ?>/?route=demandas" class="nav-link highlight-link">Procura-se</a>
                <a href="<?php echo SITE_URL; ?>/?route=talentos" class="nav-link">Talentos</a>
                <a href="<?php echo SITE_URL; ?>/?route=empresas" class="nav-link">Empresas</a>
                <a href="<?php echo SITE_URL; ?>/?route=perfis_linkedin" class="nav-link">Perfis LinkedIn</a>
                
                <?php if (Auth::isLoggedIn()): ?>
                    <div class="user-menu">
                        <button class="user-menu-toggle">
                            <?php echo $_SESSION['user_name']; ?> <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-menu-dropdown">
                            <?php if (Auth::checkUserType('talento')): ?>
                                <a href="<?php echo SITE_URL; ?>/?route=painel_talento" class="user-menu-item">Meu Painel</a>
                                <a href="<?php echo SITE_URL; ?>/?route=perfil_talento" class="user-menu-item">Meu Perfil</a>
                                <a href="<?php echo SITE_URL; ?>/?route=minhas_candidaturas" class="user-menu-item">Minhas Candidaturas</a>
                                <a href="<?php echo SITE_URL; ?>/?route=mensagens_talento" class="user-menu-item">Mensagens</a>
                            <?php elseif (Auth::checkUserType('empresa')): ?>
                                <a href="<?php echo SITE_URL; ?>/?route=painel_empresa" class="user-menu-item">Meu Painel</a>
                                <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa" class="user-menu-item">Perfil da Empresa</a>
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas" class="user-menu-item">Gerenciar Vagas</a>
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas" class="user-menu-item">Gerenciar Anúncios de Procura</a>
                                <a href="<?php echo SITE_URL; ?>/?route=buscar_talentos" class="user-menu-item">Buscar Talentos</a>
                                <a href="<?php echo SITE_URL; ?>/?route=talentos_favoritos" class="user-menu-item"><i class="fas fa-heart text-danger me-1"></i> Talentos Favoritos</a>
                                <a href="<?php echo SITE_URL; ?>/?route=mensagens_empresa" class="user-menu-item">Mensagens</a>
                            <?php elseif (Auth::checkUserType('admin')): ?>
                                <a href="<?php echo SITE_URL; ?>/?route=painel_admin" class="user-menu-item">Painel Admin</a>
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_usuarios" class="user-menu-item">Gerenciar Usuários</a>
                                <a href="<?php echo SITE_URL; ?>/?route=aprovar_cadastros" class="user-menu-item">Aprovar Cadastros</a>
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin" class="user-menu-item">Gerenciar Vagas</a>
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_blog" class="user-menu-item">Gerenciar Blog</a>
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>/?route=sair" class="user-menu-item">Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/?route=entrar" class="nav-link">Entrar</a>
                    <a href="<?php echo SITE_URL; ?>/?route=escolha_cadastro" class="btn btn-accent">Cadastre-se</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <main class="main">
        <div class="container-wide">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?>">
                    <?php 
                        echo $_SESSION['flash_message']; 
                        unset($_SESSION['flash_message']);
                        unset($_SESSION['flash_type']);
                    ?>
                </div>
            <?php endif; ?>
