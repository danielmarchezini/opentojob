<?php
// Verificar se o usuário está logado como administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/?route=acesso_negado');
    exit;
}

// Verificar se foi especificado um ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . SITE_URL . '/?route=gerenciar_perfis_linkedin');
    exit;
}

$id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';

// Buscar dados do perfil
try {
    $perfil = $db->fetch("SELECT * FROM perfis_linkedin WHERE id = :id", ['id' => $id]);
    
    if (!$perfil) {
        header('Location: ' . SITE_URL . '/?route=gerenciar_perfis_linkedin');
        exit;
    }
    
    // Preencher variáveis com os dados do perfil
    $nome = $perfil['nome'];
    $foto_atual = $perfil['foto'];
    $assunto = $perfil['assunto'];
    $link_perfil = $perfil['link_perfil'];
    $status = $perfil['status'];
    $destaque = $perfil['destaque'];
    
} catch (Exception $e) {
    header('Location: ' . SITE_URL . '/?route=gerenciar_perfis_linkedin');
    exit;
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar e processar os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $link_perfil = trim($_POST['link_perfil'] ?? '');
    $status = isset($_POST['status']) && $_POST['status'] === 'ativo' ? 'ativo' : 'inativo';
    $destaque = isset($_POST['destaque']) && $_POST['destaque'] === '1' ? 1 : 0;
    
    $erros = [];
    
    // Validar campos obrigatórios
    if (empty($nome)) {
        $erros[] = 'O nome do perfil é obrigatório';
    }
    
    if (empty($assunto)) {
        $erros[] = 'O assunto principal é obrigatório';
    }
    
    if (empty($link_perfil)) {
        $erros[] = 'O link do perfil é obrigatório';
    } elseif (!filter_var($link_perfil, FILTER_VALIDATE_URL)) {
        $erros[] = 'Por favor, informe um link válido';
    } elseif (strpos($link_perfil, 'linkedin.com/') === false) {
        $erros[] = 'O link deve ser de um perfil do LinkedIn';
    }
    
    // Se não houver erros, processar o upload da foto e atualizar os dados
    if (empty($erros)) {
        try {
            // Iniciar transação
            $db->beginTransaction();
            
            // Nome do arquivo da foto
            $foto = $foto_atual;
            
            // Processar upload da nova foto, se houver
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $arquivo_temporario = $_FILES['foto']['tmp_name'];
                $nome_arquivo = $_FILES['foto']['name'];
                $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
                
                // Verificar se a extensão é permitida
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($extensao, $extensoes_permitidas)) {
                    // Gerar um nome único para o arquivo
                    $nova_foto = 'perfil_' . time() . '_' . uniqid() . '.' . $extensao;
                    
                    // Caminho para salvar a foto
                    $diretorio_destino = dirname(dirname(dirname(__FILE__))) . '/uploads/perfis_linkedin/';
                    
                    // Criar o diretório se não existir
                    if (!is_dir($diretorio_destino)) {
                        mkdir($diretorio_destino, 0755, true);
                    }
                    
                    // Mover o arquivo para o diretório de destino
                    if (move_uploaded_file($arquivo_temporario, $diretorio_destino . $nova_foto)) {
                        // Se o upload for bem-sucedido, excluir a foto antiga (se não for a padrão)
                        if ($foto_atual !== 'default-profile.jpg' && file_exists($diretorio_destino . $foto_atual)) {
                            unlink($diretorio_destino . $foto_atual);
                        }
                        
                        // Atualizar o nome da foto
                        $foto = $nova_foto;
                    } else {
                        throw new Exception('Erro ao fazer upload da foto');
                    }
                } else {
                    $erros[] = 'Formato de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF são aceitos.';
                }
            }
            
            // Se não houver erros no upload, atualizar os dados no banco
            if (empty($erros)) {
                $sql = "UPDATE perfis_linkedin 
                        SET nome = :nome, foto = :foto, assunto = :assunto, link_perfil = :link_perfil, 
                            status = :status, destaque = :destaque, data_atualizacao = NOW()
                        WHERE id = :id";
                
                $params = [
                    'id' => $id,
                    'nome' => $nome,
                    'foto' => $foto,
                    'assunto' => $assunto,
                    'link_perfil' => $link_perfil,
                    'status' => $status,
                    'destaque' => $destaque
                ];
                
                $db->execute($sql, $params);
                
                // Confirmar transação
                $db->commit();
                
                $mensagem = 'Perfil atualizado com sucesso!';
                $tipo_mensagem = 'success';
                
                // Atualizar a variável foto_atual para exibição correta
                $foto_atual = $foto;
            } else {
                // Reverter transação em caso de erro
                $db->rollBack();
                $mensagem = 'Por favor, corrija os seguintes erros:<ul><li>' . implode('</li><li>', $erros) . '</li></ul>';
                $tipo_mensagem = 'danger';
            }
        } catch (Exception $e) {
            // Reverter transação em caso de exceção
            $db->rollBack();
            $mensagem = 'Erro ao atualizar perfil: ' . $e->getMessage();
            $tipo_mensagem = 'danger';
            error_log('Erro ao atualizar perfil do LinkedIn: ' . $e->getMessage());
        }
    } else {
        $mensagem = 'Por favor, corrija os seguintes erros:<ul><li>' . implode('</li><li>', $erros) . '</li></ul>';
        $tipo_mensagem = 'danger';
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Editar Perfil do LinkedIn</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin">Gerenciar Perfis do LinkedIn</a></li>
        <li class="breadcrumb-item active">Editar Perfil</li>
    </ol>
    
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        
        <?php if ($tipo_mensagem === 'success'): ?>
            <div class="mb-4">
                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Voltar para a lista de perfis
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fab fa-linkedin me-1"></i>
            Editar Perfil #<?php echo $id; ?>
        </div>
        <div class="card-body">
            <form action="<?php echo SITE_URL; ?>/?route=editar_perfil_linkedin&id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome do Perfil <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars((string)$nome); ?>" required>
                        <div class="invalid-feedback">
                            Por favor, informe o nome do perfil.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="assunto" class="form-label">Assunto Principal <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="assunto" name="assunto" value="<?php echo htmlspecialchars((string)$assunto); ?>" placeholder="Ex: Carreira em Tecnologia, Empregabilidade" required>
                        <div class="invalid-feedback">
                            Por favor, informe o assunto principal do perfil.
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="link_perfil" class="form-label">Link do Perfil no LinkedIn <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" id="link_perfil" name="link_perfil" value="<?php echo htmlspecialchars((string)$link_perfil); ?>" placeholder="https://www.linkedin.com/in/nome-do-perfil/" required>
                    <div class="invalid-feedback">
                        Por favor, informe um link válido do LinkedIn.
                    </div>
                    <div class="form-text">
                        Exemplo: https://www.linkedin.com/in/nome-do-perfil/
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="foto" class="form-label">Foto do Perfil</label>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <img src="<?php echo SITE_URL; ?>/uploads/perfis_linkedin/<?php echo htmlspecialchars((string)$foto_atual); ?>" 
                                     alt="<?php echo htmlspecialchars((string)$nome); ?>" 
                                     class="img-thumbnail" 
                                     style="max-width: 200px; max-height: 200px;">
                                <div class="form-text">
                                    Foto atual
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <div class="form-text">
                                Formatos aceitos: JPG, JPEG, PNG, GIF. Tamanho recomendado: 400x400 pixels.<br>
                                Deixe em branco para manter a foto atual.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_ativo" value="ativo" <?php echo $status === 'ativo' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status_ativo">
                                Ativo
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_inativo" value="inativo" <?php echo $status === 'inativo' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status_inativo">
                                Inativo
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Destaque</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="destaque" id="destaque" value="1" <?php echo $destaque ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="destaque">
                                Destacar este perfil na lista
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin" class="btn btn-secondary me-2">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    
    // Preview da imagem
    var fotoInput = document.getElementById('foto');
    if (fotoInput) {
        fotoInput.addEventListener('change', function() {
            var previewContainer = document.getElementById('foto-preview');
            
            // Criar o container de preview se não existir
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.id = 'foto-preview';
                previewContainer.className = 'mt-2';
                this.parentNode.appendChild(previewContainer);
            }
            
            // Limpar preview anterior
            previewContainer.innerHTML = '';
            
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    previewContainer.appendChild(img);
                    
                    var label = document.createElement('div');
                    label.className = 'form-text';
                    label.textContent = 'Nova foto (prévia)';
                    previewContainer.appendChild(label);
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
})();
</script>
