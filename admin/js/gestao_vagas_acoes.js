/**
 * Funções para gestão de vagas no OpenToJob
 * Conectando talentos prontos a oportunidades imediatas
 */

// Função para visualizar vaga
function visualizarVaga(id, titulo) {
    console.log('Visualizando vaga:', id, titulo);
    
    // Verificar se estamos usando Bootstrap 5 ou 4
    const usarBS5 = typeof bootstrap !== 'undefined';
    
    // Atualizar título do modal
    document.getElementById('modalVisualizarVagaLabel').textContent = 'Detalhes da Vaga: ' + titulo;
    
    // Mostrar indicador de carregamento
    document.getElementById('vagaDetalhes').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando detalhes da vaga...</p>
        </div>
    `;
    
    // Abrir modal usando a API correta do Bootstrap
    const modalElement = document.getElementById('modalVisualizarVaga');
    if (usarBS5) {
        // Bootstrap 5
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        // Fallback para jQuery (Bootstrap 4)
        if (typeof $ !== 'undefined') {
            $(modalElement).modal('show');
        } else {
            console.error('Nem Bootstrap 5 nem jQuery estão disponíveis para abrir o modal');
        }
    }
    
    // Preparar dados para a requisição
    const formData = new FormData();
    formData.append('acao', 'obter_detalhes');
    formData.append('vaga_id', id);
    
    // Fazer requisição fetch para obter detalhes da vaga
    fetch(SITE_URL + '/admin/processar_gestao_vagas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(response => {
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
                            <span class="badge bg-${getStatusClass(vaga.status)}">${vaga.status}</span>
                        </p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong>Empresa:</strong> ${vaga.tipo_vaga === 'externa' ? '<span class="badge bg-info">Externa</span> ' : ''}${vaga.empresa_nome || 'Não informada'}</p>
                        <p><strong>Localização:</strong> ${vaga.cidade ? vaga.cidade + (vaga.estado ? ', ' + vaga.estado : '') : 'Não informada'}</p>
                        <p><strong>Tipo de Contrato:</strong> ${vaga.tipo_contrato_nome || vaga.tipo_contrato || 'Não informado'}</p>
                        <p><strong>Regime de Trabalho:</strong> ${vaga.regime_trabalho_nome || vaga.regime_trabalho || 'Não informado'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Nível de Experiência:</strong> ${vaga.nivel_experiencia_nome || vaga.nivel_experiencia || 'Não informado'}</p>
                        <p><strong>Salário:</strong> ${formatSalario(vaga.salario_min, vaga.salario_max)}</p>
                        <p><strong>Data de Publicação:</strong> ${formatDate(vaga.data_publicacao)}</p>
                        <p><strong>Data de Expiração:</strong> ${formatDate(vaga.data_expiracao) || 'Não definida'}</p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5>Descrição</h5>
                        <div class="p-3 bg-light rounded">
                            ${vaga.descricao || 'Nenhuma descrição fornecida.'}
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5>Requisitos</h5>
                        <div class="p-3 bg-light rounded">
                            ${vaga.requisitos || 'Nenhum requisito especificado.'}
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5>Benefícios</h5>
                        <div class="p-3 bg-light rounded">
                            ${vaga.beneficios || 'Nenhum benefício especificado.'}
                        </div>
                    </div>
                </div>
            `;
            
            // Atualizar conteúdo do modal
            document.getElementById('vagaDetalhes').innerHTML = html;
            
            // Atualizar botão de edição
            const btnEditarVagaDetalhe = document.getElementById('btnEditarVagaDetalhe');
            btnEditarVagaDetalhe.setAttribute('data-id', vaga.id);
            btnEditarVagaDetalhe.setAttribute('data-titulo', vaga.titulo);
        } else {
            // Exibir mensagem de erro
            document.getElementById('vagaDetalhes').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    ${response.message || 'Não foi possível carregar os detalhes da vaga.'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        document.getElementById('vagaDetalhes').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                Erro ao carregar detalhes da vaga: ${error.message}
            </div>
        `;
    });
}

// Função para editar vaga
function editarVaga(id, titulo) {
    console.log('Editando vaga:', id, titulo);
    
    // Verificar se estamos usando Bootstrap 5 ou 4
    const usarBS5 = typeof bootstrap !== 'undefined';
    
    // Atualizar título do modal
    document.getElementById('modalEditarVagaLabel').textContent = 'Editar Vaga: ' + titulo;
    
    // Mostrar indicador de carregamento
    document.getElementById('editarVagaLoading').style.display = 'block';
    document.getElementById('editarVagaForm').style.display = 'none';
    
    // Abrir modal usando a API correta do Bootstrap
    const modalElement = document.getElementById('modalEditarVaga');
    if (usarBS5) {
        // Bootstrap 5
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        // Fallback para jQuery (Bootstrap 4)
        if (typeof $ !== 'undefined') {
            $(modalElement).modal('show');
        } else {
            console.error('Nem Bootstrap 5 nem jQuery estão disponíveis para abrir o modal');
        }
    }
    
    // Preparar dados para a requisição
    const formData = new FormData();
    formData.append('acao', 'obter_detalhes');
    formData.append('vaga_id', id);
    
    // Fazer requisição fetch para obter detalhes da vaga
    fetch(SITE_URL + '/admin/processar_gestao_vagas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(response => {
        console.log('Resposta recebida para edição:', response);
        
        if (response.success && response.vaga) {
            const vaga = response.vaga;
            
            // Preencher formulário de edição
            document.getElementById('editar_vaga_id').value = vaga.id;
            document.getElementById('editar_titulo').value = vaga.titulo || '';
            
            // Tipo de vaga e campos relacionados
            const tipoVagaSelect = document.getElementById('editar_tipo_vaga');
            tipoVagaSelect.value = vaga.tipo_vaga || 'interna';
            
            // Disparar evento de change para mostrar/ocultar campos relacionados
            const event = new Event('change');
            tipoVagaSelect.dispatchEvent(event);
            
            // Preencher campos específicos do tipo de vaga
            if (vaga.tipo_vaga === 'interna') {
                document.getElementById('editar_empresa_id').value = vaga.empresa_id || '';
            } else {
                document.getElementById('editar_empresa_externa').value = vaga.empresa_externa || '';
            }
            
            // Preencher outros campos
            document.getElementById('editar_cidade').value = vaga.cidade || '';
            document.getElementById('editar_estado').value = vaga.estado || '';
            
            if (document.getElementById('editar_tipo_contrato_id')) {
                document.getElementById('editar_tipo_contrato_id').value = vaga.tipo_contrato_id || '';
            }
            
            if (document.getElementById('editar_regime_trabalho_id')) {
                document.getElementById('editar_regime_trabalho_id').value = vaga.regime_trabalho_id || '';
            }
            
            if (document.getElementById('editar_nivel_experiencia_id')) {
                document.getElementById('editar_nivel_experiencia_id').value = vaga.nivel_experiencia_id || '';
            }
            
            document.getElementById('editar_salario_min').value = vaga.salario_min || '';
            document.getElementById('editar_salario_max').value = vaga.salario_max || '';
            document.getElementById('editar_descricao').value = vaga.descricao || '';
            document.getElementById('editar_requisitos').value = vaga.requisitos || '';
            document.getElementById('editar_beneficios').value = vaga.beneficios || '';
            document.getElementById('editar_status').value = vaga.status || 'aberta';
            
            // Mostrar formulário e ocultar indicador de carregamento
            document.getElementById('editarVagaLoading').style.display = 'none';
            document.getElementById('editarVagaForm').style.display = 'block';
        } else {
            // Exibir mensagem de erro
            document.getElementById('editarVagaLoading').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    ${response.message || 'Não foi possível carregar os detalhes da vaga para edição.'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        document.getElementById('editarVagaLoading').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                Erro ao carregar detalhes da vaga para edição: ${error.message}
            </div>
        `;
    });
}

