<?php
// Verificar se o formulário foi enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter instância do banco de dados
    $db = Database::getInstance();
    
    // Validar campos obrigatórios
    $nome_indicador = trim($_POST['nome_indicador'] ?? '');
    $email_indicador = trim($_POST['email_indicador'] ?? '');
    $nome_perfil = trim($_POST['nome_perfil'] ?? '');
    $link_perfil = trim($_POST['link_perfil'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem_indicacao = trim($_POST['mensagem'] ?? '');
    
    $erros = [];
    
    if (empty($nome_indicador)) {
        $erros[] = 'O seu nome é obrigatório';
    }
    
    if (empty($email_indicador)) {
        $erros[] = 'O seu e-mail é obrigatório';
    } elseif (!filter_var($email_indicador, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Por favor, informe um e-mail válido';
    }
    
    if (empty($nome_perfil)) {
        $erros[] = 'O nome do perfil é obrigatório';
    }
    
    if (empty($link_perfil)) {
        $erros[] = 'O link do perfil é obrigatório';
    } elseif (!filter_var($link_perfil, FILTER_VALIDATE_URL)) {
        $erros[] = 'Por favor, informe um link válido';
    } elseif (strpos($link_perfil, 'linkedin.com/') === false) {
        $erros[] = 'O link deve ser de um perfil do LinkedIn';
    }
    
    if (empty($assunto)) {
        $erros[] = 'O assunto principal do perfil é obrigatório';
    }
    
    // Se não houver erros, salvar a indicação
    if (empty($erros)) {
        try {
            $sql = "INSERT INTO indicacoes_perfis_linkedin 
                    (nome_indicador, email_indicador, nome_perfil, link_perfil, assunto, mensagem, status) 
                    VALUES (:nome_indicador, :email_indicador, :nome_perfil, :link_perfil, :assunto, :mensagem, 'pendente')";
            
            $params = [
                'nome_indicador' => $nome_indicador,
                'email_indicador' => $email_indicador,
                'nome_perfil' => $nome_perfil,
                'link_perfil' => $link_perfil,
                'assunto' => $assunto,
                'mensagem' => $mensagem_indicacao
            ];
            
            $db->execute($sql, $params);
            
            // Enviar e-mail para o administrador (opcional)
            // Aqui você pode adicionar código para enviar um e-mail notificando sobre a nova indicação
            
            $mensagem = 'Sua indicação foi enviada com sucesso! Irei analisar e, se aprovada, o perfil será adicionado à lista.';
            $tipo_mensagem = 'success';
            
            // Limpar os campos do formulário
            $nome_indicador = $email_indicador = $nome_perfil = $link_perfil = $assunto = $mensagem_indicacao = '';
            
        } catch (Exception $e) {
            $mensagem = 'Ocorreu um erro ao processar sua indicação. Por favor, tente novamente mais tarde.';
            $tipo_mensagem = 'danger';
            error_log('Erro ao salvar indicação de perfil: ' . $e->getMessage());
        }
    } else {
        $mensagem = 'Por favor, corrija os seguintes erros:<ul><li>' . implode('</li><li>', $erros) . '</li></ul>';
        $tipo_mensagem = 'danger';
    }
}
?>

<div class="linkedin-profiles-header">
    <div class="container-wide">
        <h1 class="profiles-title">Indicar um Perfil do LinkedIn</h1>
        <p class="profiles-subtitle">Conhece algum perfil que compartilha conteúdo relevante sobre empregabilidade? Indique para que eu possa adicionar à lista!</p>
    </div>
</div>

<section class="section-indicar-perfil py-5">
    <div class="container-wide">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fab fa-linkedin me-2"></i>Formulário de Indicação</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                                <?php echo $mensagem; ?>
                            </div>
                            
                            <?php if ($tipo_mensagem === 'success'): ?>
                                <div class="text-center my-4">
                                    <a href="<?php echo SITE_URL; ?>/?route=perfis_linkedin" class="btn btn-primary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar para Perfis do LinkedIn
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (empty($mensagem) || $tipo_mensagem !== 'success'): ?>
                            <form action="<?php echo SITE_URL; ?>/?route=indicar_perfil_linkedin" method="POST" class="needs-validation" novalidate>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nome_indicador" class="form-label">Seu Nome <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nome_indicador" name="nome_indicador" value="<?php echo htmlspecialchars($nome_indicador ?? ''); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor, informe seu nome.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email_indicador" class="form-label">Seu E-mail <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email_indicador" name="email_indicador" value="<?php echo htmlspecialchars($email_indicador ?? ''); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor, informe um e-mail válido.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nome_perfil" class="form-label">Nome do Perfil <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nome_perfil" name="nome_perfil" value="<?php echo htmlspecialchars($nome_perfil ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor, informe o nome do perfil.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="link_perfil" class="form-label">Link do Perfil no LinkedIn <span class="text-danger">*</span></label>
                                    <input type="url" class="form-control" id="link_perfil" name="link_perfil" value="<?php echo htmlspecialchars($link_perfil ?? ''); ?>" placeholder="https://www.linkedin.com/in/nome-do-perfil/" required>
                                    <div class="invalid-feedback">
                                        Por favor, informe um link válido do LinkedIn.
                                    </div>
                                    <div class="form-text">
                                        Exemplo: https://www.linkedin.com/in/nome-do-perfil/
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="assunto" class="form-label">Assunto Principal <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="assunto" name="assunto" value="<?php echo htmlspecialchars($assunto ?? ''); ?>" placeholder="Ex: Carreira em Tecnologia, Empregabilidade, Desenvolvimento Profissional" required>
                                    <div class="invalid-feedback">
                                        Por favor, informe o assunto principal do perfil.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mensagem" class="form-label">Por que você recomenda este perfil?</label>
                                    <textarea class="form-control" id="mensagem" name="mensagem" rows="3"><?php echo htmlspecialchars($mensagem_indicacao ?? ''); ?></textarea>
                                    <div class="form-text">
                                        Conte-nos por que você acha que este perfil seria útil para nossa comunidade.
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="termos" required>
                                    <label class="form-check-label" for="termos">
                                        Confirmo que este perfil compartilha conteúdo relevante sobre empregabilidade e desenvolvimento profissional.
                                    </label>
                                    <div class="invalid-feedback">
                                        Você precisa confirmar esta declaração.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo SITE_URL; ?>/?route=perfis_linkedin" class="btn btn-outline-secondary">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Indicação
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações</h5>
                    </div>
                    <div class="card-body">
                        <p>Sua indicação será analisada por mim antes de ser publicada.</p>
                        <p>Procuro perfis que:</p>
                        <ul>
                            <li>Compartilham conteúdo relevante sobre empregabilidade</li>
                            <li>Oferecem dicas práticas para quem está em busca de emprego</li>
                            <li>Discutem tendências do mercado de trabalho</li>
                            <li>Fornecem orientações sobre desenvolvimento profissional</li>
                        </ul>
                        <p>Agradeço sua contribuição para nossa comunidade!</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Perfis em Destaque</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Obter perfis em destaque
                        $perfis_destaque = $db->fetchAll("
                            SELECT nome, foto, link_perfil
                            FROM perfis_linkedin
                            WHERE status = 'ativo' AND destaque = 1
                            ORDER BY RAND()
                            LIMIT 3
                        ");
                        
                        if (empty($perfis_destaque)) {
                            echo '<p class="text-muted">Nenhum perfil em destaque no momento.</p>';
                        } else {
                            echo '<ul class="list-group list-group-flush">';
                            foreach ($perfis_destaque as $perfil) {
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo '<div class="d-flex align-items-center">';
                                echo '<img src="' . SITE_URL . '/uploads/perfis_linkedin/' . htmlspecialchars($perfil['foto']) . '" alt="' . htmlspecialchars($perfil['nome']) . '" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">';
                                echo '<span>' . htmlspecialchars($perfil['nome']) . '</span>';
                                echo '</div>';
                                echo '<a href="' . htmlspecialchars($perfil['link_perfil']) . '" class="btn btn-sm btn-linkedin" target="_blank">';
                                echo '<i class="fab fa-linkedin"></i>';
                                echo '</a>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        ?>
                        
                        <div class="mt-3">
                            <a href="<?php echo SITE_URL; ?>/?route=perfis_linkedin" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users me-2"></i>Ver todos os perfis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Validação do formulário
(function() {
    'use strict';
    
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Validação adicional para o link do LinkedIn
    var linkInput = document.getElementById('link_perfil');
    if (linkInput) {
        linkInput.addEventListener('blur', function() {
            if (this.value && !this.value.includes('linkedin.com/')) {
                this.setCustomValidity('O link deve ser de um perfil do LinkedIn');
            } else {
                this.setCustomValidity('');
            }
        });
    }
})();
</script>

<style>
.linkedin-profiles-header {
    background-color: #0077b5;
    color: white;
    padding: 60px 0;
    text-align: center;
    margin-bottom: 30px;
}

.profiles-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.profiles-subtitle {
    font-size: 1.2rem;
    max-width: 800px;
    margin: 0 auto;
}

.btn-linkedin {
    background-color: #0077b5;
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.btn-linkedin:hover {
    background-color: #005e8d;
    color: white;
}
</style>
