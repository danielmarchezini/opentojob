<?php
// Obter lista de vagas
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
} catch (PDOException $e) {
    // Se ocorrer um erro, exibir mensagem e criar array vazio
    $_SESSION['flash_message'] = "Erro ao carregar vagas: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $vagas = [];
}

// Obter lista de empresas para o formulário de adicionar vaga
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
        <div class="card-body">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarVaga">
                <i class="fas fa-plus"></i> Adicionar Nova Vaga
            </button>
        </div>
    </div>
        
    <!-- Lista de Vagas -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-list me-1"></i>
            Lista de Vagas
        </div>
        <div class="card-body">
            <table id="vagasTable" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Empresa</th>
                            <th>Localização</th>
                            <th>Tipo de Contrato</th>
                            <th>Regime</th>
                            <th>Status</th>
                            <th>Data de Publicação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vagas)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Nenhuma vaga encontrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vagas as $vaga): ?>
                                <tr>
                                    <td><?php echo $vaga['id']; ?></td>
                                    <td><?php echo htmlspecialchars($vaga['titulo']); ?></td>
                                    <td>
                                        <?php 
                                        if ($vaga['tipo_vaga'] == 'externa') {
                                             echo '<span class="badge bg-info">Externa</span> ';
                                            echo htmlspecialchars($vaga['empresa_externa'] ?? 'Não informada');
                                        } else {
                                            echo htmlspecialchars($vaga['empresa_nome'] ?? 'Não informada');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $localizacao = '';
                                        if (!empty($vaga['cidade'])) {
                                            $localizacao .= htmlspecialchars($vaga['cidade']);
                                            if (!empty($vaga['estado'])) {
                                                $localizacao .= '/' . htmlspecialchars($vaga['estado']);
                                            }
                                        } else {
                                            $localizacao = 'Não informado';
                                        }
                                        echo $localizacao;
                                        ?>
                                    </td>
                                    <td><?php echo !empty($vaga['tipo_contrato']) ? ucfirst(htmlspecialchars($vaga['tipo_contrato'])) : 'Não informado'; ?></td>
                                    <td><?php echo !empty($vaga['regime_trabalho']) ? htmlspecialchars($vaga['regime_trabalho']) : 'Não informado'; ?></td>
                                    <td>
                                        <?php
                                        $status = isset($vaga['status']) ? $vaga['status'] : 'pendente';
                                        $status_class = '';
                                        switch ($status) {
                                            case 'aberta':
                                                $status_class = 'success';
                                                break;
                                            case 'fechada':
                                                $status_class = 'danger';
                                                break;
                                            case 'pendente':
                                            default:
                                                $status_class = 'warning';
                                                break;
                                        }
                                        ?>
                                         <span class="badge bg-<?php echo str_replace('badge-', '', $status_class); ?>"><?php echo ucfirst($status); ?></span>
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
                                            <button type="button" class="btn btn-sm btn-info" onclick="visualizarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes(htmlspecialchars($vaga['titulo'])); ?>')" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="editarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes(htmlspecialchars($vaga['titulo'])); ?>')" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $vaga['id']; ?>, '<?php echo addslashes(htmlspecialchars($vaga['titulo'])); ?>')" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
        </div>
    </div>
</div>

<!-- Modal Alterar Senha -->
<div class="modal fade" id="modalAlterarSenha" tabindex="-1" role="dialog" aria-labelledby="modalAlterarSenhaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAlterarSenhaLabel">Alterar Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formAlterarSenha">
                    <input type="hidden" id="usuario_id_senha" name="usuario_id">
                    <!-- Campo de usuário oculto para acessibilidade -->
                    <input type="hidden" id="username_senha" name="username" autocomplete="username">
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6" autocomplete="new-password">
                        <small class="form-text text-muted">A senha deve ter pelo menos 6 caracteres.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" required minlength="6" autocomplete="new-password">
                        <div id="senha_feedback" class="invalid-feedback">As senhas não coincidem.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarSenha">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Vaga -->
