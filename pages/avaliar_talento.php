<?php
// Iniciar o output buffer para evitar problemas com redirecionamento
ob_start();

// Verificar se o ID do talento foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirecionar para a página de talentos se nenhum ID for fornecido
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

// Se o talento não existir ou não estiver ativo, redirecionar
if (!$talento) {
    $_SESSION['flash_message'] = "Talento não encontrado ou inativo. Por favor, tente novamente mais tarde.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=talentos';</script>";
    exit;
}

// Verificar se o usuário está logado como empresa ou admin
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'empresa' && $_SESSION['user_type'] !== 'admin')) {
    $_SESSION['flash_message'] = "Você precisa estar logado como empresa para avaliar talentos.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Processar o envio da avaliação
$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter os dados do formulário
    $nome_avaliador = isset($_POST['nome_avaliador']) ? trim($_POST['nome_avaliador']) : '';
    $linkedin_avaliador = isset($_POST['linkedin_avaliador']) ? trim($_POST['linkedin_avaliador']) : '';
    $avaliacao = isset($_POST['avaliacao']) ? trim($_POST['avaliacao']) : '';
    $pontuacao = isset($_POST['pontuacao']) ? (int)$_POST['pontuacao'] : 0;
    $publica = isset($_POST['publica']) ? 1 : 0;
    
    // Validação básica
    if (empty($nome_avaliador)) {
        $erros[] = "O nome é obrigatório.";
    }
    
    if (empty($avaliacao)) {
        $erros[] = "A avaliação é obrigatória.";
    }
    
    if ($pontuacao < 1 || $pontuacao > 5) {
        $erros[] = "A pontuação deve ser entre 1 e 5.";
    }
    
    // Se não houver erros, salvar a avaliação
    if (empty($erros)) {
        // Verificar se o usuário está logado como empresa
        $empresa_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        
        try {
            // Verificar se a tabela avaliacoes existe
            $tabela_existe = $db->query("SHOW TABLES LIKE 'avaliacoes'")->rowCount() > 0;
            
            if (!$tabela_existe) {
                // Criar a tabela avaliacoes
                $sql = "CREATE TABLE avaliacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    talento_id INT NOT NULL,
                    empresa_id INT NOT NULL DEFAULT 0,
                    nome_avaliador VARCHAR(255) NOT NULL,
                    linkedin_avaliador VARCHAR(255) NULL,
                    avaliacao TEXT NOT NULL,
                    pontuacao INT NOT NULL,
                    data_avaliacao DATETIME NOT NULL,
                    publica TINYINT(1) NOT NULL DEFAULT 1,
                    status ENUM('pendente', 'aprovada', 'rejeitada') NOT NULL DEFAULT 'pendente',
                    FOREIGN KEY (talento_id) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                $db->query($sql);
            }
            
            // Dados para inserção na tabela avaliacoes
            $dados_avaliacao = [
                'talento_id' => $talento_id,
                'empresa_id' => $empresa_id,
                'nome_avaliador' => $nome_avaliador,
                'linkedin_avaliador' => $linkedin_avaliador,
                'avaliacao' => $avaliacao,
                'pontuacao' => $pontuacao,
                'data_avaliacao' => date('Y-m-d H:i:s'),
                'publica' => $publica,
                'status' => 'pendente'
            ];
            
            // Inserir na tabela avaliacoes
            $resultado = $db->insert('avaliacoes', $dados_avaliacao);
            
            if ($resultado) {
                $_SESSION['flash_message'] = "Avaliação enviada com sucesso! Ela será revisada por nossa equipe antes de ser publicada.";
                $_SESSION['flash_type'] = "success";
                
                // Redirecionar para a página de perfil do talento
                header("Location: " . SITE_URL . "/?route=perfil_talento&id=" . $talento_id);
                exit;
            } else {
                $erros[] = "Erro ao salvar a avaliação. Por favor, tente novamente.";
            }
        } catch (Exception $e) {
            $erros[] = "Erro ao salvar a avaliação: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Avaliar Talento</h1>
                <p class="lead">Compartilhe sua experiência com <?php echo htmlspecialchars((string)$talento['nome']); ?></p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=talentos">Talentos</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento_id; ?>"><?php echo htmlspecialchars((string)$talento['nome']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Avaliar</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-avaliacao py-5">
    <div class="container">
        <div class="row">
            <!-- Coluna da esquerda - Informações do talento -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Talento a ser avaliado</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="talento-avatar mb-3">
                            <?php if (!empty($talento['foto_perfil'])): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" alt="<?php echo htmlspecialchars((string)$talento['nome']); ?>" class="rounded-circle">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle bg-primary text-white">
                                    <?php echo strtoupper(substr($talento['nome'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars((string)$talento['nome']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars((string)$talento['profissao'] ?? 'Profissional'); ?></p>
                        
                        <div class="alert alert-info">
                            <small>Sua avaliação será revisada por nossa equipe antes de ser publicada no perfil do talento.</small>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Dicas para uma boa avaliação</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Seja específico sobre suas experiências</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Destaque pontos fortes e áreas de melhoria</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Seja respeitoso e construtivo</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Forneça exemplos concretos</li>
                            <li class="mb-2"><i class="fas fa-times-circle text-danger me-2"></i> Evite comentários pessoais não relacionados ao trabalho</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Coluna da direita - Formulário de avaliação -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Formulário de Avaliação</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($erros)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($erros as $erro): ?>
                                        <li><?php echo htmlspecialchars((string)$erro); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo SITE_URL; ?>/?route=avaliar_talento&id=<?php echo $talento_id; ?>" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome_avaliador" class="form-label">Seu Nome Completo *</label>
                                    <input type="text" class="form-control" id="nome_avaliador" name="nome_avaliador" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="linkedin_avaliador" class="form-label">Seu LinkedIn (opcional)</label>
                                    <input type="url" class="form-control" id="linkedin_avaliador" name="linkedin_avaliador" placeholder="https://linkedin.com/in/seu-perfil">
                                    <small class="text-muted">Adicione seu perfil para dar mais credibilidade à sua avaliação</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Pontuação *</label>
                                <div class="d-flex align-items-center">
                                    <div class="btn-group" role="group" aria-label="Pontuação">
                                        <input type="radio" class="btn-check" name="pontuacao" id="pontuacao1" value="1" required>
                                        <label class="btn btn-outline-warning" for="pontuacao1">1 <i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" class="btn-check" name="pontuacao" id="pontuacao2" value="2">
                                        <label class="btn btn-outline-warning" for="pontuacao2">2 <i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" class="btn-check" name="pontuacao" id="pontuacao3" value="3">
                                        <label class="btn btn-outline-warning" for="pontuacao3">3 <i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" class="btn-check" name="pontuacao" id="pontuacao4" value="4">
                                        <label class="btn btn-outline-warning" for="pontuacao4">4 <i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" class="btn-check" name="pontuacao" id="pontuacao5" value="5" checked>
                                        <label class="btn btn-outline-warning" for="pontuacao5">5 <i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="avaliacao" class="form-label">Sua Avaliação *</label>
                                <textarea class="form-control" id="avaliacao" name="avaliacao" rows="6" required></textarea>
                                <small class="text-muted">Descreva sua experiência trabalhando com <?php echo htmlspecialchars((string)$talento['nome']); ?>. Seja específico e construtivo.</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="publica" name="publica" checked>
                                <label class="form-check-label" for="publica">
                                    Quero avaliar este perfil publicamente
                                </label>
                                <small class="form-text text-muted d-block">Se marcado, seu nome e avaliação serão exibidos no perfil público do talento após aprovação.</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="termos" name="termos" required>
                                <label class="form-check-label" for="termos">
                                    Confirmo que conheço <?php echo htmlspecialchars((string)$talento['nome']); ?> profissionalmente e que as informações fornecidas são verdadeiras. *
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento_id; ?>" class="btn btn-outline-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Enviar Avaliação</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.talento-avatar img, .avatar-placeholder {
    width: 120px;
    height: 120px;
    object-fit: cover;
    margin: 0 auto;
}

.avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: bold;
}

/* Estilo para o sistema de avaliação por estrelas simplificado */
.btn-outline-warning {
    color: #ffc107;
    border-color: #ffc107;
}

.btn-outline-warning:hover,
.btn-check:checked + .btn-outline-warning {
    color: #000;
    background-color: #ffc107;
    border-color: #ffc107;
}
</style>
