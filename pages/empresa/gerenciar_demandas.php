<?php
// Verificar se o usuário está logado e é uma empresa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'empresa') {
    $_SESSION['flash_message'] = "Acesso restrito a empresas.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "';</script>";
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o sistema de demandas de talentos está ativo
$sistema_demandas_ativo = $db->fetchColumn("SELECT valor FROM configuracoes WHERE chave = 'sistema_demandas_talentos_ativo'");

// Se o sistema não estiver ativo, mostrar mensagem de aviso
if (!$sistema_demandas_ativo) {
    $_SESSION['flash_message'] = "O sistema de demandas de talentos está desativado no momento. Entre em contato com o administrador.";
    $_SESSION['flash_type'] = "warning";
}

// Obter ID da empresa
$empresa_id = $db->fetchColumn("SELECT id FROM empresas WHERE usuario_id = ?", [$_SESSION['user_id']]);

if (!$empresa_id) {
    $_SESSION['flash_message'] = "Perfil de empresa não encontrado.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "';</script>";
    exit;
}

// Processar exclusão de demanda
if (isset($_GET['excluir']) && !empty($_GET['excluir'])) {
    $demanda_id = (int)$_GET['excluir'];
    
    // Verificar se a demanda pertence à empresa
    $demanda_empresa = $db->fetchColumn("SELECT empresa_id FROM demandas_talentos WHERE id = ?", [$demanda_id]);
    
    if ($demanda_empresa == $empresa_id) {
        try {
            // Iniciar transação
            $db->beginTransaction();
            
            // Excluir registros de interessados
            $db->execute("DELETE FROM demandas_interessados WHERE demanda_id = ?", [$demanda_id]);
            
            // Excluir profissões da demanda
            $db->execute("DELETE FROM demandas_profissoes WHERE demanda_id = ?", [$demanda_id]);
            
            // Excluir a demanda
            $db->execute("DELETE FROM demandas_talentos WHERE id = ?", [$demanda_id]);
            
            // Confirmar transação
            $db->commit();
            
            $_SESSION['flash_message'] = "Demanda excluída com sucesso!";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $db->rollBack();
            
            $_SESSION['flash_message'] = "Erro ao excluir demanda: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Você não tem permissão para excluir esta demanda.";
        $_SESSION['flash_type'] = "danger";
    }
    
    // Redirecionar para evitar reenvio
    echo "<script>window.location.href = '" . SITE_URL . "/?route=gerenciar_demandas';</script>";
    exit;
}

// Processar alteração de status
if (isset($_GET['status']) && !empty($_GET['status']) && isset($_GET['id']) && !empty($_GET['id'])) {
    $demanda_id = (int)$_GET['id'];
    $novo_status = $_GET['status'];
    
    // Verificar se o status é válido
    if (in_array($novo_status, ['ativa', 'inativa', 'concluida'])) {
        // Verificar se a demanda pertence à empresa
        $demanda_empresa = $db->fetchColumn("SELECT empresa_id FROM demandas_talentos WHERE id = ?", [$demanda_id]);
        
        if ($demanda_empresa == $empresa_id) {
            try {
                $db->execute("UPDATE demandas_talentos SET status = ? WHERE id = ?", [$novo_status, $demanda_id]);
                
                $_SESSION['flash_message'] = "Status da demanda atualizado com sucesso!";
                $_SESSION['flash_type'] = "success";
            } catch (Exception $e) {
                $_SESSION['flash_message'] = "Erro ao atualizar status: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "Você não tem permissão para alterar esta demanda.";
            $_SESSION['flash_type'] = "danger";
        }
        
        // Redirecionar para evitar reenvio
        echo "<script>window.location.href = '" . SITE_URL . "/?route=gerenciar_demandas';</script>";
        exit;
    }
}

// Configuração de paginação
$demandas_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $demandas_por_pagina;

// Filtro de status
$filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : '';
$sql_status = "";
$params = [$empresa_id];

if (!empty($filtro_status) && in_array($filtro_status, ['ativa', 'inativa', 'concluida'])) {
    $sql_status = " AND status = ?";
    $params[] = $filtro_status;
}

// Obter total de demandas
$total_demandas = $db->fetchColumn("
    SELECT COUNT(*) FROM demandas_talentos 
    WHERE empresa_id = ? $sql_status
", $params);

// Obter demandas da empresa
$demandas = $db->fetchAll("
    SELECT * FROM demandas_talentos 
    WHERE empresa_id = ? $sql_status
    ORDER BY data_publicacao DESC
    LIMIT $demandas_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_demandas / $demandas_por_pagina);

// Obter contagem de interessados para cada demanda
foreach ($demandas as $key => $demanda) {
    $demandas[$key]['total_interessados'] = $db->fetchColumn("
        SELECT COUNT(*) FROM demandas_interessados WHERE demanda_id = ?
    ", [$demanda['id']]);
    
    // Obter as profissões da demanda
    $demandas[$key]['profissoes'] = $db->fetchAll("
        SELECT profissao FROM demandas_profissoes WHERE demanda_id = ?
    ", [$demanda['id']]);
}

// Obter estatísticas
$total_ativas = $db->fetchColumn("SELECT COUNT(*) FROM demandas_talentos WHERE empresa_id = ? AND status = 'ativa'", [$empresa_id]);
$total_inativas = $db->fetchColumn("SELECT COUNT(*) FROM demandas_talentos WHERE empresa_id = ? AND status = 'inativa'", [$empresa_id]);
$total_concluidas = $db->fetchColumn("SELECT COUNT(*) FROM demandas_talentos WHERE empresa_id = ? AND status = 'concluida'", [$empresa_id]);
$total_interessados = $db->fetchColumn("
    SELECT COUNT(*) FROM demandas_interessados di
    JOIN demandas_talentos dt ON di.demanda_id = dt.id
    WHERE dt.empresa_id = ?
", [$empresa_id]);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="row">
                        <div class="col-6">
                            <h5>Gerenciar Anúncios de Procura</h5>
                        </div>
                        <div class="col-6 text-end">
                            <?php if ($sistema_demandas_ativo): ?>
                                <a href="<?php echo SITE_URL; ?>/?route=criar_demanda" class="btn btn-primary btn-sm mb-0">
                                    <i class="fas fa-plus me-2"></i>Novo Anúncio de Procura
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm mb-0" disabled>
                                    <i class="fas fa-plus me-2"></i>Novo Anúncio de Procura
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!$sistema_demandas_ativo): ?>
                <div class="alert alert-warning mx-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção:</strong> O sistema de demandas de talentos está desativado no momento. Você pode visualizar suas demandas existentes, mas não pode criar novas demandas até que o sistema seja reativado.
                </div>
                <?php endif; ?>
                
                <!-- Estatísticas -->
                <div class="card-body pt-0 pb-2">
                    <div class="row">
                        <div class="col-xl-3 col-sm-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="numbers">
                                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Anúncios Ativos</p>
                                                <h5 class="font-weight-bolder"><?php echo $total_ativas; ?></h5>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-sm-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="numbers">
                                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Anúncios Inativos</p>
                                                <h5 class="font-weight-bolder"><?php echo $total_inativas; ?></h5>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="icon icon-shape bg-secondary text-white rounded-circle shadow text-center">
                                                <i class="fas fa-pause-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-sm-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="numbers">
                                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Anúncios Concluídos</p>
                                                <h5 class="font-weight-bolder"><?php echo $total_concluidas; ?></h5>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="icon icon-shape bg-success text-white rounded-circle shadow text-center">
                                                <i class="fas fa-flag-checkered"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-sm-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="numbers">
                                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Total de Interessados</p>
                                                <h5 class="font-weight-bolder"><?php echo $total_interessados; ?></h5>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="icon icon-shape bg-info text-white rounded-circle shadow text-center">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="card-body pt-0 pb-2">
                    <form action="" method="GET" class="mb-4">
                        <input type="hidden" name="route" value="gerenciar_demandas">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filtro_status">Filtrar por status:</label>
                                    <select class="form-control" id="filtro_status" name="filtro_status" onchange="this.form.submit()">
                                        <option value="">Todos os status</option>
                                        <option value="ativa" <?php echo ($filtro_status == 'ativa') ? 'selected' : ''; ?>>Ativas</option>
                                        <option value="inativa" <?php echo ($filtro_status == 'inativa') ? 'selected' : ''; ?>>Inativas</option>
                                        <option value="concluida" <?php echo ($filtro_status == 'concluida') ? 'selected' : ''; ?>>Concluídas</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Lista de demandas -->
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <?php if (empty($demandas)): ?>
                            <div class="text-center p-4">
                                <p class="mb-2">Nenhuma demanda encontrada.</p>
                                <?php if ($sistema_demandas_ativo): ?>
                                    <a href="<?php echo SITE_URL; ?>/?route=criar_demanda" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-2"></i>Criar nova demanda
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Demanda</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Profissões</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Data</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Interessados</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($demandas as $demanda): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div>
                                                        <div class="icon icon-shape bg-primary text-white rounded-circle shadow text-center me-2">
                                                            <i class="fas fa-briefcase"></i>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($demanda['titulo']); ?></h6>
                                                        <p class="text-xs text-secondary mb-0">
                                                            <?php 
                                                            if (!empty($demanda['modelo_trabalho'])) {
                                                                echo htmlspecialchars($demanda['modelo_trabalho']);
                                                            }
                                                            if (!empty($demanda['nivel_experiencia'])) {
                                                                echo !empty($demanda['modelo_trabalho']) ? ' | ' : '';
                                                                echo htmlspecialchars($demanda['nivel_experiencia']);
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="profissoes-tags">
                                                    <?php foreach ($demanda['profissoes'] as $prof): ?>
                                                        <span class="badge bg-light text-dark mb-1 me-1"><?php echo htmlspecialchars($prof['profissao']); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    <?php echo date('d/m/Y', strtotime($demanda['data_publicacao'])); ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php if ($demanda['status'] == 'ativa'): ?>
                                                    <span class="badge bg-success">Ativa</span>
                                                <?php elseif ($demanda['status'] == 'inativa'): ?>
                                                    <span class="badge bg-secondary">Inativa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Concluída</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle text-center">
                                                <a href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda['id']; ?>" class="text-secondary font-weight-bold text-xs">
                                                    <span class="badge bg-primary"><?php echo $demanda['total_interessados']; ?></span>
                                                </a>
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="<?php echo SITE_URL; ?>/?route=visualizar_demanda&id=<?php echo $demanda['id']; ?>" class="btn btn-sm btn-info" title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo SITE_URL; ?>/?route=editar_demanda&id=<?php echo $demanda['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <!-- Botões de alteração de status -->
                                                    <?php if ($demanda['status'] != 'ativa'): ?>
                                                        <a href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas&id=<?php echo $demanda['id']; ?>&status=ativa" class="btn btn-sm btn-success" title="Ativar" onclick="return confirm('Deseja realmente ativar esta demanda?');">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($demanda['status'] != 'inativa'): ?>
                                                        <a href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas&id=<?php echo $demanda['id']; ?>&status=inativa" class="btn btn-sm btn-secondary" title="Inativar" onclick="return confirm('Deseja realmente inativar esta demanda?');">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($demanda['status'] != 'concluida'): ?>
                                                        <a href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas&id=<?php echo $demanda['id']; ?>&status=concluida" class="btn btn-sm btn-primary" title="Marcar como concluída" onclick="return confirm('Deseja realmente marcar esta demanda como concluída?');">
                                                            <i class="fas fa-flag-checkered"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas&excluir=<?php echo $demanda['id']; ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Deseja realmente excluir esta demanda? Esta ação não pode ser desfeita.');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Navegação de páginas">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($pagina_atual > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas&pagina=<?php echo $pagina_atual - 1; ?>&filtro_status=<?php echo $filtro_status; ?>">
                                            Anterior
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Anterior</span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                // Mostrar no máximo 5 links de página
                                $start_page = max(1, $pagina_atual - 2);
                                $end_page = min($total_paginas, $start_page + 4);
                                
                                if ($end_page - $start_page < 4) {
                                    $start_page = max(1, $end_page - 4);
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas&pagina=<?php echo $i; ?>&filtro_status=<?php echo $filtro_status; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagina_atual < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas&pagina=<?php echo $pagina_atual + 1; ?>&filtro_status=<?php echo $filtro_status; ?>">
                                            Próxima
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Próxima</span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.profissoes-tags {
    display: flex;
    flex-wrap: wrap;
}
</style>
