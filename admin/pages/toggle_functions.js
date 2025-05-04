// Função para alternar a exibição dos campos de empresa no formulário de adição
function toggleEmpresaFields() {
    const tipoVaga = document.getElementById('tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('empresa_interna_div');
    const empresaExternaDiv = document.getElementById('empresa_externa_div');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('empresa_id').required = true;
        document.getElementById('empresa_externa').required = false;
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        document.getElementById('empresa_id').required = false;
        document.getElementById('empresa_externa').required = true;
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('empresa_id').required = false;
        document.getElementById('empresa_externa').required = false;
    }
}

// Função para alternar a exibição dos campos de empresa no formulário de edição
function toggleEditarEmpresaFields() {
    const tipoVaga = document.getElementById('editar_tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('editar_empresa_interna_div');
    const empresaExternaDiv = document.getElementById('editar_empresa_externa_div');
    
    console.log('toggleEditarEmpresaFields chamado. Tipo de vaga:', tipoVaga);
    
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
