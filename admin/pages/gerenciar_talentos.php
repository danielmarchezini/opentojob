<?php
// Obter lista de talentos
$db = Database::getInstance();
$talentos = $db->fetchAll("
    SELECT u.*, t.id as talento_id
    FROM usuarios u
    LEFT JOIN talentos t ON u.id = t.usuario_id
    WHERE u.tipo = 'talento'
    ORDER BY u.nome ASC
");
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Talentos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Talentos</li>
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

    <!-- Lista de Talentos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Lista de Talentos
        </div>
        <div class="card-body">
            <table id="talentosTable" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Profissão</th>
                            <th>Experiência</th>
                            <th>Perfil Público</th>
                            <th>Status</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($talentos as $talento): ?>
                        <tr>
                            <td><?php echo $talento['id']; ?></td>
                            <td><?php echo $talento['nome']; ?></td>
                            <td><?php echo $talento['email']; ?></td>
                            <td>Não informado</td>
                            <td>Não informado</td>
                            <td>
                                <span class="badge badge-secondary">Não</span>
                            </td>
                            <td><?php echo getStatusBadge($talento['status'], 'usuario'); ?></td>
                            <td><?php echo formatAdminDate($talento['data_cadastro']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" data-id="<?php echo $talento['id']; ?>" data-nome="<?php echo htmlspecialchars($talento['nome']); ?>" data-action="visualizar">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" data-id="<?php echo $talento['id']; ?>" data-nome="<?php echo htmlspecialchars($talento['nome']); ?>" data-action="editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-id="<?php echo $talento['id']; ?>" data-nome="<?php echo htmlspecialchars($talento['nome']); ?>" data-action="senha">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <?php if ($talento['status'] !== 'bloqueado'): ?>
                                    <button type="button" class="btn btn-sm btn-warning" data-id="<?php echo $talento['id']; ?>" data-nome="<?php echo htmlspecialchars($talento['nome']); ?>" data-action="bloquear">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-success" data-id="<?php echo $talento['id']; ?>" data-nome="<?php echo htmlspecialchars($talento['nome']); ?>" data-action="ativar">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-id="<?php echo $talento['id']; ?>" data-nome="<?php echo htmlspecialchars($talento['nome']); ?>" data-action="excluir">
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
    <div class="modal-dialog">
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

<!-- Modal Visualizar Talento -->
<div class="modal fade" id="modalVisualizarTalento" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarTalentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarTalentoLabel">Detalhes do Talento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="talentoDetalhes" class="p-3">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p>Carregando detalhes do talento...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarTalentoDetalhe">Editar Talento</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Talento -->
<div class="modal fade" id="modalEditarTalento" tabindex="-1" aria-labelledby="modalEditarTalentoLabel" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEditarTalentoLabel">Editar Talento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/pages/processar_talento.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="talento_id" id="editar_talento_id">
                    
                    <!-- Tabs para organizar os campos -->
                    <ul class="nav nav-tabs" id="talentoTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="dados-pessoais-tab" data-bs-toggle="tab" href="#dados-pessoais" role="tab">Dados Pessoais</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="perfil-profissional-tab" data-bs-toggle="tab" href="#perfil-profissional" role="tab">Perfil Profissional</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="configuracoes-tab" data-bs-toggle="tab" href="#configuracoes" role="tab">Configurações</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content pt-3" id="talentoTabsContent">
                        <!-- Tab Dados Pessoais -->
                        <div class="tab-pane fade show active" id="dados-pessoais" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editar_nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="editar_nome" name="nome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editar_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="editar_email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editar_status" class="form-label">Status</label>
                                    <select class="form-select" id="editar_status" name="status" required>
                                        <option value="ativo">Ativo</option>
                                        <option value="pendente">Pendente</option>
                                        <option value="inativo">Inativo</option>
                                        <option value="bloqueado">Bloqueado</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editar_telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="editar_telefone" name="telefone">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="editar_foto" class="form-label">Foto de Perfil</label>
                                <input type="file" class="form-control" id="editar_foto" name="foto" accept="image/*">
                                <div class="form-text">Deixe em branco para manter a foto atual. Formatos aceitos: JPG, PNG. Tamanho máximo: 2MB.</div>
                                <input type="hidden" id="foto_atual" name="foto_atual" value="">
                                <div id="previewFotoContainer" class="mt-2 d-none">
                                    <img id="previewFoto" src="" alt="Preview da foto" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Perfil Profissional -->
                        <div class="tab-pane fade" id="perfil-profissional" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editar_profissao" class="form-label">Profissão</label>
                                    <input type="text" class="form-control" id="editar_profissao" name="profissao">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editar_experiencia" class="form-label">Experiência (anos)</label>
                                    <input type="number" class="form-control" id="editar_experiencia" name="experiencia" min="0">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="editar_apresentacao" class="form-label">Carta de Apresentação</label>
                                <textarea class="form-control" id="editar_apresentacao" name="apresentacao" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <!-- Tab Configurações -->
                        <div class="tab-pane fade" id="configuracoes" role="tabpanel">
                            <div class="mb-3">
                                <label>Visibilidade do Perfil</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="checkbox" class="form-check-input" id="editar_mostrar_perfil" name="mostrar_perfil" value="1">
                                    <label class="form-check-label" for="editar_mostrar_perfil">Mostrar perfil publicamente</label>
                                </div>
                                <small class="form-text text-muted">Se ativado, o perfil será exibido nas pesquisas e na página inicial</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="editar_senha">Nova Senha</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="editar_senha" name="senha" placeholder="Deixe em branco para manter a senha atual">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="btn_alterar_senha_talento">Alterar Senha</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Removido campo duplicado de senha -->
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
<div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmar Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p id="mensagem_confirmacao"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_talento.php" method="post">
                    <input type="hidden" name="acao" id="acao_confirmacao">
                    <input type="hidden" name="talento_id" id="talento_id_confirmacao">
                    <button type="submit" class="btn btn-danger" id="btn_confirmar">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Função para mostrar prévia da imagem selecionada
document.getElementById('editar_foto').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('previewFotoContainer');
    const previewImg = document.getElementById('previewFoto');
    
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.classList.remove('d-none');
        }
        
        reader.readAsDataURL(this.files[0]);
    } else {
        previewContainer.classList.add('d-none');
    }
});

