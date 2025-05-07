<section class="hero">
    <div class="container-wide hero-container">
        <div class="hero-content">
            <h1 class="hero-title">Conectando talentos prontos a oportunidades imediatas</h1>
            <p class="hero-subtitle">A plataforma onde só existem profissionais #OpenToJob, dispostos a contribuir imediatamente</p>
            
            <form class="search-form" action="<?php echo SITE_URL; ?>/?route=talentos" method="GET">
                <input type="hidden" name="route" value="talentos">
                <input type="text" name="q" class="search-input" placeholder="Digite habilidade, cargo ou palavra-chave">
                <button type="submit" class="search-btn">Buscar talentos</button>
            </form>
        </div>
        
        <div class="hero-image">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo_opentojob.svg" alt="OpenToJob - Conectando talentos prontos a oportunidades imediatas" style="max-width: 400px;">
        </div>
    </div>
</section>

<!-- Espaço para anúncio no topo da página -->
<?php if ($adsense->isPosicaoAtiva('inicio_topo')): ?>
<div class="ad-container">
    <?php echo $adsense->exibirAnuncio('inicio_topo', 'horizontal'); ?>
</div>
<?php endif; ?>

<section class="categories">
    <div class="container-wide">
        <h2 class="section-title">Profissões recentemente cadastradas</h2>
        
        <div class="categories-grid">
            <?php
            // Buscar profissões recentemente cadastradas no banco de dados
            $db = Database::getInstance();
            try {
                $profissoes = $db->fetchAll("
                    SELECT DISTINCT profissao, COUNT(*) as total
                    FROM talentos
                    WHERE profissao != ''
                    GROUP BY profissao
                    ORDER BY MAX(id) DESC
                    LIMIT 3
                ");
                
                if (empty($profissoes)) {
                    // Se não houver profissões cadastradas, exibir exemplos
                    $profissoes = [
                        ['profissao' => 'Desenvolvedor', 'total' => 0, 'icon' => 'fas fa-code', 'description' => 'Desenvolvimento de software e aplicações'],
                        ['profissao' => 'Marketing', 'total' => 0, 'icon' => 'fas fa-chart-line', 'description' => 'Marketing digital, SEO e mídias sociais'],
                        ['profissao' => 'Design', 'total' => 0, 'icon' => 'fas fa-paint-brush', 'description' => 'UI/UX, gráfico e web design']
                    ];
                }
                
                // Definir ícones para as profissões
                $icones = [
                    'desenvolvedor' => 'fas fa-code',
                    'programador' => 'fas fa-code',
                    'analista' => 'fas fa-laptop-code',
                    'designer' => 'fas fa-paint-brush',
                    'marketing' => 'fas fa-chart-line',
                    'vendas' => 'fas fa-handshake',
                    'administração' => 'fas fa-briefcase',
                    'recursos humanos' => 'fas fa-users',
                    'financeiro' => 'fas fa-money-bill-wave',
                    'jurídico' => 'fas fa-balance-scale',
                    'saúde' => 'fas fa-heartbeat',
                    'educação' => 'fas fa-graduation-cap'
                ];
                
                // Definir descrições para as profissões
                $descricoes = [
                    'desenvolvedor' => 'Desenvolvimento de software e aplicações',
                    'programador' => 'Codificação e desenvolvimento de sistemas',
                    'analista' => 'Análise e desenvolvimento de soluções',
                    'designer' => 'Design gráfico, UI/UX e web design',
                    'marketing' => 'Marketing digital, SEO e mídias sociais',
                    'vendas' => 'Vendas, negociação e atendimento ao cliente',
                    'administração' => 'Gestão administrativa e processos',
                    'recursos humanos' => 'Recrutamento, seleção e gestão de pessoas',
                    'financeiro' => 'Finanças, contabilidade e controle',
                    'jurídico' => 'Assessoria jurídica e compliance',
                    'saúde' => 'Cuidados com saúde e bem-estar',
                    'educação' => 'Ensino, treinamento e capacitação'
                ];
                
                foreach ($profissoes as $profissao) {
                    // Determinar o ícone com base na profissão
                    $icone = 'fas fa-briefcase'; // Ícone padrão
                    $descricao = 'Profissionais disponíveis para contratação imediata';
                    
                    // Verificar se há um ícone específico para esta profissão
                    foreach ($icones as $chave => $valor) {
                        if (stripos($profissao['profissao'], $chave) !== false) {
                            $icone = $valor;
                            break;
                        }
                    }
                    
                    // Verificar se há uma descrição específica para esta profissão
                    foreach ($descricoes as $chave => $valor) {
                        if (stripos($profissao['profissao'], $chave) !== false) {
                            $descricao = $valor;
                            break;
                        }
                    }
            ?>
            <div class="category-card">
                <div class="category-icon">
                    <i class="<?php echo $icone; ?>"></i>
                </div>
                <h3 class="category-title"><?php echo htmlspecialchars($profissao['profissao']); ?></h3>
                <p class="category-description"><?php echo $descricao; ?></p>
                <p class="category-count"><?php echo $profissao['total']; ?> talentos disponíveis</p>
                <div class="category-action">
                    <a href="<?php echo SITE_URL; ?>/?route=talentos&profissao=<?php echo urlencode($profissao['profissao']); ?>" class="btn btn-outline-primary btn-sm mt-3">Ver talentos</a>
                </div>
            </div>
            <?php 
                }
            } catch (PDOException $e) {
                // Em caso de erro, exibir categorias padrão
            ?>
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-code"></i>
                </div>
                <h3 class="category-title">Desenvolvedor</h3>
                <p class="category-description">Desenvolvimento de software e aplicações</p>
                <p class="category-count">Talentos disponíveis</p>
                <div class="category-action">
                    <a href="<?php echo SITE_URL; ?>/?route=talentos&profissao=Desenvolvedor" class="btn btn-outline-primary btn-sm mt-3">Ver talentos</a>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="category-title">Marketing</h3>
                <p class="category-description">Marketing digital, SEO e mídias sociais</p>
                <p class="category-count">Talentos disponíveis</p>
                <div class="category-action">
                    <a href="<?php echo SITE_URL; ?>/?route=talentos&profissao=Marketing" class="btn btn-outline-primary btn-sm mt-3">Ver talentos</a>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h3 class="category-title">Design</h3>
                <p class="category-description">UI/UX, gráfico e web design</p>
                <p class="category-count">Talentos disponíveis</p>
                <div class="category-action">
                    <a href="<?php echo SITE_URL; ?>/?route=talentos&profissao=Design" class="btn btn-outline-primary btn-sm mt-3">Ver talentos</a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>

<!-- Espaço para anúncio no meio da página -->
<?php if ($adsense->isPosicaoAtiva('inicio_meio')): ?>
<div class="ad-container">
    <?php echo $adsense->exibirAnuncio('inicio_meio', 'horizontal'); ?>
</div>
<?php endif; ?>

<section class="featured-talents">
    <div class="container-wide">
        <h2 class="section-title">Talentos recentemente cadastrados</h2>
        <p class="section-subtitle">Economize tempo e recursos com um processo de contratação simplificado. Aqui, cada conexão tem potencial real de conversão, já que todos os talentos estão realmente disponíveis e motivados para novos desafios</p>
        
        <div class="talents-grid">
            <?php
            // Buscar talentos recentemente cadastrados
            $db = Database::getInstance();
            $talentos = [];
            
            try {
                // Buscar talentos recentes, incluindo Daniel Marchezini se ele existir
                $talentos = $db->fetchAll("
                    SELECT t.usuario_id, t.profissao, t.disponibilidade, t.cidade, t.estado, t.foto_perfil,
                           u.nome, u.data_cadastro
                    FROM talentos t
                    JOIN usuarios u ON t.usuario_id = u.id
                    WHERE u.status = 'ativo'
                    ORDER BY CASE WHEN u.nome = 'Daniel Marchezini' THEN 0 ELSE 1 END, u.data_cadastro DESC
                    LIMIT 12
                ");
                
                if (empty($talentos)) {
                    // Se não houver talentos cadastrados, exibir exemplos
                    $talentos = [
                        [
                            'usuario_id' => 1,
                            'nome' => 'Ana Silva',
                            'profissao' => 'Desenvolvedora Full Stack',
                            'disponibilidade' => 'Imediata',
                            'cidade' => 'São Paulo',
                            'estado' => 'SP',
                            'foto_perfil' => 'default-user.jpg'
                        ],
                        [
                            'usuario_id' => 2,
                            'nome' => 'Carlos Oliveira',
                            'profissao' => 'Designer UX/UI',
                            'disponibilidade' => 'Imediata',
                            'cidade' => 'Rio de Janeiro',
                            'estado' => 'RJ',
                            'foto_perfil' => 'default-user.jpg'
                        ],
                        [
                            'usuario_id' => 3,
                            'nome' => 'Mariana Costa',
                            'profissao' => 'Analista de Marketing Digital',
                            'disponibilidade' => 'Imediata',
                            'cidade' => 'Belo Horizonte',
                            'estado' => 'MG',
                            'foto_perfil' => 'default-user.jpg'
                        ]
                    ];
                }
                
                // Exibir os talentos
                foreach ($talentos as $talento) {
                    // Definir foto, localidade e disponibilidade
                    $foto = !empty($talento['foto_perfil']) ? $talento['foto_perfil'] : 'default-user.jpg';
                    $localidade = '';
                    if (!empty($talento['cidade']) && !empty($talento['estado'])) {
                        $localidade = $talento['cidade'] . ', ' . $talento['estado'];
                    } elseif (!empty($talento['cidade'])) {
                        $localidade = $talento['cidade'];
                    } elseif (!empty($talento['estado'])) {
                        $localidade = $talento['estado'];
                    }
                    $disponibilidade = isset($talento['disponibilidade']) ? $talento['disponibilidade'] : 'Imediata';
            ?>
            <div class="talent-card">
                <div class="talent-header">
                    <?php 
                    $tem_foto = false;
                    if (!empty($talento['foto_perfil'])) {
                        $caminho_foto = $_SERVER['DOCUMENT_ROOT'] . '/open2w/uploads/perfil/' . $talento['foto_perfil'];
                        if (file_exists($caminho_foto)) {
                            $tem_foto = true;
                        }
                    }
                    
                    if ($tem_foto): 
                    ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" 
                             alt="<?php echo htmlspecialchars($talento['nome']); ?>" 
                             class="talent-avatar">
                    <?php else: ?>
                        <div class="talent-avatar-placeholder">
                            <?php 
                            $nome_completo = trim($talento['nome']);
                            $partes_nome = explode(' ', $nome_completo);
                            $iniciais = '';
                            
                            if (count($partes_nome) > 1) {
                                // Pegar a primeira letra do primeiro e do último nome
                                $iniciais = strtoupper(substr($partes_nome[0], 0, 1) . substr($partes_nome[count($partes_nome)-1], 0, 1));
                            } else {
                                // Se for apenas um nome, pegar a primeira letra
                                $iniciais = strtoupper(substr($nome_completo, 0, 1));
                            }
                            
                            echo $iniciais;
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="talent-info">
                        <h3 class="talent-name"><?php echo htmlspecialchars($talento['nome']); ?></h3>
                        <p class="talent-title"><?php echo htmlspecialchars($talento['profissao']); ?></p>
                        <p class="talent-location"><i class="fas fa-map-marker-alt"></i> <?php echo !empty($localidade) ? htmlspecialchars($localidade) : ''; ?></p>
                    </div>
                </div>
                
                <div class="talent-details">
                    <div class="talent-detail">
                        <i class="fas fa-clock"></i>
                        <span>Disponibilidade <?php echo htmlspecialchars($disponibilidade); ?></span>
                    </div>
                </div>
                
                <div class="talent-body">
                    <div class="talent-availability">
                        <span class="availability-badge">Pronto para começar</span>
                    </div>
                    <div class="talent-actions">
                        <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento['usuario_id']; ?>" class="btn btn-outline-primary">Ver perfil completo</a>
                    </div>
                </div>
            </div>
            <?php 
                }
            } catch (PDOException $e) {
                // Em caso de erro, exibir mensagem
                echo '<div class="text-center py-4">';
                echo '<p class="mb-4">Não encontramos talentos cadastrados no momento.</p>';
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/?route=talentos" class="btn btn-primary">Ver todos os talentos</a>
        </div>
    </div>
</section>

<section class="popular-jobs">
    <div class="container-wide">
        <h2 class="section-title">Vagas em destaque</h2>
        <p class="section-subtitle">Oportunidades selecionadas para profissionais #OpenToJob</p>
        
        <div class="jobs-grid">
            <?php
            // Buscar vagas em destaque (internas e externas)
            $db = Database::getInstance();
            $featuredJobs = [];
            
            try {
                // Buscar vagas internas
                $vagasInternas = $db->fetchAll("
                    SELECT v.id, v.titulo as title, e.nome as company, e.logo, 
                           CONCAT(v.cidade, ', ', v.estado) as location, 
                           v.tipo_contrato as type, v.regime_trabalho as model,
                           CONCAT('R$ ', FORMAT(v.salario_min, 0), ' - R$ ', FORMAT(v.salario_max, 0)) as salary,
                           v.data_publicacao as date
                    FROM vagas v
                    JOIN empresas e ON v.empresa_id = e.id
                    WHERE v.status = 'ativa'
                    ORDER BY v.data_publicacao DESC
                    LIMIT 12
                ");
                
                // Processar vagas internas
                foreach ($vagasInternas as $vaga) {
                    // Buscar tags/habilidades da vaga
                    $tags = $db->fetchColumn("
                        SELECT habilidade FROM vaga_habilidades 
                        WHERE vaga_id = :vaga_id
                        LIMIT 4
                    ", ['vaga_id' => $vaga['id']]);
                    
                    $vaga['tags'] = $tags ?: ['PHP', 'JavaScript', 'HTML', 'CSS'];
                    $featuredJobs[] = $vaga;
                }
                
                // Se não tiver 3 vagas internas, adicionar vagas simuladas
                if (count($featuredJobs) < 3) {
                    // Vagas simuladas para complementar
                    $simulatedJobs = [
                [
                    'id' => 1,
                    'title' => 'Desenvolvedor Full Stack',
                    'company' => 'TechSolutions',
                    'logo' => 'default-company.png',
                    'location' => 'São Paulo, SP',
                    'type' => 'CLT',
                    'model' => 'Híbrido',
                    'salary' => 'R$ 8.000 - R$ 12.000',
                    'tags' => ['PHP', 'JavaScript', 'React', 'Node.js'],
                    'date' => '2025-04-15'
                ],
                [
                    'id' => 2,
                    'title' => 'UX/UI Designer Sênior',
                    'company' => 'Creative Design',
                    'logo' => 'default-company.png',
                    'location' => 'Rio de Janeiro, RJ',
                    'type' => 'PJ',
                    'model' => 'Remoto',
                    'salary' => 'R$ 7.000 - R$ 10.000',
                    'tags' => ['Figma', 'Adobe XD', 'UI', 'UX Research'],
                    'date' => '2025-04-18'
                ],
                [
                    'id' => 3,
                    'title' => 'Analista de Marketing Digital',
                    'company' => 'MarketBoost',
                    'logo' => 'default-company.png',
                    'location' => 'Belo Horizonte, MG',
                    'type' => 'CLT',
                    'model' => 'Híbrido',
                    'salary' => 'R$ 6.000 - R$ 8.000',
                    'tags' => ['SEO', 'Google Ads', 'Social Media', 'Analytics'],
                    'date' => '2025-04-16'
                ],
                [
                    'id' => 4,
                    'title' => 'Cientista de Dados',
                    'company' => 'DataInsights',
                    'logo' => 'company4.png',
                    'location' => 'Curitiba, PR',
                    'type' => 'CLT',
                    'model' => 'Híbrido',
                    'salary' => 'R$ 10.000 - R$ 15.000',
                    'tags' => ['Python', 'Machine Learning', 'SQL', 'Data Visualization'],
                    'date' => '2025-04-17'
                ],
                [
                    'id' => 5,
                    'title' => 'Gerente de Projetos',
                    'company' => 'ProjectMasters',
                    'logo' => 'company5.png',
                    'location' => 'Brasília, DF',
                    'type' => 'CLT',
                    'model' => 'Presencial',
                    'salary' => 'R$ 12.000 - R$ 18.000',
                    'tags' => ['Scrum', 'Agile', 'Gestão de Equipes', 'PMP'],
                    'date' => '2025-04-16'
                ],
                [
                    'id' => 6,
                    'title' => 'DevOps Engineer',
                    'company' => 'CloudTech',
                    'logo' => 'company6.png',
                    'location' => 'Porto Alegre, RS',
                    'type' => 'PJ',
                    'model' => 'Remoto',
                    'salary' => 'R$ 9.000 - R$ 14.000',
                    'tags' => ['AWS', 'Docker', 'Kubernetes', 'CI/CD'],
                    'date' => '2025-04-19'
                ]
                    ];
                    
                    // Adicionar apenas o número necessário de vagas simuladas
                    $needed = 3 - count($featuredJobs);
                    for ($i = 0; $i < $needed && $i < count($simulatedJobs); $i++) {
                        $featuredJobs[] = $simulatedJobs[$i];
                    }
                }
            } catch (PDOException $e) {
                // Em caso de erro, usar apenas vagas simuladas
                $featuredJobs = [
                    [
                        'id' => 1,
                        'title' => 'Desenvolvedor Full Stack',
                        'company' => 'TechSolutions',
                        'logo' => 'default-company.png',
                        'location' => 'São Paulo, SP',
                        'type' => 'CLT',
                        'model' => 'Híbrido',
                        'salary' => 'R$ 8.000 - R$ 12.000',
                        'tags' => ['PHP', 'JavaScript', 'React', 'Node.js'],
                        'date' => '2025-04-15'
                    ],
                    [
                        'id' => 2,
                        'title' => 'UX/UI Designer Sênior',
                        'company' => 'Creative Design',
                        'logo' => 'default-company.png',
                        'location' => 'Rio de Janeiro, RJ',
                        'type' => 'PJ',
                        'model' => 'Remoto',
                        'salary' => 'R$ 7.000 - R$ 10.000',
                        'tags' => ['Figma', 'Adobe XD', 'UI', 'UX Research'],
                        'date' => '2025-04-18'
                    ],
                    [
                        'id' => 3,
                        'title' => 'Analista de Marketing Digital',
                        'company' => 'MarketBoost',
                        'logo' => 'default-company.png',
                        'location' => 'Belo Horizonte, MG',
                        'type' => 'CLT',
                        'model' => 'Híbrido',
                        'salary' => 'R$ 6.000 - R$ 8.000',
                        'tags' => ['SEO', 'Google Ads', 'Social Media', 'Analytics'],
                        'date' => '2025-04-16'
                    ]
                ];
            }
            
            foreach ($featuredJobs as $job):
            ?>
            <div class="job-card">
                <div class="job-header">
                    <img src="<?php echo SITE_URL; ?>/assets/img/companies/<?php echo $job['logo']; ?>" alt="<?php echo $job['company']; ?>" class="job-logo">
                    <div>
                        <p class="job-company"><?php echo $job['company']; ?></p>
                    </div>
                </div>
                
                <h3 class="job-title">
                    <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $job['id']; ?>"><?php echo $job['title']; ?></a>
                </h3>
                
                <div class="job-details">
                    <span class="job-detail">
                        <i class="fas fa-map-marker-alt"></i> <?php echo $job['location']; ?>
                    </span>
                    <span class="job-detail">
                        <i class="fas fa-briefcase"></i> <?php echo $job['type']; ?>
                    </span>
                    <span class="job-detail">
                        <i class="fas fa-building"></i> <?php echo $job['model']; ?>
                    </span>
                </div>
                
                <div class="job-tags">
                    <?php foreach ($job['tags'] as $tag): ?>
                    <span class="job-tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                
                <div class="job-actions mt-3">
                    <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm">Visitar vaga</a>
                </div>
                
                <div class="job-footer">
                    <span class="job-salary"><?php echo $job['salary']; ?></span>
                    <span class="job-date"><?php echo timeAgo($job['date']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/?route=vagas" class="btn btn-outline">Ver todas as vagas</a>
            <a href="<?php echo SITE_URL; ?>/?route=talentos" class="btn btn-accent ml-3">Buscar talentos</a>
        </div>
    </div>
</section>

<!-- Espaço para anúncio antes do rodapé -->
<?php if ($adsense->isPosicaoAtiva('inicio_rodape')): ?>
<div class="ad-container">
    <?php echo $adsense->exibirAnuncio('inicio_rodape', 'horizontal'); ?>
</div>
<?php endif; ?>

<section class="partner-companies">
    <div class="container-wide">
        <h2 class="section-title">Empresas que confiam no OpenToJob</h2>
        <p class="section-subtitle">Conectamos as melhores empresas com talentos prontos para começar</p>
        
        <?php
        // Buscar empresas cadastradas
        $db = Database::getInstance();
        try {
            $empresas = $db->fetchAll("
                SELECT nome, logo 
                FROM empresas 
                WHERE status = 'ativa' 
                ORDER BY id DESC 
                LIMIT 6
            ");
            
            if (!empty($empresas)) {
                echo '<div class="partner-logos">';
                foreach ($empresas as $empresa) {
                    $logo = !empty($empresa['logo']) ? $empresa['logo'] : 'default-company.png';
                    echo '<img src="' . SITE_URL . '/uploads/empresas/' . $logo . '" alt="' . htmlspecialchars($empresa['nome']) . '" class="partner-logo">';
                }
                echo '</div>';
            } else {
                // Não há empresas cadastradas
                echo '<div class="text-center py-4">';
                echo '<p class="mb-4">Não temos nenhuma ainda :( Cadastre-se para ajudar nossos talentos :)</p>';
                echo '<a href="' . SITE_URL . '/?route=cadastro_empresa" class="btn btn-primary">Cadastrar minha empresa</a>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            // Em caso de erro, mostrar mensagem padrão
            echo '<div class="text-center py-4">';
            echo '<p class="mb-4">Não temos nenhuma ainda :( Cadastre-se para ajudar nossos talentos :)</p>';
            echo '<a href="' . SITE_URL . '/?route=cadastro_empresa" class="btn btn-primary">Cadastrar minha empresa</a>';
            echo '</div>';
        }
        ?>
    </div>
</section>

<?php
// Verificar se os depoimentos estão habilitados
$db = Database::getInstance();
try {
    $depoimentos_habilitados = $db->fetchColumn("
        SELECT valor FROM configuracoes 
        WHERE chave = 'depoimentos_habilitados'
    ");
    
    // Se os depoimentos estiverem habilitados, exibi-los
    if ($depoimentos_habilitados && $depoimentos_habilitados[0] == '1') {
        // Buscar depoimentos cadastrados
        $depoimentos = $db->fetchAll("
            SELECT d.*, t.profissao 
            FROM depoimentos d
            JOIN talentos t ON d.talento_id = t.usuario_id
            WHERE d.status = 'aprovado'
            ORDER BY d.data_cadastro DESC
            LIMIT 3
        ");
        
        if (!empty($depoimentos)) {
?>
<section class="testimonials">
    <div class="container-wide">
        <h2 class="section-title">Histórias de sucesso do OpenToJob</h2>
        
        <div class="testimonials-grid">
            <?php foreach ($depoimentos as $depoimento): ?>
            <div class="testimonial-card">
                <p class="testimonial-text"><?php echo htmlspecialchars($depoimento['depoimento']); ?></p>
                <div class="testimonial-author">
                    <?php 
                    $foto = !empty($depoimento['foto']) ? $depoimento['foto'] : 'default-user.jpg';
                    ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $foto; ?>" alt="<?php echo htmlspecialchars($depoimento['nome']); ?>" class="testimonial-avatar">
                    <div>
                        <p class="testimonial-name"><?php echo htmlspecialchars($depoimento['nome']); ?></p>
                        <p class="testimonial-position"><?php echo htmlspecialchars($depoimento['profissao']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php 
        }
    }
} catch (PDOException $e) {
    // Em caso de erro, não exibir a seção
}
?>

<section class="stats">
    <div class="container-wide">
        <div class="stats-grid">
            <div class="stat-item">
                <?php
                // Buscar número real de vagas ativas
                try {
                    $total_vagas = $db->fetchColumn("SELECT COUNT(*) FROM vagas WHERE status = 'ativa'");
                    // Verificar se o valor retornado é válido
                    if (is_numeric($total_vagas)) {
                        echo '<p class="stat-number">' . number_format($total_vagas, 0, ',', '.') . '+</p>';
                    } else {
                        echo '<p class="stat-number">0+</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="stat-number">15.000+</p>';
                }
                ?>
                <p class="stat-label">Vagas ativas</p>
            </div>
            
            <div class="stat-item">
                <?php
                // Buscar número real de empresas cadastradas
                try {
                    $total_empresas = $db->fetchColumn("SELECT COUNT(*) FROM empresas");
                    // Verificar se o valor retornado é válido
                    if (is_numeric($total_empresas)) {
                        echo '<p class="stat-number">' . number_format($total_empresas, 0, ',', '.') . '+</p>';
                    } else {
                        echo '<p class="stat-number">0+</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="stat-number">8.500+</p>';
                }
                ?>
                <p class="stat-label">Empresas cadastradas</p>
            </div>
            
            <div class="stat-item">
                <?php
                // Buscar número real de talentos cadastrados
                try {
                    $total_talentos = $db->fetchColumn("SELECT COUNT(*) FROM talentos");
                    // Verificar se o valor retornado é válido
                    if (is_numeric($total_talentos)) {
                        echo '<p class="stat-number">' . number_format($total_talentos, 0, ',', '.') . '+</p>';
                    } else {
                        echo '<p class="stat-number">0+</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="stat-number">120.000+</p>';
                }
                ?>
                <p class="stat-label">Talentos cadastrados</p>
            </div>
            
            <div class="stat-item">
                <?php
                // Buscar número real de demandas cadastradas
                try {
                    // Verificar se a tabela existe antes de consultar
                    $tabela_existe = $db->fetchColumn("SELECT COUNT(*) 
                        FROM information_schema.tables 
                        WHERE table_schema = '" . DB_NAME . "' 
                        AND table_name = 'demandas_talentos'");
                    
                    if ($tabela_existe > 0) {
                        $total_demandas = $db->fetchColumn("SELECT COUNT(*) FROM demandas_talentos");
                    } else {
                        $total_demandas = 0;
                    }
                    
                    // Verificar se o valor retornado é válido
                    if (is_numeric($total_demandas)) {
                        echo '<p class="stat-number">' . number_format($total_demandas, 0, ',', '.') . '+</p>';
                    } else {
                        echo '<p class="stat-number">0+</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="stat-number">45.000+</p>';
                }
                ?>
                <p class="stat-label">Procura-se cadastrados</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container-wide">
        <h2 class="cta-title">Pronto para dar o próximo passo na sua carreira?</h2>
        <p class="cta-subtitle">Onde talentos disponíveis encontram oportunidades imediatas</p>
        
        <div class="cta-buttons">
            <a href="<?php echo SITE_URL; ?>/?route=cadastro_talento" class="btn btn-accent">Destaque-se como profissional #OpenToJob</a>
            <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="btn btn-outline">Encontre seu próximo talento hoje</a>
        </div>
    </div>
</section>
