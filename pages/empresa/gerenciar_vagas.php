<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa') && !Auth::checkUserType('admin')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Verificar se o sistema de vagas internas está ativo
$db = Database::getInstance();
$sistema_vagas_internas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_vagas_internas_ativo'");

// Se o sistema de vagas internas não estiver ativo, redirecionar para o painel
if (!$sistema_vagas_internas_ativo && !Auth::checkUserType('admin')) {
    $_SESSION['flash_message'] = "O sistema de vagas internas está temporariamente desativado. Entre em contato com o administrador para mais informações.";
    $_SESSION['flash_type'] = "warning";
    header('Location: ' . SITE_URL . '/?route=painel_empresa');
    exit;
}

// Instância do banco de dados já obtida acima

// Definir filtros
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Construir condição SQL para filtros
$condicao = "v.empresa_id = :empresa_id";
$params = ['empresa_id' => $usuario_id];

if (!empty($status_filtro)) {
    $condicao .= " AND v.status = :status";
    $params['status'] = $status_filtro;
}

if (!empty($busca)) {
    $condicao .= " AND (v.titulo LIKE :busca OR v.descricao LIKE :busca OR v.cidade LIKE :busca)";
    $params['busca'] = "%{$busca}%";
}

// Obter vagas
try {
    $vagas = $db->fetchAll("
        SELECT v.*, 
               (SELECT COUNT(*) FROM candidaturas c WHERE c.vaga_id = v.id) as total_candidaturas
        FROM vagas v
        WHERE $condicao
        ORDER BY v.data_publicacao DESC
    ", $params);
} catch (PDOException $e) {
    error_log("Erro ao buscar vagas: " . $e->getMessage());
    $_SESSION['flash_message'] = "Erro ao carregar vagas: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $vagas = [];
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

// Função para obter badge de status
function getBadgeStatus($status) {
    switch ($status) {
        case 'ativa':
            return '<span class="badge bg-success">Ativa</span>';
        case 'inativa':
            return '<span class="badge bg-warning">Inativa</span>';
        case 'encerrada':
            return '<span class="badge bg-secondary">Encerrada</span>';
        case 'rascunho':
            return '<span class="badge bg-info">Rascunho</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

// Função para obter badge de tipo de contrato
function getBadgeTipoContrato($tipo) {
    switch ($tipo) {
        case 'CLT':
            return '<span class="badge bg-primary">CLT</span>';
        case 'PJ':
            return '<span class="badge bg-success">PJ</span>';
        case 'Estágio':
            return '<span class="badge bg-info">Estágio</span>';
        case 'Freelancer':
            return '<span class="badge bg-warning">Freelancer</span>';
        case 'Temporário':
            return '<span class="badge bg-secondary">Temporário</span>';
        default:
            return '<span class="badge bg-light text-dark">' . $tipo . '</span>';
    }
}

// Função para obter badge de modelo de trabalho
function getBadgeModeloTrabalho($modelo) {
    switch ($modelo) {
        case 'Presencial':
            return '<span class="badge bg-danger">Presencial</span>';
        case 'Remoto':
            return '<span class="badge bg-success">Remoto</span>';
        case 'Híbrido':
            return '<span class="badge bg-info">Híbrido</span>';
        default:
            return '<span class="badge bg-light text-dark">' . $modelo . '</span>';
    }
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gerenciar Vagas</h5>
                        <a href="<?php echo SITE_URL; ?>/?route=nova_vaga" class="btn btn-light btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Nova Vaga
                        </a>
                    </div>
                </div>
                <?php if ($sistema_vagas_internas_ativo): ?>
                <div class="card-footer bg-light">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i> O sistema de vagas internas está ativo. Você pode cadastrar novas vagas e receber candidaturas.
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Filtros -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo SITE_URL; ?>/">
                        <input type="hidden" name="route" value="gerenciar_vagas">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="ativa" <?php echo $status_filtro === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                                    <option value="inativa" <?php echo $status_filtro === 'inativa' ? 'selected' : ''; ?>>Inativa</option>
                                    <option value="encerrada" <?php echo $status_filtro === 'encerrada' ? 'selected' : ''; ?>>Encerrada</option>
                                    <option value="rascunho" <?php echo $status_filtro === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="busca" class="form-label">Busca</label>
                                <input type="text" class="form-control" id="busca" name="busca" placeholder="Buscar por título, descrição ou cidade" value="<?php echo htmlspecialchars($busca); ?>">
                            </div>
                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Lista de Vagas -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Minhas Vagas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($vagas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Nenhuma vaga encontrada com os filtros selecionados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Local</th>
                                        <th>Tipo</th>
                                        <th>Modelo</th>
                                        <th>Publicação</th>
                                        <th>Status</th>
                                        <th>Candidaturas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vagas as $vaga): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vaga['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($vaga['cidade'] . ', ' . $vaga['estado']); ?></td>
                                            <td><?php echo getBadgeTipoContrato($vaga['tipo_contrato']); ?></td>
                                            <td><?php echo getBadgeModeloTrabalho($vaga['modelo_trabalho']); ?></td>
                                            <td><?php echo formatarData($vaga['data_publicacao']); ?></td>
                                            <td><?php echo getBadgeStatus($vaga['status']); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/?route=candidaturas_vaga&id=<?php echo $vaga['id']; ?>" class="badge bg-primary text-decoration-none">
                                                    <?php echo $vaga['total_candidaturas']; ?> candidato(s)
                                                </a>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?php echo SITE_URL; ?>/?route=vaga_detalhe&id=<?php echo $vaga['id']; ?>" class="btn btn-sm btn-info" title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo SITE_URL; ?>/?route=editar_vaga&id=<?php echo $vaga['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" title="Excluir" 
                                                            onclick="confirmarExclusao(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a vaga <strong id="vagaTitulo"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita e todas as candidaturas associadas serão removidas.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnConfirmarExclusao" class="btn btn-danger">Confirmar Exclusão</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, titulo) {
    document.getElementById('vagaTitulo').textContent = titulo;
    document.getElementById('btnConfirmarExclusao').href = `<?php echo SITE_URL; ?>/?route=api_excluir_vaga&id=${id}`;
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
    modal.show();
}
</script>
