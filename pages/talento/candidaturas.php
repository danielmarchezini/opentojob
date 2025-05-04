<?php
// Verificar se o usuário está logado e é um talento
if (!Auth::checkUserType('talento') && !Auth::checkUserType('admin')) {
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Verificar se o sistema de vagas internas está ativo
$db = Database::getInstance();
$sistema_vagas_internas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_vagas_internas_ativo'");

// Se o sistema de vagas internas não estiver ativo, redirecionar para o painel
if (!$sistema_vagas_internas_ativo && !Auth::checkUserType('admin')) {
    $_SESSION['flash_message'] = "O sistema de vagas internas está temporariamente desativado. Entre em contato com o administrador para mais informações.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_talento';</script>";
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Definir filtros
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';
$periodo_filtro = isset($_GET['periodo']) ? $_GET['periodo'] : '';

// Construir condição SQL para filtros
$condicao = "c.talento_id = :talento_id";
$params = ['talento_id' => $usuario_id];

if (!empty($status_filtro)) {
    $condicao .= " AND c.status = :status";
    $params['status'] = $status_filtro;
}

if (!empty($periodo_filtro)) {
    $data_inicio = '';
    switch ($periodo_filtro) {
        case 'hoje':
            $data_inicio = date('Y-m-d');
            break;
        case 'semana':
            $data_inicio = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'mes':
            $data_inicio = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'trimestre':
            $data_inicio = date('Y-m-d', strtotime('-90 days'));
            break;
    }
    
    if (!empty($data_inicio)) {
        $condicao .= " AND c.data_candidatura >= :data_inicio";
        $params['data_inicio'] = $data_inicio;
    }
}

// Obter candidaturas
try {
    $candidaturas = $db->fetchAll("
        SELECT c.*, v.titulo, v.cidade, v.estado, v.tipo_contrato, v.regime_trabalho, v.nivel_experiencia,
               e.razao_social, u.nome as empresa_nome
        FROM candidaturas c
        JOIN vagas v ON c.vaga_id = v.id
        JOIN usuarios u ON v.empresa_id = u.id
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE $condicao
        ORDER BY c.data_candidatura DESC
    ", $params);
} catch (PDOException $e) {
    error_log("Erro ao buscar candidaturas: " . $e->getMessage());
    $_SESSION['flash_message'] = "Erro ao carregar candidaturas: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $candidaturas = [];
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

// Função para obter badge de status
function getBadgeStatus($status) {
    switch ($status) {
        case 'enviada':
            return '<span class="badge bg-primary">Enviada</span>';
        case 'visualizada':
            return '<span class="badge bg-info">Visualizada</span>';
        case 'em_analise':
            return '<span class="badge bg-warning">Em análise</span>';
        case 'aprovada':
            return '<span class="badge bg-success">Aprovada</span>';
        case 'rejeitada':
            return '<span class="badge bg-danger">Rejeitada</span>';
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

// Contar candidaturas por status
$status_counts = [
    'total' => 0,
    'enviada' => 0,
    'visualizada' => 0,
    'em_analise' => 0,
    'aprovada' => 0,
    'rejeitada' => 0
];

foreach ($candidaturas as $candidatura) {
    $status_counts['total']++;
    $status = $candidatura['status'];
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Minhas Candidaturas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p>Acompanhe o status de todas as suas candidaturas a vagas.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="<?php echo SITE_URL; ?>/?route=vagas" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Buscar Novas Vagas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <!-- Resumo de Candidaturas -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Resumo</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo $status_counts['total']; ?></h3>
                                    <p class="mb-0">Total</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo $status_counts['enviada']; ?></h3>
                                    <p class="mb-0">Enviadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-info"><?php echo $status_counts['visualizada']; ?></h3>
                                    <p class="mb-0">Visualizadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-warning"><?php echo $status_counts['em_analise']; ?></h3>
                                    <p class="mb-0">Em Análise</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-success"><?php echo $status_counts['aprovada']; ?></h3>
                                    <p class="mb-0">Aprovadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h3 class="text-danger"><?php echo $status_counts['rejeitada']; ?></h3>
                                    <p class="mb-0">Rejeitadas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <input type="hidden" name="route" value="minhas_candidaturas">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">Todos</option>
                                        <option value="enviada" <?php echo $status_filtro == 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                                        <option value="visualizada" <?php echo $status_filtro == 'visualizada' ? 'selected' : ''; ?>>Visualizada</option>
                                        <option value="em_analise" <?php echo $status_filtro == 'em_analise' ? 'selected' : ''; ?>>Em Análise</option>
                                        <option value="aprovada" <?php echo $status_filtro == 'aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                                        <option value="rejeitada" <?php echo $status_filtro == 'rejeitada' ? 'selected' : ''; ?>>Rejeitada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="periodo">Período</label>
                                    <select class="form-control" id="periodo" name="periodo">
                                        <option value="">Todos</option>
                                        <option value="hoje" <?php echo $periodo_filtro == 'hoje' ? 'selected' : ''; ?>>Hoje</option>
                                        <option value="semana" <?php echo $periodo_filtro == 'semana' ? 'selected' : ''; ?>>Última semana</option>
                                        <option value="mes" <?php echo $periodo_filtro == 'mes' ? 'selected' : ''; ?>>Último mês</option>
                                        <option value="trimestre" <?php echo $periodo_filtro == 'trimestre' ? 'selected' : ''; ?>>Último trimestre</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-2"></i>Filtrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Lista de Candidaturas -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Candidaturas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($candidaturas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Nenhuma candidatura encontrada com os filtros selecionados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vaga</th>
                                        <th>Empresa</th>
                                        <th>Localização</th>
                                        <th>Tipo</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidaturas as $candidatura): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/?route=vaga_detalhe&id=<?php echo $candidatura['vaga_id']; ?>">
                                                    <?php echo htmlspecialchars($candidatura['titulo']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($candidatura['razao_social'] ?: $candidatura['empresa_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($candidatura['cidade'] . '/' . $candidatura['estado']); ?></td>
                                            <td>
                                                <?php echo getBadgeTipoContrato($candidatura['tipo_contrato']); ?>
                                                <?php echo getBadgeModeloTrabalho($candidatura['modelo_trabalho']); ?>
                                            </td>
                                            <td><?php echo formatarData($candidatura['data_candidatura']); ?></td>
                                            <td><?php echo getBadgeStatus($candidatura['status']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="visualizarCandidatura(<?php echo $candidatura['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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

<!-- Modal Visualizar Candidatura -->
<div class="modal fade" id="modalVisualizarCandidatura" tabindex="-1" aria-labelledby="modalVisualizarCandidaturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalVisualizarCandidaturaLabel">Detalhes da Candidatura</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesCandidatura">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando detalhes da candidatura...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Função para visualizar detalhes da candidatura
function visualizarCandidatura(id) {
    // Mostrar modal com loading
    const modal = new bootstrap.Modal(document.getElementById('modalVisualizarCandidatura'));
    modal.show();
    
    // Mostrar loading
    document.getElementById('detalhesCandidatura').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando detalhes da candidatura...</p>
        </div>
    `;
    
    // Carregar dados da candidatura via AJAX
    fetch('<?php echo SITE_URL; ?>/?route=api_candidatura_detalhe&id=' + id)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const candidatura = data.data.candidatura;
                
                // Formatar data
                const dataFormatada = new Date(candidatura.data_candidatura).toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Preencher detalhes da candidatura
                document.getElementById('detalhesCandidatura').innerHTML = `
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Informações da Vaga</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Título:</strong> ${candidatura.titulo}
                                </li>
                                <li class="list-group-item">
                                    <strong>Empresa:</strong> ${candidatura.empresa_nome}
                                </li>
                                <li class="list-group-item">
                                    <strong>Localização:</strong> ${candidatura.cidade}/${candidatura.estado}
                                </li>
                                <li class="list-group-item">
                                    <strong>Tipo de Contrato:</strong> ${candidatura.tipo_contrato}
                                </li>
                                <li class="list-group-item">
                                    <strong>Modelo de Trabalho:</strong> ${candidatura.modelo_trabalho}
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Informações da Candidatura</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Data de Candidatura:</strong> ${dataFormatada}
                                </li>
                                <li class="list-group-item">
                                    <strong>Status:</strong> ${getStatusHTML(candidatura.status)}
                                </li>
                                <li class="list-group-item">
                                    <strong>Última Atualização:</strong> ${candidatura.data_atualizacao ? new Date(candidatura.data_atualizacao).toLocaleDateString('pt-BR', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'Não disponível'}
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <h5>Mensagem Enviada</h5>
                            <div class="card">
                                <div class="card-body bg-light">
                                    ${candidatura.mensagem ? candidatura.mensagem.replace(/\n/g, '<br>') : 'Nenhuma mensagem enviada.'}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${candidatura.feedback ? `
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Feedback da Empresa</h5>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        ${candidatura.feedback.replace(/\n/g, '<br>')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                `;
            } else {
                document.getElementById('detalhesCandidatura').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>${data.message || 'Erro ao carregar detalhes da candidatura.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('detalhesCandidatura').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>Erro ao carregar detalhes: ${error.message}
                </div>
            `;
        });
}

// Função para obter HTML do status
function getStatusHTML(status) {
    switch (status) {
        case 'enviada':
            return '<span class="badge bg-primary">Enviada</span>';
        case 'visualizada':
            return '<span class="badge bg-info">Visualizada</span>';
        case 'em_analise':
            return '<span class="badge bg-warning">Em análise</span>';
        case 'aprovada':
            return '<span class="badge bg-success">Aprovada</span>';
        case 'rejeitada':
            return '<span class="badge bg-danger">Rejeitada</span>';
        default:
            return '<span class="badge bg-secondary">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }
}
</script>
