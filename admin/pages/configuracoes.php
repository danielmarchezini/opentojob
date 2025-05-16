<?php
// Iniciar o buffer de saída para evitar problemas com o header
ob_start();

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar formulário de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_configuracoes'])) {
    // Validar e salvar configurações
    $configuracoes = [
        'site_titulo' => htmlspecialchars((string)trim($_POST['site_titulo'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'site_descricao' => htmlspecialchars((string)trim($_POST['site_descricao'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'email_contato' => filter_input(INPUT_POST, 'email_contato', FILTER_SANITIZE_EMAIL),
        'telefone_contato' => htmlspecialchars((string)trim($_POST['telefone_contato'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'endereco' => htmlspecialchars((string)trim($_POST['endereco'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'redes_sociais_facebook' => filter_input(INPUT_POST, 'redes_sociais_facebook', FILTER_SANITIZE_URL),
        'redes_sociais_instagram' => filter_input(INPUT_POST, 'redes_sociais_instagram', FILTER_SANITIZE_URL),
        'redes_sociais_linkedin' => filter_input(INPUT_POST, 'redes_sociais_linkedin', FILTER_SANITIZE_URL),
        'redes_sociais_twitter' => filter_input(INPUT_POST, 'redes_sociais_twitter', FILTER_SANITIZE_URL),
        'manutencao_ativo' => isset($_POST['manutencao_ativo']) ? 1 : 0,
        'registro_empresas_aprovacao' => isset($_POST['registro_empresas_aprovacao']) ? 1 : 0,
        'registro_talentos_aprovacao' => isset($_POST['registro_talentos_aprovacao']) ? 1 : 0,
        'sistema_vagas_internas_ativo' => isset($_POST['sistema_vagas_internas_ativo']) ? 1 : 0,
        'sistema_demandas_talentos_ativo' => isset($_POST['sistema_demandas_talentos_ativo']) ? 1 : 0
    ];
    
    // Verificar se a tabela existe
    $tabela_existe = $db->fetch("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'configuracoes'");
    
    if (!$tabela_existe || $tabela_existe['count'] == 0) {
        // Criar tabela de configurações se não existir
        $db->query("
            CREATE TABLE IF NOT EXISTS configuracoes (
                chave VARCHAR(100) PRIMARY KEY,
                valor TEXT NULL,
                descricao VARCHAR(255) NULL,
                tipo VARCHAR(50) DEFAULT 'texto'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    
    // Salvar cada configuração
    foreach ($configuracoes as $chave => $valor) {
        // Verificar se a configuração já existe
        $config_existe = $db->fetch("SELECT COUNT(*) as count FROM configuracoes WHERE chave = :chave", ['chave' => $chave]);
        
        if ($config_existe && $config_existe['count'] > 0) {
            // Atualizar configuração existente
            $db->update('configuracoes', [
                'valor' => $valor
            ], 'chave = :chave', [
                'chave' => $chave
            ]);
        } else {
            // Inserir nova configuração
            $db->insert('configuracoes', [
                'chave' => $chave,
                'valor' => $valor,
                'descricao' => ucfirst(str_replace('_', ' ', $chave)),
                'tipo' => 'texto'
            ]);
        }
    }
    
    // Mensagem de sucesso
    $_SESSION['flash_message'] = "Configurações salvas com sucesso!";
    $_SESSION['flash_type'] = "success";
    
    // Usar JavaScript para redirecionar em vez de header() para evitar problemas com saída já enviada
    echo "<script>window.location.href = '" . SITE_URL . "/?route=configuracoes_admin';</script>";
    exit;
}

// Obter configurações atuais
$configuracoes = [];
$tabela_existe = $db->fetch("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'configuracoes'");

if ($tabela_existe && $tabela_existe['count'] > 0) {
    try {
        $configs = $db->fetchAll("SELECT chave, valor FROM configuracoes");
        foreach ($configs as $config) {
            $configuracoes[$config['chave']] = $config['valor'];
        }
    } catch (Exception $e) {
        // Silenciar erros
    }
}

// Definir valores padrão para configurações não existentes
$defaults = [
    'site_titulo' => 'Open2W - Plataforma de Recrutamento',
    'site_descricao' => 'Conectando talentos e empresas',
    'email_contato' => 'contato@open2w.com',
    'telefone_contato' => '(11) 1234-5678',
    'endereco' => 'Av. Paulista, 1000 - São Paulo/SP',
    'redes_sociais_facebook' => 'https://facebook.com/open2w',
    'redes_sociais_instagram' => 'https://instagram.com/open2w',
    'redes_sociais_linkedin' => 'https://linkedin.com/company/open2w',
    'redes_sociais_twitter' => 'https://twitter.com/open2w',
    'manutencao_ativo' => 0,
    'registro_empresas_aprovacao' => 1,
    'registro_talentos_aprovacao' => 0,
    'sistema_vagas_internas_ativo' => 0,
    'sistema_demandas_talentos_ativo' => 1
];

// Mesclar configurações com valores padrão
foreach ($defaults as $chave => $valor) {
    if (!isset($configuracoes[$chave])) {
        $configuracoes[$chave] = $valor;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Configurações do Sistema</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Configurações</li>
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
        <form method="post" action="">
            <div class="row">
                <!-- Configurações Gerais -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Configurações Gerais</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="site_titulo" class="form-label">Título do Site</label>
                                <input type="text" class="form-control" id="site_titulo" name="site_titulo" value="<?php echo htmlspecialchars((string)$configuracoes['site_titulo']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="site_descricao" class="form-label">Descrição do Site</label>
                                <textarea class="form-control" id="site_descricao" name="site_descricao" rows="3"><?php echo htmlspecialchars((string)$configuracoes['site_descricao']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="email_contato" class="form-label">E-mail de Contato</label>
                                <input type="email" class="form-control" id="email_contato" name="email_contato" value="<?php echo htmlspecialchars((string)$configuracoes['email_contato']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="telefone_contato" class="form-label">Telefone de Contato</label>
                                <input type="text" class="form-control" id="telefone_contato" name="telefone_contato" value="<?php echo htmlspecialchars((string)$configuracoes['telefone_contato']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="endereco" class="form-label">Endereço</label>
                                <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo htmlspecialchars((string)$configuracoes['endereco']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Redes Sociais e Configurações de Sistema -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Redes Sociais</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="redes_sociais_facebook" class="form-label">Facebook</label>
                                <input type="url" class="form-control" id="redes_sociais_facebook" name="redes_sociais_facebook" value="<?php echo htmlspecialchars((string)$configuracoes['redes_sociais_facebook']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="redes_sociais_instagram" class="form-label">Instagram</label>
                                <input type="url" class="form-control" id="redes_sociais_instagram" name="redes_sociais_instagram" value="<?php echo htmlspecialchars((string)$configuracoes['redes_sociais_instagram']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="redes_sociais_linkedin" class="form-label">LinkedIn</label>
                                <input type="url" class="form-control" id="redes_sociais_linkedin" name="redes_sociais_linkedin" value="<?php echo htmlspecialchars((string)$configuracoes['redes_sociais_linkedin']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="redes_sociais_twitter" class="form-label">Twitter</label>
                                <input type="url" class="form-control" id="redes_sociais_twitter" name="redes_sociais_twitter" value="<?php echo htmlspecialchars((string)$configuracoes['redes_sociais_twitter']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Configurações do Sistema</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="manutencao_ativo" name="manutencao_ativo" <?php echo $configuracoes['manutencao_ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="manutencao_ativo">Modo de Manutenção Ativo</label>
                                </div>
                                <small class="form-text text-muted">Quando ativado, apenas administradores poderão acessar o site.</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="registro_empresas_aprovacao" name="registro_empresas_aprovacao" <?php echo $configuracoes['registro_empresas_aprovacao'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="registro_empresas_aprovacao">Exigir aprovação para cadastro de empresas</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="registro_talentos_aprovacao" name="registro_talentos_aprovacao" <?php echo $configuracoes['registro_talentos_aprovacao'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="registro_talentos_aprovacao">Exigir aprovação para cadastro de talentos</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="sistema_vagas_internas_ativo" name="sistema_vagas_internas_ativo" <?php echo $configuracoes['sistema_vagas_internas_ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sistema_vagas_internas_ativo">Habilitar sistema de vagas e candidaturas internas</label>
                                </div>
                                <small class="form-text text-muted">Quando desativado, as empresas não poderão cadastrar vagas nem receber candidaturas. Apenas vagas externas cadastradas pelo administrador serão exibidas.</small>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="sistema_demandas_talentos_ativo" name="sistema_demandas_talentos_ativo" <?php echo $configuracoes['sistema_demandas_talentos_ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sistema_demandas_talentos_ativo">Habilitar sistema de demandas de talentos</label>
                                </div>
                                <small class="form-text text-muted">Quando ativado, as empresas poderão publicar demandas de talentos e os talentos poderão demonstrar interesse.</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botões de Ação -->
                <div class="col-12 text-center mb-4">
                    <button type="submit" name="salvar_configuracoes" class="btn btn-primary btn-lg">
                        <i class="fas fa-save mr-2"></i> Salvar Configurações
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
// Validação do formulário
document.querySelector('form').addEventListener('submit', function(e) {
    const emailContato = document.getElementById('email_contato').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(emailContato)) {
        e.preventDefault();
        alert('Por favor, insira um endereço de e-mail válido.');
        document.getElementById('email_contato').focus();
    }
});
</script>
