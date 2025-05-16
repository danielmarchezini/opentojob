<?php
// Definir caminho raiz do site
$root_path = dirname(dirname(__DIR__)); // Subir dois níveis a partir de admin/pages

// Verificar se o usuário está logado e é um administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['flash_message'] = "Acesso restrito. Faça login como administrador.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . SITE_URL . "/?route=admin_login");
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
            // Em vez de incluir o arquivo sitemap.php, vamos fazer uma requisição ao sitemap_generator.php
            try {
                // Usar file_get_contents com contexto para fazer uma requisição HTTP
                $opts = [
                    'http' => [
                        'method' => 'GET',
                        'header' => 'Content-Type: application/x-www-form-urlencoded'
                    ]
                ];
                $context = stream_context_create($opts);
                file_get_contents(SITE_URL . '/sitemap_generator.php?save=1&nocache=' . time(), false, $context);
                
                // Não precisamos do resultado, apenas queremos que o sitemap seja gerado
            } catch (Exception $e) {
                error_log("Erro ao gerar sitemap: " . $e->getMessage());
                $_SESSION['flash_message'] .= " Aviso: Não foi possível gerar o sitemap automaticamente.";
            }
        }
        
        // Atualizar robots.txt se tiver conteúdo personalizado
        if (!empty($robots_txt_personalizado)) {
            try {
                if (is_writable($root_path) || (file_exists($root_path . '/robots.txt') && is_writable($root_path . '/robots.txt'))) {
                    file_put_contents($root_path . '/robots.txt', $robots_txt_personalizado);
                } else {
                    $_SESSION['flash_message'] .= " Aviso: Não foi possível escrever no arquivo robots.txt (permissão negada).";
                }
            } catch (Exception $e) {
                $_SESSION['flash_message'] .= " Erro ao atualizar robots.txt: " . $e->getMessage();
            }
        }
        
        $_SESSION['flash_message'] = "Configurações de SEO atualizadas com sucesso!";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Erro ao atualizar configurações: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    
    // Redirecionar para evitar reenvio do formulário
    // Usar JavaScript para redirecionar, evitando o erro "headers already sent"
    echo '<script>window.location.href = "' . SITE_URL . '/?route=configuracoes_seo_admin";</script>';
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
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars((string)$meta_description); ?></textarea>
                            <div class="form-text">Descrição que aparece nos resultados de busca. Recomendado: até 160 caracteres.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars((string)$meta_keywords); ?>">
                            <div class="form-text">Palavras-chave separadas por vírgula.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="google_tag_manager_id" class="form-label">ID do Google Tag Manager</label>
                            <input type="text" class="form-control" id="google_tag_manager_id" name="google_tag_manager_id" value="<?php echo htmlspecialchars((string)$google_tag_manager_id); ?>" placeholder="GTM-XXXXXX">
                            <div class="form-text">Formato: GTM-XXXXXX. O código será gerado automaticamente.</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="sitemap_auto_update" name="sitemap_auto_update" value="1" <?php echo $sitemap_auto_update === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sitemap_auto_update">Atualizar sitemap automaticamente</label>
                            <div class="form-text">Se marcado, o sitemap.xml será atualizado sempre que estas configurações forem salvas.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="robots_txt_personalizado" class="form-label">Conteúdo do robots.txt</label>
                            <textarea class="form-control" id="robots_txt_personalizado" name="robots_txt_personalizado" rows="6"><?php echo htmlspecialchars((string)$robots_txt_personalizado); ?></textarea>
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