<div class="modal fade" id="modalAdicionarVaga" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarVagaLabel">Adicionar Nova Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="form-group">
                        <label for="titulo">Título da Vaga</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_vaga">Tipo de Vaga</label>
                        <select class="form-control" id="tipo_vaga" name="tipo_vaga" required onchange="toggleEmpresaFields()">
                            <option value="">Selecione o tipo de vaga</option>
                            <option value="interna">Interna (empresa cadastrada)</option>
                            <option value="externa">Externa (empresa não cadastrada)</option>
                        </select>
                    </div>
                    
                    <div id="empresa_interna_div" class="form-group">
                        <label for="empresa_id">Empresa Cadastrada</label>
                        <select class="form-control" id="empresa_id" name="empresa_id">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo $empresa['id']; ?>"><?php echo $empresa['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="empresa_externa_div" class="form-group" style="display: none;">
                        <label for="empresa_externa">Nome da Empresa Externa</label>
                        <input type="text" class="form-control" id="empresa_externa" name="empresa_externa" placeholder="Nome da empresa não cadastrada">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado" maxlength="2">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
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
                        </div>
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
                        </div>
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
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="salario_min">Salário Mínimo</label>
                                <input type="number" class="form-control" id="salario_min" name="salario_min" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="salario_max">Salário Máximo</label>
                                <input type="number" class="form-control" id="salario_max" name="salario_max" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Mostrar Salário</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="mostrar_salario" name="mostrar_salario" value="1">
                                    <label class="custom-control-label" for="mostrar_salario">Sim</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição da Vaga</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requisitos">Requisitos</label>
                        <textarea class="form-control" id="requisitos" name="requisitos" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="beneficios">Benefícios</label>
                        <textarea class="form-control" id="beneficios" name="beneficios" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="aberta">Aberta</option>
                            <option value="fechada">Fechada</option>
                            <option value="pendente">Pendente</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Vaga -->
<div class="modal fade" id="modalEditarVaga" tabindex="-1" role="dialog" aria-labelledby="modalEditarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarVagaLabel">Editar Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarVaga" action="<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="vaga_id" id="editar_vaga_id">
                    
                    <div class="form-group">
                        <label for="editar_titulo">Título da Vaga</label>
                        <input type="text" class="form-control" id="editar_titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_tipo_vaga">Tipo de Vaga</label>
                        <select class="form-control" id="editar_tipo_vaga" name="tipo_vaga" required onchange="toggleEditarEmpresaFields()">
                            <option value="">Selecione o tipo de vaga</option>
                            <option value="interna">Interna (empresa cadastrada)</option>
                            <option value="externa">Externa (empresa não cadastrada)</option>
                        </select>
                    </div>
                    
                    <div id="editar_empresa_interna_div" class="form-group">
                        <label for="editar_empresa_id">Empresa Cadastrada</label>
                        <select class="form-control" id="editar_empresa_id" name="empresa_id">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo $empresa['id']; ?>"><?php echo $empresa['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="editar_empresa_externa_div" class="form-group" style="display: none;">
                        <label for="editar_empresa_externa">Nome da Empresa Externa</label>
                        <input type="text" class="form-control" id="editar_empresa_externa" name="empresa_externa" placeholder="Nome da empresa não cadastrada">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_cidade">Cidade</label>
                                <input type="text" class="form-control" id="editar_cidade" name="cidade">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_estado">Estado</label>
                                <input type="text" class="form-control" id="editar_estado" name="estado" maxlength="2">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_tipo_contrato">Tipo de Contrato</label>
                                <select class="form-control" id="editar_tipo_contrato" name="tipo_contrato">
                                    <option value="">Selecione</option>
                                    <option value="CLT">CLT</option>
                                    <option value="PJ">PJ</option>
                                    <option value="Estágio">Estágio</option>
                                    <option value="Freelancer">Freelancer</option>
                                    <option value="Temporário">Temporário</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_regime_trabalho">Regime de Trabalho</label>
                                <select class="form-control" id="editar_regime_trabalho" name="regime_trabalho">
                                    <option value="">Selecione</option>
                                    <option value="Presencial">Presencial</option>
                                    <option value="Remoto">Remoto</option>
                                    <option value="Híbrido">Híbrido</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_nivel_experiencia">Nível de Experiência</label>
                                <select class="form-control" id="editar_nivel_experiencia" name="nivel_experiencia">
                                    <option value="">Selecione</option>
                                    <option value="Estágio">Estágio</option>
                                    <option value="Júnior">Júnior</option>
                                    <option value="Pleno">Pleno</option>
                                    <option value="Sênior">Sênior</option>
                                    <option value="Especialista">Especialista</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_salario_min">Salário Mínimo</label>
                                <input type="number" class="form-control" id="editar_salario_min" name="salario_min" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_salario_max">Salário Máximo</label>
                                <input type="number" class="form-control" id="editar_salario_max" name="salario_max" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Mostrar Salário</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="editar_mostrar_salario" name="mostrar_salario" value="1">
                                    <label class="custom-control-label" for="editar_mostrar_salario">Sim</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_descricao">Descrição da Vaga</label>
                        <textarea class="form-control" id="editar_descricao" name="descricao" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_requisitos">Requisitos</label>
                        <textarea class="form-control" id="editar_requisitos" name="requisitos" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_beneficios">Benefícios</label>
                        <textarea class="form-control" id="editar_beneficios" name="beneficios" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_status">Status</label>
                        <select class="form-control" id="editar_status" name="status" required>
                            <option value="aberta">Aberta</option>
                            <option value="fechada">Fechada</option>
                            <option value="pendente">Pendente</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Vaga -->
