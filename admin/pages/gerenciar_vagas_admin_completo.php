<?php
// Obter lista de vagas com todos os campos relevantes
$db = Database::getInstance();
try {
    $vagas = $db->fetchAll("
        SELECT v.*, 
               CASE 
                   WHEN v.tipo_vaga = 'externa' AND v.empresa_externa IS NOT NULL THEN v.empresa_externa
                   ELSE u.nome 
               END as empresa_nome, 
               e.razao_social
        FROM vagas v
        LEFT JOIN usuarios u ON v.empresa_id = u.id
        LEFT JOIN empresas e ON u.id = e.usuario_id
        ORDER BY v.data_publicacao DESC
    ");
    
    // Log para depuração
    error_log('Vagas carregadas: ' . count($vagas));
} catch (PDOException $e) {
    // Se ocorrer um erro, exibir mensagem e criar array vazio
    $_SESSION['flash_message'] = "Erro ao carregar vagas: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $vagas = [];
    
    // Log para depuração
    error_log('Erro ao carregar vagas: ' . $e->getMessage());
}

// Obter lista de empresas para o formulário
try {
    $empresas = $db->fetchAll("
        SELECT u.id, u.nome, e.razao_social
        FROM usuarios u
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE u.tipo = 'empresa' AND u.status = 'ativo'
        ORDER BY u.nome ASC
    ");
} catch (PDOException $e) {
    // Se ocorrer um erro, exibir mensagem e criar array vazio
    if (!isset($_SESSION['flash_message'])) {
        $_SESSION['flash_message'] = "Erro ao carregar empresas: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    $empresas = [];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Vagas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Vagas</li>
    </ol>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
        <?php echo $_SESSION['flash_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
    <?php 
        // Limpar mensagem flash
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    endif; ?>

    <!-- Botão para adicionar nova vaga -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            Adicionar Nova Vaga
        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
        <div class="card-body">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarVaga">
                <i class="fas fa-plus"></i> Adicionar Nova Vaga
            </button>
        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
        
    <!-- Lista de Vagas -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-list me-1"></i>
            Lista de Vagas
        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
        <div class="card-body">
            <table id="vagasTable" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Empresa</th>
                        <th>Localização</th>
                        <th>Tipo de Contrato</th>
                        <th>Regime</th>
                        <th>Nível de Experiência</th>
                        <th>Status</th>
                        <th>Data de Publicação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vagas)): ?>
                        <tr>
                            <td colspan="11" class="text-center">Nenhuma vaga encontrada</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vagas as $vaga): ?>
                            <tr>
                                <td><?php echo $vaga['id']; ?></td>
                                <td><?php echo htmlspecialchars($vaga['titulo']); ?></td>
                                <td>
                                    <?php echo ($vaga['tipo_vaga'] == 'interna') ? 'Interna' : 'Externa'; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($vaga['tipo_vaga'] == 'interna') {
                                        echo htmlspecialchars($vaga['empresa_nome'] ?? 'Não informada');
                                    } else {
                                        echo htmlspecialchars($vaga['empresa_externa'] ?? 'Não informada');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $localizacao = [];
                                    if (!empty($vaga['cidade'])) $localizacao[] = $vaga['cidade'];
                                    if (!empty($vaga['estado'])) $localizacao[] = $vaga['estado'];
                                    echo !empty($localizacao) ? htmlspecialchars(implode(' - ', $localizacao)) : 'Não informada';
                                    ?>
                                </td>
                                <td><?php echo !empty($vaga['tipo_contrato']) ? htmlspecialchars($vaga['tipo_contrato']) : 'Não informado'; ?></td>
                                <td><?php echo !empty($vaga['regime_trabalho']) ? htmlspecialchars($vaga['regime_trabalho']) : 'Não informado'; ?></td>
                                <td><?php echo !empty($vaga['nivel_experiencia']) ? htmlspecialchars($vaga['nivel_experiencia']) : 'Não informado'; ?></td>
                                <td>
                                    <?php
                                    $status = isset($vaga['status']) ? $vaga['status'] : 'pendente';
                                    $status_class = '';
                                    
                                    switch ($status) {
                                        case 'aberta':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'fechada':
                                            $status_class = 'bg-danger';
                                            break;
                                        case 'pendente':
                                            $status_class = 'bg-warning';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                    }
                                    ?>
                                     <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($vaga['data_publicacao'])) {
                                        echo date('d/m/Y', strtotime($vaga['data_publicacao']));
                                    } else {
                                        echo 'Não informado';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-info btn-sm" onclick="visualizarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="editarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmarExclusao(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
</div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>

<!-- Modal Adicionar Vaga -->
<div class="modal fade" id="modalAdicionarVaga" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarVagaLabel">Adicionar Nova Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
            <form action="<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="form-group mb-3">
                        <label for="titulo">Título da Vaga</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="form-group mb-3">
                        <label for="tipo_vaga">Tipo de Vaga</label>
                        <select class="form-control" id="tipo_vaga" name="tipo_vaga" required onchange="toggleEmpresaFields()">
                            <option value="">Selecione o tipo de vaga</option>
                            <option value="interna">Interna (empresa cadastrada)</option>
                            <option value="externa">Externa (empresa não cadastrada)</option>
                        </select>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div id="empresa_interna_div" class="form-group mb-3">
                        <label for="empresa_id">Empresa Cadastrada</label>
                        <select class="form-control" id="empresa_id" name="empresa_id">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo $empresa['id']; ?>"><?php echo $empresa['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div id="empresa_externa_div" class="form-group mb-3" style="display: none;">
                        <label for="empresa_externa">Nome da Empresa Externa</label>
                        <input type="text" class="form-control" id="empresa_externa" name="empresa_externa" placeholder="Nome da empresa não cadastrada">
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade">
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado" maxlength="2">
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo_contrato">Tipo de Contrato</label>
                                <select class="form-control" id="tipo_contrato" name="tipo_contrato">
                                    <option value="">Selecione</option>
                                    <option value="CLT">CLT</option>
                                    <option value="PJ">PJ</option>
                                    <option value="Estágio">Estágio</option>
                                    <option value="Freelancer">Freelancer</option>
                                    <option value="Temporário">Temporário</option>
                                </select>
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="regime_trabalho">Regime de Trabalho</label>
                                <select class="form-control" id="regime_trabalho" name="regime_trabalho">
                                    <option value="">Selecione</option>
                                    <option value="Presencial">Presencial</option>
                                    <option value="Remoto">Remoto</option>
                                    <option value="Híbrido">Híbrido</option>
                                </select>
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nivel_experiencia">Nível de Experiência</label>
                                <select class="form-control" id="nivel_experiencia" name="nivel_experiencia">
                                    <option value="">Selecione</option>
                                    <option value="Estágio">Estágio</option>
                                    <option value="Júnior">Júnior</option>
                                    <option value="Pleno">Pleno</option>
                                    <option value="Sênior">Sênior</option>
                                    <option value="Especialista">Especialista</option>
                                </select>
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="salario_min">Salário Mínimo</label>
                                <input type="number" class="form-control" id="salario_min" name="salario_min" step="0.01">
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="salario_max">Salário Máximo</label>
                                <input type="number" class="form-control" id="salario_max" name="salario_max" step="0.01">
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Mostrar Salário</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="mostrar_salario" name="mostrar_salario" value="1">
                                    <label class="form-check-label" for="mostrar_salario">Sim</label>
                                </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                            </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="form-group mb-3">
                        <label for="descricao">Descrição da Vaga</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="form-group mb-3">
                        <label for="requisitos">Requisitos</label>
                        <textarea class="form-control" id="requisitos" name="requisitos" rows="3" required></textarea>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="form-group mb-3">
                        <label for="beneficios">Benefícios</label>
                        <textarea class="form-control" id="beneficios" name="beneficios" rows="3"></textarea>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                    
                    <div class="form-group mb-3">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="aberta">Aberta</option>
                            <option value="fechada">Fechada</option>
                            <option value="pendente">Pendente</option>
                        </select>
                    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
            </form>
        </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
    </div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
</div>

<?php include __DIR__ . '/gerenciar_vagas_admin_modals.php'; ?>

<script>
    // Definir variável global SITE_URL para uso nos scripts
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/pages/gerenciar_vagas_admin_scripts.js"></script>
