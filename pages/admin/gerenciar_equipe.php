<?php
// Verificar se o usuário está logado como administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar exclusão
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $db->query("DELETE FROM equipe WHERE id = :id", ['id' => $id]);
    $mensagem = "Membro da equipe excluído com sucesso!";
    $tipo_mensagem = "success";
}

// Processar alteração de status
if (isset($_GET['status']) && is_numeric($_GET['status']) && isset($_GET['valor'])) {
    $id = (int)$_GET['status'];
    $valor = $_GET['valor'] == '1' ? 1 : 0;
    $db->query("UPDATE equipe SET ativo = :valor WHERE id = :id", [
        'id' => $id,
        'valor' => $valor
    ]);
    $mensagem = "Status atualizado com sucesso!";
    $tipo_mensagem = "success";
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome = trim($_POST['nome']);
    $profissao = trim($_POST['profissao']);
    $linkedin = trim($_POST['linkedin']);
    $ordem = isset($_POST['ordem']) ? (int)$_POST['ordem'] : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($profissao)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        $tipo_mensagem = "danger";
    } else {
        // Upload de foto
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $upload_dir = ROOT_PATH . '/uploads/equipe/';
            
            // Criar diretório se não existir
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['foto']['name']);
            $upload_file = $upload_dir . $file_name;
            
            // Verificar se é uma imagem válida
            $check = getimagesize($_FILES['foto']['tmp_name']);
            if ($check !== false) {
                // Mover arquivo para o diretório de uploads
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_file)) {
                    $foto = 'uploads/equipe/' . $file_name;
                }
            }
        }
        
        // Inserir ou atualizar registro
        if ($id > 0) {
            // Atualizar registro existente
            $sql = "UPDATE equipe SET 
                    nome = :nome, 
                    profissao = :profissao, 
                    linkedin = :linkedin, 
                    ordem = :ordem, 
                    ativo = :ativo";
            
            $params = [
                'id' => $id,
                'nome' => $nome,
                'profissao' => $profissao,
                'linkedin' => $linkedin,
                'ordem' => $ordem,
                'ativo' => $ativo
            ];
            
            // Adicionar foto à query apenas se uma nova foto foi enviada
            if ($foto) {
                $sql .= ", foto = :foto";
                $params['foto'] = $foto;
            }
            
            $sql .= " WHERE id = :id";
            
            $db->query($sql, $params);
            $mensagem = "Membro da equipe atualizado com sucesso!";
        } else {
            // Inserir novo registro
            $sql = "INSERT INTO equipe (nome, profissao, foto, linkedin, ordem, ativo) 
                    VALUES (:nome, :profissao, :foto, :linkedin, :ordem, :ativo)";
            
            $db->query($sql, [
                'nome' => $nome,
                'profissao' => $profissao,
                'foto' => $foto,
                'linkedin' => $linkedin,
                'ordem' => $ordem,
                'ativo' => $ativo
            ]);
            $mensagem = "Membro da equipe adicionado com sucesso!";
        }
        
        $tipo_mensagem = "success";
        
        // Redirecionar para limpar o formulário
        header('Location: ' . SITE_URL . '/?route=admin/gerenciar_equipe&mensagem=' . urlencode($mensagem) . '&tipo=' . $tipo_mensagem);
        exit;
    }
}

// Verificar se é uma edição
$membro = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $membro = $db->fetch("SELECT * FROM equipe WHERE id = :id", ['id' => $id]);
}

// Listar todos os membros da equipe
$membros = $db->fetchAll("SELECT * FROM equipe ORDER BY ordem ASC, nome ASC");

// Exibir mensagem de feedback
if (isset($_GET['mensagem'])) {
    $mensagem = $_GET['mensagem'];
    $tipo_mensagem = $_GET['tipo'] ?? 'info';
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gerenciar Equipe</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMembro">
            <i class="fas fa-plus"></i> Adicionar Membro
        </button>
    </div>
    
    <?php if (isset($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Membros da Equipe</h5>
        </div>
        <div class="card-body">
            <?php if (empty($membros)): ?>
            <div class="alert alert-info">
                Nenhum membro da equipe cadastrado. Utilize o botão "Adicionar Membro" para começar.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Profissão</th>
                            <th>LinkedIn</th>
                            <th>Ordem</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membros as $m): ?>
                        <tr>
                            <td>
                                <?php if (!empty($m['foto'])): ?>
                                <img src="<?php echo SITE_URL . '/' . $m['foto']; ?>" alt="<?php echo htmlspecialchars($m['nome']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 4px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($m['nome']); ?></td>
                            <td><?php echo htmlspecialchars($m['profissao']); ?></td>
                            <td>
                                <?php if (!empty($m['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($m['linkedin']); ?>" target="_blank">
                                    <i class="fab fa-linkedin"></i> Ver perfil
                                </a>
                                <?php else: ?>
                                <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $m['ordem']; ?></td>
                            <td>
                                <?php if ($m['ativo']): ?>
                                <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo SITE_URL; ?>/?route=admin/gerenciar_equipe&editar=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($m['ativo']): ?>
                                    <a href="<?php echo SITE_URL; ?>/?route=admin/gerenciar_equipe&status=<?php echo $m['id']; ?>&valor=0" class="btn btn-sm btn-warning" title="Desativar">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>/?route=admin/gerenciar_equipe&status=<?php echo $m['id']; ?>&valor=1" class="btn btn-sm btn-success" title="Ativar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo SITE_URL; ?>/?route=admin/gerenciar_equipe&excluir=<?php echo $m['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este membro?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para adicionar/editar membro -->
<div class="modal fade" id="modalMembro" tabindex="-1" aria-labelledby="modalMembroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?php echo SITE_URL; ?>/?route=admin/gerenciar_equipe" method="post" enctype="multipart/form-data">
                <?php if ($membro): ?>
                <input type="hidden" name="id" value="<?php echo $membro['id']; ?>">
                <?php endif; ?>
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMembroLabel">
                        <?php echo $membro ? 'Editar Membro da Equipe' : 'Adicionar Membro da Equipe'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   value="<?php echo $membro ? htmlspecialchars($membro['nome']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="profissao" class="form-label">Profissão/Cargo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="profissao" name="profissao" required
                                   value="<?php echo $membro ? htmlspecialchars($membro['profissao']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="foto" class="form-label">Foto</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <?php if ($membro && !empty($membro['foto'])): ?>
                            <div class="mt-2">
                                <img src="<?php echo SITE_URL . '/' . $membro['foto']; ?>" alt="Foto atual" class="img-thumbnail" style="max-height: 100px;">
                                <p class="small text-muted">Foto atual. Envie uma nova imagem para substituí-la.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="linkedin" class="form-label">Perfil LinkedIn</label>
                            <input type="url" class="form-control" id="linkedin" name="linkedin" placeholder="https://www.linkedin.com/in/seu-perfil"
                                   value="<?php echo $membro ? htmlspecialchars($membro['linkedin']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ordem" class="form-label">Ordem de Exibição</label>
                            <input type="number" class="form-control" id="ordem" name="ordem" min="0" 
                                   value="<?php echo $membro ? $membro['ordem'] : '0'; ?>">
                            <div class="form-text">Números menores aparecem primeiro. Use 0 para ordenação alfabética.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                       <?php echo (!$membro || $membro['ativo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">
                                    Ativo (visível no site)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Abrir modal automaticamente se estiver editando
    <?php if ($membro): ?>
    var modal = new bootstrap.Modal(document.getElementById('modalMembro'));
    modal.show();
    <?php endif; ?>
});
</script>
