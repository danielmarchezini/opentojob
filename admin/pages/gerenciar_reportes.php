<?php
// Obter lista de reportes
$db = Database::getInstance();
try {
    $reportes = $db->fetchAll("
        SELECT r.*, 
               u_reportado.nome as usuario_reportado_nome,
               u_reportante.nome as usuario_reportante_nome
        FROM reportes r
        JOIN usuarios u_reportado ON r.usuario_reportado_id = u_reportado.id
        LEFT JOIN usuarios u_reportante ON r.usuario_reportante_id = u_reportante.id
        ORDER BY r.data_reporte DESC
    ");
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Erro ao carregar reportes: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $reportes = [];
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && isset($_POST['reporte_id'])) {
        $acao = $_POST['acao'];
        $reporte_id = (int)$_POST['reporte_id'];
        
        try {
            if ($acao === 'revisar') {
                // Marcar como revisado
                $db->query("UPDATE reportes SET status = 'revisado', data_atualizacao = NOW() WHERE id = :id", [
                    'id' => $reporte_id
                ]);
                
                $_SESSION['flash_message'] = "Reporte marcado como revisado.";
                $_SESSION['flash_type'] = "success";
            } elseif ($acao === 'arquivar') {
                // Arquivar reporte
                $db->query("UPDATE reportes SET status = 'arquivado', data_atualizacao = NOW() WHERE id = :id", [
                    'id' => $reporte_id
                ]);
                
                $_SESSION['flash_message'] = "Reporte arquivado.";
                $_SESSION['flash_type'] = "success";
            } elseif ($acao === 'excluir') {
                // Excluir reporte
                $db->query("DELETE FROM reportes WHERE id = :id", [
                    'id' => $reporte_id
                ]);
                
                $_SESSION['flash_message'] = "Reporte excluído com sucesso.";
                $_SESSION['flash_type'] = "success";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Erro ao processar ação: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        // Redirecionar para evitar reenvio do formulário
        header('Location: ' . SITE_URL . '/?route=gerenciar_reportes');
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gerenciar Reportes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Gerenciar Reportes</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
    <?php echo $_SESSION['flash_message']; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php 
    // Limpar mensagem flash
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
endif; ?>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Reportes de Usuários</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <input type="text" id="pesquisarReporte" class="form-control float-right" placeholder="Buscar">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário Reportado</th>
                            <th>Tipo</th>
                            <th>Motivo</th>
                            <th>Reportado por</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportes)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum reporte encontrado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reportes as $reporte): ?>
                                <tr>
                                    <td><?php echo $reporte['id']; ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/?route=<?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'perfil_talento' : 'perfil_empresa'; ?>&id=<?php echo $reporte['usuario_reportado_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($reporte['usuario_reportado_nome']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'badge-info' : 'badge-primary'; ?>">
                                            <?php echo ucfirst($reporte['tipo_usuario_reportado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($reporte['motivo']); ?></td>
                                    <td>
                                        <?php if (!empty($reporte['usuario_reportante_id'])): ?>
                                            <?php echo htmlspecialchars($reporte['usuario_reportante_nome']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Anônimo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($reporte['data_reporte'])); ?></td>
                                    <td>
                                        <?php
                                        $status = $reporte['status'];
                                        $status_class = '';
                                        switch ($status) {
                                            case 'revisado':
                                                $status_class = 'badge-success';
                                                break;
                                            case 'arquivado':
                                                $status_class = 'badge-secondary';
                                                break;
                                            case 'pendente':
                                            default:
                                                $status_class = 'badge-warning';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalDetalhes<?php echo $reporte['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($reporte['status'] === 'pendente'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                                <input type="hidden" name="acao" value="revisar">
                                                <button type="submit" class="btn btn-sm btn-success" title="Marcar como revisado">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($reporte['status'] !== 'arquivado'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                                <input type="hidden" name="acao" value="arquivar">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Arquivar reporte">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                                <input type="hidden" name="acao" value="excluir">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este reporte? Esta ação não pode ser desfeita.')" title="Excluir reporte">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Modal de Detalhes -->
                                <div class="modal fade" id="modalDetalhes<?php echo $reporte['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalDetalhesLabel<?php echo $reporte['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalDetalhesLabel<?php echo $reporte['id']; ?>">Detalhes do Reporte #<?php echo $reporte['id']; ?></h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Usuário Reportado:</strong></div>
                                                    <div class="col-md-8">
                                                        <a href="<?php echo SITE_URL; ?>/?route=<?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'perfil_talento' : 'perfil_empresa'; ?>&id=<?php echo $reporte['usuario_reportado_id']; ?>" target="_blank">
                                                            <?php echo htmlspecialchars($reporte['usuario_reportado_nome']); ?>
                                                        </a>
                                                        <span class="badge <?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'badge-info' : 'badge-primary'; ?> ml-2">
                                                            <?php echo ucfirst($reporte['tipo_usuario_reportado']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Reportado por:</strong></div>
                                                    <div class="col-md-8">
                                                        <?php if (!empty($reporte['usuario_reportante_id'])): ?>
                                                            <?php echo htmlspecialchars($reporte['usuario_reportante_nome']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Anônimo</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Motivo:</strong></div>
                                                    <div class="col-md-8"><?php echo htmlspecialchars($reporte['motivo']); ?></div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Data:</strong></div>
                                                    <div class="col-md-8"><?php echo date('d/m/Y H:i', strtotime($reporte['data_reporte'])); ?></div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Status:</strong></div>
                                                    <div class="col-md-8">
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                                        <?php if ($reporte['status'] !== 'pendente' && !empty($reporte['data_atualizacao'])): ?>
                                                            <small class="text-muted d-block">Atualizado em: <?php echo date('d/m/Y H:i', strtotime($reporte['data_atualizacao'])); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php if (!empty($reporte['descricao'])): ?>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Descrição:</strong></div>
                                                    <div class="col-md-8">
                                                        <div class="p-2 bg-light rounded">
                                                            <?php echo nl2br(htmlspecialchars($reporte['descricao'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="alert alert-info mt-3">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    <small>Você pode tomar ações como suspender o usuário reportado ou entrar em contato para esclarecer a situação.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                                                <?php if ($reporte['status'] === 'pendente'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                                    <input type="hidden" name="acao" value="revisar">
                                                    <button type="submit" class="btn btn-success">Marcar como Revisado</button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtro de pesquisa
    const inputPesquisa = document.getElementById('pesquisarReporte');
    if (inputPesquisa) {
        inputPesquisa.addEventListener('keyup', function() {
            const termo = this.value.toLowerCase();
            const tabela = document.querySelector('table tbody');
            const linhas = tabela.querySelectorAll('tr');
            
            linhas.forEach(function(linha) {
                const texto = linha.textContent.toLowerCase();
                if (texto.indexOf(termo) > -1) {
                    linha.style.display = '';
                } else {
                    linha.style.display = 'none';
                }
            });
        });
    }
});
</script>
