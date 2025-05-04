/**
 * Funções auxiliares para a página de gerenciamento de vagas
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

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

// Função para alternar campos de empresa interna/externa
function toggleEmpresaFields() {
    const tipoVaga = document.getElementById('tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('empresa_interna_div');
    const empresaExternaDiv = document.getElementById('empresa_externa_div');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
    }
}

// Inicializar DataTables
$(document).ready(function() {
    // Inicializar DataTables com configurações em português
    $('#vagasTable').DataTable({
        "language": {
            "url": "/open2w/assets/js/pt-BR.json"
        },
        "responsive": true,
        "order": [[0, "desc"]], // Ordenar por ID (primeira coluna) em ordem decrescente
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Desativar ordenação na coluna de ações
        ]
    });
    
    // Configurar evento para o botão de editar no modal de visualização
    $('#btnEditarVagaDetalhe').on('click', function() {
        const id = $(this).attr('data-id');
        const titulo = $(this).attr('data-titulo');
        
        $('#modalVisualizarVaga').modal('hide');
        setTimeout(function() {
            editarVaga(id, titulo);
        }, 500);
    });
    
    // Inicializar campos de empresa ao carregar a página
    if (document.getElementById('tipo_vaga')) {
        toggleEmpresaFields();
    }
});

// Função para visualizar vaga
function visualizarVaga(id, titulo) {
    // Mostrar modal com loading
    $('#modalVisualizarVaga').modal('show');
    $('#modalVisualizarVagaLabel').text('Detalhes da Vaga: ' + titulo);
    
    // Mostrar indicador de carregamento
    $('#vagaDetalhes').html(`
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando detalhes da vaga...</p>
        </div>
    `);
    
    // Fazer requisição AJAX para obter detalhes da vaga
    $.ajax({
        url: SITE_URL + '/admin/processar_vaga_admin.php',
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
                                    `<span class="badge bg-info">Externa</span> ${vaga.empresa_externa || 'Não informada'}` : 
                                    vaga.empresa_nome || 'Não informada'}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong> <span class="badge bg-${getStatusClass(vaga.status)}">${vaga.status}</span>
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
                                <strong>Salário:</strong> ${vaga.mostrar_salario == 1 ? formatarSalario(vaga.salario_min, vaga.salario_max) : 'Não divulgado'}
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <h5>Descrição</h5>
                            <div class="p-3 bg-light rounded">
                                ${vaga.descricao ? formatTextWithLineBreaks(vaga.descricao) : 'Não informada'}
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Requisitos</h5>
                            <div class="p-3 bg-light rounded">
                                ${vaga.requisitos ? formatTextWithLineBreaks(vaga.requisitos) : 'Não informados'}
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h5>Benefícios</h5>
                            <div class="p-3 bg-light rounded">
                                ${vaga.beneficios ? formatTextWithLineBreaks(vaga.beneficios) : 'Não informados'}
                            </div>
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
                // Exibir mensagem de erro
                $('#vagaDetalhes').html(`
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Erro!</h5>
                        Não foi possível carregar os detalhes da vaga: ${response.message || 'Erro desconhecido'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr, status, error);
            
            // Exibir mensagem de erro
            $('#vagaDetalhes').html(`
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erro!</h5>
                    Ocorreu um erro ao carregar os detalhes da vaga: ${error}
                </div>
            `);
        }
    });
}

// Função para editar vaga
function editarVaga(id, titulo) {
    // Limpar formulário
    $('#formEditarVaga')[0].reset();
    
    // Atualizar título do modal
    $('#modalEditarVagaLabel').text('Editar Vaga: ' + titulo);
    
    // Obter detalhes da vaga via AJAX
    $.ajax({
        url: SITE_URL + '/admin/processar_vaga_admin.php',
        type: 'POST',
        dataType: 'json',
        data: {
            acao: 'obter_detalhes',
            vaga_id: id
        },
        success: function(response) {
            console.log('Resposta recebida:', response);
            
            if (response.success && response.vaga) {
                // Preencher formulário com os dados da vaga
                const vaga = response.vaga;
                
                $('#vaga_id').val(vaga.id);
                $('#editar_titulo').val(vaga.titulo);
                
                // Configurar tipo de vaga e campos relacionados
                if (vaga.tipo_vaga === 'externa') {
                    $('#editar_tipo_vaga').val('externa');
                    $('#editar_empresa_externa').val(vaga.empresa_externa);
                } else {
                    $('#editar_tipo_vaga').val('interna');
                    $('#editar_empresa_id').val(vaga.empresa_id);
                }
                
                // Preencher outros campos
                $('#editar_descricao').val(vaga.descricao);
                $('#editar_requisitos').val(vaga.requisitos);
                $('#editar_beneficios').val(vaga.beneficios);
                $('#editar_tipo_contrato').val(vaga.tipo_contrato);
                $('#editar_regime_trabalho').val(vaga.regime_trabalho);
                $('#editar_nivel_experiencia').val(vaga.nivel_experiencia);
                $('#editar_palavras_chave').val(vaga.palavras_chave);
                $('#editar_cidade').val(vaga.cidade);
                $('#editar_estado').val(vaga.estado);
                $('#editar_salario_min').val(vaga.salario_min);
                $('#editar_salario_max').val(vaga.salario_max);
                $('#editar_mostrar_salario').prop('checked', vaga.mostrar_salario == 1);
                $('#editar_status').val(vaga.status);
                
                // Abrir modal
                $('#modalEditarVaga').modal('show');
            } else {
                alert('Erro ao obter detalhes da vaga: ' + (response.message || 'Erro desconhecido'));
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
    if (confirm(`Tem certeza que deseja excluir a vaga "${titulo}"? Esta ação não pode ser desfeita.`)) {
        // Enviar requisição para excluir vaga
        $.ajax({
            url: SITE_URL + '/admin/processar_vaga_admin.php',
            type: 'POST',
            dataType: 'json',
            data: {
                acao: 'excluir',
                vaga_id: id
            },
            success: function(response) {
                console.log('Resposta recebida:', response);
                
                if (response.success) {
                    alert('Vaga excluída com sucesso!');
                    // Recarregar a página para atualizar a lista
                    window.location.reload();
                } else {
                    alert('Erro ao excluir vaga: ' + (response.message || 'Erro desconhecido'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', xhr, status, error);
                alert('Erro ao excluir vaga: ' + error);
            }
        });
    }
}
