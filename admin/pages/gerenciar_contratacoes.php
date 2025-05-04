<?php
// Obter lista de contratações
$db = Database::getInstance();
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

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gerenciar Contratações</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Gerenciar Contratações</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
    <?php echo $_SESSION['flash_message']; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php 
    // Limpar mensagem flash
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
endif; ?>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Contratações Informadas por Talentos</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 150px;">
                        <input type="text" id="pesquisarContratacao" class="form-control float-right" placeholder="Buscar">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
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
                                            <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $contratacao['talento_foto']; ?>" class="img-circle mr-2" width="30" height="30" alt="Foto">
                                        <?php else: ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/img/avatar.png" class="img-circle mr-2" width="30" height="30" alt="Foto">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($contratacao['talento_nome']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($contratacao['empresa_id']) && !empty($contratacao['empresa_nome_cadastrada'])) {
                                            echo htmlspecialchars($contratacao['empresa_nome_cadastrada']);
                                            echo ' <span class="badge badge-info">Cadastrada</span>';
                                        } else {
                                            echo htmlspecialchars($contratacao['empresa_nome']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($contratacao['cargo']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($contratacao['data_contratacao'])); ?></td>
                                    <td>
                                        <?php
                                        $status = $contratacao['status'];
                                        $status_class = '';
                                        switch ($status) {
                                            case 'confirmada':
                                                $status_class = 'badge-success';
                                                break;
                                            case 'rejeitada':
                                                $status_class = 'badge-danger';
                                                break;
                                            case 'pendente':
                                            default:
                                                $status_class = 'badge-warning';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalDetalhes<?php echo $contratacao['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($contratacao['status'] === 'pendente'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="confirmar">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Confirmar esta contratação?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="rejeitar">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Rejeitar esta contratação?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($contratacao['status'] === 'confirmada'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="adicionar_depoimento">
                                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Adicionar como depoimento na página inicial?')">
                                                    <i class="fas fa-comment"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="contratacao_id" value="<?php echo $contratacao['id']; ?>">
                                                <input type="hidden" name="acao" value="excluir">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Excluir esta contratação? Esta ação não pode ser desfeita.')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Modal de Detalhes -->
                                <div class="modal fade" id="modalDetalhes<?php echo $contratacao['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalDetalhesLabel<?php echo $contratacao['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalDetalhesLabel<?php echo $contratacao['id']; ?>">Detalhes da Contratação #<?php echo $contratacao['id']; ?></h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Talento:</strong></div>
                                                    <div class="col-md-8"><?php echo htmlspecialchars($contratacao['talento_nome']); ?></div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Empresa:</strong></div>
                                                    <div class="col-md-8">
                                                        <?php 
                                                        if (!empty($contratacao['empresa_id']) && !empty($contratacao['empresa_nome_cadastrada'])) {
                                                            echo htmlspecialchars($contratacao['empresa_nome_cadastrada']);
                                                            echo ' <span class="badge badge-info">Cadastrada</span>';
                                                        } else {
                                                            echo htmlspecialchars($contratacao['empresa_nome']);
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Cargo:</strong></div>
                                                    <div class="col-md-8"><?php echo htmlspecialchars($contratacao['cargo']); ?></div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Data:</strong></div>
                                                    <div class="col-md-8"><?php echo date('d/m/Y H:i', strtotime($contratacao['data_contratacao'])); ?></div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4 text-right"><strong>Status:</strong></div>
                                                    <div class="col-md-8">
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
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
                                                            <?php echo nl2br(htmlspecialchars($contratacao['descricao'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
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
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

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
