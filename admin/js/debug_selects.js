/**
 * Script de depuração para os selects de vagas no OpenToJob
 */

// Sobrescrever a função editarVaga para adicionar depuração
const originalEditarVaga = window.editarVaga;

window.editarVaga = function(id, titulo) {
    console.log('Depuração: Editando vaga:', id, titulo);
    
    // Atualizar título do modal
    $('#modalEditarVagaLabel').text('Editar Vaga: ' + titulo);
    
    // Mostrar indicador de carregamento
    $('#editarVagaForm').hide();
    $('#editarVagaLoading').show();
    
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
            console.log('Depuração: Resposta completa recebida:', response);
            
            try {
                if (response && response.success && response.vaga) {
                    const vaga = response.vaga;
                    
                    // Depurar valores de ID das tabelas de referência
                    console.log('Depuração: tipo_contrato_id =', vaga.tipo_contrato_id);
                    console.log('Depuração: regime_trabalho_id =', vaga.regime_trabalho_id);
                    console.log('Depuração: nivel_experiencia_id =', vaga.nivel_experiencia_id);
                    
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
                    
                    // Depurar antes de definir os valores dos selects
                    console.log('Depuração: Antes de definir valores dos selects:');
                    console.log('Depuração: #editar_tipo_contrato options:', $('#editar_tipo_contrato option').length);
                    console.log('Depuração: #editar_regime_trabalho options:', $('#editar_regime_trabalho option').length);
                    console.log('Depuração: #editar_nivel_experiencia options:', $('#editar_nivel_experiencia option').length);
                    
                    // Usar os IDs das tabelas de referência
                    $('#editar_tipo_contrato').val(vaga.tipo_contrato_id || '');
                    $('#editar_regime_trabalho').val(vaga.regime_trabalho_id || '');
                    $('#editar_nivel_experiencia').val(vaga.nivel_experiencia_id || '');
                    
                    // Depurar depois de definir os valores dos selects
                    console.log('Depuração: Depois de definir valores dos selects:');
                    console.log('Depuração: #editar_tipo_contrato valor selecionado:', $('#editar_tipo_contrato').val());
                    console.log('Depuração: #editar_regime_trabalho valor selecionado:', $('#editar_regime_trabalho').val());
                    console.log('Depuração: #editar_nivel_experiencia valor selecionado:', $('#editar_nivel_experiencia').val());
                    
                    // Verificar se os valores foram definidos corretamente
                    if ($('#editar_tipo_contrato').val() != vaga.tipo_contrato_id) {
                        console.error('Depuração: Falha ao definir tipo_contrato_id. Esperado:', vaga.tipo_contrato_id, 'Atual:', $('#editar_tipo_contrato').val());
                        
                        // Tentar forçar a seleção
                        setTimeout(function() {
                            $('#editar_tipo_contrato').val(vaga.tipo_contrato_id).trigger('change');
                            console.log('Depuração: Tentativa de forçar seleção de tipo_contrato_id:', $('#editar_tipo_contrato').val());
                        }, 100);
                    }
                    
                    if ($('#editar_regime_trabalho').val() != vaga.regime_trabalho_id) {
                        console.error('Depuração: Falha ao definir regime_trabalho_id. Esperado:', vaga.regime_trabalho_id, 'Atual:', $('#editar_regime_trabalho').val());
                        
                        // Tentar forçar a seleção
                        setTimeout(function() {
                            $('#editar_regime_trabalho').val(vaga.regime_trabalho_id).trigger('change');
                            console.log('Depuração: Tentativa de forçar seleção de regime_trabalho_id:', $('#editar_regime_trabalho').val());
                        }, 100);
                    }
                    
                    if ($('#editar_nivel_experiencia').val() != vaga.nivel_experiencia_id) {
                        console.error('Depuração: Falha ao definir nivel_experiencia_id. Esperado:', vaga.nivel_experiencia_id, 'Atual:', $('#editar_nivel_experiencia').val());
                        
                        // Tentar forçar a seleção
                        setTimeout(function() {
                            $('#editar_nivel_experiencia').val(vaga.nivel_experiencia_id).trigger('change');
                            console.log('Depuração: Tentativa de forçar seleção de nivel_experiencia_id:', $('#editar_nivel_experiencia').val());
                        }, 100);
                    }
                    
                    $('#editar_palavras_chave').val(vaga.palavras_chave || '');
                    $('#editar_cidade').val(vaga.cidade || '');
                    $('#editar_estado').val(vaga.estado || '');
                    $('#editar_salario_min').val(vaga.salario_min || '');
                    $('#editar_salario_max').val(vaga.salario_max || '');
                    $('#editar_mostrar_salario').prop('checked', vaga.mostrar_salario == 1);
                    $('#editar_status').val(vaga.status || 'pendente');
                    
                    // Abrir modal
                    $('#modalEditarVaga').modal('show');
                    
                    // Esconder indicador de carregamento
                    $('#editarVagaLoading').hide();
                    $('#editarVagaForm').show();
                } else {
                    console.error('Depuração: Resposta inválida:', response);
                    alert('Erro ao carregar detalhes da vaga: ' + (response.message || 'Resposta inválida do servidor'));
                }
            } catch (e) {
                console.error('Depuração: Erro ao processar resposta:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Depuração: Erro na requisição AJAX:', xhr, status, error);
            alert('Erro ao carregar detalhes da vaga: ' + error);
        },
        complete: function() {
            // Esconder indicador de carregamento em caso de erro
            $('#editarVagaLoading').hide();
            $('#editarVagaForm').show();
        }
    });
};