// Função para visualizar talento
function visualizarTalento(id, nome) {
    // Atualizar título do modal
    document.getElementById('modalVisualizarTalentoLabel').textContent = 'Detalhes do Talento: ' + nome;
    
    // Armazenar ID do talento para o botão de edição
    document.getElementById('btnEditarTalentoDetalhe').setAttribute('data-id', id);
    document.getElementById('btnEditarTalentoDetalhe').setAttribute('data-nome', nome);
    
    // Mostrar loading
    document.getElementById('talentoDetalhes').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
            <p>Carregando detalhes do talento...</p>
        </div>
    `;
    
    // Abrir modal
    const modalVisualizarTalento = new bootstrap.Modal(document.getElementById('modalVisualizarTalento'));
    modalVisualizarTalento.show();
    
    // Carregar detalhes do talento via AJAX
    fetch('<?php echo SITE_URL; ?>/admin/api_talento.php?id=' + id)
        .then(response => {
            console.log('Status da resposta:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }
            
            // Verificar se os dados contêm o talento
            if (!data.data || !data.data.talento) {
                throw new Error('Dados do talento não encontrados na resposta');
            }
            
            const talento = data.data.talento;
            console.log('Dados do talento:', talento);
            
            // Verificar se todos os campos necessários existem
            if (!talento.id) {
                throw new Error('Dados do talento incompletos ou inválidos');
            }
            
            // Construir HTML com os detalhes do talento
            let html = `
                <div class="row">
                    <div class="col-md-3 text-center">
                        ${talento.foto_perfil ? 
                            `<img src="${'<?php echo SITE_URL; ?>/uploads/perfil/' + talento.foto_perfil}" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">` : 
                            `<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 150px; height: 150px; font-size: 60px; margin: 0 auto;">
                                ${talento.nome.charAt(0).toUpperCase()}
                            </div>`
                        }
                    </div>
                    <div class="col-md-9">
                        <h4>${talento.nome}</h4>
                        <p class="text-muted">${talento.email}</p>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong>Profissão:</strong> ${talento.profissao || 'Não informado'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Experiência:</strong> ${talento.experiencia ? talento.experiencia + ' anos' : 'Não informado'}</p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Status:</strong> ${talento.status.charAt(0).toUpperCase() + talento.status.slice(1)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Perfil Público:</strong> ${talento.mostrar_perfil == 1 ? 'Sim' : 'Não'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="mt-3">
                    <h5>Carta de Apresentação</h5>
                    <p>${talento.apresentacao ? talento.apresentacao.replace(/\n/g, '<br>') : 'Não informado'}</p>
                </div>
                
                <div class="mt-3">
                    <h5>Informações Adicionais</h5>
                    <p><strong>Data de Cadastro:</strong> ${new Date(talento.data_cadastro).toLocaleDateString('pt-BR')}</p>
                </div>
            `;
            
            document.getElementById('talentoDetalhes').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('talentoDetalhes').innerHTML = `
                <div class="alert alert-danger">
                    Erro ao carregar detalhes do talento: ${error.message}
                </div>
            `;
        });
}

// Função para editar talento
function editarTalento(id, nome) {
    console.log('Iniciando edição do talento ID:', id, 'Nome:', nome);
    
    // Mostrar indicador de carregamento
    document.getElementById('modalEditarTalentoLabel').textContent = 'Carregando dados...';
    const modalEditarTalento = new bootstrap.Modal(document.getElementById('modalEditarTalento'));
    modalEditarTalento.show();
    
    // URL da API
    const apiUrl = '<?php echo SITE_URL; ?>/admin/api_talento.php?id=' + id;
    console.log('Fazendo requisição para:', apiUrl);
    
    // Carregar dados do talento via AJAX
    fetch(apiUrl)
        .then(response => {
            console.log('Status da resposta:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }
            
            // Verificar se os dados contêm o talento
            if (!data.data || !data.data.talento) {
                throw new Error('Dados do talento não encontrados na resposta');
            }
            
            const talento = data.data.talento;
            console.log('Dados do talento:', talento);
            
            // Verificar se todos os campos necessários existem
            if (!talento.id) {
                throw new Error('Dados do talento incompletos ou inválidos');
            }
            
            // Preencher o formulário com os dados do talento
            document.getElementById('editar_talento_id').value = talento.id;
            document.getElementById('editar_nome').value = talento.nome || '';
            document.getElementById('editar_email').value = talento.email || '';
            
            // Verificar se os campos existem antes de tentar preencher
            if (document.getElementById('editar_telefone')) {
                document.getElementById('editar_telefone').value = talento.telefone || '';
            }
            
            if (document.getElementById('editar_cidade')) {
                document.getElementById('editar_cidade').value = talento.cidade || '';
            }
            
            if (document.getElementById('editar_estado')) {
                document.getElementById('editar_estado').value = talento.estado || '';
            }
            
            // Mostrar a foto atual do talento se existir
            if (talento.foto_perfil) {
                const previewContainer = document.getElementById('previewFotoContainer');
                const previewImg = document.getElementById('previewFoto');
                
                if (previewContainer && previewImg) {
                    previewImg.src = '<?php echo SITE_URL; ?>/uploads/perfil/' + talento.foto_perfil;
                    previewImg.alt = 'Foto atual de ' + talento.nome;
                    previewContainer.classList.remove('d-none');
                    
                    // Adicionar um texto indicando que esta é a foto atual
                    const fotoLabel = document.querySelector('label[for="editar_foto"]');
                    if (fotoLabel) {
                        fotoLabel.innerHTML = 'Foto de Perfil <small class="text-success">(Foto atual mostrada abaixo)</small>';
                    }
                    
                    // Armazenar o nome da foto atual no campo oculto
                    const fotoAtualInput = document.getElementById('foto_atual');
                    if (fotoAtualInput) {
                        fotoAtualInput.value = talento.foto_perfil;
                    }
                }
            } else {
                // Se não tiver foto, esconder o preview
                const previewContainer = document.getElementById('previewFotoContainer');
                if (previewContainer) {
                    previewContainer.classList.add('d-none');
                }
                
                // Resetar o texto do label
                const fotoLabel = document.querySelector('label[for="editar_foto"]');
                if (fotoLabel) {
                    fotoLabel.textContent = 'Foto de Perfil';
                }
                
                // Limpar o campo oculto
                const fotoAtualInput = document.getElementById('foto_atual');
                if (fotoAtualInput) {
                    fotoAtualInput.value = '';
                }
            }
            
            // Verificar campos específicos de talentos que podem existir
            console.log('Dados completos do talento:', talento);
            
            if (document.getElementById('editar_profissao')) {
                document.getElementById('editar_profissao').value = talento.profissao || '';
                console.log('Profissão:', talento.profissao);
            }
            
            if (document.getElementById('editar_experiencia')) {
                // Verificar se experiencia é um número ou texto
                if (talento.experiencia) {
                    // Tentar extrair apenas o número se for no formato "X anos"
                    const expMatch = talento.experiencia.match(/^(\d+)/);
                    if (expMatch) {
                        document.getElementById('editar_experiencia').value = expMatch[1];
                    } else {
                        document.getElementById('editar_experiencia').value = talento.experiencia;
                    }
                } else {
                    document.getElementById('editar_experiencia').value = '';
                }
                console.log('Experiência:', talento.experiencia);
            }
            
            if (document.getElementById('editar_apresentacao')) {
                document.getElementById('editar_apresentacao').value = talento.apresentacao || '';
                console.log('Apresentação:', talento.apresentacao);
            }
            
            if (document.getElementById('editar_mostrar_perfil')) {
                // Converter para número para garantir comparação correta
                const mostrarPerfil = parseInt(talento.mostrar_perfil) || 0;
                document.getElementById('editar_mostrar_perfil').checked = mostrarPerfil === 1;
                console.log('Mostrar perfil (original):', talento.mostrar_perfil);
                console.log('Mostrar perfil (convertido):', mostrarPerfil);
            }
            
            document.getElementById('editar_status').value = talento.status || 'ativo';
            
            // Atualizar título do modal
            document.getElementById('modalEditarTalentoLabel').textContent = 'Editar Talento: ' + (talento.nome || nome);
            
            console.log('Formulário preenchido com sucesso');
        })
        .catch(error => {
            // Fechar modal e mostrar erro
            const modalEditarTalento = bootstrap.Modal.getInstance(document.getElementById('modalEditarTalento'));
            modalEditarTalento.hide();
            console.error('Erro na requisição:', error);
            alert('Erro ao carregar dados do talento: ' + error.message);
        });
}

// Função para confirmar ação (bloquear, ativar, excluir)
function confirmarAcao(acao, id, nome) {
    let mensagem = '';
    let titulo = '';
    
    if (acao === 'bloquear') {
        mensagem = `Tem certeza que deseja bloquear o talento "${nome}"?`;
        titulo = 'Bloquear Talento';
    } else if (acao === 'ativar') {
        mensagem = `Tem certeza que deseja ativar o talento "${nome}"?`;
        titulo = 'Ativar Talento';
    } else if (acao === 'excluir') {
        mensagem = `ATENÇÃO: Esta ação não pode ser desfeita. Tem certeza que deseja excluir o talento "${nome}"?`;
        titulo = 'Excluir Talento';
    }
    
    document.getElementById('modalConfirmacaoLabel').textContent = titulo;
    document.getElementById('mensagem_confirmacao').textContent = mensagem;
    document.getElementById('acao_confirmacao').value = acao;
    document.getElementById('talento_id_confirmacao').value = id;
    
    const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
    modalConfirmacao.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // A inicialização do DataTable foi movida para o arquivo gerenciar_talentos.js
    // para centralizar toda a lógica JavaScript em um único lugar
});

// Botão para editar talento a partir do modal de visualização
document.getElementById('btnEditarTalentoDetalhe').addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const nome = this.getAttribute('data-nome');
    
    // Fechar modal de visualização
    const modalVisualizarTalento = bootstrap.Modal.getInstance(document.getElementById('modalVisualizarTalento'));
    modalVisualizarTalento.hide();
    
    // Abrir modal de edição
    editarTalento(id, nome);
});

// Função para alterar senha
function alterarSenha(id, nome) {
    // Limpar formulário
    document.getElementById('formAlterarSenha').reset();
    document.getElementById('usuario_id_senha').value = id;
    
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

// Adicionar evento de clique aos botões de ação
document.addEventListener('DOMContentLoaded', function() {
    const botoesAcao = document.querySelectorAll('[data-action]');
    
    botoesAcao.forEach(botao => {
        botao.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            const acao = this.getAttribute('data-action');
            
            if (acao === 'visualizar') {
                visualizarTalento(id, nome);
            } else if (acao === 'editar') {
                editarTalento(id, nome);
            } else if (acao === 'senha') {
                alterarSenha(id, nome);
            } else if (acao === 'bloquear' || acao === 'ativar' || acao === 'excluir') {
                confirmarAcao(acao, id, nome);
            }
        });
    });
});
</script>
