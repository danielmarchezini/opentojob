<?php
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

// Verificar se o talento existe, está ativo e tem perfil público
$talento = $db->fetchRow("
    SELECT u.id, u.nome, t.profissao, t.foto_perfil
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    WHERE u.id = :id AND u.tipo = 'talento' AND u.status = 'ativo' AND t.mostrar_perfil = 1
", [
    'id' => $talento_id
]);

// Se o talento não existir, não estiver ativo ou não tiver perfil público, redirecionar
if (!$talento) {
    $_SESSION['flash_message'] = "Talento não encontrado ou não disponível para avaliação.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=talentos';</script>";
    exit;
}

// Processar o envio da avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_avaliador = isset($_POST['nome_avaliador']) ? trim($_POST['nome_avaliador']) : '';
    $linkedin_avaliador = isset($_POST['linkedin_avaliador']) ? trim($_POST['linkedin_avaliador']) : '';
    $avaliacao = isset($_POST['avaliacao']) ? trim($_POST['avaliacao']) : '';
    $pontuacao = isset($_POST['pontuacao']) ? (int)$_POST['pontuacao'] : 0;
    $publica = isset($_POST['publica']) ? 1 : 0;
    
    // Validação básica
    $erros = [];
    
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
        // Inserir a avaliação no banco de dados
        $resultado = $db->insert('avaliacoes', [
            'talento_id' => $talento_id,
            'nome_avaliador' => $nome_avaliador,
            'linkedin_avaliador' => $linkedin_avaliador,
            'avaliacao' => $avaliacao,
            'pontuacao' => $pontuacao,
            'data_avaliacao' => date('Y-m-d H:i:s'),
            'publica' => $publica,
            'aprovada' => 0 // Avaliações precisam ser aprovadas por um administrador
        ]);
        
        if ($resultado) {
            $_SESSION['flash_message'] = "Avaliação enviada com sucesso! Ela será revisada por nossa equipe antes de ser publicada.";
            $_SESSION['flash_type'] = "success";
            echo "<script>window.location.href = '" . SITE_URL . "/?route=perfil_talento&id=" . $talento_id . "';</script>";
            exit;
        } else {
            $_SESSION['flash_message'] = "Erro ao enviar avaliação. Por favor, tente novamente.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Erro ao enviar avaliação: " . implode(", ", $erros);
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Avaliar Talento</h1>
                <p class="lead">Compartilhe sua experiência com <?php echo htmlspecialchars($talento['nome']); ?></p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=talentos">Talentos</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento_id; ?>"><?php echo htmlspecialchars($talento['nome']); ?></a></li>
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
                                <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" alt="<?php echo htmlspecialchars($talento['nome']); ?>" class="rounded-circle">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle bg-primary text-white">
                                    <?php echo strtoupper(substr($talento['nome'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($talento['nome']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($talento['profissao'] ?? 'Profissional'); ?></p>
                        
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
                        <form action="<?php echo SITE_URL; ?>/?route=avaliar_talento&id=<?php echo $talento_id; ?>" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome_avaliador" class="form-label">Seu Nome Completo *</label>
                                    <input type="text" class="form-control" id="nome_avaliador" name="nome_avaliador" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="linkedin_avaliador" class="form-label">Seu Perfil do LinkedIn (opcional)</label>
                                    <input type="url" class="form-control" id="linkedin_avaliador" name="linkedin_avaliador" placeholder="https://www.linkedin.com/in/seu-perfil">
                                    <small class="text-muted">Adicione seu perfil para dar mais credibilidade à sua avaliação</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Pontuação *</label>
                                <div class="rating-stars">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pontuacao" id="pontuacao1" value="1" required>
                                        <label class="form-check-label" for="pontuacao1">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pontuacao" id="pontuacao2" value="2">
                                        <label class="form-check-label" for="pontuacao2">
                                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pontuacao" id="pontuacao3" value="3">
                                        <label class="form-check-label" for="pontuacao3">
                                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pontuacao" id="pontuacao4" value="4">
                                        <label class="form-check-label" for="pontuacao4">
                                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="pontuacao" id="pontuacao5" value="5" checked>
                                        <label class="form-check-label" for="pontuacao5">
                                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="avaliacao" class="form-label">Sua Avaliação *</label>
                                <textarea class="form-control" id="avaliacao" name="avaliacao" rows="6" required></textarea>
                                <small class="text-muted">Descreva sua experiência trabalhando com <?php echo htmlspecialchars($talento['nome']); ?>. Seja específico e construtivo.</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="publica" name="publica" checked>
                                <label class="form-check-label" for="publica">
                                    Quero avaliar este perfil publicamente
                                </label>
                                <small class="form-text text-muted d-block">Se marcado, seu nome e avaliação serão exibidos no perfil público do talento após aprovação.</small>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="termos" required>
                                <label class="form-check-label" for="termos">
                                    Confirmo que conheço <?php echo htmlspecialchars($talento['nome']); ?> profissionalmente e que as informações fornecidas são verdadeiras. *
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

.rating-stars .form-check-input {
    display: none;
}

.rating-stars .form-check-label {
    color: #ffc107;
    cursor: pointer;
}

.rating-stars .form-check-input:checked ~ .form-check-label {
    color: #6c757d;
}
</style>

<script>
// Script para atualizar visualmente a seleção de estrelas
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('input[name="pontuacao"]');
    
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const selectedValue = parseInt(this.value);
            
            ratingInputs.forEach(otherInput => {
                const otherValue = parseInt(otherInput.value);
                const stars = otherInput.nextElementSibling.querySelectorAll('i');
                
                stars.forEach(star => {
                    if (otherValue <= selectedValue) {
                        star.classList.add('text-warning');
                    } else {
                        star.classList.remove('text-warning');
                    }
                });
            });
        });
    });
    
    // Inicializar com a pontuação 5 selecionada
    document.getElementById('pontuacao5').dispatchEvent(new Event('change'));
});
</script>