<div class="modal fade" id="modalVisualizarVaga" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarVagaLabel">Detalhes da Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="vagaDetalhes" class="p-3">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p>Carregando detalhes da vaga...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarVagaDetalhe" onclick="editarVagaDoModal()">Editar Vaga</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmação -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p id="mensagem_confirmacao"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php" method="post">
                    <input type="hidden" name="acao" id="acao_confirmacao">
                    <input type="hidden" name="vaga_id" id="vaga_id_confirmacao">
                    <button type="submit" class="btn btn-danger">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEmpresaFields() {
    const tipoVaga = document.getElementById('tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('empresa_interna_div');
    const empresaExternaDiv = document.getElementById('empresa_externa_div');
    const empresaIdField = document.getElementById('empresa_id');
    const empresaExternaField = document.getElementById('empresa_externa');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = true;
        empresaExternaField.required = false;
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        empresaIdField.required = false;
        empresaExternaField.required = true;
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = false;
        empresaExternaField.required = false;
    }
}

function toggleEditarEmpresaFields() {
    const tipoVaga = document.getElementById('editar_tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('editar_empresa_interna_div');
    const empresaExternaDiv = document.getElementById('editar_empresa_externa_div');
    const empresaIdField = document.getElementById('editar_empresa_id');
    const empresaExternaField = document.getElementById('editar_empresa_externa');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = true;
        empresaExternaField.required = false;
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        empresaIdField.required = false;
        empresaExternaField.required = true;
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = false;
        empresaExternaField.required = false;
    }
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    new DataTable('#vagasTable', {
        responsive: true,
        language: {
            url: '/open2w/assets/js/pt-BR.json',
        }
    });
});

