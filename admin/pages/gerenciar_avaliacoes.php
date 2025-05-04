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
        } else {
            // Ação inválida, redirecionar para pendentes
            header("Location: " . SITE_URL . "/?route=gerenciar_avaliacoes_admin&acao=pendentes");
            exit;
        }

        // Contar avaliações pendentes
        $total_pendentes = $db->fetchColumn("
            SELECT COUNT(*) FROM avaliacoes WHERE aprovada = 0
        ");
    } catch (Exception $e) {
        // Silenciar erros
    }
} else {
    if ($acao === 'pendentes') {
        $titulo_pagina = "Avaliações Pendentes";
    } elseif ($acao === 'aprovadas') {
        $titulo_pagina = "Avaliações Aprovadas";
    } else {
        // Ação inválida, redirecionar para pendentes
        header("Location: " . SITE_URL . "/?route=gerenciar_avaliacoes_admin&acao=pendentes");
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gerenciar Avaliações</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Gerenciar Avaliações</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filtros</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=pendentes" class="nav-link <?php echo ($acao === 'pendentes') ? 'active' : ''; ?>">
                                    Pendentes
                                    <?php if ($total_pendentes > 0): ?>
                                        <span class="badge bg-warning float-right"><?php echo $total_pendentes; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=aprovadas" class="nav-link <?php echo ($acao === 'aprovadas') ? 'active' : ''; ?>">
                                    Aprovadas
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $titulo_pagina; ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($avaliacoes)): ?>
                            <div class="alert alert-info">
                                Nenhuma avaliação <?php echo ($acao === 'pendentes') ? 'pendente' : 'aprovada'; ?> encontrada.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Talento</th>
                                            <th>Avaliador</th>
                                            <th>Pontuação</th>
                                            <th>Data</th>
                                            <th>Pública</th>
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
                                                    <?php if (!empty($avaliacao['profissao'])): ?>
                                                        <small class="d-block text-muted"><?php echo htmlspecialchars($avaliacao['profissao']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($avaliacao['nome_avaliador']); ?>
                                                    <?php if (!empty($avaliacao['linkedin_avaliador'])): ?>
                                                        <a href="<?php echo htmlspecialchars($avaliacao['linkedin_avaliador']); ?>" target="_blank" class="ml-1">
                                                            <i class="fab fa-linkedin"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $avaliacao['pontuacao']) {
                                                            echo '<i class="fas fa-star text-warning"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star text-muted"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($avaliacao['data_avaliacao'])); ?></td>
                                                <td>
                                                    <?php if ($avaliacao['publica']): ?>
                                                        <span class="badge bg-success">Sim</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Não</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info" onclick="visualizarAvaliacao(<?php echo $avaliacao['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($acao === 'pendentes'): ?>
                                                        <form action="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=pendentes" method="POST" class="d-inline">
                                                            <input type="hidden" name="avaliacao_id" value="<?php echo $avaliacao['id']; ?>">
                                                            <input type="hidden" name="aprovar_avaliacao" value="1">
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form action="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=aprovadas" method="POST" class="d-inline">
                                                            <input type="hidden" name="avaliacao_id" value="<?php echo $avaliacao['id']; ?>">
                                                            <input type="hidden" name="rejeitar_avaliacao" value="1">
                                                            <button type="submit" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form action="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=<?php echo $acao; ?>" method="POST" class="d-inline">
                                                        <input type="hidden" name="avaliacao_id" value="<?php echo $avaliacao['id']; ?>">
                                                        <input type="hidden" name="excluir_avaliacao" value="1">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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
</section>

<!-- Modal Visualizar Avaliação -->
<div class="modal fade" id="modalVisualizarAvaliacao" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarAvaliacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarAvaliacaoLabel">Detalhes da Avaliação</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="avaliacaoDetalhes" class="p-3">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p>Carregando detalhes da avaliação...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                
                <div id="botoesAcao">
                    <!-- Os botões de ação serão inseridos aqui via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Função para visualizar avaliação
function visualizarAvaliacao(id) {
    // Mostrar loading
    document.getElementById('avaliacaoDetalhes').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
            <p>Carregando detalhes da avaliação...</p>
        </div>
    `;
    
    // Limpar botões de ação
    document.getElementById('botoesAcao').innerHTML = '';
    
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
                            <p><strong>Data:</strong> ${new Date(avaliacao.data_avaliacao).toLocaleString('pt-BR')}</p>
                            <p><strong>Pontuação:</strong> 
                                ${Array(5).fill(0).map((_, i) => 
                                    i < avaliacao.pontuacao 
                                        ? '<i class="fas fa-star text-warning"></i>' 
                                        : '<i class="far fa-star text-muted"></i>'
                                ).join('')}
                            </p>
                            <p><strong>Pública:</strong> ${avaliacao.publica ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</p>
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
                
                // Adicionar botões de ação com base no status da avaliação
                let botoesHtml = '';
                
                if (avaliacao.aprovada) {
                    botoesHtml = `
                        <form action="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=aprovadas" method="POST" class="d-inline">
                            <input type="hidden" name="avaliacao_id" value="${avaliacao.id}">
                            <input type="hidden" name="rejeitar_avaliacao" value="1">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-times me-2"></i> Rejeitar
                            </button>
                        </form>
                    `;
                } else {
                    botoesHtml = `
                        <form action="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=pendentes" method="POST" class="d-inline">
                            <input type="hidden" name="avaliacao_id" value="${avaliacao.id}">
                            <input type="hidden" name="aprovar_avaliacao" value="1">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i> Aprovar
                            </button>
                        </form>
                    `;
                }
                
                botoesHtml += `
                    <form action="<?php echo SITE_URL; ?>/?route=gerenciar_avaliacoes_admin&acao=<?php echo $acao; ?>" method="POST" class="d-inline ms-2">
                        <input type="hidden" name="avaliacao_id" value="${avaliacao.id}">
                        <input type="hidden" name="excluir_avaliacao" value="1">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
                            <i class="fas fa-trash me-2"></i> Excluir
                        </button>
                    </form>
                `;
                
                document.getElementById('botoesAcao').innerHTML = botoesHtml;
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
