<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o sistema de vagas internas está ativo
$sistema_vagas_internas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_vagas_internas_ativo'");

// Configuração de paginação
$vagas_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $vagas_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtro_localizacao = isset($_GET['location']) ? trim($_GET['location']) : '';
$filtro_tipo = isset($_GET['type']) ? trim($_GET['type']) : '';
$filtro_modelo = isset($_GET['model']) ? trim($_GET['model']) : '';
$filtro_nivel = isset($_GET['experience']) ? trim($_GET['experience']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE v.status = 'aberta'";

// Mostrar todas as vagas (internas e externas), independentemente da configuração
$sql_where .= " AND (v.tipo_vaga = 'interna' OR v.tipo_vaga = 'externa')";

$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (v.titulo LIKE :busca OR v.descricao LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_localizacao)) {
    // Verificar se a localização contém vírgula (formato: cidade, estado)
    if (strpos($filtro_localizacao, ',') !== false) {
        list($cidade, $estado) = array_map('trim', explode(',', $filtro_localizacao));
        $sql_where .= " AND v.cidade LIKE :cidade AND v.estado LIKE :estado";
        $params['cidade'] = "%$cidade%";
        $params['estado'] = "%$estado%";
    } else {
        // Se não tiver vírgula, buscar em ambos os campos
        $sql_where .= " AND (v.cidade LIKE :localizacao OR v.estado LIKE :localizacao)";
        $params['localizacao'] = "%$filtro_localizacao%";
    }
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

// Consulta para contar o total de vagas
$total_vagas = $db->fetchColumn("
    SELECT COUNT(*)
    FROM vagas v
    LEFT JOIN usuarios u ON v.empresa_id = u.id
    LEFT JOIN empresas e ON u.id = e.usuario_id
    $sql_where
", $params);

// Consulta para obter as vagas da página atual
$vagas = $db->fetchAll("
    SELECT v.*, 
           CASE 
               WHEN v.tipo_vaga = 'externa' AND v.empresa_externa IS NOT NULL THEN v.empresa_externa
               ELSE u.nome 
           END as empresa_nome, 
           u.foto_perfil as empresa_logo,
           tc.nome as tipo_contrato_nome,
           rt.nome as regime_trabalho_nome,
           ne.nome as nivel_experiencia_nome
    FROM vagas v
    LEFT JOIN usuarios u ON v.empresa_id = u.id
    LEFT JOIN empresas e ON u.id = e.usuario_id
    LEFT JOIN tipos_contrato tc ON v.tipo_contrato_id = tc.id
    LEFT JOIN regimes_trabalho rt ON v.regime_trabalho_id = rt.id
    LEFT JOIN niveis_experiencia ne ON v.nivel_experiencia_id = ne.id
    $sql_where
    ORDER BY v.data_publicacao DESC
    LIMIT $vagas_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_vagas / $vagas_por_pagina);

// Obter tipos de contrato, modelos de trabalho e níveis de experiência para os filtros
$tipos_contrato = $db->fetchAll("SELECT DISTINCT tipo_contrato FROM vagas WHERE tipo_contrato IS NOT NULL AND tipo_contrato != '' ORDER BY tipo_contrato");
$modelos_trabalho = $db->fetchAll("SELECT DISTINCT regime_trabalho FROM vagas WHERE regime_trabalho IS NOT NULL AND regime_trabalho != '' ORDER BY regime_trabalho");
$niveis_experiencia = $db->fetchAll("SELECT DISTINCT nivel_experiencia FROM vagas WHERE nivel_experiencia IS NOT NULL AND nivel_experiencia != '' ORDER BY nivel_experiencia");
// Obter localizações distintas para o filtro
$localizacoes = $db->fetchAll("SELECT DISTINCT CONCAT(cidade, ', ', estado) as localizacao FROM vagas WHERE cidade IS NOT NULL AND estado IS NOT NULL ORDER BY localizacao");

// Simulação de dados para a sidebar (manter para compatibilidade)
$allJobs = [
    [
        'id' => 1,
        'title' => 'Desenvolvedor Full Stack',
        'company' => 'TechSolutions',
        'logo' => 'default-company.png',
        'location' => 'São Paulo, SP',
        'type' => 'CLT',
        'model' => 'Híbrido',
        'experience' => 'Pleno',
        'salary' => 'R$ 8.000 - R$ 12.000',
        'tags' => ['PHP', 'JavaScript', 'React', 'Node.js'],
        'date' => '2025-04-15',
        'description' => 'A TechSolutions está em busca de um Desenvolvedor Full Stack para integrar nosso time de tecnologia. O profissional será responsável pelo desenvolvimento e manutenção de aplicações web, trabalhando tanto no front-end quanto no back-end.'
    ],
    [
        'id' => 2,
        'title' => 'UX/UI Designer Sênior',
        'company' => 'Creative Design',
        'logo' => 'default-company.png',
        'location' => 'Rio de Janeiro, RJ',
        'type' => 'PJ',
        'model' => 'Remoto',
        'experience' => 'Sênior',
        'salary' => 'R$ 7.000 - R$ 10.000',
        'tags' => ['Figma', 'Adobe XD', 'UI', 'UX Research'],
        'date' => '2025-04-18',
        'description' => 'A Creative Design está em busca de um UX/UI Designer Sênior para liderar projetos de design de interfaces e experiência do usuário. O profissional será responsável por criar interfaces intuitivas e atraentes para nossos clientes.'
    ],
    [
        'id' => 3,
        'title' => 'Analista de Marketing Digital',
        'company' => 'MarketBoost',
        'logo' => 'default-company.png',
        'location' => 'Belo Horizonte, MG',
        'type' => 'CLT',
        'model' => 'Presencial',
        'experience' => 'Pleno',
        'salary' => 'R$ 5.000 - R$ 7.000',
        'tags' => ['SEO', 'Google Ads', 'Social Media', 'Analytics'],
        'date' => '2025-04-20',
        'description' => 'Estamos procurando um Analista de Marketing Digital para desenvolver e implementar estratégias de marketing online, gerenciar campanhas e analisar resultados para otimização contínua.'
    ],
    [
        'id' => 4,
        'title' => 'Cientista de Dados',
        'company' => 'DataInsights',
        'logo' => 'default-company.png',
        'location' => 'Curitiba, PR',
        'type' => 'CLT',
        'model' => 'Híbrido',
        'experience' => 'Sênior',
        'salary' => 'R$ 10.000 - R$ 15.000',
        'tags' => ['Python', 'Machine Learning', 'SQL', 'Data Visualization'],
        'date' => '2025-04-17',
        'description' => 'Buscamos um Cientista de Dados experiente para analisar grandes volumes de dados, desenvolver modelos preditivos e extrair insights valiosos para tomada de decisões de negócios.'
    ],
    [
        'id' => 5,
        'title' => 'Gerente de Projetos',
        'company' => 'ProjectMasters',
        'logo' => 'default-company.png',
        'location' => 'Brasília, DF',
        'type' => 'CLT',
        'model' => 'Presencial',
        'experience' => 'Sênior',
        'salary' => 'R$ 12.000 - R$ 18.000',
        'tags' => ['Scrum', 'Agile', 'Gestão de Equipes', 'PMP'],
        'date' => '2025-04-16',
        'description' => 'Procuramos um Gerente de Projetos para liderar equipes multidisciplinares, garantir a entrega de projetos dentro do prazo e orçamento, e implementar metodologias ágeis.'
    ],
    [
        'id' => 6,
        'title' => 'DevOps Engineer',
        'company' => 'CloudTech',
        'logo' => 'default-company.png',
        'location' => 'Porto Alegre, RS',
        'type' => 'PJ',
        'model' => 'Remoto',
        'experience' => 'Pleno',
        'salary' => 'R$ 9.000 - R$ 14.000',
        'tags' => ['AWS', 'Docker', 'Kubernetes', 'CI/CD'],
        'date' => '2025-04-19',
        'description' => 'Estamos à procura de um DevOps Engineer para implementar e gerenciar infraestrutura em nuvem, automatizar processos de deploy e garantir a estabilidade dos ambientes de produção.'
    ],
    [
        'id' => 7,
        'title' => 'Desenvolvedor Mobile',
        'company' => 'AppMakers',
        'logo' => 'default-company.png',
        'location' => 'São Paulo, SP',
        'type' => 'CLT',
        'model' => 'Híbrido',
        'experience' => 'Pleno',
        'salary' => 'R$ 7.000 - R$ 10.000',
        'tags' => ['React Native', 'Flutter', 'iOS', 'Android'],
        'date' => '2025-04-14',
        'description' => 'Buscamos um Desenvolvedor Mobile para criar aplicativos nativos e híbridos para iOS e Android, implementar novas funcionalidades e otimizar o desempenho dos apps existentes.'
    ],
    [
        'id' => 8,
        'title' => 'Analista de Segurança da Informação',
        'company' => 'SecureData',
        'logo' => 'default-company.png',
        'location' => 'Rio de Janeiro, RJ',
        'type' => 'CLT',
        'model' => 'Presencial',
        'experience' => 'Sênior',
        'salary' => 'R$ 9.000 - R$ 13.000',
        'tags' => ['Pentest', 'Firewall', 'LGPD', 'ISO 27001'],
        'date' => '2025-04-13',
        'description' => 'Procuramos um Analista de Segurança da Informação para implementar políticas de segurança, realizar testes de penetração, monitorar ameaças e garantir a conformidade com regulamentações.'
    ],
    [
        'id' => 9,
        'title' => 'Desenvolvedor Back-end',
        'company' => 'ServerTech',
        'logo' => 'default-company.png',
        'location' => 'Florianópolis, SC',
        'type' => 'PJ',
        'model' => 'Remoto',
        'experience' => 'Pleno',
        'salary' => 'R$ 7.000 - R$ 11.000',
        'tags' => ['Java', 'Spring Boot', 'Microservices', 'PostgreSQL'],
        'date' => '2025-04-12',
        'description' => 'Estamos à procura de um Desenvolvedor Back-end para projetar e implementar APIs, desenvolver microserviços e garantir a escalabilidade e performance das aplicações.'
    ],
    [
        'id' => 10,
        'title' => 'Product Owner',
        'company' => 'InnovateNow',
        'logo' => 'default-company.png',
        'location' => 'Belo Horizonte, MG',
        'type' => 'CLT',
        'model' => 'Híbrido',
        'experience' => 'Sênior',
        'salary' => 'R$ 10.000 - R$ 15.000',
        'tags' => ['Scrum', 'Backlog', 'User Stories', 'Roadmap'],
        'date' => '2025-04-11',
        'description' => 'Buscamos um Product Owner para definir a visão do produto, priorizar o backlog, trabalhar com stakeholders e garantir que o time de desenvolvimento entregue valor aos usuários.'
    ]
];

// Simulação de filtro de vagas (em um sistema real, seria feito no banco de dados)
$filteredJobs = $allJobs;

if (!empty($searchQuery)) {
    $filteredJobs = array_filter($filteredJobs, function($job) use ($searchQuery) {
        return (
            stripos($job['title'], $searchQuery) !== false ||
            stripos($job['company'], $searchQuery) !== false ||
            stripos($job['description'], $searchQuery) !== false ||
            count(array_filter($job['tags'], function($tag) use ($searchQuery) {
                return stripos($tag, $searchQuery) !== false;
            })) > 0
        );
    });
}

if (!empty($location)) {
    $filteredJobs = array_filter($filteredJobs, function($job) use ($location) {
        return stripos($job['location'], $location) !== false;
    });
}

if (!empty($type)) {
    $filteredJobs = array_filter($filteredJobs, function($job) use ($type) {
        return $job['type'] === $type;
    });
}

if (!empty($model)) {
    $filteredJobs = array_filter($filteredJobs, function($job) use ($model) {
        return $job['model'] === $model;
    });
}

if (!empty($experience)) {
    $filteredJobs = array_filter($filteredJobs, function($job) use ($experience) {
        return $job['experience'] === $experience;
    });
}

// Buscar termos populares reais do banco de dados
$popularSearches = [];

// Consulta mais abrangente para termos populares
$popular_terms_query = "SELECT 
    CASE 
        WHEN palavras_chave IS NOT NULL AND palavras_chave != '' THEN palavras_chave
        ELSE titulo 
    END as termo,
    COUNT(*) as count 
    FROM vagas 
    WHERE status = 'aberta' 
    GROUP BY termo 
    ORDER BY count DESC, termo ASC 
    LIMIT 8";
$popular_terms = $db->fetchAll($popular_terms_query);

foreach ($popular_terms as $term) {
    // Extrair palavras-chave individuais se contiver vírgulas
    if (strpos($term['termo'], ',') !== false) {
        $keywords = array_map('trim', explode(',', $term['termo']));
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 3) { // Ignorar palavras muito curtas
                $popularSearches[] = [
                    'term' => $keyword,
                    'count' => ceil($term['count'] / count($keywords))
                ];
            }
        }
    } else {
        $popularSearches[] = [
            'term' => $term['termo'],
            'count' => $term['count']
        ];
    }
    
    // Limitar a 5 termos
    if (count($popularSearches) >= 5) {
        break;
    }
}

