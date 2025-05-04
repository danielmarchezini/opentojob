/**
 * Funções para gerenciamento de vagas no OpenToJob
 * Conectando talentos prontos a oportunidades imediatas
 */

// Função para editar vaga
function editarVaga(id, titulo) {
    console.log('Editando vaga:', id, titulo);
    
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
            console.log('Resposta recebida:', response);
            
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
                        $('#editar_tipo_contrato').val(tipoId);
                    }
                    
                    if (vaga.regime_trabalho_id) {
                        var regimeId = parseInt(vaga.regime_trabalho_id);
                        console.log('Definindo regime_trabalho_id:', regimeId);
                        $('#editar_regime_trabalho').val(regimeId);
                    }
                    
                    if (vaga.nivel_experiencia_id) {
                        var nivelId = parseInt(vaga.nivel_experiencia_id);
                        console.log('Definindo nivel_experiencia_id:', nivelId);
                        $('#editar_nivel_experiencia').val(nivelId);
                    }
                    
                    // Forçar atualização dos selects após um pequeno delay
                    setTimeout(function() {
                        if (vaga.tipo_contrato_id) $('#editar_tipo_contrato').trigger('change');
                        if (vaga.regime_trabalho_id) $('#editar_regime_trabalho').trigger('change');
                        if (vaga.nivel_experiencia_id) $('#editar_nivel_experiencia').trigger('change');
                    }, 100);
                    
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
                    console.error('Resposta inválida:', response);
                    alert('Erro ao carregar detalhes da vaga: ' + (response.message || 'Resposta inválida do servidor'));
                }
            } catch (e) {
                console.error('Erro ao processar resposta:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr, status, error);
            alert('Erro ao carregar detalhes da vaga: ' + error);
        },
        complete: function() {
            // Esconder indicador de carregamento em caso de erro
            $('#editarVagaLoading').hide();
            $('#editarVagaForm').show();
        }
    });
}

// Função para alternar campos de empresa interna/externa no modal de edição
function toggleEditarEmpresaFields() {
    const tipoVaga = document.getElementById('editar_tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('editar_empresa_interna_div');
    const empresaExternaDiv = document.getElementById('editar_empresa_externa_div');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('editar_empresa_id').required = true;
        document.getElementById('editar_empresa_externa').required = false;
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        document.getElementById('editar_empresa_id').required = false;
        document.getElementById('editar_empresa_externa').required = true;
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('editar_empresa_id').required = false;
        document.getElementById('editar_empresa_externa').required = false;
    }
}