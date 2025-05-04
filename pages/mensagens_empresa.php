<?php
// Verificar se o usuário está logado como empresa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'empresa') {
    $_SESSION['flash_message'] = "Você precisa estar logado como empresa para acessar suas mensagens.";
    $_SESSION['flash_type'] = "warning";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Obter o ID da empresa logada
$empresa_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Definir a ação (inbox, enviadas, visualizar)
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'inbox';

// Processar exclusão de mensagem
if (isset($_POST['excluir_mensagem']) && isset($_POST['mensagem_id'])) {
    $mensagem_id = (int)$_POST['mensagem_id'];
    
    // Verificar se a mensagem pertence ao usuário
    $mensagem = $db->fetchRow("
        SELECT * FROM mensagens 
        WHERE id = :id AND (remetente_id = :usuario_id OR destinatario_id = :usuario_id)
    ", [
        'id' => $mensagem_id,
        'usuario_id' => $empresa_id
    ]);
    
    if ($mensagem) {
        if ($mensagem['remetente_id'] == $empresa_id) {
            // Marcar como excluída pelo remetente
            $db->update('mensagens', [
                'excluida_remetente' => 1
            ], 'id = :id', [
                'id' => $mensagem_id
            ]);
        } else {
            // Marcar como excluída pelo destinatário
            $db->update('mensagens', [
                'excluida_destinatario' => 1
            ], 'id = :id', [
                'id' => $mensagem_id
            ]);
        }
        
        $_SESSION['flash_message'] = "Mensagem excluída com sucesso.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Erro ao excluir mensagem. Mensagem não encontrada.";
        $_SESSION['flash_type'] = "danger";
    }
    
    // Redirecionar para evitar reenvio do formulário
    echo "<script>window.location.href = '" . SITE_URL . "/?route=mensagens_empresa&acao=" . $acao . "';</script>";
    exit;
}

// Processar resposta à mensagem
if (isset($_POST['responder_mensagem']) && isset($_POST['mensagem_id']) && isset($_POST['resposta'])) {
    $mensagem_id = (int)$_POST['mensagem_id'];
    $resposta = trim($_POST['resposta']);
    
    // Verificar se a mensagem existe e pertence ao usuário
    $mensagem_original = $db->fetchRow("
        SELECT * FROM mensagens 
        WHERE id = :id AND destinatario_id = :usuario_id
    ", [
        'id' => $mensagem_id,
        'usuario_id' => $empresa_id
    ]);
    
    if ($mensagem_original && !empty($resposta)) {
        // Inserir a resposta
        $resultado = $db->insert('mensagens', [
            'remetente_id' => $empresa_id,
            'destinatario_id' => $mensagem_original['remetente_id'],
            'assunto' => "RE: " . $mensagem_original['assunto'],
            'mensagem' => $resposta,
            'data_envio' => date('Y-m-d H:i:s')
        ]);
        
        if ($resultado) {
            // Registrar a interação para estatísticas
            $db->insert('estatisticas_interacoes', [
                'tipo_interacao' => 'contato',
                'usuario_origem_id' => $empresa_id,
                'usuario_destino_id' => $mensagem_original['remetente_id'],
                'data_interacao' => date('Y-m-d H:i:s'),
                'detalhes' => json_encode(['tipo' => 'resposta', 'mensagem_original_id' => $mensagem_id])
            ]);
            
            $_SESSION['flash_message'] = "Resposta enviada com sucesso!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Erro ao enviar resposta. Por favor, tente novamente.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_message'] = "Erro ao enviar resposta. Mensagem não encontrada ou resposta vazia.";
        $_SESSION['flash_type'] = "danger";
    }
    
    // Redirecionar para evitar reenvio do formulário
    echo "<script>window.location.href = '" . SITE_URL . "/?route=mensagens_empresa&acao=inbox';</script>";
    exit;
}

// Marcar mensagem como lida
if ($acao === 'visualizar' && isset($_GET['id'])) {
    $mensagem_id = (int)$_GET['id'];
    
    // Verificar se a mensagem pertence ao usuário e não foi lida
    $mensagem = $db->fetchRow("
        SELECT * FROM mensagens 
        WHERE id = :id AND destinatario_id = :usuario_id AND lida = 0
    ", [
        'id' => $mensagem_id,
        'usuario_id' => $empresa_id
    ]);
    
    if ($mensagem) {
        // Marcar como lida
        $db->update('mensagens', [
            'lida' => 1
        ], 'id = :id', [
            'id' => $mensagem_id
        ]);
    }
}

