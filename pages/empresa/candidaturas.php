<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::isLoggedIn() || !Auth::checkUserType('empresa')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID da empresa (usuário logado)
$empresa_id = $_SESSION['user_id'];

// Instância do banco de dados
$db = Database::getInstance();

// Filtros
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';
$vaga_filtro = isset($_GET['vaga']) ? (int)$_GET['vaga'] : 0;
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'recentes';

// Buscar vagas da empresa para o filtro
try {
    $vagas = $db->fetchAll("
        SELECT id, titulo 
        FROM vagas 
        WHERE empresa_id = :empresa_id 
        ORDER BY titulo ASC
    ", ['empresa_id' => $empresa_id]);
} catch (PDOException $e) {
    error_log("Erro ao buscar vagas: " . $e->getMessage());
    $vagas = [];
}

// Construir a query de busca de candidaturas
$query = "
    SELECT c.*, v.titulo as vaga_titulo, v.tipo_vaga, 
           t.nome as talento_nome, t.email as talento_email,
           t.cidade as talento_cidade, t.estado as talento_estado,
           t.foto as talento_foto, t.profissao as talento_profissao
    FROM candidaturas c
    JOIN vagas v ON c.vaga_id = v.id
    JOIN talentos t ON c.talento_id = t.usuario_id
    WHERE v.empresa_id = :empresa_id
";

$params = ['empresa_id' => $empresa_id];

// Adicionar filtros à query
if (!empty($status_filtro)) {
    $query .= " AND c.status = :status";
    $params['status'] = $status_filtro;
}

if ($vaga_filtro > 0) {
    $query .= " AND c.vaga_id = :vaga_id";
    $params['vaga_id'] = $vaga_filtro;
}

// Adicionar ordenação
switch ($ordem) {
    case 'recentes':
        $query .= " ORDER BY c.data_candidatura DESC";
        break;
    case 'antigas':
        $query .= " ORDER BY c.data_candidatura ASC";
        break;
    case 'nome_asc':
        $query .= " ORDER BY t.nome ASC";
        break;
    case 'nome_desc':
        $query .= " ORDER BY t.nome DESC";
        break;
    default:
        $query .= " ORDER BY c.data_candidatura DESC";
}

// Buscar candidaturas
try {
    $candidaturas = $db->fetchAll($query, $params);
} catch (PDOException $e) {
    error_log("Erro ao buscar candidaturas: " . $e->getMessage());
    $candidaturas = [];
}

// Contar candidaturas por status
try {
    $contagem_status = $db->fetchAll("
        SELECT c.status, COUNT(*) as total
        FROM candidaturas c
        JOIN vagas v ON c.vaga_id = v.id
        WHERE v.empresa_id = :empresa_id
        GROUP BY c.status
    ", ['empresa_id' => $empresa_id]);
    
    $status_counts = [
        'recebida' => 0,
        'em_analise' => 0,
        'entrevista' => 0,
        'aprovada' => 0,
        'reprovada' => 0
    ];
    
    foreach ($contagem_status as $status) {
        $status_counts[$status['status']] = $status['total'];
    }
    
    $total_candidaturas = array_sum($status_counts);
} catch (PDOException $e) {
    error_log("Erro ao contar candidaturas: " . $e->getMessage());
    $status_counts = [
        'recebida' => 0,
        'em_analise' => 0,
        'entrevista' => 0,
        'aprovada' => 0,
        'reprovada' => 0
    ];
    $total_candidaturas = 0;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $candidatura_id = isset($_POST['candidatura_id']) ? (int)$_POST['candidatura_id'] : 0;
    $novo_status = isset($_POST['novo_status']) ? $_POST['novo_status'] : '';
    
    if ($candidatura_id > 0 && !empty($novo_status)) {
        try {
            $db->query("
                UPDATE candidaturas 
                SET status = :status, data_atualizacao = NOW() 
                WHERE id = :id
            ", [
                'status' => $novo_status,
                'id' => $candidatura_id
            ]);
            
            // Redirecionar para evitar reenvio do formulário
            header('Location: ' . SITE_URL . '/?route=empresa/candidaturas&atualizado=1');
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao atualizar candidatura: " . $e->getMessage());
            $erro = "Erro ao atualizar status da candidatura.";
        }
    }
}

// Função para formatar status
function formatarStatus($status) {
    $status_formatado = [
        'recebida' => '<span class="badge bg-info">Recebida</span>',
        'em_analise' => '<span class="badge bg-warning">Em Análise</span>',
        'entrevista' => '<span class="badge bg-primary">Entrevista</span>',
        'aprovada' => '<span class="badge bg-success">Aprovada</span>',
        'reprovada' => '<span class="badge bg-danger">Reprovada</span>'
    ];
    
    return $status_formatado[$status] ?? '<span class="badge bg-secondary">Desconhecido</span>';
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gerenciamento de Candidaturas</h1>
        <a href="<?php echo SITE_URL; ?>/?route=empresa/painel" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Voltar ao Painel
        </a>
    </div>
    
    <?php if (isset($_GET['atualizado']) && $_GET['atualizado'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Status da candidatura atualizado com sucesso!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $erro; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php endif; ?>
    
    <!-- Cards de resumo -->
    <div class="row mb-4">
        <div class="col-md">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Total de Candidaturas</h5>
                    <p class="display-4"><?php echo $total_candidaturas; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Novas</h5>
                    <p class="display-4 text-info"><?php echo $status_counts['recebida']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Em Análise</h5>
                    <p class="display-4 text-warning"><?php echo $status_counts['em_analise']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Entrevistas</h5>
                    <p class="display-4 text-primary"><?php echo $status_counts['entrevista']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Aprovadas</h5>
                    <p class="display-4 text-success"><?php echo $status_counts['aprovada']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Filtros</h5>
            <form method="get" action="<?php echo SITE_URL; ?>/" class="row g-3">
                <input type="hidden" name="route" value="empresa/candidaturas">
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="recebida" <?php echo $status_filtro === 'recebida' ? 'selected' : ''; ?>>Recebida</option>
                        <option value="em_analise" <?php echo $status_filtro === 'em_analise' ? 'selected' : ''; ?>>Em Análise</option>
                        <option value="entrevista" <?php echo $status_filtro === 'entrevista' ? 'selected' : ''; ?>>Entrevista</option>
                        <option value="aprovada" <?php echo $status_filtro === 'aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="reprovada" <?php echo $status_filtro === 'reprovada' ? 'selected' : ''; ?>>Reprovada</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="vaga" class="form-label">Vaga</label>
                    <select name="vaga" id="vaga" class="form-select">
                        <option value="0">Todas</option>
                        <?php foreach ($vagas as $vaga): ?>
                        <option value="<?php echo $vaga['id']; ?>" <?php echo $vaga_filtro === (int)$vaga['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vaga['titulo']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="ordem" class="form-label">Ordenar por</label>
                    <select name="ordem" id="ordem" class="form-select">
                        <option value="recentes" <?php echo $ordem === 'recentes' ? 'selected' : ''; ?>>Mais recentes</option>
                        <option value="antigas" <?php echo $ordem === 'antigas' ? 'selected' : ''; ?>>Mais antigas</option>
                        <option value="nome_asc" <?php echo $ordem === 'nome_asc' ? 'selected' : ''; ?>>Nome (A-Z)</option>
                        <option value="nome_desc" <?php echo $ordem === 'nome_desc' ? 'selected' : ''; ?>>Nome (Z-A)</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-2"></i> Filtrar
                    </button>
                    <a href="<?php echo SITE_URL; ?>/?route=empresa/candidaturas" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de candidaturas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Candidaturas <?php echo !empty($status_filtro) ? '- ' . ucfirst(str_replace('_', ' ', $status_filtro)) : ''; ?></h5>
            
            <?php if (empty($candidaturas)): ?>
            <div class="alert alert-info">
                Nenhuma candidatura encontrada com os filtros selecionados.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Candidato</th>
                            <th>Vaga</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidaturas as $candidatura): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $foto_talento = !empty($candidatura['talento_foto']) 
                                        ? SITE_URL . '/uploads/perfil/' . $candidatura['talento_foto'] 
                                        : SITE_URL . '/assets/img/placeholder-user.png';
                                    ?>
                                    <img src="<?php echo $foto_talento; ?>" alt="<?php echo htmlspecialchars($candidatura['talento_nome']); ?>" 
                                         class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($candidatura['talento_nome']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($candidatura['talento_profissao'] ?: 'Profissão não informada'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($candidatura['vaga_titulo']); ?>
                                <?php if ($candidatura['tipo_vaga'] === 'externa'): ?>
                                <span class="badge bg-secondary ms-1">Externa</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatarData($candidatura['data_candidatura']); ?></td>
                            <td><?php echo formatarStatus($candidatura['status']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetalhes<?php echo $candidatura['id']; ?>" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalStatus<?php echo $candidatura['id']; ?>" title="Alterar status">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <a href="<?php echo SITE_URL; ?>/?route=mensagens&para=<?php echo $candidatura['talento_id']; ?>" class="btn btn-sm btn-outline-info" title="Enviar mensagem">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                    <?php if (in_array($candidatura['status'], ['recebida', 'em_analise'])): ?>
                                    <a href="<?php echo SITE_URL; ?>/?route=convidar_entrevista&id=<?php echo $candidatura['id']; ?>" class="btn btn-sm btn-outline-primary" title="Convidar para entrevista">
                                        <i class="fas fa-calendar-check"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Modal de Detalhes -->
                                <div class="modal fade" id="modalDetalhes<?php echo $candidatura['id']; ?>" tabindex="-1" aria-labelledby="modalDetalhesLabel<?php echo $candidatura['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalDetalhesLabel<?php echo $candidatura['id']; ?>">Detalhes da Candidatura</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-4 text-center mb-3">
                                                        <img src="<?php echo $foto_talento; ?>" alt="<?php echo htmlspecialchars($candidatura['talento_nome']); ?>" 
                                                             class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                                                        <h5><?php echo htmlspecialchars($candidatura['talento_nome']); ?></h5>
                                                        <p class="text-muted"><?php echo htmlspecialchars($candidatura['talento_profissao'] ?: 'Profissão não informada'); ?></p>
                                                        <p><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($candidatura['talento_cidade'] . ', ' . $candidatura['talento_estado']); ?></p>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <h6 class="border-bottom pb-2 mb-3">Informações da Candidatura</h6>
                                                        <p><strong>Vaga:</strong> <?php echo htmlspecialchars($candidatura['vaga_titulo']); ?></p>
                                                        <p><strong>Data da Candidatura:</strong> <?php echo formatarData($candidatura['data_candidatura']); ?></p>
                                                        <p><strong>Status Atual:</strong> <?php echo formatarStatus($candidatura['status']); ?></p>
                                                        
                                                        <h6 class="border-bottom pb-2 mb-3 mt-4">Mensagem do Candidato</h6>
                                                        <div class="card bg-light">
                                                            <div class="card-body">
                                                                <?php if (!empty($candidatura['mensagem'])): ?>
                                                                <p><?php echo nl2br(htmlspecialchars($candidatura['mensagem'])); ?></p>
                                                                <?php else: ?>
                                                                <p class="text-muted">Nenhuma mensagem enviada pelo candidato.</p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($candidatura['curriculo'])): ?>
                                                        <div class="mt-3">
                                                            <a href="<?php echo SITE_URL; ?>/uploads/curriculos/<?php echo $candidatura['curriculo']; ?>" class="btn btn-primary" target="_blank">
                                                                <i class="fas fa-file-pdf me-2"></i> Ver Currículo
                                                            </a>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                <a href="<?php echo SITE_URL; ?>/?route=talento/perfil&id=<?php echo $candidatura['talento_id']; ?>" class="btn btn-primary" target="_blank">
                                                    Ver Perfil Completo
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal de Alteração de Status -->
                                <div class="modal fade" id="modalStatus<?php echo $candidatura['id']; ?>" tabindex="-1" aria-labelledby="modalStatusLabel<?php echo $candidatura['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalStatusLabel<?php echo $candidatura['id']; ?>">Alterar Status da Candidatura</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>
                                            <form method="post" action="<?php echo SITE_URL; ?>/?route=empresa/candidaturas">
                                                <div class="modal-body">
                                                    <input type="hidden" name="candidatura_id" value="<?php echo $candidatura['id']; ?>">
                                                    <input type="hidden" name="action" value="atualizar_status">
                                                    
                                                    <div class="mb-3">
                                                        <label for="novo_status<?php echo $candidatura['id']; ?>" class="form-label">Novo Status</label>
                                                        <select name="novo_status" id="novo_status<?php echo $candidatura['id']; ?>" class="form-select">
                                                            <option value="recebida" <?php echo $candidatura['status'] === 'recebida' ? 'selected' : ''; ?>>Recebida</option>
                                                            <option value="em_analise" <?php echo $candidatura['status'] === 'em_analise' ? 'selected' : ''; ?>>Em Análise</option>
                                                            <option value="entrevista" <?php echo $candidatura['status'] === 'entrevista' ? 'selected' : ''; ?>>Entrevista</option>
                                                            <option value="aprovada" <?php echo $candidatura['status'] === 'aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                                                            <option value="reprovada" <?php echo $candidatura['status'] === 'reprovada' ? 'selected' : ''; ?>>Reprovada</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="alert alert-info">
                                                        <small>
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            Ao alterar o status, o candidato será notificado automaticamente.
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Salvar Alteração</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
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
document.addEventListener('DOMContentLoaded', function() {
    // Ativar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-fechar alertas após 5 segundos
    setTimeout(function() {
        var alertList = document.querySelectorAll('.alert');
        alertList.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
