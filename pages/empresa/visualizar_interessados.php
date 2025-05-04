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

// Verificar se o ID da demanda foi fornecido
if (!isset($_GET['demanda_id']) || empty($_GET['demanda_id'])) {
    $_SESSION['flash_message'] = "ID da demanda não fornecido.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=gerenciar_demandas';</script>";
    exit;
}

$demanda_id = (int)$_GET['demanda_id'];

// Obter ID da empresa
$empresa_id = $db->fetchColumn("SELECT id FROM empresas WHERE usuario_id = ?", [$_SESSION['user_id']]);

if (!$empresa_id) {
    $_SESSION['flash_message'] = "Perfil de empresa não encontrado.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "';</script>";
    exit;
}

// Verificar se a demanda pertence à empresa
$demanda = $db->fetchRow("
    SELECT * FROM demandas_talentos 
    WHERE id = ? AND empresa_id = ?
", [$demanda_id, $empresa_id]);

if (!$demanda) {
    $_SESSION['flash_message'] = "Demanda não encontrada ou você não tem permissão para acessá-la.";
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=gerenciar_demandas';</script>";
    exit;
}

// Processar alteração de status do interessado
if (isset($_GET['interessado_id']) && isset($_GET['status'])) {
    $interessado_id = (int)$_GET['interessado_id'];
    $novo_status = $_GET['status'];
    
    // Verificar se o status é válido
    if (in_array($novo_status, ['pendente', 'visualizado', 'contatado', 'recusado'])) {
        try {
            $db->execute("
                UPDATE demandas_interessados 
                SET status = ? 
                WHERE id = ? AND demanda_id = ?
            ", [$novo_status, $interessado_id, $demanda_id]);
            
            $_SESSION['flash_message'] = "Status do interessado atualizado com sucesso!";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            $_SESSION['flash_message'] = "Erro ao atualizar status: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        // Redirecionar para evitar reenvio
        echo "<script>window.location.href = '" . SITE_URL . "/?route=visualizar_interessados&demanda_id=" . $demanda_id . "';</script>";
        exit;
    }
}

// Obter as profissões da demanda
$profissoes = $db->fetchAll("
    SELECT profissao FROM demandas_profissoes WHERE demanda_id = ?
", [$demanda_id]);

// Configuração de paginação
$interessados_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $interessados_por_pagina;

// Filtro de status
$filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : '';
$sql_status = "";
$params = [$demanda_id];

if (!empty($filtro_status) && in_array($filtro_status, ['pendente', 'visualizado', 'contatado', 'recusado'])) {
    $sql_status = " AND di.status = ?";
    $params[] = $filtro_status;
}

// Obter total de interessados
$total_interessados = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM demandas_interessados di
    WHERE di.demanda_id = ? $sql_status
", $params);

// Obter interessados
$interessados = $db->fetchAll("
    SELECT di.*, t.profissao, t.experiencia, t.resumo, 
           u.nome, u.email, u.foto_perfil, u.data_cadastro, u.ultimo_acesso
    FROM demandas_interessados di
    JOIN talentos t ON di.talento_id = t.id
    JOIN usuarios u ON t.usuario_id = u.id
    WHERE di.demanda_id = ? $sql_status
    ORDER BY 
        CASE 
            WHEN di.status = 'pendente' THEN 1
            WHEN di.status = 'visualizado' THEN 2
            WHEN di.status = 'contatado' THEN 3
            WHEN di.status = 'recusado' THEN 4
        END,
        di.data_interesse DESC
    LIMIT $interessados_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_interessados / $interessados_por_pagina);

// Obter estatísticas
$total_pendentes = $db->fetchColumn("SELECT COUNT(*) FROM demandas_interessados WHERE demanda_id = ? AND status = 'pendente'", [$demanda_id]);
$total_visualizados = $db->fetchColumn("SELECT COUNT(*) FROM demandas_interessados WHERE demanda_id = ? AND status = 'visualizado'", [$demanda_id]);
$total_contatados = $db->fetchColumn("SELECT COUNT(*) FROM demandas_interessados WHERE demanda_id = ? AND status = 'contatado'", [$demanda_id]);
$total_recusados = $db->fetchColumn("SELECT COUNT(*) FROM demandas_interessados WHERE demanda_id = ? AND status = 'recusado'", [$demanda_id]);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="row">
                        <div class="col-6">
                            <h5>Interessados na Demanda</h5>
                            <p class="text-sm mb-0">
                                <i class="fas fa-briefcase me-1"></i>
                                <?php echo htmlspecialchars($demanda['titulo']); ?>
                            </p>
                        </div>
                        <div class="col-6 text-end">
                            <a href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas" class="btn btn-outline-secondary btn-sm mb-0">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Resumo da demanda -->
                <div class="card-body pt-0 pb-2">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="mb-2">Detalhes da demanda</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="text-sm mb-1">
                                        <strong>Status:</strong>
                                        <?php if ($demanda['status'] == 'ativa'): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php elseif ($demanda['status'] == 'inativa'): ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Concluída</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-sm mb-1">
                                        <strong>Data de publicação:</strong>
                                        <?php echo date('d/m/Y', strtotime($demanda['data_publicacao'])); ?>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <?php if (!empty($demanda['modelo_trabalho'])): ?>
                                    <p class="text-sm mb-1">
                                        <strong>Modelo de trabalho:</strong>
                                        <?php echo htmlspecialchars($demanda['modelo_trabalho']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($demanda['nivel_experiencia'])): ?>
                                    <p class="text-sm mb-1">
                                        <strong>Nível de experiência:</strong>
                                        <?php echo htmlspecialchars($demanda['nivel_experiencia']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <?php if (!empty($demanda['prazo_contratacao'])): ?>
                                    <p class="text-sm mb-1">
                                        <strong>Prazo para contratação:</strong>
                                        <?php echo date('d/m/Y', strtotime($demanda['prazo_contratacao'])); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <p class="text-sm mb-1"><strong>Profissões desejadas:</strong></p>
                                <div class="profissoes-tags">
                                    <?php foreach ($profissoes as $prof): ?>
                                        <span class="badge bg-light text-dark mb-1 me-1"><?php echo htmlspecialchars($prof['profissao']); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h6 class="mb-2">Estatísticas de interessados</h6>
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <div class="d-flex">
                                        <div>
                                            <div class="icon icon-shape bg-warning text-white rounded-circle shadow text-center me-2">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-sm">Pendentes</span>
                                            <h5 class="mb-0"><?php echo $total_pendentes; ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="d-flex">
                                        <div>
                                            <div class="icon icon-shape bg-info text-white rounded-circle shadow text-center me-2">
                                                <i class="fas fa-eye"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-sm">Visualizados</span>
                                            <h5 class="mb-0"><?php echo $total_visualizados; ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="d-flex">
                                        <div>
                                            <div class="icon icon-shape bg-success text-white rounded-circle shadow text-center me-2">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-sm">Contatados</span>
                                            <h5 class="mb-0"><?php echo $total_contatados; ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <div class="d-flex">
                                        <div>
                                            <div class="icon icon-shape bg-danger text-white rounded-circle shadow text-center me-2">
                                                <i class="fas fa-times"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-sm">Recusados</span>
                                            <h5 class="mb-0"><?php echo $total_recusados; ?></h5>
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
                        <input type="hidden" name="route" value="visualizar_interessados">
                        <input type="hidden" name="demanda_id" value="<?php echo $demanda_id; ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filtro_status">Filtrar por status:</label>
                                    <select class="form-control" id="filtro_status" name="filtro_status" onchange="this.form.submit()">
                                        <option value="">Todos os status</option>
                                        <option value="pendente" <?php echo ($filtro_status == 'pendente') ? 'selected' : ''; ?>>Pendentes</option>
                                        <option value="visualizado" <?php echo ($filtro_status == 'visualizado') ? 'selected' : ''; ?>>Visualizados</option>
                                        <option value="contatado" <?php echo ($filtro_status == 'contatado') ? 'selected' : ''; ?>>Contatados</option>
                                        <option value="recusado" <?php echo ($filtro_status == 'recusado') ? 'selected' : ''; ?>>Recusados</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Lista de interessados -->
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <?php if (empty($interessados)): ?>
                            <div class="text-center p-4">
                                <p class="mb-0">Nenhum interessado encontrado para esta demanda.</p>
                            </div>
                        <?php else: ?>
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Talento</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Profissão</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Experiência</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Data de Interesse</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interessados as $interessado): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div>
                                                        <?php if (isset($interessado['foto_perfil']) && !empty($interessado['foto_perfil'])): ?>
                                                            <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $interessado['foto_perfil']; ?>" class="avatar avatar-sm me-3" alt="<?php echo htmlspecialchars($interessado['nome']); ?>">
                                                        <?php else: ?>
                                                            <div class="avatar avatar-sm me-3 bg-gradient-primary">
                                                                <?php echo strtoupper(substr($interessado['nome'], 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($interessado['nome']); ?></h6>
                                                        <p class="text-xs text-secondary mb-0"><?php echo htmlspecialchars($interessado['email']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?php echo htmlspecialchars($interessado['profissao'] ?? 'Não informada'); ?></p>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    <?php echo !empty($interessado['experiencia']) ? $interessado['experiencia'] . ' anos' : 'Não informada'; ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    <?php echo date('d/m/Y H:i', strtotime($interessado['data_interesse'])); ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php if ($interessado['status'] == 'pendente'): ?>
                                                    <span class="badge bg-warning">Pendente</span>
                                                <?php elseif ($interessado['status'] == 'visualizado'): ?>
                                                    <span class="badge bg-info">Visualizado</span>
                                                <?php elseif ($interessado['status'] == 'contatado'): ?>
                                                    <span class="badge bg-success">Contatado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Recusado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $interessado['usuario_id']; ?>" class="btn btn-sm btn-info" title="Ver perfil" target="_blank">
                                                        <i class="fas fa-user"></i>
                                                    </a>
                                                    
                                                    <!-- Botões de alteração de status -->
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Status
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if ($interessado['status'] != 'pendente'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda_id; ?>&interessado_id=<?php echo $interessado['id']; ?>&status=pendente">
                                                                        <i class="fas fa-clock me-2"></i>Marcar como pendente
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($interessado['status'] != 'visualizado'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda_id; ?>&interessado_id=<?php echo $interessado['id']; ?>&status=visualizado">
                                                                        <i class="fas fa-eye me-2"></i>Marcar como visualizado
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($interessado['status'] != 'contatado'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda_id; ?>&interessado_id=<?php echo $interessado['id']; ?>&status=contatado">
                                                                        <i class="fas fa-phone me-2"></i>Marcar como contatado
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($interessado['status'] != 'recusado'): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda_id; ?>&interessado_id=<?php echo $interessado['id']; ?>&status=recusado">
                                                                        <i class="fas fa-times me-2"></i>Marcar como recusado
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                    
                                                    <a href="<?php echo SITE_URL; ?>/?route=enviar_mensagem&destinatario_id=<?php echo $interessado['usuario_id']; ?>&assunto=Interesse na demanda: <?php echo urlencode($demanda['titulo']); ?>" class="btn btn-sm btn-success" title="Enviar mensagem">
                                                        <i class="fas fa-envelope"></i>
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
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda_id; ?>&pagina=<?php echo $pagina_atual - 1; ?>&filtro_status=<?php echo $filtro_status; ?>">
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
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda_id; ?>&pagina=<?php echo $i; ?>&filtro_status=<?php echo $filtro_status; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagina_atual < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=visualizar_interessados&demanda_id=<?php echo $demanda_id; ?>&pagina=<?php echo $pagina_atual + 1; ?>&filtro_status=<?php echo $filtro_status; ?>">
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

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
