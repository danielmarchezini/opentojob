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

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestão de Vagas</h1>
    
    <?php if (!empty($flash_message)): ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Vagas</h6>
            <div class="dropdown no-arrow">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarVaga">
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
                            <tr>
                                <td><?php echo $vaga['id']; ?></td>
                                <td><?php echo htmlspecialchars($vaga['titulo']); ?></td>
                                <td>
                                    <?php if ($vaga['tipo_vaga'] === 'externa'): ?>
                                        <span class="badge badge-info">Externa</span> <?php echo htmlspecialchars($vaga['empresa_nome']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($vaga['empresa_nome']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($vaga['tipo_contrato_nome'] ?? $vaga['tipo_contrato'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vaga['regime_trabalho_nome'] ?? $vaga['regime_trabalho'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vaga['nivel_experiencia_nome'] ?? $vaga['nivel_experiencia'] ?? 'N/A'); ?></td>
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
<script src="<?php echo SITE_URL; ?>/admin/js/gestao_vagas_acoes.js"></script>
<script src="<?php echo SITE_URL; ?>/admin/js/toggle_gestao_vagas.js"></script>
