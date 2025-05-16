<?php
// Verificar se o usuário está logado como administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/?route=acesso_negado');
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

// Ação de aprovar indicação
if (isset($_GET['acao']) && $_GET['acao'] === 'aprovar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Verificar se a indicação existe
        $indicacao = $db->fetch("SELECT * FROM indicacoes_perfis_linkedin WHERE id = :id", ['id' => $id]);
        
        if ($indicacao) {
            // Iniciar transação
            $db->beginTransaction();
            
            // Inserir o perfil na tabela de perfis
            $sql = "INSERT INTO perfis_linkedin (nome, foto, assunto, link_perfil, status, destaque) 
                    VALUES (:nome, :foto, :assunto, :link_perfil, :status, :destaque)";
            
            $params = [
                'nome' => $indicacao['nome_perfil'],
                'foto' => 'default-profile.jpg',
                'assunto' => $indicacao['assunto'],
                'link_perfil' => $indicacao['link_perfil'],
                'status' => 'ativo',
                'destaque' => 0
            ];
            
            $db->execute($sql, $params);
            
            // Atualizar status da indicação
            $db->execute("UPDATE indicacoes_perfis_linkedin SET status = 'aprovado', data_processamento = NOW() WHERE id = :id", ['id' => $id]);
            
            // Confirmar transação
            $db->commit();
            
            $mensagem = 'Indicação aprovada com sucesso! O perfil foi adicionado à lista.';
            $tipo_mensagem = 'success';
        } else {
            $mensagem = 'Indicação não encontrada.';
            $tipo_mensagem = 'danger';
        }
    } catch (Exception $e) {
        // Reverter transação em caso de exceção
        $db->rollBack();
        $mensagem = 'Erro ao aprovar indicação: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
        error_log('Erro ao aprovar indicação de perfil: ' . $e->getMessage());
    }
}

// Ação de rejeitar indicação
if (isset($_GET['acao']) && $_GET['acao'] === 'rejeitar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Verificar se a indicação existe
        $indicacao = $db->fetch("SELECT * FROM indicacoes_perfis_linkedin WHERE id = :id", ['id' => $id]);
        
        if ($indicacao) {
            // Atualizar status da indicação
            $db->execute("UPDATE indicacoes_perfis_linkedin SET status = 'rejeitado', data_processamento = NOW() WHERE id = :id", ['id' => $id]);
            
            $mensagem = 'Indicação rejeitada com sucesso.';
            $tipo_mensagem = 'success';
        } else {
            $mensagem = 'Indicação não encontrada.';
            $tipo_mensagem = 'danger';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao rejeitar indicação: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
        error_log('Erro ao rejeitar indicação de perfil: ' . $e->getMessage());
    }
}

// Ação de excluir indicação
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Verificar se a indicação existe
        $indicacao = $db->fetch("SELECT * FROM indicacoes_perfis_linkedin WHERE id = :id", ['id' => $id]);
        
        if ($indicacao) {
            // Excluir a indicação
            $db->execute("DELETE FROM indicacoes_perfis_linkedin WHERE id = :id", ['id' => $id]);
            
            $mensagem = 'Indicação excluída com sucesso.';
            $tipo_mensagem = 'success';
        } else {
            $mensagem = 'Indicação não encontrada.';
            $tipo_mensagem = 'danger';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao excluir indicação: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
        error_log('Erro ao excluir indicação de perfil: ' . $e->getMessage());
    }
}

// Configuração de paginação
$indicacoes_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $indicacoes_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (nome_perfil LIKE :busca OR nome_indicador LIKE :busca OR email_indicador LIKE :busca OR assunto LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_status)) {
    $sql_where .= " AND status = :status";
    $params['status'] = $filtro_status;
}

// Consulta para obter o total de indicações
$total_indicacoes = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM indicacoes_perfis_linkedin
    $sql_where
", $params);

// Consulta para obter as indicações da página atual
$indicacoes = $db->fetchAll("
    SELECT *
    FROM indicacoes_perfis_linkedin
    $sql_where
    ORDER BY 
        CASE 
            WHEN status = 'pendente' THEN 1
            WHEN status = 'aprovado' THEN 2
            WHEN status = 'rejeitado' THEN 3
        END,
        data_indicacao DESC
    LIMIT $indicacoes_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_indicacoes / $indicacoes_por_pagina);

