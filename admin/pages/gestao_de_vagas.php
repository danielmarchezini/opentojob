<?php
// Verificar se o usuário está logado como admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . SITE_URL . "/admin/login.php");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Carregar empresas para o select
try {
    $empresas = $db->fetchAll("
        SELECT u.id, u.nome, e.razao_social 
        FROM usuarios u 
        JOIN empresas e ON u.id = e.usuario_id 
        WHERE u.tipo = 'empresa' AND u.status = 'ativo'
        ORDER BY u.nome
    ");
} catch (PDOException $e) {
    error_log('Erro ao carregar empresas: ' . $e->getMessage());
    $empresas = [];
}

// Obter tipos de contrato, regimes de trabalho e níveis de experiência
// Removido filtro WHERE ativo = 1 para mostrar todos os registros
try {
    $tipos_contrato = $db->fetchAll("SELECT id, nome FROM tipos_contrato ORDER BY nome");
    $regimes_trabalho = $db->fetchAll("SELECT id, nome FROM regimes_trabalho ORDER BY nome");
    $niveis_experiencia = $db->fetchAll("SELECT id, nome FROM niveis_experiencia ORDER BY nome");
} catch (PDOException $e) {
    error_log('Erro ao carregar opções de selects: ' . $e->getMessage());
    $tipos_contrato = [];
    $regimes_trabalho = [];
    $niveis_experiencia = [];
}

// Verificar se há mensagem flash
$flash_message = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Obter vagas com JOIN para as tabelas de referência
try {
    $vagas = $db->fetchAll("
        SELECT v.*, 
               tc.nome as tipo_contrato_nome,
               rt.nome as regime_trabalho_nome,
               ne.nome as nivel_experiencia_nome,
               CASE 
                   WHEN v.tipo_vaga = 'externa' AND v.empresa_externa IS NOT NULL THEN v.empresa_externa
                   ELSE u.nome 
               END as empresa_nome
        FROM vagas v
        LEFT JOIN tipos_contrato tc ON v.tipo_contrato_id = tc.id
        LEFT JOIN regimes_trabalho rt ON v.regime_trabalho_id = rt.id
        LEFT JOIN niveis_experiencia ne ON v.nivel_experiencia_id = ne.id
        LEFT JOIN usuarios u ON v.empresa_id = u.id
        ORDER BY v.data_publicacao DESC
    ");
} catch (PDOException $e) {
    error_log('Erro ao carregar vagas: ' . $e->getMessage());
    $vagas = [];
}

// Função para formatar data
function formatarDataVaga($data) {
    if (!$data) return 'N/A';
    return date('d/m/Y', strtotime($data));
}

// Função para formatar salário
function formatarSalarioVaga($min, $max) {
    if (!$min && !$max) return 'Não informado';
    
    if ($min && $max) {
        return 'R$ ' . number_format($min, 2, ',', '.') . ' - R$ ' . number_format($max, 2, ',', '.');
    } else if ($min) {
        return 'A partir de R$ ' . number_format($min, 2, ',', '.');
    } else if ($max) {
        return 'Até R$ ' . number_format($max, 2, ',', '.');
    }
    
    return 'Não informado';
}

// Função para obter a classe de status
function getStatusClassVaga($status) {
    switch ($status) {
        case 'aberta':
            return 'success';
        case 'fechada':
            return 'danger';
        case 'pendente':
            return 'warning';
        default:
            return 'secondary';
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestão de Vagas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gestão de Vagas</li>
    </ol>
    
    <?php if (!empty($flash_message)): ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    
    <!-- Lista de Vagas -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-briefcase me-1"></i>
            Lista de Vagas
            <div class="float-end">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdicionarVaga">
                    <i class="fas fa-plus fa-sm"></i> Nova Vaga
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="vagasTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Empresa</th>
                            <th>Tipo de Contrato</th>
                            <th>Regime</th>
                            <th>Nível</th>
                            <th>Publicação</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vagas as $vaga): ?>
                            <tr data-vaga-id="<?php echo $vaga['id']; ?>">
                                <td><?php echo $vaga['id']; ?></td>
                                <td><?php echo htmlspecialchars((string)$vaga['titulo']); ?></td>
                                <td>
                                    <?php if ($vaga['tipo_vaga'] === 'externa'): ?>
                                        <span class="badge badge-info">Externa</span> <?php echo htmlspecialchars((string)$vaga['empresa_nome']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars((string)$vaga['empresa_nome']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)$vaga['tipo_contrato_nome'] ?? $vaga['tipo_contrato'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars((string)$vaga['regime_trabalho_nome'] ?? $vaga['regime_trabalho'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars((string)$vaga['nivel_experiencia_nome'] ?? $vaga['nivel_experiencia'] ?? 'N/A'); ?></td>
                                <td><?php echo formatarDataVaga($vaga['data_publicacao']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo getStatusClassVaga($vaga['status']); ?>">
                                        <?php echo ucfirst($vaga['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" onclick="visualizarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="editarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Incluir modais -->
<?php include __DIR__ . '/gestao_de_vagas_modals.php'; ?>

<!-- Scripts -->
<script>var SITE_URL = '<?php echo SITE_URL; ?>';</script>
<script src="<?php echo SITE_URL; ?>/admin/js/gestao_vagas_acoes.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo SITE_URL; ?>/admin/js/toggle_gestao_vagas.js?v=<?php echo time(); ?>"></script>

<!-- Script para excluir vaga via AJAX -->
<script>
// Função para excluir vaga via AJAX
function excluirVaga() {
    const vagaId = document.getElementById('vaga_id_confirmacao').value;
    const formData = new FormData();
    formData.append('acao', 'excluir');
    formData.append('vaga_id', vagaId);
    
    // Mostrar indicador de carregamento
    document.getElementById('btnConfirmarExclusao').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Excluindo...';
    document.getElementById('btnConfirmarExclusao').disabled = true;
    
    // Enviar requisição AJAX
    fetch(SITE_URL + '/admin/processar_gestao_vagas.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Fechar o modal
        const modalElement = document.getElementById('modalConfirmacao');
        if (typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();
        } else if (typeof $ !== 'undefined') {
            $(modalElement).modal('hide');
        }
        
        // Exibir mensagem de sucesso
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + (data.success ? 'success' : 'danger') + ' alert-dismissible fade show';
        alertDiv.innerHTML = `
            ${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        `;
        
        // Inserir alerta antes da tabela
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(alertDiv, cardBody.firstChild);
        
        // Se a exclusão foi bem-sucedida, remover a linha da tabela
        if (data.success) {
            const row = document.querySelector(`tr[data-vaga-id="${vagaId}"]`);
            if (row) {
                row.remove();
            } else {
                // Se não encontrou a linha pelo atributo data, recarregar a tabela
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }
        
        // Rolar para o topo para mostrar a mensagem
        window.scrollTo(0, 0);
    })
    .catch(error => {
        console.error('Erro ao excluir vaga:', error);
        alert('Erro ao excluir vaga. Por favor, tente novamente.');
    })
    .finally(() => {
        // Restaurar botão
        document.getElementById('btnConfirmarExclusao').innerHTML = 'Excluir';
        document.getElementById('btnConfirmarExclusao').disabled = false;
    });
}
</script>
