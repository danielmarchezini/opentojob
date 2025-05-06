<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Definir ação (pendentes, aprovadas, rejeitadas)
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'pendentes';

// Verificar se a tabela avaliacoes existe
$tabela_existe = $db->fetch("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'avaliacoes'");
$tabela_existe = ($tabela_existe && $tabela_existe['count'] > 0);

// Processar ações de aprovação ou rejeição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aprovar_avaliacao']) && isset($_POST['avaliacao_id'])) {
        $avaliacao_id = (int)$_POST['avaliacao_id'];
        
        $db->update('avaliacoes', [
            'aprovada' => 1
        ], 'id = :id', [
            'id' => $avaliacao_id
        ]);
        
        $_SESSION['flash_message'] = "Avaliação aprovada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } elseif (isset($_POST['rejeitar_avaliacao']) && isset($_POST['avaliacao_id'])) {
        $avaliacao_id = (int)$_POST['avaliacao_id'];
        
        $db->update('avaliacoes', [
            'aprovada' => 0
        ], 'id = :id', [
            'id' => $avaliacao_id
        ]);
        
        $_SESSION['flash_message'] = "Avaliação rejeitada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } elseif (isset($_POST['excluir_avaliacao']) && isset($_POST['avaliacao_id'])) {
        $avaliacao_id = (int)$_POST['avaliacao_id'];
        
        $db->delete('avaliacoes', 'id = :id', [
            'id' => $avaliacao_id
        ]);
        
        $_SESSION['flash_message'] = "Avaliação excluída com sucesso!";
        $_SESSION['flash_type'] = "success";
    }
    
    // Redirecionar para evitar reenvio do formulário
    header("Location: " . SITE_URL . "/?route=gerenciar_avaliacoes_admin&acao=" . $acao);
    exit;
}

// Inicializar variáveis
$avaliacoes = [];
$total_pendentes = 0;
$total_aprovadas = 0;
$total_rejeitadas = 0;