// Função para confirmar exclusão de vaga
function confirmarExclusao(id, titulo) {
    console.log('Confirmando exclusão da vaga:', id, titulo);
    
    // Verificar se estamos usando Bootstrap 5 ou 4
    const usarBS5 = typeof bootstrap !== 'undefined';
    
    // Preencher dados no modal de confirmação
    document.getElementById('vaga_titulo_confirmacao').textContent = titulo;
    document.getElementById('vaga_id_confirmacao').value = id;
    
    // Abrir modal usando a API correta do Bootstrap
    const modalElement = document.getElementById('modalConfirmacao');
    if (usarBS5) {
        // Bootstrap 5
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        // Fallback para jQuery (Bootstrap 4)
        if (typeof $ !== 'undefined') {
            $(modalElement).modal('show');
        } else {
            console.error('Nem Bootstrap 5 nem jQuery estão disponíveis para abrir o modal');
        }
    }
}

// Função para excluir vaga via AJAX
function excluirVaga() {
    const vagaId = document.getElementById('vaga_id_confirmacao').value;
    const formData = new FormData();
    formData.append('acao', 'excluir');
    formData.append('vaga_id', vagaId);
    
    // Mostrar indicador de carregamento
    document.getElementById('btnConfirmarExclusao').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Excluindo...';
    document.getElementById('btnConfirmarExclusao').disabled = true;
    
    // Enviar requisição AJAX
    fetch(SITE_URL + '/admin/processar_gestao_vagas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Fechar o modal
        const modalElement = document.getElementById('modalConfirmacao');
        if (typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getInstance(modalElement);
            modal.hide();
        } else if (typeof $ !== 'undefined') {
            $(modalElement).modal('hide');
        }
        
        // Exibir mensagem de sucesso
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + (data.success ? 'success' : 'danger') + ' alert-dismissible fade show';
        alertDiv.innerHTML = `
            ${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        `;
        
        // Inserir alerta antes da tabela
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(alertDiv, cardBody.firstChild);
        
        // Se a exclusão foi bem-sucedida, remover a linha da tabela
        if (data.success) {
            const row = document.querySelector(`tr[data-vaga-id="${vagaId}"]`);
            if (row) {
                row.remove();
            } else {
                // Se não encontrou a linha pelo atributo data, recarregar a tabela
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }
        
        // Rolar para o topo para mostrar a mensagem
        window.scrollTo(0, 0);
    })
    .catch(error => {
        console.error('Erro ao excluir vaga:', error);
        alert('Erro ao excluir vaga. Por favor, tente novamente.');
    })
    .finally(() => {
        // Restaurar botão
        document.getElementById('btnConfirmarExclusao').innerHTML = 'Excluir';
        document.getElementById('btnConfirmarExclusao').disabled = false;
    });
}

