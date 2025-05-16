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

// Ação de exclusão
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Verificar se o perfil existe
        $perfil = $db->fetch("SELECT * FROM perfis_linkedin WHERE id = :id", ['id' => $id]);
        
        if ($perfil) {
            // Excluir o perfil
            $db->execute("DELETE FROM perfis_linkedin WHERE id = :id", ['id' => $id]);
            
            // Se tiver foto e não for a padrão, excluir a foto
            if ($perfil['foto'] !== 'default-profile.jpg') {
                $caminho_foto = dirname(dirname(dirname(__FILE__))) . '/uploads/perfis_linkedin/' . $perfil['foto'];
                if (file_exists($caminho_foto)) {
                    unlink($caminho_foto);
                }
            }
            
            $mensagem = 'Perfil excluído com sucesso!';
            $tipo_mensagem = 'success';
        } else {
            $mensagem = 'Perfil não encontrado.';
            $tipo_mensagem = 'danger';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao excluir perfil: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Ação de alteração de status
if (isset($_GET['acao']) && $_GET['acao'] === 'alterarStatus' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'] === 'ativo' ? 'ativo' : 'inativo';
    
    try {
        $db->execute("UPDATE perfis_linkedin SET status = :status WHERE id = :id", [
            'id' => $id,
            'status' => $status
        ]);
        
        $mensagem = 'Status do perfil alterado com sucesso!';
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = 'Erro ao alterar status do perfil: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Ação de alteração de destaque
if (isset($_GET['acao']) && $_GET['acao'] === 'alterarDestaque' && isset($_GET['id']) && isset($_GET['destaque'])) {
    $id = (int)$_GET['id'];
    $destaque = $_GET['destaque'] === '1' ? 1 : 0;
    
    try {
        $db->execute("UPDATE perfis_linkedin SET destaque = :destaque WHERE id = :id", [
            'id' => $id,
            'destaque' => $destaque
        ]);
        
        $mensagem = 'Destaque do perfil alterado com sucesso!';
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $mensagem = 'Erro ao alterar destaque do perfil: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Configuração de paginação
$perfis_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $perfis_por_pagina;

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$filtro_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filtro_destaque = isset($_GET['destaque']) ? trim($_GET['destaque']) : '';

// Construir consulta SQL com base nos filtros
$sql_where = "WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql_where .= " AND (nome LIKE :busca OR assunto LIKE :busca)";
    $params['busca'] = "%$busca%";
}

if (!empty($filtro_status)) {
    $sql_where .= " AND status = :status";
    $params['status'] = $filtro_status;
}

if (!empty($filtro_destaque)) {
    $sql_where .= " AND destaque = :destaque";
    $params['destaque'] = $filtro_destaque;
}

// Consulta para obter o total de perfis
$total_perfis = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM perfis_linkedin
    $sql_where
", $params);

// Consulta para obter os perfis da página atual
$perfis = $db->fetchAll("
    SELECT *
    FROM perfis_linkedin
    $sql_where
    ORDER BY destaque DESC, nome ASC
    LIMIT $perfis_por_pagina OFFSET $offset
", $params);

// Calcular o número total de páginas
$total_paginas = ceil($total_perfis / $perfis_por_pagina);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Perfis do LinkedIn</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Perfis do LinkedIn</li>
    </ol>
    
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fab fa-linkedin me-1"></i>
                    Perfis do LinkedIn
                </div>
                <div>
                    <a href="<?php echo SITE_URL; ?>/?route=adicionar_perfil_linkedin" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Adicionar Novo Perfil
                    </a>
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_indicacoes_perfis" class="btn btn-info btn-sm ms-2">
                        <i class="fas fa-user-check me-1"></i> Gerenciar Indicações
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <form action="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin" method="GET" class="row g-3">
                        <input type="hidden" name="route" value="gerenciar_perfis_linkedin">
                        
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" id="busca" name="busca" placeholder="Buscar por nome ou assunto" value="<?php echo htmlspecialchars((string)$busca); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos os status</option>
                                <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" id="destaque" name="destaque">
                                <option value="">Todos</option>
                                <option value="1" <?php echo $filtro_destaque === '1' ? 'selected' : ''; ?>>Em destaque</option>
                                <option value="0" <?php echo $filtro_destaque === '0' ? 'selected' : ''; ?>>Sem destaque</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabela de perfis -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Assunto</th>
                            <th>Status</th>
                            <th>Destaque</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($perfis)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum perfil encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($perfis as $perfil): ?>
                                <tr>
                                    <td><?php echo $perfil['id']; ?></td>
                                    <td class="text-center">
                                        <img src="<?php echo SITE_URL; ?>/uploads/perfis_linkedin/<?php echo htmlspecialchars((string)$perfil['foto']); ?>" 
                                             alt="<?php echo htmlspecialchars((string)$perfil['nome']); ?>" 
                                             class="img-thumbnail" 
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars((string)$perfil['nome']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$perfil['assunto']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $perfil['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                                            <?php echo $perfil['status'] === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($perfil['destaque']): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-star"></i> Destaque
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="far fa-star"></i> Normal
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($perfil['data_cadastro'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo $perfil['link_perfil']; ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Ver no LinkedIn">
                                                <i class="fab fa-linkedin"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/?route=editar_perfil_linkedin&id=<?php echo $perfil['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($perfil['status'] === 'ativo'): ?>
                                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&acao=alterarStatus&id=<?php echo $perfil['id']; ?>&status=inativo" class="btn btn-sm btn-outline-warning" title="Desativar" onclick="return confirm('Tem certeza que deseja desativar este perfil?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&acao=alterarStatus&id=<?php echo $perfil['id']; ?>&status=ativo" class="btn btn-sm btn-outline-success" title="Ativar">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($perfil['destaque']): ?>
                                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&acao=alterarDestaque&id=<?php echo $perfil['id']; ?>&destaque=0" class="btn btn-sm btn-outline-dark" title="Remover destaque">
                                                    <i class="far fa-star"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&acao=alterarDestaque&id=<?php echo $perfil['id']; ?>&destaque=1" class="btn btn-sm btn-outline-warning" title="Destacar">
                                                    <i class="fas fa-star"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&acao=excluir&id=<?php echo $perfil['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este perfil? Esta ação não pode ser desfeita.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&pagina=1<?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?><?php echo !empty($filtro_destaque) ? '&destaque=' . urlencode($filtro_destaque) : ''; ?>" aria-label="Primeira">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&pagina=<?php echo $pagina_atual - 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?><?php echo !empty($filtro_destaque) ? '&destaque=' . urlencode($filtro_destaque) : ''; ?>" aria-label="Anterior">
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
                            echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=gerenciar_perfis_linkedin&pagina=1' . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_status) ? '&status=' . urlencode($filtro_status) : '') . (!empty($filtro_destaque) ? '&destaque=' . urlencode($filtro_destaque) : '') . '">1</a></li>';
                            if ($inicio_intervalo > 2) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                        }
                        
                        // Exibir páginas do intervalo
                        for ($i = $inicio_intervalo; $i <= $fim_intervalo; $i++) {
                            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . SITE_URL . '/?route=gerenciar_perfis_linkedin&pagina=' . $i . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_status) ? '&status=' . urlencode($filtro_status) : '') . (!empty($filtro_destaque) ? '&destaque=' . urlencode($filtro_destaque) : '') . '">' . $i . '</a></li>';
                        }
                        
                        // Exibir última página se não estiver no intervalo
                        if ($fim_intervalo < $total_paginas) {
                            if ($fim_intervalo < $total_paginas - 1) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=gerenciar_perfis_linkedin&pagina=' . $total_paginas . (!empty($busca) ? '&busca=' . urlencode($busca) : '') . (!empty($filtro_status) ? '&status=' . urlencode($filtro_status) : '') . (!empty($filtro_destaque) ? '&destaque=' . urlencode($filtro_destaque) : '') . '">' . $total_paginas . '</a></li>';
                        }
                        ?>
                        
                        <?php if ($pagina_atual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&pagina=<?php echo $pagina_atual + 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?><?php echo !empty($filtro_destaque) ? '&destaque=' . urlencode($filtro_destaque) : ''; ?>" aria-label="Próxima">
                                    <span aria-hidden="true">&gt;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo SITE_URL; ?>/?route=gerenciar_perfis_linkedin&pagina=<?php echo $total_paginas; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?><?php echo !empty($filtro_status) ? '&status=' . urlencode($filtro_status) : ''; ?><?php echo !empty($filtro_destaque) ? '&destaque=' . urlencode($filtro_destaque) : ''; ?>" aria-label="Última">
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
