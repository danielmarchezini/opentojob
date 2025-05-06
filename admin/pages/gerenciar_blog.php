<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se as tabelas existem e criá-las se necessário
try {
    // Verificar se a tabela categorias_blog existe
    $tabela_categorias_existe = $db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'categorias_blog'");
    
    // Verificar se a tabela artigos_blog existe
    $tabela_artigos_existe = $db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'artigos_blog'");
    
    // Se as tabelas não existirem, criar as tabelas do blog
    if (!$tabela_categorias_existe || !$tabela_artigos_existe) {
        // Ler o conteúdo do arquivo SQL
        $sql_file = file_get_contents(dirname(dirname(dirname(__FILE__))) . '/sql/criar_tabelas_blog.sql');
        
        // Dividir o arquivo em comandos SQL individuais
        $sql_commands = explode(';', $sql_file);
        
        // Executar cada comando SQL
        foreach ($sql_commands as $sql) {
            $sql = trim($sql);
            if (!empty($sql)) {
                $db->query($sql);
            }
        }
        
        // Definir mensagem de sucesso
        $_SESSION['flash_message'] = "Tabelas do blog criadas com sucesso!";
        $_SESSION['flash_type'] = "success";
    }
} catch (Exception $e) {
    // Registrar erro
    error_log("Erro ao verificar/criar tabelas do blog: " . $e->getMessage());
}

$artigos = [];
$categorias = [];

