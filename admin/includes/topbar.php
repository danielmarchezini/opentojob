<div class="admin-topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle d-md-none">
            <i class="fas fa-bars"></i>
        </button>
        <div class="page-title">
            <?php
            $page_titles = [
                'dashboard' => 'Dashboard',
                'usuarios' => 'Gerenciar Usuários',
                'talentos' => 'Gerenciar Talentos',
                'empresas' => 'Gerenciar Empresas',
                'vagas' => 'Gerenciar Vagas',
                'blog' => 'Gerenciar Blog',
                'categorias' => 'Gerenciar Categorias',
                'configuracoes' => 'Configurações do Sistema',
                'perfil' => 'Meu Perfil'
            ];
            
            echo isset($page_titles[$page]) ? $page_titles[$page] : 'Dashboard';
            ?>
        </div>
    </div>
    
    <div class="topbar-right">
        <div class="topbar-item">
            <a href="<?php echo SITE_URL; ?>" target="_blank" data-toggle="tooltip" title="Visualizar site">
                <i class="fas fa-globe"></i>
            </a>
        </div>
        
        <div class="topbar-item">
            <a href="#" data-toggle="tooltip" title="Notificações">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </a>
        </div>
        
        <div class="topbar-item profile-dropdown">
            <?php
            // Obter dados do usuário logado se ainda não estiver definido
            if (!isset($admin) || empty($admin)) {
                $admin = [];
                if (isset($_SESSION['user_id'])) {
                    $db = Database::getInstance();
                    $admin = $db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $_SESSION['user_id']]);
                }
            }
            ?>
            <a href="#" class="profile-dropdown-toggle">
                <?php if (!empty($admin) && !empty($admin['foto_perfil'])): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $admin['foto_perfil']; ?>" alt="<?php echo $admin['nome']; ?>">
                <?php else: ?>
                    <div class="user-initial"><?php echo !empty($admin) ? substr($admin['nome'], 0, 1) : 'A'; ?></div>
                <?php endif; ?>
                <span class="d-none d-md-inline-block"><?php echo !empty($admin) ? $admin['nome'] : 'Administrador'; ?></span>
                <i class="fas fa-chevron-down"></i>
            </a>
            
            <div class="dropdown-menu">
                <a href="<?php echo SITE_URL; ?>/?route=perfil_admin" class="dropdown-item">
                    <i class="fas fa-user"></i> Meu Perfil
                </a>
                <a href="<?php echo SITE_URL; ?>/?route=configuracoes_admin" class="dropdown-item">
                    <i class="fas fa-cog"></i> Configurações
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo SITE_URL; ?>/?route=sair" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </div>
</div>
