// Função para alternar entre campos de empresa interna/externa
function toggleEmpresaFields() {
    const tipoVaga = document.getElementById('tipo_vaga').value;
    const empresaIdGroup = document.getElementById('empresa_id_group');
    const empresaExternaGroup = document.getElementById('empresa_externa_group');
    
    if (tipoVaga === 'interna') {
        empresaIdGroup.style.display = 'block';
        empresaExternaGroup.style.display = 'none';
    } else {
        empresaIdGroup.style.display = 'none';
        empresaExternaGroup.style.display = 'block';
    }
}

// Função para alternar entre campos de empresa interna/externa no formulário de edição
function toggleEditarEmpresaFields() {
    const tipoVaga = document.getElementById('editar_tipo_vaga').value;
    const empresaIdGroup = document.getElementById('editar_empresa_id_group');
    const empresaExternaGroup = document.getElementById('editar_empresa_externa_group');
    
    if (tipoVaga === 'interna') {
        empresaIdGroup.style.display = 'block';
        empresaExternaGroup.style.display = 'none';
    } else {
        empresaIdGroup.style.display = 'none';
        empresaExternaGroup.style.display = 'block';
    }
}

// Função para visualizar detalhes da vaga
function visualizarVaga(id, titulo) {
    // Mostrar modal com loading
    $('#modalVisualizarVaga').modal('show');
    $('#modalVisualizarVagaLabel').text('Detalhes da Vaga: ' + titulo);
    
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
            if (response.success) {
                const vaga = response.data;
                let html = '<div class="row">';
                
                // Informações básicas
                html += '<div class="col-md-12 mb-3">';
                html += '<h4>' + vaga.titulo + '</h4>';
                html += '<p><strong>Tipo de Vaga:</strong> ' + (vaga.tipo_vaga === 'externa' ? 'Externa' : 'Interna') + '</p>';
                
                // Empresa
                if (vaga.tipo_vaga === 'externa' && vaga.empresa_externa) {
                    html += '<p><strong>Empresa:</strong> ' + vaga.empresa_externa + '</p>';
                } else if (vaga.empresa_nome) {
                    html += '<p><strong>Empresa:</strong> ' + vaga.empresa_nome + '</p>';
                }
                
                // Localização
                let localizacao = [];
                if (vaga.cidade) localizacao.push(vaga.cidade);
                if (vaga.estado) localizacao.push(vaga.estado);
                if (localizacao.length > 0) {
                    html += '<p><strong>Localização:</strong> ' + localizacao.join(', ') + '</p>';
                }
                
                // Detalhes da vaga
                if (vaga.regime_trabalho) {
                    html += '<p><strong>Regime de Trabalho:</strong> ' + vaga.regime_trabalho + '</p>';
                }
                if (vaga.tipo_contrato) {
                    html += '<p><strong>Tipo de Contrato:</strong> ' + vaga.tipo_contrato + '</p>';
                }
                if (vaga.nivel_experiencia) {
                    html += '<p><strong>Nível de Experiência:</strong> ' + vaga.nivel_experiencia + '</p>';
                }
                
                // Salário
                if (vaga.mostrar_salario == 1) {
                    let salario = '';
                    if (vaga.salario_min && vaga.salario_max) {
                        salario = 'R$ ' + vaga.salario_min + ' - R$ ' + vaga.salario_max;
                    } else if (vaga.salario_min) {
                        salario = 'A partir de R$ ' + vaga.salario_min;
                    } else if (vaga.salario_max) {
                        salario = 'Até R$ ' + vaga.salario_max;
                    }
                    if (salario) {
                        html += '<p><strong>Salário:</strong> ' + salario + '</p>';
                    }
                } else {
                    html += '<p><strong>Salário:</strong> Não informado</p>';
                }
                
                // Status e datas
                html += '<p><strong>Status:</strong> <span class="badge ' + getStatusClass(vaga.status) + '">' + capitalizeFirstLetter(vaga.status) + '</span></p>';
                if (vaga.data_publicacao) {
                    html += '<p><strong>Data de Publicação:</strong> ' + formatDate(vaga.data_publicacao) + '</p>';
                }
                if (vaga.data_atualizacao) {
                    html += '<p><strong>Última Atualização:</strong> ' + formatDate(vaga.data_atualizacao) + '</p>';
                }
                
                // Palavras-chave
                if (vaga.palavras_chave) {
                    html += '<p><strong>Palavras-chave:</strong> ' + vaga.palavras_chave + '</p>';
                }
                
                html += '</div>'; // Fim da coluna de informações básicas
                
                // Descrição, Requisitos e Benefícios
                html += '<div class="col-md-12">';
                html += '<div class="card mb-3">';
                html += '<div class="card-header">Descrição</div>';
                html += '<div class="card-body">' + vaga.descricao.replace(/\n/g, '<br>') + '</div>';
                html += '</div>';
                
                html += '<div class="card mb-3">';
                html += '<div class="card-header">Requisitos</div>';
                html += '<div class="card-body">' + vaga.requisitos.replace(/\n/g, '<br>') + '</div>';
                html += '</div>';
                
                if (vaga.beneficios) {
                    html += '<div class="card mb-3">';
                    html += '<div class="card-header">Benefícios</div>';
                    html += '<div class="card-body">' + vaga.beneficios.replace(/\n/g, '<br>') + '</div>';
                    html += '</div>';
                }
                
                html += '</div>'; // Fim da coluna de descrição, requisitos e benefícios
                
                html += '</div>'; // Fim da row
                
                $('#visualizarVagaConteudo').html(html);
            } else {
                $('#visualizarVagaConteudo').html('<div class="alert alert-danger">Erro ao carregar detalhes da vaga: ' + response.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            $('#visualizarVagaConteudo').html('<div class="alert alert-danger">Erro ao carregar detalhes da vaga. Tente novamente mais tarde.</div>');
            console.error('Erro AJAX:', error);
        }
    });
}

