<?php
// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "<script>window.location.href = '" . SITE_URL . "/admin/login.php';</script>";
    exit;
}

$db = Database::getInstance();
$membro = [
    'id' => '',
    'nome' => '',
    'profissao' => '',
    'subtitulo' => '',
    'comentarios' => '',
    'foto' => '',
    'linkedin' => '',
    'ordem' => 0,
    'ativo' => 1
];

// Verificar se é edição
$modo = 'novo';
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $membro_db = $db->fetchRow("SELECT * FROM equipe WHERE id = ?", [$id]);
    
    if ($membro_db) {
        $membro = $membro_db;
        $modo = 'editar';
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados
    $nome = trim($_POST['nome'] ?? '');
    $profissao = trim($_POST['profissao'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $comentarios = trim($_POST['comentarios'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $ordem = (int)($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório.";
    }
    
    if (empty($profissao)) {
        $erros[] = "A profissão/cargo é obrigatória.";
    }
    
    // Processar upload de foto, se houver
    $foto = $membro['foto']; // Manter a foto atual por padrão
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        // Registrar informações de depuração
        error_log('Iniciando upload de foto para membro da equipe');
        error_log('Nome do arquivo: ' . $_FILES['foto']['name']);
        error_log('Tamanho do arquivo: ' . $_FILES['foto']['size'] . ' bytes');
        
        // Definir o caminho do diretório de upload
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/equipe/';
        error_log('Diretório de upload: ' . $upload_dir);
        
        // Criar diretório se não existir
        if (!is_dir($upload_dir)) {
            error_log('Criando diretório: ' . $upload_dir);
            $created = mkdir($upload_dir, 0755, true);
            error_log('Diretório criado: ' . ($created ? 'Sim' : 'Não'));
        }
        
        $file_name = $_FILES['foto']['name'];
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Verificar extensão
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_ext)) {
            $erros[] = "Formato de arquivo não permitido. Use apenas JPG, JPEG, PNG ou GIF.";
            error_log('Extensão não permitida: ' . $file_ext);
        } else {
            // Gerar nome único para o arquivo
            $novo_nome = 'membro_' . time() . '_' . uniqid() . '.' . $file_ext;
            $destino = $upload_dir . $novo_nome;
            error_log('Destino do arquivo: ' . $destino);
            
            // Verificar permissões do diretório
            error_log('Permissões do diretório: ' . substr(sprintf('%o', fileperms($upload_dir)), -4));
            error_log('Diretório gravável: ' . (is_writable($upload_dir) ? 'Sim' : 'Não'));
            
            if (move_uploaded_file($file_tmp, $destino)) {
                error_log('Arquivo movido com sucesso para: ' . $destino);
                
                // Excluir foto antiga se existir
                if (!empty($membro['foto'])) {
                    $caminho_antigo = $_SERVER['DOCUMENT_ROOT'] . '/' . $membro['foto'];
                    error_log('Verificando arquivo antigo: ' . $caminho_antigo);
                    
                    if (file_exists($caminho_antigo)) {
                        error_log('Excluindo arquivo antigo: ' . $caminho_antigo);
                        unlink($caminho_antigo);
                    } else {
                        error_log('Arquivo antigo não encontrado: ' . $caminho_antigo);
                    }
                }
                
                $foto = 'uploads/equipe/' . $novo_nome;
                error_log('Caminho da foto salvo no banco: ' . $foto);
            } else {
                $erros[] = "Falha ao fazer upload da foto.";
                error_log('Falha ao mover o arquivo para: ' . $destino);
                error_log('Erro de upload: ' . error_get_last()['message']);
            }
        }
    }
    
    // Se não houver erros, salvar no banco
    if (empty($erros)) {
        try {
            if ($modo === 'editar') {
                // Atualizar membro existente
                $db->execute(
                    "UPDATE equipe SET nome = ?, profissao = ?, subtitulo = ?, comentarios = ?, foto = ?, linkedin = ?, ordem = ?, ativo = ? WHERE id = ?",
                    [$nome, $profissao, $subtitulo, $comentarios, $foto, $linkedin, $ordem, $ativo, $membro['id']]
                );
                
                $_SESSION['mensagem'] = "Membro da equipe atualizado com sucesso!";
            } else {
                // Inserir novo membro
                $db->execute(
                    "INSERT INTO equipe (nome, profissao, subtitulo, comentarios, foto, linkedin, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$nome, $profissao, $subtitulo, $comentarios, $foto, $linkedin, $ordem, $ativo]
                );
                
                $_SESSION['mensagem'] = "Membro da equipe adicionado com sucesso!";
            }
            
            $_SESSION['tipo_mensagem'] = "success";
            echo "<script>window.location.href = '" . SITE_URL . "/admin/?page=gerenciar_equipe';</script>";
            exit;
            
        } catch (Exception $e) {
            $erros[] = "Erro ao salvar membro da equipe: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo ($modo === 'editar') ? 'Editar' : 'Adicionar'; ?> Membro da Equipe</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_equipe">Gerenciar Equipe</a></li>
        <li class="breadcrumb-item active"><?php echo ($modo === 'editar') ? 'Editar' : 'Adicionar'; ?> Membro</li>
    </ol>
    
    <?php if (!empty($erros)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erro!</strong>
        <ul class="mb-0">
            <?php foreach ($erros as $erro): ?>
                <li><?php echo $erro; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-edit me-1"></i>
            <?php echo ($modo === 'editar') ? 'Editar' : 'Adicionar'; ?> Membro da Equipe
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars((string)$membro['nome']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profissao" class="form-label">Profissão/Cargo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="profissao" name="profissao" value="<?php echo htmlspecialchars((string)$membro['profissao']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subtitulo" class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" id="subtitulo" name="subtitulo" value="<?php echo htmlspecialchars((string)$membro['subtitulo'] ?? ''); ?>">
                            <div class="form-text">Breve descrição ou frase que aparecerá abaixo do nome</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comentarios" class="form-label">Comentários/Biografia</label>
                            <textarea class="form-control" id="comentarios" name="comentarios" rows="4"><?php echo htmlspecialchars((string)$membro['comentarios'] ?? ''); ?></textarea>
                            <div class="form-text">Informações adicionais sobre o membro que serão exibidas na página Sobre</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="linkedin" class="form-label">LinkedIn (URL)</label>
                            <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars((string)$membro['linkedin']); ?>" placeholder="https://www.linkedin.com/in/seu-perfil">
                            <div class="form-text">URL completa do perfil do LinkedIn (opcional)</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ordem" class="form-label">Ordem de Exibição</label>
                                    <input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo $membro['ordem']; ?>" min="0">
                                    <div class="form-text">Membros com menor número aparecem primeiro</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 mt-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo ($membro['ativo']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ativo">Membro Ativo</label>
                                    </div>
                                    <div class="form-text">Apenas membros ativos são exibidos na página Sobre</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <div class="form-text">Formatos aceitos: JPG, JPEG, PNG, GIF. Tamanho recomendado: 300x300px</div>
                        </div>
                        
                        <?php if (!empty($membro['foto'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Foto Atual</label>
                            <div class="text-center p-3 bg-light rounded">
                                <img src="<?php echo SITE_URL . '/' . $membro['foto']; ?>" alt="Foto atual" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                            <div class="form-text">Envie uma nova foto para substituir a atual</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_equipe" class="btn btn-secondary ms-2">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
