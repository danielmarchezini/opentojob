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

// Verificar se existem mensagens flash
$flash_message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
$flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : '';

// Limpar mensagens flash após exibição
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gerenciar Modelos de E-mail</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Modelos de E-mail</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <?php if (!empty($flash_message)): ?>
            <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show">
                <?php echo $flash_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Modelos de E-mail</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAdicionarModelo">
                        <i class="fas fa-plus"></i> Adicionar Modelo
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Assunto</th>
                                <th>Data de Atualização</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($modelos)): ?>
                                <?php foreach ($modelos as $modelo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)$modelo['codigo']); ?></td>
                                        <td><?php echo htmlspecialchars((string)$modelo['nome']); ?></td>
                                        <td><?php echo htmlspecialchars((string)$modelo['assunto']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($modelo['data_atualizacao'])) {
                                                echo date('d/m/Y H:i', strtotime($modelo['data_atualizacao']));
                                            } else {
                                                echo 'Não informado';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" onclick="visualizarModelo(<?php echo $modelo['id']; ?>, '<?php echo addslashes(htmlspecialchars((string)$modelo['nome'])); ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editarModelo(<?php echo $modelo['id']; ?>, '<?php echo addslashes(htmlspecialchars((string)$modelo['nome'])); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $modelo['id']; ?>, '<?php echo addslashes(htmlspecialchars((string)$modelo['nome'])); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum modelo de e-mail encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Adicionar Modelo -->
<div class="modal fade" id="modalAdicionarModelo" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarModeloLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarModeloLabel">Adicionar Modelo de E-mail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAdicionarModelo" action="<?php echo SITE_URL; ?>/?route=processar_email_admin" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="form-group">
                        <label for="adicionar_codigo">Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adicionar_codigo" name="codigo" required placeholder="Ex: boas_vindas">
                        <small class="form-text text-muted">Código único para identificar o modelo (use apenas letras, números e underscore).</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_nome">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adicionar_nome" name="nome" required placeholder="Ex: E-mail de Boas-vindas">
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_assunto">Assunto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adicionar_assunto" name="assunto" required placeholder="Ex: Bem-vindo ao OpenToJob!">
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_corpo">Corpo do E-mail <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="adicionar_corpo" name="corpo" rows="10" required></textarea>
                        <small class="form-text text-muted">Você pode usar HTML para formatar o e-mail. Use {{variavel}} para inserir variáveis dinâmicas.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="adicionar_variaveis">Variáveis Disponíveis</label>
                        <input type="text" class="form-control" id="adicionar_variaveis" name="variaveis" placeholder="Ex: nome, email, url_site">
                        <small class="form-text text-muted">Lista de variáveis disponíveis, separadas por vírgula.</small>
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

<!-- Modal Visualizar Modelo -->
<div class="modal fade" id="modalVisualizarModelo" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarModeloLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarModeloLabel">Detalhes do Modelo de E-mail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modelo_detalhes">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Carregando detalhes do modelo...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarModeloDetalhe">Editar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Modelo -->
<div class="modal fade" id="modalEditarModelo" tabindex="-1" role="dialog" aria-labelledby="modalEditarModeloLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarModeloLabel">Editar Modelo de E-mail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditarModelo" action="<?php echo SITE_URL; ?>/?route=processar_email_admin" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" id="editar_id" name="id" value="">
                    
                    <div class="form-group">
                        <label for="editar_codigo">Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar_codigo" name="codigo" required readonly>
                        <small class="form-text text-muted">O código não pode ser alterado após a criação.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_nome">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar_nome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_assunto">Assunto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar_assunto" name="assunto" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_corpo">Corpo do E-mail <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editar_corpo" name="corpo" rows="10" required></textarea>
                        <small class="form-text text-muted">Você pode usar HTML para formatar o e-mail. Use {{variavel}} para inserir variáveis dinâmicas.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_variaveis">Variáveis Disponíveis</label>
                        <input type="text" class="form-control" id="editar_variaveis" name="variaveis">
                        <small class="form-text text-muted">Lista de variáveis disponíveis, separadas por vírgula.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmar Exclusão -->
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
                <p>Tem certeza que deseja excluir o modelo de e-mail "<span id="excluir_nome_modelo"></span>"?</p>
                <p class="text-danger">Esta ação não pode ser desfeita!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formExcluirModelo" action="<?php echo SITE_URL; ?>/?route=processar_email_admin" method="post">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" id="excluir_id" name="id" value="">
                    <button type="submit" class="btn btn-danger">Excluir</button>
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
        url: '<?php echo SITE_URL; ?>/?route=processar_email_admin',
        type: 'POST',
        dataType: 'json',
        data: {
            acao: 'obter_detalhes',
            id: id
        },
        success: function(data) {
            debug('Resposta recebida:', data);
            
            if (data.success && data.modelo) {
                const modelo = data.modelo;
                
                // Formatar variáveis para exibição
                let variaveis = '';
                if (modelo.variaveis) {
                    try {
                        const vars = JSON.parse(modelo.variaveis);
                        variaveis = vars.join(', ');
                    } catch (e) {
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
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Visualização do E-mail</h5>
                            <div class="card">
                                <div class="card-header">
                                    <strong>Assunto:</strong> ${modelo.assunto}
                                </div>
                                <div class="card-body bg-light">
                                    ${modelo.corpo}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Atualizar conteúdo do modal
                $('#modelo_detalhes').html(html);
            } else {
                // Exibir mensagem de erro
                $('#modelo_detalhes').html(`<div class="alert alert-danger">Erro ao carregar detalhes do modelo: ${data.message || 'Erro desconhecido'}</div>`);
            }
        },
        error: function(xhr, status, error) {
            debug('Erro na requisição AJAX:', {xhr: xhr, status: status, error: error});
            // Exibir mensagem de erro
            $('#modelo_detalhes').html(`<div class="alert alert-danger">Erro ao carregar detalhes do modelo: ${error}</div>`);
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
    
    // Buscar detalhes do modelo via API
    debug('Enviando requisição AJAX para obter dados do modelo para edição:', {id: id});
    
    $.ajax({
        url: '<?php echo SITE_URL; ?>/?route=processar_email_admin',
        type: 'POST',
        dataType: 'json',
        data: {
            acao: 'obter_detalhes',
            id: id
        },
        success: function(data) {
            debug('Resposta recebida:', data);
            
            if (data.success && data.modelo) {
                const modelo = data.modelo;
                
                // Formatar variáveis para edição
                let variaveis = '';
                if (modelo.variaveis) {
                    try {
                        const vars = JSON.parse(modelo.variaveis);
                        variaveis = vars.join(', ');
                    } catch (e) {
                        variaveis = modelo.variaveis;
                    }
                }
                
                // Preencher formulário com dados do modelo
                $('#editar_id').val(modelo.id);
                $('#editar_codigo').val(modelo.codigo);
                $('#editar_nome').val(modelo.nome);
                $('#editar_assunto').val(modelo.assunto);
                $('#editar_corpo').val(modelo.corpo);
                $('#editar_variaveis').val(variaveis);
                
                // Se o CKEditor estiver disponível, atualizar o conteúdo
                if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.editar_corpo) {
                    CKEDITOR.instances.editar_corpo.setData(modelo.corpo);
                }
            } else {
                alert('Erro ao carregar dados do modelo: ' + (data.message || 'Erro desconhecido'));
                $('#modalEditarModelo').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            debug('Erro na requisição AJAX:', {xhr: xhr, status: status, error: error});
            alert('Erro ao carregar dados do modelo: ' + error);
            $('#modalEditarModelo').modal('hide');
        }
    });
}

// Função para confirmar exclusão
function confirmarExclusao(id, nome) {
    debug('Confirmando exclusão do modelo:', {id: id, nome: nome});
    
    $('#excluir_id').val(id);
    $('#excluir_nome_modelo').text(nome);
    $('#modalConfirmarExclusao').modal('show');
}

$(document).ready(function() {
    debug('DOM carregado, configurando componentes...');
    
    // Inicializar editor WYSIWYG para os campos de corpo do e-mail
    if (typeof CKEDITOR !== 'undefined') {
        debug('Inicializando CKEditor...');
        
        CKEDITOR.replace('adicionar_corpo', {
            height: 300,
            removeButtons: 'Save,NewPage,Preview,Print,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Undo,Redo,Find,Replace,SelectAll,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Strike,Subscript,Superscript,CopyFormatting,RemoveFormat,NumberedList,BulletedList,Outdent,Indent,Blockquote,CreateDiv,JustifyLeft,JustifyCenter,JustifyRight,JustifyBlock,BidiLtr,BidiRtl,Language,Link,Unlink,Anchor,Image,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Styles,Format,Font,FontSize,TextColor,BGColor,Maximize,ShowBlocks,About'
        });
        
        CKEDITOR.replace('editar_corpo', {
            height: 300,
            removeButtons: 'Save,NewPage,Preview,Print,Templates,Cut,Copy,Paste,PasteText,PasteFromWord,Undo,Redo,Find,Replace,SelectAll,Scayt,Form,Checkbox,Radio,TextField,Textarea,Select,Button,ImageButton,HiddenField,Strike,Subscript,Superscript,CopyFormatting,RemoveFormat,NumberedList,BulletedList,Outdent,Indent,Blockquote,CreateDiv,JustifyLeft,JustifyCenter,JustifyRight,JustifyBlock,BidiLtr,BidiRtl,Language,Link,Unlink,Anchor,Image,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Styles,Format,Font,FontSize,TextColor,BGColor,Maximize,ShowBlocks,About'
        });
    } else {
        debug('CKEditor não disponível');
    }
    
    // Botão de editar a partir da visualização
    $('#btnEditarModeloDetalhe').on('click', function() {
        const id = $(this).attr('data-id');
        const nome = $(this).attr('data-nome');
        debug('Clique em botão de editar a partir da visualização:', {id: id, nome: nome});
        
        $('#modalVisualizarModelo').modal('hide');
        editarModelo(id, nome);
    });
    
    // Validação do formulário de adição
    $('#formAdicionarModelo').on('submit', function(e) {
        debug('Submetendo formulário de adição...');
        
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.adicionar_corpo) {
            const conteudo = CKEDITOR.instances.adicionar_corpo.getData();
            if (!conteudo.trim()) {
                alert('O corpo do e-mail não pode estar vazio.');
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Validação do formulário de edição
    $('#formEditarModelo').on('submit', function(e) {
        debug('Submetendo formulário de edição...');
        
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.editar_corpo) {
            const conteudo = CKEDITOR.instances.editar_corpo.getData();
            if (!conteudo.trim()) {
                alert('O corpo do e-mail não pode estar vazio.');
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
