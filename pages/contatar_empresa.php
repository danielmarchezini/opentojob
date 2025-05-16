<?php
// Incluir arquivo para registrar interações
require_once 'includes/registrar_interacao.php';

// Verificar se o usuário está logado e é um talento
if (!Auth::isLoggedIn() || !Auth::checkUserType('talento')) {
    $_SESSION['flash_message'] = "Acesso restrito. Apenas talentos podem contatar empresas.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . SITE_URL . "/?route=empresas");
    exit;
}

// Verificar se o ID da empresa foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "Empresa não especificada.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . SITE_URL . "/?route=empresas");
    exit;
}

// Obter ID da empresa
$empresa_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a empresa existe e está ativa
$empresa = $db->fetchRow("
    SELECT u.id, u.nome, u.email, e.nome_empresa, e.logo
    FROM usuarios u
    JOIN empresas e ON u.id = e.usuario_id
    WHERE u.id = :id AND u.tipo = 'empresa' AND u.status = 'ativo'
", [
    'id' => $empresa_id
]);

// Se a empresa não existir ou não estiver ativa, redirecionar
if (!$empresa) {
    $_SESSION['flash_message'] = "Empresa não encontrada ou não está ativa.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . SITE_URL . "/?route=empresas");
    exit;
}

// Obter dados do talento logado
$talento_id = $_SESSION['user_id'];
$talento = $db->fetchRow("
    SELECT u.nome, t.profissao, t.foto_perfil
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    WHERE u.id = :id
", [
    'id' => $talento_id
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
            'remetente_id' => $talento_id,
            'destinatario_id' => $empresa_id,
            'assunto' => $assunto,
            'mensagem' => $mensagem,
            'data_envio' => date('Y-m-d H:i:s')
        ]);
        
        if ($resultado) {
            // Registrar a interação para estatísticas
            registrarContato($talento_id, $empresa_id, $assunto);
            
            $_SESSION['flash_message'] = "Mensagem enviada com sucesso para " . $empresa['nome_empresa'] . "!";
            $_SESSION['flash_type'] = "success";
            header("Location: " . SITE_URL . "/?route=mensagens_talento");
            exit;
        } else {
            $_SESSION['flash_message'] = "Erro ao enviar mensagem. Por favor, tente novamente.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = implode("<br>", $erros);
        $_SESSION['flash_type'] = "danger";
    }
}

// Definir título da página
$page_title = "Contatar " . $empresa['nome_empresa'];
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-0">Contatar Empresa</h1>
            <p class="text-muted">Envie uma mensagem para <?php echo htmlspecialchars((string)$empresa['nome_empresa']); ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa&id=<?php echo $empresa_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Voltar ao Perfil
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <?php if (!empty($empresa['logo'])): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/empresas/<?php echo $empresa['logo']; ?>" alt="<?php echo htmlspecialchars((string)$empresa['nome_empresa']); ?>" class="img-fluid rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                            <i class="fas fa-building fa-4x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h4 class="card-title"><?php echo htmlspecialchars((string)$empresa['nome_empresa']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars((string)$empresa['nome']); ?></p>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-center">
                        <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa&id=<?php echo $empresa_id; ?>" class="btn btn-sm btn-outline-secondary mx-1">
                            <i class="fas fa-user"></i> Ver Perfil
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Enviar Mensagem</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo SITE_URL; ?>/?route=contatar_empresa&id=<?php echo $empresa_id; ?>" method="POST">
                        <div class="mb-3">
                            <label for="assunto" class="form-label">Assunto</label>
                            <input type="text" class="form-control" id="assunto" name="assunto" required value="<?php echo isset($_POST['assunto']) ? htmlspecialchars((string)$_POST['assunto']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="mensagem" class="form-label">Mensagem</label>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="6" required><?php echo isset($_POST['mensagem']) ? htmlspecialchars((string)$_POST['mensagem']) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Enviar Mensagem
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