// Obter mensagens com base na ação
if ($acao === 'inbox') {
    // Caixa de entrada (mensagens recebidas)
    $mensagens = $db->fetchAll("
        SELECT m.*, u.nome as remetente_nome, t.profissao, t.foto_perfil
        FROM mensagens m
        JOIN usuarios u ON m.remetente_id = u.id
        LEFT JOIN talentos t ON u.id = t.usuario_id
        WHERE m.destinatario_id = :usuario_id AND m.excluida_destinatario = 0
        ORDER BY m.data_envio DESC
    ", [
        'usuario_id' => $empresa_id
    ]);
    
    $titulo_pagina = "Caixa de Entrada";
} elseif ($acao === 'enviadas') {
    // Mensagens enviadas
    $mensagens = $db->fetchAll("
        SELECT m.*, u.nome as destinatario_nome, t.profissao, t.foto_perfil
        FROM mensagens m
        JOIN usuarios u ON m.destinatario_id = u.id
        LEFT JOIN talentos t ON u.id = t.usuario_id
        WHERE m.remetente_id = :usuario_id AND m.excluida_remetente = 0
        ORDER BY m.data_envio DESC
    ", [
        'usuario_id' => $empresa_id
    ]);
    
    $titulo_pagina = "Mensagens Enviadas";
} elseif ($acao === 'visualizar' && isset($_GET['id'])) {
    // Visualizar uma mensagem específica
    $mensagem_id = (int)$_GET['id'];
    
    // Verificar se a mensagem pertence ao usuário
    $mensagem = $db->fetchRow("
        SELECT m.*, 
               ur.nome as remetente_nome, 
               ud.nome as destinatario_nome,
               tr.profissao as profissao_remetente,
               tr.foto_perfil as foto_remetente
        FROM mensagens m
        JOIN usuarios ur ON m.remetente_id = ur.id
        JOIN usuarios ud ON m.destinatario_id = ud.id
        LEFT JOIN talentos tr ON ur.id = tr.usuario_id
        WHERE m.id = :id AND (m.remetente_id = :usuario_id OR m.destinatario_id = :usuario_id)
              AND ((m.remetente_id = :usuario_id AND m.excluida_remetente = 0) 
                   OR (m.destinatario_id = :usuario_id AND m.excluida_destinatario = 0))
    ", [
        'id' => $mensagem_id,
        'usuario_id' => $empresa_id
    ]);
    
    if (!$mensagem) {
        $_SESSION['flash_message'] = "Mensagem não encontrada ou foi excluída.";
        $_SESSION['flash_type'] = "warning";
        echo "<script>window.location.href = '" . SITE_URL . "/?route=mensagens_empresa';</script>";
        exit;
    }
    
    $titulo_pagina = "Visualizar Mensagem";
} else {
    // Ação inválida, redirecionar para a caixa de entrada
    echo "<script>window.location.href = '" . SITE_URL . "/?route=mensagens_empresa&acao=inbox';</script>";
    exit;
}

// Contar mensagens não lidas
$total_nao_lidas = $db->fetchColumn("
    SELECT COUNT(*) FROM mensagens 
    WHERE destinatario_id = :usuario_id AND lida = 0 AND excluida_destinatario = 0
", [
    'usuario_id' => $empresa_id
]);

