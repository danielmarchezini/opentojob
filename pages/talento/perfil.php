<?php
// Verificar se o usuário está logado e é um talento
if (!Auth::checkUserType('talento') && !Auth::checkUserType('admin')) {
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter detalhes do talento
try {
    $talento = $db->fetchRow("
        SELECT u.nome, u.email, u.status, t.*
        FROM usuarios u
        LEFT JOIN talentos t ON u.id = t.usuario_id
        WHERE u.id = :id
    ", ['id' => $usuario_id]);
    
    if (!$talento) {
        $_SESSION['flash_message'] = "Perfil não encontrado.";
        $_SESSION['flash_type'] = "danger";
        echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_talento';</script>";
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar detalhes do talento: " . $e->getMessage());
    $_SESSION['flash_message'] = "Erro ao carregar perfil: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_talento';</script>";
    exit;
}

// Obter avaliações do talento
try {
    $avaliacoes = $db->fetchAll("
        SELECT a.*, u.nome as avaliador_nome, e.razao_social
        FROM avaliacoes a
        JOIN usuarios u ON a.avaliador_id = u.id
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE a.talento_id = :talento_id AND a.status = 'aprovada'
        ORDER BY a.data_avaliacao DESC
    ", ['talento_id' => $usuario_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar avaliações: " . $e->getMessage());
    $avaliacoes = [];
}

// Calcular média das avaliações
$media_avaliacoes = 0;
$total_avaliacoes = count($avaliacoes);

if ($total_avaliacoes > 0) {
    $soma_avaliacoes = 0;
    foreach ($avaliacoes as $avaliacao) {
        $soma_avaliacoes += $avaliacao['nota'];
    }
    $media_avaliacoes = round($soma_avaliacoes / $total_avaliacoes, 1);
}

// Obter histórico de candidaturas
try {
    $candidaturas = $db->fetchAll("
        SELECT c.*, v.titulo, v.cidade, v.estado, v.tipo_contrato, v.regime_trabalho,
               u.nome as empresa_nome, e.razao_social
        FROM candidaturas c
        JOIN vagas v ON c.vaga_id = v.id
        JOIN usuarios u ON v.empresa_id = u.id
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE c.talento_id = :talento_id
        ORDER BY c.data_candidatura DESC
    ", ['talento_id' => $usuario_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar candidaturas: " . $e->getMessage());
    $candidaturas = [];
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

// Função para obter badge de status
function getBadgeStatus($status) {
    switch ($status) {
        case 'enviada':
            return '<span class="badge bg-primary">Enviada</span>';
        case 'visualizada':
            return '<span class="badge bg-info">Visualizada</span>';
        case 'em_analise':
            return '<span class="badge bg-warning">Em análise</span>';
        case 'aprovada':
            return '<span class="badge bg-success">Aprovada</span>';
        case 'rejeitada':
            return '<span class="badge bg-danger">Rejeitada</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

// Função para exibir estrelas de avaliação
function exibirEstrelas($nota) {
    $html = '';
    $nota_inteira = floor($nota);
    $tem_meia = ($nota - $nota_inteira) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $nota_inteira) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i == $nota_inteira + 1 && $tem_meia) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    
    return $html . ' <span class="text-muted">(' . $nota . '/5)</span>';
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Meu Perfil</h5>
                        <div>
                            <a href="<?php echo SITE_URL; ?>/?route=perfil_talento_editar" class="btn btn-light btn-sm">
                                <i class="fas fa-edit me-1"></i>Editar Perfil
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="mb-3">
                                <?php if (!empty($talento['foto_perfil'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" 
                                         class="img-fluid rounded-circle" style="width: 180px; height: 180px; object-fit: cover;" 
                                         alt="Foto de perfil">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center" 
                                         style="width: 180px; height: 180px;">
                                        <i class="fas fa-user fa-5x text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4><?php echo htmlspecialchars($talento['nome']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($talento['profissao'] ?? 'Profissão não informada'); ?></p>
                            
                            <?php if ($total_avaliacoes > 0): ?>
                                <div class="mb-3">
                                    <?php echo exibirEstrelas($media_avaliacoes); ?>
                                    <p class="text-muted"><?php echo $total_avaliacoes; ?> avaliação(ões)</p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>/?route=vagas" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-1"></i>Buscar Vagas
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-info-circle me-2"></i>Informações Básicas</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <strong><i class="fas fa-envelope me-2"></i>Email:</strong> 
                                            <?php echo htmlspecialchars($talento['email']); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong><i class="fas fa-map-marker-alt me-2"></i>Localização:</strong> 
                                            <?php echo htmlspecialchars(($talento['cidade'] ?? 'Não informado') . 
                                                (isset($talento['estado']) ? '/' . $talento['estado'] : '')); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong><i class="fas fa-phone me-2"></i>Telefone:</strong> 
                                            <?php echo htmlspecialchars($talento['telefone'] ?? 'Não informado'); ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong><i class="fas fa-birthday-cake me-2"></i>Data de Nascimento:</strong> 
                                            <?php echo isset($talento['data_nascimento']) ? formatarData($talento['data_nascimento']) : 'Não informado'; ?>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-link me-2"></i>Links e Redes Sociais</h5>
                                    <ul class="list-group list-group-flush">
                                        <?php if (!empty($talento['linkedin'])): ?>
                                            <li class="list-group-item">
                                                <i class="fab fa-linkedin me-2 text-primary"></i>
                                                <a href="<?php echo htmlspecialchars($talento['linkedin']); ?>" target="_blank">LinkedIn</a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($talento['github'])): ?>
                                            <li class="list-group-item">
                                                <i class="fab fa-github me-2"></i>
                                                <a href="<?php echo htmlspecialchars($talento['github']); ?>" target="_blank">GitHub</a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($talento['portfolio'])): ?>
                                            <li class="list-group-item">
                                                <i class="fas fa-briefcase me-2 text-success"></i>
                                                <a href="<?php echo htmlspecialchars($talento['portfolio']); ?>" target="_blank">Portfólio</a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($talento['website'])): ?>
                                            <li class="list-group-item">
                                                <i class="fas fa-globe me-2 text-info"></i>
                                                <a href="<?php echo htmlspecialchars($talento['website']); ?>" target="_blank">Website</a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (empty($talento['linkedin']) && empty($talento['github']) && 
                                                  empty($talento['portfolio']) && empty($talento['website'])): ?>
                                            <li class="list-group-item text-muted">Nenhum link informado</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="fas fa-user-tie me-2"></i>Resumo Profissional</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <?php if (!empty($talento['resumo'])): ?>
                                            <p><?php echo nl2br(htmlspecialchars($talento['resumo'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted">Resumo não informado</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="fas fa-tags me-2"></i>Habilidades</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <?php if (!empty($talento['habilidades'])): ?>
                                            <?php 
                                            $habilidades = explode(',', $talento['habilidades']);
                                            foreach ($habilidades as $habilidade): 
                                                $habilidade = trim($habilidade);
                                                if (!empty($habilidade)):
                                            ?>
                                                <span class="badge bg-primary me-2 mb-2 p-2"><?php echo htmlspecialchars($habilidade); ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        <?php else: ?>
                                            <p class="text-muted">Nenhuma habilidade informada</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Experiência Profissional -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Experiência Profissional</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($talento['experiencia'])): ?>
                        <div class="timeline">
                            <?php echo nl2br(htmlspecialchars($talento['experiencia'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma experiência profissional informada</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Formação Acadêmica -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Formação Acadêmica</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($talento['formacao'])): ?>
                        <div class="timeline">
                            <?php echo nl2br(htmlspecialchars($talento['formacao'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma formação acadêmica informada</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Candidaturas -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Minhas Candidaturas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($candidaturas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Você ainda não se candidatou a nenhuma vaga.
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=vagas" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Explorar vagas
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php 
                            $contador = 0;
                            foreach ($candidaturas as $candidatura): 
                                if ($contador >= 5) break; // Limitar a 5 candidaturas
                                $contador++;
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($candidatura['titulo']); ?></h6>
                                        <small><?php echo getBadgeStatus($candidatura['status']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Empresa:</strong> <?php echo htmlspecialchars($candidatura['razao_social'] ?: $candidatura['empresa_nome']); ?>
                                    </p>
                                    <small>
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($candidatura['cidade'] . '/' . $candidatura['estado']); ?> |
                                        <i class="fas fa-calendar-alt me-1"></i><?php echo formatarData($candidatura['data_candidatura']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($candidaturas) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="<?php echo SITE_URL; ?>/?route=minhas_candidaturas" class="btn btn-outline-primary">
                                    Ver todas as candidaturas
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Avaliações -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Avaliações Recebidas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($avaliacoes)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Você ainda não recebeu avaliações.
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <h4 class="me-2 mb-0"><?php echo $media_avaliacoes; ?></h4>
                                <div>
                                    <?php echo exibirEstrelas($media_avaliacoes); ?>
                                    <div class="text-muted"><?php echo $total_avaliacoes; ?> avaliação(ões)</div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="list-group">
                            <?php 
                            $contador = 0;
                            foreach ($avaliacoes as $avaliacao): 
                                if ($contador >= 3) break; // Limitar a 3 avaliações
                                $contador++;
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($avaliacao['titulo']); ?></h6>
                                        <small><?php echo formatarData($avaliacao['data_avaliacao']); ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $avaliacao['nota']): ?>
                                                <i class="fas fa-star text-warning"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-warning"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($avaliacao['comentario'])); ?></p>
                                    <small>
                                        <strong>Avaliador:</strong> <?php echo htmlspecialchars($avaliacao['razao_social'] ?: $avaliacao['avaliador_nome']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
