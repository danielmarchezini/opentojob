<?php
// Obter lista de usuários
$db = Database::getInstance();
$usuarios = $db->fetchAll("
    SELECT * FROM usuarios 
    ORDER BY data_cadastro DESC
");
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gerenciar Usuários</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Gerenciar Usuários</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
    <?php echo $_SESSION['flash_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php 
    // Limpar mensagem flash
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
endif; ?>

<section class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarUsuario">
                    <i class="fas fa-plus"></i> Adicionar Novo Usuário
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Usuários</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <input type="text" id="pesquisarUsuario" class="form-control float-right" placeholder="Buscar">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo $usuario['nome']; ?></td>
                            <td><?php echo $usuario['email']; ?></td>
                            <td><?php echo ucfirst($usuario['tipo']); ?></td>
                            <td><?php echo getStatusBadge($usuario['status'], 'usuario'); ?></td>
                            <td><?php echo formatAdminDate($usuario['data_cadastro']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" onclick="visualizarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>', '<?php echo $usuario['email']; ?>', '<?php echo $usuario['tipo']; ?>', '<?php echo $usuario['status']; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>', '<?php echo $usuario['email']; ?>', '<?php echo $usuario['tipo']; ?>', '<?php echo $usuario['status']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($usuario['status'] == 'ativo'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmarAcao('bloquear', <?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="confirmarAcao('ativar', <?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmarAcao('excluir', <?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal Adicionar Usuário -->
<div class="modal fade" id="modalAdicionarUsuario" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarUsuarioLabel">Adicionar Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/pages/processar_usuario.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo de Usuário</label>
                        <select class="form-control" id="tipo" name="tipo" required>
                            <option value="">Selecione</option>
                            <option value="admin">Administrador</option>
                            <option value="talento">Talento</option>
                            <option value="empresa">Empresa</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="ativo">Ativo</option>
                            <option value="pendente">Pendente</option>
                            <option value="inativo">Inativo</option>
                            <option value="bloqueado">Bloqueado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuário -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" role="dialog" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarUsuarioLabel">Editar Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/pages/processar_usuario.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="usuario_id" id="editar_usuario_id">
                    
                    <div class="form-group">
                        <label for="editar_nome">Nome</label>
                        <input type="text" class="form-control" id="editar_nome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_email">E-mail</label>
                        <input type="email" class="form-control" id="editar_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_senha">Nova Senha (deixe em branco para manter a atual)</label>
                        <input type="password" class="form-control" id="editar_senha" name="senha">
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_tipo">Tipo de Usuário</label>
                        <select class="form-control" id="editar_tipo" name="tipo" required>
                            <option value="admin">Administrador</option>
                            <option value="talento">Talento</option>
                            <option value="empresa">Empresa</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_status">Status</label>
                        <select class="form-control" id="editar_status" name="status" required>
                            <option value="ativo">Ativo</option>
                            <option value="pendente">Pendente</option>
                            <option value="inativo">Inativo</option>
                            <option value="bloqueado">Bloqueado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Usuário -->
<div class="modal fade" id="modalVisualizarUsuario" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarUsuarioLabel">Detalhes do Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="user-details">
                    <p><strong>Nome:</strong> <span id="visualizar_nome"></span></p>
                    <p><strong>E-mail:</strong> <span id="visualizar_email"></span></p>
                    <p><strong>Tipo:</strong> <span id="visualizar_tipo"></span></p>
                    <p><strong>Status:</strong> <span id="visualizar_status"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmação -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p id="mensagem_confirmacao"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_usuario.php" method="post">
                    <input type="hidden" name="acao" id="acao_confirmacao">
                    <input type="hidden" name="usuario_id" id="usuario_id_confirmacao">
                    <button type="submit" class="btn btn-danger">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Função para visualizar usuário
function visualizarUsuario(id, nome, email, tipo, status) {
    document.getElementById('visualizar_nome').textContent = nome;
    document.getElementById('visualizar_email').textContent = email;
    document.getElementById('visualizar_tipo').textContent = tipo.charAt(0).toUpperCase() + tipo.slice(1);
    document.getElementById('visualizar_status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
    
    $('#modalVisualizarUsuario').modal('show');
}

// Função para editar usuário
function editarUsuario(id, nome, email, tipo, status) {
    document.getElementById('editar_usuario_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_email').value = email;
    document.getElementById('editar_tipo').value = tipo;
    document.getElementById('editar_status').value = status;
    
    $('#modalEditarUsuario').modal('show');
}

// Função para confirmar ação (bloquear, ativar, excluir)
function confirmarAcao(acao, id, nome) {
    let mensagem = '';
    let titulo = '';
    
    if (acao === 'bloquear') {
        mensagem = `Tem certeza que deseja bloquear o usuário "${nome}"?`;
        titulo = 'Bloquear Usuário';
    } else if (acao === 'ativar') {
        mensagem = `Tem certeza que deseja ativar o usuário "${nome}"?`;
        titulo = 'Ativar Usuário';
    } else if (acao === 'excluir') {
        mensagem = `ATENÇÃO: Esta ação não pode ser desfeita. Tem certeza que deseja excluir o usuário "${nome}"?`;
        titulo = 'Excluir Usuário';
    }
    
    document.getElementById('modalConfirmacaoLabel').textContent = titulo;
    document.getElementById('mensagem_confirmacao').textContent = mensagem;
    document.getElementById('acao_confirmacao').value = acao;
    document.getElementById('usuario_id_confirmacao').value = id;
    
    const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
    modalConfirmacao.show();
}

// Função para pesquisar usuários na tabela
document.getElementById('pesquisarUsuario').addEventListener('keyup', function() {
    const termo = this.value.toLowerCase();
    const tabela = document.querySelector('table tbody');
    const linhas = tabela.querySelectorAll('tr');
    
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        if (texto.includes(termo)) {
            linha.style.display = '';
        } else {
            linha.style.display = 'none';
        }
    });
});
</script>