// Função para editar vaga a partir do modal de visualização
function editarVagaDoModal() {
    const btnEditarVagaDetalhe = document.getElementById('btnEditarVagaDetalhe');
    const id = btnEditarVagaDetalhe.getAttribute('data-id');
    const titulo = btnEditarVagaDetalhe.getAttribute('data-titulo');
    
    // Verificar se estamos usando Bootstrap 5 ou 4
    const usarBS5 = typeof bootstrap !== 'undefined';
    
    // Fechar modal de visualização
    const modalVisualizarVaga = document.getElementById('modalVisualizarVaga');
    if (usarBS5) {
        const modalInstance = bootstrap.Modal.getInstance(modalVisualizarVaga);
        if (modalInstance) {
            modalInstance.hide();
        }
    } else if (typeof $ !== 'undefined') {
        $(modalVisualizarVaga).modal('hide');
    }
    
    // Abrir modal de edição
    setTimeout(() => {
        editarVaga(id, titulo);
    }, 500);
}

// Funções auxiliares
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

function formatDate(dateString) {
    if (!dateString) return 'Não definida';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function formatSalario(min, max) {
    function formatValue(value) {
        return new Intl.NumberFormat('pt-BR', { 
            style: 'currency', 
            currency: 'BRL' 
        }).format(value);
    }
    
    if (min && max) {
        return `${formatValue(min)} - ${formatValue(max)}`;
    } else if (min) {
        return `A partir de ${formatValue(min)}`;
    } else if (max) {
        return `Até ${formatValue(max)}`;
    }
    
    return 'Não informado';
}

// Inicializar DataTable quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined') {
        // jQuery está disponível, podemos usar $
        $('#vagasTable').DataTable({
            responsive: true,
            language: {
                "url": "/open2w/assets/js/pt-BR.json"
            },
            "order": [[6, "desc"]] // Ordenar por data de publicação (decrescente)
        });
    } else if (typeof DataTable !== 'undefined') {
        // jQuery não está disponível, mas DataTable sim (versão vanilla JS)
        new DataTable('#vagasTable', {
            responsive: true,
            language: {
                "url": "/open2w/assets/js/pt-BR.json"
            },
            "order": [[6, "desc"]] // Ordenar por data de publicação (decrescente)
        });
    } else {
        console.error('Nem jQuery nem DataTable estão disponíveis. A tabela não será inicializada.');
    }
});
