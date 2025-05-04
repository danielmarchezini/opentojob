<?php
// Incluir arquivo para registrar interações
require_once 'includes/registrar_interacao.php';

// Verificar se o usuário está logado como empresa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'empresa') {
    $_SESSION['flash_message'] = "Você precisa estar logado como empresa para contatar um talento.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Verificar se o ID do talento foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Talento não especificado.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=talentos';</script>";
    exit;
}

// Obter o ID do talento
$talento_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o talento existe e está ativo
$talento = $db->fetchRow("
    SELECT u.id, u.nome, t.profissao, t.foto_perfil
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    WHERE u.id = :id AND u.tipo = 'talento' AND u.status = 'ativo'
", [
    'id' => $talento_id
]);

// Se o talento não existir, não estiver ativo ou não tiver perfil público, redirecionar
if (!$talento) {
    $_SESSION['flash_message'] = "Talento não encontrado ou não disponível.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=talentos';</script>";
    exit;
}

// Obter dados da empresa logada
$empresa_id = $_SESSION['user_id'];
$empresa = $db->fetchRow("
    SELECT u.nome, e.nome_empresa, e.logo
    FROM usuarios u
    JOIN empresas e ON u.id = e.usuario_id
    WHERE u.id = :id
", [
    'id' => $empresa_id
]);

// Processar o envio da mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto = isset($_POST['assunto']) ? trim($_POST['assunto']) : '';
    $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
    
    // Validação básica
    $erros = [];
    
    if (empty($assunto)) {
        $erros[] = "O assunto é obrigatório.";
    }
    
    if (empty($mensagem)) {
        $erros[] = "A mensagem é obrigatória.";
    }
    
    // Se não houver erros, enviar a mensagem
    if (empty($erros)) {
        // Inserir a mensagem no banco de dados
        $resultado = $db->insert('mensagens', [
            'remetente_id' => $empresa_id,
            'destinatario_id' => $talento_id,
            'assunto' => $assunto,
            'mensagem' => $mensagem,
            'remetente_tipo' => 'empresa',
            'conteudo' => $mensagem,
            'data_envio' => date('Y-m-d H:i:s'),
            'lida' => 0,
            'excluida_remetente' => 0,
            'excluida_destinatario' => 0
        ]);
        
        if ($resultado) {
            // Registrar a interação para estatísticas
            registrarContato($empresa_id, $talento_id, $assunto);
            
            $_SESSION['flash_message'] = "Mensagem enviada com sucesso para " . $talento['nome'] . ".";
            $_SESSION['flash_type'] = "success";
            echo "<script>window.location.href = '" . SITE_URL . "/?route=mensagens_empresa';</script>";
            exit;
        } else {
            $_SESSION['flash_message'] = "Erro ao enviar mensagem. Por favor, tente novamente.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Erro ao enviar mensagem: " . implode(", ", $erros);
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Contatar Talento</h1>
                <p class="lead">Envie uma mensagem para <?php echo htmlspecialchars($talento['nome']); ?></p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=talentos">Talentos</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento_id; ?>"><?php echo htmlspecialchars($talento['nome']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Contatar</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-contato py-5">
    <div class="container">
        <div class="row">
            <!-- Coluna da esquerda - Informações do talento -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Destinatário</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="talento-avatar me-3">
                                <?php if (!empty($talento['foto_perfil'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" alt="<?php echo htmlspecialchars($talento['nome']); ?>" class="rounded-circle">
                                <?php else: ?>
                                    <div class="avatar-placeholder rounded-circle bg-primary text-white">
                                        <?php echo strtoupper(substr($talento['nome'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($talento['nome']); ?></h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($talento['profissao'] ?? 'Profissional'); ?></p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>Esta mensagem será enviada para a caixa de entrada do talento na plataforma Open2W.</small>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Remetente</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="empresa-avatar me-3">
                                <?php if (!empty($empresa['logo'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/empresas/<?php echo $empresa['logo']; ?>" alt="<?php echo htmlspecialchars($empresa['nome_empresa']); ?>" class="rounded">
                                <?php else: ?>
                                    <div class="avatar-placeholder rounded bg-secondary text-white">
                                        <?php echo strtoupper(substr($empresa['nome_empresa'] ?: $empresa['nome'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($empresa['nome_empresa'] ?: $empresa['nome']); ?></h5>
                                <p class="text-muted mb-0">Empresa</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Coluna da direita - Formulário de mensagem -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Enviar Mensagem</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo SITE_URL; ?>/?route=contatar_talento&id=<?php echo $talento_id; ?>" method="POST">
                            <div class="form-group mb-3">
                                <label for="assunto" class="form-label">Assunto</label>
                                <input type="text" class="form-control" id="assunto" name="assunto" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="mensagem" class="form-label">Mensagem</label>
                                <textarea class="form-control" id="mensagem" name="mensagem" rows="6" required></textarea>
                                <small class="text-muted">Seja claro e objetivo. Informe o motivo do contato e como o talento pode responder.</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="termos" required>
                                <label class="form-check-label" for="termos">
                                    Concordo com os <a href="<?php echo SITE_URL; ?>/?route=termos" target="_blank">termos de uso</a> e estou ciente de que esta mensagem será armazenada na plataforma.
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento_id; ?>" class="btn btn-outline-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.talento-avatar img, .empresa-avatar img, .avatar-placeholder {
    width: 60px;
    height: 60px;
    object-fit: cover;
}

.avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
}
</style>
