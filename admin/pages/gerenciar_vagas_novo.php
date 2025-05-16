<?php
// Obter lista de vagas
$db = Database::getInstance();
try {
    $vagas = $db->fetchAll("
        SELECT v.*, u.nome as empresa_nome, e.razao_social
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

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gerenciar Vagas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Gerenciar Vagas</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
    // Definir a URL do site para uso no JavaScript
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/js/gerenciar_vagas.js"></script>

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
        <div class="row mb-3">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAdicionarVaga">
                    <i class="fas fa-plus"></i> Adicionar Nova Vaga
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Vagas</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <input type="text" id="pesquisarVaga" class="form-control float-right" placeholder="Buscar">
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
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Empresa</th>
                            <th>Localização</th>
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
                                    <td><?php echo htmlspecialchars((string)$vaga['titulo']); ?></td>
                                    <td>
                                        <?php if (isset($vaga['tipo_vaga']) && $vaga['tipo_vaga'] == 'externa'): ?>
                                            <span class="badge badge-info">Externa</span>
                                        <?php else: ?>
                                            <span class="badge badge-primary">Interna</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($vaga['tipo_vaga']) && $vaga['tipo_vaga'] == 'externa' && !empty($vaga['empresa_externa'])) {
                                            echo htmlspecialchars((string)$vaga['empresa_externa']);
                                        } else {
                                            echo !empty($vaga['empresa_nome']) ? htmlspecialchars((string)$vaga['empresa_nome']) : 'Não informado';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $localizacao = [];
                                        if (!empty($vaga['cidade'])) $localizacao[] = $vaga['cidade'];
                                        if (!empty($vaga['estado'])) $localizacao[] = $vaga['estado'];
                                        echo !empty($localizacao) ? htmlspecialchars(implode(', ', $localizacao)) : 'Não informado';
                                        ?>
                                    </td>
                                    <td><?php echo !empty($vaga['regime_trabalho']) ? ucfirst(htmlspecialchars((string)$vaga['regime_trabalho'])) : 'Não informado'; ?></td>
                                    <td>
                                        <?php
                                        $status = isset($vaga['status']) ? $vaga['status'] : 'pendente';
                                        $status_class = '';
                                        
                                        switch ($status) {
                                            case 'aberta':
                                                $status_class = 'badge-success';
                                                break;
                                            case 'fechada':
                                                $status_class = 'badge-danger';
                                                break;
                                            case 'pendente':
                                                $status_class = 'badge-warning';
                                                break;
                                            default:
                                                $status_class = 'badge-secondary';
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
                                            <button type="button" class="btn btn-sm btn-info btn-visualizar" 
                                                    data-id="<?php echo $vaga['id']; ?>" 
                                                    data-titulo="<?php echo htmlspecialchars((string)$vaga['titulo']); ?>"
                                                    onclick="visualizarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary btn-editar" 
                                                    data-id="<?php echo $vaga['id']; ?>" 
                                                    data-titulo="<?php echo htmlspecialchars((string)$vaga['titulo']); ?>"
                                                    onclick="editarVaga(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger btn-excluir" 
                                                    data-id="<?php echo $vaga['id']; ?>" 
                                                    data-titulo="<?php echo htmlspecialchars((string)$vaga['titulo']); ?>"
                                                    onclick="confirmarExclusao(<?php echo $vaga['id']; ?>, '<?php echo addslashes($vaga['titulo']); ?>')">
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
</section>

<!-- Modal para Adicionar Vaga -->
<div class="modal fade" id="modalAdicionarVaga" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarVagaLabel">Adicionar Nova Vaga</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAdicionarVaga" action="<?php echo SITE_URL; ?>/?route=processar_vaga_admin" method="post">
                <input type="hidden" name="acao" value="adicionar">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tipo_vaga">Tipo de Vaga</label>
                        <select class="form-control" id="tipo_vaga" name="tipo_vaga" required onchange="toggleEmpresaFields()">
                            <option value="interna">Interna (Empresa cadastrada)</option>
                            <option value="externa">Externa (Empresa não cadastrada)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="empresa_id_group">
                        <label for="empresa_id">Empresa</label>
                        <select class="form-control" id="empresa_id" name="empresa_id">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id']; ?>">
                                    <?php echo !empty($empresa['razao_social']) ? htmlspecialchars((string)$empresa['razao_social']) : htmlspecialchars((string)$empresa['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="empresa_externa_group" style="display: none;">
                        <label for="empresa_externa">Nome da Empresa Externa</label>
                        <input type="text" class="form-control" id="empresa_externa" name="empresa_externa" placeholder="Nome da empresa não cadastrada">
                    </div>
                    
                    <div class="form-group">
                        <label for="titulo">Título da Vaga</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requisitos">Requisitos</label>
                        <textarea class="form-control" id="requisitos" name="requisitos" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="palavras_chave">Palavras-chave (separadas por vírgula)</label>
                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" placeholder="Ex: php, desenvolvedor, programador">
                    </div>
                    
                    <div class="form-group">
                        <label for="beneficios">Benefícios</label>
                        <textarea class="form-control" id="beneficios" name="beneficios" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_contrato">Tipo de Contrato</label>
                                <select class="form-control" id="tipo_contrato" name="tipo_contrato">
                                    <option value="">Selecione</option>
                                    <option value="CLT">CLT</option>
                                    <option value="PJ">PJ</option>
                                    <option value="Temporário">Temporário</option>
                                    <option value="Estágio">Estágio</option>
                                    <option value="Freelancer">Freelancer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nivel_experiencia">Nível de Experiência</label>
                                <select class="form-control" id="nivel_experiencia" name="nivel_experiencia">
                                    <option value="">Selecione</option>
                                    <option value="Estágio">Estágio</option>
                                    <option value="Júnior">Júnior</option>
                                    <option value="Pleno">Pleno</option>
                                    <option value="Sênior">Sênior</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="aberta">Aberta</option>
                                    <option value="fechada">Fechada</option>
                                    <option value="pendente">Pendente</option>
                                </select>
                            </div>
                        </div>
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
                                <input type="text" class="form-control" id="estado" name="estado">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="salario_min">Salário Mínimo</label>
                                <input type="text" class="form-control" id="salario_min" name="salario_min" placeholder="Ex: 2000.00">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="salario_max">Salário Máximo</label>
                                <input type="text" class="form-control" id="salario_max" name="salario_max" placeholder="Ex: 3000.00">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="mostrar_salario">Mostrar?</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="mostrar_salario" name="mostrar_salario" value="1">
                                    <label class="custom-control-label" for="mostrar_salario">Sim</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Definir a URL do site para uso no JavaScript
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/js/gerenciar_vagas.js"></script>

<!-- Modal para Visualizar Vaga -->
<div class="modal fade" id="modalVisualizarVaga" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarVagaLabel">Detalhes da Vaga</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="visualizarVagaConteudo">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Carregando...</span>
                    </div>
                    <p>Carregando detalhes da vaga...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Definir a URL do site para uso no JavaScript
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/js/gerenciar_vagas.js"></script>

<!-- Modal para Editar Vaga -->
<div class="modal fade" id="modalEditarVaga" tabindex="-1" role="dialog" aria-labelledby="modalEditarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarVagaLabel">Editar Vaga</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditarVaga" action="<?php echo SITE_URL; ?>/?route=processar_vaga_admin" method="post">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="vaga_id" id="editar_vaga_id" value="">
                
                <div class="modal-body">
                    <div class="text-center" id="editarVagaLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p>Carregando dados da vaga...</p>
                    </div>
                    
                    <div id="editarVagaForm" style="display: none;">
                        <div class="form-group">
                            <label for="editar_tipo_vaga">Tipo de Vaga</label>
                            <select class="form-control" id="editar_tipo_vaga" name="tipo_vaga" required onchange="toggleEditarEmpresaFields()">
                                <option value="interna">Interna (Empresa cadastrada)</option>
                                <option value="externa">Externa (Empresa não cadastrada)</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="editar_empresa_id_group">
                            <label for="editar_empresa_id">Empresa</label>
                            <select class="form-control" id="editar_empresa_id" name="empresa_id">
                                <option value="">Selecione uma empresa</option>
                                <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?php echo $empresa['id']; ?>">
                                        <?php echo !empty($empresa['razao_social']) ? htmlspecialchars((string)$empresa['razao_social']) : htmlspecialchars((string)$empresa['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="editar_empresa_externa_group" style="display: none;">
                            <label for="editar_empresa_externa">Nome da Empresa Externa</label>
                            <input type="text" class="form-control" id="editar_empresa_externa" name="empresa_externa" placeholder="Nome da empresa não cadastrada">
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_titulo">Título da Vaga</label>
                            <input type="text" class="form-control" id="editar_titulo" name="titulo" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_descricao">Descrição</label>
                            <textarea class="form-control" id="editar_descricao" name="descricao" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_requisitos">Requisitos</label>
                            <textarea class="form-control" id="editar_requisitos" name="requisitos" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_palavras_chave">Palavras-chave (separadas por vírgula)</label>
                            <input type="text" class="form-control" id="editar_palavras_chave" name="palavras_chave" placeholder="Ex: php, desenvolvedor, programador">
                        </div>
                        
                        <div class="form-group">
                            <label for="editar_beneficios">Benefícios</label>
                            <textarea class="form-control" id="editar_beneficios" name="beneficios" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editar_tipo_contrato">Tipo de Contrato</label>
                                    <select class="form-control" id="editar_tipo_contrato" name="tipo_contrato">
                                        <option value="">Selecione</option>
                                        <option value="CLT">CLT</option>
                                        <option value="PJ">PJ</option>
                                        <option value="Temporário">Temporário</option>
                                        <option value="Estágio">Estágio</option>
                                        <option value="Freelancer">Freelancer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editar_nivel_experiencia">Nível de Experiência</label>
                                    <select class="form-control" id="editar_nivel_experiencia" name="nivel_experiencia">
                                        <option value="">Selecione</option>
                                        <option value="Estágio">Estágio</option>
                                        <option value="Júnior">Júnior</option>
                                        <option value="Pleno">Pleno</option>
                                        <option value="Sênior">Sênior</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editar_status">Status</label>
                                    <select class="form-control" id="editar_status" name="status" required>
                                        <option value="aberta">Aberta</option>
                                        <option value="fechada">Fechada</option>
                                        <option value="pendente">Pendente</option>
                                    </select>
                                </div>
                            </div>
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
                                    <input type="text" class="form-control" id="editar_estado" name="estado">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="editar_salario_min">Salário Mínimo</label>
                                    <input type="text" class="form-control" id="editar_salario_min" name="salario_min" placeholder="Ex: 2000.00">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="editar_salario_max">Salário Máximo</label>
                                    <input type="text" class="form-control" id="editar_salario_max" name="salario_max" placeholder="Ex: 3000.00">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="editar_mostrar_salario">Mostrar?</label>
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="editar_mostrar_salario" name="mostrar_salario" value="1">
                                        <label class="custom-control-label" for="editar_mostrar_salario">Sim</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Definir a URL do site para uso no JavaScript
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/js/gerenciar_vagas.js"></script>

<!-- Modal para Confirmar Exclusão -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmarExclusaoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarExclusaoLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a vaga <strong id="excluirVagaTitulo"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formExcluirVaga" action="<?php echo SITE_URL; ?>/?route=processar_vaga_admin" method="post">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="vaga_id" id="excluir_vaga_id" value="">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Definir a URL do site para uso no JavaScript
    var SITE_URL = '<?php echo SITE_URL; ?>';
</script>
<script src="<?php echo SITE_URL; ?>/admin/js/gerenciar_vagas.js"></script>
