<?php
// Obter lista de cadastros pendentes
$db = Database::getInstance();
$pendentes = $db->fetchAll("
    SELECT * FROM usuarios 
    WHERE status = 'pendente'
    ORDER BY data_cadastro DESC
");
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Aprovar Cadastros</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Aprovar Cadastros</li>
    </ol>

    <!-- Cadastros Pendentes -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-check me-1"></i>
            Cadastros Pendentes
        </div>
        <div class="card-body">
            <?php if (empty($pendentes)): ?>
            <div class="alert alert-info">
                Não há cadastros pendentes de aprovação.
            </div>
            <?php else: ?>
            <table id="cadastrosTable" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendentes as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo $usuario['nome']; ?></td>
                            <td><?php echo $usuario['email']; ?></td>
                            <td><?php echo ucfirst($usuario['tipo']); ?></td>
                            <td><?php echo formatAdminDate($usuario['data_cadastro']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" onclick="visualizarCadastro(<?php echo $usuario['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" onclick="aprovarCadastro(<?php echo $usuario['id']; ?>)">
                                        <i class="fas fa-check"></i> Aprovar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="rejeitarCadastro(<?php echo $usuario['id']; ?>)">
                                        <i class="fas fa-times"></i> Rejeitar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables se houver cadastros pendentes
    if (document.getElementById('cadastrosTable')) {
        new DataTable('#cadastrosTable', {
            responsive: true,
            language: {
                url: '/open2w/assets/js/pt-BR.json',
            }
        });
    }
});

function visualizarCadastro(id) {
    // Implementar visualização de cadastro
    alert('Visualizar cadastro ' + id);
}

function aprovarCadastro(id) {
    if (confirm('Tem certeza que deseja aprovar este cadastro?')) {
        // Implementar aprovação de cadastro
        alert('Cadastro ' + id + ' aprovado com sucesso!');
    }
}

function rejeitarCadastro(id) {
    if (confirm('Tem certeza que deseja rejeitar este cadastro?')) {
        // Implementar rejeição de cadastro
        alert('Cadastro ' + id + ' rejeitado com sucesso!');
    }
}
</script>
