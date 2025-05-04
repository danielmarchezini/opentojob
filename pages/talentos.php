<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Configuração de paginação
$talentos_por_pagina = 12;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $talentos_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_profissao = isset($_GET['profissao']) ? trim($_GET['profissao']) : '';
$filtro_nivel = isset($_GET['nivel']) ? trim($_GET['nivel']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE u.tipo = 'talento' AND u.status = 'ativo'";
$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (u.nome LIKE :busca OR t.profissao LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_profissao)) {
    $sql_where .= " AND t.profissao LIKE :profissao";
    $params['profissao'] = "%$filtro_profissao%";
}

if (!empty($filtro_nivel)) {
    $sql_where .= " AND t.nivel = :nivel";
    $params['nivel'] = $filtro_nivel;
}

// Consulta para obter o total de talentos
$total_talentos = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    $sql_where
", $params);

// Consulta para obter os talentos da página atual
$talentos = $db->fetchAll("
    SELECT u.id, u.nome, u.data_cadastro, u.foto_perfil, t.profissao, t.nivel, t.carta_apresentacao
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    $sql_where
    ORDER BY u.nome ASC
    LIMIT $talentos_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_talentos / $talentos_por_pagina);

// Obter lista de profissões para o filtro
$profissoes = $db->fetchAll("
    SELECT DISTINCT profissao 
    FROM talentos 
    WHERE profissao IS NOT NULL AND profissao != '' 
    ORDER BY profissao ASC
");
?>

<div class="page-header bg-primary text-white py-4">
    <div class="container-wide">
        <div class="row">
            <div class="col-lg-8">
                <?php if (!empty($filtro_profissao)): ?>
                    <h1>Talentos: <?php echo htmlspecialchars($filtro_profissao); ?></h1>
                    <p class="lead">Profissionais de <?php echo htmlspecialchars($filtro_profissao); ?> prontos para oportunidades imediatas</p>
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/?route=talentos" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-times"></i> Remover filtro
                        </a>
                    </div>
                <?php else: ?>
                    <h1>Talentos</h1>
                    <p class="lead">Conheça os profissionais disponíveis para contratação imediata</p>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>" class="text-white">Home</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Talentos</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-talentos py-5">
    <div class="container-wide">
        <!-- Espaço para anúncio no topo da página de talentos -->
        <?php if ($adsense->isPosicaoAtiva('talentos_topo')): ?>
        <div class="ad-container mb-4">
            <?php echo $adsense->exibirAnuncio('talentos_topo', 'horizontal'); ?>
        </div>
        <?php endif; ?>
        
        <!-- Filtros de busca -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Filtrar Talentos</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo SITE_URL; ?>/?route=talentos" method="GET">
                    <input type="hidden" name="route" value="talentos">
                    
                    <div class="row">
                        <div class="col-lg-5 mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control form-control-lg" id="busca" name="busca" placeholder="Buscar por nome ou profissão..." value="<?php echo htmlspecialchars($busca); ?>">
                            </div>
                        </div>
                        
                        <div class="col-lg-3 mb-3">
                            <select class="form-control form-control-lg" id="profissao" name="profissao">
                                <option value="">Todas as profissões</option>
                                <?php foreach ($profissoes as $prof): ?>
                                    <option value="<?php echo htmlspecialchars($prof['profissao']); ?>" <?php echo ($filtro_profissao == $prof['profissao']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prof['profissao']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 mb-3">
                            <select class="form-control form-control-lg" id="nivel" name="nivel">
                                <option value="">Qualquer nível</option>
                                <option value="Estágio" <?php echo ($filtro_nivel == 'Estágio') ? 'selected' : ''; ?>>Estágio</option>
                                <option value="Júnior" <?php echo ($filtro_nivel == 'Júnior') ? 'selected' : ''; ?>>Júnior</option>
                                <option value="Pleno" <?php echo ($filtro_nivel == 'Pleno') ? 'selected' : ''; ?>>Pleno</option>
                                <option value="Sênior" <?php echo ($filtro_nivel == 'Sênior') ? 'selected' : ''; ?>>Sênior</option>
                                <option value="Especialista" <?php echo ($filtro_nivel == 'Especialista') ? 'selected' : ''; ?>>Especialista</option>
                                <option value="Coordenador" <?php echo ($filtro_nivel == 'Coordenador') ? 'selected' : ''; ?>>Coordenador</option>
                                <option value="Gerente" <?php echo ($filtro_nivel == 'Gerente') ? 'selected' : ''; ?>>Gerente</option>
                                <option value="Diretor" <?php echo ($filtro_nivel == 'Diretor') ? 'selected' : ''; ?>>Diretor</option>
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
                <p>Encontrei <?php echo $total_talentos; ?> talentos que correspondem aos seus critérios.</p>
            </div>
            
            <?php if (empty($talentos)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhum talento encontrado com os critérios de busca atuais. Tente ajustar os filtros.
                    </div>
                </div>
            <?php else: ?>
                <?php 
                $contador = 0;
                foreach ($talentos as $talento): 
                    $contador++;
                ?>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card talento-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="talento-avatar me-3">
                                        <?php 
                                        $tem_foto = false;
                                        if (!empty($talento['foto_perfil'])) {
                                            // Verificar se o arquivo existe no caminho correto
                                            $caminho_foto = $_SERVER['DOCUMENT_ROOT'] . '/open2w/uploads/perfil/' . $talento['foto_perfil'];
                                            if (file_exists($caminho_foto)) {
                                                $tem_foto = true;
                                            }
                                        }
                                        
                                        if ($tem_foto): 
                                        ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" alt="<?php echo htmlspecialchars($talento['nome']); ?>" class="rounded-circle">
                                        <?php else: 
                                            // Gerar iniciais do nome
                                            $nome_partes = explode(' ', $talento['nome']);
                                            $iniciais = '';
                                            
                                            if (count($nome_partes) >= 2) {
                                                // Pegar a primeira letra do primeiro e último nome
                                                $iniciais = strtoupper(substr($nome_partes[0], 0, 1) . substr(end($nome_partes), 0, 1));
                                            } else {
                                                // Se tiver apenas um nome, pegar as duas primeiras letras
                                                $iniciais = strtoupper(substr($talento['nome'], 0, 2));
                                                if (strlen($iniciais) < 2) {
                                                    $iniciais = strtoupper(substr($talento['nome'], 0, 1)) . 'T';
                                                }
                                            }
                                            
                                            // Gerar cor baseada no nome para o background
                                            $hash = md5($talento['nome']);
                                            $cor_bg = '#' . substr($hash, 0, 6);
                                            
                                            // Garantir contraste adequado para o texto
                                            $r = hexdec(substr($cor_bg, 1, 2));
                                            $g = hexdec(substr($cor_bg, 3, 2));
                                            $b = hexdec(substr($cor_bg, 5, 2));
                                            $luminosidade = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                            $cor_texto = ($luminosidade > 128) ? '#000000' : '#FFFFFF';
                                        ?>
                                            <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" style="background-color: <?php echo $cor_bg; ?>; color: <?php echo $cor_texto; ?>; width: 60px; height: 60px; font-weight: bold; font-size: 1.2rem;">
                                                <?php echo $iniciais; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($talento['nome']); ?></h5>
                                        <p class="profissao-tag mb-0"><?php echo htmlspecialchars($talento['profissao'] ?? 'Profissional'); ?></p>
                                        <?php if (!empty($talento['nivel'])): ?>
                                        <span class="badge bg-info text-dark nivel-badge"><?php echo htmlspecialchars($talento['nivel']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="talento-info mb-3">
                                    <p class="mb-0">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <strong>Membro desde:</strong> 
                                        <?php echo date('d/m/Y', strtotime($talento['data_cadastro'])); ?>
                                    </p>
                                </div>
                                
                                <div class="talento-apresentacao">
                                    <p class="card-text">
                                        <?php 
                                        if (!empty($talento['carta_apresentacao'])) {
                                            echo nl2br(htmlspecialchars(substr($talento['carta_apresentacao'], 0, 150) . (strlen($talento['carta_apresentacao']) > 150 ? '...' : '')));
                                        } else {
                                            echo '<em>Este talento ainda não adicionou uma apresentação.</em>';
                                        }
                                        ?>
                                    </p>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento['id']; ?>" class="btn btn-primary btn-sm">Ver perfil completo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Inserir anúncio a cada 8 talentos (após 2 linhas de 4 talentos)
                    if ($contador % 8 === 0 && $adsense->isPosicaoAtiva('talentos_lista')): 
                    ?>
                    <div class="col-12 mb-4">
                        <div class="ad-container">
                            <?php echo $adsense->exibirAnuncio('talentos_lista', 'horizontal'); ?>
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
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=talentos&pagina=<?php echo $pagina_atual - 1; ?>&busca=<?php echo urlencode($busca); ?>&profissao=<?php echo urlencode($filtro_profissao); ?>&experiencia=<?php echo $filtro_experiencia; ?>">
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
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=talentos&pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&profissao=<?php echo urlencode($filtro_profissao); ?>&experiencia=<?php echo $filtro_experiencia; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=talentos&pagina=<?php echo $pagina_atual + 1; ?>&busca=<?php echo urlencode($busca); ?>&profissao=<?php echo urlencode($filtro_profissao); ?>&experiencia=<?php echo $filtro_experiencia; ?>">
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
.filtros-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.filtros-titulo {
    margin-bottom: 20px;
    font-weight: 600;
}

.talento-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.talento-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.talento-avatar {
    width: 60px;
    height: 60px;
    overflow: hidden;
}

.talento-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
}

/* Estilos para destacar a profissão */
.profissao-tag {
    color: var(--bs-accent);
    font-weight: 500;
    font-size: 0.95rem;
    margin-bottom: 5px;
}

/* Estilo para o badge de nível */
.nivel-badge {
    display: inline-block;
    background-color: #e9f5ff;
    color: #0077cc;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    margin-top: 5px;
}

/* Melhorar a apresentação da carta */
.talento-apresentacao {
    font-size: 0.9rem;
    line-height: 1.4;
    color: #555;
    height: 80px;
    overflow: hidden;
}
</style>
