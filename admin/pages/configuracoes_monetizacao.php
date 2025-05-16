<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $ativa = isset($_POST['ativa']) ? 1 : 0;
    $codigo_adsense = isset($_POST['codigo_adsense']) ? trim($_POST['codigo_adsense']) : '';
    
    // Obter posições ativas
    $posicoes = [
        'inicio_topo', 'inicio_meio', 'inicio_rodape',
        'vagas_topo', 'vagas_lista', 'vagas_lateral',
        'talentos_topo', 'talentos_lista',
        'empresas_topo', 'empresas_lista',
        'perfis_linkedin_topo', 'perfis_linkedin_lista',
        'blog_topo', 'blog_conteudo', 'blog_lateral',
        'cadastro_empresa_lateral'
    ];
    
    $posicoes_ativas = [];
    foreach ($posicoes as $posicao) {
        $posicoes_ativas[$posicao] = isset($_POST[$posicao]) ? 1 : 0;
    }
    
    // Atualizar configurações no banco de dados
    $db->update(
        'configuracoes_monetizacao',
        [
            'ativa' => $ativa,
            'codigo_adsense' => $codigo_adsense,
            'posicoes_ativas' => json_encode($posicoes_ativas)
        ],
        'id = 1'
    );
    
    // Definir mensagem de sucesso
    $_SESSION['flash_message'] = "Configurações de monetização atualizadas com sucesso!";
    $_SESSION['flash_type'] = "success";
    
    // Em vez de redirecionar, definir uma variável para mostrar a mensagem
    $mensagem_sucesso = true;
}

// Obter configurações atuais
$config = $db->fetchRow("SELECT * FROM configuracoes_monetizacao WHERE id = 1");

// Se não existir configuração, criar uma padrão
if (!$config) {
    $posicoes_padrao = [
        'inicio_topo' => 0, 'inicio_meio' => 0, 'inicio_rodape' => 0,
        'vagas_topo' => 0, 'vagas_lista' => 0, 'vagas_lateral' => 0,
        'talentos_topo' => 0, 'talentos_lista' => 0,
        'empresas_topo' => 0, 'empresas_lista' => 0,
        'perfis_linkedin_topo' => 0, 'perfis_linkedin_lista' => 0,
        'blog_topo' => 0, 'blog_conteudo' => 0, 'blog_lateral' => 0,
        'cadastro_empresa_lateral' => 0
    ];
    
    $db->insert('configuracoes_monetizacao', [
        'ativa' => 0,
        'codigo_adsense' => '',
        'posicoes_ativas' => json_encode($posicoes_padrao)
    ]);
    
    $config = $db->fetchRow("SELECT * FROM configuracoes_monetizacao WHERE id = 1");
}

