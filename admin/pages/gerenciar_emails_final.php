<?php
// Obter lista de modelos de e-mail
$db = Database::getInstance();
try {
    $modelos = $db->fetchAll("
        SELECT *
        FROM modelos_email
        ORDER BY nome ASC
    ");
} catch (PDOException $e) {
    // Se ocorrer um erro, exibir mensagem e criar array vazio
    $_SESSION['flash_message'] = "Erro ao carregar modelos de e-mail: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $modelos = [];
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gerenciar Modelos de E-mail</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">Modelos de E-mail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['flash_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Modelos de E-mail</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="adicionarModelo()">
                            <i class="fas fa-plus"></i> Adicionar Modelo
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Assunto</th>
                                <th style="width: 200px">Última Atualização</th>
                                <th style="width: 150px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($modelos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Nenhum modelo de e-mail encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($modelos as $modelo): ?>
                                    <tr>
                                        <td><?php echo $modelo['id']; ?></td>
                                        <td><?php echo htmlspecialchars($modelo['codigo']); ?></td>
                                        <td><?php echo htmlspecialchars($modelo['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($modelo['assunto']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($modelo['data_atualizacao'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm" onclick="visualizarModelo(<?php echo $modelo['id']; ?>, '<?php echo addslashes($modelo['nome']); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" onclick="editarModelo(<?php echo $modelo['id']; ?>, '<?php echo addslashes($modelo['nome']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="excluirModelo(<?php echo $modelo['id']; ?>, '<?php echo addslashes($modelo['nome']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
</div>

<!-- Modal de Visualização -->
<div class="modal fade" id="modalVisualizarModelo" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarModeloLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarModeloLabel">Detalhes do Modelo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modelo_detalhes">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Carregando detalhes do modelo...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnEditarModeloDetalhe">Editar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Edição -->
<div class="modal fade" id="modalEditarModelo" tabindex="-1" role="dialog" aria-labelledby="modalEditarModeloLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarModeloLabel">Editar Modelo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form_editar_modelo" action="<?php echo SITE_URL; ?>/?route=processar_email_admin" method="post">
                    <input type="hidden" name="acao" value="atualizar">
                    <input type="hidden" name="id" id="editar_id">
                    
                    <div class="form-group">
                        <label for="editar_codigo">Código</label>
                        <input type="text" class="form-control" id="editar_codigo" name="codigo" readonly>
                        <small class="form-text text-muted">O código é usado internamente pelo sistema e não pode ser alterado.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_nome">Nome</label>
                        <input type="text" class="form-control" id="editar_nome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_assunto">Assunto</label>
                        <input type="text" class="form-control" id="editar_assunto" name="assunto" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_corpo">Corpo do E-mail (HTML)</label>
                        <textarea class="form-control" id="editar_corpo" name="corpo" rows="10" required></textarea>
                        <small class="form-text text-muted">Use HTML para formatar o conteúdo. Variáveis podem ser incluídas no formato {{nome_variavel}}.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_variaveis">Variáveis Disponíveis (JSON)</label>
                        <textarea class="form-control" id="editar_variaveis" name="variaveis" rows="3"></textarea>
                        <small class="form-text text-muted">Lista de variáveis disponíveis para este modelo em formato JSON array. Ex: ["nome", "email", "url_site"]</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="salvarEdicao()">Salvar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Adição -->
<div class="modal fade" id="modalAdicionarModelo" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarModeloLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarModeloLabel">Adicionar Modelo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form_adicionar_modelo" action="<?php echo SITE_URL; ?>/?route=processar_email_admin" method="post">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="form-group">
                        <label for="adicionar_codigo">Código</label>
                        <input type="text" class="form-control" id="adicionar_codigo" name="codigo" required>
                        <small class="form-text text-muted">O código deve ser único e será usado internamente pelo sistema.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_nome">Nome</label>
                        <input type="text" class="form-control" id="adicionar_nome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_assunto">Assunto</label>
                        <input type="text" class="form-control" id="adicionar_assunto" name="assunto" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_corpo">Corpo do E-mail (HTML)</label>
                        <textarea class="form-control" id="adicionar_corpo" name="corpo" rows="10" required></textarea>
                        <small class="form-text text-muted">Use HTML para formatar o conteúdo. Variáveis podem ser incluídas no formato {{nome_variavel}}.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_variaveis">Variáveis Disponíveis (JSON)</label>
                        <textarea class="form-control" id="adicionar_variaveis" name="variaveis" rows="3"></textarea>
                        <small class="form-text text-muted">Lista de variáveis disponíveis para este modelo em formato JSON array. Ex: ["nome", "email", "url_site"]</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="salvarAdicao()">Salvar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluirModelo" tabindex="-1" role="dialog" aria-labelledby="modalExcluirModeloLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExcluirModeloLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o modelo <strong id="excluir_nome_modelo"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <form id="form_excluir_modelo" action="<?php echo SITE_URL; ?>/?route=processar_email_admin" method="post">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" id="excluir_id">
                    <button type="button" class="btn btn-danger" onclick="confirmarExclusao()">Excluir</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Função para depuração
function debug(message, data) {
    console.log(message, data || '');
}

// Função para visualizar modelo
function visualizarModelo(id, nome) {
    debug('Visualizando modelo:', {id: id, nome: nome});
    
    // Abrir modal de visualização
    $('#modalVisualizarModelo').modal('show');
    
    // Atualizar título do modal
    $('#modalVisualizarModeloLabel').text('Detalhes do Modelo: ' + nome);
    
    // Mostrar indicador de carregamento
    $('#modelo_detalhes').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Carregando detalhes do modelo...</p></div>');
    
    // Configurar botão de edição
    $('#btnEditarModeloDetalhe').attr('onclick', 'editarModelo(' + id + ', "' + nome.replace(/"/g, '\\"') + '")');
    
    // Buscar detalhes do modelo via API
    debug('Enviando requisição AJAX para obter detalhes do modelo:', {id: id});
    
    $.ajax({
        url: '<?php echo SITE_URL; ?>/admin/api_email.php',
        type: 'GET',
        dataType: 'json',
        data: {
            acao: 'obter',
            id: id
        },
        success: function(data) {
            debug('Resposta recebida:', data);
            
            if (data && data.success && data.modelo) {
                const modelo = data.modelo;
                
                // Formatar variáveis para exibição
                let variaveis = '';
                if (modelo.variaveis) {
                    try {
                        const vars = JSON.parse(modelo.variaveis);
                        variaveis = vars.join(', ');
                    } catch (e) {
                        debug('Erro ao processar variáveis:', e);
                        variaveis = modelo.variaveis;
                    }
                }
                
                // Preencher detalhes do modelo
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Informações Básicas</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Código</th>
                                    <td>${modelo.codigo}</td>
                                </tr>
                                <tr>
                                    <th>Nome</th>
                                    <td>${modelo.nome}</td>
                                </tr>
                                <tr>
                                    <th>Assunto</th>
                                    <td>${modelo.assunto}</td>
                                </tr>
                                <tr>
                                    <th>Variáveis</th>
                                    <td>${variaveis || 'Nenhuma variável definida'}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Datas</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Data de Criação</th>
                                    <td>${new Date(modelo.data_criacao).toLocaleString('pt-BR')}</td>
                                </tr>
                                <tr>
                                    <th>Última Atualização</th>
                                    <td>${new Date(modelo.data_atualizacao).toLocaleString('pt-BR')}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Corpo do E-mail</h5>
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Visualização do HTML</h3>
                                </div>
                                <div class="card-body">
                                    ${modelo.corpo}
                                </div>
                            </div>
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">Código HTML</h3>
                                </div>
                                <div class="card-body">
                                    <pre><code>${modelo.corpo.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#modelo_detalhes').html(html);
            } else {
                // Exibir mensagem de erro
                $('#modelo_detalhes').html(`
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Erro ao carregar dados do modelo</h5>
                        <p>${data && data.message ? data.message : 'Erro desconhecido'}</p>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='<?php echo SITE_URL; ?>/?route=diagnostico_emails'">Executar Diagnóstico</button>
                        </div>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            debug('Erro na requisição AJAX:', {xhr: xhr, status: status, error: error});
            
            let errorMessage = error;
            try {
                // Tentar extrair a mensagem de erro do JSON de resposta
                if (xhr.responseText) {
                    const responseData = JSON.parse(xhr.responseText);
                    if (responseData && responseData.message) {
                        errorMessage = responseData.message;
                    }
                }
            } catch (e) {
                debug('Erro ao processar resposta de erro:', e);
                errorMessage = 'Erro ao processar resposta do servidor. Verifique o console para mais detalhes.';
            }
            
            // Exibir mensagem de erro
            $('#modelo_detalhes').html(`
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erro ao carregar dados do modelo</h5>
                    <p>${errorMessage}</p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='<?php echo SITE_URL; ?>/?route=diagnostico_emails'">Executar Diagnóstico</button>
                    </div>
                </div>
            `);
        }
    });
}

// Função para editar modelo
function editarModelo(id, nome) {
    debug('Editando modelo:', {id: id, nome: nome});
    
    // Fechar modal de visualização se estiver aberto
    $('#modalVisualizarModelo').modal('hide');
    
    // Abrir modal de edição
    $('#modalEditarModelo').modal('show');
    
    // Atualizar título do modal
    $('#modalEditarModeloLabel').text('Editar Modelo: ' + nome);
    
    // Mostrar indicador de carregamento
    $('#form_editar_modelo').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Carregando formulário...</p></div>');
    
    // Buscar detalhes do modelo via API
    debug('Enviando requisição AJAX para obter detalhes do modelo para edição:', {id: id});
    
    $.ajax({
        url: '<?php echo SITE_URL; ?>/admin/api_email.php',
        type: 'GET',
        dataType: 'json',
        data: {
            acao: 'obter',
            id: id
        },
        success: function(data) {
            debug('Resposta recebida para edição:', data);
            
            if (data && data.success && data.modelo) {
                const modelo = data.modelo;
                
                // Reconstruir o formulário
                let html = `
                    <input type="hidden" name="acao" value="atualizar">
                    <input type="hidden" name="id" id="editar_id" value="${modelo.id}">
                    
                    <div class="form-group">
                        <label for="editar_codigo">Código</label>
                        <input type="text" class="form-control" id="editar_codigo" name="codigo" value="${modelo.codigo}" readonly>
                        <small class="form-text text-muted">O código é usado internamente pelo sistema e não pode ser alterado.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_nome">Nome</label>
                        <input type="text" class="form-control" id="editar_nome" name="nome" value="${modelo.nome}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_assunto">Assunto</label>
                        <input type="text" class="form-control" id="editar_assunto" name="assunto" value="${modelo.assunto}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_corpo">Corpo do E-mail (HTML)</label>
                        <textarea class="form-control" id="editar_corpo" name="corpo" rows="10" required>${modelo.corpo}</textarea>
                        <small class="form-text text-muted">Use HTML para formatar o conteúdo. Variáveis podem ser incluídas no formato {{nome_variavel}}.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_variaveis">Variáveis Disponíveis (JSON)</label>
                        <textarea class="form-control" id="editar_variaveis" name="variaveis" rows="3">${modelo.variaveis || ''}</textarea>
                        <small class="form-text text-muted">Lista de variáveis disponíveis para este modelo em formato JSON array. Ex: ["nome", "email", "url_site"]</small>
                    </div>
                `;
                
                $('#form_editar_modelo').html(html);
            } else {
                // Exibir mensagem de erro
                $('#form_editar_modelo').html(`
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Erro ao carregar dados do modelo</h5>
                        <p>${data && data.message ? data.message : 'Erro desconhecido'}</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            debug('Erro na requisição AJAX para edição:', {xhr: xhr, status: status, error: error});
            
            // Exibir mensagem de erro
            $('#form_editar_modelo').html(`
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erro ao carregar dados do modelo</h5>
                    <p>${error}</p>
                </div>
            `);
        }
    });
}

// Função para adicionar modelo
function adicionarModelo() {
    debug('Adicionando novo modelo');
    
    // Abrir modal de adição
    $('#modalAdicionarModelo').modal('show');
    
    // Limpar formulário
    $('#form_adicionar_modelo')[0].reset();
}

// Função para excluir modelo
function excluirModelo(id, nome) {
    debug('Excluindo modelo:', {id: id, nome: nome});
    
    // Abrir modal de exclusão
    $('#modalExcluirModelo').modal('show');
    
    // Atualizar texto de confirmação
    $('#excluir_nome_modelo').text(nome);
    
    // Definir ID para exclusão
    $('#excluir_id').val(id);
}

// Função para salvar edição
function salvarEdicao() {
    debug('Salvando edição do modelo');
    
    // Validar formulário
    const form = $('#form_editar_modelo')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Enviar formulário via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/?route=processar_email_admin',
        type: 'POST',
        data: $('#form_editar_modelo').serialize(),
        dataType: 'json',
        success: function(data) {
            debug('Resposta do servidor após edição:', data);
            
            if (data.success) {
                // Fechar modal
                $('#modalEditarModelo').modal('hide');
                
                // Exibir mensagem de sucesso e recarregar página
                alert('Modelo atualizado com sucesso!');
                window.location.reload();
            } else {
                // Exibir mensagem de erro
                alert('Erro ao atualizar modelo: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function(xhr, status, error) {
            debug('Erro ao salvar edição:', {xhr: xhr, status: status, error: error});
            
            // Exibir mensagem de erro
            alert('Erro ao atualizar modelo: ' + error);
        }
    });
}

// Função para salvar adição
function salvarAdicao() {
    debug('Salvando novo modelo');
    
    // Validar formulário
    const form = $('#form_adicionar_modelo')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Enviar formulário via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/?route=processar_email_admin',
        type: 'POST',
        data: $('#form_adicionar_modelo').serialize(),
        dataType: 'json',
        success: function(data) {
            debug('Resposta do servidor após adição:', data);
            
            if (data.success) {
                // Fechar modal
                $('#modalAdicionarModelo').modal('hide');
                
                // Exibir mensagem de sucesso e recarregar página
                alert('Modelo adicionado com sucesso!');
                window.location.reload();
            } else {
                // Exibir mensagem de erro
                alert('Erro ao adicionar modelo: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function(xhr, status, error) {
            debug('Erro ao salvar novo modelo:', {xhr: xhr, status: status, error: error});
            
            // Exibir mensagem de erro
            alert('Erro ao adicionar modelo: ' + error);
        }
    });
}

// Função para confirmar exclusão
function confirmarExclusao() {
    debug('Confirmando exclusão do modelo');
    
    // Enviar formulário via AJAX
    $.ajax({
        url: '<?php echo SITE_URL; ?>/?route=processar_email_admin',
        type: 'POST',
        data: $('#form_excluir_modelo').serialize(),
        dataType: 'json',
        success: function(data) {
            debug('Resposta do servidor após exclusão:', data);
            
            if (data.success) {
                // Fechar modal
                $('#modalExcluirModelo').modal('hide');
                
                // Exibir mensagem de sucesso e recarregar página
                alert('Modelo excluído com sucesso!');
                window.location.reload();
            } else {
                // Exibir mensagem de erro
                alert('Erro ao excluir modelo: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function(xhr, status, error) {
            debug('Erro ao excluir modelo:', {xhr: xhr, status: status, error: error});
            
            // Exibir mensagem de erro
            alert('Erro ao excluir modelo: ' + error);
        }
    });
}

// Inicialização
$(document).ready(function() {
    debug('Página de gerenciamento de modelos de e-mail carregada');
});
</script>
