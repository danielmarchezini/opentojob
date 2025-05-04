<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa') && !Auth::checkUserType('admin')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID do usuário logado (empresa)
$empresa_id = $_SESSION['user_id'];

// Verificar se o ID da candidatura foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "ID da candidatura não fornecido.";
    $_SESSION['flash_type'] = "danger";
    header('Location: ' . SITE_URL . '/?route=empresa/candidaturas');
    exit;
}

$candidatura_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a candidatura existe e pertence a uma vaga da empresa logada
try {
    $candidatura = $db->fetch("
        SELECT c.*, v.titulo as vaga_titulo, v.empresa_id,
               t.nome as talento_nome, t.email as talento_email
        FROM candidaturas c
        JOIN vagas v ON c.vaga_id = v.id
        JOIN talentos t ON c.talento_id = t.usuario_id
        WHERE c.id = :id AND v.empresa_id = :empresa_id
    ", [
        'id' => $candidatura_id,
        'empresa_id' => $empresa_id
    ]);
    
    if (!$candidatura) {
        $_SESSION['flash_message'] = "Candidatura não encontrada ou você não tem permissão para acessá-la.";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . SITE_URL . '/?route=empresa/candidaturas');
        exit;
    }
    
    // Obter dados da empresa
    $empresa = $db->fetch("
        SELECT e.*, u.nome, u.email
        FROM empresas e
        JOIN usuarios u ON e.usuario_id = u.id
        WHERE u.id = :id
    ", ['id' => $empresa_id]);
    
    $empresa_nome = $empresa['razao_social'] ?? $empresa['nome'] ?? 'Empresa';
    $empresa_email = $empresa['email'] ?? '';
    
} catch (PDOException $e) {
    error_log("Erro ao buscar candidatura: " . $e->getMessage());
    $_SESSION['flash_message'] = "Erro ao buscar dados da candidatura: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    header('Location: ' . SITE_URL . '/?route=empresa/candidaturas');
    exit;
}