// Função para editar vaga
function editarVaga(id, titulo) {
    // Mostrar modal com loading
    $('#modalEditarVaga').modal('show');
    $('#modalEditarVagaLabel').text('Editar Vaga: ' + titulo);
    $('#editar_vaga_id').val(id);
    $('#editarVagaLoading').show();
    $('#editarVagaForm').hide();
    
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
            if (response.success) {
                const vaga = response.data;
                
                // Preencher formulário com dados da vaga
                $('#editar_titulo').val(vaga.titulo);
                $('#editar_descricao').val(vaga.descricao);
                $('#editar_requisitos').val(vaga.requisitos);
                $('#editar_beneficios').val(vaga.beneficios);
                $('#editar_tipo_contrato').val(vaga.tipo_contrato);
                $('#editar_regime_trabalho').val(vaga.regime_trabalho);
                $('#editar_nivel_experiencia').val(vaga.nivel_experiencia);
                $('#editar_cidade').val(vaga.cidade);
                $('#editar_estado').val(vaga.estado);
                $('#editar_salario_min').val(vaga.salario_min);
                $('#editar_salario_max').val(vaga.salario_max);
                $('#editar_status').val(vaga.status);
                $('#editar_palavras_chave').val(vaga.palavras_chave);
                
                // Definir tipo de vaga e mostrar campos apropriados
                if (vaga.tipo_vaga === 'externa') {
                    $('#editar_tipo_vaga').val('externa');
                    $('#editar_empresa_externa').val(vaga.empresa_externa);
                } else {
                    $('#editar_tipo_vaga').val('interna');
                    $('#editar_empresa_id').val(vaga.empresa_id);
                }
                
                // Mostrar/esconder campos de empresa
                toggleEditarEmpresaFields();
                
                // Definir checkbox de mostrar salário
                $('#editar_mostrar_salario').prop('checked', vaga.mostrar_salario == 1);
                
                // Esconder loading e mostrar formulário
                $('#editarVagaLoading').hide();
                $('#editarVagaForm').show();
            } else {
                $('#editarVagaLoading').html('<div class="alert alert-danger">Erro ao carregar dados da vaga: ' + response.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            $('#editarVagaLoading').html('<div class="alert alert-danger">Erro ao carregar dados da vaga. Tente novamente mais tarde.</div>');
            console.error('Erro AJAX:', error);
        }
    });
}

// Função para confirmar exclusão de vaga
function confirmarExclusao(id, titulo) {
    $('#modalConfirmarExclusao').modal('show');
    $('#excluirVagaTitulo').text(titulo);
    $('#excluir_vaga_id').val(id);
}

// Funções auxiliares
function getStatusClass(status) {
    switch (status) {
        case 'aberta':
            return 'badge-success';
        case 'fechada':
            return 'badge-danger';
        case 'pendente':
            return 'badge-warning';
        default:
            return 'badge-secondary';
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Inicializar funções quando o documento estiver pronto
$(document).ready(function() {
    // Inicializar campos de empresa ao carregar a página
    toggleEmpresaFields();
    
    // Filtro de pesquisa na tabela
    $("#pesquisarVaga").on("keyup", function() {
        const value = $(this).val().toLowerCase();
        $("table tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
