<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a tabela de contratações existe, caso contrário, criá-la
try {
    $db->execute("
        CREATE TABLE IF NOT EXISTS contratacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            talento_id INT NOT NULL,
            empresa_id INT NULL,
            empresa_nome VARCHAR(255) NULL,
            cargo VARCHAR(255) NOT NULL,
            descricao TEXT NULL,
            data_contratacao DATE NOT NULL,
            status ENUM('pendente', 'confirmada', 'rejeitada') DEFAULT 'pendente',
            depoimento_id INT NULL,
            data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (talento_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (empresa_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )
    ");
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Erro ao criar tabela de contratações: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

// Obter lista de contratações
try {
    $contratacoes = $db->fetchAll("
        SELECT c.*, 
               u.nome as talento_nome, 
               u.foto_perfil as talento_foto,
               e.nome as empresa_nome_cadastrada
        FROM contratacoes c
        JOIN usuarios u ON c.talento_id = u.id
        LEFT JOIN usuarios e ON c.empresa_id = e.id
        ORDER BY c.data_contratacao DESC
    ");
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Erro ao carregar contratações: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    $contratacoes = [];
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && isset($_POST['contratacao_id'])) {
        $acao = $_POST['acao'];
        $contratacao_id = (int)$_POST['contratacao_id'];
        
        try {
            if ($acao === 'confirmar') {
                // Confirmar contratação
                $db->query("UPDATE contratacoes SET status = 'confirmada', data_atualizacao = NOW() WHERE id = :id", [
                    'id' => $contratacao_id
                ]);
                
                // Buscar dados da contratação
                $contratacao = $db->fetch("SELECT talento_id, empresa_nome, cargo FROM contratacoes WHERE id = :id", [
                    'id' => $contratacao_id
                ]);
                
                // Notificar talento
                if ($contratacao) {
                    $db->query("INSERT INTO notificacoes (usuario_id, tipo, mensagem, link, data_criacao, lida) 
                               VALUES (:usuario_id, 'contratacao', :mensagem, :link, NOW(), 0)", [
                        'usuario_id' => $contratacao['talento_id'],
                        'mensagem' => "Sua contratação na empresa {$contratacao['empresa_nome']} como {$contratacao['cargo']} foi confirmada!",
                        'link' => SITE_URL . "/?route=informar_contratacao"
                    ]);
                }
                
                $_SESSION['flash_message'] = "Contratação confirmada com sucesso!";
                $_SESSION['flash_type'] = "success";
            } elseif ($acao === 'rejeitar') {
                // Rejeitar contratação
                $db->query("UPDATE contratacoes SET status = 'rejeitada', data_atualizacao = NOW() WHERE id = :id", [
                    'id' => $contratacao_id
                ]);
                
                // Buscar dados da contratação
                $contratacao = $db->fetch("SELECT talento_id FROM contratacoes WHERE id = :id", [
                    'id' => $contratacao_id
                ]);
                
                // Notificar talento
                if ($contratacao) {
                    $db->query("INSERT INTO notificacoes (usuario_id, tipo, mensagem, link, data_criacao, lida) 
                               VALUES (:usuario_id, 'contratacao', :mensagem, :link, NOW(), 0)", [
                        'usuario_id' => $contratacao['talento_id'],
                        'mensagem' => "Sua informação de contratação não pôde ser confirmada. Entre em contato para mais detalhes.",
                        'link' => SITE_URL . "/?route=informar_contratacao"
                    ]);
                }
                
                $_SESSION['flash_message'] = "Contratação rejeitada.";
                $_SESSION['flash_type'] = "warning";
            } elseif ($acao === 'adicionar_depoimento') {
                // Adicionar como depoimento
                $contratacao = $db->fetch("
                    SELECT c.*, u.nome as talento_nome, t.profissao 
                    FROM contratacoes c
                    JOIN usuarios u ON c.talento_id = u.id
                    LEFT JOIN talentos t ON u.id = t.usuario_id
                    WHERE c.id = :id", [
                    'id' => $contratacao_id
                ]);
                
                if ($contratacao) {
                    // Verificar se já existe na tabela de depoimentos
                    $depoimento_existente = $db->fetch("SELECT id FROM depoimentos WHERE contratacao_id = :contratacao_id", [
                        'contratacao_id' => $contratacao_id
                    ]);
                    
                    if (!$depoimento_existente) {
                        // Inserir na tabela de depoimentos
                        $db->query("INSERT INTO depoimentos (
                                   usuario_id, nome, cargo, empresa, depoimento, 
                                   data_cadastro, status, contratacao_id) 
                                   VALUES (
                                   :usuario_id, :nome, :cargo, :empresa, :depoimento, 
                                   NOW(), 'ativo', :contratacao_id)", [
                            'usuario_id' => $contratacao['talento_id'],
                            'nome' => $contratacao['talento_nome'],
                            'cargo' => $contratacao['cargo'],
                            'empresa' => $contratacao['empresa_nome'],
                            'depoimento' => $contratacao['descricao'] ?: "Contratado através do OpenToJob",
                            'contratacao_id' => $contratacao_id
                        ]);
                        
                        $_SESSION['flash_message'] = "Depoimento adicionado com sucesso!";
                        $_SESSION['flash_type'] = "success";
                    } else {
                        $_SESSION['flash_message'] = "Esta contratação já possui um depoimento.";
                        $_SESSION['flash_type'] = "info";
                    }
                } else {
                    $_SESSION['flash_message'] = "Contratação não encontrada.";
                    $_SESSION['flash_type'] = "danger";
                }
            } elseif ($acao === 'excluir') {
                // Excluir contratação
                $db->query("DELETE FROM contratacoes WHERE id = :id", [
                    'id' => $contratacao_id
                ]);
                
                $_SESSION['flash_message'] = "Contratação excluída com sucesso.";
                $_SESSION['flash_type'] = "success";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Erro ao processar ação: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
        
        // Redirecionar para evitar reenvio do formulário
        header('Location: ' . SITE_URL . '/?route=gerenciar_contratacoes');
        exit;
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Contratações</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Contratações</li>
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

    <!-- Lista de Contratações -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-handshake me-1"></i>
            Contratações Informadas por Talentos
        </div>
        <div class="card-body">
            <table id="contratoesTable" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Talento</th>
                        <th>Empresa</th>
                        <th>Cargo</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contratacoes)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma contratação informada</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contratacoes as $contratacao): ?>
                            <tr>
                                <td><?php echo $contratacao['id']; ?></td>
                                <td>
                                    <?php if (!empty($contratacao['talento_foto'])): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $contratacao['talento_foto']; ?>" class="img-circle me-2" width="30" height="30" alt="Foto">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars((string)$contratacao['talento_nome']); ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($contratacao['empresa_nome_cadastrada'])) {
                                        echo htmlspecialchars((string)$contratacao['empresa_nome_cadastrada']);
                                    } else {
                                        echo htmlspecialchars((string)$contratacao['empresa_nome'] ?? 'N/A');
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)$contratacao['cargo']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($contratacao['data_contratacao'])); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch ($contratacao['status']) {
                                        case 'pendente':
                                            $status_class = 'warning';
                                            break;
                                        case 'confirmada':
                                            $status_class = 'success';
                                            break;
                                        case 'rejeitada':
                                            $status_class = 'danger';
                                            break;
                                        default:
                                            $status_class = 'secondary';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($contratacao['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDetalhes<?php echo $contratacao['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($contratacao['status'] === 'pendente'): ?>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalConfirmar<?php echo $contratacao['id']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalRejeitar<?php echo $contratacao['id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($contratacao['status'] === 'confirmada' && empty($contratacao['depoimento_id'])): ?>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalDepoimento<?php echo $contratacao['id']; ?>">
                                            <i class="fas fa-comment"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- Modal de Detalhes -->
                            <div class="modal fade" id="modalDetalhes<?php echo $contratacao['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalDetalhesLabel<?php echo $contratacao['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalDetalhesLabel<?php echo $contratacao['id']; ?>">Detalhes da Contratação #<?php echo $contratacao['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Talento:</strong></div>
                                                <div class="col-md-8"><?php echo htmlspecialchars((string)$contratacao['talento_nome']); ?></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Empresa:</strong></div>
                                                <div class="col-md-8">
                                                    <?php 
                                                    if (!empty($contratacao['empresa_nome_cadastrada'])) {
                                                        echo htmlspecialchars((string)$contratacao['empresa_nome_cadastrada']);
                                                    } else {
                                                        echo htmlspecialchars((string)$contratacao['empresa_nome'] ?? 'N/A');
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Cargo:</strong></div>
                                                <div class="col-md-8"><?php echo htmlspecialchars((string)$contratacao['cargo']); ?></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Data:</strong></div>
                                                <div class="col-md-8"><?php echo date('d/m/Y H:i', strtotime($contratacao['data_contratacao'])); ?></div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Status:</strong></div>
                                                <div class="col-md-8">
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($contratacao['status']); ?>
                                                    </span>
                                                    <?php if ($contratacao['status'] !== 'pendente'): ?>
                                                        <small class="text-muted d-block">Atualizado em: <?php echo date('d/m/Y H:i', strtotime($contratacao['data_atualizacao'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($contratacao['descricao'])): ?>
                                            <div class="row mb-3">
                                                <div class="col-md-4 text-right"><strong>Depoimento:</strong></div>
                                                <div class="col-md-8">
                                                    <div class="p-2 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars((string)$contratacao['descricao'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                            <?php if ($contratacao['status'] === 'pendente'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="confirmar">
                                                <button type="submit" class="btn btn-success">Confirmar Contratação</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal de Confirmação -->
                            <div class="modal fade" id="modalConfirmar<?php echo $contratacao['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalConfirmarLabel<?php echo $contratacao['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalConfirmarLabel<?php echo $contratacao['id']; ?>">Confirmar Contratação #<?php echo $contratacao['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Deseja confirmar a contratação de <strong><?php echo htmlspecialchars((string)$contratacao['talento_nome']); ?></strong> como <strong><?php echo htmlspecialchars((string)$contratacao['cargo']); ?></strong> na empresa <strong><?php echo htmlspecialchars((string)$contratacao['empresa_nome']); ?></strong>?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="confirmar">
                                                <button type="submit" class="btn btn-success">Confirmar</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal de Rejeição -->
                            <div class="modal fade" id="modalRejeitar<?php echo $contratacao['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalRejeitarLabel<?php echo $contratacao['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalRejeitarLabel<?php echo $contratacao['id']; ?>">Rejeitar Contratação #<?php echo $contratacao['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Deseja rejeitar a contratação de <strong><?php echo htmlspecialchars((string)$contratacao['talento_nome']); ?></strong> como <strong><?php echo htmlspecialchars((string)$contratacao['cargo']); ?></strong> na empresa <strong><?php echo htmlspecialchars((string)$contratacao['empresa_nome']); ?></strong>?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="rejeitar">
                                                <button type="submit" class="btn btn-danger">Rejeitar</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal de Depoimento -->
                            <div class="modal fade" id="modalDepoimento<?php echo $contratacao['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalDepoimentoLabel<?php echo $contratacao['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalDepoimentoLabel<?php echo $contratacao['id']; ?>">Adicionar como Depoimento #<?php echo $contratacao['id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Deseja adicionar a contratação de <strong><?php echo htmlspecialchars((string)$contratacao['talento_nome']); ?></strong> como <strong><?php echo htmlspecialchars((string)$contratacao['cargo']); ?></strong> na empresa <strong><?php echo htmlspecialchars((string)$contratacao['empresa_nome']); ?></strong> como depoimento na página inicial?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="adicionar_depoimento">
                                                <button type="submit" class="btn btn-primary">Adicionar</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtro de pesquisa
    const inputPesquisa = document.getElementById('pesquisarContratacao');
    if (inputPesquisa) {
        inputPesquisa.addEventListener('keyup', function() {
            const termo = this.value.toLowerCase();
            const tabela = document.querySelector('table tbody');
            const linhas = tabela.querySelectorAll('tr');
            
            linhas.forEach(function(linha) {
                const texto = linha.textContent.toLowerCase();
                if (texto.indexOf(termo) > -1) {
                    linha.style.display = '';
                } else {
                    linha.style.display = 'none';
                }
            });
        });
    }
});
</script>
