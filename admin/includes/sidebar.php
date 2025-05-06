<?php
// Verificar se o buffer de saída já foi iniciado
if (!ob_get_level()) {
    ob_start();
}
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <a href="<?php echo SITE_URL; ?>/?route=painel_admin">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo_opentojob.svg" alt="OpenToJob" height="50">
            </a>
        </div>
        <div class="sidebar-toggle d-md-none">
            <i class="fas fa-times"></i>
        </div>
    </div>
    
    <div class="sidebar-user">
        <?php
        // Obter dados do usuário logado
        $admin = [];
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstance();
            $admin = $db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $_SESSION['user_id']]);
        }
        ?>
        <div class="user-avatar">
            <?php if (!empty($admin) && !empty($admin['foto_perfil'])): ?>
                <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $admin['foto_perfil']; ?>" alt="<?php echo $admin['nome']; ?>">
            <?php else: ?>
                <div class="user-initial"><?php echo !empty($admin) ? substr($admin['nome'], 0, 1) : 'A'; ?></div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <h5><?php echo !empty($admin) ? $admin['nome'] : 'Administrador'; ?></h5>
            <span>Administrador</span>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <ul>
            <li class="menu-header">Principal</li>
            <li class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=painel_admin">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="menu-header">Gerenciamento</li>
            <li class="<?php echo ($page == 'gerenciar_usuarios') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_usuarios_admin">
                    <i class="fas fa-users"></i>
                    <span>Usuários</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'aprovar_cadastros') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=aprovar_cadastros_admin">
                    <i class="fas fa-user-tie"></i>
                    <span>Aprovar Cadastros</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_talentos') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_talentos_admin">
                    <i class="fas fa-user-graduate"></i>
                    <span>Talentos</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_empresas') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_empresas_admin">
                    <i class="fas fa-building"></i>
                    <span>Empresas</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gestao_de_vagas') ? 'active' : ''; ?>">
                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/admin/index.php?page=gestao_de_vagas">
                    <i class="fas fa-tasks"></i>
                    <span>Nova Gestão de Vagas</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'cadastrar_vaga_externa') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=cadastrar_vaga_externa_admin">
                    <i class="fas fa-plus-circle"></i>
                    <span>Cadastrar Vaga</span>
                </a>
            </li>
            
            <li class="menu-header">Conteúdo</li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseVagas" aria-expanded="false" aria-controls="collapseVagas">
                    <div class="sb-nav-link-icon"><i class="fas fa-briefcase"></i></div>
                    Vagas
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseVagas" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/?route=cadastrar_vaga_externa">Cadastrar Vaga Externa</a>
                    </nav>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseNewsletter" aria-expanded="false" aria-controls="collapseNewsletter">
                    <div class="sb-nav-link-icon"><i class="fas fa-envelope"></i></div>
                    Newsletter
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseNewsletter" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_newsletter">Gerenciar Inscritos</a>
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/?route=enviar_newsletter">Enviar Newsletter</a>
                    </nav>
                </div>
            </li>
            
            <li class="<?php echo ($page == 'gerenciar_blog') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_blog_admin">
                    <i class="fas fa-blog"></i>
                    <span>Gerenciar Blog</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_depoimentos') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_depoimentos_admin">
                    <i class="fas fa-comment-dots"></i>
                    <span>Gerenciar Depoimentos</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_equipe') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_equipe_admin">
                    <i class="fas fa-users-cog"></i>
                    <span>Gerenciar Equipe</span>
                </a>
            </li>
            <li class="<?php echo (in_array($page, ['gerenciar_perfis_linkedin', 'adicionar_perfil_linkedin', 'editar_perfil_linkedin', 'gerenciar_indicacoes_perfis'])) ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin">
                    <i class="fab fa-linkedin"></i>
                    <span>Perfis do LinkedIn</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_emails') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_emails_admin">
                    <i class="fas fa-envelope"></i>
                    <span>Modelos de E-mail</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'configurar_smtp') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=configurar_smtp">
                    <i class="fas fa-server"></i>
                    <span>Configurar SMTP</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'configuracoes_seo') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=configuracoes_seo_admin">
                    <i class="fas fa-search"></i>
                    <span>SEO e Analytics</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_webhooks') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_webhooks_admin">
                    <i class="fas fa-plug"></i>
                    <span>Webhooks e Automações</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_contratacoes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_contratacoes">
                    <i class="fas fa-handshake"></i>
                    <span>Gerenciar Contratações</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_reportes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_reportes">
                    <i class="fas fa-flag"></i>
                    <span>Gerenciar Reportes</span>
                    <?php
                    // Verificar se existem reportes pendentes
                    $reportes_pendentes = $db->fetchColumn("SELECT COUNT(*) FROM reportes WHERE status = 'pendente'");
                    if ($reportes_pendentes > 0):
                    ?>
                    <span class="badge badge-danger"><?php echo $reportes_pendentes; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="<?php echo ($page == 'relatorios') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=relatorios_admin">
                    <i class="fas fa-chart-bar"></i>
                    <span>Relatórios</span>
                </a>
            </li>
            
            <li class="<?php echo ($page == 'gerenciar_avaliacoes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin">
                    <i class="fas fa-star"></i>
                    <span>Gerenciar Avaliações</span>
                </a>
            </li>
            
            <li class="menu-header">Sistema</li>
            <li class="<?php echo ($page == 'gerenciar_cache') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_cache_admin">
                    <i class="fas fa-memory"></i>
                    <span>Gerenciar Cache</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_reportes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_reportes">
                    <i class="fas fa-flag"></i>
                    <span>Gerenciar Reportes</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_avaliacoes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin">
                    <i class="fas fa-star"></i>
                    <span>Gerenciar Avaliações</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'gerenciar_contratacoes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_contratacoes">
                    <i class="fas fa-handshake"></i>
                    <span>Gerenciar Contratações</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'estatisticas_interacoes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=estatisticas_interacoes">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Estatísticas de Interações</span>
                </a>
            </li>
            
            <li class="menu-header">Sistema</li>
            <li class="<?php echo ($page == 'configuracoes') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=configuracoes_admin">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
            </li>
            <li class="menu-header">Configurações</li>
            <li class="<?php echo ($page == 'configuracoes_site') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=configuracoes_site_admin">
                    <i class="fas fa-cog"></i>
                    <span>Configurações do Site</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'configuracoes_seo') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=configuracoes_seo_admin">
                    <i class="fas fa-search"></i>
                    <span>Configurações de SEO</span>
                </a>
            </li>
            <li class="<?php echo ($page == 'configuracoes_monetizacao') ? 'active' : ''; ?>">
                <a href="<?php echo SITE_URL; ?>/?route=configuracoes_monetizacao_admin">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Monetização</span>
                </a>
            </li>
            <li>
                <a href="<?php echo SITE_URL; ?>/?route=sair">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </li>
        </ul>
    </div>
</div>
