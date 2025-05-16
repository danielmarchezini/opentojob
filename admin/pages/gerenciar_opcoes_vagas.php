<?php
// Incluir arquivos necessários
// Usar caminhos absolutos para evitar problemas
$root_path = realpath(__DIR__ . '/../..');

// Verificar se o arquivo config.php existe
if (file_exists($root_path . '/config/config.php')) {
    require_once $root_path . '/config/config.php';
} elseif (file_exists($root_path . '/includes/config.php')) {
    require_once $root_path . '/includes/config.php';
} else {
    die('Arquivo de configuração não encontrado!');
}

// Incluir os arquivos com os nomes corretos (com letras maiúsculas)
require_once $root_path . '/includes/Database.php';
require_once $root_path . '/includes/Auth.php';

// Verificar se o usuário está logado e é administrador
// Verificar manualmente já que a plataforma foi renomeada e pode haver inconsistências nas variáveis de sessão
if ((!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') && 
    (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin')) {
    // Salvar URL atual para redirecionamento após login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Definir mensagem de erro
    $_SESSION['flash_message'] = "Você precisa estar logado como administrador para acessar esta página.";
    $_SESSION['flash_type'] = "danger";
    
    // Redirecionar para a página de login
    header('Location: ' . SITE_URL . '/?route=login');
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';
    $tabela = isset($_POST['tabela']) ? $_POST['tabela'] : '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    
    // Validar tabela
    $tabelas_validas = ['tipos_contrato', 'regimes_trabalho', 'niveis_experiencia'];
    if (!in_array($tabela, $tabelas_validas)) {
        $mensagem = "Tabela inválida.";
        $tipo_mensagem = "danger";
    } else {
        // Processar de acordo com a ação
        switch ($acao) {
            case 'adicionar':
                if (empty($nome)) {
                    $mensagem = "O nome é obrigatório.";
                    $tipo_mensagem = "danger";
                } else {
                    try {
                        // Verificar se já existe
                        $existe = $db->fetch("SELECT id FROM $tabela WHERE nome = ?", [$nome]);
                        if ($existe) {
                            $mensagem = "Este nome já existe na tabela.";
                            $tipo_mensagem = "warning";
                        } else {
                            $db->insert($tabela, [
                                'nome' => $nome,
                                'descricao' => $descricao,
                                'ativo' => 1
                            ]);
                            $mensagem = "Item adicionado com sucesso.";
                            $tipo_mensagem = "success";
                        }
                    } catch (Exception $e) {
                        $mensagem = "Erro ao adicionar item: " . $e->getMessage();
                        $tipo_mensagem = "danger";
                    }
                }
                break;
                
            case 'editar':
                if (empty($nome)) {
                    $mensagem = "O nome é obrigatório.";
                    $tipo_mensagem = "danger";
                } else {
                    try {
                        // Verificar se já existe com o mesmo nome (exceto o próprio registro)
                        $existe = $db->fetch("SELECT id FROM $tabela WHERE nome = ? AND id != ?", [$nome, $id]);
                        if ($existe) {
                            $mensagem = "Este nome já existe na tabela.";
                            $tipo_mensagem = "warning";
                        } else {
                            $db->update($tabela, [
                                'nome' => $nome,
                                'descricao' => $descricao
                            ], "id = $id");
                            $mensagem = "Item atualizado com sucesso.";
                            $tipo_mensagem = "success";
                        }
                    } catch (Exception $e) {
                        $mensagem = "Erro ao atualizar item: " . $e->getMessage();
                        $tipo_mensagem = "danger";
                    }
                }
                break;
                
            case 'excluir':
                try {
                    // Verificar se está sendo usado em alguma vaga
                    $coluna_id = '';
                    switch ($tabela) {
                        case 'tipos_contrato':
                            $coluna_id = 'tipo_contrato_id';
                            break;
                        case 'regimes_trabalho':
                            $coluna_id = 'regime_trabalho_id';
                            break;
                        case 'niveis_experiencia':
                            $coluna_id = 'nivel_experiencia_id';
                            break;
                    }
                    
                    $em_uso = $db->fetch("SELECT COUNT(*) as total FROM vagas WHERE $coluna_id = ?", [$id]);
                    
                    if ($em_uso && $em_uso['total'] > 0) {
                        $mensagem = "Este item não pode ser excluído pois está sendo usado em " . $em_uso['total'] . " vaga(s).";
                        $tipo_mensagem = "warning";
                    } else {
                        $db->delete($tabela, "id = $id");
                        $mensagem = "Item excluído com sucesso.";
                        $tipo_mensagem = "success";
                    }
                } catch (Exception $e) {
                    $mensagem = "Erro ao excluir item: " . $e->getMessage();
                    $tipo_mensagem = "danger";
                }
                break;
                
            case 'ativar':
            case 'desativar':
                try {
                    $ativo = ($acao === 'ativar') ? 1 : 0;
                    $db->update($tabela, ['ativo' => $ativo], "id = $id");
                    $mensagem = "Item " . ($ativo ? "ativado" : "desativado") . " com sucesso.";
                    $tipo_mensagem = "success";
                } catch (Exception $e) {
                    $mensagem = "Erro ao " . ($acao === 'ativar' ? "ativar" : "desativar") . " item: " . $e->getMessage();
                    $tipo_mensagem = "danger";
                }
                break;
        }
    }
}

// Obter dados das tabelas
try {
    $tipos_contrato = $db->fetchAll("SELECT * FROM tipos_contrato ORDER BY nome");
} catch (Exception $e) {
    $tipos_contrato = [];
    $mensagem = "Erro ao carregar tipos de contrato: " . $e->getMessage();
    $tipo_mensagem = "danger";
}

try {
    $regimes_trabalho = $db->fetchAll("SELECT * FROM regimes_trabalho ORDER BY nome");
} catch (Exception $e) {
    $regimes_trabalho = [];
    if (empty($mensagem)) {
        $mensagem = "Erro ao carregar regimes de trabalho: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

try {
    $niveis_experiencia = $db->fetchAll("SELECT * FROM niveis_experiencia ORDER BY nome");
} catch (Exception $e) {
    $niveis_experiencia = [];
    if (empty($mensagem)) {
        $mensagem = "Erro ao carregar níveis de experiência: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}
?>

<?php
// Adicionar estilos específicos para esta página
?>
<style>
    .tab-content {
        padding: 20px 0;
    }
    .btn-group-sm {
        margin-top: 5px;
    }
</style>

            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Gerenciar Opções de Vagas</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">Gerenciar Opções de Vagas</li>
                    </ol>
                    
                    <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                        <?php echo $mensagem; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-cogs me-1"></i>
                            Opções de Vagas
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tipos-contrato-tab" data-toggle="tab" href="#tipos-contrato" role="tab" aria-controls="tipos-contrato" aria-selected="true">Tipos de Contrato</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="regimes-trabalho-tab" data-toggle="tab" href="#regimes-trabalho" role="tab" aria-controls="regimes-trabalho" aria-selected="false">Regimes de Trabalho</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="niveis-experiencia-tab" data-toggle="tab" href="#niveis-experiencia" role="tab" aria-controls="niveis-experiencia" aria-selected="false">Níveis de Experiência</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <!-- Tipos de Contrato -->
                                <div class="tab-pane fade show active" id="tipos-contrato" role="tabpanel" aria-labelledby="tipos-contrato-tab">
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAdicionarItem" onclick="prepararAdicionar('tipos_contrato', 'Tipo de Contrato')">
                                            <i class="fas fa-plus"></i> Adicionar Tipo de Contrato
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nome</th>
                                                    <th>Descrição</th>
                                                    <th>Status</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($tipos_contrato)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Nenhum tipo de contrato encontrado</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($tipos_contrato as $tipo): ?>
                                                    <tr>
                                                        <td><?php echo $tipo['id']; ?></td>
                                                        <td><?php echo htmlspecialchars((string)$tipo['nome']); ?></td>
                                                        <td><?php echo htmlspecialchars((string)$tipo['descricao'] ?? ''); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $tipo['ativo'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $tipo['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-primary" onclick="prepararEditar('tipos_contrato', <?php echo $tipo['id']; ?>, '<?php echo addslashes($tipo['nome']); ?>', '<?php echo addslashes($tipo['descricao'] ?? ''); ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php if ($tipo['ativo']): ?>
                                                                <button type="button" class="btn btn-warning" onclick="confirmarAcao('desativar', 'tipos_contrato', <?php echo $tipo['id']; ?>, '<?php echo addslashes($tipo['nome']); ?>')">
                                                                    <i class="fas fa-ban"></i>
                                                                </button>
                                                                <?php else: ?>
                                                                <button type="button" class="btn btn-success" onclick="confirmarAcao('ativar', 'tipos_contrato', <?php echo $tipo['id']; ?>, '<?php echo addslashes($tipo['nome']); ?>')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                                <button type="button" class="btn btn-danger" onclick="confirmarAcao('excluir', 'tipos_contrato', <?php echo $tipo['id']; ?>, '<?php echo addslashes($tipo['nome']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Regimes de Trabalho -->
                                <div class="tab-pane fade" id="regimes-trabalho" role="tabpanel" aria-labelledby="regimes-trabalho-tab">
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAdicionarItem" onclick="prepararAdicionar('regimes_trabalho', 'Regime de Trabalho')">
                                            <i class="fas fa-plus"></i> Adicionar Regime de Trabalho
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nome</th>
                                                    <th>Descrição</th>
                                                    <th>Status</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($regimes_trabalho)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Nenhum regime de trabalho encontrado</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($regimes_trabalho as $regime): ?>
                                                    <tr>
                                                        <td><?php echo $regime['id']; ?></td>
                                                        <td><?php echo htmlspecialchars((string)$regime['nome']); ?></td>
                                                        <td><?php echo htmlspecialchars((string)$regime['descricao'] ?? ''); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $regime['ativo'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $regime['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-primary" onclick="prepararEditar('regimes_trabalho', <?php echo $regime['id']; ?>, '<?php echo addslashes($regime['nome']); ?>', '<?php echo addslashes($regime['descricao'] ?? ''); ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php if ($regime['ativo']): ?>
                                                                <button type="button" class="btn btn-warning" onclick="confirmarAcao('desativar', 'regimes_trabalho', <?php echo $regime['id']; ?>, '<?php echo addslashes($regime['nome']); ?>')">
                                                                    <i class="fas fa-ban"></i>
                                                                </button>
                                                                <?php else: ?>
                                                                <button type="button" class="btn btn-success" onclick="confirmarAcao('ativar', 'regimes_trabalho', <?php echo $regime['id']; ?>, '<?php echo addslashes($regime['nome']); ?>')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                                <button type="button" class="btn btn-danger" onclick="confirmarAcao('excluir', 'regimes_trabalho', <?php echo $regime['id']; ?>, '<?php echo addslashes($regime['nome']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Níveis de Experiência -->
                                <div class="tab-pane fade" id="niveis-experiencia" role="tabpanel" aria-labelledby="niveis-experiencia-tab">
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAdicionarItem" onclick="prepararAdicionar('niveis_experiencia', 'Nível de Experiência')">
                                            <i class="fas fa-plus"></i> Adicionar Nível de Experiência
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nome</th>
                                                    <th>Descrição</th>
                                                    <th>Status</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($niveis_experiencia)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Nenhum nível de experiência encontrado</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($niveis_experiencia as $nivel): ?>
                                                    <tr>
                                                        <td><?php echo $nivel['id']; ?></td>
                                                        <td><?php echo htmlspecialchars((string)$nivel['nome']); ?></td>
                                                        <td><?php echo htmlspecialchars((string)$nivel['descricao'] ?? ''); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $nivel['ativo'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $nivel['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-primary" onclick="prepararEditar('niveis_experiencia', <?php echo $nivel['id']; ?>, '<?php echo addslashes($nivel['nome']); ?>', '<?php echo addslashes($nivel['descricao'] ?? ''); ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php if ($nivel['ativo']): ?>
                                                                <button type="button" class="btn btn-warning" onclick="confirmarAcao('desativar', 'niveis_experiencia', <?php echo $nivel['id']; ?>, '<?php echo addslashes($nivel['nome']); ?>')">
                                                                    <i class="fas fa-ban"></i>
                                                                </button>
                                                                <?php else: ?>
                                                                <button type="button" class="btn btn-success" onclick="confirmarAcao('ativar', 'niveis_experiencia', <?php echo $nivel['id']; ?>, '<?php echo addslashes($nivel['nome']); ?>')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                                <button type="button" class="btn btn-danger" onclick="confirmarAcao('excluir', 'niveis_experiencia', <?php echo $nivel['id']; ?>, '<?php echo addslashes($nivel['nome']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Modal Adicionar Item -->
    <div class="modal fade" id="modalAdicionarItem" tabindex="-1" aria-labelledby="modalAdicionarItemLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdicionarItemLabel">Adicionar Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="adicionar">
                        <input type="hidden" name="tabela" id="adicionar_tabela">
                        
                        <div class="mb-3">
                            <label for="adicionar_nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="adicionar_nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adicionar_descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="adicionar_descricao" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Item -->
    <div class="modal fade" id="modalEditarItem" tabindex="-1" aria-labelledby="modalEditarItemLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarItemLabel">Editar Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="tabela" id="editar_tabela">
                        <input type="hidden" name="id" id="editar_id">
                        
                        <div class="mb-3">
                            <label for="editar_nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="editar_nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="editar_descricao" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
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
                    <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                </div>
                <div class="modal-body">
                    <p id="mensagem_confirmacao"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <form method="POST">
                        <input type="hidden" name="acao" id="acao_confirmacao">
                        <input type="hidden" name="tabela" id="tabela_confirmacao">
                        <input type="hidden" name="id" id="id_confirmacao">
                        <button type="submit" class="btn btn-danger">Confirmar</button>
                    </form>
                </div>
            </div>
<script>
    function prepararAdicionar(tabela, tipo) {
        document.getElementById('modalAdicionarItemLabel').textContent = 'Adicionar ' + tipo;
        document.getElementById('adicionar_tabela').value = tabela;
    }
    
    function prepararEditar(tabela, id, nome, descricao) {
        let tipoItem = '';
        switch (tabela) {
            case 'tipos_contrato':
                tipoItem = 'Tipo de Contrato';
                break;
            case 'regimes_trabalho':
                tipoItem = 'Regime de Trabalho';
                break;
            case 'niveis_experiencia':
                tipoItem = 'Nível de Experiência';
                break;
        }
        
        document.getElementById('modalEditarItemLabel').textContent = 'Editar ' + tipoItem;
        document.getElementById('editar_tabela').value = tabela;
        document.getElementById('editar_id').value = id;
        document.getElementById('editar_nome').value = nome;
        document.getElementById('editar_descricao').value = descricao;
        
        $('#modalEditarItem').modal('show');
    }
    
    function confirmarAcao(acao, tabela, id, nome) {
        let mensagem = '';
        let tipoItem = '';
        
        switch (tabela) {
            case 'tipos_contrato':
                tipoItem = 'tipo de contrato';
                break;
            case 'regimes_trabalho':
                tipoItem = 'regime de trabalho';
                break;
            case 'niveis_experiencia':
                tipoItem = 'nível de experiência';
                break;
        }
        
        switch (acao) {
            case 'excluir':
                mensagem = `Tem certeza que deseja excluir o ${tipoItem} "${nome}"?`;
                break;
            case 'ativar':
                mensagem = `Tem certeza que deseja ativar o ${tipoItem} "${nome}"?`;
                break;
            case 'desativar':
                mensagem = `Tem certeza que deseja desativar o ${tipoItem} "${nome}"?`;
                break;
        }
        
        document.getElementById('mensagem_confirmacao').textContent = mensagem;
        document.getElementById('acao_confirmacao').value = acao;
        document.getElementById('tabela_confirmacao').value = tabela;
        document.getElementById('id_confirmacao').value = id;
        
        $('#modalConfirmacao').modal('show');
    }
</script>
