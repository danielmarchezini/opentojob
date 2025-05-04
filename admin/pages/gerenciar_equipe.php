<?php
// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "<script>window.location.href = '" . SITE_URL . "/admin/login.php';</script>";
    exit;
}

// Processar exclusão
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    
    try {
        $db = Database::getInstance();
        $db->execute("DELETE FROM equipe WHERE id = ?", [$id]);
        
        $_SESSION['mensagem'] = "Membro da equipe excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
    } catch (Exception $e) {
        $_SESSION['mensagem'] = "Erro ao excluir membro da equipe: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
    
    echo "<script>window.location.href = '" . SITE_URL . "/admin/?page=gerenciar_equipe';</script>";
    exit;
}

// Processar alteração de status (ativar/desativar)
if (isset($_GET['status']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = ($_GET['status'] == 'ativar') ? 1 : 0;
    
    try {
        $db = Database::getInstance();
        $db->execute("UPDATE equipe SET ativo = ? WHERE id = ?", [$status, $id]);
        
        $mensagem = ($status == 1) ? "Membro da equipe ativado com sucesso!" : "Membro da equipe desativado com sucesso!";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['tipo_mensagem'] = "success";
    } catch (Exception $e) {
        $_SESSION['mensagem'] = "Erro ao alterar status do membro da equipe: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
    
    echo "<script>window.location.href = '" . SITE_URL . "/admin/?page=gerenciar_equipe';</script>";
    exit;
}

// Buscar membros da equipe
$db = Database::getInstance();
$membros = $db->fetchAll("SELECT * FROM equipe ORDER BY ordem ASC, nome ASC");
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Equipe</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Equipe</li>
    </ol>
    
    <?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?> alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        unset($_SESSION['tipo_mensagem']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Membros da Equipe
            <a href="<?php echo SITE_URL; ?>/admin/?page=editar_membro_equipe" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-plus"></i> Adicionar Novo Membro
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($membros)): ?>
                <div class="alert alert-info">
                    Nenhum membro da equipe cadastrado. Clique em "Adicionar Novo Membro" para começar.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="tabelaEquipe">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nome</th>
                                <th>Profissão/Cargo</th>
                                <th>LinkedIn</th>
                                <th>Ordem</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($membros as $membro): ?>
                            <tr>
                                <td class="text-center">
                                    <?php if (!empty($membro['foto']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $membro['foto'])): ?>
                                        <img src="<?php echo SITE_URL . '/' . $membro['foto']; ?>" alt="Foto de <?php echo htmlspecialchars($membro['nome']); ?>" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                    <?php else: ?>
                                        <i class="fas fa-user fa-2x text-secondary"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($membro['nome']); ?></td>
                                <td><?php echo htmlspecialchars($membro['profissao']); ?></td>
                                <td>
                                    <?php if (!empty($membro['linkedin'])): ?>
                                        <a href="<?php echo htmlspecialchars($membro['linkedin']); ?>" target="_blank">
                                            <i class="fab fa-linkedin"></i> Ver Perfil
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $membro['ordem']; ?></td>
                                <td>
                                    <?php if ($membro['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo SITE_URL; ?>/admin/?page=editar_membro_equipe&id=<?php echo $membro['id']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($membro['ativo']): ?>
                                            <a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_equipe&id=<?php echo $membro['id']; ?>&status=desativar" class="btn btn-warning btn-sm" title="Desativar" onclick="return confirm('Tem certeza que deseja desativar este membro da equipe?');">
                                                <i class="fas fa-eye-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_equipe&id=<?php echo $membro['id']; ?>&status=ativar" class="btn btn-success btn-sm" title="Ativar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_equipe&excluir=<?php echo $membro['id']; ?>" class="btn btn-danger btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este membro da equipe? Esta ação não pode ser desfeita.');">
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

<script>
$(document).ready(function() {
    $('#tabelaEquipe').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
        },
        responsive: true
    });
});
</script>
