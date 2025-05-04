<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - Open2W</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/favicons/favicon.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>/assets/favicons/favicon.svg" type="image/svg+xml">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/admin.css">
    
    <!-- CSS específico da página (se existir) -->
    <?php 
    $page_css = ADMIN_PATH . '/assets/css/' . $page . '.css';
    if (file_exists($page_css)): 
    ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/<?php echo $page; ?>.css">
    <?php endif; ?>
</head>
<body>
    <div class="admin-wrapper">
