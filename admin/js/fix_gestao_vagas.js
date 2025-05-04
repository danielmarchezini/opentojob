/**
 * Script de correção para a página de gestão de vagas
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Garantir que o jQuery esteja carregado
if (typeof jQuery === 'undefined') {
    console.error('jQuery não está carregado!');
} else {
    console.log('jQuery está carregado. Versão:', jQuery.fn.jquery);
}

// Função para inicializar os eventos dos botões
function initButtonEvents() {
    console.log('Inicializando eventos dos botões...');
    
    // Botões de visualização
    $('.btn-visualizar').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data('id');
        var titulo = $(this).data('titulo');
        console.log('Clique em visualizar:', id, titulo);
        
        // Atualizar título do modal
        $('#modalVisualizarVagaLabel').text('Detalhes da Vaga: ' + titulo);
        
        // Mostrar indicador de carregamento
        $('#vagaDetalhes').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Carregando...</span>
                </div>
                <p class="mt-2">Carregando detalhes da vaga...</p>
            </div>
        `);
        
        // Abrir modal
        $('#modalVisualizarVaga').modal('show');
        
        // Fazer requisição AJAX para obter detalhes da vaga
        $.ajax({
            url: SITE_URL + '/admin/processar_gestao_vagas.php',
            type: 'POST',
            dataType: 'json',
            data: {
                acao: 'obter_detalhes',
                vaga_id: id
            },
            success: function(response) {
                console.log('Resposta recebida:', response);
                
                if (response.success && response.vaga) {
                    const vaga = response.vaga;
                    
                    // Construir HTML com detalhes da vaga
                    var html = `
                        <div class="row">
                            <div class="col-md-12">
                                <h4>${vaga.titulo}</h4>
                                <p class="text-muted">
                                    <strong>Status:</strong> 
                                    <span class="badge badge-${getStatusClass(vaga.status)}">${vaga.status}</span>
                                </p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong>Empresa:</strong> ${vaga.empresa_nome || vaga.empresa_externa || 'Não informada'}</p>
                                <p><strong>Tipo de Vaga:</strong> ${vaga.tipo_vaga === 'interna' ? 'Interna' : 'Externa'}</p>
                                <p><strong>Localização:</strong> ${vaga.cidade ? vaga.cidade + ' - ' + vaga.estado : 'Não informada'}</p>
                                <p><strong>Tipo de Contrato:</strong> ${vaga.tipo_contrato || 'Não informado'}</p>
                                <p><strong>Regime de Trabalho:</strong> ${vaga.regime_trabalho || 'Não informado'}</p>
                                <p><strong>Nível de Experiência:</strong> ${vaga.nivel_experiencia || 'Não informado'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Data de Publicação:</strong> ${formatDate(vaga.data_publicacao)}</p>
                                <p><strong>Última Atualização:</strong> ${formatDate(vaga.data_atualizacao)}</p>
                                <p><strong>Palavras-chave:</strong> ${vaga.palavras_chave || 'Não informadas'}</p>
                                <p><strong>Salário:</strong> ${vaga.mostrar_salario ? formatSalario(vaga.salario_min, vaga.salario_max) : 'Não informado'}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Descrição</h5>
                                <div class="p-3 bg-light rounded">${vaga.descricao || 'Não informada'}</div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Requisitos</h5>
                                <div class="p-3 bg-light rounded">${vaga.requisitos || 'Não informados'}</div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Responsabilidades</h5>
                                <div class="p-3 bg-light rounded">${vaga.responsabilidades || 'Não informadas'}</div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Benefícios</h5>
                                <div class="p-3 bg-light rounded">${vaga.beneficios || 'Não informados'}</div>
                            </div>
                        </div>
                    `;
                    
                    // Atualizar conteúdo do modal
                    $('#vagaDetalhes').html(html);
                } else {
                    // Exibir mensagem de erro
                    $('#vagaDetalhes').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> 
                            ${response.message || 'Erro ao carregar detalhes da vaga.'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
                
                // Exibir mensagem de erro
                $('#vagaDetalhes').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        Erro ao carregar detalhes da vaga. Tente novamente mais tarde.
                    </div>
                `);
            }
        });
        
        return false;
    });
    
    // Botões de edição
    $('.btn-editar').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data('id');
        var titulo = $(this).data('titulo');
        console.log('Clique em editar:', id, titulo);
        
        // Atualizar título do modal
        $('#modalEditarVagaLabel').text('Editar Vaga: ' + titulo);
        
        // Mostrar indicador de carregamento e ocultar formulário
        $('#editarVagaForm').hide();
        $('#editarVagaLoading').show();
        
        // Abrir modal
        $('#modalEditarVaga').modal('show');
        
        // Fazer requisição AJAX para obter detalhes da vaga
        $.ajax({
            url: SITE_URL + '/admin/processar_gestao_vagas.php',
            type: 'POST',
            dataType: 'json',
            data: {
                acao: 'obter_detalhes',
                vaga_id: id
            },
            success: function(response) {
                console.log('Resposta recebida para edição:', response);
                
                if (response.success && response.vaga) {
                    const vaga = response.vaga;
                    
                    // Preencher campos do formulário de edição
                    $('#editar_vaga_id').val(vaga.id);
                    $('#editar_titulo').val(vaga.titulo);
                    $('#editar_tipo_vaga').val(vaga.tipo_vaga);
                    $('#editar_cidade').val(vaga.cidade);
                    $('#editar_estado').val(vaga.estado);
                    $('#editar_palavras_chave').val(vaga.palavras_chave);
                    $('#editar_descricao').val(vaga.descricao);
                    $('#editar_requisitos').val(vaga.requisitos);
                    $('#editar_responsabilidades').val(vaga.responsabilidades);
                    $('#editar_beneficios').val(vaga.beneficios);
                    $('#editar_status').val(vaga.status);
                    
                    // Campos de referência
                    if (vaga.tipo_contrato_id) {
                        $('#editar_tipo_contrato_id').val(parseInt(vaga.tipo_contrato_id));
                    }
                    
                    if (vaga.regime_trabalho_id) {
                        $('#editar_regime_trabalho_id').val(parseInt(vaga.regime_trabalho_id));
                    }
                    
                    if (vaga.nivel_experiencia_id) {
                        $('#editar_nivel_experiencia_id').val(parseInt(vaga.nivel_experiencia_id));
                    }
                    
                    // Campos de empresa
                    if (vaga.tipo_vaga === 'interna' && vaga.empresa_id) {
                        $('#editar_empresa_id').val(vaga.empresa_id);
                    } else if (vaga.tipo_vaga === 'externa' && vaga.empresa_externa) {
                        $('#editar_empresa_externa').val(vaga.empresa_externa);
                    }
                    
                    // Campos de salário
                    if (vaga.salario_min) {
                        $('#editar_salario_min').val(vaga.salario_min);
                    }
                    
                    if (vaga.salario_max) {
                        $('#editar_salario_max').val(vaga.salario_max);
                    }
                    
                    $('#editar_mostrar_salario').prop('checked', vaga.mostrar_salario == 1);
                    
                    // Alternar campos de empresa com base no tipo de vaga
                    toggleEditarEmpresaFields();
                    
                    // Ocultar indicador de carregamento e mostrar formulário
                    $('#editarVagaLoading').hide();
                    $('#editarVagaForm').show();
                } else {
                    // Exibir mensagem de erro
                    $('#editarVagaLoading').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> 
                            ${response.message || 'Erro ao carregar detalhes da vaga.'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
                
                // Exibir mensagem de erro
                $('#editarVagaLoading').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        Erro ao carregar detalhes da vaga. Tente novamente mais tarde.
                    </div>
                `);
            }
        });
        
        return false;
    });
    
    // Botões de exclusão
    $('.btn-excluir').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var id = $(this).data('id');
        var titulo = $(this).data('titulo');
        console.log('Clique em excluir:', id, titulo);
        
        // Atualizar modal de confirmação
        $('#excluir_vaga_id').val(id);
        $('#excluirVagaTitulo').text(titulo);
        
        // Abrir modal de confirmação
        $('#modalExcluirVaga').modal('show');
        
        return false;
    });
    
    console.log('Eventos dos botões inicializados com sucesso.');
}

// Funções auxiliares
function getStatusClass(status) {
    switch (status) {
        case 'ativa':
            return 'success';
        case 'pendente':
            return 'warning';
        case 'inativa':
            return 'danger';
        case 'encerrada':
            return 'secondary';
        default:
            return 'info';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'Não informada';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
}

function formatSalario(min, max) {
    if (!min && !max) return 'Não informado';
    
    const formatValue = (value) => {
        return parseFloat(value).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    };
    
    if (min && max) {
        return `${formatValue(min)} a ${formatValue(max)}`;
    } else if (min) {
        return `A partir de ${formatValue(min)}`;
    } else if (max) {
        return `Até ${formatValue(max)}`;
    }
}

// Inicializar quando o documento estiver pronto
$(document).ready(function() {
    console.log('Documento pronto. Inicializando script de correção...');
    
    // Verificar se a tabela DataTable existe
    if ($.fn.DataTable && $('#vagasTable').length > 0) {
        console.log('DataTable encontrado. Configurando...');
        
        // Destruir DataTable existente se já estiver inicializado
        if ($.fn.DataTable.isDataTable('#vagasTable')) {
            $('#vagasTable').DataTable().destroy();
        }
        
        // Inicializar DataTable com configurações corretas
        $('#vagasTable').DataTable({
            "language": {
                "url": "/open2w/assets/js/pt-BR.json"
            },
            "order": [[6, "desc"]], // Ordenar por data de publicação (decrescente)
            "drawCallback": function() {
                // Reinicializar eventos dos botões após cada redesenho da tabela
                initButtonEvents();
            },
            "initComplete": function() {
                // Inicializar eventos dos botões após a inicialização completa
                initButtonEvents();
            }
        });
    } else {
        console.log('DataTable não encontrado. Inicializando eventos diretamente...');
        initButtonEvents();
    }
    
    // Garantir que os eventos de alternância de campos funcionem
    $('#editar_tipo_vaga').on('change', function() {
        toggleEditarEmpresaFields();
    });
    
    $('#adicionar_tipo_vaga').on('change', function() {
        toggleAdicionarEmpresaFields();
    });
    
    console.log('Script de correção inicializado com sucesso.');
});
