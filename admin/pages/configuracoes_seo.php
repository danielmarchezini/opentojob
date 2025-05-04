<?php
// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=entrar");
    exit;
}

// Obter instância do banco de dados
$db = Database::getInstance();

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_configuracoes_seo') {
    // Obter dados do formulário
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';
    $google_tag_manager_id = isset($_POST['google_tag_manager_id']) ? trim($_POST['google_tag_manager_id']) : '';
    $sitemap_auto_update = isset($_POST['sitemap_auto_update']) ? '1' : '0';
    $robots_txt_personalizado = isset($_POST['robots_txt_personalizado']) ? trim($_POST['robots_txt_personalizado']) : '';
    
    // Gerar códigos do GTM com base no ID
    $gtm_code_head = '';
    $gtm_code_body = '';
    
    if (!empty($google_tag_manager_id)) {
        $gtm_code_head = "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','" . $google_tag_manager_id . "');</script>
<!-- End Google Tag Manager -->";

        $gtm_code_body = "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=" . $google_tag_manager_id . "\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->";
    }
    
    // Atualizar configurações no banco de dados
    try {
        // Atualizar meta description
        $db->update('configuracoes_seo', [
            'valor' => $meta_description,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'chave = :chave', ['chave' => 'meta_description']);
        
        // Atualizar meta keywords
        $db->update('configuracoes_seo', [
            'valor' => $meta_keywords,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'chave = :chave', ['chave' => 'meta_keywords']);
        
        // Atualizar Google Tag Manager ID
        $db->update('configuracoes_seo', [
            'valor' => $google_tag_manager_id,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'chave = :chave', ['chave' => 'google_tag_manager_id']);
        
        // Atualizar códigos do GTM
        $db->update('configuracoes_seo', [
            'valor' => $gtm_code_head,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'chave = :chave', ['chave' => 'google_tag_manager_code_head']);
        
        $db->update('configuracoes_seo', [
            'valor' => $gtm_code_body,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'chave = :chave', ['chave' => 'google_tag_manager_code_body']);
        
        // Atualizar configuração de atualização automática do sitemap
        $db->update('configuracoes_seo', [
            'valor' => $sitemap_auto_update,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'chave = :chave', ['chave' => 'sitemap_auto_update']);
        
        // Atualizar robots.txt personalizado
        $db->update('configuracoes_seo', [
            'valor' => $robots_txt_personalizado,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ], 'chave = :chave', ['chave' => 'robots_txt_personalizado']);
        
        // Gerar sitemap se a opção estiver ativada
        if ($sitemap_auto_update === '1') {
            // Executar o script de geração do sitemap
            include_once($root_path . '/sitemap.php');
        }
        
        // Atualizar robots.txt se tiver conteúdo personalizado
        if (!empty($robots_txt_personalizado)) {
            file_put_contents($root_path . '/robots.txt', $robots_txt_personalizado);
        }
        
        $_SESSION['flash_message'] = "Configurações de SEO atualizadas com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Erro ao atualizar configurações: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    // Redirecionar para evitar reenvio do formulário
    header("Location: " . SITE_URL . "/?route=configuracoes_seo_admin");
    exit;
}

// Obter configurações atuais
$configuracoes = [];
$configs_db = $db->fetchAll("SELECT chave, valor FROM configuracoes_seo");

foreach ($configs_db as $config) {
    $configuracoes[$config['chave']] = $config['valor'];
}

// Valores padrão caso não existam no banco
$meta_description = $configuracoes['meta_description'] ?? '';
$meta_keywords = $configuracoes['meta_keywords'] ?? '';
$google_tag_manager_id = $configuracoes['google_tag_manager_id'] ?? '';
$sitemap_auto_update = $configuracoes['sitemap_auto_update'] ?? '1';
$robots_txt_personalizado = $configuracoes['robots_txt_personalizado'] ?? '';

// Definir caminho para a raiz do projeto
$root_path = dirname(dirname(dirname(__FILE__)));

// Se robots.txt personalizado estiver vazio, usar o padrão
if (empty($robots_txt_personalizado) && file_exists($root_path . '/robots.txt')) {
    $robots_txt_personalizado = file_get_contents($root_path . '/robots.txt');
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Configurações de SEO e Analytics</h1>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Configurações de SEO e Google Tag Manager</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo SITE_URL; ?>/?route=configuracoes_seo_admin" method="post">
                        <input type="hidden" name="acao" value="salvar_configuracoes_seo">
                        
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($meta_description); ?></textarea>
                            <div class="form-text">Descrição que aparece nos resultados de busca. Recomendado: até 160 caracteres.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($meta_keywords); ?>">
                            <div class="form-text">Palavras-chave separadas por vírgula.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="google_tag_manager_id" class="form-label">ID do Google Tag Manager</label>
                            <input type="text" class="form-control" id="google_tag_manager_id" name="google_tag_manager_id" value="<?php echo htmlspecialchars($google_tag_manager_id); ?>" placeholder="GTM-XXXXXX">
                            <div class="form-text">Formato: GTM-XXXXXX. O código será gerado automaticamente.</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="sitemap_auto_update" name="sitemap_auto_update" value="1" <?php echo $sitemap_auto_update === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sitemap_auto_update">Atualizar sitemap automaticamente</label>
                            <div class="form-text">Se marcado, o sitemap.xml será atualizado sempre que estas configurações forem salvas.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="robots_txt_personalizado" class="form-label">Conteúdo do robots.txt</label>
                            <textarea class="form-control" id="robots_txt_personalizado" name="robots_txt_personalizado" rows="6"><?php echo htmlspecialchars($robots_txt_personalizado); ?></textarea>
                            <div class="form-text">Conteúdo personalizado para o arquivo robots.txt. Deixe em branco para usar o padrão.</div>
                        </div>
                        
                        <div class="mb-3">
                            <p><strong>Sitemap:</strong> <a href="<?php echo SITE_URL; ?>/sitemap.php" target="_blank"><?php echo SITE_URL; ?>/sitemap.php</a></p>
                            <p><strong>Robots.txt:</strong> <a href="<?php echo SITE_URL; ?>/robots.txt" target="_blank"><?php echo SITE_URL; ?>/robots.txt</a></p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
