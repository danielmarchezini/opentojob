function toggleEmpresaFields() {
    const tipoVaga = document.getElementById('tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('empresa_interna_div');
    const empresaExternaDiv = document.getElementById('empresa_externa_div');
    const empresaIdField = document.getElementById('empresa_id');
    const empresaExternaField = document.getElementById('empresa_externa');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = true;
        empresaExternaField.required = false;
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        empresaIdField.required = false;
        empresaExternaField.required = true;
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = false;
        empresaExternaField.required = false;
    }
}

function toggleEditarEmpresaFields() {
    const tipoVaga = document.getElementById('editar_tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('editar_empresa_interna_div');
    const empresaExternaDiv = document.getElementById('editar_empresa_externa_div');
    const empresaIdField = document.getElementById('editar_empresa_id');
    const empresaExternaField = document.getElementById('editar_empresa_externa');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = true;
        empresaExternaField.required = false;
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        empresaIdField.required = false;
        empresaExternaField.required = true;
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        empresaIdField.required = false;
        empresaExternaField.required = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    if (typeof DataTable !== 'undefined') {
        new DataTable('#vagasTable', {
            responsive: true,
            language: {
                url: '/open2w/assets/js/pt-BR.json',
            }
        });
    } else {
        console.warn('DataTable não está definido. A tabela não será inicializada.');
    }
    
    // Inicializar toggleEmpresaFields para garantir que os campos corretos estejam visíveis
    if (document.getElementById('tipo_vaga')) {
        toggleEmpresaFields();
    }
});

// Função para visualizar vaga
function visualizarVaga(id, titulo) {
    // Atualizar título do modal
    document.getElementById('modalVisualizarVagaLabel').textContent = 'Detalhes da Vaga: ' + titulo;
    
    // Armazenar ID da vaga para o botão de edição
    document.getElementById('btnEditarVagaDetalhe').setAttribute('data-id', id);
    document.getElementById('btnEditarVagaDetalhe').setAttribute('data-titulo', titulo);
    
    // Mostrar loading
    document.getElementById('vagaDetalhes').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p>Carregando detalhes da vaga...</p>
        </div>
    `;
    
    // Verificar se jQuery está disponível
    if (typeof jQuery !== 'undefined') {
        // Usar jQuery para AJAX
        jQuery.ajax({
            url: SITE_URL + '/?route=api_vaga_detalhe&id=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                processarDadosVaga(data);
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar detalhes da vaga:', error);
                document.getElementById('vagaDetalhes').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar detalhes da vaga. Por favor, tente novamente.
                    </div>
                `;
            }
        });
    } else {
        // Usar Fetch API (JavaScript puro)
        fetch(SITE_URL + '/?route=api_vaga_detalhe&id=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                processarDadosVaga(data);
            })
            .catch(error => {
                console.error('Erro ao carregar detalhes da vaga:', error);
                document.getElementById('vagaDetalhes').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar detalhes da vaga. Por favor, tente novamente.
                    </div>
                `;
            });
    }
}

// Função para processar os dados da vaga
function processarDadosVaga(data) {
    if (data.success && data.data && data.data.vaga) {
        const vaga = data.data.vaga;
        
        // Formatar texto com quebras de linha
        function formatTextWithLineBreaks(text) {
            return text ? text.replace(/\n/g, '<br>') : '';
        }
        
        // Preencher detalhes da vaga
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h5>Informações Gerais</h5>
                    <ul class="list-group mb-3">
                        <li class="list-group-item"><strong>ID:</strong> ${vaga.id}</li>
                        <li class="list-group-item"><strong>Tipo:</strong> ${vaga.tipo_vaga === 'interna' ? 'Interna' : 'Externa'}</li>
                        <li class="list-group-item"><strong>Empresa:</strong> ${vaga.tipo_vaga === 'interna' ? vaga.empresa_nome : vaga.empresa_externa || 'Não informada'}</li>
                        <li class="list-group-item"><strong>Localização:</strong> ${vaga.cidade ? vaga.cidade + (vaga.estado ? ' - ' + vaga.estado : '') : 'Não informada'}</li>
                        <li class="list-group-item"><strong>Status:</strong> <span class="badge bg-${vaga.status === 'aberta' ? 'success' : (vaga.status === 'fechada' ? 'danger' : 'warning')}">${vaga.status.charAt(0).toUpperCase() + vaga.status.slice(1)}</span></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Detalhes da Vaga</h5>
                    <ul class="list-group mb-3">
                        <li class="list-group-item"><strong>Tipo de Contrato:</strong> ${vaga.tipo_contrato_nome || vaga.tipo_contrato || 'Não informado'}</li>
                        <li class="list-group-item"><strong>Regime de Trabalho:</strong> ${vaga.regime_trabalho_nome || vaga.regime_trabalho || 'Não informado'}</li>
                        <li class="list-group-item"><strong>Nível de Experiência:</strong> ${vaga.nivel_experiencia_nome || vaga.nivel_experiencia || 'Não informado'}</li>
                        <li class="list-group-item"><strong>Data de Publicação:</strong> ${vaga.data_publicacao ? new Date(vaga.data_publicacao).toLocaleDateString('pt-BR') : 'Não informada'}</li>
                        <li class="list-group-item"><strong>Salário:</strong> ${formatarSalario(vaga.salario_min, vaga.salario_max) || 'Não informado'}</li>
                    </ul>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <h5>Descrição</h5>
                    <div class="card mb-3">
                        <div class="card-body">
                            ${formatTextWithLineBreaks(vaga.descricao) || 'Não informada'}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Requisitos</h5>
                    <div class="card mb-3">
                        <div class="card-body">
                            ${formatTextWithLineBreaks(vaga.requisitos) || 'Não informados'}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Benefícios</h5>
                    <div class="card mb-3">
                        <div class="card-body">
                            ${formatTextWithLineBreaks(vaga.beneficios) || 'Não informados'}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Se for vaga externa, mostrar link
        if (vaga.tipo_vaga === 'externa' && vaga.url_externa) {
            html += `
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-external-link-alt me-2"></i>
                            Esta é uma vaga externa. <a href="${vaga.url_externa}" target="_blank" class="alert-link">Clique aqui</a> para acessar a página original da vaga.
                        </div>
                    </div>
                </div>
            `;
        }
        
        document.getElementById('vagaDetalhes').innerHTML = html;
    } else {
        document.getElementById('vagaDetalhes').innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle me-2"></i>
                Não foi possível carregar os detalhes da vaga. Por favor, tente novamente.
            </div>
        `;
    }
}

