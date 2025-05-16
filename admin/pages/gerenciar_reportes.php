<?php
// Verificar se a tabela de reportes existe, caso contrário, criá-la
try {
    $db->execute("
        CREATE TABLE IF NOT EXISTS reportes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_reportado_id INT NOT NULL,
            tipo_usuario_reportado ENUM('talento', 'empresa') NOT NULL,
            usuario_reportante_id INT NULL,
            motivo VARCHAR(255) NOT NULL,
            descricao TEXT NOT NULL,
            status ENUM('pendente', 'revisado', 'arquivado') DEFAULT 'pendente',
            data_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_reportado_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_reportante_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )
    ");
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Erro ao criar tabela de reportes: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

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

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Reportes</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Reportes</li>
    </ol>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
        <?php echo $_SESSION['flash_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php 
        // Limpar mensagem flash
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    endif; ?>

    <!-- Lista de Reportes -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-flag me-1"></i>
            Reportes de Usuários
        </div>
        <div class="card-body">
            <table id="reportesTable" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário Reportado</th>
                        <th>Reportado por</th>
                        <th>Motivo</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportes)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhum reporte encontrado</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reportes as $reporte): ?>
                            <tr>
                                <td><?php echo $reporte['id']; ?></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/?route=<?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'perfil_talento' : 'perfil_empresa'; ?>&id=<?php echo $reporte['usuario_reportado_id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars((string)$reporte['usuario_reportado_nome']); ?>
                                    </a>
                                    <span class="badge bg-<?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'info' : 'primary'; ?>">
                                        <?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'Talento' : 'Empresa'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($reporte['usuario_reportante_id'])): ?>
                                        <?php echo htmlspecialchars((string)$reporte['usuario_reportante_nome']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Anônimo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)$reporte['motivo']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($reporte['data_reporte'])); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch ($reporte['status']) {
                                        case 'revisado':
                                            $status_class = 'success';
                                            break;
                                        case 'arquivado':
                                            $status_class = 'secondary';
                                            break;
                                        case 'pendente':
                                        default:
                                            $status_class = 'warning';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($reporte['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDetalhes<?php echo $reporte['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($reporte['status'] === 'pendente'): ?>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalRevisar<?php echo $reporte['id']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($reporte['status'] !== 'arquivado'): ?>
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#modalArquivar<?php echo $reporte['id']; ?>">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Modal de Detalhes -->
                            <div class="modal fade" id="modalDetalhes<?php echo $reporte['id']; ?>" tabindex="-1" aria-labelledby="modalDetalhesLabel<?php echo $reporte['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalDetalhesLabel<?php echo $reporte['id']; ?>">Detalhes do Reporte #<?php echo $reporte['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Usuário Reportado:</strong></div>
                                                <div class="col-md-8">
                                                    <a href="<?php echo SITE_URL; ?>/?route=<?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'perfil_talento' : 'perfil_empresa'; ?>&id=<?php echo $reporte['usuario_reportado_id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars((string)$reporte['usuario_reportado_nome']); ?>
                                                    </a>
                                                    <span class="badge bg-<?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'info' : 'primary'; ?>">
                                                        <?php echo $reporte['tipo_usuario_reportado'] === 'talento' ? 'Talento' : 'Empresa'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Reportado por:</strong></div>
                                                <div class="col-md-8">
                                                    <?php if (!empty($reporte['usuario_reportante_id'])): ?>
                                                        <?php echo htmlspecialchars((string)$reporte['usuario_reportante_nome']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Anônimo</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Motivo:</strong></div>
                                                <div class="col-md-8"><?php echo htmlspecialchars((string)$reporte['motivo']); ?></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Data:</strong></div>
                                                <div class="col-md-8"><?php echo date('d/m/Y H:i', strtotime($reporte['data_reporte'])); ?></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Status:</strong></div>
                                                <div class="col-md-8">
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($reporte['status']); ?>
                                                    </span>
                                                    <?php if ($reporte['status'] !== 'pendente'): ?>
                                                        <small class="text-muted d-block">Atualizado em: <?php echo date('d/m/Y H:i', strtotime($reporte['data_atualizacao'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Descrição:</strong></div>
                                                <div class="col-md-8">
                                                    <div class="p-2 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars((string)$reporte['descricao'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <small>Você pode tomar ações como suspender o usuário reportado ou entrar em contato para esclarecer a situação.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
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
                            
                            <!-- Modal de Revisar -->
                            <div class="modal fade" id="modalRevisar<?php echo $reporte['id']; ?>" tabindex="-1" aria-labelledby="modalRevisarLabel<?php echo $reporte['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalRevisarLabel<?php echo $reporte['id']; ?>">Revisar Reporte #<?php echo $reporte['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Deseja marcar este reporte como revisado?</p>
                                            <p>Isso indica que você analisou o reporte e tomou as medidas necessárias.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="post">
                                                <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                                <input type="hidden" name="acao" value="revisar">
                                                <button type="submit" class="btn btn-success">Marcar como Revisado</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal de Arquivar -->
                            <div class="modal fade" id="modalArquivar<?php echo $reporte['id']; ?>" tabindex="-1" aria-labelledby="modalArquivarLabel<?php echo $reporte['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalArquivarLabel<?php echo $reporte['id']; ?>">Arquivar Reporte #<?php echo $reporte['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Deseja arquivar este reporte?</p>
                                            <p>Reportes arquivados ficam ocultos da lista principal.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="post">
                                                <input type="hidden" name="reporte_id" value="<?php echo $reporte['id']; ?>">
                                                <input type="hidden" name="acao" value="arquivar">
                                                <button type="submit" class="btn btn-primary">Arquivar Reporte</button>
                                            </form>
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

<script>
$(document).ready(function() {
    $('#reportesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json'
        },
        order: [[0, 'desc']]
    });
});
</script>