// Obter avaliações com base na ação apenas se a tabela existir
if ($tabela_existe) {
    try {
        if ($acao === 'pendentes') {
            $avaliacoes = $db->fetchAll("
                SELECT a.*, u.nome as talento_nome, t.profissao
                FROM avaliacoes a
                JOIN usuarios u ON a.talento_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id
                WHERE a.aprovada = 0
                ORDER BY a.data_avaliacao DESC
            ");
            
            $titulo_pagina = "Avaliações Pendentes";
        } elseif ($acao === 'aprovadas') {
            $avaliacoes = $db->fetchAll("
                SELECT a.*, u.nome as talento_nome, t.profissao
                FROM avaliacoes a
                JOIN usuarios u ON a.talento_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id
                WHERE a.aprovada = 1
                ORDER BY a.data_avaliacao DESC
            ");
            
            $titulo_pagina = "Avaliações Aprovadas";
        } elseif ($acao === 'rejeitadas') {
            $avaliacoes = $db->fetchAll("
                SELECT a.*, u.nome as talento_nome, t.profissao
                FROM avaliacoes a
                JOIN usuarios u ON a.talento_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id
                WHERE a.aprovada = 0 AND a.rejeitada = 1
                ORDER BY a.data_avaliacao DESC
            ");
            
            $titulo_pagina = "Avaliações Rejeitadas";
        } else {
            // Ação inválida, redirecionar para pendentes
            header("Location: " . SITE_URL . "/?route=gerenciar_avaliacoes_admin&acao=pendentes");
            exit;
        }

        // Contar avaliações pendentes, aprovadas e rejeitadas
        $total_pendentes = $db->fetchColumn("
            SELECT COUNT(*) FROM avaliacoes WHERE aprovada = 0 AND rejeitada = 0
        ");
        $total_aprovadas = $db->fetchColumn("
            SELECT COUNT(*) FROM avaliacoes WHERE aprovada = 1
        ");
        $total_rejeitadas = $db->fetchColumn("
            SELECT COUNT(*) FROM avaliacoes WHERE aprovada = 0 AND rejeitada = 1
        ");
    } catch (Exception $e) {
        // Silenciar erros
    }
} else {
    if ($acao === 'pendentes') {
        $titulo_pagina = "Avaliações Pendentes";
    } elseif ($acao === 'aprovadas') {
        $titulo_pagina = "Avaliações Aprovadas";
    } elseif ($acao === 'rejeitadas') {
        $titulo_pagina = "Avaliações Rejeitadas";
    } else {
        // Ação inválida, redirecionar para pendentes
        header("Location: " . SITE_URL . "/?route=gerenciar_avaliacoes_admin&acao=pendentes");
        exit;
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Avaliações</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Avaliações</li>
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

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter me-1"></i>
                    Filtros
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_avaliacoes&acao=pendentes" class="list-group-item list-group-item-action <?php echo ($acao === 'pendentes') ? 'active' : ''; ?>">
                            <i class="fas fa-clock me-2"></i> Pendentes
                            <?php if ($total_pendentes > 0): ?>
                                <span class="badge bg-primary float-end"><?php echo $total_pendentes; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_avaliacoes&acao=aprovadas" class="list-group-item list-group-item-action <?php echo ($acao === 'aprovadas') ? 'active' : ''; ?>">
                            <i class="fas fa-check me-2"></i> Aprovadas
                            <?php if ($total_aprovadas > 0): ?>
                                <span class="badge bg-success float-end"><?php echo $total_aprovadas; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/?page=gerenciar_avaliacoes&acao=rejeitadas" class="list-group-item list-group-item-action <?php echo ($acao === 'rejeitadas') ? 'active' : ''; ?>">
                            <i class="fas fa-times me-2"></i> Rejeitadas
                            <?php if ($total_rejeitadas > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $total_rejeitadas; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-star me-1"></i>
                    <?php echo $titulo_pagina; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($avaliacoes)): ?>
                        <div class="alert alert-info">
                            Nenhuma avaliação <?php echo ($acao === 'pendentes') ? 'pendente' : (($acao === 'aprovadas') ? 'aprovada' : 'rejeitada'); ?> encontrada.
                        </div>
                    <?php else: ?>
                        <table id="avaliacoesTable" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Talento</th>
                                    <th>Empresa</th>
                                    <th>Nota</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avaliacoes as $avaliacao): ?>
                                    <tr>
                                        <td><?php echo $avaliacao['id']; ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $avaliacao['talento_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($avaliacao['talento_nome']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa&id=<?php echo $avaliacao['empresa_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($avaliacao['empresa_nome']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo number_format($avaliacao['nota'], 1); ?></span>
                                                <div class="stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $avaliacao['nota']): ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php elseif ($i - 0.5 <= $avaliacao['nota']): ?>
                                                            <i class="fas fa-star-half-alt text-warning"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-warning"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($avaliacao['data_criacao'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" onclick="visualizarAvaliacao(<?php echo $avaliacao['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($acao === 'pendentes'): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="avaliacao_id" value="<?php echo $avaliacao['id']; ?>">
                                                        <input type="hidden" name="aprovar_avaliacao" value="1">
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Tem certeza que deseja aprovar esta avaliação?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="avaliacao_id" value="<?php echo $avaliacao['id']; ?>">
                                                        <input type="hidden" name="rejeitar_avaliacao" value="1">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja rejeitar esta avaliação?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Avaliação -->
<div class="modal fade" id="modalVisualizarAvaliacao" tabindex="-1" aria-labelledby="modalVisualizarAvaliacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarAvaliacaoLabel">Detalhes da Avaliação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="avaliacaoDetalhes">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
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
$(document).ready(function() {
    $('#avaliacoesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json'
        },
        order: [[0, 'desc']]
    });
});

function visualizarAvaliacao(id) {
    // Mostrar loading
    document.getElementById('avaliacaoDetalhes').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    // Abrir modal
    $('#modalVisualizarAvaliacao').modal('show');
    
    // Carregar detalhes da avaliação via AJAX
    fetch('<?php echo SITE_URL; ?>/?route=api_avaliacao_detalhe&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const avaliacao = data.avaliacao;
                
                // Construir HTML com os detalhes da avaliação
                let html = `
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Informações do Talento</h5>
                            <p><strong>Nome:</strong> ${avaliacao.talento_nome}</p>
                            <p><strong>Profissão:</strong> ${avaliacao.profissao || 'Não informado'}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Informações do Avaliador</h5>
                            <p><strong>Nome:</strong> ${avaliacao.nome_avaliador}</p>
                            <p><strong>LinkedIn:</strong> ${avaliacao.linkedin_avaliador ? `<a href="${avaliacao.linkedin_avaliador}" target="_blank">${avaliacao.linkedin_avaliador}</a>` : 'Não informado'}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Detalhes da Avaliação</h5>
                            <p><strong>Data:</strong> ${new Date(avaliacao.data_criacao).toLocaleString('pt-BR')}</p>
                            <p><strong>Nota:</strong> ${avaliacao.nota}</p>
                            <p><strong>Status:</strong> ${avaliacao.aprovada ? '<span class="badge bg-success">Aprovada</span>' : '<span class="badge bg-warning">Pendente</span>'}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Conteúdo da Avaliação</h5>
                            <div class="p-3 bg-light rounded">
                                ${avaliacao.avaliacao.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('avaliacaoDetalhes').innerHTML = html;
            } else {
                document.getElementById('avaliacaoDetalhes').innerHTML = `
                    <div class="alert alert-danger">
                        Erro ao carregar detalhes da avaliação: ${data.message || 'Erro desconhecido'}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('avaliacaoDetalhes').innerHTML = `
                <div class="alert alert-danger">
                    Erro ao carregar detalhes da avaliação: ${error.message}
                </div>
            `;
        });
}
</script>
