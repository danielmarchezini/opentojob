<?php
// Incluir arquivo para registrar interações
require_once 'includes/registrar_interacao.php';

// Verificar se o ID da empresa foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirecionar para a página de empresas se nenhum ID for fornecido
    echo "<script>window.location.href = '" . SITE_URL . "/?route=empresas';</script>";
    exit;
}

// Obter o ID da empresa
$empresa_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a empresa existe e está ativa
$empresa = $db->fetch("
    SELECT u.id, u.nome, u.email, u.data_cadastro, u.website as site, u.linkedin, 
           e.razao_social, e.segmento, e.descricao
    FROM usuarios u
    JOIN empresas e ON u.id = e.usuario_id
    WHERE u.id = :id AND u.tipo = 'empresa' AND u.status = 'ativo'
", [
    'id' => $empresa_id
]);

// Se a empresa não existir, não estiver ativa ou não tiver perfil público, redirecionar
if (!$empresa) {
    $_SESSION['flash_message'] = "Perfil de empresa não encontrado ou não disponível publicamente.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=empresas';</script>";
    exit;
}

// Registrar visualização do perfil se houver um usuário logado
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $empresa_id) {
    // Registrar a visualização do perfil
    registrarVisualizacaoPerfil($_SESSION['user_id'], $empresa_id);
}

// Verificar se o usuário atual é um talento para mostrar informações adicionais
$is_talento = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'talento';

// Verificar se o sistema de vagas internas está ativo
$sistema_vagas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_vagas_interno_ativo'") ?? '0';

// Obter vagas da empresa se o sistema estiver ativo
$vagas = [];
if ($sistema_vagas_ativo == '1') {
    $vagas = $db->fetchAll("
        SELECT id, titulo, cidade, estado, tipo_contrato, data_publicacao
        FROM vagas
        WHERE empresa_id = :empresa_id AND status = 'aberta'
        ORDER BY data_publicacao DESC
    ", [
        'empresa_id' => $empresa_id
    ]);
}

// Obter demandas (ofertas "Procura-se") da empresa
$demandas = $db->fetchAll("
    SELECT dt.id, dt.titulo, dt.descricao, dt.data_publicacao, dt.modelo_trabalho as tipo_contrato, dt.nivel_experiencia, dt.status
    FROM demandas_talentos dt
    WHERE dt.empresa_id = :empresa_id AND dt.status = 'ativa'
    ORDER BY dt.data_publicacao DESC
", [
    'empresa_id' => $empresa_id
]);

