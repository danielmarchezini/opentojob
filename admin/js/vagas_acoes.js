/**
 * Funções para gerenciamento de vagas no OpenToJob
 * Conectando talentos prontos a oportunidades imediatas
 */

// Função para visualizar vaga
function visualizarVaga(id, titulo) {
    console.log('Visualizando vaga:', id, titulo);
    
    // Atualizar título do modal
    jQuery('#modalVisualizarVagaLabel').text('Detalhes da Vaga: ' + titulo);
    
    // Mostrar indicador de carregamento
    jQuery('#vagaDetalhes').html(`
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando detalhes da vaga...</p>
        </div>
    `);
    
    // Abrir modal
    jQuery('#modalVisualizarVaga').modal('show');
    
    // Fazer requisição AJAX para obter detalhes da vaga
    jQuery.ajax({
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
                jQuery('#btnEditarVagaDetalhe').attr('data-id', vaga.id);
                jQuery('#btnEditarVagaDetalhe').attr('data-titulo', vaga.titulo);
                
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
                jQuery('#vagaDetalhes').html(html);
            } else {
                // Exibir mensagem de erro
                jQuery('#vagaDetalhes').html(`
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
            jQuery('#vagaDetalhes').html(`
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
    console.log('Editando vaga:', id, titulo);
    
    // Atualizar título do modal
    jQuery('#modalEditarVagaLabel').text('Editar Vaga: ' + titulo);
    
    // Mostrar indicador de carregamento
    jQuery('#editarVagaForm').hide();
    jQuery('#editarVagaLoading').show();
    
    // Fazer requisição AJAX para obter detalhes da vaga
    jQuery.ajax({
        url: SITE_URL + '/admin/processar_vaga_admin.php',
        type: 'POST',
        dataType: 'json',
        data: {
            acao: 'obter_detalhes',
            vaga_id: id
        },
        success: function(response) {
            console.log('Resposta recebida:', response);
            
            try {
                if (response && response.success && response.vaga) {
                    const vaga = response.vaga;
                    
                    // Preencher campos do formulário de edição
                    jQuery('#editar_vaga_id').val(vaga.id);
                    jQuery('#editar_titulo').val(vaga.titulo || '');
                    jQuery('#editar_descricao').val(vaga.descricao || '');
                    jQuery('#editar_tipo_vaga').val(vaga.tipo_vaga || 'interna');
                    
                    // Alternar campos de empresa com base no tipo de vaga
                    toggleEditarEmpresaFields();
                    
                    if (vaga.tipo_vaga === 'interna') {
                        jQuery('#editar_empresa_id').val(vaga.empresa_id || '');
                    } else {
                        jQuery('#editar_empresa_externa').val(vaga.empresa_externa || '');
                    }
                    
                    jQuery('#editar_requisitos').val(vaga.requisitos || '');
                    jQuery('#editar_responsabilidades').val(vaga.responsabilidades || '');
                    jQuery('#editar_beneficios').val(vaga.beneficios || '');
                    // Usar os IDs das tabelas de referência em vez dos nomes
                    // Garantir que os valores sejam números para compatibilidade com o select
                    if (vaga.tipo_contrato_id) {
                        jQuery('#editar_tipo_contrato').val(parseInt(vaga.tipo_contrato_id));
                        console.log('Definindo tipo_contrato_id:', parseInt(vaga.tipo_contrato_id));
                    }
                    
                    if (vaga.regime_trabalho_id) {
                        jQuery('#editar_regime_trabalho').val(parseInt(vaga.regime_trabalho_id));
                        console.log('Definindo regime_trabalho_id:', parseInt(vaga.regime_trabalho_id));
                    }
                    
                    if (vaga.nivel_experiencia_id) {
                        jQuery('#editar_nivel_experiencia').val(parseInt(vaga.nivel_experiencia_id));
                        console.log('Definindo nivel_experiencia_id:', parseInt(vaga.nivel_experiencia_id));
                    }
                    
                    // Forçar atualização dos selects após um pequeno delay
                    setTimeout(function() {
                        if (vaga.tipo_contrato_id) jQuery('#editar_tipo_contrato').trigger('change');
                        if (vaga.regime_trabalho_id) jQuery('#editar_regime_trabalho').trigger('change');
                        if (vaga.nivel_experiencia_id) jQuery('#editar_nivel_experiencia').trigger('change');
                    }, 100);
                    jQuery('#editar_palavras_chave').val(vaga.palavras_chave || '');
                    jQuery('#editar_cidade').val(vaga.cidade || '');
                    jQuery('#editar_estado').val(vaga.estado || '');
                    jQuery('#editar_salario_min').val(vaga.salario_min || '');
                    jQuery('#editar_salario_max').val(vaga.salario_max || '');
                    jQuery('#editar_mostrar_salario').prop('checked', vaga.mostrar_salario == 1);
                    jQuery('#editar_status').val(vaga.status || 'pendente');
                    
                    // Abrir modal
                    jQuery('#modalEditarVaga').modal('show');
                } else {
                    alert('Erro ao obter detalhes da vaga: ' + (response && response.message ? response.message : 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Erro ao processar resposta:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr, status, error);
            
            // Tentar interpretar a resposta como JSON
            let errorMessage = error;
            try {
                if (xhr.responseText) {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse && errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                }
            } catch (e) {
                console.error('Não foi possível interpretar a resposta como JSON:', e);
            }
            
            alert('Erro ao obter detalhes da vaga: ' + errorMessage);
        },
        complete: function() {
            // Esconder indicador de carregamento e mostrar formulário
            jQuery('#editarVagaLoading').hide();
            jQuery('#editarVagaForm').show();
        }
    });
}

// Função para confirmar exclusão de vaga
function confirmarExclusao(id, titulo) {
    console.log('Confirmando exclusão da vaga:', id, titulo);
    
    if (confirm(`Tem certeza que deseja excluir a vaga "${titulo}"? Esta ação não pode ser desfeita.`)) {
        // Enviar requisição para excluir vaga
        jQuery.ajax({
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
    const id = jQuery('#btnEditarVagaDetalhe').attr('data-id');
    const titulo = jQuery('#btnEditarVagaDetalhe').attr('data-titulo');
    
    jQuery('#modalVisualizarVaga').modal('hide');
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando eventos para botões de ação...');
    
    // Verificar se jQuery está disponível
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery está disponível, usando jQuery para eventos');
        
        // Botões de visualização
        jQuery('.btn-visualizar').on('click', function() {
            const id = jQuery(this).data('id');
            const titulo = jQuery(this).data('titulo');
            console.log('Clique em visualizar:', id, titulo);
            visualizarVaga(id, titulo);
        });
        
        // Botões de edição
        jQuery('.btn-editar').on('click', function() {
            const id = jQuery(this).data('id');
            const titulo = jQuery(this).data('titulo');
            console.log('Clique em editar:', id, titulo);
            editarVaga(id, titulo);
        });
        
        // Botões de exclusão
        jQuery('.btn-excluir').on('click', function() {
            const id = jQuery(this).data('id');
            const titulo = jQuery(this).data('titulo');
            console.log('Clique em excluir:', id, titulo);
            confirmarExclusao(id, titulo);
        });
    } else {
        console.log('jQuery não está disponível, usando JavaScript puro para eventos');
        
        // Adicionar eventos usando JavaScript puro
        document.querySelectorAll('.btn-visualizar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const titulo = this.getAttribute('data-titulo');
                console.log('Clique em visualizar:', id, titulo);
                visualizarVaga(id, titulo);
            });
        });
        
        document.querySelectorAll('.btn-editar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const titulo = this.getAttribute('data-titulo');
                console.log('Clique em editar:', id, titulo);
                editarVaga(id, titulo);
            });
        });
        
        document.querySelectorAll('.btn-excluir').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const titulo = this.getAttribute('data-titulo');
                console.log('Clique em excluir:', id, titulo);
                confirmarExclusao(id, titulo);
            });
        });
    }
    
    console.log('Inicialização concluída');
});
