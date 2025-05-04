<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Configuração de paginação
$vagas_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $vagas_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$filtro_modelo = isset($_GET['modelo']) ? trim($_GET['modelo']) : '';
$filtro_nivel = isset($_GET['nivel']) ? trim($_GET['nivel']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE v.status = 'aberta' AND v.tipo_vaga = 'externa'";
$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (v.titulo LIKE :busca OR v.descricao LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_tipo)) {
    $sql_where .= " AND v.tipo_contrato = :tipo";
    $params['tipo'] = $filtro_tipo;
}

if (!empty($filtro_modelo)) {
    $sql_where .= " AND v.regime_trabalho = :modelo";
    $params['modelo'] = $filtro_modelo;
}

if (!empty($filtro_nivel)) {
    $sql_where .= " AND v.nivel_experiencia = :nivel";
    $params['nivel'] = $filtro_nivel;
}

// Consulta para obter o total de vagas
$total_vagas = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM vagas v
    JOIN empresas e ON v.empresa_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    $sql_where
", $params);

// Consulta para obter as vagas da página atual
$vagas = $db->fetchAll("
    SELECT v.*, u.nome as empresa_nome, u.foto_perfil as empresa_logo 
    FROM vagas v
    JOIN empresas e ON v.empresa_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    $sql_where
    ORDER BY v.data_publicacao DESC
    LIMIT $vagas_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_vagas / $vagas_por_pagina);

// Obter tipos de contrato, modelos de trabalho e níveis de experiência para os filtros
$tipos_contrato = $db->fetchAll("SELECT DISTINCT tipo_contrato FROM vagas WHERE tipo_contrato IS NOT NULL AND tipo_contrato != '' AND tipo_vaga = 'externa' ORDER BY tipo_contrato");
$modelos_trabalho = $db->fetchAll("SELECT DISTINCT regime_trabalho FROM vagas WHERE regime_trabalho IS NOT NULL AND regime_trabalho != '' AND tipo_vaga = 'externa' ORDER BY regime_trabalho");
$niveis_experiencia = $db->fetchAll("SELECT DISTINCT nivel_experiencia FROM vagas WHERE nivel_experiencia IS NOT NULL AND nivel_experiencia != '' AND tipo_vaga = 'externa' ORDER BY nivel_experiencia");
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Vagas Externas</h1>
                <p class="lead">Oportunidades de trabalho em empresas parceiras</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Vagas Externas</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-vagas py-5">
    <div class="container">
        <!-- Filtros de busca -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Filtrar Vagas</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo SITE_URL; ?>/?route=vagas_externas" method="GET">
                    <input type="hidden" name="route" value="vagas_externas">
                    
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="busca" name="busca" placeholder="Buscar vagas..." value="<?php echo htmlspecialchars($busca); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-2 mb-3">
                            <select class="form-control" id="tipo" name="tipo">
                                <option value="">Tipo de Contrato</option>
                                <?php foreach ($tipos_contrato as $tipo): ?>
                                    <option value="<?php echo htmlspecialchars($tipo['tipo_contrato']); ?>" <?php echo ($filtro_tipo == $tipo['tipo_contrato']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['tipo_contrato']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 col-lg-2 mb-3">
                            <select class="form-control" id="modelo" name="modelo">
                                <option value="">Modelo de Trabalho</option>
                                <?php foreach ($modelos_trabalho as $modelo): ?>
                                    <option value="<?php echo htmlspecialchars($modelo['regime_trabalho']); ?>" <?php echo ($filtro_modelo == $modelo['regime_trabalho']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($modelo['regime_trabalho']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 col-lg-2 mb-3">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Resultados -->
        <div class="row">
            <div class="col-12 mb-4">
                <h2>Vagas Externas Disponíveis</h2>
                <p>Encontramos <?php echo $total_vagas; ?> vagas externas que correspondem aos seus critérios.</p>
            </div>
            
            <?php if (empty($vagas)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhuma vaga externa encontrada com os critérios de busca atuais. Tente ajustar os filtros.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($vagas as $vaga): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card vaga-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                                        <h6 class="card-subtitle text-muted"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></h6>
                                    </div>
                                    <?php if (!empty($vaga['empresa_logo'])): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/empresas/<?php echo $vaga['empresa_logo']; ?>" alt="<?php echo htmlspecialchars($vaga['empresa_nome']); ?>" class="empresa-logo">
                                    <?php else: ?>
                                        <div class="empresa-logo-placeholder"><?php echo substr($vaga['empresa_nome'], 0, 1); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="vaga-info mb-3">
                                    <?php if (!empty($vaga['localizacao'])): ?>
                                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($vaga['localizacao']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($vaga['tipo_contrato'])): ?>
                                        <p class="mb-1"><i class="fas fa-file-contract me-2"></i><?php echo htmlspecialchars($vaga['tipo_contrato']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($vaga['regime_trabalho'])): ?>
                                        <p class="mb-1"><i class="fas fa-laptop-house me-2"></i><?php echo htmlspecialchars($vaga['regime_trabalho']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($vaga['nivel_experiencia'])): ?>
                                        <p class="mb-1"><i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($vaga['nivel_experiencia']); ?></p>
                                    <?php endif; ?>
                                    
                                    <p class="mb-1"><i class="far fa-clock me-2"></i>Publicada em <?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?></p>
                                </div>
                                
                                <p class="vaga-descricao"><?php echo nl2br(htmlspecialchars(substr($vaga['descricao'], 0, 150) . (strlen($vaga['descricao']) > 150 ? '...' : ''))); ?></p>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <a href="<?php echo htmlspecialchars($vaga['url_externa']); ?>" target="_blank" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-external-link-alt me-2"></i>Candidatar-se Externamente
                                </a>
                            </div>
                        </div>
                    </div>
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
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=vagas_externas&pagina=<?php echo $pagina_atual - 1; ?>&busca=<?php echo urlencode($busca); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&modelo=<?php echo urlencode($filtro_modelo); ?>&nivel=<?php echo urlencode($filtro_nivel); ?>">
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
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=vagas_externas&pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&modelo=<?php echo urlencode($filtro_modelo); ?>&nivel=<?php echo urlencode($filtro_nivel); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=vagas_externas&pagina=<?php echo $pagina_atual + 1; ?>&busca=<?php echo urlencode($busca); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&modelo=<?php echo urlencode($filtro_modelo); ?>&nivel=<?php echo urlencode($filtro_nivel); ?>">
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
.vaga-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.vaga-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.empresa-logo, .empresa-logo-placeholder {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border-radius: 4px;
    background-color: #f8f9fa;
    padding: 5px;
}

.empresa-logo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    color: #6c757d;
}

.vaga-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.vaga-descricao {
    min-height: 80px;
    font-size: 0.95rem;
}
</style>
