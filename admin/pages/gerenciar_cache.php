<?php
// Verificar se o usuário está logado como admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . SITE_URL . "/admin/login.php");
    exit;
}

// Incluir a classe de cache
require_once '../includes/Cache.php';

// Processar ações
$message = '';
$messageType = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_cache':
            // Criar cache para uma chave específica
            $key = isset($_POST['cache_key']) ? trim($_POST['cache_key']) : '';
            $group = isset($_POST['cache_group']) ? trim($_POST['cache_group']) : '';
            $ttl = isset($_POST['cache_ttl']) ? intval($_POST['cache_ttl']) : 3600;
            $data = isset($_POST['cache_data']) ? $_POST['cache_data'] : '';
            
            if (empty($key)) {
                $message = "A chave do cache é obrigatória.";
                $messageType = "danger";
            } else {
                // Adicionar prefixo de grupo se fornecido
                if (!empty($group)) {
                    $key = $group . '_' . $key;
                }
                
                if (Cache::set($key, $data, $ttl)) {
                    $message = "Cache criado com sucesso para a chave: $key";
                    $messageType = "success";
                } else {
                    $message = "Erro ao criar cache para a chave: $key";
                    $messageType = "danger";
                }
            }
            break;
            
        case 'delete_cache':
            // Excluir cache para uma chave específica
            $key = isset($_POST['cache_key']) ? trim($_POST['cache_key']) : '';
            
            if (empty($key)) {
                $message = "A chave do cache é obrigatória para exclusão.";
                $messageType = "danger";
            } else {
                if (Cache::delete($key)) {
                    $message = "Cache excluído com sucesso para a chave: $key";
                    $messageType = "success";
                } else {
                    $message = "Erro ao excluir cache para a chave: $key";
                    $messageType = "danger";
                }
            }
            break;
            
        case 'clear_group':
            // Limpar cache para um grupo específico
            $group = isset($_POST['cache_group']) ? trim($_POST['cache_group']) : '';
            
            if (empty($group)) {
                $message = "O grupo de cache é obrigatório para limpeza.";
                $messageType = "danger";
            } else {
                if (Cache::clear($group)) {
                    $message = "Cache limpo com sucesso para o grupo: $group";
                    $messageType = "success";
                } else {
                    $message = "Erro ao limpar cache para o grupo: $group";
                    $messageType = "danger";
                }
            }
            break;
            
        case 'clear_all':
            // Limpar todo o cache
            if (Cache::clear()) {
                $message = "Todo o cache foi limpo com sucesso.";
                $messageType = "success";
            } else {
                $message = "Erro ao limpar todo o cache.";
                $messageType = "danger";
            }
            break;
            
        case 'clear_expired':
            // Limpar apenas cache expirado
            $cacheInfo = Cache::getInfo();
            $expiredCount = 0;
            
            foreach ($cacheInfo['files'] as $file) {
                if ($file['expired']) {
                    if (Cache::delete($file['key'])) {
                        $expiredCount++;
                    }
                }
            }
            
            $message = "$expiredCount arquivos de cache expirados foram removidos.";
            $messageType = "success";
            break;
    }
}

// Obter informações do cache
$cacheInfo = Cache::getInfo();