// Verificar se há artigos no banco de dados
try {
    $total_artigos = $db->fetchColumn("SELECT COUNT(*) FROM artigos_blog");
    
    // Se não houver artigos, inserir um artigo de exemplo
    if ($total_artigos == 0) {
        // Verificar se o administrador existe
        $admin_id = $db->fetchColumn("SELECT id FROM usuarios WHERE tipo = 'admin' LIMIT 1");
        if (!$admin_id) {
            $admin_id = 1; // ID padrão se não encontrar
        }
        
        // Verificar se existe pelo menos uma categoria
        $categoria_id = $db->fetchColumn("SELECT id FROM blog_categorias LIMIT 1");
        if (!$categoria_id) {
            $categoria_id = 1; // ID padrão se não encontrar
        }
        
        // Inserir artigo de exemplo
        $db->query("INSERT INTO artigos_blog (
            titulo, slug, resumo, conteudo, autor_id, categoria_id, data_publicacao, status
        ) VALUES (
            'Bem-vindo ao Blog do OpenToJob', 
            'bem-vindo-ao-blog-do-opentojob',
            'Conheça o blog do OpenToJob, onde compartilhamos dicas e informações valiosas para talentos prontos e empresas.',
            '<p>Bem-vindo ao blog oficial do OpenToJob!</p><p>Aqui você encontrará conteúdos relevantes para sua carreira e busca por oportunidades.</p><p>Fique atento às nossas publicações semanais!</p>',
            :admin_id,
            :categoria_id,
            NOW(),
            'publicado'
        )", [
            'admin_id' => $admin_id,
            'categoria_id' => $categoria_id
        ]);
        
        $_SESSION['flash_message'] = "Artigo de exemplo criado com sucesso!";
        $_SESSION['flash_type'] = "success";
    }
    
    // Obter lista de artigos do blog com LEFT JOIN para ser mais tolerante
    $artigos = $db->fetchAll("
        SELECT a.*, c.nome as categoria_nome, u.nome as autor_nome
        FROM artigos_blog a
        LEFT JOIN categorias_blog c ON a.categoria_id = c.id
        LEFT JOIN usuarios u ON a.autor_id = u.id
        ORDER BY a.data_publicacao DESC
    ");
    
    // Obter lista de categorias para o formulário de adicionar artigo
    $categorias = $db->fetchAll("
        SELECT id, nome
        FROM categorias_blog
        ORDER BY nome ASC
    ");
    
    // Adicionar mensagem de diagnóstico
    if (empty($artigos)) {
        $_SESSION['flash_message'] = "Não foram encontrados artigos no banco de dados. Verifique se as tabelas foram criadas corretamente.";
        $_SESSION['flash_type'] = "warning";
    }
} catch (Exception $e) {
    // Registrar erro e exibir mensagem
    $erro_msg = "Erro ao buscar artigos/categorias: " . $e->getMessage();
    error_log($erro_msg);
    $_SESSION['flash_message'] = $erro_msg;
    $_SESSION['flash_type'] = "danger";
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Blog</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Blog</li>
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

    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarArtigo">
                <i class="fas fa-plus me-1"></i> Novo Artigo
            </button>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalGerenciarCategorias">
                <i class="fas fa-tags me-1"></i> Gerenciar Categorias
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-newspaper me-1"></i>
            Artigos do Blog
        </div>
        <div class="card-body">
            <?php if (empty($artigos)): ?>
                <div class="alert alert-info">
                    Nenhum artigo encontrado. Clique em "Novo Artigo" para começar.
                </div>
            <?php else: ?>
                <table id="artigosTable" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Autor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artigos as $artigo): ?>
                            <tr>
                                <td><?php echo $artigo['id']; ?></td>
                                <td><?php echo htmlspecialchars($artigo['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($artigo['categoria_nome']); ?></td>
                                <td><?php echo htmlspecialchars($artigo['autor_nome']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($artigo['data_publicacao'])); ?></td>
                                <td>
                                    <?php if ($artigo['status'] == 'publicado'): ?>
                                        <span class="badge bg-success">Publicado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Rascunho</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo SITE_URL; ?>/?route=artigo&id=<?php echo $artigo['id']; ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="editarArtigo(<?php echo $artigo['id']; ?>, '<?php echo addslashes($artigo['titulo']); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmarAcao('excluir', <?php echo $artigo['id']; ?>, '<?php echo addslashes($artigo['titulo']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Adicionar Artigo -->
<div class="modal fade" id="modalAdicionarArtigo" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarArtigoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarArtigoLabel">Adicionar Novo Artigo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/pages/processar_blog.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria_id">Categoria</label>
                        <select class="form-control" id="categoria_id" name="categoria_id" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_descricao">Meta Descrição (Resumo)</label>
                        <textarea class="form-control" id="meta_descricao" name="meta_descricao" rows="2"></textarea>
                        <small class="form-text text-muted">Breve descrição do artigo para SEO e compartilhamento em redes sociais.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Palavras-chave</label>
                        <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="Palavras-chave separadas por vírgula">
                        <small class="form-text text-muted">Palavras-chave relacionadas ao artigo, separadas por vírgula.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="conteudo">Conteúdo</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="10" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagem">Imagem de Destaque</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="imagem" name="imagem">
                            <label class="custom-file-label" for="imagem">Escolher arquivo</label>
                        </div>
                        <small class="form-text text-muted">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="rascunho">Rascunho</option>
                            <option value="publicado">Publicado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Artigo -->
<div class="modal fade" id="modalEditarArtigo" tabindex="-1" role="dialog" aria-labelledby="modalEditarArtigoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarArtigoLabel">Editar Artigo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/pages/processar_blog.php" method="post" enctype="multipart/form-data" id="formEditarArtigo">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="artigo_id" id="editar_artigo_id">
                    
                    <div class="form-group">
                        <label for="editar_titulo">Título</label>
                        <input type="text" class="form-control" id="editar_titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_categoria_id">Categoria</label>
                        <select class="form-control" id="editar_categoria_id" name="categoria_id" required>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_meta_descricao">Meta Descrição (Resumo)</label>
                        <textarea class="form-control" id="editar_meta_descricao" name="meta_descricao" rows="2"></textarea>
                        <small class="form-text text-muted">Breve descrição do artigo para SEO e compartilhamento em redes sociais.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_meta_keywords">Palavras-chave</label>
                        <input type="text" class="form-control" id="editar_meta_keywords" name="meta_keywords" placeholder="Palavras-chave separadas por vírgula">
                        <small class="form-text text-muted">Palavras-chave relacionadas ao artigo, separadas por vírgula.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_conteudo">Conteúdo</label>
                        <textarea class="form-control" id="editar_conteudo" name="conteudo" rows="10" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_imagem">Nova Imagem de Destaque (opcional)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="editar_imagem" name="imagem">
                            <label class="custom-file-label" for="editar_imagem">Escolher arquivo</label>
                        </div>
                        <small class="form-text text-muted">Deixe em branco para manter a imagem atual. Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editar_status">Status</label>
                        <select class="form-control" id="editar_status" name="status" required>
                            <option value="rascunho">Rascunho</option>
                            <option value="publicado">Publicado</option>
                        </select>
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

<!-- Modal Gerenciar Categorias -->
<div class="modal fade" id="modalGerenciarCategorias" tabindex="-1" role="dialog" aria-labelledby="modalGerenciarCategoriasLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGerenciarCategoriasLabel">Gerenciar Categorias</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_blog.php" method="post" class="mb-4">
                    <input type="hidden" name="acao" value="adicionar_categoria">
                    <div class="form-group">
                        <label for="nome_categoria">Nova Categoria</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="nome_categoria" name="nome" required placeholder="Nome da categoria">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">Adicionar</button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo $categoria['id']; ?></td>
                                <td><?php echo $categoria['nome']; ?></td>
                                <td>
                                    <form action="<?php echo SITE_URL; ?>/admin/pages/processar_blog.php" method="post" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.')">
                                        <input type="hidden" name="acao" value="excluir_categoria">
                                        <input type="hidden" name="categoria_id" value="<?php echo $categoria['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
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
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_blog.php" method="post">
                    <input type="hidden" name="acao" id="acao_confirmacao">
                    <input type="hidden" name="artigo_id" id="artigo_id_confirmacao">
                    <button type="submit" class="btn btn-danger">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Incluir arquivo de funções auxiliares -->
<script src="<?php echo SITE_URL; ?>/js/admin_helpers.js"></script>

<script>
// Função para visualizar artigo
function visualizarArtigo(id, titulo) {
    window.open('<?php echo SITE_URL; ?>/?route=artigo_detalhe&id=' + id, '_blank');
}

// Função para editar artigo
function editarArtigo(id, titulo) {
    console.log('Editando artigo ID:', id, 'Título:', titulo);
    
    // Mostrar modal com mensagem de carregamento
    const modalEditarArtigo = new bootstrap.Modal(document.getElementById('modalEditarArtigo'));
    modalEditarArtigo.show();
    document.getElementById('modalEditarArtigoLabel').textContent = 'Carregando dados...';
    
    // Carregar dados do artigo via AJAX usando a nova API simplificada
    fetch('<?php echo SITE_URL; ?>/admin/api_artigo.php?id=' + id)
        .then(response => {
            console.log('Status da resposta (editar):', response.status);
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos (editar):', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erro desconhecido');
            }
            
            if (!data.data || !data.data.artigo) {
                throw new Error('Dados do artigo não encontrados na resposta');
            }
            
            const artigo = data.data.artigo;
            console.log('Dados do artigo para edição:', artigo);
            
            // Preencher o formulário com os dados do artigo
            document.getElementById('editar_artigo_id').value = artigo.id;
            document.getElementById('editar_titulo').value = artigo.titulo || '';
            document.getElementById('editar_categoria_id').value = artigo.categoria_id || '';
            document.getElementById('editar_meta_descricao').value = artigo.meta_descricao || '';
            document.getElementById('editar_meta_keywords').value = artigo.meta_keywords || '';
            document.getElementById('editar_conteudo').value = artigo.conteudo || '';
            document.getElementById('editar_status').value = artigo.status || 'rascunho';
            
            // Atualizar título do modal
            document.getElementById('modalEditarArtigoLabel').textContent = 'Editar Artigo: ' + artigo.titulo;
        })
        .catch(error => {
            console.error('Erro ao carregar dados do artigo:', error);
            
            // Exibir erro no modal
            const form = document.getElementById('formEditarArtigo');
            if (form) {
                form.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                        Erro ao carregar dados do artigo: ${error.message}
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                `;
            } else {
                alert('Erro ao carregar dados do artigo: ' + error.message);
            }
        });
}

// Função para confirmar ação (publicar, despublicar, excluir)
function confirmarAcao(acao, id, titulo) {
    let mensagem = '';
    let tituloModal = '';
    
    if (acao === 'publicar') {
        mensagem = `Tem certeza que deseja publicar o artigo "${titulo}"?`;
        tituloModal = 'Publicar Artigo';
    } else if (acao === 'despublicar') {
        mensagem = `Tem certeza que deseja despublicar o artigo "${titulo}"?`;
        tituloModal = 'Despublicar Artigo';
    } else if (acao === 'excluir') {
        mensagem = `Tem certeza que deseja excluir o artigo "${titulo}"? Esta ação não pode ser desfeita.`;
        tituloModal = 'Excluir Artigo';
    } else {
        console.error('Ação inválida:', acao);
        return;
    }
    
    if (confirm(mensagem)) {
        // Criar formulário dinâmico para enviar a ação
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo SITE_URL; ?>/admin/processar_blog.php';
        
        const inputAcao = document.createElement('input');
        inputAcao.type = 'hidden';
        inputAcao.name = 'acao';
        inputAcao.value = acao;
        form.appendChild(inputAcao);
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'artigo_id';
        inputId.value = id;
        form.appendChild(inputId);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Função para excluir artigo
function excluirArtigo(id, titulo) {
    if (confirm(`Tem certeza que deseja excluir o artigo "${titulo}"? Esta ação não pode ser desfeita.`)) {
        // Criar formulário dinâmico para enviar a ação
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo SITE_URL; ?>/admin/processar_blog.php';
        
        const inputAcao = document.createElement('input');
        inputAcao.type = 'hidden';
        inputAcao.name = 'acao';
        inputAcao.value = 'excluir';
        form.appendChild(inputAcao);
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'artigo_id';
        inputId.value = id;
        form.appendChild(inputId);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Função para pesquisar artigos na tabela
document.getElementById('pesquisarArtigo').addEventListener('keyup', function() {
    const termo = this.value.toLowerCase();
    const tabela = document.querySelector('table tbody');
    const linhas = tabela.querySelectorAll('tr');
    
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        if (texto.includes(termo)) {
            linha.style.display = '';
        } else {
            linha.style.display = 'none';
        }
    });
});

// Mostrar nome do arquivo selecionado no input de arquivo
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    const fileName = e.target.files[0].name;
    const label = e.target.nextElementSibling;
    label.textContent = fileName;
});
</script>
