<?php
// Incluir arquivo para registrar interações
require_once 'includes/registrar_interacao.php';

// Verificar se o usuário está logado como talento
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'talento') {
    $_SESSION['flash_message'] = "Você precisa estar logado como talento para se candidatar a uma vaga.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=vagas';</script>";
    exit;
}

// Verificar se o ID da vaga foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Vaga não especificada.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=vagas';</script>";
    exit;
}

// Obter ID da vaga
$vaga_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a vaga existe e está ativa
$vaga = $db->fetchRow("
    SELECT v.*, u.id as empresa_id, u.nome as contato_nome, e.nome_empresa
    FROM vagas v
    JOIN usuarios u ON v.empresa_id = u.id
    JOIN empresas e ON u.id = e.usuario_id
    WHERE v.id = :id AND v.status = 'ativa'
", [
    'id' => $vaga_id
]);

// Se a vaga não existir ou não estiver ativa, redirecionar
if (!$vaga) {
    $_SESSION['flash_message'] = "Vaga não encontrada ou não disponível.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=vagas';</script>";
    exit;
}

// Obter dados do talento logado
$talento_id = $_SESSION['user_id'];
$talento = $db->fetchRow("
    SELECT u.nome, u.email, t.profissao, t.curriculo, t.foto_perfil
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    WHERE u.id = :id
", [
    'id' => $talento_id
]);

// Verificar se o talento já se candidatou a esta vaga
$ja_candidatou = $db->fetchRow("
    SELECT id FROM candidaturas
    WHERE vaga_id = :vaga_id AND talento_id = :talento_id
", [
    'vaga_id' => $vaga_id,
    'talento_id' => $talento_id
]);

if ($ja_candidatou) {
    $_SESSION['flash_message'] = "Você já se candidatou a esta vaga.";
    $_SESSION['flash_type'] = "info";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=vaga&id=" . $vaga_id . "';</script>";
    exit;
}

// Processar o envio da candidatura
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carta_apresentacao = isset($_POST['carta_apresentacao']) ? trim($_POST['carta_apresentacao']) : '';
    
    // Validação básica
    $erros = [];
    
    if (empty($carta_apresentacao)) {
        $erros[] = "A carta de apresentação é obrigatória.";
    }
    
    // Se não houver erros, enviar a candidatura
    if (empty($erros)) {
        // Inserir a candidatura no banco de dados
        $resultado = $db->insert('candidaturas', [
            'vaga_id' => $vaga_id,
            'talento_id' => $talento_id,
            'data_candidatura' => date('Y-m-d H:i:s'),
            'carta_apresentacao' => $carta_apresentacao,
            'status' => 'pendente'
        ]);
        
        if ($resultado) {
            // Registrar a interação para estatísticas
            registrarCandidatura($talento_id, $vaga['empresa_id'], $vaga_id);
            
            // Enviar notificação para a empresa
            $db->insert('notificacoes', [
                'usuario_id' => $vaga['empresa_id'],
                'tipo' => 'candidatura',
                'mensagem' => 'Nova candidatura para a vaga: ' . $vaga['titulo'],
                'link' => '?route=candidaturas_vaga&id=' . $vaga_id,
                'data_criacao' => date('Y-m-d H:i:s'),
                'lida' => 0
            ]);
            
            $_SESSION['flash_message'] = "Candidatura enviada com sucesso! A empresa será notificada.";
            $_SESSION['flash_type'] = "success";
            echo "<script>window.location.href = '" . SITE_URL . "/?route=minhas_candidaturas';</script>";
            exit;
        } else {
            $_SESSION['flash_message'] = "Erro ao enviar candidatura. Por favor, tente novamente.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = implode("<br>", $erros);
        $_SESSION['flash_type'] = "danger";
    }
}

// Definir título da página
$page_title = "Candidatar-se à Vaga: " . $vaga['titulo'];
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-0">Candidatar-se à Vaga</h1>
            <p class="text-muted">Envie sua candidatura para a vaga: <?php echo htmlspecialchars($vaga['titulo']); ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?php echo SITE_URL; ?>/?route=vaga&id=<?php echo $vaga_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Voltar à Vaga
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Detalhes da Vaga</h5>
                </div>
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                    <p class="text-muted mb-2">
                        <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($vaga['nome_empresa']); ?>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($vaga['localizacao']); ?>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-money-bill-wave me-1"></i> <?php echo htmlspecialchars($vaga['faixa_salarial']); ?>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-briefcase me-1"></i> <?php echo htmlspecialchars($vaga['tipo_contrato']); ?>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-calendar-alt me-1"></i> Publicada em: <?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?>
                    </p>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Seu Perfil</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($talento['foto_perfil'])): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" alt="<?php echo htmlspecialchars($talento['nome']); ?>" class="img-fluid rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px;">
                            <i class="fas fa-user fa-3x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($talento['nome']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($talento['profissao']); ?></p>
                    
                    <?php if (!empty($talento['curriculo'])): ?>
                        <p class="mb-0">
                            <a href="<?php echo SITE_URL; ?>/uploads/curriculos/<?php echo $talento['curriculo']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-pdf"></i> Ver Currículo
                            </a>
                        </p>
                    <?php else: ?>
                        <p class="text-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Você não possui currículo cadastrado
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Enviar Candidatura</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo SITE_URL; ?>/?route=candidatar_vaga&id=<?php echo $vaga_id; ?>" method="POST">
                        <div class="mb-3">
                            <label for="carta_apresentacao" class="form-label">Carta de Apresentação</label>
                            <textarea class="form-control" id="carta_apresentacao" name="carta_apresentacao" rows="8" required><?php echo isset($_POST['carta_apresentacao']) ? htmlspecialchars($_POST['carta_apresentacao']) : ''; ?></textarea>
                            <div class="form-text">
                                Descreva por que você é um bom candidato para esta vaga. Destaque suas habilidades e experiências relevantes.
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Enviar Candidatura
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
