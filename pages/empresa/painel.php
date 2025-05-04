<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa') && !Auth::checkUserType('admin')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter dados da empresa
try {
    $empresa = $db->fetch("
        SELECT e.*, u.nome, u.email
        FROM empresas e
        JOIN usuarios u ON e.usuario_id = u.id
        WHERE e.usuario_id = :usuario_id
    ", ['usuario_id' => $usuario_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar dados da empresa: " . $e->getMessage());
    $empresa = [];
}

// Obter estatísticas de vagas
try {
    $estatisticas_vagas = $db->fetch("
        SELECT 
            COUNT(*) as total_vagas,
            SUM(CASE WHEN status = 'ativa' THEN 1 ELSE 0 END) as vagas_ativas,
            SUM(CASE WHEN status = 'inativa' THEN 1 ELSE 0 END) as vagas_inativas,
            SUM(CASE WHEN status = 'encerrada' THEN 1 ELSE 0 END) as vagas_encerradas
        FROM vagas
        WHERE empresa_id = :empresa_id
    ", ['empresa_id' => $usuario_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas de vagas: " . $e->getMessage());
    $estatisticas_vagas = [
        'total_vagas' => 0,
        'vagas_ativas' => 0,
        'vagas_inativas' => 0,
        'vagas_encerradas' => 0
    ];
}

// Obter últimas candidaturas
try {
    $candidaturas_recentes = $db->fetchAll("
        SELECT c.*, v.titulo as vaga_titulo, u.nome as talento_nome
        FROM candidaturas c
        JOIN vagas v ON c.vaga_id = v.id
        JOIN usuarios u ON c.talento_id = u.id
        WHERE v.empresa_id = :empresa_id
        ORDER BY c.data_candidatura DESC
        LIMIT 5
    ", ['empresa_id' => $usuario_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar candidaturas recentes: " . $e->getMessage());
    $candidaturas_recentes = [];
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
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Painel da Empresa</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4>Bem-vindo(a), <?php echo htmlspecialchars($empresa['nome'] ?? 'Empresa'); ?></h4>
                            <p class="text-muted">
                                <?php echo htmlspecialchars($empresa['razao_social'] ?? ''); ?>
                                <?php if (!empty($empresa['cnpj'])): ?>
                                    | CNPJ: <?php echo htmlspecialchars($empresa['cnpj']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa" class="btn btn-outline-primary me-2">
                                <i class="fas fa-user-edit me-2"></i>Editar Perfil
                            </a>
                            <a href="<?php echo SITE_URL; ?>/?route=nova_vaga" class="btn btn-success">
                                <i class="fas fa-plus-circle me-2"></i>Nova Vaga
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Resumo de Vagas -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Resumo de Vagas</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h1 class="display-4"><?php echo $estatisticas_vagas['total_vagas'] ?? 0; ?></h1>
                                    <p class="mb-0">Total de Vagas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <h1 class="display-4"><?php echo $estatisticas_vagas['vagas_ativas'] ?? 0; ?></h1>
                                    <p class="mb-0">Vagas Ativas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning h-100">
                                <div class="card-body">
                                    <h1 class="display-4"><?php echo $estatisticas_vagas['vagas_inativas'] ?? 0; ?></h1>
                                    <p class="mb-0">Vagas Inativas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-secondary text-white h-100">
                                <div class="card-body">
                                    <h1 class="display-4"><?php echo $estatisticas_vagas['vagas_encerradas'] ?? 0; ?></h1>
                                    <p class="mb-0">Vagas Encerradas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Candidaturas Recentes -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Candidaturas Recentes</h5>
                        <a href="<?php echo SITE_URL; ?>/?route=gerenciar_candidaturas" class="btn btn-sm btn-primary">
                            <i class="fas fa-list me-1"></i>Ver Todas
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($candidaturas_recentes)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Não há candidaturas recentes para exibir.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Talento</th>
                                        <th>Vaga</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidaturas_recentes as $candidatura): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($candidatura['talento_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($candidatura['vaga_titulo']); ?></td>
                                            <td><?php echo formatarData($candidatura['data_candidatura']); ?></td>
                                            <td><?php echo getBadgeStatus($candidatura['status']); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/?route=candidatura_detalhe&id=<?php echo $candidatura['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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

<!-- Modal de Detalhes da Candidatura -->
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalhesLabel">Detalhes da Candidatura</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="detalhesCandidatura">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p>Carregando detalhes...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Função para carregar detalhes da candidatura
function carregarDetalhesCandidatura(id) {
    fetch(`<?php echo SITE_URL; ?>/?route=api_candidatura_detalhe&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const candidatura = data.candidatura;
                const dataFormatada = new Date(candidatura.data_candidatura).toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                document.getElementById('detalhesCandidatura').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Informações do Talento</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Nome:</strong> ${candidatura.talento_nome}
                                </li>
                                <li class="list-group-item">
                                    <strong>Email:</strong> ${candidatura.talento_email}
                                </li>
                                <li class="list-group-item">
                                    <strong>Telefone:</strong> ${candidatura.telefone || 'Não informado'}
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Informações da Vaga</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Título:</strong> ${candidatura.vaga_titulo}
                                </li>
                                <li class="list-group-item">
                                    <strong>Localização:</strong> ${candidatura.cidade}, ${candidatura.estado}
                                </li>
                                <li class="list-group-item">
                                    <strong>Tipo de Contrato:</strong> ${candidatura.tipo_contrato}
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Mensagem do Candidato</h5>
                            <div class="card">
                                <div class="card-body bg-light">
                                    ${candidatura.mensagem ? candidatura.mensagem.replace(/\n/g, '<br>') : 'Nenhuma mensagem enviada.'}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Atualizar Status</h5>
                            <form id="formAtualizarStatus">
                                <input type="hidden" name="candidatura_id" value="${candidatura.id}">
                                <div class="mb-3">
                                    <select class="form-select" name="status" id="statusCandidatura">
                                        <option value="enviada" ${candidatura.status === 'enviada' ? 'selected' : ''}>Enviada</option>
                                        <option value="visualizada" ${candidatura.status === 'visualizada' ? 'selected' : ''}>Visualizada</option>
                                        <option value="em_analise" ${candidatura.status === 'em_analise' ? 'selected' : ''}>Em análise</option>
                                        <option value="aprovada" ${candidatura.status === 'aprovada' ? 'selected' : ''}>Aprovada</option>
                                        <option value="rejeitada" ${candidatura.status === 'rejeitada' ? 'selected' : ''}>Rejeitada</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="feedbackCandidatura" class="form-label">Feedback ao Candidato</label>
                                    <textarea class="form-control" id="feedbackCandidatura" name="feedback" rows="3">${candidatura.feedback || ''}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Atualizar</button>
                            </form>
                        </div>
                    </div>
                `;
                
                // Adicionar evento ao formulário
                document.getElementById('formAtualizarStatus').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch(`<?php echo SITE_URL; ?>/?route=api_atualizar_candidatura`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Status atualizado com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao atualizar status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Erro ao atualizar status: ' + error.message);
                    });
                });
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
</script>
