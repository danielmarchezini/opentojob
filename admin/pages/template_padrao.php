<?php
/**
 * Template padrão para páginas administrativas
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Verificar permissões
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=entrar");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Título da página - deve ser definido antes de incluir este template
if (!isset($titulo_pagina)) {
    $titulo_pagina = "Painel Administrativo";
}

// Ícone da página - deve ser definido antes de incluir este template
if (!isset($icone_pagina)) {
    $icone_pagina = "fas fa-cog";
}

// Link anterior no breadcrumb - deve ser definido antes de incluir este template
if (!isset($link_anterior)) {
    $link_anterior = "painel_admin";
    $texto_anterior = "Dashboard";
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $titulo_pagina; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=<?php echo $link_anterior; ?>"><?php echo $texto_anterior; ?></a></li>
        <li class="breadcrumb-item active"><?php echo $titulo_pagina; ?></li>
    </ol>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
        <?php echo $_SESSION['flash_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php 
        // Limpar mensagem flash
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    endif; ?>

    <!-- Conteúdo da Página -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="<?php echo $icone_pagina; ?> me-1"></i>
            <?php echo $titulo_pagina; ?>
        </div>
        <div class="card-body">
            <!-- O conteúdo específico da página deve ser inserido aqui -->
            <div id="conteudo_pagina">
                <!-- Conteúdo específico da página -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos da página devem ser inseridos aqui -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicialização de DataTables
    if ($.fn.DataTable && document.querySelector('.table')) {
        $('.table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
            },
            responsive: true
        });
    }
    
    // Inicialização de tooltips
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>
