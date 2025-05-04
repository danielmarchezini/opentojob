<?php
// Verificar se o usuário está logado como admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . SITE_URL . "/admin/login.php");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se há mensagem flash
$flash_message = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Obter inscritos da newsletter
try {
    $inscritos = $db->fetchAll("
        SELECT id, email, nome, data_inscricao, status, ip_inscricao
        FROM newsletter_inscritos
        ORDER BY data_inscricao DESC
    ");
} catch (PDOException $e) {
    error_log('Erro ao carregar inscritos da newsletter: ' . $e->getMessage());
    $inscritos = [];
}

// Função para formatar data
function formatarData($data) {
    if (!$data) return 'N/A';
    return date('d/m/Y H:i', strtotime($data));
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gerenciar Newsletter</h1>
    
    <?php if (!empty($flash_message)): ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Inscritos</h6>
            <div class="dropdown no-arrow">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalExportarInscritos">
                    <i class="fas fa-file-export fa-sm"></i> Exportar Lista
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="inscritosTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Nome</th>
                            <th>Data de Inscrição</th>
                            <th>Status</th>
                            <th>IP</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscritos as $inscrito): ?>
                            <tr>
                                <td><?php echo $inscrito['id']; ?></td>
                                <td><?php echo htmlspecialchars($inscrito['email']); ?></td>
                                <td><?php echo htmlspecialchars($inscrito['nome'] ?? 'N/A'); ?></td>
                                <td><?php echo formatarData($inscrito['data_inscricao']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $inscrito['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($inscrito['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($inscrito['ip_inscricao'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="alterarStatus(<?php echo $inscrito['id']; ?>, '<?php echo $inscrito['status'] === 'ativo' ? 'inativo' : 'ativo'; ?>')">
                                            <i class="fas fa-<?php echo $inscrito['status'] === 'ativo' ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $inscrito['id']; ?>, '<?php echo addslashes($inscrito['email']); ?>')">
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
</div>

<!-- Modal para Exportar Inscritos -->
<div class="modal fade" id="modalExportarInscritos" tabindex="-1" role="dialog" aria-labelledby="modalExportarInscritosLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExportarInscritosLabel">Exportar Lista de Inscritos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/processar_newsletter.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="exportar">
                    
                    <div class="form-group mb-3">
                        <label for="formato">Formato de Exportação</label>
                        <select class="form-control" id="formato" name="formato" required>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Status dos Inscritos</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_todos" value="todos" checked>
                            <label class="form-check-label" for="status_todos">
                                Todos
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_ativos" value="ativo">
                            <label class="form-check-label" for="status_ativos">
                                Apenas Ativos
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_inativos" value="inativo">
                            <label class="form-check-label" for="status_inativos">
                                Apenas Inativos
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Exportar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Exclusão -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmarExclusaoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarExclusaoLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o inscrito <span id="email_inscrito"></span>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?php echo SITE_URL; ?>/admin/processar_newsletter.php" method="post">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="inscrito_id" id="inscrito_id" value="">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#inscritosTable').DataTable({
            "language": {
                "url": "/open2w/assets/js/pt-BR.json"
            },
            "order": [[3, "desc"]] // Ordenar por data de inscrição (decrescente)
        });
    }
});

// Função para confirmar exclusão
function confirmarExclusao(id, email) {
    document.getElementById('inscrito_id').value = id;
    document.getElementById('email_inscrito').textContent = email;
    
    var modal = new bootstrap.Modal(document.getElementById('modalConfirmarExclusao'));
    modal.show();
}

// Função para alterar status
function alterarStatus(id, novoStatus) {
    // Criar formulário dinâmico
    var form = document.createElement('form');
    form.method = 'post';
    form.action = '<?php echo SITE_URL; ?>/admin/processar_newsletter.php';
    
    var acaoInput = document.createElement('input');
    acaoInput.type = 'hidden';
    acaoInput.name = 'acao';
    acaoInput.value = 'alterar_status';
    
    var idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'inscrito_id';
    idInput.value = id;
    
    var statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'novo_status';
    statusInput.value = novoStatus;
    
    form.appendChild(acaoInput);
    form.appendChild(idInput);
    form.appendChild(statusInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>
