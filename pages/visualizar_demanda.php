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

// Verificar se o ID da demanda foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "ID da demanda não fornecido.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=demandas';</script>";
    exit;
}

$demanda_id = (int)$_GET['id'];

// Obter detalhes da demanda
$demanda = $db->fetchRow("
    SELECT dt.*, e.razao_social as empresa_nome, u.foto_perfil as empresa_logo,
           u.id as usuario_id, e.id as empresa_id, e.cidade, e.estado, e.site
    FROM demandas_talentos dt
    JOIN empresas e ON dt.empresa_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE dt.id = ? AND dt.status = 'ativa'
", [$demanda_id]);

// Verificar se a demanda existe
if (!$demanda) {
    $_SESSION['flash_message'] = "Demanda não encontrada ou não está ativa.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=demandas';</script>";
    exit;
}

// Obter as profissões da demanda
$profissoes = $db->fetchAll("
    SELECT profissao FROM demandas_profissoes WHERE demanda_id = ?
", [$demanda_id]);

// Verificar se o usuário atual é um talento
$is_talento = false;
$talento_id = null;
$ja_interessado = false;

if (isset($_SESSION['user_id'])) {
    $usuario_tipo = $db->fetchColumn("SELECT tipo FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);
    if ($usuario_tipo == 'talento') {
        $is_talento = true;
        $talento_id = $db->fetchColumn("SELECT id FROM talentos WHERE usuario_id = ?", [$_SESSION['user_id']]);
        
        // Verificar se o talento já demonstrou interesse
        $ja_interessado = $db->fetchColumn("
            SELECT COUNT(*) FROM demandas_interessados 
            WHERE demanda_id = ? AND talento_id = ?
        ", [$demanda_id, $talento_id]) > 0;
    }
}

// Obter demandas relacionadas (mesma empresa ou profissões similares)
$demandas_relacionadas = $db->fetchAll("
    SELECT dt.id, dt.titulo, e.razao_social as empresa_nome
    FROM demandas_talentos dt
    JOIN empresas e ON dt.empresa_id = e.id
    WHERE dt.id != ? AND dt.status = 'ativa'
    AND (dt.empresa_id = ? OR EXISTS (
        SELECT 1 FROM demandas_profissoes dp1
        JOIN demandas_profissoes dp2 ON dp1.profissao = dp2.profissao
        WHERE dp1.demanda_id = ? AND dp2.demanda_id = dt.id
    ))
    LIMIT 5
", [$demanda_id, $demanda['empresa_id'], $demanda_id]);
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1><?php echo htmlspecialchars($demanda['titulo']); ?></h1>
                <p class="lead">Demanda de talentos publicada por <?php echo htmlspecialchars($demanda['empresa_nome']); ?></p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=demandas">Procura-se Profissionais</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Visualizar Demanda</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-demanda py-5">
    <div class="container">
        <div class="row">
            <!-- Detalhes da demanda -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="empresa-logo me-3">
                                <?php if (isset($demanda['empresa_logo']) && !empty($demanda['empresa_logo'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $demanda['empresa_logo']; ?>" alt="<?php echo htmlspecialchars($demanda['empresa_nome']); ?>" class="rounded-circle">
                                <?php else: ?>
                                    <div class="logo-placeholder rounded-circle bg-light text-primary">
                                        <?php echo strtoupper(substr($demanda['empresa_nome'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo htmlspecialchars($demanda['empresa_nome']); ?></h4>
                                <p class="text-muted mb-0">
                                    <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa&id=<?php echo $demanda['usuario_id']; ?>">
                                        Ver perfil da empresa
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Detalhes da demanda</h5>
                                <ul class="list-unstyled">
                                    <?php if (!empty($demanda['regime_trabalho'])): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-building me-2"></i>
                                        <strong>Regime de trabalho:</strong> <?php echo htmlspecialchars($demanda['regime_trabalho']); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($demanda['nivel_experiencia'])): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-user-tie me-2"></i>
                                        <strong>Nível de experiência:</strong> <?php echo htmlspecialchars($demanda['nivel_experiencia']); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($demanda['prazo_contratacao'])): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <strong>Prazo para contratação:</strong> <?php echo date('d/m/Y', strtotime($demanda['prazo_contratacao'])); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <li class="mb-2">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>Publicada em:</strong> <?php echo date('d/m/Y', strtotime($demanda['data_publicacao'])); ?>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Sobre a empresa</h5>
                                <ul class="list-unstyled">
                                    <?php if (!empty($demanda['cidade']) && !empty($demanda['estado'])): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <strong>Localização:</strong> <?php echo htmlspecialchars($demanda['cidade'] . ', ' . $demanda['estado']); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($demanda['site'])): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-globe me-2"></i>
                                        <strong>Website:</strong> 
                                        <a href="<?php echo htmlspecialchars($demanda['site']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($demanda['site']); ?>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Profissões desejadas</h5>
                            <div class="profissoes-tags">
                                <?php foreach ($profissoes as $prof): ?>
                                    <span class="badge bg-primary mb-1 me-1"><?php echo htmlspecialchars($prof['profissao']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Descrição da demanda</h5>
                            <div class="demanda-descricao">
                                <?php 
                                if (!empty($demanda['descricao'])) {
                                    echo nl2br(htmlspecialchars($demanda['descricao']));
                                } else {
                                    echo '<p class="text-muted">Sem descrição disponível.</p>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="demanda-actions">
                            <?php if ($is_talento): ?>
                                <?php if ($ja_interessado): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Você já demonstrou interesse nesta demanda. A empresa entrará em contato caso seu perfil seja compatível.
                                    </div>
                                <?php else: ?>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5>Tem interesse nesta demanda?</h5>
                                            <p>Demonstre seu interesse para que a empresa possa entrar em contato com você.</p>
                                            <a href="<?php echo SITE_URL; ?>/?route=demonstrar_interesse&demanda_id=<?php echo $demanda['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-hand-paper me-2"></i> Tenho interesse nesta demanda
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5>Tem interesse nesta demanda?</h5>
                                        <p>Entre ou cadastre-se para demonstrar interesse nesta demanda.</p>
                                        <div class="d-grid gap-2 d-md-flex">
                                            <a href="<?php echo SITE_URL; ?>/?route=login&redirect=visualizar_demanda&id=<?php echo $demanda['id']; ?>" class="btn btn-primary me-md-2">
                                                <i class="fas fa-sign-in-alt me-2"></i> Entrar
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/?route=cadastro&tipo=talento&redirect=visualizar_demanda&id=<?php echo $demanda['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-user-plus me-2"></i> Cadastrar-se como talento
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'empresa'): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Você está logado como empresa. Para demonstrar interesse, é necessário entrar com uma conta de talento.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Compartilhar -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i>Compartilhar</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(SITE_URL . '/?route=visualizar_demanda&id=' . $demanda['id']); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fab fa-linkedin me-2"></i> Compartilhar no LinkedIn
                            </a>
                            <a href="https://wa.me/?text=<?php echo urlencode('Confira esta demanda de talentos: ' . $demanda['titulo'] . ' - ' . SITE_URL . '/?route=visualizar_demanda&id=' . $demanda['id']); ?>" target="_blank" class="btn btn-outline-success">
                                <i class="fab fa-whatsapp me-2"></i> Compartilhar no WhatsApp
                            </a>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard('<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda['id']; ?>')">
                                <i class="fas fa-copy me-2"></i> Copiar link
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Anúncios relacionados -->
                <?php if (!empty($demandas_relacionadas)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Anúncios relacionados</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($demandas_relacionadas as $rel): ?>
                                <li class="list-group-item px-0">
                                    <a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $rel['id']; ?>" class="text-decoration-none">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($rel['titulo']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($rel['empresa_nome']); ?></small>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Voltar para a lista -->
                <div class="d-grid">
                    <a href="<?php echo SITE_URL; ?>/?route=demandas" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Voltar para a lista de anúncios
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Link copiado para a área de transferência!');
    }, function() {
        alert('Não foi possível copiar o link. Por favor, tente novamente.');
    });
}
</script>

<style>
.empresa-logo img, .logo-placeholder {
    width: 60px;
    height: 60px;
    object-fit: cover;
}

.logo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
}

.profissoes-tags {
    display: flex;
    flex-wrap: wrap;
}

.demanda-descricao {
    white-space: pre-line;
}
</style>
