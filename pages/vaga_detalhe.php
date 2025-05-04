<?php
// Obter slug da vaga (se disponível)
$vaga_slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Obter ID da vaga (para compatibilidade com URLs antigas)
$vaga_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se temos slug ou ID
if (!empty($vaga_slug)) {
    // Buscar vaga pelo slug
    try {
        $vaga = $db->fetch("
            SELECT v.*, e.razao_social as empresa_nome, u.nome as empresa_usuario_nome, 
                   e.logo as empresa_logo, e.descricao as empresa_descricao,
                   e.tamanho as empresa_tamanho, u.website as empresa_website,
                   tc.nome as tipo_contrato_nome,
                   rt.nome as regime_trabalho_nome,
                   ne.nome as nivel_experiencia_nome
            FROM vagas v
            LEFT JOIN usuarios u ON v.empresa_id = u.id
            LEFT JOIN empresas e ON u.id = e.usuario_id
            LEFT JOIN tipos_contrato tc ON v.tipo_contrato_id = tc.id
            LEFT JOIN regimes_trabalho rt ON v.regime_trabalho_id = rt.id
            LEFT JOIN niveis_experiencia ne ON v.nivel_experiencia_id = ne.id
            WHERE v.slug = :slug AND v.status = 'aberta'
        ", ['slug' => $vaga_slug]);
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Erro ao buscar vaga: ' . $e->getMessage() . '</div>';
        exit;
    }
} elseif ($vaga_id > 0) {
    // Buscar vaga pelo ID (compatibilidade com URLs antigas)
    try {
        $vaga = $db->fetch("
            SELECT v.*, e.razao_social as empresa_nome, u.nome as empresa_usuario_nome, 
                   e.logo as empresa_logo, e.descricao as empresa_descricao,
                   e.tamanho as empresa_tamanho, u.website as empresa_website,
                   tc.nome as tipo_contrato_nome,
                   rt.nome as regime_trabalho_nome,
                   ne.nome as nivel_experiencia_nome
            FROM vagas v
            LEFT JOIN usuarios u ON v.empresa_id = u.id
            LEFT JOIN empresas e ON u.id = e.usuario_id
            LEFT JOIN tipos_contrato tc ON v.tipo_contrato_id = tc.id
            LEFT JOIN regimes_trabalho rt ON v.regime_trabalho_id = rt.id
            LEFT JOIN niveis_experiencia ne ON v.nivel_experiencia_id = ne.id
            WHERE v.id = :id AND v.status = 'aberta'
        ", ['id' => $vaga_id]);
        
        // Não redirecionamos aqui para evitar o erro "headers already sent"
        // O redirecionamento deve ser feito no index.php antes de incluir os templates
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Erro ao buscar vaga: ' . $e->getMessage() . '</div>';
        exit;
    }
} else {
    // Nem slug nem ID fornecidos
    echo '<div class="alert alert-danger">Vaga não encontrada.</div>';
    exit;
}

// Verificar se a vaga foi encontrada
if (!$vaga) {
    echo '<div class="alert alert-danger">Vaga não encontrada ou não está ativa.</div>';
    exit;
}

// Verificar se é uma vaga externa e se o usuário está logado
$is_logged_in = Auth::isLoggedIn();
$is_talento = $is_logged_in && Auth::checkUserType('talento');

// Verificar se o recrutamento interno está habilitado
try {
    $recrutamento_interno_habilitado = $db->fetch("
        SELECT valor FROM configuracoes WHERE chave = 'recrutamento_interno_habilitado'
    ");
    $recrutamento_interno_habilitado = $recrutamento_interno_habilitado['valor'] ?? '1';
} catch (PDOException $e) {
    error_log("Erro ao verificar configuração de recrutamento interno: " . $e->getMessage());
    $recrutamento_interno_habilitado = '1'; // Valor padrão caso ocorra erro
}

// Se for uma vaga externa e o recrutamento interno estiver desabilitado, redirecionar
if ($vaga['tipo_vaga'] === 'interna' && $recrutamento_interno_habilitado === '0') {
    echo '<div class="alert alert-danger">O recrutamento interno está temporariamente desabilitado.</div>';
    exit;
}

// Incrementar visualizações
$db->query("
    UPDATE vagas SET visualizacoes = visualizacoes + 1 WHERE id = :id
", ['id' => $vaga['id']]);

// Formatar data de publicação
$data_publicacao = !empty($vaga['data_publicacao']) ? date('d/m/Y', strtotime($vaga['data_publicacao'])) : 'Não informada';

// Formatar data de expiração
$data_expiracao = !empty($vaga['data_expiracao']) ? date('d/m/Y', strtotime($vaga['data_expiracao'])) : 'Não informada';

// Formatar salário
$salario_formatado = '';
if ($vaga['mostrar_salario']) {
    if (!empty($vaga['salario_min']) && !empty($vaga['salario_max'])) {
        $salario_formatado = 'R$ ' . number_format($vaga['salario_min'], 2, ',', '.') . ' - R$ ' . number_format($vaga['salario_max'], 2, ',', '.');
    } elseif (!empty($vaga['salario_min'])) {
        $salario_formatado = 'A partir de R$ ' . number_format($vaga['salario_min'], 2, ',', '.');
    } elseif (!empty($vaga['salario_max'])) {
        $salario_formatado = 'Até R$ ' . number_format($vaga['salario_max'], 2, ',', '.');
    }
} else {
    $salario_formatado = 'Não informado';
}

// Nome da empresa
$empresa_nome = $vaga['empresa_nome'] ?: $vaga['empresa_usuario_nome'] ?: 'Empresa';

// Logo da empresa (usar placeholder se não existir)
$empresa_logo = !empty($vaga['empresa_logo']) ? SITE_URL . '/uploads/empresas/' . $vaga['empresa_logo'] : SITE_URL . '/assets/img/placeholder-company.png';

// Converter requisitos, responsabilidades e benefícios em arrays
$requisitos = !empty($vaga['requisitos']) ? explode("\n", $vaga['requisitos']) : [];
$responsabilidades = !empty($vaga['responsabilidades']) ? explode("\n", $vaga['responsabilidades']) : [];
$beneficios = !empty($vaga['beneficios']) ? explode("\n", $vaga['beneficios']) : [];

// Converter tags em array (se existirem)
$tags = [];
if (!empty($vaga['tags'])) {
    $tags = explode(',', $vaga['tags']);
    $tags = array_map('trim', $tags);
}

// Verificar se o usuário já se candidatou a esta vaga
$ja_candidatou = false;
if ($is_logged_in && $is_talento) {
    try {
        $candidatura = $db->fetch("
            SELECT id FROM candidaturas 
            WHERE vaga_id = :vaga_id AND talento_id = :talento_id
        ", [
            'vaga_id' => $vaga['id'],
            'talento_id' => $_SESSION['user_id']
        ]);
        
        $ja_candidatou = !empty($candidatura);
    } catch (PDOException $e) {
        error_log("Erro ao verificar candidatura: " . $e->getMessage());
    }
}

?>

<div class="jobs-header">
    <div class="container">
        <h1 class="jobs-title"><?php echo htmlspecialchars($vaga['titulo']); ?></h1>
        <p class="jobs-subtitle">Vaga publicada em <?php echo $data_publicacao; ?></p>
    </div>
</div>

<div class="container job-detail-container">
    <div class="job-detail-main">
        <div class="job-detail-header">
            <img src="<?php echo $empresa_logo; ?>" alt="<?php echo htmlspecialchars($empresa_nome); ?>" class="job-detail-logo">
            <div>
                <h2 class="job-detail-company"><?php echo htmlspecialchars($empresa_nome); ?></h2>
                <p class="job-detail-location"><?php echo htmlspecialchars($vaga['cidade'] . ', ' . $vaga['estado']); ?></p>
            </div>
        </div>
        
        <div class="job-meta">
            <div class="job-meta-item">
                <i class="fas fa-briefcase"></i>
                <span><?php echo htmlspecialchars(isset($vaga['tipo_contrato_nome']) && !empty($vaga['tipo_contrato_nome']) ? $vaga['tipo_contrato_nome'] : (isset($vaga['tipo_contrato']) && !empty($vaga['tipo_contrato']) ? $vaga['tipo_contrato'] : 'Tipo não informado')); ?></span>
            </div>
            <div class="job-meta-item">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo htmlspecialchars(isset($vaga['regime_trabalho_nome']) && !empty($vaga['regime_trabalho_nome']) ? $vaga['regime_trabalho_nome'] : (isset($vaga['regime_trabalho']) && !empty($vaga['regime_trabalho']) ? $vaga['regime_trabalho'] : 'Regime não informado')); ?></span>
            </div>
            <div class="job-meta-item">
                <i class="fas fa-user-graduate"></i>
                <span><?php echo htmlspecialchars(isset($vaga['nivel_experiencia_nome']) && !empty($vaga['nivel_experiencia_nome']) ? $vaga['nivel_experiencia_nome'] : (isset($vaga['nivel_experiencia']) && !empty($vaga['nivel_experiencia']) ? $vaga['nivel_experiencia'] : 'Nível não informado')); ?></span>
            </div>
            <div class="job-meta-item">
                <i class="fas fa-money-bill-wave"></i>
                <span><?php echo $salario_formatado; ?></span>
            </div>
            <?php if ($vaga['tipo_vaga'] === 'externa'): ?>
            <div class="job-meta-item">
                <i class="fas fa-external-link-alt"></i>
                <span>Vaga Externa</span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="job-detail-section">
            <h2 class="job-detail-section-title">Descrição da vaga</h2>
            <div class="job-detail-description">
                <p><?php echo nl2br(htmlspecialchars($vaga['descricao'])); ?></p>
            </div>
        </div>
        
        <?php if (!empty($responsabilidades)): ?>
        <div class="job-detail-section">
            <h2 class="job-detail-section-title">Responsabilidades</h2>
            <ul class="job-detail-list">
                <?php foreach ($responsabilidades as $responsabilidade): ?>
                    <?php if (!empty(trim($responsabilidade))): ?>
                        <li><?php echo htmlspecialchars($responsabilidade); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($requisitos)): ?>
        <div class="job-detail-section">
            <h2 class="job-detail-section-title">Requisitos</h2>
            <ul class="job-detail-list">
                <?php foreach ($requisitos as $requisito): ?>
                    <?php if (!empty(trim($requisito))): ?>
                        <li><?php echo htmlspecialchars($requisito); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($beneficios)): ?>
        <div class="job-detail-section">
            <h2 class="job-detail-section-title">Benefícios</h2>
            <ul class="job-detail-list">
                <?php foreach ($beneficios as $beneficio): ?>
                    <?php if (!empty(trim($beneficio))): ?>
                        <li><?php echo htmlspecialchars($beneficio); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($tags)): ?>
        <div class="job-detail-section">
            <h2 class="job-detail-section-title">Tags</h2>
            <div class="job-tags">
                <?php foreach ($tags as $tag): ?>
                    <?php if (!empty(trim($tag))): ?>
                        <span class="job-tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="job-detail-sidebar">
        <div class="job-apply-widget">
            <h3 class="job-apply-title">Interessado na vaga?</h3>
            <div class="job-apply-deadline">
                <i class="fas fa-clock"></i>
                <span>Prazo: <?php echo $data_expiracao; ?></span>
            </div>
            
            <?php if ($vaga['tipo_vaga'] === 'externa'): ?>
                <!-- Botão para vaga externa -->
                <a href="<?php echo htmlspecialchars($vaga['url_externa']); ?>" target="_blank" class="btn btn-success job-apply-btn">
                    <i class="fas fa-external-link-alt me-2"></i> Candidatar-se no site externo
                </a>
                <?php if ($is_logged_in && $is_talento): ?>
                    <button class="job-save-btn">
                        <i class="far fa-bookmark"></i> Salvar vaga
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <!-- Botão para vaga interna -->
                <?php if ($is_logged_in && $is_talento): ?>
                    <?php if ($ja_candidatou): ?>
                        <button class="btn btn-secondary job-apply-btn" disabled>
                            <i class="fas fa-check me-2"></i> Você já se candidatou
                        </button>
                    <?php else: ?>
                        <a href="<?php echo url('candidatar', ['id' => $vaga['id']]); ?>" class="btn btn-primary job-apply-btn">Candidatar-se</a>
                    <?php endif; ?>
                    <button class="job-save-btn">
                        <i class="far fa-bookmark"></i> Salvar vaga
                    </button>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/?route=entrar" class="btn btn-accent job-apply-btn">Entrar para se candidatar</a>
                    <a href="<?php echo SITE_URL; ?>/?route=cadastro_talento" class="job-save-btn">Criar conta</a>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="job-share-options">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(url('vaga', ['slug' => $vaga['slug']])); ?>" target="_blank" class="job-share-btn share-facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(url('vaga', ['slug' => $vaga['slug']])); ?>&text=<?php echo urlencode('Vaga: ' . $vaga['titulo']); ?>" target="_blank" class="job-share-btn share-twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(url('vaga', ['slug' => $vaga['slug']])); ?>" target="_blank" class="job-share-btn share-linkedin">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Vaga: ' . $vaga['titulo'] . ' - ' . url('vaga', ['slug' => $vaga['slug']])); ?>" target="_blank" class="job-share-btn share-whatsapp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            </div>
        </div>
        
        <div class="company-widget">
            <h3 class="company-widget-title">Sobre a empresa</h3>
            <div class="company-widget-info">
                <img src="<?php echo $empresa_logo; ?>" alt="<?php echo htmlspecialchars($empresa_nome); ?>" class="company-widget-logo">
                <div>
                    <p class="company-widget-name"><?php echo htmlspecialchars($empresa_nome); ?></p>
                    <p class="company-widget-location"><?php echo htmlspecialchars($vaga['cidade'] . ', ' . $vaga['estado']); ?></p>
                </div>
            </div>
            
            <?php if (!empty($vaga['empresa_descricao'])): ?>
            <p class="company-widget-description"><?php echo htmlspecialchars($vaga['empresa_descricao']); ?></p>
            <?php endif; ?>
            
            <div class="company-widget-stats">
                <?php if (!empty($vaga['empresa_tamanho'])): ?>
                <div class="company-stat-item">
                    <p class="company-stat-value"><?php echo htmlspecialchars($vaga['empresa_tamanho']); ?></p>
                    <p class="company-stat-label">Tamanho</p>
                </div>
                <?php endif; ?>
                
                <?php 
                // Contar vagas ativas da empresa
                try {
                    $vagas_ativas = $db->fetch("
                        SELECT COUNT(*) as total FROM vagas 
                        WHERE empresa_id = :empresa_id AND status = 'ativa'
                    ", ['empresa_id' => $vaga['empresa_id']]);
                    
                    $total_vagas = $vagas_ativas['total'] ?? 0;
                } catch (PDOException $e) {
                    error_log("Erro ao contar vagas ativas: " . $e->getMessage());
                    $total_vagas = 0;
                }
                ?>
                
                <div class="company-stat-item">
                    <p class="company-stat-value"><?php echo $total_vagas; ?></p>
                    <p class="company-stat-label">Vagas ativas</p>
                </div>
            </div>
            
            <?php if (!empty($vaga['empresa_website'])): ?>
            <a href="<?php echo htmlspecialchars($vaga['empresa_website']); ?>" target="_blank" class="company-widget-link">
                <i class="fas fa-globe"></i> Visitar site
            </a>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-widget">
            <h3 class="widget-title">Vagas semelhantes</h3>
            
            <?php
            // Buscar vagas semelhantes
            try {
                $vagas_semelhantes = $db->fetchAll("
                    SELECT v.id, v.titulo, v.cidade, v.estado, e.razao_social as empresa_nome, u.nome as empresa_usuario_nome
                    FROM vagas v
                    LEFT JOIN usuarios u ON v.empresa_id = u.id
                    LEFT JOIN empresas e ON u.id = e.usuario_id
                    WHERE v.id != :vaga_id 
                      AND v.status = 'ativa'
                      AND (v.nivel_experiencia = :nivel_experiencia OR v.tipo_contrato = :tipo_contrato)
                    LIMIT 3
                ", [
                    'vaga_id' => $vaga['id'],
                    'nivel_experiencia' => $vaga['nivel_experiencia'],
                    'tipo_contrato' => $vaga['tipo_contrato']
                ]);
            } catch (PDOException $e) {
                error_log("Erro ao buscar vagas semelhantes: " . $e->getMessage());
                $vagas_semelhantes = [];
            }
            
            if (empty($vagas_semelhantes)) {
                echo '<p>Nenhuma vaga semelhante encontrada.</p>';
            } else {
                foreach ($vagas_semelhantes as $vaga_semelhante):
                    $empresa_semelhante = $vaga_semelhante['empresa_nome'] ?: $vaga_semelhante['empresa_usuario_nome'] ?: 'Empresa';
            ?>
                <div class="job-item" style="padding: 15px; margin-bottom: 15px;">
                    <h4 style="font-size: 1rem; margin-bottom: 10px;">
                        <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $vaga_semelhante['id']; ?>"><?php echo htmlspecialchars($vaga_semelhante['titulo']); ?></a>
                    </h4>
                    <div style="display: flex; gap: 10px; font-size: 0.8rem; color: #6c757d; margin-bottom: 5px;">
                        <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($empresa_semelhante); ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vaga_semelhante['cidade'] . ', ' . $vaga_semelhante['estado']); ?></span>
                    </div>
                </div>
            <?php
                endforeach;
            }
            ?>
        </div>
    </div>
</div>
