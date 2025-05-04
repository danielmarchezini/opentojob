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

// Verificar se o sistema de vagas internas está ativo
$sistema_vagas_internas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_vagas_internas_ativo'");

// Verificar se o sistema de demandas de talentos está ativo
$sistema_demandas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_demandas_talentos_ativo'");

// Obter detalhes do talento
try {
    $talento = $db->fetchRow("
        SELECT u.nome, u.email, t.*
        FROM usuarios u
        LEFT JOIN talentos t ON u.id = t.usuario_id
        WHERE u.id = :id
    ", ['id' => $usuario_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar detalhes do talento: " . $e->getMessage());
    $talento = [];
}

// Obter candidaturas recentes (apenas se o sistema de vagas internas estiver ativo)
$candidaturas = [];
if ($sistema_vagas_internas_ativo) {
    try {
        $candidaturas = $db->fetchAll("
            SELECT c.*, v.titulo, v.cidade, v.estado, v.tipo_contrato, v.regime_trabalho, v.nivel_experiencia,
                   e.razao_social, u.nome as empresa_nome
            FROM candidaturas c
            JOIN vagas v ON c.vaga_id = v.id
            JOIN usuarios u ON v.empresa_id = u.id
            LEFT JOIN empresas e ON u.id = e.usuario_id
            WHERE c.talento_id = :talento_id
            ORDER BY c.data_candidatura DESC
            LIMIT 5
        ", ['talento_id' => $usuario_id]);
    } catch (PDOException $e) {
        error_log("Erro ao buscar candidaturas: " . $e->getMessage());
        $candidaturas = [];
    }
}

// Obter demandas com interesse demonstrado
$demandas_interesse = [];
if ($sistema_demandas_ativo) {
    try {
        // Primeiro, obter o ID do talento
        $talento_id = $db->fetchColumn("SELECT id FROM talentos WHERE usuario_id = ?", [$usuario_id]);
        
        if ($talento_id) {
            $demandas_interesse = $db->fetchAll("
                SELECT di.*, dt.titulo, dt.descricao, dt.data_publicacao, dt.prazo_contratacao,
                       e.razao_social as empresa_nome, u.nome as contato_nome
                FROM demandas_interessados di
                JOIN demandas_talentos dt ON di.demanda_id = dt.id
                JOIN empresas e ON dt.empresa_id = e.id
                JOIN usuarios u ON e.usuario_id = u.id
                WHERE di.talento_id = :talento_id
                ORDER BY di.data_interesse DESC
                LIMIT 5
            ", ['talento_id' => $talento_id]);
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar demandas com interesse: " . $e->getMessage());
        $demandas_interesse = [];
    }
}

// Obter vagas recomendadas
try {
    // Buscar áreas de interesse e habilidades do talento para recomendação
    $areas_interesse = $talento['areas_interesse'] ?? '';
    $habilidades = $talento['habilidades'] ?? '';
    
    // Criar array de termos para busca
    $termos_busca = [];
    if (!empty($areas_interesse)) {
        $areas = explode(',', $areas_interesse);
        foreach ($areas as $area) {
            $termos_busca[] = trim($area);
        }
    }
    if (!empty($habilidades)) {
        $skills = explode(',', $habilidades);
        foreach ($skills as $skill) {
            $termos_busca[] = trim($skill);
        }
    }
    
    // Adicionar profissão como termo de busca
    if (!empty($talento['profissao'])) {
        $termos_busca[] = $talento['profissao'];
    }
    
    // Construir condição SQL para busca de vagas relacionadas
    $condicao_busca = '';
    $params = [];
    
    if (!empty($termos_busca)) {
        $condicoes = [];
        $i = 0;
        foreach ($termos_busca as $termo) {
            if (!empty($termo)) {
                $param_name = ':termo' . $i;
                $condicoes[] = "(v.titulo LIKE $param_name OR v.descricao LIKE $param_name OR v.requisitos LIKE $param_name)";
                $params[$param_name] = '%' . $termo . '%';
                $i++;
            }
        }
        
        if (!empty($condicoes)) {
            $condicao_busca = 'AND (' . implode(' OR ', $condicoes) . ')';
        }
    }
    
    // Buscar vagas recomendadas
    $vagas_recomendadas = $db->fetchAll("
        SELECT v.*, u.nome as empresa_nome, e.razao_social
        FROM vagas v
        JOIN usuarios u ON v.empresa_id = u.id
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE v.status = 'aberta' $condicao_busca
        ORDER BY v.data_publicacao DESC
        LIMIT 5
    ", $params);
} catch (PDOException $e) {
    error_log("Erro ao buscar vagas recomendadas: " . $e->getMessage());
    $vagas_recomendadas = [];
}

// Obter mensagens recentes
try {
    $mensagens = $db->fetchAll("
        SELECT m.*, u.nome as remetente_nome
        FROM mensagens m
        JOIN usuarios u ON m.remetente_id = u.id
        WHERE m.destinatario_id = :usuario_id
        ORDER BY m.data_envio DESC
        LIMIT 5
    ", ['usuario_id' => $usuario_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar mensagens: " . $e->getMessage());
    $mensagens = [];
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
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
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Bem-vindo(a), <?php echo htmlspecialchars($talento['nome'] ?? 'Talento'); ?>!</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p>Este é o seu painel de controle onde você pode gerenciar seu perfil, candidaturas e mensagens.</p>
                            <div class="d-grid gap-2 d-md-flex">
                                <a href="<?php echo SITE_URL; ?>/?route=perfil_talento" class="btn btn-outline-primary">
                                    <i class="fas fa-user me-2"></i>Ver meu perfil
                                </a>
                                <a href="<?php echo SITE_URL; ?>/?route=perfil_talento_editar" class="btn btn-outline-secondary">
                                    <i class="fas fa-edit me-2"></i>Editar perfil
                                </a>
                                <a href="<?php echo SITE_URL; ?>/?route=minhas_candidaturas" class="btn btn-outline-success">
                                    <i class="fas fa-briefcase me-2"></i>Minhas candidaturas
                                </a>
                                <a href="<?php echo SITE_URL; ?>/?route=mensagens_talento" class="btn btn-outline-info">
                                    <i class="fas fa-envelope me-2"></i>Mensagens
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6>Completude do perfil</h6>
                                    <?php
                                    // Calcular completude do perfil
                                    $campos_obrigatorios = ['nome', 'email', 'profissao', 'resumo', 'experiencia', 'formacao', 'habilidades'];
                                    $campos_preenchidos = 0;
                                    
                                    foreach ($campos_obrigatorios as $campo) {
                                        if (!empty($talento[$campo])) {
                                            $campos_preenchidos++;
                                        }
                                    }
                                    
                                    $porcentagem = round(($campos_preenchidos / count($campos_obrigatorios)) * 100);
                                    $cor_barra = $porcentagem < 50 ? 'danger' : ($porcentagem < 80 ? 'warning' : 'success');
                                    ?>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?php echo $cor_barra; ?>" role="progressbar" style="width: <?php echo $porcentagem; ?>%" 
                                             aria-valuenow="<?php echo $porcentagem; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <p class="mb-0"><strong><?php echo $porcentagem; ?>%</strong> completo</p>
                                    <?php if ($porcentagem < 100): ?>
                                        <a href="<?php echo SITE_URL; ?>/?route=perfil_talento_editar" class="btn btn-sm btn-primary mt-2">
                                            Completar perfil
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <?php if ($sistema_vagas_internas_ativo): ?>
        <!-- Candidaturas Recentes -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Candidaturas Recentes</h5>
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
                            <?php foreach ($candidaturas as $candidatura): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($candidatura['titulo']); ?></h6>
                                        <small><?php echo getBadgeStatus($candidatura['status']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Empresa:</strong> <?php echo htmlspecialchars($candidatura['razao_social'] ?: $candidatura['empresa_nome']); ?>
                                    </p>
                                    <small>
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($candidatura['cidade'] . '/' . $candidatura['estado']); ?> |
                                        <i class="fas fa-briefcase me-1"></i><?php echo htmlspecialchars($candidatura['tipo_contrato']); ?> |
                                        <i class="fas fa-calendar-alt me-1"></i><?php echo formatarData($candidatura['data_candidatura']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=minhas_candidaturas" class="btn btn-outline-success">
                                Ver todas as candidaturas
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($sistema_demandas_ativo): ?>
        <!-- Anúncios de Procura com Interesse -->
        <div class="col-md-<?php echo $sistema_vagas_internas_ativo ? '6' : '12'; ?> mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Anúncios de Procura com Interesse</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($demandas_interesse)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Você ainda não demonstrou interesse em nenhuma demanda.
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=demandas" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Explorar anúncios de procura
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($demandas_interesse as $demanda): ?>
                                <a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda['demanda_id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($demanda['titulo']); ?></h6>
                                        <small><?php echo getBadgeStatus($demanda['status']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Empresa:</strong> <?php echo htmlspecialchars($demanda['empresa_nome']); ?>
                                    </p>
                                    <small>
                                        <i class="fas fa-calendar-alt me-1"></i>Interesse demonstrado em: <?php echo formatarData($demanda['data_interesse']); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=demandas" class="btn btn-outline-primary">
                                Ver todos os anúncios de procura
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$sistema_vagas_internas_ativo && !$sistema_demandas_ativo): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Informação</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Os sistemas de vagas internas e anúncios de procura estão temporariamente desativados. Entre em contato com o administrador para mais informações.
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Vagas Recomendadas -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Vagas Recomendadas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($vagas_recomendadas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Não há vagas recomendadas no momento.
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=perfil_talento_editar" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Atualizar áreas de interesse
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($vagas_recomendadas as $vaga): ?>
                                <a href="<?php echo SITE_URL; ?>/?route=vaga_detalhe&id=<?php echo $vaga['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($vaga['titulo']); ?></h6>
                                        <small><?php echo formatarData($vaga['data_publicacao']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Empresa:</strong> <?php echo htmlspecialchars($vaga['razao_social'] ?: $vaga['empresa_nome']); ?>
                                    </p>
                                    <small>
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($vaga['cidade'] . '/' . $vaga['estado']); ?> |
                                        <i class="fas fa-briefcase me-1"></i><?php echo htmlspecialchars($vaga['tipo_contrato']); ?> |
                                        <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($vaga['modelo_trabalho']); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=vagas" class="btn btn-outline-info">
                                Ver todas as vagas
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Mensagens Recentes -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Mensagens Recentes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($mensagens)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Você não possui mensagens recentes.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($mensagens as $mensagem): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($mensagem['assunto']); ?></h6>
                                        <small><?php echo formatarData($mensagem['data_envio']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong>De:</strong> <?php echo htmlspecialchars($mensagem['remetente_nome']); ?>
                                    </p>
                                    <small>
                                        <?php echo htmlspecialchars(substr($mensagem['conteudo'], 0, 100)) . (strlen($mensagem['conteudo']) > 100 ? '...' : ''); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=mensagens_talento" class="btn btn-outline-primary">
                                Ver todas as mensagens
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Estatísticas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h2 class="text-primary"><?php echo count($candidaturas); ?></h2>
                                    <p class="mb-0">Candidaturas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h2 class="text-info"><?php echo count($mensagens); ?></h2>
                                    <p class="mb-0">Mensagens</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <a href="<?php echo SITE_URL; ?>/?route=estatisticas_talento" class="btn btn-outline-secondary">
                            <i class="fas fa-chart-bar me-2"></i>Ver estatísticas detalhadas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