// Função para formatar salário
function formatarSalario(min, max) {
    if (!min && !max) return null;
    
    const formatarValor = (valor) => {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    };
    
    if (min && max) {
        return `${formatarValor(min)} - ${formatarValor(max)}`;
    } else if (min) {
        return `A partir de ${formatarValor(min)}`;
    } else if (max) {
        return `Até ${formatarValor(max)}`;
    }
    
    return null;
}

// Função para editar vaga
function editarVaga(id, titulo) {
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarVaga'));
    modal.show();
    
    // Atualizar título do modal
    document.getElementById('modalEditarVagaLabel').textContent = 'Editar Vaga: ' + titulo;
    
    // Mostrar loading e esconder formulário
    document.getElementById('editarVagaLoading').style.display = 'block';
    document.getElementById('editarVagaForm').style.display = 'none';
    
    // Definir ID da vaga no formulário
    document.getElementById('editar_vaga_id').value = id;
    
    // Carregar dados da vaga
    if (typeof jQuery !== 'undefined') {
        jQuery.ajax({
            url: SITE_URL + '/?route=api_vaga_detalhe&id=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                preencherFormularioEdicao(data);
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar dados da vaga:', error);
                alert('Erro ao carregar dados da vaga. Por favor, tente novamente.');
                modal.hide();
            }
        });
    } else {
        fetch(SITE_URL + '/?route=api_vaga_detalhe&id=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                preencherFormularioEdicao(data);
            })
            .catch(error => {
                console.error('Erro ao carregar dados da vaga:', error);
                alert('Erro ao carregar dados da vaga. Por favor, tente novamente.');
                modal.hide();
            });
    }
}

// Função para preencher o formulário de edição
function preencherFormularioEdicao(data) {
    if (data.success && data.data && data.data.vaga) {
        const vaga = data.data.vaga;
        
        // Preencher campos do formulário
        document.getElementById('editar_titulo').value = vaga.titulo || '';
        document.getElementById('editar_tipo_vaga').value = vaga.tipo_vaga || '';
        
        if (vaga.tipo_vaga === 'interna') {
            document.getElementById('editar_empresa_id').value = vaga.empresa_id || '';
        } else if (vaga.tipo_vaga === 'externa') {
            document.getElementById('editar_empresa_externa').value = vaga.empresa_externa || '';
        }
        
        document.getElementById('editar_cidade').value = vaga.cidade || '';
        document.getElementById('editar_estado').value = vaga.estado || '';
        document.getElementById('editar_tipo_contrato').value = vaga.tipo_contrato_id || '';
        document.getElementById('editar_regime_trabalho').value = vaga.regime_trabalho_id || '';
        document.getElementById('editar_nivel_experiencia').value = vaga.nivel_experiencia_id || '';
        document.getElementById('editar_salario_min').value = vaga.salario_min || '';
        document.getElementById('editar_salario_max').value = vaga.salario_max || '';
        document.getElementById('editar_descricao').value = vaga.descricao || '';
        document.getElementById('editar_requisitos').value = vaga.requisitos || '';
        document.getElementById('editar_beneficios').value = vaga.beneficios || '';
        document.getElementById('editar_status').value = vaga.status || 'pendente';
        
        if (vaga.tipo_vaga === 'externa') {
            document.getElementById('editar_url_externa').value = vaga.url_externa || '';
        }
        
        // Atualizar visibilidade dos campos de empresa
        toggleEditarEmpresaFields();
        
        // Esconder loading e mostrar formulário
        document.getElementById('editarVagaLoading').style.display = 'none';
        document.getElementById('editarVagaForm').style.display = 'block';
    } else {
        console.error('Erro ao carregar dados da vaga:', data);
        alert('Erro ao carregar dados da vaga. Por favor, tente novamente.');
        
        // Fechar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarVaga'));
        if (modal) {
            modal.hide();
        }
    }
}

// Função para editar vaga a partir do modal de visualização
function editarVagaDoModal() {
    const id = document.getElementById('btnEditarVagaDetalhe').getAttribute('data-id');
    const titulo = document.getElementById('btnEditarVagaDetalhe').getAttribute('data-titulo');
    
    // Fechar modal de visualização
    const modalVisualizar = bootstrap.Modal.getInstance(document.getElementById('modalVisualizarVaga'));
    if (modalVisualizar) {
        modalVisualizar.hide();
    }
    
    // Abrir modal de edição
    setTimeout(function() {
        editarVaga(id, titulo);
    }, 500);
}

// Função para confirmar exclusão
function confirmarExclusao(id, titulo) {
    document.getElementById('mensagem_confirmacao').textContent = `Tem certeza que deseja excluir a vaga "${titulo}"?`;
    document.getElementById('vaga_id_confirmacao').value = id;
    document.getElementById('acao_confirmacao').value = 'excluir';
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
    modal.show();
}