// Decodificar posições ativas
$posicoes_ativas = json_decode($config['posicoes_ativas'], true);
if (!$posicoes_ativas) {
    $posicoes_ativas = [];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Configurações de Monetização</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Configurações de Monetização</li>
    </ol>
    
    <?php if (isset($mensagem_sucesso)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Configurações de monetização atualizadas com sucesso!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
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
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-money-bill-wave me-1"></i>
            Configurações do Google AdSense
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ativa" name="ativa" <?php echo ($config['ativa'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativa">Ativar monetização</label>
                    </div>
                    <small class="text-muted">Quando desativada, nenhum anúncio será exibido no site, independente das configurações abaixo.</small>
                </div>
                
                <div class="mb-4">
                    <label for="codigo_adsense" class="form-label">Código do Google AdSense</label>
                    <textarea class="form-control" id="codigo_adsense" name="codigo_adsense" rows="3" placeholder="Cole aqui o código de publicador do Google AdSense"><?php echo htmlspecialchars((string)$config['codigo_adsense']); ?></textarea>
                    <small class="text-muted">Exemplo: <code>data-ad-client="ca-pub-1234567890123456"</code></small>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Posições de Anúncios</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Selecione as posições onde deseja exibir anúncios em cada página:</p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Página Inicial</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="inicio_topo" name="inicio_topo" <?php echo (isset($posicoes_ativas['inicio_topo']) && $posicoes_ativas['inicio_topo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="inicio_topo">Topo da página</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="inicio_meio" name="inicio_meio" <?php echo (isset($posicoes_ativas['inicio_meio']) && $posicoes_ativas['inicio_meio'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="inicio_meio">Meio da página</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="inicio_rodape" name="inicio_rodape" <?php echo (isset($posicoes_ativas['inicio_rodape']) && $posicoes_ativas['inicio_rodape'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="inicio_rodape">Antes do rodapé</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Página de Vagas</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="vagas_topo" name="vagas_topo" <?php echo (isset($posicoes_ativas['vagas_topo']) && $posicoes_ativas['vagas_topo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="vagas_topo">Topo da página</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="vagas_lista" name="vagas_lista" <?php echo (isset($posicoes_ativas['vagas_lista']) && $posicoes_ativas['vagas_lista'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="vagas_lista">Entre os itens da lista (formato de vaga)</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="vagas_lateral" name="vagas_lateral" <?php echo (isset($posicoes_ativas['vagas_lateral']) && $posicoes_ativas['vagas_lateral'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="vagas_lateral">Barra lateral</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Página de Talentos</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="talentos_topo" name="talentos_topo" <?php echo (isset($posicoes_ativas['talentos_topo']) && $posicoes_ativas['talentos_topo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="talentos_topo">Topo da página</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="talentos_lista" name="talentos_lista" <?php echo (isset($posicoes_ativas['talentos_lista']) && $posicoes_ativas['talentos_lista'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="talentos_lista">Entre os itens da lista</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Página de Empresas</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="empresas_topo" name="empresas_topo" <?php echo (isset($posicoes_ativas['empresas_topo']) && $posicoes_ativas['empresas_topo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="empresas_topo">Topo da página</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="empresas_lista" name="empresas_lista" <?php echo (isset($posicoes_ativas['empresas_lista']) && $posicoes_ativas['empresas_lista'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="empresas_lista">Entre os itens da lista</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Página de Perfis LinkedIn</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="perfis_linkedin_topo" name="perfis_linkedin_topo" <?php echo (isset($posicoes_ativas['perfis_linkedin_topo']) && $posicoes_ativas['perfis_linkedin_topo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="perfis_linkedin_topo">Topo da página</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="perfis_linkedin_lista" name="perfis_linkedin_lista" <?php echo (isset($posicoes_ativas['perfis_linkedin_lista']) && $posicoes_ativas['perfis_linkedin_lista'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="perfis_linkedin_lista">Entre os itens da lista</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Página de Blog</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="blog_topo" name="blog_topo" <?php echo (isset($posicoes_ativas['blog_topo']) && $posicoes_ativas['blog_topo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="blog_topo">Topo da página</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="blog_conteudo" name="blog_conteudo" <?php echo (isset($posicoes_ativas['blog_conteudo']) && $posicoes_ativas['blog_conteudo'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="blog_conteudo">Dentro do conteúdo do artigo</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="blog_lateral" name="blog_lateral" <?php echo (isset($posicoes_ativas['blog_lateral']) && $posicoes_ativas['blog_lateral'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="blog_lateral">Barra lateral</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Página de Cadastro de Empresa</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="cadastro_empresa_lateral" name="cadastro_empresa_lateral" <?php echo (isset($posicoes_ativas['cadastro_empresa_lateral']) && $posicoes_ativas['cadastro_empresa_lateral'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cadastro_empresa_lateral">Barra lateral</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salvar Configurações
                    </button>
                    <button type="button" class="btn btn-success" id="btnPrevisualizar">
                        <i class="fas fa-eye me-1"></i> Previsualizar Anúncios
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-info-circle me-1"></i>
            Instruções de Uso
        </div>
        <div class="card-body">
            <h5>Como configurar o Google AdSense no seu site</h5>
            <ol>
                <li>Crie uma conta no <a href="https://www.google.com/adsense" target="_blank">Google AdSense</a> se ainda não tiver uma.</li>
                <li>Adicione seu site ao AdSense e aguarde a aprovação do Google.</li>
                <li>Após a aprovação, copie o código do publicador fornecido pelo Google e cole-o no campo "Código do Google AdSense" acima.</li>
                <li>Selecione as posições onde deseja exibir anúncios em cada página do site.</li>
                <li>Ative a monetização usando o botão no topo desta página.</li>
                <li>Salve as configurações e verifique se os anúncios estão sendo exibidos corretamente.</li>
            </ol>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Importante:</strong> O excesso de anúncios pode prejudicar a experiência do usuário. Recomendamos usar anúncios com moderação.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botão de previsualização
    document.getElementById('btnPrevisualizar').addEventListener('click', function() {
        alert('Recurso de previsualização em desenvolvimento. Por enquanto, salve as configurações e verifique os anúncios diretamente no site.');
    });
});
</script>
