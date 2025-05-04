<?php
// Verificar se o usuário está logado e é um talento
if (!Auth::checkUserType('talento') && !Auth::checkUserType('admin')) {
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Definir filtros
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : 'recebidas';
$lida_filtro = isset($_GET['lida']) ? $_GET['lida'] : '';
$periodo_filtro = isset($_GET['periodo']) ? $_GET['periodo'] : '';

// Construir condição SQL para filtros
$condicao = "";
$params = [];

if ($tipo_filtro == 'recebidas') {
    $condicao = "m.destinatario_id = :usuario_id";
    $params['usuario_id'] = $usuario_id;
} else {
    $condicao = "m.remetente_id = :usuario_id";
    $params['usuario_id'] = $usuario_id;
}

if ($lida_filtro !== '') {
    $condicao .= " AND m.lida = :lida";
    $params['lida'] = ($lida_filtro == 'sim') ? 1 : 0;
}

if (!empty($periodo_filtro)) {
    $data_inicio = '';
    switch ($periodo_filtro) {
        case 'hoje':
            $data_inicio = date('Y-m-d');
            break;
        case 'semana':
            $data_inicio = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'mes':
            $data_inicio = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'trimestre':
            $data_inicio = date('Y-m-d', strtotime('-90 days'));
            break;
    }
    
    if (!empty($data_inicio)) {
        $condicao .= " AND m.data_envio >= :data_inicio";
        $params['data_inicio'] = $data_inicio;
    }
}

// Obter mensagens
try {
    if ($tipo_filtro == 'recebidas') {
        $mensagens = $db->fetchAll("
            SELECT m.*, u.nome as remetente_nome, u.tipo as remetente_tipo,
                   e.razao_social as empresa_razao_social
            FROM mensagens m
            JOIN usuarios u ON m.remetente_id = u.id
            LEFT JOIN empresas e ON u.id = e.usuario_id
            WHERE $condicao
            ORDER BY m.data_envio DESC
        ", $params);
    } else {
        $mensagens = $db->fetchAll("
            SELECT m.*, u.nome as destinatario_nome, u.tipo as destinatario_tipo,
                   e.razao_social as empresa_razao_social
            FROM mensagens m
            JOIN usuarios u ON m.destinatario_id = u.id
            LEFT JOIN empresas e ON u.id = e.usuario_id
            WHERE $condicao
            ORDER BY m.data_envio DESC
        ", $params);
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar mensagens: " . $e->getMessage());
    $_SESSION['flash_message'] = "Erro ao carregar mensagens: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $mensagens = [];
}

// Obter contatos (empresas) para enviar mensagens
try {
    $contatos = $db->fetchAll("
        SELECT u.id, u.nome, e.razao_social
        FROM usuarios u
        LEFT JOIN empresas e ON u.id = e.usuario_id
        WHERE u.tipo = 'empresa' AND u.status = 'ativo'
        ORDER BY u.nome ASC
    ");
} catch (PDOException $e) {
    error_log("Erro ao buscar contatos: " . $e->getMessage());
    $contatos = [];
}

// Função para formatar data
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

// Contar mensagens não lidas
try {
    $mensagens_nao_lidas = $db->fetchRow("
        SELECT COUNT(*) as total
        FROM mensagens
        WHERE destinatario_id = :usuario_id AND lida = 0
    ", ['usuario_id' => $usuario_id]);
    
    $total_nao_lidas = $mensagens_nao_lidas ? $mensagens_nao_lidas['total'] : 0;
} catch (PDOException $e) {
    error_log("Erro ao contar mensagens não lidas: " . $e->getMessage());
    $total_nao_lidas = 0;
}

// Processar envio de mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensagem'])) {
    $destinatario_id = isset($_POST['destinatario_id']) ? (int)$_POST['destinatario_id'] : 0;
    $assunto = isset($_POST['assunto']) ? trim($_POST['assunto']) : '';
    $conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : '';
    
    $erros = [];
    
    if ($destinatario_id <= 0) {
        $erros[] = "Selecione um destinatário válido.";
    }
    
    if (empty($assunto)) {
        $erros[] = "O assunto é obrigatório.";
    }
    
    if (empty($conteudo)) {
        $erros[] = "O conteúdo da mensagem é obrigatório.";
    }
    
    if (empty($erros)) {
        try {
            $db->execute("
                INSERT INTO mensagens (remetente_id, destinatario_id, assunto, conteudo, data_envio, lida)
                VALUES (:remetente_id, :destinatario_id, :assunto, :conteudo, NOW(), 0)
            ", [
                'remetente_id' => $usuario_id,
                'destinatario_id' => $destinatario_id,
                'assunto' => $assunto,
                'conteudo' => $conteudo
            ]);
            
            $_SESSION['flash_message'] = "Mensagem enviada com sucesso!";
            $_SESSION['flash_type'] = "success";
            
            // Redirecionar para a página de mensagens enviadas
            echo "<script>window.location.href = '" . SITE_URL . "/?route=mensagens_talento&tipo=enviadas';</script>";
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            $_SESSION['flash_message'] = "Erro ao enviar mensagem: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Erro ao enviar mensagem: " . implode(" ", $erros);
        $_SESSION['flash_type'] = "danger";
    }
}

// Marcar mensagem como lida
if (isset($_GET['marcar_lida']) && isset($_GET['id'])) {
    $mensagem_id = (int)$_GET['id'];
    
    try {
        $db->execute("
            UPDATE mensagens
            SET lida = 1
            WHERE id = :id AND destinatario_id = :usuario_id
        ", [
            'id' => $mensagem_id,
            'usuario_id' => $usuario_id
        ]);
        
        $_SESSION['flash_message'] = "Mensagem marcada como lida.";
        $_SESSION['flash_type'] = "success";
        
        // Redirecionar para a mesma página
        echo "<script>window.location.href = '" . SITE_URL . "/?route=mensagens_talento&tipo=" . $tipo_filtro . "';</script>";
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao marcar mensagem como lida: " . $e->getMessage());
        $_SESSION['flash_message'] = "Erro ao marcar mensagem como lida: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Mensagens</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p>Gerencie suas mensagens e entre em contato com empresas.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-primary" onclick="abrirNovaMensagem()">
                                <i class="fas fa-plus-circle me-2"></i>Nova Mensagem
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Pastas</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/?route=mensagens_talento&tipo=recebidas" 
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $tipo_filtro == 'recebidas' ? 'active' : ''; ?>">
                        <div>
                            <i class="fas fa-inbox me-2"></i>Recebidas
                        </div>
                        <?php if ($total_nao_lidas > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $total_nao_lidas; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/?route=mensagens_talento&tipo=enviadas" 
                       class="list-group-item list-group-item-action <?php echo $tipo_filtro == 'enviadas' ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane me-2"></i>Enviadas
                    </a>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo SITE_URL; ?>/">
                        <input type="hidden" name="route" value="mensagens_talento">
                        <input type="hidden" name="tipo" value="<?php echo $tipo_filtro; ?>">
                        
                        <?php if ($tipo_filtro == 'recebidas'): ?>
                            <div class="mb-3">
                                <label for="lida" class="form-label">Status</label>
                                <select class="form-select" id="lida" name="lida">
                                    <option value="">Todas</option>
                                    <option value="nao" <?php echo $lida_filtro == 'nao' ? 'selected' : ''; ?>>Não lidas</option>
                                    <option value="sim" <?php echo $lida_filtro == 'sim' ? 'selected' : ''; ?>>Lidas</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="periodo" class="form-label">Período</label>
                            <select class="form-select" id="periodo" name="periodo">
                                <option value="">Todos</option>
                                <option value="hoje" <?php echo $periodo_filtro == 'hoje' ? 'selected' : ''; ?>>Hoje</option>
                                <option value="semana" <?php echo $periodo_filtro == 'semana' ? 'selected' : ''; ?>>Última semana</option>
                                <option value="mes" <?php echo $periodo_filtro == 'mes' ? 'selected' : ''; ?>>Último mês</option>
                                <option value="trimestre" <?php echo $periodo_filtro == 'trimestre' ? 'selected' : ''; ?>>Último trimestre</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Lista de Mensagens -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <?php echo $tipo_filtro == 'recebidas' ? 'Mensagens Recebidas' : 'Mensagens Enviadas'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($mensagens)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Nenhuma mensagem encontrada.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($mensagens as $mensagem): ?>
                                <?php 
                                    $is_unread = $tipo_filtro == 'recebidas' && $mensagem['lida'] == 0;
                                    $nome_contato = $tipo_filtro == 'recebidas' ? 
                                        ($mensagem['empresa_razao_social'] ?: $mensagem['remetente_nome']) : 
                                        ($mensagem['empresa_razao_social'] ?: $mensagem['destinatario_nome']);
                                ?>
                                <div class="list-group-item list-group-item-action <?php echo $is_unread ? 'bg-light' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php if ($is_unread): ?>
                                                <span class="badge bg-primary me-2">Nova</span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($mensagem['assunto']); ?>
                                        </h6>
                                        <small><?php echo formatarData($mensagem['data_envio']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <strong><?php echo $tipo_filtro == 'recebidas' ? 'De:' : 'Para:'; ?></strong> 
                                        <?php echo htmlspecialchars($nome_contato); ?>
                                    </p>
                                    <small>
                                        <?php echo htmlspecialchars(substr($mensagem['conteudo'], 0, 100)) . (strlen($mensagem['conteudo']) > 100 ? '...' : ''); ?>
                                    </small>
                                    <div class="mt-2 text-end">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="visualizarMensagem(<?php echo $mensagem['id']; ?>)">
                                            <i class="fas fa-envelope-open-text me-1"></i> Ler mensagem
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Mensagem -->
<div class="modal fade" id="modalNovaMensagem" tabindex="-1" aria-labelledby="modalNovaMensagemLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNovaMensagemLabel">Nova Mensagem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formNovaMensagem" method="post" action="<?php echo SITE_URL; ?>/?route=mensagens_talento">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="destinatario_id" class="form-label">Destinatário</label>
                        <select class="form-select" id="destinatario_id" name="destinatario_id" required>
                            <option value="">Selecione um destinatário</option>
                            <?php foreach ($contatos as $contato): ?>
                                <option value="<?php echo $contato['id']; ?>">
                                    <?php echo htmlspecialchars($contato['razao_social'] ?: $contato['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assunto" class="form-label">Assunto</label>
                        <input type="text" class="form-control" id="assunto" name="assunto" required>
                    </div>
                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Mensagem</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="enviar_mensagem" class="btn btn-primary">Enviar Mensagem</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Mensagem -->
<div class="modal fade" id="modalVisualizarMensagem" tabindex="-1" aria-labelledby="modalVisualizarMensagemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalVisualizarMensagemLabel">Detalhes da Mensagem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesMensagem">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando detalhes da mensagem...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <div id="acoesMensagem"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Função para limpar o formulário de nova mensagem quando o modal é fechado
document.getElementById('modalNovaMensagem').addEventListener('hidden.bs.modal', function () {
    // Limpar campos do formulário
    document.getElementById('formNovaMensagem').reset();
    
    // Reativar o campo de destinatário
    const selectDestinatario = document.getElementById('destinatario_id');
    selectDestinatario.disabled = false;
    
    // Remover campos hidden que possam ter sido adicionados
    const hiddenInputs = document.querySelectorAll('input[type="hidden"][name="destinatario_id"]');
    hiddenInputs.forEach(input => input.remove());
});

// Função para abrir o modal de nova mensagem
function abrirNovaMensagem() {
    // Limpar campos do formulário
    document.getElementById('formNovaMensagem').reset();
    
    // Reativar o campo de destinatário
    const selectDestinatario = document.getElementById('destinatario_id');
    selectDestinatario.disabled = false;
    
    // Remover campos hidden que possam ter sido adicionados
    const hiddenInputs = document.querySelectorAll('input[type="hidden"][name="destinatario_id"]');
    hiddenInputs.forEach(input => input.remove());
    
    // Abrir o modal
    const modalNovaMensagem = new bootstrap.Modal(document.getElementById('modalNovaMensagem'));
    modalNovaMensagem.show();
}

// Função para visualizar detalhes da mensagem
function visualizarMensagem(id) {
    // Mostrar modal com loading
    const modal = new bootstrap.Modal(document.getElementById('modalVisualizarMensagem'));
    modal.show();
    
    // Mostrar loading
    document.getElementById('detalhesMensagem').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando detalhes da mensagem...</p>
        </div>
    `;
    
    // Limpar área de ações
    document.getElementById('acoesMensagem').innerHTML = '';
    
    // Carregar dados da mensagem via AJAX
    fetch('<?php echo SITE_URL; ?>/?route=api_mensagem_detalhe&id=' + id)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const mensagem = data.data.mensagem;
                const tipoAtual = '<?php echo $tipo_filtro; ?>';
                
                // Formatar data
                const dataFormatada = new Date(mensagem.data_envio).toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Determinar nome do remetente/destinatário
                const nomeRemetente = mensagem.remetente_razao_social || mensagem.remetente_nome;
                const nomeDestinatario = mensagem.destinatario_razao_social || mensagem.destinatario_nome;
                
                // Atualizar título do modal
                document.getElementById('modalVisualizarMensagemLabel').textContent = mensagem.assunto;
                
                // Preencher detalhes da mensagem
                document.getElementById('detalhesMensagem').innerHTML = `
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>De:</strong> ${nomeRemetente}
                                </div>
                                <div class="col-md-6 text-end">
                                    <strong>Data:</strong> ${dataFormatada}
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <strong>Para:</strong> ${nomeDestinatario}
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <strong>Assunto:</strong> ${mensagem.assunto}
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="message-content">
                                ${mensagem.conteudo.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                `;
                
                // Adicionar botões de ação
                const acoesMensagem = document.getElementById('acoesMensagem');
                
                // Se for uma mensagem recebida e não lida, adicionar botão para marcar como lida
                if (tipoAtual === 'recebidas' && mensagem.lida === 0) {
                    acoesMensagem.innerHTML = `
                        <a href="<?php echo SITE_URL; ?>/?route=mensagens_talento&tipo=${tipoAtual}&marcar_lida=1&id=${mensagem.id}" 
                           class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Marcar como lida
                        </a>
                    `;
                }
                
                // Adicionar botão de resposta se for uma mensagem recebida
                if (tipoAtual === 'recebidas') {
                    const btnResponder = document.createElement('button');
                    btnResponder.className = 'btn btn-success ms-2';
                    btnResponder.innerHTML = '<i class="fas fa-reply me-2"></i>Responder';
                    btnResponder.onclick = function() {
                        // Fechar modal atual
                        modal.hide();
                        
                        // Abrir modal de nova mensagem
                        setTimeout(() => {
                            const modalNovaMensagem = new bootstrap.Modal(document.getElementById('modalNovaMensagem'));
                            
                            // Preencher destinatário e desabilitar o campo para evitar mudanças
                            const selectDestinatario = document.getElementById('destinatario_id');
                            selectDestinatario.value = mensagem.remetente_id;
                            selectDestinatario.disabled = true;
                            
                            // Adicionar campo hidden para garantir que o valor seja enviado mesmo com o select desabilitado
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'destinatario_id';
                            hiddenInput.value = mensagem.remetente_id;
                            document.getElementById('formNovaMensagem').appendChild(hiddenInput);
                            
                            // Preencher assunto com "Re: " + assunto original
                            const assuntoOriginal = mensagem.assunto;
                            document.getElementById('assunto').value = assuntoOriginal.startsWith('Re:') ? assuntoOriginal : 'Re: ' + assuntoOriginal;
                            
                            // Focar no campo de conteúdo
                            modalNovaMensagem.show();
                            setTimeout(() => {
                                document.getElementById('conteudo').focus();
                            }, 500);
                        }, 500);
                    };
                    
                    acoesMensagem.appendChild(btnResponder);
                }
                
                // Se a mensagem não estava lida, marcar como lida via AJAX
                if (tipoAtual === 'recebidas' && mensagem.lida === 0) {
                    fetch('<?php echo SITE_URL; ?>/?route=api_marcar_mensagem_lida&id=' + id, {
                        method: 'POST'
                    }).then(response => {
                        if (response.ok) {
                            console.log('Mensagem marcada como lida');
                        }
                    }).catch(error => {
                        console.error('Erro ao marcar mensagem como lida:', error);
                    });
                }
            } else {
                document.getElementById('detalhesMensagem').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>${data.message || 'Erro ao carregar detalhes da mensagem.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('detalhesMensagem').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>Erro ao carregar detalhes: ${error.message}
                </div>
            `;
        });
}
</script>
