<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Configuração de paginação
$empresas_por_pagina = 12;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $empresas_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_segmento = isset($_GET['segmento']) ? trim($_GET['segmento']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE u.tipo = 'empresa' AND u.status = 'ativo'";
$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (u.nome LIKE :busca OR e.razao_social LIKE :busca OR e.segmento LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_segmento)) {
    $sql_where .= " AND e.segmento LIKE :segmento";
    $params['segmento'] = "%$filtro_segmento%";
}

// Consulta para obter o total de empresas
$total_empresas = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM usuarios u
    JOIN empresas e ON u.id = e.usuario_id
    $sql_where
", $params);

// Consulta para obter as empresas da página atual
$empresas = $db->fetchAll("
    SELECT u.id, u.nome, u.data_cadastro, u.foto_perfil, u.ultimo_acesso,
           e.razao_social, e.segmento, e.descricao, e.cidade, e.estado, e.site
    FROM usuarios u
    JOIN empresas e ON u.id = e.usuario_id
    $sql_where
    ORDER BY e.razao_social ASC
    LIMIT $empresas_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_empresas / $empresas_por_pagina);

// Obter lista de segmentos para o filtro
$segmentos = $db->fetchAll("
    SELECT DISTINCT segmento 
    FROM empresas 
    WHERE segmento IS NOT NULL AND segmento != '' 
    ORDER BY segmento ASC
");
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Empresas</h1>
                <p class="lead">Conheça as empresas parceiras da plataforma Open2W</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Empresas</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-empresas py-5">
    <div class="container-wide">
        <!-- Espaço para anúncio no topo da página de empresas -->
        <?php if ($adsense->isPosicaoAtiva('empresas_topo')): ?>
        <div class="ad-container mb-4">
            <?php echo $adsense->exibirAnuncio('empresas_topo', 'horizontal'); ?>
        </div>
        <?php endif; ?>
        
        <!-- Filtros de busca -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Filtrar Empresas</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo SITE_URL; ?>/?route=empresas" method="GET">
                    <input type="hidden" name="route" value="empresas">
                    
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control form-control-lg" id="busca" name="busca" placeholder="Buscar por nome, razão social ou segmento..." value="<?php echo htmlspecialchars((string)$busca); ?>">
                            </div>
                        </div>
                        
                        <div class="col-lg-4 mb-3">
                            <select class="form-control form-control-lg" id="segmento" name="segmento">
                                <option value="">Todos os segmentos</option>
                                <?php foreach ($segmentos as $seg): ?>
                                    <option value="<?php echo htmlspecialchars((string)$seg['segmento']); ?>" <?php echo ($filtro_segmento == $seg['segmento']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string)$seg['segmento']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-filter me-2"></i>Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Resultados -->
        <div class="row">
            <div class="col-12 mb-4">
                <h2>Resultados da busca</h2>
                <p>Encontramos <?php echo $total_empresas; ?> empresas que correspondem aos seus critérios.</p>
            </div>
            
            <?php if (empty($empresas)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhuma empresa encontrada com os critérios de busca atuais. Tente ajustar os filtros.
                    </div>
                </div>
            <?php else: ?>
                <?php 
                $contador = 0;
                foreach ($empresas as $empresa): 
                    $contador++;
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card empresa-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="empresa-logo me-3">
                                        <?php if (!empty($empresa['foto_perfil'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/empresas/<?php echo $empresa['foto_perfil']; ?>" alt="<?php echo htmlspecialchars((string)$empresa['razao_social']); ?>" class="img-fluid">
                                        <?php else: ?>
                                            <div class="logo-placeholder bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-building text-secondary"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars((string)$empresa['razao_social']); ?></h5>
                                        <?php if (!empty($empresa['segmento'])): ?>
                                            <p class="segmento-tag mb-0"><?php echo htmlspecialchars((string)$empresa['segmento']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($empresa['cidade']) || !empty($empresa['estado'])): ?>
                                    <div class="empresa-info mb-2">
                                        <p class="mb-0">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <?php 
                                                echo !empty($empresa['cidade']) ? htmlspecialchars((string)$empresa['cidade']) : '';
                                                echo (!empty($empresa['cidade']) && !empty($empresa['estado'])) ? ', ' : '';
                                                echo !empty($empresa['estado']) ? htmlspecialchars((string)$empresa['estado']) : '';
                                            ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="empresa-descricao">
                                    <p class="card-text">
                                        <?php 
                                        if (!empty($empresa['descricao'])) {
                                            echo nl2br(htmlspecialchars(substr($empresa['descricao'], 0, 150) . (strlen($empresa['descricao']) > 150 ? '...' : '')));
                                        } else {
                                            echo '<em>Esta empresa ainda não adicionou uma descrição.</em>';
                                        }
                                        ?>
                                    </p>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa&id=<?php echo $empresa['id']; ?>" class="btn btn-primary btn-sm">Ver perfil completo</a>
                                    <?php if (!empty($empresa['site'])): ?>
                                        <a href="<?php echo htmlspecialchars((string)$empresa['site']); ?>" class="btn btn-outline-primary btn-sm ms-2" target="_blank">Visitar site</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Inserir anúncio a cada 6 empresas (após 2 linhas de 3 empresas)
                    if ($contador % 6 === 0 && $adsense->isPosicaoAtiva('empresas_lista')): 
                    ?>
                    <div class="col-12 mb-4">
                        <div class="ad-container">
                            <?php echo $adsense->exibirAnuncio('empresas_lista', 'horizontal'); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <nav aria-label="Navegação de páginas">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina_atual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=empresas&pagina=<?php echo $pagina_atual - 1; ?>&busca=<?php echo urlencode($busca); ?>&segmento=<?php echo urlencode($filtro_segmento); ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Mostrar no máximo 5 links de página
                            $start_page = max(1, $pagina_atual - 2);
                            $end_page = min($total_paginas, $start_page + 4);
                            
                            if ($end_page - $start_page < 4) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=empresas&pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&segmento=<?php echo urlencode($filtro_segmento); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=empresas&pagina=<?php echo $pagina_atual + 1; ?>&busca=<?php echo urlencode($busca); ?>&segmento=<?php echo urlencode($filtro_segmento); ?>">
                                        Próxima
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Próxima</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.empresa-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.empresa-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.empresa-logo img, .logo-placeholder {
    width: 60px;
    height: 60px;
    object-fit: cover;
}

.logo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
}

.empresa-info {
    font-size: 0.9rem;
}

.empresa-descricao {
    min-height: 80px;
}
</style>
