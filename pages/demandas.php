<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o sistema de demandas de talentos está ativo
$sistema_demandas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_demandas_talentos_ativo'");

// Se o sistema não estiver ativo, redirecionar para a página inicial
if (!$sistema_demandas_ativo) {
    $_SESSION['flash_message'] = "O sistema de demandas de talentos está desativado no momento.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "';</script>";
    exit;
}

// Configuração de paginação
$demandas_por_pagina = 12;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $demandas_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_profissao = isset($_GET['profissao']) ? trim($_GET['profissao']) : '';
$filtro_modelo = isset($_GET['modelo']) ? trim($_GET['modelo']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE dt.status = 'ativa'";
$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (dt.titulo LIKE :busca OR dt.descricao LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_profissao)) {
    $sql_where .= " AND EXISTS (
        SELECT 1 FROM demandas_profissoes dp 
        WHERE dp.demanda_id = dt.id AND dp.profissao LIKE :profissao
    )";
    $params['profissao'] = "%$filtro_profissao%";
}

if (!empty($filtro_modelo)) {
    $sql_where .= " AND dt.regime_trabalho = :modelo";
    $params['modelo'] = $filtro_modelo;
}

// Consulta para obter o total de demandas
$total_demandas = $db->fetchColumn("
    SELECT COUNT(DISTINCT dt.id) 
    FROM demandas_talentos dt
    $sql_where
", $params);

// Consulta para obter as demandas da página atual
$demandas = $db->fetchAll("
    SELECT dt.*, e.razao_social as empresa_nome, u.foto_perfil as empresa_logo,
           u.id as usuario_id, e.id as empresa_id
    FROM demandas_talentos dt
    JOIN empresas e ON dt.empresa_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    $sql_where
    ORDER BY dt.data_publicacao DESC
    LIMIT $demandas_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_demandas / $demandas_por_pagina);

// Obter lista de profissões para o filtro
$profissoes = $db->fetchAll("
    SELECT DISTINCT profissao 
    FROM demandas_profissoes 
    ORDER BY profissao ASC
");

// Verificar se a coluna regime_trabalho existe na tabela demandas_talentos
try {
    $colunas = $db->fetchAll("SHOW COLUMNS FROM demandas_talentos LIKE 'regime_trabalho'");
    
    // Obter modelos de trabalho para o filtro apenas se a coluna existir
    if (!empty($colunas)) {
        $modelos_trabalho = $db->fetchAll("
            SELECT DISTINCT regime_trabalho 
            FROM demandas_talentos 
            WHERE regime_trabalho IS NOT NULL AND regime_trabalho != '' 
            ORDER BY regime_trabalho ASC
        ");
    } else {
        // Se a coluna não existir, definir array vazio
        $modelos_trabalho = [];
        // Registrar mensagem de log
        error_log('A coluna regime_trabalho não existe na tabela demandas_talentos. Execute o script adicionar_coluna_regime_trabalho.php para criar a coluna.');
    }
} catch (Exception $e) {
    // Em caso de erro, definir array vazio
    $modelos_trabalho = [];
    // Registrar erro
    error_log('Erro ao verificar coluna regime_trabalho: ' . $e->getMessage());
}

// Verificar se o usuário atual é um talento
$is_talento = false;
$talento_id = null;

if (isset($_SESSION['usuario_id'])) {
    $usuario_tipo = $db->fetchColumn("SELECT tipo FROM usuarios WHERE id = ?", [$_SESSION['usuario_id']]);
    if ($usuario_tipo == 'talento') {
        $is_talento = true;
        $talento_id = $db->fetchColumn("SELECT id FROM talentos WHERE usuario_id = ?", [$_SESSION['usuario_id']]);
    }
}

// Obter as profissões de cada demanda
foreach ($demandas as $key => $demanda) {
    $demandas[$key]['profissoes'] = $db->fetchAll("
        SELECT profissao FROM demandas_profissoes WHERE demanda_id = ?
    ", [$demanda['id']]);
    
    // Verificar se o talento já demonstrou interesse
    if ($is_talento) {
        $demandas[$key]['ja_interessado'] = $db->fetchColumn("
            SELECT COUNT(*) FROM demandas_interessados 
            WHERE demanda_id = ? AND talento_id = ?
        ", [$demanda['id'], $talento_id]) > 0;
    } else {
        $demandas[$key]['ja_interessado'] = false;
    }
}
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Procura-se Profissionais</h1>
                <p class="lead">Descubra quais profissionais as empresas estão buscando para contratação imediata</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Procura-se Profissionais</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-demandas py-5">
    <div class="container">
        <!-- Filtros de busca -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Filtrar Anúncios</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo SITE_URL; ?>/?route=demandas" method="GET">
                    <input type="hidden" name="route" value="demandas">
                    
                    <div class="row">
                        <div class="col-lg-5 mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control form-control-lg" id="busca" name="busca" placeholder="Buscar por título ou descrição..." value="<?php echo htmlspecialchars((string)$busca); ?>">
                            </div>
                        </div>
                        
                        <div class="col-lg-3 mb-3">
                            <select class="form-control form-control-lg" id="profissao" name="profissao">
                                <option value="">Todas as profissões</option>
                                <?php foreach ($profissoes as $prof): ?>
                                    <option value="<?php echo htmlspecialchars((string)$prof['profissao']); ?>" <?php echo ($filtro_profissao == $prof['profissao']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string)$prof['profissao']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 mb-3">
                            <select class="form-control form-control-lg" id="modelo" name="modelo">
                                <option value="">Todos os modelos</option>
                                <?php foreach ($modelos_trabalho as $modelo): ?>
                                    <option value="<?php echo htmlspecialchars((string)$modelo['regime_trabalho']); ?>" <?php echo ($filtro_modelo == $modelo['regime_trabalho']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string)$modelo['regime_trabalho']); ?>
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
                <p>Encontramos <?php echo $total_demandas; ?> demandas que correspondem aos seus critérios.</p>
            </div>
            
            <?php if (empty($demandas)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhuma demanda encontrada com os critérios de busca atuais. Tente ajustar os filtros.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($demandas as $demanda): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card demanda-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="empresa-logo me-3">
                                        <?php if (isset($demanda['empresa_logo']) && !empty($demanda['empresa_logo'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $demanda['empresa_logo']; ?>" alt="<?php echo htmlspecialchars((string)$demanda['empresa_nome']); ?>" class="rounded-circle">
                                        <?php else: ?>
                                            <div class="logo-placeholder rounded-circle bg-light text-primary">
                                                <?php echo strtoupper(substr($demanda['empresa_nome'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars((string)$demanda['titulo']); ?></h5>
                                        <p class="text-muted small mb-0">
                                            <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa&id=<?php echo $demanda['usuario_id']; ?>">
                                                <?php echo htmlspecialchars((string)$demanda['empresa_nome']); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="demanda-info mb-3">
                                    <?php if (!empty($demanda['modelo_trabalho'])): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-building me-2"></i>
                                        <?php echo htmlspecialchars((string)$demanda['modelo_trabalho']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($demanda['nivel_experiencia'])): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-user-tie me-2"></i>
                                        Nível: <?php echo htmlspecialchars((string)$demanda['nivel_experiencia']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($demanda['prazo_contratacao'])): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        Prazo: <?php echo date('d/m/Y', strtotime($demanda['prazo_contratacao'])); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <p class="mb-1">
                                        <i class="fas fa-clock me-2"></i>
                                        Publicada em: <?php echo date('d/m/Y', strtotime($demanda['data_publicacao'])); ?>
                                    </p>
                                </div>
                                
                                <div class="demanda-profissoes mb-3">
                                    <h6>Profissões desejadas:</h6>
                                    <div class="profissoes-tags">
                                        <?php foreach ($demanda['profissoes'] as $prof): ?>
                                            <span class="badge bg-light text-dark mb-1 me-1"><?php echo htmlspecialchars((string)$prof['profissao']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="demanda-descricao">
                                    <p class="card-text">
                                        <?php 
                                        if (!empty($demanda['descricao'])) {
                                            // Limitar a descrição a 100 caracteres
                                            $descricao = strip_tags($demanda['descricao']);
                                            echo (strlen($descricao) > 100) ? substr($descricao, 0, 100) . '...' : $descricao;
                                        } else {
                                            echo 'Sem descrição disponível.';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <div class="d-grid gap-2">
                                    <a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        Ver detalhes
                                    </a>
                                    
                                    <?php if ($is_talento): ?>
                                        <?php if ($demanda['ja_interessado']): ?>
                                            <button class="btn btn-success btn-sm" disabled>
                                                <i class="fas fa-check me-1"></i> Interesse demonstrado
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo SITE_URL; ?>/?route=demonstrar_interesse&demanda_id=<?php echo $demanda['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-hand-paper me-1"></i> Tenho interesse
                                            </a>
                                        <?php endif; ?>
                                    <?php elseif (!isset($_SESSION['usuario_id'])): ?>
                                        <a href="<?php echo SITE_URL; ?>/?route=login&redirect=demandas" class="btn btn-primary btn-sm">
                                            <i class="fas fa-sign-in-alt me-1"></i> Entre para demonstrar interesse
                                        </a>
                                    <?php endif; ?>
                                </div>
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
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=demandas&pagina=<?php echo $pagina_atual - 1; ?>&busca=<?php echo urlencode($busca); ?>&profissao=<?php echo urlencode($filtro_profissao); ?>&modelo=<?php echo urlencode($filtro_modelo); ?>">
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
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=demandas&pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&profissao=<?php echo urlencode($filtro_profissao); ?>&modelo=<?php echo urlencode($filtro_modelo); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=demandas&pagina=<?php echo $pagina_atual + 1; ?>&busca=<?php echo urlencode($busca); ?>&profissao=<?php echo urlencode($filtro_profissao); ?>&modelo=<?php echo urlencode($filtro_modelo); ?>">
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
.demanda-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.demanda-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.empresa-logo img, .logo-placeholder {
    width: 50px;
    height: 50px;
    object-fit: cover;
}

.logo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
}

.demanda-info {
    font-size: 0.9rem;
}

.demanda-descricao {
    min-height: 60px;
}

.profissoes-tags {
    display: flex;
    flex-wrap: wrap;
}
</style>
