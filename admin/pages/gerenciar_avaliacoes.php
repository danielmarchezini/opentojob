<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obter instância do banco de dados
$db = Database::getInstance();

// Função para registrar logs
function logDebug($message) {
    $log_file = __DIR__ . '/../../logs/avaliacoes_debug.log';
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date] $message\n";
    
    // Criar diretório de logs se não existir
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Processar ações de aprovação ou rejeição via GET
if (isset($_GET['aprovar']) && is_numeric($_GET['aprovar'])) {
    $avaliacao_id = (int)$_GET['aprovar'];
    logDebug("Tentando aprovar avaliação ID: $avaliacao_id via GET");
    
    try {
        // Verificar se a coluna status existe
        $colunas = $db->query("DESCRIBE avaliacoes")->fetchAll(PDO::FETCH_COLUMN);
        logDebug("Colunas encontradas: " . implode(", ", $colunas));
        
        if (in_array('status', $colunas)) {
            $resultado = $db->update('avaliacoes', [
                'status' => 'aprovada'
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado status para 'aprovada'. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        } else {
            $resultado = $db->update('avaliacoes', [
                'aprovada' => 1,
                'rejeitada' => 0
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado aprovada=1, rejeitada=0. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        }
        
        $_SESSION['flash_message'] = "Avaliação aprovada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        logDebug("ERRO ao aprovar avaliação: " . $e->getMessage());
        $_SESSION['flash_message'] = "Erro ao aprovar avaliação: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    // Redirecionar para evitar reenvio
    header("Location: " . SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=pendentes");
    exit;
} else if (isset($_GET['rejeitar']) && is_numeric($_GET['rejeitar'])) {
    $avaliacao_id = (int)$_GET['rejeitar'];
    logDebug("Tentando rejeitar avaliação ID: $avaliacao_id via GET");
    
    try {
        // Verificar se a coluna status existe
        $colunas = $db->query("DESCRIBE avaliacoes")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('status', $colunas)) {
            $resultado = $db->update('avaliacoes', [
                'status' => 'rejeitada'
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado status para 'rejeitada'. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        } else {
            $resultado = $db->update('avaliacoes', [
                'aprovada' => 0,
                'rejeitada' => 1
            ], 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Atualizado aprovada=0, rejeitada=1. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
        }
        
        $_SESSION['flash_message'] = "Avaliação rejeitada com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        logDebug("ERRO ao rejeitar avaliação: " . $e->getMessage());
        $_SESSION['flash_message'] = "Erro ao rejeitar avaliação: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    // Redirecionar para evitar reenvio
    header("Location: " . SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=pendentes");
    exit;
}

// Verificar se foi solicitada a visualização de uma avaliação específica
if (isset($_GET['view_id']) && is_numeric($_GET['view_id'])) {
    $avaliacao_id = (int)$_GET['view_id'];
    
    // Função para buscar detalhes da avaliação
    function buscarDetalhesAvaliacao($id) {
        global $db;
        
        try {
            $avaliacao = $db->fetch("
                SELECT a.*, 
                       u.nome as talento_nome, 
                       t.profissao,
                       COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
                       a.pontuacao as nota,
                       a.data_avaliacao as data_criacao,
                       CASE 
                           WHEN a.status = 'aprovada' OR a.aprovada = 1 THEN 1
                           ELSE 0
                       END as aprovada
                FROM avaliacoes a
                JOIN usuarios u ON a.talento_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id
                LEFT JOIN usuarios e ON a.empresa_id = e.id
                WHERE a.id = :id
            ", [
                'id' => $id
            ]);
            
            return $avaliacao;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Buscar avaliação
    $avaliacao = buscarDetalhesAvaliacao($avaliacao_id);
    
    // Se a avaliação foi encontrada, retornar o HTML com os detalhes
    if ($avaliacao) {
        // Verificar e ajustar campos nulos ou ausentes
        if (!isset($avaliacao['nome_avaliador']) || empty($avaliacao['nome_avaliador'])) {
            $avaliacao['nome_avaliador'] = 'Anônimo';
        }
        
        if (!isset($avaliacao['linkedin_avaliador'])) {
            $avaliacao['linkedin_avaliador'] = '';
        }
        
        if (!isset($avaliacao['avaliacao']) && isset($avaliacao['texto'])) {
            $avaliacao['avaliacao'] = $avaliacao['texto'];
        } else if (!isset($avaliacao['avaliacao']) && isset($avaliacao['comentario'])) {
            $avaliacao['avaliacao'] = $avaliacao['comentario'];
        } else if (!isset($avaliacao['avaliacao'])) {
            $avaliacao['avaliacao'] = 'Sem comentários';
        }
        
        // Formatar data
        $data_formatada = !empty($avaliacao['data_criacao']) 
            ? date('d/m/Y H:i', strtotime($avaliacao['data_criacao'])) 
            : date('d/m/Y H:i', strtotime($avaliacao['data_avaliacao'] ?? 'now'));
        
        // Construir HTML com os detalhes da avaliação
        echo '<div id="avaliacaoDetalhes">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Informações do Talento</h5>
                    <p><strong>Nome:</strong> ' . htmlspecialchars((string)$avaliacao['talento_nome']) . '</p>
                    <p><strong>Profissão:</strong> ' . htmlspecialchars((string)$avaliacao['profissao'] ?? 'Não informado') . '</p>
                </div>
                <div class="col-md-6">
                    <h5>Informações do Avaliador</h5>
                    <p><strong>Nome:</strong> ' . htmlspecialchars((string)$avaliacao['nome_avaliador']) . '</p>
                    <p><strong>LinkedIn:</strong> ' . (!empty($avaliacao['linkedin_avaliador']) ? '<a href="' . htmlspecialchars((string)$avaliacao['linkedin_avaliador']) . '" target="_blank">' . htmlspecialchars((string)$avaliacao['linkedin_avaliador']) . '</a>' : 'Não informado') . '</p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Detalhes da Avaliação</h5>
                    <p><strong>Data:</strong> ' . $data_formatada . '</p>
                    <p><strong>Nota:</strong> ' . htmlspecialchars((string)$avaliacao['nota']) . '</p>
                    <p><strong>Status:</strong> ' . ($avaliacao['aprovada'] ? '<span class="badge bg-success">Aprovada</span>' : '<span class="badge bg-warning">Pendente</span>') . '</p>
                </div>
                <div class="col-md-6">
                    <h5>Conteúdo da Avaliação</h5>
                    <div class="p-3 bg-light rounded">
                        ' . nl2br(htmlspecialchars((string)$avaliacao['avaliacao'])) . '
                    </div>
                </div>
            </div>
        </div>';
        exit;
    } else {
        // Se a avaliação não foi encontrada, retornar mensagem de erro
        echo '<div id="avaliacaoDetalhes">
            <div class="alert alert-danger">
                Avaliação não encontrada ou excluída.
            </div>
        </div>';
        exit;
    }
}

// Definir ação (pendentes, aprovadas, rejeitadas)
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'pendentes';

// Verificar se a tabela avaliacoes existe
$tabela_existe = $db->fetch("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'avaliacoes'");
$tabela_existe = ($tabela_existe && $tabela_existe['count'] > 0);

// Manter o código de processamento POST para compatibilidade com outras funcionalidades
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logDebug('Processando formulário POST: ' . json_encode($_POST));
    
    // Obter a ação do POST ou do GET
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        logDebug("Ação obtida do POST: $acao");
    }
    
    if (isset($_POST['excluir_avaliacao']) && isset($_POST['avaliacao_id'])) {
        $avaliacao_id = (int)$_POST['avaliacao_id'];
        logDebug("Tentando excluir avaliação ID: $avaliacao_id");
        
        try {
            $resultado = $db->delete('avaliacoes', 'id = :id', [
                'id' => $avaliacao_id
            ]);
            logDebug("Exclusão de avaliação. Resultado: " . ($resultado ? 'sucesso' : 'falha'));
            
            $_SESSION['flash_message'] = "Avaliação excluída com sucesso!";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            logDebug("ERRO ao excluir avaliação: " . $e->getMessage());
            $_SESSION['flash_message'] = "Erro ao excluir avaliação: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    $redirect_url = SITE_URL . "/admin/?page=gerenciar_avaliacoes&acao=" . $acao;
    logDebug("Redirecionando para: $redirect_url");
    
    header("Location: $redirect_url");
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
                SELECT a.*, u.nome as talento_nome, t.profissao, 
                       COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
                       a.pontuacao as nota, 
                       a.data_avaliacao as data_criacao
                FROM avaliacoes a
                JOIN usuarios u ON a.talento_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id
                LEFT JOIN usuarios e ON a.empresa_id = e.id
                WHERE (a.status = 'pendente' OR a.aprovada = 0)
                ORDER BY a.data_avaliacao DESC
            ");
            
            $titulo_pagina = "Avaliações Pendentes";
        } elseif ($acao === 'aprovadas') {
            $avaliacoes = $db->fetchAll("
                SELECT a.*, u.nome as talento_nome, t.profissao, 
                       COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
                       a.pontuacao as nota, 
                       a.data_avaliacao as data_criacao
                FROM avaliacoes a
                JOIN usuarios u ON a.talento_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id
                LEFT JOIN usuarios e ON a.empresa_id = e.id
                WHERE a.status = 'aprovada' OR a.aprovada = 1
                ORDER BY a.data_avaliacao DESC
            ");
            
            $titulo_pagina = "Avaliações Aprovadas";
        } elseif ($acao === 'rejeitadas') {
            $avaliacoes = $db->fetchAll("
                SELECT a.*, u.nome as talento_nome, t.profissao, 
                       COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
                       a.pontuacao as nota, 
                       a.data_avaliacao as data_criacao
                FROM avaliacoes a
                JOIN usuarios u ON a.talento_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id
                LEFT JOIN usuarios e ON a.empresa_id = e.id
                WHERE (a.status = 'rejeitada' OR (a.aprovada = 0 AND a.rejeitada = 1))
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
            SELECT COUNT(*) FROM avaliacoes WHERE status = 'pendente' OR (aprovada = 0 AND (rejeitada = 0 OR rejeitada IS NULL))
        ");
        $total_aprovadas = $db->fetchColumn("
            SELECT COUNT(*) FROM avaliacoes WHERE status = 'aprovada' OR aprovada = 1
        ");
        $total_rejeitadas = $db->fetchColumn("
            SELECT COUNT(*) FROM avaliacoes WHERE status = 'rejeitada' OR (aprovada = 0 AND rejeitada = 1)
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
                                                <?php echo htmlspecialchars((string)$avaliacao['talento_nome']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/?route=perfil_empresa&id=<?php echo $avaliacao['empresa_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars((string)$avaliacao['empresa_nome']); ?>
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
                                        <td><?php echo !empty($avaliacao['data_criacao']) ? date('d/m/Y H:i', strtotime($avaliacao['data_criacao'])) : date('d/m/Y H:i', strtotime($avaliacao['data_avaliacao'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" onclick="visualizarAvaliacao(<?php echo $avaliacao['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($acao === 'pendentes'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" onclick="aprovarAvaliacao(<?php echo $avaliacao['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="rejeitarAvaliacao(<?php echo $avaliacao['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
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

<!-- Incluir jQuery se ainda não estiver incluído -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Criar o modal de status no carregamento da página
document.addEventListener('DOMContentLoaded', function() {
    // Criar modal de status antecipadamente
    const modalHTML = `
        <div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalStatusTitulo"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body" id="modalStatusMensagem"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="location.reload();">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const div = document.createElement('div');
    div.innerHTML = modalHTML;
    document.body.appendChild(div.firstChild);
    
    // Inicializar DataTables com tradução em português diretamente
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#avaliacoesTable').DataTable({
            language: {
                "emptyTable": "Nenhum registro encontrado",
                "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 até 0 de 0 registros",
                "infoFiltered": "(Filtrados de _MAX_ registros)",
                "infoThousands": ".",
                "lengthMenu": "_MENU_ resultados por página",
                "loadingRecords": "Carregando...",
                "processing": "Processando...",
                "zeroRecords": "Nenhum registro encontrado",
                "search": "Pesquisar",
                "paginate": {
                    "next": "Próximo",
                    "previous": "Anterior",
                    "first": "Primeiro",
                    "last": "Último"
                },
                "aria": {
                    "sortAscending": ": Ordenar colunas de forma ascendente",
                    "sortDescending": ": Ordenar colunas de forma descendente"
                }
            },
            order: [[0, 'desc']]
        });
    } else {
        console.error('jQuery ou DataTables não estão disponíveis');
    }
});

<?php
// Função para buscar detalhes da avaliação
function buscarDetalhesAvaliacao($id) {
    global $db;
    
    try {
        $avaliacao = $db->fetch("
            SELECT a.*, 
                   u.nome as talento_nome, 
                   t.profissao,
                   COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
                   a.pontuacao as nota,
                   a.data_avaliacao as data_criacao,
                   CASE 
                       WHEN a.status = 'aprovada' OR a.aprovada = 1 THEN 1
                       ELSE 0
                   END as aprovada
            FROM avaliacoes a
            JOIN usuarios u ON a.talento_id = u.id
            LEFT JOIN talentos t ON u.id = t.usuario_id
            LEFT JOIN usuarios e ON a.empresa_id = e.id
            WHERE a.id = :id
        ", [
            'id' => $id
        ]);
        
        return $avaliacao;
    } catch (Exception $e) {
        return null;
    }
}
?>

<?php
// Pré-carregar todas as avaliações para uso no JavaScript
$todas_avaliacoes = [];
if ($tabela_existe) {
    try {
        $todas_avaliacoes = $db->fetchAll("
            SELECT a.*, 
                   u.nome as talento_nome, 
                   t.profissao,
                   COALESCE(e.nome, a.nome_avaliador) as empresa_nome,
                   a.pontuacao as nota,
                   a.data_avaliacao as data_criacao,
                   CASE 
                       WHEN a.status = 'aprovada' OR a.aprovada = 1 THEN 1
                       ELSE 0
                   END as aprovada
            FROM avaliacoes a
            JOIN usuarios u ON a.talento_id = u.id
            LEFT JOIN talentos t ON u.id = t.usuario_id
            LEFT JOIN usuarios e ON a.empresa_id = e.id
        ");
    } catch (Exception $e) {
        // Silenciar erros
    }
}
?>

// Função simplificada para mostrar mensagens (não usada mais)
function mostrarModalStatus(titulo, mensagem, tipo) {
    alert(mensagem);
    location.reload();
}

// Função para aprovar avaliação
function aprovarAvaliacao(id) {
    if (!confirm('Tem certeza que deseja aprovar esta avaliação?')) {
        return;
    }
    
    // Mostrar indicador de carregamento
    const btnAprovar = event.target.closest('button');
    const iconOriginal = btnAprovar.innerHTML;
    btnAprovar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btnAprovar.disabled = true;
    
    // Redirecionar para o arquivo de processamento
    window.location.href = '<?php echo SITE_URL; ?>/admin/processar_avaliacao.php?aprovar=' + id;
}

// Função para rejeitar avaliação
function rejeitarAvaliacao(id) {
    if (!confirm('Tem certeza que deseja rejeitar esta avaliação?')) {
        return;
    }
    
    // Mostrar indicador de carregamento
    const btnRejeitar = event.target.closest('button');
    const iconOriginal = btnRejeitar.innerHTML;
    btnRejeitar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btnRejeitar.disabled = true;
    
    // Redirecionar para o arquivo de processamento
    window.location.href = '<?php echo SITE_URL; ?>/admin/processar_avaliacao.php?rejeitar=' + id;
}

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
    if (typeof $ !== 'undefined') {
        $('#modalVisualizarAvaliacao').modal('show');
    } else {
        // Fallback para Bootstrap 5 nativo se jQuery não estiver disponível
        var modal = document.getElementById('modalVisualizarAvaliacao');
        if (modal && typeof bootstrap !== 'undefined') {
            var modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        }
    }
    
    // Dados das avaliações pré-carregados do PHP
    const avaliacoes = <?php echo json_encode($todas_avaliacoes); ?>;
    
    // Encontrar a avaliação pelo ID
    const avaliacao = avaliacoes.find(a => a.id == id);
    
    if (avaliacao) {
        // Formatar data
        const dataObj = new Date(avaliacao.data_criacao || avaliacao.data_avaliacao);
        const dataFormatada = dataObj.toLocaleDateString('pt-BR') + ' ' + dataObj.toLocaleTimeString('pt-BR');
        
        // Construir HTML com os detalhes da avaliação
        const html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Informações do Talento</h5>
                    <p><strong>Nome:</strong> ${avaliacao.talento_nome || 'Não informado'}</p>
                    <p><strong>Profissão:</strong> ${avaliacao.profissao || 'Não informado'}</p>
                </div>
                <div class="col-md-6">
                    <h5>Informações do Avaliador</h5>
                    <p><strong>Nome:</strong> ${avaliacao.nome_avaliador || avaliacao.empresa_nome || 'Anônimo'}</p>
                    <p><strong>LinkedIn:</strong> ${avaliacao.linkedin_avaliador ? `<a href="${avaliacao.linkedin_avaliador}" target="_blank">${avaliacao.linkedin_avaliador}</a>` : 'Não informado'}</p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Detalhes da Avaliação</h5>
                    <p><strong>Data:</strong> ${dataFormatada}</p>
                    <p><strong>Nota:</strong> ${avaliacao.nota || avaliacao.pontuacao || '0'}</p>
                    <p><strong>Status:</strong> ${avaliacao.aprovada == 1 ? '<span class="badge bg-success">Aprovada</span>' : '<span class="badge bg-warning">Pendente</span>'}</p>
                </div>
                <div class="col-md-6">
                    <h5>Conteúdo da Avaliação</h5>
                    <div class="p-3 bg-light rounded">
                        ${(avaliacao.avaliacao || avaliacao.texto || avaliacao.comentario || 'Sem comentários').replace(/\n/g, '<br>')}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('avaliacaoDetalhes').innerHTML = html;
    } else {
        document.getElementById('avaliacaoDetalhes').innerHTML = `
            <div class="alert alert-danger">
                Avaliação não encontrada ou excluída.
            </div>
        `;
    }
}
</script>