// Função para visualizar vaga
function visualizarVaga(id, titulo) {
    console.log('Visualizando vaga ID:', id, 'Título:', titulo);
    
    // Atualizar título do modal
    document.getElementById('modalVisualizarVagaLabel').textContent = 'Detalhes da Vaga: ' + titulo;
    
    // Limpar conteúdo anterior
    document.getElementById('vagaDetalhes').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    
    // Mostrar o modal
    const modalVisualizarVaga = new bootstrap.Modal(document.getElementById('modalVisualizarVaga'));
    modalVisualizarVaga.show();
    
    // Obter detalhes da vaga via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/?route=api_vaga_detalhe&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Dados recebidos:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }
            
            // Verificar se os dados contém a vaga
            if (!data.data || !data.data.vaga) {
                throw new Error('Dados da vaga não encontrados na resposta');
            }
            
            const vaga = data.data.vaga;
            console.log('Dados da vaga:', vaga);
            
            // Preencher detalhes da vaga
            document.getElementById('vagaDetalhes').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Empresa:</strong> ${vaga.empresa_nome || 'Não informado'}</p>
                        <p><strong>Localização:</strong> ${vaga.cidade ? vaga.cidade + (vaga.estado ? '/' + vaga.estado : '') : 'Não informado'}</p>
                        <p><strong>Tipo de Contrato:</strong> ${vaga.tipo_contrato || 'Não informado'}</p>
                        <p><strong>Regime de Trabalho:</strong> ${vaga.regime_trabalho || 'Não informado'}</p>
                        <p><strong>Nível de Experiência:</strong> ${vaga.nivel_experiencia || 'Não informado'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> ${getStatusBadgeHTML(vaga.status, 'vaga')}</p>
                        <p><strong>Data de Publicação:</strong> ${formatDate(vaga.data_publicacao)}</p>
                        <p><strong>Salário:</strong> ${vaga.mostrar_salario == 1 ? formatSalario(vaga.salario_min, vaga.salario_max) : 'Não divulgado'}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h5>Descrição</h5>
                        <div class="p-3 bg-light rounded">
                            ${vaga.descricao ? formatTextWithLineBreaks(vaga.descricao) : 'Nenhuma descrição fornecida.'}
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Requisitos</h5>
                        <div class="p-3 bg-light rounded">
                            ${vaga.requisitos ? formatTextWithLineBreaks(vaga.requisitos) : 'Nenhum requisito especificado.'}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Benefícios</h5>
                        <div class="p-3 bg-light rounded">
                            ${vaga.beneficios ? formatTextWithLineBreaks(vaga.beneficios) : 'Nenhum benefício especificado.'}
                  } else {
                // Exibir mensagem de erro
                document.getElementById('vaga_detalhes').innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes da vaga: ${data.message || 'Erro desconhecido'}</div>`;
            }
        },
        error: function(xhr, status, error) {
            // Exibir mensagem de erro
            document.getElementById('vaga_detalhes').innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes da vaga: ${error}</div>`;
function editarVaga(id, titulo) {
    console.log('Editando vaga ID:', id, 'Título:', titulo);
    
    // Mostrar modal com mensagem de carregamento
    const modalEditarVaga = new bootstrap.Modal(document.getElementById('modalEditarVaga'));
    modalEditarVaga.show();
    
    // Carregar dados da vaga via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/?route=api_vaga_detalhe&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Dados recebidos (editar):', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }
            
            // Verificar se os dados contém a vaga
            if (!data.data || !data.data.vaga) {
                throw new Error('Dados da vaga não encontrados na resposta');
            }
            
            const vaga = data.data.vaga;
            console.log('Dados da vaga para edição:', vaga);
            
            // Preencher o formulário com os dados da vaga
            document.getElementById('editar_vaga_id').value = vaga.id;
            document.getElementById('editar_titulo').value = vaga.titulo || '';
            
            // Definir o tipo de vaga (interna ou externa)
            const tipoVaga = vaga.tipo_vaga || 'interna';
            document.getElementById('editar_tipo_vaga').value = tipoVaga;
            
            // Mostrar/ocultar campos de empresa de acordo com o tipo de vaga
            toggleEditarEmpresaFields();
            
            // Preencher o campo apropriado de acordo com o tipo de vaga
            if (tipoVaga === 'interna') {
                document.getElementById('editar_empresa_id').value = vaga.empresa_id || '';
            } else if (tipoVaga === 'externa') {
                document.getElementById('editar_empresa_externa').value = vaga.empresa_externa || '';
            }
            
            // Preencher outros campos
            document.getElementById('editar_cidade').value = vaga.cidade || '';
            document.getElementById('editar_estado').value = vaga.estado || '';
            
            // Preencher campos de tipo de contrato, regime de trabalho e nível de experiência
            // Adicionando logs para depuração
            console.log('Tipo de contrato:', vaga.tipo_contrato);
            console.log('Regime de trabalho:', vaga.regime_trabalho);
            console.log('Nível de experiência:', vaga.nivel_experiencia);
            
            if (vaga.tipo_contrato) {
                document.getElementById('editar_tipo_contrato').value = vaga.tipo_contrato;
            }
            
            if (vaga.regime_trabalho) {
                document.getElementById('editar_regime_trabalho').value = vaga.regime_trabalho;
            }
            
            if (vaga.nivel_experiencia) {
                document.getElementById('editar_nivel_experiencia').value = vaga.nivel_experiencia;
            }
            document.getElementById('editar_salario_min').value = vaga.salario_min || '';
            document.getElementById('editar_salario_max').value = vaga.salario_max || '';
            document.getElementById('editar_mostrar_salario').checked = vaga.mostrar_salario == 1;
            document.getElementById('editar_descricao').value = vaga.descricao || '';
            document.getElementById('editar_requisitos').value = vaga.requisitos || '';
            document.getElementById('editar_beneficios').value = vaga.beneficios || '';
            document.getElementById('editar_status').value = vaga.status || 'pendente';
            
            // Atualizar título do modal
            document.getElementById('modalEditarVagaLabel').textContent = 'Editar Vaga: ' + vaga.titulo;
            
            // O modal já foi aberto no início da função
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar dados da vaga:', error);
            
            // Exibir mensagem de erro no modal
            const form = document.getElementById('formEditarVaga');
            if (form) {
                form.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                        Erro ao carregar dados da vaga: ${error.message}
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                `;
            } else {
                alert('Erro ao carregar dados da vaga: ' + error.message);
            }
        });
}

// Função para confirmar ação (fechar, abrir, excluir)
function confirmarAcao(acao, id, titulo) {
    let mensagem = '';
    let tituloModal = '';
    
    if (acao === 'fechar') {
        mensagem = `Tem certeza que deseja fechar a vaga "${titulo}"?`;
        tituloModal = 'Fechar Vaga';
    } else if (acao === 'abrir') {
        mensagem = `Tem certeza que deseja abrir a vaga "${titulo}"?`;
        tituloModal = 'Abrir Vaga';
    } else if (acao === 'excluir') {
        mensagem = `ATENÇÃO: Esta ação não pode ser desfeita. Tem certeza que deseja excluir a vaga "${titulo}"?`;
        tituloModal = 'Excluir Vaga';
    }
    
    document.getElementById('modalConfirmacaoLabel').textContent = tituloModal;
    document.getElementById('mensagem_confirmacao').textContent = mensagem;
    document.getElementById('acao_confirmacao').value = acao;
    document.getElementById('vaga_id_confirmacao').value = id;
    
    $('#modalConfirmacao').modal('show');
}

// Função para pesquisar vagas na tabela
document.getElementById('pesquisarVaga').addEventListener('keyup', function() {
    const termo = this.value.toLowerCase();
    const tabela = document.querySelector('table tbody');
    const linhas = tabela.querySelectorAll('tr');
    
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        if (texto.includes(termo)) {
            linha.style.display = '';
        } else {
            linha.style.display = 'none';
        }
    });
});

// Botão para editar vaga a partir do modal de visualização
document.getElementById('btnEditarVagaDetalhe').addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const titulo = this.getAttribute('data-titulo');
    
    const modalVisualizarVaga = bootstrap.Modal.getInstance(document.getElementById('modalVisualizarVaga'));
    modalVisualizarVaga.hide();
    editarVaga(id, titulo);
});

// Funções auxiliares para formatação
function formatDate(dateString) {
    if (!dateString) return 'Não informado';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function formatSalario(min, max) {
    if (!min && !max) return 'Não informado';
    if (min && max) return `R$ ${min} - R$ ${max}`;
    if (min) return `A partir de R$ ${min}`;
    if (max) return `Até R$ ${max}`;
}

function formatTextWithLineBreaks(text) {
    if (!text) return '';
    return text.replace(/\n/g, '<br>');
}

function getStatusBadgeHTML(status, tipo) {
    let className = '';
    let label = '';
    
    if (tipo === 'vaga') {
        if (status === 'aberta') {
            className = 'badge-success';
            label = 'Aberta';
        } else if (status === 'fechada') {
            className = 'badge-danger';
            label = 'Fechada';
        } else if (status === 'pendente') {
            className = 'badge-warning';
            label = 'Pendente';
        }
    } else {
        if (status === 'ativo') {
            className = 'badge-success';
            label = 'Ativo';
        } else if (status === 'inativo') {
            className = 'badge-danger';
            label = 'Inativo';
        } else if (status === 'pendente') {
            className = 'badge-warning';
            label = 'Pendente';
        } else if (status === 'bloqueado') {
            className = 'badge-dark';
            label = 'Bloqueado';
        }
    }
    
    return `<span class="badge ${className}">${label}</span>`;
}

// Função para alterar senha
function alterarSenha(id, nome) {
    // Limpar formulário
    document.getElementById('formAlterarSenha').reset();
    document.getElementById('usuario_id_senha').value = id;
    
    // Atualizar título do modal
    document.getElementById('modalAlterarSenhaLabel').textContent = 'Alterar Senha: ' + nome;
    
    // Abrir modal
    $('#modalAlterarSenha').modal('show');
}

// Validar confirmação de senha
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = this.value;
    
    if (novaSenha !== confirmarSenha) {
        this.classList.add('is-invalid');
        document.getElementById('senha_feedback').style.display = 'block';
    } else {
        this.classList.remove('is-invalid');
        document.getElementById('senha_feedback').style.display = 'none';
    }
});

// Botão para salvar nova senha
document.getElementById('btnSalvarSenha').addEventListener('click', function() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = document.getElementById('confirmar_senha').value;
    
    // Validar senhas
    if (novaSenha.length < 6) {
        alert('A senha deve ter pelo menos 6 caracteres.');
        return;
    }
    
    if (novaSenha !== confirmarSenha) {
        alert('As senhas não coincidem.');
        return;
    }
    
    // Obter dados do formulário
    const formData = new FormData(document.getElementById('formAlterarSenha'));
    
    // Enviar requisição para alterar senha
    fetch('<?php echo SITE_URL; ?>/?route=api_alterar_senha', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Senha alterada com sucesso!');
            $('#modalAlterarSenha').modal('hide');
        } else {
            alert('Erro ao alterar senha: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        alert('Erro ao alterar senha: ' + error.message);
    });
}

// Função para visualizar detalhes da vaga
function visualizarVaga(id, titulo) {
    console.log('Visualizando vaga ID:', id, 'Título:', titulo);
    
    // Atualizar título do modal
    document.getElementById('modalVisualizarVagaLabel').textContent = 'Detalhes da Vaga: ' + titulo;
    
    // Limpar conteúdo anterior
    document.getElementById('vagaDetalhes').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    
    // Mostrar o modal
    const modalVisualizarVaga = new bootstrap.Modal(document.getElementById('modalVisualizarVaga'));
    modalVisualizarVaga.show();
    
    // Obter detalhes da vaga via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php',
        type: 'POST',
        dataType: 'json',
        data: {
            acao: 'obter_detalhes',
            vaga_id: id
        },
        success: function(data) {
            console.log('Resposta recebida:', data);
            if (data.success && data.vaga) {
                const vaga = data.vaga;
                let html = `
                    <div class="vaga-info">
                        <h4>${vaga.titulo}</h4>
                        <p class="text-muted">ID: ${vaga.id}</p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Empresa:</strong> ${vaga.empresa_nome || 'Não informada'}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong> <span class="badge ${getStatusClass(vaga.status)}">${vaga.status}</span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Tipo de Contrato:</strong> ${vaga.tipo_contrato || 'Não informado'}
                            </div>
                            <div class="col-md-6">
                                <strong>Regime de Trabalho:</strong> ${vaga.regime_trabalho || 'Não informado'}
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Nível de Experiência:</strong> ${vaga.nivel_experiencia || 'Não informado'}
                            </div>
                            <div class="col-md-6">
                                <strong>Localização:</strong> ${getLocalizacao(vaga)}
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Data de Publicação:</strong> ${formatarData(vaga.data_publicacao)}
                            </div>
                            <div class="col-md-6">
                                <strong>Salário:</strong> ${formatarSalario(vaga)}
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <h5>Descrição</h5>
                            <p>${vaga.descricao ? vaga.descricao.replace(/\n/g, '<br>') : 'Não informada'}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Requisitos</h5>
                            <p>${vaga.requisitos ? vaga.requisitos.replace(/\n/g, '<br>') : 'Não informados'}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Benefícios</h5>
                            <p>${vaga.beneficios ? vaga.beneficios.replace(/\n/g, '<br>') : 'Não informados'}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Palavras-chave</h5>
                            <p>${vaga.palavras_chave || 'Não informadas'}</p>
                        </div>
                    </div>
                `;
                
                document.getElementById('vagaDetalhes').innerHTML = html;
                
                // Configurar botão de edição
                document.getElementById('btnEditarVagaDetalhe').setAttribute('data-id', vaga.id);
                document.getElementById('btnEditarVagaDetalhe').setAttribute('data-titulo', vaga.titulo);
            } else {
                document.getElementById('vagaDetalhes').innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes da vaga: ${data.message || 'Erro desconhecido'}<div>`;
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr, status, error);
            document.getElementById('vagaDetalhes').innerHTML = `<div class="alert alert-danger">Erro ao carregar detalhes da vaga: ${error}<div>`;
        }
    });
    
    // Abrir modal
    const modalVisualizarVaga = new bootstrap.Modal(document.getElementById('modalVisualizarVaga'));
    modalVisualizarVaga.show();
}

