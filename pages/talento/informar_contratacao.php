<?php
// Verificar se o usuário está logado e é um talento
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'talento') {
    echo "<script>window.location.href = '" . SITE_URL . "/?route=acesso_negado';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();

// Verificar se já existe uma contratação pendente
$contratacao_pendente = $db->fetch("SELECT * FROM contratacoes WHERE talento_id = :talento_id AND status = 'pendente'", [
    'talento_id' => $user_id
]);

// Verificar se o formulário foi enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['informar_contratacao'])) {
    // Obter dados do formulário
    $empresa_nome = trim($_POST['empresa_nome']);
    $cargo = trim($_POST['cargo']);
    $descricao = trim($_POST['descricao']);
    $empresa_id = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
    $vaga_id = !empty($_POST['vaga_id']) ? (int)$_POST['vaga_id'] : null;
    
    // Validar dados
    if (empty($empresa_nome) || empty($cargo)) {
        $mensagem = "Por favor, preencha o nome da empresa e o cargo.";
        $tipo_mensagem = "danger";
    } else {
        try {
            // Inserir contratação
            $db->query("INSERT INTO contratacoes (talento_id, empresa_id, vaga_id, empresa_nome, cargo, descricao, data_contratacao, status) 
                       VALUES (:talento_id, :empresa_id, :vaga_id, :empresa_nome, :cargo, :descricao, NOW(), 'pendente')", [
                'talento_id' => $user_id,
                'empresa_id' => $empresa_id,
                'vaga_id' => $vaga_id,
                'empresa_nome' => $empresa_nome,
                'cargo' => $cargo,
                'descricao' => $descricao
            ]);
            
            // Enviar notificação para o admin
            $db->query("INSERT INTO notificacoes (usuario_id, tipo, mensagem, link, data_criacao, lida) 
                       VALUES (:admin_id, 'contratacao', :mensagem, :link, NOW(), 0)", [
                'admin_id' => 1, // ID do administrador
                'mensagem' => "Novo registro de contratação informado por um talento",
                'link' => SITE_URL . "/?route=gerenciar_contratacoes"
            ]);
            
            $_SESSION['flash_message'] = "Contratação informada com sucesso! Agradecemos por compartilhar essa conquista conosco.";
            $_SESSION['flash_type'] = "success";
            echo "<script>window.location.href = '" . SITE_URL . "/?route=informar_contratacao&success=1';</script>";
            exit;
        } catch (Exception $e) {
            $mensagem = "Erro ao registrar contratação: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    }
}

// Buscar empresas para o select
$empresas = $db->fetchAll("SELECT u.id, u.nome, e.razao_social 
                         FROM usuarios u 
                         JOIN empresas e ON u.id = e.usuario_id 
                         WHERE u.tipo = 'empresa' AND u.status = 'ativo' 
                         ORDER BY u.nome ASC");

// Buscar vagas para o select
$vagas = $db->fetchAll("SELECT v.id, v.titulo, u.nome as empresa_nome 
                      FROM vagas v 
                      JOIN usuarios u ON v.empresa_id = u.id 
                      WHERE v.status = 'aberta' 
                      ORDER BY v.data_publicacao DESC 
                      LIMIT 100");

// Exibir mensagem de sucesso da URL
if (isset($_GET['success']) && $_GET['success'] == '1' && empty($mensagem)) {
    $mensagem = "Sua contratação foi informada com sucesso! Após a confirmação, seu depoimento poderá ser exibido na página inicial.";
    $tipo_mensagem = "success";
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Informar Contratação</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensagem; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($contratacao_pendente): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Contratação Pendente</h5>
                        <p>Você já possui uma contratação pendente de confirmação:</p>
                        <ul>
                            <li><strong>Empresa:</strong> <?php echo htmlspecialchars($contratacao_pendente['empresa_nome']); ?></li>
                            <li><strong>Cargo:</strong> <?php echo htmlspecialchars($contratacao_pendente['cargo']); ?></li>
                            <li><strong>Data informada:</strong> <?php echo date('d/m/Y', strtotime($contratacao_pendente['data_contratacao'])); ?></li>
                        </ul>
                        <p>Nossa equipe está analisando sua informação. Você será notificado assim que a contratação for confirmada.</p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <p><i class="fas fa-info-circle"></i> Você foi contratado através do OpenToJob? Informe-nos para que possamos celebrar seu sucesso!</p>
                        <p>Após a confirmação, você poderá ser convidado a compartilhar um depoimento sobre sua experiência.</p>
                    </div>
                    
                    <form method="post" action="">
                        <div class="form-group mb-3">
                            <label for="empresa_id" class="form-label">Empresa cadastrada no OpenToJob (opcional):</label>
                            <select class="form-control" id="empresa_id" name="empresa_id">
                                <option value="">Selecione a empresa (se estiver cadastrada)</option>
                                <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id']; ?>">
                                    <?php echo htmlspecialchars($empresa['nome']); ?>
                                    <?php if (!empty($empresa['razao_social'])): ?>
                                    (<?php echo htmlspecialchars($empresa['razao_social']); ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Se a empresa não estiver na lista, você pode informar o nome manualmente abaixo.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="empresa_nome" class="form-label">Nome da Empresa: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="empresa_nome" name="empresa_nome" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="vaga_id" class="form-label">Vaga do OpenToJob (opcional):</label>
                            <select class="form-control" id="vaga_id" name="vaga_id">
                                <option value="">Selecione a vaga (se aplicável)</option>
                                <?php foreach ($vagas as $vaga): ?>
                                <option value="<?php echo $vaga['id']; ?>">
                                    <?php echo htmlspecialchars($vaga['titulo']); ?> - 
                                    <?php echo htmlspecialchars($vaga['empresa_nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="cargo" class="form-label">Cargo/Função: <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cargo" name="cargo" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="descricao" class="form-label">Como o OpenToJob ajudou na sua contratação?</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Conte-nos como o OpenToJob contribuiu para sua contratação..."></textarea>
                            <small class="form-text text-muted">Este texto poderá ser usado como depoimento na página inicial (com sua autorização).</small>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="autorizar_depoimento" name="autorizar_depoimento" value="1">
                            <label class="form-check-label" for="autorizar_depoimento">
                                Autorizo o OpenToJob a utilizar meu depoimento na página inicial e materiais promocionais.
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/?route=perfil_talento_editar" class="btn btn-secondary">Voltar</a>
                            <button type="submit" name="informar_contratacao" class="btn btn-primary">
                                Informar Contratação
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preencher automaticamente o nome da empresa quando selecionar do dropdown
    const empresaSelect = document.getElementById('empresa_id');
    const empresaNomeInput = document.getElementById('empresa_nome');
    
    if (empresaSelect && empresaNomeInput) {
        empresaSelect.addEventListener('change', function() {
            if (this.options[this.selectedIndex]) {
                const empresaNome = this.options[this.selectedIndex].text.split('(')[0].trim();
                if (empresaNome && empresaNome !== "Selecione a empresa (se estiver cadastrada)") {
                    empresaNomeInput.value = empresaNome;
                }
            }
        });
    }
});
</script>
