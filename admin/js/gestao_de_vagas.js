/**
 * Funções para gestão de vagas no OpenToJob
 * Conectando talentos prontos a oportunidades imediatas
 */

// Função para visualizar vaga
function visualizarVaga(id, titulo) {
    console.log('Visualizando vaga:', id, titulo);
    
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
        url: SITE_URL + '/admin/processar_gestao_vagas.php', // Caminho absoluto para o processador
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
                
                // Atualizar botão de editar com o ID e título da vaga
                $('#btnEditarVagaDetalhe').attr('data-id', vaga.id);
                $('#btnEditarVagaDetalhe').attr('data-titulo', vaga.titulo);
                
                // Construir HTML com os detalhes da vaga
                let html = `
                    <div class="vaga-info">
                        <h4>${vaga.titulo}</h4>
                        <p class="text-muted">ID: ${vaga.id}</p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Empresa:</strong> ${vaga.tipo_vaga === 'externa' ? 
                                    `<span class="badge badge-info">Externa</span> ${vaga.empresa_externa || 'Não informada'}` : 
                                    vaga.empresa_nome || 'Não informada'}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong> <span class="badge badge-${getStatusClass(vaga.status)}">${vaga.status}</span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Tipo de Contrato:</strong> ${vaga.tipo_contrato_nome || vaga.tipo_contrato || 'Não informado'}
                            </div>
                            <div class="col-md-6">
                                <strong>Regime de Trabalho:</strong> ${vaga.regime_trabalho_nome || vaga.regime_trabalho || 'Não informado'}
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Nível de Experiência:</strong> ${vaga.nivel_experiencia_nome || vaga.nivel_experiencia || 'Não informado'}
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
                                <strong>Salário:</strong> ${vaga.mostrar_salario == 1 ? formatarSalario(vaga.salario_min, vaga.salario_max) : 'Não divulgado'}
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <h5>Descrição</h5>
                            <p>${formatTextWithLineBreaks(vaga.descricao) || 'Não informada'}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Requisitos</h5>
                            <p>${formatTextWithLineBreaks(vaga.requisitos) || 'Não informados'}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Responsabilidades</h5>
                            <p>${formatTextWithLineBreaks(vaga.responsabilidades) || 'Não informadas'}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Benefícios</h5>
                            <p>${formatTextWithLineBreaks(vaga.beneficios) || 'Não informados'}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Palavras-chave</h5>
                            <p>${vaga.palavras_chave || 'Não informadas'}</p>
                        </div>
                    </div>
                `;
                
                // Atualizar conteúdo do modal
                $('#vagaDetalhes').html(html);
            } else {
                $('#vagaDetalhes').html(`
                    <div class="alert alert-danger">
                        Erro ao carregar detalhes da vaga: ${response.message || 'Erro desconhecido'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr, status, error);
            $('#vagaDetalhes').html(`
                <div class="alert alert-danger">
                    Erro ao carregar detalhes da vaga: ${error}
                </div>
            `);
        }
    });
}

// Função para editar vaga
function editarVaga(id, titulo) {
    console.log('Editando vaga:', id, titulo);
    
    // Atualizar título do modal
    $('#modalEditarVagaLabel').text('Editar Vaga: ' + titulo);
    
    // Mostrar indicador de carregamento
    $('#editarVagaForm').hide();
    $('#editarVagaLoading').show();
    
    // Abrir modal
    $('#modalEditarVaga').modal('show');
    
    // Fazer requisição AJAX para obter detalhes da vaga
    $.ajax({
        url: SITE_URL + '/admin/processar_gestao_vagas.php', // Caminho absoluto para o processador
        type: 'POST',
        dataType: 'json',
        data: {
            acao: 'obter_detalhes',
            vaga_id: id
        },
        success: function(response) {
            console.log('Resposta recebida para edição:', response);
            
            try {
                if (response && response.success && response.vaga) {
                    const vaga = response.vaga;
                    
                    // Preencher campos do formulário de edição
                    $('#editar_vaga_id').val(vaga.id);
                    $('#editar_titulo').val(vaga.titulo || '');
                    $('#editar_descricao').val(vaga.descricao || '');
                    $('#editar_tipo_vaga').val(vaga.tipo_vaga || 'interna');
                    
                    // Alternar campos de empresa com base no tipo de vaga
                    toggleEditarEmpresaFields();
                    
                    if (vaga.tipo_vaga === 'interna') {
                        $('#editar_empresa_id').val(vaga.empresa_id || '');
                    } else {
                        $('#editar_empresa_externa').val(vaga.empresa_externa || '');
                    }
                    
                    $('#editar_requisitos').val(vaga.requisitos || '');
                    $('#editar_responsabilidades').val(vaga.responsabilidades || '');
                    $('#editar_beneficios').val(vaga.beneficios || '');
                    
                    // Usar os IDs das tabelas de referência
                    console.log('Valores de ID recebidos:', {
                        tipo_contrato_id: vaga.tipo_contrato_id,
                        regime_trabalho_id: vaga.regime_trabalho_id,
                        nivel_experiencia_id: vaga.nivel_experiencia_id
                    });
                    
                    // Garantir que os valores sejam números para compatibilidade com o select
                    if (vaga.tipo_contrato_id) {
                        var tipoId = parseInt(vaga.tipo_contrato_id);
                        console.log('Definindo tipo_contrato_id:', tipoId);
                        $('#editar_tipo_contrato_id').val(tipoId);
                    }
                    
                    if (vaga.regime_trabalho_id) {
                        var regimeId = parseInt(vaga.regime_trabalho_id);
                        console.log('Definindo regime_trabalho_id:', regimeId);
                        $('#editar_regime_trabalho_id').val(regimeId);
                    }
                    
                    if (vaga.nivel_experiencia_id) {
                        var nivelId = parseInt(vaga.nivel_experiencia_id);
                        console.log('Definindo nivel_experiencia_id:', nivelId);
                        $('#editar_nivel_experiencia_id').val(nivelId);
                    }
                    
                    // Forçar atualização dos selects após um pequeno delay
                    setTimeout(function() {
                        if (vaga.tipo_contrato_id) $('#editar_tipo_contrato_id').trigger('change');
                        if (vaga.regime_trabalho_id) $('#editar_regime_trabalho_id').trigger('change');
                        if (vaga.nivel_experiencia_id) $('#editar_nivel_experiencia_id').trigger('change');
                    }, 100);
                    
                    $('#editar_palavras_chave').val(vaga.palavras_chave || '');
                    $('#editar_cidade').val(vaga.cidade || '');
                    $('#editar_estado').val(vaga.estado || '');
                    $('#editar_salario_min').val(vaga.salario_min || '');
                    $('#editar_salario_max').val(vaga.salario_max || '');
                    $('#editar_mostrar_salario').prop('checked', vaga.mostrar_salario == 1);
                    $('#editar_status').val(vaga.status || 'pendente');
                    
                    // Esconder indicador de carregamento
                    $('#editarVagaLoading').hide();
                    $('#editarVagaForm').show();
                } else {
                    console.error('Resposta inválida:', response);
                    alert('Erro ao carregar detalhes da vaga: ' + (response.message || 'Resposta inválida do servidor'));
                    $('#modalEditarVaga').modal('hide');
                }
            } catch (e) {
                console.error('Erro ao processar resposta:', e);
                alert('Erro ao processar resposta: ' + e.message);
                $('#modalEditarVaga').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr, status, error);
            alert('Erro ao carregar detalhes da vaga: ' + error);
            $('#modalEditarVaga').modal('hide');
        }
    });
}

// Função para confirmar exclusão de vaga
function confirmarExclusao(id, titulo) {
    console.log('Confirmando exclusão da vaga:', id, titulo);
    
    // Atualizar modal de confirmação
    $('#vaga_titulo_confirmacao').text(titulo);
    $('#vaga_id_confirmacao').val(id);
    
    // Abrir modal de confirmação
    $('#modalConfirmacao').modal('show');
}

// Função para formatar texto com quebras de linha
function formatTextWithLineBreaks(text) {
    if (!text) return '';
    return text.replace(/\n/g, '<br>');
}

// Função para formatar data
function formatarData(dataString) {
    if (!dataString) return 'Não informada';
    
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR');
}

// Função para formatar salário
function formatarSalario(salarioMin, salarioMax) {
    if (!salarioMin && !salarioMax) return 'Não informado';
    
    if (salarioMin && salarioMax) {
        return `R$ ${parseFloat(salarioMin).toLocaleString('pt-BR')} - R$ ${parseFloat(salarioMax).toLocaleString('pt-BR')}`;
    } else if (salarioMin) {
        return `A partir de R$ ${parseFloat(salarioMin).toLocaleString('pt-BR')}`;
    } else if (salarioMax) {
        return `Até R$ ${parseFloat(salarioMax).toLocaleString('pt-BR')}`;
    }
    
    return 'Não informado';
}

// Função para obter a localização formatada
function getLocalizacao(vaga) {
    if (!vaga.cidade && !vaga.estado) return 'Não informada';
    
    let localizacao = [];
    if (vaga.cidade) localizacao.push(vaga.cidade);
    if (vaga.estado) localizacao.push(vaga.estado);
    
    return localizacao.join('/');
}

// Função para editar vaga a partir do modal de visualização
function editarVagaDoModal() {
    const id = $('#btnEditarVagaDetalhe').attr('data-id');
    const titulo = $('#btnEditarVagaDetalhe').attr('data-titulo');
    
    $('#modalVisualizarVaga').modal('hide');
    setTimeout(function() {
        editarVaga(id, titulo);
    }, 500);
}

// Função para obter a classe de status
function getStatusClass(status) {
    switch (status) {
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

// Inicializar quando o documento estiver pronto
$(document).ready(function() {
    console.log('Inicializando eventos para botões de ação...');
    console.log('SITE_URL:', SITE_URL);
    
    // Verificar se os botões existem na página
    console.log('Botões de visualizar:', $('.btn-visualizar').length);
    console.log('Botões de editar:', $('.btn-editar').length);
    console.log('Botões de excluir:', $('.btn-excluir').length);
    
    // Usar delegação de eventos para garantir que os eventos funcionem mesmo após o DataTable ser inicializado
    $(document).on('click', '.btn-visualizar', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const titulo = $(this).data('titulo');
        console.log('Clique em visualizar:', id, titulo);
        visualizarVaga(id, titulo);
        return false;
    });
    
    // Botões de edição
    $(document).on('click', '.btn-editar', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const titulo = $(this).data('titulo');
        console.log('Clique em editar:', id, titulo);
        editarVaga(id, titulo);
        return false;
    });
    
    // Botões de exclusão
    $(document).on('click', '.btn-excluir', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const titulo = $(this).data('titulo');
        console.log('Clique em excluir:', id, titulo);
        confirmarExclusao(id, titulo);
        return false;
    });
    
    console.log('Inicialização concluída');
});