// Se não houver resultados suficientes, buscar termos de outras tabelas
if (count($popularSearches) < 5) {
    // Buscar habilidades populares dos talentos
    $skills_query = "SELECT habilidade as termo, COUNT(*) as count FROM talentos_habilidades GROUP BY habilidade ORDER BY count DESC LIMIT 5";
    try {
        $skills = $db->fetchAll($skills_query);
        foreach ($skills as $skill) {
            if (count($popularSearches) >= 5) break;
            
            // Verificar se o termo já existe
            $exists = false;
            foreach ($popularSearches as $search) {
                if (strtolower($search['term']) == strtolower($skill['termo'])) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $popularSearches[] = [
                    'term' => $skill['termo'],
                    'count' => $skill['count']
                ];
            }
        }
    } catch (Exception $e) {
        // Ignorar erros e usar termos padrão se necessário
    }
    
    // Se ainda não tiver 5 termos, adicionar alguns padrão
    if (count($popularSearches) < 5) {
        $default_terms = [
            ['term' => 'Desenvolvedor', 'count' => max(1, count($vagas))],
            ['term' => 'Marketing', 'count' => max(1, round(count($vagas) * 0.8))],
            ['term' => 'Designer', 'count' => max(1, round(count($vagas) * 0.6))],
            ['term' => 'Analista', 'count' => max(1, round(count($vagas) * 0.4))],
            ['term' => 'Gerente', 'count' => max(1, round(count($vagas) * 0.3))]
        ];
        
        // Adicionar apenas os termos necessários para completar 5
        $needed = 5 - count($popularSearches);
        for ($i = 0; $i < $needed && $i < count($default_terms); $i++) {
            // Verificar se o termo já existe
            $exists = false;
            foreach ($popularSearches as $search) {
                if (strtolower($search['term']) == strtolower($default_terms[$i]['term'])) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $popularSearches[] = $default_terms[$i];
            }
        }
    }
}

