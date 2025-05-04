<?php
// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = "Você precisa estar logado para demonstrar interesse em uma demanda.";
    $_SESSION['flash_type'] = "warning";
    
    // Redirecionar para a página de login com redirecionamento de volta
    $redirect = "demandas";
    if (isset($_GET['demanda_id'])) {
        $redirect = "visualizar_demanda&id=" . $_GET['demanda_id'];
    }
    
    echo "<script>window.location.href = '" . SITE_URL . "/?route=login&redirect=" . $redirect . "';</script>";
    exit;
}

// Verificar se o usuário é um talento
$db = Database::getInstance();
$usuario_tipo = $db->fetchColumn("SELECT tipo FROM usuarios WHERE id = ?", [$_SESSION['user_id']]);

if ($usuario_tipo != 'talento') {
    $_SESSION['flash_message'] = "Apenas talentos podem demonstrar interesse em demandas.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=demandas';</script>";
    exit;
}

// Verificar se o ID da demanda foi fornecido
if (!isset($_GET['demanda_id']) || empty($_GET['demanda_id'])) {
    $_SESSION['flash_message'] = "ID da demanda não fornecido.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=demandas';</script>";
    exit;
}

$demanda_id = (int)$_GET['demanda_id'];

// Verificar se a demanda existe e está ativa
$demanda = $db->fetchRow("
    SELECT dt.*, e.razao_social as empresa_nome
    FROM demandas_talentos dt
    JOIN empresas e ON dt.empresa_id = e.id
    WHERE dt.id = ? AND dt.status = 'ativa'
", [$demanda_id]);

if (!$demanda) {
    $_SESSION['flash_message'] = "Demanda não encontrada ou não está ativa.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=demandas';</script>";
    exit;
}

// Obter o ID do talento
$talento_id = $db->fetchColumn("SELECT id FROM talentos WHERE usuario_id = ?", [$_SESSION['user_id']]);

if (!$talento_id) {
    $_SESSION['flash_message'] = "Seu perfil de talento não foi encontrado.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=demandas';</script>";
    exit;
}

// Verificar se o talento já demonstrou interesse
$ja_interessado = $db->fetchColumn("
    SELECT COUNT(*) FROM demandas_interessados 
    WHERE demanda_id = ? AND talento_id = ?
", [$demanda_id, $talento_id]) > 0;

if ($ja_interessado) {
    $_SESSION['flash_message'] = "Você já demonstrou interesse nesta demanda.";
    $_SESSION['flash_type'] = "info";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=visualizar_demanda&id=" . $demanda_id . "';</script>";
    exit;
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $db->beginTransaction();
        
        // Registrar o interesse
        $db->execute("
            INSERT INTO demandas_interessados (demanda_id, talento_id, status)
            VALUES (?, ?, 'pendente')
        ", [$demanda_id, $talento_id]);
        
        // Enviar notificação para a empresa (implementação futura)
        // TODO: Implementar sistema de notificações
        
        // Confirmar transação
        $db->commit();
        
        $_SESSION['flash_message'] = "Seu interesse foi registrado com sucesso! A empresa será notificada.";
        $_SESSION['flash_type'] = "success";
        echo "<script>window.location.href = '" . SITE_URL . "/?route=visualizar_demanda&id=" . $demanda_id . "';</script>";
        exit;
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $db->rollBack();
        
        $_SESSION['flash_message'] = "Erro ao registrar interesse: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Obter dados do talento para exibir no formulário
$talento = $db->fetchRow("
    SELECT t.*, u.nome, u.email
    FROM talentos t
    JOIN usuarios u ON t.usuario_id = u.id
    WHERE t.id = ?
", [$talento_id]);

// Obter as profissões da demanda
$profissoes = $db->fetchAll("
    SELECT profissao FROM demandas_profissoes WHERE demanda_id = ?
", [$demanda_id]);
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Demonstrar Interesse</h1>
                <p class="lead">Demonstre seu interesse na demanda "<?php echo htmlspecialchars($demanda['titulo']); ?>"</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=demandas">Demandas de Talentos</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda_id; ?>">Visualizar Demanda</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Demonstrar Interesse</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-demonstrar-interesse py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-hand-paper me-2"></i>Demonstrar Interesse</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5>Resumo da demanda</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div>
                                    <p class="mb-1"><strong>Título:</strong> <?php echo htmlspecialchars($demanda['titulo']); ?></p>
                                    <p class="mb-1"><strong>Empresa:</strong> <?php echo htmlspecialchars($demanda['empresa_nome']); ?></p>
                                    <p class="mb-0"><strong>Profissões desejadas:</strong> 
                                        <?php 
                                        $prof_list = array_map(function($p) { 
                                            return htmlspecialchars($p['profissao']); 
                                        }, $profissoes);
                                        echo implode(', ', $prof_list);
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Como funciona:</strong> Ao demonstrar interesse, a empresa será notificada e poderá visualizar seu perfil. 
                                Se seu perfil for compatível com as necessidades da empresa, ela poderá entrar em contato com você.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Seu perfil</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-1"><strong>Nome:</strong> <?php echo htmlspecialchars($talento['nome']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($talento['email']); ?></p>
                                    <p class="mb-1"><strong>Profissão:</strong> <?php echo htmlspecialchars($talento['profissao'] ?? 'Não informada'); ?></p>
                                    <p class="mb-0"><strong>Experiência:</strong> <?php echo htmlspecialchars($talento['experiencia'] ?? 'Não informada'); ?> anos</p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="confirmar" name="confirmar" required>
                                <label class="form-check-label" for="confirmar">
                                    Confirmo que desejo demonstrar interesse nesta demanda e autorizo que a empresa visualize meu perfil.
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda_id; ?>" class="btn btn-outline-secondary me-md-2">
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i> Confirmar interesse
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