<!-- Nova seção para Meta Descrições por Página -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Meta Descrições por Página</h6>
            </div>
            <div class="card-body">
                <p class="mb-3">Configure meta descrições específicas para cada página do site. Estas descrições serão usadas tanto na página quanto no sitemap.</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="metaDescTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Página</th>
                                <th>Meta Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Páginas principais do site
                            $paginas = [
                                'home' => 'Página Inicial',
                                'empresas' => 'Empresas',
                                'talentos' => 'Talentos',
                                'demandas' => 'Demandas',
                                'vagas' => 'Vagas',
                                'sobre' => 'Sobre',
                                'contato' => 'Contato',
                                'blog' => 'Blog',
                                'entrar' => 'Login',
                                'cadastrar' => 'Cadastro',
                                'termos' => 'Termos de Uso',
                                'privacidade' => 'Política de Privacidade',
                                'cookies' => 'Política de Cookies',
                                'categoria/carreira' => 'Categoria: Carreira',
                                'categoria/curriculo' => 'Categoria: Currículo',
                                'categoria/entrevistas' => 'Categoria: Entrevistas',
                                'categoria/mercado-de-trabalho' => 'Categoria: Mercado de Trabalho',
                                'categoria/tecnologia' => 'Categoria: Tecnologia'
                            ];
                            
                            // Obter meta descrições existentes
                            $meta_descricoes = [];
                            try {
                                $meta_db = $db->fetchAll("SELECT pagina, descricao FROM meta_descricoes_paginas");
                                foreach ($meta_db as $meta) {
                                    $meta_descricoes[$meta['pagina']] = $meta['descricao'];
                                }
                            } catch (Exception $e) {
                                // Se a tabela não existir, criar
                                $sql = "CREATE TABLE IF NOT EXISTS meta_descricoes_paginas (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    pagina VARCHAR(255) NOT NULL UNIQUE,
                                    descricao TEXT NOT NULL,
                                    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                                )";
                                $db->query($sql);
                            }
                            
                            // Descrições padrão para páginas principais
                            $descricoes_padrao = [
                                'home' => 'OpenToJob - Conectando talentos prontos a oportunidades imediatas. Plataforma de recrutamento e seleção focada em profissionais disponíveis para iniciar imediatamente.',
                                'empresas' => 'Encontre talentos qualificados e disponíveis para iniciar imediatamente. Simplifique seu processo de recrutamento e reduza o tempo de contratação com a OpenToJob.',
                                'talentos' => 'Descubra talentos prontos para iniciar imediatamente. Profissionais qualificados e disponíveis para contratação em diversas áreas e níveis de experiência.',
                                'demandas' => 'Publique suas demandas de contratação e conecte-se com talentos disponíveis imediatamente. Solução eficiente para necessidades urgentes de recrutamento.',
                                'vagas' => 'Encontre vagas de emprego para profissionais disponíveis imediatamente. Oportunidades em diversas áreas e níveis de experiência em todo o Brasil.',
                                'sobre' => 'Conheça a OpenToJob, nossa missão, valores e a equipe por trás da plataforma que está revolucionando o mercado de recrutamento e seleção.',
                                'contato' => 'Entre em contato com a equipe OpenToJob. Estamos prontos para ajudar com dúvidas, sugestões ou parcerias.',
                                'blog' => 'Blog da OpenToJob com artigos, dicas e tendências sobre o mercado de trabalho, recrutamento e seleção.',
                                'entrar' => 'Acesse sua conta na plataforma OpenToJob. Portal de login para talentos e empresas.',
                                'cadastrar' => 'Crie sua conta na OpenToJob. Cadastro gratuito para talentos e empresas que buscam conexões rápidas no mercado de trabalho.',
                                'termos' => 'Termos e condições de uso da plataforma OpenToJob. Informações legais sobre o uso do serviço.',
                                'privacidade' => 'Política de privacidade da OpenToJob. Saiba como protegemos seus dados e informações pessoais.',
                                'cookies' => 'Política de cookies da OpenToJob. Entenda como utilizamos cookies para melhorar sua experiência.',
                                'categoria/carreira' => 'Artigos sobre carreira profissional, desenvolvimento pessoal e crescimento na carreira. Dicas e orientações para impulsionar sua trajetória profissional.',
                                'categoria/curriculo' => 'Dicas e modelos para criar currículos eficientes. Aprenda a destacar suas habilidades e experiências para conseguir mais entrevistas.',
                                'categoria/entrevistas' => 'Preparação para entrevistas de emprego, perguntas comuns e como se destacar. Estratégias para impressionar recrutadores e conquistar a vaga.',
                                'categoria/mercado-de-trabalho' => 'Análises e tendências do mercado de trabalho atual. Informações sobre setores em crescimento, demandas e oportunidades emergentes.',
                                'categoria/tecnologia' => 'Novidades tecnológicas e seu impacto no mercado de trabalho. Habilidades técnicas em demanda e como se manter atualizado no mundo digital.'
                            ];
                            
                            // Exibir formulário para cada página
                            foreach ($paginas as $rota => $titulo) {
                                $descricao = isset($meta_descricoes[$rota]) ? $meta_descricoes[$rota] : ($descricoes_padrao[$rota] ?? '');
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars((string)$titulo) . ' <small class="text-muted">(' . htmlspecialchars((string)$rota) . ')</small></td>';
                                echo '<td>';
                                echo '<textarea class="form-control meta-desc-field" id="meta_desc_' . htmlspecialchars((string)$rota) . '" data-pagina="' . htmlspecialchars((string)$rota) . '" rows="2">' . htmlspecialchars((string)$descricao) . '</textarea>';
                                echo '<small class="text-muted caracteres-count">Caracteres: ' . mb_strlen($descricao) . '/160</small>';
                                echo '</td>';
                                echo '<td>';
                                echo '<button type="button" class="btn btn-sm btn-primary salvar-meta-desc" data-pagina="' . htmlspecialchars((string)$rota) . '">Salvar</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                            <tr>
                                <td colspan="3">
                                    <button type="button" class="btn btn-success" id="adicionar-nova-pagina">
                                        <i class="fas fa-plus"></i> Adicionar Nova Página
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-primary" id="salvar-todas-meta-desc">Salvar Todas as Descrições</button>
                    <button type="button" class="btn btn-secondary" id="gerar-sitemap">Atualizar Sitemap</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para adicionar nova página -->
<div class="modal fade" id="novaPaginaModal" tabindex="-1" aria-labelledby="novaPaginaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="novaPaginaModalLabel">Adicionar Nova Página</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="nova_pagina_rota" class="form-label">Rota da Página</label>
                    <input type="text" class="form-control" id="nova_pagina_rota" placeholder="Ex: nova-pagina">
                    <div class="form-text">URL relativa da página (sem http://localhost/open2w/)</div>
                </div>
                <div class="mb-3">
                    <label for="nova_pagina_titulo" class="form-label">Título da Página</label>
                    <input type="text" class="form-control" id="nova_pagina_titulo" placeholder="Ex: Nova Página">
                </div>
                <div class="mb-3">
                    <label for="nova_pagina_descricao" class="form-label">Meta Descrição</label>
                    <textarea class="form-control" id="nova_pagina_descricao" rows="3"></textarea>
                    <small class="text-muted caracteres-count">Caracteres: 0/160</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="salvar_nova_pagina">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contador de caracteres para todas as descrições
    document.querySelectorAll('.meta-desc-field').forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            var count = this.value.length;
            var countElement = this.nextElementSibling;
            countElement.textContent = "Caracteres: " + count + "/160";
            
            if (count > 160) {
                countElement.classList.add('text-danger');
            } else {
                countElement.classList.remove('text-danger');
            }
        });
    });
    
    // Salvar meta descrição individual
    document.querySelectorAll('.salvar-meta-desc').forEach(function(button) {
        button.addEventListener('click', function() {
            var pagina = this.getAttribute('data-pagina');
            var descricao = document.getElementById('meta_desc_' + pagina).value;
            
            salvarMetaDescricao(pagina, descricao, button);
        });
    });
    
    // Salvar todas as meta descrições
    document.getElementById('salvar-todas-meta-desc').addEventListener('click', function() {
        var button = this;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
        
        var metaDescs = [];
        document.querySelectorAll('.meta-desc-field').forEach(function(textarea) {
            metaDescs.push({
                pagina: textarea.getAttribute('data-pagina'),
                descricao: textarea.value
            });
        });
        
        fetch('<?php echo SITE_URL; ?>/?route=api_salvar_meta_descricoes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ meta_descricoes: metaDescs })
        })
        .then(function(response) {
            // Primeiro verificamos o status da resposta
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            
            // Tentamos converter para texto primeiro para ver o que o servidor está retornando
            return response.text().then(function(text) {
                console.log("Resposta do servidor:", text);
                try {
                    // Tentamos converter o texto para JSON
                    return JSON.parse(text);
                } catch (e) {
                    // Se não for um JSON válido, mostramos o texto recebido
                    console.error("Resposta não é um JSON válido:", text);
                    throw new Error('Resposta do servidor não é um JSON válido');
                }
            });
        })
        .then(function(data) {
            if (data && data.success) {
                var message = 'Todas as meta descrições foram salvas com sucesso!';
                if (data.count) {
                    message += ' (' + data.count + ' descrições processadas)';
                }
                
                // Mostrar toast de sucesso
                var toast = document.createElement('div');
                toast.className = 'toast position-fixed bottom-0 end-0 m-3 bg-success text-white';
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                toast.innerHTML = `
                    <div class="toast-header bg-success text-white">
                        <strong class="me-auto">Sucesso</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                `;
                document.body.appendChild(toast);
                var bsToast = new bootstrap.Toast(toast, { delay: 3000 });
                bsToast.show();
                
                // Remover o toast do DOM depois que ele desaparecer
                toast.addEventListener('hidden.bs.toast', function() {
                    document.body.removeChild(toast);
                });
                
                // Atualizar a interface para mostrar que tudo foi salvo
                document.querySelectorAll('.salvar-meta-desc').forEach(function(btn) {
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(function() {
                        btn.innerHTML = 'Salvar';
                        btn.disabled = false;
                    }, 1000);
                });
            } else {
                throw new Error(data && data.message ? data.message : 'Erro desconhecido');
            }
        })
        .catch(function(error) {
            console.error('Erro ao salvar todas as meta descrições:', error);
            
            // Mostrar toast de erro
            var toast = document.createElement('div');
            toast.className = 'toast position-fixed bottom-0 end-0 m-3 bg-danger text-white';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Erro</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    Erro ao salvar meta descrições: ${error.message}
                </div>
            `;
            document.body.appendChild(toast);
            var bsToast = new bootstrap.Toast(toast, { delay: 5000 });
            bsToast.show();
            
            // Remover o toast do DOM depois que ele desaparecer
            toast.addEventListener('hidden.bs.toast', function() {
                document.body.removeChild(toast);
            });
        })
        .finally(function() {
            button.disabled = false;
            button.innerHTML = 'Salvar Todas as Descrições';
        });
    });
    
    // Gerar sitemap
    document.getElementById('gerar-sitemap').addEventListener('click', function() {
        var button = this;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando...';
        
        // Abrir o gerador de sitemap em uma nova aba
        var sitemapWindow = window.open('<?php echo SITE_URL; ?>/sitemap_generator.php?save=1&t=' + new Date().getTime(), '_blank');
        
        // Verificar se a janela foi aberta com sucesso
        if (sitemapWindow) {
            setTimeout(function() {
                alert('Sitemap gerado com sucesso!');
                button.disabled = false;
                button.innerHTML = 'Atualizar Sitemap';
            }, 2000);
        } else {
            alert('O navegador bloqueou a abertura da janela. O sitemap pode ter sido gerado, mas não foi possível visualizá-lo.');
            button.disabled = false;
            button.innerHTML = 'Atualizar Sitemap';
        }
    });
    
    // Abrir modal para adicionar nova página
    document.getElementById('adicionar-nova-pagina').addEventListener('click', function() {
        var modal = new bootstrap.Modal(document.getElementById('novaPaginaModal'));
        modal.show();
    });
    
    // Contador de caracteres para nova descrição
    document.getElementById('nova_pagina_descricao').addEventListener('input', function() {
        var count = this.value.length;
        var countElement = this.nextElementSibling;
        countElement.textContent = "Caracteres: " + count + "/160";
        
        if (count > 160) {
            countElement.classList.add('text-danger');
        } else {
            countElement.classList.remove('text-danger');
        }
    });
    
    // Salvar nova página
    document.getElementById('salvar_nova_pagina').addEventListener('click', function() {
        var rota = document.getElementById('nova_pagina_rota').value.trim();
        var titulo = document.getElementById('nova_pagina_titulo').value.trim();
        var descricao = document.getElementById('nova_pagina_descricao').value.trim();
        
        if (!rota || !titulo || !descricao) {
            alert('Todos os campos são obrigatórios.');
            return;
        }
        
        // Adicionar à tabela
        var tabela = document.querySelector('#metaDescTable tbody');
        var novaLinha = document.createElement('tr');
        
        novaLinha.innerHTML = '<td>' + titulo + ' <small class="text-muted">(' + rota + ')</small></td>' +
            '<td>' +
            '<textarea class="form-control meta-desc-field" id="meta_desc_' + rota + '" data-pagina="' + rota + '" rows="2">' + descricao + '</textarea>' +
            '<small class="text-muted caracteres-count">Caracteres: ' + descricao.length + '/160</small>' +
            '</td>' +
            '<td>' +
            '<button type="button" class="btn btn-sm btn-primary salvar-meta-desc" data-pagina="' + rota + '">Salvar</button>' +
            '</td>';
        
        // Inserir antes do botão "Adicionar Nova Página"
        tabela.insertBefore(novaLinha, tabela.lastElementChild);
        
        // Adicionar eventos à nova linha
        var textarea = novaLinha.querySelector('.meta-desc-field');
        textarea.addEventListener('input', function() {
            var count = this.value.length;
            var countElement = this.nextElementSibling;
            countElement.textContent = "Caracteres: " + count + "/160";
            
            if (count > 160) {
                countElement.classList.add('text-danger');
            } else {
                countElement.classList.remove('text-danger');
            }
        });
        
        var button = novaLinha.querySelector('.salvar-meta-desc');
        button.addEventListener('click', function() {
            var pagina = this.getAttribute('data-pagina');
            var descricao = document.getElementById('meta_desc_' + pagina).value;
            
            salvarMetaDescricao(pagina, descricao, button);
        });
        
        // Salvar no banco de dados
        salvarMetaDescricao(rota, descricao);
        
        // Fechar modal
        bootstrap.Modal.getInstance(document.getElementById('novaPaginaModal')).hide();
        
        // Limpar campos
        document.getElementById('nova_pagina_rota').value = '';
        document.getElementById('nova_pagina_titulo').value = '';
        document.getElementById('nova_pagina_descricao').value = '';
    });
    
    // Função para salvar meta descrição
    function salvarMetaDescricao(pagina, descricao, button) {
        if (button) {
            var originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }
        
        fetch('<?php echo SITE_URL; ?>/?route=api_salvar_meta_descricao', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ pagina: pagina, descricao: descricao })
        })
        .then(function(response) {
            // Primeiro verificamos o status da resposta
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            
            // Tentamos converter para texto primeiro para ver o que o servidor está retornando
            return response.text().then(function(text) {
                console.log("Resposta do servidor para " + pagina + ":", text);
                try {
                    // Tentamos converter o texto para JSON
                    return JSON.parse(text);
                } catch (e) {
                    // Se não for um JSON válido, mostramos o texto recebido
                    console.error("Resposta não é um JSON válido:", text);
                    throw new Error('Resposta do servidor não é um JSON válido');
                }
            });
        })
        .then(function(data) {
            if (data && data.success) {
                if (button) {
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(function() {
                        button.innerHTML = originalText || 'Salvar';
                        button.disabled = false;
                    }, 1000);
                }
                
                // Mostrar mensagem de sucesso apenas se não estiver salvando todas as descrições
                if (!document.getElementById('salvar-todas-meta-desc').disabled) {
                    var toast = document.createElement('div');
                    toast.className = 'toast position-fixed bottom-0 end-0 m-3 bg-success text-white';
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');
                    toast.innerHTML = `
                        <div class="toast-header bg-success text-white">
                            <strong class="me-auto">Sucesso</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Meta descrição para "${pagina}" salva com sucesso!
                        </div>
                    `;
                    document.body.appendChild(toast);
                    var bsToast = new bootstrap.Toast(toast, { delay: 3000 });
                    bsToast.show();
                    
                    // Remover o toast do DOM depois que ele desaparecer
                    toast.addEventListener('hidden.bs.toast', function() {
                        document.body.removeChild(toast);
                    });
                }
            } else {
                throw new Error(data && data.message ? data.message : 'Erro desconhecido');
            }
        })
        .catch(function(error) {
            console.error('Erro ao salvar meta descrição para ' + pagina + ':', error);
            
            if (button) {
                button.innerHTML = originalText || 'Salvar';
                button.disabled = false;
            }
            
            // Mostrar mensagem de erro
            var toast = document.createElement('div');
            toast.className = 'toast position-fixed bottom-0 end-0 m-3 bg-danger text-white';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Erro</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    Erro ao salvar meta descrição para "${pagina}": ${error.message}
                </div>
            `;
            document.body.appendChild(toast);
            var bsToast = new bootstrap.Toast(toast, { delay: 5000 });
            bsToast.show();
            
            // Remover o toast do DOM depois que ele desaparecer
            toast.addEventListener('hidden.bs.toast', function() {
                document.body.removeChild(toast);
            });
        });
    }
});
</script>