// Formatar tamanho para exibição
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Formatar data para exibição
function formatCacheDate($timestamp) {
    return date('d/m/Y H:i:s', $timestamp);
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gerenciamento de Cache</h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Criar Cache</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="create_cache">
                        
                        <div class="form-group">
                            <label for="cache_group">Grupo (opcional)</label>
                            <input type="text" class="form-control" id="cache_group" name="cache_group" placeholder="Ex: vagas, empresas, etc.">
                            <small class="form-text text-muted">O grupo ajuda a organizar e limpar o cache por categorias.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="cache_key">Chave <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cache_key" name="cache_key" required placeholder="Ex: lista_vagas_destaque">
                            <small class="form-text text-muted">Identificador único para este cache.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="cache_ttl">Tempo de Vida (segundos)</label>
                            <input type="number" class="form-control" id="cache_ttl" name="cache_ttl" value="3600" min="0">
                            <small class="form-text text-muted">0 = sem expiração, 3600 = 1 hora, 86400 = 1 dia</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="cache_data">Dados</label>
                            <textarea class="form-control" id="cache_data" name="cache_data" rows="5" placeholder="Dados a serem armazenados em cache"></textarea>
                            <small class="form-text text-muted">Conteúdo a ser armazenado no cache.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Criar Cache</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Excluir Cache</h6>
                </div>
                <div class="card-body">
                    <form method="post" class="mb-4">
                        <input type="hidden" name="action" value="delete_cache">
                        
                        <div class="form-group">
                            <label for="delete_cache_key">Chave <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="delete_cache_key" name="cache_key" required placeholder="Ex: vagas_lista_recentes">
                        </div>
                        
                        <button type="submit" class="btn btn-danger">Excluir Cache</button>
                    </form>
                    
                    <hr>
                    
                    <form method="post" class="mb-4">
                        <input type="hidden" name="action" value="clear_group">
                        
                        <div class="form-group">
                            <label for="clear_cache_group">Grupo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="clear_cache_group" name="cache_group" required placeholder="Ex: vagas">
                        </div>
                        
                        <button type="submit" class="btn btn-warning">Limpar Grupo</button>
                    </form>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <form method="post">
                                <input type="hidden" name="action" value="clear_expired">
                                <button type="submit" class="btn btn-info btn-block">Limpar Cache Expirado</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="post">
                                <input type="hidden" name="action" value="clear_all">
                                <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Tem certeza que deseja limpar todo o cache?');">Limpar Todo o Cache</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informações do Cache</h6>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Arquivos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cacheInfo['total_files']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tamanho Total</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatSize($cacheInfo['total_size']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-database fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Grupos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($cacheInfo['groups']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-folder fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Expirados</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cacheInfo['expired_files']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (count($cacheInfo['groups']) > 0): ?>
                <h5 class="mb-3">Grupos de Cache</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th>Arquivos</th>
                                <th>Tamanho</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cacheInfo['groups'] as $group => $info): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($group); ?></td>
                                    <td><?php echo $info['count']; ?></td>
                                    <td><?php echo formatSize($info['size']); ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="clear_group">
                                            <input type="hidden" name="cache_group" value="<?php echo htmlspecialchars($group); ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">Limpar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (count($cacheInfo['files']) > 0): ?>
                <h5 class="mb-3">Arquivos de Cache</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="cacheTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Chave</th>
                                <th>Grupo</th>
                                <th>Tamanho</th>
                                <th>Criado em</th>
                                <th>Expira em</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cacheInfo['files'] as $file): ?>
                                <tr class="<?php echo $file['expired'] ? 'table-danger' : ''; ?>">
                                    <td><?php echo htmlspecialchars($file['key']); ?></td>
                                    <td><?php echo htmlspecialchars($file['group']); ?></td>
                                    <td><?php echo formatSize($file['size']); ?></td>
                                    <td><?php echo formatCacheDate($file['created']); ?></td>
                                    <td>
                                        <?php echo $file['expires'] > 0 ? formatCacheDate($file['expires']) : 'Sem expiração'; ?>
                                    </td>
                                    <td>
                                        <?php if ($file['expired']): ?>
                                            <span class="badge badge-danger">Expirado</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="delete_cache">
                                            <input type="hidden" name="cache_key" value="<?php echo htmlspecialchars($file['key']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Nenhum arquivo de cache encontrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable para a tabela de cache
    $('#cacheTable').DataTable({
        "language": {
            "url": "/open2w/assets/js/pt-BR.json"
        },
        "order": [[3, "desc"]] // Ordenar por data de criação (decrescente)
    });
});
</script>