// Função para obter detalhes da vaga para edição
function editarVaga(id, titulo) {
    console.log('Obtendo detalhes da vaga ID:', id, 'para edição');
    
    // Limpar formulário
    document.getElementById('formEditarVaga').reset();
    
    // Atualizar título do modal
    document.getElementById('modalEditarVagaLabel').textContent = 'Editar Vaga: ' + titulo;
    
    // Obter detalhes da vaga via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/?route=api_vaga_detalhe&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Resposta recebida da API:', data);
            
            if (data.success && data.data && data.data.vaga) {
                // Preencher formulário com os dados da vaga
                const vaga = data.data.vaga;
                console.log('Dados da vaga para edição:', vaga);
                
                // Definir o tipo de vaga (interna ou externa)
                const tipoVaga = vaga.tipo_vaga || 'interna';
                document.getElementById('editar_tipo_vaga').value = tipoVaga;
                
                // Mostrar/ocultar campos de empresa de acordo com o tipo de vaga
                toggleEditarEmpresaFields();
                
                // Preencher o campo apropriado de acordo com o tipo de vaga
                if (tipoVaga === 'interna') {
                    document.getElementById('editar_empresa_id').value = vaga.empresa_id || '';
                } else if (tipoVaga === 'externa') {
                    document.getElementById('editar_empresa_externa').value = vaga.empresa_externa || '';
                }
                
                // Preencher os campos do formulário
                document.getElementById('editar_vaga_id').value = vaga.id;
                document.getElementById('editar_titulo').value = vaga.titulo || '';
                document.getElementById('editar_cidade').value = vaga.cidade || '';
                document.getElementById('editar_estado').value = vaga.estado || '';
                document.getElementById('editar_descricao').value = vaga.descricao || '';
                document.getElementById('editar_requisitos').value = vaga.requisitos || '';
                document.getElementById('editar_beneficios').value = vaga.beneficios || '';
                document.getElementById('editar_salario_min').value = vaga.salario_min || '';
                document.getElementById('editar_salario_max').value = vaga.salario_max || '';
                document.getElementById('editar_mostrar_salario').checked = vaga.mostrar_salario == 1;
                document.getElementById('editar_status').value = vaga.status || 'pendente';
                
                // Preencher campos de tipo de contrato, regime de trabalho e nível de experiência
                // Adicionando logs para depuração
                console.log('Tipo de contrato:', vaga.tipo_contrato);
                console.log('Regime de trabalho:', vaga.regime_trabalho);
                console.log('Nível de experiência:', vaga.nivel_experiencia);
                
                if (vaga.tipo_contrato) {
                    document.getElementById('editar_tipo_contrato').value = vaga.tipo_contrato;
                }
                
                if (vaga.regime_trabalho) {
                    document.getElementById('editar_regime_trabalho').value = vaga.regime_trabalho;
                }
                
                if (vaga.nivel_experiencia) {
                    document.getElementById('editar_nivel_experiencia').value = vaga.nivel_experiencia;
                }
                
                // Abrir modal
                const modalEditarVaga = new bootstrap.Modal(document.getElementById('modalEditarVaga'));
                modalEditarVaga.show();
            } else {
                alert('Erro ao obter detalhes da vaga: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr, status, error);
            alert('Erro ao obter detalhes da vaga: ' + error);
        }
    });
}

