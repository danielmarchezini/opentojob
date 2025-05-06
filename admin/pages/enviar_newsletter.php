<?php
// Verificar permissões
if (!Auth::checkUserType('admin')) {
    $_SESSION['flash_message'] = "Você não tem permissão para acessar esta página.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=painel_admin");
    exit;
}

// Obter lista de inscritos na newsletter
$db = Database::getInstance();
try {
    $inscritos = $db->fetchAll("
        SELECT id, email, nome, data_inscricao, status
        FROM newsletter_inscritos
        ORDER BY data_inscricao DESC
    ");
    
    // Contar inscritos ativos
    $inscritos_ativos = 0;
    foreach ($inscritos as $inscrito) {
        if ($inscrito['status'] == 'ativo') {
            $inscritos_ativos++;
        }
    }
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Erro ao carregar inscritos: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $inscritos = [];
    $inscritos_ativos = 0;
}

// Obter modelos de email
try {
    $modelos = $db->fetchAll("
        SELECT id, nome, assunto
        FROM modelos_email
        WHERE tipo = 'newsletter'
        ORDER BY nome ASC
    ");
} catch (PDOException $e) {
    $modelos = [];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Enviar Newsletter</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=gerenciar_newsletter">Gerenciar Newsletter</a></li>
        <li class="breadcrumb-item active">Enviar Newsletter</li>
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

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo count($inscritos); ?></h4>
                            <div>Total de Inscritos</div>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo $inscritos_ativos; ?></h4>
                            <div>Inscritos Ativos</div>
                        </div>
                        <div>
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de Envio de Newsletter -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-paper-plane me-1"></i>
            Enviar Newsletter
        </div>
        <div class="card-body">
            <form id="formEnviarNewsletter" action="<?php echo SITE_URL; ?>/admin/processar_newsletter.php" method="post">
                <input type="hidden" name="acao" value="enviar_newsletter">
                
                <div class="mb-3">
                    <label for="destinatarios" class="form-label">Destinatários</label>
                    <select class="form-select" id="destinatarios" name="destinatarios" required>
                        <option value="">Selecione os destinatários</option>
                        <option value="todos">Todos os inscritos ativos (<?php echo $inscritos_ativos; ?>)</option>
                        <option value="selecionar">Selecionar destinatários manualmente</option>
                    </select>
                </div>
                
                <div id="destinatarios_manuais" class="mb-3" style="display: none;">
                    <label class="form-label">Selecione os destinatários</label>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selecionar_todos">
                                            <label class="form-check-label" for="selecionar_todos">Selecionar Todos</label>
                                        </div>
                                    </th>
                                    <th>Email</th>
                                    <th>Nome</th>
                                    <th>Data de Inscrição</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inscritos)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum inscrito encontrado</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inscritos as $inscrito): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input inscrito-checkbox" type="checkbox" name="inscritos[]" value="<?php echo $inscrito['id']; ?>" id="inscrito_<?php echo $inscrito['id']; ?>" <?php echo $inscrito['status'] == 'ativo' ? '' : 'disabled'; ?>>
                                                    <label class="form-check-label" for="inscrito_<?php echo $inscrito['id']; ?>"></label>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($inscrito['email']); ?></td>
                                            <td><?php echo htmlspecialchars($inscrito['nome'] ?? 'Não informado'); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($inscrito['data_inscricao'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $inscrito['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                                                    <?php echo $inscrito['status'] == 'ativo' ? 'Ativo' : 'Inativo'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="tipo_conteudo" class="form-label">Tipo de Conteúdo</label>
                    <select class="form-select" id="tipo_conteudo" name="tipo_conteudo" required>
                        <option value="">Selecione o tipo de conteúdo</option>
                        <option value="modelo">Usar modelo de email</option>
                        <option value="personalizado">Criar conteúdo personalizado</option>
                    </select>
                </div>
                
                <div id="modelo_email_div" class="mb-3" style="display: none;">
                    <label for="modelo_id" class="form-label">Modelo de Email</label>
                    <select class="form-select" id="modelo_id" name="modelo_id">
                        <option value="">Selecione um modelo</option>
                        <?php foreach ($modelos as $modelo): ?>
                            <option value="<?php echo $modelo['id']; ?>"><?php echo htmlspecialchars($modelo['nome']); ?> - <?php echo htmlspecialchars($modelo['assunto']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($modelos)): ?>
                        <div class="form-text text-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Não há modelos de email do tipo 'newsletter' cadastrados. 
                            <a href="<?php echo rtrim(SITE_URL, '/'); ?>/admin/index.php?page=gerenciar_emails">Clique aqui</a> para criar um modelo.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="conteudo_personalizado_div" style="display: none;">
                    <div class="mb-3">
                        <label for="assunto" class="form-label">Assunto</label>
                        <input type="text" class="form-control" id="assunto" name="assunto" placeholder="Assunto da newsletter">
                    </div>
                    
                    <div class="mb-3">
                        <label for="conteudo" class="form-label">Conteúdo</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="10" placeholder="Conteúdo da newsletter (suporta HTML)"></textarea>
                        <div class="form-text">
                            Você pode usar as seguintes variáveis no conteúdo:
                            <ul>
                                <li><code>{nome}</code> - Nome do destinatário</li>
                                <li><code>{email}</code> - Email do destinatário</li>
                                <li><code>{data_inscricao}</code> - Data de inscrição</li>
                                <li><code>{link_cancelar}</code> - Link para cancelar a inscrição</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="enviar_teste" name="enviar_teste" value="1">
                        <label class="form-check-label" for="enviar_teste">
                            Enviar um teste para mim antes de enviar para todos
                        </label>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_newsletter" class="btn btn-secondary">Voltar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Enviar Newsletter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Controle de exibição dos destinatários manuais
    const destinatariosSelect = document.getElementById('destinatarios');
    const destinatariosManuaisDiv = document.getElementById('destinatarios_manuais');
    
    destinatariosSelect.addEventListener('change', function() {
        if (this.value === 'selecionar') {
            destinatariosManuaisDiv.style.display = 'block';
        } else {
            destinatariosManuaisDiv.style.display = 'none';
        }
    });
    
    // Controle de exibição do tipo de conteúdo
    const tipoConteudoSelect = document.getElementById('tipo_conteudo');
    const modeloEmailDiv = document.getElementById('modelo_email_div');
    const conteudoPersonalizadoDiv = document.getElementById('conteudo_personalizado_div');
    
    tipoConteudoSelect.addEventListener('change', function() {
        if (this.value === 'modelo') {
            modeloEmailDiv.style.display = 'block';
            conteudoPersonalizadoDiv.style.display = 'none';
        } else if (this.value === 'personalizado') {
            modeloEmailDiv.style.display = 'none';
            conteudoPersonalizadoDiv.style.display = 'block';
        } else {
            modeloEmailDiv.style.display = 'none';
            conteudoPersonalizadoDiv.style.display = 'none';
        }
    });
    
    // Inicializar a exibição dos campos com base nos valores selecionados
    if (destinatariosSelect.value === 'selecionar') {
        destinatariosManuaisDiv.style.display = 'block';
    }
    
    if (tipoConteudoSelect.value === 'modelo') {
        modeloEmailDiv.style.display = 'block';
        conteudoPersonalizadoDiv.style.display = 'none';
    } else if (tipoConteudoSelect.value === 'personalizado') {
        modeloEmailDiv.style.display = 'none';
        conteudoPersonalizadoDiv.style.display = 'block';
    }
    
    // Pré-selecionar a opção "Usar modelo de email" se houver modelos disponíveis
    <?php if (!empty($modelos)): ?>
    if (tipoConteudoSelect.value === '') {
        tipoConteudoSelect.value = 'modelo';
        modeloEmailDiv.style.display = 'block';
    }
    <?php endif; ?>
    
    // Selecionar/deselecionar todos os inscritos
    const selecionarTodosCheckbox = document.getElementById('selecionar_todos');
    const inscritoCheckboxes = document.querySelectorAll('.inscrito-checkbox:not([disabled])');
    
    selecionarTodosCheckbox.addEventListener('change', function() {
        inscritoCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
    
    // Validação do formulário
    const form = document.getElementById('formEnviarNewsletter');
    
    form.addEventListener('submit', function(event) {
        let valid = true;
        
        // Validar destinatários
        if (destinatariosSelect.value === 'selecionar') {
            const checkedInscrito = document.querySelector('.inscrito-checkbox:checked');
            if (!checkedInscrito) {
                alert('Selecione pelo menos um destinatário.');
                valid = false;
            }
        }
        
        // Validar tipo de conteúdo
        if (tipoConteudoSelect.value === 'modelo') {
            const modeloId = document.getElementById('modelo_id');
            if (!modeloId.value) {
                alert('Selecione um modelo de email.');
                valid = false;
            }
        } else if (tipoConteudoSelect.value === 'personalizado') {
            const assunto = document.getElementById('assunto');
            const conteudo = document.getElementById('conteudo');
            
            if (!assunto.value.trim()) {
                alert('O assunto é obrigatório.');
                valid = false;
            }
            
            if (!conteudo.value.trim()) {
                alert('O conteúdo é obrigatório.');
                valid = false;
            }
        }
        
        if (!valid) {
            event.preventDefault();
        }
    });
});
</script>