// Verificar se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Perfil da Empresa</h1>
                <p class="lead">Conheça mais sobre esta empresa</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=empresas">Empresas</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-perfil-empresa py-5">
    <div class="container">
        <!-- Cabeçalho do perfil -->
        <div class="card mb-4 border-0 bg-light">
            <div class="card-body py-4">
                <div class="row align-items-center">
                    <div class="col-lg-2 col-md-3 text-center mb-3 mb-md-0">
                        <div class="perfil-logo">
                            <?php if (!empty($empresa['logo'])): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/empresas/<?php echo $empresa['logo']; ?>" alt="<?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?>" class="img-fluid rounded-circle shadow">
                            <?php else: ?>
                                <div class="logo-placeholder rounded-circle shadow bg-primary text-white">
                                    <?php echo strtoupper(substr($empresa['razao_social'] ?: $empresa['nome'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-7 col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="display-6 mb-1"><?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?></h1>
                            <button type="button" class="btn btn-link text-muted" style="font-size: 0.8rem; padding: 0;" data-bs-toggle="modal" data-bs-target="#reportarModal" title="Reportar perfil">
                                <i class="fas fa-flag"></i>
                            </button>
                        </div>
                        <p class="lead text-muted mb-2"><?php echo htmlspecialchars($empresa['segmento'] ?? 'Empresa'); ?></p>
                        <div class="d-flex flex-wrap align-items-center">
                            <span class="badge bg-light text-dark me-2 mb-2">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Membro desde <?php echo date('d/m/Y', strtotime($empresa['data_cadastro'])); ?>
                            </span>
                            
                            <?php if (!empty($empresa['site'])): ?>
                                <a href="<?php echo htmlspecialchars($empresa['site']); ?>" target="_blank" class="badge bg-light text-dark me-2 mb-2">
                                    <i class="fas fa-globe me-1"></i>
                                    <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $empresa['site'])); ?>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Redes sociais -->
                            <?php if (!empty($empresa['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($empresa['linkedin']); ?>" class="badge bg-light text-dark me-2 mb-2" target="_blank" title="LinkedIn">
                                    <i class="fab fa-linkedin me-1"></i> LinkedIn
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($empresa['facebook'])): ?>
                                <a href="<?php echo htmlspecialchars($empresa['facebook']); ?>" class="badge bg-light text-dark me-2 mb-2" target="_blank" title="Facebook">
                                    <i class="fab fa-facebook me-1"></i> Facebook
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($empresa['instagram'])): ?>
                                <a href="<?php echo htmlspecialchars($empresa['instagram']); ?>" class="badge bg-light text-dark me-2 mb-2" target="_blank" title="Instagram">
                                    <i class="fab fa-instagram me-1"></i> Instagram
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 text-md-end mt-3 mt-md-0">
                        <?php if (!$is_logged_in): ?>
                            <a href="<?php echo SITE_URL; ?>/?route=entrar" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Faça login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Coluna da esquerda - Informações principais e contato -->
            <div class="col-lg-4 mb-4">
                <!-- Informações de contato -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-address-card me-2 text-primary"></i>Informações de Contato</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php if (!empty($empresa['email'])): ?>
                                <li class="mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-envelope text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Email</h6>
                                            <a href="mailto:<?php echo htmlspecialchars($empresa['email']); ?>" class="text-muted">
                                                <?php echo htmlspecialchars($empresa['email']); ?>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                            
                            <?php if (!empty($empresa['site'])): ?>
                                <li class="mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-globe text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Website</h6>
                                            <a href="<?php echo htmlspecialchars($empresa['site']); ?>" target="_blank" class="text-muted">
                                                <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $empresa['site'])); ?>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                            
                            <li class="mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Membro desde</h6>
                                        <span class="text-muted"><?php echo date('d/m/Y', strtotime($empresa['data_cadastro'])); ?></span>
                                    </div>
                                </div>
                            </li>
                            
                            <li>
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-tag text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">Segmento</h6>
                                        <span class="text-muted"><?php echo htmlspecialchars($empresa['segmento'] ?? 'Não informado'); ?></span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Informações para talentos logados -->
                <?php if ($is_talento): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações para Talentos</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Como funciona o OpenToJob:</strong>
                            <p class="mt-2 mb-0">No OpenToJob, as empresas iniciam o contato com os talentos que correspondem às suas necessidades. Mantenha seu perfil atualizado para aumentar suas chances de ser contatado!</p>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=demandas&empresa_id=<?php echo $empresa_id; ?>" class="btn btn-outline-accent">
                                <i class="fas fa-search me-2"></i> Ver Ofertas "Procura-se"
                            </a>
                            <?php if ($sistema_vagas_ativo == '1'): ?>
                            <a href="<?php echo SITE_URL; ?>/?route=vagas&empresa_id=<?php echo $empresa_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-briefcase me-2"></i> Ver Vagas Disponíveis
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Coluna da direita - Descrição e vagas -->
            <div class="col-lg-8">
                <!-- Sobre a empresa -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-building me-2 text-primary"></i>Sobre <?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($empresa['descricao'])): ?>
                            <div class="descricao">
                                <?php echo nl2br(htmlspecialchars($empresa['descricao'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border">
                                <i class="fas fa-info-circle me-2"></i>
                                Esta empresa ainda não adicionou uma descrição.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Ofertas "Procura-se" -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-search me-2 text-primary"></i>Ofertas "Procura-se"</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($demandas)): ?>
                            <div class="alert alert-light border">
                                <i class="fas fa-info-circle me-2"></i>
                                Esta empresa não possui ofertas "Procura-se" no momento.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($demandas as $demanda): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda['id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($demanda['titulo']); ?>
                                                    </a>
                                                </h5>
                                                <div class="d-flex flex-wrap mb-2">
                                                    <?php if (!empty($demanda['tipo_contrato'])): ?>
                                                    <span class="badge bg-accent text-white me-2 mb-1"><?php echo htmlspecialchars($demanda['tipo_contrato']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($demanda['nivel_experiencia'])): ?>
                                                    <span class="badge bg-light text-dark mb-1">
                                                        <i class="fas fa-user-graduate me-1"></i>
                                                        <?php echo htmlspecialchars($demanda['nivel_experiencia']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="card-text small text-muted">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    Publicada em <?php echo date('d/m/Y', strtotime($demanda['data_publicacao'])); ?>
                                                </p>
                                            </div>
                                            <div class="card-footer bg-white border-top-0 text-end">
                                                <a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda['id']; ?>" class="btn btn-sm btn-outline-accent">
                                                    Ver detalhes <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="<?php echo SITE_URL; ?>/?route=demandas&empresa_id=<?php echo $empresa_id; ?>" class="btn btn-outline-accent">
                                    <i class="fas fa-list me-2"></i> Ver todas as ofertas desta empresa
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Vagas disponíveis (apenas se o sistema de vagas estiver ativo) -->
                <?php if ($sistema_vagas_ativo == '1'): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>Vagas Disponíveis</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($vagas)): ?>
                            <div class="alert alert-light border">
                                <i class="fas fa-info-circle me-2"></i>
                                Esta empresa não possui vagas abertas no momento.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($vagas as $vaga): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 border">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $vaga['id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($vaga['titulo']); ?>
                                                    </a>
                                                </h5>
                                                <div class="d-flex flex-wrap mb-2">
                                                    <span class="badge bg-primary me-2 mb-1"><?php echo htmlspecialchars($vaga['tipo_contrato']); ?></span>
                                                    <span class="badge bg-light text-dark mb-1">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($vaga['cidade'] . ($vaga['estado'] ? ' - ' . $vaga['estado'] : '')); ?>
                                                    </span>
                                                </div>
                                                <p class="card-text small text-muted">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    Publicada em <?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?>
                                                </p>
                                            </div>
                                            <div class="card-footer bg-white border-top-0 text-end">
                                                <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $vaga['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    Ver detalhes <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="<?php echo SITE_URL; ?>/?route=vagas&empresa_id=<?php echo $empresa_id; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-2"></i> Ver todas as vagas desta empresa
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>Faça parte da revolução #OpenToJob</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border">
                            <i class="fas fa-info-circle me-2"></i>
                            Conheça os talentos disponíveis para contratação imediata.
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=talentos" class="btn btn-primary">
                                <i class="fas fa-users me-2"></i> Encontrar Talentos
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.perfil-card {
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.perfil-logo img {
    max-width: 150px;
    max-height: 150px;
    margin: 0 auto;
    border: 5px solid #f8f9fa;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.logo-placeholder {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    font-weight: bold;
    border: 5px solid #f8f9fa;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.perfil-info {
    text-align: left;
}

.descricao {
    line-height: 1.6;
}

.btn-block {
    display: block;
    width: 100%;
}
</style>

<!-- Modal de Reportar -->
<div class="modal fade" id="reportarModal" tabindex="-1" aria-labelledby="reportarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportarModalLabel">Reportar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/pages/processar_reporte.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="usuario_reportado_id" value="<?php echo $empresa_id; ?>">
                    <input type="hidden" name="tipo_usuario_reportado" value="empresa">
                    
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo do reporte</label>
                        <select class="form-select" id="motivo" name="motivo" required>
                            <option value="">Selecione um motivo</option>
                            <option value="Informações falsas">Informações falsas</option>
                            <option value="Conteúdo inadequado">Conteúdo inadequado</option>
                            <option value="Spam ou propaganda">Spam ou propaganda</option>
                            <option value="Comportamento inadequado">Comportamento inadequado</option>
                            <option value="Violação de termos">Violação de termos</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição do problema</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Descreva o problema em detalhes..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i> Sua denúncia será analisada pela nossa equipe e tratada com confidencialidade. Agradecemos sua contribuição para manter a plataforma segura.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Enviar Reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>