// Buscar empresas em destaque reais do banco de dados
$featuredCompanies = [];
$featured_companies_query = "SELECT e.razao_social, e.logo, COUNT(v.id) as vagas_count 
                           FROM empresas e 
                           LEFT JOIN vagas v ON e.id = v.empresa_id AND v.status = 'aberta' 
                           GROUP BY e.id 
                           ORDER BY vagas_count DESC 
                           LIMIT 4";
$companies = $db->fetchAll($featured_companies_query);

foreach ($companies as $company) {
    $featuredCompanies[] = [
        'name' => $company['razao_social'],
        'logo' => $company['logo'] ?? 'default-company.png',
        'jobs' => $company['vagas_count']
    ];
}

// Se não houver resultados suficientes, adicionar algumas empresas padrão
if (count($featuredCompanies) < 4) {
    $default_companies = [
        ['name' => 'OpenToJob', 'logo' => 'default-company.png', 'jobs' => count($vagas)],
        ['name' => 'TechSolutions', 'logo' => 'default-company.png', 'jobs' => round(count($vagas) * 0.7)],
        ['name' => 'Creative Design', 'logo' => 'default-company.png', 'jobs' => round(count($vagas) * 0.5)],
        ['name' => 'DataInsights', 'logo' => 'default-company.png', 'jobs' => round(count($vagas) * 0.3)]
    ];
    
    // Adicionar apenas as empresas necessárias para completar 4
    $needed = 4 - count($featuredCompanies);
    for ($i = 0; $i < $needed && $i < count($default_companies); $i++) {
        $featuredCompanies[] = $default_companies[$i];
    }
}
?>

