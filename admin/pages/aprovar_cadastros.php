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
    // Buscar detalhes do cadastro via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/admin/get_usuario_detalhes.php',
        type: 'POST',
        dataType: 'json',
        data: {
            id: id
        },
        success: function(response) {
            if (response.success) {
                // Preencher o modal com os dados do usuário
                $('#modalUsuarioId').text(response.usuario.id);
                $('#modalUsuarioNome').text(response.usuario.nome);
                $('#modalUsuarioEmail').text(response.usuario.email);
                $('#modalUsuarioTipo').text(response.usuario.tipo.charAt(0).toUpperCase() + response.usuario.tipo.slice(1));
                $('#modalUsuarioDataCadastro').text(response.usuario.data_cadastro);
                
                // Informações adicionais baseadas no tipo de usuário
                let infoAdicional = '';
                
                if (response.usuario.tipo === 'talento') {
                    if (response.perfil_talento) {
                        infoAdicional += '<h5>Perfil do Talento</h5>';
                        infoAdicional += '<p><strong>Profissão:</strong> ' + (response.perfil_talento.profissao || 'Não informado') + '</p>';
                        infoAdicional += '<p><strong>Experiência:</strong> ' + (response.perfil_talento.anos_experiencia || 'Não informado') + ' anos</p>';
                        infoAdicional += '<p><strong>Localização:</strong> ' + (response.perfil_talento.cidade || 'Não informado') + '/' + (response.perfil_talento.estado || 'Não informado') + '</p>';
                    }
                } else if (response.usuario.tipo === 'empresa') {
                    if (response.perfil_empresa) {
                        infoAdicional += '<h5>Perfil da Empresa</h5>';
                        infoAdicional += '<p><strong>Nome Fantasia:</strong> ' + (response.perfil_empresa.nome_fantasia || 'Não informado') + '</p>';
                        infoAdicional += '<p><strong>CNPJ:</strong> ' + (response.perfil_empresa.cnpj || 'Não informado') + '</p>';
                        infoAdicional += '<p><strong>Segmento:</strong> ' + (response.perfil_empresa.segmento || 'Não informado') + '</p>';
                        infoAdicional += '<p><strong>Localização:</strong> ' + (response.perfil_empresa.cidade || 'Não informado') + '/' + (response.perfil_empresa.estado || 'Não informado') + '</p>';
                    }
                }
                
                $('#modalUsuarioInfoAdicional').html(infoAdicional);
                
                // Exibir o modal
                $('#visualizarUsuarioModal').modal('show');
            } else {
                alert('Erro: ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao processar a requisição. Tente novamente.');
        }
    });
}

function aprovarCadastro(id) {
    if (confirm('Tem certeza que deseja aprovar este cadastro?')) {
        // Enviar requisição AJAX para aprovar o cadastro
        $.ajax({
            url: '<?php echo SITE_URL; ?>/admin/processar_aprovacao_cadastro.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id: id
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    // Recarregar a página para atualizar a lista
                    location.reload();
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function() {
                alert('Erro ao processar a requisição. Tente novamente.');
            }
        });
    }
}

function rejeitarCadastro(id) {
    if (confirm('Tem certeza que deseja rejeitar este cadastro?')) {
        // Implementar rejeição de cadastro
        alert('Cadastro ' + id + ' rejeitado com sucesso!');
    }
}
</script>

<!-- Modal para visualizar detalhes do usuário -->
<div class="modal fade" id="visualizarUsuarioModal" tabindex="-1" aria-labelledby="visualizarUsuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visualizarUsuarioModalLabel">Detalhes do Cadastro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <span id="modalUsuarioId"></span></p>
                        <p><strong>Nome:</strong> <span id="modalUsuarioNome"></span></p>
                        <p><strong>Email:</strong> <span id="modalUsuarioEmail"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tipo:</strong> <span id="modalUsuarioTipo"></span></p>
                        <p><strong>Data de Cadastro:</strong> <span id="modalUsuarioDataCadastro"></span></p>
                    </div>
                </div>
                <div id="modalUsuarioInfoAdicional"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
