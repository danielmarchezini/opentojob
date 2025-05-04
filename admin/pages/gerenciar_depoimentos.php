<?php
// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a tabela de depoimentos existe, caso contrário, criá-la
try {
    $db->execute("
        CREATE TABLE IF NOT EXISTS depoimentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            talento_id INT NOT NULL,
            nome VARCHAR(255) NOT NULL,
            foto VARCHAR(255),
            empresa VARCHAR(255) NOT NULL,
            cargo VARCHAR(255) NOT NULL,
            depoimento TEXT NOT NULL,
            status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
            data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (talento_id) REFERENCES usuarios(id) ON DELETE CASCADE
        )
    ");
    
    // Verificar se existe a configuração para habilitar/desabilitar depoimentos
    $config_exists = $db->fetchRow("
        SELECT COUNT(*) as count FROM configuracoes WHERE chave = 'depoimentos_habilitados'
    ");
    
    if ($config_exists['count'] == 0) {
        // Criar a configuração se não existir
        $db->execute("
            INSERT INTO configuracoes (chave, valor, descricao)
            VALUES ('depoimentos_habilitados', '0', 'Habilitar seção de depoimentos na página inicial')
        ");
    }
} catch (PDOException $e) {
    $_SESSION['flash_message'] = "Erro ao verificar tabela de depoimentos: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adicionar novo depoimento
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $talento_id = isset($_POST['talento_id']) ? intval($_POST['talento_id']) : 0;
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $empresa = isset($_POST['empresa']) ? trim($_POST['empresa']) : '';
        $cargo = isset($_POST['cargo']) ? trim($_POST['cargo']) : '';
        $depoimento = isset($_POST['depoimento']) ? trim($_POST['depoimento']) : '';
        
        // Validar dados
        $erros = [];
        if (empty($talento_id)) $erros[] = "ID do talento é obrigatório";
        if (empty($nome)) $erros[] = "Nome é obrigatório";
        if (empty($empresa)) $erros[] = "Empresa é obrigatória";
        if (empty($cargo)) $erros[] = "Cargo é obrigatório";
        if (empty($depoimento)) $erros[] = "Depoimento é obrigatório";
        
        if (empty($erros)) {
            try {
                // Verificar se o talento existe
                $talento = $db->fetchRow("
                    SELECT u.nome, t.foto_perfil 
                    FROM usuarios u 
                    JOIN talentos t ON u.id = t.usuario_id 
                    WHERE u.id = :id
                ", ['id' => $talento_id]);
                
                if ($talento) {
                    // Inserir depoimento
                    $db->execute("
                        INSERT INTO depoimentos (talento_id, nome, foto, empresa, cargo, depoimento, status)
                        VALUES (:talento_id, :nome, :foto, :empresa, :cargo, :depoimento, 'aprovado')
                    ", [
                        'talento_id' => $talento_id,
                        'nome' => $nome,
                        'foto' => $talento['foto_perfil'] ?? '',
                        'empresa' => $empresa,
                        'cargo' => $cargo,
                        'depoimento' => $depoimento
                    ]);
                    
                    $_SESSION['flash_message'] = "Depoimento adicionado com sucesso!";
                    $_SESSION['flash_type'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Talento não encontrado";
                    $_SESSION['flash_type'] = "danger";
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = "Erro ao adicionar depoimento: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "Erro ao adicionar depoimento: " . implode(", ", $erros);
            $_SESSION['flash_type'] = "danger";
        }
    }
    
    // Atualizar status do depoimento
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        
        if ($id > 0 && in_array($status, ['pendente', 'aprovado', 'rejeitado'])) {
            try {
                $db->execute("
                    UPDATE depoimentos SET status = :status WHERE id = :id
                ", ['status' => $status, 'id' => $id]);
                
                $_SESSION['flash_message'] = "Status do depoimento atualizado com sucesso!";
                $_SESSION['flash_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = "Erro ao atualizar status: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "Parâmetros inválidos para atualização de status";
            $_SESSION['flash_type'] = "danger";
        }
    }
    
    // Excluir depoimento
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id > 0) {
            try {
                $db->execute("DELETE FROM depoimentos WHERE id = :id", ['id' => $id]);
                
                $_SESSION['flash_message'] = "Depoimento excluído com sucesso!";
                $_SESSION['flash_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = "Erro ao excluir depoimento: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = "ID de depoimento inválido";
            $_SESSION['flash_type'] = "danger";
        }
    }
    
    // Atualizar configuração de depoimentos
    if (isset($_POST['action']) && $_POST['action'] === 'update_config') {
        $valor = isset($_POST['depoimentos_habilitados']) ? '1' : '0';
        
        try {
            $db->execute("
                UPDATE configuracoes SET valor = :valor WHERE chave = 'depoimentos_habilitados'
            ", ['valor' => $valor]);
            
            $_SESSION['flash_message'] = "Configuração de depoimentos atualizada com sucesso!";
            $_SESSION['flash_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Erro ao atualizar configuração: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
    
    // Redirecionar para evitar reenvio do formulário
    header('Location: ' . SITE_URL . '/admin/?page=gerenciar_depoimentos');
    exit;
}

// Buscar configuração atual
try {
    $config = $db->fetchRow("
        SELECT valor FROM configuracoes WHERE chave = 'depoimentos_habilitados'
    ");
    $depoimentos_habilitados = ($config && $config['valor'] == '1');
} catch (PDOException $e) {
    $depoimentos_habilitados = false;
}

// Buscar depoimentos
try {
    $depoimentos = $db->fetchAll("
        SELECT d.*, u.nome as talento_nome, t.profissao
        FROM depoimentos d
        JOIN usuarios u ON d.talento_id = u.id
        JOIN talentos t ON u.id = t.usuario_id
        ORDER BY d.data_cadastro DESC
    ");
} catch (PDOException $e) {
    $depoimentos = [];
    $_SESSION['flash_message'] = "Erro ao buscar depoimentos: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

// Buscar talentos para o formulário de adição
try {
    $talentos = $db->fetchAll("
        SELECT u.id, u.nome, t.profissao
        FROM usuarios u
        JOIN talentos t ON u.id = t.usuario_id
        WHERE u.status = 'ativo' AND u.tipo = 'talento'
        ORDER BY u.nome
    ");
} catch (PDOException $e) {
    $talentos = [];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gerenciar Depoimentos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/">Dashboard</a></li>
        <li class="breadcrumb-item active">Gerenciar Depoimentos</li>
    </ol>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
    
    <!-- Configuração de Depoimentos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-cog me-1"></i>
            Configuração de Depoimentos
        </div>
        <div class="card-body">
            <form method="post" action="">
                <input type="hidden" name="action" value="update_config">
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="depoimentos_habilitados" name="depoimentos_habilitados" <?php echo $depoimentos_habilitados ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="depoimentos_habilitados">Habilitar seção de depoimentos na página inicial</label>
                </div>
                
                <button type="submit" class="btn btn-primary">Salvar Configuração</button>
            </form>
        </div>
    </div>
    
    <!-- Adicionar Novo Depoimento -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            Adicionar Novo Depoimento
        </div>
        <div class="card-body">
            <form method="post" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="talento_id" class="form-label">Talento</label>
                        <select class="form-select" id="talento_id" name="talento_id" required>
                            <option value="">Selecione um talento</option>
                            <?php foreach ($talentos as $talento): ?>
                                <option value="<?php echo $talento['id']; ?>"><?php echo htmlspecialchars($talento['nome']); ?> - <?php echo htmlspecialchars($talento['profissao']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome a exibir</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="empresa" class="form-label">Empresa contratante</label>
                        <input type="text" class="form-control" id="empresa" name="empresa" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cargo" class="form-label">Cargo</label>
                        <input type="text" class="form-control" id="cargo" name="cargo" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="depoimento" class="form-label">Depoimento</label>
                    <textarea class="form-control" id="depoimento" name="depoimento" rows="4" required></textarea>
                    <div class="form-text">Depoimento do talento sobre como o OpenToJob ajudou na contratação</div>
                </div>
                
                <button type="submit" class="btn btn-success">Adicionar Depoimento</button>
            </form>
        </div>
    </div>
    
    <!-- Lista de Depoimentos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Depoimentos Cadastrados
        </div>
        <div class="card-body">
            <table id="depoimentosTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Talento</th>
                        <th>Nome Exibido</th>
                        <th>Empresa</th>
                        <th>Cargo</th>
                        <th>Depoimento</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($depoimentos as $depoimento): ?>
                        <tr>
                            <td><?php echo $depoimento['id']; ?></td>
                            <td><?php echo htmlspecialchars($depoimento['talento_nome']); ?></td>
                            <td><?php echo htmlspecialchars($depoimento['nome']); ?></td>
                            <td><?php echo htmlspecialchars($depoimento['empresa']); ?></td>
                            <td><?php echo htmlspecialchars($depoimento['cargo']); ?></td>
                            <td><?php echo htmlspecialchars(substr($depoimento['depoimento'], 0, 100)) . (strlen($depoimento['depoimento']) > 100 ? '...' : ''); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $depoimento['status'] === 'aprovado' ? 'success' : 
                                        ($depoimento['status'] === 'pendente' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($depoimento['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($depoimento['data_cadastro'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewDepoimentoModal<?php echo $depoimento['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($depoimento['status'] !== 'aprovado'): ?>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $depoimento['id']; ?>">
                                            <input type="hidden" name="status" value="aprovado">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Aprovar este depoimento?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($depoimento['status'] !== 'rejeitado'): ?>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $depoimento['id']; ?>">
                                            <input type="hidden" name="status" value="rejeitado">
                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Rejeitar este depoimento?')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="post" action="" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $depoimento['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este depoimento?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Modal de Visualização -->
                                <div class="modal fade" id="viewDepoimentoModal<?php echo $depoimento['id']; ?>" tabindex="-1" aria-labelledby="viewDepoimentoModalLabel<?php echo $depoimento['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewDepoimentoModalLabel<?php echo $depoimento['id']; ?>">Depoimento de <?php echo htmlspecialchars($depoimento['nome']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Talento:</strong> <?php echo htmlspecialchars($depoimento['talento_nome']); ?></p>
                                                        <p><strong>Nome exibido:</strong> <?php echo htmlspecialchars($depoimento['nome']); ?></p>
                                                        <p><strong>Empresa:</strong> <?php echo htmlspecialchars($depoimento['empresa']); ?></p>
                                                        <p><strong>Cargo:</strong> <?php echo htmlspecialchars($depoimento['cargo']); ?></p>
                                                        <p><strong>Status:</strong> 
                                                            <span class="badge bg-<?php 
                                                                echo $depoimento['status'] === 'aprovado' ? 'success' : 
                                                                    ($depoimento['status'] === 'pendente' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($depoimento['status']); ?>
                                                            </span>
                                                        </p>
                                                        <p><strong>Data de cadastro:</strong> <?php echo date('d/m/Y H:i', strtotime($depoimento['data_cadastro'])); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <?php if (!empty($depoimento['foto'])): ?>
                                                            <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $depoimento['foto']; ?>" alt="<?php echo htmlspecialchars($depoimento['nome']); ?>" class="img-fluid rounded" style="max-height: 200px;">
                                                        <?php else: ?>
                                                            <div class="alert alert-info">Sem foto de perfil</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="card">
                                                    <div class="card-header">Depoimento</div>
                                                    <div class="card-body">
                                                        <p><?php echo nl2br(htmlspecialchars($depoimento['depoimento'])); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                
                                                <?php if ($depoimento['status'] !== 'aprovado'): ?>
                                                    <form method="post" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="id" value="<?php echo $depoimento['id']; ?>">
                                                        <input type="hidden" name="status" value="aprovado">
                                                        <button type="submit" class="btn btn-success">Aprovar</button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($depoimento['status'] !== 'rejeitado'): ?>
                                                    <form method="post" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="id" value="<?php echo $depoimento['id']; ?>">
                                                        <input type="hidden" name="status" value="rejeitado">
                                                        <button type="submit" class="btn btn-warning">Rejeitar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    new DataTable('#depoimentosTable', {
        responsive: true,
        language: {
            url: '/open2w/assets/js/pt-BR.json',
        }
    });
    
    // Preencher automaticamente o nome ao selecionar o talento
    const talentoSelect = document.getElementById('talento_id');
    const nomeInput = document.getElementById('nome');
    
    if (talentoSelect && nomeInput) {
        talentoSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const talento = selectedOption.text.split(' - ')[0];
                nomeInput.value = talento;
            } else {
                nomeInput.value = '';
            }
        });
    }
});
</script>