<div class="jobs-header">
    <div class="container-wide">
        <h1 class="jobs-title">Encontre a vaga ideal para você</h1>
        <p class="jobs-subtitle">Explore oportunidades em diversas áreas e localizações</p>

    </div>
</div>

<div class="container-wide">
    <!-- Espaço para anúncio no topo da página de vagas -->
    <?php if ($adsense->isPosicaoAtiva('vagas_topo')): ?>
    <div class="ad-container mb-4">
        <?php echo $adsense->exibirAnuncio('vagas_topo', 'horizontal'); ?>
    </div>
    <?php endif; ?>
    
    <div class="jobs-filters">
        <form action="<?php echo SITE_URL; ?>/?route=vagas" method="GET" class="filters-form">
            <input type="hidden" name="route" value="vagas">
            
            <div class="filter-group">
                <label for="q" class="filter-label">Palavra-chave</label>
                <input type="text" id="q" name="q" class="filter-control" placeholder="Cargo, habilidade ou empresa" value="<?php echo isset($busca) ? htmlspecialchars($busca) : ''; ?>">
            </div>
            
            <div class="filter-group">
                <label for="location" class="filter-label">Localização</label>
                <select id="location" name="location" class="filter-control">
                    <option value="">Todas as localizações</option>
                    <?php foreach ($localizacoes as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc['localizacao']); ?>" <?php echo $filtro_localizacao === $loc['localizacao'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['localizacao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="type" class="filter-label">Tipo de contrato</label>
                <select id="type" name="type" class="filter-control">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($tipos_contrato as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo['tipo_contrato']); ?>" <?php echo $filtro_tipo === $tipo['tipo_contrato'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['tipo_contrato']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="regime_trabalho" class="filter-label">Regime de Trabalho</label>
                <select id="model" name="model" class="filter-control">
                    <option value="">Todos os modelos</option>
                    <?php foreach ($modelos_trabalho as $modelo): ?>
                        <option value="<?php echo htmlspecialchars($modelo['regime_trabalho']); ?>" <?php echo $filtro_modelo === $modelo['regime_trabalho'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($modelo['regime_trabalho']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="experience" class="filter-label">Nível de experiência</label>
                <select id="experience" name="experience" class="filter-control">
                    <option value="">Todos os níveis</option>
                    <?php foreach ($niveis_experiencia as $nivel): ?>
                        <option value="<?php echo htmlspecialchars($nivel['nivel_experiencia']); ?>" <?php echo $filtro_nivel === $nivel['nivel_experiencia'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nivel['nivel_experiencia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filters-buttons">
                <button type="submit" class="btn btn-primary filter-btn">Filtrar vagas</button>
                <button type="reset" class="clear-filters">Limpar filtros</button>
            </div>
        </form>
    </div>
    
    <div class="jobs-container">
        <div class="jobs-list">
            <h2>Resultados da busca <span>(<?php echo count($vagas); ?> vagas encontradas)</span></h2>
            
            <?php if (empty($vagas)): ?>
                <div class="alert alert-info">
                    Nenhuma vaga encontrada com os filtros selecionados. Tente ajustar seus critérios de busca.
                </div>
            <?php else: ?>
                <?php 
                $contador = 0;
                foreach ($vagas as $vaga): 
                    $contador++;
                ?>
                    <div class="job-item">
                        <div class="job-header">
                            <img src="<?php echo SITE_URL; ?>/uploads/empresas/<?php echo $vaga['empresa_logo']; ?>" alt="<?php echo $vaga['empresa_nome']; ?>" class="job-logo">
                            <div class="job-title-container">
                                <h3 class="job-title"><?php echo htmlspecialchars($vaga['titulo']); ?></h3>
                                <div class="job-company"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></div>
                            </div>
                        </div>
                        <div class="job-details">
                            <div class="job-location"><i class="fas fa-map-marker-alt"></i> <?php echo isset($vaga['localizacao']) && $vaga['localizacao'] !== null ? htmlspecialchars($vaga['localizacao']) : ''; ?></div>
                            <div class="job-type"><i class="fas fa-briefcase"></i> <?php echo isset($vaga['tipo_contrato']) && $vaga['tipo_contrato'] !== null ? htmlspecialchars($vaga['tipo_contrato']) : ''; ?></div>
                            <?php if (!empty($vaga['modelo_trabalho'])): ?>
                                <div class="job-model"><i class="fas fa-building"></i> <?php echo htmlspecialchars($vaga['modelo_trabalho']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($vaga['nivel_experiencia'])): ?>
                                <div class="job-experience"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($vaga['nivel_experiencia']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="job-actions">
                            <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $vaga['id']; ?>" class="btn btn-primary">Ver detalhes</a>
                            <?php if (!empty($vaga['link_candidatura'])): ?>
                            <a href="<?php echo htmlspecialchars($vaga['link_candidatura']); ?>" class="btn btn-outline-primary" target="_blank">Candidatar-se</a>
                            <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $vaga['id']; ?>" class="btn btn-outline-primary">Candidatar-se</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php 
                    // Inserir anúncio a cada 3 vagas
                    if ($contador % 3 === 0 && $adsense->isPosicaoAtiva('vagas_lista')): 
                    ?>
                    <div class="ad-container my-4 anuncio-vaga">
                        <?php echo $adsense->exibirAnuncio('vagas_lista', 'vaga'); ?>
                    </div>
                    <?php endif; ?>
                    
                <?php endforeach; ?>
                
                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <ul class="pagination-list">
                            <?php if ($pagina_atual > 1): ?>
                                <li class="pagination-item">
                                    <a href="<?php echo SITE_URL; ?>/?route=vagas&pagina=<?php echo $pagina_atual - 1; ?>&q=<?php echo urlencode($busca); ?>&location=<?php echo urlencode($filtro_localizacao); ?>&type=<?php echo urlencode($filtro_tipo); ?>&model=<?php echo urlencode($filtro_modelo); ?>&experience=<?php echo urlencode($filtro_nivel); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="pagination-item disabled">
                                    <span><i class="fas fa-chevron-left"></i></span>
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
                                <li class="pagination-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                    <?php if ($i == $pagina_atual): ?>
                                        <span><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo SITE_URL; ?>/?route=vagas&pagina=<?php echo $i; ?>&q=<?php echo urlencode($busca); ?>&location=<?php echo urlencode($filtro_localizacao); ?>&type=<?php echo urlencode($filtro_tipo); ?>&model=<?php echo urlencode($filtro_modelo); ?>&experience=<?php echo urlencode($filtro_nivel); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_atual < $total_paginas): ?>
                                <li class="pagination-item">
                                    <a href="<?php echo SITE_URL; ?>/?route=vagas&pagina=<?php echo $pagina_atual + 1; ?>&q=<?php echo urlencode($busca); ?>&location=<?php echo urlencode($filtro_localizacao); ?>&type=<?php echo urlencode($filtro_tipo); ?>&model=<?php echo urlencode($filtro_modelo); ?>&experience=<?php echo urlencode($filtro_nivel); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="pagination-item disabled">
                                    <span><i class="fas fa-chevron-right"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="jobs-sidebar">
            <!-- Espaço para anúncio na barra lateral -->
            <?php if ($adsense->isPosicaoAtiva('vagas_lateral')): ?>
            <div class="sidebar-widget ad-sidebar-container mb-4">
                <?php echo $adsense->exibirAnuncio('vagas_lateral', 'vertical'); ?>
            </div>
            <?php endif; ?>
            
            <div class="sidebar-widget">
                <h3 class="widget-title">Buscas populares</h3>
                <ul class="popular-searches">
                    <?php foreach ($popularSearches as $search): ?>
                        <li class="popular-search-item">
                            <a href="<?php echo SITE_URL; ?>/?route=vagas&q=<?php echo urlencode($search['term']); ?>" class="popular-search-link">
                                <?php echo $search['term']; ?>
                                <span class="popular-search-count"><?php echo $search['count']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="sidebar-widget">
                <h3 class="widget-title">Empresas em destaque</h3>
                
                <ul class="featured-companies">
                    <?php foreach ($featuredCompanies as $company): ?>
                        <li class="featured-company-item">
                            <a href="<?php echo SITE_URL; ?>/?route=vagas&q=<?php echo urlencode($company['name']); ?>" class="featured-company-link">
                                <img src="<?php echo SITE_URL; ?>/assets/img/companies/<?php echo $company['logo']; ?>" alt="<?php echo $company['name']; ?>" class="featured-company-logo">
                                <div class="featured-company-info">
                                    <p class="featured-company-name"><?php echo $company['name']; ?></p>
                                    <p class="featured-company-jobs"><?php echo $company['jobs']; ?> vagas ativas</p>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="sidebar-widget">
                <h3 class="widget-title">Ative seu status #opentojob</h3>
                <p>Mostre às empresas que você está disponível para novas oportunidades imediatas.</p>
                <?php if (Auth::isLoggedIn() && Auth::checkUserType('talento')): ?>
                    <a href="<?php echo SITE_URL; ?>/?route=perfil_talento" class="btn btn-accent" style="width: 100%; margin-top: 15px;">Ativar #opentojob</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/?route=cadastro_talento" class="btn btn-accent" style="width: 100%; margin-top: 15px;">Cadastre-se como talento</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
