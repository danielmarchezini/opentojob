/**
 * Funções de alternância para formulários de gestão de vagas
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Função para alternar campos de empresa no formulário de adicionar vaga
function toggleEmpresaFields() {
    const tipoVaga = document.getElementById('tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('empresa_interna_div');
    const empresaExternaDiv = document.getElementById('empresa_externa_div');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('empresa_id').required = true;
        document.getElementById('empresa_externa').required = false;
        document.getElementById('empresa_externa').value = '';
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        document.getElementById('empresa_id').required = false;
        document.getElementById('empresa_externa').required = true;
        document.getElementById('empresa_id').value = '';
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('empresa_id').required = false;
        document.getElementById('empresa_externa').required = false;
    }
}

// Função para alternar campos de empresa no formulário de adicionar vaga
function toggleAdicionarEmpresaFields() {
    const tipoVaga = document.getElementById('adicionar_tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('adicionar_empresa_interna_div');
    const empresaExternaDiv = document.getElementById('adicionar_empresa_externa_div');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('adicionar_empresa_id').required = true;
        document.getElementById('adicionar_empresa_externa').required = false;
        document.getElementById('adicionar_empresa_externa').value = '';
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        document.getElementById('adicionar_empresa_id').required = false;
        document.getElementById('adicionar_empresa_externa').required = true;
        document.getElementById('adicionar_empresa_id').value = '';
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('adicionar_empresa_id').required = false;
        document.getElementById('adicionar_empresa_externa').required = false;
    }
}

// Função para alternar campos de empresa no formulário de editar vaga
function toggleEditarEmpresaFields() {
    const tipoVaga = document.getElementById('editar_tipo_vaga').value;
    const empresaInternaDiv = document.getElementById('editar_empresa_interna_div');
    const empresaExternaDiv = document.getElementById('editar_empresa_externa_div');
    
    if (tipoVaga === 'interna') {
        empresaInternaDiv.style.display = 'block';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('editar_empresa_id').required = true;
        document.getElementById('editar_empresa_externa').required = false;
        document.getElementById('editar_empresa_externa').value = '';
    } else if (tipoVaga === 'externa') {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'block';
        document.getElementById('editar_empresa_id').required = false;
        document.getElementById('editar_empresa_externa').required = true;
        document.getElementById('editar_empresa_id').value = '';
    } else {
        empresaInternaDiv.style.display = 'none';
        empresaExternaDiv.style.display = 'none';
        document.getElementById('editar_empresa_id').required = false;
        document.getElementById('editar_empresa_externa').required = false;
    }
}

// Função para alternar exibição do campo de salário
function toggleSalarioFields(prefix) {
    const mostrarSalario = document.getElementById(prefix + '_mostrar_salario').checked;
    const salarioDiv = document.getElementById(prefix + '_salario_div');
    
    if (mostrarSalario) {
        salarioDiv.style.display = 'block';
    } else {
        salarioDiv.style.display = 'none';
        document.getElementById(prefix + '_salario_min').value = '';
        document.getElementById(prefix + '_salario_max').value = '';
    }
}

// Inicializar quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar campos de empresa no formulário padrão
    const tipoVaga = document.getElementById('tipo_vaga');
    if (tipoVaga) {
        tipoVaga.addEventListener('change', toggleEmpresaFields);
        toggleEmpresaFields(); // Inicializar estado
    }
    
    // Inicializar campos de empresa no formulário de adicionar
    const adicionarTipoVaga = document.getElementById('adicionar_tipo_vaga');
    if (adicionarTipoVaga) {
        adicionarTipoVaga.addEventListener('change', toggleAdicionarEmpresaFields);
        toggleAdicionarEmpresaFields(); // Inicializar estado
    }
    
    // Inicializar campos de empresa no formulário de editar
    const editarTipoVaga = document.getElementById('editar_tipo_vaga');
    if (editarTipoVaga) {
        editarTipoVaga.addEventListener('change', toggleEditarEmpresaFields);
        // Não inicializar aqui, será feito quando o modal for aberto
    }
    
    // Inicializar campos de salário no formulário de adicionar
    const adicionarMostrarSalario = document.getElementById('adicionar_mostrar_salario');
    if (adicionarMostrarSalario) {
        adicionarMostrarSalario.addEventListener('change', function() {
            toggleSalarioFields('adicionar');
        });
        toggleSalarioFields('adicionar'); // Inicializar estado
    }
    
    // Inicializar campos de salário no formulário de editar
    const editarMostrarSalario = document.getElementById('editar_mostrar_salario');
    if (editarMostrarSalario) {
        editarMostrarSalario.addEventListener('change', function() {
            toggleSalarioFields('editar');
        });
        // Não inicializar aqui, será feito quando o modal for aberto
    }
    
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    console.log('Funções de alternância inicializadas');
});
