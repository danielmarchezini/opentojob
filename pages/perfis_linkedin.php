<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Configuração de paginação
$perfis_por_pagina = 12;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $perfis_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_assunto = isset($_GET['assunto']) ? trim($_GET['assunto']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE status = 'ativo'";
$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (nome LIKE :busca OR assunto LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_assunto)) {
    $sql_where .= " AND assunto LIKE :assunto";
    $params['assunto'] = "%$filtro_assunto%";
}

// Consulta para obter o total de perfis
$total_perfis = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM perfis_linkedin
    $sql_where
", $params);

// Consulta para obter os perfis da página atual
$perfis = $db->fetchAll("
    SELECT id, nome, foto, assunto, link_perfil, destaque
    FROM perfis_linkedin
    $sql_where
    ORDER BY destaque DESC, nome ASC
    LIMIT $perfis_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_perfis / $perfis_por_pagina);

// Obter lista de assuntos para o filtro
$assuntos = $db->fetchAll("
    SELECT DISTINCT assunto
    FROM perfis_linkedin
    WHERE status = 'ativo'
    ORDER BY assunto ASC
");
?>

<div class="linkedin-profiles-header">
    <div class="container-wide">
        <h1 class="profiles-title">Perfis Top do LinkedIn</h1>
        <p class="profiles-subtitle">Conecte-se com profissionais que compartilham conteúdo relevante sobre empregabilidade, carreira e desenvolvimento profissional</p>
    </div>
</div>

<section class="section-perfis-linkedin py-5">
    <div class="container-wide">
        <!-- Espaço para anúncio no topo da página de perfis LinkedIn -->
        <?php if ($adsense->isPosicaoAtiva('perfis_linkedin_topo')): ?>
        <div class="ad-container mb-4">
            <?php echo $adsense->exibirAnuncio('perfis_linkedin_topo', 'horizontal'); ?>
        </div>
        <?php endif; ?>
        
        <!-- Filtros de busca -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fab fa-linkedin me-2"></i>Filtrar Perfis</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo SITE_URL; ?>/?route=perfis_linkedin" method="GET" class="row g-3">
                    <input type="hidden" name="route" value="perfis_linkedin">
                    
                    <div class="col-md-6">
                        <label for="busca" class="form-label">Buscar por nome ou palavra-chave</label>
                        <input type="text" class="form-control" id="busca" name="busca" placeholder="Digite um nome ou palavra-chave" value="<?php echo htmlspecialchars((string)$busca); ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="assunto" class="form-label">Filtrar por assunto</label>
                        <select class="form-select" id="assunto" name="assunto">
                            <option value="">Todos os assuntos</option>
                            <?php foreach ($assuntos as $assunto): ?>
                                <option value="<?php echo htmlspecialchars((string)$assunto['assunto']); ?>" <?php echo ($filtro_assunto == $assunto['assunto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$assunto['assunto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Resultados da busca</h2>
                <p>Encontramos <?php echo $total_perfis; ?> perfis que correspondem aos seus critérios.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?php echo SITE_URL; ?>/?route=indicar_perfil_linkedin" class="btn btn-accent">
                    <i class="fas fa-user-plus me-2"></i>Indicar um perfil
                </a>
            </div>
        </div>
        
        <!-- Grid de perfis -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php if (empty($perfis)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <p class="mb-0">Nenhum perfil encontrado com os critérios selecionados.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php 
                $contador = 0;
                foreach ($perfis as $perfil): 
                    $contador++;
                ?>
                    <div class="col">
                        <div class="card h-100 linkedin-profile-card <?php echo $perfil['destaque'] ? 'featured' : ''; ?>">
                            <?php if ($perfil['destaque']): ?>
                                <div class="featured-badge">
                                    <i class="fas fa-star"></i> Destaque
                                </div>
                            <?php endif; ?>
                            
                            <div class="profile-img-container">
                                <img src="<?php echo SITE_URL; ?>/uploads/perfis_linkedin/<?php echo htmlspecialchars((string)$perfil['foto']); ?>" 
                                     class="card-img-top profile-img" 
                                     alt="<?php echo htmlspecialchars((string)$perfil['nome']); ?>">
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars((string)$perfil['nome']); ?></h5>
                                <p class="card-text profile-topic">
                                    <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars((string)$perfil['assunto']); ?>
                                </p>
                                
                                <a href="<?php echo htmlspecialchars((string)$perfil['link_perfil']); ?>" 
                                   class="btn btn-linkedin w-100" 
                                   target="_blank">
                                    <i class="fab fa-linkedin me-2"></i>Ver perfil
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Inserir anúncio a cada 8 perfis (após 2 linhas de 4 perfis)
                    if ($contador % 8 === 0 && $adsense->isPosicaoAtiva('perfis_linkedin_lista')): 
                    ?>
                    <div class="col-12 my-4">
                        <div class="ad-container">
                            <?php echo $adsense->exibirAnuncio('perfis_linkedin_lista', 'horizontal'); ?>
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
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=perfis_linkedin&pagina=1<?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_assunto) ? '&assunto=' . urlencode($filtro_assunto) : ''; ?>" aria-label="Primeira">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=perfis_linkedin&pagina=<?php echo $pagina_atual - 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_assunto) ? '&assunto=' . urlencode($filtro_assunto) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&lt;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Definir intervalo de páginas a serem exibidas
                            $intervalo = 2;
                            $inicio_intervalo = max(1, $pagina_atual - $intervalo);
                            $fim_intervalo = min($total_paginas, $pagina_atual + $intervalo);
                            
                            // Exibir primeira página se não estiver no intervalo
                            if ($inicio_intervalo > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=perfis_linkedin&pagina=1' . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_assunto) ? '&assunto=' . urlencode($filtro_assunto) : '') . '">1</a></li>';
                                if ($inicio_intervalo > 2) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                            }
                            
                            // Exibir páginas do intervalo
                            for ($i = $inicio_intervalo; $i <= $fim_intervalo; $i++) {
                                echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . SITE_URL . '/?route=perfis_linkedin&pagina=' . $i . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_assunto) ? '&assunto=' . urlencode($filtro_assunto) : '') . '">' . $i . '</a></li>';
                            }
                            
                            // Exibir última página se não estiver no intervalo
                            if ($fim_intervalo < $total_paginas) {
                                if ($fim_intervalo < $total_paginas - 1) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=perfis_linkedin&pagina=' . $total_paginas . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_assunto) ? '&assunto=' . urlencode($filtro_assunto) : '') . '">' . $total_paginas . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=perfis_linkedin&pagina=<?php echo $pagina_atual + 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_assunto) ? '&assunto=' . urlencode($filtro_assunto) : ''; ?>" aria-label="Próxima">
                                        <span aria-hidden="true">&gt;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo SITE_URL; ?>/?route=perfis_linkedin&pagina=<?php echo $total_paginas; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_assunto) ? '&assunto=' . urlencode($filtro_assunto) : ''; ?>" aria-label="Última">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
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
.linkedin-profiles-header {
    background-color: #0077b5;
    color: white;
    padding: 60px 0;
    text-align: center;
    margin-bottom: 30px;
}

.profiles-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.profiles-subtitle {
    font-size: 1.2rem;
    max-width: 800px;
    margin: 0 auto;
}

.linkedin-profile-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.linkedin-profile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.linkedin-profile-card.featured {
    border: 2px solid #0077b5;
}

.featured-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #0077b5;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    z-index: 10;
}

.profile-img-container {
    height: 200px;
    overflow: hidden;
    background-color: #f8f9fa;
}

.profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.linkedin-profile-card:hover .profile-img {
    transform: scale(1.05);
}

.profile-topic {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.btn-linkedin {
    background-color: #0077b5;
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.btn-linkedin:hover {
    background-color: #005e8d;
    color: white;
}
</style>