// Obter dados da empresa
$empresa = $db->fetchRow("
    SELECT u.nome, e.nome_empresa, e.logo
    FROM usuarios u
    JOIN empresas e ON u.id = e.usuario_id
    WHERE u.id = :id
", [
    'id' => $empresa_id
]);
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Mensagens</h1>
                <p class="lead">Gerencie suas comunicações com talentos</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_empresa">Meu Painel</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Mensagens</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<section class="section-mensagens py-5">
    <div class="container">
        <div class="row">
            <!-- Menu lateral -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Menu</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="<?php echo SITE_URL; ?>/?route=mensagens_empresa&acao=inbox" class="list-group-item list-group-item-action <?php echo ($acao === 'inbox') ? 'active' : ''; ?>">
                            Caixa de Entrada
                            <?php if ($total_nao_lidas > 0): ?>
                                <span class="badge bg-primary rounded-pill float-end"><?php echo $total_nao_lidas; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/?route=mensagens_empresa&acao=enviadas" class="list-group-item list-group-item-action <?php echo ($acao === 'enviadas') ? 'active' : ''; ?>">
                            Mensagens Enviadas
                        </a>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ações</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="<?php echo SITE_URL; ?>/?route=talentos" class="list-group-item list-group-item-action">
                            <i class="fas fa-search me-2"></i> Buscar Talentos
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Conteúdo principal -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $titulo_pagina; ?></h5>
                    </div>
                    
                    <?php if ($acao === 'inbox' || $acao === 'enviadas'): ?>
                        <!-- Lista de mensagens -->
                        <div class="card-body p-0">
                            <?php if (empty($mensagens)): ?>
                                <div class="alert alert-info m-3">
                                    Nenhuma mensagem encontrada.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($mensagens as $msg): ?>
                                        <a href="<?php echo SITE_URL; ?>/?route=mensagens_empresa&acao=visualizar&id=<?php echo $msg['id']; ?>" class="list-group-item list-group-item-action <?php echo ($acao === 'inbox' && $msg['lida'] == 0) ? 'fw-bold' : ''; ?>">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <?php 
                                                        $foto = $acao === 'inbox' ? $msg['foto_perfil'] : $msg['foto_perfil'];
                                                        if (!empty($foto)): 
                                                        ?>
                                                            <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $foto; ?>" alt="Foto" class="rounded-circle" width="40" height="40">
                                                        <?php else: ?>
                                                            <div class="avatar-placeholder rounded-circle bg-primary text-white">
                                                                <?php 
                                                                $nome_exibicao = $acao === 'inbox' ? $msg['remetente_nome'] : $msg['destinatario_nome'];
                                                                echo strtoupper(substr($nome_exibicao, 0, 1)); 
                                                                ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($msg['assunto']); ?></h6>
                                                        <p class="mb-1 text-muted">
                                                            <?php if ($acao === 'inbox'): ?>
                                                                De: <?php echo htmlspecialchars($msg['remetente_nome']); ?>
                                                                <?php if (!empty($msg['profissao'])): ?>
                                                                    <small>(<?php echo htmlspecialchars($msg['profissao']); ?>)</small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                Para: <?php echo htmlspecialchars($msg['destinatario_nome']); ?>
                                                                <?php if (!empty($msg['profissao'])): ?>
                                                                    <small>(<?php echo htmlspecialchars($msg['profissao']); ?>)</small>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($msg['data_envio'])); ?></small>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($acao === 'visualizar' && isset($mensagem)): ?>
                        <!-- Visualização de mensagem -->
                        <div class="card-body">
                            <div class="mensagem-header mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($mensagem['assunto']); ?></h5>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($mensagem['data_envio'])); ?></small>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php if (!empty($mensagem['foto_remetente'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $mensagem['foto_remetente']; ?>" alt="Foto" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle bg-primary text-white">
                                                <?php echo strtoupper(substr($mensagem['remetente_nome'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="mb-0">
                                            <strong>De:</strong> <?php echo htmlspecialchars($mensagem['remetente_nome']); ?>
                                            <?php if (!empty($mensagem['profissao_remetente'])): ?>
                                                <small>(<?php echo htmlspecialchars($mensagem['profissao_remetente']); ?>)</small>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Para:</strong> <?php echo htmlspecialchars($empresa['nome_empresa'] ?: $mensagem['destinatario_nome']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mensagem-corpo p-3 bg-light rounded mb-4">
                                <?php echo nl2br(htmlspecialchars($mensagem['mensagem'])); ?>
                            </div>
                            
                            <div class="mensagem-acoes d-flex justify-content-between">
                                <div>
                                    <a href="<?php echo SITE_URL; ?>/?route=mensagens_empresa&acao=<?php echo $mensagem['destinatario_id'] == $empresa_id ? 'inbox' : 'enviadas'; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i> Voltar
                                    </a>
                                </div>
                                <div>
                                    <?php if ($mensagem['destinatario_id'] == $empresa_id): ?>
                                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="collapse" data-bs-target="#responderForm">
                                            <i class="fas fa-reply me-2"></i> Responder
                                        </button>
                                    <?php endif; ?>
                                    
                                    <form action="<?php echo SITE_URL; ?>/?route=mensagens_empresa&acao=<?php echo $mensagem['destinatario_id'] == $empresa_id ? 'inbox' : 'enviadas'; ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="mensagem_id" value="<?php echo $mensagem['id']; ?>">
                                        <input type="hidden" name="excluir_mensagem" value="1">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta mensagem?')">
                                            <i class="fas fa-trash-alt me-2"></i> Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if ($mensagem['destinatario_id'] == $empresa_id): ?>
                                <!-- Formulário de resposta -->
                                <div class="collapse mt-4" id="responderForm">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Responder</h5>
                                        </div>
                                        <div class="card-body">
                                            <form action="<?php echo SITE_URL; ?>/?route=mensagens_empresa&acao=visualizar&id=<?php echo $mensagem['id']; ?>" method="POST">
                                                <input type="hidden" name="mensagem_id" value="<?php echo $mensagem['id']; ?>">
                                                <input type="hidden" name="responder_mensagem" value="1">
                                                
                                                <div class="form-group mb-3">
                                                    <label for="resposta" class="form-label">Sua resposta</label>
                                                    <textarea class="form-control" id="resposta" name="resposta" rows="5" required></textarea>
                                                </div>
                                                
                                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                    <button type="button" class="btn btn-outline-secondary me-md-2" data-bs-toggle="collapse" data-bs-target="#responderForm">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Enviar Resposta</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.avatar-placeholder {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
}

.mensagem-corpo {
    min-height: 200px;
    white-space: pre-line;
}
</style>