// Contagem por status
$contagem_pendentes = $db->fetchColumn("SELECT COUNT(*) FROM indicacoes_perfis_linkedin WHERE status = 'pendente'");
$contagem_aprovados = $db->fetchColumn("SELECT COUNT(*) FROM indicacoes_perfis_linkedin WHERE status = 'aprovado'");
$contagem_rejeitados = $db->fetchColumn("SELECT COUNT(*) FROM indicacoes_perfis_linkedin WHERE status = 'rejeitado'");
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Indicações de Perfis</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin">Gerenciar Perfis do LinkedIn</a></li>
        <li class="breadcrumb-item active">Gerenciar Indicações</li>
    </ol>
    
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Total de Indicações</div>
                            <div class="fs-4"><?php echo $total_indicacoes; ?></div>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Pendentes</div>
                            <div class="fs-4"><?php echo $contagem_pendentes; ?></div>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Aprovadas</div>
                            <div class="fs-4"><?php echo $contagem_aprovados; ?></div>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Rejeitadas</div>
                            <div class="fs-4"><?php echo $contagem_rejeitados; ?></div>
                        </div>
                        <div>
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user-check me-1"></i>
                    Indicações de Perfis do LinkedIn
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin" class="btn btn-primary btn-sm">
                        <i class="fab fa-linkedin me-1"></i> Gerenciar Perfis
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <form action="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis" method="GET" class="row g-3">
                        <input type="hidden" name="route" value="gerenciar_indicacoes_perfis">
                        
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="busca" name="busca" placeholder="Buscar por nome, email ou assunto" value="<?php echo htmlspecialchars((string)$busca); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos os status</option>
                                <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                                <option value="aprovado" <?php echo $filtro_status === 'aprovado' ? 'selected' : ''; ?>>Aprovados</option>
                                <option value="rejeitado" <?php echo $filtro_status === 'rejeitado' ? 'selected' : ''; ?>>Rejeitados</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabela de indicações -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome do Perfil</th>
                            <th>Assunto</th>
                            <th>Indicado por</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($indicacoes)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Nenhuma indicação encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($indicacoes as $indicacao): ?>
                                <tr class="<?php echo $indicacao['status'] === 'pendente' ? 'table-warning' : ($indicacao['status'] === 'aprovado' ? 'table-success' : 'table-danger'); ?>">
                                    <td><?php echo $indicacao['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars((string)$indicacao['nome_perfil']); ?>
                                        <div class="small text-muted">
                                            <a href="<?php echo htmlspecialchars((string)$indicacao['link_perfil']); ?>" target="_blank">
                                                <i class="fab fa-linkedin"></i> Ver perfil
                                            </a>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)$indicacao['assunto']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars((string)$indicacao['nome_indicador']); ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars((string)$indicacao['email_indicador']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($indicacao['data_indicacao'])); ?>
                                        <?php if (!empty($indicacao['data_processamento'])): ?>
                                            <div class="small text-muted">
                                                Processado: <?php echo date('d/m/Y H:i', strtotime($indicacao['data_processamento'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($indicacao['status'] === 'pendente'): ?>
                                            <span class="badge bg-warning text-dark">Pendente</span>
                                        <?php elseif ($indicacao['status'] === 'aprovado'): ?>
                                            <span class="badge bg-success">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejeitado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detalhesModal<?php echo $indicacao['id']; ?>" title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($indicacao['status'] === 'pendente'): ?>
                                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&acao=aprovar&id=<?php echo $indicacao['id']; ?>" class="btn btn-sm btn-success" title="Aprovar" onclick="return confirm('Tem certeza que deseja aprovar esta indicação?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&acao=rejeitar&id=<?php echo $indicacao['id']; ?>" class="btn btn-sm btn-danger" title="Rejeitar" onclick="return confirm('Tem certeza que deseja rejeitar esta indicação?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&acao=excluir&id=<?php echo $indicacao['id']; ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta indicação? Esta ação não pode ser desfeita.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Modal de Detalhes -->
                                        <div class="modal fade" id="detalhesModal<?php echo $indicacao['id']; ?>" tabindex="-1" aria-labelledby="detalhesModalLabel<?php echo $indicacao['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="detalhesModalLabel<?php echo $indicacao['id']; ?>">Detalhes da Indicação #<?php echo $indicacao['id']; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Informações do Perfil</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th>Nome:</th>
                                                                        <td><?php echo htmlspecialchars((string)$indicacao['nome_perfil']); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Link:</th>
                                                                        <td>
                                                                            <a href="<?php echo htmlspecialchars((string)$indicacao['link_perfil']); ?>" target="_blank">
                                                                                <?php echo htmlspecialchars((string)$indicacao['link_perfil']); ?>
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Assunto:</th>
                                                                        <td><?php echo htmlspecialchars((string)$indicacao['assunto']); ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Informações do Indicador</h6>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <th>Nome:</th>
                                                                        <td><?php echo htmlspecialchars((string)$indicacao['nome_indicador']); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>E-mail:</th>
                                                                        <td><?php echo htmlspecialchars((string)$indicacao['email_indicador']); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th>Data:</th>
                                                                        <td><?php echo date('d/m/Y H:i', strtotime($indicacao['data_indicacao'])); ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($indicacao['mensagem'])): ?>
                                                            <div class="mt-3">
                                                                <h6>Mensagem do Indicador</h6>
                                                                <div class="card">
                                                                    <div class="card-body bg-light">
                                                                        <?php echo nl2br(htmlspecialchars((string)$indicacao['mensagem'])); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="mt-3">
                                                            <h6>Status da Indicação</h6>
                                                            <div>
                                                                <?php if ($indicacao['status'] === 'pendente'): ?>
                                                                    <span class="badge bg-warning text-dark">Pendente</span>
                                                                <?php elseif ($indicacao['status'] === 'aprovado'): ?>
                                                                    <span class="badge bg-success">Aprovado</span>
                                                                    <?php if (!empty($indicacao['data_processamento'])): ?>
                                                                        <small class="text-muted ms-2">
                                                                            em <?php echo date('d/m/Y H:i', strtotime($indicacao['data_processamento'])); ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Rejeitado</span>
                                                                    <?php if (!empty($indicacao['data_processamento'])): ?>
                                                                        <small class="text-muted ms-2">
                                                                            em <?php echo date('d/m/Y H:i', strtotime($indicacao['data_processamento'])); ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <?php if ($indicacao['status'] === 'pendente'): ?>
                                                            <a href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&acao=aprovar&id=<?php echo $indicacao['id']; ?>" class="btn btn-success" onclick="return confirm('Tem certeza que deseja aprovar esta indicação?')">
                                                                <i class="fas fa-check me-1"></i> Aprovar
                                                            </a>
                                                            <a href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&acao=rejeitar&id=<?php echo $indicacao['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja rejeitar esta indicação?')">
                                                                <i class="fas fa-times me-1"></i> Rejeitar
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagina_atual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&pagina=1<?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?>" aria-label="Primeira">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&pagina=<?php echo $pagina_atual - 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&lt;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        // Definir intervalo de páginas a serem exibidas
                        $intervalo = 2;
                        $inicio_intervalo = max(1, $pagina_atual - $intervalo);
                        $fim_intervalo = min($total_paginas, $pagina_atual + $intervalo);
                        
                        // Exibir primeira página se não estiver no intervalo
                        if ($inicio_intervalo > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=gerenciar_indicacoes_perfis&pagina=1' . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_status) ? '&status=' . urlencode($filtro_status) : '') . '">1</a></li>';
                            if ($inicio_intervalo > 2) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                        }
                        
                        // Exibir páginas do intervalo
                        for ($i = $inicio_intervalo; $i <= $fim_intervalo; $i++) {
                            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . SITE_URL . '/?route=gerenciar_indicacoes_perfis&pagina=' . $i . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_status) ? '&status=' . urlencode($filtro_status) : '') . '">' . $i . '</a></li>';
                        }
                        
                        // Exibir última página se não estiver no intervalo
                        if ($fim_intervalo < $total_paginas) {
                            if ($fim_intervalo < $total_paginas - 1) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=gerenciar_indicacoes_perfis&pagina=' . $total_paginas . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_status) ? '&status=' . urlencode($filtro_status) : '') . '">' . $total_paginas . '</a></li>';
                        }
                        ?>
                        
                        <?php if ($pagina_atual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&pagina=<?php echo $pagina_atual + 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?>" aria-label="Próxima">
                                    <span aria-hidden="true">&gt;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis&pagina=<?php echo $total_paginas; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?>" aria-label="Última">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