// Função para confirmar exclusão de vaga
function confirmarExclusao(id, titulo) {
    console.log('Confirmando exclusão da vaga ID:', id, 'Título:', titulo);
    
    if (confirm(`Tem certeza que deseja excluir a vaga "${titulo}"? Esta ação não pode ser desfeita.`)) {
        console.log('Exclusão confirmada, enviando requisição...');
        
        // Enviar requisição para excluir vaga
        $.ajax({
            url: '<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php',
            type: 'POST',
            dataType: 'json',
            data: {
                acao: 'excluir',
                vaga_id: id
            },
            success: function(data) {
                console.log('Resposta recebida:', data);
                if (data.success) {
                    alert('Vaga excluída com sucesso!');
                    // Recarregar a página para atualizar a lista
                    window.location.reload();
                } else {
                    alert('Erro ao excluir vaga: ' + (data.message || 'Erro desconhecido'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', xhr, status, error);
                alert('Erro ao excluir vaga: ' + error);
            }
        });
    } else {
        console.log('Exclusão cancelada pelo usuário');
    }
}

</script>

<script>
    // Definir a URL do site para uso no JavaScript
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/js/vagas_acoes.js"></script>
<script src="<?php echo SITE_URL; ?>/admin/pages/toggle_functions.js"></script>
