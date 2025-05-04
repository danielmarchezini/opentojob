<?php
// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Redirecionar para a página de login com mensagem
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=entrar");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a tabela webhooks existe
$tabela_existe = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'webhooks'
");

// Se a tabela não existir, mostrar mensagem e link em vez de redirecionar
if (!$tabela_existe) {
    echo '<div class="alert alert-warning">
        <p>A tabela de webhooks não existe. Por favor, <a href="' . SITE_URL . '/admin/pages/criar_tabela_webhooks.php" class="alert-link">clique aqui</a> para criá-la.</p>
    </div>';
} else {
    // Buscar todos os webhooks
    $webhooks = $db->fetchAll("SELECT * FROM webhooks ORDER BY tipo");

    // Verificar se há mensagem flash
    $flash_message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
    $flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : '';

    // Limpar mensagem flash após exibição
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);

    ?>
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Gerenciamento de Webhooks</h1>
        
        <?php if (!empty($flash_message)): ?>
        <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php endif; ?>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Webhooks Configurados</h6>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalInformacoes">
                    <i class="fas fa-info-circle"></i> Como Funciona
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="webhooksTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($webhooks as $webhook): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($webhook['nome']); ?></td>
                                <td><?php echo htmlspecialchars($webhook['tipo']); ?></td>
                                <td>
                                    <?php if (empty($webhook['url'])): ?>
                                    <span class="text-muted">Não configurado</span>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($webhook['url']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($webhook['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                        data-id="<?php echo $webhook['id']; ?>"
                                        data-nome="<?php echo htmlspecialchars($webhook['nome']); ?>"
                                        data-tipo="<?php echo htmlspecialchars($webhook['tipo']); ?>"
                                        data-url="<?php echo htmlspecialchars($webhook['url']); ?>"
                                        data-api-key="<?php echo htmlspecialchars($webhook['api_key']); ?>"
                                        data-ativo="<?php echo $webhook['ativo']; ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarWebhook">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    
                                    <button type="button" class="btn btn-info btn-sm" 
                                        data-id="<?php echo $webhook['id']; ?>"
                                        data-tipo="<?php echo htmlspecialchars($webhook['tipo']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalTestarWebhook">
                                        <i class="fas fa-vial"></i> Testar
                                    </button>
                                    
                                    <?php if ($webhook['ativo']): ?>
                                    <button type="button" class="btn btn-warning btn-sm"
                                        onclick="confirmarAcao('desativar', <?php echo $webhook['id']; ?>, '<?php echo htmlspecialchars($webhook['nome']); ?>')">
                                        <i class="fas fa-pause"></i> Desativar
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-success btn-sm"
                                        onclick="confirmarAcao('ativar', <?php echo $webhook['id']; ?>, '<?php echo htmlspecialchars($webhook['nome']); ?>')">
                                        <i class="fas fa-play"></i> Ativar
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Logs de Webhooks</h6>
            </div>
            <div class="card-body">
                <p>Visualize os últimos 20 logs de webhooks disparados:</p>
                
                <?php
                $logs = $db->fetchAll("
                    SELECT * FROM logs_sistema 
                    WHERE acao LIKE 'webhook%' 
                    ORDER BY data_hora DESC 
                    LIMIT 20
                ");
                ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="width: 20%">Data/Hora</th>
                                <th style="width: 20%">Ação</th>
                                <th style="width: 60%">Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Nenhum log encontrado</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['data_hora'])); ?></td>
                                <td><?php echo htmlspecialchars($log['acao']); ?></td>
                                <td><?php echo htmlspecialchars($log['descricao']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Webhook -->
    <div class="modal fade" id="modalEditarWebhook" tabindex="-1" aria-labelledby="modalEditarWebhookLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_webhook.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarWebhookLabel">Editar Webhook</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="webhook_id" id="editar_webhook_id">
                        
                        <div class="mb-3">
                            <label for="editar_nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="editar_nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_tipo" class="form-label">Tipo</label>
                            <input type="text" class="form-control" id="editar_tipo" name="tipo" readonly>
                            <small class="form-text text-muted">O tipo do webhook não pode ser alterado</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_url" class="form-label">URL do Webhook</label>
                            <input type="url" class="form-control" id="editar_url" name="url" placeholder="https://seu-servidor-n8n.com/webhook/...">
                            <small class="form-text text-muted">URL completa do endpoint no n8n que receberá as notificações</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_api_key" class="form-label">Chave de API</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="editar_api_key" name="api_key" required>
                                <button class="btn btn-outline-secondary" type="button" id="btn_gerar_api_key">Gerar Nova</button>
                            </div>
                            <small class="form-text text-muted">Chave de autenticação para o webhook</small>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="editar_ativo" name="ativo" value="1">
                            <label class="form-check-label" for="editar_ativo">Webhook Ativo</label>
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

    <!-- Modal Testar Webhook -->
    <div class="modal fade" id="modalTestarWebhook" tabindex="-1" aria-labelledby="modalTestarWebhookLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_webhook.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTestarWebhookLabel">Testar Webhook</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="testar">
                        <input type="hidden" name="webhook_id" id="testar_webhook_id">
                        
                        <p>Você está prestes a enviar um teste para o webhook <span id="testar_webhook_tipo" class="fw-bold"></span>.</p>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Este teste enviará dados fictícios para o endpoint configurado.
                        </div>
                        
                        <div id="dados_teste_container">
                            <!-- Será preenchido via JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Teste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmação -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?php echo SITE_URL; ?>/admin/pages/processar_webhook.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmar Ação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="acao" id="acao_confirmacao">
                        <input type="hidden" name="webhook_id" id="webhook_id_confirmacao">
                        
                        <p id="mensagem_confirmacao"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Informações -->
    <div class="modal fade" id="modalInformacoes" tabindex="-1" aria-labelledby="modalInformacoesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalInformacoesLabel">Como Funcionam os Webhooks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <h5>O que são Webhooks?</h5>
                    <p>Webhooks são "callbacks HTTP" que permitem a comunicação entre sistemas. Quando um evento ocorre no Open2W, um webhook envia dados para um URL externo, permitindo automações.</p>
                    
                    <h5>Tipos de Webhooks Disponíveis</h5>
                    <ul>
                        <li><strong>Cadastro de Talento</strong>: Disparado quando um talento é cadastrado ou atualizado</li>
                        <li><strong>Cadastro de Vaga</strong>: Disparado quando uma vaga é cadastrada ou atualizada</li>
                        <li><strong>Atualização de Status</strong>: Disparado quando o status de um usuário é alterado</li>
                    </ul>
                    
                    <h5>Integrando com o n8n</h5>
                    <ol>
                        <li>Configure um novo workflow no n8n</li>
                        <li>Adicione um nó "Webhook" como trigger</li>
                        <li>Copie a URL do webhook gerada pelo n8n</li>
                        <li>Cole a URL no campo correspondente nesta página</li>
                        <li>Ative o webhook</li>
                    </ol>
                    
                    <h5>Exemplos de Uso</h5>
                    <ul>
                        <li>Enviar mensagem de WhatsApp para talentos recém-cadastrados</li>
                        <li>Notificar grupos sobre novas vagas</li>
                        <li>Atualizar status de usuários automaticamente</li>
                        <li>Integrar com CRMs, planilhas ou outras ferramentas</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> Mantenha suas chaves de API seguras e não compartilhe as URLs de webhook.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar DataTables apenas para a tabela de webhooks
        if (typeof $.fn.DataTable !== 'undefined') {
            try {
                $('#webhooksTable').DataTable({
                    "language": {
                        "url": "/open2w/assets/js/pt-BR.json"
                    }
                });
                
                // Não inicializar DataTables para a tabela de logs para evitar erros
                console.log("DataTables inicializado apenas para a tabela de webhooks");
            } catch (e) {
                console.error("Erro ao inicializar DataTables:", e);
            }
        }
        
        // Preencher modal de edição
        $('#modalEditarWebhook').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            const tipo = button.getAttribute('data-tipo');
            const url = button.getAttribute('data-url');
            const apiKey = button.getAttribute('data-api-key');
            const ativo = button.getAttribute('data-ativo') === '1';
            
            document.getElementById('editar_webhook_id').value = id;
            document.getElementById('editar_nome').value = nome;
            document.getElementById('editar_tipo').value = tipo;
            document.getElementById('editar_url').value = url;
            document.getElementById('editar_api_key').value = apiKey;
            document.getElementById('editar_ativo').checked = ativo;
        });
        
        // Preencher modal de teste
        $('#modalTestarWebhook').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const tipo = button.getAttribute('data-tipo');
            
            document.getElementById('testar_webhook_id').value = id;
            document.getElementById('testar_webhook_tipo').textContent = tipo;
            
            // Preencher dados de teste com base no tipo
            const container = document.getElementById('dados_teste_container');
            let html = '<div class="alert alert-secondary"><pre>';
            
            switch (tipo) {
                case 'talento_cadastro':
                    html += JSON.stringify({
                        usuario_id: 123,
                        nome: 'Talento Teste',
                        email: 'talento@exemplo.com',
                        telefone: '(11) 98765-4321'
                    }, null, 2);
                    break;
                    
                case 'vaga_cadastro':
                    html += JSON.stringify({
                        vaga_id: 456,
                        titulo: 'Vaga Teste',
                        empresa: 'Empresa Teste',
                        cidade: 'São Paulo',
                        estado: 'SP'
                    }, null, 2);
                    break;
                    
                case 'atualizar_status':
                    html += JSON.stringify({
                        usuario_id: 789,
                        status: 'ativo',
                        tipo: 'talento'
                    }, null, 2);
                    break;
            }
            
            html += '</pre></div>';
            container.innerHTML = html;
        });
        
        // Gerar nova chave de API
        document.getElementById('btn_gerar_api_key').addEventListener('click', function() {
            // Gerar uma string aleatória de 32 caracteres hexadecimais
            const caracteres = 'abcdef0123456789';
            let apiKey = '';
            for (let i = 0; i < 32; i++) {
                apiKey += caracteres.charAt(Math.floor(Math.random() * caracteres.length));
            }
            
            document.getElementById('editar_api_key').value = apiKey;
        });
    });

    // Função para confirmar ação
    function confirmarAcao(acao, id, nome) {
        let mensagem = '';
        let titulo = '';
        
        if (acao === 'ativar') {
            mensagem = `Tem certeza que deseja ativar o webhook "${nome}"?`;
            titulo = 'Ativar Webhook';
        } else if (acao === 'desativar') {
            mensagem = `Tem certeza que deseja desativar o webhook "${nome}"?`;
            titulo = 'Desativar Webhook';
        }
        
        // Atualizar o conteúdo do modal
        document.getElementById('modalConfirmacaoLabel').textContent = titulo;
        document.getElementById('mensagem_confirmacao').textContent = mensagem;
        document.getElementById('acao_confirmacao').value = acao;
        document.getElementById('webhook_id_confirmacao').value = id;
        
        // Mostrar o modal usando Bootstrap 5
        var myModal = new bootstrap.Modal(document.getElementById('modalConfirmacao'), {
            keyboard: false
        });
        myModal.show();
    }
    </script>
    <?php } ?>