// Processar formulário de convite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados
    $data_entrevista = trim($_POST['data_entrevista'] ?? '');
    $hora_entrevista = trim($_POST['hora_entrevista'] ?? '');
    $local_entrevista = trim($_POST['local_entrevista'] ?? '');
    $tipo_entrevista = $_POST['tipo_entrevista'] ?? '';
    $link_entrevista = trim($_POST['link_entrevista'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    // Validar campos obrigatórios
    $errors = [];
    
    if (empty($data_entrevista)) {
        $errors[] = "A data da entrevista é obrigatória.";
    }
    
    if (empty($hora_entrevista)) {
        $errors[] = "A hora da entrevista é obrigatória.";
    }
    
    if ($tipo_entrevista === 'presencial' && empty($local_entrevista)) {
        $errors[] = "O local da entrevista é obrigatório para entrevistas presenciais.";
    }
    
    if ($tipo_entrevista === 'online' && empty($link_entrevista)) {
        $errors[] = "O link da entrevista é obrigatório para entrevistas online.";
    }
    
    // Se não houver erros, registrar entrevista e enviar convite
    if (empty($errors)) {
        try {
            // Verificar se a tabela entrevistas existe
            $db->query("
                CREATE TABLE IF NOT EXISTS entrevistas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    candidatura_id INT NOT NULL,
                    data_entrevista DATE NOT NULL,
                    hora_entrevista TIME NOT NULL,
                    tipo_entrevista ENUM('presencial', 'online', 'telefone') NOT NULL,
                    local_entrevista TEXT NULL,
                    link_entrevista VARCHAR(255) NULL,
                    observacoes TEXT NULL,
                    status ENUM('agendada', 'realizada', 'cancelada', 'reagendada') NOT NULL DEFAULT 'agendada',
                    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE
                )
            ");
            
            // Registrar entrevista
            $entrevista_id = $db->insert('entrevistas', [
                'candidatura_id' => $candidatura_id,
                'data_entrevista' => $data_entrevista,
                'hora_entrevista' => $hora_entrevista,
                'tipo_entrevista' => $tipo_entrevista,
                'local_entrevista' => $local_entrevista,
                'link_entrevista' => $link_entrevista,
                'observacoes' => $observacoes,
                'status' => 'agendada'
            ]);
            
            // Atualizar status da candidatura para 'entrevista'
            $db->update('candidaturas', 
                ['status' => 'entrevista', 'data_atualizacao' => date('Y-m-d H:i:s')], 
                "id = :id", 
                ['id' => $candidatura_id]
            );
            
            // Criar mensagem para o candidato
            $assunto = "Convite para Entrevista - " . htmlspecialchars($candidatura['vaga_titulo']);
            $mensagem = "Olá " . htmlspecialchars($candidatura['talento_nome']) . ",\n\n";
            $mensagem .= "Gostaríamos de convidá-lo(a) para uma entrevista para a vaga de " . htmlspecialchars($candidatura['vaga_titulo']) . ".\n\n";
            $mensagem .= "Detalhes da entrevista:\n";
            $mensagem .= "Data: " . date('d/m/Y', strtotime($data_entrevista)) . "\n";
            $mensagem .= "Hora: " . $hora_entrevista . "\n";
            $mensagem .= "Tipo: " . ($tipo_entrevista === 'presencial' ? 'Presencial' : ($tipo_entrevista === 'online' ? 'Online' : 'Telefone')) . "\n";
            
            if ($tipo_entrevista === 'presencial') {
                $mensagem .= "Local: " . $local_entrevista . "\n";
            } elseif ($tipo_entrevista === 'online') {
                $mensagem .= "Link: " . $link_entrevista . "\n";
            }
            
            if (!empty($observacoes)) {
                $mensagem .= "\nObservações:\n" . $observacoes . "\n";
            }
            
            $mensagem .= "\nPor favor, confirme sua presença respondendo a esta mensagem.\n\n";
            $mensagem .= "Atenciosamente,\n" . $empresa_nome;
            
            // Verificar se a tabela mensagens existe
            $db->query("
                CREATE TABLE IF NOT EXISTS mensagens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    remetente_id INT NOT NULL,
                    destinatario_id INT NOT NULL,
                    assunto VARCHAR(255) NOT NULL,
                    mensagem TEXT NOT NULL,
                    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    lida TINYINT(1) NOT NULL DEFAULT 0,
                    data_leitura DATETIME NULL,
                    FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
                )
            ");
            
            // Enviar mensagem interna
            $db->insert('mensagens', [
                'remetente_id' => $empresa_id,
                'destinatario_id' => $candidatura['talento_id'],
                'assunto' => $assunto,
                'mensagem' => $mensagem,
                'data_envio' => date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['flash_message'] = "Convite para entrevista enviado com sucesso!";
            $_SESSION['flash_type'] = "success";
            
            // Redirecionar para a página de candidaturas
            header('Location: ' . SITE_URL . '/?route=empresa/candidaturas');
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao registrar entrevista: " . $e->getMessage());
            $_SESSION['flash_message'] = "Erro ao registrar entrevista: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        // Exibir erros
        $_SESSION['flash_message'] = implode("<br>", $errors);
        $_SESSION['flash_type'] = "danger";
    }
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Convidar para Entrevista</h1>
                <a href="<?php echo SITE_URL; ?>/?route=empresa/candidaturas" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Voltar para Candidaturas
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['flash_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php 
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    endif; 
    ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Dados da Candidatura</h5>
                </div>
                <div class="card-body">
                    <p><strong>Candidato:</strong> <?php echo htmlspecialchars($candidatura['talento_nome']); ?></p>
                    <p><strong>Vaga:</strong> <?php echo htmlspecialchars($candidatura['vaga_titulo']); ?></p>
                    <p><strong>Data da Candidatura:</strong> <?php echo date('d/m/Y', strtotime($candidatura['data_candidatura'])); ?></p>
                    <p><strong>Status Atual:</strong> 
                        <?php 
                        $status_formatado = [
                            'recebida' => '<span class="badge bg-info">Recebida</span>',
                            'em_analise' => '<span class="badge bg-warning">Em Análise</span>',
                            'entrevista' => '<span class="badge bg-primary">Entrevista</span>',
                            'aprovada' => '<span class="badge bg-success">Aprovada</span>',
                            'reprovada' => '<span class="badge bg-danger">Reprovada</span>'
                        ];
                        echo $status_formatado[$candidatura['status']] ?? '<span class="badge bg-secondary">Desconhecido</span>';
                        ?>
                    </p>
                    
                    <?php if (!empty($candidatura['mensagem'])): ?>
                    <div class="mt-3">
                        <p><strong>Mensagem do Candidato:</strong></p>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p><?php echo nl2br(htmlspecialchars($candidatura['mensagem'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/?route=talento/perfil&id=<?php echo $candidatura['talento_id']; ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-user me-2"></i> Ver Perfil Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Dados da Entrevista</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_entrevista" class="form-label">Data da Entrevista *</label>
                                <input type="date" class="form-control" id="data_entrevista" name="data_entrevista" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hora_entrevista" class="form-label">Hora da Entrevista *</label>
                                <input type="time" class="form-control" id="hora_entrevista" name="hora_entrevista" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_entrevista" class="form-label">Tipo de Entrevista *</label>
                            <select class="form-select" id="tipo_entrevista" name="tipo_entrevista" required onchange="toggleEntrevistaFields()">
                                <option value="">Selecione o tipo de entrevista</option>
                                <option value="presencial">Presencial</option>
                                <option value="online">Online</option>
                                <option value="telefone">Telefone</option>
                            </select>
                        </div>
                        
                        <div id="local_entrevista_container" class="mb-3" style="display: none;">
                            <label for="local_entrevista" class="form-label">Local da Entrevista *</label>
                            <textarea class="form-control" id="local_entrevista" name="local_entrevista" rows="3" placeholder="Endereço completo do local da entrevista"></textarea>
                            <small class="text-muted">Informe o endereço completo, incluindo CEP e referências se necessário.</small>
                        </div>
                        
                        <div id="link_entrevista_container" class="mb-3" style="display: none;">
                            <label for="link_entrevista" class="form-label">Link da Entrevista *</label>
                            <input type="url" class="form-control" id="link_entrevista" name="link_entrevista" placeholder="https://meet.google.com/xxx-xxxx-xxx">
                            <small class="text-muted">Informe o link completo para a sala de reunião virtual (Google Meet, Zoom, Microsoft Teams, etc).</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="4" placeholder="Informações adicionais sobre a entrevista..."></textarea>
                            <small class="text-muted">Informe detalhes adicionais como documentos necessários, pessoas que participarão da entrevista, etc.</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <p class="mb-0"><i class="fas fa-info-circle me-2"></i> Ao enviar o convite, o candidato receberá uma mensagem com os detalhes da entrevista e o status da candidatura será atualizado para "Entrevista".</p>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Convite
                            </button>
                            <a href="<?php echo SITE_URL; ?>/?route=empresa/candidaturas" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEntrevistaFields() {
    const tipoEntrevista = document.getElementById('tipo_entrevista').value;
    const localContainer = document.getElementById('local_entrevista_container');
    const linkContainer = document.getElementById('link_entrevista_container');
    const localInput = document.getElementById('local_entrevista');
    const linkInput = document.getElementById('link_entrevista');
    
    // Resetar required
    localInput.required = false;
    linkInput.required = false;
    
    // Esconder todos
    localContainer.style.display = 'none';
    linkContainer.style.display = 'none';
    
    // Mostrar apenas o necessário
    if (tipoEntrevista === 'presencial') {
        localContainer.style.display = 'block';
        localInput.required = true;
    } else if (tipoEntrevista === 'online') {
        linkContainer.style.display = 'block';
        linkInput.required = true;
    }
}

// Inicializar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    toggleEntrevistaFields();
});
</script>
