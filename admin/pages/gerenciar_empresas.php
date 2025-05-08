<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Obter lista de empresas
$db = Database::getInstance();
$empresas = $db->fetchAll("
    SELECT u.id, u.nome, u.status, u.data_cadastro, e.id as empresa_id, e.razao_social
    FROM usuarios u
    LEFT JOIN empresas e ON u.id = e.usuario_id
    WHERE u.tipo = 'empresa'
    ORDER BY u.nome ASC
");
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Empresas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Empresas</li>
    </ol>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
        <?php echo $_SESSION['flash_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php 
        // Limpar mensagem flash
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    endif; ?>

    <!-- Lista de Empresas -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-building me-1"></i>
            Lista de Empresas
        </div>
        <div class="card-body">
            <table id="empresasTable" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Nome Contato</th>
                            <th>Nome Empresa</th>
                            <th>Status</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td><?php echo $empresa['nome']; ?></td>
                            <td><?php echo $empresa['razao_social'] ?? 'Não informado'; ?></td>
                            <td><?php echo getStatusBadge($empresa['status'], 'usuario'); ?></td>
                            <td><?php echo formatAdminDate($empresa['data_cadastro']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" data-id="<?php echo $empresa['id']; ?>" data-nome="<?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?>" data-action="visualizar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" data-id="<?php echo $empresa['id']; ?>" data-nome="<?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?>" data-action="editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-id="<?php echo $empresa['id']; ?>" data-nome="<?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?>" data-action="senha">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <?php if ($empresa['status'] !== 'bloqueado'): ?>
                                    <button type="button" class="btn btn-sm btn-warning" data-id="<?php echo $empresa['id']; ?>" data-nome="<?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?>" data-action="bloquear">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-success" data-id="<?php echo $empresa['id']; ?>" data-nome="<?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?>" data-action="ativar">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-id="<?php echo $empresa['id']; ?>" data-nome="<?php echo htmlspecialchars($empresa['razao_social'] ?: $empresa['nome']); ?>" data-action="excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
        </div>
    </div>
</div>

<!-- Modal Alterar Senha -->
<div class="modal fade" id="modalAlterarSenha" tabindex="-1" aria-labelledby="modalAlterarSenhaLabel" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAlterarSenhaLabel">Alterar Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    
            </div>
            <div class="modal-body">
                <form id="formAlterarSenha" action="<?php echo SITE_URL; ?>/admin/processar_senha.php" method="post">
                    <input type="hidden" name="acao" value="alterar_senha">
                    <input type="hidden" id="usuario_id_senha" name="usuario_id">
                    
                    <!-- Campo de email oculto para acessibilidade e gerenciadores de senha -->
                    <div class="visually-hidden">
                        <label for="usuario_email_senha">Email do usuário</label>
                        <input type="text" id="usuario_email_senha" name="usuario_email" autocomplete="username">
                    </div>
                    
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6" autocomplete="new-password">
                        <small class="form-text text-muted">A senha deve ter pelo menos 6 caracteres.</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" required minlength="6" autocomplete="new-password">
                        <div id="senha_feedback" class="invalid-feedback">As senhas não coincidem.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarSenha">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizar Empresa -->
<div class="modal fade" id="modalVisualizarEmpresa" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarEmpresaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarEmpresaLabel">Detalhes da Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    
            </div>
            <div class="modal-body">
                <div id="empresaDetalhes" class="p-3">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p>Carregando detalhes da empresa...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarEmpresaDetalhe">Editar Empresa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Empresa -->
<div class="modal fade" id="modalEditarEmpresa" tabindex="-1" aria-labelledby="modalEditarEmpresaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEditarEmpresaLabel">Editar Empresa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/pages/processar_empresa.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="empresa_id" id="editar_empresa_id">
                    
                    <!-- Tabs para organizar os campos -->
                    <ul class="nav nav-tabs" id="empresaTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="info-basica-tab" data-bs-toggle="tab" href="#info-basica" role="tab">Informações Básicas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="info-adicional-tab" data-bs-toggle="tab" href="#info-adicional" role="tab">Informações Adicionais</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="seguranca-tab" data-bs-toggle="tab" href="#seguranca" role="tab">Segurança</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content pt-3" id="empresaTabsContent">
                        <!-- Tab Informações Básicas -->
                        <div class="tab-pane fade show active" id="info-basica" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editar_nome" class="form-label">Nome do Contato</label>
                                    <input type="text" class="form-control" id="editar_nome" name="nome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editar_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="editar_email" name="email" required autocomplete="username">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editar_nome_empresa" class="form-label">Nome da Empresa</label>
                                    <input type="text" class="form-control" id="editar_nome_empresa" name="nome_empresa" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editar_status" class="form-label">Status</label>
                                    <select class="form-control" id="editar_status" name="status" required>
                                        <option value="ativo">Ativo</option>
                                        <option value="pendente">Pendente</option>
                                        <option value="inativo">Inativo</option>
                                        <option value="bloqueado">Bloqueado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Informações Adicionais -->
                        <div class="tab-pane fade" id="info-adicional" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editar_cnpj" class="form-label">CNPJ</label>
                                    <input type="text" class="form-control" id="editar_cnpj" name="cnpj">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editar_segmento" class="form-label">Segmento</label>
                                    <input type="text" class="form-control" id="editar_segmento" name="segmento">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="editar_descricao" class="form-label">Descrição da Empresa</label>
                                <textarea class="form-control" id="editar_descricao" name="descricao" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="editar_logo" class="form-label">Logo da Empresa</label>
                                <input type="file" class="form-control" id="editar_logo" name="logo" accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB.</div>
                                <div id="previewLogoContainer" class="mt-2 d-none">
                                    <img id="previewLogo" src="#" alt="Prévia da Logo" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Segurança -->
                        <div class="tab-pane fade" id="seguranca" role="tabpanel">
                            <div class="mb-3">
                                <label for="editar_senha" class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                                <input type="password" class="form-control" id="editar_senha" name="senha" autocomplete="current-password">
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> A senha deve ter pelo menos 6 caracteres. Deixe em branco se não deseja alterar a senha atual.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmação -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    
            </div>
            <div class="modal-body">
                <p id="mensagem_confirmacao"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_empresa.php" method="post">
                    <input type="hidden" name="acao" id="acao_confirmacao">
                    <input type="hidden" name="empresa_id" id="empresa_id_confirmacao">
                    <button type="submit" class="btn btn-danger">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Função para visualizar empresa
function visualizarEmpresa(id, nome) {
    // Atualizar título do modal
    document.getElementById('modalVisualizarEmpresaLabel').textContent = 'Detalhes da Empresa: ' + nome;
    
    // Armazenar ID da empresa para o botão de edição
    document.getElementById('btnEditarEmpresaDetalhe').setAttribute('data-id', id);
    document.getElementById('btnEditarEmpresaDetalhe').setAttribute('data-nome', nome);
    
    // Mostrar loading
    document.getElementById('empresaDetalhes').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
            <p>Carregando detalhes da empresa...</p>
        </div>
    `;
    
    // Abrir modal
    const modalVisualizarEmpresa = new bootstrap.Modal(document.getElementById('modalVisualizarEmpresa'));
    modalVisualizarEmpresa.show();
    
    // Carregar detalhes da empresa via AJAX
    fetch('<?php echo SITE_URL; ?>/?route=api_empresa_detalhe&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.empresa) {
                const empresa = data.data.empresa;
                
                // Construir HTML com os detalhes da empresa
                let html = `
                    <div class="row">
                        <div class="col-md-3 text-center">
                            ${empresa.logo ? 
                                `<img src="${'<?php echo SITE_URL; ?>/uploads/empresas/' + empresa.logo}" class="img-fluid mb-3" style="max-width: 150px;">` : 
                                `<div class="rounded bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 150px; height: 150px; font-size: 60px; margin: 0 auto;">
                                    ${empresa.nome_empresa ? empresa.nome_empresa.charAt(0).toUpperCase() : empresa.nome.charAt(0).toUpperCase()}
                                </div>`
                            }
                        </div>
                        <div class="col-md-9">
                            <h4>${empresa.nome_empresa || 'Não informado'}</h4>
                            <p class="text-muted">Contato: ${empresa.nome}</p>
                            <p class="text-muted">Email: ${empresa.email}</p>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p><strong>CNPJ:</strong> ${empresa.cnpj || 'Não informado'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Segmento:</strong> ${empresa.segmento || 'Não informado'}</p>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> ${empresa.status.charAt(0).toUpperCase() + empresa.status.slice(1)}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Data de Cadastro:</strong> ${new Date(empresa.data_cadastro).toLocaleDateString('pt-BR')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mt-3">
                        <h5>Descrição da Empresa</h5>
                        <p>${empresa.descricao ? empresa.descricao.replace(/\n/g, '<br>') : 'Não informado'}</p>
                    </div>
                    
                    <div class="mt-3">
                        <h5>Vagas Publicadas</h5>
                        <p>${empresa.total_vagas || 0} vagas</p>
                    </div>
                `;
                
                document.getElementById('empresaDetalhes').innerHTML = html;
            } else {
                document.getElementById('empresaDetalhes').innerHTML = `
                    <div class="alert alert-danger">
                        Erro ao carregar detalhes da empresa: ${data.message || 'Dados da empresa não encontrados'}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('empresaDetalhes').innerHTML = `
                <div class="alert alert-danger">
                    Erro ao carregar detalhes da empresa: ${error.message}
                </div>
            `;
        });
}

// Função para editar empresa
function editarEmpresa(id, nome) {
    console.log('Iniciando edição da empresa ID:', id, 'Nome:', nome);
    
    // Mostrar indicador de carregamento
    document.getElementById('modalEditarEmpresaLabel').textContent = 'Carregando dados...';
    
    // URL da API
    const apiUrl = '<?php echo SITE_URL; ?>/?route=api_empresa_detalhe&id=' + id;
    console.log('Fazendo requisição para:', apiUrl);
    
    // Carregar dados da empresa via AJAX
    fetch(apiUrl)
        .then(response => {
            console.log('Status da resposta:', response.status, response.statusText);
            console.log('Headers:', [...response.headers.entries()]);
            
            // Verificar o tipo de conteúdo da resposta
            const contentType = response.headers.get('content-type');
            console.log('Tipo de conteúdo:', contentType);
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            
            // Verificar se a resposta é JSON
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error(`Resposta não é JSON: ${contentType}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }
            
            // Verificar se os dados contêm a empresa
            if (!data.data || !data.data.empresa) {
                throw new Error('Dados da empresa não encontrados na resposta');
            }
            
            const empresa = data.data.empresa;
            console.log('Dados da empresa:', empresa);
            
            // Verificar se todos os campos necessários existem
            if (!empresa.id) {
                throw new Error('Dados da empresa incompletos ou inválidos');
            }
            
            // Preencher o formulário com os dados da empresa
            document.getElementById('editar_empresa_id').value = empresa.id;
            document.getElementById('editar_nome').value = empresa.nome || '';
            document.getElementById('editar_email').value = empresa.email || '';
            
            // Verificar se os campos existem antes de tentar preencher
            if (document.getElementById('editar_nome_empresa')) {
                document.getElementById('editar_nome_empresa').value = empresa.nome_empresa || '';
            }
            
            if (document.getElementById('editar_cnpj')) {
                document.getElementById('editar_cnpj').value = empresa.cnpj || '';
            }
            
            if (document.getElementById('editar_segmento')) {
                document.getElementById('editar_segmento').value = empresa.segmento || '';
            }
            
            if (document.getElementById('editar_descricao')) {
                document.getElementById('editar_descricao').value = empresa.descricao || '';
            }
            
            if (document.getElementById('editar_razao_social') && empresa.razao_social !== undefined) {
                document.getElementById('editar_razao_social').value = empresa.razao_social || '';
            }
            
            if (document.getElementById('editar_cidade') && empresa.cidade !== undefined) {
                document.getElementById('editar_cidade').value = empresa.cidade || '';
            }
            
            if (document.getElementById('editar_estado') && empresa.estado !== undefined) {
                document.getElementById('editar_estado').value = empresa.estado || '';
            }
            
            document.getElementById('editar_status').value = empresa.status || 'ativo';
            
            // Atualizar título do modal
            document.getElementById('modalEditarEmpresaLabel').textContent = 'Editar Empresa: ' + (empresa.nome_empresa || empresa.nome || nome);
            
            // Abrir modal
            const modalEditarEmpresa = new bootstrap.Modal(document.getElementById('modalEditarEmpresa'));
            modalEditarEmpresa.show();
            
            console.log('Formulário preenchido com sucesso');
        })
        .catch(error => {
            // Fechar modal e mostrar erro
            const modalEditarEmpresa = bootstrap.Modal.getInstance(document.getElementById('modalEditarEmpresa'));
            modalEditarEmpresa.hide();
            console.error('Erro na requisição:', error);
            alert('Erro ao carregar dados da empresa: ' + error.message);
        });
}

// Função para confirmar ação (bloquear, ativar, excluir)
function confirmarAcao(acao, id, nome) {
    let mensagem = '';
    let titulo = '';
    
    if (acao === 'bloquear') {
        titulo = 'Bloquear Empresa';
        mensagem = `Tem certeza que deseja bloquear a empresa "${nome}"?`;
    } else if (acao === 'ativar') {
        titulo = 'Ativar Empresa';
        mensagem = `Tem certeza que deseja ativar a empresa "${nome}"?`;
    } else if (acao === 'excluir') {
        titulo = 'Excluir Empresa';
        mensagem = `ATENÇÃO: Esta ação não pode ser desfeita. Tem certeza que deseja excluir a empresa "${nome}"?`;
    }
    
    document.getElementById('modalConfirmacaoLabel').textContent = titulo;
    document.getElementById('mensagem_confirmacao').textContent = mensagem;
    document.getElementById('acao_confirmacao').value = acao;
    document.getElementById('empresa_id_confirmacao').value = id;
    
    const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
    modalConfirmacao.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar evento de clique aos botões de ação
    const botoesAcao = document.querySelectorAll('[data-action]');
    
    botoesAcao.forEach(botao => {
        botao.addEventListener('click', function() {
            const acao = this.getAttribute('data-action');
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            
            if (acao === 'visualizar') {
                visualizarEmpresa(id, nome);
            } else if (acao === 'editar') {
                editarEmpresa(id, nome);
            } else if (acao === 'senha') {
                alterarSenha(id, nome);
            } else if (acao === 'bloquear' || acao === 'ativar' || acao === 'excluir') {
                confirmarAcao(acao, id, nome);
            }
        });
    });
});

// Botão para editar empresa a partir do modal de visualização
document.getElementById('btnEditarEmpresaDetalhe').addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const nome = this.getAttribute('data-nome');
    
    // Fechar modal de visualização
    const modalVisualizarEmpresa = bootstrap.Modal.getInstance(document.getElementById('modalVisualizarEmpresa'));
    modalVisualizarEmpresa.hide();
    
    // Abrir modal de edição
    editarEmpresa(id, nome);
});

// Função para alterar senha
function alterarSenha(id, nome) {
    // Limpar formulário
    document.getElementById('formAlterarSenha').reset();
    document.getElementById('usuario_id_senha').value = id;
    
    // Buscar o email do usuário para preencher o campo oculto de username
    // Usamos a mesma API que é usada para editar a empresa
    const apiUrl = '<?php echo SITE_URL; ?>/?route=api_empresa_detalhe&id=' + id;
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                console.error('Erro ao buscar dados do usuário:', response.status);
                return;
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data && data.data.empresa && data.data.empresa.email) {
                // Preencher o campo oculto de username com o email do usuário
                document.getElementById('usuario_email_senha').value = data.data.empresa.email;
                console.log('Email do usuário preenchido:', data.data.empresa.email);
            }
        })
        .catch(error => {
            console.error('Erro ao buscar email do usuário:', error);
        });
    
    // Atualizar título do modal
    document.getElementById('modalAlterarSenhaLabel').textContent = 'Alterar Senha: ' + nome;
    
    // Abrir modal
    const modalAlterarSenha = new bootstrap.Modal(document.getElementById('modalAlterarSenha'));
    modalAlterarSenha.show();
}

// Validar confirmação de senha
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = this.value;
    
    if (novaSenha !== confirmarSenha) {
        this.classList.add('is-invalid');
        document.getElementById('senha_feedback').style.display = 'block';
    } else {
        this.classList.remove('is-invalid');
        document.getElementById('senha_feedback').style.display = 'none';
    }
});

// Botão para salvar nova senha
document.getElementById('btnSalvarSenha').addEventListener('click', function() {
    console.log('Iniciando alteração de senha');
    
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = document.getElementById('confirmar_senha').value;
    const usuarioId = document.getElementById('usuario_id_senha').value;
    
    // Validar dados
    if (!usuarioId) {
        alert('ID do usuário não fornecido.');
        return;
    }
    
    // Validar senhas
    if (novaSenha.length < 6) {
        alert('A senha deve ter pelo menos 6 caracteres.');
        return;
    }
    
    if (novaSenha !== confirmarSenha) {
        alert('As senhas não coincidem.');
        return;
    }
    
    // Mostrar indicador de carregamento
    const btnSalvar = document.getElementById('btnSalvarSenha');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
    btnSalvar.disabled = true;
    
    console.log('Enviando requisição para alterar senha do usuário ID:', usuarioId);
    
    // Obter dados do formulário
    const formData = new FormData();
    formData.append('acao', 'alterar_senha');
    formData.append('usuario_id', usuarioId);
    formData.append('nova_senha', novaSenha);
    
    // URL da API
    const apiUrl = '<?php echo SITE_URL; ?>/admin/processar_senha.php';
    
    // Enviar requisição para alterar senha
    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Status da resposta:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos:', data);
        
        // Restaurar botão
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
        
        if (data.success) {
            alert('Senha alterada com sucesso!');
            const modalAlterarSenha = bootstrap.Modal.getInstance(document.getElementById('modalAlterarSenha'));
            modalAlterarSenha.hide();
        } else {
            alert('Erro ao alterar senha: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        
        // Restaurar botão
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
        
        alert('Erro ao alterar senha: ' + error.message);
    });
});
</script>
